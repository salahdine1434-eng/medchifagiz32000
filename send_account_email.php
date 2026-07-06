<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

function sendAccountEmail($toEmail, $fullName, $password, $role)
{
    $mail = new PHPMailer(true);

    try {

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nadjetkheira631@gmail.com';
        $mail->Password   = 'ppikzfqeauzihjjr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, 'MedChifaGiz');
        $mail->addAddress($toEmail);

       
        $mail->isHTML(true);
        $mail->Subject = 'تم إنشاء حسابك - MedChifaGiz';

        $mail->Body = "

<div style='font-family:Tahoma,Arial,sans-serif;direction:rtl;line-height:1.8;color:#222'>

    <h2 style='color:#2e7d32'>
        🎉 تم إنشاء حسابك بنجاح
    </h2>

    <p>
        مرحباً <strong>{$fullName}</strong>
    </p>

    <p>
        تم إنشاء حسابك على منصة MedChifaGiz.
    </p>

    <div style='
        background:#f5f7fa;
        border:1px solid #dfe6ee;
        border-radius:10px;
        padding:15px;
        margin:15px 0;
    '>

        <p><strong>الوظيفة:</strong> {$role}</p>

        <p><strong>البريد الإلكتروني:</strong> {$toEmail}</p>

        <p><strong>كلمة المرور:</strong></p>

        <div style='
            display:inline-block;
            padding:12px 25px;
            background:#f1f8f4;
            border:2px dashed #2e7d32;
            border-radius:8px;
            font-size:22px;
            font-weight:bold;
            color:#2e7d32;
            letter-spacing:2px;
        '>
            {$password}
        </div>

    </div>

    <p>
        يمكنك الآن تسجيل الدخول باستعمال هذه المعلومات.
    </p>

    <hr style='margin:25px 0'>

    <div style='direction:ltr;text-align:left'>

        <h3 style='color:#2e7d32'>
            Your Account Has Been Created
        </h3>

        <p><strong>Name:</strong> {$fullName}</p>
        <p><strong>Role:</strong> {$role}</p>
        <p><strong>Email:</strong> {$toEmail}</p>
        <p><strong>Password:</strong> {$password}</p>

        <p>
            You can now log in using these credentials.
        </p>

    </div>

    <hr style='margin:25px 0'>

    <p style='font-size:13px;color:gray'>
        يرجى تغيير كلمة المرور بعد أول تسجيل دخول.
    </p>

    <p>
        💙 MedChifaGiz Team
    </p>

</div>

";

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}