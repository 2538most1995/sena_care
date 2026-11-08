<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$apiOnly = !empty((require APP_ROOT . '/app/config/config.php')['students_api_only']);
$students = $studentsData['data'] ?? [];
$total = $studentsData['total'] ?? 0;
$page = $studentsData['page'] ?? 1;
$totalPages = $studentsData['total_pages'] ?? 1;
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>รายชื่อผู้เรียน</h4>
            <p class="text-muted small mb-0">ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. (ทั้งหมด <?= number_format($total) ?> คน)</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!$apiOnly && hasRole('super_admin', 'admin', 'teacher')): ?>
            <a href="<?= $baseUrl ?>/students/import" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> นำเข้า Excel/CSV
            </a>
            <a href="<?= $baseUrl ?>/students/create" class="btn btn-primary">
                <i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้เรียนใหม่
            </a>
            <?php endif; ?>
            <?php if ($apiOnly): ?>
                <span class="badge bg-success align-self-center">รายชื่อจาก SDL_school API</span>
                <?php if (hasRole('super_admin')): ?>
                    <a href="<?= $baseUrl ?>/integrations/sdl" class="btn btn-outline-success"><i class="bi bi-arrow-repeat me-1"></i> ซิงก์ข้อมูล</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= $baseUrl ?>/students" class="filter-grid">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-secondary">ค้นหา</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="ชื่อ, รหัส, เลขบัตร..." value="<?= e($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">ระดับการศึกษา</label>
                    <select name="level" class="form-select">
                        <option value="">ทุกระดับ</option>
                        <option value="primary" <?= ($filters['level'] ?? '') === 'primary' ? 'selected' : '' ?>>ประถมศึกษา</option>
                        <option value="lower_secondary" <?= ($filters['level'] ?? '') === 'lower_secondary' ? 'selected' : '' ?>>ม.ต้น</option>
                        <option value="upper_secondary" <?= ($filters['level'] ?? '') === 'upper_secondary' ? 'selected' : '' ?>>ม.ปลาย</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">ศกร.ตำบล</label>
                    <select name="center_id" class="form-select">
                        <option value="">ทุกแห่ง</option>
                        <?php foreach ($centers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($filters['center_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['center_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">สถานะการเยี่ยม</label>
                    <select name="visit_status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="visited" <?= ($filters['visit_status'] ?? '') === 'visited' ? 'selected' : '' ?>>เยี่ยมแล้ว</option>
                        <option value="not_visited" <?= ($filters['visit_status'] ?? '') === 'not_visited' ? 'selected' : '' ?>>ยังไม่เยี่ยม</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">ระดับความเสี่ยง</label>
                    <select name="risk_level" class="form-select">
                        <option value="">ทุกระดับ</option>
                        <option value="low" <?= ($filters['risk_level'] ?? '') === 'low' ? 'selected' : '' ?>>เสี่ยงต่ำ</option>
                        <option value="medium" <?= ($filters['risk_level'] ?? '') === 'medium' ? 'selected' : '' ?>>เสี่ยงปานกลาง</option>
                        <option value="high" <?= ($filters['risk_level'] ?? '') === 'high' ? 'selected' : '' ?>>เสี่ยงสูง</option>
                        <option value="critical" <?= ($filters['risk_level'] ?? '') === 'critical' ? 'selected' : '' ?>>เสี่ยงสูงมาก</option>
                    </select>
                </div>

                <div class="col-12 col-lg-1 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> กรอง</button>
                    <?php if (!empty(array_filter($filters))): ?>
                        <a href="<?= $baseUrl ?>/students" class="btn btn-outline-secondary" title="ล้างตัวกรอง"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 records-table students-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ผู้เรียน</th>
                        <th>รหัสนักศึกษา</th>
                        <th>กลุ่มเรียน / ครูผู้รับผิดชอบ</th>
                        <th class="text-center">สถานะการเยี่ยม</th>
                        <th class="text-center">ความเสี่ยง</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                ไม่พบข้อมูลผู้เรียนตามเงื่อนไขที่เลือก
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar avatar-md bg-primary-subtle text-primary fw-bold">
                                            <?= mb_substr($s['firstname'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <a href="<?= $baseUrl ?>/students/<?= $s['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= e($s['prefix'] . $s['firstname'] . ' ' . $s['lastname']) ?>
                                            </a>
                                            <div class="small text-muted"><?= e(educationLevelName($s['education_level'])) ?></div>
                                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i><span data-student-phone-url="<?= e(url('api/students/' . (int)$s['id'] . '/phone?term_id=' . (int)$filters['term_id'])) ?>" aria-live="polite"><?= e(formatPhone($s['phone'])) ?></span></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($s['student_code']) ?></span></td>
                                <td><div><?= e($s['center_name'] ?: '-') ?></div><small class="text-muted"><?= e(($s['teacher_firstname']??'').' '.($s['teacher_lastname']??'')) ?: '-' ?></small></td>
                                <td class="text-center">
                                    <?php if ($s['visit_count'] > 0): ?>
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle me-1"></i> เยี่ยมแล้ว (<?= $s['visit_count'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill">ยังไม่เยี่ยม</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= riskBadge($s['risk_level']) ?>
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons student-actions gap-2">
                                        <?php if (hasRole('super_admin', 'admin', 'teacher')): ?>
                                        <a href="<?= $baseUrl ?>/visits/create/<?= $s['id'] ?>" class="btn btn-outline-primary btn-icon" title="บันทึกเยี่ยมบ้าน" aria-label="บันทึกเยี่ยมบ้าน" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="bi bi-house-heart" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= $baseUrl ?>/students/<?= $s['id'] ?>" class="btn btn-outline-secondary btn-icon student-action-view" title="ดูข้อมูลผู้เรียน" aria-label="ดูข้อมูลผู้เรียน" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if (hasRole('super_admin', 'admin', 'teacher')): ?>
                                        <a href="<?= $baseUrl ?>/students/<?= $s['id'] ?>/edit" class="btn btn-outline-primary btn-icon student-action-edit" title="แก้ไขข้อมูลผู้เรียน" aria-label="แก้ไขข้อมูลผู้เรียน" data-bs-toggle="tooltip" data-bs-placement="top">
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
            <?= paginationHtml($page, $totalPages, $baseUrl . '/students', $filters) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
