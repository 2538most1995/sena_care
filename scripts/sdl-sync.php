<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/config/database.php';
require APP_ROOT . '/app/services/SdlApiClient.php';
require APP_ROOT . '/app/services/SdlSyncService.php';
try {
    echo json_encode((new SdlSyncService())->sync(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
