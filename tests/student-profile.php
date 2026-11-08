<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/SdlStudentProfile.php';
$payload = [
    'citizen_id' => 'EXCLUDED',
    'personal' => ['birthdate_display' => '01/01/2545', 'gender' => 'หญิง', 'occupation' => 'ค้าขาย', 'email' => 'test@example.test', 'citizen_id' => 'EXCLUDED', 'citizen_id_masked' => 'EXCLUDED'],
    'guardians' => [['name' => 'ผู้ปกครองทดสอบ', 'relationship' => 'มารดา', 'citizen_id' => 'EXCLUDED'], ['name' => null]],
];
$fields = SdlStudentProfile::fields($payload);
$contacts = SdlStudentProfile::contacts($payload);
assert($fields['gender'] === 'female');
assert($fields['birthdate_display'] === '01/01/2545');
assert(!str_contains(json_encode([$fields, $contacts]), 'EXCLUDED'));
assert(count($contacts) === 1 && $contacts[0]['relationship'] === 'มารดา');
assert(SdlStudentProfile::fields([]) === []);
assert(SdlStudentProfile::fields(['personal' => 'invalid']) === []);
assert(SdlStudentProfile::contacts(['guardians' => 'invalid']) === []);
$official = [
    'demographics' => ['birth_date' => '02/03/2545', 'gender' => 'ชาย', 'citizen_id_masked' => 'EXCLUDED'],
    'contact' => ['phone' => '0890000000', 'email' => 'api@example.test', 'current_address' => 'ที่อยู่ปัจจุบันทดสอบ', 'registered_address' => 'ทะเบียนบ้านทดสอบ', 'citizen_id' => 'EXCLUDED'],
    'profile' => ['personal' => ['occupation_label' => 'ค้าขาย', 'nationality_label' => 'ไทย', 'religion_label' => 'พุทธ', 'citizen_id' => 'EXCLUDED'], 'family' => ['mother' => ['name' => 'มารดาทดสอบ', 'occupation_label' => 'รับจ้าง'], 'guardian' => ['name' => 'มารดาทดสอบ']]],
    'guardian' => ['name' => 'มารดาทดสอบ', 'phone' => '0810000000', 'citizen_id' => 'EXCLUDED'],
];
$mapped = SdlStudentProfile::fields($official);
$family = SdlStudentProfile::contacts($official);
assert($mapped['birthdate_display'] === '02/03/2545' && $mapped['gender'] === 'male');
assert($mapped['occupation'] === 'ค้าขาย' && $mapped['email'] === 'api@example.test');
assert($mapped['source_current_address'] === 'ที่อยู่ปัจจุบันทดสอบ');
assert($mapped['nationality'] === 'ไทย' && $mapped['religion'] === 'พุทธ');
assert(count($family) === 1 && $family[0]['relationship'] === 'มารดา' && $family[0]['phone'] === '0810000000');
assert(!str_contains(json_encode([$mapped, $family]), 'EXCLUDED'));
assert(SdlStudentProfile::fields(['demographics' => [], 'contact' => []]) === []);
echo "Passed 14 profile checks; no database writes.\n";
