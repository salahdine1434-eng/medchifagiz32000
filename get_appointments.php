<?php
/**
 * get_appointments.php
 * جلب حجوزات الطبيب الحالي — يستخدم PDO والجلسة
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

// ----- التحقق من الجلسة -----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

// ----- جلب doctor_id من جدول doctors -----
$stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmtDoc->execute([$_SESSION['user_id']]);
$doctorRow = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doctorRow) {
    echo json_encode(['error' => 'لم يُعثر على الطبيب']);
    exit;
}

$doctor_id = $doctorRow['id'];

// ----- جلب الحجوزات بالحالة المطلوبة -----
// الفلتر الاختياري: ?status=pending أو all
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'confirmed', 'cancelled', 'all'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

if ($status_filter === 'all') {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.patient_name,
            a.phone,
            a.case_type,
            a.status,
            a.appointment_date,
            a.appointment_time,
            a.created_at
        FROM appointments a
        WHERE a.doctor_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$doctor_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.patient_name,
            a.phone,
            a.case_type,
            a.status,
            a.appointment_date,
            a.appointment_time,
            a.created_at
        FROM appointments a
        WHERE a.doctor_id = ? AND a.status = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$doctor_id, $status_filter]);
}

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($appointments, JSON_UNESCAPED_UNICODE);
