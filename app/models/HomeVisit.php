<?php
/**
 * ============================================================
 * HomeVisit Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/app/models/AssessmentQuestion.php';
require_once APP_ROOT . '/app/models/TeacherAssignment.php';

class HomeVisit
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /** Completed rounds for the authorized roster, fetched in one query. */
    public function forEnrollments(array $ids, ?int $teacherId = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        if (!$ids) return [];
        $slots = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $scope = '';
        if ($teacherId !== null) {
            $scope = ' AND (hv.teacher_id=? OR e.teacher_id=? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            array_push($params, $teacherId, $teacherId, $teacherId);
        }
        $stmt = $this->pdo->prepare("SELECT hv.id,hv.enrollment_id,hv.visit_date FROM home_visits hv
            JOIN enrollments e ON e.id=hv.enrollment_id
            WHERE hv.status='completed' AND hv.enrollment_id IN ($slots)$scope
            ORDER BY hv.visit_date ASC,hv.id ASC");
        $stmt->execute($params);
        $rounds = [];
        foreach ($stmt->fetchAll() as $visit) $rounds[(int)$visit['enrollment_id']][] = $visit;
        return $rounds;
    }

    public function findById(int $id): ?array
    {
        $teacherName=TeacherAssignment::nameSql();
        $stmt = $this->pdo->prepare(
            "SELECT {$teacherName} AS group_teacher_name, hv.*, s.student_code, s.prefix, s.firstname, s.lastname, s.phone, s.profile_image,
                    sa.house_no, sa.moo, sa.subdistrict, sa.district, sa.province, sa.zipcode, sa.latitude AS home_lat, sa.longitude AS home_lng,
                    e.education_level, e.academic_term_id, e.learning_center_id, at.term_name, lc.center_name, o.organization_name,
                    u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
             FROM home_visits hv
             JOIN students s ON hv.student_id = s.id
             LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.address_type = 'home'
             LEFT JOIN enrollments e ON hv.enrollment_id = e.id
             LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
             JOIN users u ON hv.teacher_id = u.id
             LEFT JOIN academic_terms at ON at.id=e.academic_term_id
             LEFT JOIN organizations o ON o.id=COALESCE(lc.organization_id,u.organization_id)
             WHERE hv.id = ?"
        );
        $stmt->execute([$id]);
        $visit = $stmt->fetch();
        if (!$visit) return null;

        // Fetch assessment answers
        $stmtAns = $this->pdo->prepare(
            "SELECT aq.*, aa.question_id, aa.answer_value
             FROM assessment_answers aa
             JOIN assessment_questions aq ON aa.question_id = aq.id
             WHERE aa.visit_id = ?
             ORDER BY aq.sort_order ASC, (aq.status='active') DESC, aq.id ASC"
        );
        $stmtAns->execute([$id]);
        $visit['answers'] = AssessmentQuestion::uniqueAnswers($stmtAns->fetchAll());

        // Fetch photos
        $stmtPhotos = $this->pdo->prepare("SELECT * FROM visit_files WHERE visit_id = ? ORDER BY id ASC");
        $stmtPhotos->execute([$id]);
        $visit['photos'] = $stmtPhotos->fetchAll();

        return $visit;
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['term_id'])) {
            $where[] = 'e.academic_term_id = ?';
            $params[] = (int)$filters['term_id'];
        }

        if (!empty($filters['teacher_id'])) {
            $where[] = '(hv.teacher_id = ? OR e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            $params[] = (int)$filters['teacher_id'];
            $params[] = (int)$filters['teacher_id'];
            $params[] = (int)$filters['teacher_id'];
        }

        if (!empty($filters['student_id'])) {
            $where[] = 'hv.student_id = ?';
            $params[] = (int)$filters['student_id'];
        }

        if (!empty($filters['risk_level'])) {
            $where[] = 'hv.risk_level = ?';
            $params[] = $filters['risk_level'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'hv.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = '(s.firstname LIKE ? OR s.lastname LIKE ? OR s.student_code LIKE ?)';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM home_visits hv
             JOIN students s ON hv.student_id = s.id
             LEFT JOIN enrollments e ON hv.enrollment_id = e.id
             WHERE {$whereClause}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $teacherName=TeacherAssignment::nameSql();
        $dataStmt = $this->pdo->prepare(
            "SELECT {$teacherName} AS group_teacher_name, hv.*, s.student_code, s.prefix, s.firstname, s.lastname, s.phone,
                    sa.subdistrict, e.education_level, e.learning_center_id, e.academic_term_id, lc.center_name,
                    u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
             FROM home_visits hv
             JOIN students s ON hv.student_id = s.id
             LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.address_type = 'home'
             LEFT JOIN enrollments e ON hv.enrollment_id = e.id
             LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
             JOIN users u ON hv.teacher_id = u.id
             WHERE {$whereClause}
             ORDER BY hv.visit_date DESC, hv.id DESC
             LIMIT ? OFFSET ?"
        );
        $dataParams = array_merge($params, [$perPage, $offset]);
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

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO home_visits (student_id, enrollment_id, teacher_id, visit_plan_id, visit_date, checkin_time, checkin_latitude, checkin_longitude, distance_from_home, visit_type, purpose, summary, problem, student_need, teacher_recommendation, risk_level, followup_required, next_followup_date, status)
             VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['student_id'],
            $data['enrollment_id'] ?? null,
            $data['teacher_id'],
            $data['visit_plan_id'] ?? null,
            $data['visit_date'] ?? date('Y-m-d'),
            !empty($data['checkin_latitude']) ? (float)$data['checkin_latitude'] : null,
            !empty($data['checkin_longitude']) ? (float)$data['checkin_longitude'] : null,
            !empty($data['distance_from_home']) ? (int)$data['distance_from_home'] : null,
            $data['visit_type'] ?? 'home_visit',
            $data['purpose'] ?? 'เยี่ยมบ้านเพื่อติดตามการเรียนและความเป็นอยู่',
            $data['summary'] ?? null,
            $data['problem'] ?? null,
            $data['student_need'] ?? null,
            $data['teacher_recommendation'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['followup_required']) ? 1 : 0,
            $data['next_followup_date'] ?? null,
            $data['status'] ?? 'completed'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'visit_date = ?',
            'visit_type = ?',
            'summary = ?',
            'teacher_recommendation = ?',
            'risk_level = ?',
            'followup_required = ?',
            'next_followup_date = ?'
        ];
        $params = [
            $data['visit_date'] ?? date('Y-m-d'),
            $data['visit_type'] ?? 'home_visit',
            $data['summary'] ?? null,
            $data['teacher_recommendation'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['followup_required']) ? 1 : 0,
            $data['next_followup_date'] ?? null
        ];

        if (array_key_exists('checkin_latitude', $data) && $data['checkin_latitude'] !== null && $data['checkin_latitude'] !== '') {
            $fields[] = 'checkin_latitude = ?';
            $params[] = (float)$data['checkin_latitude'];
        }
        if (array_key_exists('checkin_longitude', $data) && $data['checkin_longitude'] !== null && $data['checkin_longitude'] !== '') {
            $fields[] = 'checkin_longitude = ?';
            $params[] = (float)$data['checkin_longitude'];
        }
        if (array_key_exists('distance_from_home', $data) && $data['distance_from_home'] !== null && $data['distance_from_home'] !== '') {
            $fields[] = 'distance_from_home = ?';
            $params[] = (int)$data['distance_from_home'];
        }

        $params[] = $id;
        $sql = "UPDATE home_visits SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft-cancel a completed home visit. The record and its photos stay as
     * history, but it no longer counts as a visit. The risk assessment derived
     * from the cancelled visit is removed so statistics stay consistent.
     */
    public function cancel(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE home_visits SET status = 'cancelled' WHERE id = ? AND status = 'completed'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }
            $this->pdo->prepare('DELETE FROM student_risks WHERE home_visit_id = ?')->execute([$id]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteFile(int $fileId, int $visitId): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM visit_files WHERE id = ? AND visit_id = ?");
        $stmt->execute([$fileId, $visitId]);
        $file = $stmt->fetch();
        if ($file) {
            $filePath = APP_ROOT . '/public/' . ltrim($file['file_path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $delStmt = $this->pdo->prepare("DELETE FROM visit_files WHERE id = ?");
            return $delStmt->execute([$fileId]);
        }
        return false;
    }
}
