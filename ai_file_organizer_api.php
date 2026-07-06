<?php
/**
 * ai_file_organizer_api.php
 * ════════════════════════════════════════════════════════════════
 *  واجهة AJAX (JSON) لميزة «تنظيم الملفات تلقائياً».
 *  • تعيد استخدام db.php  ($pdo) و GroqReportService الموجودَين مسبقاً.
 *  • لا تنشئ أي اتصال Groq جديد ولا أي إعدادات جديدة.
 *  • تعمل فقط على الملفات الطبية الخاصة بالطبيب الحالي (عزل البيانات).
 *
 *  الإجراءات (action):
 *    - list      : إرجاع التحليلات المحفوظة + الإحصائيات + عدد الملفات المعلّقة.
 *    - organize  : تحليل دفعة من الملفات الجديدة/المعدّلة عبر Groq (مع Cache).
 *                  تعيد {processed, remaining, done} لتكرارها من الواجهة.
 * ════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);
session_start();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

/* ── حارس الجلسة (نفس منطق dr_dashboard.php) ── */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'غير مصرّح بالوصول.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require __DIR__ . '/db.php';                      // يوفّر $pdo (PDO، يرمي استثناءات)
require_once __DIR__ . '/groq_report_service.php'; // يوفّر GroqReportService و groq_config

