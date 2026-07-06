<?php
session_start();
require_once 'db.php';
require_once 'send_reset_password_email.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المستخدم غير صالح'
    ]);
    exit;
}

/* إنشاء كلمة مرور مؤقتة جديدة */
$newPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 8);

/* تشفير كلمة المرور */
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$userStmt = $pdo->prepare("
    SELECT full_name, email, role
    FROM clinic_staff
    WHERE id = ?
");

$userStmt->execute([$user_id]);

$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'المستخدم غير موجود'
    ]);
    exit;
}
/* تحديث قاعدة البيانات */
$stmt = $pdo->prepare("
    UPDATE clinic_staff
    SET password_hash = ?
    WHERE id = ?
");

$stmt->execute([$passwordHash, $user_id]);
if (!empty($user['email'])) {

   sendResetPasswordEmail(
    $user['email'],
    $user['full_name'],
    $newPassword
);

}
echo json_encode([
    'success' => true,
    'new_password' => $newPassword
]);
