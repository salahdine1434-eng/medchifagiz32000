<?php

function sendViaTurboSMTP($toEmail, $subject, $htmlContent) {
    $data = [
        "from"         => "hinanadjet@gmail.com",
        "to"           => $toEmail,
        "subject"      => $subject,
        "html_content" => $htmlContent
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.turbo-smtp.com/api/v2/mail/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Consumerkey: ' . getenv('TURBOSMTP_KEY'),
        'Consumersecret: ' . getenv('TURBOSMTP_SECRET'),
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("TurboSMTP cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("TurboSMTP Error ($httpCode): " . $result);
        return false;
    }

    return true;
}

function sendOtpEmail($toEmail, $otp) {

    $body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>
    <h2 style='color:#2e7d32; margin-bottom:10px'>👋 مرحباً بك في MedChifaGiz</h2>
    <p>نحن سعداء بانضمامك إلى منصتنا الصحية الذكية</p>
    <p>رمز التحقق الخاص بك:</p>
    <div style='display:inline-block; padding:12px 25px; font-size:26px; font-weight:bold; color:#2e7d32; background:#f1f8f4; border:2px dashed #2e7d32; border-radius:8px; letter-spacing:3px; margin:10px 0;'>
        $otp
    </div>
    <p>يرجى إدخال هذا الرمز لتفعيل حسابك.</p>
    <hr style='margin:25px 0'>
    <div style='direction:ltr; text-align:left; font-size:17px'>
        <h3 style='color:#2e7d32; margin-bottom:10px'>Welcome to MedChifaGiz 👋</h3>
        <p>We are happy to have you on our smart healthcare platform.</p>
        <p>Your verification code is:</p>
        <div style='display:inline-block; padding:10px 20px; font-size:24px; font-weight:bold; color:#2e7d32; background:#f1f8f4; border:2px dashed #2e7d32; border-radius:8px; letter-spacing:3px; margin:10px 0;'>
            $otp
        </div>
        <p>Please enter this code to activate your account.</p>
    </div>
    <hr style='margin:25px 0'>
    <p style='font-size:13px; color:gray'>
        إذا لم تقم بطلب هذا، يرجى تجاهل هذه الرسالة / If you did not request this, please ignore this email.
    </p>
    <p>💙 MedChifaGiz Team</p>
</div>
";

    return sendViaTurboSMTP($toEmail, 'رمز التحقق - MedChifaGiz', $body);
}

function sendInvitationEmail($toEmail, $doctorName = 'طبيبك') {

    $registerLink = "http://localhost/fix7/index.html?invite=1&email=" . urlencode($toEmail);

    $body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>
    <h2 style='color:#2e7d32; margin-bottom:10px'>🩺 دعوة للانضمام إلى MedChifaGiz</h2>
    <p>أهلاً بك 👋</p>
    <p>قام الطبيب <b style='color:#2e7d32;'>$doctorName</b> بإضافتك إلى منصة <b>MedChifaGiz</b> لمتابعة حالتك الصحية.</p>
    <div style='margin:30px 0;text-align:center;'>
        <a href='$registerLink' style='background:#2e7d32; color:white; text-decoration:none; padding:14px 35px; border-radius:8px; font-size:18px; font-weight:bold; display:inline-block;'>
            إنشاء حساب
        </a>
    </div>
</div>
";

    return sendViaTurboSMTP($toEmail, "دعوة للانضمام إلى MedChifaGiz", $body);
}
