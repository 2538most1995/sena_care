<?php
if (!defined('APP_ROOT')) die();
require_once APP_ROOT.'/app/models/AssessmentQuestion.php';

/**
 * Student survey history.
 *
 * A student can be surveyed many times per enrollment/term. Every save
 * appends a new record; cancellation is a soft delete so the history and
 * audit trail stay intact.
 */
class StudentSurvey
{
    public function __construct(private PDO $pdo) {}

    /** Latest active (not cancelled) survey for an enrollment, or null. */
    public function find(int $enrollmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ss.*, u.firstname, u.lastname
             FROM student_surveys ss
             JOIN users u ON u.id = ss.surveyed_by
             WHERE ss.enrollment_id = ? AND ss.cancelled_at IS NULL
             ORDER BY ss.surveyed_at DESC, ss.id DESC
             LIMIT 1'
        );
        $stmt->execute([$enrollmentId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['answers'] = json_decode($row['answers_json'], true, 512, JSON_THROW_ON_ERROR);
        return $row;
    }

    /** One survey record with student/enrollment context. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ss.*, u.firstname, u.lastname, u.username,
                    s.prefix AS s_prefix, s.firstname AS s_firstname, s.lastname AS s_lastname,
                    s.student_code, e.student_id, e.academic_term_id, e.learning_center_id, e.group_name
             FROM student_surveys ss
             JOIN users u ON u.id = ss.surveyed_by
             JOIN enrollments e ON e.id = ss.enrollment_id
             JOIN students s ON s.id = e.student_id
             WHERE ss.id = ? AND ss.cancelled_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['answers'] = json_decode($row['answers_json'], true, 512, JSON_THROW_ON_ERROR);
        return $row;
    }

    /** Visible survey history for an enrollment, excluding cancelled records. */
    public function history(int $enrollmentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ss.*, u.firstname, u.lastname
             FROM student_surveys ss
             JOIN users u ON u.id = ss.surveyed_by
             WHERE ss.enrollment_id = ? AND ss.cancelled_at IS NULL
             ORDER BY ss.surveyed_at DESC, ss.id DESC'
        );
        $stmt->execute([$enrollmentId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['answers'] = json_decode($row['answers_json'] ?? '{}', true) ?: [];
        }
        return $rows;
    }

    /** Number of active (not cancelled) surveys for an enrollment. */
    public function countActive(int $enrollmentId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM student_surveys WHERE enrollment_id = ? AND cancelled_at IS NULL'
        );
        $stmt->execute([$enrollmentId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Append a new survey record (one survey per round).
     * Returns the new survey id.
     */
    public function save(int $enrollmentId, int $userId, mixed $submitted, string $note, int $revision = 0): int
    {
        $answers = AssessmentQuestion::validateAnswers($this->pdo, $submitted);
        if (mb_strlen($note) > 10000) {
            throw new RuntimeException('บันทึกเพิ่มเติมต้องไม่เกิน 10,000 ตัวอักษร');
        }
        $json = json_encode((object)$answers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM student_surveys WHERE enrollment_id = ?');
            $stmt->execute([$enrollmentId]);
            $nextRevision = (int)$stmt->fetchColumn() + 1;

            $stmt = $this->pdo->prepare(
                'INSERT INTO student_surveys(enrollment_id, answers_json, note, surveyed_by, revision)
                 VALUES(?, ?, ?, ?, ?)'
            );
            $stmt->execute([$enrollmentId, $json, $note, $userId, $nextRevision]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Soft-cancel a survey record. Returns false when it was already cancelled. */
    public function cancel(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE student_surveys SET cancelled_at = NOW(), cancelled_by = ?
             WHERE id = ? AND cancelled_at IS NULL'
        );
        $stmt->execute([$userId, $id]);
        return $stmt->rowCount() > 0;
    }
}
