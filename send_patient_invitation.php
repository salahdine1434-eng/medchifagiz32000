<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

require 'db.php';
require 'send_otp_email.php';

$recordId = intval($_POST['record_id'] ?? 0);

if ($recordId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المريض غير صالح'
    ]);
    exit;
}

// جلب الملف الطبي
$stmt = $pdo->prepare("
    SELECT full_name, email
    FROM medical_records
    WHERE id = ?
");
$stmt->execute([$recordId]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode([
        'success' => false,
        'message' => 'المريض غير موجود'
    ]);
    exit;
}

if (empty($patient['email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'لا يوجد بريد إلكتروني لهذا المريض'
    ]);
    exit;
}

// اسم الطبيب
$stmt = $pdo->prepare("
    SELECT u.full_name
    FROM doctors d
    INNER JOIN users u ON d.user_id = u.id
    WHERE d.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);

$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$doctorName = $doctor['full_name'] ?? 'طبيبك';
$stmt->execute([$_SESSION['user_id']]);

$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$doctorName = $doctor['full_name'] ?? 'طبيبك';

// إرسال الدعوة
if (sendInvitationEmail($patient['email'], $doctorName)) {

    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الدعوة بنجاح'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'فشل إرسال البريد الإلكتروني'
    ]);

}