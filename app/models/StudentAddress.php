<?php
/**
 * ============================================================
 * StudentAddress Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class StudentAddress
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByStudentId(int $studentId, string $type = 'home'): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM student_addresses WHERE student_id = ? AND address_type = ? LIMIT 1");
        $stmt->execute([$studentId, $type]);
        return $stmt->fetch() ?: null;
    }

    public function saveOrUpdate(int $studentId, array $data, string $type = 'home'): bool
    {
        $existing = $this->findByStudentId($studentId, $type);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE student_addresses SET
                 house_no = ?, moo = ?, village = ?, road = ?, subdistrict = ?, district = ?, province = ?, zipcode = ?, latitude = ?, longitude = ?, landmark = ?, note = ?
                 WHERE id = ?"
            );
            return $stmt->execute([
                $data['house_no'] ?? null,
                $data['moo'] ?? null,
                $data['village'] ?? null,
                $data['road'] ?? null,
                $data['subdistrict'] ?? null,
                $data['district'] ?? null,
                $data['province'] ?? null,
                $data['zipcode'] ?? null,
                !empty($data['latitude']) ? (float)$data['latitude'] : null,
                !empty($data['longitude']) ? (float)$data['longitude'] : null,
                $data['landmark'] ?? null,
                $data['note'] ?? null,
                $existing['id']
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO student_addresses (student_id, address_type, house_no, moo, village, road, subdistrict, district, province, zipcode, latitude, longitude, landmark, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([
                $studentId,
                $type,
                $data['house_no'] ?? null,
                $data['moo'] ?? null,
                $data['village'] ?? null,
                $data['road'] ?? null,
                $data['subdistrict'] ?? null,
                $data['district'] ?? null,
                $data['province'] ?? null,
                $data['zipcode'] ?? null,
                !empty($data['latitude']) ? (float)$data['latitude'] : null,
                !empty($data['longitude']) ? (float)$data['longitude'] : null,
                $data['landmark'] ?? null,
                $data['note'] ?? null,
            ]);
        }
    }
}
