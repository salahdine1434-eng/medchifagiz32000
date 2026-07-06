<?php
$conn = new mysqli("localhost", "root", "", "medchifagiz");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// القيم
$type    = $_GET['type']    ?? '';
$wilaya  = $_GET['wilaya']  ?? '';
$commune = $_GET['commune'] ?? '';
$sub     = $_GET['sub']     ?? '';

// حماية: الجداول المسموح بها فقط
$allowed = [
    'pharmacies',
    'labs',
    'nurses',
    'donors',
    'clinics',
    'civil_protection',
    'sport_health',
    'associations',
    'elderly',
    'orphans'
];

if (!in_array($type, $allowed)) {
    echo json_encode([]);
    exit;
}

// الاستعلام الأساسي
$sql = "SELECT * FROM $type";

$where = [];

// فلترة بالولاية (نفس doctors)
if ($wilaya != '') {
    $where[] = "LOWER(wilaya) LIKE LOWER('%$wilaya%')";
}

// فلترة بالبلدية
if ($commune != '') {
    $where[] = "LOWER(commune) LIKE LOWER('%$commune%')";
}
// فلترة الزمرة
// فلترة الزمرة
if ($type === 'donors' && isset($_GET['blood']) && $_GET['blood'] !== '') {
    $blood = $conn->real_escape_string($_GET['blood']);
    $where[] = "REPLACE(blood_type, ' ', '') = REPLACE('$blood', ' ', '')";
}
// فلترة إضافية (رياضة/تغذية)
if ($type === 'sport_health' && $sub != '') {
    $where[] = "sub_type = '$sub'";
}

// إضافة WHERE إذا كاين شروط
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// تنفيذ
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// إخراج JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>