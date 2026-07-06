<?php
session_start();
require 'send_otp_email.php';
if (!isset($_SESSION['register'])) {
    header("Location: register_step1.php");
    exit;
}

// توليد OTP مرة وحدة فقط
unset($_SESSION['otp']);
if (!isset($_SESSION['otp'])) {
    $_SESSION['otp'] = rand(100000, 999999);
    $email = $_SESSION['register']['email'];
    sendOtpEmail($email, $_SESSION['otp']);
}

$email = $_SESSION['register']['email'];



$otp = $_SESSION['otp'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الهوية</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Toast Notification -->


<div class="container">
    <div class="logo-area">
        <img src="medchifagz.png" alt="Logo">
    </div>

<div class="card step3-card">
    <h2>تأكيد الهوية</h2>

    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" style="width:100%;"></div>
        </div>
        <p class="step-text">الخطوة 3 من 3</p>
    </div>

    <form method="POST" action="verify_otp.php">

       <p class="verify-text">لقد أرسلنا رمز تحقق إلى بريدك الإلكتروني</p>

        <div class="input-group">
            <input type="text" 
                   name="otp" 
                   maxlength="6" 
                   placeholder="أدخل رمز التحقق" 
                   required>
        </div>

        <div class="action-buttons">
            <button type="submit" class="primary-btn">تأكيد</button>
            <button type="button" class="secondary-btn">إعادة إرسال الرمز</button>
        </div>

    </form>
</div>

<script src="script.js"></script>

<script>
window.addEventListener("load", function() {
    const toast = document.getElementById("toast");

    // يظهر بعد 2 ثواني
    setTimeout(() => {
        toast.classList.add("show");
    }, 2000);

    // يختفي بعد 5 ثواني
    setTimeout(() => {
        toast.classList.remove("show");
    }, 7000);
});
</script>

</body>
</html>