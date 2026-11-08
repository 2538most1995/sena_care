<?php
/**
 * Top Navbar
 */
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$user = currentUser();
?>
<header class="top-navbar" id="topNavbar">
    <div class="navbar-left">
        <button class="menu-toggle" id="menuToggle" aria-label="เมนู">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title d-none d-md-block"><?= e($displayTitle ?? $pageTitle ?? 'SENA Care') ?></h1>
    </div>

    <div class="navbar-center d-md-none">
        <span class="mobile-title"><?= e($displayTitle ?? $pageTitle ?? 'SENA Care') ?></span>
    </div>

    <div class="navbar-right">
        <!-- Search Toggle -->
        <button class="navbar-btn d-none d-md-flex" id="globalSearchToggle" title="ค้นหา" data-bs-toggle="modal" data-bs-target="#globalSearchModal">
            <i class="bi bi-search"></i>
        </button>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="navbar-btn position-relative" data-bs-toggle="dropdown" aria-label="การแจ้งเตือน">
                <i class="bi bi-bell"></i>
                <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">การแจ้งเตือน</span>
                    <?php if ($unreadCount > 0): ?>
                    <form method="post" action="<?= e(url('notifications/read-all')) ?>"><?= csrfField() ?><button type="submit" class="btn btn-sm btn-link p-0">อ่านทั้งหมด</button></form>
                    <?php endif; ?>
                </div>
                <div class="notification-list" id="notificationList">
                    <?php $notifications=(new AuthService())->getRecentNotifications(currentUserId()); ?>
                    <?php foreach($notifications as $notification): ?>
                    <div class="px-3 py-2 border-top"><strong class="small"><?= e($notification['title']) ?></strong><p class="small text-muted mb-0"><?= e($notification['message']) ?></p></div>
                    <?php endforeach; ?>
                    <?php if(!$notifications): ?><div class="text-center text-muted py-4"><small>ไม่มีการแจ้งเตือนใหม่</small></div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="dropdown">
            <button class="navbar-btn user-menu-btn" data-bs-toggle="dropdown" aria-label="เมนูผู้ใช้">
                <div class="user-avatar-sm">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($baseUrl . '/public/uploads/' . $user['avatar']) ?>" alt="avatar">
                    <?php else: ?>
                    <i class="bi bi-person-fill"></i>
                    <?php endif; ?>
                </div>
                <span class="d-none d-lg-inline ms-2"><?= e($user['firstname'] ?? '') ?></span>
                <i class="bi bi-chevron-down d-none d-lg-inline ms-1 small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header">
                    <strong><?= e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></strong>
                    <br><small class="text-muted"><?= e($user['email'] ?? $user['username'] ?? '') ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $baseUrl ?>/profile"><i class="bi bi-person me-2"></i>โปรไฟล์</a></li>
                <li><a class="dropdown-item" href="<?= $baseUrl ?>/change-password"><i class="bi bi-key me-2"></i>เปลี่ยนรหัสผ่าน</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="<?= $baseUrl ?>/logout" id="logoutForm">
                        <?= csrfField() ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Global Search Modal -->
<div class="modal fade" id="globalSearchModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-3">
                <form method="get" action="<?= e(url('students')) ?>"><div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" id="globalSearchInput" name="search" 
                           placeholder="ค้นหาผู้เรียน (ชื่อ, รหัส, เบอร์โทร, ตำบล...)" autofocus>
                </div>
                <button type="submit" class="btn btn-primary mt-3">ค้นหาผู้เรียน</button></form>
                <div id="searchResults" class="mt-3 d-none">
                    <!-- AJAX search results -->
                </div>
            </div>
        </div>
    </div>
</div>
