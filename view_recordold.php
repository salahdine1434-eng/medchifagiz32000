
<?php

require 'db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT * FROM medical_records
    WHERE id = ?
");

$stmt->execute([$id]);

$record = $stmt->fetch(PDO::FETCH_ASSOC);

/* ── جلب فيش العلاجات ── */
$ficheData = null;
try {
    $stmtFiche = $pdo->prepare("SELECT * FROM fiche_traitement WHERE medical_record_id = ? LIMIT 1");
    $stmtFiche->execute([$id]);
    $ficheData = $stmtFiche->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* الجدول غير موجود بعد */ }

/* ── جلب التقرير الطبي ── */
$rapportData = null;
try {
    $stmtRapport = $pdo->prepare("SELECT * FROM rapport_medical WHERE patient_id = ? LIMIT 1");
    $stmtRapport->execute([$id]);
    $rapportData = $stmtRapport->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* الجدول غير موجود بعد */ }


/* تحويل أسماء الحقول للعربية */

$labels = [

    'full_name' => 'الاسم الكامل',
    'birth_info' => 'معلومات الميلاد',
    'birth_place' => 'مكان الميلاد',
    'birth_date' => 'تاريخ الميلاد',
    'age' => 'العمر',
    'gender' => 'الجنس',
    'marital_status' => 'الحالة العائلية',
    'job' => 'العمل',
    'address' => 'العنوان',
    'phone' => 'الهاتف',
    'entry_date' => 'تاريخ الدخول',
    'room_number' => 'رقم الغرفة',

    'reason_exam' => 'سبب الفحص',
    'reason_visit' => 'سبب الزيارة',
    'symptoms' => 'الأعراض',

    'blood_pressure' => 'ضغط الدم',
    'blood_sugar' => 'السكر',
    'heart_rate' => 'نبضات القلب',
    'temperature' => 'درجة الحرارة',
    'oxygen_level' => 'نسبة الأكسجين',

    'chronic_patient' => 'الأمراض المزمنة',
    'chronic_family' => 'الأمراض المزمنة العائلية',
    'genetic_diseases' => 'أمراض وراثية',

    'pregnancy_follow' => 'متابعة الحمل',
    'last_period' => 'آخر دورة',
    'expected_birth' => 'تاريخ الولادة المتوقع',
    'blood_type' => 'فصيلة الدم',
    'pregnancy_count' => 'عدد مرات الحمل',
    'birth_count' => 'عدد الولات',
    'abortions' => 'الإجهاضات',
    'cesarean' => 'قيصرية سابقة',
    'father_status' => 'حالة الأب',
    'fetus_position' => 'وضعية الجنين',
    'fetus_move' => 'حركة الجنين',
    'fetus_weight' => 'وزن الجنين',

    'medical_tests' => 'التحاليل الطبية',
    'radiology' => 'الأشعة',

    'diagnostic' => 'التشخيص',
    'medications' => 'الأدوية والعلاجات',

    'prescription' => 'الوصفة الطبية',

    'medical_report' => 'التقرير الطبي',

    'residency_status'       => 'الحالة',
    'admission_date'          => 'تاريخ الدخول',
    'next_appointment'        => 'الموعد القادم',
    'next_appointment_date'   => 'تاريخ الموعد القادم',
    'next_appointment_time'   => 'وقت الموعد القادم',
    'appointment_time'        => 'وقت الموعد',

    'doctor_notes' => 'ملاحظات الطبيب',
    'general_notes' => 'ملاحظات عامة'

];

?>
<?php
/* تحويل yes/no إلى نعم/لا للعرض */
function _translateValue($v) {
    $v = trim($v);
    if (strtolower($v) === 'yes') return 'نعم';
    if (strtolower($v) === 'no')  return 'لا';
    return $v;
}
?>

