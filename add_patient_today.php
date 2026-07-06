<?php
// ================================================================
//  add_patient_today.php — MedChifaGiz (النسخة الصحيحة)
//  يحفظ في medical_records فقط — لا يلمس patients أبداً
// ================================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

require 'db.php';

// ── جلب الطبيب ───────────────────────────────────────────────
$stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmtDoc->execute([$_SESSION['user_id']]);
$doctor = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo json_encode(['success' => false, 'message' => 'الطبيب غير موجود']);
    exit;
}
$doctorId = (int) $doctor['id'];
$patientId = 0;

if (!empty($email)) {
    $stmtPatient = $pdo->prepare("SELECT id FROM patients WHERE email = ? LIMIT 1");
    $stmtPatient->execute([$email]);
    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patientId = (int)$patient['id'];
    }
}

// ── helper ────────────────────────────────────────────────────
function p(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

// ── الحقول الإلزامية ──────────────────────────────────────────
$patientName = p('patient_name');
$age         = p('age');
$gender      = p('gender');
$reason      = p('reason');

if (!$patientName || !$age || !$gender || !$reason) {
    echo json_encode(['success' => false, 'message' => 'الحقول الإلزامية ناقصة']);
    exit;
}

// ── بقية الحقول ───────────────────────────────────────────────
$phone           = p('phone');
$email = p('email');
$birthInfo       = p('birth_info');
$maritalStatus   = p('marital_status');
$job             = p('job');
$address         = p('address');
$admissionDate   = p('admission_date')  ?: null;
$residencyStatus = p('residency_status');          // 'مقيم' | 'غير مقيم' | ''
$symptoms        = p('symptoms');
$bloodPressure   = p('blood_pressure');
$bloodSugar      = p('blood_sugar');
$heartRate       = p('heart_rate');
$temperature     = p('temperature');
$oxygenLevel     = p('oxygen_level');
$chronicPatient  = p('chronic_patient');
$chronicFamily   = p('chronic_family');
$medicalTests    = p('medical_tests');
$radiology       = p('radiology');
$prescription    = p('rx_prescription');
$needsAppt       = (p('needs_appointment') === 'yes') ? 'yes' : 'no';
$nextAppDate     = p('next_appointment_date') ?: null;
$nextAppTime     = p('next_appointment_time') ?: null;

// حمل
$lastPeriodDate        = p('last_period_date')        ?: null;
$expectedDeliveryDate  = p('expected_delivery_date')  ?: null;
$pregBloodType         = p('preg_blood_type');
$pregnanciesCount      = p('pregnancies_count')       ?: null;
$birthsCount           = p('births_count')            ?: null;
$miscarriagesCount     = p('miscarriages_count')      ?: null;
$cSectionsCount        = p('c_sections_count')        ?: null;
$pregChronicDiseases   = p('preg_chronic_diseases');
$fatherStatus          = p('father_status');
$consanguinity         = p('consanguinity');
$pregnancyNotes        = p('pregnancy_notes');
$pregWeight            = p('preg_weight');
$pregBP                = p('preg_blood_pressure');
$pregSugar             = p('preg_sugar_level');
$fetalHeartbeat        = p('fetal_heartbeat');
$fetalMovement         = p('fetal_movement');
$fetalWeight           = p('fetal_weight');
$fetalPosition         = p('fetal_position');
$echoNotes             = p('echo_notes');
$followupNotes         = p('followup_notes');


// البحث عن المريض بالبريد الإلكتروني
$patientId = 0;

if (!empty($email)) {
    $stmtPatient = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND role = 'patient'
        LIMIT 1
    ");
    $stmtPatient->execute([$email]);

    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patientId = (int)$patient['id'];
    }
}

