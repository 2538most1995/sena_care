<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$visits = $visitsData['data'] ?? [];
$total = $visitsData['total'] ?? 0;
$page = $visitsData['page'] ?? 1;
$totalPages = $visitsData['total_pages'] ?? 1;
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a class="small text-secondary d-inline-block mb-3" data-back-link href="<?= e(url('visits?term_id=' . (int)$filters['term_id'])) ?>"><i class="bi bi-arrow-left me-1"></i>ย้อนกลับ</a><h4 class="fw-bold mb-1"><i class="bi bi-house-heart text-success me-2"></i>ประวัติการเยี่ยมบ้าน<?= !empty($filters['student_id']) ? 'รายบุคคล' : '' ?></h4>
            <?php if ($historyStudent): ?>
            <p class="fw-semibold mb-1"><?= e($historyStudent['prefix'] . $historyStudent['firstname'] . ' ' . $historyStudent['lastname']) ?> <span class="text-muted small"><?= e($historyStudent['student_code']) ?></span></p>
            <?php endif; ?>
            <p class="text-muted small mb-0">รายการการเยี่ยมบ้านผู้เรียน (ทั้งหมด <?= number_format($total) ?> รายการ)</p>
        </div>

    </div>

    <?php if ($historyStudent && hasRole('super_admin', 'admin', 'teacher') && canAccessStudent((int)$historyStudent['student_id'], (int)$historyStudent['academic_term_id']) && canCreateVisitForCenter((int)$historyStudent['learning_center_id'])): ?>
    <a class="btn btn-primary mb-3" href="<?= e(url('visits/create/' . $historyStudent['student_id'] . '?term_id=' . (int)$historyStudent['academic_term_id'])) ?>"><i class="bi bi-plus-lg me-1"></i>บันทึกการเยี่ยมครั้งใหม่</a>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= $baseUrl ?>/visits/history" class="row g-3">
                <?php if (!empty($filters['student_id'])): ?><input type="hidden" name="student_id" value="<?= (int)$filters['student_id'] ?>"><?php endif; ?>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">ค้นหาผู้เรียน</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="ชื่อ, รหัสนักศึกษา..." value="<?= e($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">ระดับความเสี่ยง</label>
                    <select name="risk_level" class="form-select">
                        <option value="">ทุกระดับ</option>
                        <option value="low" <?= ($filters['risk_level'] ?? '') === 'low' ? 'selected' : '' ?>>เสี่ยงต่ำ</option>
                        <option value="medium" <?= ($filters['risk_level'] ?? '') === 'medium' ? 'selected' : '' ?>>เสี่ยงปานกลาง</option>
                        <option value="high" <?= ($filters['risk_level'] ?? '') === 'high' ? 'selected' : '' ?>>เสี่ยงสูง</option>
                        <option value="critical" <?= ($filters['risk_level'] ?? '') === 'critical' ? 'selected' : '' ?>>เสี่ยงสูงมาก</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">ภาคเรียน</label>
                    <select name="term_id" class="form-select">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['term_id'] ?? currentTermId()) == $t['id'] ? 'selected' : '' ?>><?= e($t['term_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> กรอง</button>
                    <?php if (!empty(array_filter($filters))): ?>
                        <a href="<?= e(url('visits/history' . (!empty($filters['student_id']) ? '?student_id=' . (int)$filters['student_id'] . '&term_id=' . (int)$filters['term_id'] : ''))) ?>" class="btn btn-outline-secondary" title="ล้างตัวกรอง" aria-label="ล้างตัวกรอง"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Visits Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 records-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">วันที่เยี่ยม</th>
                        <th>ผู้เรียน</th>
                        <th>ศกร.ตำบล</th>
                        <th>ครูผู้เยี่ยม (ครูประจำกลุ่ม)</th>
                        <th>ประเภทการเยี่ยม</th>
                        <th class="text-center">ความเสี่ยง</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visits)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                ไม่พบบันทึกการเยี่ยมบ้าน
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visits as $v): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e(thaiDate($v['visit_date'], 'short')) ?></div>
                                    <small class="text-muted"><?= !empty($v['checkin_time']) ? date('H:i น.', strtotime($v['checkin_time'])) : '-' ?></small>
                                </td>
                                <td>
                                    <a href="<?= $baseUrl ?>/students/<?= $v['student_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($v['prefix'] . $v['firstname'] . ' ' . $v['lastname']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($v['student_code']) ?></div>
                                </td>
                                <td><small><?= e($v['center_name'] ?: '-') ?></small></td>
                                <td><small><?= e($v['group_teacher_name'] ?: 'ยังไม่ระบุครูประจำกลุ่ม') ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= e($v['visit_type'] === 'home_visit' ? 'เยี่ยมที่บ้าน' : $v['visit_type']) ?></span></td>
                                <td class="text-center"><?= riskBadge($v['risk_level']) ?></td>
                                <td class="text-center"><?= statusBadge($v['status'], 'visit') ?></td>
                                <td class="text-center">
                                    <div class="action-buttons action-buttons-nowrap gap-2">
                                        <a href="<?= $baseUrl ?>/visits/<?= $v['id'] ?>" class="btn btn-outline-secondary btn-icon" title="ดูรายละเอียดการเยี่ยม" aria-label="ดูรายละเอียดการเยี่ยม" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if (hasRole('super_admin', 'admin', 'teacher')): ?>
                                        <a href="<?= $baseUrl ?>/visits/<?= $v['id'] ?>/edit" class="btn btn-outline-secondary btn-icon" title="แก้ไขบันทึก" aria-label="แก้ไขบันทึก" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <?= paginationHtml($page, $totalPages, $baseUrl . '/visits/history', $filters) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
