<?php
/**
 * ============================================================
 * LearningCenter Model (ศกร.ระดับตำบล)
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class LearningCenter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT lc.*, o.organization_name FROM learning_centers lc JOIN organizations o ON lc.organization_id = o.id WHERE lc.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(?int $orgId = null): array
    {
        $sql = "SELECT lc.*, o.organization_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.learning_center_id = lc.id AND e.enrollment_status = 'active') as student_count,
                (SELECT COUNT(DISTINCT t.id) FROM users t
                 WHERE t.role = 'teacher' AND t.status = 'active'
                   AND EXISTS (
                       SELECT 1 FROM enrollments e
                       WHERE e.learning_center_id = lc.id AND e.enrollment_status = 'active'
                         AND (e.teacher_id = t.id OR EXISTS (
                             SELECT 1 FROM sdl_enrollment_teachers st
                             WHERE st.enrollment_id = e.id AND st.teacher_id = t.id
                         ))
                   )) as teacher_count,
                (SELECT GROUP_CONCAT(DISTINCT CONCAT(t.firstname, ' ', t.lastname)
                        ORDER BY CONCAT(t.firstname, ' ', t.lastname) SEPARATOR ', ')
                 FROM users t
                 WHERE t.role = 'teacher' AND t.status = 'active'
                   AND EXISTS (
                       SELECT 1 FROM enrollments e
                       WHERE e.learning_center_id = lc.id AND e.enrollment_status = 'active'
                         AND (e.teacher_id = t.id OR EXISTS (
                             SELECT 1 FROM sdl_enrollment_teachers st
                             WHERE st.enrollment_id = e.id AND st.teacher_id = t.id
                         ))
                   )) as teacher_names
                FROM learning_centers lc
                JOIN organizations o ON lc.organization_id = o.id";
        $params = [];
        if ($orgId) {
            $sql .= " WHERE lc.organization_id = ?";
            $params[] = $orgId;
        }
        $sql .= " ORDER BY lc.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM learning_centers WHERE status = 'active' ORDER BY center_name ASC");
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO learning_centers (organization_id, center_code, center_name, subdistrict, district, province, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['organization_id'] ?? 1,
            $data['center_code'] ?? null,
            $data['center_name'],
            $data['subdistrict'] ?? null,
            $data['district'] ?? null,
            $data['province'] ?? null,
            $data['phone'] ?? null,
            $data['status'] ?? 'active'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update the activity windows (home visit & individual survey) of one center.
     */
    public function updateWindows(int $centerId, array $d): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE learning_centers
             SET visit_enabled = ?, visit_open_at = ?, visit_close_at = ?,
                 survey_enabled = ?, survey_open_at = ?, survey_close_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            !empty($d['visit_enabled']) ? 1 : 0,
            self::normalizeDateTime($d['visit_open_at'] ?? null),
            self::normalizeDateTime($d['visit_close_at'] ?? null),
            !empty($d['survey_enabled']) ? 1 : 0,
            self::normalizeDateTime($d['survey_open_at'] ?? null),
            self::normalizeDateTime($d['survey_close_at'] ?? null),
            $centerId,
        ]);
    }

    /** Enable/disable a window type for every center at once. */
    public function setEnabledAll(string $type, bool $enabled): void
    {
        $column = $type === 'survey' ? 'survey_enabled' : 'visit_enabled';
        $this->pdo->exec('UPDATE learning_centers SET ' . $column . ' = ' . ($enabled ? '1' : '0'));
    }

    /**
     * Apply the same date/time window to every center (district "set all").
     * Only the provided (non-empty) columns are changed; when a type has a
     * schedule it is also enabled.
     */
    public function applyScheduleAll(array $d): void
    {
        $set = [];
        $params = [];
        foreach (['visit_open_at', 'visit_close_at', 'survey_open_at', 'survey_close_at'] as $col) {
            if (array_key_exists($col, $d) && trim((string)$d[$col]) !== '') {
                $set[] = $col . ' = ?';
                $params[] = self::normalizeDateTime((string)$d[$col]);
            }
        }
        if (!$set) return;
        if (!empty($d['visit_open_at']) || !empty($d['visit_close_at'])) $set[] = 'visit_enabled = 1';
        if (!empty($d['survey_open_at']) || !empty($d['survey_close_at'])) $set[] = 'survey_enabled = 1';
        $this->pdo->prepare('UPDATE learning_centers SET ' . implode(', ', $set))->execute($params);
    }

    /** Convert <input type="datetime-local"> value ("2026-11-07T08:00") to MySQL DATETIME. */
    private static function normalizeDateTime(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
