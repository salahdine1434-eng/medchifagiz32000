<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// نفس معرّف العيادة الموحّد المستعمل في save/get/update
$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;
$id        = intval($_POST['id'] ?? 0);

if (empty($clinic_id) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

// الحذف مقيّد بعيادة المستخدم الحالي فقط — لا يمكن حذف طاقم عيادة أخرى
$stmt = $pdo->prepare("DELETE FROM clinic_staff WHERE id = ? AND clinic_id = ?");
$stmt->execute([$id, $clinic_id]);

echo json_encode([
    'success' => $stmt->rowCount() > 0
], JSON_UNESCAPED_UNICODE);
