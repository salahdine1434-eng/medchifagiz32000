<?php
// منع طباعة أي Warning / Notice / Error كـ HTML داخل الـ Response
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

ob_start();

session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

function respond($success, $message) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length() !== false) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'خطأ في الخادم'], JSON_UNESCAPED_UNICODE);
    }
});

try {
    $clinic_id     = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;
    $id            = $_POST['id'] ?? 0;
    $full_name     = trim($_POST['full_name'] ?? '');
    $role          = trim($_POST['role'] ?? '');
    $department    = trim($_POST['department'] ?? '');   // اسم المصلحة القادم من الواجهة
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $specialty     = trim($_POST['specialty'] ?? '');
    $pharmacy_type = trim($_POST['pharmacy_type'] ?? '');

    if (empty($clinic_id)) {
        respond(false, 'انتهت الجلسة، يرجى إعادة تسجيل الدخول');
    }
    if (empty($id) || empty($full_name) || empty($role)) {
        respond(false, 'يرجى ملء الحقول المطلوبة');
    }

    // المصلحة مخزّنة في clinic_staff.service_id (لا يوجد عمود department).
    // نحوّل اسم المصلحة المُرسَل إلى service_id من جدول services.
    $service_id = null;
    if ($department !== '') {
        $look = $pdo->prepare("SELECT id FROM services WHERE name = ? AND clinic_id = ? LIMIT 1");
        $look->execute([$department, $clinic_id]);
        $found = $look->fetchColumn();
        if ($found !== false) {
            $service_id = $found;
        }
    }

    // نُحدّث service_id فقط عند العثور على مصلحة مطابقة، وإلا نتركه كما هو دون المساس به.
    if ($service_id !== null) {
        $stmt = $pdo->prepare("
            UPDATE clinic_staff
            SET full_name = ?,
                role = ?,
                email = ?,
                phone = ?,
                specialty = ?,
                pharmacy_type = ?,
                service_id = ?
            WHERE id = ? AND clinic_id = ?
        ");
        $params = [$full_name, $role, $email, $phone, $specialty, $pharmacy_type, $service_id, $id, $clinic_id];
    } else {
        $stmt = $pdo->prepare("
            UPDATE clinic_staff
            SET full_name = ?,
                role = ?,
                email = ?,
                phone = ?,
                specialty = ?,
                pharmacy_type = ?
            WHERE id = ? AND clinic_id = ?
        ");
        $params = [$full_name, $role, $email, $phone, $specialty, $pharmacy_type, $id, $clinic_id];
    }

    $result = $stmt->execute($params);

    respond($result, $result ? 'تم حفظ التعديلات' : 'فشل التحديث');

} catch (Throwable $e) {
    error_log('update_clinic_staff error: ' . $e->getMessage());
    // مؤقت للتشخيص: إظهار رسالة PDO الحقيقية. أعد هذا السطر لاحقاً إلى رسالة عامة.
    respond(false, $e->getMessage());
}
