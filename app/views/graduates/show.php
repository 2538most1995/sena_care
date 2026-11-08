<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$totalTrackings = count($trackings);
?>
<div class="<?= !empty($isPanel) ? 'graduate-panel' : 'container-fluid px-3 px-md-4 py-3' ?>">
    <?php if (empty($isPanel)): ?>
    <a href="<?= e(url('graduates')) ?>" class="small">← กลับรายชื่อผู้สำเร็จการศึกษา</a>
    <?php endif; ?>

    <?php include __DIR__ . '/_profile.php'; ?>

    <?php if (!$totalTrackings): ?>
        <div class="w-100">
            <?php if ($canTrack): ?>
                <div class="alert bg-primary-subtle text-primary-emphasis border-0 d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>ยังไม่มีผลการติดตาม กรอกแบบฟอร์มด้านล่างเพื่อเริ่มบันทึก</span>
                </div>
                <?php
                $formAction = url('graduates/' . $graduate['id'] . '/track');
                $tracking = null;
                $graduateId = (int)$graduate['id'];
                include __DIR__ . '/_form.php';
                ?>
            <?php else: ?>
                <div class="alert alert-secondary"><i class="bi bi-info-circle me-1"></i>สิทธิ์ผู้บริหาร: ดูข้อมูลได้อย่างเดียว ยังไม่มีผลการติดตามสำหรับผู้สำเร็จการศึกษารายนี้</div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="graduate-history-heading d-flex align-items-center gap-2 mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>ประวัติการติดตาม</h5>
            <span class="badge bg-light text-secondary border"><?= $totalTrackings ?> ครั้ง</span>
        </div>

        <div class="tracking-list">
            <?php foreach ($trackings as $index => $t): ?>
                <div class="tracking-card">
                    <div class="tracking-card-head">
                        <span class="tracking-round">ครั้งที่ <?= $totalTrackings - $index ?></span>
                        <?php if ($canTrack): ?>
                        <a class="btn btn-sm btn-outline-secondary ms-auto" href="<?= e(url('graduates/' . $graduate['id'] . '/track/' . $t['id'] . '/edit')) ?>" data-panel-url="<?= e(url('graduates/' . $graduate['id'] . '/track/' . $t['id'] . '/edit')) ?>">
                            <i class="bi bi-pencil me-1"></i>แก้ไข
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="tracking-card-content <?= !empty($t['image_path']) ? 'has-photo' : '' ?>">
                        <div class="tracking-card-summary">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="tracking-date"><i class="bi bi-calendar3 me-1"></i><?= e(thaiDateTime($t['tracked_at'])) ?></span>
                                <span class="tracking-user"><i class="bi bi-person me-1"></i><?= e(($t['firstname'] ?? '') . ' ' . ($t['lastname'] ?? '')) ?></span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php if ($t['categories']): ?>
                                    <?php foreach ($t['categories'] as $cat): ?>
                                        <span class="badge bg-primary-subtle text-primary-emphasis"><?= e($categories[$cat] ?? $cat) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">ไม่ได้ระบุหมวด</span>
                                <?php endif; ?>
                            </div>

                            <?php if (trim((string)($t['other_detail'] ?? '')) !== ''): ?>
                                <p class="tracking-detail"><?= nl2br(e($t['other_detail'])) ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($t['image_path'])): ?>
                            <a class="tracking-evidence" href="<?= e($baseUrl . '/' . $t['image_path']) ?>" data-lightbox data-alt="ภาพหลักฐานการติดตามผู้สำเร็จการศึกษา" title="คลิกเพื่อดูรูปขนาดเต็ม">
                                <img src="<?= e($baseUrl . '/' . $t['image_path']) ?>" alt="ภาพหลักฐานการติดตาม" class="tracking-photo" loading="lazy">
                                <span><i class="bi bi-arrows-fullscreen me-1" aria-hidden="true"></i>ดูรูปหลักฐาน</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
