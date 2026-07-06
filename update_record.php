<?php
/* ════════════════════════════════════════════════════════════════
   update_record.php
   تحديث (UPDATE) السجل الطبي الموجود في جدول medical_records
   بنفس المفتاح id الذي يعرضه view_record.php — بدون إنشاء سجل جديد.
   يُستدعى من زر "حفظ التعديلات" داخل نافذة الملف الطبي (الأرشيف).
═══════════════════════════════════════════════════════════════════ */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['id']) || empty($data['fields']) || !is_array($data['fields'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

$id = (int) $data['id'];

/* ── قائمة بيضاء بأعمدة medical_records القابلة للتعديل ──
   مطابقة تمامًا لحقول view_record.php (data-field-name).
   لا تتضمن أي أعمدة نظام (id/patient_id/doctor_id/created_at/updated_at)
   ولا التقرير الطبي rapport_medical ولا بطاقة العلاج fiche_traitement. */
$allowed = [
    // المعلومات الشخصية
    'full_name', 'birth_info', 'birth_place', 'birth_date', 'age', 'gender',
    'marital_status', 'job', 'address', 'phone', 'residency_status',
    // الدخول
    'entry_date', 'room_number',
    // سبب الزيارة
    'reason_exam', 'reason_visit', 'symptoms',
    // العلامات الحيوية
    'blood_pressure', 'blood_sugar', 'heart_rate', 'temperature', 'oxygen_level', 'blood_type',
    // التاريخ المرضي
    'chronic_patient', 'chronic_family', 'genetic_diseases',
    // متابعة الحمل
    'pregnancy_follow', 'last_period', 'expected_birth', 'pregnancy_count', 'birth_count',
    'abortions', 'cesarean', 'father_status', 'fetus_position', 'fetus_move', 'fetus_weight',
    // التحاليل والأشعة
    'medical_tests', 'radiology',
    // التشخيص والعلاج
    'diagnostic', 'medications', 'prescription', 'medical_report',
    // المتابعة والملاحظات
    'admission_date', 'next_appointment', 'next_appointment_date', 'next_appointment_time',
    'appointment_time', 'doctor_notes', 'general_notes',
];

$set    = [];
$params = [];

foreach ($data['fields'] as $col => $val) {
    if (in_array($col, $allowed, true)) {
        $set[]    = "`$col` = ?";
        $params[] = ($val === '' ? null : $val);
    }
}

if (empty($set)) {
    echo json_encode(['success' => false, 'message' => 'لا توجد حقول قابلة للتحديث']);
    exit;
}

$params[] = $id;

try {
    $sql  = "UPDATE medical_records SET " . implode(', ', $set) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'تم حفظ التعديلات بنجاح']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
