<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/** Local report configuration; no database migration or source-control secrets. */
class ReportSettings
{
    public const TEXT_FIELDS = [
        'director_name' => ['ชื่อ–นามสกุลผู้อำนวยการ / ผู้รับทราบ', 150],
        'director_position' => ['ตำแหน่งผู้อำนวยการ / ผู้รับทราบ', 250],
        'recipient_title' => ['ข้อความเรียน (ผู้รับรายงาน)', 250],
        'director_label' => ['ข้อความกำกับผู้รับทราบ', 100],
        'reporter_name' => ['ชื่อ–นามสกุลผู้รายงาน', 150],
        'reporter_position' => ['ตำแหน่งผู้รายงาน', 250],
        'reporter_label' => ['ข้อความกำกับผู้รายงาน', 100],
        'signature_label' => ['ข้อความหน้าช่องลงนาม', 60],
        'date_label' => ['ข้อความหน้าช่องวันที่', 60],
    ];
    public const CHECKBOXES = [
        'show_reporter' => 'แสดงช่องลงนามผู้รายงาน',
        'show_director' => 'แสดงช่องลงนามผู้อำนวยการ / ผู้รับทราบ',
        'show_date' => 'แสดงช่องวันที่ใต้ลายมือชื่อ',
    ];

    public function __construct(private ?string $path = null)
    {
        $this->path ??= APP_ROOT . '/app/config/report.local.php';
    }

    public static function defaults(): array
    {
        return ['director_name'=>'', 'director_position'=>'ผู้อำนวยการ', 'recipient_title'=>'ผู้อำนวยการ',
            'director_label'=>'ผู้รับทราบรายงาน', 'reporter_name'=>'', 'reporter_position'=>'ครูผู้เยี่ยม / ครูประจำกลุ่ม',
            'reporter_label'=>'ผู้รายงาน', 'signature_label'=>'ลงชื่อ', 'date_label'=>'วันที่',
            'show_reporter'=>true, 'show_director'=>true, 'show_date'=>true];
    }

    /** Keep explicit line breaks; split legacy acting-director text for the signature. */
    public static function directorPositionForReport(string $position): string
    {
        $position = str_replace(["\r\n", "\r"], "\n", $position);
        if (str_contains($position, "\n")) return $position;
        return preg_replace('/(รักษาการ(?:ในตำแหน่ง)?|รักษาราชการแทน)\h*(?=ผู้อำนวยการ)/u', "$1\n", $position, 1);
    }

    public function load(): array
    {
        if (!is_file($this->path)) return self::defaults();
        $values = require $this->path;
        if (!is_array($values)) throw new RuntimeException('ไฟล์ตั้งค่ารายงานไม่ถูกต้อง');
        return $this->normalize(array_replace(self::defaults(), $values));
    }

    public function normalize(array $input): array
    {
        $values = self::defaults();
        foreach (self::TEXT_FIELDS as $key => [$label, $max]) {
            $value = $input[$key] ?? $values[$key];
            if (!is_string($value)) throw new InvalidArgumentException($label . ' ต้องเป็นข้อความ');
            $value = trim($value);
            if (in_array($key, ['director_position', 'reporter_position'], true)) $value = str_replace(["\r\n", "\r"], "\n", $value);
            if (mb_strlen($value) > $max) throw new InvalidArgumentException($label . ' ต้องไม่เกิน ' . $max . ' ตัวอักษร');
            $values[$key] = $value;
        }
        foreach (self::CHECKBOXES as $key => $label) {
            if (isset($input[$key]) && !in_array($input[$key], [true,false,1,0,'1','0'], true)) throw new InvalidArgumentException($label . ' ไม่ถูกต้อง');
            $values[$key] = in_array($input[$key] ?? false, [true,1,'1'], true);
        }
        return $values;
    }

    public function save(array $input): array
    {
        $values = $this->normalize($input);
        $directory = dirname($this->path);
        if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('ไม่สามารถเขียนไฟล์ตั้งค่ารายงานได้');
        $temp = tempnam($directory, '.report-');
        if ($temp === false) throw new RuntimeException('ไม่สามารถสร้างไฟล์ตั้งค่ารายงานได้');
        try {
            chmod($temp, 0600);
            $content = "<?php\nif (!defined('APP_ROOT')) die('Direct access not permitted');\nreturn " . var_export($values, true) . ";\n";
            if (file_put_contents($temp, $content, LOCK_EX) === false || !rename($temp, $this->path)) throw new RuntimeException('ไม่สามารถบันทึกไฟล์ตั้งค่ารายงานได้');
            if (function_exists('opcache_invalidate')) opcache_invalidate($this->path, true);
        } finally { if (is_file($temp)) unlink($temp); }
        return $values;
    }
}
