<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

$role = $_SESSION['role'];

if ($role !== 'doctor' && $role !== 'patient') {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

$record_id = intval($_POST['record_id'] ?? 0);
$message   = trim($_POST['message'] ?? '');

if ($record_id <= 0 || $message == '') {
    echo json_encode([
        'success' => false,
        'message' => 'بيانات غير صحيحة'
    ]);
    exit;
}

// جلب بيانات السجل الطبي (صاحب الملف من المرضى + الطبيب صاحب الملف)
$stmt = $pdo->prepare("
    SELECT patient_id, doctor_id
    FROM medical_records
    WHERE id = ?
");
$stmt->execute([$record_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        'success' => false,
        'message' => 'الملف غير موجود'
    ]);
    exit;
}

// patient_id في medical_records يخزن أصلاً user_id الخاص بالمريض
$patientUserId = intval($row['patient_id']);

// doctor_id في medical_records يخزن id الخاص بجدول doctors (وليس user_id)
// بينما نظام المحادثة يعتمد على user_id الخاص بالطبيب في كل مكان
$stmt = $pdo->prepare("
    SELECT user_id
    FROM doctors
    WHERE id = ?
");
$stmt->execute([$row['doctor_id']]);
$doctorRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctorRow) {
    echo json_encode([
        'success' => false,
        'message' => 'الطبيب غير موجود'
    ]);
    exit;
}

$doctorUserId = intval($doctorRow['user_id']);

/* ============================================================
   تحديد المُرسِل الحقيقي:
   نعتمد على معرّف المستخدم المسجّل دخوله فعلاً ($_SESSION['user_id'])
   بمطابقته مع طرفَي هذا السجل (الطبيب/المريض) — وليس على
   $_SESSION['role'] وحده. السبب: عند تسجيل الدخول بحسابين (طبيب ومريض)
   في نفس المتصفح تُشارَك نفس جلسة PHP، فتصبح قيمة role قديمة/خاطئة،
   فكانت رسائل الطبيب تُحفَظ عن طريق الخطأ بـ sender_role='patient'.
   بالاعتماد على user_id مقابل أطراف السجل، تُحفَظ رسالة الطبيب دائماً
   'doctor' ورسالة المريض دائماً 'patient'، بشكل ثابت لا يختلط.
   ============================================================ */
$sessionUserId = intval($_SESSION['user_id']);

if ($sessionUserId === $doctorUserId) {

    // المستخدم المسجّل دخوله هو طبيب هذا السجل
    $senderId   = $doctorUserId;
    $receiverId = $patientUserId;
    $senderRole = 'doctor';

} elseif ($sessionUserId === $patientUserId) {

    // المستخدم المسجّل دخوله هو مريض هذا السجل
    $senderId   = $patientUserId;
    $receiverId = $doctorUserId;
    $senderRole = 'patient';

} else {

    // المستخدم الحالي ليس طرفاً في هذه المحادثة
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

$replyTo = isset($_POST['reply_to']) ? intval($_POST['reply_to']) : 0;
$replyTo = $replyTo > 0 ? $replyTo : null;

$stmt = $pdo->prepare("
INSERT INTO medical_messages
(record_id, doctor_id, patient_user_id, sender_id, receiver_id, sender_role, message, reply_to_message_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $record_id,
    $doctorUserId,
    $patientUserId,
    $senderId,
    $receiverId,
    $senderRole,
    $message,
    $replyTo
]);

echo json_encode([
    'success' => true
]);
