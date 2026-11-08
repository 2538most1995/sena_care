<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-mortarboard-fill text-success me-2"></i>จัดการปีการศึกษาและภาคเรียน</h4>
            <p class="text-muted small mb-0">กำหนดปีการศึกษา ภาคเรียนที่เปิดสอน และกำหนดภาคเรียนปัจจุบัน</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTermModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มภาคเรียนใหม่
            </button>
        </div>
    </div>

    <!-- Terms Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ชื่อภาคเรียน</th>
                        <th>ปีการศึกษา</th>
                        <th>ภาคเรียนที่</th>
                        <th>ช่วงเวลา</th>
                        <th class="text-center">ภาคเรียนปัจจุบัน</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-end pe-4">สลับใช้งาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $t): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?= e($t['term_name']) ?></td>
                            <td><?= e($t['academic_year']) ?></td>
                            <td><?= e($t['term_no']) ?></td>
                            <td>
                                <small class="text-muted">
                                    <?= !empty($t['start_date']) ? e(thaiDate($t['start_date'], 'short')) : '-' ?> ถึง <?= !empty($t['end_date']) ? e(thaiDate($t['end_date'], 'short')) : '-' ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php if ($t['is_current']): ?>
                                    <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle me-1"></i> ปัจจุบัน</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $t['status'] === 'active' ? 'bg-primary' : 'bg-secondary' ?>">
                                    <?= $t['status'] === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" action="<?= $baseUrl ?>/terms/switch" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="term_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary" <?= ($t['id'] == currentTermId()) ? 'disabled' : '' ?>>
                                        <?= ($t['id'] == currentTermId()) ? 'กำลังทำงาน' : 'สลับมาใช้' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Term Modal -->
<div class="modal fade" id="createTermModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="<?= $baseUrl ?>/terms/create">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่มภาคเรียนใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ชื่อภาคเรียน (เช่น 1/2570) *</label>
                        <input type="text" name="term_name" class="form-control" required placeholder="เช่น 1/2570">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ปีการศึกษา *</label>
                            <input type="number" name="academic_year" class="form-control" value="2570" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ภาคเรียนที่ *</label>
                            <select name="term_no" class="form-select" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">วันที่เริ่มต้น</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">วันที่สิ้นสุด</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_current" id="checkCurrent" value="1">
                        <label class="form-check-label small fw-semibold" for="checkCurrent">ตั้งเป็นภาคเรียนปัจจุบันของระบบ</label>
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
