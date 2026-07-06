<?php
session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$logged_in = $is_clinic_staff
    ? !empty($_SESSION['staff_id'])
    : !empty($_SESSION['user_id']);

if (!$logged_in) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([
        "success" => true,
        "doctors" => []
    ]);
    exit;
}

$like = '%' . $q . '%';

/* ===========================
   أطباء العيادات (clinic_staff) + اسم ووِلاية العيادة
=========================== */

$stmt = $pdo->prepare("
    SELECT
        cs.id,
        cs.full_name,
        cs.specialty,
        c.wilaya AS wilaya,
        c.name AS clinic_name
    FROM clinic_staff cs
    LEFT JOIN clinics c ON c.id = cs.clinic_id
    WHERE cs.role = 'doctor'
      AND cs.account_status = 'active'
      AND (
            cs.full_name LIKE ?
         OR cs.specialty LIKE ?
         OR c.wilaya LIKE ?
      )
    ORDER BY cs.full_name
    LIMIT 30
");
$stmt->execute([$like, $like, $like]);
$clinic_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   الأطباء الخواص (users JOIN doctors)
=========================== */

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name,
        u.profile_picture,
        d.specialty,
        d.wilaya,
        d.workplace
    FROM users u
    INNER JOIN doctors d ON d.user_id = u.id
    WHERE u.role = 'doctor'
      AND u.account_status = 'active'
      AND u.status = 'approved'
      AND (
            u.full_name LIKE ?
         OR d.specialty LIKE ?
         OR d.wilaya LIKE ?
      )
    ORDER BY u.full_name
    LIMIT 30
");
$stmt->execute([$like, $like, $like]);
$private_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   توحيد شكل النتائج
=========================== */

$doctors = [];

foreach ($clinic_rows as $row) {
    $doctors[] = [
        "id"          => "clinic_" . $row['id'],
        "full_name"   => $row['full_name'],
        "specialty"   => $row['specialty'] ?: null,
        "wilaya"      => $row['wilaya'] ?: null,
        "clinic_name" => $row['clinic_name'] ?: null,
        "avatar"      => null
    ];
}

foreach ($private_rows as $row) {
    $doctors[] = [
        "id"          => "user_" . $row['id'],
        "full_name"   => $row['full_name'],
        "specialty"   => $row['specialty'] ?: null,
        "wilaya"      => $row['wilaya'] ?: null,
        "clinic_name" => $row['workplace'] ?: null,
        "avatar"      => $row['profile_picture'] ?: null
    ];
}

usort($doctors, function ($a, $b) {
    return strcmp($a['full_name'], $b['full_name']);
});

echo json_encode([
    "success" => true,
    "doctors" => $doctors
]);

exit;
