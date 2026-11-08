<?php
/**
 * ============================================================
 * AcademicTerm Model
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class AcademicTerm
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Get current active term
     */
    public function getCurrent(): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM academic_terms WHERE is_current = 1 AND status = 'active' LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all terms ordered by year and term
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM academic_terms ORDER BY academic_year DESC, term_no DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Find by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academic_terms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Set current term (unset all others first)
     */
    public function setCurrent(int $id): bool
    {
        $this->pdo->exec("UPDATE academic_terms SET is_current = 0");
        $stmt = $this->pdo->prepare("UPDATE academic_terms SET is_current = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get active terms for dropdown
     */
    public function getActiveTerms(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, term_name, academic_year, term_no, is_current 
             FROM academic_terms WHERE status = 'active' 
             ORDER BY academic_year DESC, term_no DESC"
        );
        return $stmt->fetchAll();
    }
}
