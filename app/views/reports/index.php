<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line-fill text-info me-2"></i>รายงานและสถิติภาพรวม</h4>
            <p class="text-muted small mb-0">สรุปความก้าวหน้าการเยี่ยมบ้าน และรายงานความเสี่ยงของผู้เรียน สกร.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a class="btn btn-primary" href="<?= $baseUrl ?>/reports/teachers?term_id=<?= $termId ?>"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF รายงานรายครู</a>
            <form method="GET" action="<?= $baseUrl ?>/reports" class="d-flex align-items-center gap-2">
                <select name="term_id" class="form-select" onchange="this.form.submit();">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($t['id'] == ($termId ?? currentTermId())) ? 'selected' : '' ?>>ภาคเรียน <?= e($t['term_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> ส่งออกรายงาน (CSV)
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= $baseUrl ?>/reports/export?term_id=<?= $termId ?? currentTermId() ?>&type=students"><i class="bi bi-people me-2"></i>รายชื่อผู้เรียนและสถานะเยี่ยมบ้าน</a></li>
                    <li><a class="dropdown-item" href="<?= $baseUrl ?>/reports/export?term_id=<?= $termId ?? currentTermId() ?>&type=risks"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>รายชื่อผู้เรียนกลุ่มเสี่ยงสูง/วิกฤต</a></li>
                    <li><a class="dropdown-item" href="<?= $baseUrl ?>/reports/export?term_id=<?= $termId ?? currentTermId() ?>&type=centers"><i class="bi bi-building me-2"></i>สรุปสถิติแยกตาม ศกร.ตำบล</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-warning" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
            </button>
        </div>
    </div>

    <!-- Center Breakdown Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-building text-primary me-2"></i>สถิติแยกตาม ศกร.ระดับตำบล</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ศกร.ตำบล</th>
                            <th class="text-center">จำนวนผู้เรียนทั้งหมด</th>
                            <th class="text-center">เยี่ยมบ้านแล้ว</th>
                            <th class="text-center">ร้อยละความสำเร็จ</th>
                            <th class="text-center">เสี่ยงสูง/วิกฤต</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $totalAll = 0; $totalVisited = 0; $totalHighRisk = 0;
                            foreach ($centerStats as $cs): 
                                $pct = $cs['total_students'] > 0 ? round(($cs['visited_count'] / $cs['total_students']) * 100, 1) : 0;
                                $totalAll += $cs['total_students'];
                                $totalVisited += $cs['visited_count'];
                                $totalHighRisk += $cs['high_risk_count'];
                        ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= e($cs['center_name']) ?></td>
                                <td class="text-center"><?= number_format($cs['total_students']) ?> คน</td>
                                <td class="text-center text-success fw-bold"><?= number_format($cs['visited_count']) ?> คน</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; max-width: 100px;">
                                            <div class="progress-bar bg-success" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <small class="fw-bold"><?= $pct ?>%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($cs['high_risk_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $cs['high_risk_count'] ?> คน</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">0 คน</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3">รวมทั้งหมด</td>
                            <td class="text-center"><?= number_format($totalAll) ?> คน</td>
                            <td class="text-center text-success"><?= number_format($totalVisited) ?> คน</td>
                            <td class="text-center"><?= $totalAll > 0 ? round(($totalVisited / $totalAll) * 100, 1) : 0 ?>%</td>
                            <td class="text-center text-danger"><?= number_format($totalHighRisk) ?> คน</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- High Risk / Critical Students List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>รายชื่อผู้เรียนที่มีความเสี่ยงสูง / วิกฤต (ต้องการความช่วยเหลือ)</h5>
            <span class="badge bg-danger rounded-pill"><?= count($riskStudents) ?> คน</span>
        </div>
        <div class="card-body p-4">
            <?php if (empty($riskStudents)): ?>
                <div class="text-center py-4 text-muted">ไม่พบผู้เรียนในระดับความเสี่ยงสูง</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ผู้เรียน</th>
                                <th>รหัสนักศึกษา</th>
                                <th>ระดับการศึกษา</th>
                                <th>ศกร.ตำบล</th>
                                <th>ครูผู้รับผิดชอบ</th>
                                <th>เบอร์โทร</th>
                                <th class="text-center">ระดับความเสี่ยง</th>
                                <th class="text-end pe-3 no-print">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riskStudents as $rs): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark"><?= e($rs['prefix'] . $rs['firstname'] . ' ' . $rs['lastname']) ?></td>
                                    <td><?= e($rs['student_code']) ?></td>
                                    <td><small><?= e(educationLevelName($rs['education_level'])) ?></small></td>
                                    <td><small><?= e($rs['center_name'] ?: '-') ?></small></td>
                                    <td><small><?= e($rs['teacher_firstname'] . ' ' . $rs['teacher_lastname']) ?></small></td>
                                    <td><?= e(formatPhone($rs['phone'])) ?></td>
                                    <td class="text-center"><?= riskBadge($rs['risk_level']) ?></td>
                                    <td class="text-end pe-3 no-print">
                                        <a href="<?= $baseUrl ?>/students/<?= $rs['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i> ดูข้อมูล
                                        </a>
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
