<?php
/**
 * ============================================================
 * Validation Helper Functions
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get trimmed value or null
     */
    public function get(string $key, $default = null)
    {
        $value = $this->data[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Get integer value
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int)($this->data[$key] ?? $default);
    }

    /**
     * Validate required field
     */
    public function required(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value === null || $value === '') {
            $this->errors[$field] = ($label ?: $field) . ' จำเป็นต้องกรอก';
        }
        return $this;
    }

    /**
     * Validate string max length
     */
    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && mb_strlen($value) > $max) {
            $this->errors[$field] = ($label ?: $field) . " ต้องไม่เกิน {$max} ตัวอักษร";
        }
        return $this;
    }

    /**
     * Validate string min length
     */
    public function minLength(string $field, int $min, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = ($label ?: $field) . " ต้องมีอย่างน้อย {$min} ตัวอักษร";
        }
        return $this;
    }

    /**
     * Validate email
     */
    public function email(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = ($label ?: $field) . ' รูปแบบอีเมลไม่ถูกต้อง';
        }
        return $this;
    }

    /**
     * Validate phone number (Thai format)
     */
    public function phone(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $clean = preg_replace('/[^0-9]/', '', $value);
            if (strlen($clean) < 9 || strlen($clean) > 10) {
                $this->errors[$field] = ($label ?: $field) . ' รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
            }
        }
        return $this;
    }

    /**
     * Validate citizen ID (13 digits)
     */
    public function citizenId(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $clean = preg_replace('/[^0-9]/', '', $value);
            if (strlen($clean) !== 13) {
                $this->errors[$field] = ($label ?: $field) . ' ต้องเป็นตัวเลข 13 หลัก';
                return $this;
            }
            // Verify checksum
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int)$clean[$i] * (13 - $i);
            }
            $check = (11 - ($sum % 11)) % 10;
            if ($check !== (int)$clean[12]) {
                $this->errors[$field] = ($label ?: $field) . ' เลขบัตรประชาชนไม่ถูกต้อง';
            }
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$field] = ($label ?: $field) . ' รูปแบบวันที่ไม่ถูกต้อง';
            }
        }
        return $this;
    }

    /**
     * Validate value is in a list
     */
    public function in(string $field, array $allowed, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = ($label ?: $field) . ' ค่าไม่ถูกต้อง';
        }
        return $this;
    }

    /**
     * Validate numeric
     */
    public function numeric(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = ($label ?: $field) . ' ต้องเป็นตัวเลข';
        }
        return $this;
    }

    /**
     * Validate latitude
     */
    public function latitude(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $val = (float)$value;
            if ($val < -90 || $val > 90) {
                $this->errors[$field] = ($label ?: $field) . ' ค่า Latitude ไม่ถูกต้อง';
            }
        }
        return $this;
    }

    /**
     * Validate longitude
     */
    public function longitude(string $field, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $val = (float)$value;
            if ($val < -180 || $val > 180) {
                $this->errors[$field] = ($label ?: $field) . ' ค่า Longitude ไม่ถูกต้อง';
            }
        }
        return $this;
    }

    /**
     * Validate unique in database
     */
    public function unique(string $field, string $table, string $column, ?int $excludeId = null, string $label = ''): self
    {
        $value = $this->get($field);
        if ($value !== null && $value !== '') {
            $pdo = Database::getConnection();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn() > 0) {
                $this->errors[$field] = ($label ?: $field) . ' มีอยู่ในระบบแล้ว';
            }
        }
        return $this;
    }

    /**
     * Add custom error
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): string
    {
        return !empty($this->errors) ? reset($this->errors) : '';
    }

    /**
     * Sanitize input - remove HTML tags, trim
     */
    public static function sanitize(?string $value): ?string
    {
        if ($value === null) return null;
        return trim(strip_tags($value));
    }

    /**
     * Sanitize all input array
     */
    public static function sanitizeAll(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (is_array($value)) {
                $clean[$key] = self::sanitizeAll($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}
