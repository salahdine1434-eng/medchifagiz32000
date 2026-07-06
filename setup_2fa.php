<?php
session_start();
require_once "db.php";
require_once "lib/GoogleAuthenticator.php";

$ga = new GoogleAuthenticator();

$email = $_GET['email'] ?? '';

if(empty($email)){
    die("Email غير موجود");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    die("المستخدم غير موجود");
}

if(empty($user['twofa_secret'])){

    $secret = $ga->createSecret();

    $stmt = $pdo->prepare("UPDATE users SET twofa_secret=? WHERE email=?");
    $stmt->execute([$secret,$email]);

}else{
    $secret = $user['twofa_secret'];
}

$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/MedChifaGiz:$email?secret=$secret&issuer=MedChifaGiz";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>تفعيل التحقق بخطوتين</title>

<link rel="stylesheet" href="style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

/* QR code */
.qr-code{
width:150px;
max-width:90%;
height:auto;
margin:15px auto;
display:block;
}

/* تحسين الكارد */
@media (max-width:600px){

.card{
width:80% !important;
padding:20px;
}

.qr-code{
width:85px;
}

.primary-btn{
width:70%;
}

}

</style>

</head>

<body>

<div class="container">

<div class="logo-area">
<img src="medchifagz.png" alt="Logo">
</div>

<div class="card login-card show">

<h2>تفعيل التحقق بخطوتين</h2>

<p style="text-align:center;color:#555;">
امسح الكود بتطبيق Google Authenticator
</p>

<img src="<?php echo $qrCodeUrl; ?>" class="qr-code">

<form action="verify_2fa.php" method="POST">

<input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

<div class="input-group">

<i class="fas fa-key icon"></i>

<input
type="text"
name="code"
placeholder="رمز التحقق"
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
<div style="text-align:center;margin-top:10px;">

<a href="skip_2fa.php?email=<?php echo $email; ?>" class="skip-btn">
ليس الآن
</a>

</div>
</div>

</form>

</div>

</div>

</body>

</html>