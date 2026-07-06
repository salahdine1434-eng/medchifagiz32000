<?php
/**
 * get_consultation_details.php
 * ---------------------------------------------------------------------------
 * المرحلة الثانية من نظام الاستشارات — قراءة تفاصيل استشارة واحدة (قراءة فقط).
 *
 * يُرجع كل بيانات الحالة من consultation_cases حسب المعرّف، مع أسماء المنشئ
 * والطبيب المختار والمريض، وتسميات عربية جاهزة، وعدّادات (المرفقات/المشاركين/
 * الرسائل) — وهي 0 حالياً لعدم وجود جداول لها بعد.
 *
 * أمان: يقتصر على الاستشارات التي أنشأها المستخدم الحالي (created_by = me).
 * لا يكتب أي شيء في قاعدة البيانات (SELECT فقط).
 * يتوافق مع نفس نموذج الجلسة في get_consultations.php / create_consultation.php.
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

$created_by = intval($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0);
if ($created_by <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== المعرّف ===== */
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الاستشارة غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== جلب الحالة (المُرسَلة من هذا المستخدم فقط) ===== */
$where  = "cc.id = ? AND cc.created_by = ?";
$params = [$id, $created_by];
if ($is_clinic_staff && $clinic_id > 0) {
    $where   .= " AND cc.clinic_id = ?";
    $params[] = $clinic_id;
}

$sql = "
    SELECT
        cc.*,
        COALESCE(cs.full_name, du.full_name) AS assigned_doctor_name,
        pu.full_name AS patient_name
    FROM consultation_cases cc
    LEFT JOIN clinic_staff cs
        ON cs.id = cc.assigned_doctor_id AND cc.assigned_doctor_type = 'clinic_staff'
    LEFT JOIN users du
        ON du.id = cc.assigned_doctor_id AND cc.assigned_doctor_type = 'private'
    LEFT JOIN users pu
        ON pu.id = cc.patient_id AND cc.patient_id > 0
    WHERE $where
    LIMIT 1
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultation_details failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب تفاصيل الاستشارة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$r) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== اسم المُنشئ (هو المستخدم الحالي لأننا قيّدنا created_by = me) ===== */
$creator_name = '';
try {
    if ($is_clinic_staff) {
        $s = $pdo->prepare("SELECT full_name FROM clinic_staff WHERE id = ? LIMIT 1");
        $s->execute([$created_by]);
    } else {
        $s = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
        $s->execute([$created_by]);
    }
    $cr = $s->fetch(PDO::FETCH_ASSOC);
    if ($cr) $creator_name = $cr['full_name'];
} catch (PDOException $e) {
    // غير حرج — نتركه فارغاً
}

/* ===== تسميات عربية (مطابقة للمخطط) ===== */
$type_labels = [
    'medical_opinion' => 'رأي طبي', 'urgent_opinion' => 'رأي عاجل',
    'case_discussion' => 'مناقشة حالة', 'patient_transfer' => 'طلب تحويل مريض',
    'radiology_review' => 'طلب تفسير أشعة', 'lab_review' => 'طلب تفسير تحاليل',
    'follow_up' => 'متابعة حالة',
];
$priority_labels = ['normal' => 'عادية', 'urgent' => 'مستعجلة', 'critical' => 'عاجلة جداً'];
$status_labels   = ['new' => 'جديدة', 'in_review' => 'قيد المراجعة', 'answered' => 'تم الرد', 'closed' => 'مغلقة'];
$scope_labels    = ['internal' => 'داخلية', 'external' => 'خارجية'];

$hidden = intval($r['hide_patient_identity']) === 1;

/* اسم المريض يُخفى إن فُعّلت الخصوصية */
$patient_display = null;
if (!$hidden) {
    $patient_display = ($r['patient_name'] !== null && trim($r['patient_name']) !== '')
        ? $r['patient_name'] : null;
}

/* عدّادات — لا جداول لها بعد (مرفقات/رسائل/مشاركون) */
$attachments_count = 0;
$messages_count    = 0;
$participants_count = ($r['assigned_doctor_name'] !== null && trim($r['assigned_doctor_name']) !== '') ? 1 : 0;

echo json_encode([
    'success' => true,
    'consultation' => [
        'id'                    => intval($r['id']),
        'case_number'           => $r['case_number'],
        'scope'                 => $r['consultation_scope'],
        'scope_label'           => $scope_labels[$r['consultation_scope']] ?? $r['consultation_scope'],
        'type'                  => $r['consultation_type'],
        'type_label'            => $type_labels[$r['consultation_type']] ?? $r['consultation_type'],
        'title'                 => $r['title'],
        'description'           => $r['description'],
        'priority'              => $r['priority'],
        'priority_label'        => $priority_labels[$r['priority']] ?? $r['priority'],
        'status'                => $r['status'],
        'status_label'          => $status_labels[$r['status']] ?? $r['status'],
        'hide_patient_identity' => $hidden ? 1 : 0,
        'patient_name'          => $patient_display,
        'assigned_doctor_name'  => $r['assigned_doctor_name'],
        'creator_name'          => $creator_name,
        'created_at'            => $r['created_at'],
        'updated_at'            => $r['updated_at'],
        'closed_at'             => $r['closed_at'],
        'counts' => [
            'attachments'  => $attachments_count,
            'participants' => $participants_count,
            'messages'     => $messages_count,
        ],
    ],
], JSON_UNESCAPED_UNICODE);
exit;
