<?php
/* ================================================================
   get_patient_medical_files.php
   يُرجع قائمة الوثائق الطبية الموجودة فعلاً لمريض (سجل طبي) معيّن،
   قراءةً مباشرة من الجداول الحالية — بلا رفع ولا نسخ ولا إنشاء جداول.
   المفتاح record_id = medical_records.id (نفس currentFollowupPatient).
   المدخل:  ?record_id=INT
   المخرج:  [{type, name, date}, ...]
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;
if ($recordId <= 0) { echo json_encode([]); exit; }

/* ── جلب السجل الطبي ── */
$stmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ?");
$stmt->execute([$recordId]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rec) { echo json_encode([]); exit; }

/* ── التحقق من الصلاحية: طبيب هذا السجل أو مريضه ── */
$authorized = false;
if ($_SESSION['role'] === 'doctor') {
    $d = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $d->execute([$_SESSION['user_id']]);
    $doc = $d->fetch(PDO::FETCH_ASSOC);
    if ($doc && intval($doc['id']) === intval($rec['doctor_id'])) $authorized = true;
} elseif ($_SESSION['role'] === 'patient') {
    // medical_records.patient_id يخزّن user_id الخاص بالمريض
    if (intval($_SESSION['user_id']) === intval($rec['patient_id'])) $authorized = true;
}
if (!$authorized) { echo json_encode([]); exit; }

$files = [];
$notEmpty = function ($v) { return $v !== null && trim((string)$v) !== ''; };

/* helper: يجلب أحدث تاريخ من جدول اختياري مع حماية من عدم وجوده */
$latestDate = function ($sql, $params) use ($pdo) {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ? ($r['d'] ?? null) : null;
    } catch (PDOException $e) {
        return false; // الجدول غير موجود / خطأ => نعتبره غير متوفر
    }
};

/* 1) Dossier Médical — السجل نفسه، موجود دائماً */
$files[] = [
    'type' => 'dossier',
    'name' => 'Dossier Médical',
    'date' => $rec['created_at'] ?? null
];

/* 2) Rapport Médical — جدول rapport_medical (patient_id = record_id) */
$d = $latestDate("SELECT COALESCE(updated_at, created_at) AS d FROM rapport_medical WHERE patient_id = ? AND rapport_content IS NOT NULL AND rapport_content <> '' ORDER BY id DESC LIMIT 1", [$recordId]);
if ($d) {
    $files[] = ['type' => 'rapport', 'name' => 'Rapport Médical', 'date' => $d];
}

/* 3) Fiche de traitement — جدول fiche_traitement (medical_record_id = record_id) */
$d = $latestDate("SELECT COALESCE(updated_at, created_at) AS d FROM fiche_traitement WHERE medical_record_id = ? ORDER BY id DESC LIMIT 1", [$recordId]);
if ($d) {
    $files[] = ['type' => 'fiche', 'name' => 'Fiche de traitement', 'date' => $d];
}

/* 4) Résultats d'analyses — عمود medical_tests أو جدول lab_requests */
$hasAnalyses = $notEmpty($rec['medical_tests'] ?? null);
$dLab = $latestDate("SELECT created_at AS d FROM lab_requests WHERE patient_id = ? ORDER BY id DESC LIMIT 1", [$recordId]);
if ($hasAnalyses || $dLab) {
    $files[] = [
        'type' => 'analyses',
        'name' => "Résultats d'analyses",
        'date' => $dLab ?: ($rec['updated_at'] ?? $rec['created_at'] ?? null)
    ];
}

/* 5) Radiologie — عمود radiology أو جدول radiology_requests */
$hasRadio = $notEmpty($rec['radiology'] ?? null);
$dRad = $latestDate("SELECT created_at AS d FROM radiology_requests WHERE patient_id = ? ORDER BY id DESC LIMIT 1", [$recordId]);
if ($hasRadio || $dRad) {
    $files[] = [
        'type' => 'radiologie',
        'name' => 'Radiologie',
        'date' => $dRad ?: ($rec['updated_at'] ?? $rec['created_at'] ?? null)
    ];
}

/* 6) Ordonnance — عمود prescription */
if ($notEmpty($rec['prescription'] ?? null)) {
    $files[] = [
        'type' => 'ordonnance',
        'name' => 'Ordonnance',
        'date' => $rec['updated_at'] ?? $rec['created_at'] ?? null
    ];
}

echo json_encode($files, JSON_UNESCAPED_UNICODE);
