<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/** Read-only adapter for the existing local SDL MySQL schema. */
final class SdlLegacyGateway
{
    private array $config;
    private int $districtId;
    private $app;
    private $db;
    private $request;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require APP_ROOT . '/app/config/sdl.local.php';
        $this->districtId = (int)($this->config['legacy_district_id'] ?? 1);
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        header('Vary: Authorization, X-Student-Data-Token');
        try {
            if (($this->config['mode'] ?? '') !== 'local') {
                $this->respond(['message' => 'ไม่พบ API'], 404); return;
            }
            if (empty($this->config['enabled']) || empty($this->config['token'])) {
                $this->respond(['message' => 'ยังไม่ได้เปิดการเชื่อมต่อ SDL_school'], 503); return;
            }
            if (!empty($_SERVER['HTTP_ORIGIN'])) {
                $this->respond(['message' => 'รองรับ backend server-to-server เท่านั้น'], 403); return;
            }
            $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) ? trim($matches[1]) : trim($_SERVER['HTTP_X_STUDENT_DATA_TOKEN'] ?? '');
            if (!hash_equals((string)$this->config['token'], $token)) {
                $this->respond(['message' => 'ข้อมูลยืนยันตัวตนไม่ถูกต้อง'], 401); return;
            }
            if (!empty($this->config['token_expires_at']) && strtotime($this->config['token_expires_at']) <= time()) {
                $this->respond(['message' => 'ข้อมูลยืนยันตัวตนไม่ถูกต้อง'], 401); return;
            }
            if (isset($_GET['district_id']) || isset($_SERVER['HTTP_X_DISTRICT_ID'])) {
                $this->respond(['message' => 'อำเภอกำหนดจากการตั้งค่า backend เท่านั้น'], 422); return;
            }
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
            $prefix = '/api/v1/integrations/student-data/';
            $position = strpos($path, $prefix);
            $route = $position === false ? '' : substr($path, $position + strlen($prefix));
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $allowed = ['students' => 'GET', 'teachers' => 'GET', 'groups' => 'GET', 'teacher-authenticate' => 'POST'];
            if (!isset($allowed[$route])) { $this->respond(['message' => 'ไม่พบ API'], 404); return; }
            if ($allowed[$route] !== $method) { $this->respond(['message' => 'HTTP method ไม่ถูกต้อง'], 405); return; }
            $this->bootstrap();
            $tokenKey = 'sdl-gateway-token:'.hash('sha256', $token);
            if (!$this->allowAttempt($tokenKey, 60)) return;
            \Illuminate\Support\Facades\RateLimiter::hit($tokenKey, 60);
            if (!$this->db->table('districts')->where('id', $this->districtId)->where('is_active', 1)->exists()) {
                $this->respond(['message' => 'อำเภอไม่เปิดใช้งาน'], 403); return;
            }
            $data = match ($route) {
                'students' => $this->students(),
                'teachers' => $this->teachers(),
                'groups' => $this->groups(),
                'teacher-authenticate' => $this->authenticate(),
            };
            if ($data !== null) $this->respond($data);
        } catch (\Illuminate\Validation\ValidationException $error) {
            $this->respond(['message' => 'ข้อมูลคำขอไม่ถูกต้อง', 'errors' => $error->errors()], 422);
        } catch (\JsonException $error) {
            $this->respond(['message' => 'รูปแบบ JSON ไม่ถูกต้อง'], 422);
        } catch (Throwable $error) {
            // Never return SQL, credentials, or submitted passwords to the caller.
            $this->respond(['message' => 'ไม่สามารถอ่านข้อมูล SDL_school ได้ในขณะนี้'], 503);
        }
    }

    private function bootstrap(): void
    {
        $root = $this->config['legacy_laravel_root'] ?? dirname(APP_ROOT).'/SDL_school/laravel-app';
        if (!is_file($root.'/vendor/autoload.php')) throw new RuntimeException('SDL runtime unavailable');
        $connection = $this->config['legacy_connection'] ?? [];
        if (empty($connection['database']) || empty($connection['username'])) throw new RuntimeException('Source connection not configured');
        require_once $root.'/vendor/autoload.php';
        $this->app = require $root.'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $cachePath = APP_ROOT.'/storage/cache/sdl-gateway';
        if (!is_dir($cachePath) && !mkdir($cachePath, 0700, true) && !is_dir($cachePath)) throw new RuntimeException('Cache unavailable');
        config([
            'app.debug' => false,
            'database.default' => 'sdl_care_read',
            'database.connections.sdl_care_read' => array_merge(config('database.connections.mysql'), $connection, [
                'driver' => 'mysql', 'url' => null, 'unix_socket' => '', 'read' => null, 'write' => null,
            ]),
            'cache.default' => 'file',
            'cache.stores.file.path' => $cachePath,
            'cache.stores.file.lock_path' => $cachePath,
            'cache.prefix' => 'sena_care_sdl_gateway',
            'system_data.enabled' => true,
            'system_data.student_enabled' => true,
        ]);
        \Illuminate\Support\Facades\DB::purge('sdl_care_read');
        $this->db = \Illuminate\Support\Facades\DB::connection('sdl_care_read');
        $this->db->statement('SET SESSION TRANSACTION READ ONLY');
        $this->request = \Illuminate\Http\Request::capture();
        $this->app->instance('request', $this->request);
    }

    private function pagination(): array
    {
        return \Illuminate\Support\Facades\Validator::make($_GET, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ])->validate();
    }

    private function students(): array
    {
        $filters = $this->pagination();
        $viewer = new \App\Models\User(['id' => 0, 'role' => 'admin', 'district_id' => $this->districtId, 'assigned_groups' => []]);
        $viewer->setRelation('selectedDistrictContext', $this->districtId);
        $result = $this->app->make(\App\Domain\Students\Services\StudentDirectoryService::class)
            ->paginate($viewer, $filters, includeCitizenIdInSearch: false, clampPage: false);
        return [
            'data' => array_map(fn ($student) => (new \App\Http\Resources\Students\StudentDataIntegrationResource($student))->resolve($this->request), $result['items']),
            'meta' => ['pagination' => $result['meta']['pagination'], 'source' => 'local_sdl_mysql_snapshot'],
        ];
    }

    private function teacherQuery()
    {
        $query = $this->db->table('users')->where('district_id', $this->districtId)->where('role', 'teacher');
        if ($this->db->getSchemaBuilder()->hasColumn('users', 'disabled_at')) $query->whereNull('disabled_at');
        return $query;
    }

    private function teacherPayload(object $teacher): array
    {
        $name = trim(($teacher->first_name ?? '').' '.($teacher->last_name ?? ''));
        $assigned = is_string($teacher->assigned_groups) ? json_decode($teacher->assigned_groups, true, 512, JSON_THROW_ON_ERROR) : [];
        return [
            'id' => (int)$teacher->id, 'username' => $teacher->username, 'email' => $teacher->email ?? null,
            'name' => $name ?: $teacher->username, 'display_name' => $name ?: $teacher->username,
            'assigned_groups' => is_array($assigned) ? array_values($assigned) : [], 'district_id' => (int)$teacher->district_id,
        ];
    }

    private function teacherColumns(): array
    {
        $columns = ['id', 'username', 'first_name', 'last_name', 'assigned_groups', 'district_id'];
        if ($this->db->getSchemaBuilder()->hasColumn('users', 'email')) $columns[] = 'email';
        return $columns;
    }

    private function teachers(): array
    {
        $filters = $this->pagination(); $page = (int)($filters['page'] ?? 1); $perPage = (int)($filters['per_page'] ?? 100);
        $query = $this->teacherQuery(); $total = $query->count();
        $teachers = $query->orderBy('id')->offset(($page - 1) * $perPage)->limit($perPage)->get($this->teacherColumns());
        return ['data' => $teachers->map(fn ($teacher) => $this->teacherPayload($teacher))->all(), 'meta' => ['pagination' => [
            'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int)ceil($total/$perPage)),
        ]]];
    }

    private function authenticate(): ?array
    {
        $raw = file_get_contents('php://input');
        if (strlen($raw) > 4096) { $this->respond(['message' => 'คำขอมีขนาดใหญ่เกินไป'], 413); return null; }
        $input = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $credentials = \Illuminate\Support\Facades\Validator::make(is_array($input) ? $input : [], [
            'identifier' => ['required', 'string', 'max:190'], 'password' => ['required', 'string', 'max:200'],
        ])->validate();
        $identifier = trim($credentials['identifier']);
        $identityKey = 'sdl-gateway-login:'.hash('sha256', $this->districtId.'|'.mb_strtolower($identifier));
        $ipKey = 'sdl-gateway-ip:'.hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!$this->allowAttempt($identityKey, 5) || !$this->allowAttempt($ipKey, 60)) return null;
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) && $this->db->getSchemaBuilder()->hasColumn('users', 'email') ? 'email' : 'username';
        $teacher = $this->teacherQuery()->where($field, $identifier)->first([...$this->teacherColumns(), 'password']);
        $hash = $teacher->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        try { $valid = \Illuminate\Support\Facades\Hash::check($credentials['password'], $hash); } catch (RuntimeException $e) { $valid = false; }
        if (!$teacher || !$valid) {
            \Illuminate\Support\Facades\RateLimiter::hit($identityKey, 60);
            \Illuminate\Support\Facades\RateLimiter::hit($ipKey, 60);
            $this->respond(['message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401); return null;
        }
        \Illuminate\Support\Facades\RateLimiter::clear($identityKey);
        return ['data' => $this->teacherPayload($teacher)];
    }

    private function groups(): array
    {
        $batch = $this->db->selectOne("SELECT ib.batch_key FROM import_batches ib INNER JOIN import_history ih ON ih.id=ib.import_history_id AND BINARY ih.batch_key=BINARY ib.batch_key AND ih.district_id=ib.district_id WHERE ih.status='success' AND ib.district_id=? ORDER BY COALESCE(ib.created_at,ih.created_at) DESC,ib.batch_key DESC LIMIT 1", [$this->districtId]);
        $key = $batch->batch_key ?? '';
        if (!preg_match('/^import_\d{10}_[A-Za-z0-9]+$/', $key)) return ['data' => []];
        $groups = [];
        foreach ($this->db->getSchemaBuilder()->getTableListing(null, false) as $table) {
            if (!preg_match('/^db_'.preg_quote($key, '/').'_[A-Za-z0-9]+_group$/', $table)) continue;
            foreach ($this->db->table($table)->get(['grp_code', 'grp_name', 'grp_class']) as $row) {
                $code = trim((string)$row->grp_code); if ($code === '') continue;
                $name = trim((string)$row->grp_name) ?: $code;
                $level = match(trim((string)$row->grp_class)) { '1' => 'ประถมศึกษา', '2' => 'มัธยมศึกษาตอนต้น', '3' => 'มัธยมศึกษาตอนปลาย', default => null };
                $groups[$code] = ['code' => $code, 'name' => $name, 'label' => $level ? $level.' · '.$name : $name, 'level' => $level];
            }
        }
        uasort($groups, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return ['data' => array_values($groups)];
    }

    private function allowAttempt(string $key, int $limit): bool
    {
        if (!\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $limit)) return true;
        header('Retry-After: '.\Illuminate\Support\Facades\RateLimiter::availableIn($key));
        $this->respond(['message' => 'เรียก API บ่อยเกินไป กรุณาลองใหม่ภายหลัง'], 429); return false;
    }

    private function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
