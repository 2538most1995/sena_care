<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT.'/app/config/database.php';
require APP_ROOT.'/app/models/AssessmentQuestion.php';
$pdo=Database::getConnection();
$pdo->beginTransaction();
try {
    $count=AssessmentQuestion::retireDuplicates($pdo);
    $pdo->commit();
    echo "Retired $count exact duplicate questions; historical answers preserved.\n";
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }
