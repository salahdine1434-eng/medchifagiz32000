<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}
$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;
if (!$clinic_id) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
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

// فك ارتباط الموظفين بالمصلحة قبل الحذف
// (clinic_staff.service_id هو FK مع ON DELETE SET NULL — يُنفَّذ تلقائياً بقاعدة البيانات)
// لكن نضيف هذا كضمان إضافي إن لم تكن ON DELETE SET NULL مفعّلة
$pdo->prepare("UPDATE clinic_staff SET service_id = NULL WHERE service_id = ?")->execute([$id]);

$stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND clinic_id = ?");

if ($stmt->execute([$id, $clinic_id])) {
    echo json_encode(['success' => true, 'message' => 'تم حذف المصلحة']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حذف المصلحة']);
}
