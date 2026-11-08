<?php
// Fake API only: no database writes or external requests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/SdlApiClient.php';
require APP_ROOT . '/app/services/GraduateDocuments.php';
class DocumentsFixtureApi extends SdlApiClient
{
    public array $pages = [];
    public function request(string $path, ?array $body = null): array
    {
        parse_str(parse_url($path, PHP_URL_QUERY), $query);
        if (($query['search'] ?? '') !== 'TEST001' || ($query['term'] ?? '') !== '2/2554') throw new RuntimeException('Incorrect API query');
        return $this->pages[(int)$query['page']];
    }
}
function checkDocuments(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$api = new DocumentsFixtureApi([]);
$graduate = ['student_code' => 'TEST001', 'graduation_term' => '2/2554', 'level_id' => 2];
$row = ['code'=>'TEST001','graduation_term'=>'2/2554','level'=>['id'=>2],'transcript_number'=>'000123','transcript_series'=>'00001','certificate_number'=>'TEST-CERT-0001','transcript_date'=>'2012-03-30','graduation_date'=>'2012-03-30','citizen_id'=>'EXCLUDED'];
$api->pages = [1=>['data'=>[array_replace($row, ['code'=>'TEST0019'])], 'meta'=>['selected_term'=>'2/2554','pagination'=>['last_page'=>2]]], 2=>['data'=>[$row], 'meta'=>['selected_term'=>'2/2554','pagination'=>['last_page'=>2]]]];
$service = new GraduateDocuments($api);
$data = $service->find($graduate);
checkDocuments($data['transcript_series'] === '00001', 'Leading zeroes must be retained');
checkDocuments($data['transcript_number'] === '000123' && $data['certificate_number'] === 'TEST-CERT-0001', 'Document numbers are mapped correctly');
checkDocuments($data['graduation_date'] === '2012-03-30', 'Graduation date is mapped correctly');
checkDocuments(!str_contains(json_encode($data), 'EXCLUDED'), 'Only allowlisted fields are returned');
$api->pages[2]['data'][] = $row;
checkDocuments($service->find($graduate) === null, 'Ambiguous identities must not show another record');
$api->pages[2]['data'] = [array_replace($row, ['level'=>['id'=>3]])];
checkDocuments($service->find($graduate) === null, 'Different levels must not be matched');
$api->pages[1]['meta']['selected_term'] = '1/2554';
try { $service->find($graduate); throw new LogicException('Expected invalid term'); }
catch (RuntimeException $e) { checkDocuments(str_contains($e->getMessage(), 'ภาคเรียน'), 'Wrong selected term must fail'); }
echo "Passed graduate document identity, pagination and field checks; no database writes.\n";
