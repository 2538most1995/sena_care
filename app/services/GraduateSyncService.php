<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/**
 * Sync graduates (ผู้สำเร็จการศึกษา) from the SDL_school "graduates" endpoint.
 * Each graduate is mapped to a learning center through its group code so the
 * roster can be filtered by the sub-district teacher's groups.
 */
class GraduateSyncService
{
    public const HISTORY_START_TERM = '1/2564';

    public function __construct(private SdlApiClient $api, private PDO $pdo) {}

    /**
     * @param string|null $term graduation term (e.g. "1/2569"); null = API default (latest)
     * @return array{term:string, graduates:int}
     */
    public function sync(?string $term = null): array
    {
        if (!$this->api->enabled()) {
            throw new RuntimeException('ยังไม่ได้ตั้งค่าการเชื่อมต่อ SDL_school');
        }

        if ($term === null || trim($term) === '') {
            $probe = $this->api->request('graduates?per_page=1');
            $term = trim((string)($probe['meta']['selected_term'] ?? ''));
            if ($term === '') {
                throw new RuntimeException('ไม่พบภาคเรียนของผู้สำเร็จการศึกษาจาก API');
            }
        }

        $rows = $this->fetchTerm($term);
        return ['term' => $term, 'graduates' => $this->importRows($rows)];
    }

    /** Fetch the entire history before writing, so a failed API page leaves data unchanged. */
    public function syncHistory(string $fromTerm = self::HISTORY_START_TERM): array
    {
        $fromOrder = $this->termOrder($fromTerm);
        $probe = $this->api->request('graduates?per_page=1');
        $available = $probe['meta']['terms'] ?? [];
        if (!is_array($available) || !$available) {
            throw new RuntimeException('ไม่พบรายการภาคเรียนของผู้สำเร็จการศึกษาจาก API');
        }

        $terms = [];
        foreach ($available as $term) {
            $term = trim((string)$term);
            $order = $this->termOrder($term);
            if ($order >= $fromOrder) $terms[$order] = $term;
        }
        ksort($terms, SORT_NUMERIC);
        $terms = array_values($terms);
        if (!$terms) {
            throw new RuntimeException('ไม่พบข้อมูลผู้สำเร็จการศึกษาตั้งแต่ภาคเรียน ' . $fromTerm);
        }

        $rows = [];
        $counts = [];
        $seen = [];
        foreach ($terms as $term) {
            $termRows = $this->fetchTerm($term);
            foreach ($termRows as $row) {
                $code = trim((string)($row['code'] ?? ''));
                if ($code !== '' && isset($seen[$code]) && $seen[$code] !== $term) {
                    throw new RuntimeException('พบรหัสนักศึกษาซ้ำข้ามภาคเรียน กรุณาตรวจสอบข้อมูลต้นทางก่อนนำเข้า');
                }
                if ($code !== '') $seen[$code] = $term;
            }
            $counts[$term] = count($termRows);
            $rows = array_merge($rows, $termRows);
        }

        return [
            'from_term' => $fromTerm,
            'term' => $terms[count($terms) - 1],
            'terms' => $counts,
            'graduates' => $this->importRows($rows),
        ];
    }

    private function termOrder(string $term): int
    {
        if (!preg_match('#^([12])/(25\d{2})$#', $term, $match)) {
            throw new RuntimeException('รูปแบบภาคเรียนไม่ถูกต้อง');
        }
        return (int)$match[2] * 10 + (int)$match[1];
    }

    private function fetchTerm(string $term): array
    {
        $this->termOrder($term);
        $rows = $this->api->allWithQuery('graduates', ['term' => $term]);
        foreach ($rows as $row) {
            if (!is_array($row) || trim((string)($row['graduation_term'] ?? '')) !== $term) {
                throw new RuntimeException('API ส่งข้อมูลไม่ตรงกับภาคเรียน ' . $term . ' ระบบยกเลิกการนำเข้า');
            }
        }
        return $rows;
    }

    private function importRows(array $rows): int
    {

        $centerByCode = [];
        foreach ($this->pdo->query('SELECT id, center_code FROM learning_centers')->fetchAll() as $center) {
            $code = trim((string)$center['center_code']);
            if ($code !== '') {
                $centerByCode[$code] = (int)$center['id'];
                $centerByCode[ltrim($code, '0')] = (int)$center['id'];
            }
        }

        $upsert = $this->pdo->prepare(
            'INSERT INTO graduates(student_code, full_name, level_id, level_label, group_code, group_name, graduation_term, learning_center_id, synced_at)
             VALUES(?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name), level_id = VALUES(level_id), level_label = VALUES(level_label),
                group_code = VALUES(group_code), group_name = VALUES(group_name), graduation_term = VALUES(graduation_term),
                learning_center_id = VALUES(learning_center_id), synced_at = NOW()'
        );

        $count = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($rows as $graduate) {
                $code = trim((string)($graduate['code'] ?? ''));
                if ($code === '') continue;

                $name = trim((string)($graduate['name']['full_name'] ?? ''));
                $levelId = (int)($graduate['level']['id'] ?? 0) ?: null;
                $levelLabel = trim((string)($graduate['level']['label'] ?? ''));
                $groupCode = trim((string)($graduate['group']['code'] ?? ''));
                $groupName = trim((string)($graduate['group']['name'] ?? ''));
                $termValue = trim((string)$graduate['graduation_term']);

                $centerId = null;
                if ($groupCode !== '') {
                    $suffix = (strlen($groupCode) >= 6 && in_array(substr($groupCode, 0, 2), ['21', '22', '23'], true))
                        ? substr($groupCode, 2)
                        : $groupCode;
                    $centerId = $centerByCode[$suffix] ?? $centerByCode[ltrim($suffix, '0')] ?? null;
                }

                $upsert->execute([$code, $name, $levelId, $levelLabel, $groupCode, $groupName, $termValue, $centerId]);
                $count++;
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }

        return $count;
    }
}
