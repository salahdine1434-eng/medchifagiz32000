<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $license = trim($_POST['license_number']);
    $specialty = trim($_POST['specialty']);
    $workplace = trim($_POST['workplace']);
    $phone = trim($_POST['phone']);
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    $licenseFileName = null;

    if(isset($_FILES['license_file']) && $_FILES['license_file']['error'] == 0){

        if(!is_dir('uploads/licenses')){
            mkdir('uploads/licenses', 0777, true);
        }

        $extension = pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION);

        $licenseFileName =
            'uploads/licenses/' .
            uniqid('license_') .
            '.' .
            $extension;

        move_uploaded_file(
            $_FILES['license_file']['tmp_name'],
            $licenseFileName
        );
    }

    $stmt = $pdo->prepare("
        UPDATE doctors
        SET license_number = ?,
            specialty = ?,
            workplace = ?,
            lat = ?,
            lng = ?,
            license_file = ?,
            is_profile_complete = 1
        WHERE user_id = ?
    ");

    $stmt->execute([
        $license,
        $specialty,
        $workplace,
        $lat,
        $lng,
        $licenseFileName,
        $_SESSION['user_id']
    ]);
    $updatePhone = $pdo->prepare("
UPDATE users
SET phone = ?
WHERE id = ?
");

$updatePhone->execute([
    $phone,
    $_SESSION['user_id']
]);
$updateUser = $pdo->prepare("
 UPDATE users
SET status='pending',
    profile_completed=1
WHERE id=?
");

$updateUser->execute([$_SESSION['user_id']]);


$_SESSION['success_message'] = true;
$_SESSION['success_message'] = "تم إرسال طلب المؤسسة الصحية بنجاح، يرجى انتظار موافقة الإدارة.";
header("Location: login.php");
exit;

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إكمال معلومات الطبيب</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <div class="logo-area">
        <img src="medchifagz.png" alt="Logo">
    </div>

    <div class="card">
        <h2>إكمال معلومات الطبيب</h2>

        <p class="verify-text">
            من فضلك كمل معلوماتك باش تقدر تدخل للوحة التحكم
        </p>
<?php if(isset($_GET['pending'])){ ?>
<div id="pendingMessage" class="pending-message">
    ✅ تم استلام طلبكم بنجاح، وسيتم الرد عليكم بعد المراجعة.
</div>

<script>
setTimeout(function () {
    let msg = document.getElementById("pendingMessage");

    msg.style.opacity = "0";

    setTimeout(function(){
        msg.style.display = "none";
    },1000);

},5000);
</script>
<?php } ?>
        <form method="POST" enctype="multipart/form-data">

            <div class="input-group">
                <i class="fas fa-id-card icon"></i>
                <input type="text" name="license_number" placeholder="رقم التسجيل المهني" required>
            </div>

            <div class="input-group">
                <i class="fas fa-user-doctor icon"></i>
                <input type="text" name="specialty" placeholder="التخصص" required>
            </div>

            <div class="input-group">
                <i class="fas fa-hospital icon"></i>
                <input type="text" name="workplace" placeholder="مكان العمل" required>
            </div>
<div class="input-group">
    <i class="fas fa-phone icon"></i>
    <input type="text" name="phone" placeholder="رقم الهاتف" required>
</div>
            <div class="input-group">
                <label>📎 رفع الرخصة المهنية</label>
                <input type="file"
                       name="license_file"
                       accept=".pdf,.jpg,.jpeg,.png"
                       required>
            </div>

            <button type="button" onclick="getLocation()">
                📍 تحديد موقعي
            </button>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">

            <div class="action-buttons">
                <button type="submit" class="primary-btn">
                    حفظ المعلومات
                </button>
            </div>

        </form>
    </div>

</div>

<script src="script.js"></script>

<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {

            document.getElementById("lat").value =
                position.coords.latitude;

            document.getElementById("lng").value =
                position.coords.longitude;

            alert("تم تحديد موقعك بنجاح ✅");

        }, function() {

            alert("ما قدرناش نحددو الموقع ❌");

        });
    } else {
        alert("المتصفح ما يدعمش تحديد الموقع");
    }
}
</script>

</body>
</html>