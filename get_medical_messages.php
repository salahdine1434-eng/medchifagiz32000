<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$sessionUserId = intval($_SESSION['user_id']);
$role          = $_SESSION['role'];

$doctorUserId  = null;
$patientUserId = null;

/* ============================================================
   1) تحديد طرفَي المحادثة (doctor_user_id / patient_user_id)
   ============================================================
   - واجهة الطبيب (dr_dashboard.js -> loadMedicalMessages) ترسل record_id
   - واجهة المريض (patient_medcomm.js -> medcommSelectDoctor) ترسل doctor_id
   كلا المدخلين يجب أن يؤديا لنفس نتيجة الجلب الكاملة، دون أي تصفية
   حسب sender_role، ودون الاعتماد على سجل واحد فقط (record_id) بحيث
   لا تختفي رسائل تعود لسجل طبي سابق بين نفس الطبيب ونفس المريض.
*/

if (isset($_GET['record_id']) && intval($_GET['record_id']) > 0) {

    $recordId = intval($_GET['record_id']);

    $stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM medical_records WHERE id = ?");
    $stmt->execute([$recordId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) {
        echo json_encode([]);
        exit;
    }

    // patient_id في medical_records يخزن أصلاً user_id الخاص بالمريض
    $patientUserId = intval($rec['patient_id']);

    // doctor_id في medical_records يخزن id جدول doctors وليس user_id
    $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt->execute([$rec['doctor_id']]);
    $docRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$docRow) {
        echo json_encode([]);
        exit;
    }

    $doctorUserId = intval($docRow['user_id']);

} elseif (isset($_GET['doctor_id']) && intval($_GET['doctor_id']) > 0) {

    $doctorUserId  = intval($_GET['doctor_id']);
    $patientUserId = $sessionUserId;

} else {
    echo json_encode([]);
    exit;
}

/* ============================================================
   2) التحقق من الصلاحية: يجب أن يكون المستخدم الحالي
      أحد طرفي هذه المحادثة فعلاً (طبيبها أو مريضها)
   ============================================================ */

if ($role === 'doctor') {
    if ($sessionUserId !== $doctorUserId) {
        echo json_encode([]);
        exit;
    }
} elseif ($role === 'patient') {
    if ($sessionUserId !== $patientUserId) {
        echo json_encode([]);
        exit;
    }
} else {
    echo json_encode([]);
    exit;
}

/* ============================================================
   3) جلب كامل المحادثة بين الطرفين
      - بدون أي شرط على sender_role (لا نصفّي حسب من أرسل)
      - مرتّبة تصاعدياً حسب created_at (الأقدم أولاً)
      - حسب (doctor_id, patient_user_id) وليس حسب record_id فقط،
        حتى تبقى كل الرسائل التاريخية ظاهرة حتى لو تعلقت بسجل طبي
        (record_id) مختلف بين نفس الطبيب ونفس المريض
   ============================================================ */

$stmt = $pdo->prepare("
    SELECT
        id,
        record_id,
        doctor_id,
        patient_user_id,
        sender_id,
        receiver_id,
        sender_role,
        message,
        attachment_path,
        attachment_name,
        attachment_type,
        voice_path,
        voice_duration,
        is_deleted,
        is_edited,
        is_pinned,
        is_read,
        reply_to_message_id,
        created_at
    FROM medical_messages
    WHERE doctor_id = ?
      AND patient_user_id = ?
    ORDER BY created_at ASC, id ASC
");

$stmt->execute([$doctorUserId, $patientUserId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
