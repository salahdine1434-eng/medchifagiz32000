<?php

require_once 'db.php';

$monthly = $pdo->query("
    SELECT
        MONTH(created_at) AS month,
        COUNT(*) AS total
    FROM users
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

$roles = $pdo->query("
    SELECT
        role,
        COUNT(*) AS total
    FROM users
    GROUP BY role
")->fetchAll(PDO::FETCH_ASSOC);
$growth = [];

for ($month = 1; $month <= 12; $month++) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE MONTH(created_at) <= ?
    ");

    $stmt->execute([$month]);

    $growth[] = (int)$stmt->fetchColumn();
}
$patientsGrowth = [];
$doctorsGrowth = [];

for ($month = 1; $month <= 12; $month++) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'patient'
        AND MONTH(created_at) <= ?
    ");
    $stmt->execute([$month]);
    $patientsGrowth[] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'doctor'
        AND MONTH(created_at) <= ?
    ");
    $stmt->execute([$month]);
    $doctorsGrowth[] = (int)$stmt->fetchColumn();
}
echo json_encode([
    'monthly'         => $monthly,
    'roles'           => $roles,
    'growth'          => $growth,
    'patientsGrowth'  => $patientsGrowth,
    'doctorsGrowth'   => $doctorsGrowth
]);