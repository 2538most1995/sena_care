<?php
/**
 * ============================================================
 * User Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, o.organization_name, o.short_name AS org_short_name,
                    lc.center_name
             FROM users u
             LEFT JOIN organizations o ON u.organization_id = o.id
             LEFT JOIN learning_centers lc ON u.learning_center_id = lc.id
             WHERE u.id = ? AND u.status != 'inactive'"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all users with pagination
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = '(u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($filters['role'])) {
            $where[] = 'u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['organization_id'])) {
            $where[] = 'u.organization_id = ?';
            $params[] = $filters['organization_id'];
        }

        $whereClause = implode(' AND ', $where);

        // Count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users u WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data
        $offset = ($page - 1) * $perPage;
        $dataStmt = $this->pdo->prepare(
            "SELECT u.*, o.short_name AS org_short_name, lc.center_name
             FROM users u
             LEFT JOIN organizations o ON u.organization_id = o.id
             LEFT JOIN learning_centers lc ON u.learning_center_id = lc.id
             WHERE {$whereClause}
             ORDER BY u.firstname ASC
             LIMIT ? OFFSET ?"
        );
        $dataParams = array_merge($params, [$perPage, $offset]);
        $dataStmt->execute($dataParams);
        $data = $dataStmt->fetchAll();

        return [
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Get all teachers
     */
    public function getTeachers(?int $organizationId = null): array
    {
        $sql = "SELECT id, prefix, firstname, lastname, phone 
                FROM users WHERE role = 'teacher' AND status = 'active'";
        $params = [];

        if ($organizationId) {
            $sql .= " AND organization_id = ?";
            $params[] = $organizationId;
        }

        $sql .= " ORDER BY firstname ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create user
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password, prefix, firstname, lastname, 
             phone, role, organization_id, learning_center_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['username'],
            $data['email'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['prefix'] ?? null,
            $data['firstname'],
            $data['lastname'],
            $data['phone'] ?? null,
            $data['role'] ?? 'teacher',
            $data['organization_id'] ?? null,
            $data['learning_center_id'] ?? null,
            $data['status'] ?? 'active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'username', 'email', 'prefix', 'firstname', 'lastname',
            'phone', 'role', 'organization_id', 'learning_center_id', 'status', 'avatar'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->pdo->prepare(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?"
        );
        return $stmt->execute($params);
    }

    /**
     * Update password
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Count login attempts
     */
    public function countLoginAttempts(string $username, string $ip, int $withinMinutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE username = ? AND ip_address = ? AND success = 0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$username, $ip, $withinMinutes]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Record login attempt
     */
    public function recordLoginAttempt(string $username, string $ip, bool $success): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)"
        );
        $stmt->execute([$username, $ip, $success ? 1 : 0]);
    }

    /**
     * Clear login attempts on success
     */
    public function clearLoginAttempts(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM login_attempts WHERE username = ? AND ip_address = ?"
        );
        $stmt->execute([$username, $ip]);
    }

    /**
     * Get full name with prefix
     */
    public static function fullName(array $user): string
    {
        $parts = [];
        if (!empty($user['prefix'])) $parts[] = $user['prefix'];
        $parts[] = $user['firstname'] ?? '';
        $parts[] = $user['lastname'] ?? '';
        return implode('', array_filter($parts));
    }

    /**
     * Get display name (no prefix)
     */
    public static function displayName(array $user): string
    {
        return trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
    }
}
