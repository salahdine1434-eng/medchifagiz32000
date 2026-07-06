<?php
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ==========================
   استلام البيانات
========================== */

$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? '';
$reason = $_POST['reason'] ?? '';

/* ==========================
   جلب معلومات المستخدم
========================== */

$userStmt = $pdo->prepare("
    SELECT full_name, email
    FROM users
    WHERE id = ?
");

$userStmt->execute([$id]);

$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {

    ob_end_clean();

    echo json_encode([
        'success' => false,
        'message' => 'المستخدم غير موجود'
    ]);

    exit;
}

$userName  = $user['full_name'];
$userEmail = $user['email'];

/* ==========================
   دالة إرسال الإيميل
========================== */

function sendStatusEmail($to, $name, $status, $reason = '')
{
    $mail = new PHPMailer(true);

    try {

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nadjetkheira631@gmail.com';
        $mail->Password   = 'wrfyvaovuggirujg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, 'MedChifaGiz');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);

        /* ==========================
           قبول الطلب
        ========================== */

        if ($status === 'approved') {

            $mail->Subject = 'تم قبول طلب التسجيل';

            $mail->Body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>

    <h2 style='color:#2e7d32; margin-bottom:10px'>
        🎉 مرحباً بك في MedChifaGiz
    </h2>

    <p>
        أهلاً <strong>$name</strong>،
    </p>

    <p>
        يسعدنا إعلامك بأنه تم قبول طلب التسجيل الخاص بك في منصتنا الصحية الذكية.
    </p>

    <div style='
        display:inline-block;
        padding:12px 25px;
        font-size:22px;
        font-weight:bold;
        color:#2e7d32;
        background:#f1f8f4;
        border:2px dashed #2e7d32;
        border-radius:8px;
        margin:10px 0;
    '>
        ✓ تم تفعيل حسابك بنجاح
    </div>

    <p>
        يمكنك الآن تسجيل الدخول والاستفادة من جميع خدمات MedChifaGiz.
    </p>

    <hr style='margin:25px 0'>

    <div style='direction:ltr; text-align:left; font-size:17px'>

        <h3 style='color:#2e7d32; margin-bottom:10px'>
            Registration Approved 🎉
        </h3>

        <p>
            Dear $name,
        </p>

        <p>
            Your registration request has been approved successfully.
        </p>

        <p>
            You can now log in and access your account.
        </p>

    </div>

    <hr style='margin:25px 0'>

    <p style='font-size:13px; color:gray'>
        Thank you for choosing MedChifaGiz.
    </p>

    <p>
        💙 MedChifaGiz Team
    </p>

</div>
";
        }

        /* ==========================
           رفض الطلب
        ========================== */

        else {

            $mail->Subject = 'تم رفض طلب التسجيل';

            $mail->Body = "
<div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; line-height:1.8; color:#222; font-size:15px'>

    <h2 style='color:#d32f2f; margin-bottom:10px'>
        ⚠️ إشعار بخصوص طلب التسجيل
    </h2>

    <p>
        أهلاً <strong>$name</strong>،
    </p>

    <p>
        نأسف لإعلامك بأنه تم رفض طلب التسجيل الخاص بك في منصة MedChifaGiz.
    </p>

    <p>
        سبب الرفض:
    </p>
    <div style='
        display:inline-block;
        padding:12px 25px;
        font-size:18px;
        font-weight:bold;
        color:#d32f2f;
        background:#fff5f5;
        border:2px dashed #d32f2f;
        border-radius:8px;
        margin:10px 0;
    '>
        $reason
    </div>

    <p>
        يمكنك إعادة تقديم الطلب بعد تصحيح المشكلة المذكورة أعلاه.
    </p>

    <hr style='margin:25px 0'>

    <div style='direction:ltr; text-align:left; font-size:17px'>

        <h3 style='color:#d32f2f; margin-bottom:10px'>
            Registration Rejected ⚠️
        </h3>

        <p>
            Dear $name,
        </p>

        <p>
            Unfortunately, your registration request has been rejected.
        </p>

        <p>
            Reason: $reason
        </p>

    </div>

    <hr style='margin:25px 0'>

    <p style='font-size:13px; color:gray'>
        If you believe this is an error, please contact us.
    </p>

    <p>
        💙 MedChifaGiz Team
    </p>

</div>
";
        }

        $mail->send();

    } catch (Exception $e) {
        // تجاهل الخطأ حتى لا يتوقف النظام
    }
}

/* ==========================
   قبول الطلب
========================== */

if ($status === 'approved') {

    $stmt = $pdo->prepare("
        UPDATE users
        SET status = 'approved'
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    sendStatusEmail(
        $userEmail,
        $userName,
        'approved'
    );
}

/* ==========================
   رفض الطلب
========================== */

elseif ($status === 'rejected') {

    $stmt = $pdo->prepare("
        UPDATE users
        SET status = 'rejected',
            rejection_reason = ?
        WHERE id = ?
    ");

    $stmt->execute([$reason, $id]);

    sendStatusEmail(
        $userEmail,
        $userName,
        'rejected',
        $reason
    );
}

/* ==========================
   النتيجة النهائية
========================== */

ob_end_clean();

echo json_encode([
    'success' => true
]);