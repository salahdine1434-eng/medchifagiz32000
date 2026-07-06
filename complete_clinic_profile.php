<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'clinic') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $clinic_name = trim($_POST['clinic_name']);
    $license_number = trim($_POST['license_number']);
    $wilaya = trim($_POST['wilaya']);
    $institution_type = $_POST['institution_type'] ?? 'clinic';
    $phone = trim($_POST['phone']);
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

$license_file = null;

if (!empty($_FILES['license_file']['name'])) {

    $uploadDir = "uploads/licenses/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES['license_file']['name']);
    $targetFile = $uploadDir . $fileName;

    move_uploaded_file($_FILES['license_file']['tmp_name'], $targetFile);

    $license_file = $targetFile;
}

  $stmt = $pdo->prepare("
    UPDATE clinic_profiles
    SET
    clinic_name = ?,
    license_number = ?,
    wilaya = ?,
    institution_type = ?,
    license_file = ?,
    lat = ?,
    lng = ?,
    is_profile_complete = 1
    WHERE user_id = ?
");

  $stmt->execute([
    $clinic_name,
    $license_number,
    $wilaya,
    $institution_type,
    $license_file,
    $lat,
    $lng,
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
   $_SESSION['success_message'] = "تم استلام معلوماتكم ووثائقكم، سيتم الرد عليكم بعد مراجعة الإدارة.";
header("Location: login.php");
exit;
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إكمال معلومات المؤسسة الصحية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container clinic-container">

    <div class="logo-area">
        <img src="medchifagz.png" alt="Logo">
    </div>

    <div class="card clinic-card">
        <h2>إكمال معلومات المؤسسة الصحية</h2>

       

        <form method="POST" enctype="multipart/form-data">

            <div class="input-group">
                <i class="fas fa-hospital icon"></i>
                <input type="text" name="clinic_name" placeholder="اسم المؤسسة الصحية" required>
            </div>

            <div class="input-group">
                <i class="fas fa-id-card icon"></i>
                <input type="text" name="license_number" placeholder="رقم الاعتماد" required>
            </div>

            <div class="input-group">
                <i class="fas fa-phone icon"></i>
                <input type="text" name="phone" placeholder="رقم الهاتف" required>
            </div>

            <div class="input-group">
                <i class="fas fa-map icon"></i>
                <input type="text" name="wilaya" placeholder="الولاية" required>
            </div>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">

            <div class="clinic-dual-row">
                <div class="clinic-half clinic-file-wrap">
                    <label class="clinic-file-label">
                        <i class="fas fa-file-upload"></i>
                        <span id="file-label-text">ملف الاعتماد</span>
                        <input type="file" name="license_file" id="license_file" required>
                    </label>
                </div>
                <div class="clinic-half">
                    <div class="input-group clinic-select-group">
                        <i class="fas fa-building icon"></i>
                        <select name="institution_type" required>
                            <option value="">نوع المؤسسة</option>
                            <option value="clinic">عيادة</option>
                            <option value="hospital">مستشفى</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="action-buttons clinic-actions">
                <button type="button" class="primary-btn clinic-location-btn" onclick="getLocation()">
                    <i class="fas fa-location-dot"></i> تحديد الموقع
                </button>
            </div>

            <div class="action-buttons clinic-actions">
                <button type="submit" class="primary-btn">
                    <i class="fas fa-save"></i> حفظ المعلومات
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
            document.getElementById("lat").value = position.coords.latitude;
            document.getElementById("lng").value = position.coords.longitude;
            alert("تم تحديد موقعك بنجاح ✅");
        }, function() {
            alert("ما قدرناش نحددو الموقع ❌");
        });
    } else {
        alert("المتصفح لا يدعم تحديد الموقع");
    }
}

document.getElementById('license_file').addEventListener('change', function () {
    const name = this.files[0] ? this.files[0].name : 'ملف الاعتماد';
    document.getElementById('file-label-text').textContent = name;
});
</script>
</body>
</html>