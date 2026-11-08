<?php
/**
 * Sidebar Navigation - Desktop
 */
if (!defined('APP_ROOT')) die();
$currentPage = $currentPage ?? '';
$user = currentUser();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');

$menuItems = [
    ['page' => 'dashboard', 'icon' => 'bi-grid-1x2-fill', 'label' => 'แดชบอร์ด', 'url' => '/dashboard', 'roles' => ['super_admin','admin','teacher','executive']],
    ['page' => 'students', 'icon' => 'bi-people-fill', 'label' => 'ผู้เรียน', 'url' => '/students', 'roles' => ['super_admin','admin','teacher','executive']],
    ['page' => 'surveys', 'icon' => 'bi-clipboard-data', 'label' => 'สำรวจผู้เรียนรายบุคคล', 'url' => '/surveys', 'roles' => ['super_admin','admin','teacher','executive']],
    ['page' => 'map', 'icon' => 'bi-geo-alt-fill', 'label' => 'แผนที่ผู้เรียน', 'url' => '/map', 'roles' => ['super_admin','admin','teacher','executive']],
    ['type' => 'divider'],
    ['page' => 'visits', 'icon' => 'bi-house-heart-fill', 'label' => 'บันทึกเยี่ยมบ้าน', 'url' => '/visits', 'roles' => ['super_admin','admin','teacher','executive']],
    ['page' => 'graduates', 'icon' => 'bi-award-fill', 'label' => 'ติดตามผู้สำเร็จการศึกษา', 'url' => '/graduates', 'roles' => ['super_admin','admin','teacher','executive']],
    ['type' => 'divider'],
    ['page' => 'teacher-reports', 'icon' => 'bi-file-earmark-pdf', 'label' => 'รายงานเยี่ยมบ้านรายครู', 'url' => '/reports/teachers', 'roles' => ['super_admin','admin','teacher','executive']],
    ['page' => 'reports', 'icon' => 'bi-bar-chart-line-fill', 'label' => 'รายงาน', 'url' => '/reports', 'roles' => ['super_admin','admin','executive']],
    ['type' => 'divider', 'roles' => ['super_admin','admin']],
    ['type' => 'heading', 'label' => 'จัดการระบบ', 'roles' => ['super_admin','admin']],
    ['page' => 'terms', 'icon' => 'bi-mortarboard-fill', 'label' => 'ภาคเรียน', 'url' => '/terms', 'roles' => ['super_admin','admin']],
    ['page' => 'centers', 'icon' => 'bi-building', 'label' => 'ศกร.', 'url' => '/centers', 'roles' => ['super_admin','admin']],
    ['page' => 'users', 'icon' => 'bi-person-gear', 'label' => 'ผู้ใช้งาน', 'url' => '/users', 'roles' => ['super_admin','admin']],
    ['page' => 'sdl', 'icon' => 'bi-arrow-repeat', 'label' => 'เชื่อมต่อ SDL_school', 'url' => '/integrations/sdl', 'roles' => ['super_admin']],
    ['page' => 'settings', 'icon' => 'bi-gear-fill', 'label' => 'ตั้งค่า', 'url' => '/settings', 'roles' => ['super_admin']],
];
?>
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">SENA Care</span>
            <span class="brand-sub">ระบบเยี่ยมบ้านผู้เรียน</span>
        </div>
        <button class="sidebar-close d-lg-none" id="sidebarClose" aria-label="ปิดเมนู">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Current Term Badge -->
    <div class="sidebar-term">
        <i class="bi bi-calendar3 me-2"></i>
        <span>ภาคเรียน <?= e(currentTermName()) ?></span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menuItems as $item): ?>
                <?php
                // Check type
                if (isset($item['type'])) {
                    if ($item['type'] === 'divider') {
                        if (isset($item['roles']) && !hasRole(...$item['roles'])) continue;
                        echo '<li class="nav-divider"></li>';
                        continue;
                    }
                    if ($item['type'] === 'heading') {
                        if (isset($item['roles']) && !hasRole(...$item['roles'])) continue;
                        echo '<li class="nav-heading">' . e($item['label']) . '</li>';
                        continue;
                    }
                }

                // Check role
                if (isset($item['roles']) && !hasRole(...$item['roles'])) continue;

                $active = ($currentPage === $item['page']) ? ' active' : '';
                ?>
                <li class="nav-item">
                    <a href="<?= $baseUrl . $item['url'] ?>" class="nav-link<?= $active ?>">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- User Info -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?= e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></span>
                <span class="user-role"><?= e(match(currentRole()) {
                    'super_admin' => 'ผู้ดูแลระบบสูงสุด',
                    'admin' => 'ผู้ดูแลระบบ',
                    'teacher' => 'ครู',
                    'executive' => 'ผู้บริหาร',
                    default => currentRole()
                }) ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
