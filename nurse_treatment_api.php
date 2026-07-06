<?php
/* ════════════════════════════════════════════════════════════════
   MedChifaGiz — API علاجات الممرض (الطبيب → الممرض)
   نقطة نهاية مستقلة بنفس أسلوب المشروع.
   تستخدم db.php الموجود + جدول nurse_treatments الجديد.
   لا تلمس جدول fiche_traitement الموجود ولا نظام المرضى/الأجنحة.
   الأفعال (action):
     send_treatment → حفظ/تحديث علاجات المريض (UPSERT = مزامنة حقيقية)
     list           → قراءة العلاجات (اختياري ?aile=men|women)
     get            → علاجات مريض واحد (?patient_id=..)
     confirm        → الممرض يؤكد التنفيذ → status=completed
   ════════════════════════════════════════════════════════════════ */

session_start();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require 'db.php'; // نفس ملف الاتصال ($pdo)

function nt_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    nt_json(['success' => false, 'message' => 'الرجاء تسجيل الدخول']);
}

function nt_get_doctor_id($pdo) {
    if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
        return isset($_SESSION['staff_id']) ? (int) $_SESSION['staff_id'] : 0;
    }
    $st = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id'] : 0;
}

/* treatments: JSON صحيح أو نص حر (كل سطر = علاج) → JSON صالح دائماً */
function nt_normalize_treatments($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return '[]';
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE);
    }
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $arr = [];
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln !== '') {
            $arr[] = [
                'name' => $ln, 'medicament' => $ln, 'dose' => '',
                'heure' => '', 'freq' => '', 'duree' => '', 'instructions' => ''
            ];
        }
    }
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

