<?php
session_start();
require_once "db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$clinic_id       = $_SESSION['clinic_id'] ?? null;

$logged_in = $is_clinic_staff
    ? !empty($_SESSION['staff_id'])
    : !empty($_SESSION['user_id']);

if (!$logged_in) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===========================
   أطباء العيادة (استشارة داخلية)
   فقط أطباء نفس العيادة الحالية
=========================== */

$internal_doctors = [];

if ($is_clinic_staff && $clinic_id) {
    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name
        FROM clinic_staff
        WHERE clinic_id = ?
          AND role = 'doctor'
          AND account_status = 'active'
        ORDER BY full_name
    ");
    $stmt->execute([$clinic_id]);
    $internal_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===========================
   أطباء العيادات (لجميع العيادات) - لدمجها في القائمة الخارجية
=========================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name
    FROM clinic_staff
    WHERE role = 'doctor'
      AND account_status = 'active'
    ORDER BY full_name
");
$stmt->execute();
$clinic_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   الأطباء الخواص (users JOIN doctors)
=========================== */

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name
    FROM users u
    INNER JOIN doctors d ON d.user_id = u.id
    WHERE u.role = 'doctor'
      AND u.account_status = 'active'
      AND u.status = 'approved'
    ORDER BY u.full_name
");
$stmt->execute();
$private_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   دمج أطباء العيادات + الأطباء الخواص (استشارة خارجية)
   مع حذف التكرار عبر بادئة تحدد مصدر كل طبيب
=========================== */

$external_map = [];

foreach ($clinic_doctors as $row) {
    $key = 'clinic_' . $row['id'];
    $external_map[$key] = [
        "id"        => $key,
        "full_name" => $row['full_name']
    ];
}

foreach ($private_doctors as $row) {
    $key = 'user_' . $row['id'];
    if (!isset($external_map[$key])) {
        $external_map[$key] = [
            "id"        => $key,
            "full_name" => $row['full_name']
        ];
    }
}

$external_doctors = array_values($external_map);

usort($external_doctors, function ($a, $b) {
    return strcmp($a['full_name'], $b['full_name']);
});

/* ===========================
   تحديد معرّف الطبيب الحالي (doctor_id في medical_records)
   - طبيب عيادة: نفس معرف صفه في clinic_staff (staff_id)
   - طبيب خاص: معرف صفه في جدول doctors (وليس معرف users)
=========================== */

$current_doctor_id = 0;

if ($is_clinic_staff) {
    $current_doctor_id = intval($_SESSION['staff_id'] ?? 0);
} else {
    if (!empty($_SESSION['doctor_id'])) {
        $current_doctor_id = intval($_SESSION['doctor_id']);
    } elseif (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $current_doctor_id = intval($row['id']);
        }
    }
}

/* ===========================
   جلب المرضى — كل مرضى أرشيف هذا الطبيب (من medical_records فقط)

   السبب الجذري للمشكلة السابقة: الاستعلام القديم كان يشترط
   INNER JOIN مع users ثم INNER JOIN مع patients، أي أنه كان
   يستبعد:
     1) المرضى المضافين يدويًا (add_patient_today.php) الذين
        patient_id لديهم = 0 (لا يوجد users.id = 0).
     2) المرضى الذين لديهم patient_id صحيح (= users.id) لكن لا
        يملكون صفًا مقابلًا في جدول patients (وهو ما يحدث فعليًا
        لغالبية الحالات في قاعدة البيانات).

   الحل: الاعتماد على medical_records وحدها كمصدر وحيد للحقيقة،
   دون أي اشتراط لوجود صف في patients أو حتى في users.
     - إن كان mr.patient_id > 0: نجلب الاسم من users إن وُجد
       (LEFT JOIN اختياري) وإلا نستخدم mr.full_name كاحتياط،
       ونجمّع (dedup) حسب patient_id نفسه.
     - إن كان mr.patient_id = 0 (مريض يدوي بلا حساب): نجمّع
       حسب الاسم نفسه (بعد التطبيع) لأنه المعرّف الوحيد المتاح،
       ونعيد id = 0 (نفس القيمة التي يخزنها medical_records
       و appointments لهذه الحالة أصلاً في بقية النظام).
=========================== */

$patients = [];

if ($current_doctor_id > 0) {
    $stmt = $pdo->prepare("
        SELECT
            grp.id,
            MAX(grp.full_name) AS full_name
        FROM (
            SELECT
                CASE WHEN mr.patient_id > 0 THEN mr.patient_id ELSE 0 END AS id,
                COALESCE(
                    u.full_name,
                    NULLIF(TRIM(mr.full_name), ''),
                    CONCAT('مريض بدون اسم #', mr.id)
                ) AS full_name,
                CASE
                    WHEN mr.patient_id > 0 THEN CONCAT('u_', mr.patient_id)
                    ELSE CONCAT('m_', LOWER(TRIM(COALESCE(mr.full_name, ''))))
                END AS dedup_key
            FROM medical_records mr
            LEFT JOIN users u ON u.id = mr.patient_id AND mr.patient_id > 0
            WHERE mr.doctor_id = ?
        ) grp
        GROUP BY grp.dedup_key
        ORDER BY full_name
    ");
    $stmt->execute([$current_doctor_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===========================
   إرسال البيانات
=========================== */

echo json_encode([
    "success"           => true,
    "internal_doctors"  => $internal_doctors,
    "external_doctors"  => $external_doctors,
    "patients"          => $patients
]);

exit;
