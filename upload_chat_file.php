<?php
/* ================================================================
   upload_chat_file.php
   نقطة رفع مرفقات التواصل الطبي (منفصلة عن send_medical_message.php
   حتى لا نمسّ نظام الرسائل النصية إطلاقاً). تستعمل نفس منطق التحقق
   وتحديد المُرسِل الموجود في send_medical_message.php، ثم:
   - تحفظ الملف داخل uploads/chat_files/ باسم فريد.
   - تسجّل مساره واسمه ونوعه مع الرسالة في medical_messages.
   المدخلات (POST multipart):
     record_id (int) , file (ملف) , message (اختياري: تعليق نصي)
================================================================ */

session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

function respond($ok, $msg = '', $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    respond(false, 'غير مصرح');
}

$role = $_SESSION['role'];
if ($role !== 'doctor' && $role !== 'patient') {
    respond(false, 'غير مصرح');
}

$record_id = intval($_POST['record_id'] ?? 0);
$caption   = trim($_POST['message'] ?? ''); // تعليق نصي اختياري مع المرفق

if ($record_id <= 0) {
    respond(false, 'بيانات غير صحيحة');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'لم يتم استلام أي ملف');
}

/* حجم أقصى (15 ميغابايت) */
$maxBytes = 15 * 1024 * 1024;
if ($_FILES['file']['size'] > $maxBytes) {
    respond(false, 'حجم الملف كبير جداً (الحد 15MB)');
}

/* ---- تحديد طرفَي المحادثة (نفس منطق send_medical_message.php) ---- */
$stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM medical_records WHERE id = ?");
$stmt->execute([$record_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    respond(false, 'الملف غير موجود');
}

$patientUserId = intval($row['patient_id']); // user_id الخاص بالمريض

$stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
$stmt->execute([$row['doctor_id']]);
$doctorRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctorRow) {
    respond(false, 'الطبيب غير موجود');
}
$doctorUserId = intval($doctorRow['user_id']);

/* ---- تحديد المُرسِل والتأكد أنه طرف في هذه المحادثة ---- */
if ($role === 'doctor') {
    if ($_SESSION['user_id'] != $doctorUserId) {
        respond(false, 'غير مصرح');
    }
    $senderId   = $_SESSION['user_id'];
    $receiverId = $patientUserId;
    $senderRole = 'doctor';
} else {
    if ($_SESSION['user_id'] != $patientUserId) {
        respond(false, 'غير مصرح');
    }
    $senderId   = $_SESSION['user_id'];
    $receiverId = $doctorUserId;
    $senderRole = 'patient';
}

/* ---- تجهيز الاسم والامتداد ---- */
$originalName = $_FILES['file']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$ext = preg_replace('/[^a-z0-9]/', '', $ext); // تنظيف الامتداد

/* منع الامتدادات القابلة للتنفيذ على الخادم (حماية من رفع سكربتات خبيثة) */
$blocked = ['php','php3','php4','php5','php7','php8','phtml','phps','phar',
            'cgi','pl','py','sh','exe','bat','com','htaccess','htm','html','svg'];
if ($ext === '' || in_array($ext, $blocked, true)) {
    respond(false, 'نوع الملف غير مسموح');
}

/* ---- إنشاء مجلد الرفع + حماية بسيطة من تنفيذ السكربتات ---- */
$dir = __DIR__ . '/uploads/chat_files';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
$htaccess = $dir . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\nAddType text/plain .php .phtml .php3 .php4 .php5 .pl .py .cgi\n");
}

/* ---- اسم فريد لمنع التكرار ---- */
$unique = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $dir . '/' . $unique;
$relativePath = 'uploads/chat_files/' . $unique; // المسار المخزَّن والمستعمل في الواجهة

if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    respond(false, 'تعذّر حفظ الملف على الخادم');
}

/* ---- نوع الملف (MIME) ---- */
$mime = '';
if (function_exists('mime_content_type')) {
    $mime = @mime_content_type($targetPath);
}
if (!$mime) {
    $mime = $_FILES['file']['type'] ?: 'application/octet-stream';
}

/* ---- الحفظ في قاعدة البيانات (message قد يكون فارغاً مع مرفق) ---- */
$stmt = $pdo->prepare("
    INSERT INTO medical_messages
        (record_id, doctor_id, patient_user_id, sender_id, receiver_id, sender_role,
         message, attachment_path, attachment_name, attachment_type)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $record_id,
    $doctorUserId,
    $patientUserId,
    $senderId,
    $receiverId,
    $senderRole,
    $caption,
    $relativePath,
    $originalName,
    $mime
]);

respond(true, '', [
    'attachment_path' => $relativePath,
    'attachment_name' => $originalName,
    'attachment_type' => $mime
]);