try {
    // ── INSERT في medical_records (patient_id = 0 للمرضى اليدويين) ──
    $sql = "
        INSERT INTO medical_records (
            patient_id, doctor_id, full_name, gender, birth_info, marital_status, job,
            address, phone, email, reason_exam, symptoms,
            blood_pressure, blood_sugar, heart_rate, temperature, oxygen_level,
            chronic_patient, chronic_family,
            medical_tests, radiology, prescription,
            next_appointment, next_appointment_date, next_appointment_time,
            admission_date, residency_status,
            last_period_date, expected_delivery_date, preg_blood_type,
            pregnancies_count, births_count, miscarriages_count, c_sections_count,
            preg_chronic_diseases, father_status, consanguinity, pregnancy_notes,
            preg_weight, preg_blood_pressure, preg_sugar_level,
            fetal_heartbeat, fetal_movement, fetal_weight, fetal_position,
            echo_notes, followup_notes,
            created_at, updated_at
        ) VALUES (
           :patient_id, :doctor_id, :full_name, :gender, :birth_info, :marital_status, :job,
            :address, :phone, :email, :reason_exam, :symptoms,
            :blood_pressure, :blood_sugar, :heart_rate, :temperature, :oxygen_level,
            :chronic_patient, :chronic_family,
            :medical_tests, :radiology, :prescription,
            :next_appointment, :next_appointment_date, :next_appointment_time,
            :admission_date, :residency_status,
            :last_period_date, :expected_delivery_date, :preg_blood_type,
            :pregnancies_count, :births_count, :miscarriages_count, :c_sections_count,
            :preg_chronic_diseases, :father_status, :consanguinity, :pregnancy_notes,
            :preg_weight, :preg_blood_pressure, :preg_sugar_level,
            :fetal_heartbeat, :fetal_movement, :fetal_weight, :fetal_position,
            :echo_notes, :followup_notes,
            NOW(), NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':patient_id' => $patientId,
        ':doctor_id'             => $doctorId,
        ':full_name'             => $patientName,
        ':gender'                => $gender,
        ':birth_info'            => $birthInfo,
        ':marital_status'        => $maritalStatus,
        ':job'                   => $job,
        ':address'               => $address,
        ':phone'                 => $phone,
        ':email' => $email,
        ':reason_exam'           => $reason,
        ':symptoms'              => $symptoms,
        ':blood_pressure'        => $bloodPressure,
        ':blood_sugar'           => $bloodSugar,
        ':heart_rate'            => $heartRate,
        ':temperature'           => $temperature,
        ':oxygen_level'          => $oxygenLevel,
        ':chronic_patient'       => $chronicPatient,
        ':chronic_family'        => $chronicFamily,
        ':medical_tests'         => $medicalTests,
        ':radiology'             => $radiology,
        ':prescription'          => $prescription,
        ':next_appointment'      => $needsAppt,
        ':next_appointment_date' => $nextAppDate,
        ':next_appointment_time' => $nextAppTime,
        ':admission_date'        => $admissionDate,
        ':residency_status'      => $residencyStatus,
        ':last_period_date'          => $lastPeriodDate,
        ':expected_delivery_date'    => $expectedDeliveryDate,
        ':preg_blood_type'           => $pregBloodType,
        ':pregnancies_count'         => $pregnanciesCount,
        ':births_count'              => $birthsCount,
        ':miscarriages_count'        => $miscarriagesCount,
        ':c_sections_count'          => $cSectionsCount,
        ':preg_chronic_diseases'     => $pregChronicDiseases,
        ':father_status'             => $fatherStatus,
        ':consanguinity'             => $consanguinity,
        ':pregnancy_notes'           => $pregnancyNotes,
        ':preg_weight'               => $pregWeight,
        ':preg_blood_pressure'       => $pregBP,
        ':preg_sugar_level'          => $pregSugar,
        ':fetal_heartbeat'           => $fetalHeartbeat,
        ':fetal_movement'            => $fetalMovement,
        ':fetal_weight'              => $fetalWeight,
        ':fetal_position'            => $fetalPosition,
        ':echo_notes'                => $echoNotes,
        ':followup_notes'            => $followupNotes,
    ]);

    $recordId = (int) $pdo->lastInsertId();

    // ✅ FIX: حفظ بطاقة العلاج في fiche_traitement إذا أُرسلت مع الطلب
    $ficheDiagnostic  = mb_substr(trim(strip_tags($_POST['fiche_diagnostic']  ?? '')), 0, 5000);
    $ficheMedications = mb_substr(trim(strip_tags($_POST['fiche_medications'] ?? '')), 0, 5000);
    if ($ficheDiagnostic || $ficheMedications) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `fiche_traitement` (
                    `id`                INT(11)   NOT NULL AUTO_INCREMENT,
                    `medical_record_id` INT(11)   NOT NULL,
                    `fiche_diagnostic`  TEXT      DEFAULT NULL,
                    `fiche_medications` TEXT      DEFAULT NULL,
                    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_record` (`medical_record_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $stmtFiche = $pdo->prepare("
                INSERT INTO fiche_traitement (medical_record_id, fiche_diagnostic, fiche_medications)
                VALUES (:rid, :diag, :meds)
                ON DUPLICATE KEY UPDATE
                    fiche_diagnostic  = VALUES(fiche_diagnostic),
                    fiche_medications = VALUES(fiche_medications),
                    updated_at        = CURRENT_TIMESTAMP
            ");
            $stmtFiche->execute([
                ':rid'  => $recordId,
                ':diag' => $ficheDiagnostic  ?: null,
                ':meds' => $ficheMedications ?: null,
            ]);
        } catch (PDOException $eFiche) {
            error_log('[add_patient_today] fiche_traitement save error: ' . $eFiche->getMessage());
        }
    }

    // ✅ FIX: حفظ التقرير الطبي في rapport_medical إذا أُرسل مع الطلب
    $rapportContent = trim(strip_tags($_POST['rapport_content'] ?? ''));
    if ($rapportContent) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `rapport_medical` (
                    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
                    `patient_id`      INT(11)      NOT NULL,
                    `rapport_date`    DATE         DEFAULT NULL,
                    `rapport_patient` VARCHAR(255) DEFAULT NULL,
                    `rapport_age`     VARCHAR(50)  DEFAULT NULL,
                    `rapport_doctor`  VARCHAR(255) DEFAULT NULL,
                    `rapport_content` TEXT         DEFAULT NULL,
                    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_patient` (`patient_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $rapportDate    = $_POST['rapport_date']    ?? null;
            $rapportPatient = mb_substr(trim(strip_tags($_POST['rapport_patient'] ?? '')), 0, 255);
            $rapportAge     = mb_substr(trim(strip_tags($_POST['rapport_age']     ?? '')), 0, 50);
            $rapportDoctor  = mb_substr(trim(strip_tags($_POST['rapport_doctor']  ?? '')), 0, 255);
            // تحقق من صحة التاريخ
            $rapportDateVal = null;
            if ($rapportDate) {
                $d = DateTime::createFromFormat('Y-m-d', $rapportDate);
                if ($d && $d->format('Y-m-d') === $rapportDate) $rapportDateVal = $rapportDate;
            }
            $stmtRapport = $pdo->prepare("
                INSERT INTO rapport_medical
                    (patient_id, rapport_date, rapport_patient, rapport_age, rapport_doctor, rapport_content)
                VALUES (:pid, :rdate, :rpatient, :rage, :rdoctor, :rcontent)
                ON DUPLICATE KEY UPDATE
                    rapport_date    = VALUES(rapport_date),
                    rapport_patient = VALUES(rapport_patient),
                    rapport_age     = VALUES(rapport_age),
                    rapport_doctor  = VALUES(rapport_doctor),
                    rapport_content = VALUES(rapport_content),
                    updated_at      = CURRENT_TIMESTAMP
            ");
            $stmtRapport->execute([
                ':pid'      => $recordId,
                ':rdate'    => $rapportDateVal,
                ':rpatient' => $rapportPatient ?: null,
                ':rage'     => $rapportAge     ?: null,
                ':rdoctor'  => $rapportDoctor  ?: null,
                ':rcontent' => $rapportContent,
            ]);
        } catch (PDOException $eRapport) {
            error_log('[add_patient_today] rapport_medical save error: ' . $eRapport->getMessage());
        }
    }

    // ── حفظ في الأرشيف ──

// حفظ في الأرشيف
$stmtArchive = $pdo->prepare("
    INSERT INTO archived_records
    (
        patient_name,
        birth_date,
        medical_condition,
        job_type,
        blood_pressure,
        heart_rate,
        temperature,
        created_at
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmtArchive->execute([
    $patientName,
    $birthInfo,
    $chronicPatient,
    $job,
    $bloodPressure,
    $heartRate,
    $temperature
]);
    echo json_encode([
        'success'      => true,
        'record_id'    => $recordId,
        'patient_id'   => $recordId,      // JS يستعمل هذا للعرض
        'is_inpatient' => ($residencyStatus === 'مقيم'),
        'message'      => 'تم حفظ الملف الطبي بنجاح'
    ]);

} catch (PDOException $e) {
    error_log('[add_patient_today] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
