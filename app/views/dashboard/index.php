<?php
/**
 * Dashboard View
 */
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3 dashboard-page">

    <!-- Dashboard Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 d-none d-md-block">
                <i class="bi bi-grid-1x2-fill text-primary me-2"></i>แดชบอร์ด
            </h2>
            <p class="text-muted mb-0 small">ภาพรวมระบบ ภาคเรียน <?= e(currentTermName()) ?></p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <select class="form-select form-select-sm rounded-pill" id="termFilter" style="width:auto;">
                <?php foreach ($terms as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id'] == currentTermId() ? 'selected' : '' ?>>
                    <?= e($t['term_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="dashboard-metrics mb-4">
        <div class="metric-cell">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-value" id="statTotal"><?= number_format($stats['total_students']) ?></div>
                <div class="stat-label">ผู้เรียนทั้งหมด</div>
            </div>
        </div>
        <div class="metric-cell">
            <div class="stat-card stat-card-success">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-value" id="statVisited"><?= number_format($stats['visited']) ?></div>
                <div class="stat-label">เยี่ยมแล้ว</div>
                <div class="stat-progress">
                    <div class="progress" style="height:4px;">
                        <div class="progress-bar bg-success" style="width:<?= $stats['visit_percent'] ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $stats['visit_percent'] ?>%</small>
                </div>
            </div>
        </div>
        <div class="metric-cell">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon"><i class="bi bi-clipboard2-check"></i></div>
                <div class="stat-value" id="statSurveyed"><?= number_format($stats['surveyed']) ?></div>
                <div class="stat-label">สำรวจแล้ว</div>
            </div>
        </div>
        <div class="metric-cell">
            <div class="stat-card stat-card-danger">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stat-value" id="statRisk"><?= number_format($stats['high_risk']) ?></div>
                <div class="stat-label">เสี่ยงสูง</div>
            </div>
        </div>
        <div class="metric-cell">
            <div class="stat-card stat-card-warning">
                <div class="stat-icon"><i class="bi bi-clipboard2-check"></i></div>
                <div class="stat-value" id="statFollowup"><?= number_format($stats['pending_followup']) ?></div>
                <div class="stat-label">ต้องติดตาม</div>
            </div>
        </div>
        <div class="metric-cell">
            <div class="stat-card stat-card-info">
                <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                <div class="stat-value" id="statToday"><?= number_format($stats['today_visits']) ?></div>
                <div class="stat-label">เยี่ยมวันนี้</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions - Mobile -->
    <div class="d-lg-none mb-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-lightning-fill text-warning me-1"></i>เมนูลัด</h6>
        <div class="row g-2">
            <div class="col-3">
                <a href="<?= $baseUrl ?>/students" class="quick-action-card">
                    <i class="bi bi-people-fill text-primary"></i>
                    <small>ผู้เรียน</small>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= $baseUrl ?>/visits" class="quick-action-card">
                    <i class="bi bi-house-heart-fill text-success"></i>
                    <small>เยี่ยมบ้าน</small>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= $baseUrl ?>/map" class="quick-action-card">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    <small>แผนที่</small>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= $baseUrl ?>/followups" class="quick-action-card">
                    <i class="bi bi-clipboard2-check text-warning"></i>
                    <small>ติดตาม</small>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Home Visit Action Card -->
        <div class="col-lg-6">
            <div class="dashboard-card">
                <div class="card-head">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center">
                        <i class="bi bi-house-heart-fill me-2 text-primary"></i>บันทึกเยี่ยมบ้านผู้เรียน
                    </h6>
                </div>
                <div class="card-body-custom d-flex flex-column justify-content-between">
                    <p class="text-muted mb-3">เลือกผู้เรียนประจำกลุ่ม แล้วบันทึกผลการเยี่ยมได้ทันที</p>
                    <div class="d-flex flex-wrap align-items-center gap-2 pt-1">
                        <a class="btn btn-primary" href="<?= e(url('visits')) ?>">
                            <i class="bi bi-person-lines-fill"></i>
                            <span>เปิดรายชื่อเพื่อบันทึกเยี่ยมบ้าน</span>
                            <i class="bi bi-arrow-right-short fs-5"></i>
                        </a>
                        <a class="btn btn-outline-primary" href="<?= e(url('visits/history')) ?>">
                            <i class="bi bi-clock-history"></i>
                            <span>ดูประวัติการเยี่ยม</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Follow-ups -->
        <div class="col-lg-6">
            <div class="dashboard-card">
                <div class="card-head">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center">
                        <i class="bi bi-clipboard2-check me-2 text-warning"></i>งานติดตามที่รอดำเนินการ
                    </h6>
                    <a href="<?= $baseUrl ?>/followups" class="btn btn-sm btn-outline-warning rounded-pill">
                        <span>ดูทั้งหมด</span><i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
                <div class="card-body-custom">
                    <?php if (empty($pendingFollowups)): ?>
                    <div class="empty-state py-4 text-center">
                        <div class="empty-state-icon bg-warning-subtle text-warning">
                            <i class="bi bi-clipboard2-check fs-4"></i>
                        </div>
                        <p class="text-muted mt-1 mb-0">ไม่มีงานติดตามที่รอดำเนินการ</p>
                    </div>
                    <?php else: ?>
                    <div class="followup-list">
                        <?php foreach ($pendingFollowups as $fu): ?>
                        <div class="followup-item">
                            <div class="followup-priority">
                                <?= priorityBadge($fu['priority']) ?>
                            </div>
                            <div class="followup-info">
                                <span class="fw-medium"><?= e(truncate($fu['title'], 40)) ?></span>
                                <small class="text-muted d-block">
                                    <?= e($fu['s_prefix'] . $fu['s_firstname'] . ' ' . $fu['s_lastname']) ?>
                                    <?php if ($fu['due_date']): ?>
                                    · ครบกำหนด <?= thaiDate($fu['due_date'], 'short') ?>
                                    <?php
                                    if (strtotime($fu['due_date']) < strtotime('today')):
                                        echo ' <span class="text-danger fw-bold">(เกินกำหนด)</span>';
                                    endif;
                                    ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?= statusBadge($fu['status'], 'followup') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="card-head">
                    <h6 class="fw-semibold mb-0">สถานะการเยี่ยมบ้าน</h6>
                </div>
                <div class="card-body-custom chart-container">
                    <canvas id="visitStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="card-head">
                    <h6 class="fw-semibold mb-0">ผู้เรียนตามระดับ</h6>
                </div>
                <div class="card-body-custom chart-container">
                    <canvas id="levelChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="card-head">
                    <h6 class="fw-semibold mb-0">ระดับความเสี่ยง</h6>
                </div>
                <div class="card-body-custom chart-container">
                    <canvas id="riskChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Visits -->
    <div class="dashboard-card mb-4">
        <div class="card-head">
            <h6 class="fw-semibold mb-0 d-flex align-items-center">
                <i class="bi bi-house-heart me-2 text-success"></i>การเยี่ยมบ้านล่าสุด
            </h6>
            <a href="<?= $baseUrl ?>/visits" class="btn btn-sm btn-outline-success rounded-pill">
                <span>ดูทั้งหมด</span><i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
        <div class="card-body-custom">
            <?php if (empty($recentVisits)): ?>
            <div class="empty-state py-4 text-center">
                <div class="empty-state-icon bg-success-subtle text-success">
                    <i class="bi bi-house-heart fs-4"></i>
                </div>
                <p class="text-muted mt-1 mb-2">ยังไม่มีการเยี่ยมบ้าน</p>
                <?php if (hasRole('teacher', 'admin', 'super_admin')): ?>
                <a href="<?= $baseUrl ?>/students" class="btn btn-sm btn-success rounded-pill mt-1">
                    <i class="bi bi-plus-circle me-1"></i><span>บันทึกเยี่ยมบ้าน</span>
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ผู้เรียน</th>
                            <th class="d-none d-md-table-cell">วันที่เยี่ยม</th>
                            <th class="d-none d-md-table-cell">ครูผู้เยี่ยม</th>
                            <th>ความเสี่ยง</th>
                            <th class="d-none d-md-table-cell">ติดตาม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentVisits as $visit): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <?php if ($visit['profile_image']): ?>
                                        <img src="<?= $baseUrl ?>/public/uploads/<?= e($visit['profile_image']) ?>" alt="">
                                        <?php else: ?>
                                        <i class="bi bi-person-fill"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="fw-medium"><?= e($visit['s_prefix'] . $visit['s_firstname'] . ' ' . $visit['s_lastname']) ?></span>
                                        <small class="d-block d-md-none text-muted"><?= thaiDate($visit['visit_date'], 'short') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell"><?= thaiDate($visit['visit_date'], 'short') ?></td>
                            <td class="d-none d-md-table-cell"><?= e($visit['group_teacher_name'] ?: 'ยังไม่ระบุครูประจำกลุ่ม') ?></td>
                            <td><?= riskBadge($visit['risk_level']) ?></td>
                            <td class="d-none d-md-table-cell">
                                <?php if ($visit['followup_required']): ?>
                                <span class="badge bg-warning text-dark rounded-pill">ต้องติดตาม</span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
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

<!-- Dashboard Charts Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const theme = getComputedStyle(document.documentElement);
    const chartColor = token => theme.getPropertyValue(token).trim();
    const chartText = chartColor('--text-body');
    const chartPlugins = {
        legend: { position: 'bottom', labels: { color: chartText, padding: 16, usePointStyle: true, font: { family: 'Sarabun' } } },
        tooltip: {
            backgroundColor: chartColor('--palette-ink'),
            titleColor: chartColor('--on-primary'),
            bodyColor: chartColor('--on-primary'),
            titleFont: { family: 'Sarabun' },
            bodyFont: { family: 'Sarabun' }
        }
    };
    // Visit Status Chart (Doughnut)
    const visitCtx = document.getElementById('visitStatusChart');
    if (visitCtx) {
        new Chart(visitCtx, {
            type: 'doughnut',
            data: {
                labels: ['เยี่ยมแล้ว', 'ยังไม่เยี่ยม'],
                datasets: [{
                    data: [<?= $stats['visited'] ?>, <?= $stats['not_visited'] ?>],
                    backgroundColor: ['--success-color', '--secondary-color'].map(chartColor),
                    borderColor: chartColor('--surface'),
                    borderWidth: 2,
                    cutout: '72%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: chartPlugins
            }
        });
    }

    // Education Level Chart (Bar)
    const levelCtx = document.getElementById('levelChart');
    if (levelCtx) {
        new Chart(levelCtx, {
            type: 'bar',
            data: {
                labels: ['ประถม', 'ม.ต้น', 'ม.ปลาย'],
                datasets: [{
                    label: 'จำนวนผู้เรียน',
                    data: [
                        <?= (int)($stats['education_levels']['primary'] ?? 0) ?>,
                        <?= (int)($stats['education_levels']['lower_secondary'] ?? 0) ?>,
                        <?= (int)($stats['education_levels']['upper_secondary'] ?? 0) ?>
                    ],
                    backgroundColor: ['--palette-navy', '--palette-sage', '--palette-sand'].map(chartColor),
                    borderColor: ['--palette-navy', '--bs-success-text-emphasis', '--bs-warning-text-emphasis'].map(chartColor),
                    borderWidth: 1,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { ...chartPlugins, legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: chartText, precision: 0, font: { family: 'Sarabun' } }, grid: { color: chartColor('--border') } },
                    x: { ticks: { color: chartText, font: { family: 'Sarabun' } }, grid: { display: false } }
                }
            }
        });
    }

    // Risk Level Chart (Doughnut)
    const riskCtx = document.getElementById('riskChart');
    if (riskCtx) {
        new Chart(riskCtx, {
            type: 'doughnut',
            data: {
                labels: ['เสี่ยงต่ำ', 'เสี่ยงปานกลาง', 'เสี่ยงสูง', 'เสี่ยงสูงมาก'],
                datasets: [{
                    data: [
                        <?= (int)($stats['risk_distribution']['low'] ?? 0) ?>,
                        <?= (int)($stats['risk_distribution']['medium'] ?? 0) ?>,
                        <?= (int)($stats['risk_distribution']['high'] ?? 0) ?>,
                        <?= (int)($stats['risk_distribution']['critical'] ?? 0) ?>
                    ],
                    backgroundColor: ['--risk-low', '--risk-medium', '--risk-high', '--risk-critical'].map(chartColor),
                    borderColor: chartColor('--surface'),
                    borderWidth: 2,
                    cutout: '72%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: chartPlugins
            }
        });
    }
});
</script>
