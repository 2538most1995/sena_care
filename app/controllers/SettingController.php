<?php
/**
 * ============================================================
 * Setting Controller (Academic Terms & Learning Centers)
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class SettingController
{
    private AcademicTerm $termModel;
    private LearningCenter $centerModel;

    public function __construct()
    {
        $this->termModel = new AcademicTerm();
        $this->centerModel = new LearningCenter();
    }

    /**
     * Switch current working term in session
     */
    public function switchTerm(): void
    {
        requireLogin();
        requireCsrf();
        $termId = (int)($_POST['term_id'] ?? $_GET['term_id'] ?? 0);
        $term = $this->termModel->findById($termId);
        if ($term) {
            $_SESSION['current_term_id'] = (int)$term['id'];
            $_SESSION['current_term_name'] = $term['term_name'];
            setFlash('success', 'เปลี่ยนภาคเรียนทำงานเป็น: ' . $term['term_name']);
        }
        redirect(url('dashboard'));
    }

    /**
     * Terms management
     */
    public function terms(): void
    {
        requireRole('super_admin', 'admin');
        $terms = $this->termModel->getAll();
        $pageTitle = 'จัดการภาคเรียน';
        $currentPage = 'terms';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/settings/terms.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function storeTerm(): void
    {
        requireRole('super_admin', 'admin');
        requireCsrf();

        $val=new Validator($_POST);
        $val->required('term_name','ชื่อภาคเรียน')->required('academic_year','ปีการศึกษา')->required('term_no','ภาคเรียน');
        if ($val->fails() || !in_array((int)($_POST['term_no']??0),[1,2],true)) { setFlash('danger','กรุณากรอกข้อมูลภาคเรียนให้ครบและถูกต้อง'); redirect(url('terms')); }
        $pdo=Database::getConnection();
        try {
            $pdo->beginTransaction();
            $isCurrent=!empty($_POST['is_current'])?1:0;
            if ($isCurrent) $pdo->exec('UPDATE academic_terms SET is_current=0');
            $stmt=$pdo->prepare("INSERT INTO academic_terms(term_name,academic_year,term_no,start_date,end_date,is_current,status) VALUES(?,?,?,?,?,?,'active')");
            $stmt->execute([trim($_POST['term_name']),(int)$_POST['academic_year'],(int)$_POST['term_no'],($_POST['start_date']??'')?:null,($_POST['end_date']??'')?:null,$isCurrent]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash('danger','ภาคเรียนนี้มีอยู่แล้ว หรือข้อมูลวันที่ไม่ถูกต้อง'); redirect(url('terms'));
        }

        setFlash('success', 'เพิ่มภาคเรียนเรียบร้อย');
        redirect(url('terms'));
    }

    /**
     * Learning Centers management
     */
    public function centers(): void
    {
        requireRole('super_admin', 'admin');
        $centers = $this->centerModel->getAll(isSuperAdmin() ? null : currentOrganizationId());
        $pageTitle = 'จัดการ ศกร.ระดับตำบล';
        $currentPage = 'centers';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/settings/centers.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function storeCenter(): void
    {
        requireRole('super_admin', 'admin');
        requireCsrf();

        $this->centerModel->create([
            'organization_id' => currentOrganizationId() ?: 1,
            'center_code'     => trim($_POST['center_code'] ?? ''),
            'center_name'     => trim($_POST['center_name']),
            'subdistrict'     => trim($_POST['subdistrict'] ?? ''),
            'district'        => trim($_POST['district'] ?? 'เสนา'),
            'province'        => trim($_POST['province'] ?? 'พระนครศรีอยุธยา'),
            'phone'           => trim($_POST['phone'] ?? ''),
            'status'          => 'active'
        ]);

        setFlash('success', 'เพิ่ม ศกร.ระดับตำบล เรียบร้อย');
        redirect(url('centers'));
    }

    /**
     * Activity windows for home visits & individual surveys (district admins only).
     * GET renders the management form; POST applies bulk toggles or selected changes.
     */
    public function windows(): void
    {
        requireRole('super_admin', 'admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrf();
            $action = $_POST['action'] ?? 'save_selected';
            try {
                switch ($action) {
                    case 'open_all':
                        $this->centerModel->setEnabledAll('visit', true);
                        $this->centerModel->setEnabledAll('survey', true);
                        setFlash('success', 'เปิดการเยี่ยมบ้านและการสำรวจทุกศกร.แล้ว');
                        break;
                    case 'close_all':
                        $this->centerModel->setEnabledAll('visit', false);
                        $this->centerModel->setEnabledAll('survey', false);
                        setFlash('success', 'ปิดการเยี่ยมบ้านและการสำรวจทุกศกร.แล้ว');
                        break;
                    case 'open_visits':
                        $this->centerModel->setEnabledAll('visit', true);
                        setFlash('success', 'เปิดการเยี่ยมบ้านทุกศกร.แล้ว');
                        break;
                    case 'close_visits':
                        $this->centerModel->setEnabledAll('visit', false);
                        setFlash('success', 'ปิดการเยี่ยมบ้านทุกศกร.แล้ว');
                        break;
                    case 'open_surveys':
                        $this->centerModel->setEnabledAll('survey', true);
                        setFlash('success', 'เปิดการสำรวจทุกศกร.แล้ว');
                        break;
                    case 'close_surveys':
                        $this->centerModel->setEnabledAll('survey', false);
                        setFlash('success', 'ปิดการสำรวจทุกศกร.แล้ว');
                        break;
                    case 'apply_all_schedule':
                        $schedule = [
                            'visit_open_at'   => $_POST['global_visit_open_at'] ?? null,
                            'visit_close_at'  => $_POST['global_visit_close_at'] ?? null,
                            'survey_open_at'  => $_POST['global_survey_open_at'] ?? null,
                            'survey_close_at' => $_POST['global_survey_close_at'] ?? null,
                        ];
                        $hasAny = false;
                        foreach ($schedule as $value) {
                            if (trim((string)$value) !== '') { $hasAny = true; break; }
                        }
                        if (!$hasAny) {
                            setFlash('warning', 'กรุณากำหนดวันเวลาอย่างน้อยหนึ่งช่อง');
                            break;
                        }
                        $this->centerModel->applyScheduleAll($schedule);
                        setFlash('success', 'ใช้ช่วงเวลาเปิด–ปิดกับทุกศกร.แล้ว');
                        break;
                    default:
                        $ids = array_values(array_filter(array_map('intval', (array)($_POST['center_ids'] ?? []))));
                        $count = 0;
                        foreach ($ids as $id) {
                            $this->centerModel->updateWindows($id, [
                                'visit_enabled'   => isset($_POST['visit_enabled_' . $id]),
                                'visit_open_at'   => $_POST['visit_open_at_' . $id] ?? null,
                                'visit_close_at'  => $_POST['visit_close_at_' . $id] ?? null,
                                'survey_enabled'  => isset($_POST['survey_enabled_' . $id]),
                                'survey_open_at'  => $_POST['survey_open_at_' . $id] ?? null,
                                'survey_close_at' => $_POST['survey_close_at_' . $id] ?? null,
                            ]);
                            $count++;
                        }
                        setFlash($count ? 'success' : 'warning', $count ? 'บันทึกช่วงเวลาเปิด–ปิด ' . $count . ' ศกร.แล้ว' : 'กรุณาเลือกศกร.ที่ต้องการบันทึก');
                }
            } catch (Throwable $e) {
                error_log('Activity windows: ' . $e->getMessage());
                setFlash('danger', 'บันทึกไม่สำเร็จ กรุณาลองใหม่');
            }
            redirect(url('centers/windows'));
        }

        $centers = $this->centerModel->getAll(isSuperAdmin() ? null : currentOrganizationId());
        $pageTitle = 'ช่วงเวลาเปิด–ปิดการเยี่ยมบ้าน/การสำรวจ';
        $currentPage = 'centers';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/settings/windows.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function sdl(): void
    {
        requireRole('super_admin');
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrf();
            try {
                if (($_POST['action'] ?? '') === 'connect_remote') {
                    $token = trim($_POST['api_token'] ?? '');
                    if (!preg_match('/^sdl_student_[A-Za-z0-9]{64}$/', $token)) {
                        throw new RuntimeException('กรุณากรอก API token ที่สร้างจาก SDL_school');
                    }
                    if ($pdo->query("SELECT COUNT(*) FROM sdl_links WHERE entity='student'")->fetchColumn()) {
                        $existing = require APP_ROOT . '/app/config/sdl.local.php';
                        if (($existing['mode'] ?? '') !== 'remote') throw new RuntimeException('มีข้อมูลที่เชื่อมจากเครื่องนี้แล้ว กรุณาตรวจสอบการเปลี่ยนแหล่งข้อมูลก่อน');
                    }
                    $settings = ['enabled'=>true, 'mode'=>'remote', 'base_url'=>'https://krumost.com/SDL_school/api/v1/integrations/student-data', 'resolve_ip'=>PHP_OS_FAMILY === 'Darwin' ? '147.50.255.13' : null, 'token'=>$token];
                    $client = new SdlApiClient($settings);
                    $client->request('teachers?per_page=1');
                    $client->request('groups');
                    $target = APP_ROOT . '/app/config/sdl.local.php';
                    $temporary = tempnam(dirname($target), '.sdl-');
                    if ($temporary === false) throw new RuntimeException('ไม่สามารถบันทึกการตั้งค่าได้');
                    chmod($temporary, 0600);
                    $contents = "<?php\nif (!defined('APP_ROOT')) die('Direct access not permitted');\nreturn " . var_export($settings, true) . ";\n";
                    if (file_put_contents($temporary, $contents, LOCK_EX) === false || !rename($temporary, $target)) {
                        @unlink($temporary);
                        throw new RuntimeException('ไม่สามารถบันทึกการตั้งค่าได้');
                    }
                    setFlash('success', 'เชื่อมต่อเว็บจริงสำเร็จ กดดึงข้อมูลล่าสุดเพื่อนำเข้า');
                    redirect(url('integrations/sdl'));
                }
                $summary = (new SdlSyncService())->sync();
                $current = $this->termModel->getCurrent();
                if ($current) {
                    $_SESSION['current_term_id'] = (int)$current['id'];
                    $_SESSION['current_term_name'] = $current['term_name'];
                }
                (new AuthService())->logAudit(currentUserId(), 'sdl_sync', null, null, null, $summary);
                setFlash('success', 'ซิงก์สำเร็จ: นักศึกษา ' . $summary['students'] . ' คน ครู ' . $summary['teachers'] . ' คน กลุ่มเรียน ' . $summary['groups'] . ' กลุ่ม');
            } catch (Throwable $e) {
                error_log('SDL sync failed: ' . $e->getMessage());
                setFlash('danger', 'ซิงก์ไม่สำเร็จ: ' . $e->getMessage());
            }
            redirect(url('integrations/sdl'));
        }
        $sdlSettings = is_file(APP_ROOT . '/app/config/sdl.local.php') ? require APP_ROOT . '/app/config/sdl.local.php' : [];
        $enabled = (new SdlApiClient())->enabled();
        $sourceMode = $sdlSettings['mode'] ?? 'remote';
        $lastRun = $pdo->query('SELECT * FROM sdl_sync_runs ORDER BY id DESC LIMIT 1')->fetch();
        $summary = $lastRun ? json_decode($lastRun['summary'], true) : null;
        $unassignedGroups = $pdo->query("SELECT COALESCE(lc.center_name,'ไม่ระบุกลุ่ม') AS group_name, COUNT(*) AS students
            FROM enrollments e JOIN sdl_links sl ON sl.entity='student' AND sl.local_id=e.student_id
            LEFT JOIN learning_centers lc ON lc.id=e.learning_center_id
            WHERE e.academic_term_id=(SELECT id FROM academic_terms WHERE is_current=1 LIMIT 1)
            AND sl.source_present=1
            AND NOT EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id)
            GROUP BY lc.center_name ORDER BY lc.center_name")->fetchAll();
        $pageTitle = 'เชื่อมต่อ SDL_school';
        $currentPage = 'sdl';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/settings/sdl.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

}
