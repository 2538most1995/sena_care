<?php if (!defined('APP_ROOT')) die(); ?>
<div class="container-fluid px-3 px-md-4 py-3 visit-record-page"><div class="mx-auto" style="max-width:850px">
<a href="<?= e(url('surveys?term_id='.$termId)) ?>">← กลับหน้าสำรวจผู้เรียน</a>
<div class="card border-0 shadow-sm mt-3 mb-4"><div class="card-body"><h4 class="fw-bold">แบบสำรวจผู้เรียนรายบุคคล</h4>
<h5><?= e($student['prefix'].$student['firstname'].' '.$student['lastname']) ?></h5>
<p class="mb-1">รหัสนักศึกษา <?= e($student['student_code']) ?> · ภาคเรียน <?= e($student['term_name']) ?></p><p><?= e($student['center_name']??'-') ?> · ครู <?= e(($student['teacher_firstname']??'').' '.($student['teacher_lastname']??'')) ?></p>
<a href="<?= e(url('students/'.$student['id'])) ?>">ดูข้อมูลผู้เรียน ที่อยู่ และการเยี่ยมบ้าน</a>
<p class="small text-muted mb-0 mt-2">ชื่อ รหัส กลุ่มเรียน และครูอ้างอิงจากข้อมูล API · บันทึกได้หลายครั้ง และแสดงเฉพาะประวัติที่ยังไม่ยกเลิก</p>
</div></div>

<?php if ($readOnly && !isExecutive()): ?>
<div class="alert alert-warning"><i class="bi bi-lock me-1"></i>ปิดรับการสำรวจสำหรับศกร.นี้ตามช่วงเวลาที่อำเภอกำหนด — ดูข้อมูลย้อนหลังได้ แต่ไม่สามารถบันทึกใหม่ได้</div>
<?php endif; ?>

<?php if ($surveyHistory): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 px-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>ประวัติการสำรวจ (<?= count($surveyHistory) ?>)</h6>
        <small class="text-muted">บันทึกใหม่ทุกครั้ง = เพิ่มรายการใหม่ ไม่ทับของเดิม</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>ครั้งที่</th><th>วันที่สำรวจ</th><th>ผู้บันทึก</th><th class="d-none d-md-table-cell">หมายเหตุ</th><th>สถานะ</th><th class="text-center">จัดการ</th></tr></thead>
            <tbody>
            <?php foreach ($surveyHistory as $h): ?>
                <tr class="<?= !empty($h['cancelled_at']) ? 'text-muted' : '' ?>">
                    <td>#<?= (int)$h['revision'] ?></td>
                    <td><?= e(thaiDateTime($h['surveyed_at'])) ?></td>
                    <td><?= e(($h['firstname'] ?? '') . ' ' . ($h['lastname'] ?? '')) ?></td>
                    <td class="d-none d-md-table-cell"><?= e(truncate((string)($h['note'] ?? ''), 40)) ?: '-' ?></td>
                    <td><?= !empty($h['cancelled_at']) ? '<span class="badge bg-secondary">ยกเลิกแล้ว</span>' : '<span class="badge bg-success">ใช้งาน</span>' ?></td>
                    <td class="text-center">
                        <div class="action-buttons">
                            <a class="btn btn-sm btn-outline-secondary btn-icon" href="<?= e(url('surveys/view/'.$h['id'])) ?>" title="ดูรายละเอียดแบบสำรวจ" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-eye"></i></a>
                            <?php if (canCancelRecords() && empty($h['cancelled_at'])): ?>
                            <form method="post" action="<?= e(url('surveys/cancel/'.$h['id'])) ?>" class="d-inline" onsubmit="return confirm('ยืนยันยกเลิกการสำรวจครั้งนี้? รายการนี้จะถูกนำออกจากประวัติ');">
                                <?= csrfField() ?>
                                <button class="btn btn-sm btn-outline-danger btn-icon" title="ยกเลิกการสำรวจ" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-x-circle"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($survey): ?><div class="alert alert-success">สำรวจล่าสุดครั้งที่ <?= (int)$survey['revision'] ?> · ผู้บันทึก <?= e(($survey['firstname'] ?? '').' '.($survey['lastname'] ?? '')) ?> · <?= e(thaiDateTime($survey['surveyed_at'])) ?></div><?php endif; ?>
<?php if ($fromVisit): ?><div class="alert alert-info">แสดงคำตอบเดิมจากการเยี่ยมบ้านล่าสุดเพื่อไม่ต้องกรอกใหม่ กรุณาตรวจสอบและบันทึกยืนยันแบบสำรวจ</div><?php endif; ?>
<form method="post" action="<?= e(url('surveys/'.$student['id'].'?term_id='.$termId)) ?>" class="card border-0 shadow-sm"><div class="card-body p-3 p-md-4">
<?= csrfField() ?><input type="hidden" name="term_id" value="<?= $termId ?>"><input type="hidden" name="revision" value="<?= (int)($survey['revision']??0) ?>">
<fieldset <?= $readOnly?'disabled':'' ?>>
<?php include APP_ROOT.'/app/views/assessments/fields.php'; ?>
<label class="form-label" for="surveyNote">บันทึกเพิ่มเติมของการสำรวจ (ถ้ามี)</label><textarea class="form-control mb-4" id="surveyNote" name="note" maxlength="10000" rows="3"><?= e($draft['note']??$survey['note']??'') ?></textarea>
<?php if (!$readOnly): ?><button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-check2-circle me-1"></i>บันทึกการสำรวจครั้งใหม่</button><?php endif; ?>
</fieldset></div></form>
</div></div>
