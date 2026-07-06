<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

if (!empty($_SESSION['is_clinic_staff'])) {

    $stmt = $pdo->prepare("
        SELECT previous_login
        FROM clinic_staff
        WHERE id = ?
    ");

    $stmt->execute([$_SESSION['staff_id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'last_login' => $user['previous_login'] ?? null
    ]);

} else {

    $stmt = $pdo->prepare("
        SELECT last_login
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$_SESSION['user_id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'last_login' => $user['last_login'] ?? null
    ]);
}