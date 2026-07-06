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

/* ===== جلب الحالة ===== */
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
    WHERE cc.id = ?
    LIMIT 1
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
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

/* ===== التحقق من الصلاحية: أحد طرفي المحادثة فقط =====
   - الطبيب المُنشئ (created_by).
   - الطبيب المختار (assigned_doctor_id / assigned_doctor_type). */
$my_type = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_id   = $created_by; // بنفس صيغة staff_id ?? user_id المستخدمة عند الإنشاء

/* المُنشئ: عمود created_by لا يخزّن نوع الحساب (clinic_staff/private) إطلاقاً،
   لذا لا يمكن الاستدلال عليه من clinic_id — فقد يكون clinic_id مساوياً بالصدفة
   لمعرّف حساب طبيب خاص (كما هو حاصل فعلياً في بيانات النظام)، مما كان يجعل هذا
   الشرط يفشل دائماً لصاحب الاستشارة نفسه ويُظهر "الاستشارة غير موجودة" رغم أنها
   موجودة. المطابقة الصحيحة الوحيدة الممكنة هي المعرّف نفسه، لأن created_by
   خُزِّن أصلاً بنفس صيغة staff_id ?? user_id المستخدمة هنا تماماً. */
$is_creator   = ($my_id === intval($r['created_by']));
$is_assigned  = ($my_type === $r['assigned_doctor_type']) && ($my_id === intval($r['assigned_doctor_id']));

if (!$is_creator && !$is_assigned) {
    echo json_encode(['success' => false, 'message' => 'الاستشارة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* نوع حساب المُنشئ (لأجل جلب اسمه فقط أدناه): إن كان الفاتح الحالي هو المُنشئ
   نفسه (الحالة الغالبة لهذه الصفحة) فنوعه هو نوع جلسته الحالية بالضبط. وإلا
   (الطبيب المختار يفتح الحالة) يُحدَّد بالبحث الفعلي عن الحساب في الجدولين. */
if ($is_creator) {
    $creator_type = $my_type;
} else {
    $creator_type = 'private';
    try {
        $ck = $pdo->prepare("SELECT id FROM clinic_staff WHERE id = ? LIMIT 1");
        $ck->execute([$r['created_by']]);
        if ($ck->fetch()) $creator_type = 'clinic_staff';
    } catch (PDOException $e) {
        // غير حرج — نُبقي على الافتراضي 'private'
    }
}

/* ===== اسم المُنشئ (يُحدَّد حسب creator_type أعلاه، وليس حسب هوية الفاتح) ===== */
$creator_name = '';
try {
    if ($creator_type === 'clinic_staff') {
        $s = $pdo->prepare("SELECT full_name FROM clinic_staff WHERE id = ? LIMIT 1");
    } else {
        $s = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    }
    $s->execute([$r['created_by']]);
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

/* عدّادات — المرفقات/المشاركون لا جداول لهم بعد، أما الرسائل فمن consultation_messages */
$attachments_count = 0;
$participants_count = ($r['assigned_doctor_name'] !== null && trim($r['assigned_doctor_name']) !== '') ? 1 : 0;

$messages_count = 0;
try {
    $mc = $pdo->prepare("SELECT COUNT(*) FROM consultation_messages WHERE consultation_case_id = ?");
    $mc->execute([$id]);
    $messages_count = intval($mc->fetchColumn());
} catch (PDOException $e) {
    // غير حرج — يبقى 0
}

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
