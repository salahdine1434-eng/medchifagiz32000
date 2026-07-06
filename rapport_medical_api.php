<?php
/**
 * rapport_medical_api.php
 * ════════════════════════════════════════════════════════════════
 * API بسيط لحفظ وتحميل التقرير الطبي (Rapport Médical)
 *
 * الاستخدام في dr_dashboard.php:
 *   أضف في بداية الملف:
 *   require_once 'rapport_medical_api.php';
 *
 * أو استدعاءه مباشرة من JavaScript:
 *   window.RAPPORT_SAVE_URL = 'rapport_medical_api.php';
 *   window.RAPPORT_LOAD_URL = 'rapport_medical_api.php';
 *
 * متطلبات:
 *   - جدول `rapport_medical` في قاعدة البيانات (SQL أدناه)
 *   - متغير $pdo أو $conn موجود في السياق (يُعدَّل حسب مشروعك)
 * ════════════════════════════════════════════════════════════════
 */

/* ─── SQL لإنشاء الجدول (نفّذه مرة واحدة في phpMyAdmin) ───────
CREATE TABLE IF NOT EXISTS `rapport_medical` (
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `patient_id`      INT(11)      NOT NULL,
    `rapport_date`    DATE         DEFAULT NULL,
    `rapport_patient` VARCHAR(255) DEFAULT NULL,
    `rapport_age`     VARCHAR(50)  DEFAULT NULL,
    `rapport_doctor`  VARCHAR(255) DEFAULT NULL,
    `rapport_content` TEXT         DEFAULT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
─────────────────────────────────────────────────────────────── */

// ─── تأكد من أن هذا الملف لا يُنفَّذ مباشرةً إذا أُدرج كـ require
if (basename($_SERVER['PHP_SELF']) === 'rapport_medical_api.php') {
    handleRapportRequest();
    exit;
}

/**
 * الدالة الرئيسية — تُستدعى عند الطلب المباشر
 */
function handleRapportRequest(): void
{
    // ── رؤوس CORS وJSON ──────────────────────────────────────────
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    // ── حماية: التأكد من أن الطلب AJAX ──────────────────────────
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // ── قراءة الـ action ──────────────────────────────────────────
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // ── تحقق من الجلسة (يُعدَّل حسب نظام المصادقة لديك) ─────────
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // مثال: التحقق من تسجيل الدخول
    // if (empty($_SESSION['doctor_id'])) {
    //     jsonError('غير مصرح', 401);
    //     return;
    // }

    // ── الاتصال بقاعدة البيانات ───────────────────────────────────
    $pdo = getRapportPdo();
    if (!$pdo) {
        jsonError('تعذّر الاتصال بقاعدة البيانات');
        return;
    }

    // ── إنشاء الجدول تلقائياً إن لم يكن موجوداً ─────────────────
    ensureRapportTable($pdo);

    // ── توجيه الطلب ──────────────────────────────────────────────
    match ($action) {
        'save_rapport_medical' => saveRapport($pdo),
        'load_rapport_medical' => loadRapport($pdo),
        default                => jsonError('action غير معروف')
    };
}

