<?php
if (!defined('APP_ROOT')) die();
$now = time();
$stateOf = function (array $c, string $type) use ($now): array {
    $enabled = (bool)($c[$type . '_enabled'] ?? 1);
    $openAt  = $c[$type . '_open_at'] ?? null;
    $closeAt = $c[$type . '_close_at'] ?? null;
    $open = $enabled;
    if ($open && $openAt && strtotime($openAt) > $now) $open = false;
    if ($open && $closeAt && strtotime($closeAt) < $now) $open = false;
    return ['enabled' => $enabled, 'open' => $open, 'open_at' => $openAt, 'close_at' => $closeAt];
};
$dtLocal = function (?string $v): string {
    return $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
};
$badge = function (bool $enabled, bool $open): string {
    if ($open) return '<span class="badge bg-success">เปิดอยู่</span>';
    if ($enabled) return '<span class="badge bg-warning text-dark">นอกช่วงเวลา</span>';
    return '<span class="badge bg-secondary">ปิด</span>';
};
// Visible Thai (พ.ศ.) text input + hidden input carrying YYYY-MM-DDTHH:mm.
$field = function (string $name, ?string $iso) use ($dtLocal): string {
    $isoVal  = $iso ? $dtLocal($iso) : '';
    $display = $isoVal ? thaiDateTime($isoVal) : '';
    return '<input type="text" class="form-control form-control-sm thai-datetime" readonly data-target="' . e($name) . '" value="' . e($display) . '" placeholder="วว/ดด/ปปปป HH:mm">'
         . '<input type="hidden" id="' . e($name) . '" name="' . e($name) . '" value="' . e($isoVal) . '">';
};
?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar-range text-primary me-2"></i>ช่วงเวลาเปิด–ปิด การเยี่ยมบ้าน/การสำรวจ</h4>
            <p class="text-muted mb-0 small">กำหนดครั้งเดียวใช้กับทุกศกร. หรือปรับรายศกร.ก็ได้ — ครูตำบลบันทึก/สำรวจได้เฉพาะเมื่อเปิด (ยังดูข้อมูลย้อนหลังได้)</p>
        </div>
        <a href="<?= e(url('centers')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับหน้าศกร.</a>
    </div>

    <!-- Two prominent actions (open modals) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <button type="button" class="btn-action btn-action-schedule" data-bs-toggle="modal" data-bs-target="#scheduleAllModal">
                <i class="bi bi-calendar2-week"></i>
                <span>
                    <span class="btn-action-title">กำหนดเวลาทั้งหมด</span>
                    <span class="btn-action-sub">ตั้งช่วงเวลาเปิด–ปิดให้ทุกศกร.พร้อมกัน</span>
                </span>
            </button>
        </div>
        <div class="col-12 col-md-6">
            <button type="button" class="btn-action btn-action-custom" data-bs-toggle="modal" data-bs-target="#bulkToggleModal">
                <i class="bi bi-toggles"></i>
                <span>
                    <span class="btn-action-title">กำหนดเองทั้งหมด</span>
                    <span class="btn-action-sub">เปิด/ปิดการเยี่ยมบ้าน/การสำรวจด้วยตนเองทั้งอำเภอ</span>
                </span>
            </button>
        </div>
    </div>

    <!-- Per-center schedule -->
    <form method="post" action="<?= e(url('centers/windows')) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_selected">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label for="selectAll" class="form-check-label fw-semibold">เลือกทั้งหมด</label>
                    <span class="text-muted small ms-2">ปรับรายศกร. (ถ้าต้องการ)</span>
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>บันทึกที่เลือก</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:44px"></th>
                            <th>ศกร.ระดับตำบล</th>
                            <th style="min-width:340px">การเยี่ยมบ้าน</th>
                            <th style="min-width:340px">การสำรวจรายบุคคล</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($centers as $c): $v = $stateOf($c, 'visit'); $s = $stateOf($c, 'survey'); $cid = (int)$c['id']; ?>
                        <tr>
                            <td><input class="form-check-input center-check" type="checkbox" name="center_ids[]" value="<?= $cid ?>"></td>
                            <td>
                                <strong><?= e($c['center_name']) ?></strong>
                                <br><small class="text-muted"><?= e($c['center_code'] ?: '-') ?></small>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="visit_enabled_<?= $cid ?>" <?= $v['enabled'] ? 'checked' : '' ?>>
                                    </div>
                                    <?= $badge($v['enabled'], $v['open']) ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <div style="max-width:200px">
                                        <label class="form-label small text-muted mb-0">เปิด</label>
                                        <?= $field('visit_open_at_' . $cid, $v['open_at']) ?>
                                    </div>
                                    <div style="max-width:200px">
                                        <label class="form-label small text-muted mb-0">ปิด</label>
                                        <?= $field('visit_close_at_' . $cid, $v['close_at']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="survey_enabled_<?= $cid ?>" <?= $s['enabled'] ? 'checked' : '' ?>>
                                    </div>
                                    <?= $badge($s['enabled'], $s['open']) ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <div style="max-width:200px">
                                        <label class="form-label small text-muted mb-0">เปิด</label>
                                        <?= $field('survey_open_at_' . $cid, $s['open_at']) ?>
                                    </div>
                                    <div style="max-width:200px">
                                        <label class="form-label small text-muted mb-0">ปิด</label>
                                        <?= $field('survey_close_at_' . $cid, $s['close_at']) ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$centers): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบศกร.ในสังกัด</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white py-3 text-end">
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>บันทึกที่เลือก</button>
            </div>
        </div>
    </form>
