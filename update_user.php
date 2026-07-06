<?php

require_once 'db.php';

$id    = $_POST['id'] ?? 0;
$name  = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$role  = $_POST['role'] ?? '';
$permissions = $_POST['permissions'] ?? '{}';
$stmt = $pdo->prepare("
UPDATE users
SET full_name = ?, email = ?, role = ?, permissions = ?
WHERE id = ?
");
$result = $stmt->execute([
    $name,
    $email,
    $role,
    $permissions,
    $id
]);

echo json_encode([
    'success' => $result,
    'permissions_received' => $permissions,
    'error' => $stmt->errorInfo()
]);