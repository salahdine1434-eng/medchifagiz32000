<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}

$clinic_id = $_SESSION['user_id'] ?? null;
if (!$clinic_id) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$id         = (int)($_POST['id']          ?? 0);
$name       = trim($_POST['name']         ?? '');
$has_rooms  = (int)($_POST['has_rooms']   ?? 0);
$room_data  = $_POST['room_data']         ?? null;
$total_rooms= (int)($_POST['total_rooms'] ?? 0);
$total_beds = (int)($_POST['total_beds']  ?? 0);

if (!$id || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

// التأكد أن المصلحة تعود للعيادة الحالية
$check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND clinic_id = ?");
$check->execute([$id, $clinic_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'المصلحة غير موجودة']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE services
    SET name        = ?,
        has_rooms   = ?,
        room_data   = ?,
        total_rooms = ?,
        total_beds  = ?
    WHERE id = ? AND clinic_id = ?
");

if ($stmt->execute([$name, $has_rooms, $room_data, $total_rooms, $total_beds, $id, $clinic_id])) {
    echo json_encode(['success' => true, 'message' => 'تم حفظ التعديلات']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ التعديلات']);
}
