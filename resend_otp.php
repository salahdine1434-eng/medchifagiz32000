<?php
session_start();
require_once "db.php";
require_once "send_otp_email.php";

if(isset($_GET['email'])){

$email = $_GET['email'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user){

$otp = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,6);

$expire = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$stmt = $pdo->prepare("UPDATE users SET reset_otp=?, otp_expire=?, otp_attempts=0 WHERE email=?");
$stmt->execute([$otp,$expire,$email]);

sendOtpEmail($email,$otp);

header("Location: verify_reset_otp.php?email=".$email."&resend=1");
exit();

}

}
?>