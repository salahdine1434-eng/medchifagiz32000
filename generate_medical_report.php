<?php
/**
 * generate_medical_report.php
 * ════════════════════════════════════════════════════════════════
 *  وحدة التحكّم (Controller) لميزة «توليد التقارير الطبية بالذكاء الاصطناعي».
 *  • يتحقّق من الجلسة (طبيب فقط).
 *  • يوجّه الطلبات: list_patients | generate_report | save_report.
 *  • يربط: Repository (قراءة) + Prompt (هندسة) + GroqService (نقل).
 *  • يُرجع JSON نظيفاً دائماً.
 *
 *  هذا الملف هو نقطة النهاية (API) الوحيدة لهذه الميزة، ولا يلمس أي API آخر.
 * ════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';                          // اتصال PDO الحالي للمشروع
require_once __DIR__ . '/groq_report_service.php';
require_once __DIR__ . '/medical_report_prompt.php';
require_once __DIR__ . '/medical_report_repository.php';

/* ─────────────────────────── أدوات مساعدة ─────────────────────────── */

function respond(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $code = 400): void
{
    respond(['success' => false, 'message' => $message], $code);
}

/* ───────────────────────── التحقّق من الجلسة ───────────────────────── */

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    fail('غير مصرّح. يجب تسجيل الدخول كطبيب.', 401);
}

/* استخراج معرّف الطبيب بنفس منطق لوحة التحكّم (طبيب أو عضو عيادة) */
if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
    $doctorId = (int) ($_SESSION['staff_id'] ?? 0);
} else {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $doctorId = (int) ($stmt->fetchColumn() ?: 0);
}

if ($doctorId <= 0) {
    fail('تعذّر تحديد هوية الطبيب.', 403);
}

$repo   = new MedicalReportRepository($pdo);
$action = $_REQUEST['action'] ?? '';

/* ───────────────────────────── التوجيه ───────────────────────────── */

switch ($action) {

    /* ① جلب قائمة المرضى للقائمة المنسدلة */
    case 'list_patients':
        $patients = $repo->listPatients($doctorId);
        respond(['success' => true, 'patients' => $patients]);
        break;

    /* ② توليد التقرير عبر Groq */
    case 'generate_report':
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $notes    = trim((string) ($_POST['notes'] ?? ''));

        if ($recordId <= 0) {
            fail('يرجى اختيار مريض أولاً.');
        }

        // قراءة جميع بيانات المريض (مع تقييد ملكية الطبيب داخل الـ Repository)
        $data = $repo->buildPatientData($recordId, $doctorId);
        if ($data === null) {
            fail('السجل الطبي غير موجود أو لا يخصّ هذا الطبيب.', 404);
        }

        $service = new GroqReportService();
        if (!$service->isConfigured()) {
            fail('خدمة الذكاء الاصطناعي غير مُفعّلة بعد. يرجى ضبط مفتاح Groq.', 503);
        }

        $messages = [
            ['role' => 'system', 'content' => MedicalReportPrompt::systemPrompt()],
            ['role' => 'user',   'content' => MedicalReportPrompt::userPrompt($data, $notes)],
        ];

        $result = $service->complete($messages);
        if (!$result['success']) {
            fail($result['message'] ?? 'فشل توليد التقرير.', 502);
        }

        respond([
            'success'      => true,
            'report'       => $result['content'],
            'patient_name' => $repo->patientName($recordId, $doctorId),
            'model'        => $service->modelName(),
            'generated_at' => date('Y-m-d H:i'),
        ]);
        break;

    /* ③ حفظ التقرير المُولّد في جدول مخصّص لهذه الميزة */
    case 'save_report':
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $report   = trim((string) ($_POST['report'] ?? ''));
        $model    = trim((string) ($_POST['model'] ?? ''));

        if ($recordId <= 0 || $report === '') {
            fail('لا يوجد تقرير لحفظه.');
        }

        // تحقّق من ملكية الطبيب للسجل قبل الحفظ
        $name = $repo->patientName($recordId, $doctorId);
        if ($name === '' && $repo->buildPatientData($recordId, $doctorId) === null) {
            fail('السجل الطبي غير موجود أو لا يخصّ هذا الطبيب.', 404);
        }

        // إنشاء جدول الحفظ تلقائياً إن لم يكن موجوداً (هجرة كسولة — خاص بهذه الميزة فقط)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `ai_medical_reports` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `medical_record_id` INT(11) NOT NULL,
              `doctor_id` INT(11) NOT NULL,
              `patient_name` VARCHAR(255) DEFAULT NULL,
              `model` VARCHAR(100) DEFAULT NULL,
              `report_content` MEDIUMTEXT NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_doctor` (`doctor_id`),
              KEY `idx_record` (`medical_record_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $ins = $pdo->prepare("
            INSERT INTO ai_medical_reports
                (medical_record_id, doctor_id, patient_name, model, report_content)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([$recordId, $doctorId, $name, $model, $report]);

        respond(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'تم حفظ التقرير بنجاح.']);
        break;

    default:
        fail('إجراء غير معروف.', 400);
}
