<?php
session_start();
require "db.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$patientUserId = $_SESSION['user_id'];

$sql = "
SELECT DISTINCT

    d.user_id AS doctor_id,
    u.full_name,
    d.specialty,

    (
        SELECT message
        FROM medical_messages mm2
        WHERE mm2.doctor_id = d.user_id
          AND mm2.patient_user_id = ?
        ORDER BY mm2.created_at DESC
        LIMIT 1
    ) AS last_message,

    (
        SELECT created_at
        FROM medical_messages mm2
        WHERE mm2.doctor_id = d.user_id
          AND mm2.patient_user_id = ?
        ORDER BY mm2.created_at DESC
        LIMIT 1
    ) AS last_time,

    (
        SELECT record_id
        FROM medical_messages mm2
        WHERE mm2.doctor_id = d.user_id
          AND mm2.patient_user_id = ?
        ORDER BY mm2.created_at DESC
        LIMIT 1
    ) AS record_id

FROM medical_messages mm

JOIN doctors d
    ON d.user_id = mm.doctor_id

JOIN users u
    ON u.id = d.user_id

WHERE mm.patient_user_id = ?

ORDER BY last_time DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $patientUserId,
    $patientUserId,
    $patientUserId,
    $patientUserId
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));