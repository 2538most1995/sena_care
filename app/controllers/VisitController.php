<?php
/**
 * ============================================================
 * Visit Controller (Student Roster, GPS Check-in, Assessment)
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/app/models/AssessmentQuestion.php';
require_once APP_ROOT . '/app/services/VisitPhotoStorage.php';

class VisitController
{
    private HomeVisit $visitModel;
    private Student $studentModel;
    private AcademicTerm $termModel;
    private PDO $pdo;

    public function __construct()
    {
        $this->visitModel = new HomeVisit();
        $this->studentModel = new Student();
        $this->termModel = new AcademicTerm();
        $this->pdo = Database::getConnection();
    }

    /**
     * Home visit list
     */
    public function history(): void
    {
        requireLogin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1,min(100,(int)($_GET['per_page'] ?? 25)));

        $filters = [
            'term_id'    => $_GET['term_id'] ?? currentTermId(),
            'student_id' => max(0, (int)($_GET['student_id'] ?? 0)),
            'teacher_id' => isTeacher() ? currentUserId() : ($_GET['teacher_id'] ?? ''),
            'risk_level' => $_GET['risk_level'] ?? '',
            'status'     => $_GET['status'] ?? '',
            'search'     => trim($_GET['search'] ?? ''),
        ];

        $visitsData = $this->visitModel->getAll($filters, $page, $perPage);
        $historyStudent = $filters['student_id'] > 0 ? ($visitsData['data'][0] ?? null) : null;
        $terms = $this->termModel->getActiveTerms();
        $userModel = new User();
        $teachers = $userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'บันทึกการเยี่ยมบ้าน';
        $currentPage = 'visits';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/visits/history.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function index(): void
    {
        requireLogin();
        $page=max(1,(int)($_GET['page']??1));
        $filters=['term_id'=>(int)($_GET['term_id']??currentTermId()),'search'=>trim($_GET['search']??''),
            'teacher_id'=>isTeacher()?currentUserId():null,'center_id'=>(int)($_GET['center_id']??0),
            'level'=>$_GET['level']??'','visit_status'=>$_GET['visit_status']??''];
        $studentsData=$this->studentModel->getAll($filters,$page,20);
        $visitRounds=$this->visitModel->forEnrollments(array_column($studentsData['data'],'enrollment_id'),isTeacher()?currentUserId():null);
        $terms=$this->termModel->getActiveTerms();
        $groupSql="SELECT DISTINCT lc.id,lc.center_name FROM learning_centers lc JOIN enrollments e ON e.learning_center_id=lc.id WHERE e.academic_term_id=? AND NOT EXISTS(SELECT 1 FROM sdl_links sl WHERE sl.entity='student' AND sl.local_id=e.student_id AND sl.source_present=0)";
        $params=[$filters['term_id']];
        if(isTeacher()) { $groupSql.=' AND (e.teacher_id=? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))'; $params[]=currentUserId(); $params[]=currentUserId(); }
        $stmt=$this->pdo->prepare($groupSql.' ORDER BY lc.center_name'); $stmt->execute($params); $centers=$stmt->fetchAll();
        $pageTitle='บันทึกเยี่ยมบ้าน'; $currentPage='visits';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/visits/index.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    /**
     * Visit recording form
     */
    public function create(int $studentId): void
    {
        requireRole('super_admin', 'admin', 'teacher');

        $termId = (int)($_POST['term_id'] ?? $_GET['term_id'] ?? currentTermId());
        if ($termId < 1 || !canAccessStudent($studentId, $termId)) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        $student = $this->studentModel->findById($studentId, $termId);
        if (!$student || empty($student['enrollment_id'])) {
            setFlash('danger', 'ไม่พบข้อมูลผู้เรียน');
            redirect(url('students'));
        }
        if (!canCreateVisitForCenter(isset($student['learning_center_id']) ? (int)$student['learning_center_id'] : null)) {
            setFlash('danger', 'ปิดรับการบันทึกเยี่ยมบ้านสำหรับศกร.นี้ ตามช่วงเวลาที่อำเภอกำหนด');
            redirect(url('visits'));
        }

        $questions = AssessmentQuestion::active($this->pdo);

        // Group questions by section
        $sections = [];
        foreach ($questions as $q) {
            $sections[$q['section_name']][] = $q;
        }

        require_once APP_ROOT.'/app/models/StudentSurvey.php';
        $surveySeed=(new StudentSurvey($this->pdo))->find((int)$student['enrollment_id']);
        $pageTitle = 'บันทึกผลการเยี่ยมบ้าน: ' . e($student['firstname'] . ' ' . $student['lastname']);
        $currentPage = 'visits';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/visits/create.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Store home visit record
     */
    public function store(int $studentId): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        requireCsrf();

        $termId = (int)($_POST['term_id'] ?? $_GET['term_id'] ?? currentTermId());
        if ($termId < 1 || !canAccessStudent($studentId, $termId)) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        $student = $this->studentModel->findById($studentId, $termId);
        if (!$student || empty($student['enrollment_id'])) {
            setFlash('danger', 'ไม่พบข้อมูลผู้เรียน');
            redirect(url('students'));
        }
        if (!canCreateVisitForCenter(isset($student['learning_center_id']) ? (int)$student['learning_center_id'] : null)) {
            setFlash('danger', 'ปิดรับการบันทึกเยี่ยมบ้านสำหรับศกร.นี้ ตามช่วงเวลาที่อำเภอกำหนด');
            redirect(url('visits'));
        }

        $checkinLat = !empty($_POST['checkin_latitude']) ? (float)$_POST['checkin_latitude'] : null;
        $checkinLng = !empty($_POST['checkin_longitude']) ? (float)$_POST['checkin_longitude'] : null;
        $distance = !empty($_POST['distance_from_home']) ? (int)$_POST['distance_from_home'] : null;
        $riskLevel = $_POST['risk_level'] ?? 'low';
        $summary = trim($_POST['summary'] ?? '');
        $recommendation = trim($_POST['teacher_recommendation'] ?? '');
        $followupRequired = !empty($_POST['followup_required']);
        $nextFollowupDate = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null;

        $photos = new VisitPhotoStorage();
        Database::beginTransaction();
        try {
            $validatedAnswers = AssessmentQuestion::validateAnswers($this->pdo, $_POST['answers'] ?? []);
            $visitId = $this->visitModel->create([
                'student_id'             => $studentId,
                'enrollment_id'          => $student['enrollment_id'] ?? null,
                'teacher_id'             => currentUserId(),
                'visit_date'             => $_POST['visit_date'] ?? date('Y-m-d'),
                'checkin_latitude'       => $checkinLat,
                'checkin_longitude'      => $checkinLng,
                'distance_from_home'     => $distance,
                'visit_type'             => $_POST['visit_type'] ?? 'home_visit',
                'summary'                => $summary,
                'problem'                => trim($_POST['problem'] ?? ''),
                'student_need'           => trim($_POST['student_need'] ?? ''),
                'teacher_recommendation' => $recommendation,
                'risk_level'             => $riskLevel,
                'followup_required'      => $followupRequired ? 1 : 0,
                'next_followup_date'     => $nextFollowupDate,
                'status'                 => 'completed'
            ]);

            $stmtAns = $this->pdo->prepare("INSERT INTO assessment_answers (visit_id, question_id, answer_value) VALUES (?, ?, ?)");
            foreach ($validatedAnswers as $qId => $value) $stmtAns->execute([$visitId,$qId,$value]);

            $photos->save($this->pdo, $visitId, $_FILES['photos'] ?? null, currentUserId());

            // Auto-create Follow-up task if requested
            if ($followupRequired && !empty($_POST['followup_title'])) {
                $followupModel = new Followup();
                $followupModel->create([
                    'student_id'    => $studentId,
                    'home_visit_id' => $visitId,
                    'assigned_to'   => currentUserId(),
                    'title'         => trim($_POST['followup_title']),
                    'description'   => $summary,
                    'priority'      => in_array($riskLevel, ['high', 'critical']) ? 'urgent' : 'normal',
                    'due_date'      => $nextFollowupDate ?: date('Y-m-d', strtotime('+14 days')),
                    'status'        => 'pending'
                ]);
            }

            // Record or update Student Risk
            $stmtRisk = $this->pdo->prepare(
                "INSERT INTO student_risks (student_id, enrollment_id, home_visit_id, risk_level, total_score, assessed_by)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE risk_level = VALUES(risk_level), home_visit_id = VALUES(home_visit_id)"
            );
            $scoreMap = ['low' => 2, 'medium' => 5, 'high' => 8, 'critical' => 10];
            $stmtRisk->execute([
                $studentId,
                $student['enrollment_id'] ?? null,
                $visitId,
                $riskLevel,
                $scoreMap[$riskLevel] ?? 2,
                currentUserId()
            ]);

            Database::commit();
            unset($_SESSION['visit_draft'][(int)$student['enrollment_id']]);
            setFlash('success', 'บันทึกผลการเยี่ยมบ้านเรียบร้อยแล้ว');
            redirect(url('visits/' . $visitId));
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) Database::rollBack();
            $photos->rollback();
            $_SESSION['visit_draft'][(int)$student['enrollment_id']] = $_POST;
            error_log('Visit save failed: '.$e->getMessage());
            $message = $e instanceof PDOException ? 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง' : $e->getMessage();
            setFlash('danger', 'เกิดข้อผิดพลาดในการบันทึก: ' . $message . ' (ข้อมูลที่กรอกไว้ยังอยู่ กรุณาเลือกรูปแนบใหม่)');
            redirect(url('visits/create/' . $studentId . '?term_id=' . $termId));
        }
    }

    /** A dedicated A4 PDF, rather than printing the application navigation and cards. */
    public function pdf(int $id): void
    {
        requireRole('super_admin','admin','executive','teacher');
        $visit=$this->visitModel->findById($id);
        if (!$visit) { http_response_code(404); include APP_ROOT.'/app/views/errors/404.php'; return; }
        if (isTeacher() && (int)$visit['teacher_id']!==currentUserId() && !canAccessStudent((int)$visit['student_id'],(int)$visit['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        $tasks=$this->pdo->prepare('SELECT title,status,due_date FROM followups WHERE home_visit_id=? ORDER BY id');
        $tasks->execute([$id]); $visit['followups']=$tasks->fetchAll();
        require_once APP_ROOT.'/app/services/VisitReportPdf.php';
        $bytes=(new VisitReportPdf())->pdf($visit);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="home-visit-'.$id.'.pdf"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
    }

    /**
     * Show detailed visit
     */
    public function show(int $id): void
    {
        requireLogin();

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            setFlash('danger', 'ไม่พบบันทึกการเยี่ยมบ้านที่ระบุ');
            redirect(url('visits'));
        }

        if (isTeacher() && (int)$visit['teacher_id'] !== currentUserId() && !canAccessStudent((int)$visit['student_id'], (int)$visit['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }
        $pageTitle = 'บันทึกการเยี่ยมบ้าน: ' . e($visit['firstname'] . ' ' . $visit['lastname']);
        $currentPage = 'visits';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/visits/show.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Soft-cancel a completed home visit (district admins only).
     */
    public function cancel(int $id): void
    {
        requireRole('super_admin', 'admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }
        requireCsrf();

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            setFlash('danger', 'ไม่พบบันทึกการเยี่ยมบ้านที่ระบุ');
            redirect(url('visits'));
        }
        if ($visit['status'] !== 'completed') {
            setFlash('warning', 'เฉพาะบันทึกที่เสร็จสิ้นแล้วเท่านั้นที่ยกเลิกได้');
            redirect(url('visits'));
        }
        if ($this->visitModel->cancel($id)) {
            setFlash('success', 'ยกเลิกบันทึกการเยี่ยมบ้านแล้ว (ยังเก็บประวัติไว้)');
        } else {
            setFlash('warning', 'ไม่สามารถยกเลิกได้ อาจถูกยกเลิกไปแล้ว');
        }
        redirect(url('visits'));
    }

    /**
     * Edit home visit record form
     */
    public function edit(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            setFlash('danger', 'ไม่พบบันทึกการเยี่ยมบ้านที่ระบุ');
            redirect(url('visits'));
        }
        if (($visit['status'] ?? 'completed') === 'cancelled') {
            setFlash('warning', 'บันทึกนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้');
            redirect(url('visits/' . $id));
        }

        if (isTeacher() && (int)$visit['teacher_id'] !== currentUserId() && !canAccessStudent((int)$visit['student_id'], (int)$visit['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }

        $student = $this->studentModel->findById((int)$visit['student_id'], (int)$visit['academic_term_id']);
        if (!$student) {
            setFlash('danger', 'ไม่พบข้อมูลผู้เรียน');
            redirect(url('visits'));
        }

        $questions = AssessmentQuestion::active($this->pdo);

        // Group questions by section
        $sections = [];
        foreach ($questions as $q) {
            $sections[$q['section_name']][] = $q;
        }

        $answersMap = AssessmentQuestion::answersForActive($this->pdo,$visit['answers']);

        // Check if there is an existing followup
        $stmtFu = $this->pdo->prepare("SELECT * FROM followups WHERE home_visit_id = ? ORDER BY id DESC LIMIT 1");
        $stmtFu->execute([$id]);
        $followup = $stmtFu->fetch();

        $pageTitle = 'แก้ไขผลการเยี่ยมบ้าน: ' . e($visit['firstname'] . ' ' . $visit['lastname']);
        $currentPage = 'visits';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/visits/edit.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Update home visit record
     */
    public function update(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        requireCsrf();

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            setFlash('danger', 'ไม่พบบันทึกการเยี่ยมบ้านที่ระบุ');
            redirect(url('visits'));
        }

        if (isTeacher() && (int)$visit['teacher_id'] !== currentUserId() && !canAccessStudent((int)$visit['student_id'], (int)$visit['academic_term_id'])) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; return;
        }

        if (($visit['status'] ?? 'completed') === 'cancelled') {
            setFlash('warning', 'บันทึกนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้');
            redirect(url('visits/' . $id));
        }

        $studentId = (int)$visit['student_id'];
        $checkinLat = !empty($_POST['checkin_latitude']) ? (float)$_POST['checkin_latitude'] : null;
        $checkinLng = !empty($_POST['checkin_longitude']) ? (float)$_POST['checkin_longitude'] : null;
        $distance = !empty($_POST['distance_from_home']) ? (int)$_POST['distance_from_home'] : null;
        $riskLevel = $_POST['risk_level'] ?? 'low';
        $summary = trim($_POST['summary'] ?? '');
        $recommendation = trim($_POST['teacher_recommendation'] ?? '');
        $followupRequired = !empty($_POST['followup_required']);
        $nextFollowupDate = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null;

        $photos = new VisitPhotoStorage();
        $removedPhotos = [];
        Database::beginTransaction();
        try {
            $updateData = [
                'visit_date'             => $_POST['visit_date'] ?? date('Y-m-d'),
                'visit_type'             => $_POST['visit_type'] ?? 'home_visit',
                'summary'                => $summary,
                'problem'                => trim($_POST['problem'] ?? ''),
                'student_need'           => trim($_POST['student_need'] ?? ''),
                'teacher_recommendation' => $recommendation,
                'risk_level'             => $riskLevel,
                'followup_required'      => $followupRequired ? 1 : 0,
                'next_followup_date'     => $nextFollowupDate,
            ];

            if ($checkinLat !== null && $checkinLng !== null) {
                $updateData['checkin_latitude'] = $checkinLat;
                $updateData['checkin_longitude'] = $checkinLng;
                $updateData['distance_from_home'] = $distance;
            }

            $this->visitModel->update($id, $updateData);

            // Only replace active-form answers; preserve retired historical definitions.
            if (array_key_exists('answers', $_POST)) {
                $answers = AssessmentQuestion::validateAnswers($this->pdo, $_POST['answers']);
                $stmtDel = $this->pdo->prepare("DELETE aa FROM assessment_answers aa JOIN assessment_questions q ON q.id=aa.question_id WHERE aa.visit_id=? AND q.form_id=1 AND q.status='active'");
                $stmtDel->execute([$id]);
                $stmtAns = $this->pdo->prepare("INSERT INTO assessment_answers (visit_id,question_id,answer_value) VALUES (?,?,?)");
                foreach ($answers as $qId => $value) $stmtAns->execute([$id,$qId,$value]);
            }

            // Keep existing files until the entire transaction commits.
            $findFile = $this->pdo->prepare('SELECT stored_name FROM visit_files WHERE id=? AND visit_id=?');
            $deleteFile = $this->pdo->prepare('DELETE FROM visit_files WHERE id=? AND visit_id=?');
            foreach ((array)($_POST['delete_photos'] ?? []) as $fileId) {
                $findFile->execute([(int)$fileId,$id]);
                $name = $findFile->fetchColumn();
                if ($name !== false) {
                    $removedPhotos[] = APP_ROOT.'/public/uploads/visits/'.basename($name);
                    $deleteFile->execute([(int)$fileId,$id]);
                }
            }
            $photos->save($this->pdo,$id,$_FILES['photos'] ?? null,currentUserId());

            // Follow-up task
            if ($followupRequired && !empty($_POST['followup_title'])) {
                $followupModel = new Followup();
                $stmtFu = $this->pdo->prepare("SELECT id FROM followups WHERE home_visit_id = ? LIMIT 1");
                $stmtFu->execute([$id]);
                $fuId = $stmtFu->fetchColumn();

                if ($fuId) {
                    $stmtFuUp = $this->pdo->prepare("UPDATE followups SET title = ?, description = ?, priority = ?, due_date = ? WHERE id = ?");
                    $stmtFuUp->execute([
                        trim($_POST['followup_title']),
                        $summary,
                        in_array($riskLevel, ['high', 'critical']) ? 'urgent' : 'normal',
                        $nextFollowupDate ?: date('Y-m-d', strtotime('+14 days')),
                        $fuId
                    ]);
                } else {
                    $followupModel->create([
                        'student_id'    => $studentId,
                        'home_visit_id' => $id,
                        'assigned_to'   => currentUserId(),
                        'title'         => trim($_POST['followup_title']),
                        'description'   => $summary,
                        'priority'      => in_array($riskLevel, ['high', 'critical']) ? 'urgent' : 'normal',
                        'due_date'      => $nextFollowupDate ?: date('Y-m-d', strtotime('+14 days')),
                        'status'        => 'pending'
                    ]);
                }
            }

            // Update Student Risk
            $stmtRisk = $this->pdo->prepare(
                "INSERT INTO student_risks (student_id, enrollment_id, home_visit_id, risk_level, total_score, assessed_by)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE risk_level = VALUES(risk_level), home_visit_id = VALUES(home_visit_id)"
            );
            $scoreMap = ['low' => 2, 'medium' => 5, 'high' => 8, 'critical' => 10];
            $stmtRisk->execute([
                $studentId,
                $visit['enrollment_id'] ?? null,
                $id,
                $riskLevel,
                $scoreMap[$riskLevel] ?? 2,
                currentUserId()
            ]);

            Database::commit();
            foreach ($removedPhotos as $path) { if (is_file($path) && !@unlink($path)) error_log('Could not remove visit photo: '.$path); }
            unset($_SESSION['visit_edit_draft'][$id]);
            setFlash('success', 'บันทึกการแก้ไขผลการเยี่ยมบ้านเรียบร้อยแล้ว');
            redirect(url('visits/' . $id));
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) Database::rollBack();
            $photos->rollback();
            $_SESSION['visit_edit_draft'][$id] = $_POST;
            error_log('Visit save failed: '.$e->getMessage());
            $message = $e instanceof PDOException ? 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง' : $e->getMessage();
            setFlash('danger', 'เกิดข้อผิดพลาดในการบันทึก: ' . $message . ' (ข้อมูลที่กรอกไว้ยังอยู่ กรุณาเลือกรูปแนบใหม่)');
            redirect(url('visits/' . $id . '/edit'));
        }
    }
}
