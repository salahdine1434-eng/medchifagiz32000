<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode([
        'exists' => false,
        'status' => '',
        'message' => 'أدخل البريد الإلكتروني'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, account_status
    FROM users
    WHERE email = ? AND role = 'patient'
    LIMIT 1
");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'exists' => true,
        'status' => $user['account_status'],
        'message' => 'تم العثور على الحساب'
    ]);
} else {
    echo json_encode([
        'exists' => false,
        'status' => '',
        'message' => 'لا يوجد حساب بهذا البريد'
    ]);
}