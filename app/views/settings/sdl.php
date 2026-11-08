<?php if (!defined('APP_ROOT')) die(); ?>
<div class="page-header"><h1 class="page-title">เชื่อมต่อ SDL_school</h1><p class="text-muted">นักศึกษา กลุ่มเรียน และครูจากระบบต้นทาง</p></div>
<div class="card"><div class="card-body">
    <p>แหล่งข้อมูล: <strong><?= $sourceMode === 'remote' ? 'เว็บจริง krumost.com/SDL_school' : 'ฐานข้อมูลสำเนาในเครื่อง' ?></strong></p>
    <p>สถานะ: <span class="badge <?= $enabled ? 'bg-success' : 'bg-secondary' ?>"><?= $enabled ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้ตั้งค่า' ?></span></p>
    <?php if (($sdlSettings['token_expiry_mode'] ?? '') === 'never'): ?>
        <p>อายุการเชื่อมต่อ: <strong>ไม่มีวันหมดอายุ</strong></p>
    <?php endif; ?>
    <p>ครูใช้ชื่อผู้ใช้และรหัสผ่านเดียวกับ SDL_school ระบบตรวจรหัสผ่านที่ต้นทางทุกครั้งที่เข้าสู่ระบบ</p>
    <?php if ($lastRun): ?>
        <p class="text-muted">ซิงก์ล่าสุด: <?= e($lastRun['created_at']) ?> (เวลาไทย)</p>
        <div class="row g-3 mb-3">
        <?php foreach (['students'=>'นักศึกษา','teachers'=>'ครู','groups'=>'กลุ่มเรียน'] as $key=>$label): ?>
            <div class="col-sm-4"><div class="border rounded p-3"><?= e($label) ?> <strong><?= (int)$summary[$key] ?></strong></div></div>
        <?php endforeach; ?>
        </div>
        <?php if ((int)$summary['students'] === 0): ?>
            <div class="alert alert-warning">API ยังไม่พบนักศึกษาในอำเภอที่เชื่อมต่อ กรุณาตรวจสอบข้อมูลต้นทางแล้วซิงก์อีกครั้ง</div>
        <?php endif; ?>
        <?php if (!empty($summary['conflicts'])): ?>
            <p>ผู้เรียน <?= (int)$summary['conflicts'] ?> คนมีครูร่วมรับผิดชอบ ระบบให้สิทธิ์ครูทุกคนตามกลุ่มที่ต้นทางกำหนดแล้ว</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($unassignedGroups): ?>
        <div class="alert alert-warning">ยังมีผู้เรียน <?= (int)($summary['unassigned'] ?? 0) ?> คนที่ต้นทางไม่ได้ผูกครู กรุณากำหนดกลุ่มให้ครูใน SDL_school แล้วซิงก์อีกครั้ง</div>
        <table class="table mb-3"><thead><tr><th>กลุ่มเรียนที่ยังไม่ผูกครู</th><th>นักศึกษา</th></tr></thead><tbody>
        <?php foreach ($unassignedGroups as $group): ?>
            <tr><td><?= e($group['group_name']) ?></td><td><?= (int)$group['students'] ?> คน</td></tr>
        <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
    <form method="post" action="<?= e(url('integrations/sdl')) ?>">
        <?= csrfField() ?>
        <button class="btn btn-primary" type="submit" <?= $enabled ? '' : 'disabled' ?>><i class="bi bi-arrow-repeat me-2"></i>ดึงข้อมูลล่าสุดจาก SDL_school</button>
    </form>
    <p class="text-muted small mt-3 mb-0">การซิงก์ปรับชื่อ กลุ่มเรียน และครูตามต้นทาง โดยเก็บที่อยู่ โทรศัพท์ และประวัติเยี่ยมบ้านที่บันทึกใน SENA Care ไว้</p>
</div></div>
<details class="card mt-3" <?= $enabled ? '' : 'open' ?>>
    <summary class="card-header">ตั้งค่าการเชื่อมต่อเว็บจริง / เปลี่ยน API token</summary>
    <div class="card-body">
        <p>สร้าง API token ที่โฮสติ้งของ SDL_school ด้วยบัญชีผู้ดูแลระบบ แล้วกรอกด้านล่าง ระบบทดสอบสิทธิ์ก่อนบันทึก</p>
        <pre class="bg-light p-3 text-wrap">php artisan system:create-student-api-token YOUR_ADMIN_USERNAME --district-code=sena --name=sena-care --sena-care --no-expiry</pre>
        <p class="text-muted small">รันในโฟลเดอร์ laravel-app หลังติดตั้ง API สำหรับ SENA Care แล้ว เปลี่ยน YOUR_ADMIN_USERNAME เป็นชื่อผู้ดูแลต้นทาง</p>
        <form method="post" action="<?= e(url('integrations/sdl')) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="connect_remote">
            <label class="form-label" for="sdlApiToken">API token จาก SDL_school</label>
            <input class="form-control mb-3" id="sdlApiToken" name="api_token" type="password" autocomplete="off" required placeholder="sdl_student_…">
            <button class="btn btn-outline-primary" type="submit">ทดสอบและบันทึกการเชื่อมต่อ</button>
        </form>
    </div>
</details>
