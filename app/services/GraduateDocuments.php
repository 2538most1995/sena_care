<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/** Read document details for one authorized graduate without storing source data. */
class GraduateDocuments
{
    public function __construct(private SdlApiClient $api) {}

    public function find(array $graduate): ?array
    {
        $code = trim((string)($graduate['student_code'] ?? ''));
        $term = trim((string)($graduate['graduation_term'] ?? ''));
        $level = (int)($graduate['level_id'] ?? 0);
        if ($code === '' || !preg_match('#^[12]/25\d{2}$#', $term)) return null;
        $matches = [];
        for ($page = 1; $page <= 100; $page++) {
            $query = http_build_query(['term' => $term, 'search' => $code, 'per_page' => 100, 'page' => $page]);
            $result = $this->api->request('graduates?' . $query);
            if (!is_array($result['data'] ?? null)) throw new RuntimeException('ข้อมูลเอกสารจาก API ไม่ถูกต้อง');
            if (isset($result['meta']['selected_term']) && $result['meta']['selected_term'] !== $term) throw new RuntimeException('ภาคเรียนจาก API ไม่ตรงกัน');
            foreach ($result['data'] as $row) {
                if (!is_array($row) || (string)($row['code'] ?? '') !== $code
                    || (string)($row['graduation_term'] ?? '') !== $term
                    || ($level && (int)($row['level']['id'] ?? 0) !== $level)) continue;
                $matches[] = $row;
            }
            $last = (int)($result['meta']['pagination']['last_page'] ?? 1);
            if ($page >= $last) break;
            if ($page === 100) throw new RuntimeException('ข้อมูล API มีจำนวนหน้าเกินขอบเขต');
        }
        if (count($matches) !== 1) return null;
        $fields = [];
        foreach (['transcript_group', 'transcript_number', 'certificate_number', 'transcript_date', 'graduation_date'] as $key) {
            $value = $matches[0][$key] ?? null;
            $fields[$key] = is_string($value) || is_int($value) ? trim((string)$value) : '';
        }
        return $fields;
    }
}
