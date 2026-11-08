<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/**
 * Single-image uploader that downsizes the image before storing it, so the
 * server does not keep full-resolution photos. Output is always JPEG.
 */
class ImageUploader
{
    /**
     * @param array|null $file   one entry of $_FILES (e.g. $_FILES['photo'])
     * @param string     $subdir folder under public/uploads (e.g. "graduates")
     * @param string     $prefix file-name prefix
     * @return string|null stored relative path ("public/uploads/...") or null when no file
     */
    public static function saveOne(?array $file, string $subdir, string $prefix): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ กรุณาตรวจสอบขนาดไฟล์และลองใหม่');
        }

        $config = require APP_ROOT . '/app/config/config.php';
        $maxWidth = (int)($config['graduate_image_max_width'] ?? 1000);
        $quality = (int)($config['graduate_image_quality'] ?? 70);

        if ((int)($file['size'] ?? 0) > $config['max_upload_size']) {
            throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน ' . round($config['max_upload_size'] / 1048576) . ' MB');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('กรุณาแนบรูป JPG, PNG หรือ WebP ที่เปิดอ่านได้');
        }
        if (!extension_loaded('gd')) {
            throw new RuntimeException('เซิร์ฟเวอร์ไม่รองรับการย่อรูปภาพ');
        }

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => imagecreatefrompng($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
        };
        if (!$source) {
            throw new RuntimeException('ไม่สามารถอ่านรูปภาพได้');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Downsize only when wider than the limit; always re-encode to JPEG.
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = max(1, (int)round($height * $maxWidth / $width));
            $target = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $white);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $target;
        }

        $directory = APP_ROOT . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์รูปภาพได้');
        }

        $name = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $fullPath = $directory . '/' . $name;
        if (!imagejpeg($source, $fullPath, $quality)) {
            imagedestroy($source);
            throw new RuntimeException('ไม่สามารถบันทึกรูปภาพได้');
        }
        imagedestroy($source);

        return 'public/uploads/' . trim($subdir, '/') . '/' . $name;
    }
}
