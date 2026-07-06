<?php
/* ================================================================
   view_medical_document.php
   يعرض وثيقة طبية موجودة (يقرأها من الجداول الحالية) كصفحة قابلة
   للطباعة/الحفظ — بلا نسخ ولا رفع. يُفتح من بطاقة المرفق في المحادثة.
   المدخلات: ?type=dossier|rapport|fiche|analyses|radiologie|ordonnance
             &record_id=INT
   الصلاحية: طبيب السجل أو مريضه فقط.
================================================================ */
session_start();
require 'db.php';

$type     = isset($_GET['type']) ? preg_replace('/[^a-z]/', '', $_GET['type']) : '';
$recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

function deny($msg) {
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:40px;text-align:center;color:#334155;">' . htmlspecialchars($msg) . '</body>';
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) deny('يجب تسجيل الدخول');
if ($recordId <= 0) deny('طلب غير صالح');

$stmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ?");
$stmt->execute([$recordId]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rec) deny('الوثيقة غير موجودة');

/* الصلاحية */
$ok = false;
if ($_SESSION['role'] === 'doctor') {
    $d = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $d->execute([$_SESSION['user_id']]);
    $doc = $d->fetch(PDO::FETCH_ASSOC);
    if ($doc && intval($doc['id']) === intval($rec['doctor_id'])) $ok = true;
} elseif ($_SESSION['role'] === 'patient') {
    if (intval($_SESSION['user_id']) === intval($rec['patient_id'])) $ok = true;
}
if (!$ok) deny('لا تملك صلاحية عرض هذه الوثيقة');

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function row($label, $val) {
    if ($val === null || trim((string)$val) === '') return '';
    return '<tr><th style="text-align:right;padding:8px 12px;background:#f1f5f9;width:210px;vertical-align:top;">' . e($label) . '</th>' .
           '<td style="padding:8px 12px;white-space:pre-wrap;">' . nl2br(e($val)) . '</td></tr>';
}

/* عنوان الوثيقة + محتواها حسب النوع */
$title = 'Document';
$body  = '';

if ($type === 'dossier') {
    $title = 'Dossier Médical';
    $body .= row('الاسم الكامل', $rec['full_name']);
    $body .= row('معلومات الميلاد', $rec['birth_info']);
    $body .= row('الجنس', $rec['gender']);
    $body .= row('الهاتف', $rec['phone']);
    $body .= row('العنوان', $rec['address']);
    $body .= row('سبب الفحص', $rec['reason_exam']);
    $body .= row('الأعراض', $rec['symptoms']);
    $body .= row('ضغط الدم', $rec['blood_pressure']);
    $body .= row('السكري', $rec['blood_sugar']);
    $body .= row('النبض', $rec['heart_rate']);
    $body .= row('الحرارة', $rec['temperature']);
    $body .= row('الأكسجين', $rec['oxygen_level']);
    $body .= row('أمراض مزمنة', $rec['chronic_patient']);
    $body .= row('أمراض عائلية', $rec['chronic_family']);

} elseif ($type === 'rapport') {
    $title = 'Rapport Médical';
    try {
        $s = $pdo->prepare("SELECT * FROM rapport_medical WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $s->execute([$recordId]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $body .= row('التاريخ', $r['rapport_date'] ?? null);
            $body .= row('المريض', $r['rapport_patient'] ?? null);
            $body .= row('السن', $r['rapport_age'] ?? null);
            $body .= row('الطبيب', $r['rapport_doctor'] ?? null);
            $body .= row('محتوى التقرير', $r['rapport_content'] ?? null);
        }
    } catch (PDOException $ex) {}

} elseif ($type === 'fiche') {
    $title = 'Fiche de traitement';
    try {
        $s = $pdo->prepare("SELECT * FROM fiche_traitement WHERE medical_record_id = ? ORDER BY id DESC LIMIT 1");
        $s->execute([$recordId]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $body .= row('التشخيص', $r['fiche_diagnostic'] ?? null);
            $body .= row('الأدوية والعلاجات', $r['fiche_medications'] ?? null);
        }
    } catch (PDOException $ex) {}

} elseif ($type === 'analyses') {
    $title = "Résultats d'analyses";
    $body .= row('التحاليل (الملف الطبي)', $rec['medical_tests']);
    try {
        $s = $pdo->prepare("SELECT analysis_text, status, created_at FROM lab_requests WHERE patient_id = ? ORDER BY id DESC");
        $s->execute([$recordId]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $i => $lr) {
            $body .= row('طلب تحاليل (' . e($lr['status']) . ')', $lr['analysis_text']);
        }
    } catch (PDOException $ex) {}

} elseif ($type === 'radiologie') {
    $title = 'Radiologie';
    $body .= row('الأشعة (الملف الطبي)', $rec['radiology']);
    try {
        $s = $pdo->prepare("SELECT radiology_text, status, created_at FROM radiology_requests WHERE patient_id = ? ORDER BY id DESC");
        $s->execute([$recordId]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $rr) {
            $body .= row('طلب أشعة (' . e($rr['status']) . ')', $rr['radiology_text']);
        }
    } catch (PDOException $ex) {}

} elseif ($type === 'ordonnance') {
    $title = 'Ordonnance';
    $body .= row('الوصفة', $rec['prescription']);

} else {
    deny('نوع وثيقة غير معروف');
}

if (trim($body) === '') {
    $body = '<tr><td style="padding:16px;color:#64748b;">لا يوجد محتوى في هذه الوثيقة.</td></tr>';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($title) ?> — <?= e($rec['full_name']) ?></title>
<style>
  body{font-family:'Cairo',Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0;padding:24px;}
  .doc{max-width:820px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 6px 24px rgba(15,23,42,.06);}
  .doc-head{background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:#fff;padding:20px 24px;}
  .doc-head h1{margin:0;font-size:1.2rem;}
  .doc-head p{margin:4px 0 0;font-size:.85rem;opacity:.9;}
  table{width:100%;border-collapse:collapse;font-size:.9rem;}
  td,th{border-bottom:1px solid #eef2f7;}
  .actions{padding:16px 24px;text-align:left;}
  .actions button{background:#0ea5e9;color:#fff;border:none;padding:9px 18px;border-radius:8px;cursor:pointer;font-family:inherit;font-size:.85rem;}
  @media print{.actions{display:none;} body{background:#fff;padding:0;} .doc{border:none;box-shadow:none;}}
</style>
</head>
<body>
  <div class="doc">
    <div class="doc-head">
      <h1><?= e($title) ?></h1>
      <p><?= e($rec['full_name']) ?><?= $rec['created_at'] ? ' • ' . e(date('d/m/Y', strtotime($rec['created_at']))) : '' ?></p>
    </div>
    <table><?= $body ?></table>
    <div class="actions">
      <button onclick="window.print()">🖨️ طباعة / حفظ PDF</button>
    </div>
  </div>
</body>
</html>
