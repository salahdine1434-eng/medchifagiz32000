<?php
session_start();
require_once 'db.php';
require_once 'send_account_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}
if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ]);
    exit;
}
// نفس منطق get_clinic_staff.php تماماً — هذا هو جوهر الإصلاح:
// كان الحفظ يستعمل clinic_id والجلب يستعمل user_id، فيُكتب السجل بمعرّف ثم يُبحث عنه بمعرّف آخر.
$clinic_id = $_SESSION['clinic_id'] ?? $_SESSION['user_id'] ?? null;

if (empty($clinic_id)) {
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => 'انتهت الجلسة، يرجى إعادة تسجيل الدخول'
    ]);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$password  = $_POST['password']       ?? '';
$role      = $_POST['role']           ?? '';
$specialty = trim($_POST['specialty'] ?? '');
$pharmacy_type = trim($_POST['pharmacy_type'] ?? '');
$dept_name = trim($_POST['dept_name'] ?? '');

if (empty($full_name) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'يرجى ملء الحقول المطلوبة']);
    exit;
}

// التحقق من أن الدور من القيم المسموح بها في ENUM
$allowed_roles = ['doctor','nurse','lab_technician','radiology_technician','pharmacist','receptionist','service_admin'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'الدور الوظيفي غير صحيح: ' . $role]);
    exit;
}

// البحث عن المصلحة بالاسم — وإنشاؤها تلقائياً إذا لم تكن موجودة
$service_id = null;
if ($dept_name !== '') {
    $svc = $pdo->prepare("SELECT id FROM services WHERE clinic_id = ? AND name = ? LIMIT 1");
    $svc->execute([$clinic_id, $dept_name]);
    $row = $svc->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // المصلحة موجودة — نأخذ id مباشرة
        $service_id = $row['id'];
    } else {
        // المصلحة غير موجودة — ننشئها تلقائياً
        try {
            $insert_svc = $pdo->prepare("INSERT INTO services (clinic_id, name) VALUES (?, ?)");
            $insert_svc->execute([$clinic_id, $dept_name]);
            $service_id = $pdo->lastInsertId();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في إنشاء المصلحة: ' . $e->getMessage()]);
            exit;
        }
    }
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO clinic_staff
        (clinic_id, full_name, email, phone, password_hash, role, specialty, service_id, pharmacy_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)
    ");

$stmt->execute([
    $clinic_id,
    $full_name,
    $email ?: null,
    $phone ?: null,
    $password_hash,
    $role,
    ($role === 'doctor' && $specialty !== '') ? $specialty : null,
    $service_id,
    ($role === 'pharmacist') ? $pharmacy_type : null
]);
if (!empty($email)) {
    sendAccountEmail(
        $email,
        $full_name,
        $password,
        $role
    );
}
    echo json_encode(['success' => true, 'message' => 'تم حفظ المستخدم بنجاح']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'SQL ERROR: ' . $e->getMessage()]);
}
?>
