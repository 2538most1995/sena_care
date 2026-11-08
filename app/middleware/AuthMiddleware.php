<?php
/**
 * ============================================================
 * Auth Middleware
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class AuthMiddleware
{
    /**
     * Handle authentication check
     */
    public static function handle(): void
    {
        if (!isLoggedIn()) {
            if (isAjaxRequest()) {
                jsonResponse(false, 'กรุณาเข้าสู่ระบบก่อนทำรายการ', null, 401);
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            setFlash('warning', 'กรุณาเข้าสู่ระบบก่อนทำรายการ');
            redirect(url('login'));
        }
    }
}
