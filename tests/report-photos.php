<?php
// All images, PDF cache and paths live in a disposable root; no database or user uploads.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$sourceRoot = dirname(__DIR__);
define('APP_ROOT', sys_get_temp_dir().'/sena-report-photos-'.bin2hex(random_bytes(6)));
mkdir(APP_ROOT, 0700);
$checks = 0;
$check = function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
try {
    foreach (['app','resources','vendor'] as $directory) symlink($sourceRoot.'/'.$directory, APP_ROOT.'/'.$directory);
    mkdir(APP_ROOT.'/public/uploads/visits', 0700, true);
    mkdir(APP_ROOT.'/storage', 0700);
    require APP_ROOT.'/app/services/TeacherVisitReport.php';
    require APP_ROOT.'/app/helpers/response.php';
    $signatures = ReportSettings::defaults();
    $signatures['director_name'] = 'ผู้ลงนาม ทดสอบ';
    $signatures['director_position'] = "ครู รักษาการในตำแหน่ง\nผู้อำนวยการ สกร.ระดับอำเภอเสนา";
    $definitions = ['wide'=>[1600,900,[42,108,163]], 'standard'=>[1200,900,[84,139,93]],
        'portrait'=>[900,1200,[42,108,163]], 'square'=>[900,900,[84,139,93]]];
    $hashes = [];
    foreach ($definitions as $name=>[$width,$height,$rgb]) {
        $image = imagecreatetruecolor($width,$height);
        $background = imagecolorallocate($image,...$rgb);
        $white = imagecolorallocate($image,255,255,255);
        imagefill($image,0,0,$background);
        // The centered circle and cross reveal stretching or an off-center crop visually.
        imageellipse($image,(int)($width/2),(int)($height/2),240,240,$white);
        imageline($image,0,(int)($height/2),$width,(int)($height/2),$white);
        imageline($image,(int)($width/2),0,(int)($width/2),$height,$white);
        imagestring($image,5,(int)($width/2)-30,(int)($height/2)-30,$name,$white);
        $path = APP_ROOT.'/public/uploads/visits/'.$name.'.png';
        imagepng($image,$path); imagedestroy($image);
        $hashes[$name] = hash_file('sha256',$path);
    }
    $photo = fn(string $name) => ['stored_name'=>$name.'.png','caption'=>'ภาพทดสอบ '.$name];
    $visit = ['firstname'=>'ผู้เรียน','lastname'=>'ทดสอบ','prefix'=>'นางสาว','student_code'=>'PHOTO001',
        'group_teacher_name'=>'ครู เจ้าของบันทึก','term_name'=>'2/2569','visit_date'=>'2026-11-08',
        'center_name'=>'ศกร. ทดสอบ','education_level'=>'upper_secondary','risk_level'=>'low',
        'status'=>'completed','visit_type'=>'home_visit','answers'=>[],
        'home_lat'=>'14.318116','home_lng'=>'100.412223','summary'=>'ทดสอบกรอบรูปแนวนอน',
        'teacher_recommendation'=>'ติดตามการเรียนอย่างต่อเนื่อง'];
    $pdfs = [];
    foreach (['landscape'=>['wide','standard'], 'mixed'=>['portrait','square'],
        'single'=>['wide'], 'odd'=>['wide','standard','portrait']] as $name=>$images) {
        $record = $visit + ['photos'=>array_map($photo,$images)];
        $html = VisitReportPdf::html($record,null,$signatures);
        $check(!str_contains($html,'ไม่พบไฟล์รูปภาพแนบ'), $name.': valid photos render');
        $pdfs[$name] = (new VisitReportPdf())->pdf($record,$signatures);
        $check(str_starts_with($pdfs[$name],'%PDF-'), $name.': PDF renders');
        if (count($images)<=2) $check(preg_match_all('~/Type\s*/Page\b~',$pdfs[$name])===2, $name.': photos, QR and signatures fit two pages');
    }
    $report = ['teacher'=>['prefix'=>'นาย','firstname'=>'ครู','lastname'=>'ทดสอบ','organization_name'=>'ศูนย์ทดสอบ'],
        'term'=>['term_name'=>'2/2569'],'assigned'=>1,'visitedStudents'=>1,'highRisk'=>0,'photoCount'=>2,
        'visits'=>[$visit+['photos'=>[$photo('wide'),$photo('standard')]]]];
    $pdfs['teacher'] = (new TeacherVisitReport(new PDO('sqlite::memory:')))->pdf($report,null,$signatures);
    $check(preg_match_all('~/Type\s*/Page\b~',$pdfs['teacher'])===3, 'Teacher report uses the same two-page photo layout');
    $missing = $visit+['photos'=>[$photo('absent')]];
    $check(str_contains(VisitReportPdf::html($missing,null,$signatures),'ไม่พบไฟล์รูปภาพแนบ'), 'Missing files retain the existing placeholder');
    foreach ($hashes as $name=>$hash) $check(hash_file('sha256',APP_ROOT.'/public/uploads/visits/'.$name.'.png')===$hash, $name.': original remains unchanged');
    if (isset($argv[1])) {
        if (!is_dir($argv[1])) mkdir($argv[1],0700,true);
        foreach ($pdfs as $name=>$bytes) file_put_contents($argv[1].'/'.$name.'.pdf',$bytes);
    }
    echo "Passed {$checks} report photo checks (temporary images only).\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isLink() || $file->isFile()) unlink($file->getPathname());
        else rmdir($file->getPathname());
    }
    rmdir(APP_ROOT);
}
