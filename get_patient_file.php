<?php

require 'db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
SELECT * FROM medical_records
WHERE id = ?
");

$stmt->execute([$id]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');

echo json_encode($patient);
?>