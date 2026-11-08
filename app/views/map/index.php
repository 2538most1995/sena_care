<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3 map-page">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-geo-alt-fill text-danger me-2"></i>แผนที่บ้านผู้เรียน</h4>
            <p class="text-muted small mb-0">แสดงตำแหน่งบ้านผู้เรียนทั้งหมดบนแผนที่ Leaflet พร้อมระบบกรองและนำทาง</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btnToggleMapHeight" title="ปรับขนาดแผนที่ (ย่อ/ขยาย)">
                <i class="bi bi-arrows-angle-expand me-1" id="btnToggleMapIcon"></i> <span id="btnToggleMapText">ขยาย</span>
            </button>
            <button type="button" class="btn btn-outline-primary" id="btnLocateMe">
                <i class="bi bi-crosshair me-1"></i> ตำแหน่งปัจจุบันของฉัน
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterTerm" class="form-select form-select-sm">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == currentTermId() ? 'selected' : '' ?>><?= e($t['term_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterLevel" class="form-select form-select-sm">
                        <option value="">ทุกระดับการศึกษา</option>
                        <option value="primary">ประถมศึกษา</option>
                        <option value="lower_secondary">มัธยมศึกษาตอนต้น</option>
                        <option value="upper_secondary">มัธยมศึกษาตอนปลาย</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterCenter" class="form-select form-select-sm">
                        <option value="">ทุก ศกร.ตำบล</option>
                        <?php foreach ($centers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['center_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterVisitStatus" class="form-select form-select-sm">
                        <option value="">สถานะการเยี่ยมทั้งหมด</option>
                        <option value="visited">เยี่ยมแล้ว</option>
                        <option value="not_visited">ยังไม่เยี่ยม</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterRisk" class="form-select form-select-sm">
                        <option value="">ทุกระดับความเสี่ยง</option>
                        <option value="low">เสี่ยงต่ำ</option>
                        <option value="medium">เสี่ยงปานกลาง</option>
                        <option value="high">เสี่ยงสูง</option>
                        <option value="critical">เสี่ยงสูงมาก</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <button type="button" class="btn btn-sm btn-primary w-100" id="btnApplyFilter">
                        <i class="bi bi-funnel-fill me-1"></i> ใช้ตัวกรอง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Map & Legend Container -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative map-card">
        <div id="allStudentsMap" class="map-container"></div>

        <!-- Floating Legend Badge (Collapsible) -->
        <div class="position-absolute bottom-0 start-0 m-2 m-md-3 p-2 p-md-3 bg-white bg-opacity-95 rounded-3 shadow-sm border" style="z-index: 1000; max-width: 250px;" id="mapLegendBox">
            <div class="d-flex justify-content-between align-items-center cursor-pointer" id="btnToggleLegend" role="button" title="คลิกเพื่อย่อ/ขยายคำอธิบาย">
                <span class="fw-bold small text-dark"><i class="bi bi-info-circle me-1 text-primary"></i> สัญลักษณ์ความเสี่ยง</span>
                <i class="bi bi-chevron-down small text-muted ms-2" id="legendChevron"></i>
            </div>
            <div id="legendContent">
                <div class="d-flex flex-column gap-1 small mt-2">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--risk-critical); display: inline-block;"></span>
                        <span style="font-size: 0.8rem;">เสี่ยงสูงมาก (Critical)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--risk-high); display: inline-block;"></span>
                        <span style="font-size: 0.8rem;">เสี่ยงสูง (High)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--risk-medium); display: inline-block;"></span>
                        <span style="font-size: 0.8rem;">เสี่ยงปานกลาง (Medium)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--risk-low); display: inline-block;"></span>
                        <span style="font-size: 0.8rem;">เสี่ยงต่ำ (Low)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--secondary-color); display: inline-block;"></span>
                        <span style="font-size: 0.8rem;">ยังไม่เยี่ยม</span>
                    </div>
                </div>
                <div class="mt-2 pt-1 border-top text-muted" style="font-size: 0.75rem;" id="markerCountText">
                    กำลังโหลดข้อมูล...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('allStudentsMap', {
        scrollWheelZoom: false // ปิดการดัก scroll เมาส์ เพื่อให้ผู้ใช้เลื่อนหน้าจอได้อย่างราบรื่น
    }).setView([14.3350, 100.4100], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    let markersLayer = L.layerGroup().addTo(map);
    let userLocationMarker = null;

    const mapTheme = getComputedStyle(document.documentElement);
    const mapColor = token => mapTheme.getPropertyValue(token).trim();
    const riskBadges = {
        low: { className: 'risk-low', label: 'เสี่ยงต่ำ' },
        medium: { className: 'risk-medium', label: 'เสี่ยงปานกลาง' },
        high: { className: 'risk-high', label: 'เสี่ยงสูง' },
        critical: { className: 'risk-critical', label: 'เสี่ยงสูงมาก' }
    };

    function getMarkerColor(student) {
        if (student.visit_count == 0) {
            return mapColor('--secondary-color'); // Gray for not visited
        }
        switch (student.risk_level) {
            case 'critical': return mapColor('--risk-critical');
            case 'high':     return mapColor('--risk-high');
            case 'medium':   return mapColor('--risk-medium');
            case 'low':
            default:         return mapColor('--risk-low');
        }
    }

    async function loadMarkers() {
        markersLayer.clearLayers();
        document.getElementById('markerCountText').innerText = 'กำลังโหลดข้อมูล...';

        const termId = document.getElementById('filterTerm').value;
        const level = document.getElementById('filterLevel').value;
        const centerId = document.getElementById('filterCenter').value;
        const visitStatus = document.getElementById('filterVisitStatus').value;
        const riskLevel = document.getElementById('filterRisk').value;

        const params = new URLSearchParams({
            term_id: termId,
            level: level,
            center_id: centerId,
            visit_status: visitStatus,
            risk_level: riskLevel
        });

        try {
            const res = await apiFetch('<?= $baseUrl ?>/api/map/students?' + params.toString());
            if (res.success && res.data) {
                const students = res.data;
                document.getElementById('markerCountText').innerText = `พบ ${students.length} หลังคาเรือน`;

                const latLngs = [];

                students.forEach(s => {
                    const lat = parseFloat(s.latitude);
                    const lng = parseFloat(s.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return;

                    latLngs.push([lat, lng]);
                    const color = getMarkerColor(s);
                    const markerInk = s.visit_count == 0 || !['medium', 'high'].includes(s.risk_level) ? mapColor('--on-primary') : mapColor('--palette-ink');
                    const riskBadge = riskBadges[s.risk_level] || { className: 'risk-unknown', label: 'ไม่ระบุ' };

                    const icon = L.divIcon({
                        className: 'student-pin',
                        html: `<div style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2px solid white; box-shadow: 0 3px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center;"><i class="bi bi-house-door-fill" style="color:${markerInk}; transform: rotate(45deg); font-size: 14px;"></i></div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 30],
                        popupAnchor: [0, -30]
                    });

                    const popupContent = `
                        <div style="min-width: 220px;">
                            <div class="fw-bold fs-6 mb-1 text-dark">${s.prefix || ''}${s.firstname} ${s.lastname}</div>
                            <div class="small text-muted mb-2"><i class="bi bi-card-heading me-1"></i> ${s.student_code}</div>
                            <div class="small mb-1"><i class="bi bi-geo-alt me-1 text-danger"></i> บ้านเลขที่ ${s.house_no || '-'} ต.${s.subdistrict || '-'}</div>
                            <div class="small mb-2"><i class="bi bi-telephone me-1 text-primary"></i> ${s.phone || '-'}</div>
                            <div class="d-flex gap-1 mb-2">
                                <span class="badge ${s.visit_count > 0 ? 'bg-success' : 'bg-secondary'}">${s.visit_count > 0 ? 'เยี่ยมแล้ว' : 'ยังไม่เยี่ยม'}</span>
                                <span class="badge rounded-pill ${riskBadge.className}">${riskBadge.label}</span>
                            </div>
                            <div class="d-flex flex-column gap-1 mt-2 pt-2 border-top">
                                <a href="<?= $baseUrl ?>/students/${s.id}" class="btn btn-xs btn-outline-primary w-100" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <i class="bi bi-person me-1"></i> ดูข้อมูลผู้เรียน
                                </a>
                                <a href="<?= $baseUrl ?>/visits/create/${s.id}" class="btn btn-xs btn-success w-100" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <i class="bi bi-house-heart me-1"></i> บันทึกเยี่ยมบ้าน
                                </a>
                                ${s.navigation_url ? `<a href="${s.navigation_url}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-secondary w-100" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <i class="bi bi-navigation me-1"></i> นำทาง Google Maps
                                </a>` : ''}
                            </div>
                        </div>
                    `;

                    L.marker([lat, lng], { icon: icon })
                        .bindPopup(popupContent)
                        .addTo(markersLayer);
                });

                if (latLngs.length > 0) {
                    // Keep a useful overview even for one home or identical coordinates.
                    map.fitBounds(latLngs, { padding: [40, 40], maxZoom: 15 });
                }
            }
        } catch (err) {
            console.error('Error loading map students:', err);
        }
    }

    // Apply Filter Button
    document.getElementById('btnApplyFilter').addEventListener('click', loadMarkers);

    // Locate Me Button
    document.getElementById('btnLocateMe').addEventListener('click', async function() {
        try {
            const pos = await getCurrentPosition();
            map.setView([pos.latitude, pos.longitude], 16);

            if (userLocationMarker) {
                userLocationMarker.setLatLng([pos.latitude, pos.longitude]);
            } else {
                userLocationMarker = L.circleMarker([pos.latitude, pos.longitude], {
                    radius: 9,
                    fillColor: mapColor('--primary-color'),
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 0.9
                }).bindPopup('<strong>ตำแหน่งปัจจุบันของคุณ</strong>').addTo(map);
            }
            showToast('info', 'ระบุตำแหน่งปัจจุบันของคุณเรียบร้อย');
        } catch (err) {
            showToast('danger', err.message);
        }
    });

    // Toggle Map Height (Expand / Compact)
    const mapEl = document.getElementById('allStudentsMap');
    const btnToggleHeight = document.getElementById('btnToggleMapHeight');
    const toggleIcon = document.getElementById('btnToggleMapIcon');
    const toggleText = document.getElementById('btnToggleMapText');
    let isMapExpanded = false;

    if (btnToggleHeight && mapEl) {
        btnToggleHeight.addEventListener('click', function() {
            isMapExpanded = !isMapExpanded;
            if (isMapExpanded) {
                mapEl.classList.add('is-expanded');
                toggleIcon.className = 'bi bi-arrows-angle-contract me-1';
                toggleText.innerText = 'ย่อ';
            } else {
                mapEl.classList.remove('is-expanded');
                toggleIcon.className = 'bi bi-arrows-angle-expand me-1';
                toggleText.innerText = 'ขยาย';
            }
            setTimeout(() => { map.invalidateSize(); }, 320);
        });
    }

    // Toggle Legend Box
    const btnToggleLegend = document.getElementById('btnToggleLegend');
    const legendContent = document.getElementById('legendContent');
    const legendChevron = document.getElementById('legendChevron');
    if (btnToggleLegend && legendContent) {
        btnToggleLegend.addEventListener('click', function() {
            if (legendContent.classList.contains('d-none')) {
                legendContent.classList.remove('d-none');
                legendChevron.className = 'bi bi-chevron-down small text-muted ms-2';
            } else {
                legendContent.classList.add('d-none');
                legendChevron.className = 'bi bi-chevron-up small text-muted ms-2';
            }
        });
    }

    // Initial load
    loadMarkers();
});
</script>
