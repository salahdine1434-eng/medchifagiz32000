<?php
/* ================================================================
   heartbeat.php
   يُحدِّث last_seen للمستخدم المسجّل دخوله (نبض كل ~30 ثانية من
   الواجهتين). بذلك تعكس حالة الاتصال وجود المستخدم فعلياً على الصفحة.
   لا يمسّ أي منطق آخر.
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt->execute([intval($_SESSION['user_id'])]);

echo json_encode(['success' => true]);
