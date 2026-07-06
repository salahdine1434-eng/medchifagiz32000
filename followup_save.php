<?php
/**
 * followup_save.php
 * حفظ متابعة طبية جديدة — INSERT فقط، لا يمس medical_records
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

/* ── حماية ── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

require 'db.php';

/* ── استقبال البيانات ── */
$recordId    = isset($_POST['medical_record_id']) ? (int)$_POST['medical_record_id'] : 0;
$date        = trim($_POST['followup_date']    ?? '');
$symptoms    = trim($_POST['new_symptoms']     ?? '');
$treatment   = trim($_POST['new_treatment']    ?? '');
$notes       = trim($_POST['doctor_notes']     ?? '');

if (!$recordId || !$date) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير كافية']);
    exit;
}

/* ── التأكد أن السجل ينتمي لهذا الطبيب ── */
$stmtCheck = $pdo->prepare("
    SELECT mr.id, d.id AS did
    FROM medical_records mr
    JOIN doctors d ON d.user_id = ?
    WHERE mr.id = ? AND mr.doctor_id = d.id
");
$stmtCheck->execute([$_SESSION['user_id'], $recordId]);
$row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'السجل غير موجود أو غير مسموح']);
    exit;
}

$doctorId = (int)$row['did'];

/* ── INSERT المتابعة ── */
$stmt = $pdo->prepare("
    INSERT INTO medical_followups
        (medical_record_id, doctor_id, followup_date, new_symptoms, new_treatment, doctor_notes)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $recordId,
    $doctorId,
    $date,
    $symptoms  ?: null,
    $treatment ?: null,
    $notes     ?: null,
]);

$newId = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'id'      => $newId,
    'data'    => [
        'id'            => $newId,
        'followup_date' => $date,
        'new_symptoms'  => $symptoms,
        'new_treatment' => $treatment,
        'doctor_notes'  => $notes,
    ]
]);
