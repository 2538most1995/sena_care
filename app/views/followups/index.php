<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$followups = $followupsData['data'] ?? [];
$total = $followupsData['total'] ?? 0;
$page = $followupsData['page'] ?? 1;
$totalPages = $followupsData['total_pages'] ?? 1;
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clipboard2-check text-warning me-2"></i>การติดตามช่วยเหลือผู้เรียน</h4>
            <p class="text-muted small mb-0">รายการและสถานะการดำเนินการช่วยเหลือผู้เรียนที่มีความเสี่ยง (ทั้งหมด <?= number_format($total) ?> รายการ)</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFollowupModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการติดตาม
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= $baseUrl ?>/followups" class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">สถานะ</label>
                    <select name="status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">ระดับความสำคัญ</label>
                    <select name="priority" class="form-select">
                        <option value="">ทุกระดับ</option>
                        <option value="urgent" <?= ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>เร่งด่วน</option>
                        <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>สูง</option>
                        <option value="normal" <?= ($filters['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>ปกติ</option>
                        <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>ต่ำ</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> กรอง</button>
                    <?php if (!empty(array_filter($filters))): ?>
                        <a href="<?= $baseUrl ?>/followups" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Followup Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">หัวข้อการติดตาม</th>
                        <th>ผู้เรียน</th>
                        <th>ความสำคัญ</th>
                        <th>ครบกำหนด</th>
                        <th>ผู้รับผิดชอบ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($followups)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                ไม่พบรายการติดตามช่วยเหลือ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($followups as $f): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e($f['title']) ?></div>
                                    <small class="text-muted"><?= e(truncate($f['description'] ?? '', 70)) ?></small>
                                    <?php if (!empty($f['result'])): ?>
                                        <div class="mt-1 small text-success"><i class="bi bi-check2 me-1"></i><strong>ผล:</strong> <?= e($f['result']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= $baseUrl ?>/students/<?= $f['student_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($f['prefix'] . $f['firstname'] . ' ' . $f['lastname']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($f['student_code']) ?></div>
                                </td>
                                <td><?= priorityBadge($f['priority']) ?></td>
                                <td><small class="fw-medium"><?= e(thaiDate($f['due_date'], 'short')) ?></small></td>
                                <td><small><?= e(($f['assigned_firstname'] ?? '') . ' ' . ($f['assigned_lastname'] ?? '')) ?: '-' ?></small></td>
                                <td class="text-center"><?= statusBadge($f['status'], 'followup') ?></td>
                                <td class="text-center">
                                    <?php if ($f['status'] !== 'completed'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="openUpdateModal(<?= $f['id'] ?>, '<?= e($f['title']) ?>')">
                                            <i class="bi bi-check-lg me-1"></i> อัปเดตสถานะ
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-check-all text-success"></i> เสร็จสิ้น</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <?= paginationHtml($page, $totalPages, $baseUrl . '/followups', $filters) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="updateStatusForm">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">อัปเดตสถานะการติดตาม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">สถานะ</label>
                        <select name="status" class="form-select" required>
                            <option value="in_progress">กำลังดำเนินการ</option>
                            <option value="completed" selected>ดำเนินการเสร็จสิ้น</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">บันทึกผลการดำเนินการ / รายละเอียดการช่วยเหลือ</label>
                        <textarea name="result" class="form-control" rows="3" placeholder="ระบุสิ่งที่ได้ดำเนินการช่วยเหลือ เช่น มอบทุนเรียบร้อยแล้ว, ประสานงาน พม. แล้ว..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Followup Modal -->
<div class="modal fade" id="createFollowupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="<?= $baseUrl ?>/followups/create">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่มรายการติดตามช่วยเหลือ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">เลือกผู้เรียน <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required><option value="">เลือกจากรายชื่อจริง</option><?php foreach($studentOptions as $option): ?><option value="<?= $option['id'] ?>"><?= e($option['student_code'].' · '.$option['firstname'].' '.$option['lastname']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">หัวข้อการติดตาม <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="เช่น ขอรับทุนการศึกษา, ติดตามการเรียน..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="รายละเอียดเพิ่มเติม"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ความสำคัญ</label>
                            <select name="priority" class="form-select">
                                <option value="urgent">เร่งด่วน</option>
                                <option value="high">สูง</option>
                                <option value="normal" selected>ปกติ</option>
                                <option value="low">ต่ำ</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">กำหนดเสร็จ</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
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

<script>
function openUpdateModal(id, title) {
    document.getElementById('modalTitle').innerText = 'อัปเดต: ' + title;
    document.getElementById('updateStatusForm').action = '<?= $baseUrl ?>/followups/' + id + '/update-status';
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}
</script>
