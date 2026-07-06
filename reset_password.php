<?php
session_start();
require_once "db.php";

$success=false;

if($_SERVER["REQUEST_METHOD"]=="POST"){

$email=$_POST['email'];
$password=$_POST['password'];

if(!empty($password)){

$hash=password_hash($password,PASSWORD_DEFAULT);

$stmt=$pdo->prepare("UPDATE users SET password_hash=?, reset_otp=NULL, otp_expire=NULL, otp_attempts=0 WHERE email=?");
$stmt->execute([$hash,$email]);

$success=true;

}

}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>تغيير كلمة السر</title>

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

<h2>تغيير كلمة السر</h2>

<?php if($success): ?>

<p style="color:green;text-align:center;font-weight:bold;">
تم تغيير كلمة السر بنجاح
</p>

<div class="action-buttons">
<a href="login.php" class="primary-btn" style="text-decoration:none;display:block;text-align:center;">
العودة إلى تسجيل الدخول
</a>
</div>

<?php else: ?>

<form method="POST">

<input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">

<div class="input-group">
<i class="fas fa-lock icon"></i>
<input type="password" name="password" placeholder="كلمة السر الجديدة" required>
</div>

<div class="action-buttons">
<button type="submit" class="primary-btn">
تغيير كلمة السر
</button>
</div>

</form>

<?php endif; ?>

</div>

</div>

</body>
</html>