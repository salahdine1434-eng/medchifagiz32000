<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'المستخدم غير مسجل الدخول'
    ]);
    exit;
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'ماكانتش بيانات'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE patients SET
            first_name = ?,
            last_name = ?,
            birth_date = ?,
            last_period_date = ?,
            gender = ?,
            blood_type = ?,
            weight = ?,
            height = ?,
            phone = ?,
            chronic_diseases = ?,
            allergies = ?,
            medications = ?,
            health_notes = ?,
            emergency_name = ?,
            emergency_phone = ?,
            medical_completed = 1
        WHERE user_id = ?
      
    ");

$stmt->execute([
    $data['first_name'] ?? null,
    $data['last_name'] ?? null,
    $data['birth_date'] ?? null,
    $data['last_period_date'] ?? null,
    $data['gender'] ?? null,
    $data['blood_type'] ?? null,
    $data['weight'] ?? null,
    $data['height'] ?? null,
    $data['phone'] ?? null,
    $data['chronic_diseases'] ?? null,
    $data['allergies'] ?? null,
    $data['medications'] ?? null,
    $data['health_notes'] ?? null,
    $data['emergency_name'] ?? null,
    $data['emergency_phone'] ?? null,
    $_SESSION['user_id']
]);
    // حفظ في الأرشيف

$patient_name = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');

/* ============================================================
   الإصلاح: تحديد medical_record_id الحقيقي لربط الأرشيف بالملف الطبي.
   1) إن أرسلته الواجهة صراحةً (_apfCurrentRecordId) نستعمله مباشرة.
   2) وإلا نبحث عن أحدث ملف طبي لنفس المريض (patient_id = user_id).
   3) وإلا أحدث ملف طبي لنفس الطبيب (احتياط أخير).
   ============================================================ */
$medical_record_id = null;

if (!empty($data['medical_record_id']) && (int)$data['medical_record_id'] > 0) {
    $medical_record_id = (int)$data['medical_record_id'];
} elseif (!empty($data['record_id']) && (int)$data['record_id'] > 0) {
    $medical_record_id = (int)$data['record_id'];
} elseif (!empty($data['patient_id'])) {
    $stmtMR = $pdo->prepare("
        SELECT id FROM medical_records
        WHERE patient_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmtMR->execute([$data['patient_id']]);
    $mr = $stmtMR->fetch(PDO::FETCH_ASSOC);
    if ($mr) {
        $medical_record_id = (int)$mr['id'];
    }
}

if ($medical_record_id === null) {
    // احتياط أخير: أحدث ملف طبي أنشأه هذا الطبيب
    $stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmtDoc->execute([$_SESSION['user_id']]);
    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $stmtMR2 = $pdo->prepare("
            SELECT id FROM medical_records
            WHERE doctor_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmtMR2->execute([$doc['id']]);
        $mr2 = $stmtMR2->fetch(PDO::FETCH_ASSOC);
        if ($mr2) {
            $medical_record_id = (int)$mr2['id'];
        }
    }
}

$stmtArchive = $pdo->prepare("
    INSERT INTO archived_records
    (
        medical_record_id,
        patient_name,
        birth_date,
        medical_condition,
        job_type,
        blood_pressure,
        heart_rate,
        temperature,
        created_at
    )

    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmtArchive->execute([
    $medical_record_id,
    $patient_name,
    $data['birth_date'] ?? null,
    $data['chronic_diseases'] ?? null,
    $data['job'] ?? null,
    $data['blood_pressure'] ?? null,
    $data['heart_rate'] ?? null,
    $data['temperature'] ?? null
]);
// حفظ الموعد القادم إذا كان موجود
if (
    !empty($data['next_appointment_date']) &&
    !empty($data['next_appointment_time'])
) {
    // نجيب doctor_id الحقيقي
    $stmtDoctor = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmtDoctor->execute([$_SESSION['user_id']]);
    $doctor = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

    $doctor_id = $doctor['id'];

    // patient_id
    $patient_id = $data['patient_id'];

    // نسجل الموعد
    $stmtApp = $pdo->prepare("
        INSERT INTO appointments
        (patient_id, doctor_id, status, created_at, appointment_date, appointment_time)
        VALUES (?, ?, 'confirmed', NOW(), ?, ?)
    ");

    $stmtApp->execute([
        $patient_id,
        $doctor_id,
        $data['next_appointment_date'],
        $data['next_appointment_time']
    ]);
}
    echo json_encode([
        'success' => true,
        'message' => 'تم الحفظ'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
