<?php
// ================================================================
//  save_doctor_record.php — MedChifaGiz
//  يحفظ / يحدّث السجل الطبي لمريض موجود
//  ويحدّث residency_status + admission_date في جدول patients
// ================================================================
session_start();
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo 'غير مصرح';
    exit;
}

require 'db.php';

// ── جلب الطبيب ───────────────────────────────────────────────
$stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmtDoc->execute([$_SESSION['user_id']]);
$doctor = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo 'الطبيب غير موجود';
    exit;
}
$doctorId = (int) $doctor['id'];

// ── استقبال البيانات ──────────────────────────────────────────
function p(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

$patientId       = (int) p('patient_id');   // 0 = مريض جديد (بدون حساب)
$admissionDate   = p('admission_date') ?: null;
$residencyStatus = p('residency_status');   // 'مقيم' | 'غير مقيم' | ''

$needsAppt = (p('needs_appointment') === 'yes') ? 'yes' : 'no';
$nextAppDate = p('next_appointment_date') ?: null;
$nextAppTime = p('next_appointment_time') ?: null;

try {
    $pdo->beginTransaction();

    // ── البحث عن سجل طبي موجود لهذا الطبيب + المريض ─────────────
    if ($patientId > 0) {
        $stmtCheck = $pdo->prepare(
            "SELECT id FROM medical_records WHERE patient_id = ? AND doctor_id = ? LIMIT 1"
        );
        $stmtCheck->execute([$patientId, $doctorId]);
        $existingRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    } else {
        $existingRecord = false;
    }

    if ($existingRecord) {
        // ── UPDATE السجل الموجود ──────────────────────────────────
        $sql = "
            UPDATE medical_records SET
                full_name             = :full_name,
                gender                = :gender,
                birth_info            = :birth_info,
                marital_status        = :marital_status,
                job                   = :job,
                address               = :address,
                phone                 = :phone,
                reason_exam           = :reason_exam,
                symptoms              = :symptoms,
                blood_pressure        = :blood_pressure,
                blood_sugar           = :blood_sugar,
                heart_rate            = :heart_rate,
                temperature           = :temperature,
                oxygen_level          = :oxygen_level,
                chronic_patient       = :chronic_patient,
                chronic_family        = :chronic_family,
                medical_tests         = :medical_tests,
                radiology             = :radiology,
                prescription          = :prescription,
                next_appointment      = :next_appointment,
                next_appointment_date = :next_appointment_date,
                next_appointment_time = :next_appointment_time,
                last_period_date         = :last_period_date,
                expected_delivery_date   = :expected_delivery_date,
                preg_blood_type          = :preg_blood_type,
                pregnancies_count        = :pregnancies_count,
                births_count             = :births_count,
                miscarriages_count       = :miscarriages_count,
                c_sections_count         = :c_sections_count,
                preg_chronic_diseases    = :preg_chronic_diseases,
                father_status            = :father_status,
                consanguinity            = :consanguinity,
                pregnancy_notes          = :pregnancy_notes,
                preg_weight              = :preg_weight,
                preg_blood_pressure      = :preg_blood_pressure,
                preg_sugar_level         = :preg_sugar_level,
                fetal_heartbeat          = :fetal_heartbeat,
                fetal_movement           = :fetal_movement,
                fetal_weight             = :fetal_weight,
                fetal_position           = :fetal_position,
                echo_notes               = :echo_notes,
                followup_notes           = :followup_notes,
                updated_at            = NOW()
            WHERE patient_id = :patient_id
              AND doctor_id   = :doctor_id
        ";
    } else {
        // ── INSERT سجل جديد ───────────────────────────────────────
        $sql = "
            INSERT INTO medical_records
                (patient_id, doctor_id, full_name, gender, birth_info, marital_status, job,
                 address, phone, reason_exam, symptoms,
                 blood_pressure, blood_sugar, heart_rate, temperature, oxygen_level,
                 chronic_patient, chronic_family, medical_tests, radiology, prescription,
                 next_appointment, next_appointment_date, next_appointment_time,
                 last_period_date, expected_delivery_date, preg_blood_type,
                 pregnancies_count, births_count, miscarriages_count, c_sections_count,
                 preg_chronic_diseases, father_status, consanguinity, pregnancy_notes,
                 preg_weight, preg_blood_pressure, preg_sugar_level,
                 fetal_heartbeat, fetal_movement, fetal_weight, fetal_position,
                 echo_notes, followup_notes,
                 created_at, updated_at)
            VALUES
                (:patient_id, :doctor_id, :full_name, :gender, :birth_info, :marital_status, :job,
                 :address, :phone, :reason_exam, :symptoms,
                 :blood_pressure, :blood_sugar, :heart_rate, :temperature, :oxygen_level,
                 :chronic_patient, :chronic_family, :medical_tests, :radiology, :prescription,
                 :next_appointment, :next_appointment_date, :next_appointment_time,
                 :last_period_date, :expected_delivery_date, :preg_blood_type,
                 :pregnancies_count, :births_count, :miscarriages_count, :c_sections_count,
                 :preg_chronic_diseases, :father_status, :consanguinity, :pregnancy_notes,
                 :preg_weight, :preg_blood_pressure, :preg_sugar_level,
                 :fetal_heartbeat, :fetal_movement, :fetal_weight, :fetal_position,
                 :echo_notes, :followup_notes,
                 NOW(), NOW())
        ";
    }

    $stmtSave = $pdo->prepare($sql);
    $stmtSave->execute([
        ':patient_id'            => $patientId,
        ':doctor_id'             => $doctorId,
        ':full_name'             => p('full_name'),
        ':gender'                => p('gender'),
        ':birth_info'            => p('birth_info'),
        ':marital_status'        => p('marital_status'),
        ':job'                   => p('job'),
        ':address'               => p('address'),
        ':phone'                 => p('phone'),
        ':reason_exam'           => p('reason_exam'),
        ':symptoms'              => p('symptoms'),
        ':blood_pressure'        => p('blood_pressure'),
        ':blood_sugar'           => p('blood_sugar'),
        ':heart_rate'            => p('heart_rate'),
        ':temperature'           => p('temperature'),
        ':oxygen_level'          => p('oxygen_level'),
        ':chronic_patient'       => p('chronic_patient'),
        ':chronic_family'        => p('chronic_family'),
        ':medical_tests'         => p('medical_tests'),
        ':radiology'             => p('radiology'),
        ':prescription'          => p('prescription'),
        ':next_appointment'      => $needsAppt,
        ':next_appointment_date' => $nextAppDate,
        ':next_appointment_time' => $nextAppTime,
        ':last_period_date'         => p('last_period_date') ?: null,
        ':expected_delivery_date'   => p('expected_delivery_date') ?: null,
        ':preg_blood_type'          => p('preg_blood_type'),
        ':pregnancies_count'        => p('pregnancies_count') ?: null,
        ':births_count'             => p('births_count') ?: null,
        ':miscarriages_count'       => p('miscarriages_count') ?: null,
        ':c_sections_count'         => p('c_sections_count') ?: null,
        ':preg_chronic_diseases'    => p('preg_chronic_diseases'),
        ':father_status'            => p('father_status'),
        ':consanguinity'            => p('consanguinity'),
        ':pregnancy_notes'          => p('pregnancy_notes'),
        ':preg_weight'              => p('preg_weight'),
        ':preg_blood_pressure'      => p('preg_blood_pressure'),
        ':preg_sugar_level'         => p('preg_sugar_level'),
        ':fetal_heartbeat'          => p('fetal_heartbeat'),
        ':fetal_movement'           => p('fetal_movement'),
        ':fetal_weight'             => p('fetal_weight'),
        ':fetal_position'           => p('fetal_position'),
        ':echo_notes'               => p('echo_notes'),
        ':followup_notes'           => p('followup_notes'),
    ]);

    // ── معرّف السجل الطبي (موجود مسبقاً أو الجديد) لإرجاعه للواجهة ──
    $medicalRecordId = $existingRecord ? (int) $existingRecord['id'] : (int) $pdo->lastInsertId();

    // ── تحديث patients: residency_status + admission_date ────────
    //    هذا هو مصدر الخطأ الأصلي — نتحقق من وجود doctor_id أولاً
    if ($patientId > 0 && $residencyStatus !== '') {
        // تحديث آمن: نضيف doctor_id فقط إذا كان فارغاً (للمرضى القدامى)
        $stmtUpdatePatient = $pdo->prepare("
            UPDATE patients
            SET residency_status = :residency_status,
                admission_date   = COALESCE(:admission_date, admission_date),
                doctor_id        = CASE WHEN doctor_id IS NULL OR doctor_id = 0
                                        THEN :doctor_id
                                        ELSE doctor_id
                                   END
            WHERE id = :patient_id
        ");
        $stmtUpdatePatient->execute([
            ':residency_status' => $residencyStatus,
            ':admission_date'   => $admissionDate,
            ':doctor_id'        => $doctorId,
            ':patient_id'       => $patientId,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    //  الأرشفة عند نجاح الحفظ (داخل نفس المعاملة = all-or-nothing)
    //  1) نقل لقطة المريض إلى archived_records (نفس أعمدة add_patient_today.php)
    //  2) إخفاؤه من "مرضى اليوم" بضبط حالة موعد اليوم = completed
    //     (قائمة مرضى اليوم تُظهر status='confirmed' فقط)
    //  إذا فشلت أي خطوة → استثناء → rollBack → العملية كلها تفشل.
    // ════════════════════════════════════════════════════════════
    if ($patientId > 0) {
        $stmtArchive = $pdo->prepare("
            INSERT INTO archived_records
                (patient_name, birth_date, medical_condition, job_type,
                 blood_pressure, heart_rate, temperature, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtArchive->execute([
            p('full_name'),
            p('birth_info'),
            p('chronic_patient'),
            p('job'),
            p('blood_pressure'),
            p('heart_rate'),
            p('temperature'),
        ]);

        $stmtAppt = $pdo->prepare("
            UPDATE appointments
            SET status = 'completed'
            WHERE patient_id = :patient_id
              AND doctor_id  = :doctor_id
              AND appointment_date = :today
              AND status = 'confirmed'
        ");
        $stmtAppt->execute([
            ':patient_id' => $patientId,
            ':doctor_id'  => $doctorId,
            ':today'      => date('Y-m-d'),
        ]);
    }

    $pdo->commit();
    echo json_encode([
        'success'           => true,
        'message'           => 'تم حفظ الملف الطبي بنجاح',
        'patient_id'        => $patientId,
        'medical_record_id' => $medicalRecordId,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[save_doctor_record] PDO Error: ' . $e->getMessage());
    echo 'خطأ: ' . $e->getMessage();
}
