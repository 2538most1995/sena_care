<?php
/**
 * ============================================================
 * VisitPlan Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT.'/app/models/TeacherAssignment.php';

class VisitPlan
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['teacher_id'])) {
            $where[] = 'vp.teacher_id = ?';
            $params[] = (int)$filters['teacher_id'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'vp.visit_date = ?';
            $params[] = $filters['date'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'vp.status = ?';
            $params[] = $filters['status'];
        }

        $teacherName=TeacherAssignment::nameSql();
        $sql = "SELECT {$teacherName} AS group_teacher_name,vp.*, s.student_code, s.prefix, s.firstname, s.lastname, s.phone,
                       sa.house_no, sa.moo, sa.subdistrict, sa.latitude, sa.longitude,
                       u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
                FROM visit_plans vp
                JOIN students s ON vp.student_id = s.id
                LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.address_type = 'home'
                LEFT JOIN enrollments e ON e.id=vp.enrollment_id
                JOIN users u ON vp.teacher_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY vp.visit_date ASC, vp.visit_time ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO visit_plans (student_id, enrollment_id, teacher_id, visit_date, visit_time, purpose, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['student_id'],
            $data['enrollment_id'] ?? null,
            $data['teacher_id'],
            $data['visit_date'],
            $data['visit_time'] ?? null,
            $data['purpose'] ?? 'เยี่ยมบ้านเพื่อติดตามการเรียนและความเป็นอยู่',
            $data['status'] ?? 'planned'
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
