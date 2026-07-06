<?php
/**
 * accept_appointment.php
 * قبول أو رفض الموعد من طرف الطبيب
 * FIX: validation صارم على التاريخ والوقت + حماية من قيم فارغة + تحسين notification
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

// ----- التحقق من الجلسة -----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بهذا الإجراء']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'البيانات غير صالحة']);
    exit;
}

$appointment_id = isset($data['id']) ? (int) $data['id'] : 0;
$action         = trim($data['action'] ?? '');
$appt_date      = trim($data['date'] ?? '');
$appt_time      = trim($data['time'] ?? '');

// ----- Validation أساسي -----
if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف الحجز غير صحيح']);
    exit;
}

if (!in_array($action, ['confirm', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'الإجراء غير صحيح']);
    exit;
}

// ----- FIX: Validation صارم على التاريخ والوقت عند التأكيد -----
if ($action === 'confirm') {
    if (empty($appt_date)) {
        echo json_encode(['success' => false, 'message' => '⚠️ يرجى تحديد تاريخ الموعد قبل التأكيد']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appt_date)) {
        echo json_encode(['success' => false, 'message' => '⚠️ صيغة التاريخ غير صحيحة (YYYY-MM-DD)']);
        exit;
    }
    // التحقق من صحة التاريخ فعلياً
    $dateParts = explode('-', $appt_date);
    if (!checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
        echo json_encode(['success' => false, 'message' => '⚠️ التاريخ المدخل غير صحيح']);
        exit;
    }

    if (empty($appt_time)) {
        echo json_encode(['success' => false, 'message' => '⚠️ يرجى تحديد ساعة الموعد قبل التأكيد']);
        exit;
    }
    // FIX: قبول صيغة HH:MM أو HH:MM:SS من HTML time input
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $appt_time)) {
        echo json_encode(['success' => false, 'message' => '⚠️ صيغة الوقت غير صحيحة (HH:MM)']);
        exit;
    }
    // نضمن صيغة HH:MM فقط للحفظ في DB
    $appt_time = substr($appt_time, 0, 5);
}

// ----- جلب doctor_id من جدول doctors -----
$stmtDoctor = $pdo->prepare("SELECT id, user_id FROM doctors WHERE user_id = ?");
$stmtDoctor->execute([$_SESSION['user_id']]);
$doctorRow = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

if (!$doctorRow) {
    echo json_encode(['success' => false, 'message' => 'لم يُعثر على ملفك الطبي']);
    exit;
}
$doctor_id = $doctorRow['id'];

// ----- جلب بيانات الحجز والتحقق من الملكية -----
$stmtApp = $pdo->prepare("
    SELECT a.*, u.full_name AS doctor_full_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmtApp->execute([$appointment_id, $doctor_id]);
$appointment = $stmtApp->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'الحجز غير موجود أو لا يخصك']);
    exit;
}

// FIX: التحقق أن الحجز لا يزال pending قبل التعديل
if ($appointment['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'هذا الحجز تمت معالجته مسبقاً']);
    exit;
}

$patient_id       = (int) $appointment['patient_id'];
$doctor_full_name = $appointment['doctor_full_name'];

// ----- تنفيذ الإجراء -----
if ($action === 'confirm') {

    // FIX: تحديث صريح لكل الحقول مع التأكد من عدم حفظ NULL
    $stmtUpdate = $pdo->prepare("
        UPDATE appointments
        SET status           = 'confirmed',
            appointment_date = ?,
            appointment_time = ?
        WHERE id = ? AND status = 'pending'
    ");
    $updated = $stmtUpdate->execute([$appt_date, $appt_time, $appointment_id]);

    if (!$updated || $stmtUpdate->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'فشل حفظ الموعد، حاول مرة أخرى']);
        exit;
    }

    // تنسيق للعرض في الإشعار
    $display_date = date('d/m/Y', strtotime($appt_date));
    $display_time = $appt_time; // HH:MM

    // FIX: إشعار واضح للمريض يحتوي اسم الطبيب + التاريخ + الوقت
    $message = "📅 تم تأكيد موعدك\n🩺 مع د. {$doctor_full_name}\n🗓️ يوم {$display_date}\n🕐 على الساعة {$display_time}";

    $stmtNotif = $pdo->prepare("
        INSERT INTO notifications (user_id, message, is_read, created_at)
        VALUES (?, ?, 0, NOW())
    ");
    $stmtNotif->execute([$patient_id, $message]);

    echo json_encode([
        'success' => true,
        'message' => 'تم تأكيد الموعد وإرسال إشعار للمريض ✅',
        'date'    => $display_date,
        'time'    => $display_time
    ]);

} elseif ($action === 'reject') {

    $stmtUpdate = $pdo->prepare("
        UPDATE appointments
        SET status = 'cancelled'
        WHERE id = ? AND status = 'pending'
    ");
    $stmtUpdate->execute([$appointment_id]);

    $message = "❌ تم رفض طلب موعدك\n🩺 من د. {$doctor_full_name}\nيمكنك حجز موعد مع طبيب آخر.";

    $stmtNotif = $pdo->prepare("
        INSERT INTO notifications (user_id, message, is_read, created_at)
        VALUES (?, ?, 0, NOW())
    ");
    $stmtNotif->execute([$patient_id, $message]);

    echo json_encode(['success' => true, 'message' => 'تم رفض الطلب وإرسال إشعار للمريض']);
}
