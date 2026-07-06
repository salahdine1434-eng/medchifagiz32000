<?php

require_once 'db.php';

header('Content-Type: application/json');

$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'admin';

if(empty($name) || empty($email) || empty($password)){
    echo json_encode([
        'success' => false,
        'message' => 'Missing data'
    ]);
    exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);

if($check->fetch()){
    echo json_encode([
        'success' => false,
        'message' => 'Email already exists'
    ]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
INSERT INTO users
(full_name, email, password_hash, role, status, account_status, permissions, created_at)
VALUES
(?, ?, ?, ?, 'approved', 'active', ?, NOW())
");

$permissions = $_POST['permissions'] ?? '{}';

$stmt->execute([
    $name,
    $email,
    $hashedPassword,
    $role,
    $permissions
]);
echo json_encode([
    'success' => true
]);