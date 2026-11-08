<?php
if (!defined('APP_ROOT')) die();
$answers = $survey['answers'] ?? [];
$activeIds = [];
$sections = [];
foreach ($questions as $q) {
    $activeIds[(int)$q['id']] = true;
    $val = array_key_exists($q['id'], $answers) ? $answers[$q['id']] : (array_key_exists((int)$q['id'], $answers) ? $answers[(int)$q['id']] : null);
    if ($val === null || $val === '') continue;
    $decoded = json_decode((string)$val, true);
    if (is_array($decoded)) $val = implode(', ', $decoded);
    $sections[$q['section_name']][] = ['question' => $q['question_text'], 'answer' => (string)$val];
}
$retired = [];
foreach ($answers as $qid => $val) {
    if (isset($activeIds[(int)$qid])) continue;
    if ($val === null || $val === '') continue;
    $decoded = json_decode((string)$val, true);
    $retired[] = ['question' => 'คำถาม #' . (int)$qid . ' (เลิกใช้แล้ว)', 'answer' => is_array($decoded) ? implode(', ', $decoded) : (string)$val];
}
$isCancelled = !empty($survey['cancelled_at']);
$canCreate = canCreateSurveyForCenter((int)($survey['learning_center_id'] ?? 0));
$canCancel = canCancelRecords();
$canEdit = $canCreate || $canCancel;
?>
<div class="container-fluid px-3 px-md-4 py-3 visit-record-page"><div class="mx-auto" style="max-width:900px">

    <a href="<?= e(url('surveys/' . $survey['student_id'] . '?term_id=' . $survey['academic_term_id'])) ?>" class="small">← กลับหน้าแบบสำรวจผู้เรียน</a>

    <div class="card border-0 shadow-sm mt-3 mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="fw-bold mb-1">
                        <?= e(($survey['s_prefix'] ?? '') . $survey['s_firstname'] . ' ' . $survey['s_lastname']) ?>
                        <span class="badge bg-light text-dark border ms-1">ครั้งที่ <?= (int)$survey['revision'] ?></span>
                        <?php if ($isCancelled): ?><span class="badge bg-secondary">ยกเลิกแล้ว</span><?php else: ?><span class="badge bg-success">ใช้งาน</span><?php endif; ?>
                    </h4>
                    <p class="text-muted small mb-1">รหัสนักศึกษา <?= e($survey['student_code']) ?> · กลุ่ม <?= e($survey['group_name'] ?? '') ?></p>
                    <p class="small mb-0 text-muted">
                        <i class="bi bi-person-check me-1"></i>ผู้บันทึก <?= e(($survey['firstname'] ?? '') . ' ' . ($survey['lastname'] ?? '')) ?>
                        · <i class="bi bi-clock me-1"></i><?= e(thaiDateTime($survey['surveyed_at'])) ?>
                        <?php if ($isCancelled): ?> · ยกเลิกเมื่อ <?= e(thaiDateTime($survey['cancelled_at'])) ?><?php endif; ?>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" onclick="history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>ย้อนกลับ</button>
                    <?php if ($canCreate && !$isCancelled): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('surveys/' . $survey['student_id'] . '?term_id=' . $survey['academic_term_id'])) ?>"><i class="bi bi-plus-lg me-1"></i>สำรวจครั้งใหม่</a>
                    <?php endif; ?>
                    <?php if ($canCancel && !$isCancelled): ?>
                    <form method="post" action="<?= e(url('surveys/cancel/' . $survey['id'])) ?>" class="d-inline" onsubmit="return confirm('ยืนยันยกเลิกการสำรวจครั้งนี้? รายการนี้จะถูกนำออกจากประวัติ');">
                        <?= csrfField() ?>
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>ยกเลิกการสำรวจ</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($sections || $retired): ?>
        <?php foreach ($sections as $sectionName => $rows): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-4 pb-2"><h6 class="fw-bold mb-0 text-primary"><i class="bi bi-tag-fill me-2"></i>หมวด: <?= e($sectionName) ?></h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th style="width:55%">คำถาม</th><th>คำตอบ</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr><td class="small"><?= e($r['question']) ?></td><td class="small fw-semibold text-dark"><?= e($r['answer']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($retired): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-4 pb-2"><h6 class="fw-bold mb-0 text-secondary">คำถามที่เลิกใช้แล้ว (เก็บไว้เป็นประวัติ)</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    <?php foreach ($retired as $r): ?>
                        <tr><td class="small" style="width:55%"><?= e($r['question']) ?></td><td class="small text-muted"><?= e($r['answer']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-2 mb-0">ไม่มีคำตอบในแบบสำรวจครั้งนี้</p>
        </div>
    <?php endif; ?>

    <?php if (trim((string)($survey['note'] ?? '')) !== ''): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 px-4 pb-2"><h6 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>บันทึกเพิ่มเติม</h6></div>
        <div class="card-body px-4 py-3"><p class="mb-0 small"><?= nl2br(e($survey['note'])) ?></p></div>
    </div>
    <?php endif; ?>

    <?php if (count($history) > 1): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 px-4 pb-2"><h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>ประวัติการสำรวจทั้งหมด (<?= count($history) ?>)</h6></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead><tr><th>ครั้งที่</th><th>วันที่</th><th>ผู้บันทึก</th><th>สถานะ</th><th class="text-end">ดู</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr class="<?= (int)$h['id'] === (int)$survey['id'] ? 'table-active' : (empty($h['cancelled_at']) ? '' : 'text-muted') ?>">
                        <td>#<?= (int)$h['revision'] ?></td>
                        <td class="small"><?= e(thaiDateTime($h['surveyed_at'])) ?></td>
                        <td class="small"><?= e(($h['firstname'] ?? '') . ' ' . ($h['lastname'] ?? '')) ?></td>
                        <td><?= !empty($h['cancelled_at']) ? '<span class="badge bg-secondary">ยกเลิก</span>' : '<span class="badge bg-success">ใช้งาน</span>' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary btn-icon" href="<?= e(url('surveys/view/' . $h['id'])) ?>" title="ดูแบบสำรวจครั้งนี้"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div></div>
