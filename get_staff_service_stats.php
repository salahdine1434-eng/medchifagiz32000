<?php
/**
 * get_staff_service_stats.php
 * عدد الموظفين في كل مصلحة (Bar Chart)
 * يجمع البيانات من جدولي services و clinic_staff باستعمال LEFT JOIN + COUNT + GROUP BY.
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

try {
    // عدد الموظفين لكل مصلحة تابعة لهذه العيادة (يشمل المصالح بدون موظفين -> 0)
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, COUNT(cs.id) AS staff_count
        FROM services s
        LEFT JOIN clinic_staff cs
               ON cs.service_id = s.id
              AND cs.clinic_id  = s.clinic_id
        WHERE s.clinic_id = ?
        GROUP BY s.id, s.name
        ORDER BY staff_count DESC, s.name ASC
    ");
    $stmt->execute([$clinic_id]);

    $services = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $services[] = [
            'name'  => $row['name'],
            'count' => (int) $row['staff_count'],
        ];
    }

    // الموظفون غير المرتبطين بأي مصلحة (service_id = NULL)
    $stmt2 = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM clinic_staff
        WHERE clinic_id = ? AND service_id IS NULL
    ");
    $stmt2->execute([$clinic_id]);
    $unassigned = (int) $stmt2->fetchColumn();

    if ($unassigned > 0) {
        $services[] = [
            'name'  => 'بدون مصلحة',
            'count' => $unassigned,
        ];
    }

    echo json_encode([
        'success'  => true,
        'services' => $services,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('get_staff_service_stats.php failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'تعذّر جلب إحصائيات المصالح'
    ], JSON_UNESCAPED_UNICODE);
}
