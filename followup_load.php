<?php
/**
 * followup_load.php
 * جلب جميع المتابعات الطبية لسجل معين — JSON
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

require 'db.php';

$recordId = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
if (!$recordId) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

/* التأكد من ملكية السجل */
$stmtCheck = $pdo->prepare("
    SELECT mr.id FROM medical_records mr
    JOIN doctors d ON d.user_id = ?
    WHERE mr.id = ? AND mr.doctor_id = d.id
");
$stmtCheck->execute([$_SESSION['user_id'], $recordId]);
if (!$stmtCheck->fetch()) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, followup_date, new_symptoms, new_treatment, doctor_notes
    FROM medical_followups
    WHERE medical_record_id = ?
    ORDER BY followup_date ASC, id ASC
");
$stmt->execute([$recordId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $rows]);
