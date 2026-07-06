<?php

session_start();
require_once "db.php";

$email = $_GET['email'] ?? '';

if(!empty($email)){

$stmt = $pdo->prepare("UPDATE users SET twofa_enabled=0 WHERE email=?");
$stmt->execute([$email]);

}

header("Location: success.php");
exit();

?>