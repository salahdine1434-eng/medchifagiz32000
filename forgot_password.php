<?php
session_start();
require_once "db.php";

$message = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

$email = trim($_POST['email']);

if(!empty($email)){

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user){

// إنشاء رمز التحقق
$otp = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,6);

// إرسال الكود إلى الإيميل
require_once "send_otp_email.php";
sendOtpEmail($email,$otp);

// تحديد انتهاء الكود بعد 10 دقائق
$expire = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// حفظ الكود في قاعدة البيانات
$stmt = $pdo->prepare("UPDATE users SET reset_otp=?, otp_expire=?, otp_attempts=0 WHERE email=?");
$stmt->execute([$otp,$expire,$email]);

// الانتقال مباشرة لصفحة إدخال الكود
header("Location: verify_reset_otp.php?email=".$email);
exit();

}else{

$message = "هذا البريد غير موجود";

}

}else{

$message = "يرجى إدخال البريد الإلكتروني";

}

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>استرجاع كلمة السر</title>

<link rel="stylesheet" href="style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

<div class="container">

<div class="logo-area">
<img src="medchifagz.png" alt="Logo">
</div>

<div class="card card show">

<h2>استرجاع كلمة السر</h2>

<?php if(!empty($message)): ?>
<p style="color:red;text-align:center;"><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST">

<div class="input-group">
<i class="fas fa-envelope icon"></i>
<input type="email" name="email" placeholder="البريد الإلكتروني" required>
</div>

<div class="action-buttons">
<button type="submit" class="primary-btn">
إرسال رمز التحقق
</button>
</div>

</form>

<p class="forgot-pass">
<a href="login.php">العودة إلى تسجيل الدخول</a>
</p>

</div>

</div>

</body>
</html>