<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   

$email = $_POST['email'];
$password = $_POST['password'];

if(!empty($email) && !empty($password)){

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    $stmt = $pdo->prepare("SELECT * FROM clinic_staff WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
$isClinicStaff = ($user && isset($user['clinic_id']));
if($user && $user['account_status'] == 'inactive'){
    $isClinicStaff = isset($user['clinic_id']);
    $_SESSION['error'] = 'تم تعطيل حسابك من طرف الإدارة. يرجى التواصل مع الدعم.';

    header('Location: login.php');
    exit;
}
if (
    !$isClinicStaff &&
    $user &&
    $user['role'] != 'patient' &&
    $user['role'] != 'admin' &&
    $user['role'] != 'moderator' &&
    $user['status'] == 'pending'
) {

    $error = "حسابكم قيد المراجعة من طرف الإدارة";

}

elseif (
    !$isClinicStaff &&
    $user &&
    $user['role'] != 'patient' &&
    $user['role'] != 'admin' &&
    $user['role'] != 'moderator' &&
    $user['status'] == 'rejected'
) {
    $error = "تم رفض طلب التسجيل";

}

elseif($user && password_verify($password,$user['password_hash'])){

    // ── فحص وضع الصيانة قبل إنشاء الجلسة ──
    $roleCheck = $user['role'] ?? '';
    if (!in_array($roleCheck, ['super_admin','admin','moderator'])) {
        try {
            $stmtOn = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`='is_on'");
            $stmtOn->execute();
            $isOn = ($stmtOn->fetchColumn() === '1');
            if ($isOn) {
                $keyMap = [
                    'doctor'   => 'access_doctors',
                    'patient'  => 'access_patients',
                    'pharmacy' => 'access_pharmacies',
                    'lab'      => 'access_labs',
                    'hospital' => 'access_hospitals',
                    'clinic'   => 'access_clinics',
                ];
                $accessKey = $keyMap[$roleCheck] ?? null;
                $allowed = false;
                if ($accessKey) {
                    $stmtAcc = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`=?");
                    $stmtAcc->execute([$accessKey]);
                    $allowed = ($stmtAcc->fetchColumn() === '1');
                }
                if (!$allowed) {
                    // منع الدخول وعرض صفحة الصيانة
                    $stmtMsg = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`='user_message'");
                    $stmtMsg->execute();
                    $maintMsg = $stmtMsg->fetchColumn() ?: 'منصة MedChifaGiz تخضع حالياً لأعمال صيانة. نعتذر عن الإزعاج.';
                    $maintMsg = htmlspecialchars($maintMsg, ENT_QUOTES);
                    http_response_code(503);
                    header('Retry-After: 3600');
                    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MedChifaGiz — وضع الصيانة</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;
     background:#0f172a;font-family:Tahoma,Arial,sans-serif;color:#e2e8f0}
.card{background:#1e293b;border:1px solid rgba(255,255,255,.08);border-radius:20px;
      padding:48px 40px;max-width:520px;width:90%;text-align:center}
.icon{font-size:48px;margin-bottom:20px}
h1{font-size:22px;font-weight:700;margin-bottom:12px;color:#f1f5f9}
h1 em{color:#0ea5e9;font-style:normal}
p{font-size:14px;color:#94a3b8;line-height:1.7;margin-bottom:20px}
.badge{display:inline-flex;align-items:center;gap:8px;background:rgba(239,68,68,.1);
       border:1px solid rgba(239,68,68,.3);color:#f87171;padding:6px 16px;
       border-radius:99px;font-size:13px}
.dot{width:8px;height:8px;border-radius:50%;background:#f87171;
     animation:pulse 1.4s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
</style>
</head>
<body>
<div class="card">
  <div class="icon">🔧</div>
  <h1><em>MedChifa</em>Giz — وضع الصيانة</h1>
  <p>$maintMsg</p>
  <div class="badge"><span class="dot"></span>قيد الصيانة — يُرجى العودة لاحقاً</div>
</div>
</body>
</html>
HTML;
                    exit;
                }
            }
        } catch (Exception $e) { /* في حال خطأ DB نكمل الدخول */ }
    }
    // ─────────────────────────────────────────

$_SESSION['user_id'] = $user['id'];
// معرّف العيادة الموحّد لكل الجلسات:
//  - موظف عيادة (سجل من جدول clinic_staff) => له clinic_id حقيقي.
//  - صاحب العيادة (role='clinic' من users) => معرّف عيادته هو id نفسه.
// ضبطه هنا صراحةً يمنع بقاء clinic_id قديم من جلسة سابقة ويجعل save/get/update متطابقة.
$_SESSION['clinic_id'] = $user['clinic_id'] ?? $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['permissions'] = json_decode($user['permissions'] ?? '{}', true);

$isClinicStaff = isset($user['clinic_id']);
$_SESSION['user_email'] = $user['email'];

$_SESSION['name'] = $user['full_name'];
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user['id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$_SESSION['doctor_id'] = $doctor ? $doctor['id'] : 0;
$_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
if(
    !$isClinicStaff &&
    $user['role'] != 'patient' &&
    $user['role'] != 'super_admin' &&
    $user['role'] != 'admin' &&
    $user['role'] != 'moderator' &&
    $user['profile_completed'] == 0
){
    if($user['role']=="doctor"){
        header("Location: complete_doctor_profile.php");
    }
    elseif($user['role']=="pharmacy"){
        header("Location: complete_pharmacy_profile.php");
    }
    elseif($user['role']=="clinic"){
        header("Location: complete_clinic_profile.php");
    }
    elseif($user['role']=="lab"){
        header("Location: complete_lab_profile.php");
    }

    exit();
}

# هنا نتحقق هل 2FA مفعل
if(
    !$isClinicStaff &&
    !empty($user['twofa_enabled']) &&
    $user['twofa_enabled'] == 1
){

    $_SESSION['temp_user_2fa'] = $user['id'];

    header("Location: login_2fa.php");
    exit();
}
# إذا لم يكن مفعل يدخل مباشرة
if($isClinicStaff){
    

    // ── تحديث الدخول لموظّف العيادة (clinic_staff) ──
    // عبارة واحدة ذرّية: previous_login يأخذ القيمة القديمة لـ last_login
    // قبل أن يُحدَّث last_login إلى NOW(). MySQL يقيّم المسندات يساراً→يميناً،
    // لذا الجانب الأيمن (last_login) في أول مسند يحمل القيمة القديمة.
    $pdo->prepare("
        UPDATE clinic_staff
        SET previous_login = last_login,
            last_login     = NOW()
        WHERE id = ?
    ")->execute([$user['id']]);
$_SESSION['is_clinic_staff'] = 1;
$_SESSION['staff_id'] = $user['id'];
$_SESSION['clinic_id'] = $user['clinic_id'];
$_SESSION['service_id'] = $user['service_id'];
    if($user['role'] == 'doctor'){
        header("Location: dr_dashboard.php");
    }
    elseif($user['role'] == 'nurse'){
        header("Location: nurse_dashboard.html");
    }
    elseif($user['role'] == 'lab_technician'){
        header("Location: labo_central.html");
    }
    elseif($user['role'] == 'radiology_technician'){
        header("Location: radio_central.html");
    }
   elseif($user['role'] == 'pharmacist'){

    if($user['pharmacy_type'] == 'صيدلية مصلحة'){
        header("Location: pharmacie_service.html");
    }else{
        header("Location: pharmacie_central.html");
    }
}
    elseif($user['role'] == 'receptionist'){
        header("Location: clinic_admin.php");
    }
    elseif($user['role'] == 'service_admin'){
        header("Location: service_admin_dashboard.html");
    }
    exit();
}
if($user['role']=="doctor"){
    header("Location: dr_dashboard.php");
}
elseif($user['role']=="pharmacy"){
    header("Location: pharmacie_central.html");
}
elseif($user['role']=="lab"){
    header("Location:labo_central.html");
}
elseif($user['role']=="clinic"){

    // ── تحديث الدخول لصاحب العيادة (users) ──
    // نفس منطق الموظّفين: previous_login يأخذ القيمة القديمة لـ last_login
    // ثم last_login يصبح NOW()، في عبارة واحدة ذرّية قبل التوجيه.
    $pdo->prepare("
        UPDATE users
        SET previous_login = last_login,
            last_login     = NOW()
        WHERE id = ?
    ")->execute([$user['id']]);

    header("Location: clinic_admin.php");
    exit();
}
elseif(
    $user['role']=="super_admin" ||
    $user['role']=="admin" ||
    $user['role']=="moderator"
){
    header("Location: super_admin_dashboard.php");
    exit;
}
else{
    header("Location: patient_dashboard.php");
}

exit();

}else{
$error="البريد الإلكتروني أو كلمة السر غير صحيحة";
}

}else{
$error="يرجى ملء جميع الحقول";
}
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>تسجيل الدخول</title>

<link rel="stylesheet" href="style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

<div class="container">

<div class="logo-area">
<img src="medchifagz.png" alt="Logo">
</div>

<div class="card login-card show">

<h2>تسجيل الدخول</h2>

<?php if($error != ""){ ?>
<div id="error-message" class="error-msg">
    <?php echo $error; ?>
</div>
<?php } ?>
<?php if(isset($_SESSION['error'])){ ?>
<div id="error-message" class="error-msg">
    <?= $_SESSION['error']; ?>
</div>
<?php unset($_SESSION['error']); ?>
<?php } ?>
<?php if(isset($_SESSION['success_message'])){ ?>
<div id="successMsg" class="success-msg">
   ✅ تم استلام معلوماتكم ووثائقكم، سيتم الرد عليكم بعد مراجعة الإدارة.
</div>
<?php unset($_SESSION['success_message']); ?>
<?php } ?>

<form method="POST">

<div class="input-group">
<i class="fas fa-envelope icon"></i>
<input type="email" name="email" placeholder="البريد الإلكتروني" required>
</div>

<div class="input-group">
<i class="fas fa-lock icon"></i>
<input type="password" name="password" placeholder="كلمة السر" required>
</div>

<div class="action-buttons">
<button type="submit" class="primary-btn">
دخول
</button>
</div>
<p class="forgot-pass">
<a href="forgot_password.php">نسيت كلمة السر؟</a>
</p>

</form>

<p class="step-text" style="margin-top:30px;text-align:center;">
ليس لديك حساب؟
<a href="index.html" style="color:#1231e3;font-weight:bold;">
إنشاء حساب
</a>
</p>

</div>

</div>
<script>
setTimeout(function() {

    var msg = document.getElementById("error-message");

    if(msg){

        msg.style.transition = "opacity 2s";

        msg.style.opacity = "0";

        setTimeout(function(){
            msg.style.display = "none";
        }, 2000);

    }

}, 5000);
</script>
<script>
setTimeout(function(){

    let msg = document.getElementById('successMsg');

    if(msg){

        msg.style.transition = "opacity 1.5s ease";
        msg.style.opacity = "0";

        setTimeout(function(){
            msg.remove();
        },1500);

    }

},5000);
</script>
</body>
</html>