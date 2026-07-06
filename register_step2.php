<?php
session_start();

if (!isset($_SESSION['register'])) {
    die("الرجاء إتمام الخطوة الأولى");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role = $_POST['role'] ?? '';

    if ($role === '') {
        die("الرجاء اختيار نوع المستخدم");
    }

    $_SESSION['register']['role'] = $role;

    header("Location: step3.php");
    exit;
}
?>