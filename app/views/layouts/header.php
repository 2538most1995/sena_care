<?php
/**
 * Layout Header - HTML Head + Start of Body
 */
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
$config = require APP_ROOT . '/app/config/config.php';
$appName = $config['app_short'];
$baseUrl = rtrim($config['app_url'], '/');
$rawTitle = $pageTitle ?? '';
$displayTitle = !empty($rawTitle) ? $rawTitle : $appName;
$browserTitle = !empty($rawTitle) ? $rawTitle . ' | ' . $appName : $appName;
$flash = getFlash();
$unreadCount = 0;
if (isLoggedIn()) {
    if (!class_exists('AuthService')) {
        require_once APP_ROOT . '/app/services/AuthService.php';
    }
    $authSvc = new AuthService();
    $unreadCount = $authSvc->getUnreadNotificationCount(currentUserId());
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.">
    <meta name="theme-color" content="#04395e">
    <?= csrfMeta() ?>
    <title><?= e($browserTitle) ?></title>

    <!-- Google Fonts - Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <!-- Bootstrap Icons (Local) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Leaflet CSS (Local) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/leaflet/leaflet.css">

    <!-- Custom CSS (SENA Care Modern Theme) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= @filemtime(APP_ROOT . '/public/assets/css/style.css') ?: '1.0.1' ?>">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
</head>
<body>
<?php if (isLoggedIn() && empty($hideLayout)): ?>
<div class="app-wrapper" id="appWrapper">
    <!-- Sidebar - Desktop -->
    <?php include APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <?php include APP_ROOT . '/app/views/layouts/navbar.php'; ?>

        <!-- Page Content -->
        <div class="page-content">
            <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mx-3 mt-3 mb-0" role="alert" id="flashAlert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
<?php endif; ?>
