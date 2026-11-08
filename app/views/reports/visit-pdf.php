<?php
if (!defined('APP_ROOT')) die();
$escape=fn($s)=>htmlspecialchars((string)($s??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$text=fn($s)=>nl2br($escape(trim((string)($s??''))===''?'ไม่ได้บันทึก':$s));
$studentName=trim(($v['prefix']??'').$v['firstname'].' '.$v['lastname']);
$teacherName=$v['group_teacher_name']?:'ยังไม่ระบุครูประจำกลุ่ม';
$riskNames=['low'=>'เสี่ยงต่ำ','medium'=>'เสี่ยงปานกลาง','high'=>'เสี่ยงสูง','critical'=>'เสี่ยงวิกฤต'];
$visitTypes=['home_visit'=>'ลงพื้นที่เยี่ยมบ้าน','phone'=>'โทรศัพท์','line'=>'LINE','at_center'=>'พบที่ศูนย์การเรียนรู้','parent_meeting'=>'ประชุมผู้ปกครอง','online'=>'ออนไลน์','other'=>'อื่นๆ'];
$statusNames=['completed'=>'เยี่ยมแล้ว','draft'=>'ฉบับร่าง','cancelled'=>'ยกเลิก'];
$pairs=function(array $items) use($text) {
    if (!$items) $items=[['ผลการสำรวจ','ไม่ได้บันทึก']];
    echo '<table class="formal-data" autosize="1"><tbody>';
    foreach(array_chunk($items,2) as $pair) {
        echo '<tr>';
        foreach($pair as $item) echo '<td class="field">'.$text($item[0]).'</td><td class="value">'.$text($item[1]).(!empty($item[2])?'<br><span class="small">มีคำตอบเดิมต่างกัน โปรดตรวจยืนยัน</span>':'').'</td>';
        if(count($pair)===1) echo '<td class="field"></td><td class="value"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
};
$notes=function(array $items) use($text) {
    echo '<table class="formal-data notes" autosize="1"><tbody>';
    foreach($items as $item) echo '<tr><td class="field">'.$text($item[0]).'</td><td>'.$text($item[1]).(!empty($item[2])?'<br><span class="small">คำตอบเดิมอีกชุดที่แตกต่างกัน</span>':'').'</td></tr>';
    echo '</tbody></table>';
};
$address=implode(' ',array_filter([
    !empty($v['house_no'])?'บ้านเลขที่ '.$v['house_no']:null,!empty($v['moo'])?'หมู่ '.$v['moo']:null,
    !empty($v['subdistrict'])?'ต.'.$v['subdistrict']:null,!empty($v['district'])?'อ.'.$v['district']:null,
    !empty($v['province'])?'จ.'.$v['province']:null,$v['zipcode']??null
]));
$coordinate=function($lat,$lng) {
    if ($lat===null || $lng===null || $lat==='' || $lng==='' || !is_numeric($lat) || !is_numeric($lng) || abs((float)$lat)>90 || abs((float)$lng)>180) return null;
    return number_format((float)$lat,6,'.','').', '.number_format((float)$lng,6,'.','');
};
$checkin=$coordinate($v['checkin_latitude']??null,$v['checkin_longitude']??null);
$home=$coordinate($v['home_lat']??null,$v['home_lng']??null);
$mapUrl=$checkin?'https://www.google.com/maps?q='.rawurlencode($checkin):null;
?>
<style>
body {font-family:thsarabun;font-size:16pt;line-height:1.17;color:#263c53}
.formal-title {text-align:center;margin:0 0 6mm}
.formal-title h1 {font-size:20pt;font-weight:bold;color:#263c53;margin:0 0 1mm}
.formal-title p {margin:0.5mm 0}
.formal-subtitle {color:#657b94;font-size:14pt}
h2.formal-section {font-size:17pt;font-weight:bold;margin:5mm 0 2.5mm;color:#263c53;page-break-after:avoid}
table.formal-data {width:100%;border-collapse:collapse;font-size:15pt;table-layout:fixed;margin:0}
.formal-data td {border:0;border-bottom:0.2mm solid #d3dfea;padding:1.6mm 2.2mm;vertical-align:middle;line-height:1.14}
.formal-data .field {width:19%;background:#f0f4f8;color:#344d66}
.formal-data .value {width:31%;color:#263442}
.formal-data.notes .field {width:26%}
.formal-data.notes td {vertical-align:top}
.formal-caption {font-size:13pt;color:#657b94;margin:1.5mm 0 0}
.formal-photo {width:100%;border-collapse:collapse;page-break-inside:avoid;margin:0 0 3mm}
.formal-photo td {width:50%;text-align:center;border:0;padding:0 2mm;vertical-align:middle}
.formal-photo .photo-frame {height:79mm;vertical-align:middle}
.formal-photo img {vertical-align:middle}
.formal-signatures {width:100%;font-size:15pt;border-collapse:collapse;page-break-inside:avoid;margin-top:2mm}
.formal-signatures td {width:50%;border:0;text-align:center;padding:1.5mm 1mm;line-height:1.25}
.formal-location {width:100%;border-collapse:collapse;font-size:14pt;background:#f0f4f8;page-break-inside:avoid}
.formal-location td {border:0;padding:2.5mm;vertical-align:middle}
.small {font-size:12pt;color:#657b94}
.formal-evidence .formal-title {margin-bottom:3mm}
.formal-evidence h2.formal-section {margin:2.5mm 0 1.5mm}
</style>
<div class="formal-title">
    <h1><?= $escape($v['organization_name']??'ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.') ?></h1>
    <p>รายงานผลการติดตามเยี่ยมบ้านผู้เรียนรายบุคคล</p>
    <p class="formal-subtitle">ภาคเรียน <?= $escape($v['term_name']??'-') ?> &nbsp; • &nbsp; วันที่เยี่ยม <?= $escape(thaiDate($v['visit_date'])) ?></p>
</div>
<h2 class="formal-section">1. &nbsp; ข้อมูลทั่วไปและการเยี่ยมบ้าน</h2>
<?php $pairs([
    ['ชื่อ - สกุล',$studentName],['รหัสผู้เรียน',$v['student_code']],
    ['กลุ่มเรียน / ศกร.',$v['center_name']??''],['ระดับชั้น',educationLevelName($v['education_level']??'')],
    ['ครูผู้เยี่ยม',$teacherName],['วันที่เยี่ยม',thaiDate($v['visit_date'])],
    ['ระดับความเสี่ยง',$riskNames[$v['risk_level']]??$v['risk_level']],['สถานะ / ประเภท',($statusNames[$v['status']]??$v['status']).' / '.($visitTypes[$v['visit_type']]??$v['visit_type'])],
]); ?>
<?php if($address!==''): ?><p class="formal-caption"><b>ที่อยู่ผู้เรียน:</b> <?= $escape($address) ?></p><?php endif; ?>

<h2 class="formal-section">2. &nbsp; สภาพครอบครัวและการดำรงชีวิต</h2>
<?php $pairs($sections['family']); ?>
<h2 class="formal-section">3. &nbsp; ความพร้อมด้านการเรียนรู้</h2>
<?php $pairs($sections['learning']); ?>
<h2 class="formal-section">4. &nbsp; ปัญหา ความเสี่ยง และความต้องการ</h2>
<?php $pairs($sections['needs']); ?>
<?php if($sections['other']): ?><h2 class="formal-section">ผลสำรวจเพิ่มเติม</h2><?php $notes($sections['other']); endif; ?>

<!--REPORT_CHUNK-->
<pagebreak />
<div class="formal-evidence">
<div class="formal-title">
    <h1>หลักฐานประกอบการเยี่ยมบ้านและการรับรองรายงาน</h1>
    <p class="formal-subtitle"><?= $escape($studentName) ?> &nbsp; | &nbsp; รหัส <?= $escape($v['student_code']) ?> &nbsp; | &nbsp; <?= $escape(thaiDate($v['visit_date'])) ?></p>
</div>
<h2 class="formal-section">5. &nbsp; สรุปผลการเยี่ยมและข้อเสนอแนะ</h2>
<?php
$summaryItems=[['ผลการเยี่ยมบ้าน',$v['summary']??'']];
if(trim((string)($v['problem']??''))!=='') $summaryItems[]=['ปัญหาที่พบ',$v['problem']];
if(trim((string)($v['student_need']??''))!=='') $summaryItems[]=['ความต้องการของผู้เรียน',$v['student_need']];
$summaryItems[]=['ข้อเสนอแนะของครู',$v['teacher_recommendation']??''];
foreach($sections['recommendation'] as $item) {
    if(trim($item[1])!=='' && trim($item[1])!==trim((string)($v['teacher_recommendation']??''))) $summaryItems[]=$item;
}
$notes($summaryItems);
?>
<h2 class="formal-section">6. &nbsp; ภาพประกอบการเยี่ยมบ้าน (<?= count($v['photos']) ?> รูป)</h2>
<?php foreach(array_chunk($v['photos'],2) as $pairIndex=>$pair): ?>
<table class="formal-photo"><tr>
<?php foreach($pair as $photo):
    $path=APP_ROOT.'/public/uploads/visits/'.basename($photo['stored_name']);
    $size=is_file($path)?@getimagesize($path):false;
    if($size && $size[0]>0 && $size[1]>0) { $scale=min(82/$size[0],78/$size[1]); $width=$size[0]*$scale; $height=$size[1]*$scale; }
?><td class="photo-frame"><?php if($size): ?><img src="<?= $escape($path) ?>" style="width:<?= number_format($width,2,'.','') ?>mm;height:<?= number_format($height,2,'.','') ?>mm" /><?php else: ?><span class="formal-caption">ไม่พบไฟล์รูปภาพแนบ</span><?php endif; ?></td><?php endforeach; ?>
<?php if(count($pair)===1): ?><td></td><?php endif; ?>
</tr><tr><?php foreach($pair as $photoIndex=>$photo): ?><td><p class="formal-caption">ภาพที่ <?= $pairIndex*2+$photoIndex+1 ?> &nbsp; <?= $escape(trim((string)($photo['caption']??''))?:'หลักฐานที่แนบในบันทึกเยี่ยมบ้าน') ?></p></td><?php endforeach; ?><?php if(count($pair)===1): ?><td></td><?php endif; ?></tr></table>
<?php endforeach; ?>
<?php if(!$v['photos']): ?><p class="formal-caption">ไม่มีภาพแนบในบันทึกนี้</p><?php endif; ?>

<h2 class="formal-section">7. &nbsp; แนวทางการติดตามเพิ่มเติม</h2>
<?php $followup=($v['followup_required']??false)?'ต้องติดตามต่อ':'ไม่ได้ระบุให้ติดตามต่อ';
if(!empty($v['next_followup_date'])) $followup.=' • กำหนด '.thaiDate($v['next_followup_date']);
$notes([['การติดตามผล',$followup]]);
foreach($v['followups']??[] as $task): ?><p class="formal-caption"><?= $escape($task['title']) ?> (<?= $escape(['pending'=>'รอดำเนินการ','in_progress'=>'กำลังดำเนินการ','completed'=>'เสร็จสิ้น','cancelled'=>'ยกเลิก'][$task['status']]??$task['status']) ?>) <?= !empty($task['due_date'])?'กำหนด '.$escape(thaiDate($task['due_date'])):'' ?></p><?php endforeach; ?>

<h2 class="formal-section">8. &nbsp; การยืนยันพิกัด GPS และแผนที่</h2>
<table class="formal-location"><tr><td>
<b>พิกัดเช็คอิน:</b> <?= $escape($checkin??'ไม่ได้บันทึก') ?><br>
<b>พิกัดบ้านผู้เรียน:</b> <?= $escape($home??'ไม่ได้บันทึก') ?><br>
<b>ระยะห่างจากบ้าน:</b> <?= isset($v['distance_from_home'])?number_format((int)$v['distance_from_home']).' เมตร':'ไม่ได้บันทึก' ?>
<?php if($mapUrl): ?><br><a href="<?= $escape($mapUrl) ?>" style="color:#344d66">เปิดแผนที่ตำแหน่งเช็คอิน</a><?php endif; ?>
</td><?php if($mapUrl): ?><td style="width:30mm;text-align:center"><barcode code="<?= $escape($mapUrl) ?>" type="QR" size="0.8" error="M" /><br><span class="small">สแกนเปิดแผนที่</span></td><?php endif; ?></tr></table>

<div style="page-break-inside:avoid">
<h2 class="formal-section">9. &nbsp; การลงนามรับรองรายงาน</h2>
<table class="formal-signatures"><tr><td>
ลงชื่อ ....................................................<br>
(<?= $escape($teacherName) ?>)<br>ครูผู้เยี่ยม / ครูประจำกลุ่ม<br>วันที่ .......... / .......... / ..........
</td><td>
ลงชื่อ ....................................................<br>
(....................................................)<br>ผู้อำนวยการ / ผู้รับทราบรายงาน<br>วันที่ .......... / .......... / ..........
</td></tr></table>
</div>
</div>
