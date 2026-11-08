<?php
/**
 * ============================================================
 * Role Middleware
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class RoleMiddleware
{
    /**
     * Handle role authorization check
     */
    public static function handle(string ...$allowedRoles): void
    {
        AuthMiddleware::handle();

        if (!hasRole(...$allowedRoles)) {
            http_response_code(403);
            if (isAjaxRequest()) {
                jsonResponse(false, 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้', null, 403);
            }
            include APP_ROOT . '/app/views/errors/403.php';
            exit;
        }
    }
}
