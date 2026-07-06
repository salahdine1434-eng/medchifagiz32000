<?php
/* ================================================================
   edit_medical_message.php
   تعديل نص رسالة — يُسمح فقط لصاحب الرسالة (المُرسِل)، وفقط للرسائل
   النصية (بدون مرفق ولا صوت). لا يغيّر created_at. يضع is_edited = 1.
   المدخلات (POST): message_id , message
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$messageId = intval($_POST['message_id'] ?? 0);
$newText   = trim($_POST['message'] ?? '');

if ($messageId <= 0 || $newText === '') {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

/* جلب الرسالة والتأكد من الملكية والنوع */
$stmt = $pdo->prepare("SELECT sender_id, attachment_path, voice_path, is_deleted FROM medical_messages WHERE id = ?");
$stmt->execute([$messageId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'الرسالة غير موجودة']);
    exit;
}

/* صاحب الرسالة فقط */
if (intval($row['sender_id']) !== intval($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لا يمكنك تعديل رسالة لست مُرسِلها']);
    exit;
}

/* الرسائل النصية فقط: لا مرفق، لا صوت، وغير محذوفة */
$hasAttachment = isset($row['attachment_path']) && trim((string)$row['attachment_path']) !== '';
$hasVoice      = isset($row['voice_path'])      && trim((string)$row['voice_path'])      !== '';
if ($hasAttachment || $hasVoice || intval($row['is_deleted']) === 1) {
    echo json_encode(['success' => false, 'message' => 'لا يمكن تعديل هذه الرسالة']);
    exit;
}

/* التحديث: النص + is_edited، دون المساس بـ created_at */
$upd = $pdo->prepare("UPDATE medical_messages SET message = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
$ok = $upd->execute([$newText, $messageId, intval($_SESSION['user_id'])]);

echo json_encode(['success' => (bool)$ok]);
