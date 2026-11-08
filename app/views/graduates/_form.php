<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$catIcons = ['study' => 'bi-mortarboard-fill', 'career' => 'bi-briefcase-fill', 'development' => 'bi-people-fill'];
$catHelp = [
    'study'       => 'ศึกษาต่อในระดับที่สูงขึ้น',
    'career'      => 'เพิ่มรายได้ ลดรายจ่าย สร้างอาชีพใหม่ หรือต่อยอดอาชีพเดิม',
    'development' => 'ทักษะชีวิต สุขภาพ การคิดวิเคราะห์ หรือการแก้ปัญหาในชุมชน',
];
$selected = $tracking['categories'] ?? [];
$isEdit = !empty($tracking);
?>
<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="tracking-form <?= !empty($isPanel) ? 'graduate-form-panel' : 'card card-body p-3 p-md-4' ?>">
    <?= csrfField() ?>
    <div class="alert alert-danger d-none" role="alert" data-tracking-error tabindex="-1"></div>

    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar avatar-md bg-primary-subtle text-primary fw-bold"><i class="bi bi-clipboard2-check fs-5"></i></div>
        <div>
            <h5 class="fw-bold mb-0"><?= $isEdit ? 'แก้ไขผลการติดตาม' : 'บันทึกผลการติดตาม' ?></h5>
            <p class="text-muted small mb-0">นำวุฒิการศึกษาไปใช้ทำอะไร (เลือกได้มากกว่า 1 หมวด)</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <fieldset>
            <legend class="form-label fw-semibold small">ผลลัพธ์หลังสำเร็จการศึกษา</legend>
            <div class="outcome-list">
                <?php foreach ($categories as $key => $label): ?>
                <label class="outcome-option">
                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?= e($key) ?>" <?= in_array($key, $selected, true) ? 'checked' : '' ?>>
                    <span class="outcome-card">
                        <i class="bi <?= e($catIcons[$key] ?? 'bi-check2-circle') ?> oc-icon"></i>
                        <span class="oc-body">
                            <span class="oc-title"><?= e($label) ?></span>
                            <span class="oc-desc"><?= e($catHelp[$key] ?? '') ?></span>
                        </span>
                        <span class="oc-check"><i class="bi bi-check-circle-fill"></i></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-2">เลือกอย่างน้อย 1 หมวด หรือระบุรายละเอียดเพิ่มเติม</div>
            </fieldset>
        </div>

        <div class="col-12 col-lg-6">
            <div class="mb-3">
                <label class="form-label fw-semibold small text-dark" for="gradDetail">รายละเอียดเพิ่มเติม (ถ้ามี)</label>
                <textarea class="form-control" id="gradDetail" name="other_detail" rows="4" maxlength="5000" placeholder="เช่น ศึกษาต่อที่ใด ประกอบอาชีพอะไร หรือนำความรู้ไปใช้ในชุมชนอย่างไร..."><?= e((string)($tracking['other_detail'] ?? '')) ?></textarea>
                <div class="form-text">พิมพ์เพิ่มได้ไม่เกิน 5,000 ตัวอักษร</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-dark" for="gradPhoto">แนบภาพหลักฐาน 1 ภาพ (ถ้ามี)</label>
                <input class="form-control" type="file" id="gradPhoto" name="photo" accept="image/jpeg,image/png,image/webp" data-graduate-photo>
                <div class="form-text"><i class="bi bi-info-circle me-1"></i>ระบบจะย่อขนาดรูปอัตโนมัติเพื่อประหยัดพื้นที่เซิร์ฟเวอร์</div>
                <?php if (!empty($tracking['image_path'])): ?>
                <div class="d-flex align-items-center gap-3 mt-2">
                    <a href="<?= e($baseUrl . '/' . $tracking['image_path']) ?>" data-lightbox data-alt="ภาพหลักฐานเดิม" title="คลิกเพื่อดูรูปขนาดเต็ม">
                        <img src="<?= e($baseUrl . '/' . $tracking['image_path']) ?>" alt="ภาพหลักฐานเดิม" class="tracking-photo">
                    </a>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeGradPhoto">
                        <label class="form-check-label small text-danger" for="removeGradPhoto">ลบรูปเดิม</label>
                    </div>
                </div>
                <?php endif; ?>
                <img class="graduate-photo-preview mt-2 rounded border d-none" data-graduate-preview alt="ตัวอย่างรูปที่เลือก">
            </div>

        </div>
        <div class="col-12">
            <div class="tracking-form-actions d-flex flex-wrap justify-content-end gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกผลการติดตาม' ?></button>
                <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('graduates/' . $graduateId)) ?>" data-panel-url="<?= e(url('graduates/' . $graduateId)) ?>">ยกเลิก</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