</div>

<!-- Modal: set the same schedule for all centers -->
<div class="modal fade" id="scheduleAllModal" tabindex="-1" data-bs-focus="false" aria-labelledby="scheduleAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <form method="post" action="<?= e(url('centers/windows')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="apply_all_schedule">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="scheduleAllModalLabel"><i class="bi bi-calendar2-week me-2 text-primary"></i>กำหนดเวลาทั้งหมด</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">ใช้ช่วงเวลาเดียวกับทุกศกร. · ช่องที่เว้นว่างจะไม่ถูกแก้ไข · ประเภทที่มีการกำหนดเวลาจะถูกเปิดใช้งานอัตโนมัติ</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-dark">การเยี่ยมบ้าน</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-0">เปิด</label>
                                    <?= $field('global_visit_open_at', null) ?>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-0">ปิด</label>
                                    <?= $field('global_visit_close_at', null) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-dark">การสำรวจรายบุคคล</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-0">เปิด</label>
                                    <?= $field('global_survey_open_at', null) ?>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-0">ปิด</label>
                                    <?= $field('global_survey_close_at', null) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>ใช้กับทุกศกร.</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: bulk manual open/close -->
<div class="modal fade" id="bulkToggleModal" tabindex="-1" aria-labelledby="bulkToggleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post" action="<?= e(url('centers/windows')) ?>">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="bulkToggleModalLabel"><i class="bi bi-toggles me-2 text-primary"></i>กำหนดเองทั้งหมด</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">เปิด/ปิดด้วยตนเองทั้งอำเภอ (ไม่ผูกกับช่วงเวลา) — ครูตำบลจะบันทึก/สำรวจได้เมื่อเปิดเท่านั้น</p>
                    <div class="row g-2">
                        <div class="col-6"><button class="btn btn-success w-100" name="action" value="open_all"><i class="bi bi-check2-circle me-1"></i>เปิดทั้งหมด</button></div>
                        <div class="col-6"><button class="btn btn-outline-danger w-100" name="action" value="close_all"><i class="bi bi-slash-circle me-1"></i>ปิดทั้งหมด</button></div>
                        <div class="col-6"><button class="btn btn-outline-success w-100" name="action" value="open_visits">เปิดการเยี่ยมบ้าน</button></div>
                        <div class="col-6"><button class="btn btn-outline-danger w-100" name="action" value="close_visits">ปิดการเยี่ยมบ้าน</button></div>
                        <div class="col-6"><button class="btn btn-outline-success w-100" name="action" value="open_surveys">เปิดการสำรวจ</button></div>
                        <div class="col-6"><button class="btn btn-outline-danger w-100" name="action" value="close_surveys">ปิดการสำรวจ</button></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.center-check').forEach(function (cb) { cb.checked = this.checked; });
});
</script>
