<?php
/**
 * add_consultation_participant.php
 * ---------------------------------------------------------------------------
 * إضافة طبيب مشارك (Collaborator) إلى استشارة موجودة.
 *
 * إعادة استخدام كاملة لما هو موجود:
 *   - لا يبحث عن أي طبيب هنا إطلاقاً. الواجهة تبحث عبر نفس نداء البحث
 *     الموجود مسبقاً (search_doctors.php)، ونفس بطاقات النتائج المستخدمة في
 *     قسم "الأطباء المشاركون" بنافذة إنشاء الاستشارة. هذا الملف يستقبل فقط
 *     الطبيب الذي اختاره المستخدم من تلك النتائج (بنفس صيغة المعرّف
 *     "clinic_<id>" أو "user_<id>" الصادرة من search_doctors.php).
 *   - نفس منطق تفسير معرّف الطبيب (clinic_/user_) المستخدم أصلاً في
 *     create_consultation.php، ونفس فحص وجود الطبيب ونشاطه.
 *
 * القواعد:
 *   - لا يمكن إضافة الطبيب المُنشئ للاستشارة.
 *   - لا يمكن إضافة الطبيب الرئيسي (assigned_doctor) لأنه مشارك أصلاً.
 *   - لا يمكن إضافة نفس الطبيب مرتين لنفس الاستشارة.
 *   - يُسمح بالإضافة فقط لمن يملك صلاحية الوصول للحالة أصلاً: المُنشئ،
 *     الطبيب الرئيسي، أو أي طبيب مشارك حالياً (حتى يمكن للمشاركين أنفسهم
 *     دعوة زملاء آخرين لنفس الاستشارة).
 *
 * ملاحظة على الجدول الجديد (consultation_participants):
 *   consultation_cases يخزّن طبيباً رئيسياً واحداً فقط (assigned_doctor_id)،
 *   ولا توجد أي بنية سابقة تسمح بأكثر من طبيب واحد مرتبط بنفس الاستشارة.
 *   لذلك، وبنفس أسلوب هذا المشروع تماماً (انظر إنشاء جدول
 *   consultation_messages داخل reply_consultation.php /
 *   get_consultation_messages.php)، يُنشأ الجدول تلقائياً هنا عبر
 *   CREATE TABLE IF NOT EXISTS، دون أي Migration يدوي مطلوب.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== هوية المستخدم الحالي كطبيب ===== */
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

$case_id    = intval($data['case_id'] ?? $data['consultation_case_id'] ?? 0);
$doctor_raw = trim((string)($data['doctor_id'] ?? ''));

