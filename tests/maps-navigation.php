<?php
// URL contract checks only; no database writes or external requests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/helpers/response.php';
$checks = 0;
function navigationCheck(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
$url = googleMapsUrl('14.3284000', '100.4042000');
parse_str(parse_url($url, PHP_URL_QUERY), $query);
navigationCheck(parse_url($url, PHP_URL_HOST) === 'www.google.com', 'Use the universal Google Maps host');
navigationCheck(parse_url($url, PHP_URL_PATH) === '/maps/dir/', 'Use directions, rather than a location search');
navigationCheck($query['api'] === '1', 'Include the required Maps URL version');
navigationCheck($query['destination'] === '14.3284,100.4042', 'Preserve the latitude/longitude destination order');
navigationCheck($query['travelmode'] === 'driving', 'Request a driving route');
navigationCheck($query['dir_action'] === 'navigate', 'Request navigation');
navigationCheck(!isset($query['origin']), 'Let Maps determine the current device origin');
navigationCheck(str_contains($url, '14.3284%2C100.4042'), 'Encode the coordinate separator');
parse_str(parse_url(googleMapsUrl('-33.865143', '151.2099', false), PHP_URL_QUERY), $search);
navigationCheck($search['query'] === '-33.865143,151.2099' && !isset($search['destination']), 'Location previews preserve their own coordinates');
navigationCheck(parse_url(googleMapsUrl(0, 0, false), PHP_URL_PATH) === '/maps/search/', 'Valid zero coordinates can be displayed');
navigationCheck(googleMapsUrl(90, -180) !== null, 'Accept valid coordinate boundaries');
foreach ([[null,100],['',100],[' ',100],['invalid',100],[14,'100 east'],[100,14],[91,100],[14,181],[-91,100],[14,-181],[INF,100],[14,NAN],[[],100]] as [$lat,$lng]) {
    navigationCheck(googleMapsUrl($lat,$lng) === null, 'Reject invalid or incomplete coordinates');
}
echo "Passed {$checks} navigation checks; no database writes.\n";
