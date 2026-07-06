<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ]);
    exit;
}
// معرّف العيادة الموحّد:
//  - موظف (service_admin / staff) سجّل دخوله عبر clinic_staff  => clinic_id مضبوط في الجلسة.
//  - صاحب العيادة (role='clinic' من جدول users)               => لا يملك clinic_id، فمعرّف عيادته هو user_id نفسه.
// نفس هذا السطر مستعمل حرفياً في save_clinic_staff.php و update/delete/toggle حتى لا يحدث تباين أبداً.
$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;

if (empty($clinic_id)) {
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT cs.*, s.name AS service_name
    FROM clinic_staff cs
    LEFT JOIN services s ON cs.service_id = s.id
    WHERE cs.clinic_id = ?
    ORDER BY cs.id DESC
");

$stmt->execute([$clinic_id]);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'users' => $users
]);
