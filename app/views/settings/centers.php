<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-building text-primary me-2"></i>จัดการ ศกร.ระดับตำบล</h4>
            <p class="text-muted small mb-0">ศูนย์การเรียนรู้ระดับตำบลในสังกัด สกร.ระดับอำเภอเสนา</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(url('centers/windows')) ?>" class="btn btn-outline-primary">
                <i class="bi bi-calendar-range me-1"></i> ช่วงเวลาเปิด–ปิด
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCenterModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่ม ศกร.ตำบล
            </button>
        </div>
    </div>

    <!-- Centers Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">รหัสศูนย์</th>
                        <th>ชื่อ ศกร.ระดับตำบล</th>
                        <th>ตำบล</th>
                        <th>เบอร์โทร</th>
                        <th class="text-center">จำนวนผู้เรียน</th>
                        <th class="text-center">จำนวนครู</th>
                        <th class="text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($centers as $c): ?>
                        <tr>
                            <td class="ps-4"><code><?= e($c['center_code'] ?: '-') ?></code></td>
                            <td class="fw-bold text-dark"><?= e($c['center_name']) ?></td>
                            <td><?= e($c['subdistrict'] ?: '-') ?></td>
                            <td><small><?= e(formatPhone($c['phone'])) ?></small></td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format((int)($c['student_count'] ?? 0)) ?> คน</span></td>
                            <td class="text-center">
                                <?php $teacherNames = trim((string)($c['teacher_names'] ?? '')); ?>
                                <span class="badge bg-light text-dark border<?= $teacherNames !== '' ? ' badge-tooltip' : '' ?>"
                                      <?php if ($teacherNames !== ''): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="ครูผู้รับผิดชอบ: <?= e($teacherNames) ?>"<?php endif; ?>>
                                    <?= number_format((int)($c['teacher_count'] ?? 0)) ?> คน
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $c['status'] === 'active' ? 'เปิดบริการ' : 'ปิดบริการ' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Center Modal -->
<div class="modal fade" id="createCenterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="<?= $baseUrl ?>/centers/create">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่ม ศกร.ระดับตำบลใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">รหัสศูนย์</label>
                            <input type="text" name="center_code" class="form-control" placeholder="เช่น LC-06">
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-semibold">ชื่อ ศกร.ตำบล *</label>
                            <input type="text" name="center_name" class="form-control" required placeholder="เช่น ศกร.ระดับตำบล...">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ตำบล</label>
                            <input type="text" name="subdistrict" class="form-control" placeholder="ตำบล">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">เบอร์โทร</label>
                            <input type="tel" name="phone" class="form-control" placeholder="035-xxx-xxx">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
