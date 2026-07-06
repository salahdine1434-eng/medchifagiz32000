<?php
session_start();
require_once "db.php";

$message="";

if($_SERVER["REQUEST_METHOD"]=="POST"){

$email=$_POST['email'];
$otp=$_POST['otp'];

$stmt=$pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);

$user=$stmt->fetch(PDO::FETCH_ASSOC);

if($user){

if($user['otp_attempts']>=3){
$message="تم تجاوز عدد المحاولات المسموح";
}

else{

$current_time=date("Y-m-d H:i:s");

if($current_time > $user['otp_expire']){
$message="انتهت صلاحية الكود";
}

elseif($otp==$user['reset_otp']){

$_SESSION['reset_email']=$email;

header("Location: reset_password.php?email=".$email);
exit();

}

else{

$attempts=$user['otp_attempts']+1;

$stmt=$pdo->prepare("UPDATE users SET otp_attempts=? WHERE email=?");
$stmt->execute([$attempts,$email]);

$message="الكود غير صحيح";

}

}

}else{

$message="هذا البريد غير موجود";

}

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>التحقق من الكود</title>

<link rel="stylesheet" href="style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

<div class="container">

<div class="logo-area">
<img src="medchifagz.png">
</div>

<div class="card card show">

<h2>إدخال رمز التحقق</h2>

<?php if(isset($_GET['resend'])): ?>
<p style="color:green;text-align:center;">
تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني
</p>
<?php endif; ?>
<?php if(!empty($message) && !isset($_GET['resend'])): ?>
<p style="color:red;text-align:center;"><?php echo $message; ?></p>
<?php endif; ?>

<p id="timer" style="text-align:center;color:#333;font-weight:bold;"></p>
<div style="text-align:center;margin-top:10px;">
<a id="resendBtn" href="resend_otp.php?email=<?php echo $email; ?>" class="primary-btn">
إعادة إرسال الرمز
</a>
</div>

<form method="POST">

<input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">

<?php if(isset($user['otp_attempts']) && $user['otp_attempts']>=3): ?>



<?php else: ?>

<div class="input-group">
<i class="fas fa-key icon"></i>
<input type="text" name="otp" placeholder="رمز التحقق" maxlength="6" pattern="[A-Za-z0-9]{6}" required>
</div>

<div class="action-buttons">
<button type="submit" class="primary-btn">
تأكيد الكود
</button>
</div>

<?php endif; ?>

</form>

</div>

</div>
<script>

let resendBtn = document.getElementById("resendBtn");
let timer = document.getElementById("timer");

<?php if(isset($user['otp_attempts']) && $user['otp_attempts'] >= 3): ?>

let time = 600;

resendBtn.style.pointerEvents = "none";
resendBtn.style.opacity = "0.5";

let countdown = setInterval(function(){

let minutes = Math.floor(time / 60);
let seconds = time % 60;

if(seconds < 10){
seconds = "0" + seconds;
}

timer.innerHTML = "انتظر " + minutes + ":" + seconds;

time--;

if(time < 0){

clearInterval(countdown);

timer.innerHTML = "يمكنك الآن إعادة إرسال الرمز";

resendBtn.style.pointerEvents = "auto";
resendBtn.style.opacity = "1";

}

},1000);

<?php else: ?>

timer.innerHTML = "";
resendBtn.style.display = "none";

<?php endif; ?>

</script>
</body>
</html>