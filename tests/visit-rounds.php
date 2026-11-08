<?php
// Disposable in-memory fixtures only; never changes the application database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/config/database.php';
require APP_ROOT . '/app/models/HomeVisit.php';
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE enrollments(id INTEGER,teacher_id INTEGER);
CREATE TABLE sdl_enrollment_teachers(enrollment_id INTEGER,teacher_id INTEGER);
CREATE TABLE home_visits(id INTEGER,enrollment_id INTEGER,teacher_id INTEGER,visit_date TEXT,status TEXT);
INSERT INTO enrollments VALUES(1,10),(2,20),(3,10);
INSERT INTO sdl_enrollment_teachers VALUES(1,30);
INSERT INTO home_visits VALUES(12,1,99,'2026-11-08','completed'),(11,1,10,'2026-10-01','completed'),(13,1,10,'2026-11-09','cancelled'),(14,1,10,'2026-11-10','draft'),(21,2,20,'2026-11-01','completed'),(31,3,10,'2027-01-01','completed');");
$property = new ReflectionProperty(Database::class, 'instance');
$property->setValue(null, $pdo);
try {
    $model = new HomeVisit();
    $checks = 0;
    foreach ([
        [[],null,[]],
        [[1],null,[1=>[11,12]]],
        [[1,2],null,[1=>[11,12],2=>[21]]],
        [[1,2],10,[1=>[11,12]]],
        [[1,2],30,[1=>[11,12]]],
        [[1,2],99,[1=>[12]]],
        [[1,2],999,[]],
        [[3],null,[3=>[31]]],
    ] as [$ids,$teacher,$expected]) {
        $actual = array_map(static fn($rows)=>array_map('intval',array_column($rows,'id')), $model->forEnrollments($ids,$teacher));
        if ($actual !== $expected) throw new RuntimeException('Incorrect round ordering, enrollment or teacher scope');
        $checks++;
    }
    echo "Passed {$checks} visit-round checks (in-memory database).\n";
} finally { $property->setValue(null, null); }
