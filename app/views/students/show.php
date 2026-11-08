<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$navigationUrl = googleMapsUrl($address['latitude'] ?? null, $address['longitude'] ?? null);
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Back Button & Breadcrumbs -->
    <div class="mb-3">
        <a href="<?= $baseUrl ?>/students" class="text-decoration-none text-secondary small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> กลับไปหน้ารายชื่อผู้เรียน
        </a>
    </div>

    <!-- Student Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary-subtle text-primary fw-bold fs-2 rounded-circle">
                        <?= mb_substr($student['firstname'], 0, 1) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h3 class="fw-bold mb-0 text-dark"><?= e($student['prefix'] . $student['firstname'] . ' ' . $student['lastname']) ?></h3>
                            <?= riskBadge($student['risk_level'] ?? 'low') ?>
                            <span class="badge bg-secondary-subtle text-secondary"><?= e(educationLevelName($student['education_level'] ?? '')) ?></span>
                        </div>
                        <div class="text-muted small d-flex flex-wrap gap-3">
                            <span><i class="bi bi-card-heading text-primary me-1"></i> รหัสนักศึกษา: <strong><?= e($student['student_code']) ?></strong></span>
                            <span><i class="bi bi-telephone text-primary me-1"></i> เบอร์โทร: <?= e(formatPhone($student['phone'] ?? '')) ?></span>
                            <span><i class="bi bi-building text-primary me-1"></i> <?= e($student['center_name'] ?? 'ไม่ระบุ ศกร.') ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <?php if (canCreateVisitForCenter((int)($student['learning_center_id'] ?? 0))): ?>
                    <a href="<?= $baseUrl ?>/visits/create/<?= $student['id'] ?>" class="btn btn-success">
                        <i class="bi bi-house-heart-fill me-1"></i> บันทึกเยี่ยมบ้าน
                    </a>
                    <?php endif; ?>
                    <?php if (hasRole('super_admin', 'admin', 'teacher')): ?>
                    <a href="<?= $baseUrl ?>/students/<?= $student['id'] ?>/edit" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i> แก้ไข
                    </a>
                    <?php endif; ?>
                    <?php if ($navigationUrl !== null): ?>
                    <a href="<?= e($navigationUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                        <i class="bi bi-navigation-fill me-1"></i> นำทาง GPS
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 gap-2" id="studentTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-pill px-4" id="info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">
                <i class="bi bi-person-lines-fill me-1"></i> ข้อมูลทั่วไปและแผนที่
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" id="visits-tab" data-bs-toggle="tab" data-bs-target="#tab-visits" type="button">
                <i class="bi bi-house-door-fill me-1"></i> ประวัติการเยี่ยมบ้าน (<?= count($visits) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" id="surveys-tab" data-bs-toggle="tab" data-bs-target="#tab-surveys" type="button">
                <i class="bi bi-clipboard2-data me-1"></i> ประวัติการสำรวจ (<?= count($surveys ?? []) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" id="followups-tab" data-bs-toggle="tab" data-bs-target="#tab-followups" type="button">
                <i class="bi bi-clipboard-check-fill me-1"></i> การติดตามช่วยเหลือ (<?= count($followups) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" id="history-tab" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">
                <i class="bi bi-clock-history me-1"></i> ประวัติการเรียน (<?= count($enrollmentHistory) ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="studentTabContent">

        <!-- Tab 1: Info & Map -->
        <div class="tab-pane fade show active" id="tab-info">
            <div class="row g-4">
                <!-- Left: Details -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold mb-0"><i class="bi bi-info-circle text-primary me-2"></i>ข้อมูลส่วนตัว</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-6 text-muted">วันเดือนปีเกิด:</div>
                                <div class="col-6 fw-medium"><?= e($student['birthdate_display'] ?? thaiDate($student['birthdate'])) ?></div>
                                <div class="col-6 text-muted">เพศ:</div>
                                <div class="col-6 fw-medium"><?= $student['gender'] === 'male' ? 'ชาย' : ($student['gender'] === 'female' ? 'หญิง' : 'ยังไม่ระบุ') ?></div>
                                <div class="col-6 text-muted">อาชีพ:</div>
                                <div class="col-6 fw-medium"><?= e($student['occupation'] ?: '-') ?></div>
                                <div class="col-6 text-muted">อีเมล:</div>
                                <div class="col-6 fw-medium"><?= e($student['email'] ?: '-') ?></div>
                                <?php foreach (['nationality' => 'สัญชาติ', 'religion' => 'ศาสนา'] as $field => $label): ?>
                                    <?php if (!empty($student[$field])): ?>
                                    <div class="col-6 text-muted"><?= e($label) ?>:</div>
                                    <div class="col-6 fw-medium"><?= e($student[$field]) ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <div class="col-6 text-muted">ครูที่ปรึกษา:</div>
                                <div class="col-6 fw-medium text-primary"><?= e(($student['teacher_firstname'] ?? '') . ' ' . ($student['teacher_lastname'] ?? '')) ?: '-' ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Parents / Contacts -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold mb-0"><i class="bi bi-people text-primary me-2"></i>ผู้ปกครอง / บุคคลติดต่อ</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($contacts)): ?>
                                <p class="text-muted small mb-0">ยังไม่มีข้อมูลผู้ปกครอง</p>
                            <?php else: ?>
                                <?php foreach ($contacts as $cnt): ?>
                                    <div class="p-3 bg-light rounded-3 mb-2">
                                        <div class="fw-bold text-dark"><?= e($cnt['name']) ?> (<?= e($cnt['relationship']) ?>)</div>
                                        <div class="small text-muted">
                                            <span><i class="bi bi-telephone me-1"></i><?= e(formatPhone($cnt['phone'])) ?></span>
                                            <?php if (!empty($cnt['occupation'])): ?>
                                                <span class="ms-3"><i class="bi bi-briefcase me-1"></i><?= e($cnt['occupation']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Address & Map -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt-fill text-danger me-2"></i>ที่อยู่และพิกัดบ้าน</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($student['source_current_address']) || !empty($student['source_registered_address'])): ?>
                            <p class="mb-3 text-secondary"><i class="bi bi-house-door me-1"></i><?= e($student['source_current_address'] ?? $student['source_registered_address']) ?></p>
                            <?php if (!empty($student['source_registered_address']) && ($student['source_current_address'] ?? '') !== $student['source_registered_address']): ?>
                            <p class="small text-muted mb-3">ที่อยู่ตามทะเบียนบ้าน: <?= e($student['source_registered_address']) ?></p>
                            <?php endif; ?>
                            <?php else: ?>
                            <p class="mb-3 text-secondary">
                                <i class="bi bi-house-door me-1"></i>
                                บ้านเลขที่ <?= e($address['house_no'] ?? '-') ?> 
                                <?= !empty($address['moo']) ? 'หมู่ ' . e($address['moo']) : '' ?>
                                ต.<?= e($address['subdistrict'] ?? '-') ?> อ.<?= e($address['district'] ?? 'เสนา') ?> จ.<?= e($address['province'] ?? 'พระนครศรีอยุธยา') ?> <?= e($address['zipcode'] ?? '13110') ?>
                            </p>
                            <?php endif; ?>

                            <?php if ($navigationUrl !== null): ?>
                                <div class="map-container rounded-4 mb-3" id="studentMap" style="height: 320px;"></div>
                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span>พิกัด: <strong><?= e($address['latitude']) ?>, <?= e($address['longitude']) ?></strong></span>
                                    <a href="<?= e(googleMapsUrl($address['latitude'], $address['longitude'], false)) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> เปิด Google Maps
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning border-0 rounded-3 mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ยังไม่ได้ระบุพิกัด GPS บ้านผู้เรียนรายนี้
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Home Visits -->
        <div class="tab-pane fade" id="tab-visits">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if (empty($visits)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-house-heart fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            ยังไม่มีประวัติการเยี่ยมบ้านสำหรับผู้เรียนรายนี้
                            <?php if (canCreateVisitForCenter((int)($student['learning_center_id'] ?? 0))): ?>
                            <div class="mt-3">
                                <a href="<?= $baseUrl ?>/visits/create/<?= $student['id'] ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> บันทึกการเยี่ยมบ้านครั้งแรก
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($visits as $v): ?>
                                <div class="col-12 col-md-6">
                                    <div class="card border-0 bg-light rounded-4 h-100 p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-check me-1 text-primary"></i> <?= e(thaiDate($v['visit_date'])) ?></h6>
                                                <small class="text-muted">ผู้เยี่ยม: <?= e($v['teacher_firstname'] . ' ' . $v['teacher_lastname']) ?></small>
                                            </div>
                                            <?= riskBadge($v['risk_level']) ?>
                                        </div>
                                        <p class="small text-secondary mb-3"><?= e(truncate($v['summary'] ?: 'ไม่มีบันทึกสรุป', 120)) ?></p>
                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <span class="badge bg-white text-dark border"><?= e($v['visit_type'] === 'home_visit' ? 'เยี่ยมที่บ้าน' : $v['visit_type']) ?></span>
                                            <div class="d-flex gap-1">
                                                <?php if (hasRole('super_admin', 'admin', 'teacher')): ?>
                                                <a href="<?= $baseUrl ?>/visits/<?= $v['id'] ?>/edit" class="btn btn-sm btn-outline-warning text-dark" title="แก้ไขบันทึก">
                                                    <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                </a>
                                                <?php endif; ?>
                                                <a href="<?= $baseUrl ?>/visits/<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    ดูบันทึกฉบับเต็ม <i class="bi bi-chevron-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Survey history -->
        <div class="tab-pane fade" id="tab-surveys">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clipboard2-data me-2 text-primary"></i>ประวัติการสำรวจรายบุคคล</h6>
                        <?php if (canCreateSurveyForCenter((int)($student['learning_center_id'] ?? 0))): ?>
                        <a class="btn btn-sm btn-primary" href="<?= e(url('surveys/' . $student['id'] . '?term_id=' . $termId)) ?>"><i class="bi bi-plus-lg me-1"></i>สำรวจครั้งใหม่</a>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($surveys)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2-data fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            ยังไม่มีประวัติการสำรวจสำหรับผู้เรียนรายนี้
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>ครั้งที่</th><th>วันที่สำรวจ</th><th>ผู้บันทึก</th><th class="d-none d-md-table-cell">หมายเหตุ</th><th>สถานะ</th><th class="text-end">ดู</th></tr></thead>
                                <tbody>
                                <?php foreach ($surveys as $sv): ?>
                                    <tr class="<?= !empty($sv['cancelled_at']) ? 'text-muted' : '' ?>">
                                        <td>#<?= (int)$sv['revision'] ?></td>
                                        <td class="small"><?= e(thaiDateTime($sv['surveyed_at'])) ?></td>
                                        <td class="small"><?= e(($sv['surveyor_firstname'] ?? '') . ' ' . ($sv['surveyor_lastname'] ?? '')) ?></td>
                                        <td class="d-none d-md-table-cell small"><?= e(truncate((string)($sv['note'] ?? ''), 40)) ?: '-' ?></td>
                                        <td><?= !empty($sv['cancelled_at']) ? '<span class="badge bg-secondary">ยกเลิกแล้ว</span>' : '<span class="badge bg-success">ใช้งาน</span>' ?></td>
                                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary btn-icon" href="<?= e(url('surveys/view/' . $sv['id'])) ?>" title="ดูแบบสำรวจ"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab 3: Follow-ups -->
        <div class="tab-pane fade" id="tab-followups">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if (empty($followups)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            ไม่มีรายการติดตามช่วยเหลือค้างอยู่
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>หัวข้อการติดตาม</th>
                                        <th>ความสำคัญ</th>
                                        <th>ครบกำหนด</th>
                                        <th>ผู้รับผิดชอบ</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($followups as $f): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($f['title']) ?></div>
                                                <small class="text-muted"><?= e($f['description']) ?></small>
                                            </td>
                                            <td><?= priorityBadge($f['priority']) ?></td>
                                            <td><small><?= e(thaiDate($f['due_date'], 'short')) ?></small></td>
                                            <td><small><?= e($f['assigned_firstname'] . ' ' . $f['assigned_lastname']) ?></small></td>
                                            <td><?= statusBadge($f['status'], 'followup') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab 4: History -->
        <div class="tab-pane fade" id="tab-history">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ปีการศึกษา/ภาคเรียน</th>
                                    <th>ระดับการศึกษา</th>
                                    <th>ศกร.ตำบล</th>
                                    <th>ครูผู้รับผิดชอบ</th>
                                    <th class="text-center">จำนวนครั้งที่เยี่ยม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollmentHistory as $eh): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($eh['term_name']) ?></td>
                                        <td><?= e(educationLevelName($eh['education_level'])) ?></td>
                                        <td><?= e($eh['center_name'] ?: '-') ?></td>
                                        <td><?= e($eh['teacher_firstname'] . ' ' . $eh['teacher_lastname']) ?></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?= $eh['visit_count'] ?> ครั้ง</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php if ($navigationUrl !== null): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = <?= (float)$address['latitude'] ?>;
    const lng = <?= (float)$address['longitude'] ?>;
    const map = L.map('studentMap', { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lng], { icon: createMapMarkerIcon(), title: 'ตำแหน่งบ้านผู้เรียน' }).addTo(map)
        .bindPopup('<strong>บ้าน <?= e($student['firstname'] . ' ' . $student['lastname']) ?></strong><br><?= e($address['house_no']) ?> ต.<?= e($address['subdistrict']) ?>')
        .openPopup();
});
</script>
<?php endif; ?>
