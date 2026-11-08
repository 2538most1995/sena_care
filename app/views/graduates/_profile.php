<?php
if (!defined('APP_ROOT')) die();
$profileGroup = trim((string)($graduate['group_name'] ?? ''));
$profileCenter = trim((string)($graduate['center_name'] ?? ''));
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
        <span><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>ภาคเรียนที่สำเร็จ <?= e($graduate['graduation_term']) ?></span>
        <?php if ($profileGroup !== '' || $profileCenter !== ''): ?>
        <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= e($profileGroup !== '' ? $profileGroup : $profileCenter) ?></span>
        <?php endif; ?>
        <?php if ($profileGroup !== '' && $profileCenter !== '' && $profileGroup !== $profileCenter): ?>
        <span><?= e($profileCenter) ?></span>
        <?php endif; ?>
    </div>
</section>
