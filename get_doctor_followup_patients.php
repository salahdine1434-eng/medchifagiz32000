<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode([]);
    exit;
}

// جلب معرف الطبيب
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo json_encode([]);
    exit;
}

$doctorId = $doctor['id'];

// جلب مرضى هذا الطبيب
$stmt = $pdo->prepare("
SELECT
    id,
    patient_id,
    full_name,
    email,
    phone,
    reason_exam,
    created_at
FROM medical_records
WHERE doctor_id = ?
ORDER BY created_at DESC
");

$stmt->execute([$doctorId]);

$patients = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $row['has_account'] = ($row['patient_id'] > 0);

    $patients[] = $row;
}

echo json_encode($patients);