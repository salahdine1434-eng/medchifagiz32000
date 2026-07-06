<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

function sendResetPasswordEmail($toEmail, $fullName, $newPassword)
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
        $mail->Subject = 'إعادة تعيين كلمة المرور - MedChifaGiz';

        $mail->Body = "

<div style='font-family:Tahoma,Arial,sans-serif;direction:rtl;line-height:1.8;color:#222'>

    <h2 style='color:#e67e22'>
        🔐 تم إعادة تعيين كلمة المرور
    </h2>

    <p>
        مرحباً <strong>{$fullName}</strong>
    </p>

    <p>
        تم إنشاء كلمة مرور جديدة لحسابك على منصة MedChifaGiz.
    </p>

    <div style='
        background:#f5f7fa;
        border:1px solid #dfe6ee;
        border-radius:10px;
        padding:15px;
        margin:15px 0;
    '>

        <p><strong>البريد الإلكتروني:</strong> {$toEmail}</p>

        <p><strong>كلمة المرور الجديدة:</strong></p>

        <div style='
            display:inline-block;
            padding:12px 25px;
            background:#fff8e1;
            border:2px dashed #e67e22;
            border-radius:8px;
            font-size:22px;
            font-weight:bold;
            color:#e67e22;
            letter-spacing:2px;
        '>
            {$newPassword}
        </div>

    </div>

    <p>
        تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول باستعمال كلمة المرور الجديدة.
    </p>

    <hr style='margin:25px 0'>

    <div style='direction:ltr;text-align:left'>

        <h3 style='color:#e67e22'>
            Password Reset Successful
        </h3>

        <p><strong>Name:</strong> {$fullName}</p>
        <p><strong>Email:</strong> {$toEmail}</p>

        <p><strong>New Password:</strong></p>

        <div style='
            display:inline-block;
            padding:10px 20px;
            background:#fff8e1;
            border:2px dashed #e67e22;
            border-radius:8px;
            font-size:20px;
            font-weight:bold;
            color:#e67e22;
        '>
            {$newPassword}
        </div>

        <p style='margin-top:15px'>
            Your password has been reset successfully.
            You can now log in using your new password.
        </p>

    </div>

    <hr style='margin:25px 0'>

    <p style='font-size:13px;color:gray'>
        إذا لم تطلب إعادة تعيين كلمة المرور، يرجى التواصل مع إدارة النظام فوراً.
    </p>

    <p style='font-size:13px;color:gray'>
        If you did not request a password reset, please contact the administrator immediately.
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