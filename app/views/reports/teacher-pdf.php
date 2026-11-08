<?php
if (!defined('APP_ROOT')) die();
$escape=fn($s)=>htmlspecialchars((string)($s??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$name=trim(($report['teacher']['prefix']??'').$report['teacher']['firstname'].' '.$report['teacher']['lastname']);
$riskNames=['low'=>'ปกติ / ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','critical'=>'วิกฤต'];
$text=fn($s)=>nl2br($escape(trim((string)$s)===''?'ไม่ได้ระบุ':$s));
?>
<style>
body {font-family:thsarabun;font-size:16pt;line-height:1.3;color:#263c53} h1{font-size:23pt;text-align:center;margin:0 0 3mm}h2{font-size:19pt;margin:5mm 0 2mm;color:#344d66}p{margin:2mm 0}table{border-collapse:collapse;width:100%;font-size:15pt}td,th{border:0.2mm solid #d3dfea;padding:2mm;vertical-align:top}th{background:#f0f4f8;text-align:left} .muted{color:#666;font-size:14pt}.center{text-align:center}.plain td{border:0;padding:1mm}.photos td{text-align:center;width:50%;border:0}.signatures td{border:0;text-align:center;width:50%;padding-top:12mm}
</style>
<h1>รายงานผลการเยี่ยมบ้านผู้เรียน</h1>
<p class="center"><?= $escape($report['teacher']['organization_name']??'ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.') ?></p>
<p class="center">ภาคเรียน <?= $escape($report['term']['term_name']) ?></p>
<p><b>เรื่อง</b> รายงานผลการติดตามและเยี่ยมบ้านผู้เรียน</p>
<p><b>เรียน</b> <?= $escape($signatures['recipient_title']) ?><?= $director!==''?' '.$escape($director):'' ?></p>
<p><b>ผู้รายงาน</b> <?= $escape($signatures['reporter_name'] !== '' ? $signatures['reporter_name'] : $name) ?> &nbsp; <b>วันที่จัดทำ</b> <?= $escape(thaiDate(date('Y-m-d'))) ?></p>
<p>ขอรายงานผลการติดตามและเยี่ยมบ้านผู้เรียนในภาคเรียนดังกล่าว พร้อมผลประเมิน สรุปปัญหา แนวทางช่วยเหลือ และภาพประกอบ ตามรายละเอียดต่อไปนี้</p>
<h2>1. สรุปผลการดำเนินงาน</h2>
<table><tr><th>ผู้เรียนในความรับผิดชอบปัจจุบัน</th><td><?= $report['assigned'] ?> คน</td><th>ผู้เรียนในความรับผิดชอบที่เยี่ยมแล้ว</th><td><?= $report['visitedStudents'] ?> คน</td></tr><tr><th>บันทึกเยี่ยมที่เสร็จสมบูรณ์</th><td><?= count($report['visits']) ?> ครั้ง</td><th>ความเสี่ยงสูง / วิกฤต</th><td><?= $report['highRisk'] ?> คน</td></tr><tr><th>รูปภาพแนบ</th><td colspan="3"><?= $report['photoCount'] ?> รูป</td></tr></table>
<p class="muted">นับผลเยี่ยมที่เสร็จสมบูรณ์ของผู้เรียนในความรับผิดชอบตามการจัดกลุ่มในภาคเรียนที่เลือก รวมกรณีผู้ดูแลระบบบันทึกแทน ผู้เรียนที่เยี่ยมซ้ำนับเป็น 1 คน ระดับความเสี่ยงใช้ผลเยี่ยมล่าสุดของแต่ละคน จำนวนผู้เรียนที่รับผิดชอบใช้การจัดกลุ่มปัจจุบัน</p>
<h2>2. รายการเยี่ยมบ้าน</h2>
<table><thead><tr><th>ลำดับ</th><th>ผู้เรียน / รหัสนักศึกษา</th><th>วันที่เยี่ยม</th><th>ศกร. / กลุ่มเรียน</th><th>ความเสี่ยง</th></tr></thead><tbody>
<?php foreach($report['visits'] as $i=>$v): ?><tr><td><?= $i+1 ?></td><td><?= $escape($v['prefix'].$v['firstname'].' '.$v['lastname']) ?><br><?= $escape($v['student_code']) ?></td><td><?= $escape(thaiDate($v['visit_date'])) ?></td><td><?= $escape($v['center_name']??'-') ?></td><td><?= $escape($riskNames[$v['risk_level']]??$v['risk_level']) ?></td></tr><?php endforeach; ?>
<?php if(!$report['visits']): ?><tr><td colspan="5">ยังไม่มีบันทึกเยี่ยมที่เสร็จสมบูรณ์ของครูในภาคเรียนนี้</td></tr><?php endif; ?>
</tbody></table>
<p>จึงเรียนมาเพื่อโปรดทราบและพิจารณาแนวทางช่วยเหลือผู้เรียนตามความเหมาะสม</p>
<?php $signatureTableClass = 'signatures'; $reporterDefaultName = $name; include APP_ROOT.'/app/views/reports/_signatures.php'; ?>
<?php foreach($report['visits'] as $v): ?>
<!--REPORT_CHUNK-->
<pagebreak />
<?= VisitReportPdf::html($v,$director,$signatures) ?>
<?php endforeach; ?>
