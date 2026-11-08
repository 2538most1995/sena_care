<?php
/**
 * ============================================================
 * Authentication Service
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class AuthService
{
    private User $userModel;
    private PDO $pdo;
    private SdlApiClient $sdlApi;

    public function __construct(?SdlApiClient $sdlApi = null)
    {
        $this->sdlApi = $sdlApi ?? new SdlApiClient();
        $this->userModel = new User();
        $this->pdo = Database::getConnection();
    }

    /**
     * Attempt login
     * Returns: ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function login(string $username, string $password, bool $remember = false): array
    {
        $config = require APP_ROOT . '/app/config/config.php';
        $ip = getClientIp();

        // Check rate limit
        $attempts = $this->userModel->countLoginAttempts($username, $ip);
        if ($attempts >= $config['login_max_attempts']) {
            return [
                'success' => false,
                'message' => 'บัญชีถูกล็อกชั่วคราว กรุณาลองใหม่ใน 15 นาที',
            ];
        }

        // Find user
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            // Also try email
            $user = $this->userModel->findByEmail($username);
        }

        $validPassword = false;
        if ($user) {
            $linked = (new SdlSyncService())->linkedUser((int)$user['id']);
            if ($linked) {
                try {
                    $verified = $this->sdlApi->request('teacher-authenticate', [
                        'identifier' => $username, 'password' => $password,
                    ])['data'];
                    $validPassword = is_array($verified)
                        && (string)$verified['id'] === $linked['source_key'];
                } catch (Throwable $e) {
                    return ['success' => false, 'message' => 'ไม่สามารถตรวจสอบบัญชี SDL_school ได้ในขณะนี้ กรุณาลองใหม่ภายหลัง'];
                }
            } else {
                $validPassword = password_verify($password, $user['password']);
            }
        }
        if (!$validPassword) {
            $this->userModel->recordLoginAttempt($username, $ip, false);
            $remaining = $config['login_max_attempts'] - $attempts - 1;
            $msg = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            if ($remaining <= 2 && $remaining > 0) {
                $msg .= " (เหลือ {$remaining} ครั้ง)";
            }
            return ['success' => false, 'message' => $msg];
        }

        // Check if account is locked or inactive
        if ($user['status'] === 'locked') {
            return ['success' => false, 'message' => 'บัญชีถูกระงับ กรุณาติดต่อผู้ดูแลระบบ'];
        }
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'บัญชีไม่ได้ใช้งาน กรุณาติดต่อผู้ดูแลระบบ'];
        }

        // Success - clear login attempts
        $this->userModel->clearLoginAttempts($username, $ip);
        $this->userModel->recordLoginAttempt($username, $ip, true);
        $this->userModel->updateLastLogin($user['id']);

        // Regenerate session ID
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id'                 => $user['id'],
            'username'           => $user['username'],
            'email'              => $user['email'],
            'prefix'             => $user['prefix'],
            'firstname'          => $user['firstname'],
            'lastname'           => $user['lastname'],
            'role'               => $user['role'],
            'organization_id'    => $user['organization_id'],
            'learning_center_id' => $user['learning_center_id'],
            'avatar'             => $user['avatar'],
        ];

        // Load current term
        $termModel = new AcademicTerm();
        $currentTerm = $termModel->getCurrent();
        if ($currentTerm) {
            $_SESSION['current_term_id'] = (int)$currentTerm['id'];
            $_SESSION['current_term_name'] = $currentTerm['term_name'];
        }

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            // Store remember token (simplified - in production use a separate table)
            setcookie('remember_token', $token, [
                'expires'  => time() + $config['remember_lifetime'],
                'path'     => '/',
                'httponly'  => true,
                'samesite' => 'Lax',
            ]);
        }

        // Audit log
        $this->logAudit($user['id'], 'login', 'users', $user['id']);

        return [
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user'    => $user,
        ];
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $userId = currentUserId();
        if ($userId) {
            $this->logAudit($userId, 'logout', 'users', $userId);
        }

        // Clear remember token
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly'  => true,
                'samesite' => 'Lax',
            ]);
        }

        // Destroy session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Log audit event
     */
    public function logAudit(?int $userId, string $action, ?string $table = null, ?int $recordId = null, $oldData = null, $newData = null): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $action,
                $table,
                $recordId,
                $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                getClientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (PDOException $e) {
            // Don't let audit log errors break the main flow
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadNotificationCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get recent notifications for user
     */
    public function getRecentNotifications(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