<?php
/* ── تجميع الحقول في مجموعات منطقية ── */
$groups = [
    'personal' => [
        'icon'   => 'fa-user',
        'label'  => 'المعلومات الشخصية',
        'color'  => '#0ea5e9',
        'fields' => ['full_name','birth_date','birth_place','birth_info','age','gender','marital_status','job','address','phone','residency_status'],
    ],
    'admission' => [
        'icon'   => 'fa-hospital',
        'label'  => 'معلومات الدخول',
        'color'  => '#8b5cf6',
        'fields' => ['entry_date','room_number'],
    ],
    'visit' => [
        'icon'   => 'fa-stethoscope',
        'label'  => 'سبب الزيارة',
        'color'  => '#06b6d4',
        'fields' => ['reason_exam','reason_visit','symptoms'],
    ],
    'vitals' => [
        'icon'   => 'fa-heartbeat',
        'label'  => 'العلامات الحيوية',
        'color'  => '#ef4444',
        'fields' => ['blood_pressure','blood_sugar','heart_rate','temperature','oxygen_level','blood_type'],
    ],
    'history' => [
        'icon'   => 'fa-file-medical',
        'label'  => 'التاريخ المرضي',
        'color'  => '#f59e0b',
        'fields' => ['chronic_patient','chronic_family','genetic_diseases'],
    ],
    'pregnancy' => [
        'icon'   => 'fa-baby',
        'label'  => 'متابعة الحمل',
        'color'  => '#ec4899',
        'fields' => ['pregnancy_follow','last_period','expected_birth','pregnancy_count','birth_count','abortions','cesarean','father_status','fetus_position','fetus_move','fetus_weight'],
    ],
    'tests' => [
        'icon'   => 'fa-flask',
        'label'  => 'التحاليل والأشعة',
        'color'  => '#10b981',
        'fields' => ['medical_tests','radiology'],
    ],
    'treatment' => [
        'icon'   => 'fa-pills',
        'label'  => 'التشخيص والعلاج',
        'color'  => '#0284c7',
        'fields' => ['diagnostic','medications','prescription','medical_report'],
    ],
    'followup' => [
        'icon'   => 'fa-calendar-check',
        'label'  => 'المتابعة والملاحظات',
        'color'  => '#64748b',
        'fields' => ['next_appointment','appointment_time','doctor_notes','general_notes'],
    ],
];

/* حقول العلامات الحيوية — تُعرض كـ chips صغيرة */
$vitalsFields = ['blood_pressure','blood_sugar','heart_rate','temperature','oxygen_level','blood_type'];

/* حقول تُعرض بعرض كامل (نصوص طويلة) */
$wideFields = ['reason_exam','reason_visit','symptoms','chronic_patient','chronic_family','genetic_diseases',
               'medical_tests','radiology','diagnostic','medications','prescription','medical_report',
               'doctor_notes','general_notes'];

$hide = ['id','patient_id','doctor_id','created_at','updated_at','consanguinity'];

/* كل الحقول الموزّعة في المجموعات */
$groupedFieldsList = array_merge(...array_column($groups, 'fields'));
?>

<style>
/* ════ RESET داخل المودال فقط ════ */
.vr-wrap * { box-sizing: border-box; }

/* ════ WRAPPER ════ */
.vr-wrap {
    direction: rtl;
    font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    color: #0f172a;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* ════ HEADER ════ */
.vr-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(14,165,233,0.12);
    margin-bottom: 2px;
}
.vr-header-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg,#0ea5e9,#06b6d4);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 0.9rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(14,165,233,0.3);
}
.vr-header h2 {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}
.vr-header p {
    font-size: 0.72rem;
    color: #94a3b8;
    margin: 0;
}

/* ════ GROUP SECTION ════ */
.vr-group {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(14,165,233,0.09);
    box-shadow: 0 2px 8px rgba(14,165,233,0.05);
    background: #fff;
}
.vr-group-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    border-bottom: 1px solid rgba(14,165,233,0.08);
    background: rgba(14,165,233,0.04);
}
.vr-group-head i {
    font-size: 0.75rem;
    opacity: 0.9;
}
.vr-group-body {
    padding: 12px 14px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 8px;
}

