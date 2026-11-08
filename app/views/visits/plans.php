<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar-event text-primary me-2"></i>แผนการเยี่ยมบ้าน</h4>
            <p class="text-muted small mb-0">ตารางนัดหมายและแผนการลงพื้นที่เยี่ยมบ้านผู้เรียน</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" data-bs-toggle="modal" data-bs-target="#createVisitPlan" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> วางแผนการเยี่ยม
            </button>
        </div>
    </div>

    <!-- Plans Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">วัน-เวลานัดหมาย</th>
                        <th>ผู้เรียน</th>
                        <th>ที่อยู่ / ตำบล</th>
                        <th>วัตถุประสงค์</th>
                        <th>ครูผู้รับผิดชอบ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-end pe-4">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                ไม่พบรายการแผนการเยี่ยมบ้านในขณะนี้
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plans as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i> <?= e(thaiDate($p['visit_date'], 'short')) ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> <?= e(date('H:i น.', strtotime($p['visit_time'] ?? '09:00:00'))) ?></small>
                                </td>
                                <td>
                                    <a href="<?= $baseUrl ?>/students/<?= $p['student_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($p['prefix'] . $p['firstname'] . ' ' . $p['lastname']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($p['student_code']) ?></div>
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        บ้านเลขที่ <?= e($p['house_no'] ?: '-') ?> ต.<?= e($p['subdistrict'] ?: '-') ?>
                                    </small>
                                    <?php if (($navigationUrl = googleMapsUrl($p['latitude'] ?? null, $p['longitude'] ?? null)) !== null): ?>
                                        <a href="<?= e($navigationUrl) ?>" target="_blank" rel="noopener noreferrer" class="badge bg-light text-primary border text-decoration-none ms-1">
                                            <i class="bi bi-navigation-fill"></i> แผนที่
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= e($p['purpose']) ?></small></td>
                                <td><small><?= e($p['group_teacher_name'] ?: 'ยังไม่ระบุครูประจำกลุ่ม') ?></small></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?= $p['status'] === 'confirmed' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                        <?= $p['status'] === 'confirmed' ? 'ยืนยันแล้ว' : 'วางแผนไว้' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= $baseUrl ?>/visits/create/<?= $p['student_id'] ?>?plan_id=<?= $p['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-house-heart me-1"></i> เริ่มบันทึกเยี่ยม
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createVisitPlan" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="<?= e(url('visit-plans/create')) ?>">
<div class="modal-header"><h5 class="modal-title">วางแผนการเยี่ยมบ้าน</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button></div>
<div class="modal-body"><?= csrfField() ?>
<label class="form-label" for="planStudent">เลือกผู้เรียน</label><select class="form-select mb-3" id="planStudent" name="student_id" required><option value="">เลือกจากรายชื่อจริง</option><?php foreach($studentOptions as $option): ?><option value="<?= $option['id'] ?>"><?= e($option['student_code'].' · '.$option['firstname'].' '.$option['lastname']) ?></option><?php endforeach; ?></select>
<div class="row g-3"><div class="col-7"><label class="form-label" for="planDate">วันที่นัดหมาย</label><input id="planDate" type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div><div class="col-5"><label class="form-label" for="planTime">เวลานัดหมาย</label><input id="planTime" type="time" name="visit_time" class="form-control" value="09:00"></div></div>
<label class="form-label mt-3" for="planPurpose">วัตถุประสงค์</label><textarea id="planPurpose" name="purpose" class="form-control" rows="2">เยี่ยมบ้านเพื่อติดตามการเรียนและความเป็นอยู่</textarea></div><div class="modal-footer"><button type="submit" class="btn btn-primary">บันทึกแผนการเยี่ยม</button></div>
</form></div></div></div>
