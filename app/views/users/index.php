<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$users = $usersData['data'] ?? [];
$total = $usersData['total'] ?? 0;
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>จัดการผู้ใช้งานระบบ</h4>
            <p class="text-muted small mb-0">รายชื่อผู้ดูแลระบบ ครูผู้สอน และผู้บริหาร (ทั้งหมด <?= number_format($total) ?> คน)</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $baseUrl ?>/users/<?= currentUserId() ?>/edit" class="btn btn-outline-primary">แก้ไข user / pass ของฉัน</a>
            <a class="btn btn-primary" href="<?= e(url('integrations/sdl')) ?>">ข้อมูลครูจาก SDL_school API</a>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ชื่อ-นามสกุล</th>
                        <th>ชื่อผู้ใช้ (Username)</th>
                        <th>อีเมล</th>
                        <th>เบอร์โทร</th>
                        <th>บทบาท</th>
                        <th>ศกร.ตำบล</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm bg-primary-subtle text-primary fw-bold">
                                        <?= mb_substr($u['firstname'], 0, 1) ?>
                                    </div>
                                    <span class="fw-bold text-dark"><?= e($u['prefix'] . $u['firstname'] . ' ' . $u['lastname']) ?></span>
                                </div>
                            </td>
                            <td><code><?= e($u['username']) ?></code></td>
                            <td><small><?= e($u['email'] ?: '-') ?></small></td>
                            <td><small><?= e(formatPhone($u['phone'])) ?></small></td>
                            <td>
                                <?php
                                    $roleBadge = [
                                        'super_admin' => 'bg-danger',
                                        'admin'       => 'bg-primary',
                                        'teacher'     => 'bg-success',
                                        'executive'   => 'bg-info text-dark',
                                    ];
                                ?>
                                <span class="badge rounded-pill <?= $roleBadge[$u['role']] ?? 'bg-secondary' ?>"><?= e($u['role']) ?></span>
                            </td>
                            <td><small><?= e($u['center_name'] ?: '-') ?></small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $u['status'] === 'active' ? 'ใช้งานปกติ' : 'ปิดใช้งาน' ?>
                                </span>
                            </td>
                            <td class="text-center"><?php if ($u['role'] !== 'super_admin' || isSuperAdmin()): ?><a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= $baseUrl ?>/users/<?= $u['id'] ?>/edit">แก้ไข user / pass</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?= paginationHtml($page, $usersData['total_pages'], $baseUrl.'/users', $filters) ?>
</div>

