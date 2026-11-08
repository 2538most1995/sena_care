<?php
/**
 * Consolidate Learning Centers (ศกร.ระดับตำบล) in sena_care
 * Merge duplicate groups (ประถม, ม.ต้น, ม.ปลาย) into 1 distinct learning center per subdistrict.
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/database.php';

$pdo = Database::getConnection();

// Mapping of 4-digit code suffix to official center info
$centersMaster = [
    '0001' => ['name' => 'ศกร.ระดับตำบลสามกอ', 'subdistrict' => 'สามกอ', 'phone' => '035-201-239'],
    '0002' => ['name' => 'ศกร.ระดับตำบลบางนมโค', 'subdistrict' => 'บางนมโค', 'phone' => '035-201-236'],
    '0003' => ['name' => 'ศกร.ระดับตำบลชายนา', 'subdistrict' => 'ชายนา', 'phone' => ''],
    '0004' => ['name' => 'ศกร.ระดับตำบลเจ้าเจ็ด', 'subdistrict' => 'เจ้าเจ็ด', 'phone' => ''],
    '0005' => ['name' => 'ศกร.ระดับตำบลลาดงา', 'subdistrict' => 'ลาดงา', 'phone' => ''],
    '0006' => ['name' => 'ศกร.ระดับตำบลบ้านโพธิ์', 'subdistrict' => 'บ้านโพธิ์', 'phone' => ''],
    '0007' => ['name' => 'สกร.ระดับอำเภอเสนา', 'subdistrict' => 'เสนา', 'phone' => '035-201-234'],
    '0008' => ['name' => 'ศกร.ระดับตำบลดอนทอง', 'subdistrict' => 'ดอนทอง', 'phone' => ''],
    '0009' => ['name' => 'ศกร.ระดับตำบลบ้านแพน', 'subdistrict' => 'บ้านแพน', 'phone' => '035-201-238'],
    '0010' => ['name' => 'ศกร.ระดับตำบลสามตุ่ม', 'subdistrict' => 'สามตุ่ม', 'phone' => ''],
    '0011' => ['name' => 'ศกร.ระดับตำบลเสนา', 'subdistrict' => 'เสนา', 'phone' => '035-201-235'],
    '0012' => ['name' => 'ศกร.ระดับตำบลหัวเวียง', 'subdistrict' => 'หัวเวียง', 'phone' => '035-201-237'],
    '0013' => ['name' => 'ศกร.ระดับตำบลรางจรเข้', 'subdistrict' => 'รางจรเข้', 'phone' => ''],
    '0014' => ['name' => 'ศกร.ระดับตำบลมารวิชัย', 'subdistrict' => 'มารวิชัย', 'phone' => ''],
    '0015' => ['name' => 'ศกร.ระดับตำบลเจ้าเสด็จ', 'subdistrict' => 'เจ้าเสด็จ', 'phone' => ''],
    '0016' => ['name' => 'ศกร.ระดับตำบลบ้านแถว', 'subdistrict' => 'บ้านแถว', 'phone' => ''],
    '0017' => ['name' => 'ศกร.ระดับตำบลบ้านกระทุ่ม', 'subdistrict' => 'บ้านกระทุ่ม', 'phone' => ''],
    '0018' => ['name' => 'ศกร.ระดับตำบลบ้านหลวง', 'subdistrict' => 'บ้านหลวง', 'phone' => ''],
    '0019' => ['name' => 'อสม.ดอนทอง', 'subdistrict' => 'ดอนทอง', 'phone' => ''],
    '0020' => ['name' => 'พระดาบส', 'subdistrict' => 'เสนา', 'phone' => ''],
];

// Mapping of seed centers (LC-01 to LC-05) to 4-digit code
$seedMap = [
    1 => '0011', // เสนา
    2 => '0002', // บางนมโค
    3 => '0012', // หัวเวียง
    4 => '0009', // บ้านแพน
    5 => '0001', // สามกอ
];

$pdo->beginTransaction();

try {
    // 1. Merge Organization 3 into Organization 1 if exists
    $org3 = $pdo->query("SELECT id FROM organizations WHERE id = 3")->fetchColumn();
    if ($org3) {
        $pdo->exec("UPDATE users SET organization_id = 1 WHERE organization_id = 3");
        $pdo->exec("UPDATE learning_centers SET organization_id = 1 WHERE organization_id = 3");
        $pdo->exec("UPDATE sdl_links SET local_id = 1 WHERE entity = 'district' AND source_key = '1'");
        $pdo->exec("DELETE FROM organizations WHERE id = 3");
        echo "Merged Organization 3 into Organization 1\n";
    }

    // 2. For each center, find all matching learning_centers rows
    $allCenters = $pdo->query("SELECT id, center_code, center_name FROM learning_centers")->fetchAll();

    $codeToIds = [];
    foreach ($allCenters as $c) {
        $id = (int)$c['id'];
        $code = (string)$c['center_code'];
        $name = trim((string)$c['center_name']);

        $matchedSuffix = null;
        if (isset($seedMap[$id])) {
            $matchedSuffix = $seedMap[$id];
        } elseif (strlen($code) >= 6 && in_array(substr($code, 0, 2), ['21', '22', '23'])) {
            $matchedSuffix = substr($code, 2);
        } else {
            // Match by name
            foreach ($centersMaster as $sfx => $info) {
                if ($name === $info['name'] || ($sfx === '0003' && $name === 'ศกร.ตำบลชายนา')) {
                    $matchedSuffix = $sfx;
                    break;
                }
            }
        }

        if ($matchedSuffix && isset($centersMaster[$matchedSuffix])) {
            $codeToIds[$matchedSuffix][] = $id;
        } else {
            echo "Warning: Unmatched center id={$id}, code={$code}, name={$name}\n";
        }
    }

    // 3. For each group of duplicate IDs, designate the lowest ID as canonical
    foreach ($centersMaster as $sfx => $info) {
        if (empty($codeToIds[$sfx])) {
            echo "No existing rows for {$sfx} ({$info['name']})\n";
            continue;
        }

        $ids = array_unique($codeToIds[$sfx]);
        sort($ids);
        $canonicalId = array_shift($ids); // keep lowest ID
        $duplicateIds = $ids;

        echo "Center {$sfx} ({$info['name']}): Canonical ID = {$canonicalId}, duplicates to merge: " . implode(',', $duplicateIds) . "\n";

        if (!empty($duplicateIds)) {
            $inList = implode(',', $duplicateIds);

            // Re-point enrollments
            $pdo->exec("UPDATE enrollments SET learning_center_id = {$canonicalId} WHERE learning_center_id IN ({$inList})");

            // Re-point users
            $pdo->exec("UPDATE users SET learning_center_id = {$canonicalId} WHERE learning_center_id IN ({$inList})");

            // Re-point sdl_links
            $pdo->exec("UPDATE sdl_links SET local_id = {$canonicalId} WHERE entity = 'group' AND local_id IN ({$inList})");

            // Delete duplicates
            $pdo->exec("DELETE FROM learning_centers WHERE id IN ({$inList})");
        }

        // Update canonical center details
        $stmtUp = $pdo->prepare(
            "UPDATE learning_centers SET
                organization_id = 1,
                center_code = ?,
                center_name = ?,
                subdistrict = ?,
                district = 'เสนา',
                province = 'พระนครศรีอยุธยา',
                phone = COALESCE(NULLIF(phone, ''), ?),
                status = 'active'
             WHERE id = ?"
        );
        $stmtUp->execute([
            $sfx,
            $info['name'],
            $info['subdistrict'],
            $info['phone'] ?: null,
            $canonicalId
        ]);
    }

    // 4. Update teachers' learning_center_id from their username if username has the 4-digit suffix
    $teachers = $pdo->query("SELECT id, username FROM users WHERE role = 'teacher' AND username REGEXP '^121412[0-9]{4}$'")->fetchAll();
    foreach ($teachers as $t) {
        $teacherSuffix = substr($t['username'], -4);
        $cId = $pdo->query("SELECT id FROM learning_centers WHERE center_code = '{$teacherSuffix}' LIMIT 1")->fetchColumn();
        if ($cId) {
            $pdo->exec("UPDATE users SET learning_center_id = {$cId} WHERE id = {$t['id']}");
            echo "Assigned teacher {$t['username']} to center {$teacherSuffix} (ID {$cId})\n";
        }
    }

    $pdo->commit();
    echo "\nConsolidation completed successfully!\n";

    // Summary count
    $cnt = $pdo->query("SELECT COUNT(*) FROM learning_centers")->fetchColumn();
    echo "Total learning centers now: {$cnt}\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
