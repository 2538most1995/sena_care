<?php if (!defined('APP_ROOT')) die(); ?>
<div class="container-fluid px-3 px-md-4 py-3">
    <h4 class="fw-bold">ตั้งค่าระบบ SENA Care</h4>
    <p class="text-muted mb-4">กำหนดผู้ลงนามและรูปแบบการลงนามสำหรับรายงานเยี่ยมบ้าน PDF รายบุคคลและรายครู</p>
    <?php if ($settingsError !== ''): ?><div class="alert alert-danger" role="alert"><?= e($settingsError) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('settings')) ?>" class="mb-4">
        <?= csrfField() ?>
        <div class="row g-3">
            <?php foreach ([
                ['ผู้อำนวยการ / ผู้รับทราบ', 'bi-person-check', ['director_name','director_position','recipient_title','director_label']],
                ['ผู้รายงาน', 'bi-person-badge', ['reporter_name','reporter_position','reporter_label']],
            ] as [$heading, $icon, $fields]): ?>
            <div class="col-12 col-xl-6">
                <section class="card card-body h-100 p-3 p-md-4">
                    <h5 class="fw-bold mb-3"><i class="bi <?= e($icon) ?> text-primary me-2" aria-hidden="true"></i><?= e($heading) ?></h5>
                    <div class="row g-3">
                        <?php foreach ($fields as $key): [$label,$max] = ReportSettings::TEXT_FIELDS[$key]; ?>
                        <div class="col-12">
                            <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                            <?php if (in_array($key, ['director_position','reporter_position'], true)): ?>
                            <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3" maxlength="<?= $max ?>" aria-describedby="<?= e($key) ?>_help"><?= e($key === 'director_position' ? ReportSettings::directorPositionForReport($reportSettings[$key]) : $reportSettings[$key]) ?></textarea>
                            <div class="form-text" id="<?= e($key) ?>_help">กด Enter เพื่อขึ้นบรรทัดใหม่ใน PDF ได้ตามต้องการ</div>
                            <?php else: ?>
                            <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($reportSettings[$key]) ?>" maxlength="<?= $max ?>">
                            <?php endif; ?>
                            <?php if ($key === 'reporter_name'): ?><div class="form-text">เว้นว่างเพื่อใช้ชื่อครูเจ้าของบันทึกในแต่ละรายงาน</div><?php endif; ?>
                            <?php if ($key === 'director_name'): ?><div class="form-text">เว้นว่างเพื่อเว้นช่องให้กรอกชื่อผู้ลงนามภายหลัง</div><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
            <?php endforeach; ?>
            <div class="col-12">
                <section class="card card-body p-3 p-md-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pen text-primary me-2" aria-hidden="true"></i>รูปแบบช่องลงนาม</h5>
                    <div class="row g-3 mb-3">
                        <?php foreach (['signature_label','date_label'] as $key): [$label,$max] = ReportSettings::TEXT_FIELDS[$key]; ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                            <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($reportSettings[$key]) ?>" maxlength="<?= $max ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach (ReportSettings::CHECKBOXES as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="<?= e($key) ?>" name="<?= e($key) ?>" value="1"<?= $reportSettings[$key] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-2" aria-hidden="true"></i>บันทึกการตั้งค่า</button>
        </div>
        <p class="form-text text-end mb-0">ค่าที่บันทึกจะใช้เมื่อสร้างรายงาน PDF ครั้งถัดไป</p>
    </form>
    <h5 class="fw-bold mb-3">จัดการระบบ</h5>
    <div class="row g-3">
        <?php foreach (['users/'.currentUserId().'/edit'=>['บัญชี super admin','แก้ไขข้อมูลส่วนตัว ชื่อผู้ใช้ และรหัสผ่าน'],'integrations/sdl'=>['เชื่อมต่อ SDL_school','ซิงก์ครู ผู้เรียน และกลุ่มเรียนจากข้อมูลจริง'],'terms'=>['ภาคเรียน','กำหนดภาคเรียนทำงาน'],'centers'=>['ศกร. / กลุ่มเรียน','ตรวจสอบการผูกกลุ่มเรียนและครู']] as $path=>$item): ?>
        <div class="col-12 col-md-6"><a class="card card-body h-100 text-decoration-none" href="<?= e(url($path)) ?>"><h6 class="fw-bold"><?= e($item[0]) ?></h6><span class="text-muted"><?= e($item[1]) ?></span></a></div>
        <?php endforeach; ?>
    </div>
</div>
