<?php
/**
 * get_consultation_messages.php
 * ---------------------------------------------------------------------------
 * المحادثة الحقيقية داخل الاستشارة — جلب كل الرسائل مرتبة زمنياً (قراءة فقط).
 *
 * مسموح لطرفي الاستشارة فقط (نفس منطق reply_consultation.php):
 *   - الطبيب المُنشئ (created_by)
 *   - الطبيب المختار (assigned_doctor_id / assigned_doctor_type)
 * أي طرف آخر يحصل على نفس رسالة "الاستشارة غير موجودة" دون تفاصيل.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

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

$my_type = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_id   = $is_clinic_staff ? intval($_SESSION['staff_id'] ?? 0) : intval($_SESSION['user_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== المعرّف ===== */
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من الصلاحية (نفس منطق reply_consultation.php) ===== */
try {
    $stmt = $pdo->prepare("
        SELECT id, created_by, clinic_id, assigned_doctor_id, assigned_doctor_type
        FROM consultation_cases
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultation_messages (fetch case) failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الرسائل'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$case) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$creator_type = (intval($case['clinic_id']) > 0) ? 'clinic_staff' : 'private';
$is_creator   = ($my_type === $creator_type) && ($my_id === intval($case['created_by']));
$is_assigned  = ($my_type === $case['assigned_doctor_type']) && ($my_id === intval($case['assigned_doctor_id']));

if (!$is_creator && !$is_assigned) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== جلب الرسائل مرتبة زمنياً (تصاعدياً) مع أسماء المرسلين ===== */
try {
    $sql = "
        SELECT
            cm.id,
            cm.sender_type,
            cm.sender_id,
            cm.message,
            cm.created_at,
            COALESCE(cs.full_name, u.full_name) AS sender_name
        FROM consultation_messages cm
        LEFT JOIN clinic_staff cs
            ON cs.id = cm.sender_id AND cm.sender_type = 'clinic_staff'
        LEFT JOIN users u
            ON u.id = cm.sender_id AND cm.sender_type = 'private'
        WHERE cm.consultation_case_id = ?
        ORDER BY cm.created_at ASC, cm.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultation_messages (fetch messages) failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الرسائل'], JSON_UNESCAPED_UNICODE);
    exit;
}

$messages = [];
foreach ($rows as $r) {
    $mine = ($r['sender_type'] === $my_type) && (intval($r['sender_id']) === $my_id);
    $messages[] = [
        'id'          => intval($r['id']),
        'message'     => $r['message'],
        'sender_name' => $r['sender_name'],
        'mine'        => $mine,
        'created_at'  => $r['created_at'],
    ];
}

echo json_encode([
    'success'  => true,
    'messages' => $messages,
    'counts'   => [
        'messages' => count($messages),
    ],
], JSON_UNESCAPED_UNICODE);
exit;
