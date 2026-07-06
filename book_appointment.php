<?php
/**
 * book_appointment.php
 * ملف حجز المواعيد - نظيف وآمن
 * يستقبل JSON ويرجع JSON
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

// ----- التحقق من الجلسة -----
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

// ----- رفض أي طلب ليس POST -----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

// ----- قراءة JSON -----
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'البيانات غير صالحة']);
    exit;
}

// ----- استخراج البيانات -----
$patient_id  = (int) $_SESSION['user_id'];
$doctor_id   = isset($data['doctor_id']) ? (int) $data['doctor_id'] : 0;
$patient_name = trim($data['name'] ?? '');
$phone        = trim($data['phone'] ?? '');
$case_type    = trim($data['case_type'] ?? '');

// ----- Validation -----
if ($doctor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الطبيب غير صحيح']);
    exit;
}

if (empty($patient_name) || mb_strlen($patient_name) < 2) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال الاسم كاملاً']);
    exit;
}

if (empty($phone) || !preg_match('/^[0-9\+\s\-]{7,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'رقم الهاتف غير صحيح']);
    exit;
}

$allowed_case_types = ['عادية', 'مستعجلة', 'مزمنة'];
if (!in_array($case_type, $allowed_case_types)) {
    echo json_encode(['success' => false, 'message' => 'يرجى اختيار نوع الحالة']);
    exit;
}

// ----- التحقق من وجود الطبيب -----
$stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE id = ?");
$stmtDoc->execute([$doctor_id]);
if (!$stmtDoc->fetch()) {
    echo json_encode(['success' => false, 'message' => 'الطبيب غير موجود في النظام']);
    exit;
}

// ----- منع الحجز المكرر لنفس اليوم -----
$today = date('Y-m-d');
$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE doctor_id = :doctor_id
      AND patient_id = :patient_id
      AND DATE(created_at) = :today
      AND status != 'cancelled'
");
$stmtCheck->execute([
    ':doctor_id'  => $doctor_id,
    ':patient_id' => $patient_id,
    ':today'      => $today
]);

if ($stmtCheck->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'لديك حجز مع هذا الطبيب اليوم، لا يمكن الحجز مرتين']);
    exit;
}

// ----- حفظ الحجز -----
$stmtInsert = $pdo->prepare("
    INSERT INTO appointments (patient_id, doctor_id, status, patient_name, phone, case_type, created_at)
    VALUES (:patient_id, :doctor_id, 'pending', :patient_name, :phone, :case_type, NOW())
");

$result = $stmtInsert->execute([
    ':patient_id'   => $patient_id,
    ':doctor_id'    => $doctor_id,
    ':patient_name' => $patient_name,
    ':phone'        => $phone,
    ':case_type'    => $case_type
]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'تم إرسال طلب الحجز بنجاح ✅']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحفظ، حاول مرة أخرى']);
}
