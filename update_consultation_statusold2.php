<?php
/**
 * update_consultation_status.php
 * ---------------------------------------------------------------------------
 * المرحلة الثانية — تحديث حالة استشارة (لأزرار "تغيير الحالة" و"إغلاق الحالة").
 *
 * يُحدّث consultation_cases.status فقط (و closed_at عند الإغلاق).
 * لا يرسل شيئاً لأي طبيب، ولا إشعارات، ولا ردود — تحديث حالة محلي فقط.
 *
 * أمان: يقتصر على استشارات المستخدم الحالي (created_by = me).
 * يتوافق مع نفس نموذج الجلسة في بقية ملفات الاستشارات.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من تسجيل الدخول ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$clinic_id       = intval($_SESSION['clinic_id'] ?? 0);

$logged_in = $is_clinic_staff
    ? !empty($_SESSION['staff_id'])
    : !empty($_SESSION['user_id']);

if (!$logged_in) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$created_by = intval($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0);
if ($created_by <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== قراءة المدخلات (JSON أو POST عادي) ===== */
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$id     = intval($data['id'] ?? 0);
$status = trim($data['status'] ?? '');

$allowed_statuses = ['new', 'in_review', 'answered', 'closed'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($status, $allowed_statuses, true)) {
    echo json_encode(['success' => false, 'message' => 'حالة غير صالحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التأكد أن الحالة تخص المستخدم الحالي ===== */
$where  = "id = ? AND created_by = ?";
$params = [$id, $created_by];
if ($is_clinic_staff && $clinic_id > 0) {
    $where   .= " AND clinic_id = ?";
    $params[] = $clinic_id;
}

try {
    $chk = $pdo->prepare("SELECT id FROM consultation_cases WHERE $where LIMIT 1");
    $chk->execute($params);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* عند الإغلاق نضبط closed_at؛ وإلا نُبقيه NULL (إعادة فتح) */
    if ($status === 'closed') {
        $upd = $pdo->prepare("UPDATE consultation_cases SET status = ?, closed_at = NOW() WHERE $where");
        $upd->execute(array_merge([$status], $params));
    } else {
        $upd = $pdo->prepare("UPDATE consultation_cases SET status = ?, closed_at = NULL WHERE $where");
        $upd->execute(array_merge([$status], $params));
    }
} catch (PDOException $e) {
    error_log("update_consultation_status failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر تحديث الحالة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$status_labels = ['new' => 'جديدة', 'in_review' => 'قيد المراجعة', 'answered' => 'تم الرد', 'closed' => 'مغلقة'];

echo json_encode([
    'success'      => true,
    'message'      => 'تم تحديث حالة الاستشارة.',
    'id'           => $id,
    'status'       => $status,
    'status_label' => $status_labels[$status] ?? $status,
], JSON_UNESCAPED_UNICODE);
exit;
