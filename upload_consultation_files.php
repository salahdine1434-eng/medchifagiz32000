<?php
/**
 * upload_consultation_files.php
 * ---------------------------------------------------------------------------
 * تفعيل زر المشبك داخل محادثة الاستشارة: رفع صورة وإرسالها كرسالة من نوع
 * image داخل نفس محادثة consultation_messages (نفس جدول الرسائل النصية،
 * فقط type='image' و file_path يحمل مسار الصورة المحفوظة).
 *
 * الصور فقط في هذه المرحلة: jpg, jpeg, png, webp — يُتحقق من الامتداد
 * ومن الـ MIME الحقيقي للملف معاً (وليس فقط اسم الملف).
 *
 * نفس صلاحيات الوصول المستخدمة في reply_consultation.php بالضبط: المُنشئ،
 * الطبيب المختار، أو أي طبيب مشارك.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== هوية المستخدم الحالي ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$my_id        = intval($is_clinic_staff ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['user_id'] ?? 0));
$my_type      = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_clinic_id = intval($_SESSION['clinic_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== المدخلات ===== */
$case_id = intval($_POST['case_id'] ?? $_POST['consultation_case_id'] ?? 0);
if ($case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['file']) || !isset($_FILES['file']['error'])) {
    echo json_encode(['success' => false, 'message' => 'لم يتم اختيار أي ملف'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $err = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
        ? 'حجم الصورة أكبر من المسموح.'
        : 'تعذّر رفع الملف.';
    echo json_encode(['success' => false, 'message' => $err], JSON_UNESCAPED_UNICODE);
    exit;
}

/* حد أقصى معقول لحجم الصورة (10 ميغابايت) */
$MAX_BYTES = 10 * 1024 * 1024;
if ($file['size'] > $MAX_BYTES) {
    echo json_encode(['success' => false, 'message' => 'حجم الصورة يتجاوز الحد المسموح (10MB).'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== التحقق من نوع الملف: صور فقط (jpg, jpeg, png, webp) — امتداد + MIME حقيقي ===== */
$allowed_ext_mime = [
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'webp' => ['image/webp'],
];

$orig_name = $file['name'];
$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

if (!isset($allowed_ext_mime[$ext])) {
    echo json_encode(['success' => false, 'message' => 'صيغة الملف غير مدعومة. المسموح: JPG, JPEG, PNG, WEBP فقط.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$real_mime = false;
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $real_mime = mime_content_type($file['tmp_name']);
}

if (!$real_mime || !in_array($real_mime, $allowed_ext_mime[$ext], true)) {
    echo json_encode(['success' => false, 'message' => 'محتوى الملف لا يطابق صورة صالحة (JPG, JPEG, PNG, WEBP).'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* تأكيد إضافي أنه فعلاً صورة قابلة للفك (يرفض ملفات مموَّهة بامتداد صورة) */
$imgInfo = @getimagesize($file['tmp_name']);
if ($imgInfo === false) {
    echo json_encode(['success' => false, 'message' => 'تعذّر التعرّف على الصورة. تأكد أن الملف صورة صالحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== ضمان وجود جدول الرسائل (+ عمودا type / file_path) ===== */
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
try {
    $colType = $pdo->query("SHOW COLUMNS FROM `consultation_messages` LIKE 'type'")->fetch();
    if (!$colType) {
        $pdo->exec("ALTER TABLE `consultation_messages` ADD COLUMN `type` ENUM('text','image') NOT NULL DEFAULT 'text' AFTER `message`");
    }
    $colFile = $pdo->query("SHOW COLUMNS FROM `consultation_messages` LIKE 'file_path'")->fetch();
    if (!$colFile) {
        $pdo->exec("ALTER TABLE `consultation_messages` ADD COLUMN `file_path` VARCHAR(255) NULL AFTER `type`");
    }
} catch (PDOException $e) {
    error_log("ensure messages image columns failed: " . $e->getMessage());
}

/* ===== جلب الحالة + التحقق من صلاحية الوصول (نفس منطق reply_consultation.php بالضبط) ===== */
try {
    $stmt = $pdo->prepare("
        SELECT created_by, clinic_id, assigned_doctor_id, assigned_doctor_type
        FROM consultation_cases WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("upload_consultation_files fetch case failed: " . $e->getMessage());
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
    // غير حرج
}

if (!($is_creator || $is_assigned || $is_participant)) {
    echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية المشاركة في هذه الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== نقل الملف إلى مجلد الرفع ===== */
$upload_dir_fs  = __DIR__ . '/uploads/consultations/' . $case_id;
$upload_dir_web = 'uploads/consultations/' . $case_id;

if (!is_dir($upload_dir_fs)) {
    if (!@mkdir($upload_dir_fs, 0755, true) && !is_dir($upload_dir_fs)) {
        echo json_encode(['success' => false, 'message' => 'تعذّر إنشاء مجلد الرفع على الخادم.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$safe_name = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest_fs  = $upload_dir_fs . '/' . $safe_name;
$dest_web = $upload_dir_web . '/' . $safe_name;

if (!move_uploaded_file($file['tmp_name'], $dest_fs)) {
    echo json_encode(['success' => false, 'message' => 'تعذّر حفظ الصورة على الخادم.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== حفظ رسالة من نوع image في نفس جدول الرسائل ===== */
try {
    $ins = $pdo->prepare("
        INSERT INTO consultation_messages (consultation_case_id, sender_id, sender_type, message, type, file_path)
        VALUES (?, ?, ?, ?, 'image', ?)
    ");
    // عمود message NOT NULL أصلاً في المخطط الحالي — نضع نصاً بديلاً مقروءاً بدل تعديل بنية العمود
    $ins->execute([$case_id, $my_id, $my_type, '📷 صورة', $dest_web]);
    $message_id = intval($pdo->lastInsertId());

    if ($my_type === 'clinic_staff') {
        $ns = $pdo->prepare("SELECT full_name FROM clinic_staff WHERE id = ? LIMIT 1");
    } else {
        $ns = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    }
    $ns->execute([$my_id]);
    $nr = $ns->fetch(PDO::FETCH_ASSOC);
    $sender_name = $nr ? $nr['full_name'] : 'طبيب';

    $cnt = $pdo->prepare("SELECT COUNT(*) AS c FROM consultation_messages WHERE consultation_case_id = ?");
    $cnt->execute([$case_id]);
    $count = intval($cnt->fetch(PDO::FETCH_ASSOC)['c']);

    $ts = $pdo->prepare("SELECT created_at FROM consultation_messages WHERE id = ? LIMIT 1");
    $ts->execute([$message_id]);
    $created_at = $ts->fetch(PDO::FETCH_ASSOC)['created_at'] ?? date('Y-m-d H:i:s');
} catch (PDOException $e) {
    error_log("upload_consultation_files insert failed: " . $e->getMessage());
    @unlink($dest_fs);
    echo json_encode(['success' => false, 'message' => 'تعذّر حفظ الصورة في قاعدة البيانات.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success'        => true,
    'message'        => 'تم إرسال الصورة.',
    'messages_count' => $count,
    'data' => [
        'id'          => $message_id,
        'sender_id'   => $my_id,
        'sender_type' => $my_type,
        'sender_name' => $sender_name,
        'text'        => '📷 صورة',
        'type'        => 'image',
        'file_url'    => $dest_web,
        'created_at'  => $created_at,
        'is_mine'     => true,
    ],
], JSON_UNESCAPED_UNICODE);
exit;
