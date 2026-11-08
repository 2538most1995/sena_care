<?php
// Uses temporary configuration and synthetic PDF data; never connects to MySQL.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT.'/app/services/TeacherVisitReport.php';
require APP_ROOT.'/app/helpers/response.php';
$directory = sys_get_temp_dir().'/sena-report-settings-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
$checks = 0;
$check = function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$reject = function (callable $action, string $message) use ($check): void {
    try { $action(); } catch (InvalidArgumentException $error) { $check(true, $message); return; }
    throw new RuntimeException($message);
};
try {
    $store = new ReportSettings($directory.'/report.local.php');
    $check($store->load() === ReportSettings::defaults(), 'Fresh installs retain the default signature layout');
    $input = ReportSettings::defaults();
    $input['director_name'] = 'นางสาวผู้ลงนาม ทดสอบ';
    $input['director_position'] = 'ครู รักษาการในตำแหน่งผู้อำนวยการศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอทดสอบ';
    $input['recipient_title'] = 'ผู้อำนวยการศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอทดสอบ';
    $input['reporter_position'] = 'ครูชำนาญการ';
    $input['signature_label'] = 'ลงลายมือชื่อ';
    $input['date_label'] = 'ลงวันที่';
    $input['unrecognized'] = 'must not persist';
    $saved = $store->save($input);
    $check((new ReportSettings($directory.'/report.local.php'))->load() === $saved, 'Values persist across instances');
    $check(!isset($saved['unrecognized']), 'Only supported settings persist');
    $check((fileperms($directory.'/report.local.php') & 0777) === 0600, 'Configuration permissions are private');
    $reject(fn() => $store->save(array_replace($input, ['director_name'=>str_repeat('ก',151)])), 'Reject overlong Unicode names');
    $reject(fn() => $store->save(array_replace($input, ['reporter_name'=>['bad']])), 'Reject array text fields');
    $reject(fn() => $store->save(array_replace($input, ['show_director'=>['bad']])), 'Reject array checkbox fields');
    $check($store->load() === $saved, 'Rejected writes preserve the previous configuration');
    $unchecked = $input;
    foreach (ReportSettings::CHECKBOXES as $key=>$label) unset($unchecked[$key]);
    $check(!array_filter(array_intersect_key($store->save($unchecked), ReportSettings::CHECKBOXES)), 'Unchecked options persist as false');
    $visit = ['firstname'=>'ผู้เรียน','lastname'=>'ทดสอบ','prefix'=>'นางสาว','student_code'=>'TEST001',
        'group_teacher_name'=>'นายครู เจ้าของบันทึก','term_name'=>'2/2569','visit_date'=>'2026-11-08',
        'center_name'=>'ศกร. ทดสอบ','education_level'=>'upper_secondary','risk_level'=>'low',
        'status'=>'completed','visit_type'=>'home_visit','photos'=>[],'answers'=>[],
        'summary'=>'ทดสอบรายงานโดยไม่ใช้ข้อมูลจริง','teacher_recommendation'=>'ติดตามการเรียนอย่างต่อเนื่อง'];
    $html = VisitReportPdf::html($visit, null, $saved);
    $check(str_contains($html, $saved['director_name']) && str_contains($html, $saved['director_position']), 'Individual reports use the saved director and position');
    $check(str_contains($html, 'นายครู เจ้าของบันทึก') && str_contains($html, 'ครูชำนาญการ'), 'Blank reporter uses the actual teacher and saved position');
    $override = array_replace($saved, ['reporter_name'=>'นางผู้รายงาน เฉพาะกิจ']);
    $check(str_contains(VisitReportPdf::html($visit, null, $override), '(นางผู้รายงาน เฉพาะกิจ)'), 'Named reporter overrides the signature only');
    $unsafe = array_replace($saved, ['director_name'=>"<script>alert('x')</script> & test"]);
    $escaped = VisitReportPdf::html($visit, null, $unsafe);
    $check(!str_contains($escaped, '<script>') && str_contains($escaped, '&lt;script&gt;'), 'Signature output escapes HTML');
    $store->save($unsafe);
    $check($store->load()['director_name'] === $unsafe['director_name'], 'Quoted content round-trips without executing PHP');
    $hidden = array_replace($saved, ['show_director'=>false,'show_date'=>false]);
    $html = VisitReportPdf::html($visit, null, $hidden);
    $check(!str_contains($html, $saved['director_name']) && !str_contains($html, 'ลงวันที่ ..........'), 'Hidden director and dates are omitted');
    $hidden['show_reporter'] = false;
    $check(!str_contains(VisitReportPdf::html($visit, null, $hidden), 'การลงนามรับรองรายงาน'), 'No empty signature section when both signers are hidden');
    $check(!str_contains(VisitReportPdf::html($visit, '', $saved), $saved['director_name']), 'Explicit blank per-report director override is respected');
    $report = ['teacher'=>['prefix'=>'นาย','firstname'=>'ครู','lastname'=>'เจ้าของบันทึก','organization_name'=>'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอทดสอบ'],
        'term'=>['term_name'=>'2/2569'],'assigned'=>1,'visitedStudents'=>1,'highRisk'=>0,'photoCount'=>0,'visits'=>[$visit]];
    $individual = (new VisitReportPdf())->pdf($visit, $saved);
    $teacher = (new TeacherVisitReport(new PDO('sqlite::memory:')))->pdf($report, null, $saved);
    $check(str_starts_with($individual,'%PDF-') && strlen($individual)>10000, 'Individual PDF renders');
    $check(str_starts_with($teacher,'%PDF-') && strlen($teacher)>10000, 'Teacher PDF and included visit render');
    // Optional destination retains only synthetic artifacts for visual QA.
    if (isset($argv[1])) {
        if (!is_dir($argv[1])) mkdir($argv[1],0700,true);
        file_put_contents($argv[1].'/individual.pdf', $individual);
        file_put_contents($argv[1].'/teacher.pdf', $teacher);
    }
    echo "Passed {$checks} report settings checks (no MySQL).\n";
} finally {
    foreach (glob($directory.'/*') as $file) unlink($file);
    rmdir($directory);
}
