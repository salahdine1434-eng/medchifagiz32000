<?php
/* ================================================================
   upload_voice.php
   رفع الرسائل الصوتية (منفصل تماماً عن الرسائل النصية والمرفقات).
   يستعمل نفس منطق التحقق وتحديد المُرسِل، ثم:
   - يقبل ملفات الصوت فقط.
   - يحفظ التسجيل داخل uploads/chat_voice/ باسم فريد.
   - يسجّل voice_path و voice_duration مع الرسالة في medical_messages.
   المدخلات (POST multipart):
     record_id (int) , voice (ملف صوتي) , duration (ثواني)
================================================================ */

session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

function vrespond($ok, $msg = '', $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    vrespond(false, 'غير مصرح');
}

$role = $_SESSION['role'];
if ($role !== 'doctor' && $role !== 'patient') {
    vrespond(false, 'غير مصرح');
}

$record_id = intval($_POST['record_id'] ?? 0);
$duration  = intval($_POST['duration'] ?? 0);

if ($record_id <= 0) {
    vrespond(false, 'بيانات غير صحيحة');
}

if (!isset($_FILES['voice']) || $_FILES['voice']['error'] !== UPLOAD_ERR_OK) {
    vrespond(false, 'لم يتم استلام التسجيل');
}

/* حجم أقصى (20 ميغابايت) */
if ($_FILES['voice']['size'] > 20 * 1024 * 1024) {
    vrespond(false, 'حجم التسجيل كبير جداً');
}

/* ---- قبول ملفات الصوت فقط ---- */
$mime = '';
if (function_exists('mime_content_type')) {
    $mime = @mime_content_type($_FILES['voice']['tmp_name']);
}
if (!$mime) {
    $mime = $_FILES['voice']['type'] ?: '';
}
// MediaRecorder ينتج عادةً audio/webm أو audio/ogg؛ نتحقق أنه صوت
if (strpos($mime, 'audio/') !== 0 && strpos($mime, 'video/webm') !== 0) {
    // بعض المتصفحات ترسل webm بنوع video/webm رغم أنه صوت فقط
    vrespond(false, 'يُسمح بملفات الصوت فقط');
}

/* اشتقاق الامتداد من النوع */
$ext = 'webm';
if (strpos($mime, 'ogg') !== false)      $ext = 'ogg';
elseif (strpos($mime, 'mp4') !== false)  $ext = 'm4a';
elseif (strpos($mime, 'mpeg') !== false) $ext = 'mp3';
elseif (strpos($mime, 'wav') !== false)  $ext = 'wav';

/* ---- تحديد طرفَي المحادثة (نفس منطق الإرسال) ---- */
$stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM medical_records WHERE id = ?");
$stmt->execute([$record_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    vrespond(false, 'الملف غير موجود');
}

$patientUserId = intval($row['patient_id']);

$stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
$stmt->execute([$row['doctor_id']]);
$doctorRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctorRow) {
    vrespond(false, 'الطبيب غير موجود');
}
$doctorUserId = intval($doctorRow['user_id']);

/* ---- تحديد المُرسِل والتأكد أنه طرف في المحادثة ---- */
if ($role === 'doctor') {
    if ($_SESSION['user_id'] != $doctorUserId) {
        vrespond(false, 'غير مصرح');
    }
    $senderId   = $_SESSION['user_id'];
    $receiverId = $patientUserId;
    $senderRole = 'doctor';
} else {
    if ($_SESSION['user_id'] != $patientUserId) {
        vrespond(false, 'غير مصرح');
    }
    $senderId   = $_SESSION['user_id'];
    $receiverId = $doctorUserId;
    $senderRole = 'patient';
}

/* ---- مجلد الصوت + حماية من تنفيذ السكربتات ---- */
$dir = __DIR__ . '/uploads/chat_voice';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
$htaccess = $dir . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\n");
}

/* ---- اسم فريد ---- */
$unique = 'voice_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $dir . '/' . $unique;
$relativePath = 'uploads/chat_voice/' . $unique;

if (!move_uploaded_file($_FILES['voice']['tmp_name'], $targetPath)) {
    vrespond(false, 'تعذّر حفظ التسجيل');
}

if ($duration < 0) $duration = 0;

/* ---- الحفظ في قاعدة البيانات (message فارغ مع رسالة صوتية) ---- */
$stmt = $pdo->prepare("
    INSERT INTO medical_messages
        (record_id, doctor_id, patient_user_id, sender_id, receiver_id, sender_role,
         message, voice_path, voice_duration)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $record_id,
    $doctorUserId,
    $patientUserId,
    $senderId,
    $receiverId,
    $senderRole,
    '',
    $relativePath,
    $duration
]);

vrespond(true, '', [
    'voice_path' => $relativePath,
    'voice_duration' => $duration
]);
