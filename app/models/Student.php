<?php
/**
 * ============================================================
 * Student Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT.'/app/models/TeacherAssignment.php';

class Student
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Find student by ID with address, current enrollment, and risk info
     */
    public function findById(int $id, ?int $termId = null): ?array
    {
        $sql = "SELECT s.*, 
                       sa.house_no, sa.moo, sa.village, sa.road, sa.subdistrict, sa.district, sa.province, sa.zipcode, sa.latitude, sa.longitude, sa.landmark,
                       e.id AS enrollment_id, e.academic_term_id, e.education_level, e.learning_center_id, e.teacher_id, e.enrollment_status,
                       lc.center_name,
                       u.prefix AS teacher_prefix, u.firstname AS teacher_firstname, u.lastname AS teacher_lastname,
                       at.term_name,
                       sr.risk_level, sr.total_score AS risk_score, sr.risk_factors
                FROM students s
                LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.address_type = 'home'
                LEFT JOIN enrollments e ON s.id = e.student_id " . ($termId ? "AND e.academic_term_id = " . (int)$termId : "") . "
                LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
                LEFT JOIN users u ON e.teacher_id = u.id
                LEFT JOIN academic_terms at ON e.academic_term_id = at.id
                LEFT JOIN student_risks sr ON sr.id = (
                    SELECT latest_risk.id FROM student_risks latest_risk
                    WHERE latest_risk.student_id=s.id AND latest_risk.enrollment_id=e.id
                    ORDER BY latest_risk.assessed_at DESC, latest_risk.id DESC LIMIT 1
                )
                WHERE s.id = ? AND s.deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $student = $stmt->fetch();
        return $student ?: null;
    }

    /**
     * Get paginated students with rich filtering
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['s.deleted_at IS NULL', "NOT EXISTS(SELECT 1 FROM sdl_links current_source WHERE current_source.entity='student' AND current_source.local_id=s.id AND current_source.source_present=0)"];
        $params = [];

        // Search text: name, student_code, citizen_id
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = '(s.firstname LIKE ? OR s.lastname LIKE ? OR s.student_code LIKE ? OR s.citizen_id LIKE ?)';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        // Academic Term
        if (!empty($filters['term_id'])) {
            $where[] = 'e.academic_term_id = ?';
            $params[] = (int)$filters['term_id'];
        }

        // Education level
        if (!empty($filters['level'])) {
            $where[] = 'e.education_level = ?';
            $params[] = $filters['level'];
        }

        // Learning center
        if (!empty($filters['center_id'])) {
            $where[] = 'e.learning_center_id = ?';
            $params[] = (int)$filters['center_id'];
        }

        // Teacher
        if (!empty($filters['teacher_id'])) {
            $where[] = '(e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            $params[] = (int)$filters['teacher_id'];
            $params[] = (int)$filters['teacher_id'];
        }

        // Visit status: visited vs not_visited
        if (!empty($filters['visit_status'])) {
            if ($filters['visit_status'] === 'visited') {
                $where[] = 'EXISTS (SELECT 1 FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = "completed")';
            } elseif ($filters['visit_status'] === 'not_visited') {
                $where[] = 'NOT EXISTS (SELECT 1 FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = "completed")';
            }
        }

        if (($filters['survey_status'] ?? '') === 'surveyed') $where[] = 'EXISTS(SELECT 1 FROM student_surveys ss WHERE ss.enrollment_id=e.id AND ss.cancelled_at IS NULL)';
        if (($filters['survey_status'] ?? '') === 'not_surveyed') $where[] = 'NOT EXISTS(SELECT 1 FROM student_surveys ss WHERE ss.enrollment_id=e.id AND ss.cancelled_at IS NULL)';

        // Risk level
        if (!empty($filters['risk_level'])) {
            $where[] = 'sr.risk_level = ?';
            $params[] = $filters['risk_level'];
        }

        $whereClause = implode(' AND ', $where);

        // Count query
        $countSql = "SELECT COUNT(DISTINCT s.id) 
                     FROM students s
                     LEFT JOIN enrollments e ON s.id = e.student_id
                     LEFT JOIN student_risks sr ON sr.id = (
                    SELECT latest_risk.id FROM student_risks latest_risk
                    WHERE latest_risk.student_id=s.id AND latest_risk.enrollment_id=e.id
                    ORDER BY latest_risk.assessed_at DESC, latest_risk.id DESC LIMIT 1
                )
                     WHERE {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data query
        $offset = ($page - 1) * $perPage;
        $groupTeacher=TeacherAssignment::nameSql();
        $dataSql = "SELECT {$groupTeacher} AS group_teacher_name, s.*, 
                           sa.subdistrict, sa.latitude, sa.longitude,
                           e.id AS enrollment_id, e.education_level, e.learning_center_id, e.teacher_id,
                           lc.center_name,
                           u.firstname AS teacher_firstname, u.lastname AS teacher_lastname,
                           sr.risk_level, sr.total_score AS risk_score,
                           (SELECT COUNT(*) FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = 'completed') AS visit_count,
                           (SELECT hv3.id FROM home_visits hv3 WHERE hv3.student_id=s.id AND hv3.enrollment_id=e.id AND hv3.status='completed' ORDER BY hv3.visit_date DESC,hv3.id DESC LIMIT 1) AS latest_visit_id,
                           (SELECT MAX(hv2.visit_date) FROM home_visits hv2 WHERE hv2.student_id = s.id AND hv2.enrollment_id = e.id AND hv2.status = 'completed') AS last_visit_date,
                           (SELECT COUNT(*) FROM student_surveys ss WHERE ss.enrollment_id = e.id AND ss.cancelled_at IS NULL) AS survey_count,
                           (SELECT ss2.id FROM student_surveys ss2 WHERE ss2.enrollment_id = e.id AND ss2.cancelled_at IS NULL ORDER BY ss2.surveyed_at DESC, ss2.id DESC LIMIT 1) AS latest_survey_id,
                           (SELECT MAX(ss3.surveyed_at) FROM student_surveys ss3 WHERE ss3.enrollment_id = e.id AND ss3.cancelled_at IS NULL) AS last_survey_at
                    FROM students s
                    LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.address_type = 'home'
                    LEFT JOIN enrollments e ON s.id = e.student_id
                    LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
                    LEFT JOIN users u ON e.teacher_id = u.id
                    LEFT JOIN student_risks sr ON sr.id = (
                    SELECT latest_risk.id FROM student_risks latest_risk
                    WHERE latest_risk.student_id=s.id AND latest_risk.enrollment_id=e.id
                    ORDER BY latest_risk.assessed_at DESC, latest_risk.id DESC LIMIT 1
                )
                    WHERE {$whereClause}
                    GROUP BY s.id, sa.id, e.id, lc.id, u.id, sr.id
                    ORDER BY s.student_code ASC
                    LIMIT ? OFFSET ?";

        $dataParams = array_merge($params, [$perPage, $offset]);
        $dataStmt = $this->pdo->prepare($dataSql);
        $dataStmt->execute($dataParams);
        $data = $dataStmt->fetchAll();

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Create student record
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO students (student_code, citizen_id, prefix, firstname, lastname, nickname, gender, birthdate, phone, email, occupation, profile_image, student_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['student_code'],
            $data['citizen_id'] ?? null,
            $data['prefix'] ?? null,
            $data['firstname'],
            $data['lastname'],
            $data['nickname'] ?? null,
            $data['gender'] ?? 'other',
            $data['birthdate'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['occupation'] ?? null,
            $data['profile_image'] ?? null,
            $data['student_status'] ?? 'active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update student record
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        $allowed = ['student_code', 'citizen_id', 'prefix', 'firstname', 'lastname', 'nickname', 'gender', 'birthdate', 'phone', 'email', 'occupation', 'profile_image', 'student_status'];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = ?";
                $params[] = $data[$f];
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Soft delete student
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE students SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
