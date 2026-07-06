<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$clinic_id = $_SESSION['clinic_id'] ?? null;

if (!$clinic_id) {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, full_name
    FROM clinic_staff
    WHERE clinic_id = ?
    AND role = 'service_admin'
    ORDER BY full_name ASC
");

$stmt->execute([$clinic_id]);

echo json_encode([
    'success' => true,
    'admins' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);