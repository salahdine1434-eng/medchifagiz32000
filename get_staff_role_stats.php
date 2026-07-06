<?php
/**
 * get_staff_role_stats.php
 * إحصائيات الطاقم الطبي حسب الوظيفة (Doughnut Chart)
 * يرجع عدد الموظفين الحقيقي لكل دور من جدول clinic_staff باستعمال COUNT + GROUP BY.
 * ملف مستقل جديد — لا يعدّل أي ملف قائم.
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success'         => false,
        'session_expired' => true,
        'message'         => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// نفس آلية تحديد معرّف العيادة المستعملة حرفياً في get_clinic_staff.php
$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;

if (empty($clinic_id)) {
    echo json_encode([
        'success'         => false,
        'session_expired' => true,
        'message'         => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// قالب الأدوار السبعة (مطابق لقيم enum في جدول clinic_staff) + التسميات العربية
$roleTemplate = [
    'doctor'                => 'طبيب',
    'nurse'                 => 'ممرض/ة',
    'lab_technician'        => 'مخبر',
    'radiology_technician'  => 'أشعة',
    'pharmacist'            => 'صيدلي',
    'receptionist'          => 'موظف استقبال',
    'service_admin'         => 'Service Admin',
];

try {
    $stmt = $pdo->prepare("
        SELECT role, COUNT(*) AS c
        FROM clinic_staff
        WHERE clinic_id = ?
        GROUP BY role
    ");
    $stmt->execute([$clinic_id]);

    // تهيئة كل الأدوار بصفر ثم تعبئة القيم الحقيقية
    $counts = array_fill_keys(array_keys($roleTemplate), 0);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (array_key_exists($row['role'], $counts)) {
            $counts[$row['role']] = (int) $row['c'];
        }
    }

    $roles = [];
    $total = 0;
    foreach ($roleTemplate as $role => $label) {
        $roles[] = [
            'role'  => $role,
            'label' => $label,
            'count' => $counts[$role],
        ];
        $total += $counts[$role];
    }

    echo json_encode([
        'success' => true,
        'roles'   => $roles,
        'total'   => $total,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('get_staff_role_stats.php failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'تعذّر جلب إحصائيات الطاقم'
    ], JSON_UNESCAPED_UNICODE);
}