/* ════ FIELD ITEM ════ */
.vr-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 8px 10px;
    background: #f8fafc;
    border-radius: 9px;
    border: 1px solid rgba(148,163,184,0.12);
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    min-width: 0;
}
.vr-field:hover {
    border-color: rgba(14,165,233,0.2);
    box-shadow: 0 2px 8px rgba(14,165,233,0.07);
}
.vr-field.vr-wide {
    grid-column: 1 / -1;
}
.vr-field-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.vr-field-value {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.55;
    word-break: break-word;
}

/* ════ VITALS CHIPS ════ */
.vr-vitals-grid {
    padding: 12px 14px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 8px;
}
.vr-vital-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 8px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid rgba(14,165,233,0.1);
    text-align: center;
    transition: box-shadow 0.18s ease;
}
.vr-vital-chip:hover {
    box-shadow: 0 3px 10px rgba(14,165,233,0.12);
}
.vr-vital-chip .chip-label {
    font-size: 0.62rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.vr-vital-chip .chip-value {
    font-size: 0.95rem;
    font-weight: 800;
    color: #ef4444;
}

/* ════ EMPTY ════ */
.vr-empty {
    text-align: center;
    padding: 24px;
    color: #94a3b8;
    font-size: 0.82rem;
}

/* ════ DARK MODE ════ */
body.dark-mode .vr-wrap          { color: #f1f5f9; }
body.dark-mode .vr-header h2     { color: #f1f5f9; }
body.dark-mode .vr-group         { background: #1a2235; border-color: rgba(14,165,233,0.12); }
body.dark-mode .vr-group-head    { background: rgba(14,165,233,0.07); border-bottom-color: rgba(14,165,233,0.12); }
body.dark-mode .vr-field         { background: #111827; border-color: rgba(255,255,255,0.06); }
body.dark-mode .vr-field:hover   { border-color: rgba(14,165,233,0.22); }
body.dark-mode .vr-field-value   { color: #e2e8f0; }
body.dark-mode .vr-vital-chip    { background: #111827; border-color: rgba(14,165,233,0.14); }
body.dark-mode .vr-header        { border-bottom-color: rgba(14,165,233,0.14); }
</style>

<div class="vr-wrap">

    <!-- Header -->
    <div class="vr-header">
        <div class="vr-header-icon"><i class="fas fa-notes-medical"></i></div>
        <div>
            <h2>الملف الطبي</h2>
            <p>السجل الطبي الكامل للمريض</p>
        </div>
    </div>

    <?php foreach ($groups as $gKey => $group):

        /* تحقق هل يوجد قيمة في أي حقل من هذه المجموعة */
        $hasData = false;
        foreach ($group['fields'] as $f) {
            if (!empty($record[$f])) { $hasData = true; break; }
        }
        if (!$hasData) continue;

        $isVitals = ($gKey === 'vitals');
    ?>

    <div class="vr-group">

        <!-- Group Header -->
        <div class="vr-group-head" style="color:<?= $group['color'] ?>;">
            <i class="fas <?= $group['icon'] ?>"></i>
            <?= $group['label'] ?>
        </div>

        <?php if ($isVitals): ?>
        <!-- Vitals — chip layout -->
        <div class="vr-vitals-grid">
            <?php foreach ($group['fields'] as $f):
                if (empty($record[$f])) continue;
                $title = $labels[$f] ?? $f;
            ?>
            <div class="vr-vital-chip" data-field-name="<?= htmlspecialchars($f) ?>">
                <span class="chip-label"><?= $title ?></span>
                <span class="chip-value"><?= htmlspecialchars(_translateValue($record[$f])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Normal fields grid -->
        <div class="vr-group-body">
            <?php foreach ($group['fields'] as $f):
                if (empty($record[$f])) continue;
                $title = $labels[$f] ?? $f;
                $wide  = in_array($f, $wideFields) ? ' vr-wide' : '';
            ?>
            <div class="vr-field<?= $wide ?>" data-field-name="<?= htmlspecialchars($f) ?>">
                <span class="vr-field-label"><?= $title ?></span>
                <span class="vr-field-value"><?= nl2br(htmlspecialchars(_translateValue($record[$f]))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php endforeach; ?>

    <?php
    /* حقول غير مصنّفة في أي مجموعة */
    $ungrouped = [];
    foreach ($record as $f => $v) {
        if (in_array($f, $hide)) continue;
        if (empty($v)) continue;
        if (in_array($f, $groupedFieldsList)) continue;
        $ungrouped[$f] = $v;
    }

    if (!empty($ungrouped)): ?>
    <div class="vr-group">
        <div class="vr-group-head" style="color:#64748b;">
            <i class="fas fa-ellipsis-h"></i>
            معلومات إضافية
        </div>
        <div class="vr-group-body">
            <?php foreach ($ungrouped as $f => $v):
                $title = $labels[$f] ?? $f;
            ?>
            <div class="vr-field" data-field-name="<?= htmlspecialchars($f) ?>">
                <span class="vr-field-label"><?= $title ?></span>
                <span class="vr-field-value"><?= nl2br(htmlspecialchars(_translateValue($v))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
/* ══════════════════════════════════════════════════
   فيش العلاج — fiche_traitement
══════════════════════════════════════════════════ */
if (!empty($ficheData) && (!empty($ficheData['fiche_diagnostic']) || !empty($ficheData['fiche_medications']))): ?>

<div class="vr-group" style="border-color:rgba(124,58,237,0.18);overflow:hidden;position:relative;">
    <!-- شريط لوني جانبي -->
    <div style="position:absolute;right:0;top:0;bottom:0;width:3px;background:linear-gradient(to bottom,#7c3aed,#a78bfa);"></div>

    <!-- Header -->
    <div class="vr-group-head" style="color:#7c3aed;background:linear-gradient(135deg,rgba(124,58,237,0.07),rgba(167,139,250,0.04));border-bottom:1px solid rgba(124,58,237,0.1);padding-right:16px;">
        <i class="fas fa-notes-medical"></i>
        Fiche de Traitement &nbsp;/&nbsp; بطاقة العلاج
        <span style="margin-right:auto;background:rgba(124,58,237,0.12);color:#7c3aed;font-size:0.6rem;padding:2px 8px;border-radius:20px;font-weight:700;">
            <i class="fas fa-check-circle" style="margin-left:3px;"></i>محفوظ
        </span>
    </div>

    <!-- التشخيص -->
    <?php if (!empty($ficheData['fiche_diagnostic'])): ?>
    <div style="padding:12px 16px 6px 16px;">
        <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px;">
            <div style="width:26px;height:26px;border-radius:7px;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:0.72rem;">
                <i class="fas fa-stethoscope"></i>
            </div>
            <span style="font-size:0.68rem;font-weight:800;color:#5b21b6;text-transform:uppercase;letter-spacing:0.8px;">التشخيص / Diagnostic</span>
        </div>
        <div data-fiche-field="fiche_diagnostic" style="background:linear-gradient(135deg,#faf5ff,#f5f3ff);border:1px solid rgba(124,58,237,0.12);border-radius:10px;padding:10px 13px;font-size:0.84rem;font-weight:600;color:#1e293b;line-height:1.6;">
            <?= nl2br(htmlspecialchars($ficheData['fiche_diagnostic'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- الأدوية -->
    <?php if (!empty($ficheData['fiche_medications'])): ?>
    <div style="padding:8px 16px 14px 16px;">
        <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px;">
            <div style="width:26px;height:26px;border-radius:7px;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;color:#059669;font-size:0.72rem;">
                <i class="fas fa-pills"></i>
            </div>
            <span style="font-size:0.68rem;font-weight:800;color:#059669;text-transform:uppercase;letter-spacing:0.8px;">الأدوية والعلاجات / Médicaments</span>
        </div>
        <div data-fiche-field="fiche_medications" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid rgba(16,185,129,0.15);border-radius:10px;padding:10px 13px;font-size:0.84rem;font-weight:600;color:#1e293b;line-height:1.85;font-family:'Courier New','Cairo',monospace;white-space:pre-wrap;">
            <?= nl2br(htmlspecialchars($ficheData['fiche_medications'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- timestamp -->
    <?php if (!empty($ficheData['updated_at'])): ?>
    <div style="padding:6px 16px 10px;font-size:0.62rem;color:#94a3b8;display:flex;align-items:center;gap:5px;border-top:1px dashed rgba(124,58,237,0.1);">
        <i class="fas fa-clock" style="color:#a78bfa;"></i>
        آخر تحديث: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ficheData['updated_at']))) ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
/* ══════════════════════════════════════════════════
   التقرير الطبي — rapport_medical
══════════════════════════════════════════════════ */
if (!empty($rapportData) && !empty($rapportData['rapport_content'])): ?>

<div class="vr-group" style="border-color:rgba(14,165,233,0.2);overflow:hidden;position:relative;">
    <!-- شريط لوني جانبي -->
    <div style="position:absolute;right:0;top:0;bottom:0;width:3px;background:linear-gradient(to bottom,#0ea5e9,#06b6d4);"></div>

    <!-- Header -->
    <div class="vr-group-head" style="color:#0284c7;background:linear-gradient(135deg,rgba(14,165,233,0.07),rgba(6,182,212,0.04));border-bottom:1px solid rgba(14,165,233,0.1);padding-right:16px;">
        <i class="fas fa-file-medical-alt"></i>
        Rapport Médical &nbsp;/&nbsp; التقرير الطبي
        <span style="margin-right:auto;background:rgba(2,132,199,0.1);color:#0284c7;font-size:0.6rem;padding:2px 8px;border-radius:20px;font-weight:700;">
            <i class="fas fa-file-alt" style="margin-left:3px;"></i>وثيقة رسمية
        </span>
    </div>

    <!-- ورقة التقرير الطبي -->
    <div style="margin:12px;border:1px solid rgba(14,165,233,0.15);border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(14,165,233,0.07);">

        <!-- ترويسة المؤسسة -->
        <div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);padding:10px 14px;border-bottom:1.5px solid rgba(14,165,233,0.15);display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:10px;">
            <!-- يسار: المؤسسة -->
            <div style="direction:rtl;font-family:'Cairo',sans-serif;font-size:0.68rem;color:#0f172a;line-height:1.6;">
                <div style="font-weight:800;color:#0ea5e9;font-size:0.72rem;border-bottom:1px solid rgba(14,165,233,0.3);padding-bottom:2px;margin-bottom:3px;">Centre Hospitalo-Universitaire</div>
                <div style="color:#475569;">Médecin chef service</div>
                <div style="font-style:italic;color:#64748b;font-size:0.64rem;text-decoration:underline;">Service de Médecine Interne</div>
            </div>
            <!-- وسط: شعار -->
            <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                <div style="width:44px;height:44px;border-radius:9px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(14,165,233,0.3);">
                    <span style="font-size:1.1rem;font-weight:900;color:#fff;font-family:'Arial Black',sans-serif;letter-spacing:-1px;">CHU</span>
                </div>
                <div style="font-size:0.5rem;color:#64748b;text-align:center;line-height:1.3;font-family:'Cairo',sans-serif;">Hassani Abdelkader<br>Sidi Bel Abbès</div>
            </div>
            <!-- يمين: الطبيب -->
            <?php if (!empty($rapportData['rapport_doctor'])): ?>
            <div style="text-align:left;direction:ltr;font-family:'Cairo',sans-serif;font-size:0.68rem;color:#0f172a;">
                <div style="font-weight:800;color:#0f172a;font-size:0.72rem;">Dr. <?= htmlspecialchars($rapportData['rapport_doctor']) ?></div>
                <div style="color:#64748b;font-size:0.63rem;">Médecin traitant</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- عنوان RAPPORT MÉDICAL -->
        <div style="text-align:center;padding:9px 16px;border-bottom:1px solid rgba(14,165,233,0.08);background:#fafcff;">
            <div style="font-size:0.9rem;font-weight:900;color:#0f172a;letter-spacing:4px;text-transform:uppercase;font-family:'Times New Roman',Times,serif;">RAPPORT MÉDICAL</div>
            <div style="width:48px;height:2px;background:linear-gradient(90deg,#0ea5e9,#06b6d4);margin:6px auto 0;border-radius:2px;"></div>
        </div>

        <!-- بيانات المريض -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;padding:10px 14px;background:rgba(248,250,252,0.6);border-bottom:1px solid rgba(14,165,233,0.07);">
            <?php if (!empty($rapportData['rapport_patient'])): ?>
            <div style="display:flex;align-items:center;gap:6px;border-bottom:1px dashed rgba(14,165,233,0.15);padding-bottom:4px;">
                <span style="font-size:0.68rem;font-weight:700;color:#475569;white-space:nowrap;">المريض :</span>
                <span style="font-size:0.8rem;font-weight:700;color:#0f172a;"><?= htmlspecialchars($rapportData['rapport_patient']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($rapportData['rapport_age'])): ?>
            <div style="display:flex;align-items:center;gap:6px;border-bottom:1px dashed rgba(14,165,233,0.15);padding-bottom:4px;">
                <span style="font-size:0.68rem;font-weight:700;color:#475569;white-space:nowrap;">العمر :</span>
                <span style="font-size:0.8rem;font-weight:700;color:#0f172a;"><?= htmlspecialchars($rapportData['rapport_age']) ?> سنة</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($rapportData['rapport_date'])): ?>
            <div style="display:flex;align-items:center;gap:6px;border-bottom:1px dashed rgba(14,165,233,0.15);padding-bottom:4px;">
                <span style="font-size:0.68rem;font-weight:700;color:#475569;white-space:nowrap;">التاريخ :</span>
                <span style="font-size:0.8rem;font-weight:600;color:#0f172a;"><?= htmlspecialchars(date('d/m/Y', strtotime($rapportData['rapport_date']))) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- محتوى التقرير على ورق مسطر -->
        <div style="
            padding:12px 16px;
            min-height:140px;
            background:repeating-linear-gradient(transparent,transparent 31px,rgba(14,165,233,0.055) 31px,rgba(14,165,233,0.055) 32px);
            position:relative;
        ">
            <div style="font-size:0.62rem;font-weight:700;color:#0ea5e9;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;display:flex;align-items:center;gap:6px;font-family:'Cairo',sans-serif;">
                <i class="fas fa-align-right"></i> محتوى التقرير
                <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(14,165,233,0.25),transparent);"></span>
            </div>
            <div data-rapport-field="rapport_content" style="font-family:'Times New Roman',Times,serif;font-size:0.9rem;line-height:32px;color:#1e293b;white-space:pre-wrap;word-break:break-word;">
                <?= nl2br(htmlspecialchars($rapportData['rapport_content'])) ?>
            </div>
        </div>

        <!-- توقيع الطبيب -->
        <div style="display:flex;justify-content:flex-end;padding:10px 18px 14px;background:#fafcff;border-top:1px dashed rgba(14,165,233,0.1);">
            <div style="text-align:center;">
                <div style="font-size:0.68rem;font-weight:700;color:#475569;margin-bottom:28px;font-family:'Cairo',sans-serif;text-transform:uppercase;letter-spacing:0.8px;">
                    Médecin traitant — توقيع الطبيب
                </div>
                <div style="width:130px;border-top:1px solid #94a3b8;padding-top:4px;font-size:0.62rem;color:#94a3b8;font-family:'Cairo',sans-serif;text-align:center;">
                    Signature &amp; Cachet
                </div>
            </div>
        </div>

    </div><!-- /ورقة التقرير -->

    <!-- timestamp -->
    <?php if (!empty($rapportData['updated_at'])): ?>
    <div style="padding:4px 16px 10px;font-size:0.62rem;color:#94a3b8;display:flex;align-items:center;gap:5px;">
        <i class="fas fa-clock" style="color:#7dd3fc;"></i>
        آخر تحديث: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($rapportData['updated_at']))) ?>
    </div>
    <?php endif; ?>

</div>

<?php endif; ?>

