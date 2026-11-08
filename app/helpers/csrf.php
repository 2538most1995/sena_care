<?php
/**
 * ============================================================
 * CSRF Protection Helper
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF token and store in session
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input field for forms
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

/**
 * Get CSRF meta tag for AJAX
 */
function csrfMeta(): string
{
    $token = generateCsrfToken();
    return '<meta name="csrf-token" content="' . $token . '">';
}

/**
 * Validate CSRF token from POST or header
 */
function validateCsrfToken(): bool
{
    $token = $_POST['_csrf_token'] 
        ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
        ?? '';

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token, abort with 403 if invalid
 */
function requireCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !validateCsrfToken()) {
        http_response_code(403);
        if (isAjaxRequest()) {
            jsonResponse(false, 'CSRF token ไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บ', null, 403);
        }
        die('403 Forbidden - Invalid CSRF Token');
    }
}

/**
 * Regenerate CSRF token
 */
function regenerateCsrfToken(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
