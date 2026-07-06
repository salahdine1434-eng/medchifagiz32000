<?php
session_start();

$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($full_name === '' ||  $email === '' ||  $password === '' || $confirm === '') {
    die("يرجى ملء جميع الحقول");
}

if ($password !== $confirm) {
    die("كلمتا السر غير متطابقتين");
}

$_SESSION['register'] = [
    'full_name' => $full_name,
    'email' => $email,
    'password' => $password
];

header("Location: step2.php");
exit;
