<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

class SdlApiClient
{
    private array $config;
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (is_file(APP_ROOT . '/app/config/sdl.local.php') ? require APP_ROOT . '/app/config/sdl.local.php' : []);
    }
    public function enabled(): bool { return !empty($this->config['enabled']) && !empty($this->config['token']); }
    public function cacheNamespace(): string
    {
        return hash('sha256', ($this->config['base_url'] ?? '') . '|' . ($this->config['token'] ?? ''));
    }
    public function withTimeout(int $seconds): self
    {
        $client = clone $this;
        $client->config['timeout'] = max(1, min(90, $seconds));
        return $client;
    }
    public function request(string $path, ?array $body = null): array
    {
        if (!$this->enabled()) throw new RuntimeException('ยังไม่ได้ตั้งค่าการเชื่อมต่อ SDL_school');
        $base = rtrim($this->config['base_url'], '/');
        $host = parse_url($base, PHP_URL_HOST);
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('การเชื่อมต่อ SDL_school ต้องใช้ HTTPS');
        }
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $this->config['token'], 'X-Student-Data-Token: ' . $this->config['token']];
        $http = ['method' => $body === null ? 'GET' : 'POST', 'timeout' => $this->config['timeout'] ?? 90,
            'ignore_errors' => true, 'follow_location' => 0];
        if ($body !== null) {
            $http['content'] = json_encode($body, JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
        }
        $requestUrl = $base . '/' . ltrim($path, '/');
        $resolvedIp = $this->config['resolve_ip'] ?? null;
        if ($resolvedIp && filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Avoid macOS's fork-unsafe DNS resolver in MAMP FastCGI workers.
            // Host routing, TLS SNI and certificate validation still use the real domain.
            $headers[] = 'Host: ' . $host;
            $requestUrl = preg_replace('#^(https?://)[^/]+#', '${1}' . $resolvedIp, $requestUrl);
        }
        $http['header'] = implode("\r\n", $headers);
        // Native OpenSSL streams also work in MAMP FastCGI on macOS, where
        // SecureTransport/libcurl can crash after FastCGI forks a worker.
        $context = stream_context_create(['http' => $http,
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true]]);
        $response = @file_get_contents($requestUrl, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) $status = (int)$matches[1];
        }
        if ($status === 401 && $body !== null) return ['data' => null];
        if ($response === false || $status !== 200) throw new RuntimeException('เชื่อมต่อ SDL_school ไม่สำเร็จ (HTTP ' . $status . ') กรุณาตรวจสอบ API และอายุ token');
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($result) || !array_key_exists('data', $result)) throw new RuntimeException('รูปแบบข้อมูล SDL_school ไม่ถูกต้อง');
        return $result;
    }
    public function all(string $resource): array
    {
        $rows = [];
        for ($page = 1; $page <= 10000; $page++) {
            $result = $this->request($resource . '?per_page=100&page=' . $page);
            if (!is_array($result['data'])) throw new RuntimeException('ข้อมูล API ไม่ถูกต้อง');
            $rows = array_merge($rows, $result['data']);
            $last = (int)($result['meta']['pagination']['last_page'] ?? 1);
            if ($page >= $last) return $rows;
        }
        throw new RuntimeException('ข้อมูล API มีจำนวนหน้าเกินขอบเขต');
    }

    /** Paginate a resource with extra query parameters (e.g. graduates?term=1/2569). */
    public function allWithQuery(string $resource, array $params = []): array
    {
        $rows = [];
        for ($page = 1; $page <= 10000; $page++) {
            $query = http_build_query($params + ['per_page' => 100, 'page' => $page]);
            $result = $this->request($resource . '?' . $query);
            if (!is_array($result['data'])) throw new RuntimeException('ข้อมูล API ไม่ถูกต้อง');
            $rows = array_merge($rows, $result['data']);
            $last = (int)($result['meta']['pagination']['last_page'] ?? 1);
            if ($page >= $last) return $rows;
        }
        throw new RuntimeException('ข้อมูล API มีจำนวนหน้าเกินขอบเขต');
    }
}
