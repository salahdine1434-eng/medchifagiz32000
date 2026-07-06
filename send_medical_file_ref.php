<?php
/* ================================================================
   send_medical_file_ref.php
   يُرسل "مرجع" وثيقة طبية موجودة أصلاً في القاعدة داخل المحادثة،
   دون رفع ولا نسخ: يُدرج رسالة تستعمل أعمدة المرفقات الموجودة
   (attachment_path/name/type) بحيث تظهر كبطاقة مرفق عادية في
   الواجهتين، وعند فتحها تُعرض الوثيقة عبر view_medical_document.php.
   المدخلات (POST): record_id , type
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

function sresp($ok, $msg = '') {
    echo json_encode(['success' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) sresp(false, 'غير مصرح');

$role     = $_SESSION['role'];
if ($role !== 'doctor' && $role !== 'patient') sresp(false, 'غير مصرح');

$recordId = intval($_POST['record_id'] ?? 0);
$type     = preg_replace('/[^a-z]/', '', $_POST['type'] ?? '');

$names = [
    'dossier'    => 'Dossier Médical',
    'rapport'    => 'Rapport Médical',
    'fiche'      => 'Fiche de traitement',
    'analyses'   => "Résultats d'analyses",
    'radiologie' => 'Radiologie',
    'ordonnance' => 'Ordonnance',
];

if ($recordId <= 0 || !isset($names[$type])) sresp(false, 'بيانات غير صحيحة');

/* ── طرفا السجل ── */
$stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM medical_records WHERE id = ?");
$stmt->execute([$recordId]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rec) sresp(false, 'السجل غير موجود');

$patientUserId = intval($rec['patient_id']); // user_id للمريض

$d = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
$d->execute([$rec['doctor_id']]);
$docRow = $d->fetch(PDO::FETCH_ASSOC);
if (!$docRow) sresp(false, 'الطبيب غير موجود');
$doctorUserId = intval($docRow['user_id']);

/* ── تحديد المُرسِل (نفس منطق الإرسال الحالي) ── */
if ($role === 'doctor') {
    if ($_SESSION['user_id'] != $doctorUserId) sresp(false, 'غير مصرح');
    $senderId = $doctorUserId; $receiverId = $patientUserId; $senderRole = 'doctor';
} else {
    if ($_SESSION['user_id'] != $patientUserId) sresp(false, 'غير مصرح');
    $senderId = $patientUserId; $receiverId = $doctorUserId; $senderRole = 'patient';
}

/* ── مرجع الوثيقة (رابط العارض، لا ملف حقيقي يُرفع) ── */
$viewUrl = 'view_medical_document.php?type=' . $type . '&record_id=' . $recordId;
$fileName = $names[$type] . '.html';

/* ── الإدراج باستعمال أعمدة المرفقات الموجودة أصلاً ── */
try {
    $stmt = $pdo->prepare("
        INSERT INTO medical_messages
            (record_id, doctor_id, patient_user_id, sender_id, receiver_id, sender_role,
             message, attachment_path, attachment_name, attachment_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $recordId,
        $doctorUserId,
        $patientUserId,
        $senderId,
        $receiverId,
        $senderRole,
        '',
        $viewUrl,
        $fileName,
        'medical/document'
    ]);
    sresp(true, '');
} catch (PDOException $e) {
    sresp(false, 'تعذّر إرسال الوثيقة');
}
