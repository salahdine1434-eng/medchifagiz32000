<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

require 'db.php';

$stmt = $pdo->prepare("
UPDATE patients SET
first_name = NULL,
last_name = NULL,
birth_date = NULL,
gender = NULL,
blood_type = NULL,
weight = NULL,
height = NULL,
phone = NULL,
chronic_diseases = NULL,
allergies = NULL,
medications = NULL,
health_notes = NULL,
emergency_name = NULL,
emergency_phone = NULL,
medical_completed = 0
WHERE user_id = ?
");

$ok = $stmt->execute([$_SESSION['user_id']]);

echo json_encode([
  'success' => $ok
]);
?>