<?php
if (!defined('APP_ROOT')) die();
$profileGroup = trim((string)($graduate['group_name'] ?? ''));
$profileCenter = trim((string)($graduate['center_name'] ?? ''));
$documents = $graduateDocuments ?? [];
$term = (string)($graduate['graduation_term'] ?? '');
$shortTerm = preg_match('#^([12])/(25\d{2})$#', $term, $termParts) ? substr($termParts[2], -2) . '/' . $termParts[1] : '';
$documentDate = static function ($value): string {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return '–';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? thaiDate($value) : '–';
};
?>
<section class="graduate-profile <?= empty($isPanel) ? 'card mt-3' : '' ?>" aria-label="ข้อมูลผู้สำเร็จการศึกษา">
    <div class="graduate-profile-heading">
        <div class="avatar bg-primary-subtle text-primary"><i class="bi bi-award-fill" aria-hidden="true"></i></div>
        <div class="graduate-profile-title">
            <h4 class="fw-bold mb-1"><?= e($graduate['full_name']) ?></h4>
            <div class="graduate-profile-identifiers">
                <span>รหัสนักศึกษา <?= e($graduate['student_code']) ?></span>
                <span><?= e($graduate['level_label'] ?: '-') ?></span>
            </div>
        </div>
        <?php if (isset($totalTrackings)): ?>
        <span class="badge rounded-pill <?= $totalTrackings ? 'bg-success' : 'bg-secondary' ?>">
            <?= $totalTrackings ? 'ติดตามแล้ว ' . $totalTrackings . ' ครั้ง' : 'ยังไม่ติดตาม' ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="graduate-profile-meta">
        <span><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>ภาคเรียนที่สำเร็จ <?= e($term) ?><?= $shortTerm !== '' ? ' (' . e($shortTerm) . ')' : '' ?></span>
        <?php if ($profileGroup !== '' || $profileCenter !== ''): ?>
        <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= e($profileGroup !== '' ? $profileGroup : $profileCenter) ?></span>
        <?php endif; ?>
        <?php if ($profileGroup !== '' && $profileCenter !== '' && $profileGroup !== $profileCenter): ?>
        <span><?= e($profileCenter) ?></span>
        <?php endif; ?>
    </div>
    <div class="graduate-document-details" aria-label="ข้อมูลเอกสารสำเร็จการศึกษา">
        <div><span>เลขที่ รบ.</span><strong><?= e(($documents['transcript_number'] ?? '') !== '' ? $documents['transcript_number'] : '–') ?></strong></div>
        <div><span>ชุด รบ.</span><strong><?= e(($documents['transcript_group'] ?? '') !== '' ? $documents['transcript_group'] : '–') ?></strong></div>
        <div><span>เลขที่ประกาศนียบัตร</span><strong><?= e(($documents['certificate_number'] ?? '') !== '' ? $documents['certificate_number'] : '–') ?></strong></div>
        <div><span>วันที่ออก รบ.</span><strong><?= e($documentDate($documents['transcript_date'] ?? null)) ?></strong></div>
        <div><span>วันที่จบ</span><strong><?= e($documentDate($documents['graduation_date'] ?? null)) ?></strong></div>
    </div>
    <?php if (!empty($documentsUnavailable)): ?>
        <p class="small text-muted mb-0 mt-2">ไม่สามารถดึงข้อมูลเอกสารจาก SDL_school ได้ในขณะนี้</p>
    <?php elseif (!is_array($graduateDocuments ?? null)): ?>
        <p class="small text-muted mb-0 mt-2">ไม่พบข้อมูลเอกสารของผู้สำเร็จการศึกษารายนี้จาก SDL_school</p>
    <?php endif; ?>
</section>
