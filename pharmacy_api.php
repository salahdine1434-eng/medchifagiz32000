<?php
/* ════════════════════════════════════════════════════════════════
   MedChifaGiz — API الصيدلية (الطبيب → الصيدلية)
   نقطة نهاية مستقلة بنفس أسلوب المشروع (rapport/fiche/lab_radiology).
   تستخدم db.php الموجود + جدول pharmacy_requests الجديد.
   لا تلمس جدول prescriptions الموجود ولا نظام المرضى.
   الأفعال (action):
     send_prescription → حفظ وصفة جديدة (status=pending)
     list_new          → قراءة الوصفات الجديدة (للوحة الصيدلية)
     set_status        → (اختياري) تحديث حالة وصفة
   ════════════════════════════════════════════════════════════════ */

session_start();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require 'db.php'; // نفس ملف الاتصال ($pdo)

function ph_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    ph_json(['success' => false, 'message' => 'الرجاء تسجيل الدخول']);
}

/* doctor_id بنفس منطق dr_dashboard.php */
function ph_get_doctor_id($pdo) {
    if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
        return isset($_SESSION['staff_id']) ? (int) $_SESSION['staff_id'] : 0;
    }
    $st = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id'] : 0;
}

/* يقبل medicines كـ JSON صحيح أو نص حر → يُعيد JSON صالح دائماً */
function ph_normalize_medicines($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return '[]';
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE);
    }
    // نص حر: كل سطر = دواء (لا نفقد أي معلومة كتبها الطبيب)
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $arr = [];
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln !== '') {
            $arr[] = ['name' => $ln, 'dose' => '', 'freq' => '', 'duration' => '', 'route' => ''];
        }
    }
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

/* ════════════════ إرسال وصفة للصيدلية ════════════════ */
if ($action === 'send_prescription') {

    $patientId   = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $medicinesIn = isset($_POST['medicines']) ? $_POST['medicines'] : '';

    if ($patientId <= 0) {
        ph_json(['success' => false, 'message' => 'لا يوجد مريض مفتوح — احفظ ملف المريض أولاً']);
    }

    $medicines = ph_normalize_medicines($medicinesIn);
    if ($medicines === '[]') {
        ph_json(['success' => false, 'message' => 'لا يمكن إرسال وصفة فارغة — اكتب الأدوية أولاً']);
    }

    $doctorId = ph_get_doctor_id($pdo);

    $patientName = isset($_POST['patient_name']) ? trim($_POST['patient_name']) : '';
    $birthInfo   = isset($_POST['birth_info'])   ? trim($_POST['birth_info'])   : '';
    $gender      = isset($_POST['gender'])       ? trim($_POST['gender'])       : '';
    $room        = isset($_POST['room'])         ? trim($_POST['room'])         : '';
    $service     = isset($_POST['service'])      ? trim($_POST['service'])      : '';
    $aile        = isset($_POST['aile'])         ? trim($_POST['aile'])         : '';
    $doctorName  = isset($_POST['doctor_name'])  ? trim($_POST['doctor_name'])  : '';
    $diagnostic  = isset($_POST['diagnostic'])   ? trim($_POST['diagnostic'])   : '';
    $rxDate      = isset($_POST['rx_date']) && $_POST['rx_date'] !== '' ? $_POST['rx_date'] : date('Y-m-d');
    $rxTime      = isset($_POST['rx_time']) && $_POST['rx_time'] !== '' ? $_POST['rx_time'] : date('H:i');
    $notes       = isset($_POST['notes'])        ? trim($_POST['notes'])        : '';

    try {
        $st = $pdo->prepare(
            "INSERT INTO pharmacy_requests
              (patient_id, patient_name, birth_info, gender, room, service, aile,
               doctor_id, doctor_name, diagnostic, rx_date, rx_time, notes, medicines, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')"
        );
        $st->execute([
            $patientId, $patientName, $birthInfo, $gender, $room, $service, $aile,
            $doctorId, $doctorName, $diagnostic, $rxDate, $rxTime, $notes, $medicines
        ]);
        ph_json([
            'success' => true,
            'message' => 'تم إرسال الوصفة إلى الصيدلية',
            'id'      => (int) $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        error_log('pharmacy send_prescription failed: ' . $e->getMessage());
        ph_json(['success' => false, 'message' => 'تعذّر حفظ الوصفة']);
    }
}

/* ════════════════ قراءة الوصفات الجديدة (لوحة الصيدلية) ════════════════ */
if ($action === 'list_new') {
    try {
        $st = $pdo->query(
            "SELECT id, patient_id, patient_name, birth_info, gender, room, service, aile,
                    doctor_name, diagnostic, rx_date, rx_time, notes, medicines, status, created_at
             FROM pharmacy_requests
             ORDER BY created_at DESC"
        );
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $dec = json_decode($r['medicines'], true);
            $r['medicines'] = is_array($dec) ? $dec : [];
        }
        unset($r);
        ph_json(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('pharmacy list_new failed: ' . $e->getMessage());
        ph_json(['success' => false, 'message' => 'تعذّر جلب الوصفات', 'data' => []]);
    }
}

/* ════════════════ (اختياري) تحديث حالة وصفة ════════════════ */
if ($action === 'set_status') {
    $id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed = ['pending', 'approved', 'rejected', 'review', 'sent', 'delivered'];
    if ($id <= 0 || !in_array($status, $allowed, true)) {
        ph_json(['success' => false, 'message' => 'بيانات غير صالحة']);
    }
    try {
        $st = $pdo->prepare("UPDATE pharmacy_requests SET status = ? WHERE id = ?");
        $st->execute([$status, $id]);
        ph_json(['success' => true, 'message' => 'تم تحديث الحالة']);
    } catch (PDOException $e) {
        error_log('pharmacy set_status failed: ' . $e->getMessage());
        ph_json(['success' => false, 'message' => 'تعذّر تحديث الحالة']);
    }
}

ph_json(['success' => false, 'message' => 'طلب غير صالح']);
