<?php
/**
 * get_consultations.php
 * ---------------------------------------------------------------------------
 * قائمة الاستشارات (قراءة فقط) — المرسلة + الواردة.
 *
 *  - المرسلة (sent)  : أنشأها المستخدم الحالي (created_by = me).
 *  - الواردة (inbox) : المستخدم الحالي هو الطبيب المختار (assigned_doctor = me)
 *                      أو طبيب مشارك (consultation_participants) في الحالة.
 *
 * لكل استشارة: تسميات عربية، اسم الطبيب المعروض (المستشار للمرسلة،
 * المُرسِل للواردة)، ووسم الصندوق box=sent|inbox.
 * لا يكتب أي شيء في قاعدة البيانات (SELECT فقط).
 *
 * تحديث "إضافة طبيب مشارك": أصبحت الاستشارة تظهر ضمن "الواردة" أيضاً لأي
 * طبيب أُضيف كمشارك (consultation_participants)، وليس فقط للطبيب الرئيسي.
 * ---------------------------------------------------------------------------
 */

session_start();
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

/* ===== هوية المستخدم الحالي كطبيب ===== */
$is_clinic_staff = !empty($_SESSION['is_clinic_staff']);
$my_id        = intval($is_clinic_staff ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['user_id'] ?? 0));
$my_type      = $is_clinic_staff ? 'clinic_staff' : 'private';
$my_clinic_id = intval($_SESSION['clinic_id'] ?? 0);

if ($my_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== ضمان وجود جدول المشاركين (يُنشأ أصلاً في add_consultation_participant.php،
   هذا فقط لضمان عدم فشل هذا الاستعلام لو فُتحت القائمة قبل أي إضافة) ===== */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `consultation_participants` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `consultation_case_id` INT(11) NOT NULL,
          `doctor_id` INT(11) NOT NULL,
          `doctor_type` ENUM('clinic_staff','private') NOT NULL DEFAULT 'clinic_staff',
          `added_by` INT(11) NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_case_doctor` (`consultation_case_id`,`doctor_id`,`doctor_type`),
          KEY `idx_case` (`consultation_case_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    error_log("ensure consultation_participants table failed: " . $e->getMessage());
}

/* ===== الحالات التي أنا مشارك فيها (طبيب مشارك، وليس بالضرورة منشئاً أو رئيسياً) ===== */
$participant_case_ids = [];
try {
    $pstmt = $pdo->prepare("SELECT consultation_case_id FROM consultation_participants WHERE doctor_id = ? AND doctor_type = ?");
    $pstmt->execute([$my_id, $my_type]);
    $participant_case_ids = array_map('intval', array_column($pstmt->fetchAll(PDO::FETCH_ASSOC), 'consultation_case_id'));
} catch (PDOException $e) {
    error_log("get_consultations participant lookup failed: " . $e->getMessage());
}
$participant_ids_sql = !empty($participant_case_ids) ? implode(',', $participant_case_ids) : '';

