<?php
/**
 * ============================================================
 * ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.
 * Application Configuration
 * ============================================================
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Auto-detect base URL dynamically to support both / and /sena_care/ DocumentRoots
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
$appRoot = str_replace('\\', '/', APP_ROOT);

$subPath = '';
if (!empty($docRoot) && strpos($appRoot, $docRoot) === 0) {
    $subPath = substr($appRoot, strlen($docRoot));
}
$autoAppUrl = rtrim($protocol . $host . $subPath, '/');

return [
    // ── Application ──
    'app_name'    => 'ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.',
    'app_short'   => 'SENA Care',
    'app_version' => '1.0.0',
    'students_api_only' => true,
    'teachers_api_only' => true,
    'app_url'     => $autoAppUrl ?: 'http://localhost:8888',
    'app_env'     => 'development', // development | production
    'app_debug'   => true,
    'app_timezone' => 'Asia/Bangkok',

    // ── Database ──
    'db_host'     => '127.0.0.1',
    'db_port'     => '8889',
    'db_name'     => 'sena_care',
    'db_user'     => 'root',
    'db_password' => 'root',
    'db_charset'  => 'utf8mb4',

    // ── Upload ──
    'upload_path'      => APP_ROOT . '/public/uploads',
    'private_upload'   => APP_ROOT . '/storage/private_uploads',
    'max_upload_size'  => 10 * 1024 * 1024, // 10 MB
    'allowed_image_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'allowed_doc_ext'   => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
    'image_max_width'   => 1600,
    'image_quality'     => 85,
    'thumb_width'       => 300,
    // Graduate tracking photos are downscaled to save server space.
    'graduate_image_max_width' => 1000,
    'graduate_image_quality'   => 70,

    // ── Session ──
    'session_name'    => 'sena_care_session',
    'session_lifetime' => 7200, // 2 hours
    'remember_lifetime' => 30 * 24 * 3600, // 30 days

    // ── Security ──
    'login_max_attempts' => 5,
    'login_lockout_time' => 900, // 15 minutes
    'csrf_token_name'    => '_csrf_token',

    // ── Pagination ──
    'per_page_options' => [10, 25, 50, 100],
    'default_per_page' => 25,

    // ── Map ──
    'default_lat' => 17.4156,
    'default_lng' => 102.7872,
    'default_zoom' => 12,

    // ── Log ──
    'log_path' => APP_ROOT . '/storage/logs',
];
