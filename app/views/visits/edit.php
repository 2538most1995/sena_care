<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
$navigationUrl = googleMapsUrl($student['latitude'] ?? null, $student['longitude'] ?? null);
$homeLat = (float)($student['latitude'] ?? $visit['home_lat'] ?? 14.328400);
$homeLng = (float)($student['longitude'] ?? $visit['home_lng'] ?? 100.404200);
$checkinLat = !empty($visit['checkin_latitude']) ? (float)$visit['checkin_latitude'] : null;
$checkinLng = !empty($visit['checkin_longitude']) ? (float)$visit['checkin_longitude'] : null;
$distMeters = !empty($visit['distance_from_home']) ? (int)$visit['distance_from_home'] : null;
?>
<div class="container-fluid px-3 px-md-4 py-3 visit-record-page">

    <div class="mb-3">
        <a href="<?= $baseUrl ?>/visits/<?= $visit['id'] ?>" data-back-link class="text-decoration-none text-secondary small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
        </a>
    </div>

    <!-- Student Info Header -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white text-primary mb-2">แก้ไขผลการเยี่ยมบ้าน #<?= $visit['id'] ?></span>
                <h3 class="fw-bold mb-1"><?= e($student['prefix'] . $student['firstname'] . ' ' . $student['lastname']) ?></h3>
                <div class="opacity-75 small">
                    <span class="me-3"><i class="bi bi-card-heading me-1"></i> รหัสนักศึกษา: <?= e($student['student_code']) ?></span>
                    <span class="me-3"><i class="bi bi-mortarboard me-1"></i> <?= e(educationLevelName($student['education_level'] ?? '')) ?></span>
                    <span><i class="bi bi-geo-alt me-1"></i> บ้านเลขที่ <?= e($student['house_no'] ?? '-') ?> ต.<?= e($student['subdistrict'] ?? '-') ?></span>
                </div>
            </div>
            <div>
                <?php if ($navigationUrl !== null): ?>
                <a href="<?= e($navigationUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-light text-primary fw-medium">
                    <i class="bi bi-navigation-fill me-1"></i> นำทาง GPS
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= $baseUrl ?>/visits/<?= $visit['id'] ?>/edit" enctype="multipart/form-data" id="visitEditForm">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- Left Column: Check-in, Meta, Photos -->
            <div class="col-12 col-lg-5">

                <!-- 1. GPS Check-in Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>1. เช็คอินพิกัด GPS ณ สถานที่จริง</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="small text-muted mb-3">กดปุ่มเพื่อดึงพิกัดปัจจุบันจากอุปกรณ์ใหม่ หากต้องการอัปเดตตำแหน่งที่เช็คอิน</p>

                        <button type="button" class="btn btn-outline-danger w-100 py-2 mb-3 fw-bold" id="btnCheckin">
                            <i class="bi bi-pin-map-fill me-2 fs-5"></i> กดเช็คอินตำแหน่งปัจจุบันใหม่
                        </button>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-muted">Latitude ที่เช็คอิน</label>
                                <input type="number" step="any" name="checkin_latitude" id="checkinLat" class="form-control form-control-sm" readonly value="<?= $checkinLat !== null ? number_format($checkinLat, 6, '.', '') : '' ?>" placeholder="รอเช็คอิน...">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Longitude ที่เช็คอิน</label>
                                <input type="number" step="any" name="checkin_longitude" id="checkinLng" class="form-control form-control-sm" readonly value="<?= $checkinLng !== null ? number_format($checkinLng, 6, '.', '') : '' ?>" placeholder="รอเช็คอิน...">
                            </div>
                        </div>

                        <!-- Distance Result Alert -->
                        <div id="distanceAlert" class="alert <?= $distMeters !== null ? ($distMeters <= 200 ? 'alert-success fw-bold' : 'alert-warning fw-bold') : 'alert-light border text-muted' ?> rounded-3 p-2 small mb-3 text-center">
                            <?php if ($distMeters !== null): ?>
                                <i class="bi bi-check-circle-fill me-1"></i> เช็คอินแล้ว: ระยะห่าง <?= number_format($distMeters) ?> เมตร จากพิกัดบ้าน
                            <?php else: ?>
                                <i class="bi bi-compass me-1"></i> ยังไม่ได้กดเช็คอิน
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="distance_from_home" id="inputDistance" value="<?= $distMeters ?? 0 ?>">

                        <!-- Mini Map Preview -->
                        <div class="map-container rounded-3 border" id="checkinMap" style="height: 220px;"></div>
                    </div>
                </div>

                <!-- 2. Visit Info -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-check text-primary me-2"></i>2. ข้อมูลการลงพื้นที่</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">วันที่เยี่ยม <span class="text-danger">*</span></label>
                            <input type="date" name="visit_date" class="form-control" value="<?= e($visit['visit_date']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ประเภทการติดตาม</label>
                            <select name="visit_type" class="form-select">
                                <option value="home_visit" <?= $visit['visit_type'] === 'home_visit' ? 'selected' : '' ?>>ลงพื้นที่เยี่ยมบ้านจริง</option>
                                <option value="phone" <?= $visit['visit_type'] === 'phone' ? 'selected' : '' ?>>ติดต่อทางโทรศัพท์</option>
                                <option value="line" <?= $visit['visit_type'] === 'line' ? 'selected' : '' ?>>ติดต่อผ่าน LINE</option>
                                <option value="at_center" <?= $visit['visit_type'] === 'at_center' ? 'selected' : '' ?>>พบที่ ศกร.ตำบล</option>
                                <option value="parent_meeting" <?= $visit['visit_type'] === 'parent_meeting' ? 'selected' : '' ?>>ประชุมผู้ปกครอง</option>
                                <option value="online" <?= $visit['visit_type'] === 'online' ? 'selected' : '' ?>>พบกลุ่มออนไลน์</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ระดับความเสี่ยงที่ประเมิน <span class="text-danger">*</span></label>
                            <select name="risk_level" class="form-select fw-bold" required>
                                <option value="low" class="text-success" <?= $visit['risk_level'] === 'low' ? 'selected' : '' ?>>เสี่ยงต่ำ (Low) - ปกติ เรียบร้อยดี</option>
                                <option value="medium" class="text-warning" <?= $visit['risk_level'] === 'medium' ? 'selected' : '' ?>>เสี่ยงปานกลาง (Medium) - มีอุปสรรคเล็กน้อย</option>
                                <option value="high" class="text-orange" <?= $visit['risk_level'] === 'high' ? 'selected' : '' ?>>เสี่ยงสูง (High) - ต้องการการช่วยเหลือ</option>
                                <option value="critical" class="text-danger" <?= $visit['risk_level'] === 'critical' ? 'selected' : '' ?>>เสี่ยงสูงมาก (Critical) - เสี่ยงหลุดจากระบบเร่งด่วน</option>
                            </select>
                        </div>

                        <!-- Existing Photos -->
                        <?php if (!empty($visit['photos'])): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">ภาพถ่ายเดิมที่บันทึกไว้</label>
                            <div class="row g-2">
                                <?php foreach ($visit['photos'] as $p): ?>
                                    <div class="col-6">
                                        <div class="border rounded-3 p-2 bg-light position-relative">
                                            <a href="<?= $baseUrl ?>/public/uploads/visits/<?= e($p['stored_name']) ?>"
                                               data-lightbox
                                               data-alt="ภาพถ่ายเดิมที่บันทึกไว้"
                                               title="คลิกเพื่อดูรูปขนาดเต็ม">
                                                <img src="<?= $baseUrl ?>/public/uploads/visits/<?= e($p['stored_name']) ?>" class="img-fluid rounded border mb-2" alt="ภาพถ่ายเดิมที่บันทึกไว้" style="height: 100px; width: 100%; object-fit: cover;">
                                            </a>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="delete_photos[]" value="<?= $p['id'] ?>" id="del_photo_<?= $p['id'] ?>">
                                                <label class="form-check-label small text-danger fw-semibold" for="del_photo_<?= $p['id'] ?>">
                                                    <i class="bi bi-trash me-1"></i> ลบรูปนี้
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- New Photos Upload (2 Photos) -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">อัปโหลดภาพถ่ายใหม่ (2 ภาพ)</label>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted">ภาพที่ 1: สภาพบ้าน/ที่อยู่อาศัย</label>
                                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm photo-input" data-preview="previewEdit1">
                                    <div id="previewEdit1" class="mt-2"><img src="" alt="Preview 1" class="img-thumbnail d-none" style="max-height:120px;"></div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">ภาพที่ 2: ถ่ายร่วมกับผู้เรียน/ผู้ปกครอง</label>
                                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm photo-input" data-preview="previewEdit2">
                                    <div id="previewEdit2" class="mt-2"><img src="" alt="Preview 2" class="img-thumbnail d-none" style="max-height:120px;"></div>
                                </div>
                            </div>
                            <div class="form-text small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>แนบรูปภาพใหม่เฉพาะภาพที่ต้องการเพิ่มหรือแทนที่ (JPG, PNG, WEBP)</div>
                        </div>
                    </div>
                </div>

                <!-- 3. Followup Option -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="followup_required" id="checkFollowup" value="1" <?= !empty($visit['followup_required']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-dark" for="checkFollowup">
                                ต้องการส่งต่อ / ติดตามช่วยเหลือเพิ่มเติม
                            </label>
                        </div>

                        <div id="followupBox" style="display: <?= !empty($visit['followup_required']) ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">หัวข้อที่ต้องติดตาม</label>
                                <input type="text" name="followup_title" class="form-control" value="<?= e($followup['title'] ?? '') ?>" placeholder="เช่น ประสานขอรับทุนการศึกษา, ตรวจสอบการส่งงาน...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">วันที่ครบกำหนดติดตาม</label>
                                <input type="date" name="next_followup_date" class="form-control" value="<?= e($visit['next_followup_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Assessment Form Questions -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-ui-checks text-success me-2"></i>3. แบบประเมินการเยี่ยมบ้านผู้เรียน</h5>
                        <p class="text-muted small mb-0">กรอกหรือแก้ไขแบบประเมินมาตรฐาน สกร. ตามสภาพความเป็นจริง</p>
                    </div>

                    <div class="card-body p-4">
                        <?php foreach ($sections as $sectionName => $qList): ?>
                            <div class="mb-4 p-3 bg-light rounded-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tag-fill me-2"></i>หมวด: <?= e($sectionName) ?></h6>

                                <?php foreach ($qList as $q): ?>
                                    <?php $existingVal = $answersMap[$q['id']] ?? null; ?>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark">
                                            <?= e($q['question_text']) ?>
                                            <?php if ($q['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                        </label>

                                        <?php if ($q['question_type'] === 'radio'): ?>
                                            <?php $opts = json_decode($q['options_json'] ?? '[]', true) ?: []; ?>
                                            <div class="d-flex flex-wrap gap-3">
                                                <?php foreach ($opts as $optIdx => $opt): ?>
                                                    <?php
                                                        $isChecked = ($existingVal !== null) ? ($existingVal === $opt) : ($optIdx === 0);
                                                    ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q_<?= $q['id'] ?>_<?= $optIdx ?>" value="<?= e($opt) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                        <label class="form-check-label small" for="q_<?= $q['id'] ?>_<?= $optIdx ?>"><?= e($opt) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        <?php elseif ($q['question_type'] === 'yes_no'): ?>
                                            <?php
                                                $isYes = ($existingVal !== null) ? ($existingVal === 'ใช่') : true;
                                            ?>
                                            <div class="d-flex gap-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q_<?= $q['id'] ?>_yes" value="ใช่" <?= $isYes ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="q_<?= $q['id'] ?>_yes">ใช่ / มี</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q_<?= $q['id'] ?>_no" value="ไม่ใช่" <?= !$isYes ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="q_<?= $q['id'] ?>_no">ไม่ใช่ / ไม่มี</label>
                                                </div>
                                            </div>

                                        <?php elseif ($q['question_type'] === 'number'): ?>
                                            <input type="number" name="answers[<?= $q['id'] ?>]" class="form-control form-control-sm" style="max-width: 150px;" value="<?= e($existingVal ?? '4') ?>">

                                        <?php elseif ($q['question_type'] === 'textarea'): ?>
                                            <textarea name="answers[<?= $q['id'] ?>]" class="form-control form-control-sm" rows="2" placeholder="ระบุรายละเอียด (ถ้ามี)"><?= e($existingVal ?? '') ?></textarea>

                                        <?php elseif ($q['question_type'] === 'checkbox'): ?>
                                            <?php
                                                $opts = json_decode($q['options_json'] ?? '[]', true) ?: [];
                                                $existingArr = is_string($existingVal) ? (json_decode($existingVal, true) ?: [$existingVal]) : [];
                                            ?>
                                            <div class="row g-2">
                                                <?php foreach ($opts as $optIdx => $opt): ?>
                                                    <?php $isChecked = in_array($opt, $existingArr, true); ?>
                                                    <div class="col-6 col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="answers[<?= $q['id'] ?>][]" id="q_<?= $q['id'] ?>_c<?= $optIdx ?>" value="<?= e($opt) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="q_<?= $q['id'] ?>_c<?= $optIdx ?>"><?= e($opt) ?></label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        <?php else: ?>
                                            <input type="text" name="answers[<?= $q['id'] ?>]" class="form-control form-control-sm" value="<?= e($existingVal ?? '') ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Summary & Recommendation -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">สรุปผลการเยี่ยมบ้านโดยรวม</label>
                            <textarea name="summary" class="form-control" rows="3" placeholder="สรุปบรรยากาศการเยี่ยมบ้าน สภาพความเป็นอยู่ ปัญหา และข้อเท็จจริงที่พบ..."><?= e($visit['summary'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">ข้อเสนอแนะและแนวทางช่วยเหลือของครู</label>
                            <textarea name="teacher_recommendation" class="form-control" rows="3" placeholder="แนวทางการจัดการเรียนรู้ การส่งเสริม หรือการประสานหน่วยงานช่วยเหลือ..."><?= e($visit['teacher_recommendation'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= $baseUrl ?>/visits/<?= $visit['id'] ?>" class="btn btn-outline-secondary px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-success px-5 fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> บันทึกการแก้ไข
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const homeLat = <?= $homeLat ?>;
    const homeLng = <?= $homeLng ?>;
    const initCheckinLat = <?= $checkinLat !== null ? $checkinLat : 'null' ?>;
    const initCheckinLng = <?= $checkinLng !== null ? $checkinLng : 'null' ?>;

    const map = L.map('checkinMap', { scrollWheelZoom: false }).setView([homeLat, homeLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const homeIcon = L.divIcon({
        className: 'home-pin',
        html: `<div style="background:var(--primary-color);width:24px;height:24px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="bi bi-house-door-fill" style="color:var(--on-primary);font-size:12px;"></i></div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });

    L.marker([homeLat, homeLng], { icon: homeIcon }).bindPopup('<strong>บ้านผู้เรียน</strong>').addTo(map);

    let checkinMarker = null;

    if (initCheckinLat && initCheckinLng) {
        checkinMarker = L.circleMarker([initCheckinLat, initCheckinLng], {
            radius: 8,
            fillColor: getComputedStyle(document.documentElement).getPropertyValue('--risk-critical').trim(),
            color: '#ffffff',
            weight: 3,
            fillOpacity: 1
        }).bindPopup('<strong>ตำแหน่งเช็คอินปัจจุบัน</strong>').addTo(map);
        map.fitBounds([[homeLat, homeLng], [initCheckinLat, initCheckinLng]], { padding: [30, 30], maxZoom: 15 });
    }

    // Checkin button handler
    document.getElementById('btnCheckin').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังดึงพิกัด GPS...';

        try {
            const pos = await getCurrentPosition();
            const lat = pos.latitude;
            const lng = pos.longitude;

            document.getElementById('checkinLat').value = lat.toFixed(6);
            document.getElementById('checkinLng').value = lng.toFixed(6);

            // Calculate distance
            const distMeters = calculateDistance(homeLat, homeLng, lat, lng);
            document.getElementById('inputDistance').value = distMeters;

            const alertEl = document.getElementById('distanceAlert');
            if (distMeters <= 200) {
                alertEl.className = 'alert alert-success border rounded-3 p-2 small mb-3 text-center fw-bold';
                alertEl.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> เช็คอินใหม่สำเร็จ: ระยะห่าง ${distMeters} เมตร (อยู่ในพื้นที่บ้าน)`;
            } else {
                alertEl.className = 'alert alert-warning border rounded-3 p-2 small mb-3 text-center fw-bold';
                alertEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> เช็คอินใหม่สำเร็จ: ระยะห่าง ${distMeters.toLocaleString()} เมตร จากพิกัดบ้าน`;
            }

            // Plot checkin marker on map
            if (checkinMarker) {
                checkinMarker.setLatLng([lat, lng]);
            } else {
                checkinMarker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: getComputedStyle(document.documentElement).getPropertyValue('--risk-critical').trim(),
                    color: '#ffffff',
                    weight: 3,
                    fillOpacity: 1
                }).bindPopup('<strong>ตำแหน่งเช็คอินของคุณ</strong>').addTo(map);
            }

            map.fitBounds([[homeLat, homeLng], [lat, lng]], { padding: [30, 30], maxZoom: 15 });
            showToast('success', 'เช็คอินพิกัดใหม่เรียบร้อย');
        } catch (err) {
            showToast('danger', err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-pin-map-fill me-2 fs-5"></i> กดเช็คอินตำแหน่งปัจจุบันใหม่';
        }
    });

    // Followup toggle
    document.getElementById('checkFollowup')?.addEventListener('change', function() {
        document.getElementById('followupBox').style.display = this.checked ? 'block' : 'none';
    });

    // Photo preview
    document.querySelectorAll('.photo-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const previewDiv = document.getElementById(previewId);
            const img = previewDiv.querySelector('img');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                img.src = '';
                img.classList.add('d-none');
            }
        });
    });
});
</script>

<?php $draft = $_SESSION['visit_edit_draft'][$visit['id']] ?? null; ?>
<?php if ($draft): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const draft = <?= json_encode($draft,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const form = document.getElementById('visitEditForm');
    for (const field of form.elements) {
        if (!field.name || field.type === 'file' || field.name === '_csrf_token') continue;
        const match = field.name.match(/^answers\[(\d+)\](\[\])?$/);
        const value = match ? (draft.answers || {})[match[1]] : draft[field.name];
        if (value === undefined) continue;
        if (field.type === 'radio' || field.type === 'checkbox') field.checked = Array.isArray(value) ? value.includes(field.value) : String(value) === field.value;
        else field.value = value;
    }
    document.getElementById('followupBox').style.display = document.getElementById('checkFollowup').checked ? 'block' : 'none';
});
</script>
<?php endif; ?>
