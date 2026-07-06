<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?? 0;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (!$id || !$date || !$time) {
        exit('error');
    }

    // نجيبو patient_id
    $get = $pdo->prepare("SELECT patient_id FROM appointments WHERE id = ?");
    $get->execute([$id]);
    $appointment = $get->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        exit('موعد غير موجود');
    }

    $patient_id = $appointment['patient_id'];

    // تحديث الموعد
    $stmt = $pdo->prepare("
        UPDATE appointments
        SET appointment_date = ?,
            appointment_time = ?,
            status = 'confirmed'
        WHERE id = ?
    ");
    $stmt->execute([$date, $time, $id]);

    // إضافة إشعار للمريض
    $message = "🔄 تم إعادة برمجة موعدك إلى يوم $date على الساعة $time";

    $notif = $pdo->prepare("
        INSERT INTO notifications (user_id, message, is_read)
        VALUES (?, ?, 0)
    ");
    $notif->execute([$patient_id, $message]);

    echo 'success';
}
?>