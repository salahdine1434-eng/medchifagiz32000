<?php
/**
 * reply_consultation.php
 * ---------------------------------------------------------------------------
 * المحادثة الحقيقية داخل الاستشارة — إرسال رسالة جديدة.
 *
 * تُحفظ كل رسالة في consultation_messages مرتبطة بـ consultation_case_id،
 * مع هوية المرسل (نوعه ومعرّفه) وتاريخ الإرسال. مسموح لطرفي الاستشارة فقط:
 *   - الطبيب المُنشئ (created_by)
 *   - الطبيب المختار (assigned_doctor_id / assigned_doctor_type)
 *
 * لا علاقة له بالمرفقات أو تغيير الحالة أو الإنشاء — تحديث حالة الرسائل
 * والعداد فقط.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من تسجيل الدخول (نفس نموذج باقي ملفات الاستشارات) ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$clinic_id       = intval($_SESSION['clinic_id'] ?? 0);

$logged_in = $is_clinic_staff
    ? !empty($_SESSION['staff_id'])
    : !empty($_SESSION['user_id']);

if (!$logged_in) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* هوية المستخدم الحالي بنفس صيغة assigned_doctor_type/assigned_doctor_id */
$my_type = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_id   = $is_clinic_staff ? intval($_SESSION['staff_id'] ?? 0) : intval($_SESSION['user_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== قراءة المدخلات ===== */
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$id      = intval($data['id'] ?? 0);
$message = trim((string)($data['message'] ?? ''));

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'لا يمكن إرسال رسالة فارغة'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (mb_strlen($message) > 2000) {
    echo json_encode(['success' => false, 'message' => 'الرسالة طويلة جداً (الحد الأقصى 2000 حرف)'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== جلب الاستشارة والتحقق من الصلاحية (أحد طرفي الاستشارة فقط) ===== */
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
    error_log("reply_consultation (fetch case) failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر إرسال الرسالة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$case) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* الطبيب المُنشئ: لا يوجد عمود لنوعه، فنحدّده حسب وجود clinic_id على الحالة
   (طبيب عيادة أنشأها ضمن clinic_id، وإلا فهو طبيب خاص أنشأها بلا عيادة) */
$creator_type  = (intval($case['clinic_id']) > 0) ? 'clinic_staff' : 'private';
$is_creator    = ($my_type === $creator_type) && ($my_id === intval($case['created_by']));

$is_assigned   = ($my_type === $case['assigned_doctor_type']) && ($my_id === intval($case['assigned_doctor_id']));

if (!$is_creator && !$is_assigned) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== إدراج الرسالة ===== */
try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("
        INSERT INTO consultation_messages (consultation_case_id, sender_type, sender_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$id, $my_type, $my_id, $message]);
    $message_id = $pdo->lastInsertId();

    // تحديث updated_at لتعكس آخر نشاط في الحالة (بدون أي تغيير على status)
    $touch = $pdo->prepare("UPDATE consultation_cases SET updated_at = NOW() WHERE id = ?");
    $touch->execute([$id]);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM consultation_messages WHERE consultation_case_id = ?");
    $cnt->execute([$id]);
    $messages_count = intval($cnt->fetchColumn());

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("reply_consultation (insert) failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر إرسال الرسالة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* اسم المرسل (أنا) لعرضه فوراً في الواجهة دون إعادة تحميل كامل المحادثة */
$sender_name = '';
try {
    if ($my_type === 'clinic_staff') {
        $s = $pdo->prepare("SELECT full_name FROM clinic_staff WHERE id = ? LIMIT 1");
    } else {
        $s = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    }
    $s->execute([$my_id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if ($row) $sender_name = $row['full_name'];
} catch (PDOException $e) {
    // غير حرج
}

echo json_encode([
    'success' => true,
    'message_data' => [
        'id'          => intval($message_id),
        'message'     => $message,
        'sender_name' => $sender_name,
        'mine'        => true,
        'created_at'  => date('Y-m-d H:i:s'),
    ],
    'counts' => [
        'messages' => $messages_count,
    ],
], JSON_UNESCAPED_UNICODE);
exit;
