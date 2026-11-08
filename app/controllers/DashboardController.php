<?php
/**
 * ============================================================
 * Dashboard Controller
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT.'/app/models/TeacherAssignment.php';

class DashboardController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        requireLogin();

        $termId = currentTermId();
        $userId = currentUserId() ?? 0;
        $role = currentRole() ?? '';

        // Get stats
        $stats = $this->getStats($termId, $userId, $role);

        // Get recent visits
        $recentVisits = $this->getRecentVisits($termId, $userId, $role, 5);

        // Get today's schedule


        // Get pending followups
        $pendingFollowups = $this->getPendingFollowups($userId, $role, 5);

        // Get academic terms for filter
        $termModel = new AcademicTerm();
        $terms = $termModel->getActiveTerms();

        $pageTitle = 'แดชบอร์ด';
        $currentPage = 'dashboard';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/dashboard/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Get dashboard statistics
     */
    private function getStats(?int $termId, int $userId, string $role): array
    {
        $teacherFilter = '';
        $params = [];

        if ($role === 'teacher') {
            $teacherFilter = ' AND (e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            $params[] = $userId;
            $params[] = $userId;
        }

        // Total students in term
        $sql = "SELECT COUNT(DISTINCT e.student_id) FROM enrollments e 
                WHERE e.academic_term_id = ? AND e.enrollment_status = 'active'{$teacherFilter}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $totalStudents = (int)$stmt->fetchColumn();

        // Visited students
        $sql = "SELECT COUNT(DISTINCT hv.student_id) FROM home_visits hv
                JOIN enrollments e ON hv.enrollment_id = e.id
                WHERE e.academic_term_id = ? AND hv.status = 'completed'{$teacherFilter}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $visited = (int)$stmt->fetchColumn();

        // Count learners once even when they have several survey rounds.
        $sql = "SELECT COUNT(DISTINCT e.student_id) FROM enrollments e
                WHERE e.academic_term_id = ? AND e.enrollment_status = 'active'
                AND EXISTS (SELECT 1 FROM student_surveys ss WHERE ss.enrollment_id = e.id AND ss.cancelled_at IS NULL){$teacherFilter}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $surveyed = (int)$stmt->fetchColumn();

        // High risk students
        $sql = "SELECT COUNT(DISTINCT hv.student_id) FROM home_visits hv
                JOIN enrollments e ON hv.enrollment_id = e.id
                WHERE e.academic_term_id = ? AND hv.risk_level IN ('high','critical') 
                AND hv.status = 'completed' AND hv.id=(SELECT v.id FROM home_visits v WHERE v.enrollment_id=e.id AND v.status='completed' ORDER BY v.visit_date DESC,v.id DESC LIMIT 1){$teacherFilter}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $highRisk = (int)$stmt->fetchColumn();

        // Pending followups
        $fParams = [];
        $fFilter = '';
        if ($role === 'teacher') {
            $fFilter = ' AND f.assigned_to = ?';
            $fParams[] = $userId;
        }
        $sql = "SELECT COUNT(*) FROM followups f
                WHERE f.status IN ('pending','in_progress'){$fFilter}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fParams);
        $pendingFollowup = (int)$stmt->fetchColumn();

        // Today's completed records, rather than planned appointments.
        $sql="SELECT COUNT(*) FROM home_visits hv JOIN enrollments e ON e.id=hv.enrollment_id WHERE e.academic_term_id=? AND hv.visit_date=? AND hv.status='completed'{$teacherFilter}";
        $stmt=$this->pdo->prepare($sql); $stmt->execute(array_merge([$termId,date('Y-m-d')],$params));
        $todayVisits=(int)$stmt->fetchColumn();

        // Education levels breakdown
        $sql = "SELECT e.education_level, COUNT(DISTINCT e.student_id) as cnt 
                FROM enrollments e 
                WHERE e.academic_term_id = ? AND e.enrollment_status = 'active'{$teacherFilter}
                GROUP BY e.education_level";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $levelMap = ['primary' => 0, 'lower_secondary' => 0, 'upper_secondary' => 0];
        while ($row = $stmt->fetch()) {
            if (isset($levelMap[$row['education_level']])) {
                $levelMap[$row['education_level']] = (int)$row['cnt'];
            }
        }

        // Risk levels breakdown
        $sql = "SELECT COALESCE(sr.risk_level, 'low') as rlevel, COUNT(DISTINCT sr.student_id) as cnt
                FROM student_risks sr
                JOIN enrollments e ON sr.enrollment_id = e.id
                WHERE e.academic_term_id = ? AND sr.id=(SELECT r.id FROM student_risks r WHERE r.enrollment_id=e.id ORDER BY r.assessed_at DESC,r.id DESC LIMIT 1){$teacherFilter}
                GROUP BY rlevel";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$termId], $params));
        $riskMap = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        while ($row = $stmt->fetch()) {
            if (isset($riskMap[$row['rlevel']])) {
                $riskMap[$row['rlevel']] = (int)$row['cnt'];
            }
        }

        return [
            'total_students'   => $totalStudents,
            'visited'          => $visited,
            'surveyed'         => $surveyed,
            'not_visited'      => $totalStudents - $visited,
            'high_risk'        => $highRisk,
            'pending_followup' => $pendingFollowup,
            'today_visits'     => $todayVisits,
            'visit_percent'    => $totalStudents > 0 ? round(($visited / $totalStudents) * 100, 1) : 0,
            'education_levels' => $levelMap,
            'risk_distribution'=> $riskMap,
        ];
    }

    /**
     * Get recent home visits
     */
    private function getRecentVisits(?int $termId, int $userId, string $role, int $limit = 5): array
    {
        $teacherFilter = '';
        $params = [$termId];

        if ($role === 'teacher') {
            $teacherFilter = ' AND (e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))';
            $params[] = $userId;
            $params[] = $userId;
        }

        $params[] = $limit;

        $teacherName=TeacherAssignment::nameSql();
        $stmt = $this->pdo->prepare(
            "SELECT {$teacherName} AS group_teacher_name,hv.*, s.prefix AS s_prefix, s.firstname AS s_firstname, s.lastname AS s_lastname,
                    s.profile_image, u.firstname AS t_firstname, u.lastname AS t_lastname
             FROM home_visits hv
             JOIN students s ON hv.student_id = s.id
             JOIN enrollments e ON hv.enrollment_id = e.id
             JOIN users u ON hv.teacher_id = u.id
             WHERE e.academic_term_id = ? AND hv.status = 'completed'{$teacherFilter}
             ORDER BY hv.visit_date DESC, hv.checkin_time DESC
             LIMIT ?"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get pending followups
     */
    private function getPendingFollowups(int $userId, string $role, int $limit = 5): array
    {
        $params = [];
        $filter = '';

        if ($role === 'teacher') {
            $filter = ' AND f.assigned_to = ?';
            $params[] = $userId;
        }

        $params[] = $limit;

        $stmt = $this->pdo->prepare(
            "SELECT f.*, s.prefix AS s_prefix, s.firstname AS s_firstname, s.lastname AS s_lastname
             FROM followups f
             JOIN students s ON f.student_id = s.id
             WHERE f.status IN ('pending','in_progress') AND f.deleted_at IS NULL{$filter}
             ORDER BY f.due_date ASC
             LIMIT ?"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * API - Dashboard stats (for AJAX refresh)
     */
    public function apiStats(): void
    {
        requireLogin();
        $termId = currentTermId();
        $stats = $this->getStats($termId, currentUserId() ?? 0, currentRole() ?? '');
        jsonResponse(true, '', $stats);
    }
}
