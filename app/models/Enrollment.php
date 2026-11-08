<?php
/**
 * ============================================================
 * Enrollment Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Enrollment
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByStudentAndTerm(int $studentId, int $termId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND academic_term_id = ? LIMIT 1");
        $stmt->execute([$studentId, $termId]);
        return $stmt->fetch() ?: null;
    }

    public function getStudentHistory(int $studentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, at.term_name, at.academic_year, at.term_no, lc.center_name,
                    u.firstname AS teacher_firstname, u.lastname AS teacher_lastname,
                    (SELECT COUNT(*) FROM home_visits hv WHERE hv.enrollment_id = e.id AND hv.status = 'completed') as visit_count
             FROM enrollments e
             JOIN academic_terms at ON e.academic_term_id = at.id
             LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
             LEFT JOIN users u ON e.teacher_id = u.id
             WHERE e.student_id = ?
             ORDER BY at.academic_year DESC, at.term_no DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function saveOrUpdate(int $studentId, int $termId, array $data): int
    {
        $existing = $this->findByStudentAndTerm($studentId, $termId);
        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE enrollments SET
                 education_level = ?, learning_center_id = ?, teacher_id = ?, enrollment_status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['education_level'] ?? 'lower_secondary',
                $data['learning_center_id'] ?? null,
                $data['teacher_id'] ?? null,
                $data['enrollment_status'] ?? 'active',
                $existing['id']
            ]);
            return (int)$existing['id'];
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO enrollments (student_id, academic_term_id, education_level, learning_center_id, teacher_id, enrollment_status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $studentId,
                $termId,
                $data['education_level'] ?? 'lower_secondary',
                $data['learning_center_id'] ?? null,
                $data['teacher_id'] ?? null,
                $data['enrollment_status'] ?? 'active'
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }
}
