<?php

include "config.php";

$id = $_GET['id'];

$sql = "SELECT * FROM archived_records WHERE id='$id'";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

?>

<h2><?= $row['patient_name'] ?></h2>

<p>تاريخ الميلاد: <?= $row['birth_date'] ?></p>

<p>الحالة: <?= $row['medical_condition'] ?></p>

<p>العمل: <?= $row['job_type'] ?></p>

<p>ضغط الدم: <?= $row['blood_pressure'] ?></p>

<p>النبض: <?= $row['heart_rate'] ?></p>

<p>الحرارة: <?= $row['temperature'] ?></p>