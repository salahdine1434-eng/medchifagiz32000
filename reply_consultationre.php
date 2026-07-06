<?php
/**
 * reply_consultation.php
 * ---------------------------------------------------------------------------
 * مرحلة المحادثة الحقيقية — إرسال رسالة داخل استشارة وحفظها في قاعدة البيانات.
 *
 * - تُربط الرسالة بـ consultation_case_id.
 * - تُحفظ هوية المرسل (sender_id + sender_type) ووقت الإرسال.
 * - يُسمح بالإرسال: الطبيب المُنشئ (created_by)، الطبيب المختار
 *   (assigned_doctor)، أو أي طبيب مشارك (consultation_participants) —
 *   تحديث "إضافة طبيب مشارك". أي طرف آخر يُرفض.
 * - بدون إشعارات، بدون WebSocket — قاعدة بيانات فقط.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من تسجيل الدخول + هوية المستخدم كطبيب ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$my_id        = intval($is_clinic_staff ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['user_id'] ?? 0));
$my_type      = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_clinic_id = intval($_SESSION['clinic_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== قراءة المدخلات ===== */
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$case_id = intval($data['case_id'] ?? $data['consultation_case_id'] ?? 0);
$message = trim($data['message'] ?? '');

if ($case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'الرسالة فارغة'], JSON_UNESCAPED_UNICODE);
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

/* ===== جلب الحالة + التحقق من صلاحية الوصول (منشئ، طبيب مختار، أو طبيب مشارك) ===== */
try {
    $stmt = $pdo->prepare("
        SELECT created_by, clinic_id, assigned_doctor_id, assigned_doctor_type
        FROM consultation_cases WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("reply_consultation fetch case failed: " . $e->getMessage());
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

$is_participant = false;
try {
    $pchk = $pdo->prepare("
        SELECT id FROM consultation_participants
        WHERE consultation_case_id = ? AND doctor_id = ? AND doctor_type = ? LIMIT 1
    ");
    $pchk->execute([$case_id, $my_id, $my_type]);
    $is_participant = (bool) $pchk->fetch();
} catch (PDOException $e) {
    // الجدول قد لا يكون موجوداً بعد لو لم يُضَف أي مشارك سابقاً — غير حرج
}

if (!($is_creator || $is_assigned || $is_participant)) {
    echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية المشاركة في هذه الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== حفظ الرسالة ===== */
try {
    $ins = $pdo->prepare("
        INSERT INTO consultation_messages (consultation_case_id, sender_id, sender_type, message)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$case_id, $my_id, $my_type, $message]);
    $message_id = intval($pdo->lastInsertId());

    /* اسم المرسل (المستخدم الحالي) */
    if ($my_type === 'clinic_staff') {
        $ns = $pdo->prepare("SELECT full_name FROM clinic_staff WHERE id = ? LIMIT 1");
    } else {
        $ns = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    }
    $ns->execute([$my_id]);
    $nr = $ns->fetch(PDO::FETCH_ASSOC);
    $sender_name = $nr ? $nr['full_name'] : 'طبيب';

    /* العدد الإجمالي للرسائل بعد الإضافة */
    $cnt = $pdo->prepare("SELECT COUNT(*) AS c FROM consultation_messages WHERE consultation_case_id = ?");
    $cnt->execute([$case_id]);
    $count = intval($cnt->fetch(PDO::FETCH_ASSOC)['c']);

    /* وقت الرسالة المُدرَجة */
    $ts = $pdo->prepare("SELECT created_at FROM consultation_messages WHERE id = ? LIMIT 1");
    $ts->execute([$message_id]);
    $created_at = $ts->fetch(PDO::FETCH_ASSOC)['created_at'] ?? date('Y-m-d H:i:s');
} catch (PDOException $e) {
    error_log("reply_consultation insert failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر إرسال الرسالة'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success'        => true,
    'message'        => 'تم إرسال الرسالة.',
    'messages_count' => $count,
    'data' => [
        'id'          => $message_id,
        'sender_id'   => $my_id,
        'sender_type' => $my_type,
        'sender_name' => $sender_name,
        'text'        => $message,
        'created_at'  => $created_at,
        'is_mine'     => true,
    ],
], JSON_UNESCAPED_UNICODE);
exit;
