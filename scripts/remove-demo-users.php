<?php
// Only exact legacy demo identities, unlinked to the API and unused by business records.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('APP_ROOT',dirname(__DIR__)); require APP_ROOT.'/app/config/database.php';
$pdo=Database::getConnection();
$legacy=[['director','สมศักดิ์','รักเรียน','executive'],['teacher1','สมศรี','ใจดี','teacher'],['teacher2','วิชัย','สอนดี','teacher'],['teacher3','กานดา','มิ่งขวัญ','teacher'],['teacher4','ประเสริฐ','สุขเกษม','teacher'],['teacher5','วรรณภา','แสงสุวรรณ','teacher']];
$pdo->beginTransaction(); $removed=0;
try {
    foreach($legacy as [$username,$first,$last,$role]) {
        $stmt=$pdo->prepare('SELECT id FROM users WHERE username=? AND firstname=? AND lastname=? AND role=? FOR UPDATE');
        $stmt->execute([$username,$first,$last,$role]); $id=$stmt->fetchColumn(); if (!$id) continue;
        $stmt=$pdo->prepare("SELECT COUNT(*) FROM sdl_links WHERE entity='teacher' AND local_id=?"); $stmt->execute([$id]);
        if ($stmt->fetchColumn()) continue;
        $used=false;
        foreach (['enrollments'=>'teacher_id','sdl_enrollment_teachers'=>'teacher_id','home_visits'=>'teacher_id','visit_plans'=>'teacher_id','followups'=>'assigned_to','student_risks'=>'assessed_by','student_surveys'=>'surveyed_by'] as $table=>$field) {
            $stmt=$pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field`=?"); $stmt->execute([$id]); if ($stmt->fetchColumn()) $used=true;
        }
        if ($used) { echo "Skipped legacy identity with business history: $username\n"; continue; }
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]); $removed++;
    }
    $stmt=$pdo->prepare('DELETE FROM notifications WHERE id=1 AND user_id=1 AND title=? AND message=? AND link=?');
    $stmt->execute(['แจ้งเตือนความเสี่ยงผู้เรียน','พบผู้เรียนระดับความเสี่ยงสูงมาก 2 รายในภาคเรียน 2/2569','/sena_care/reports']);
    $pdo->commit(); echo "Removed $removed unused legacy demo accounts. API accounts and history preserved.\n";
} catch(Throwable $e) { $pdo->rollBack(); throw $e; }
