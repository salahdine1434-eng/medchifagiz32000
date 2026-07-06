<?php
require 'db.php';

if (!isset($_GET['token'])) {
    die("Emergency token missing");
}

$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT * FROM patients WHERE emergency_token = ?");
$stmt->execute([$token]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>بطاقة طبية طارئة</title>
<style>
body{
    margin:0;
    font-family:Arial;
    background:#f4f8fb;
}
.wrap{
    max-width:900px;
    margin:30px auto;
    background:#fff;
    border-radius:25px;
    box-shadow:0 20px 50px rgba(0,0,0,.12);
    overflow:hidden;
}
.head{
    background:linear-gradient(135deg,#06d6a0,#00b4d8);
    color:white;
    padding:30px;
    text-align:center;
}
.head h1{
    margin:0;
    font-size:32px;
}
.content{
    padding:30px;
}
.box{
    background:#f8fbff;
    border:1px solid #dde8f3;
    border-radius:18px;
    padding:18px;
    margin-bottom:15px;
}
.title{
    color:#00b4d8;
    font-weight:bold;
    margin-bottom:8px;
}
.value{
    font-size:18px;
    color:#222;
}
.blood{
    background:#dc2626;
    color:white;
    display:inline-block;
    padding:10px 22px;
    border-radius:999px;
    font-weight:bold;
    font-size:22px;
}
</style>
</head>
<body>

<div class="wrap">
    <div class="head">
        <h1>🚑 الملف الطبي الطارئ</h1>
        <p>MedChifaGiz Emergency Access</p>
    </div>

    <div class="content">

        <div class="box">
            <div class="title">الاسم الكامل</div>
            <div class="value">
                <?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']) ?>
            </div>
        </div>

        <div class="box">
            <div class="title">رقم المريض</div>
            <div class="value">
                MED-<?= $patient['user_id'] ?>
            </div>
        </div>

        <div class="box">
            <div class="title">تاريخ الميلاد</div>
            <div class="value">
                <?= htmlspecialchars($patient['birth_date']) ?>
            </div>
        </div>

        <div class="box">
            <div class="title">زمرة الدم</div>
            <div class="blood">
                <?= htmlspecialchars($patient['blood_type']) ?>
            </div>
        </div>

        <div class="box">
            <div class="title">الأمراض المزمنة</div>
            <div class="value">
                <?= htmlspecialchars($patient['chronic_diseases'] ?: 'لا يوجد') ?>
            </div>
        </div>

        <div class="box">
            <div class="title">الحساسية</div>
            <div class="value">
                <?= htmlspecialchars($patient['allergies'] ?: 'لا يوجد') ?>
            </div>
        </div>

        <div class="box">
            <div class="title">الأدوية الحالية</div>
            <div class="value">
                <?= htmlspecialchars($patient['medications'] ?: 'لا يوجد') ?>
            </div>
        </div>

        <div class="box">
            <div class="title">ملاحظات صحية</div>
            <div class="value">
                <?= htmlspecialchars($patient['health_notes'] ?: 'لا يوجد') ?>
            </div>
        </div>

        <div class="box">
            <div class="title">رقم الطوارئ</div>
            <div class="value">
                <?= htmlspecialchars($patient['emergency_phone'] ?: 'لا يوجد') ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>