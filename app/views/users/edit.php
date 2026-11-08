<?php if (!defined('APP_ROOT')) die(); ?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="mx-auto" style="max-width:640px">
        <a href="<?= e(url('users')) ?>" class="text-decoration-none">← กลับไปจัดการผู้ใช้งาน</a>
        <div class="card border-0 shadow-sm rounded-4 mt-3"><div class="card-body p-3 p-md-4">
            <h4 class="fw-bold">แก้ไขข้อมูลผู้ดูแลและรหัสผ่าน</h4>
            <p class="text-muted"><?= e(($account['prefix']??'').$account['firstname'].' '.$account['lastname']) ?> · <?= e($account['role']) ?></p>
            <?php if ($linked): ?>
                <div class="alert alert-info">บัญชีนี้ใช้ชื่อผู้ใช้และรหัสผ่านจาก SDL_school กรุณาแก้ไขที่ระบบต้นทาง แล้วซิงก์ข้อมูลอีกครั้ง</div>
            <?php else: ?>
            <form method="post" action="<?= e(url('users/'.$account['id'].'/edit')) ?>">
                <?= csrfField() ?>
                <div class="row g-3 mb-3">
                <?php foreach (['prefix'=>'คำนำหน้า','firstname'=>'ชื่อ','lastname'=>'นามสกุล','email'=>'อีเมล','phone'=>'โทรศัพท์'] as $key=>$label): ?>
                    <div class="col-12 col-sm-6"><label class="form-label" for="profile_<?= $key ?>"><?= e($label) ?></label><input class="form-control" id="profile_<?= $key ?>" name="<?= $key ?>" type="<?= $key==='email'?'email':'text' ?>" value="<?= e($account[$key]??'') ?>" <?= in_array($key,['firstname','lastname'])?'required':'' ?>></div>
                <?php endforeach; ?></div>
                <div class="mb-3"><label class="form-label" for="accountUsername">ชื่อผู้ใช้ (Username)</label><input id="accountUsername" type="text" name="username" class="form-control" value="<?= e($account['username']) ?>" maxlength="100" autocomplete="username" required></div>
                <div class="mb-3"><label class="form-label" for="accountPassword">รหัสผ่านใหม่</label><input id="accountPassword" type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password" aria-describedby="passwordHelp"><div id="passwordHelp" class="form-text">อย่างน้อย 8 ตัวอักษร เว้นว่างทั้งสองช่องเพื่อใช้รหัสผ่านเดิม</div></div>
                <div class="mb-4"><label class="form-label" for="accountPasswordConfirm">ยืนยันรหัสผ่านใหม่</label><input id="accountPasswordConfirm" type="password" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password"></div>
                <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit">บันทึกการเปลี่ยนแปลง</button><a class="btn btn-outline-secondary" href="<?= e(url('users')) ?>">ยกเลิก</a></div>
            </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>
