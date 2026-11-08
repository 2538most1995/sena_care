<?php
/**
 * ============================================================
 * Authentication Helper Functions
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user ID
 */
function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data from session
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user role
 */
function currentRole(): ?string
{
    return $_SESSION['user']['role'] ?? $_SESSION['role'] ?? null;
}

/**
 * Check if current user has specific role
 */
function hasRole(string ...$roles): bool
{
    $userRole = currentRole();
    if (!$userRole) return false;
    return in_array($userRole, $roles, true);
}

/**
 * Check if user is Super Admin
 */
function isSuperAdmin(): bool
{
    return hasRole('super_admin');
}

/**
 * Check if user is Admin or Super Admin
 */
function isAdmin(): bool
{
    return hasRole('super_admin', 'admin');
}

/**
 * Check if user is Teacher
 */
function isTeacher(): bool
{
    return hasRole('teacher');
}

/**
 * Check if user is Executive
 */
function isExecutive(): bool
{
    return hasRole('executive');
}

/**
 * Activity window state of a learning center for a given activity type.
 * `open` is true only when the window is enabled AND the current time is
 * inside [open_at, close_at] (empty bounds are treated as no limit).
 *
 * @param string $type "visit" (home visits) or "survey" (individual surveys)
 * @return array{enabled:bool, open_at:?string, close_at:?string, open:bool}
 */
function centerActivityState(?int $centerId, string $type): array
{
    $col = $type === 'survey' ? 'survey' : 'visit';
    $closed = ['enabled' => false, 'open_at' => null, 'close_at' => null, 'open' => false];
    if (!$centerId) return $closed;

    $stmt = Database::getConnection()->prepare(
        "SELECT {$col}_enabled AS enabled, {$col}_open_at AS open_at, {$col}_close_at AS close_at
         FROM learning_centers WHERE id = ?"
    );
    $stmt->execute([$centerId]);
    $row = $stmt->fetch();
    if (!$row) return $closed;

    $open = (bool)$row['enabled'];
    $now = time();
    if ($open && !empty($row['open_at']) && strtotime($row['open_at']) > $now) $open = false;
    if ($open && !empty($row['close_at']) && strtotime($row['close_at']) < $now) $open = false;

    return [
        'enabled'  => (bool)$row['enabled'],
        'open_at'  => $row['open_at'],
        'close_at' => $row['close_at'],
        'open'     => $open,
    ];
}

/** Whether a center's window for an activity type is currently open. */
function centerActivityOpen(?int $centerId, string $type): bool
{
    return centerActivityState($centerId, $type)['open'];
}

/**
 * Can the current user create a home visit for a student in this center?
 * District admins bypass the window; executives are view-only; teachers
 * follow the center's visit window.
 */
function canCreateVisitForCenter(?int $centerId): bool
{
    if (isAdmin()) return true;
    if (isExecutive()) return false;
    if (isTeacher()) return centerActivityOpen($centerId, 'visit');
    return false;
}

/** Same rule as canCreateVisitForCenter(), for individual surveys. */
function canCreateSurveyForCenter(?int $centerId): bool
{
    if (isAdmin()) return true;
    if (isExecutive()) return false;
    if (isTeacher()) return centerActivityOpen($centerId, 'survey');
    return false;
}

/** Cancelling visit/survey records is a district-level action only. */
function canCancelRecords(): bool
{
    return isAdmin();
}

/**
 * Get current organization ID from session
 */
function currentOrganizationId(): ?int
{
    return $_SESSION['user']['organization_id'] ?? null;
}

/**
 * Get current learning center ID from session
 */
function currentLearningCenterId(): ?int
{
    return $_SESSION['user']['learning_center_id'] ?? null;
}

/**
 * Get the current academic term from session
 */
function currentTermId(): ?int
{
    return $_SESSION['current_term_id'] ?? null;
}

/**
 * Get current term name
 */
function currentTermName(): string
{
    return $_SESSION['current_term_name'] ?? '-';
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(url('login'));
    }
}

/**
 * Require specific role(s)
 */
function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        include APP_ROOT . '/app/views/errors/403.php';
        exit;
    }
}

/**
 * Check if user can access a specific student (IDOR prevention)
 */
function canAccessStudent(int $studentId, ?int $termId = null): bool
{
    if (isAdmin() || isSuperAdmin() || isExecutive()) {
        return true;
    }

    // Teachers can only access their own students
    if (isTeacher()) {
        $pdo = Database::getConnection();
        $termId ??= currentTermId();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM enrollments e
             WHERE e.student_id = ? AND (e.teacher_id = ? OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers st WHERE st.enrollment_id=e.id AND st.teacher_id=?)) AND e.academic_term_id = ?"
        );
        $stmt->execute([$studentId, currentUserId(), currentUserId(), $termId]);
        return $stmt->fetchColumn() > 0;
    }

    return false;
}

/**
 * Generate URL for the application
 */
function url(string $path = ''): string
{
    $config = require APP_ROOT . '/app/config/config.php';
    $base = rtrim($config['app_url'], '/');
    if ($path) {
        return $base . '/' . ltrim($path, '/');
    }
    return $base;
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get client IP address
 */
function getClientIp(): string
{
    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = explode(',', $_SERVER[$header]);
            return trim($ip[0]);
        }
    }
    return '0.0.0.0';
}
