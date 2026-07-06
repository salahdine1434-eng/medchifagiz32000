<?php
session_start();

if (!isset($_SESSION['register'])) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختيار نوع المستخدم</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">
    <div class="logo-area">
        <img src="medchifagz.png" alt="Logo">
    </div>

    <div class="card">
        <h2>اختيار نوع المستخدم</h2>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 66%;"></div>
            </div>
            <span class="step-text">الخطوة 2 من 3</span>
        </div>

        <form action="register_step2.php" method="POST">

            <!-- hidden input -->
            <input type="hidden" name="role" id="roleInput">

            <div class="user-type-grid">

                <div class="user-option" data-role="doctor">
    <div class="icon-circle"><i class="fas fa-user-md"></i></div>
    <span>طبيب</span>
</div>

<div class="user-option" data-role="patient">
    <div class="icon-circle"><i class="fas fa-user-injured"></i></div>
    <span>مريض</span>
</div>

<div class="user-option" data-role="pharmacy">
    <div class="icon-circle"><i class="fas fa-pills"></i></div>
    <span>صيدلية</span>
</div>

<div class="user-option" data-role="lab">
    <div class="icon-circle"><i class="fas fa-flask"></i></div>
    <span>مخبر</span>
</div>

<div class="user-option" data-role="clinic">
    <div class="icon-circle"><i class="fas fa-hospital"></i></div>
    <span>مؤسسة صحية</span>
</div>

            </div>

            <div class="footer-action">
                <button class="next-btn" type="submit">التالي</button>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const options = document.querySelectorAll(".user-option");
    const roleInput = document.getElementById("roleInput");

    options.forEach(option => {
        option.addEventListener("click", function () {

            // نحيو التحديد من الجميع
            options.forEach(o => o.classList.remove("selected"));

            // نضيف selected
            this.classList.add("selected");

            // نخزنو الدور
            roleInput.value = this.dataset.role;

            console.log("Selected role:", roleInput.value);
        });
    });

});
</script>

<script src="script.js"></script>
</body>
</html>