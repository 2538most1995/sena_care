<?php
/**
 * Layout Footer - Bottom Navigation (Mobile) + Scripts + Close tags
 */
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$currentPage = $currentPage ?? '';
?>
<?php if (isLoggedIn() && empty($hideLayout)): ?>
        </div><!-- /.page-content -->
    </div><!-- /.main-content -->

    <!-- Bottom Navigation - Mobile -->
    <nav class="bottom-nav d-lg-none" id="bottomNav">
        <a href="<?= $baseUrl ?>/dashboard" class="bottom-nav-item<?= $currentPage === 'dashboard' ? ' active' : '' ?>">
            <i class="bi bi-grid-1x2<?= $currentPage === 'dashboard' ? '-fill' : '' ?>"></i>
            <span>หน้าแรก</span>
        </a>
        <a href="<?= $baseUrl ?>/students" class="bottom-nav-item<?= $currentPage === 'students' ? ' active' : '' ?>">
            <i class="bi bi-people<?= $currentPage === 'students' ? '-fill' : '' ?>"></i>
            <span>ผู้เรียน</span>
        </a>
        <a href="<?= $baseUrl ?>/surveys" class="bottom-nav-item<?= $currentPage === 'surveys' ? ' active' : '' ?>">
            <i class="bi bi-clipboard2-data<?= $currentPage === 'surveys' ? '-fill' : '' ?>"></i>
            <span>สำรวจผู้เรียน</span>
        </a>
        <a href="<?= $baseUrl ?>/visits" class="bottom-nav-item<?= $currentPage === 'visits' ? ' active' : '' ?>">
            <i class="bi bi-house-heart<?= $currentPage === 'visits' ? '-fill' : '' ?>"></i>
            <span>เยี่ยมบ้าน</span>
        </a>
        <a href="#" class="bottom-nav-item" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreMenu">
            <i class="bi bi-three-dots"></i>
            <span>เพิ่มเติม</span>
        </a>
    </nav>

    <!-- Mobile More Menu -->
    <div class="offcanvas offcanvas-bottom rounded-top-4" tabindex="-1" id="mobileMoreMenu" style="height:auto;max-height:70vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">เมนูเพิ่มเติม</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body pb-4">
            <div class="row g-3">
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/map" class="more-menu-item">
                        <div class="more-icon bg-primary-subtle"><i class="bi bi-geo-alt-fill text-primary"></i></div>
                        <small>แผนที่ผู้เรียน</small>
                    </a>
                </div>
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/graduates" class="more-menu-item">
                        <div class="more-icon bg-warning-subtle"><i class="bi bi-award-fill text-warning"></i></div>
                        <small>ติดตามผู้สำเร็จ</small>
                    </a>
                </div>
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/reports/teachers" class="more-menu-item">
                        <div class="more-icon bg-info-subtle"><i class="bi bi-bar-chart-line text-info"></i></div>
                        <small>รายงาน PDF</small>
                    </a>
                </div>
                <?php if (isAdmin()): ?>
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/terms" class="more-menu-item">
                        <div class="more-icon bg-success-subtle"><i class="bi bi-mortarboard text-success"></i></div>
                        <small>ภาคเรียน</small>
                    </a>
                </div>
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/centers" class="more-menu-item">
                        <div class="more-icon bg-secondary-subtle"><i class="bi bi-building text-secondary"></i></div>
                        <small>ศกร.</small>
                    </a>
                </div>
                <div class="col-4 text-center">
                    <a href="<?= $baseUrl ?>/users" class="more-menu-item">
                        <div class="more-icon bg-danger-subtle"><i class="bi bi-person-gear text-danger"></i></div>
                        <small>ผู้ใช้งาน</small>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- /.app-wrapper -->
<?php endif; ?>

<!-- Bootstrap JS (Local) -->
<script src="<?= $baseUrl ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS (Local) -->
<script src="<?= $baseUrl ?>/assets/vendor/leaflet/leaflet.js"></script>
<!-- Chart.js (Local) -->
<script src="<?= $baseUrl ?>/assets/vendor/chartjs/chart.umd.min.js"></script>

<!-- App JS -->
<script src="<?= $baseUrl ?>/assets/js/app.js?v=<?= @filemtime(APP_ROOT . '/public/assets/js/app.js') ?: '1.0.1' ?>"></script>

<?php if (isset($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;" id="toastContainer"></div>

</body>
</html>
