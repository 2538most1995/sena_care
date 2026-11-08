<?php
/**
 * ============================================================
 * User Controller (Admin only)
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class UserController
{
    private User $userModel;
    private LearningCenter $centerModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->centerModel = new LearningCenter();
    }

    public function index(): void
    {
        requireRole('super_admin', 'admin');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'role'   => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $usersData = $this->userModel->getAll($filters, $page, 25);
        $centers = $this->centerModel->getActive();

        $pageTitle = 'จัดการผู้ใช้งานระบบ';
        $currentPage = 'users';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/users/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function store(): void
    {
        requireRole('super_admin', 'admin');
        requireCsrf();

        setFlash('info','ครูนำเข้าจาก SDL_school API เท่านั้น กรุณาใช้เมนูเชื่อมต่อเพื่อซิงก์ข้อมูล');
        redirect(url('users'));

    }

    private function editableAccount(int $id): array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE id=?');
        $stmt->execute([$id]); $account = $stmt->fetch();
        if (!$account) { http_response_code(404); include APP_ROOT.'/app/views/errors/404.php'; exit; }
        if ($account['role'] === 'super_admin' && !isSuperAdmin()) {
            http_response_code(403); include APP_ROOT.'/app/views/errors/403.php'; exit;
        }
        return $account;
    }

    public function edit(int $id): void
    {
        requireRole('super_admin','admin');
        $account = $this->editableAccount($id);
        $linked = (new SdlSyncService())->linkedUser($id);
        $pageTitle = 'แก้ไขชื่อผู้ใช้และรหัสผ่าน'; $currentPage = 'users';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/users/edit.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    public function update(int $id): void
    {
        requireRole('super_admin','admin');
        requireCsrf();
        $account = $this->editableAccount($id);
        if ((new SdlSyncService())->linkedUser($id)) {
            setFlash('danger','บัญชีนี้เชื่อมต่อ SDL_school กรุณาเปลี่ยนชื่อผู้ใช้และรหัสผ่านที่ระบบต้นทาง');
            redirect(url('users/'.$id.'/edit'));
        }
        $val = new Validator($_POST);
        $val->required('username','ชื่อผู้ใช้')->maxLength('username',100,'ชื่อผู้ใช้')
            ->unique('username','users','username',$id,'ชื่อผู้ใช้');
        $password = (string)($_POST['new_password'] ?? '');
        $confirmation = (string)($_POST['password_confirmation'] ?? '');
        $error = $val->fails() ? $val->firstError() : null;
        if ($password !== '' && mb_strlen($password) < 8) $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
        if ($password !== $confirmation) $error = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
        foreach (['prefix'=>30,'firstname'=>100,'lastname'=>100,'email'=>255,'phone'=>20] as $field=>$limit) {
            if (isset($_POST[$field]) && (!is_string($_POST[$field]) || mb_strlen($_POST[$field]) > $limit)) $error='ข้อมูลส่วนตัวมีความยาวเกินกำหนด';
        }
        foreach (['firstname','lastname'] as $field) if (isset($_POST[$field]) && trim((string)$_POST[$field]) === '') $error='กรุณากรอกชื่อและนามสกุล';
        if (!empty($_POST['email'])) {
            if (!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)) $error='รูปแบบอีเมลไม่ถูกต้อง';
            $existing=$this->userModel->findByEmail($_POST['email']);
            if ($existing && (int)$existing['id'] !== $id) $error='อีเมลนี้มีผู้ใช้งานแล้ว';
        }
        if (strlen($password)>72) $error='รหัสผ่านต้องไม่เกิน 72 ไบต์';
        if ($error) {
            setFlash('danger',$error); redirect(url('users/'.$id.'/edit'));
        }
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $username = trim($_POST['username']);
            $data = ['username'=>$username];
            foreach (['prefix'=>30,'firstname'=>100,'lastname'=>100,'email'=>255,'phone'=>20] as $field=>$limit) {
                if (array_key_exists($field,$_POST)) {
                    $data[$field] = trim((string)$_POST[$field]);
                    if ($field==='email' && $data[$field]==='') $data[$field]=null;
                }
            }
            $this->userModel->update($id,$data);
            if ($password !== '') $this->userModel->updatePassword($id,$password);
            $pdo->commit();
            if ($id === currentUserId()) {
                foreach ($data as $key=>$value) $_SESSION['user'][$key]=$value;
            }
            setFlash('success','บันทึกชื่อผู้ใช้และรหัสผ่านเรียบร้อยแล้ว ใช้ข้อมูลใหม่ในการเข้าสู่ระบบครั้งถัดไป');
            redirect(url('users'));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('User credential update failed: '.$e->getMessage());
            setFlash('danger','บันทึกไม่สำเร็จ ชื่อผู้ใช้อาจถูกใช้แล้ว กรุณาลองใหม่อีกครั้ง');
            redirect(url('users/'.$id.'/edit'));
        }
    }
}
