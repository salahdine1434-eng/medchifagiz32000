<?php
/* ================================================================
   delete_medical_message.php
   حذف ناعم (Soft Delete) لرسالة — لا يحذف السجل نهائياً، بل يضع
   is_deleted = 1. يُسمح فقط لصاحب الرسالة (المُرسِل) بحذفها.
   المدخل (POST): message_id
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$messageId = intval($_POST['message_id'] ?? 0);
if ($messageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

/* جلب الرسالة والتأكد أن المستخدم الحالي هو مُرسِلها */
$stmt = $pdo->prepare("SELECT sender_id FROM medical_messages WHERE id = ?");
$stmt->execute([$messageId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'الرسالة غير موجودة']);
    exit;
}

if (intval($row['sender_id']) !== intval($_SESSION['user_id'])) {
    // صاحب الرسالة فقط يمكنه حذفها
    echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف رسالة لست مُرسِلها']);
    exit;
}

$upd = $pdo->prepare("UPDATE medical_messages SET is_deleted = 1 WHERE id = ? AND sender_id = ?");
$ok = $upd->execute([$messageId, intval($_SESSION['user_id'])]);

echo json_encode(['success' => (bool)$ok]);
