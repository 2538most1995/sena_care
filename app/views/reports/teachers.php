<?php if (!defined('APP_ROOT')) die(); $baseUrl=rtrim(url(),'/'); ?>
<div class="container-fluid px-3 px-md-4 py-3 teacher-report-page">
    <h4 class="fw-bold">รายงานผลการเยี่ยมบ้านรายครู</h4>
    <p class="text-muted">จัดทำ PDF พร้อมผลประเมิน รูปแนบ และช่องลงนาม เพื่อเสนอผู้อำนวยการ</p>
    <?php if($reportError): ?><div class="alert alert-danger" role="alert"><?= e($reportError) ?></div><?php endif; ?>
    <form method="get" action="<?= $baseUrl ?>/reports/teachers" class="card border-0 shadow-sm mb-4">
        <div class="card-body"><div class="row g-3 align-items-end">
            <div class="col-12 col-md-4"><label for="reportTeacher" class="form-label">ครูผู้รับผิดชอบ</label><select id="reportTeacher" name="teacher_id" class="form-select" required><option value="">เลือกครู</option><?php foreach($teachers as $teacher): ?><option value="<?= $teacher['id'] ?>" <?= $teacherId===$teacher['id']?'selected':'' ?>><?= e(($teacher['prefix']??'').$teacher['firstname'].' '.$teacher['lastname']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 col-sm-6 col-md-3"><label for="reportTerm" class="form-label">ภาคเรียน</label><select id="reportTerm" name="term_id" class="form-select" required><?php foreach($terms as $term): ?><option value="<?= $term['id'] ?>" <?= $termId===$term['id']?'selected':'' ?>><?= e($term['term_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 col-sm-6 col-md-5"><label for="reportDirector" class="form-label">ชื่อผู้อำนวยการ (เว้นว่างเพื่อลงนามภายหลัง)</label><input id="reportDirector" name="director" maxlength="150" class="form-control" value="<?= e($director) ?>"></div>
            <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-outline-primary" type="submit">แสดงสรุป</button><button class="btn btn-primary report-download" type="submit" formaction="<?= $baseUrl ?>/reports/teachers/pdf"><i class="bi bi-file-earmark-pdf me-1"></i>ดาวน์โหลด PDF พร้อมรูปแนบ</button></div>
        </div></div>
    </form>
    <?php if($report): ?>
    <h5><?= e($report['teacher']['prefix'].$report['teacher']['firstname'].' '.$report['teacher']['lastname']) ?> · ภาคเรียน <?= e($report['term']['term_name']) ?></h5>
    <div class="report-stats mb-3"><?php foreach(['ผู้เรียนที่รับผิดชอบ'=>$report['assigned'],'เยี่ยมแล้ว (คน)'=>$report['visitedStudents'],'บันทึกเยี่ยม (ครั้ง)'=>count($report['visits']),'รูปแนบ (รูป)'=>$report['photoCount']] as $label=>$value): ?><div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><strong class="fs-3"><?= $value ?></strong></div></div><?php endforeach; ?></div>
    <p class="small text-muted">ผู้เรียนที่เยี่ยมซ้ำนับเป็น 1 คน · นับบันทึกที่เสร็จสมบูรณ์ของผู้เรียนในความรับผิดชอบ แม้ผู้ดูแลระบบเป็นผู้บันทึกแทน · PDF รวมรายละเอียดและรูปทั้งหมดที่แนบไว้</p>
    <div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>ผู้เรียน</th><th>วันที่เยี่ยม</th><th>กลุ่มเรียน</th><th>ครูผู้เยี่ยม (ครูประจำกลุ่ม)</th><th>ความเสี่ยง</th><th>รูปแนบ</th><th>รายละเอียด</th></tr></thead><tbody>
    <?php foreach($report['visits'] as $v): ?><tr><td><?= e($v['prefix'].$v['firstname'].' '.$v['lastname']) ?><br><small class="text-muted"><?= e($v['student_code']) ?></small></td><td class="text-nowrap"><?= e(thaiDate($v['visit_date'])) ?></td><td><?= e($v['center_name']??'-') ?></td><td><?= e($v['group_teacher_name'] ?: 'ยังไม่ระบุครูประจำกลุ่ม') ?></td><td><?= riskBadge($v['risk_level']) ?></td><td><?= count($v['photos']) ?></td><td><a href="<?= $baseUrl ?>/visits/<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">เปิดบันทึก</a></td></tr><?php endforeach; ?>
    <?php if(!$report['visits']): ?><tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีบันทึกเยี่ยมที่เสร็จสมบูรณ์ในภาคเรียนนี้</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
    <?php else: ?><div class="alert alert-light border">เลือกครูและภาคเรียนเพื่อดูสรุปหรือดาวน์โหลดรายงาน</div><?php endif; ?>
</div>
