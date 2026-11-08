<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <div class="mb-3">
        <a href="<?= $baseUrl ?>/students" class="text-decoration-none text-secondary small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> กลับไปหน้ารายชื่อผู้เรียน
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>นำเข้าข้อมูลผู้เรียน (Excel / CSV)</h4>
                    <p class="text-muted small mb-0">อัปโหลดไฟล์ CSV รายชื่อผู้เรียนเพื่อนำเข้าข้อมูลพร้อมสร้างการลงทะเบียนอัตโนมัติ</p>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="<?= $baseUrl ?>/students/import" enctype="multipart/form-data">
                        <?= csrfField() ?>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">ภาคเรียนที่จะลงทะเบียน <span class="text-danger">*</span></label>
                                <select name="academic_term_id" class="form-select" required>
                                    <?php foreach ($terms as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $t['id'] == currentTermId() ? 'selected' : '' ?>><?= e($t['term_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">ศกร.ตำบล <span class="text-danger">*</span></label>
                                <select name="learning_center_id" class="form-select" required>
                                    <?php foreach ($centers as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= e($c['center_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (hasRole('super_admin', 'admin')): ?>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">ครูผู้รับผิดชอบ</label>
                                <select name="teacher_id" class="form-select">
                                    <?php foreach ($teachers as $tch): ?>
                                        <option value="<?= $tch['id'] ?>"><?= e($tch['prefix'] . $tch['firstname'] . ' ' . $tch['lastname']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- File Input -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">เลือกไฟล์ CSV / Excel <span class="text-danger">*</span></label>
                            <input type="file" name="import_file" class="form-control" accept=".csv" required>
                            <div class="form-text small text-muted">รองรับไฟล์รูปแบบ <strong>.CSV</strong> (เข้ารหัส UTF-8) ขนาดไม่เกิน 10MB</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= $baseUrl ?>/students" class="btn btn-outline-secondary px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-upload me-1"></i> เริ่มนำเข้าข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle text-primary me-2"></i>รูปแบบคอลัมน์ของไฟล์นำเข้า (CSV)</h5>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">แถวแรกต้องเป็นหัวคอลัมน์ และเรียงลำดับคอลัมน์ดังต่อไปนี้:</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered small mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>คอลัมน์</th>
                                    <th>ชื่อฟิลด์</th>
                                    <th>คำอธิบาย / ตัวอย่าง</th>
                                    <th>จำเป็น</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>A (1)</td><td>student_code</td><td>รหัสนักศึกษา (เช่น 69110001)</td><td><span class="badge bg-danger">จำเป็น</span></td></tr>
                                <tr><td>B (2)</td><td>citizen_id</td><td>เลขบัตรประชาชน 13 หลัก</td><td>-</td></tr>
                                <tr><td>C (3)</td><td>prefix</td><td>คำนำหน้า (นาย / นางสาว / นาง)</td><td>-</td></tr>
                                <tr><td>D (4)</td><td>firstname</td><td>ชื่อจริง</td><td><span class="badge bg-danger">จำเป็น</span></td></tr>
                                <tr><td>E (5)</td><td>lastname</td><td>นามสกุล</td><td><span class="badge bg-danger">จำเป็น</span></td></tr>
                                <tr><td>F (6)</td><td>education_level</td><td>primary / lower_secondary / upper_secondary</td><td><span class="badge bg-danger">จำเป็น</span></td></tr>
                                <tr><td>G (7)</td><td>phone</td><td>เบอร์โทรศัพท์ (เช่น 0812345678)</td><td>-</td></tr>
                                <tr><td>H (8)</td><td>house_no</td><td>บ้านเลขที่ (เช่น 12/1)</td><td>-</td></tr>
                                <tr><td>I (9)</td><td>subdistrict</td><td>ตำบล (เช่น เสนา, บางนมโค)</td><td>-</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
