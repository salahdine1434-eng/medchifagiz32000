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

$id        = (int)($_POST['id']        ?? 0);
$is_active = (int)($_POST['is_active'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'معرف المصلحة مطلوب']);
    exit;
}

// التأكد أن المصلحة تعود للعيادة الحالية
$check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND clinic_id = ?");
$check->execute([$id, $clinic_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'المصلحة غير موجودة']);
    exit;
}

$stmt = $pdo->prepare("UPDATE services SET is_active = ? WHERE id = ? AND clinic_id = ?");

if ($stmt->execute([$is_active, $id, $clinic_id])) {
    echo json_encode([
        'success'   => true,
        'is_active' => $is_active,
        'message'   => $is_active ? 'تم تفعيل المصلحة' : 'تم تعطيل المصلحة'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل تغيير الحالة']);
}
