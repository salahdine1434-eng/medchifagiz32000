<?php
/**
 * fiche_traitement_api.php
 * ════════════════════════════════════════════════════════════
 *  API نظيفة لحفظ وتحميل بطاقة العلاج (fiche de traitement)
 *  يُستدعى من:
 *    - dr_dashboard.php  (apfSaveFicheTraitement)
 *    - view_record.php   (تحميل تلقائي عند فتح الملف)
 *
 *  POST save_fiche  → يحفظ بطاقة العلاج
 *  GET  load_fiche  → يرجع بيانات بطاقة العلاج
 * ════════════════════════════════════════════════════════════
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

require 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ──────────────────────────────────────────
   تأكد أن جدول fiche_traitement موجود
────────────────────────────────────────── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS fiche_traitement (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        medical_record_id INT NOT NULL,
        doctor_id         INT NOT NULL,
        fiche_diagnostic  TEXT,
        fiche_medications TEXT,
        created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_record (medical_record_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

header('Content-Type: application/json; charset=utf-8');

/* ══════════════════════════════════════════
   SAVE — POST
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'save_fiche') {

    $medical_record_id  = (int)($_POST['medical_record_id']  ?? 0);
    $fiche_diagnostic   = trim($_POST['fiche_diagnostic']    ?? '');
    $fiche_medications  = trim($_POST['fiche_medications']   ?? '');

    if ($medical_record_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف السجل الطبي مطلوب']);
        exit;
    }

    /* تحقق من صلاحية الطبيب */
    $doctorStmt = $pdo->prepare("
        SELECT d.id FROM doctors d WHERE d.user_id = ?
    ");
    $doctorStmt->execute([$_SESSION['user_id']]);
    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        echo json_encode(['success' => false, 'message' => 'بيانات الطبيب غير موجودة']);
        exit;
    }

    $ownerStmt = $pdo->prepare("
        SELECT id FROM medical_records WHERE id = ? AND doctor_id = ?
    ");
    $ownerStmt->execute([$medical_record_id, $doctor['id']]);
    if (!$ownerStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'السجل الطبي غير موجود أو لا تملك الصلاحية']);
        exit;
    }

    /* INSERT أو UPDATE */
    $stmt = $pdo->prepare("
        INSERT INTO fiche_traitement
            (medical_record_id, doctor_id, fiche_diagnostic, fiche_medications)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            fiche_diagnostic  = VALUES(fiche_diagnostic),
            fiche_medications = VALUES(fiche_medications),
            updated_at        = CURRENT_TIMESTAMP
    ");

    $ok = $stmt->execute([
        $medical_record_id,
        $doctor['id'],
        $fiche_diagnostic  ?: null,
        $fiche_medications ?: null,
    ]);

    if ($ok) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حفظ بطاقة العلاج بنجاح'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل الحفظ في قاعدة البيانات']);
    }
    exit;
}

/* ══════════════════════════════════════════
   LOAD — GET
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $action === 'load_fiche') {

    $medical_record_id = (int)($_GET['medical_record_id'] ?? 0);

    if ($medical_record_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف السجل مطلوب']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT fiche_diagnostic, fiche_medications
        FROM fiche_traitement
        WHERE medical_record_id = ?
        LIMIT 1
    ");
    $stmt->execute([$medical_record_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
    exit;
}

/* ══════════════════════════════════════════
   طلب غير معروف
══════════════════════════════════════════ */
echo json_encode(['success' => false, 'message' => 'طلب غير صحيح']);
exit;
