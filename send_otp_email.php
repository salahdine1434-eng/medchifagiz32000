<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// استدعاء مكتبة PHPMailer
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

function sendOtpEmail($toEmail, $otp) {

    $mail = new PHPMailer(true);

    try {
        // إعدادات الترميز
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // إعداد SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
       

        $mail->Host       = gethostbyname('smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hinanadjet@gmail.com';
        $mail->Password   = 'otpynwremqnnruhd'; // ⚠️ حاول تخبيها لاحقاً
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 465;

        // Important si tu remplaces le Host par une IP : Gmail vérifie le certificat SSL,
// donc il faut spécifier le nom d'origine pour la vérification TLS :
$mail->SMTPOptions = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ]
];

        // المرسل والمستقبل
        $mail->setFrom($mail->Username, 'MedChifaGiz');
        $mail->addAddress($toEmail);

       
        // إعداد الإيميل
        $mail->isHTML(true);
        $mail->Subject = 'رمز التحقق - MedChifaGiz';

        // محتوى الرسالة (تصميم احترافي)
        $mail->Body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>

    <h2 style='color:#2e7d32; margin-bottom:10px'>
        👋 مرحباً بك في MedChifaGiz
    </h2>

    <p>
        نحن سعداء بانضمامك إلى منصتنا الصحية الذكية 
    </p>

    <p>
        رمز التحقق الخاص بك:
    </p>

    <!-- OTP BOX -->
    <div style='
        display:inline-block;
        padding:12px 25px;
        font-size:26px;
        font-weight:bold;
        color:#2e7d32;
        background:#f1f8f4;
        border:2px dashed #2e7d32;
        border-radius:8px;
        letter-spacing:3px;
        margin:10px 0;
    '>
        $otp
    </div>

    <p>
        يرجى إدخال هذا الرمز لتفعيل حسابك.
    </p>

    <hr style='margin:25px 0'>

    <!-- ENGLISH VERSION -->
    <div style='direction:ltr; text-align:left; font-size:17px'>

        <h3 style='color:#2e7d32; margin-bottom:10px'>
            Welcome to MedChifaGiz 👋
        </h3>

        <p>
            We are happy to have you on our smart healthcare platform.
        </p>

        <p>
            Your verification code is:
        </p>

        <div style='
            display:inline-block;
            padding:10px 20px;
            font-size:24px;
            font-weight:bold;
            color:#2e7d32;
            background:#f1f8f4;
            border:2px dashed #2e7d32;
            border-radius:8px;
            letter-spacing:3px;
            margin:10px 0;
        '>
            $otp
        </div>

        <p>
            Please enter this code to activate your account.
        </p>

    </div>

    <hr style='margin:25px 0'>

   <p style='font-size:13px; color:gray'>
        إذا لم تقم بطلب هذا، يرجى تجاهل هذه الرسالة / If you did not request this, please ignore this email.
    </p>

    <p>
        💙 MedChifaGiz Team
    </p>

</div>
";

        // 🚀 إرسال مرة واحدة فقط
        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        }

        return true;

    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
function sendInvitationEmail($toEmail, $doctorName = 'طبيبك') {

    $mail = new PHPMailer(true);

    try {

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isSMTP();
        $mail->SMTPDebug = 0;

        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hinanadjet@gmail.com';
        $mail->Password   = 'otpynwremqnnruhd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, 'MedChifaGiz');
        $mail->addAddress($toEmail);

       $registerLink = "http://localhost/fix7/index.html?invite=1&email=" . urlencode($toEmail);

        $mail->isHTML(true);
        $mail->Subject = "دعوة للانضمام إلى MedChifaGiz";

       $mail->Body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>

    <h2 style='color:#2e7d32; margin-bottom:10px'>
        🩺 دعوة للانضمام إلى MedChifaGiz
    </h2>

    <p>
        أهلاً بك 👋
    </p>

    <p>
        قام الطبيب <b style='color:#2e7d32;'>$doctorName</b>
        بإضافتك إلى منصة <b>MedChifaGiz</b> لمتابعة حالتك الصحية.
    </p>

    <p>
        بعد إنشاء حسابك ستتمكن من:
    </p>

    <ul style='padding-right:18px'>
        <li>💬 التواصل المباشر مع طبيبك.</li>
        <li>📁 الاطلاع على ملفك الطبي.</li>
        <li>🧪 مشاهدة نتائج التحاليل.</li>
        <li>🩻 متابعة صور الأشعة.</li>
        <li>📅 متابعة المواعيد الطبية.</li>
        <li>📨 استقبال رسائل الطبيب والإشعارات.</li>
    </ul>

    <div style='margin:30px 0;text-align:center;'>

        <a href='$registerLink'
        style='
            background:#2e7d32;
            color:white;
            text-decoration:none;
            padding:14px 35px;
            border-radius:8px;
            font-size:18px;
            font-weight:bold;
            display:inline-block;
        '>
            إنشاء حساب
        </a>

    </div>

    <hr style='margin:25px 0'>

    <div style='direction:ltr;text-align:left;font-size:16px'>

        <h3 style='color:#2e7d32'>
            Welcome to MedChifaGiz 👋
        </h3>

        <p>
            Dr. <b>$doctorName</b> invited you to join MedChifaGiz.
        </p>

        <p>
            Create your account to:
        </p>

        <ul>
            <li>Chat with your doctor.</li>
            <li>Access your medical record.</li>
            <li>View lab results.</li>
            <li>View radiology reports.</li>
            <li>Manage appointments.</li>
        </ul>

        <p style='text-align:center;margin-top:25px;'>

            <a href='$registerLink'
            style='
                background:#2e7d32;
                color:#fff;
                text-decoration:none;
                padding:12px 30px;
                border-radius:8px;
                display:inline-block;
                font-weight:bold;
            '>
                Create Account
            </a>

        </p>

    </div>

    <hr style='margin:25px 0'>

    <p style='font-size:13px;color:gray'>
        هذه رسالة آلية من MedChifaGiz، إذا كنت تعتقد أنها وصلتك بالخطأ يمكنك تجاهلها.
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
