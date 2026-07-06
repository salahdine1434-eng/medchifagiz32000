<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT *
    FROM services
    ORDER BY id DESC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'services' => $services
]);