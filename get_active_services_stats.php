<?php
/**
 * get_active_services_stats.php
 * المصالح الأكثر نشاطاً (Most Active Services)
 *
 * ملاحظة بنيوية مهمّة:
 * جدول activity_logs الحالي يحتوي فقط على الأعمدة:
 *   icon, bg, color, title, description, user_name, created_at, activity_type
 * لا يوجد فيه عمود service_id ولا حتى clinic_id، لذلك لا يمكن ربط أي نشاط
 * بمصلحة معيّنة بشكل موثوق دون افتراض بنية غير موجودة أو إنشاء جدول جديد
 * (وكلاهما ممنوع حسب التعليمات).
 *
 * لذلك يرجع هذا الملف إشارة "بيانات غير كافية" مع الرسالة الاحترافية المطلوبة،
 * بدل اختلاق أرقام. وهو جاهز للتوسعة لاحقاً: إذا أُضيف عمود service_id إلى
 * activity_logs مستقبلاً، يكفي تفعيل الاستعلام في الأسفل دون تغيير الواجهة.
 *
 * ملف مستقل جديد — لا يعدّل أي ملف قائم.
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$INSUFFICIENT_MSG = 'لا توجد بيانات كافية حالياً لتحديد المصالح الأكثر نشاطاً';

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success'         => false,
        'session_expired' => true,
        'message'         => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
    // التحقق ديناميكياً مما إذا كان عمود الربط (service_id) موجوداً في activity_logs.
    // الافتراضي: غير موجود في البنية الحالية -> نُرجع رسالة "بيانات غير كافية".
    $hasServiceLink = false;
    $colStmt = $pdo->query("SHOW COLUMNS FROM activity_logs LIKE 'service_id'");
    if ($colStmt && $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $hasServiceLink = true;
    }

    if (!$hasServiceLink) {
        // البنية الحالية لا تسمح بحساب النشاط لكل مصلحة.
        echo json_encode([
            'success'     => true,
            'enough_data' => false,
            'services'    => [],
            'message'     => $INSUFFICIENT_MSG,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // مسار مستقبلي (يُنفَّذ فقط إذا أُضيف service_id إلى activity_logs لاحقاً):
    $stmt = $pdo->prepare("
        SELECT s.name AS service_name, COUNT(a.id) AS activity_count
        FROM activity_logs a
        INNER JOIN services s ON a.service_id = s.id
        WHERE a.activity_type = 'clinic_admin'
          AND s.clinic_id = ?
        GROUP BY s.id, s.name
        HAVING activity_count > 0
        ORDER BY activity_count DESC
        LIMIT 7
    ");
    $stmt->execute([$clinic_id]);

    $services = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $services[] = [
            'name'  => $row['service_name'],
            'count' => (int) $row['activity_count'],
        ];
    }

    echo json_encode([
        'success'     => true,
        'enough_data' => count($services) > 0,
        'services'    => $services,
        'message'     => count($services) > 0 ? null : $INSUFFICIENT_MSG,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('get_active_services_stats.php failed: ' . $e->getMessage());
    // لا نكسر الواجهة: نُرجع نفس الرسالة الاحترافية بدل خطأ
    echo json_encode([
        'success'     => true,
        'enough_data' => false,
        'services'    => [],
        'message'     => $INSUFFICIENT_MSG,
    ], JSON_UNESCAPED_UNICODE);
}
