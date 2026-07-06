<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['register']) || !isset($_SESSION['otp'])) {
    header("Location: register_step1.php");
    exit;
}

$entered_otp = $_POST['otp'] ?? '';

if ($entered_otp != $_SESSION['otp']) {
    die("رمز التحقق غير صحيح ❌");
}

// معلومات المستخدم من session
$full_name = $_SESSION['register']['full_name'];
$email     = $_SESSION['register']['email'];
$password  = $_SESSION['register']['password'];
$role      = $_SESSION['register']['role'];

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {

    // نتحقق إذا الإيميل مسجل مسبقاً
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        die("البريد الإلكتروني مسجل مسبقاً ❌");
    }

    // إدخال المستخدم
 $status = 'approved';

$stmt = $pdo->prepare("
    INSERT INTO users (full_name, email, password_hash, role, status)
    VALUES (?, ?, ?, ?, ?)
");
    $stmt->execute([
    $full_name,
    $email,
    $password_hash,
    $role,
    $status
]);

    $user_id = $pdo->lastInsertId();

    // إنشاء سجل حسب الدور
   if ($role === 'doctor') {

    $stmt2 = $pdo->prepare("INSERT INTO doctors (user_id) VALUES (?)");
    $stmt2->execute([$user_id]);


} elseif ($role === 'patient') {


    $stmt2 = $pdo->prepare("INSERT INTO patients (user_id) VALUES (?)");
    $stmt2->execute([$user_id]);
// ربط الحساب الجديد بالملف الطبي إذا كان موجودًا
$update = $pdo->prepare("
    UPDATE medical_records
    SET patient_id = ?
    WHERE email = ?
      AND patient_id = 0
");

$update->execute([
    $user_id,
    $email
]);
} elseif ($role === 'pharmacy') {

    $stmt2 = $pdo->prepare("INSERT INTO pharmacy_profiles (user_id) VALUES (?)");
    $stmt2->execute([$user_id]);

}elseif ($role === 'lab') {

    $stmt2 = $pdo->prepare("INSERT INTO lab_profiles (user_id) VALUES (?)");
    $stmt2->execute([$user_id]);

}elseif ($role === 'clinic') {

    $stmt2 = $pdo->prepare("INSERT INTO clinic_profiles (user_id) VALUES (?)");
    $stmt2->execute([$user_id]);

}


    // حذف السيشن
    unset($_SESSION['register']);
    unset($_SESSION['otp']);

  header("Location: setup_2fa.php?email=" . urlencode($email));
    exit;

} catch (PDOException $e) {
    die("حدث خطأ أثناء التسجيل: " . $e->getMessage());
}
?>