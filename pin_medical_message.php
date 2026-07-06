<?php
/* ================================================================
   pin_medical_message.php
   تثبيت / إلغاء تثبيت رسالة — الطبيب فقط.
   رسالة مثبتة واحدة لكل محادثة: عند تثبيت رسالة جديدة يُلغى تثبيت
   السابقة تلقائياً ضمن نفس المحادثة (doctor_id + patient_user_id).
   المدخلات (POST): message_id , action = pin | unpin
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

/* الطبيب فقط */
if ($_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'التثبيت متاح للطبيب فقط']);
    exit;
}

$messageId = intval($_POST['message_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($messageId <= 0 || ($action !== 'pin' && $action !== 'unpin')) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

/* جلب المحادثة التي تنتمي لها الرسالة */
$stmt = $pdo->prepare("SELECT doctor_id, patient_user_id FROM medical_messages WHERE id = ?");
$stmt->execute([$messageId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'الرسالة غير موجودة']);
    exit;
}

/* doctor_id في medical_messages = user_id الخاص بالطبيب */
if (intval($row['doctor_id']) !== intval($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح بهذه المحادثة']);
    exit;
}

$doctorUserId  = intval($row['doctor_id']);
$patientUserId = intval($row['patient_user_id']);

try {
    if ($action === 'pin') {
        /* إلغاء تثبيت أي رسالة سابقة في نفس المحادثة، ثم تثبيت الجديدة */
        $clear = $pdo->prepare("UPDATE medical_messages SET is_pinned = 0 WHERE doctor_id = ? AND patient_user_id = ?");
        $clear->execute([$doctorUserId, $patientUserId]);

        $set = $pdo->prepare("UPDATE medical_messages SET is_pinned = 1 WHERE id = ?");
        $set->execute([$messageId]);
    } else {
        $unset = $pdo->prepare("UPDATE medical_messages SET is_pinned = 0 WHERE id = ?");
        $unset->execute([$messageId]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'تعذّر تحديث التثبيت']);
}
