<?php
/**
 * ============================================================
 * Followup Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Followup
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['f.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'f.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 'f.priority = ?';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'f.assigned_to = ?';
            $params[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['student_id'])) {
            $where[] = 'f.student_id = ?';
            $params[] = (int)$filters['student_id'];
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM followups f WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT f.*, s.student_code, s.prefix, s.firstname, s.lastname, s.phone,
                       u.firstname AS assigned_firstname, u.lastname AS assigned_lastname
                FROM followups f
                JOIN students s ON f.student_id = s.id
                LEFT JOIN users u ON f.assigned_to = u.id
                WHERE {$whereClause}
                ORDER BY CASE f.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END, f.due_date ASC
                LIMIT ? OFFSET ?";

        $dataParams = array_merge($params, [$perPage, $offset]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($dataParams);
        $data = $stmt->fetchAll();

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO followups (student_id, home_visit_id, assigned_to, title, description, priority, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['student_id'],
            $data['home_visit_id'] ?? null,
            $data['assigned_to'] ?? currentUserId(),
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'normal',
            $data['due_date'] ?? null,
            $data['status'] ?? 'pending',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $result = null): bool
    {
        $sql = "UPDATE followups SET status = ?, result = COALESCE(?, result), updated_at = NOW()";
        if ($status === 'completed') {
            $sql .= ", completed_at = NOW()";
        } else {
            $sql .= ", completed_at = NULL";
        }
        $sql .= " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $result, $id]);
    }
}
