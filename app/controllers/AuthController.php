<?php
/**
 * ============================================================
 * Auth Controller - Login / Logout
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Show login page
     */
    public function showLogin(): void
    {
        if (isLoggedIn()) {
            redirect(url('dashboard'));
        }

        $pageTitle = 'เข้าสู่ระบบ';
        $hideLayout = true;
        include APP_ROOT . '/app/views/auth/login.php';
    }

    /**
     * Process login
     */
    public function processLogin(): void
    {
        requireCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            setFlash('danger', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            redirect(url('login'));
        }

        $result = $this->authService->login($username, $password, $remember);

        if ($result['success']) {
            $redirectTo = $_SESSION['redirect_after_login'] ?? url('dashboard');
            unset($_SESSION['redirect_after_login']);
            setFlash('success', 'เข้าสู่ระบบสำเร็จ');
            redirect($redirectTo);
        } else {
            setFlash('danger', $result['message']);
            redirect(url('login'));
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        redirect(url('login'));
    }
}
