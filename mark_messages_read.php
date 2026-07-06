<?php
/* ================================================================
   mark_messages_read.php
   يعلّم رسائل محادثة معيّنة كمقروءة — فقط الرسائل الموجَّهة إلى
   المستخدم الحالي (receiver_id = أنا) وغير المقروءة بعد. يُستدعى
   عند فتح المحادثة النشطة وتحميل رسائلها.
   المدخلات (GET/POST):
     - واجهة المريض: doctor_id
     - واجهة الطبيب: record_id
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false]);
    exit;
}

$sessionUserId = intval($_SESSION['user_id']);
$role          = $_SESSION['role'];

$doctorUserId  = null;
$patientUserId = null;

$recordId = intval($_REQUEST['record_id'] ?? 0);
$doctorId = intval($_REQUEST['doctor_id'] ?? 0);

if ($recordId > 0) {
    $stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM medical_records WHERE id = ?");
    $stmt->execute([$recordId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rec) { echo json_encode(['success' => false]); exit; }

    $patientUserId = intval($rec['patient_id']);

    $d = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $d->execute([$rec['doctor_id']]);
    $docRow = $d->fetch(PDO::FETCH_ASSOC);
    if (!$docRow) { echo json_encode(['success' => false]); exit; }
    $doctorUserId = intval($docRow['user_id']);

} elseif ($doctorId > 0) {
    $doctorUserId  = $doctorId;
    $patientUserId = $sessionUserId;
} else {
    echo json_encode(['success' => false]);
    exit;
}

/* التحقق أن المستخدم الحالي طرف في هذه المحادثة */
if ($role === 'doctor') {
    if ($sessionUserId !== $doctorUserId) { echo json_encode(['success' => false]); exit; }
} elseif ($role === 'patient') {
    if ($sessionUserId !== $patientUserId) { echo json_encode(['success' => false]); exit; }
} else {
    echo json_encode(['success' => false]);
    exit;
}

/* تعليم الرسائل الموجَّهة إلى المستخدم الحالي فقط كمقروءة */
$upd = $pdo->prepare("
    UPDATE medical_messages
    SET is_read = 1
    WHERE doctor_id = ?
      AND patient_user_id = ?
      AND receiver_id = ?
      AND is_read = 0
");
$upd->execute([$doctorUserId, $patientUserId, $sessionUserId]);

echo json_encode(['success' => true, 'updated' => $upd->rowCount()]);
