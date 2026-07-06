<?php
session_start();
require_once "db.php";
require_once "lib/GoogleAuthenticator.php";

$ga = new GoogleAuthenticator();
$message = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = $_POST['email'];
    $code = str_replace(' ', '', $_POST['code']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        $message = "المستخدم غير موجود";
    }else{

        $secret = $user['twofa_secret'];
        $checkResult = $ga->verifyCode($secret, $code, 2);

        if($checkResult){

            $stmt = $pdo->prepare("UPDATE users SET twofa_enabled=1 WHERE email=?");
            $stmt->execute([$email]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == "doctor"){
                header("Location: dr_dashboard.php");
            }else{
                header("Location: patient_dashboard.html");
            }
            exit();

        }else{
            $message = "رمز غير صحيح ❌";
        }
    }
}else{
    header("Location:success.php");
    exit();
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
        <img src="medchifagz.png" alt="Logo">
    </div>

    <div class="card login-card show">

        <h2>التحقق بخطوتين</h2>

        <p style="color:red;text-align:center;">
            <?php echo $message; ?>
        </p>

        <div class="action-buttons">
            <a href="setup_2fa.php?email=<?php echo urlencode($email); ?>" class="primary-btn">
                العودة وإعادة المحاولة
            </a>
        </div>

    </div>

</div>

</body>
</html>