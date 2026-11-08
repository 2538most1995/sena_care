<?php
/**
 * ============================================================
 * Response / Output Helper Functions
 * ============================================================
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Escape output for HTML (XSS prevention)
 */
function e(?string $value): string
{
    if ($value === null) return '';
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response and exit
 */
function jsonResponse(bool $success, string $message = '', $data = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send JSON error with validation errors
 */
function jsonValidationError(array $errors, string $message = 'ข้อมูลไม่ถูกต้อง'): void
{
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors'  => $errors,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Format date for display (Thai Buddhist Era)
 */
function thaiDate(?string $date, string $format = 'long'): string
{
    if (empty($date)) return '-';

    $timestamp = strtotime($date);
    if ($timestamp === false) return '-';

    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];

    $thaiMonthsShort = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.',
        4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.',
        10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    $day = (int)date('j', $timestamp);
    $month = (int)date('n', $timestamp);
    $year = (int)date('Y', $timestamp) + 543;

    switch ($format) {
        case 'long':
            return "{$day} {$thaiMonths[$month]} {$year}";
        case 'short':
            return "{$day} {$thaiMonthsShort[$month]} {$year}";
        case 'minimal':
            return "{$day}/{$month}/{$year}";
        case 'datetime':
            $time = date('H:i', $timestamp);
            return "{$day} {$thaiMonthsShort[$month]} {$year} {$time} น.";
        default:
            return "{$day} {$thaiMonths[$month]} {$year}";
    }
}

/**
 * Format Thai date from datetime
 */
function thaiDateTime(?string $datetime): string
{
    return thaiDate($datetime, 'datetime');
}

/**
 * Mask citizen ID for privacy
 */
function maskCitizenId(?string $citizenId): string
{
    if (empty($citizenId) || strlen($citizenId) !== 13) return '-';
    return substr($citizenId, 0, 1) . '-xxxx-xxxxx-' . substr($citizenId, 11, 2) . '-' . substr($citizenId, 12, 1);
}

/**
 * Format phone number display
 */
function formatPhone(?string $phone): string
{
    if (empty($phone)) return '-';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
    }
    return $phone;
}

/** Build an encoded Google Maps link from a validated latitude/longitude pair. */
function googleMapsUrl($latitude, $longitude, bool $navigate = true): ?string
{
    if (!is_numeric($latitude) || !is_numeric($longitude)) return null;
    $lat = (float)$latitude;
    $lng = (float)$longitude;
    if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) return null;
    $decimal = static fn(float $value): string => rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
    $point = $decimal($lat) . ',' . $decimal($lng);
    // Let Google Maps use the device's current location when available.
    $params = $navigate
        ? ['api' => 1, 'destination' => $point, 'travelmode' => 'driving', 'dir_action' => 'navigate']
        : ['api' => 1, 'query' => $point];
    return 'https://www.google.com/maps/' . ($navigate ? 'dir/' : 'search/') . '?'
        . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Get risk level badge HTML
 */
function riskBadge(?string $level): string
{
    $map = [
        'low'      => ['class' => 'risk-low', 'text' => 'เสี่ยงต่ำ'],
        'medium'   => ['class' => 'risk-medium', 'text' => 'เสี่ยงปานกลาง'],
        'high'     => ['class' => 'risk-high', 'text' => 'เสี่ยงสูง'],
        'critical' => ['class' => 'risk-critical', 'text' => 'เสี่ยงสูงมาก'],
    ];

    $badge = ($level !== null && isset($map[$level])) ? $map[$level] : ['class' => 'risk-unknown', 'text' => 'ไม่ระบุ'];
    return '<span class="badge rounded-pill ' . $badge['class'] . '">' . $badge['text'] . '</span>';
}

/**
 * Get status badge HTML
 */
function statusBadge(?string $status, string $type = 'visit'): string
{
    $visitMap = [
        'completed' => ['class' => 'badge-visited', 'text' => 'เยี่ยมแล้ว'],
        'not_visited' => ['class' => 'badge-not-visited', 'text' => 'ยังไม่เยี่ยม'],
        'followup' => ['class' => 'badge-followup', 'text' => 'ต้องติดตาม'],
        'draft' => ['class' => 'badge-draft', 'text' => 'แบบร่าง'],
    ];

    $followupMap = [
        'pending' => ['class' => 'bg-warning text-dark', 'text' => 'รอดำเนินการ'],
        'in_progress' => ['class' => 'bg-info', 'text' => 'กำลังดำเนินการ'],
        'completed' => ['class' => 'bg-success', 'text' => 'เสร็จสิ้น'],
        'cancelled' => ['class' => 'bg-secondary', 'text' => 'ยกเลิก'],
        'overdue' => ['class' => 'bg-danger', 'text' => 'เกินกำหนด'],
    ];

    $maps = $type === 'visit' ? $visitMap : $followupMap;
    $badge = ($status !== null && isset($maps[$status])) ? $maps[$status] : ['class' => 'bg-secondary', 'text' => $status ?? '-'];
    return '<span class="badge rounded-pill ' . $badge['class'] . '">' . $badge['text'] . '</span>';
}

/**
 * Get priority badge HTML
 */
function priorityBadge(?string $priority): string
{
    $map = [
        'low'    => ['class' => 'bg-secondary', 'text' => 'ต่ำ'],
        'normal' => ['class' => 'bg-info', 'text' => 'ปกติ'],
        'high'   => ['class' => 'bg-warning text-dark', 'text' => 'สูง'],
        'urgent' => ['class' => 'bg-danger', 'text' => 'เร่งด่วน'],
    ];

    $badge = ($priority !== null && isset($map[$priority])) ? $map[$priority] : ['class' => 'bg-secondary', 'text' => $priority ?? '-'];
    return '<span class="badge rounded-pill ' . $badge['class'] . '">' . $badge['text'] . '</span>';
}

/**
 * Format file size for display
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = (int)floor((strlen((string)$bytes) - 1) / 3);
    return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
}

/**
 * Truncate text
 */
function truncate(?string $text, int $length = 50): string
{
    if (empty($text)) return '';
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Generate pagination HTML
 */
function paginationHtml(int $currentPage, int $totalPages, string $baseUrl, array $params = []): string
{
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm justify-content-center mb-0">';

    // Previous
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $prevUrl = buildPaginationUrl($baseUrl, $params, $currentPage - 1);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '"><i class="bi bi-chevron-left"></i></a></li>';

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($baseUrl, $params, 1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . buildPaginationUrl($baseUrl, $params, $i) . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($baseUrl, $params, $totalPages) . '">' . $totalPages . '</a></li>';
    }

    // Next
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $nextUrl = buildPaginationUrl($baseUrl, $params, $currentPage + 1);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '"><i class="bi bi-chevron-right"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

function buildPaginationUrl(string $baseUrl, array $params, int $page): string
{
    $params['page'] = $page;
    return $baseUrl . '?' . http_build_query($params);
}

/**
 * Get education level display name
 */
function educationLevelName(?string $code): string
{
    $map = [
        'primary'         => 'ประถมศึกษา',
        'lower_secondary' => 'มัธยมศึกษาตอนต้น',
        'upper_secondary' => 'มัธยมศึกษาตอนปลาย',
    ];
    return $map[$code] ?? ($code ?: '-');
}