/* ── تحديد معرّف الطبيب (يدعم موظّف العيادة كما في الداشبورد) ── */
try {
    if (isset($_SESSION['is_clinic_staff']) && (int) $_SESSION['is_clinic_staff'] === 1) {
        $doctorId = (int) ($_SESSION['staff_id'] ?? 0);
    } else {
        $st = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $st->execute([$_SESSION['user_id']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $doctorId = $row ? (int) $row['id'] : 0;
    }
    if ($doctorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'تعذّر تحديد الطبيب.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    ensure_table($pdo);

    $action = $_POST['action'] ?? $_GET['action'] ?? 'list';

    switch ($action) {
        case 'organize':
            $batch = (int) ($_POST['batch'] ?? 4);
            if ($batch < 1)  { $batch = 1; }
            if ($batch > 8)  { $batch = 8; } // حدّ أعلى لتفادي مهلة PHP
            echo json_encode(action_organize($pdo, $doctorId, $batch), JSON_UNESCAPED_UNICODE);
            break;

        case 'list':
        default:
            echo json_encode(action_list($pdo, $doctorId), JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Throwable $e) {
    error_log('ai_file_organizer_api error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقّع أثناء المعالجة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ════════════════════════════════════════════════════════════════
 *  الإجراءات
 * ════════════════════════════════════════════════════════════════ */

/**
 * إرجاع التحليلات المحفوظة + الإحصائيات + عدد الملفات التي تحتاج تحليلاً.
 */
function action_list(PDO $pdo, int $doctorId): array
{
    $records  = fetch_doctor_records($pdo, $doctorId);
    $analyses = fetch_analyses($pdo, $doctorId);   // مفهرسة بـ medical_record_id

    $model   = (new GroqReportService())->modelName();
    $pending = 0;
    foreach ($records as $rec) {
        $hash = content_hash($rec, $model);
        $a    = $analyses[$rec['id']] ?? null;
        if (!$a || $a['status'] !== 'ok' || ($a['content_hash'] ?? '') !== $hash) {
            $pending++;
        }
    }

    $cards = [];
    foreach ($analyses as $a) {
        if ($a['status'] !== 'ok') { continue; }
        $cards[] = [
            'medical_record_id' => (int) $a['medical_record_id'],
            'patient_name'      => $a['patient_name'],
            'specialty'         => $a['specialty'],
            'specialty_ar'      => $a['specialty_ar'],
            'disease_category'  => $a['disease_category'],
            'priority'          => $a['priority'],
            'keywords'          => json_decode((string) $a['keywords'], true) ?: [],
            'summary'           => $a['summary'],
            'followup_required' => (int) $a['followup_required'] === 1,
            'followup'          => $a['followup'],
            'missing_info'      => json_decode((string) $a['missing_info'], true) ?: [],
            'is_incomplete'     => (int) $a['is_incomplete'] === 1,
            'suggested_path'    => $a['suggested_path'],
            'analyzed_at'       => $a['analyzed_at'],
        ];
    }

    // ترتيب: الأولوية العالية أولاً ثم الأحدث تحليلاً
    $rank = ['high' => 0, 'medium' => 1, 'low' => 2];
    usort($cards, function ($x, $y) use ($rank) {
        $rx = $rank[$x['priority']] ?? 1;
        $ry = $rank[$y['priority']] ?? 1;
        if ($rx !== $ry) { return $rx <=> $ry; }
        return strcmp((string) $y['analyzed_at'], (string) $x['analyzed_at']);
    });

    return [
        'success' => true,
        'stats'   => [
            'organized'     => count($cards),
            'high_priority' => count(array_filter($cards, fn($c) => $c['priority'] === 'high')),
            'incomplete'    => count(array_filter($cards, fn($c) => $c['is_incomplete'])),
            'followup'      => count(array_filter($cards, fn($c) => $c['followup_required'])),
            'total'         => count($records),
            'pending'       => $pending,
        ],
        'files'   => $cards,
        'model'   => $model,
    ];
}

/**
 * تحليل دفعة من الملفات التي تحتاج تحليلاً (جديدة أو معدّلة) عبر Groq.
 */
function action_organize(PDO $pdo, int $doctorId, int $batch): array
{
    $service = new GroqReportService();
    if (!$service->isConfigured()) {
        return ['success' => false, 'message' => 'مفتاح Groq غير مُعدّ على الخادم.'];
    }
    $model = $service->modelName();

    $records  = fetch_doctor_records($pdo, $doctorId);
    $analyses = fetch_analyses($pdo, $doctorId);

    // تحديد الملفات المعلّقة (لا تحليل لها، أو فشل سابق، أو تغيّر محتواها)
    $pendingList = [];
    foreach ($records as $rec) {
        $hash = content_hash($rec, $model);
        $a    = $analyses[$rec['id']] ?? null;
        if (!$a || $a['status'] !== 'ok' || ($a['content_hash'] ?? '') !== $hash) {
            $pendingList[] = $rec;
        }
    }

    $remainingBefore = count($pendingList);
    $toProcess       = array_slice($pendingList, 0, $batch);
    $processed       = 0;
    $errors          = 0;

    foreach ($toProcess as $rec) {
        $res = analyze_and_store($pdo, $service, $model, $doctorId, $rec);
        $res ? $processed++ : $errors++;
    }

    $remaining = max(0, $remainingBefore - count($toProcess));

    return [
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors,
        'remaining' => $remaining,
        'done'      => $remaining === 0,
    ];
}

/* ════════════════════════════════════════════════════════════════
 *  منطق التحليل والتخزين
 * ════════════════════════════════════════════════════════════════ */

/**
 * يحلّل ملفاً واحداً عبر Groq ثم يخزّن/يحدّث النتيجة (Upsert).
 * @return bool نجاح التحليل والتخزين.
 */
function analyze_and_store(PDO $pdo, GroqReportService $service, string $model, int $doctorId, array $rec): bool
{
    $text = build_record_text($rec);
    $hash = content_hash($rec, $model);
    $name = resolve_patient_name($rec);

    // ملف فارغ تقريباً → نخزّن حالة «غير مكتمل» دون استدعاء النموذج (توفير).
    if (trim($text) === '') {
        store_result($pdo, $doctorId, $rec, $name, $hash, $model, 'ok', null, [
            'specialty'         => null,
            'specialty_ar'      => null,
            'disease_category'  => null,
            'priority'          => 'low',
            'keywords'          => [],
            'summary'           => 'الملف الطبي فارغ تقريباً ولا يحتوي على بيانات سريرية كافية للتحليل.',
            'followup_required' => false,
            'followup'          => null,
            'missing_info'      => ['لا توجد بيانات سريرية في الملف'],
            'suggested_path'    => 'Archive / Incomplete',
        ], '');
        return true;
    }

    $messages = [
        ['role' => 'system', 'content' => system_prompt()],
        ['role' => 'user',   'content' => "حلّل الملف الطبي التالي وأعد JSON فقط حسب المخطط المطلوب:\n\n" . $text],
    ];

    $resp = $service->complete($messages);
    if (!$resp['success']) {
        store_result($pdo, $doctorId, $rec, $name, $hash, $model, 'error', $resp['message'] ?? 'فشل الاتصال', null, (string) ($resp['content'] ?? ''));
        return false;
    }

    $parsed = parse_ai_json($resp['content']);
    if ($parsed === null) {
        store_result($pdo, $doctorId, $rec, $name, $hash, $model, 'error', 'تعذّر تحليل استجابة النموذج.', null, (string) $resp['content']);
        return false;
    }

    store_result($pdo, $doctorId, $rec, $name, $hash, $model, 'ok', null, $parsed, (string) $resp['content']);
    return true;
}

/**
 * يخزّن نتيجة التحليل في ai_file_organization عبر Upsert (Prepared Statement).
 */
function store_result(PDO $pdo, int $doctorId, array $rec, ?string $name, string $hash, string $model, string $status, ?string $errMsg, ?array $data, string $raw): void
{
    $priority      = normalize_priority($data['priority'] ?? 'medium');
    $missing       = is_array($data['missing_info'] ?? null) ? array_values(array_filter($data['missing_info'])) : [];
    $keywords      = is_array($data['keywords'] ?? null) ? array_values(array_filter($data['keywords'])) : [];
    $followupReq   = !empty($data['followup_required']);
    $isIncomplete  = !empty($missing);
    $suggestedPath = $data['suggested_path'] ?? null;
    if (is_array($suggestedPath)) {
        $suggestedPath = implode(' / ', array_filter($suggestedPath));
    }

    $sql = "INSERT INTO ai_file_organization
              (medical_record_id, doctor_id, patient_name, specialty, specialty_ar,
               disease_category, priority, keywords, summary, followup_required,
               followup, missing_info, is_incomplete, suggested_path, raw_json,
               model, content_hash, record_updated_at, status, error_message, analyzed_at)
            VALUES
              (:rid, :did, :name, :spec, :spec_ar, :cat, :prio, :kw, :sum, :fr,
               :fu, :mi, :inc, :path, :raw, :model, :hash, :rua, :status, :err, NOW())
            ON DUPLICATE KEY UPDATE
               doctor_id        = VALUES(doctor_id),
               patient_name     = VALUES(patient_name),
               specialty        = VALUES(specialty),
               specialty_ar     = VALUES(specialty_ar),
               disease_category = VALUES(disease_category),
               priority         = VALUES(priority),
               keywords         = VALUES(keywords),
               summary          = VALUES(summary),
               followup_required= VALUES(followup_required),
               followup         = VALUES(followup),
               missing_info     = VALUES(missing_info),
               is_incomplete    = VALUES(is_incomplete),
               suggested_path   = VALUES(suggested_path),
               raw_json         = VALUES(raw_json),
               model            = VALUES(model),
               content_hash     = VALUES(content_hash),
               record_updated_at= VALUES(record_updated_at),
               status           = VALUES(status),
               error_message    = VALUES(error_message),
               analyzed_at      = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':rid'    => (int) $rec['id'],
        ':did'    => $doctorId,
        ':name'   => $name,
        ':spec'   => $data['specialty']        ?? null,
        ':spec_ar'=> $data['specialty_ar']     ?? null,
        ':cat'    => $data['disease_category'] ?? null,
        ':prio'   => $priority,
        ':kw'     => json_encode($keywords, JSON_UNESCAPED_UNICODE),
        ':sum'    => $data['summary']          ?? null,
        ':fr'     => $followupReq ? 1 : 0,
        ':fu'     => $data['followup']         ?? null,
        ':mi'     => json_encode($missing, JSON_UNESCAPED_UNICODE),
        ':inc'    => $isIncomplete ? 1 : 0,
        ':path'   => $suggestedPath,
        ':raw'    => $raw !== '' ? $raw : null,
        ':model'  => $model,
        ':hash'   => $hash,
        ':rua'    => $rec['updated_at'] ?? null,
        ':status' => $status,
        ':err'    => $errMsg,
    ]);
}

/* ════════════════════════════════════════════════════════════════
 *  جلب البيانات
 * ════════════════════════════════════════════════════════════════ */

/** جلب كل الملفات الطبية الخاصة بالطبيب مع اسم المريض. */
function fetch_doctor_records(PDO $pdo, int $doctorId): array
{
    $sql = "SELECT mr.*, p.first_name, p.last_name, u.full_name AS user_full_name
            FROM medical_records mr
            LEFT JOIN patients p ON p.id = mr.patient_id
            LEFT JOIN users    u ON u.id = p.user_id
            WHERE mr.doctor_id = ?
            ORDER BY mr.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$doctorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** جلب التحليلات المحفوظة مفهرسةً بـ medical_record_id. */
function fetch_analyses(PDO $pdo, int $doctorId): array
{
    $stmt = $pdo->prepare("SELECT * FROM ai_file_organization WHERE doctor_id = ?");
    $stmt->execute([$doctorId]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(int) $row['medical_record_id']] = $row;
    }
    return $out;
}

/* ════════════════════════════════════════════════════════════════
 *  أدوات مساعدة
 * ════════════════════════════════════════════════════════════════ */

/** تعليمات النظام للنموذج: إجبارُه على إخراج JSON صارم فقط. */
function system_prompt(): string
{
    return <<<PROMPT
أنت نظام طبّي متخصّص في تصنيف وتنظيم الملفات الطبية بدقة. مهمتك تحليل الملف الطبي وإرجاع كائن JSON واحد فقط، دون أي نص أو شرح أو علامات Markdown خارج الـ JSON.

أعِد بالضبط هذا المخطط:
{
  "specialty": "اسم التخصص الطبي المناسب بالإنجليزية (مثل Cardiology)",
  "specialty_ar": "نفس التخصص بالعربية (مثل أمراض القلب)",
  "disease_category": "فئة المرض بالإنجليزية (مثل Heart Disease)",
  "priority": "High أو Medium أو Low",
  "keywords": ["3 إلى 6 كلمات مفتاحية طبية قصيرة"],
  "summary": "ملخص طبي احترافي موجز بالعربية (جملتان إلى أربع جمل)",
  "followup_required": true أو false,
  "followup": "توصية أو موعد المتابعة بالعربية، أو null إن لم تلزم متابعة",
  "missing_info": ["قائمة بالمعلومات السريرية الناقصة، فارغة [] إن كان الملف مكتملاً"],
  "suggested_path": ["Archive", "التخصص بالإنجليزية", "مستوى الأولوية + Priority"]
}

قواعد صارمة:
- اعتمد فقط على البيانات الموجودة في الملف، ولا تخترع معلومات.
- priority يجب أن تكون واحدة من: High أو Medium أو Low فقط.
- لا تُخرِج أي شيء غير كائن JSON صالح واحد.
PROMPT;
}

/** تحويل سجل الملف الطبي إلى نص منظّم يُرسل للنموذج (الحقول غير الفارغة فقط). */
function build_record_text(array $r): string
{
    $labels = [
        'reason_exam'     => 'سبب الفحص',
        'symptoms'        => 'الأعراض',
        'blood_pressure'  => 'ضغط الدم',
        'blood_sugar'     => 'سكر الدم',
        'heart_rate'      => 'نبض القلب',
        'temperature'     => 'الحرارة',
        'oxygen_level'    => 'الأكسجين',
        'chronic_patient' => 'أمراض مزمنة (المريض)',
        'chronic_family'  => 'أمراض مزمنة (العائلة)',
        'medical_tests'   => 'التحاليل الطبية',
        'radiology'       => 'الأشعة',
        'prescription'    => 'الوصفة الطبية',
        'next_appointment'=> 'الموعد القادم',
        'followup_notes'  => 'ملاحظات المتابعة',
        'gender'          => 'الجنس',
        'birth_info'      => 'معلومات الميلاد',
        'job'             => 'المهنة',
        // حقول الحمل (إن وُجدت)
        'preg_chronic_diseases' => 'أمراض مزمنة (حمل)',
        'fetal_heartbeat' => 'نبض الجنين',
        'fetal_movement'  => 'حركة الجنين',
        'fetal_weight'    => 'وزن الجنين',
        'fetal_position'  => 'وضعية الجنين',
        'echo_notes'      => 'ملاحظات الإيكو',
        'pregnancy_notes' => 'ملاحظات الحمل',
    ];

    $lines = [];
    foreach ($labels as $col => $label) {
        $val = trim((string) ($r[$col] ?? ''));
        if ($val !== '') {
            $lines[] = $label . ': ' . $val;
        }
    }
    return implode("\n", $lines);
}

/** بصمة محتوى الملف (لاكتشاف التغيير). تتغيّر إذا تغيّر المحتوى أو النموذج. */
function content_hash(array $rec, string $model): string
{
    return hash('sha256', $model . '|' . build_record_text($rec));
}

/** اسم المريض المعروض: full_name ثم اسم المريض ثم اسم المستخدم. */
function resolve_patient_name(array $r): string
{
    $full = trim((string) ($r['full_name'] ?? ''));
    if ($full !== '') { return $full; }

    $name = trim(((string) ($r['first_name'] ?? '')) . ' ' . ((string) ($r['last_name'] ?? '')));
    if ($name !== '') { return $name; }

    $u = trim((string) ($r['user_full_name'] ?? ''));
    return $u !== '' ? $u : 'مريض غير محدّد';
}

/** تطبيع الأولوية إلى high/medium/low. */
function normalize_priority($p): string
{
    $p = strtolower(trim((string) $p));
    if (in_array($p, ['high', 'عالية', 'عالٍ', 'urgent', 'عاجل'], true))  { return 'high'; }
    if (in_array($p, ['low', 'منخفضة', 'منخفض'], true))                    { return 'low'; }
    return 'medium';
}

/**
 * استخراج وتحليل JSON من استجابة النموذج بشكل دفاعي.
 * يتعامل مع وجود نص أو علامات ```json حول الـ JSON.
 */
function parse_ai_json(string $content): ?array
{
    $content = trim($content);

    // إزالة أسوار الكود إن وُجدت
    $content = preg_replace('/^```(?:json)?/i', '', $content);
    $content = preg_replace('/```$/', '', trim($content));

    $start = strpos($content, '{');
    $end   = strrpos($content, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    $json = substr($content, $start, $end - $start + 1);

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

/** إنشاء جدول الميزة تلقائياً إن لم يكن موجوداً (آمن للتكرار). */
function ensure_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ai_file_organization` (
          `id`                INT(11)      NOT NULL AUTO_INCREMENT,
          `medical_record_id` INT(11)      NOT NULL,
          `doctor_id`         INT(11)      NOT NULL,
          `patient_name`      VARCHAR(255) DEFAULT NULL,
          `specialty`         VARCHAR(150) DEFAULT NULL,
          `specialty_ar`      VARCHAR(150) DEFAULT NULL,
          `disease_category`  VARCHAR(150) DEFAULT NULL,
          `priority`          ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
          `keywords`          TEXT         DEFAULT NULL,
          `summary`           MEDIUMTEXT   DEFAULT NULL,
          `followup_required` TINYINT(1)   NOT NULL DEFAULT 0,
          `followup`          TEXT         DEFAULT NULL,
          `missing_info`      TEXT         DEFAULT NULL,
          `is_incomplete`     TINYINT(1)   NOT NULL DEFAULT 0,
          `suggested_path`    VARCHAR(255) DEFAULT NULL,
          `raw_json`          MEDIUMTEXT   DEFAULT NULL,
          `model`             VARCHAR(100) DEFAULT NULL,
          `content_hash`      CHAR(64)     DEFAULT NULL,
          `record_updated_at` TIMESTAMP    NULL DEFAULT NULL,
          `status`            ENUM('ok','error') NOT NULL DEFAULT 'ok',
          `error_message`     VARCHAR(255) DEFAULT NULL,
          `analyzed_at`       TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
          `created_at`        TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
          `updated_at`        TIMESTAMP    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_record`  (`medical_record_id`),
          KEY        `idx_doctor`   (`doctor_id`),
          KEY        `idx_priority` (`priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
