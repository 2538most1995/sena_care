<?php
// CLI only. Back up the destination database before importing into a live installation.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/config/database.php';
require APP_ROOT . '/app/services/SdlApiClient.php';
require APP_ROOT . '/app/services/GraduateSyncService.php';
try {
    $summary = (new GraduateSyncService(new SdlApiClient(), Database::getConnection()))->syncHistory();
    echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
