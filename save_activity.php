<?php

require_once 'db.php';

$icon        = $_POST['icon'] ?? '';
$bg          = $_POST['bg'] ?? '';
$color       = $_POST['color'] ?? '';
$title       = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$user_name   = $_POST['user_name'] ?? 'Super Admin';

$stmt = $pdo->prepare("
    INSERT INTO activity_logs
    (icon, bg, color, title, description, user_name, activity_type)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $icon,
    $bg,
    $color,
    $title,
    $description,
    $user_name,
    'super_admin'
]);

echo json_encode([
    'success' => true
]);