<?php
// Isolated in-memory fixtures; no application data is changed.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/models/StudentSurvey.php';
$driver = class_exists('Pdo\Sqlite') ? 'Pdo\Sqlite' : PDO::class;
$pdo = new $driver('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$now = static fn() => '2026-11-07 23:00:00';
if (method_exists($pdo, 'createFunction')) $pdo->createFunction('NOW', $now);
else $pdo->sqliteCreateFunction('NOW', $now);
$pdo->exec('CREATE TABLE users(id INTEGER, firstname TEXT, lastname TEXT, username TEXT);
CREATE TABLE students(id INTEGER, prefix TEXT, firstname TEXT, lastname TEXT, student_code TEXT);
CREATE TABLE enrollments(id INTEGER, student_id INTEGER, academic_term_id INTEGER, learning_center_id INTEGER, group_name TEXT);
CREATE TABLE student_surveys(id INTEGER, enrollment_id INTEGER, surveyed_by INTEGER, surveyed_at TEXT, answers_json TEXT, cancelled_at TEXT, cancelled_by INTEGER);
INSERT INTO users VALUES(1,"ครู","ทดสอบ","teacher");
INSERT INTO students VALUES(1,"นาย","ผู้เรียน","ทดสอบ","TEST001");
INSERT INTO enrollments VALUES(1,1,1,1,"กลุ่มทดสอบ"),(2,1,2,1,"กลุ่มทดสอบ");
INSERT INTO student_surveys VALUES(1,1,1,"2026-11-07 10:00:00","{}",NULL,NULL),(2,1,1,"2026-11-07 11:00:00","{}","2026-11-07 12:00:00",1),(3,2,1,"2026-11-07 13:00:00","{}",NULL,NULL);');
$model = new StudentSurvey($pdo);
$checks = 0;
function check(bool $valid, string $message): void {
    global $checks;
    if (!$valid) throw new RuntimeException($message);
    $checks++;
}
check(array_column($model->history(1), 'id') === [1], 'Cancelled and other-term records excluded from history');
check($model->findById(2) === null, 'Cancelled detail unavailable');
check($model->findById(1)['id'] === 1, 'Active detail remains readable');
check($model->find(1)['id'] === 1, 'Cancelled latest survey cannot seed the form');
check($model->countActive(1) === 1, 'History count excludes cancellations');
check($model->cancel(1, 1), 'Active record can be cancelled');
check($model->history(1) === [] && $model->find(1) === null, 'All-cancelled enrollment has no visible history');
check($model->countActive(1) === 0 && $model->findById(1) === null, 'Cancelled record no longer counted or opened');
check($model->findById(3)['id'] === 3, 'Another term remains intact');
echo "Passed $checks survey history checks (in-memory database).\n";
