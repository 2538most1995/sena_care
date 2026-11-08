<?php
// Uses a fake upstream and a disposable private directory; no database writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/SdlApiClient.php';
require APP_ROOT . '/app/services/SdlStudentPhone.php';
require APP_ROOT . '/app/helpers/response.php';

class PhoneFixtureApi extends SdlApiClient
{
    public int $calls = 0;
    public bool $outage = false;
    public array $source = ['code' => 'TEST001', 'level' => ['id' => 2], 'contact' => ['phone' => '0891234567', 'citizen_id' => 'EXCLUDED'], 'demographics' => ['citizen_id' => 'EXCLUDED']];
    public string $lastPath = '';
    public function request(string $path, ?array $body = null): array
    {
        $this->calls++;
        $this->lastPath = $path;
        if ($this->outage) throw new RuntimeException('fixture outage');
        return ['data' => $this->source];
    }
}

$checks = 0;
function phoneCheck(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
$dir = sys_get_temp_dir() . '/sena-phone-test-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
$api = new PhoneFixtureApi(['base_url' => 'https://fixture.test', 'token' => 'fixture-a']);
$service = new SdlStudentPhone($api, $dir);
try {
    phoneCheck($service->get('TEST001', 2) === '0891234567', 'Profile contact phone is read');
    phoneCheck($api->lastPath === 'students/TEST001/profile?level=2', 'Code and education level are sent');
    phoneCheck(formatPhone($service->get('TEST001', 2)) === '089-123-4567', 'Leading zero and display formatting are preserved');
    phoneCheck($api->calls === 1, 'A fresh cache avoids repeated requests');
    $file = glob($dir . '/*.json')[0];
    $cached = json_decode(file_get_contents($file), true);
    phoneCheck(array_keys($cached) === ['phone', 'expires_at'], 'Only phone and expiry are cached');
    phoneCheck(!str_contains(file_get_contents($file), 'EXCLUDED'), 'Personal identifiers are excluded');
    phoneCheck((fileperms($file) & 0777) === 0600, 'Phone cache is private');
    $api->outage = true;
    phoneCheck($service->get('TEST001', 2) === '0891234567', 'Fresh cache works during an outage');
    $cached['expires_at'] = 0;
    file_put_contents($file, json_encode($cached));
    try { $service->get('TEST001', 2); throw new LogicException('Expected outage'); }
    catch (RuntimeException $e) { phoneCheck($e->getMessage() === 'fixture outage', 'Expired cache does not hide an upstream failure'); }
    phoneCheck(json_decode(file_get_contents($file), true)['expires_at'] === 0, 'Failure does not poison the cache');
    $api->outage = false;
    $api->source['contact']['phone'] = '0817654321';
    phoneCheck($service->get('TEST001', 2) === '0817654321', 'An expired phone refreshes from the API');

    $other = new PhoneFixtureApi(['base_url' => 'https://fixture.test', 'token' => 'fixture-b']);
    $other->source['contact']['phone'] = null;
    $otherService = new SdlStudentPhone($other, $dir);
    phoneCheck($otherService->get('TEST001', 2) === null && $other->calls === 1, 'Different credentials never reuse another source cache');
    phoneCheck($otherService->get('TEST001', 2) === null && $other->calls === 1, 'A missing source phone is cached too');
    $before = count(glob($dir . '/*.json'));
    try { $service->get('OTHER', 2); throw new LogicException('Expected identity mismatch'); }
    catch (RuntimeException $e) { phoneCheck(str_contains($e->getMessage(), 'ไม่ตรง'), 'Another student response is rejected'); }
    try { $service->get('TEST001', 3); throw new LogicException('Expected level mismatch'); }
    catch (RuntimeException $e) { phoneCheck(str_contains($e->getMessage(), 'ไม่ตรง'), 'Another education level response is rejected'); }
    phoneCheck(count(glob($dir . '/*.json')) === $before, 'Mismatched responses are never cached');
    echo "Passed {$checks} phone checks; no database writes.\n";
} finally {
    foreach (glob($dir . '/*') as $file) unlink($file);
    rmdir($dir);
}
