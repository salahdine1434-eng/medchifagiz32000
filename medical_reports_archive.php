<?php
/**
 * medical_reports_archive.php
 * ════════════════════════════════════════════════════════════════
 *  نقطة النهاية (API) لميزة «أرشيف التقارير الطبية».
 *  قسم مستقل تماماً عن «أرشيف المرضى» — يقرأ فقط من جدول ai_medical_reports.
 *
 *  الإجراءات:
 *   • list   : قائمة تقارير الطبيب (اسم المريض/الطبيب/التاريخ/النموذج) — الأحدث أولاً.
 *   • get    : جلب تقرير واحد كاملاً (للعرض في Modal).
 *   • delete : حذف تقرير يخصّ هذا الطبيب فقط.
 *
 *  لا يعدّل أي جدول، ولا يلمس أرشيف المرضى ولا نظام التوليد.
 * ════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

/* ─────────────────────────── أدوات ─────────────────────────── */

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function err(string $message, int $code = 400): void
{
    out(['success' => false, 'message' => $message], $code);
}

/* ───────────────────── التحقّق من الجلسة ───────────────────── */

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    err('غير مصرّح. يجب تسجيل الدخول كطبيب.', 401);
}

/* معرّف الطبيب بنفس منطق لوحة التحكّم */
if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
    $doctorId = (int) ($_SESSION['staff_id'] ?? 0);
} else {
    $st = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $st->execute([$_SESSION['user_id']]);
    $doctorId = (int) ($st->fetchColumn() ?: 0);
}
if ($doctorId <= 0) {
    err('تعذّر تحديد هوية الطبيب.', 403);
}

/* اسم الطبيب الحالي (احتياطي للعرض إذا لم يُطابق الربط) */
$stName = $pdo->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
$stName->execute([$_SESSION['user_id']]);
$currentDoctorName = (string) ($stName->fetchColumn() ?: '');

/**
 * التأكّد من وجود جدول الأرشيف. إن لم يوجد (لم يُحفظ أي تقرير بعد)
 * نعتبر الأرشيف فارغاً بدل إظهار خطأ.
 */
function archiveTableExists(PDO $pdo): bool
{
    try {
        $pdo->query("SELECT 1 FROM ai_medical_reports LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

$action = $_REQUEST['action'] ?? 'list';

/* ───────────────────────────── التوجيه ───────────────────────────── */

switch ($action) {

    /* ① قائمة التقارير (بدون محتوى التقرير) — الأحدث أولاً */
    case 'list':
        if (!archiveTableExists($pdo)) {
            out(['success' => true, 'reports' => []]);
        }

        $q      = trim((string) ($_REQUEST['q'] ?? ''));
        $params = [$doctorId];
        $where  = "r.doctor_id = ?";

        if ($q !== '') {
            $where   .= " AND r.patient_name LIKE ?";
            $params[] = '%' . $q . '%';
        }

        $sql = "
            SELECT r.id, r.patient_name, r.model, r.created_at,
                   COALESCE(NULLIF(u.full_name, ''), ?) AS doctor_name
            FROM ai_medical_reports r
            LEFT JOIN doctors d ON d.id = r.doctor_id
            LEFT JOIN users   u ON u.id = d.user_id
            WHERE $where
            ORDER BY r.created_at DESC, r.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        // أوّل علامة استفهام هي اسم الطبيب الاحتياطي
        $stmt->execute(array_merge([$currentDoctorName], $params));
        out(['success' => true, 'reports' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    /* ② جلب تقرير واحد كاملاً */
    case 'get':
        if (!archiveTableExists($pdo)) {
            err('التقرير غير موجود.', 404);
        }
        $id = (int) ($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            err('معرّف التقرير غير صالح.');
        }

        $stmt = $pdo->prepare("
            SELECT r.id, r.patient_name, r.model, r.report_content, r.created_at,
                   COALESCE(NULLIF(u.full_name, ''), ?) AS doctor_name
            FROM ai_medical_reports r
            LEFT JOIN doctors d ON d.id = r.doctor_id
            LEFT JOIN users   u ON u.id = d.user_id
            WHERE r.id = ? AND r.doctor_id = ?
            LIMIT 1
        ");
        $stmt->execute([$currentDoctorName, $id, $doctorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            err('التقرير غير موجود أو لا يخصّ هذا الطبيب.', 404);
        }
        out(['success' => true, 'report' => $row]);
        break;

    /* ③ حذف تقرير (مع تقييد الملكية) */
    case 'delete':
        if (!archiveTableExists($pdo)) {
            err('التقرير غير موجود.', 404);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            err('معرّف التقرير غير صالح.');
        }

        $stmt = $pdo->prepare("DELETE FROM ai_medical_reports WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$id, $doctorId]);

        if ($stmt->rowCount() === 0) {
            err('لم يتم العثور على التقرير أو لا يخصّ هذا الطبيب.', 404);
        }
        out(['success' => true, 'message' => 'تم حذف التقرير.']);
        break;

    default:
        err('إجراء غير معروف.', 400);
}
