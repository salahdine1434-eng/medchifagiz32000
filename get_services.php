<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;

if (!$clinic_id) {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
    s.*,
    cs.full_name AS admin_name,
    (
        SELECT COUNT(*)
        FROM clinic_staff c
        WHERE c.service_id = s.id
    ) AS workers_count
FROM services s
LEFT JOIN clinic_staff cs
    ON cs.service_id = s.id
    AND cs.role = 'service_admin'
WHERE s.clinic_id = ?
ORDER BY s.id DESC
");
$stmt->execute([$clinic_id]);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إذا لم يكن حقل is_active موجوداً بعد (قبل تنفيذ migration)
// نضيفه بقيمة افتراضية 1 (نشط) لكل مصلحة
$services = array_map(function($s) {
    if (!array_key_exists('is_active', $s)) {
        $s['is_active'] = 1;
    }
    return $s;
}, $services);

echo json_encode([
    'success'  => true,
    'services' => $services
]);
