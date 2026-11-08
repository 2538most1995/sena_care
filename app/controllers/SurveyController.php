<?php
if (!defined('APP_ROOT')) die();
require_once APP_ROOT.'/app/models/StudentSurvey.php';

class SurveyController
{
    public function index(): void
    {
        requireRole('super_admin','admin','teacher','executive');
        $termId=(int)($_GET['term_id']??currentTermId());
        $page=max(1,(int)($_GET['page']??1));
        $filters=['term_id'=>$termId,'search'=>trim($_GET['search']??''),'teacher_id'=>isTeacher()?currentUserId():null,'survey_status'=>$_GET['survey_status']??'','center_id'=>(int)($_GET['center_id']??0),'level'=>$_GET['level']??''];
        $roster=(new Student())->getAll($filters,$page,20);
        $terms=(new AcademicTerm())->getActiveTerms();
        $centers=(new LearningCenter())->getActive();
        $pageTitle='สำรวจผู้เรียนรายบุคคล'; $currentPage='surveys';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/surveys/index.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    public function form(int $studentId): void
    {
        requireRole('super_admin','admin','teacher','executive');
        $termId=(int)($_GET['term_id']??$_POST['term_id']??currentTermId());
        $student=(new Student())->findById($studentId,$termId);
        if (!$student || empty($student['enrollment_id'])) { http_response_code(404); include APP_ROOT.'/app/views/errors/404.php'; return; }
        if (!$this->canAccess($studentId,$termId)) { http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return; }

        $canCreate = canCreateSurveyForCenter(isset($student['learning_center_id']) ? (int)$student['learning_center_id'] : null);
        $model=new StudentSurvey(Database::getConnection());
        $enrollmentId=(int)$student['enrollment_id'];

        if ($_SERVER['REQUEST_METHOD']==='POST') {
            requireRole('super_admin','admin','teacher'); requireCsrf();
            if (!$canCreate) {
                setFlash('danger','ปิดรับการสำรวจสำหรับศกร.นี้ ตามช่วงเวลาที่อำเภอกำหนด');
                redirect(url('surveys/'.$studentId.'?term_id='.$termId));
            }
            try {
                $model->save($enrollmentId,currentUserId(),$_POST['answers']??[],trim((string)($_POST['note']??'')),(int)($_POST['revision']??0));
                unset($_SESSION['survey_draft'][$enrollmentId]);
                $count=$model->countActive($enrollmentId);
                setFlash('success','บันทึกการสำรวจครั้งที่ '.$count.' เรียบร้อย (เก็บประวัติเดิมไว้)');
                redirect(url('surveys/'.$studentId.'?term_id='.$termId));
            } catch (Throwable $e) {
                error_log('Survey save: '.$e->getMessage());
                $_SESSION['survey_draft'][$enrollmentId]=['answers'=>$_POST['answers']??[],'note'=>$_POST['note']??''];
                setFlash('danger',$e instanceof PDOException?'บันทึกไม่สำเร็จ กรุณาโหลดหน้าปัจจุบันแล้วลองใหม่':$e->getMessage());
                redirect(url('surveys/'.$studentId.'?term_id='.$termId));
            }
        }

        $history=$model->history($enrollmentId);
        $latest=null;
        foreach ($history as $h) {
            if (empty($h['cancelled_at'])) { $latest=$h; break; }
        }

        $questions=AssessmentQuestion::active(Database::getConnection());
        $answersMap=$latest['answers']??[];
        $fromVisit=false;
        if (!$latest) {
            $stmt=Database::getConnection()->prepare("SELECT id FROM home_visits WHERE enrollment_id=? AND status='completed' ORDER BY visit_date DESC,id DESC LIMIT 1");
            $stmt->execute([$enrollmentId]); $visitId=$stmt->fetchColumn();
            if ($visitId) {
                $visit=(new HomeVisit())->findById((int)$visitId);
                $answersMap=AssessmentQuestion::answersForActive(Database::getConnection(),$visit['answers']);
                $fromVisit=true;
            }
        }
        $draft=$_SESSION['survey_draft'][$enrollmentId]??null;
        if ($draft && is_array($draft['answers'])) $answersMap=$draft['answers'];
        $readOnly=isExecutive() || !$canCreate;
        $surveyCount=$model->countActive($enrollmentId);
        $survey=$latest;              // latest active survey (used by form fields/alert)
        $surveyHistory=$history;      // active history, newest first
        $pageTitle='สำรวจรายบุคคล: '.$student['firstname'].' '.$student['lastname']; $currentPage='surveys';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/surveys/form.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    /** View one survey record (what was surveyed in that round). */
    public function detail(int $surveyId): void
    {
        requireRole('super_admin','admin','teacher','executive');
        $model=new StudentSurvey(Database::getConnection());
        $survey=$model->findById($surveyId);
        if (!$survey) { http_response_code(404); include APP_ROOT.'/app/views/errors/404.php'; return; }
        if (!$this->canAccess((int)$survey['student_id'],(int)$survey['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        $questions=AssessmentQuestion::active(Database::getConnection());
        $history=$model->history((int)$survey['enrollment_id']);
        $pageTitle='แบบสำรวจ: '.$survey['s_firstname'].' '.$survey['s_lastname']; $currentPage='surveys';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/surveys/detail.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    /** Cancel a survey record and remove it from visible history and counts. */
    public function cancel(int $surveyId): void
    {
        requireRole('super_admin','admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
        requireCsrf();
        $model=new StudentSurvey(Database::getConnection());
        $survey=$model->findById($surveyId);
        if (!$survey) { http_response_code(404); include APP_ROOT.'/app/views/errors/404.php'; return; }
        if (!$this->canAccess((int)$survey['student_id'],(int)$survey['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        if ($model->cancel($surveyId, currentUserId())) {
            setFlash('success','ยกเลิกการสำรวจแล้ว รายการนี้จะไม่แสดงในประวัติ');
        } else {
            setFlash('warning','รายการนี้ถูกยกเลิกไปแล้ว');
        }
        redirect(url('surveys/'.$survey['student_id'].'?term_id='.$survey['academic_term_id']));
    }

    private function canAccess(int $studentId,int $termId): bool
    {
        if (isAdmin()||isExecutive()) return true;
        $stmt=Database::getConnection()->prepare('SELECT 1 FROM enrollments e WHERE e.student_id=? AND e.academic_term_id=? AND (e.teacher_id=? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))');
        $stmt->execute([$studentId,$termId,currentUserId(),currentUserId()]); return (bool)$stmt->fetchColumn();
    }
}
