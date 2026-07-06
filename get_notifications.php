<?php
/**
 * get_notifications.php
 * جلب إشعارات المريض ديناميكياً (AJAX)
 * مستخدم من patient_dashboard.php لتحديث الإشعارات بدون reload
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'notifications' => [], 'unread_count' => 0]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// جلب الإشعارات
$stmt = $pdo->prepare("
    SELECT id, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// عدد غير المقروءة
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmtCount->execute([$user_id]);
$unread_count = (int) $stmtCount->fetchColumn();

echo json_encode([
    'success'      => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
], JSON_UNESCAPED_UNICODE);
