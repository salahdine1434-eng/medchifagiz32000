<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

if (!empty($_SESSION['is_clinic_staff'])) {
    // موظّف العيادة: نقرأ الدخول السابق من جدول clinic_staff
    $stmt = $pdo->prepare("
        SELECT previous_login
        FROM clinic_staff
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['staff_id']]);
} else {
    // صاحب العيادة (وبقية حسابات users): نقرأ الدخول السابق من جدول users
    $stmt = $pdo->prepare("
        SELECT previous_login
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$previous = $row['previous_login'] ?? null;

echo json_encode([
    'success'     => true,
    // أول مرة على الإطلاق => previous_login فارغ
    'first_login' => empty($previous),
    // الدخول السابق الحقيقي (قبل الجلسة الحالية)
    'last_login'  => $previous,
], JSON_UNESCAPED_UNICODE);
