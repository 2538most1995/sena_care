<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

require_once APP_ROOT . '/app/services/SdlStudentProfile.php';

/** Cache only the allowlisted phone, never the personal-data API response. */
class SdlStudentPhone
{
    public function __construct(private SdlApiClient $api, private ?string $cacheDir = null)
    {
        $this->cacheDir ??= APP_ROOT . '/storage/cache/student-phones';
    }

    public function get(string $code, ?int $level): ?string
    {
        $key = hash('sha256', $this->api->cacheNamespace() . '|' . $code . '|' . $level);
        $path = $this->cacheDir . '/' . $key . '.json';
        $cached = is_file($path) ? json_decode((string)@file_get_contents($path), true) : null;
        if (is_array($cached) && (int)($cached['expires_at'] ?? 0) > time()
            && array_key_exists('phone', $cached) && (is_string($cached['phone']) || $cached['phone'] === null)) {
            return $cached['phone'];
        }

        $query = $level ? '?level=' . $level : '';
        $response = $this->api->request('students/' . rawurlencode($code) . '/profile' . $query);
        $source = $response['data'] ?? null;
        if (!is_array($source) || (string)($source['code'] ?? '') !== $code
            || ($level && (int)($source['level']['id'] ?? 0) !== $level)) {
            throw new RuntimeException('ข้อมูลเบอร์โทรจาก SDL_school ไม่ตรงกับผู้เรียน');
        }
        $phone = SdlStudentProfile::fields($source)['phone'] ?? null;

        if (is_dir($this->cacheDir) || @mkdir($this->cacheDir, 0700, true)) {
            $temp = @tempnam($this->cacheDir, 'phone-');
            if ($temp !== false) {
                @chmod($temp, 0600);
                if (@file_put_contents($temp, json_encode(['phone' => $phone, 'expires_at' => time() + 300]), LOCK_EX) !== false) {
                    @rename($temp, $path);
                }
                if (is_file($temp)) @unlink($temp);
            }
        }
        return $phone;
    }
}
