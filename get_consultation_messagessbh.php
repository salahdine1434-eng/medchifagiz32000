<?php
/**
 * get_consultation_messages.php
 * ---------------------------------------------------------------------------
 * مرحلة المحادثة الحقيقية — جلب كل رسائل استشارة مرتبة زمنياً (قراءة فقط).
 *
 * - يُعيد الرسائل حسب consultation_case_id مرتبة بوقت الإنشاء تصاعدياً.
 * - يُسمح بالوصول للطرفين فقط: الطبيب المُنشئ أو الطبيب المختار.
 * - لكل رسالة: اسم المرسل، وقتها، و is_mine (هل أرسلها المستخدم الحالي).
 * - بدون WebSocket — قاعدة بيانات فقط.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

/* ===== هوية المستخدم الحالي ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$my_id        = intval($is_clinic_staff ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['user_id'] ?? 0));
$my_type      = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_clinic_id = intval($_SESSION['clinic_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$case_id = intval($_GET['case_id'] ?? $_GET['consultation_case_id'] ?? 0);
if ($case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== ضمان وجود جدول الرسائل ===== */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `consultation_messages` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `consultation_case_id` INT(11) NOT NULL,
          `sender_id` INT(11) NOT NULL,
          `sender_type` ENUM('clinic_staff','private') NOT NULL DEFAULT 'clinic_staff',
          `message` TEXT NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_case_time` (`consultation_case_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    error_log("ensure messages table failed: " . $e->getMessage());
}

/* ===== التحقق من صلاحية الوصول (منشئ أو طبيب مختار) ===== */
try {
    $stmt = $pdo->prepare("
        SELECT created_by, clinic_id, assigned_doctor_id, assigned_doctor_type
        FROM consultation_cases WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultation_messages fetch case failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$case) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$is_creator = (intval($case['created_by']) === $my_id)
    && (!$is_clinic_staff || intval($case['clinic_id']) === $my_clinic_id);

$is_assigned = ($case['assigned_doctor_type'] === $my_type)
    && (intval($case['assigned_doctor_id']) === $my_id);

if (!($is_creator || $is_assigned)) {
    echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية عرض هذه المحادثة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== جلب الرسائل مرتبة زمنياً + اسم المرسل ===== */
try {
    $sql = "
        SELECT
            m.id, m.sender_id, m.sender_type, m.message, m.created_at,
            COALESCE(cs.full_name, us.full_name) AS sender_name
        FROM consultation_messages m
        LEFT JOIN clinic_staff cs ON cs.id = m.sender_id AND m.sender_type = 'clinic_staff'
        LEFT JOIN users us        ON us.id = m.sender_id AND m.sender_type = 'private'
        WHERE m.consultation_case_id = ?
        ORDER BY m.created_at ASC, m.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$case_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultation_messages select failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الرسائل'], JSON_UNESCAPED_UNICODE);
    exit;
}

$messages = [];
foreach ($rows as $r) {
    $messages[] = [
        'id'          => intval($r['id']),
        'sender_id'   => intval($r['sender_id']),
        'sender_type' => $r['sender_type'],
        'sender_name' => ($r['sender_name'] !== null && trim($r['sender_name']) !== '') ? $r['sender_name'] : 'طبيب',
        'text'        => $r['message'],
        'created_at'  => $r['created_at'],
        'is_mine'     => (intval($r['sender_id']) === $my_id && $r['sender_type'] === $my_type),
    ];
}

echo json_encode([
    'success'        => true,
    'case_id'        => $case_id,
    'messages'       => $messages,
    'messages_count' => count($messages),
], JSON_UNESCAPED_UNICODE);
exit;
