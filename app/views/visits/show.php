<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$checkinMapUrl = googleMapsUrl($visit['checkin_latitude'] ?? null, $visit['checkin_longitude'] ?? null, false);
$homeLat = (float)($visit['home_lat'] ?? 14.328400);
$homeLng = (float)($visit['home_lng'] ?? 100.404200);
$checkinLat = !empty($visit['checkin_latitude']) ? (float)$visit['checkin_latitude'] : null;
$checkinLng = !empty($visit['checkin_longitude']) ? (float)$visit['checkin_longitude'] : null;
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="<?= e(url('visits/history?student_id=' . (int)$visit['student_id'] . '&term_id=' . (int)$visit['academic_term_id'])) ?>" data-back-link class="text-decoration-none text-secondary small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
        </a>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(url('visits/'.$visit['id'].'/pdf')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
            </a>
            <?php if (hasRole('super_admin', 'admin', 'teacher') && ($visit['status'] ?? 'completed') === 'completed'): ?>
            <a href="<?= $baseUrl ?>/visits/<?= $visit['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> แก้ไข
            </a>
            <?php endif; ?>
            <?php if (hasRole('super_admin', 'admin', 'teacher') && canAccessStudent((int)$visit['student_id'], (int)$visit['academic_term_id']) && canCreateVisitForCenter((int)($visit['learning_center_id'] ?? 0))): ?>
            <a class="btn btn-sm btn-primary" href="<?= e(url('visits/create/' . (int)$visit['student_id'] . '?term_id=' . (int)$visit['academic_term_id'])) ?>"><i class="bi bi-plus-lg me-1"></i>บันทึกใหม่</a>
            <?php endif; ?>
        </div>
    </div>

    <p class="small text-muted">ผู้บันทึกข้อมูล: <?= e($visit['teacher_firstname'].' '.$visit['teacher_lastname']) ?></p>
    <!-- Visit Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-success-subtle text-success fw-bold rounded-circle">
                        <i class="bi bi-house-heart fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="fw-bold mb-0 text-dark">บันทึกผลการเยี่ยมบ้าน</h4>
                            <?= riskBadge($visit['risk_level']) ?>
                            <?php if (($visit['status'] ?? '') === 'cancelled'): ?>
                            <span class="badge bg-danger rounded-pill">ยกเลิกแล้ว</span>
                            <?php else: ?>
                            <span class="badge bg-success rounded-pill">เยี่ยมแล้ว</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small d-flex flex-wrap gap-3">
                            <span>ผู้เรียน: <strong><?= e($visit['prefix'] . $visit['firstname'] . ' ' . $visit['lastname']) ?></strong> (<?= e($visit['student_code']) ?>)</span>
                            <span>วันที่เยี่ยม: <strong><?= e(thaiDate($visit['visit_date'])) ?></strong></span>
                            <span>ครูผู้เยี่ยม (ครูประจำกลุ่ม): <?= e($visit['group_teacher_name'] ?: 'ยังไม่ระบุครูประจำกลุ่ม') ?></span>
                        </div>
                    </div>
                </div>

                <div class="text-md-end">
                    <div class="small text-muted mb-1">การลงเวลาเช็คอิน:</div>
                    <div class="badge bg-light text-dark border p-2">
                        <i class="bi bi-clock me-1 text-primary"></i>
                        <?= !empty($visit['checkin_time']) ? e(thaiDateTime($visit['checkin_time'])) : '-' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Summary & Photos & GPS -->
        <div class="col-12 col-lg-5">

            <!-- GPS Verification Map -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>การยืนยันพิกัด GPS</h5>
                </div>
                <div class="card-body p-4">
                    <div class="map-container rounded-3 border mb-3" id="verifyMap" style="height: 240px;"></div>
                    <div class="d-flex justify-content-between align-items-center small text-muted">
                        <span>ระยะห่างจากบ้าน: <strong><?= number_format((int)$visit['distance_from_home']) ?> เมตร</strong></span>
                        <?php if ($checkinMapUrl !== null): ?>
                            <a href="<?= e($checkinMapUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Google Maps
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notes & Recommendations -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-primary me-2"></i>บันทึกและข้อเสนอแนะ</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">สรุปผลการเยี่ยมบ้าน:</label>
                        <p class="p-3 bg-light rounded-3 small mb-0"><?= nl2br(e($visit['summary'] ?: 'ไม่มีบันทึกสรุป')) ?></p>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-secondary">ข้อเสนอแนะของครู:</label>
                        <p class="p-3 bg-light rounded-3 small mb-0 text-primary"><?= nl2br(e($visit['teacher_recommendation'] ?: 'ไม่มีข้อเสนอแนะ')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Evidence Photos -->
            <?php if (!empty($visit['photos'])): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-camera-fill text-info me-2"></i>รูปภาพหลักฐานการเยี่ยมบ้าน</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-2">
                        <?php foreach ($visit['photos'] as $p): ?>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/public/uploads/visits/<?= e($p['stored_name']) ?>"
                                   data-lightbox
                                   data-alt="รูปหลักฐานการเยี่ยมบ้าน"
                                   title="คลิกเพื่อดูรูปขนาดเต็ม">
                                    <img src="<?= $baseUrl ?>/public/uploads/visits/<?= e($p['stored_name']) ?>" class="img-fluid rounded-3 border" alt="รูปหลักฐานการเยี่ยมบ้าน" style="height: 120px; width: 100%; object-fit: cover;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right: Assessment Answers -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-success me-2"></i>ผลการตอบแบบประเมินเยี่ยมบ้าน</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($visit['answers'])): ?>
                        <p class="text-muted small">ไม่มีบันทึกคำตอบแบบประเมิน</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr class="table-light">
                                        <th style="width: 25%;">หมวด</th>
                                        <th style="width: 45%;">คำถาม</th>
                                        <th style="width: 30%;">คำตอบ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visit['answers'] as $ans): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?= e($ans['section_name']) ?></span></td>
                                            <td class="small fw-medium"><?= e($ans['question_text']) ?><?php if (!empty($ans['historical_variant'])): ?><br><span class="text-muted">คำตอบเดิมอีกชุดที่แตกต่างกัน</span><?php endif; ?></td>
                                            <td class="small fw-bold text-dark">
                                                <?php
                                                    $val = $ans['answer_value'];
                                                    $json = json_decode($val, true);
                                                    if (is_array($json)) {
                                                        echo e(implode(', ', $json));
                                                    } else {
                                                        echo e($val ?: '-');
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const homeLat = <?= $homeLat ?>;
    const homeLng = <?= $homeLng ?>;
    const checkinLat = <?= $checkinLat ? $checkinLat : 'null' ?>;
    const checkinLng = <?= $checkinLng ? $checkinLng : 'null' ?>;

    const map = L.map('verifyMap', { scrollWheelZoom: false }).setView([homeLat, homeLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Home marker
    L.marker([homeLat, homeLng], { icon: createMapMarkerIcon(), title: 'ตำแหน่งบ้านผู้เรียน' }).bindPopup('<strong>บ้านผู้เรียน</strong>').addTo(map);

    // Checkin marker
    if (checkinLat && checkinLng) {
        L.circleMarker([checkinLat, checkinLng], {
            radius: 8,
            fillColor: getComputedStyle(document.documentElement).getPropertyValue('--risk-critical').trim(),
            color: '#ffffff',
            weight: 3,
            fillOpacity: 1
        }).bindPopup('<strong>ตำแหน่งที่ครูเช็คอิน</strong>').addTo(map);

        map.fitBounds([[homeLat, homeLng], [checkinLat, checkinLng]], { padding: [30, 30], maxZoom: 15 });
    }
});
</script>