/* ===========================
   جلب كل استشارة يكون فيها المستخدم إمّا المُنشئ، أو الطبيب المختار،
   أو طبيب مشارك (consultation_participants)
=========================== */
$sql = "
    SELECT
        cc.id, cc.case_number, cc.consultation_scope, cc.consultation_type,
        cc.title, cc.description, cc.priority, cc.status, cc.hide_patient_identity,
        cc.created_by, cc.clinic_id, cc.assigned_doctor_id, cc.assigned_doctor_type,
        cc.created_at, cc.updated_at, cc.closed_at,
        COALESCE(cs.full_name, du.full_name)   AS assigned_doctor_name,
        COALESCE(crs.full_name, cru.full_name) AS creator_name,
        pu.full_name AS patient_name
    FROM consultation_cases cc
    LEFT JOIN clinic_staff cs  ON cs.id  = cc.assigned_doctor_id AND cc.assigned_doctor_type = 'clinic_staff'
    LEFT JOIN users du         ON du.id  = cc.assigned_doctor_id AND cc.assigned_doctor_type = 'private'
    LEFT JOIN clinic_staff crs ON crs.id = cc.created_by
    LEFT JOIN users cru        ON cru.id = cc.created_by
    LEFT JOIN users pu         ON pu.id  = cc.patient_id AND cc.patient_id > 0
    WHERE cc.created_by = ?
       OR (cc.assigned_doctor_id = ? AND cc.assigned_doctor_type = ?)
       " . ($participant_ids_sql !== '' ? "OR cc.id IN ($participant_ids_sql)" : "") . "
    ORDER BY cc.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_id, $my_id, $my_type]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("get_consultations failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'تعذّر جلب الاستشارات'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== تسميات عربية ===== */
$type_labels = [
    'medical_opinion' => 'رأي طبي', 'urgent_opinion' => 'رأي عاجل',
    'case_discussion' => 'مناقشة حالة', 'patient_transfer' => 'طلب تحويل مريض',
    'radiology_review' => 'طلب تفسير أشعة', 'lab_review' => 'طلب تفسير تحاليل',
    'follow_up' => 'متابعة حالة',
];
$priority_labels = ['normal' => 'عادية', 'urgent' => 'مستعجلة', 'critical' => 'عاجلة جداً'];
$status_labels   = ['new' => 'جديدة', 'in_review' => 'قيد المراجعة', 'answered' => 'تم الرد', 'closed' => 'مغلقة'];
$scope_labels    = ['internal' => 'داخلية', 'external' => 'خارجية'];

/* ===== تصنيف كل استشارة إلى مرسلة/واردة/مغلقة (حسب status الحقيقي فقط) ===== */
$sent   = [];
$inbox  = [];
$closed = ['internal' => [], 'external' => []];

foreach ($rows as $r) {
    $hidden = intval($r['hide_patient_identity']) === 1;
    $patient_display = null;
    if (!$hidden) {
        $patient_display = ($r['patient_name'] !== null && trim($r['patient_name']) !== '')
            ? $r['patient_name'] : null;
    }

    $is_sent = (intval($r['created_by']) === $my_id)
        && (!$is_clinic_staff || intval($r['clinic_id']) === $my_clinic_id);

    $is_participant_row = in_array(intval($r['id']), $participant_case_ids, true);

    $is_inbox = ((($r['assigned_doctor_type'] === $my_type) && (intval($r['assigned_doctor_id']) === $my_id))
                 || $is_participant_row)
        && !$is_sent;

    if (!$is_sent && !$is_inbox) continue;

    $box = $is_sent ? 'sent' : 'inbox';

    $item = [
        'id'                    => intval($r['id']),
        'case_number'           => $r['case_number'],
        'box'                   => $box,
        'scope'                 => $r['consultation_scope'],
        'scope_label'           => $scope_labels[$r['consultation_scope']] ?? $r['consultation_scope'],
        'type'                  => $r['consultation_type'],
        'type_label'            => $type_labels[$r['consultation_type']] ?? $r['consultation_type'],
        'title'                 => $r['title'],
        'description'           => $r['description'],
        'priority'              => $r['priority'],
        'priority_label'        => $priority_labels[$r['priority']] ?? $r['priority'],
        'status'                => $r['status'],
        'status_label'          => $status_labels[$r['status']] ?? $r['status'],
        'hide_patient_identity' => $hidden ? 1 : 0,
        'patient_name'          => $patient_display,
        'assigned_doctor_name'  => $r['assigned_doctor_name'],
        'creator_name'          => $r['creator_name'],
        'display_doctor_name'   => $is_sent ? $r['assigned_doctor_name'] : $r['creator_name'],
        'created_at'            => $r['created_at'],
        'updated_at'            => $r['updated_at'],
        'closed_at'             => $r['closed_at'],
    ];

    /* الحالة المغلقة تخرج من الواردة/المرسلة وتُصنَّف حسب النطاق فقط (بدون حذف أي بيانات) */
    if ($r['status'] === 'closed') {
        $scope_key = ($r['consultation_scope'] === 'external') ? 'external' : 'internal';
        $closed[$scope_key][] = $item;
        continue;
    }

    if ($is_sent) $sent[] = $item; else $inbox[] = $item;
}

function cnsScopeCounts($arr) {
    $c = ['total' => count($arr), 'internal' => 0, 'external' => 0];
    foreach ($arr as $x) { if (isset($c[$x['scope']])) $c[$x['scope']]++; }
    return $c;
}

$closed_all = array_merge($closed['internal'], $closed['external']);

echo json_encode([
    'success'       => true,
    'consultations' => $sent,   // توافق سابق
    'sent'          => $sent,
    'inbox'         => $inbox,
    'closed'        => $closed_all,
    'closed_internal' => $closed['internal'],
    'closed_external' => $closed['external'],
    'counts'        => [
        'sent'   => cnsScopeCounts($sent),
        'inbox'  => cnsScopeCounts($inbox),
        'closed' => [
            'total'    => count($closed_all),
            'internal' => count($closed['internal']),
            'external' => count($closed['external']),
        ],
    ],
], JSON_UNESCAPED_UNICODE);
exit;
