<?php

require_once 'db.php';

$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? '';

$stmt = $pdo->prepare("
UPDATE users
SET account_status=?
WHERE id=?
");

$success = $stmt->execute([$status, $id]);

echo json_encode([
    'success' => $success
]);