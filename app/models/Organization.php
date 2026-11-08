<?php
/**
 * ============================================================
 * Organization Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Organization
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM organizations ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM organizations WHERE status = 'active' ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
