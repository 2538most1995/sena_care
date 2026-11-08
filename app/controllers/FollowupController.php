<?php
/**
 * ============================================================
 * Followup Controller
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class FollowupController
{
    private Followup $followupModel;
    private Student $studentModel;

    public function __construct()
    {
        $this->followupModel = new Followup();
        $this->studentModel = new Student();
    }

    public function index(): void
    {
        requireLogin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1,min(100,(int)($_GET['per_page'] ?? 25)));

        $filters = [
            'status'      => $_GET['status'] ?? '',
            'priority'    => $_GET['priority'] ?? '',
            'assigned_to' => isTeacher() ? currentUserId() : ($_GET['assigned_to'] ?? ''),
        ];

        $followupsData = $this->followupModel->getAll($filters, $page, $perPage);
        $userModel = new User();
        $teachers = $userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $studentOptions=(new Student())->getAll(['term_id'=>currentTermId(),'teacher_id'=>isTeacher()?currentUserId():null],1,10000)['data'];
        $pageTitle = 'การติดตามช่วยเหลือผู้เรียน';
        $currentPage = 'followups';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/followups/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function store(): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        requireCsrf();

        $val = new Validator($_POST);
        $val->required('student_id', 'ผู้เรียน')
            ->required('title', 'หัวข้อการติดตาม');

        if ($val->fails()) {
            setFlash('danger', $val->firstError());
            redirect(url('followups'));
        }

        if (!canAccessStudent((int)$_POST['student_id'])) { http_response_code(403); exit; }
        if (isTeacher()) $_POST['assigned_to']=currentUserId();
        if (!$this->studentModel->findById((int)$_POST['student_id'],currentTermId()) || !in_array($_POST['priority']??'normal',['low','normal','high','urgent'],true)) { setFlash('danger','ข้อมูลผู้เรียนหรือความสำคัญไม่ถูกต้อง'); redirect(url('followups')); }
        $this->followupModel->create([
            'student_id'  => (int)$_POST['student_id'],
            'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : currentUserId(),
            'title'       => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'priority'    => $_POST['priority'] ?? 'normal',
            'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'status'      => 'pending'
        ]);

        setFlash('success', 'บันทึกรายการติดตามเรียบร้อย');
        redirect(url('followups'));
    }

    public function updateStatus(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        requireCsrf();

        $stmt=Database::getConnection()->prepare('SELECT assigned_to FROM followups WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$id]); $task=$stmt->fetch();
        if (!$task) { http_response_code(404); exit; }
        if (isTeacher() && (int)$task['assigned_to']!==currentUserId()) { http_response_code(403); exit; }
        if (!in_array($_POST['status']??'', ['pending','in_progress','completed','cancelled'],true)) { setFlash('danger','สถานะติดตามไม่ถูกต้อง'); redirect(url('followups')); }
        $status = $_POST['status'] ?? 'completed';
        $result = trim($_POST['result'] ?? '');

        $this->followupModel->updateStatus($id, $status, $result);

        setFlash('success', 'อัปเดตสถานะการติดตามเรียบร้อย');
        redirect(url('followups'));
    }
}
