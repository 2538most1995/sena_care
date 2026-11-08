<?php
// CLI-only installation. Back up the exact source file before changing it.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = realpath($argv[1] ?? '');
$relative = '/laravel-app/app/Http/Controllers/Api/Integrations/StudentDataController.php';
if (!$root || !is_file($root . $relative)) { fwrite(STDERR, "Usage: php install.php /absolute/path/to/SDL_school\n"); exit(1); }
$file = $root . $relative;
$original = file_get_contents($file);
if (str_contains($original, "$" . "request->boolean('include_personal')")) { echo "Already installed.\n"; exit; }
$before = <<<'BEFORE'
        return $this->respond([
            'data' => (new StudentDataIntegrationResource($record))->resolve($request),
            'meta' => $this->integrationMeta(),
        ]);
BEFORE;
if (substr_count($original, $before) !== 1) throw new RuntimeException('Source differs; no changes made.');
$payload = file_get_contents(__DIR__ . '/show-response.php.txt');
if ($payload === false) throw new RuntimeException('Missing payload');
$updated = str_replace($before, $payload, $original);
$backup = dirname($root, 2) . '/sena-private-backups/student-profile-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
if (!mkdir($backup, 0700, true) || file_put_contents($backup . '/StudentDataController.php', $original) === false) throw new RuntimeException('Backup failed; no changes made.');
chmod($backup . '/StudentDataController.php', 0600);
$tmp = tempnam(dirname($file), '.sena-profile-');
try {
    if (file_put_contents($tmp, $updated) === false) throw new RuntimeException('Write failed');
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp), $lint, $status);
    if ($status !== 0) throw new RuntimeException('PHP syntax check failed; no changes made.');
    chmod($tmp, fileperms($file) & 0777);
    if (!rename($tmp, $file)) throw new RuntimeException('Atomic update failed');
} finally {
    if (is_file($tmp)) unlink($tmp);
}
echo "Installed. Backup: " . $backup . PHP_EOL;
echo "Run in laravel-app: php artisan optimize:clear" . PHP_EOL;
