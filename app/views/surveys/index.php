<?php if (!defined('APP_ROOT')) die(); ?>
<div class="container-fluid px-3 px-md-4 py-3">
<h4 class="fw-bold">สำรวจผู้เรียนรายบุคคล</h4><p class="text-muted">ใช้รายชื่อจาก SDL_school API · บันทึกได้หลายครั้ง · ดูย้อนหลังเฉพาะรายการที่ยังไม่ยกเลิก</p>
<form method="get" action="<?= e(url('surveys')) ?>" class="card card-body border-0 shadow-sm mb-4"><div class="filter-grid">
<div class="col-12 col-md-5"><label class="form-label" for="surveySearch">ค้นหาชื่อหรือรหัสนักศึกษา</label><input class="form-control" id="surveySearch" name="search" value="<?= e($filters['search']) ?>"></div>
<div class="col-6 col-md-3"><label class="form-label" for="surveyTerm">ภาคเรียน</label><select class="form-select" id="surveyTerm" name="term_id"><?php foreach($terms as $term): ?><option value="<?= $term['id'] ?>" <?= $termId===(int)$term['id']?'selected':'' ?>><?= e($term['term_name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="surveyGroup">กลุ่มเรียน / ศกร.</label><select class="form-select" id="surveyGroup" name="center_id"><option value="">ทุกกลุ่มเรียน</option><?php foreach($centers as $center): ?><option value="<?= $center['id'] ?>" <?= (int)$filters['center_id']===(int)$center['id']?'selected':'' ?>><?= e($center['center_name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="surveyLevel">ระดับชั้น</label><select class="form-select" id="surveyLevel" name="level"><?php foreach([''=>'ทุกระดับชั้น','primary'=>'ประถมศึกษา','lower_secondary'=>'มัธยมศึกษาตอนต้น','upper_secondary'=>'มัธยมศึกษาตอนปลาย'] as $value=>$label): ?><option value="<?= $value ?>" <?= $filters['level']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-6 col-md-3"><label class="form-label" for="surveyState">สถานะสำรวจ</label><select class="form-select" id="surveyState" name="survey_status"><?php foreach([''=>'ทั้งหมด','surveyed'=>'สำรวจแล้ว','not_surveyed'=>'ยังไม่สำรวจ'] as $value=>$label): ?><option value="<?= $value ?>" <?= $filters['survey_status']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-1"><div class="filter-actions"><button class="btn btn-primary">ค้นหา</button><a class="btn btn-outline-secondary" href="<?= e(url('surveys')) ?>">ล้างตัวกรอง</a></div></div>
</div></form>
<p>พบ <?= number_format($roster['total']) ?> คน</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0 records-table surveys-table"><thead><tr><th>ผู้เรียน</th><th>กลุ่มเรียน / ครู</th><th>สถานะสำรวจ</th><th class="text-center">จัดการ</th></tr></thead><tbody>
<?php foreach($roster['data'] as $row): $count=(int)($row['survey_count'] ?? 0); $latestId=(int)($row['latest_survey_id'] ?? 0); ?>
<tr>
    <td>
        <strong><?= e($row['prefix'].$row['firstname'].' '.$row['lastname']) ?></strong>
        <br><small class="text-muted"><?= e($row['student_code']) ?> · <?= e(educationLevelName($row['education_level'])) ?></small>
    </td>
    <td><?= e($row['center_name']??'-') ?><br><small class="text-muted"><?= e(($row['teacher_firstname']??'').' '.($row['teacher_lastname']??'')) ?></small></td>
    <td>
        <?php if ($count): ?>
            <span class="badge bg-success">สำรวจแล้ว <?= $count ?> ครั้ง</span>
            <?php if (!empty($row['last_survey_at'])): ?><br><small class="text-muted">ล่าสุด <?= e(thaiDateTime($row['last_survey_at'])) ?></small><?php endif; ?>
        <?php else: ?>
            <span class="badge bg-secondary">ยังไม่สำรวจ</span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <div class="action-buttons">
            <?php if ($latestId): ?>
                <a class="btn btn-sm btn-outline-secondary btn-icon" href="<?= e(url('surveys/view/'.$latestId)) ?>" title="ดูแบบสำรวจล่าสุด" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-eye"></i></a>
            <?php endif; ?>
            <?php if (canCreateSurveyForCenter((int)($row['learning_center_id'] ?? 0))): ?>
                <a class="btn btn-sm <?= $count?'btn-outline-primary':'btn-primary' ?> btn-icon" href="<?= e(url('surveys/'.$row['id'].'?term_id='.$termId)) ?>" title="<?= $count?'สำรวจเพิ่มอีกครั้ง':'เริ่มสำรวจรายบุคคล' ?>" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi <?= $count?'bi-plus-lg':'bi-clipboard2-check' ?>"></i></a>
            <?php elseif (isTeacher()): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" disabled title="ปิดรับการสำรวจสำหรับศกร.นี้" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-lock"></i></button>
            <?php endif; ?>
            <?php if ($latestId && canCancelRecords()): ?>
            <form method="post" action="<?= e(url('surveys/cancel/'.$latestId)) ?>" class="d-inline" onsubmit="return confirm('ยืนยันยกเลิกการสำรวจครั้งล่าสุด? รายการนี้จะถูกนำออกจากประวัติ');">
                <?= csrfField() ?>
                <button class="btn btn-sm btn-outline-danger btn-icon" title="ยกเลิกการสำรวจล่าสุด" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-x-circle"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$roster['data']): ?><tr><td colspan="4" class="text-center py-4">ไม่พบผู้เรียนตามเงื่อนไข</td></tr><?php endif; ?>
</tbody></table></div></div><div class="mt-3"><?= paginationHtml($page,$roster['total_pages'],url('surveys'),$filters) ?></div>
</div>
