<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * get_doctors.php
 * FIX: استخدام PDO بدل mysqli، إصلاح فلترة التخصص (ID → نص)، إضافة specialty_name و lat/lng
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

$wilaya    = trim($_GET['wilaya']    ?? '');
$commune   = trim($_GET['commune']   ?? '');
$specialty = trim($_GET['specialty'] ?? ''); // قد يكون ID أو نص

// بناء الاستعلام
$params = [];
$where  = ["d.is_profile_complete = 1"];

if ($wilaya !== '') {
    $where[]  = "LOWER(d.wilaya) LIKE LOWER(?)";
    $params[] = "%{$wilaya}%";
}

if ($commune !== '') {
    $where[]  = "LOWER(d.commune) LIKE LOWER(?)";
    $params[] = "%{$commune}%";
}

if ($specialty !== '') {
    if (ctype_digit($specialty)) {

        $stmtSpec = $pdo->prepare("SELECT name_fr, name_ar FROM specialties WHERE id = ?");
        $stmtSpec->execute([(int)$specialty]);
        $specRow = $stmtSpec->fetch(PDO::FETCH_ASSOC);

        if ($specRow) {
            $where[] = "(
                LOWER(d.specialty) LIKE LOWER(?)
                OR LOWER(d.specialty) LIKE LOWER(?)
                OR LOWER(d.specialty) LIKE LOWER(?)
            )";

            $params[] = "%{$specRow['name_ar']}%"; // عربي
            $params[] = "%{$specRow['name_fr']}%"; // فرنسي
            $params[] = "%{$specialty}%";          // id
        }

    } else {
        $where[] = "LOWER(d.specialty) LIKE LOWER(?)";
        $params[] = "%{$specialty}%";
    }
}

$whereClause = implode(" AND ", $where);

$sql = "
    SELECT
        d.id,
        u.full_name,
        d.specialty       AS specialty_name,
        d.wilaya,
        d.commune,
        d.workplace,
        d.experience,
        COALESCE(d.lat, 0) AS lat,
        COALESCE(d.lng, 0) AS lng
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE {$whereClause}
    ORDER BY d.id DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
