<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'غير مسجل الدخول']);
    exit;
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("
        UPDATE patients SET
            first_name=?,
            last_name=?,
            birth_date=?,
            blood_type=?,
            weight=?,
            height=?,
            chronic_diseases=?,
            allergies=?,
            medications=?,
            health_notes=?,
            emergency_name=?,
            emergency_phone=?
        WHERE user_id=?
    ");

    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['birth_date'],
        $data['blood_type'],
        $data['weight'],
        $data['height'],
        $data['chronic_diseases'],
        $data['allergies'],
        $data['medications'],
        $data['health_notes'],
        $data['emergency_name'],
        $data['emergency_phone'],
        $_SESSION['user_id']
    ]);

    echo json_encode(['success'=>true]);

} catch(Exception $e){
    echo json_encode([
        'success'=>false,
        'message'=>$e->getMessage()
    ]);
}