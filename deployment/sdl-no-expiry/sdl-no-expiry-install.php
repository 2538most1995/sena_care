<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=realpath($argv[1] ?? '');
if (!$root || !is_file($root.'/laravel-app/artisan')) throw new RuntimeException('Pass the absolute SDL_school project path');
$payload=__DIR__.'/sena-care-no-expiry-payload';
$manifest=json_decode(file_get_contents($payload.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$changes=[];
foreach($manifest as $file=>$hashes){
 $current=is_file($root.'/'.$file)?file_get_contents($root.'/'.$file):null;
 if ($current!==null && hash('sha256',$current)===$hashes['after']) continue;
 if (($current===null)!==($hashes['before']===null) || ($current!==null && hash('sha256',$current)!==$hashes['before'])) throw new RuntimeException('Source changed; no files modified. Review '.$file);
 $new=file_get_contents($payload.'/'.$file);
 if(hash('sha256',$new)!==$hashes['after'])throw new RuntimeException('Payload checksum mismatch');
 $changes[$file]=[$current,$new];
}
if (in_array('--check',$argv,true)){echo 'Source checks passed for '.count($manifest)." files.\n";exit;}
$backup=dirname($root,2).'/sena-private-backups/sdl-no-expiry-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($backup,0700,true))throw new RuntimeException('Backup creation failed');
foreach($changes as $file=>[$old,$new]){
 $target=$root.'/'.$file;
 if($old!==null){$saved=$backup.'/'.$file;if(!is_dir(dirname($saved)))mkdir(dirname($saved),0700,true);if(file_put_contents($saved,$old)===false)throw new RuntimeException('Backup write failed');chmod($saved,0600);}
 $temp=tempnam(dirname($target),'.sdl-no-expiry-');
 if($temp===false || file_put_contents($temp,$new)===false)throw new RuntimeException('File write failed');
 chmod($temp,is_file($target)?(fileperms($target)&0777):0644);
 if(!rename($temp,$target))throw new RuntimeException('File replacement failed');
}
require $root.'/laravel-app/vendor/autoload.php';
$app=require $root.'/laravel-app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$status=Illuminate\Support\Facades\Artisan::call('migrate',['--path'=>'database/migrations/2026_10_19_000036_allow_non_expiring_student_api_clients.php','--force'=>true]);
if($status!==0)throw new RuntimeException('Migration failed; token expiry has not been changed');
$district=App\Models\District::query()->where('code','sena')->where('is_active',true)->firstOrFail();
$result=Illuminate\Support\Facades\DB::transaction(function()use($district,$backup){
 $clients=App\Models\StudentApiClient::query()->where('name','sena-care')->where('district_id',$district->id)->whereNull('revoked_at')->lockForUpdate()->get();
 if($clients->count()!==1)throw new RuntimeException('Expected exactly one active SENA Care client');
 $client=$clients->first();
 $previous=['id'=>$client->id,'name'=>$client->name,'district_id'=>$client->district_id,'expires_at'=>$client->expires_at?->toIso8601String()];
 if(file_put_contents($backup.'/previous-expiry.json',json_encode($previous,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Expiry backup failed');
 chmod($backup.'/previous-expiry.json',0600);
 $client->forceFill(['expires_at'=>null])->save();
 return ['client_id'=>$client->id,'district_code'=>'sena','expires_at'=>$client->fresh()->expires_at,'revoked'=>$client->revoked_at!==null];
});
echo json_encode(['success'=>true,'updated'=>$result,'backup'=>$backup],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
