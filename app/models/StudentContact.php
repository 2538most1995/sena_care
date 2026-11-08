<?php
/**
 * ============================================================
 * StudentContact Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class StudentContact
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByStudentId(int $studentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM student_contacts WHERE student_id = ? ORDER BY is_primary DESC, id ASC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function getPrimary(int $studentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM student_contacts WHERE student_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }

    public function savePrimary(int $studentId, array $data): bool
    {
        $primary = $this->getPrimary($studentId);
        if ($primary) {
            $stmt = $this->pdo->prepare("UPDATE student_contacts SET name = ?, relationship = ?, phone = ?, occupation = ? WHERE id = ?");
            return $stmt->execute([
                $data['parent_name'] ?? $data['name'] ?? '',
                $data['parent_relation'] ?? $data['relationship'] ?? 'ผู้ปกครอง',
                $data['parent_phone'] ?? $data['phone'] ?? null,
                $data['parent_occupation'] ?? $data['occupation'] ?? null,
                $primary['id']
            ]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO student_contacts (student_id, name, relationship, phone, occupation, is_primary) VALUES (?, ?, ?, ?, ?, 1)");
            return $stmt->execute([
                $studentId,
                $data['parent_name'] ?? $data['name'] ?? '',
                $data['parent_relation'] ?? $data['relationship'] ?? 'ผู้ปกครอง',
                $data['parent_phone'] ?? $data['phone'] ?? null,
                $data['parent_occupation'] ?? $data['occupation'] ?? null
            ]);
        }
    }
}