function nt_decode_rows(&$rows) {
    foreach ($rows as &$r) {
        $dec = json_decode($r['treatments'], true);
        $r['treatments'] = is_array($dec) ? $dec : [];
    }
    unset($r);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

/* ════════════════ إرسال/تحديث علاجات (UPSERT) ════════════════ */
if ($action === 'send_treatment') {

    $patientId    = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $treatmentsIn = isset($_POST['treatments']) ? $_POST['treatments'] : '';

    if ($patientId <= 0) {
        nt_json(['success' => false, 'message' => 'لا يوجد مريض مفتوح — احفظ ملف المريض أولاً']);
    }

    $treatments = nt_normalize_treatments($treatmentsIn);
    if ($treatments === '[]') {
        nt_json(['success' => false, 'message' => 'لا يمكن إرسال fiche فارغة — اكتب العلاجات أولاً']);
    }

    $doctorId  = nt_get_doctor_id($pdo);

    $patientName   = isset($_POST['patient_name'])   ? trim($_POST['patient_name'])   : '';
    $birthInfo     = isset($_POST['birth_info'])     ? trim($_POST['birth_info'])     : '';
    $gender        = isset($_POST['gender'])         ? trim($_POST['gender'])         : '';
    $room          = isset($_POST['room'])           ? trim($_POST['room'])           : '';
    $service       = isset($_POST['service'])        ? trim($_POST['service'])        : '';
    $aile          = isset($_POST['aile'])           ? trim($_POST['aile'])           : '';
    $doctorName    = isset($_POST['doctor_name'])    ? trim($_POST['doctor_name'])    : '';
    $motif         = isset($_POST['motif'])          ? trim($_POST['motif'])          : '';
    $diagnostic    = isset($_POST['diagnostic'])     ? trim($_POST['diagnostic'])     : '';
    $admissionDate = isset($_POST['admission_date']) && $_POST['admission_date'] !== '' ? $_POST['admission_date'] : null;

    try {
        // UPSERT: صف واحد لكل مريض (patient_id فريد) → التعديل يتحدث، والمحتوى الجديد يستبدل القديم
        $st = $pdo->prepare(
            "INSERT INTO nurse_treatments
               (patient_id, patient_name, birth_info, gender, room, service, aile,
                doctor_id, doctor_name, motif, diagnostic, admission_date, treatments, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')
             ON DUPLICATE KEY UPDATE
                patient_name   = VALUES(patient_name),
                birth_info     = VALUES(birth_info),
                gender         = VALUES(gender),
                room           = VALUES(room),
                service        = VALUES(service),
                aile           = VALUES(aile),
                doctor_id      = VALUES(doctor_id),
                doctor_name    = VALUES(doctor_name),
                motif          = VALUES(motif),
                diagnostic     = VALUES(diagnostic),
                admission_date = VALUES(admission_date),
                treatments     = VALUES(treatments),
                status         = 'pending'"
        );
        $st->execute([
            $patientId, $patientName, $birthInfo, $gender, $room, $service, $aile,
            $doctorId, $doctorName, $motif, $diagnostic, $admissionDate, $treatments
        ]);
        nt_json(['success' => true, 'message' => 'تم إرسال fiche العلاج إلى الممرض']);
    } catch (PDOException $e) {
        error_log('nurse send_treatment failed: ' . $e->getMessage());
        nt_json(['success' => false, 'message' => 'تعذّر حفظ العلاجات']);
    }
}

/* ════════════════ قراءة العلاجات (لوحة الممرض) ════════════════ */
if ($action === 'list') {
    $aile = isset($_GET['aile']) ? trim($_GET['aile']) : '';
    try {
        if ($aile === 'men' || $aile === 'women') {
            $st = $pdo->prepare(
                "SELECT * FROM nurse_treatments WHERE aile = ? ORDER BY updated_at DESC"
            );
            $st->execute([$aile]);
        } else {
            $st = $pdo->query("SELECT * FROM nurse_treatments ORDER BY updated_at DESC");
        }
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        nt_decode_rows($rows);
        nt_json(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('nurse list failed: ' . $e->getMessage());
        nt_json(['success' => false, 'message' => 'تعذّر جلب العلاجات', 'data' => []]);
    }
}

/* ════════════════ علاجات مريض واحد ════════════════ */
if ($action === 'get') {
    $patientId = isset($_GET['patient_id']) ? (int) $_GET['patient_id'] : 0;
    if ($patientId <= 0) {
        nt_json(['success' => false, 'message' => 'patient_id مطلوب', 'data' => null]);
    }
    try {
        $st = $pdo->prepare("SELECT * FROM nurse_treatments WHERE patient_id = ? LIMIT 1");
        $st->execute([$patientId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $dec = json_decode($row['treatments'], true);
            $row['treatments'] = is_array($dec) ? $dec : [];
        }
        nt_json(['success' => true, 'data' => $row ?: null]);
    } catch (PDOException $e) {
        error_log('nurse get failed: ' . $e->getMessage());
        nt_json(['success' => false, 'message' => 'تعذّر الجلب', 'data' => null]);
    }
}

/* ════════════════ تأكيد التنفيذ من الممرض → completed ════════════════ */
if ($action === 'confirm') {
    $id        = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    try {
        if ($id > 0) {
            $st = $pdo->prepare("UPDATE nurse_treatments SET status = 'completed' WHERE id = ?");
            $st->execute([$id]);
        } elseif ($patientId > 0) {
            $st = $pdo->prepare("UPDATE nurse_treatments SET status = 'completed' WHERE patient_id = ?");
            $st->execute([$patientId]);
        } else {
            nt_json(['success' => false, 'message' => 'id أو patient_id مطلوب']);
        }
        nt_json(['success' => true, 'message' => 'تم تأكيد تنفيذ العلاج']);
    } catch (PDOException $e) {
        error_log('nurse confirm failed: ' . $e->getMessage());
        nt_json(['success' => false, 'message' => 'تعذّر التأكيد']);
    }
}

nt_json(['success' => false, 'message' => 'طلب غير صالح']);
