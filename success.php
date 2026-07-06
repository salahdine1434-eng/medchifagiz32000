<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تم التسجيل</title>
<link rel="stylesheet" href="style.css">
<style>
body{
    margin:0;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(180deg, #6ff36b 0%, #4a55ec 100%) no-repeat fixed;
    font-family:Arial;
}

.success-card{
    background:rgba(255, 255, 255, 0.79);;
    padding:40px;
    border-radius:20px;
    text-align:center;
    width:350px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
}

.success-card h2{
    margin-bottom:20px;
}

.success-btn{
    display:block;
    margin-top:20px;
    padding:12px;
    background:linear-gradient(135deg,#4CAF50,#2196F3);
    color:white;
    text-decoration:none;
    border-radius:10px;
    font-size:18px;
}
</style>
</head>
<body>

<div class="success-card">
    <h2>🎉 تم التسجيل بنجاح</h2>
    <p>يمكنك الآن تسجيل الدخول إلى حسابك</p>
    <a href="login.php" class="success-btn">الذهاب إلى تسجيل الدخول</a>
</div>

</body>
</html>