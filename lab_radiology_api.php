<?php
/* ════════════════════════════════════════════════════════════════
   MedChifaGiz — API الفحوصات التكميلية (المخبر + الأشعة)
   نقطة نهاية مستقلة على نفس أسلوب المشروع (rapport_medical_api.php / fiche_traitement_api.php)
   - تستخدم db.php الموجود حالياً
   - تستخدم الجداول الموجودة مسبقاً: lab_requests و radiology_requests
   - لا تنشئ ولا تعدّل أي جدول
   الأفعال (action):
     send_lab_request        → إرسال طلب تحاليل للمخبر
     send_radiology_request  → إرسال طلب أشعة
     list_lab_requests       → قراءة طلبات المخبر (للوحة المخبر)
     list_radiology_requests → قراءة طلبات الأشعة (للوحة الأشعة)
   ════════════════════════════════════════════════════════════════ */

session_start();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require 'db.php'; // نفس ملف الاتصال الموجود ($pdo)

/* ── ردّ JSON موحّد ثم إنهاء ── */
function lr_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── التحقق من تسجيل الدخول كطبيب (نفس منطق dr_dashboard.php) ── */
if (!isset($_SESSION['user_id'])) {
    lr_json(['success' => false, 'message' => 'الرجاء تسجيل الدخول']);
}

/* ── استخراج doctor_id بنفس طريقة dr_dashboard.php ── */
function lr_get_doctor_id($pdo) {
    // حالة موظف العيادة
    if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
        return isset($_SESSION['staff_id']) ? (int) $_SESSION['staff_id'] : 0;
    }
    // الطبيب العادي: doctors.user_id = user_id الحالي
    $st = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id'] : 0;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

/* ════════════════════════ إرسال للمخبر ════════════════════════ */
if ($action === 'send_lab_request') {

    $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $text      = isset($_POST['analysis_text']) ? trim($_POST['analysis_text']) : '';

    if ($patientId <= 0) {
        lr_json(['success' => false, 'message' => 'لا يوجد مريض مفتوح — احفظ ملف المريض أولاً']);
    }
    if ($text === '') {
        lr_json(['success' => false, 'message' => 'لا يمكن إرسال طلب فارغ — اكتب التحاليل المطلوبة']);
    }

    $doctorId = lr_get_doctor_id($pdo);
    if ($doctorId <= 0) {
        lr_json(['success' => false, 'message' => 'تعذّر تحديد الطبيب']);
    }

    try {
        $st = $pdo->prepare(
            "INSERT INTO lab_requests (patient_id, doctor_id, analysis_text, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $st->execute([$patientId, $doctorId, $text]);
        lr_json([
            'success' => true,
            'message' => 'تم إرسال طلب التحاليل إلى المخبر',
            'id'      => (int) $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        error_log('lab_request insert failed: ' . $e->getMessage());
        lr_json(['success' => false, 'message' => 'تعذّر حفظ طلب المخبر']);
    }
}

/* ════════════════════════ إرسال للأشعة ════════════════════════ */
if ($action === 'send_radiology_request') {

    $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $text      = isset($_POST['radiology_text']) ? trim($_POST['radiology_text']) : '';

    if ($patientId <= 0) {
        lr_json(['success' => false, 'message' => 'لا يوجد مريض مفتوح — احفظ ملف المريض أولاً']);
    }
    if ($text === '') {
        lr_json(['success' => false, 'message' => 'لا يمكن إرسال طلب فارغ — اكتب الفحوصات المطلوبة']);
    }

    $doctorId = lr_get_doctor_id($pdo);
    if ($doctorId <= 0) {
        lr_json(['success' => false, 'message' => 'تعذّر تحديد الطبيب']);
    }

    try {
        $st = $pdo->prepare(
            "INSERT INTO radiology_requests (patient_id, doctor_id, radiology_text, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $st->execute([$patientId, $doctorId, $text]);
        lr_json([
            'success' => true,
            'message' => 'تم إرسال طلب الأشعة',
            'id'      => (int) $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        error_log('radiology_request insert failed: ' . $e->getMessage());
        lr_json(['success' => false, 'message' => 'تعذّر حفظ طلب الأشعة']);
    }
}

/* ════════════════════ قراءة طلبات المخبر (للوحة المخبر) ════════════════════ */
if ($action === 'list_lab_requests') {
    try {
        $st = $pdo->query(
            "SELECT lr.id,
                    lr.patient_id,
                    lr.analysis_text,
                    lr.status,
                    lr.created_at,
                    mr.full_name AS patient_name,
                    u.full_name  AS doctor_name
             FROM lab_requests lr
             LEFT JOIN medical_records mr ON mr.id = lr.patient_id
             LEFT JOIN doctors d          ON d.id  = lr.doctor_id
             LEFT JOIN users u            ON u.id  = d.user_id
             ORDER BY lr.created_at DESC"
        );
        lr_json(['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        error_log('list_lab_requests failed: ' . $e->getMessage());
        lr_json(['success' => false, 'message' => 'تعذّر جلب طلبات المخبر', 'data' => []]);
    }
}

/* ════════════════════ قراءة طلبات الأشعة (للوحة الأشعة) ════════════════════ */
if ($action === 'list_radiology_requests') {
    try {
        $st = $pdo->query(
            "SELECT rr.id,
                    rr.patient_id,
                    rr.radiology_text,
                    rr.status,
                    rr.created_at,
                    mr.full_name AS patient_name,
                    u.full_name  AS doctor_name
             FROM radiology_requests rr
             LEFT JOIN medical_records mr ON mr.id = rr.patient_id
             LEFT JOIN doctors d          ON d.id  = rr.doctor_id
             LEFT JOIN users u            ON u.id  = d.user_id
             ORDER BY rr.created_at DESC"
        );
        lr_json(['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        error_log('list_radiology_requests failed: ' . $e->getMessage());
        lr_json(['success' => false, 'message' => 'تعذّر جلب طلبات الأشعة', 'data' => []]);
    }
}

/* ── فعل غير معروف ── */
lr_json(['success' => false, 'message' => 'طلب غير صالح']);
