<?php
// Isolated MySQL tests: never write to the application's database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/SdlApiClient.php';
require APP_ROOT . '/app/services/GraduateSyncService.php';
require APP_ROOT . '/app/models/Graduate.php';
$cfg = require APP_ROOT . '/app/config/config.php';
$server = new PDO("mysql:host={$cfg['db_host']};port={$cfg['db_port']};charset=utf8mb4", $cfg['db_user'], $cfg['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'sena_graduates_test_' . bin2hex(random_bytes(5));
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
class GraduateFixtureApi extends SdlApiClient
{
    public array $terms = ['1/2569', '2/2568', '1/2564', '2/2563'];
    public array $requests = [];
    public bool $outage = false;
    public bool $wrongTerm = false;
    public bool $duplicateCode = false;
    public function enabled(): bool { return true; }
    public function request(string $path, ?array $body = null): array
    {
        $this->requests[] = $path;
        parse_str(parse_url($path, PHP_URL_QUERY) ?? '', $query);
        if (!isset($query['term'])) return ['data' => [], 'meta' => ['terms' => $this->terms, 'selected_term' => '1/2569']];
        $term = $query['term'];
        $page = (int)($query['page'] ?? 1);
        if ($this->outage && $term === '1/2569' && $page === 2) throw new RuntimeException('fixture outage');
        $code = 'T' . str_replace('/', '', $term) . $page;
        if ($this->duplicateCode) $code = 'DUPLICATE';
        return ['data' => [[
            'code' => $code,
            'name' => ['full_name' => 'ผู้สำเร็จการศึกษาทดสอบ'],
            'level' => ['id' => 2, 'label' => 'มัธยมศึกษาตอนต้น'],
            'group' => ['code' => '220001', 'name' => 'กลุ่มทดสอบ'],
            'graduation_term' => $this->wrongTerm ? '2/2563' : $term,
        ]], 'meta' => ['pagination' => ['last_page' => 2]]];
    }
}
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname=$name;charset=utf8mb4", $cfg['db_user'], $cfg['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->exec('CREATE TABLE learning_centers (id INT UNSIGNED PRIMARY KEY, center_code VARCHAR(30)) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE users (id INT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
    $pdo->exec(file_get_contents(APP_ROOT . '/database/graduates.sql'));
    $pdo->exec("INSERT INTO learning_centers VALUES (1, '0001')");
    $pdo->exec('INSERT INTO users VALUES (1)');
    $api = new GraduateFixtureApi([]);
    $sync = new GraduateSyncService($api, $pdo);
    $summary = $sync->syncHistory();
    check($summary['graduates'] === 6, 'Every page of every eligible term imported');
    check(array_keys($summary['terms']) === ['1/2564', '2/2568', '1/2569'], 'Source terms sorted chronologically, before 1/2564 excluded');
    check($summary['term'] === '1/2569', 'Latest source term reported');
    check((int)$pdo->query('SELECT COUNT(*) FROM graduates WHERE learning_center_id=1')->fetchColumn() === 6, 'Historical groups mapped to centers');
    $id = (int)$pdo->query('SELECT MIN(id) FROM graduates')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO graduate_trackings(graduate_id,categories,other_detail,tracked_by) VALUES(?,?,?,?)');
    $stmt->execute([$id, '["study"]', 'ประวัติเดิม', 1]);
    $sync->syncHistory();
    check((int)$pdo->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 6, 'Repeated sync does not duplicate graduates');
    check((int)$pdo->query('SELECT graduate_id FROM graduate_trackings')->fetchColumn() === $id, 'Tracking remains linked to the same graduate ID');
    check($pdo->query('SELECT other_detail FROM graduate_trackings')->fetchColumn() === 'ประวัติเดิม', 'Tracking content preserved');
    $snapshot = $pdo->query('SELECT * FROM graduates ORDER BY id')->fetchAll();
    $api->terms[] = '2/2569';
    $api->outage = true;
    try { $sync->syncHistory(); throw new LogicException('Expected API outage'); }
    catch (RuntimeException $e) { check($e->getMessage() === 'fixture outage', 'Later-page API failure surfaced'); }
    check($pdo->query('SELECT * FROM graduates ORDER BY id')->fetchAll() === $snapshot, 'API failure leaves all destination rows unchanged');
    $api->outage = false;
    $summary = $sync->syncHistory();
    check($summary['term'] === '2/2569' && $summary['graduates'] === 8, 'New latest term automatically included');
    // Source discovery, import, and the real dropdown query must survive a year rollover.
    // Deliberately keep selected_term at 1/2569 and supply new terms out of order.
    array_unshift($api->terms, '2/2570', '1/2570');
    $summary = $sync->syncHistory();
    check($summary['term'] === '2/2570' && $summary['graduates'] === 12, 'New academic year discovered from API without a fixed ending term');
    $model = new Graduate($pdo);
    check(array_slice($model->terms(), 0, 3) === ['2/2570', '1/2570', '2/2569'], 'Dropdown automatically includes new terms in newest-first order');
    check($model->getAll(['term' => '1/2570'], 1, 20)['total'] === 2, 'New-year graduates filter correctly');
    $sync->syncHistory();
    check((int)$pdo->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 12, 'Repeated sync after a new year creates no duplicates');
    check((int)$pdo->query('SELECT graduate_id FROM graduate_trackings')->fetchColumn() === $id, 'Historical tracking remains linked after future-term imports');
    $api->wrongTerm = true;
    try { $sync->syncHistory(); throw new LogicException('Expected term mismatch rejection'); }
    catch (RuntimeException $e) { check(str_contains($e->getMessage(), 'ไม่ตรง'), 'Unexpected API fallback term rejected'); }
    $api->wrongTerm = false;
    $api->duplicateCode = true;
    try { $sync->syncHistory(); throw new LogicException('Expected duplicate rejection'); }
    catch (RuntimeException $e) { check(str_contains($e->getMessage(), 'ซ้ำข้าม'), 'Duplicate codes across terms rejected before writes'); }
    $api->duplicateCode = false;
    $summary = $sync->sync('1/2564');
    check($summary['term'] === '1/2564' && $summary['graduates'] === 2, 'Single-term sync remains supported');
    echo "Passed $checks checks (isolated database)." . PHP_EOL;
} finally {
    $pdo = null;
    $server->exec("DROP DATABASE IF EXISTS `$name`");
}
