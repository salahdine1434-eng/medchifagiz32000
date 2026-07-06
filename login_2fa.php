<?php
session_start();
require_once "db.php";
require_once "lib/GoogleAuthenticator.php";

$ga = new GoogleAuthenticator();
$error = "";

if (!isset($_SESSION['temp_user_2fa'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['temp_user_2fa'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $code = str_replace(' ', '', $_POST['code']);
    $secret = $user['twofa_secret'];

    if ($ga->verifyCode($secret, $code, 2)) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        unset($_SESSION['temp_user_2fa']);

        if ($user['role'] == "doctor") {
            header("Location: dr_dashboard.php");
        } else {
            header("Location: patient_dashboard.html");
        }
        exit;

    } else {
        $error = "رمز التحقق غير صحيح";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>التحقق بخطوتين</title>

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

<h2>التحقق بخطوتين</h2>

<?php if(!empty($error)): ?>

<p style="color:red;text-align:center;">
<?php echo $error; ?>
</p>

<?php endif; ?>

<form method="POST">

<div class="input-group">

<i class="fas fa-key icon"></i>

<input
type="text"
name="code"
placeholder="رمز التطبيق"
maxlength="6"
pattern="[0-9]{6}"
inputmode="numeric"
required
oninput="this.value=this.value.replace(/[^0-9]/g,'')"
/>

</div>

<div class="action-buttons">

<button type="submit" class="primary-btn">
تأكيد
</button>

</div>

</form>

<p class="step-text" style="margin-top:20px;text-align:center;">

<a href="login.php" style="color:#1231e3;font-weight:bold;">
العودة إلى تسجيل الدخول
</a>

</p>

</div>

</div>

</body>

</html>