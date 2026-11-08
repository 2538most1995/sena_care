<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');
require_once APP_ROOT . '/app/models/Graduate.php';
require_once APP_ROOT . '/app/services/SdlApiClient.php';
require_once APP_ROOT . '/app/services/GraduateSyncService.php';
require_once APP_ROOT . '/app/services/ImageUploader.php';

class GraduateController
{
    /** Outcome categories shown on the tracking form. */
    public const CATEGORIES = [
        'study'       => 'การศึกษาต่อ',
        'career'      => 'การประกอบอาชีพ',
        'development' => 'การพัฒนาตนเองและสังคม/ชุมชน',
    ];

    private Graduate $model;
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->model = new Graduate($this->pdo);
    }

    public function index(): void
    {
        requireRole('super_admin', 'admin', 'teacher', 'executive');

        $filters = [
            'term'            => trim((string)($_GET['term'] ?? '')),
            'search'          => trim((string)($_GET['search'] ?? '')),
            'center_id'       => (int)($_GET['center_id'] ?? 0),
            'tracking_status' => $_GET['tracking_status'] ?? '',
            'teacher_center_id' => currentLearningCenterId() ?? 0,
        ];
        $terms = $this->model->terms();
        if ($filters['term'] === '' && $terms) {
            $filters['term'] = $terms[0];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $teacherGroups = isTeacher() ? $this->teacherGroupCodes(currentUserId()) : null;
        $roster = $this->model->getAll($filters, $page, 20, $teacherGroups);
        $centers = (new LearningCenter())->getActive();

        $pageTitle = 'ติดตามผู้สำเร็จการศึกษา';
        $currentPage = 'graduates';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/graduates/index.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function show(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher', 'executive');

        $graduate = $this->model->findById($id);
        if (!$graduate) {
            if (isAjaxRequest()) jsonResponse(false, 'ไม่พบผู้สำเร็จการศึกษา', null, 404);
            http_response_code(404);
            include APP_ROOT . '/app/views/errors/404.php';
            return;
        }
        if (!$this->canAccess($graduate)) {
            if (isAjaxRequest()) jsonResponse(false, 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้', null, 403);
            http_response_code(403);
            include APP_ROOT . '/app/views/errors/403.php';
            return;
        }

        $trackings = $this->model->trackings($id);
        $categories = self::CATEGORIES;
        $canTrack = hasRole('super_admin', 'admin', 'teacher');

        if (isAjaxRequest()) {
            $this->panelResponse($graduate, $trackings);
        }

        $pageTitle = 'ติดตามผู้สำเร็จการศึกษา: ' . $graduate['full_name'];
        $currentPage = 'graduates';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/graduates/show.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function storeTracking(int $id): void
    {
        requireRole('super_admin', 'admin', 'teacher');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }
        requireCsrf();

        $graduate = $this->model->findById($id);
        if (!$graduate) {
            if (isAjaxRequest()) jsonResponse(false, 'ไม่พบผู้สำเร็จการศึกษา', null, 404);
            http_response_code(404);
            include APP_ROOT . '/app/views/errors/404.php';
            return;
        }
        if (!$this->canAccess($graduate)) {
            if (isAjaxRequest()) jsonResponse(false, 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้', null, 403);
            http_response_code(403);
            include APP_ROOT . '/app/views/errors/403.php';
            return;
        }

        $submitted = $_POST['categories'] ?? [];
        $submitted = is_array($submitted) ? $submitted : [];
        $categories = array_values(array_intersect(array_keys(self::CATEGORIES), $submitted));
        $detail = trim((string)($_POST['other_detail'] ?? ''));

        if (!$categories && $detail === '') {
            if (isAjaxRequest()) jsonResponse(false, 'กรุณาเลือกหมวดผลลัพธ์หรือพิมพ์รายละเอียดอย่างน้อยหนึ่งอย่าง', null, 422);
            setFlash('warning', 'กรุณาเลือกหมวดผลลัพธ์หรือพิมพ์รายละเอียดอย่างน้อยหนึ่งอย่าง');
            redirect(url('graduates/' . $id));
        }
        if (mb_strlen($detail) > 5000) {
            if (isAjaxRequest()) jsonResponse(false, 'รายละเอียดต้องไม่เกิน 5,000 ตัวอักษร', null, 422);
            setFlash('danger', 'รายละเอียดต้องไม่เกิน 5,000 ตัวอักษร');
            redirect(url('graduates/' . $id));
        }

        $imagePath = null;
        try {
            $imagePath = ImageUploader::saveOne($_FILES['photo'] ?? null, 'graduates', 'grad_' . $id);
            $this->model->saveTracking($id, currentUserId(), $categories, $detail, $imagePath);
            if (isAjaxRequest()) $this->panelResponse($graduate, $this->model->trackings($id), 'บันทึกผลการติดตามผู้สำเร็จการศึกษาแล้ว');
            setFlash('success', 'บันทึกผลการติดตามผู้สำเร็จการศึกษาแล้ว');
        } catch (Throwable $e) {
            if ($imagePath) {
                $absolute = APP_ROOT . '/' . $imagePath;
                if (is_file($absolute)) @unlink($absolute);
            }
            error_log('Graduate tracking: ' . $e->getMessage());
            if (isAjaxRequest()) jsonResponse(false, 'บันทึกไม่สำเร็จ กรุณาตรวจสอบรูปภาพและลองอีกครั้ง', null, 422);
            setFlash('danger', $e->getMessage());
        }
        redirect(url('graduates/' . $id));
    }

    /** Edit an existing tracking record. */
    public function editTracking(int $graduateId, int $trackingId): void
    {
        requireRole('super_admin', 'admin', 'teacher');

        $graduate = $this->model->findById($graduateId);
        if (!$graduate) {
            if (isAjaxRequest()) jsonResponse(false, 'ไม่พบผู้สำเร็จการศึกษา', null, 404);
            http_response_code(404);
            include APP_ROOT . '/app/views/errors/404.php';
            return;
        }
        if (!$this->canAccess($graduate)) {
            if (isAjaxRequest()) jsonResponse(false, 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้', null, 403);
            http_response_code(403);
            include APP_ROOT . '/app/views/errors/403.php';
            return;
        }
        $tracking = $this->model->findTracking($trackingId, $graduateId);
        if (!$tracking) {
            if (isAjaxRequest()) jsonResponse(false, 'ไม่พบผลการติดตาม', null, 404);
            http_response_code(404);
            include APP_ROOT . '/app/views/errors/404.php';
            return;
        }

        $editUrl = url('graduates/' . $graduateId . '/track/' . $trackingId . '/edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrf();

            $submitted = $_POST['categories'] ?? [];
            $submitted = is_array($submitted) ? $submitted : [];
            $categories = array_values(array_intersect(array_keys(self::CATEGORIES), $submitted));
            $detail = trim((string)($_POST['other_detail'] ?? ''));
            if (!$categories && $detail === '') {
                if (isAjaxRequest()) jsonResponse(false, 'กรุณาเลือกหมวดผลลัพธ์หรือพิมพ์รายละเอียดอย่างน้อยหนึ่งอย่าง', null, 422);
                setFlash('warning', 'กรุณาเลือกหมวดผลลัพธ์หรือพิมพ์รายละเอียดอย่างน้อยหนึ่งอย่าง');
                redirect($editUrl);
            }
            if (mb_strlen($detail) > 5000) {
                if (isAjaxRequest()) jsonResponse(false, 'รายละเอียดต้องไม่เกิน 5,000 ตัวอักษร', null, 422);
                setFlash('danger', 'รายละเอียดต้องไม่เกิน 5,000 ตัวอักษร');
                redirect($editUrl);
            }

            $oldImage = $tracking['image_path'] ?: null;
            $imagePath = $oldImage;
            $newImage = null;
            try {
                if (!empty($_POST['remove_image']) && $imagePath) {
                    $this->deleteImage($imagePath);
                    $imagePath = null;
                }
                $newImage = ImageUploader::saveOne($_FILES['photo'] ?? null, 'graduates', 'grad_' . $graduateId);
                if ($newImage) {
                    if ($oldImage && $oldImage !== $newImage) $this->deleteImage($oldImage);
                    $imagePath = $newImage;
                }
                $this->model->updateTracking($trackingId, $graduateId, $categories, $detail, $imagePath);
                if (isAjaxRequest()) $this->panelResponse($graduate, $this->model->trackings($graduateId), 'แก้ไขผลการติดตามแล้ว');
                setFlash('success', 'แก้ไขผลการติดตามแล้ว');
                redirect(url('graduates/' . $graduateId));
            } catch (Throwable $e) {
                if ($newImage) $this->deleteImage($newImage);
                error_log('Graduate tracking edit: ' . $e->getMessage());
                if (isAjaxRequest()) jsonResponse(false, 'บันทึกไม่สำเร็จ กรุณาตรวจสอบรูปภาพและลองอีกครั้ง', null, 422);
                setFlash('danger', $e->getMessage());
                redirect($editUrl);
            }
        }

        $categories = self::CATEGORIES;
        if (isAjaxRequest()) {
            $isPanel = true;
            ob_start();
            include APP_ROOT . '/app/views/graduates/edit.php';
            jsonResponse(true, '', ['html' => ob_get_clean(), 'graduate_id' => $graduateId]);
        }
        $pageTitle = 'แก้ไขผลการติดตาม: ' . $graduate['full_name'];
        $currentPage = 'graduates';
        include APP_ROOT . '/app/views/layouts/header.php';
        include APP_ROOT . '/app/views/graduates/edit.php';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    private function panelResponse(array $graduate, array $trackings, string $message = ''): void
    {
        $categories = self::CATEGORIES;
        $canTrack = hasRole('super_admin', 'admin', 'teacher');
        $isPanel = true;
        ob_start();
        include APP_ROOT . '/app/views/graduates/show.php';
        $html = ob_get_clean();
        jsonResponse(true, $message, [
            'html' => $html,
            'graduate_id' => (int)$graduate['id'],
            'tracking_count' => count($trackings),
            'latest_tracked_at' => $trackings ? thaiDateTime($trackings[0]['tracked_at']) : '',
        ]);
    }

    private function deleteImage(?string $path): void
    {
        if (!$path) return;
        $absolute = APP_ROOT . '/' . $path;
        if (is_file($absolute)) @unlink($absolute);
    }

    public function sync(): void
    {
        requireRole('super_admin', 'admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }
        requireCsrf();

        $term = trim((string)($_POST['term'] ?? ''));
        try {
            $summary = (new GraduateSyncService(new SdlApiClient(), $this->pdo))->syncHistory();
            setFlash('success', 'ดึงข้อมูลผู้สำเร็จการศึกษาตั้งแต่ภาคเรียน ' . $summary['from_term'] . ' ถึง ' . $summary['term'] . ' รวม ' . count($summary['terms']) . ' ภาคเรียน จำนวน ' . number_format($summary['graduates']) . ' คน');
            if ($term === '' || !array_key_exists($term, $summary['terms'])) $term = $summary['term'];
        } catch (Throwable $e) {
            error_log('Graduate sync: ' . $e->getMessage());
            setFlash('danger', 'ดึงข้อมูลไม่สำเร็จ: ' . $e->getMessage());
        }
        redirect(url('graduates' . ($term !== '' ? '?term=' . urlencode($term) : '')));
    }

    /** Group codes assigned to a teacher at the source (from the synced snapshot). */
    private function teacherGroupCodes(?int $userId): array
    {
        if (!$userId) return [];
        $stmt = $this->pdo->prepare("SELECT snapshot FROM sdl_links WHERE entity = 'teacher' AND local_id = ?");
        $stmt->execute([$userId]);
        $snapshot = $stmt->fetchColumn();
        if ($snapshot) {
            $data = json_decode($snapshot, true);
            $groups = $data['assigned_groups'] ?? [];
            if (is_array($groups)) {
                $groups = array_values(array_filter(array_map('strval', $groups), static fn($value) => trim($value) !== ''));
                if ($groups) return $groups;
            }
        }
        return [];
    }

    private function canAccess(array $graduate): bool
    {
        if (isAdmin() || isExecutive()) return true;
        if (!isTeacher()) return false;

        $groupCode = (string)($graduate['group_code'] ?? '');
        $groups = $this->teacherGroupCodes(currentUserId());
        foreach ($groups as $assigned) {
            if ($assigned === $groupCode || ltrim($assigned, '0') === ltrim($groupCode, '0')) {
                return true;
            }
        }
        $centerId = currentLearningCenterId();
        return $centerId && (int)$graduate['learning_center_id'] === (int)$centerId;
    }
}
