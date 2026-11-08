<?php
/**
 * ============================================================
 * ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. (SENA Care)
 * Front Controller & Application Router
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// 1. Load Configuration
$config = require APP_ROOT . '/app/config/config.php';

// 2. Error Reporting
if ($config['app_debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 3. Timezone
date_default_timezone_set($config['app_timezone'] ?? 'Asia/Bangkok');

// 4. Session Setup
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name'] ?? 'sena_care_session');
    session_start();
}

// 5. Load Database Singleton
require_once APP_ROOT . '/app/config/database.php';

// 6. Load Helpers
require_once APP_ROOT . '/app/helpers/auth.php';
require_once APP_ROOT . '/app/helpers/csrf.php';
require_once APP_ROOT . '/app/helpers/response.php';
require_once APP_ROOT . '/app/helpers/validation.php';

// 7. Load Middleware
require_once APP_ROOT . '/app/middleware/AuthMiddleware.php';
require_once APP_ROOT . '/app/middleware/RoleMiddleware.php';

// 8. Load Models
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/AcademicTerm.php';
require_once APP_ROOT . '/app/models/Organization.php';
require_once APP_ROOT . '/app/models/LearningCenter.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/StudentAddress.php';
require_once APP_ROOT . '/app/models/StudentContact.php';
require_once APP_ROOT . '/app/models/Enrollment.php';
require_once APP_ROOT . '/app/models/VisitPlan.php';
require_once APP_ROOT . '/app/models/HomeVisit.php';
require_once APP_ROOT . '/app/models/Followup.php';

// 9. Load Services
require_once APP_ROOT . '/app/services/SdlApiClient.php';
require_once APP_ROOT . '/app/services/SdlSyncService.php';
require_once APP_ROOT . '/app/services/AuthService.php';

// 10. Load Controllers
require_once APP_ROOT . '/app/controllers/AuthController.php';
require_once APP_ROOT . '/app/controllers/DashboardController.php';
require_once APP_ROOT . '/app/controllers/StudentController.php';
require_once APP_ROOT . '/app/controllers/MapController.php';
require_once APP_ROOT . '/app/controllers/VisitController.php';
require_once APP_ROOT . '/app/controllers/FollowupController.php';
require_once APP_ROOT . '/app/controllers/ReportController.php';
require_once APP_ROOT . '/app/controllers/UserController.php';
require_once APP_ROOT . '/app/controllers/SettingController.php';

require_once APP_ROOT.'/app/controllers/SurveyController.php';
require_once APP_ROOT.'/app/controllers/GraduateController.php';

// 11. Parse Route
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// Clean subfolder prefix (e.g. /sena_care or /sena_care/public)
$basePath = parse_url($config['app_url'], PHP_URL_PATH) ?? '';
if (!empty($basePath) && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
if (strpos($path, '/sena_care') === 0) {
    $path = substr($path, 10);
}
if (strpos($path, '/public') === 0) {
    $path = substr($path, 7);
}
$path = '/' . trim($path, '/');

// Check Query Parameter Fallback (?page=...)
if (isset($_GET['page']) && !empty($_GET['page']) && !ctype_digit((string)$_GET['page'])) {
    $path = '/' . trim($_GET['page'], '/');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 12. Dispatch Routes
try {
    // ── Auth ──
    if ($path === '/login') {
        $c = new AuthController();
        if ($method === 'POST') {
            $c->processLogin();
        } else {
            $c->showLogin();
        }
        exit;
    }

    if ($path === '/logout') {
        $c = new AuthController();
        $c->logout();
        exit;
    }

    // ── Dashboard ──
    if ($path === '/' || $path === '/dashboard') {
        $c = new DashboardController();
        $c->index();
        exit;
    }

    if ($path === '/api/dashboard/stats') {
        $c = new DashboardController();
        $c->apiStats();
        exit;
    }

    // ── Students ──
    if ($method === 'GET' && preg_match('#^/api/students/(\d+)/phone$#', $path, $matches)) {
        (new StudentController())->apiPhone((int)$matches[1]);
        exit;
    }

    if ($path === '/students') {
        $c = new StudentController();
        $c->index();
        exit;
    }

    if ($path === '/students/create') {
        $c = new StudentController();
        if ($method === 'POST') {
            $c->store();
        } else {
            $c->create();
        }
        exit;
    }

    if ($path === '/students/import') {
        $c = new StudentController();
        $c->import();
        exit;
    }

    // /students/{id}/edit
    if (preg_match('#^/students/(\d+)/edit$#', $path, $matches)) {
        $c = new StudentController();
        if ($method === 'POST') {
            $c->update((int)$matches[1]);
        } else {
            $c->edit((int)$matches[1]);
        }
        exit;
    }

    // /students/{id}/delete
    if (preg_match('#^/students/(\d+)/delete$#', $path, $matches)) {
        $c = new StudentController();
        $c->delete((int)$matches[1]);
        exit;
    }

    // /students/{id}
    if (preg_match('#^/students/(\d+)$#', $path, $matches)) {
        $c = new StudentController();
        $c->show((int)$matches[1]);
        exit;
    }

    // ── Map ──
    if ($path === '/map') {
        $c = new MapController();
        $c->index();
        exit;
    }

    if ($path === '/api/map/students') {
        $c = new MapController();
        $c->apiStudents();
        exit;
    }

    // ── Visits ──
    if ($path === '/visits') {
        $c = new VisitController();
        $c->index();
        exit;
    }

    if ($path === '/visits/history') { (new VisitController())->history(); exit; }
    if ($path === '/visit-plans') { requireLogin(); redirect(url('visits')); }
    if ($path === '/visit-plans/create') {
        requireLogin();
        if ($method === 'GET') redirect(url('visits'));
        requireCsrf(); http_response_code(410); echo 'ยกเลิกการวางแผนเยี่ยมแล้ว กรุณาบันทึกเยี่ยมบ้านจากรายชื่อผู้เรียน'; exit;
    }

    // /visits/create/{studentId}
    if (preg_match('#^/visits/create/(\d+)$#', $path, $matches)) {
        $c = new VisitController();
        if ($method === 'POST') {
            $c->store((int)$matches[1]);
        } else {
            $c->create((int)$matches[1]);
        }
        exit;
    }

    // /visits/{id}/edit
    if (preg_match('#^/visits/(\d+)/edit$#', $path, $matches)) {
        $c = new VisitController();
        if ($method === 'POST') {
            $c->update((int)$matches[1]);
        } else {
            $c->edit((int)$matches[1]);
        }
        exit;
    }

    // /visits/{id}/cancel  (soft cancel, district admins only)
    if (preg_match('#^/visits/(\d+)/cancel$#', $path, $matches)) {
        (new VisitController())->cancel((int)$matches[1]);
        exit;
    }

    if (preg_match('#^/visits/(\d+)/pdf$#', $path, $matches)) {
        if ($method !== 'GET') { http_response_code(405); header('Allow: GET'); exit; }
        (new VisitController())->pdf((int)$matches[1]);
        exit;
    }

    // /visits/{id}
    if (preg_match('#^/visits/(\d+)$#', $path, $matches)) {
        $c = new VisitController();
        $c->show((int)$matches[1]);
        exit;
    }

    // ── Follow-ups ──
    if ($path === '/followups') {
        $c = new FollowupController();
        $c->index();
        exit;
    }

    if ($path === '/followups/create') {
        $c = new FollowupController();
        $c->store();
        exit;
    }

    if (preg_match('#^/followups/(\d+)/update-status$#', $path, $matches)) {
        $c = new FollowupController();
        $c->updateStatus((int)$matches[1]);
        exit;
    }

    // ── Graduates (ติดตามผู้สำเร็จการศึกษา) ──
    if ($path === '/graduates') {
        (new GraduateController())->index();
        exit;
    }
    if ($path === '/graduates/sync') {
        (new GraduateController())->sync();
        exit;
    }
    if (preg_match('#^/graduates/(\d+)$#', $path, $matches)) {
        (new GraduateController())->show((int)$matches[1]);
        exit;
    }
    if (preg_match('#^/graduates/(\d+)/track/(\d+)/edit$#', $path, $matches)) {
        (new GraduateController())->editTracking((int)$matches[1], (int)$matches[2]);
        exit;
    }
    if (preg_match('#^/graduates/(\d+)/track$#', $path, $matches)) {
        (new GraduateController())->storeTracking((int)$matches[1]);
        exit;
    }

    if ($path === '/surveys') { (new SurveyController())->index(); exit; }
    if (preg_match('#^/surveys/view/(\d+)$#',$path,$matches)) { (new SurveyController())->detail((int)$matches[1]); exit; }
    if (preg_match('#^/surveys/cancel/(\d+)$#',$path,$matches)) { (new SurveyController())->cancel((int)$matches[1]); exit; }
    if (preg_match('#^/surveys/(\d+)$#',$path,$matches)) {
        if (!in_array($method,['GET','POST'],true)) { http_response_code(405); exit; }
        (new SurveyController())->form((int)$matches[1]); exit;
    }

    if ($path === '/settings') {
        (new SettingController())->settings(); exit;
    }
    if ($path === '/profile' || $path === '/change-password') {
        requireLogin();
        if (isAdmin()) redirect(url('users/'.currentUserId().'/edit'));
        $pageTitle='ข้อมูลบัญชีของฉัน'; $currentPage='';
        include APP_ROOT.'/app/views/layouts/header.php'; include APP_ROOT.'/app/views/users/profile.php'; include APP_ROOT.'/app/views/layouts/footer.php'; exit;
    }
    if ($path === '/notifications/read-all') {
        requireLogin(); requireCsrf();
        if ($method !== 'POST') { http_response_code(405); exit; }
        $stmt=Database::getConnection()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?');
        $stmt->execute([currentUserId()]); redirect(url('dashboard'));
    }

    // ── Reports ──
    if ($path === '/reports/teachers' || $path === '/reports/teachers/pdf') {
        (new ReportController())->teachers($path === '/reports/teachers/pdf');
        exit;
    }

    if ($path === '/reports') {
        $c = new ReportController();
        $c->index();
        exit;
    }

    if ($path === '/reports/export') {
        $c = new ReportController();
        $c->export();
        exit;
    }

    // ── Users ──
    if ($path === '/users') {
        $c = new UserController();
        $c->index();
        exit;
    }

    if ($path === '/users/create') {
        $c = new UserController();
        $c->store();
        exit;
    }

    if (preg_match('#^/users/(\d+)/edit$#', $path, $matches)) {
        $c = new UserController();
        if ($method === 'GET') $c->edit((int)$matches[1]);
        elseif ($method === 'POST') $c->update((int)$matches[1]);
        else http_response_code(405);
        exit;
    }

    if ($path === '/integrations/sdl') {
        (new SettingController())->sdl();
        exit;
    }

    // ── Settings (Terms & Centers) ──
    if ($path === '/terms') {
        $c = new SettingController();
        $c->terms();
        exit;
    }

    if ($path === '/terms/create') {
        $c = new SettingController();
        $c->storeTerm();
        exit;
    }

    if ($path === '/terms/switch') {
        $c = new SettingController();
        $c->switchTerm();
        exit;
    }

    if ($path === '/centers') {
        $c = new SettingController();
        $c->centers();
        exit;
    }

    if ($path === '/centers/create') {
        $c = new SettingController();
        $c->storeCenter();
        exit;
    }

    if ($path === '/centers/windows') {
        (new SettingController())->windows();
        exit;
    }

    // ── 404 Fallback ──
    http_response_code(404);
    include APP_ROOT . '/app/views/errors/404.php';
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = $config['app_debug'] ? $e->getMessage() : 'เกิดข้อผิดพลาดภายในระบบ';
    include APP_ROOT . '/app/views/errors/500.php';
    exit;
}
