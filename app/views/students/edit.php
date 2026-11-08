<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <div class="mb-3">
        <a href="<?= $baseUrl ?>/students/<?= $student['id'] ?>" class="text-decoration-none text-secondary small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> กลับไปหน้าข้อมูลผู้เรียน
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>แก้ไขข้อมูลผู้เรียน: <?= e($student['firstname'] . ' ' . $student['lastname']) ?></h4>
            <p class="text-muted small mb-0">ปรับปรุงข้อมูลผู้เรียน ข้อมูลการศึกษา และพิกัดบ้าน</p>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="<?= $baseUrl ?>/students/<?= $student['id'] ?>/edit">
                <?= csrfField() ?>

                <!-- Section 1: ข้อมูลการศึกษา -->
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-mortarboard me-2"></i>1. ข้อมูลการศึกษา</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">รหัสนักศึกษา <span class="text-danger">*</span></label>
                        <input type="text" name="student_code" class="form-control" required value="<?= e($student['student_code']) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">ระดับการศึกษา <span class="text-danger">*</span></label>
                        <select name="education_level" class="form-select" required>
                            <option value="primary" <?= ($student['education_level'] ?? '') === 'primary' ? 'selected' : '' ?>>ประถมศึกษา</option>
                            <option value="lower_secondary" <?= ($student['education_level'] ?? '') === 'lower_secondary' ? 'selected' : '' ?>>มัธยมศึกษาตอนต้น</option>
                            <option value="upper_secondary" <?= ($student['education_level'] ?? '') === 'upper_secondary' ? 'selected' : '' ?>>มัธยมศึกษาตอนปลาย</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">ศกร.ตำบล</label>
                        <select name="learning_center_id" class="form-select">
                            <option value="">เลือก ศกร.</option>
                            <?php foreach ($centers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($student['learning_center_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['center_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (hasRole('super_admin', 'admin')): ?>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">ครูผู้รับผิดชอบ</label>
                        <select name="teacher_id" class="form-select">
                            <?php foreach ($teachers as $tch): ?>
                                <option value="<?= $tch['id'] ?>" <?= ($student['teacher_id'] ?? '') == $tch['id'] ? 'selected' : '' ?>><?= e($tch['prefix'] . $tch['firstname'] . ' ' . $tch['lastname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="text-muted my-4">

                <!-- Section 2: ข้อมูลส่วนตัว -->
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person me-2"></i>2. ข้อมูลส่วนตัว</h6>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">คำนำหน้า <span class="text-danger">*</span></label>
                        <select name="prefix" class="form-select" required>
                            <option value="นาย" <?= $student['prefix'] === 'นาย' ? 'selected' : '' ?>>นาย</option>
                            <option value="นางสาว" <?= $student['prefix'] === 'นางสาว' ? 'selected' : '' ?>>นางสาว</option>
                            <option value="นาง" <?= $student['prefix'] === 'นาง' ? 'selected' : '' ?>>นาง</option>
                            <option value="เด็กชาย" <?= $student['prefix'] === 'เด็กชาย' ? 'selected' : '' ?>>เด็กชาย</option>
                            <option value="เด็กหญิง" <?= $student['prefix'] === 'เด็กหญิง' ? 'selected' : '' ?>>เด็กหญิง</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" name="firstname" class="form-control" required value="<?= e($student['firstname']) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="lastname" class="form-control" required value="<?= e($student['lastname']) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">ชื่อเล่น</label>
                        <input type="text" name="nickname" class="form-control" value="<?= e($student['nickname'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">เพศ</label>
                        <select name="gender" class="form-select">
                            <option value="male" <?= ($student['gender'] ?? '') === 'male' ? 'selected' : '' ?>>ชาย</option>
                            <option value="female" <?= ($student['gender'] ?? '') === 'female' ? 'selected' : '' ?>>หญิง</option>
                            <option value="other" <?= ($student['gender'] ?? '') === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">เลขบัตรประชาชน (13 หลัก)</label>
                        <input type="text" name="citizen_id" maxlength="13" class="form-control" value="<?= e($student['citizen_id'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">วันเดือนปีเกิด</label>
                        <input type="date" name="birthdate" class="form-control" value="<?= e($student['birthdate'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($student['phone'] ?? '') ?>">
                    </div>
                </div>

                <hr class="text-muted my-4">

                <!-- Section 3: ที่อยู่และพิกัด GPS -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt me-2"></i>3. ที่อยู่และตำแหน่งบ้านบนแผนที่ (GPS)</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnUseCurrentLocation">
                        <i class="bi bi-crosshair me-1"></i> ใช้พิกัดปัจจุบัน
                    </button>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">บ้านเลขที่</label>
                        <input type="text" name="house_no" class="form-control" value="<?= e($address['house_no'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">หมู่ที่</label>
                        <input type="text" name="moo" class="form-control" value="<?= e($address['moo'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">ตำบล</label>
                        <input type="text" name="subdistrict" class="form-control" value="<?= e($address['subdistrict'] ?? 'เสนา') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">อำเภอ</label>
                        <input type="text" name="district" class="form-control" value="<?= e($address['district'] ?? 'เสนา') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">จังหวัด</label>
                        <input type="text" name="province" class="form-control" value="<?= e($address['province'] ?? 'พระนครศรีอยุธยา') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">รหัสไปรษณีย์</label>
                        <input type="text" name="zipcode" class="form-control" value="<?= e($address['zipcode'] ?? '13110') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Latitude</label>
                        <input type="number" step="any" name="latitude" id="inputLat" class="form-control" value="<?= e($address['latitude'] ?? '14.328400') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Longitude</label>
                        <input type="number" step="any" name="longitude" id="inputLng" class="form-control" value="<?= e($address['longitude'] ?? '100.404200') ?>">
                    </div>
                </div>

                <!-- Leaflet Interactive Pin Picker Map -->
                <div class="mb-4">
                    <label class="form-label small text-muted">ลากหมุดหรือคลิกบนแผนที่เพื่อเปลี่ยนตำแหน่ง</label>
                    <div class="map-container rounded-4 border" id="editMap" style="height: 350px;"></div>
                </div>

                <hr class="text-muted my-4">

                <!-- Section 4: ผู้ปกครอง -->
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-people me-2"></i>4. ข้อมูลผู้ปกครอง / บุคคลติดต่อ</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">ชื่อ-นามสกุล ผู้ปกครอง</label>
                        <input type="text" name="parent_name" class="form-control" value="<?= e($contact['name'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold">ความสัมพันธ์</label>
                        <input type="text" name="parent_relationship" class="form-control" value="<?= e($contact['relationship'] ?? 'บิดา') ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold">เบอร์โทรศัพท์ผู้ปกครอง</label>
                        <input type="tel" name="parent_phone" class="form-control" value="<?= e($contact['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <?php if (isAdmin()): ?>
                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('คุณแน่ใจว่าต้องการลบข้อมูลผู้เรียนคนนี้?')) { document.getElementById('deleteForm').submit(); }">
                        <i class="bi bi-trash me-1"></i> ลบข้อมูลผู้เรียน
                    </button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <a href="<?= $baseUrl ?>/students/<?= $student['id'] ?>" class="btn btn-outline-secondary px-4">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </div>
            </form>

            <?php if (isAdmin()): ?>
            <form method="POST" action="<?= $baseUrl ?>/students/<?= $student['id'] ?>/delete" id="deleteForm" style="display:none;">
                <?= csrfField() ?>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lat = <?= (float)($address['latitude'] ?? 14.328400) ?>;
    let lng = <?= (float)($address['longitude'] ?? 100.404200) ?>;

    const map = L.map('editMap', { scrollWheelZoom: false }).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = L.marker([lat, lng], { icon: createMapMarkerIcon(), title: 'ตำแหน่งบ้านผู้เรียน', draggable: true }).addTo(map);

    function updateInputs(newLat, newLng) {
        document.getElementById('inputLat').value = newLat.toFixed(6);
        document.getElementById('inputLng').value = newLng.toFixed(6);
    }

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        updateInputs(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });

    document.getElementById('btnUseCurrentLocation')?.addEventListener('click', async function() {
        try {
            const pos = await getCurrentPosition();
            map.setView([pos.latitude, pos.longitude], 16);
            marker.setLatLng([pos.latitude, pos.longitude]);
            updateInputs(pos.latitude, pos.longitude);
            showToast('success', 'ปักหมุดที่พิกัดปัจจุบันเรียบร้อย');
        } catch (err) {
            showToast('danger', err.message);
        }
    });
});
</script>
