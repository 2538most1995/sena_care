<?php
// In-memory fixtures only; never opens or changes the application database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/config/database.php';
require APP_ROOT . '/app/controllers/DashboardController.php';
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE enrollments(id INTEGER, student_id INTEGER, academic_term_id INTEGER, teacher_id INTEGER, enrollment_status TEXT, education_level TEXT);
CREATE TABLE sdl_enrollment_teachers(enrollment_id INTEGER, teacher_id INTEGER);
CREATE TABLE student_surveys(enrollment_id INTEGER, cancelled_at TEXT);
CREATE TABLE home_visits(id INTEGER, student_id INTEGER, enrollment_id INTEGER, status TEXT, risk_level TEXT, visit_date TEXT);
CREATE TABLE followups(status TEXT, assigned_to INTEGER);
CREATE TABLE student_risks(id INTEGER, student_id INTEGER, enrollment_id INTEGER, risk_level TEXT, assessed_at TEXT);
INSERT INTO enrollments VALUES (1,1,1,1,"active","primary"),(2,2,1,2,"active","primary"),(3,3,1,1,"active","primary"),(4,1,2,1,"active","primary"),(5,4,1,1,"inactive","primary");
INSERT INTO student_surveys VALUES (1,NULL),(1,NULL),(2,NULL),(3,"2026-11-07"),(4,NULL),(5,NULL);
INSERT INTO sdl_enrollment_teachers VALUES (1,3);');
$property = new ReflectionProperty(Database::class, 'instance');
$property->setValue(null, $pdo);
try {
    $controller = new DashboardController();
    $method = new ReflectionMethod($controller, 'getStats');
    $checks = 0;
    foreach ([[1,1,'admin',2], [1,1,'teacher',1], [1,2,'teacher',1], [1,3,'teacher',1], [1,99,'teacher',0], [2,1,'teacher',1]] as [$term,$user,$role,$expected]) {
        $stats = $method->invoke($controller, $term, $user, $role);
        if ($stats['surveyed'] !== $expected) throw new RuntimeException('Wrong survey count for role/term');
        $checks++;
    }
    $pdo->exec('UPDATE student_surveys SET cancelled_at="2026-11-07" WHERE enrollment_id=1');
    if ($method->invoke($controller, 1, 1, 'teacher')['surveyed'] !== 0) throw new RuntimeException('Cancelled surveys still counted');
    $checks++;
    echo "Passed $checks dashboard survey checks (in-memory database).\n";
} finally {
    $property->setValue(null, null);
}
