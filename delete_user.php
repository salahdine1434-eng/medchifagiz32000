<?php

require_once 'db.php';

$id = $_POST['id'] ?? 0;

$stmt = $pdo->prepare("
DELETE FROM users
WHERE id=?
");

$success = $stmt->execute([$id]);

echo json_encode([
    'success' => $success
]);