/* ════════════════════════════════════════════════════════════════
   SAVE
════════════════════════════════════════════════════════════════ */
function saveRapport(PDO $pdo): void
{
    // ── التحقق من الحقول الإلزامية ────────────────────────────────
    $patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    if (!$patientId) {
        jsonError('patient_id مطلوب');
        return;
    }

    // ── تنظيف المدخلات ────────────────────────────────────────────
    $date    = sanitizeDate($_POST['rapport_date']    ?? '');
    $patient = sanitizeStr($_POST['rapport_patient']  ?? '', 255);
    $age     = sanitizeStr($_POST['rapport_age']      ?? '', 50);
    $doctor  = sanitizeStr($_POST['rapport_doctor']   ?? '', 255);
    $content = sanitizeText($_POST['rapport_content'] ?? '');

    // ── INSERT ... ON DUPLICATE KEY UPDATE ────────────────────────
    $sql = "
        INSERT INTO rapport_medical
            (patient_id, rapport_date, rapport_patient, rapport_age, rapport_doctor, rapport_content)
        VALUES
            (:pid, :rdate, :rpatient, :rage, :rdoctor, :rcontent)
        ON DUPLICATE KEY UPDATE
            rapport_date    = VALUES(rapport_date),
            rapport_patient = VALUES(rapport_patient),
            rapport_age     = VALUES(rapport_age),
            rapport_doctor  = VALUES(rapport_doctor),
            rapport_content = VALUES(rapport_content),
            updated_at      = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);
    $ok   = $stmt->execute([
        ':pid'      => $patientId,
        ':rdate'    => $date    ?: null,
        ':rpatient' => $patient ?: null,
        ':rage'     => $age     ?: null,
        ':rdoctor'  => $doctor  ?: null,
        ':rcontent' => $content ?: null,
    ]);

    if ($ok) {
        jsonSuccess([
            'message'    => 'تم حفظ التقرير بنجاح',
            'patient_id' => $patientId
        ]);
    } else {
        $errInfo = $stmt->errorInfo();
        error_log('[RapportMedical] Execute failed: ' . ($errInfo[2] ?? 'unknown'));
        jsonError('فشل الحفظ في قاعدة البيانات');
    }
}

/* ════════════════════════════════════════════════════════════════
   LOAD
════════════════════════════════════════════════════════════════ */
function loadRapport(PDO $pdo): void
{
    $patientId = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT)
              ?? filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);

    if (!$patientId) {
        jsonError('patient_id مطلوب');
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM rapport_medical WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        jsonSuccess(['data' => $row]);
    } else {
        // لا يوجد تقرير بعد — ليس خطأ
        jsonSuccess(['data' => null]);
    }
}

/* ════════════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════════════ */

/* ════════════════════════════════════════════════════════════════
   CREATE TABLE IF NOT EXISTS
════════════════════════════════════════════════════════════════ */
function ensureRapportTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `rapport_medical` (
            `id`              INT(11)      NOT NULL AUTO_INCREMENT,
            `patient_id`      INT(11)      NOT NULL,
            `rapport_date`    DATE         DEFAULT NULL,
            `rapport_patient` VARCHAR(255) DEFAULT NULL,
            `rapport_age`     VARCHAR(50)  DEFAULT NULL,
            `rapport_doctor`  VARCHAR(255) DEFAULT NULL,
            `rapport_content` TEXT         DEFAULT NULL,
            `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_patient` (`patient_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * إنشاء/إعادة استخدام اتصال PDO.
 * يبحث أولاً عن الـ PDO الموجود في السياق العام ($pdo أو $conn).
 * إذا لم يجد، يُنشئ اتصالاً جديداً بالإعدادات أدناه.
 */
function getRapportPdo(): ?PDO
{
    // ── محاولة الوصول لـ PDO موجود في السياق العام ────────────────
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }

    // ── إنشاء اتصال جديد (عدّل الإعدادات حسب مشروعك) ────────────
    // يمكنك تعريف هذه الثوابت في ملف config.php الخاص بك
    $host   = defined('DB_HOST')   ? DB_HOST   : 'localhost';
    $name   = defined('DB_NAME')   ? DB_NAME   : 'medchifagiz';
    $user   = defined('DB_USER')   ? DB_USER   : 'root';
    $pass   = defined('DB_PASS')   ? DB_PASS   : '';
    $charset= 'utf8mb4';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('[RapportMedical] DB Error: ' . $e->getMessage());
        return null;
    }
}

function sanitizeStr(string $val, int $maxLen = 255): string
{
    return mb_substr(trim(strip_tags($val)), 0, $maxLen);
}

function sanitizeText(string $val): string
{
    return trim(strip_tags($val));
}

function sanitizeDate(string $val): ?string
{
    if (!$val) return null;
    $d = DateTime::createFromFormat('Y-m-d', $val);
    return ($d && $d->format('Y-m-d') === $val) ? $val : null;
}

function jsonSuccess(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
}
