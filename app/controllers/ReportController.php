<?php
/**
 * ============================================================
 * Report Controller
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT.'/app/services/TeacherVisitReport.php';

class ReportController
{
    private PDO $pdo;
    private AcademicTerm $termModel;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->termModel = new AcademicTerm();
    }

    public function index(): void
    {
        requireRole('super_admin', 'admin', 'executive');

        $termId = (int)($_GET['term_id'] ?? currentTermId());

        // Center stats
        $stmtCenter = $this->pdo->prepare(
            "SELECT lc.id, lc.center_name,
                    COUNT(DISTINCT e.student_id) AS total_students,
                    COUNT(DISTINCT CASE WHEN hv.status = 'completed' THEN hv.student_id END) AS visited_count,
                    COUNT(DISTINCT CASE WHEN sr.risk_level IN ('high', 'critical') THEN sr.student_id END) AS high_risk_count
             FROM learning_centers lc
             LEFT JOIN enrollments e ON lc.id = e.learning_center_id AND NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ?
             LEFT JOIN home_visits hv ON e.id = hv.enrollment_id AND hv.status = 'completed'
             LEFT JOIN student_risks sr ON sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1)
             GROUP BY lc.id
             ORDER BY lc.id ASC"
        );
        $stmtCenter->execute([$termId]);
        $centerStats = $stmtCenter->fetchAll();

        // Level stats
        $stmtLevel = $this->pdo->prepare(
            "SELECT e.education_level,
                    COUNT(DISTINCT e.student_id) AS total_students,
                    COUNT(DISTINCT CASE WHEN hv.status = 'completed' THEN hv.student_id END) AS visited_count
             FROM enrollments e
             LEFT JOIN home_visits hv ON e.id = hv.enrollment_id AND hv.status = 'completed'
             WHERE NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ?
             GROUP BY e.education_level"
        );
        $stmtLevel->execute([$termId]);
        $levelStats = $stmtLevel->fetchAll();

        // High risk students
        $stmtRisks = $this->pdo->prepare(
            "SELECT s.id, s.student_code, s.prefix, s.firstname, s.lastname, s.phone,
                    lc.center_name, e.education_level, sr.risk_level, sr.total_score,
                    u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
             FROM student_risks sr
             JOIN students s ON sr.student_id = s.id
             LEFT JOIN enrollments e ON sr.enrollment_id = e.id
             LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
             LEFT JOIN users u ON e.teacher_id = u.id
             WHERE NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ? AND sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1) AND sr.risk_level IN ('high', 'critical')
             ORDER BY CASE sr.risk_level WHEN 'critical' THEN 1 ELSE 2 END, sr.total_score DESC"
        );
        $stmtRisks->execute([$termId]);
        $riskStudents = $stmtRisks->fetchAll();

        $terms = $this->termModel->getActiveTerms();

        $pageTitle = 'รายงานและสถิติภาพรวม';
        $currentPage = 'reports';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/reports/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function teachers(bool $download = false): void
    {
        requireRole('super_admin','admin','executive','teacher');
        $terms = $this->termModel->getActiveTerms();
        $termId = (int)($_GET['term_id'] ?? currentTermId() ?? ($terms[0]['id'] ?? 0));
        $teacherId = (int)($_GET['teacher_id'] ?? (isTeacher() ? currentUserId() : 0));
        if (isTeacher() && $teacherId !== currentUserId()) {
            http_response_code(403);
            include APP_ROOT.'/app/views/errors/403.php';
            return;
        }
        $query = "SELECT id,prefix,firstname,lastname FROM users WHERE role='teacher' AND EXISTS(SELECT 1 FROM sdl_links sl WHERE sl.entity='teacher' AND sl.local_id=users.id)";
        $params = [];
        if (isTeacher()) { $query .= ' AND id=?'; $params[] = currentUserId(); }
        $stmt = $this->pdo->prepare($query.' ORDER BY firstname,lastname,id');
        $stmt->execute($params); $teachers = $stmt->fetchAll();
        $director = trim((string)($_GET['director'] ?? (new ReportSettings())->load()['director_name']));
        $director = mb_substr($director,0,150);
        $report = null; $reportError = null;
        try {
            if ($teacherId) $report = (new TeacherVisitReport($this->pdo))->data($teacherId,$termId);
            if ($download) {
                if (!$report) throw new InvalidArgumentException('กรุณาเลือกครูและภาคเรียน');
                $bytes = (new TeacherVisitReport($this->pdo))->pdf($report,$director);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="teacher-visit-'.$teacherId.'-term-'.$termId.'.pdf"');
                header('Cache-Control: private, no-store');
                header('X-Content-Type-Options: nosniff');
                echo $bytes;
                return;
            }
        } catch (InvalidArgumentException $e) {
            http_response_code(422); $reportError = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Teacher PDF: '.$e->getMessage());
            http_response_code(500); $reportError = 'สร้างรายงานไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
        }
        $pageTitle = 'รายงานผลการเยี่ยมบ้านรายครู'; $currentPage = 'teacher-reports';
        include APP_ROOT.'/app/views/layouts/header.php';
        include APP_ROOT.'/app/views/reports/teachers.php';
        include APP_ROOT.'/app/views/layouts/footer.php';
    }

    /**
     * Export reports to CSV (with UTF-8 BOM for Microsoft Excel compatibility)
     */
    public function export(): void
    {
        requireRole('super_admin', 'admin', 'executive');

        $termId = (int)($_GET['term_id'] ?? currentTermId());
        $type = $_GET['type'] ?? 'students';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($out, "\xEF\xBB\xBF");

        if ($type === 'students') {
            $this->writeCsvRow($out, ['ลำดับ', 'รหัสนักศึกษา', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ระดับการศึกษา', 'ศกร.ตำบล', 'ครูผู้รับผิดชอบ', 'เบอร์โทร', 'สถานะการเยี่ยมบ้าน', 'ระดับความเสี่ยง']);

            $stmt = $this->pdo->prepare(
                "SELECT s.student_code, s.prefix, s.firstname, s.lastname, e.education_level, lc.center_name,
                        CONCAT(u.firstname, ' ', u.lastname) as teacher_name, s.phone,
                        IF(EXISTS(SELECT 1 FROM home_visits v WHERE v.enrollment_id=e.id AND v.status='completed'), 'เยี่ยมแล้ว', 'ยังไม่เยี่ยม') as visit_status,
                        COALESCE(sr.risk_level, 'ปกติ') as risk_level
                 FROM students s
                 JOIN enrollments e ON s.id = e.student_id AND NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ?
                 LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
                 LEFT JOIN users u ON e.teacher_id = u.id
                 LEFT JOIN student_risks sr ON sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1)
                 ORDER BY s.student_code ASC"
            );
            $stmt->execute([$termId]);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $this->writeCsvRow($out, [
                    $i++,
                    $row['student_code'],
                    $row['prefix'],
                    $row['firstname'],
                    $row['lastname'],
                    educationLevelName($row['education_level']),
                    $row['center_name'],
                    $row['teacher_name'],
                    $row['phone'],
                    $row['visit_status'],
                    $row['risk_level']
                ]);
            }
        } elseif ($type === 'risks') {
            $this->writeCsvRow($out, ['ลำดับ', 'รหัสนักศึกษา', 'ชื่อ-สกุล', 'ศกร.ตำบล', 'ระดับการศึกษา', 'ระดับความเสี่ยง', 'คะแนนความเสี่ยง', 'ครูผู้ดูแล']);
            $stmt = $this->pdo->prepare(
                "SELECT s.student_code, CONCAT(s.prefix, s.firstname, ' ', s.lastname) as fullname,
                        lc.center_name, e.education_level, sr.risk_level, sr.total_score,
                        CONCAT(u.firstname, ' ', u.lastname) as teacher_name
                 FROM student_risks sr
                 JOIN students s ON sr.student_id = s.id
                 LEFT JOIN enrollments e ON sr.enrollment_id = e.id
                 LEFT JOIN learning_centers lc ON e.learning_center_id = lc.id
                 LEFT JOIN users u ON e.teacher_id = u.id
                 WHERE NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ? AND sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1) AND sr.risk_level IN ('high', 'critical')
                 ORDER BY CASE sr.risk_level WHEN 'critical' THEN 1 ELSE 2 END, sr.total_score DESC"
            );
            $stmt->execute([$termId]);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $this->writeCsvRow($out, [
                    $i++,
                    $row['student_code'],
                    $row['fullname'],
                    $row['center_name'],
                    educationLevelName($row['education_level']),
                    $row['risk_level'] === 'critical' ? 'วิกฤต' : 'สูง',
                    $row['total_score'],
                    $row['teacher_name']
                ]);
            }
        } elseif ($type === 'centers') {
            $this->writeCsvRow($out, ['ลำดับ', 'ศกร.ตำบล', 'จำนวนผู้เรียนทั้งหมด', 'เยี่ยมบ้านแล้ว', 'ร้อยละความสำเร็จ', 'เสี่ยงสูงหรือวิกฤต']);
            $stmt = $this->pdo->prepare(
                "SELECT lc.center_name,
                        COUNT(DISTINCT e.id) AS total_students,
                        COUNT(DISTINCT CASE WHEN hv.status = 'completed' THEN e.id END) AS visited_count,
                        COUNT(DISTINCT CASE WHEN sr.risk_level IN ('high', 'critical') THEN e.id END) AS high_risk_count
                 FROM learning_centers lc
                 LEFT JOIN enrollments e ON lc.id = e.learning_center_id AND NOT EXISTS(SELECT 1 FROM sdl_links roster WHERE roster.entity='student' AND roster.local_id=e.student_id AND roster.source_present=0) AND e.academic_term_id = ? AND e.enrollment_status = 'active'
                 LEFT JOIN home_visits hv ON e.id = hv.enrollment_id
                 LEFT JOIN student_risks sr ON sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1)
                 GROUP BY lc.id
                 ORDER BY lc.id ASC"
            );
            $stmt->execute([$termId]);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $pct = $row['total_students'] > 0 ? round(($row['visited_count'] / $row['total_students']) * 100, 1) : 0;
                $this->writeCsvRow($out, [
                    $i++,
                    $row['center_name'],
                    $row['total_students'],
                    $row['visited_count'],
                    $pct . '%',
                    $row['high_risk_count']
                ]);
            }
        }

        fclose($out);
        exit;
    }
    private function writeCsvRow($stream, array $row): void
    {
        fputcsv($stream,$row,',','"','');
    }
}
