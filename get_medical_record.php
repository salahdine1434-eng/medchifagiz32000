<?php
require 'db.php';
session_start();
$patient_id = $_GET['patient_id'] ?? 0;
$doctor_id  = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT *
    FROM medical_records
    WHERE patient_id = ? AND doctor_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$patient_id, $doctor_id]);

echo json_encode(
    $stmt->fetch(PDO::FETCH_ASSOC) ?: []
);
?>