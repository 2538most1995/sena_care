<?php
if (!defined('APP_ROOT')) { die('Direct access not permitted'); }
require_once APP_ROOT.'/app/models/HomeVisit.php';
require_once APP_ROOT.'/app/services/VisitReportPdf.php';

class TeacherVisitReport
{
    public function __construct(private PDO $pdo) {}

    public function data(int $teacherId, int $termId): array
    {
        $stmt=$this->pdo->prepare("SELECT u.id,u.prefix,u.firstname,u.lastname,u.organization_id,o.organization_name FROM users u LEFT JOIN organizations o ON o.id=u.organization_id WHERE u.id=? AND (u.role='teacher' OR EXISTS(SELECT 1 FROM home_visits h WHERE h.teacher_id=u.id))");
        $stmt->execute([$teacherId]); $teacher=$stmt->fetch();
        $stmt=$this->pdo->prepare('SELECT * FROM academic_terms WHERE id=?'); $stmt->execute([$termId]); $term=$stmt->fetch();
        if (!$teacher || !$term) throw new InvalidArgumentException('ไม่พบครูหรือภาคเรียนที่เลือก');
        $stmt=$this->pdo->prepare("SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.academic_term_id=? AND e.enrollment_status='active' AND (e.teacher_id=? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?))");
        $stmt->execute([$termId,$teacherId,$teacherId]); $assigned=(int)$stmt->fetchColumn();
        $stmt=$this->pdo->prepare("SELECT h.id FROM home_visits h JOIN enrollments e ON e.id=h.enrollment_id WHERE e.academic_term_id=? AND (e.teacher_id=? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?)) AND h.status='completed' ORDER BY h.visit_date,h.id");
        $stmt->execute([$termId,$teacherId,$teacherId]);
        $model=new HomeVisit(); $visits=[]; $students=[]; $risks=[]; $photoCount=0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $visit=$model->findById((int)$id);
            $tasks=$this->pdo->prepare('SELECT title,status,due_date FROM followups WHERE home_visit_id=? ORDER BY id');
            $tasks->execute([$id]); $visit['followups']=$tasks->fetchAll();
            $visits[]=$visit; $students[$visit['student_id']]=true;
            // Latest visit per student for the summary, rather than double-counting repeat visits.
            $risks[$visit['student_id']]=$visit['risk_level'];
            $photoCount+=count($visit['photos']);
        }
        return compact('teacher','term','assigned','visits','photoCount')+[
            'visitedStudents'=>count($students), 'highRisk'=>count(array_filter($risks,fn($v)=>in_array($v,['high','critical'],true)))
        ];
    }

    public function pdf(array $report, ?string $director = null, ?array $signatures = null): string
    {
        $signatures ??= (new ReportSettings())->load();
        if ($director !== null) $signatures['director_name'] = $director;
        $director = $signatures['director_name'];
        $pdf=VisitReportPdf::document('รายงานผลการเยี่ยมบ้านผู้เรียน - '.$report['teacher']['firstname'].' '.$report['teacher']['lastname']);
        ob_start(); include APP_ROOT.'/app/views/reports/teacher-pdf.php'; $html=ob_get_clean();
        // Keep each visit below PCRE's HTML size limit for large teacher rosters.
        foreach (explode('<!--REPORT_CHUNK-->', $html) as $chunk) $pdf->WriteHTML($chunk);
        return $pdf->Output('',\Mpdf\Output\Destination::STRING_RETURN);
    }
}
