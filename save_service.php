<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'طلب غير صالح'
    ]);
    exit;
}

$clinic_id = $_SESSION['user_id'];

$name = trim($_POST['name'] ?? '');

$has_rooms = isset($_POST['has_rooms']) ? (int)$_POST['has_rooms'] : 0;

$room_data = $_POST['room_data'] ?? null;

$total_rooms = isset($_POST['total_rooms'])
    ? (int)$_POST['total_rooms']
    : 0;

$total_beds = isset($_POST['total_beds'])
    ? (int)$_POST['total_beds']
    : 0;

if (empty($name)) {
    echo json_encode([
        'success' => false,
        'message' => 'اسم المصلحة مطلوب'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO services (
        clinic_id,
        name,
        has_rooms,
        room_data,
        total_rooms,
        total_beds
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

if ($stmt->execute([
    $clinic_id,
    $name,
    $has_rooms,
    $room_data,
    $total_rooms,
    $total_beds
])) {
    echo json_encode([
        'success' => true,
        'service_id' => $pdo->lastInsertId(),
        'message' => 'تم حفظ المصلحة'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'فشل حفظ المصلحة'
    ]);
}
?>