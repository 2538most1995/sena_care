<?php
// Isolated MySQL integration test: never writes to the application's database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT.'/app/config/database.php';
require APP_ROOT.'/app/models/User.php';
require APP_ROOT.'/app/models/Student.php';
require APP_ROOT.'/app/models/AcademicTerm.php';
require APP_ROOT.'/app/helpers/auth.php';
require APP_ROOT.'/app/services/SdlApiClient.php';
require APP_ROOT.'/app/services/SdlSyncService.php';
require APP_ROOT.'/app/services/AuthService.php';
session_start();
$cfg=require APP_ROOT.'/app/config/config.php';
$server=new PDO("mysql:host={$cfg['db_host']};port={$cfg['db_port']};charset=utf8mb4",$cfg['db_user'],$cfg['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='sena_sdl_test_'.bin2hex(random_bytes(5));
$checks=0;
function check(bool $condition,string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
class FixtureSdlApi extends SdlApiClient {
    public array $teachers=[['id'=>90,'username'=>'source.teacher','email'=>'teacher@example.test','display_name'=>'ครู ทดสอบ','assigned_groups'=>['1'],'district_id'=>9]];
    public array $groups=[['code'=>'001','name'=>'กลุ่มทดสอบ']];
    public array $students=[['code'=>'TEST001','district'=>['id'=>9,'name'=>'อำเภอทดสอบ'],'name'=>['prefix'=>'นาย','first_name'=>'นักเรียน','last_name'=>'ทดสอบ'],'current_term'=>'1/2569','group'=>['code'=>'001','name'=>'กลุ่มทดสอบ'],'level'=>['id'=>2],'status'=>['code'=>'studying']]];
    public bool $outage=false;
    public function all(string $resource): array { if ($this->outage) throw new RuntimeException('fixture outage'); return $this->{$resource}; }
    public function request(string $path,?array $body=null): array {
        if ($this->outage) throw new RuntimeException('fixture outage');
        if ($path==='groups') return ['data'=>$this->groups];
        return ['data'=>($body['password']??'')==='source-secret' ? $this->teachers[0] : null];
    }
}
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo=new PDO("mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname=$name;charset=utf8mb4",$cfg['db_user'],$cfg['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $schema=file_get_contents(APP_ROOT.'/database/database.sql');
    $schema=preg_replace('/CREATE DATABASE IF NOT EXISTS `sena_care`[^;]*;|USE `sena_care`;/','',$schema);
    $pdo->exec($schema);
    $pdo->exec(file_get_contents(APP_ROOT.'/database/sdl_integration.sql'));
    $property=new ReflectionProperty(Database::class,'instance'); $property->setValue(null,$pdo);
    $api=new FixtureSdlApi([]); $sync=new SdlSyncService($api);
    $summary=$sync->sync();
    check($summary['students']===1 && $summary['teachers']===1 && $summary['groups']===1,'Initial import counts');
    check((int)$pdo->query('SELECT teacher_id FROM enrollments')->fetchColumn()===1,'Teacher assigned by source group code');
    check($pdo->query('SELECT education_level FROM enrollments')->fetchColumn()==='lower_secondary','Level mapping');
    check($pdo->query('SELECT term_name FROM academic_terms WHERE is_current=1')->fetchColumn()==='1/2569','Source working term selected');
    $pdo->exec("INSERT INTO student_addresses(student_id,house_no,latitude) VALUES(1,'12',14.123)");
    $pdo->exec("UPDATE students SET phone='0890000000' WHERE id=1");
    $api->students[0]['name']['first_name']='ชื่อใหม่';
    $sync->sync();
    check((int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn()===1,'Repeated sync does not duplicate students');
    check((int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn()===1,'Repeated sync does not duplicate enrollment');
    check($pdo->query('SELECT firstname FROM students')->fetchColumn()==='ชื่อใหม่','Source updates imported');
    check($pdo->query('SELECT phone FROM students')->fetchColumn()==='0890000000','Locally entered phone preserved');
    check($pdo->query('SELECT house_no FROM student_addresses')->fetchColumn()==='12','Address preserved');
    $auth=new AuthService($api);
    check($auth->login('source.teacher','source-secret')['success'],'Same source password works');
    $pdo->exec("UPDATE users SET password='".password_hash('local-secret',PASSWORD_DEFAULT)."' WHERE id=1");
    check(!$auth->login('source.teacher','local-secret')['success'],'Linked user cannot fall back to local password');
    $api->outage=true;
    check(!$auth->login('source.teacher','source-secret')['success'],'Source outage fails closed');
    try { $sync->sync(); throw new LogicException('Expected outage'); } catch (RuntimeException $e) { check($e->getMessage()==='fixture outage','Source outage aborts sync'); }
    check((int)$pdo->query('SELECT COUNT(*) FROM sdl_sync_runs')->fetchColumn()===2,'Failed sync leaves no success record');
    $api->outage=false;
    $api->groups=[];
    $sync->sync(); $sync->sync();
    check((int)$pdo->query('SELECT COUNT(*) FROM learning_centers')->fetchColumn()===1,'Student-derived fallback group is reused');
    $joint=$api->teachers[0]; $joint['id']=91; $joint['username']='joint.teacher'; $joint['email']='joint@example.test'; $api->teachers[]=$joint;
    $sync->sync();
    check((int)$pdo->query('SELECT COUNT(*) FROM sdl_enrollment_teachers')->fetchColumn()===2,'All jointly assigned teachers linked');
    $_SESSION['user_id']=2; $_SESSION['user']['role']='teacher'; $_SESSION['current_term_id']=1;
    check(canAccessStudent(1),'Joint teacher can access assigned student');
    check((new Student())->getAll(['term_id'=>1,'teacher_id'=>2])['total']===1,'Joint teacher student list matches source scope');
    $_SESSION['user_id']=999;
    check(!canAccessStudent(1),'Unassigned teacher cannot access student');
    array_pop($api->teachers); $sync->sync();
    $extra=$api->students[0]; $extra['code']='TEST002'; $api->students[]=$extra;
    $sync->sync(); array_pop($api->students); $sync->sync();
    check($pdo->query("SELECT student_status FROM students WHERE student_code='TEST002'")->fetchColumn()==='inactive','Missing source student becomes inactive');
    check($pdo->query('SELECT enrollment_status FROM enrollments WHERE student_id=2')->fetchColumn()==='inactive','Missing source enrollment becomes inactive');
    check((new Student())->getAll(['term_id'=>1])['total']===1,'Current roster excludes student removed from API');
    check((int)$pdo->query("SELECT source_present FROM sdl_links WHERE entity='student' AND local_id=2")->fetchColumn()===0,'Missing source membership recorded without deleting student');
    $api->students[]=$extra; $sync->sync();
    check((new Student())->getAll(['term_id'=>1])['total']===2,'Returning API student reappears without creating a duplicate');
    array_pop($api->students); $sync->sync();
    $api->students[0]['status']['code']='inactive'; $sync->sync();
    check((new Student())->getAll(['term_id'=>1])['total']===1,'Inactive student explicitly present in API is still included in its roster');
    $api->students[0]['status']['code']='studying'; $sync->sync();
    $savedStudents=$api->students; $api->students=[];
    try { $sync->sync(); throw new LogicException('Expected empty source guard'); } catch (RuntimeException $e) { check(str_contains($e->getMessage(),'ศูนย์'),'Unexpected empty roster rejected'); }
    $api->students=$savedStudents;
    $api->teachers[0]['username']='colliding.teacher';
    $pdo->exec("INSERT INTO users(username,password,firstname,lastname) VALUES('colliding.teacher','unused','Existing','Account')");
    try { $sync->sync(); throw new LogicException('Expected duplicate rejection'); } catch (PDOException $e) { check(true,'Username conflict rolls back'); }
    check($pdo->query('SELECT username FROM users WHERE id=1')->fetchColumn()==='source.teacher','Conflict does not take over accounts');
    $api->teachers=[];
    $sync->sync();
    check($pdo->query('SELECT status FROM users WHERE id=1')->fetchColumn()==='inactive','Removed source teacher disabled');
    check($pdo->query('SELECT teacher_id FROM enrollments')->fetchColumn()===null,'Removed source teacher unassigned');
    echo "Passed $checks checks (isolated database).\n";
} finally {
    $property=new ReflectionProperty(Database::class,'instance'); $property->setValue(null,null);
    $pdo=null;
    $server->exec("DROP DATABASE IF EXISTS `$name`");
    session_destroy();
}
