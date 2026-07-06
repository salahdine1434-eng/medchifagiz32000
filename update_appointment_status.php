<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';

    $allowed = ['completed', 'no_show', 'confirmed', 'cancelled'];

    if (!$id || !in_array($status, $allowed)) {
        exit('error');
    }

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // ════════════════════════════════════════════════════════════
    //  إضافة فقط: بعد نجاح (حضر/لم يحضر) وإذا كان للمريض ملف طبي محفوظ
    //  لدى نفس الطبيب → أضِف نفس المريض إلى الأرشيف أيضاً (دون استبدال
    //  سجل المواعيد). لا نُفشل عملية حضر/لم يحضر إذا فشلت الأرشفة فقط.
    // ════════════════════════════════════════════════════════════
    if ($status === 'completed' || $status === 'no_show') {
        try {
            $appStmt = $pdo->prepare("SELECT patient_id, doctor_id FROM appointments WHERE id = ? LIMIT 1");
            $appStmt->execute([$id]);
            $app = $appStmt->fetch(PDO::FETCH_ASSOC);

            if ($app && (int) $app['patient_id'] > 0) {
                // هل للمريض ملف طبي محفوظ لدى نفس الطبيب؟
                $mrStmt = $pdo->prepare("
                    SELECT full_name, birth_info, chronic_patient, job,
                           blood_pressure, heart_rate, temperature
                    FROM medical_records
                    WHERE patient_id = ? AND doctor_id = ?
                    ORDER BY id DESC LIMIT 1
                ");
                $mrStmt->execute([$app['patient_id'], $app['doctor_id']]);
                $mr = $mrStmt->fetch(PDO::FETCH_ASSOC);

                if ($mr) {
                    // لقطة في الأرشيف (نفس أعمدة add_patient_today.php)
                    $arch = $pdo->prepare("
                        INSERT INTO archived_records
                            (patient_name, birth_date, medical_condition, job_type,
                             blood_pressure, heart_rate, temperature, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $arch->execute([
                        $mr['full_name'],
                        $mr['birth_info'],
                        $mr['chronic_patient'],
                        $mr['job'],
                        $mr['blood_pressure'],
                        $mr['heart_rate'],
                        $mr['temperature'],
                    ]);
                }
            }
        } catch (PDOException $e) {
            error_log('[update_appointment_status] archive error: ' . $e->getMessage());
        }
    }

    echo 'success';
}
?>