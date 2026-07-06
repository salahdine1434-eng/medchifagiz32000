<?php
session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
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

$patient_id = intval($_GET['patient_id'] ?? 0);
$types_raw  = trim($_GET['types'] ?? '');

if ($patient_id <= 0 || $types_raw === '') {
    echo json_encode([
        "success"     => true,
        "attachments" => []
    ]);
    exit;
}

$allowed_types = ['radiology', 'lab', 'report', 'prescription', 'images'];
$requested = array_values(array_intersect(
    array_filter(array_map('trim', explode(',', $types_raw))),
    $allowed_types
));

if (empty($requested)) {
    echo json_encode([
        "success"     => true,
        "attachments" => []
    ]);
    exit;
}

function cnsAttachSizeLabel($text) {
    $bytes = strlen((string) $text);
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    return round($bytes / 1024, 1) . ' KB';
}

$attachments = [];

/* ===========================
   الأشعة — من طلبات الأشعة الحقيقية للمريض
=========================== */
if (in_array('radiology', $requested)) {
    $stmt = $pdo->prepare("
        SELECT id, radiology_text, status, created_at
        FROM radiology_requests
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $attachments[] = [
            "id"           => "radiology_" . $row['id'],
            "type"         => "radiology",
            "type_label"   => "أشعة",
            "icon"         => "fa-x-ray",
            "title"        => "طلب أشعة #" . $row['id'],
            "preview_text" => $row['radiology_text'],
            "date"         => $row['created_at'],
            "size_label"   => cnsAttachSizeLabel($row['radiology_text'])
        ];
    }
}

/* ===========================
   التحاليل — من طلبات التحاليل الحقيقية للمريض
=========================== */
if (in_array('lab', $requested)) {
    $stmt = $pdo->prepare("
        SELECT id, analysis_text, status, created_at
        FROM lab_requests
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $attachments[] = [
            "id"           => "lab_" . $row['id'],
            "type"         => "lab",
            "type_label"   => "تحاليل",
            "icon"         => "fa-vials",
            "title"        => "طلب تحاليل #" . $row['id'],
            "preview_text" => $row['analysis_text'],
            "date"         => $row['created_at'],
            "size_label"   => cnsAttachSizeLabel($row['analysis_text'])
        ];
    }
}

/* ===========================
   التقرير الطبي — من التقارير الطبية الحقيقية للمريض
=========================== */
if (in_array('report', $requested)) {
    $stmt = $pdo->prepare("
        SELECT id, rapport_content, rapport_date, created_at
        FROM rapport_medical
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $attachments[] = [
            "id"           => "report_" . $row['id'],
            "type"         => "report",
            "type_label"   => "تقرير طبي",
            "icon"         => "fa-file-medical",
            "title"        => "تقرير طبي #" . $row['id'],
            "preview_text" => $row['rapport_content'],
            "date"         => $row['created_at'],
            "size_label"   => cnsAttachSizeLabel($row['rapport_content'])
        ];
    }
}

/* ===========================
   الوصفات — من الوصفات الطبية الحقيقية للمريض
=========================== */
if (in_array('prescription', $requested)) {
    $stmt = $pdo->prepare("
        SELECT id, medicines, notes, rx_date, created_at
        FROM prescriptions
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $content = trim(($row['medicines'] ?? '') . "\n" . ($row['notes'] ?? ''));
        $attachments[] = [
            "id"           => "prescription_" . $row['id'],
            "type"         => "prescription",
            "type_label"   => "وصفة طبية",
            "icon"         => "fa-prescription",
            "title"        => "وصفة طبية #" . $row['id'],
            "preview_text" => $content,
            "date"         => $row['created_at'],
            "size_label"   => cnsAttachSizeLabel($content)
        ];
    }
}

/* ===========================
   الصور — لا يوجد حالياً جدول مخصص لتخزين صور طبية في قاعدة البيانات،
   لذا لا تُعاد أي نتائج وهمية لهذا النوع؛ ستظهر رسالة "لا توجد مرفقات".
=========================== */
if (in_array('images', $requested)) {
    // عمداً بلا نتائج: لا مصدر بيانات حقيقي متاح حالياً لهذا النوع
}

echo json_encode([
    "success"     => true,
    "attachments" => $attachments
]);

exit;
