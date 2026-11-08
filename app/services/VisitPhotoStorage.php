<?php
if (!defined('APP_ROOT')) { die('Direct access not permitted'); }

class VisitPhotoStorage
{
    private array $written = [];
    public function save(PDO $pdo, int $visitId, ?array $files, int $userId): void
    {
        if (!$files) return;
        $config = require APP_ROOT . '/app/config/config.php';
        $directory = APP_ROOT . '/public/uploads/visits';
        $mimeExtensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $insert = $pdo->prepare("INSERT INTO visit_files (visit_id,file_type,stored_name,original_name,file_path,mime_type,file_size,uploaded_by) VALUES (?,'image',?,?,?,?,?,?)");
        foreach ((array)($files['error'] ?? []) as $index => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ กรุณาตรวจสอบขนาดไฟล์และลองใหม่');
            $temp = $files['tmp_name'][$index];
            $size = filesize($temp);
            if ($size === false || $size > $config['max_upload_size']) throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน 10 MB ต่อไฟล์');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
            if (!isset($mimeExtensions[$mime]) || @getimagesize($temp) === false) throw new RuntimeException('กรุณาแนบรูป JPG, PNG หรือ WebP ที่เปิดอ่านได้');
            if (!is_dir($directory) && !mkdir($directory,0755,true) && !is_dir($directory)) throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์รูปภาพได้');
            $name = 'visit_'.$visitId.'_'.bin2hex(random_bytes(16)).'.'.$mimeExtensions[$mime];
            $target = $directory.'/'.$name;
            if (!move_uploaded_file($temp,$target)) throw new RuntimeException('ไม่สามารถจัดเก็บรูปภาพได้ กรุณาลองใหม่');
            $this->written[] = $target;
            $insert->execute([$visitId,$name,basename($files['name'][$index]),'public/uploads/visits/'.$name,$mime,$size,$userId]);
        }
    }
    public function rollback(): void
    {
        foreach ($this->written as $path) { if (is_file($path)) unlink($path); }
        $this->written = [];
    }
}
