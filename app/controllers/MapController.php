<?php
/**
 * ============================================================
 * Map Controller (Leaflet Map & GPS)
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class MapController
{
    private PDO $pdo;
    private AcademicTerm $termModel;
    private LearningCenter $centerModel;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->termModel = new AcademicTerm();
        $this->centerModel = new LearningCenter();
    }

    /**
     * Map page view
     */
    public function index(): void
    {
        requireLogin();

        $terms = $this->termModel->getActiveTerms();
        $centers = $this->centerModel->getActive();
        $userModel = new User();
        $teachers = $userModel->getTeachers(isSuperAdmin() ? null : currentOrganizationId());

        $pageTitle = 'แผนที่บ้านผู้เรียน';
        $currentPage = 'map';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/map/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * API returning student coordinate markers for Leaflet map
     */
    public function apiStudents(): void
    {
        requireLogin();

        $termId = (int)($_GET['term_id'] ?? currentTermId());
        $level = $_GET['level'] ?? '';
        $centerId = $_GET['center_id'] ?? '';
        $teacherId = isTeacher() ? currentUserId() : ($_GET['teacher_id'] ?? '');
        $riskLevel = $_GET['risk_level'] ?? '';
        $visitStatus = $_GET['visit_status'] ?? '';

        $where = [
            's.deleted_at IS NULL',
            "NOT EXISTS(SELECT 1 FROM sdl_links current_source WHERE current_source.entity='student' AND current_source.local_id=s.id AND current_source.source_present=0)",
            'sa.latitude IS NOT NULL',
            'sa.longitude IS NOT NULL',
            'sa.address_type = "home"'
        ];
        $params = [];

        if ($termId) {
            $where[] = 'e.academic_term_id = ?';
            $params[] = $termId;
        }

        if ($level) {
            $where[] = 'e.education_level = ?';
            $params[] = $level;
        }

        if ($centerId) {
            $where[] = 'e.learning_center_id = ?';
            $params[] = (int)$centerId;
        }

        if ($teacherId) {
            $where[] = '(e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            $params[] = (int)$teacherId;
            $params[] = (int)$teacherId;
        }

        if ($riskLevel) {
            $where[] = 'sr.risk_level = ?';
            $params[] = $riskLevel;
        }

        if ($visitStatus === 'visited') {
            $where[] = 'EXISTS (SELECT 1 FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = "completed")';
        } elseif ($visitStatus === 'not_visited') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = "completed")';
        }

        $sql = "SELECT s.id, s.student_code, s.prefix, s.firstname, s.lastname, s.phone, s.profile_image,
                       sa.house_no, sa.moo, sa.subdistrict, sa.district, sa.province, sa.latitude, sa.longitude, sa.landmark,
                       e.education_level, lc.center_name,
                       u.firstname AS teacher_firstname, u.lastname AS teacher_lastname,
                       sr.risk_level,
                       (SELECT COUNT(*) FROM home_visits hv WHERE hv.student_id = s.id AND hv.enrollment_id = e.id AND hv.status = 'completed') as visit_count,
                       (SELECT MAX(hv2.visit_date) FROM home_visits hv2 WHERE hv2.student_id = s.id AND hv2.enrollment_id = e.id AND hv2.status = 'completed') as last_visit_date
                FROM students s
                JOIN student_addresses sa ON s.id = sa.student_id
                LEFT JOIN enrollments e ON s.id = e.student_id
                LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
                LEFT JOIN users u ON e.teacher_id = u.id
                LEFT JOIN student_risks sr ON sr.id = (
                    SELECT latest_risk.id FROM student_risks latest_risk
                    WHERE latest_risk.student_id=s.id AND latest_risk.enrollment_id=e.id
                    ORDER BY latest_risk.assessed_at DESC, latest_risk.id DESC LIMIT 1
                )
                WHERE " . implode(' AND ', $where) . "
                GROUP BY s.id, sa.id, e.id, lc.id, u.id, sr.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
        foreach ($students as &$student) {
            $student['navigation_url'] = googleMapsUrl($student['latitude'], $student['longitude']);
        }
        unset($student);

        jsonResponse(true, '', $students);
    }
}
