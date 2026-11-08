<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/**
 * Graduates (ผู้สำเร็จการศึกษา) synced from SDL_school + their outcome follow-ups.
 */
class Graduate
{
    public function __construct(private PDO $pdo) {}

    /** Distinct graduation terms that have synced graduates (newest first). */
    public function terms(): array
    {
        return $this->pdo->query(
            "SELECT DISTINCT graduation_term FROM graduates
             ORDER BY CAST(SUBSTRING_INDEX(graduation_term, '/', -1) AS UNSIGNED) DESC,
                      CAST(SUBSTRING_INDEX(graduation_term, '/', 1) AS UNSIGNED) DESC"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Paginated graduate roster.
     * @param array|null $teacherGroupCodes null = no teacher restriction (admin/executive)
     */
    public function getAll(array $filters, int $page, int $perPage, ?array $teacherGroupCodes = null): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['term'])) {
            $where[] = 'g.graduation_term = ?';
            $params[] = $filters['term'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . trim($filters['search']) . '%';
            $where[] = '(g.full_name LIKE ? OR g.student_code LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['center_id'])) {
            $where[] = 'g.learning_center_id = ?';
            $params[] = (int)$filters['center_id'];
        }
        $trackingStatus = $filters['tracking_status'] ?? '';
        if ($trackingStatus === 'tracked') {
            $where[] = 'EXISTS(SELECT 1 FROM graduate_trackings gt WHERE gt.graduate_id = g.id)';
        } elseif ($trackingStatus === 'untracked') {
            $where[] = 'NOT EXISTS(SELECT 1 FROM graduate_trackings gt WHERE gt.graduate_id = g.id)';
        }

        if ($teacherGroupCodes !== null) {
            if ($teacherGroupCodes) {
                $placeholders = implode(',', array_fill(0, count($teacherGroupCodes), '?'));
                $where[] = "g.group_code IN ($placeholders)";
                foreach ($teacherGroupCodes as $code) $params[] = $code;
            } elseif (!empty($filters['teacher_center_id'])) {
                $where[] = 'g.learning_center_id = ?';
                $params[] = (int)$filters['teacher_center_id'];
            } else {
                $where[] = '1 = 0';
            }
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM graduates g WHERE ' . $whereClause);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT g.*,
                    (SELECT COUNT(*) FROM graduate_trackings gt WHERE gt.graduate_id = g.id) AS tracking_count,
                    (SELECT gt2.id FROM graduate_trackings gt2 WHERE gt2.graduate_id = g.id ORDER BY gt2.tracked_at DESC, gt2.id DESC LIMIT 1) AS latest_tracking_id,
                    (SELECT MAX(gt3.tracked_at) FROM graduate_trackings gt3 WHERE gt3.graduate_id = g.id) AS latest_tracked_at
                FROM graduates g
                WHERE ' . $whereClause . '
                ORDER BY CAST(SUBSTRING_INDEX(g.graduation_term, CHAR(47), -1) AS UNSIGNED) DESC,
                         CAST(SUBSTRING_INDEX(g.graduation_term, CHAR(47), 1) AS UNSIGNED) DESC,
                         g.group_name ASC, g.full_name ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$perPage, $offset]));

        return [
            'data'        => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT g.*, lc.center_name FROM graduates g LEFT JOIN learning_centers lc ON lc.id = g.learning_center_id WHERE g.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** All follow-up records for a graduate, newest first. */
    public function trackings(int $graduateId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT gt.*, u.firstname, u.lastname
             FROM graduate_trackings gt
             JOIN users u ON u.id = gt.tracked_by
             WHERE gt.graduate_id = ?
             ORDER BY gt.tracked_at DESC, gt.id DESC'
        );
        $stmt->execute([$graduateId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['categories'] = json_decode($row['categories'] ?? '[]', true) ?: [];
        }
        return $rows;
    }

    /** Append an outcome follow-up record. */
    public function saveTracking(int $graduateId, int $userId, array $categories, string $detail, ?string $imagePath): int
    {
        $json = json_encode(array_values(array_unique($categories)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $stmt = $this->pdo->prepare(
            'INSERT INTO graduate_trackings(graduate_id, categories, other_detail, image_path, tracked_by)
             VALUES(?, ?, ?, ?, ?)'
        );
        $stmt->execute([$graduateId, $json, $detail !== '' ? $detail : null, $imagePath, $userId]);
        return (int)$this->pdo->lastInsertId();
    }

    /** One tracking record that belongs to the given graduate. */
    public function findTracking(int $trackingId, int $graduateId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM graduate_trackings WHERE id = ? AND graduate_id = ?');
        $stmt->execute([$trackingId, $graduateId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['categories'] = json_decode($row['categories'] ?? '[]', true) ?: [];
        return $row;
    }

    /** Update an existing tracking record. */
    public function updateTracking(int $trackingId, int $graduateId, array $categories, string $detail, ?string $imagePath): bool
    {
        $json = json_encode(array_values(array_unique($categories)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $stmt = $this->pdo->prepare(
            'UPDATE graduate_trackings
             SET categories = ?, other_detail = ?, image_path = ?
             WHERE id = ? AND graduate_id = ?'
        );
        $stmt->execute([$json, $detail !== '' ? $detail : null, $imagePath, $trackingId, $graduateId]);
        return $stmt->rowCount() > 0;
    }
}
