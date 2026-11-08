<?php
// Run only over the hosting terminal; never expose a web installer.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=realpath($argv[1] ?? '');
if (!$root || !is_file($root.'/laravel-app/artisan')) { fwrite(STDERR,"Usage: php install.php /absolute/path/to/SDL_school\n"); exit(1); }
$patches=[
 'laravel-app/app/Http/Middleware/AuthenticateStudentDataClient.php'=>[
  ["        \$request->attributes->set('student_api_client_id', (int) \$client->id);", "        \$request->attributes->set('student_api_client_id', (int) \$client->id);\n        \$request->attributes->set('student_api_client', \$client);", "        \$request->attributes->set('student_api_client', \$client);"],
 ],
 'laravel-app/app/Console/Commands/CreateStudentDataApiToken.php'=>[
  ["        {--replace :", "        {--sena-care : อนุญาตอ่านรายชื่อครูและตรวจสอบรหัสผ่านครูสำหรับ SENA Care}\n        {--replace :", '{--sena-care :'],
  ["'abilities' => ['student-data:read'],", "'abilities' => \$this->option('sena-care')\n                    ? ['student-data:read', 'sena-care:teachers', 'sena-care:authenticate']\n                    : ['student-data:read'],", "\$this->option('sena-care')"],
 ],
 'laravel-app/routes/api.php'=>[
  ['use App\\Http\\Controllers\\Api\\Integrations\\StudentDataController;', "use App\\Http\\Controllers\\Api\\Integrations\\SenaCareTeacherController;\nuse App\\Http\\Controllers\\Api\\Integrations\\StudentDataController;", 'use App\\Http\\Controllers\\Api\\Integrations\\SenaCareTeacherController;'],
  ["        Route::get('/students', [StudentDataController::class, 'index']);", "        Route::get('/teachers', [SenaCareTeacherController::class, 'index']);\n        Route::get('/groups', [SenaCareTeacherController::class, 'groups']);\n        Route::post('/teacher-authenticate', [SenaCareTeacherController::class, 'authenticate']);\n        Route::get('/students', [StudentDataController::class, 'index']);", "Route::post('/teacher-authenticate', [SenaCareTeacherController::class, 'authenticate'])"],
 ],
];
$planned=[];
foreach ($patches as $relative=>$changes) {
 $file=$root.'/'.$relative;
 $original=file_get_contents($file);
 if ($original===false) throw new RuntimeException('Cannot read '.$relative);
 $new=$original;
 foreach ($changes as [$before,$after,$marker]) {
  if (str_contains($new,$marker)) continue;
  if (substr_count($new,$before)!==1) throw new RuntimeException('Source differs; no files changed. Review '.$relative);
  $new=str_replace($before,$after,$new);
 }
 if ($new!==$original) $planned[$relative]=[$original,$new];
}
$controller='laravel-app/app/Http/Controllers/Api/Integrations/SenaCareTeacherController.php';
$payload=file_get_contents(__DIR__.'/'.$controller);
if ($payload===false) throw new RuntimeException('Missing controller payload');
if (is_file($root.'/'.$controller)) {
 if (file_get_contents($root.'/'.$controller)!==$payload) throw new RuntimeException('Existing controller differs; review before installation');
} else $planned[$controller]=[null,$payload];
$backup=dirname($root,2).'/sena-private-backups/sdl-care-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if ($planned && !mkdir($backup,0700,true)) throw new RuntimeException('Cannot create private backup');
$written=[];
try {
 foreach ($planned as $relative=>[$original,$new]) {
  $target=$root.'/'.$relative;
  if ($original!==null) {
   $saved=$backup.'/'.$relative;
   if (!is_dir(dirname($saved))) mkdir(dirname($saved),0700,true);
   if (file_put_contents($saved,$original)===false) throw new RuntimeException('Backup failed');
   chmod($saved,0600);
  }
  $tmp=tempnam(dirname($target),'.sena-care-');
  if ($tmp===false || file_put_contents($tmp,$new)===false) throw new RuntimeException('Write failed');
  chmod($tmp,is_file($target)?(fileperms($target)&0777):0644);
  if (!rename($tmp,$target)) throw new RuntimeException('Atomic update failed');
  $written[]=$relative;
 }
} catch (Throwable $error) {
 foreach (array_reverse($written) as $relative) {
  [$original]=$planned[$relative];
  if ($original===null) unlink($root.'/'.$relative); else file_put_contents($root.'/'.$relative,$original);
 }
 throw $error;
}
echo count($written)." files installed. Private backup: ".$backup.PHP_EOL;
echo "Next: cd ".escapeshellarg($root.'/laravel-app')." && php artisan route:clear".PHP_EOL;
echo "Then create a district-scoped API token with system:create-student-api-token --sena-care.".PHP_EOL;
