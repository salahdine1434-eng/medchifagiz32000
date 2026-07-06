<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$stmt = $pdo->prepare("
    SELECT *
    FROM activity_logs
    WHERE activity_type = 'clinic_admin'
    ORDER BY created_at DESC
    LIMIT 100
");

$stmt->execute();

echo json_encode([
    'success' => true,
    'activities' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);