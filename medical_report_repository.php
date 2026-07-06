<?php
/**
 * medical_report_repository.php
 * ════════════════════════════════════════════════════════════════
 *  طبقة الوصول للبيانات (قراءة فقط).
 *  تجلب قائمة مرضى الطبيب وبياناتهم السريرية الكاملة من الجداول الحالية
 *  دون أي تعديل عليها (لا INSERT/UPDATE/DELETE على جداول النظام).
 *
 *  ملاحظة عن الربط: في هذا المشروع، السجل الطبي medical_records.id هو
 *  المُعرّف الذي تشير إليه طلبات المخبر/الأشعة/المتابعات الخاصة بالطبيب.
 * ════════════════════════════════════════════════════════════════
 */

class MedicalReportRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ───────────────────────── قائمة المرضى ───────────────────────── */

    /**
     * مرضى الطبيب من medical_records (تُعرض الأسماء غير الفارغة فقط).
     */
    public function listPatients(int $doctorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, full_name, created_at
            FROM medical_records
            WHERE doctor_id = ?
              AND TRIM(COALESCE(full_name, '')) <> ''
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ─────────────────────── السجلات الفرعية ─────────────────────── */

    private function getRecord(int $recordId, int $doctorId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM medical_records
            WHERE id = ? AND doctor_id = ?
            LIMIT 1
        ");
        $stmt->execute([$recordId, $doctorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getLabRequests(int $recordId, int $doctorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT analysis_text, status, created_at
            FROM lab_requests
            WHERE patient_id = ? AND doctor_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$recordId, $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRadiology(int $recordId, int $doctorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT radiology_text, status, created_at
            FROM radiology_requests
            WHERE patient_id = ? AND doctor_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$recordId, $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFollowups(int $recordId, int $doctorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT followup_date, new_symptoms, new_treatment, doctor_notes
            FROM medical_followups
            WHERE medical_record_id = ? AND doctor_id = ?
            ORDER BY followup_date ASC
        ");
        $stmt->execute([$recordId, $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ──────────────────── تجميع البيانات للـ Prompt ──────────────────── */
    /*
     * ملاحظة مهمة للسلامة: لا نربط بجدول patients هنا. الحقل
     * medical_records.patient_id غير متّسق في هذه القاعدة (قد يكون id لمريض
     * أو user_id أو 0)، وربطه قد يُدخل بيانات مريض آخر (حساسية/عمر) في التقرير.
     * لذلك نعتمد حصراً على السجل الطبي المملوك للطبيب + طلبات المخبر/الأشعة/المتابعات
     * المرتبطة بمعرّف هذا السجل. أي معلومة غير متوفّرة يكتبها النموذج
     * «لا توجد معلومات كافية» بدل تخمينها.
     */

    /**
     * يبني مصفوفة منسّقة [ التسمية العربية => القيمة ] جاهزة للـ Prompt.
     * يرجع null إذا لم يوجد السجل أو لا يخصّ هذا الطبيب.
     */
    public function buildPatientData(int $recordId, int $doctorId): ?array
    {
        $rec = $this->getRecord($recordId, $doctorId);
        if (!$rec) return null;

        $labs     = $this->getLabRequests($recordId, $doctorId);
        $radios   = $this->getRadiology($recordId, $doctorId);
        $follows  = $this->getFollowups($recordId, $doctorId);

        /* العلامات الحيوية مجمّعة في سطر واحد منظّم */
        $vitals = [];
        if (!empty($rec['blood_pressure'])) $vitals[] = 'ضغط الدم: '   . $rec['blood_pressure'];
        if (!empty($rec['heart_rate']))     $vitals[] = 'النبض: '      . $rec['heart_rate'];
        if (!empty($rec['temperature']))    $vitals[] = 'الحرارة: '    . $rec['temperature'];
        if (!empty($rec['blood_sugar']))    $vitals[] = 'سكر الدم: '   . $rec['blood_sugar'];
        if (!empty($rec['oxygen_level']))   $vitals[] = 'الأكسجين: '   . $rec['oxygen_level'];

        /* دمج نتائج التحاليل: حقل السجل + طلبات المخبر */
        $labText = [];
        if (!empty($rec['medical_tests'])) $labText[] = $rec['medical_tests'];
        foreach ($labs as $l) {
            if (!empty($l['analysis_text'])) {
                $labText[] = $l['analysis_text'] . ' (الحالة: ' . ($l['status'] ?? 'غير محدّد') . ')';
            }
        }

        /* دمج نتائج الأشعة: حقل السجل + طلبات الأشعة */
        $radText = [];
        if (!empty($rec['radiology'])) $radText[] = $rec['radiology'];
        foreach ($radios as $r) {
            if (!empty($r['radiology_text'])) {
                $radText[] = $r['radiology_text'] . ' (الحالة: ' . ($r['status'] ?? 'غير محدّد') . ')';
            }
        }

        /* المتابعات السريرية */
        $followText = [];
        foreach ($follows as $f) {
            $parts = [];
            if (!empty($f['followup_date']))  $parts[] = 'بتاريخ ' . $f['followup_date'];
            if (!empty($f['new_symptoms']))   $parts[] = 'أعراض جديدة: ' . $f['new_symptoms'];
            if (!empty($f['new_treatment']))  $parts[] = 'علاج جديد: '   . $f['new_treatment'];
            if (!empty($f['doctor_notes']))   $parts[] = 'ملاحظات: '     . $f['doctor_notes'];
            if ($parts) $followText[] = implode(' — ', $parts);
        }

        /* الأمراض المزمنة والأدوية والملاحظات (من السجل الطبي فقط) */
        $chronic = array_filter([ $rec['chronic_patient'] ?? null ]);
        $meds    = array_filter([ $rec['prescription']    ?? null ]);
        $notes   = array_filter([ $rec['followup_notes']  ?? null ]);

        /* بناء الخريطة النهائية — تُحذف القيم الفارغة لاحقاً في الـ Prompt */
        return [
            'الاسم'                 => $rec['full_name'] ?? '',
            'تاريخ/معلومات الميلاد' => $rec['birth_info'] ?? '',
            'الجنس'                 => $rec['gender'] ?? '',
            'الحالة الاجتماعية'     => $rec['marital_status'] ?? '',
            'المهنة'                => $rec['job'] ?? '',
            'سبب الفحص'             => $rec['reason_exam'] ?? '',
            'الأعراض'               => $rec['symptoms'] ?? '',
            'العلامات الحيوية'      => $vitals ? implode(' | ', $vitals) : '',
            'الأمراض المزمنة'       => $chronic ? implode(' / ', $chronic) : '',
            'أمراض مزمنة عائلية'    => $rec['chronic_family'] ?? '',
            'نتائج التحاليل'        => $labText ? implode(' ؛ ', $labText) : '',
            'نتائج الأشعة'          => $radText ? implode(' ؛ ', $radText) : '',
            'الأدوية / خطة العلاج'  => $meds ? implode(' / ', $meds) : '',
            'المتابعات السابقة'     => $followText ? implode(' ‖ ', $followText) : '',
            'ملاحظات إضافية'        => $notes ? implode(' / ', $notes) : '',
            'تاريخ الزيارة'         => $rec['created_at'] ?? '',
        ];
    }

    /** اسم المريض فقط (لأغراض العرض/الحفظ). */
    public function patientName(int $recordId, int $doctorId): string
    {
        $rec = $this->getRecord($recordId, $doctorId);
        return $rec['full_name'] ?? '';
    }
}