if ($case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($doctor_raw === '') {
    echo json_encode(['success' => false, 'message' => 'الرجاء اختيار طبيب'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== تفسير معرّف الطبيب — نفس منطق create_consultation.php تماماً =====
   نتائج search_doctors.php تُرجع نفس صيغة معرّفات external_doctors في
   get_consultation_form_data.php: "clinic_<id>" لطبيب عيادة، أو
   "user_<id>" لطبيب خاص. */
$doctor_id   = 0;
$doctor_type = 'clinic_staff';

if (preg_match('/^clinic_(\d+)$/', $doctor_raw, $m)) {
    $doctor_id   = intval($m[1]);
    $doctor_type = 'clinic_staff';
} elseif (preg_match('/^user_(\d+)$/', $doctor_raw, $m)) {
    $doctor_id   = intval($m[1]);
    $doctor_type = 'private';
} else {
    $doctor_id = intval($doctor_raw);
}

if ($doctor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'الطبيب المختار غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== ضمان وجود جدول المشاركين (جدول جديد بالكامل — انظر الشرح أعلاه) ===== */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `consultation_participants` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `consultation_case_id` INT(11) NOT NULL,
          `doctor_id` INT(11) NOT NULL,
          `doctor_type` ENUM('clinic_staff','private') NOT NULL DEFAULT 'clinic_staff',
          `added_by` INT(11) NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_case_doctor` (`consultation_case_id`,`doctor_id`,`doctor_type`),
          KEY `idx_case` (`consultation_case_id`),
          CONSTRAINT `fk_consultation_participants_case`
            FOREIGN KEY (`consultation_case_id`) REFERENCES `consultation_cases` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    error_log("ensure consultation_participants table failed: " . $e->getMessage());
}

/* ===== جلب الحالة ===== */
try {
    $stmt = $pdo->prepare("
        SELECT created_by, clinic_id, assigned_doctor_id, assigned_doctor_type
        FROM consultation_cases WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("add_consultation_participant fetch case failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$case) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من صلاحية الوصول: مُنشئ / طبيب رئيسي / مشارك حالياً ===== */
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
    // غير حرج
}

if (!($is_creator || $is_assigned || $is_participant)) {
    echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية إضافة مشاركين لهذه الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== تحديد نوع حساب المُنشئ (لمنع إضافته كمشارك) =====
   created_by لا يخزّن نوع الحساب، فنبحث عنه فعلياً في clinic_staff أولاً
   (نفس أسلوب get_consultation_details.php تماماً). */
$creator_type = 'private';
try {
    $ck = $pdo->prepare("SELECT id FROM clinic_staff WHERE id = ? LIMIT 1");
    $ck->execute([$case['created_by']]);
    if ($ck->fetch()) $creator_type = 'clinic_staff';
} catch (PDOException $e) {
    // غير حرج
}

/* ===== قواعد المنع ===== */
if ($doctor_id === intval($case['created_by']) && $doctor_type === $creator_type) {
    echo json_encode(['success' => false, 'message' => 'لا يمكن إضافة الطبيب المُنشئ كمشارك.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (intval($case['assigned_doctor_id']) > 0
    && $doctor_id === intval($case['assigned_doctor_id'])
    && $doctor_type === $case['assigned_doctor_type']) {
    echo json_encode(['success' => false, 'message' => 'هذا الطبيب هو الطبيب الرئيسي للاستشارة أصلاً.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dup = $pdo->prepare("
        SELECT id FROM consultation_participants
        WHERE consultation_case_id = ? AND doctor_id = ? AND doctor_type = ? LIMIT 1
    ");
    $dup->execute([$case_id, $doctor_id, $doctor_type]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'هذا الطبيب مُضاف بالفعل كمشارك في هذه الاستشارة.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (PDOException $e) {
    error_log("add_consultation_participant dup check failed: " . $e->getMessage());
}

/* ===== التأكد من وجود الطبيب فعلياً وأنه نشط (نفس فحص create_consultation.php) ===== */
try {
    if ($doctor_type === 'clinic_staff') {
        $vs = $pdo->prepare("
            SELECT id, full_name FROM clinic_staff
            WHERE id = ? AND role = 'doctor' AND account_status = 'active'
        ");
        $vs->execute([$doctor_id]);
    } else {
        $vs = $pdo->prepare("
            SELECT u.id, u.full_name FROM users u
            INNER JOIN doctors d ON d.user_id = u.id
            WHERE u.id = ? AND u.role = 'doctor' AND u.account_status = 'active'
        ");
        $vs->execute([$doctor_id]);
    }
    $doctor_row = $vs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("add_consultation_participant doctor check failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر التحقق من الطبيب المختار'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$doctor_row) {
    echo json_encode(['success' => false, 'message' => 'الطبيب المختار غير موجود أو غير نشط.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== الإدراج ===== */
try {
    $ins = $pdo->prepare("
        INSERT INTO consultation_participants (consultation_case_id, doctor_id, doctor_type, added_by)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$case_id, $doctor_id, $doctor_type, $my_id]);

    $cnt = $pdo->prepare("SELECT COUNT(*) AS c FROM consultation_participants WHERE consultation_case_id = ?");
    $cnt->execute([$case_id]);
    $participants_count = intval($cnt->fetch(PDO::FETCH_ASSOC)['c']);
    if (intval($case['assigned_doctor_id']) > 0) $participants_count += 1; // + الطبيب الرئيسي
} catch (PDOException $e) {
    error_log("add_consultation_participant insert failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر إضافة المشارك.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success'     => true,
    'message'     => 'تمت إضافة الطبيب كمشارك في الاستشارة.',
    'participant' => [
        'id'        => $doctor_id,
        'type'      => $doctor_type,
        'full_name' => $doctor_row['full_name'],
    ],
    'counts' => [
        'participants' => $participants_count,
    ],
], JSON_UNESCAPED_UNICODE);
exit;
