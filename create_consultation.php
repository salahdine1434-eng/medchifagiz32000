<?php
session_start();
require_once "db.php"; // عدل المسار إذا كان db.php في مكان آخر

header('Content-Type: application/json; charset=utf-8');

// السماح فقط بطلبات POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ]);
    exit;
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) && empty($_SESSION['staff_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// استقبال البيانات
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من وجود البيانات
if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'No data received'
    ]);
    exit;
}

// استخراج البيانات
$clinic_id  = intval($_SESSION['clinic_id'] ?? 0);
$created_by = intval($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0);

// ملاحظة (السبب الجذري): المرضى المُضافون يدوياً في هذا النظام يملكون
// معرّفاً = 0 (كما في medical_records و appointments)، و get_consultation_form_data.php
// يُرجعهم بـ id = 0. لذلك نميّز بين "لم يُختَر مريض" (قيمة فارغة) و"مريض يدوي"
// (القيمة "0" أو 0)، بدل الاعتماد على intval() <= 0 الذي كان يخلط بينهما
// فيرفض المريض اليدوي بالخطأ "Patient is required".
$patient_raw      = $data['patient_id'] ?? '';
$patient_selected = ($patient_raw !== '' && $patient_raw !== null);
$patient_id       = intval($patient_raw);

$consultation_scope = trim($data['consultation_scope'] ?? '');
$consultation_type  = trim($data['consultation_type'] ?? '');

$title       = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');

$priority_raw          = trim($data['priority'] ?? 'normal');
$hide_patient_identity = !empty($data['hide_patient_identity']) ? 1 : 0;

// استخراج معرّف الطبيب المُعيَّن ونوعه (clinic_staff أو private)
// - استشارة داخلية: القيمة دائماً معرّف صف في clinic_staff (رقم فقط)
// - استشارة خارجية: القيمة بصيغة "clinic_<id>" أو "user_<id>"
$assigned_doctor_raw  = trim((string)($data['assigned_doctor_id'] ?? ''));
$assigned_doctor_id   = 0;
$assigned_doctor_type = 'clinic_staff';

if ($consultation_scope === 'internal') {
    $assigned_doctor_id   = intval($assigned_doctor_raw);
    $assigned_doctor_type = 'clinic_staff';
} elseif (preg_match('/^clinic_(\d+)$/', $assigned_doctor_raw, $m)) {
    $assigned_doctor_id   = intval($m[1]);
    $assigned_doctor_type = 'clinic_staff';
} elseif (preg_match('/^user_(\d+)$/', $assigned_doctor_raw, $m)) {
    $assigned_doctor_id   = intval($m[1]);
    $assigned_doctor_type = 'private';
} else {
    $assigned_doctor_id = intval($assigned_doctor_raw);
}

// تطبيع نوع الاستشارة ليطابق قيم enum في قاعدة البيانات
$consultation_type_map = [
    'xray_review' => 'radiology_review'
];
if (isset($consultation_type_map[$consultation_type])) {
    $consultation_type = $consultation_type_map[$consultation_type];
}

// تطبيع الأولوية لتطابق قيم enum في قاعدة البيانات
$priority_map = [
    'normal' => 'normal',
    'medium' => 'urgent',
    'urgent' => 'critical'
];
$priority = $priority_map[$priority_raw] ?? 'normal';

// ── مرحلة الاختبار ──
// الحقول الإجبارية فقط: الطبيب، المريض، عنوان الاستشارة، تفاصيل الحالة.
// باقي الحقول اختيارية وتُطبَّق لها قيم افتراضية مناسبة عند غيابها،
// حتى تُنشأ الاستشارة بنجاح.
if ($consultation_scope === '')
    $consultation_scope = 'internal';          // نطاق افتراضي

if ($consultation_type === '')
    $consultation_type = 'medical_opinion';    // نوع افتراضي
// (الأولوية مطبّعة مسبقاً إلى 'normal' افتراضياً عبر $priority_map،
//  و hide_patient_identity افتراضياً 0، و clinic_id يُؤخذ من الجلسة.)

// التحقق من صحة الحقول
$errors = [];

// المريض: مقبول id=0 (مريض يدوي)؛ نرفض فقط عند عدم اختيار أي مريض
if (!$patient_selected)
    $errors[] = "Patient is required.";

// الطبيب مطلوب
if ($assigned_doctor_id <= 0)
    $errors[] = "Doctor is required.";

// عنوان الاستشارة مطلوب
if ($title === '')
    $errors[] = "Title is required.";

// تفاصيل الحالة مطلوبة
if ($description === '')
    $errors[] = "Description is required.";

// إذا وجدت أخطاء
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// التأكد من وجود الطبيب المختار ضمن المصدر الصحيح، وأنه فعلاً
// طبيب داخل نفس العيادة في حالة الاستشارة الداخلية
if ($consultation_scope === 'internal') {
    $stmt = $pdo->prepare("
        SELECT id FROM clinic_staff
        WHERE id = ? AND clinic_id = ? AND role = 'doctor' AND account_status = 'active'
    ");
    $stmt->execute([$assigned_doctor_id, $clinic_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'الطبيب المختار غير موجود ضمن هذه العيادة.'
        ]);
        exit;
    }
} else {
    if ($assigned_doctor_type === 'clinic_staff') {
        $stmt = $pdo->prepare("
            SELECT id FROM clinic_staff
            WHERE id = ? AND role = 'doctor' AND account_status = 'active'
        ");
        $stmt->execute([$assigned_doctor_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id FROM users u
            INNER JOIN doctors d ON d.user_id = u.id
            WHERE u.id = ? AND u.role = 'doctor' AND u.account_status = 'active'
        ");
        $stmt->execute([$assigned_doctor_id]);
    }
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'الطبيب المختار غير موجود.'
        ]);
        exit;
    }
}

// إنشاء رقم الاستشارة
$case_number = 'CASE-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

$stmt = $pdo->prepare("
INSERT INTO consultation_cases (
    case_number,
    clinic_id,
    patient_id,
    created_by,
    assigned_doctor_id,
    assigned_doctor_type,
    consultation_scope,
    consultation_type,
    title,
    description,
    priority,
    hide_patient_identity
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)");

$ok = $stmt->execute([
    $case_number,
    $clinic_id,
    $patient_id,
    $created_by,
    $assigned_doctor_id,
    $assigned_doctor_type,
    $consultation_scope,
    $consultation_type,
    $title,
    $description,
    $priority,
    $hide_patient_identity
]);

if (!$ok) {
    echo json_encode([
        'success' => false,
        'message' => 'فشل إنشاء الاستشارة.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'تم إنشاء الاستشارة بنجاح.',
    'case_number' => $case_number,
    'id' => $pdo->lastInsertId()
]);
exit;
