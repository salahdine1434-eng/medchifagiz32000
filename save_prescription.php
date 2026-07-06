<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("غير مسموح");
}

$doctorUserId = $_SESSION['user_id'];

// نجيب معلومات الطبيب
$stmt = $pdo->prepare("
    SELECT doctors.*, users.full_name
    FROM doctors
    JOIN users ON doctors.user_id = users.id
    WHERE doctors.user_id = ?
");
$stmt->execute([$doctorUserId]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    exit("الطبيب غير موجود");
}

// بيانات الوصفة
$patient_id   = $_POST['patient_id'] ?? null;
$patient_name = $_POST['patient_name'] ?? '';
$rx_date      = $_POST['rx_date'] ?? date('Y-m-d');
$medicines    = $_POST['medicines'] ?? '';
$notes        = $_POST['notes'] ?? '';
$signature    = $_POST['signature'] ?? '';

$stmt = $pdo->prepare("
    INSERT INTO prescriptions
    (
        patient_id,
        doctor_id,
        patient_name,
        doctor_name,
        doctor_address,
        rx_date,
        medicines,
        notes,
        signature
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $patient_id,
    $doctor['id'],
    $patient_name,
    $doctor['full_name'],
    $doctor['workplace'],
    $rx_date,
    $medicines,
    $notes,
    $signature
]);

echo "success";
?>