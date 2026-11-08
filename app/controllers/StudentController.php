<?php
/**
 * ============================================================
 * Student Controller
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class StudentController
{
    private Student $studentModel;
    private StudentAddress $addressModel;
    private StudentContact $contactModel;
    private Enrollment $enrollmentModel;
    private AcademicTerm $termModel;
    private LearningCenter $centerModel;
    private User $userModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->addressModel = new StudentAddress();
        $this->contactModel = new StudentContact();
        $this->enrollmentModel = new Enrollment();
        $this->termModel = new AcademicTerm();
        $this->centerModel = new LearningCenter();
        $this->userModel = new User();
    }

    /**
     * List students with filtering and pagination
     */
    public function index(): void
    {
        requireLogin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1,min(100,(int)($_GET['per_page'] ?? 25)));

        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'term_id'      => $_GET['term_id'] ?? currentTermId(),
            'level'        => $_GET['level'] ?? '',
            'center_id'    => $_GET['center_id'] ?? '',
            'teacher_id'   => isTeacher() ? currentUserId() : ($_GET['teacher_id'] ?? ''),
            'visit_status' => $_GET['visit_status'] ?? '',
            'risk_level'   => $_GET['risk_level'] ?? '',
        ];

        $studentsData = $this->studentModel->getAll($filters, $page, $perPage);
        $terms = $this->termModel->getActiveTerms();
        $centers = $this->centerModel->getActive();
        $teachers = $this->userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'รายชื่อผู้เรียน';
        $currentPage = 'students';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/students/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /** Return a phone for an authorized roster row without delaying the page. */
    public function apiPhone(int $id): void
    {
        header('Cache-Control: private, no-store');
        if (!isLoggedIn()) jsonResponse(false, 'กรุณาเข้าสู่ระบบ', null, 401);
        requireLogin();
        $termId = (int)($_GET['term_id'] ?? currentTermId());
        if ($termId < 1) jsonResponse(false, 'ภาคเรียนไม่ถูกต้อง', null, 422);
        if (!canAccessStudent($id, $termId)) jsonResponse(false, 'ไม่มีสิทธิ์เข้าถึงผู้เรียน', null, 403);
        $student = $this->studentModel->findById($id, $termId);
        if (!$student) jsonResponse(false, 'ไม่พบผู้เรียน', null, 404);

        $phone = $student['phone'] ?? null;
        $link = Database::getConnection()->prepare("SELECT 1 FROM sdl_links WHERE entity='student' AND local_id=? AND source_present=1 LIMIT 1");
        $link->execute([$id]);
        $linked = (bool)$link->fetchColumn();
        $api = (new SdlApiClient())->withTimeout(5);
        // Let other authorized rows load while this request waits for SDL_school.
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        if ($linked && $api->enabled()) {
            require_once APP_ROOT . '/app/services/SdlStudentPhone.php';
            $level = match ($student['education_level'] ?? '') {
                'primary' => 1, 'lower_secondary' => 2, 'upper_secondary' => 3, default => null,
            };
            try {
                $phone = (new SdlStudentPhone($api))->get((string)$student['student_code'], $level) ?? $phone;
            } catch (Throwable $error) {
                error_log('SDL student phone unavailable');
            }
        }
        jsonResponse(true, '', ['display' => formatPhone($phone)]);
    }

    /**
     * Show student profile
     */
    public function show(int $id): void
    {
        requireLogin();

        if (!canAccessStudent($id)) {
            requireRole('admin', 'super_admin', 'executive');
        }

        $termId = currentTermId();
        $student = $this->studentModel->findById($id, $termId);
        if (!$student) {
            setFlash('danger', 'ไม่พบข้อมูลผู้เรียนที่ระบุ');
            redirect(url('students'));
        }

        $address = $this->addressModel->findByStudentId($id, 'home');
        $contacts = $this->contactModel->findByStudentId($id);
        // Read the district-scoped API only after authorizing the local student.
        // Keep local values when a field is absent or the source is unavailable.
        require_once APP_ROOT . '/app/services/SdlStudentProfile.php';
        $link = Database::getConnection()->prepare("SELECT 1 FROM sdl_links WHERE entity='student' AND local_id=? LIMIT 1");
        $link->execute([$id]);
        $api = (new SdlApiClient())->withTimeout(5);
        if ($link->fetchColumn() && $api->enabled()) {
            try {
                $sourceLevel = match ($student['education_level'] ?? '') {
                    'primary' => 1, 'lower_secondary' => 2, 'upper_secondary' => 3, default => null,
                };
                $response = $api->request('students/' . rawurlencode($student['student_code']) . '/profile' . ($sourceLevel ? '?level=' . $sourceLevel : ''));
                $source = $response['data'] ?? [];
                if (is_array($source) && (string)($source['code'] ?? '') === (string)$student['student_code']) {
                    $student = array_replace($student, SdlStudentProfile::fields($source));
                    foreach (SdlStudentProfile::contacts($source) as $contact) {
                        if (!in_array($contact['name'], array_column($contacts, 'name'), true)) $contacts[] = $contact;
                    }
                }
            } catch (Throwable $error) {
                error_log('SDL student profile unavailable');
            }
        }
        $enrollmentHistory = $this->enrollmentModel->getStudentHistory($id);

        // Fetch home visits
        $pdo = Database::getConnection();
        $stmtVisits = $pdo->prepare(
            "SELECT hv.*, u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
             FROM home_visits hv
             JOIN users u ON hv.teacher_id = u.id
             WHERE hv.student_id = ?
             ORDER BY hv.visit_date DESC, hv.id DESC"
        );
        $stmtVisits->execute([$id]);
        $visits = $stmtVisits->fetchAll();

        // Fetch followups
        $stmtFollowups = $pdo->prepare(
            "SELECT f.*, u.firstname AS assigned_firstname, u.lastname AS assigned_lastname
             FROM followups f
             LEFT JOIN users u ON f.assigned_to = u.id
             WHERE f.student_id = ? AND f.deleted_at IS NULL
             ORDER BY f.due_date ASC"
        );
        $stmtFollowups->execute([$id]);
        $followups = $stmtFollowups->fetchAll();

        // Fetch individual survey history (many rounds per enrollment)
        $stmtSurveys = $pdo->prepare(
            "SELECT ss.*, u.firstname AS surveyor_firstname, u.lastname AS surveyor_lastname
             FROM student_surveys ss
             JOIN enrollments e ON e.id = ss.enrollment_id
             JOIN users u ON u.id = ss.surveyed_by
             WHERE e.student_id = ? AND e.academic_term_id = ? AND ss.cancelled_at IS NULL
             ORDER BY ss.surveyed_at DESC, ss.id DESC"
        );
        $stmtSurveys->execute([$id, $termId]);
        $surveys = $stmtSurveys->fetchAll();

        $pageTitle = 'ข้อมูลผู้เรียน: ' . e($student['firstname'] . ' ' . $student['lastname']);
        $currentPage = 'students';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/students/show.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        $this->requireManualStudentEntry();

        $terms = $this->termModel->getActiveTerms();
        $centers = $this->centerModel->getActive();
        $teachers = $this->userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'เพิ่มข้อมูลผู้เรียนใหม่';
        $currentPage = 'students';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/students/create.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Store new student
     */
    public function store(): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        $this->requireManualStudentEntry();
        requireCsrf();

        $val = new Validator($_POST);
        $val->required('student_code', 'รหัสนักศึกษา')
            ->required('firstname', 'ชื่อ')
            ->required('lastname', 'นามสกุล')
            ->required('education_level', 'ระดับการศึกษา')
            ->unique('student_code', 'students', 'student_code', null, 'รหัสนักศึกษา');

        if ($val->fails()) {
            setFlash('danger', $val->firstError());
            redirect(url('students/create'));
        }

        $studentData = [
            'student_code'   => trim($_POST['student_code']),
            'citizen_id'     => trim($_POST['citizen_id'] ?? ''),
            'prefix'         => trim($_POST['prefix'] ?? 'นาย'),
            'firstname'      => trim($_POST['firstname']),
            'lastname'       => trim($_POST['lastname']),
            'nickname'       => trim($_POST['nickname'] ?? ''),
            'gender'         => $_POST['gender'] ?? 'male',
            'birthdate'      => !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
            'phone'          => trim($_POST['phone'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'occupation'     => trim($_POST['occupation'] ?? ''),
            'student_status' => 'active',
        ];

        Database::beginTransaction();
        try {
            $studentId = $this->studentModel->create($studentData);

            // Save Address
            $this->addressModel->saveOrUpdate($studentId, [
                'house_no'    => trim($_POST['house_no'] ?? ''),
                'moo'         => trim($_POST['moo'] ?? ''),
                'village'     => trim($_POST['village'] ?? ''),
                'subdistrict' => trim($_POST['subdistrict'] ?? 'เสนา'),
                'district'    => trim($_POST['district'] ?? 'เสนา'),
                'province'    => trim($_POST['province'] ?? 'พระนครศรีอยุธยา'),
                'zipcode'     => trim($_POST['zipcode'] ?? '13110'),
                'latitude'    => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude'   => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'landmark'    => trim($_POST['landmark'] ?? ''),
            ], 'home');

            // Save Contact
            if (!empty($_POST['parent_name'])) {
                $this->contactModel->savePrimary($studentId, [
                    'name'         => trim($_POST['parent_name']),
                    'relationship' => trim($_POST['parent_relationship'] ?? 'ผู้ปกครอง'),
                    'phone'        => trim($_POST['parent_phone'] ?? ''),
                    'occupation'   => trim($_POST['parent_occupation'] ?? ''),
                ]);
            }

            // Save Enrollment
            $termId = (int)($_POST['academic_term_id'] ?? currentTermId());
            $teacherId = isTeacher() ? currentUserId() : (int)($_POST['teacher_id'] ?? currentUserId());
            $this->enrollmentModel->saveOrUpdate($studentId, $termId, [
                'education_level'    => $_POST['education_level'],
                'learning_center_id' => !empty($_POST['learning_center_id']) ? (int)$_POST['learning_center_id'] : currentLearningCenterId(),
                'teacher_id'         => $teacherId,
                'enrollment_status'  => 'active'
            ]);

            Database::commit();
            setFlash('success', 'บันทึกข้อมูลผู้เรียนสำเร็จ');
            redirect(url('students/' . $studentId));
        } catch (Exception $e) {
            Database::rollBack();
            setFlash('danger', 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage());
            redirect(url('students/create'));
        }
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');

        if (!canAccessStudent($id)) {
            http_response_code(403);
            include APP_ROOT . '/app/views/errors/403.php';
            exit;
        }


        $student = $this->studentModel->findById($id, currentTermId());
        if (!$student) {
            setFlash('danger', 'ไม่พบข้อมูลผู้เรียน');
            redirect(url('students'));
        }

        $address = $this->addressModel->findByStudentId($id, 'home');
        $contact = $this->contactModel->getPrimary($id);
        $terms = $this->termModel->getActiveTerms();
        $centers = $this->centerModel->getActive();
        $teachers = $this->userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'แก้ไขข้อมูลผู้เรียน: ' . e($student['firstname'] . ' ' . $student['lastname']);
        $currentPage = 'students';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/students/edit.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Update student
     */
    public function update(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        requireCsrf();

        if (!canAccessStudent($id)) {
            http_response_code(403);
            include APP_ROOT . '/app/views/errors/403.php';
            exit;
        }

        $sourceLink = Database::getConnection()->prepare("SELECT 1 FROM sdl_links WHERE entity='student' AND local_id=?");
        $sourceLink->execute([$id]);
        $sourceManaged = (bool)$sourceLink->fetchColumn();
        if ($sourceManaged) {
            $existing = $this->studentModel->findById($id, currentTermId());
            $_POST['student_code'] = $existing['student_code'];
        }


        $val = new Validator($_POST);
        $val->required('student_code', 'รหัสนักศึกษา')
            ->required('firstname', 'ชื่อ')
            ->required('lastname', 'นามสกุล')
            ->unique('student_code', 'students', 'student_code', $id, 'รหัสนักศึกษา');

        if ($val->fails()) {
            setFlash('danger', $val->firstError());
            redirect(url('students/' . $id . '/edit'));
        }

        Database::beginTransaction();
        try {
            $this->studentModel->update($id, [
                'student_code' => trim($_POST['student_code']),
                'citizen_id'   => trim($_POST['citizen_id'] ?? ''),
                'prefix'       => trim($_POST['prefix'] ?? 'นาย'),
                'firstname'    => trim($_POST['firstname']),
                'lastname'     => trim($_POST['lastname']),
                'nickname'     => trim($_POST['nickname'] ?? ''),
                'gender'       => $_POST['gender'] ?? 'male',
                'birthdate'    => !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
                'phone'        => trim($_POST['phone'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'occupation'   => trim($_POST['occupation'] ?? ''),
            ]);

            // Update address
            $this->addressModel->saveOrUpdate($id, [
                'house_no'    => trim($_POST['house_no'] ?? ''),
                'moo'         => trim($_POST['moo'] ?? ''),
                'village'     => trim($_POST['village'] ?? ''),
                'subdistrict' => trim($_POST['subdistrict'] ?? ''),
                'district'    => trim($_POST['district'] ?? ''),
                'province'    => trim($_POST['province'] ?? ''),
                'zipcode'     => trim($_POST['zipcode'] ?? ''),
                'latitude'    => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude'   => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'landmark'    => trim($_POST['landmark'] ?? ''),
            ], 'home');

            // Update contact
            if (!empty($_POST['parent_name'])) {
                $this->contactModel->savePrimary($id, [
                    'name'         => trim($_POST['parent_name']),
                    'relationship' => trim($_POST['parent_relationship'] ?? 'ผู้ปกครอง'),
                    'phone'        => trim($_POST['parent_phone'] ?? ''),
                    'occupation'   => trim($_POST['parent_occupation'] ?? ''),
                ]);
            }

            // Update enrollment for current term if set
            if (!$sourceManaged && !empty($_POST['education_level'])) {
                $termId = isTeacher() ? (int)currentTermId() : (int)($_POST['academic_term_id'] ?? currentTermId());
                $teacherId = isTeacher() ? currentUserId() : (int)($_POST['teacher_id'] ?? currentUserId());
                $this->enrollmentModel->saveOrUpdate($id, $termId, [
                    'education_level'    => $_POST['education_level'],
                    'learning_center_id' => !empty($_POST['learning_center_id']) ? (int)$_POST['learning_center_id'] : null,
                    'teacher_id'         => $teacherId,
                    'enrollment_status'  => 'active'
                ]);
            }

            Database::commit();
            setFlash('success', 'อัปเดตข้อมูลผู้เรียนเรียบร้อย');
            redirect(url('students/' . $id));
        } catch (Exception $e) {
            Database::rollBack();
            setFlash('danger', 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage());
            redirect(url('students/' . $id . '/edit'));
        }
    }

    /**
     * Delete student (Soft delete)
     */
    public function delete(int $id): void
    {
        requireRole('super_admin', 'admin');
        requireCsrf();

        $this->studentModel->softDelete($id);
        setFlash('success', 'ลบข้อมูลผู้เรียนเรียบร้อย');
        redirect(url('students'));
    }

    /**
     * Import students from Excel / CSV
     */
    public function import(): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        $this->requireManualStudentEntry();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            requireCsrf();

            if (empty($_FILES['import_file']['tmp_name'])) {
                setFlash('danger', 'กรุณาเลือกไฟล์สำหรับ Import');
                redirect(url('students/import'));
            }

            $termId = (int)($_POST['academic_term_id'] ?? currentTermId());
            $teacherId = isTeacher() ? currentUserId() : (int)($_POST['teacher_id'] ?? currentUserId());
            $centerId = (int)($_POST['learning_center_id'] ?? currentLearningCenterId());

            $file = $_FILES['import_file']['tmp_name'];
            $filename = $_FILES['import_file']['name'];
            $handle = fopen($file, 'r');
            if ($handle === false) {
                setFlash('danger', 'ไม่สามารถเปิดไฟล์ได้');
                redirect(url('students/import'));
            }

            // Read rows
            $rowCount = 0;
            $inserted = 0;
            $skipped = 0;
            $failed = 0;

            // Skip UTF-8 BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Read header
            $header = fgetcsv($handle);

            Database::beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowCount++;
                    if (empty($row[0]) && empty($row[1])) continue;

                    $studentCode = trim($row[0] ?? '');
                    $citizenId = trim($row[1] ?? '');
                    $prefix = trim($row[2] ?? 'นาย');
                    $firstname = trim($row[3] ?? '');
                    $lastname = trim($row[4] ?? '');
                    $level = trim($row[5] ?? 'lower_secondary');
                    $phone = trim($row[6] ?? '');
                    $houseNo = trim($row[7] ?? '');
                    $subdistrict = trim($row[8] ?? 'เสนา');

                    if (empty($studentCode) || empty($firstname) || empty($lastname)) {
                        $failed++;
                        continue;
                    }

                    // Check duplicate
                    $pdo = Database::getConnection();
                    $stmtCheck = $pdo->prepare("SELECT id FROM students WHERE student_code = ?");
                    $stmtCheck->execute([$studentCode]);
                    $existingId = $stmtCheck->fetchColumn();

                    if ($existingId) {
                        $skipped++;
                        continue;
                    }

                    $sId = $this->studentModel->create([
                        'student_code'   => $studentCode,
                        'citizen_id'     => $citizenId,
                        'prefix'         => $prefix,
                        'firstname'      => $firstname,
                        'lastname'       => $lastname,
                        'phone'          => $phone,
                        'student_status' => 'active',
                    ]);

                    $this->addressModel->saveOrUpdate($sId, [
                        'house_no'    => $houseNo,
                        'subdistrict' => $subdistrict,
                        'district'    => 'เสนา',
                        'province'    => 'พระนครศรีอยุธยา',
                        'zipcode'     => '13110',
                    ], 'home');

                    $this->enrollmentModel->saveOrUpdate($sId, $termId, [
                        'education_level'    => $level,
                        'learning_center_id' => $centerId,
                        'teacher_id'         => $teacherId,
                        'enrollment_status'  => 'active'
                    ]);

                    $inserted++;
                }

                // Log import
                $pdo = Database::getConnection();
                $stmtLog = $pdo->prepare(
                    "INSERT INTO import_logs (filename, academic_term_id, total_rows, inserted, updated, skipped, failed, imported_by)
                     VALUES (?, ?, ?, ?, 0, ?, ?, ?)"
                );
                $stmtLog->execute([$filename, $termId, $rowCount, $inserted, $skipped, $failed, currentUserId()]);

                Database::commit();
                fclose($handle);

                setFlash('success', "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ {$inserted} รายการ, ข้ามที่ซ้ำ {$skipped} รายการ, ไม่ถูกต้อง {$failed} รายการ");
                redirect(url('students'));
            } catch (Exception $e) {
                Database::rollBack();
                fclose($handle);
                setFlash('danger', 'เกิดข้อผิดพลาดในการ Import: ' . $e->getMessage());
                redirect(url('students/import'));
            }
        }

        $terms = $this->termModel->getActiveTerms();
        $centers = $this->centerModel->getActive();
        $teachers = $this->userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'นำเข้าข้อมูลผู้เรียน (Excel / CSV)';
        $currentPage = 'students';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/students/import.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }
    private function requireManualStudentEntry(): void
    {
        $config = require APP_ROOT . '/app/config/config.php';
        if (!empty($config['students_api_only'])) {
            setFlash('info', 'ระบบใช้รายชื่อนักศึกษาจาก SDL_school เท่านั้น กรุณาเพิ่มหรือแก้ไขรายชื่อที่ต้นทางแล้วซิงก์ข้อมูล');
            redirect(url('students'));
        }
    }

}
