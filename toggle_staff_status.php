<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'انتهت الجلسة'
    ]);
    exit;
}

$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;
$id = intval($_POST['id'] ?? 0);

if (empty($clinic_id) || $id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف غير صالح'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT account_status
    FROM clinic_staff
    WHERE id = ? AND clinic_id = ?
");
$stmt->execute([$id, $clinic_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'المستخدم غير موجود'
    ]);
    exit;
}

$newStatus = ($user['account_status'] === 'inactive')
    ? 'active'
    : 'inactive';

$update = $pdo->prepare("
    UPDATE clinic_staff
    SET account_status = ?
    WHERE id = ? AND clinic_id = ?
");

$update->execute([$newStatus, $id, $clinic_id]);

echo json_encode([
    'success' => true,
    'status' => $newStatus
]);