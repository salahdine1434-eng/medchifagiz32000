/**
 * patient_inline.js
 * ═══════════════════════════════════════════════════════════
 * يحوّل كل patient-item في قائمة مرضى اليوم إلى بطاقة قابلة
 * للتوسّع — Inline Accordion بدلاً من Modal Popup.
 *
 * كيفية الإضافة في dr_dashboard.php قبل </body>:
 *   <script src="patient_inline.js"></script>
 *
 * ─ لا يُمس أي AJAX أو Logic موجود
 * ─ الـ IDs الأصلية (full_name, prescription…) تبقى في الـ DOM
 *   مع إنشاء mirrors مرتبطة two-way
 * ═══════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────────────────
       CONFIG: تعريف الأقسام وما يحتويه كل قسم من IDs
    ────────────────────────────────────────────────────── */
    const SECTIONS = [
        {
            id: 'pif-sec-1',
            icon: 'fas fa-user-circle',
            title: 'المعلومات الشخصية',
            fields: [
                { label: 'اسم ولقب المريض',    id: 'full_name',      type: 'input' },
                { label: 'تاريخ ومكان الميلاد', id: 'birth_info',     type: 'input' },
                { label: 'الجنس',              id: 'gender',         type: 'select',
                  options: [{ val: '', txt: '-- اختر --' }, { val: 'ذكر', txt: 'ذكر' }, { val: 'أنثى', txt: 'أنثى' }], grid: true },
                { label: 'السن',               id: 'age',            type: 'input', inputType: 'number', grid: true },
                { label: 'الحالة العائلية',     id: 'marital_status', type: 'input' },
                { label: 'طبيعة العمل',         id: 'job',            type: 'input' },
                { label: 'العنوان',              id: 'address',        type: 'input' },
                { label: 'رقم الهاتف',          id: 'phone',            type: 'input' },
                { label: 'تاريخ الدخول',        id: 'admission_date',   type: 'input', inputType: 'date', grid: true },
                { label: 'الحالة',              id: 'residency_status', type: 'select',
                  options: [{ val: '', txt: '-- اختر --' }, { val: 'مقيم', txt: 'مقيم' }, { val: 'غير مقيم', txt: 'غير مقيم' }], grid: true },
                { label: 'رقم الغرفة',          id: 'room_number',      type: 'input' },
            ],
            actions: []
        },
        {
            id: 'pif-sec-2',
            icon: 'fas fa-stethoscope',
            title: 'الفحص والأعراض',
            fields: [
                { label: 'سبب الفحص',              id: 'reason_exam',    type: 'textarea' },
                { label: 'الأعراض',                id: 'symptoms',       type: 'textarea' },
                { label: 'ضغط الدم',               id: 'blood_pressure', type: 'input',   grid: true },
                { label: 'نسبة السكر في الدم',      id: 'blood_sugar',    type: 'input',   grid: true },
                { label: 'معدل ضربات القلب',        id: 'heart_rate',     type: 'input',   grid: true },
                { label: 'درجة الحرارة',            id: 'temperature',    type: 'input',   grid: true },
                { label: 'نسبة الأكسجين',           id: 'oxygen_level',   type: 'input',   grid: true },
                { label: 'الأمراض المزمنة (المريض)', id: 'chronic_patient', type: 'textarea' },
                { label: 'الأمراض المزمنة (العائلة)', id: 'chronic_family', type: 'textarea' },
            ],
            actions: []
        },
        {
            id: 'pif-sec-preg',
            icon: 'fas fa-baby',
            title: 'متابعة الحمل',
            isPregnancy: true,
            fields: [],
            actions: []
        },
        {
            id: 'pif-sec-3',
            icon: 'fas fa-flask',
            title: 'الفحوصات التكميلية',
            fields: [
                { label: 'التحاليل الطبية',         id: 'medical_tests', type: 'textarea' },
                { label: 'الأشعة (Radiologie)',      id: 'radiology',     type: 'textarea' },
            ],
            actions: [
                { label: '📤 إرسال للمخبر',   fn: 'sendToLab',       cls: 'pif-btn-primary' },
                { label: '📤 إرسال للأشعة',   fn: 'sendToRadiology', cls: 'pif-btn-primary' },
            ]
        },
        {
            id: 'pif-sec-fiche',
            icon: 'fas fa-notes-medical',
            title: 'Fiche de traitement',
            isFiche: true,
            fields: [],
            actions: [
                { label: '💾 حفظ بطاقة العلاج', fn: 'saveFicheTraitement',  cls: 'pif-btn-success' },
                { label: '📤 إرسال fiche للممرض', fn: 'sendFicheToNurse',    cls: 'pif-btn-primary' },
                { label: '🖨️ طباعة الفيش',        fn: 'printFicheTraitement', cls: 'pif-btn-ghost'   },
            ]
        },
        {
            id: 'pif-sec-4',
            icon: 'fas fa-prescription',
            title: 'الوصفة الطبية',
            isRx: true,
            fields: [],
            actions: [
                { label: '💾 حفظ الوصفة',        fn: 'savePrescription',       cls: 'pif-btn-success' },
                { label: '📤 إرسال الوصفة للصيدلي', fn: 'sendPrescriptionToPharmacy', cls: 'pif-btn-primary' },
                { label: '🖨️ طباعة',             fn: 'printPrescription',      cls: 'pif-btn-ghost'   },
            ]
        },
        {
            id: 'pif-sec-rapport',
            icon: 'fas fa-file-medical-alt',
            title: 'التقرير الطبي / Rapport médical',
            isRapport: true,
            fields: [],
            actions: [
                { label: '💾 حفظ التقرير',  fn: 'saveRapportMedical',  cls: 'pif-btn-success' },
                { label: '🖨️ طباعة PDF',    fn: 'printRapportMedical', cls: 'pif-btn-ghost'   },
            ]
        },
        {
            id: 'pif-sec-6',
            icon: 'fas fa-calendar-plus',
            title: 'المواعيد القادمة',
            fields: [
                { label: 'هل يحتاج موعد جديد؟', id: 'needs_appointment', type: 'select',
                  options: [{ val:'no', txt:'لا' }, { val:'yes', txt:'نعم' }] },
                { label: 'التاريخ',  id: 'next_appointment_date', type: 'input', inputType: 'date', conditional: 'needs_appointment' },
                { label: 'الوقت',    id: 'next_appointment_time', type: 'input', inputType: 'time', conditional: 'needs_appointment' },
            ],
            actions: [
                { label: '🖨️ طباعة السجل', fn: 'printMedicalRecord', cls: 'pif-btn-ghost'    },
                { label: '💾 حفظ الملف',   fn: 'saveMedicalRecord',  cls: 'pif-btn-primary'  },
            ]
        }
    ];

    /* ──────────────────────────────────────────────────────
       STATE
    ────────────────────────────────────────────────────── */
    let activePatientItem = null;   // الـ patient-item المفتوح حالياً
    let currentPatientId  = null;   // ID المريض الحالي

    /* ──────────────────────────────────────────────────────
       INIT — يعيد بناء كل patient-item في القائمة
    ────────────────────────────────────────────────────── */
    function init() {
        const patientsList = document.querySelector('#todayPatients .patients-list');
        if (!patientsList) return;

        const items = patientsList.querySelectorAll('.patient-item');
        items.forEach(rebuildPatientItem);
    }

    /* ──────────────────────────────────────────────────────
       إعادة بناء كل patient-item
    ────────────────────────────────────────────────────── */
    function rebuildPatientItem(item) {
        // استخرج البيانات من الـ HTML الأصلي
        const nameEl = item.querySelector('.patient-info h4');
        const timeEl = item.querySelector('.appointment-time');
        const btnsDiv = item.querySelector('div[style*="gap"]');

        if (!nameEl) return;

        const patientName = nameEl.textContent.trim();
        const patientTime = timeEl ? timeEl.textContent.trim() : '';

        // استخرج patient_id من onclick
        const onclickAttr = nameEl.getAttribute('onclick') || '';
        const idMatch = onclickAttr.match(/openPatientFile\((\d+)\)/);
        const patientId = idMatch ? idMatch[1] : '0';

        // الحرف الأول للـ Avatar
        const initials = patientName.charAt(0) || '؟';

        // اسحب أزرار حضور/غياب IDs
        let presentId = '', absentId = '';
        if (btnsDiv) {
            const btns = btnsDiv.querySelectorAll('button');
            btns.forEach(btn => {
                const oc = btn.getAttribute('onclick') || '';
                if (oc.includes('markCompleted')) {
                    const m = oc.match(/\((\d+)\)/);
                    if (m) presentId = m[1];
                }
                if (oc.includes('markNoShow')) {
                    const m = oc.match(/\((\d+)\)/);
                    if (m) absentId = m[1];
                }
            });
        }

        // بناء الـ HTML الجديد
        item.innerHTML = `
            <div class="neon-bar"></div>
            <div class="patient-item-row" onclick="PIFToggle(this, '${patientId}', '${patientName}')">
                <div class="patient-item-left">
                    <div class="patient-avatar-circle">${initials}</div>
                    <div class="patient-item-meta">
                        <p class="patient-item-name">${patientName}</p>
                        <span class="patient-item-time">
                            <i class="fas fa-clock"></i> ${patientTime}
                        </span>
                    </div>
                </div>
                <div class="patient-action-btns" onclick="event.stopPropagation()">
                    ${presentId ? `<button class="pat-btn-present" onclick="markCompleted(${presentId})">✅ حضر</button>` : ''}
                    ${absentId  ? `<button class="pat-btn-absent"  onclick="markNoShow(${absentId})">❌ لم يحضر</button>` : ''}
                </div>
                <button class="patient-expand-btn" onclick="event.stopPropagation(); PIFToggle(this.closest('.patient-item-row'), '${patientId}', '${patientName}')">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="patient-inline-file" id="pif-container-${patientId}">
                <div class="patient-file-inner" id="pif-inner-${patientId}">
                    <div class="pif-loading">
                        <i class="fas fa-spinner"></i> جاري تحميل الملف الطبي...
                    </div>
                </div>
            </div>
        `;

        item.setAttribute('data-patient-id', patientId);
        item.setAttribute('data-patient-name', patientName);
    }

    /* ──────────────────────────────────────────────────────
       TOGGLE — فتح/إغلاق الملف الطبي
    ────────────────────────────────────────────────────── */
    window.PIFToggle = function (rowEl, patientId, patientName) {
        const item = rowEl.closest('.patient-item');
        const container = document.getElementById('pif-container-' + patientId);
        const expandBtn = item.querySelector('.patient-expand-btn');

        // إذا كان مفتوحاً بالفعل أغلقه
        if (item === activePatientItem) {
            closeActive();
            return;
        }

        // أغلق المفتوح السابق
        if (activePatientItem) closeActive();

        // افتح الجديد
        item.classList.add('expanded');
        container.classList.add('open');
        if (expandBtn) expandBtn.classList.add('open');
        activePatientItem = item;
        currentPatientId = patientId;

        // بناء المحتوى إذا لم يُبنَ بعد
        const inner = document.getElementById('pif-inner-' + patientId);
        if (!inner.querySelector('.patient-file-header')) {
            buildFileContent(inner, patientId, patientName);
        }

        // scroll ناعم للبطاقة
        setTimeout(() => {
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);

        // مزامنة مع الـ modal الأصلي (openPatientFile)
        if (typeof window._origOpenPatientFile === 'function') {
            window._origOpenPatientFile(patientId);
        }

        // إعادة تعيين الـ inputs الأصلية
        resetOriginalInputs();
    };

    function closeActive() {
        if (!activePatientItem) return;
        const pid = activePatientItem.getAttribute('data-patient-id');
        const container = document.getElementById('pif-container-' + pid);
        const expandBtn = activePatientItem.querySelector('.patient-expand-btn');
        activePatientItem.classList.remove('expanded');
        if (container) container.classList.remove('open');
        if (expandBtn) expandBtn.classList.remove('open');
        activePatientItem = null;
        currentPatientId = null;
    }

    /* ──────────────────────────────────────────────────────
       BUILD CONTENT — ينشئ الـ Accordion داخل الـ container
    ────────────────────────────────────────────────────── */
    function buildFileContent(inner, patientId, patientName) {
        const hasPregnancyStep = (window.DOCTOR_SPECIALTY === 'أمراض النساء والتوليد');

        inner.innerHTML = `
            <div class="patient-file-header">
                <div class="patient-file-title">
                    <i class="fas fa-notes-medical"></i>
                    الملف الطبي — ${patientName}
                </div>
                <span class="patient-file-id">ID: ${patientId}</span>
            </div>
            <input type="hidden" id="medical_patient_id_${patientId}" value="${patientId}">
            <div class="pif-accordion" id="pif-acc-${patientId}"></div>
        `;

        const acc = inner.querySelector('.pif-accordion');

        SECTIONS.forEach((sec, idx) => {
            // أخفِ قسم الحمل إذا لم يوجد step5
            if (sec.isPregnancy && !hasPregnancyStep) return;

            const section = buildSection(sec, patientId, idx);
            acc.appendChild(section);
        });

        // Wire mirrors → originals two-way
        wireMirrors(inner, patientId);

        // تعبئة اسم المريض تلقائياً في حقل التقرير
        setTimeout(() => {
            const nameInput = document.getElementById(`rapport_patient_name_${patientId}`);
            if (nameInput && !nameInput.value) nameInput.value = patientName;
        }, 50);

        // أول section تنفتح تلقائياً
        const firstSec = acc.querySelector('.pif-section');
        if (firstSec) openSection(firstSec);
    }

    /* ──────────────────────────────────────────────────────
       بناء Section واحدة
    ────────────────────────────────────────────────────── */
    function buildSection(sec, patientId, idx) {
        const div = document.createElement('div');
        div.className = 'pif-section';
        div.id = `${sec.id}-${patientId}`;

        /* Header */
        const hd = document.createElement('div');
        hd.className = 'pif-sec-header';
        hd.onclick = () => toggleSection(div);
        hd.innerHTML = `
            <div class="pif-sec-icon"><i class="${sec.icon}"></i></div>
            <span class="pif-sec-title">${sec.title}</span>
            <span class="pif-sec-status" id="status-${sec.id}-${patientId}">فارغ</span>
            <i class="fas fa-chevron-down pif-sec-arrow"></i>
        `;

        /* Body */
        const body = document.createElement('div');
        body.className = 'pif-sec-body';
        const inner = document.createElement('div');
        inner.className = 'pif-sec-body-inner';

        if (sec.isRx) {
            inner.innerHTML = buildRxContent(patientId);
        } else if (sec.isPregnancy) {
            inner.innerHTML = buildPregnancyContent(patientId);
        } else if (sec.isFiche) {
            inner.innerHTML = buildFicheContent(patientId);
        } else if (sec.isRapport) {
            inner.innerHTML = buildRapportContent(patientId);
            inner.style.padding = '0';
        } else {
            inner.innerHTML = buildFields(sec.fields, patientId);
        }

        body.appendChild(inner);

        /* Actions */
        if (sec.actions && sec.actions.length > 0) {
            const actRow = document.createElement('div');
            actRow.className = 'pif-actions-row';
            sec.actions.forEach(act => {
                const btn = document.createElement('button');
                btn.className = `pif-btn ${act.cls}`;
                btn.textContent = act.label;
                btn.onclick = () => {
                    syncMirrorsToOriginal(patientId);
                    window[act.fn] && window[act.fn]();
                };
                actRow.appendChild(btn);
            });
            body.appendChild(actRow);
        }

        /* Save All button for last section */
        if (sec.id === 'pif-sec-6') {
            /* already has actions */
        }

        div.appendChild(hd);
        div.appendChild(body);
        return div;
    }

    /* ──────────────────────────────────────────────────────
       بناء الـ Fields
    ────────────────────────────────────────────────────── */
    function buildFields(fields, patientId) {
        if (!fields || fields.length === 0) return '';

        // فصل الـ grid fields عن الـ full-width
        let html = '';
        let i = 0;
        while (i < fields.length) {
            const f = fields[i];
            if (f.grid) {
                // ابحث عن كل الـ grid fields المتتالية
                let gridHtml = '';
                while (i < fields.length && fields[i].grid) {
                    gridHtml += buildField(fields[i], patientId);
                    i++;
                }
                html += `<div class="pif-grid-2">${gridHtml}</div>`;
            } else {
                html += buildField(f, patientId);
                i++;
            }
        }
        return html;
    }

    function buildField(f, patientId) {
        const mirrorId = `mirror_${f.id}_${patientId}`;
        const conditionalAttr = f.conditional
            ? `data-conditional="${f.conditional}_${patientId}"`
            : '';
        const displayStyle = f.conditional ? 'style="display:none"' : '';

        if (f.type === 'textarea') {
            return `
                <div class="form-group" ${conditionalAttr} ${displayStyle}>
                    <label>${f.label}</label>
                    <textarea id="${mirrorId}" rows="3" placeholder="${f.label}..."></textarea>
                </div>`;
        }

        if (f.type === 'select' && f.options) {
            const opts = f.options.map(o =>
                `<option value="${o.val}">${o.txt}</option>`).join('');
            return `
                <div class="form-group" ${conditionalAttr} ${displayStyle}>
                    <label>${f.label}</label>
                    <select id="${mirrorId}">${opts}</select>
                </div>`;
        }

        const iType = f.inputType || 'text';
        return `
            <div class="form-group" ${conditionalAttr} ${displayStyle}>
                <label>${f.label}</label>
                <input type="${iType}" id="${mirrorId}" placeholder="${f.label}...">
            </div>`;
    }

    /* ──────────────────────────────────────────────────────
       محتوى الوصفة الطبية (مرآة prescription-sheet)
    ────────────────────────────────────────────────────── */
    function buildRxContent(patientId) {
        // انسخ محتوى الـ prescriptionSheet الأصلي
        const orig = document.getElementById('prescriptionSheet');
        let rxHtml = '';
        if (orig) {
            const clone = orig.cloneNode(true);
            // غيّر الـ IDs لأسماء mirrors
            clone.querySelectorAll('[id]').forEach(el => {
                el.id = `mirror_${el.id}_${patientId}`;
            });
            rxHtml = `<div class="pif-rx-sheet">${clone.innerHTML}</div>`;
        } else {
            rxHtml = `
            <div class="pif-rx-sheet">
                <div class="form-group">
                    <label>اسم المريض</label>
                    <input type="text" id="mirror_rx_patient_name_${patientId}">
                </div>
                <div class="form-group">
                    <label>التاريخ</label>
                    <input type="date" id="mirror_rx_date_${patientId}" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label>الأدوية</label>
                    <textarea id="mirror_prescription_${patientId}" rows="5" placeholder="اكتب الأدوية هنا..."></textarea>
                </div>
                <div class="form-group">
                    <label>تعليمات الطبيب</label>
                    <textarea id="mirror_doctor_notes_${patientId}" rows="3" placeholder="تعليمات الطبيب..."></textarea>
                </div>
            </div>`;
        }
        return rxHtml;
    }

    /* ──────────────────────────────────────────────────────
       محتوى متابعة الحمل
    ────────────────────────────────────────────────────── */
    function buildPregnancyContent(patientId) {
        const pregFields1 = [
            { label: 'تاريخ آخر دورة',          id: 'last_period_date',      type: 'input', inputType: 'date' },
            { label: 'تاريخ الولادة المتوقع',    id: 'expected_delivery_date', type: 'input', inputType: 'date' },
            { label: 'فصيلة الدم',               id: 'preg_blood_type',       type: 'input', grid: true },
            { label: 'عدد مرات الحمل (G)',        id: 'pregnancies_count',     type: 'input', inputType: 'number', grid: true },
            { label: 'عدد الولادات (P)',          id: 'births_count',          type: 'input', inputType: 'number', grid: true },
            { label: 'إجهاضات سابقة',            id: 'miscarriages_count',    type: 'input', inputType: 'number', grid: true },
            { label: 'قيصرية سابقة',             id: 'c_sections_count',      type: 'input', inputType: 'number', grid: true },
            { label: 'حالة الأب',                id: 'father_status',         type: 'input', grid: true },
            { label: 'أمراض مزمنة',              id: 'preg_chronic_diseases', type: 'textarea' },
            { label: 'زواج الأقارب',             id: 'consanguinity',         type: 'select',
              options: [{val:'no',txt:'لا'},{val:'yes',txt:'نعم'}] },
            { label: 'ملاحظات عامة',             id: 'pregnancy_notes',       type: 'textarea' },
        ];

        const pregFields2 = [
            { label: 'الوزن',                    id: 'preg_weight',           type: 'input', grid: true },
            { label: 'ضغط الدم',                 id: 'preg_blood_pressure',   type: 'input', grid: true },
            { label: 'السكر',                     id: 'preg_sugar_level',      type: 'input', grid: true },
            { label: 'نبض الجنين',               id: 'fetal_heartbeat',       type: 'input', grid: true },
            { label: 'حركة الجنين',              id: 'fetal_movement',        type: 'input', grid: true },
            { label: 'وزن/حجم الجنين',           id: 'fetal_weight',          type: 'input', grid: true },
            { label: 'وضعية الجنين',             id: 'fetal_position',        type: 'input', grid: true },
            { label: 'ملاحظات الإيكوغرافيا',     id: 'echo_notes',            type: 'textarea' },
            { label: 'ملاحظات الطبيب',           id: 'followup_notes',        type: 'textarea' },
        ];

        return `
            <div class="preg-sub-card blue">
                <div class="preg-sub-title"><i class="fas fa-baby-carriage"></i> بطاقة الحمل</div>
                ${buildFields(pregFields1, patientId)}
            </div>
            <div class="preg-sub-card teal">
                <div class="preg-sub-title"><i class="fas fa-heartbeat"></i> متابعة الحمل</div>
                ${buildFields(pregFields2, patientId)}
            </div>`;
    }


    /* ──────────────────────────────────────────────────────
       محتوى Fiche de traitement
    ────────────────────────────────────────────────────── */
    function buildFicheContent(patientId) {
        return `
            <div style="background:#f0f9ff;padding:16px;border-radius:10px;border:1px solid #bae6fd;margin-bottom:4px;">
                <p style="color:#0369a1;font-size:0.78rem;font-weight:600;margin:0 0 12px 0;">
                    <i class="fas fa-info-circle"></i> بطاقة خاصة بالممرض — يكتب الطبيب التعليمات العلاجية هنا
                </p>
                <div class="form-group">
                    <label>🩺 التشخيص / Diagnostic</label>
                    <textarea id="mirror_fiche_diagnostic_${patientId}" rows="2" placeholder="اكتب التشخيص..."></textarea>
                </div>
                <div class="form-group">
                    <label>💊 الأدوية والعلاجات / Médicaments &amp; traitements</label>
                    <textarea id="mirror_fiche_medications_${patientId}" rows="3" placeholder="مثال: Paracetamol 500mg — 3 fois/jour..."></textarea>
                </div>
            </div>`;
    }

    /* ──────────────────────────────────────────────────────
       ACCORDION TOGGLE
    ────────────────────────────────────────────────────── */
    function toggleSection(sectionEl) {
        const isOpen = sectionEl.classList.contains('pif-open');
        // أغلق الكل
        const acc = sectionEl.closest('.pif-accordion');
        if (acc) {
            acc.querySelectorAll('.pif-section').forEach(s => s.classList.remove('pif-open'));
        }
        // افتح المطلوب إذا لم يكن مفتوحاً
        if (!isOpen) openSection(sectionEl);
    }

    function openSection(sectionEl) {
        sectionEl.classList.add('pif-open');
    }

    /* ──────────────────────────────────────────────────────
       WIRE MIRRORS — ربط الـ mirror inputs بالـ originals
    ────────────────────────────────────────────────────── */
    function wireMirrors(container, patientId) {
        // كل الـ inputs/textareas/selects داخل الـ container
        container.querySelectorAll('input[id^="mirror_"], textarea[id^="mirror_"], select[id^="mirror_"]')
            .forEach(mirror => {
                // استخرج الـ original ID
                const origId = mirror.id.replace(`mirror_`, '').replace(`_${patientId}`, '');
                const orig = document.getElementById(origId);

                // عند التغيير في الـ mirror → حدّث الـ original
                mirror.addEventListener('input', () => {
                    if (orig) orig.value = mirror.value;
                    updateSectionStatus(mirror, patientId);
                    handleConditionalFields(mirror, patientId, container);
                });
                mirror.addEventListener('change', () => {
                    if (orig) orig.value = mirror.value;
                    updateSectionStatus(mirror, patientId);
                    handleConditionalFields(mirror, patientId, container);
                });

                // عند التغيير في الـ original → حدّث الـ mirror
                if (orig) {
                    orig.addEventListener('input', () => { mirror.value = orig.value; });
                    orig.addEventListener('change', () => { mirror.value = orig.value; });
                }
            });

        // sync medical_patient_id
        const pidInput = document.getElementById('medical_patient_id');
        if (pidInput) pidInput.value = patientId;

        const localPid = document.getElementById(`medical_patient_id_${patientId}`);
        if (localPid) {
            // ربطه أيضاً بالأصلي
            const orig = document.getElementById('medical_patient_id');
            if (orig) orig.value = patientId;
        }

        // ── تعبئة تاريخ الدخول والحالة من data-attributes ──
        const patientItem = document.querySelector(`.patient-item[data-patient-id="${patientId}"]`);
        if (patientItem) {
            const admDate = patientItem.getAttribute('data-admission-date') || '';
            const resStatus = patientItem.getAttribute('data-residency-status') || '';

            const admInput = container.querySelector(`#mirror_admission_date_${patientId}`);
            if (admInput && admDate) admInput.value = admDate;

            const resSelect = container.querySelector(`#mirror_residency_status_${patientId}`);
            if (resSelect && resStatus) resSelect.value = resStatus;
        }
    }

    /* مزامنة جميع الـ mirrors → originals قبل الحفظ */
    function syncMirrorsToOriginal(patientId) {
        const container = document.getElementById('pif-inner-' + patientId);
        if (!container) return;

        container.querySelectorAll('[id^="mirror_"]').forEach(mirror => {
            const origId = mirror.id.replace('mirror_', '').replace('_' + patientId, '');
            const orig = document.getElementById(origId);
            if (orig) orig.value = mirror.value;
        });

        // patient_id
        const orig = document.getElementById('medical_patient_id');
        if (orig) orig.value = patientId;
    }

    /* ──────────────────────────────────────────────────────
       Conditional Fields (مواعيد: يظهر التاريخ/الوقت عند نعم)
    ────────────────────────────────────────────────────── */
    function handleConditionalFields(changedEl, patientId, container) {
        const condKey = changedEl.id.replace('mirror_', '').replace('_' + patientId, '');
        const conditionals = container.querySelectorAll(`[data-conditional="${condKey}_${patientId}"]`);
        if (conditionals.length === 0) return;
        const show = changedEl.value === 'yes';
        conditionals.forEach(el => {
            el.style.display = show ? 'block' : 'none';
        });
        // sync original appointmentFields div
        const origBox = document.getElementById('appointmentFields');
        if (origBox) origBox.style.display = show ? 'block' : 'none';
    }

    /* ──────────────────────────────────────────────────────
       Section Status Badge
    ────────────────────────────────────────────────────── */
    function updateSectionStatus(changedEl, patientId) {
        const section = changedEl.closest('.pif-section');
        if (!section) return;
        const statusEl = section.querySelector('.pif-sec-status');
        if (!statusEl) return;

        const hasData = Array.from(
            section.querySelectorAll('input, textarea')
        ).some(el => el.value && el.value.trim() !== '');

        statusEl.textContent = hasData ? '✓ مكتمل' : 'فارغ';
        statusEl.classList.toggle('done', hasData);
    }

    /* ──────────────────────────────────────────────────────
       إعادة تعيين الـ Original Inputs عند فتح مريض جديد
    ────────────────────────────────────────────────────── */
    function resetOriginalInputs() {
        const allIds = [
            'full_name','birth_info','marital_status','job','address','phone',
            'reason_exam','symptoms','blood_pressure','blood_sugar','heart_rate',
            'temperature','oxygen_level','chronic_patient','chronic_family',
            'medical_tests','radiology','prescription','doctor_notes',
            'rx_patient_name','rx_date','doctor_signature',
            'needs_appointment','next_appointment_date','next_appointment_time',
            'last_period_date','expected_delivery_date','preg_blood_type',
            'pregnancies_count','births_count','miscarriages_count','c_sections_count',
            'preg_chronic_diseases','father_status','consanguinity','pregnancy_notes',
            'preg_weight','preg_blood_pressure','preg_sugar_level','fetal_heartbeat',
            'fetal_movement','fetal_weight','fetal_position','echo_notes','followup_notes'
        ];
        allIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else el.value = '';
            }
        });
        // إعادة تعيين حقول التقرير الطبي (بـ patientId في الـ ID)
        // سيُعاد البناء تلقائياً عند PIFToggle — لا حاجة لإعادة تعيين يدوية
        // reset rx_date to today
        const rxDate = document.getElementById('rx_date');
        if (rxDate) rxDate.value = new Date().toISOString().split('T')[0];
    }

    /* ──────────────────────────────────────────────────────
       محتوى التقرير الطبي / Rapport médical
    ────────────────────────────────────────────────────── */
    function buildRapportContent(patientId) {
        const hospitalName    = window.HOSPITAL_NAME    || 'Centre Hospitalo-Universitaire - Hassani Abdelkader Sidi Bel Abbes';
        const hospitalService = window.HOSPITAL_SERVICE || 'Service de Médecine Interne';
        const chefService     = window.CHEF_SERVICE     || 'Pr. ST HEBRI';
        const doctorName      = window.DOCTOR_NAME      || '';
        const todayISO        = new Date().toISOString().split('T')[0];

        return `
        <div class="pif-rapport-sheet">

            <!-- ══ ترويسة CHU ══ -->
            <div class="pif-rapport-header">

                <!-- يسار: بيانات المستشفى -->
                <div class="pif-rapport-header-left">
                    <div class="pif-rapport-institution">
                        <div class="inst-main">${hospitalName}</div>
                        <div>Médecin chef service <strong>${chefService}</strong></div>
                        <div class="inst-service">${hospitalService}</div>
                    </div>
                </div>

                <!-- وسط: شعار CHU -->
                <div class="pif-rapport-logo">
                    <div class="pif-rapport-logo-chu" title="Centre Hospitalo-Universitaire">
                        <span>CHU</span>
                    </div>
                    <div class="pif-rapport-logo-sub">
                        المركز الاستشفائي الجامعي<br>عبد القادر حساني
                    </div>
                </div>

                <!-- يمين: الطبيب المعالج -->
                <div class="pif-rapport-header-right">
                    <div class="pif-rapport-doctor-name">
                        <span>Médecin traitant :</span>
                        <span style="font-weight:700;">${doctorName}</span>
                    </div>
                </div>
            </div>

            <!-- ══ عنوان RAPPORT MÉDICAL ══ -->
            <div class="pif-rapport-title-bar">
                <h2>RAPPORT MÉDICAL</h2>
                <div class="rapport-title-line"></div>
            </div>

            <!-- ══ بيانات المريض ══ -->
            <div class="pif-rapport-patient-info">
                <div class="pif-rapport-info-row">
                    <label>Le :</label>
                    <input type="date"
                           id="rapport_date_${patientId}"
                           class="rapport-field"
                           value="${todayISO}">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Patient(e) :</label>
                    <input type="text"
                           id="rapport_patient_name_${patientId}"
                           class="rapport-field"
                           placeholder="اسم المريض...">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Age :</label>
                    <input type="text"
                           id="rapport_age_${patientId}"
                           class="rapport-field"
                           placeholder="السن...">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Médecin traitant :</label>
                    <input type="text"
                           id="rapport_doctor_${patientId}"
                           class="rapport-field"
                           value="${doctorName}"
                           placeholder="Dr. ...">
                </div>
            </div>

            <!-- ══ منطقة الكتابة ══ -->
            <div class="pif-rapport-body">
                <div class="pif-rapport-body-label">
                    <i class="fas fa-pen-alt" style="font-size:.65rem;"></i>
                    محتوى التقرير
                </div>
                <textarea id="rapport_content_${patientId}"
                          class="rapport-field"
                          rows="10"
                          placeholder="اكتب محتوى التقرير الطبي هنا...&#10;&#10;(التشخيص — الأعراض — العلاج — التوصيات...)"
                ></textarea>
            </div>

            <!-- ══ توقيع الطبيب ══ -->
            <div class="pif-rapport-footer">
                <div class="pif-rapport-signature-block">
                    <div class="pif-rapport-signature-label">Médecin traitant</div>
                    <div class="pif-rapport-signature-line">Signature &amp; Cachet</div>
                </div>
            </div>

        </div>`;
    }

    /* ──────────────────────────────────────────────────────
       حفظ التقرير الطبي
    ────────────────────────────────────────────────────── */
    window.saveRapportMedical = function () {
        const pid = currentPatientId;
        if (!pid) return;

        const getVal = (id) => { const el = document.getElementById(id + '_' + pid); return el ? el.value.trim() : ''; };

        const btn = document.querySelector(`#pif-sec-rapport-${pid} .pif-btn-success`);

        // UI: حالة التحميل
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:4px;"></i> جاري الحفظ...'; }

        const formData = new FormData();
        formData.append('action',          'save_rapport_medical');
        formData.append('patient_id',      pid);
        formData.append('rapport_date',    getVal('rapport_date'));
        formData.append('rapport_patient', getVal('rapport_patient_name'));
        formData.append('rapport_age',     getVal('rapport_age'));
        formData.append('rapport_doctor',  getVal('rapport_doctor'));
        formData.append('rapport_content', getVal('rapport_content'));

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) formData.append('_token', csrfMeta.content);

        fetch(window.RAPPORT_SAVE_URL || 'rapport_medical_api.php', {
            method: 'POST', body: formData, credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(res => {
            if (btn) {
                btn.disabled = false;
                if (res.success !== false) {
                    btn.innerHTML = '<i class="fas fa-check" style="margin-left:4px;"></i> تم الحفظ!';
                    btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
                    // تحديث Status Badge
                    const statusEl = document.getElementById(`status-pif-sec-rapport-${pid}`);
                    if (statusEl) { statusEl.textContent = '✓ محفوظ'; statusEl.classList.add('done'); }
                } else {
                    btn.innerHTML = '⚠️ خطأ في الحفظ';
                }
                setTimeout(() => {
                    btn.innerHTML = '💾 حفظ التقرير';
                    btn.style.background = '';
                }, 2800);
            }
        })
        .catch(() => {
            // Fallback: حفظ محلي
            const data = {
                rapport_date:    getVal('rapport_date'),
                rapport_patient: getVal('rapport_patient_name'),
                rapport_age:     getVal('rapport_age'),
                rapport_doctor:  getVal('rapport_doctor'),
                rapport_content: getVal('rapport_content'),
            };
            sessionStorage.setItem('rapport_' + pid, JSON.stringify(data));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-hdd" style="margin-left:4px;"></i> محفوظ محلياً';
                btn.style.background = 'linear-gradient(135deg,#f59e0b,#fbbf24)';
                setTimeout(() => { btn.innerHTML = '💾 حفظ التقرير'; btn.style.background = ''; }, 2800);
            }
        });
    };

    /* ──────────────────────────────────────────────────────
       طباعة التقرير الطبي — نافذة A4 رسمية
    ────────────────────────────────────────────────────── */
    window.printRapportMedical = function () {
        const pid = currentPatientId;
        if (!pid) return;

        const getVal = (id) => { const el = document.getElementById(id + '_' + pid); return el ? el.value.trim() : ''; };
        const esc    = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

        const hospitalName    = window.HOSPITAL_NAME    || 'Centre Hospitalo-Universitaire - Hassani Abdelkader Sidi Bel Abbes';
        const hospitalService = window.HOSPITAL_SERVICE || 'Service de Médecine Interne';
        const chefService     = window.CHEF_SERVICE     || 'Pr. ST HEBRI';

        const dateVal = getVal('rapport_date');
        const displayDate = dateVal
            ? new Date(dateVal).toLocaleDateString('fr-DZ', { day:'2-digit', month:'2-digit', year:'numeric' })
            : new Date().toLocaleDateString('fr-DZ');

        const win = window.open('', '_blank', 'width=860,height=1100');
        if (!win) { alert('يرجى السماح بالنوافذ المنبثقة للطباعة.'); return; }

        win.document.write(`<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Rapport Médical — ${esc(getVal('rapport_patient_name'))}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Times New Roman',Times,serif;color:#000;background:#fff;}
.page{width:210mm;min-height:297mm;padding:18mm 20mm 24mm;margin:0 auto;position:relative;}
/* Header */
.rp-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #000;gap:16px;}
.rp-inst{font-size:11px;line-height:1.7;flex:1;}
.rp-inst .inst-main{font-weight:700;font-size:11.5px;text-decoration:underline;display:block;margin-bottom:2px;}
.rp-inst .inst-service{font-style:italic;text-decoration:underline;}
.rp-logo{border:2px solid #555;padding:10px 16px;text-align:center;min-width:80px;}
.rp-logo .logo-text{font-size:24px;font-weight:900;font-family:Arial,sans-serif;letter-spacing:-1px;line-height:1;}
.rp-logo .logo-sub{font-size:7px;color:#555;line-height:1.4;margin-top:4px;}
.rp-doctor{font-size:11px;line-height:1.7;text-align:right;flex:1;}
.rp-doctor .doc-lbl{text-decoration:underline;font-weight:700;display:block;}
/* Titre */
.rp-title{text-align:center;font-size:18px;font-weight:900;letter-spacing:5px;text-transform:uppercase;margin:22px 0 8px;}
.rp-title-line{width:80px;border-top:3px solid #000;margin:0 auto 22px;}
/* Patient */
.rp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 30px;margin-bottom:20px;font-size:12px;}
.rp-row{display:flex;align-items:baseline;gap:6px;border-bottom:1px solid #bbb;padding-bottom:3px;}
.rp-row label{font-weight:700;white-space:nowrap;}
.rp-row span{flex:1;min-height:16px;}
/* Content */
.rp-content{min-height:155mm;font-size:13px;line-height:2;white-space:pre-wrap;padding:0 4px;
  background:repeating-linear-gradient(transparent,transparent 31px,#ddd 31px,#ddd 32px);}
/* Signature */
.rp-sig{position:absolute;bottom:22mm;right:22mm;text-align:center;}
.rp-sig .sig-title{font-size:12px;font-weight:700;margin-bottom:38px;}
.rp-sig .sig-line{width:130px;border-top:1px solid #000;margin:0 auto;padding-top:4px;font-size:10px;color:#555;}
/* Print controls */
.no-print{text-align:center;padding:14px;background:#f0f9ff;border-bottom:1px solid #bae6fd;}
.no-print button{background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;font-weight:700;margin:0 5px;}
.no-print .btn-close{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;}
@media print{.no-print{display:none!important;}.page{padding:15mm 18mm 18mm;}}
</style>
</head>
<body>
<div class="no-print">
  <button onclick="window.print()">🖨️ طباعة / Imprimer</button>
  <button class="btn-close" onclick="window.close()">✕ إغلاق</button>
</div>
<div class="page">
  <div class="rp-header">
    <div class="rp-inst">
      <span class="inst-main">${esc(hospitalName)}</span>
      Médecin chef service ${esc(chefService)}<br>
      <span class="inst-service">${esc(hospitalService)}</span>
    </div>
    <div class="rp-logo">
      <div class="logo-text">CHU</div>
      <div class="logo-sub">المركز الاستشفائي<br>الجامعي<br>عبد القادر حساني</div>
    </div>
    <div class="rp-doctor">
      <span class="doc-lbl">Médecin traitant :</span>
      ${esc(getVal('rapport_doctor'))}
    </div>
  </div>

  <div class="rp-title">RAPPORT MÉDICAL</div>
  <div class="rp-title-line"></div>

  <div class="rp-grid">
    <div class="rp-row"><label>Le :</label><span>${esc(displayDate)}</span></div>
    <div class="rp-row"><label>Patient(e) :</label><span>${esc(getVal('rapport_patient_name'))}</span></div>
    <div class="rp-row"><label>Age :</label><span>${esc(getVal('rapport_age'))}</span></div>
    <div class="rp-row"><label>Médecin traitant :</label><span>${esc(getVal('rapport_doctor'))}</span></div>
  </div>

  <div class="rp-content">${esc(getVal('rapport_content'))}</div>

  <div class="rp-sig">
    <div class="sig-title">Médecin traitant</div>
    <div class="sig-line">Signature &amp; Cachet</div>
  </div>
</div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),400));<\/script>
</body></html>`);
        win.document.close();
    };

    /* ──────────────────────────────────────────────────────
       Override openPatientFile — إبقاء الـ Logic الأصلي
       يُخفي الـ Modal ويستبدله بالـ Inline
    ────────────────────────────────────────────────────── */
    window._origOpenPatientFile = window.openPatientFile;
    window.openPatientFile = function (patientId) {
        // نمنع فتح الـ Modal
        const modal = document.getElementById('patientFileModal');
        if (modal) modal.style.display = 'none';
        // نسجّل الـ patient_id الأصلي
        const pidEl = document.getElementById('medical_patient_id');
        if (pidEl) pidEl.value = patientId;
    };

    /* Override saveMedicalRecord ليعمل مع الـ Inline */
    const _origSave = window.saveMedicalRecord;
    window.saveMedicalRecord = function () {
        if (currentPatientId) syncMirrorsToOriginal(currentPatientId);
        if (typeof _origSave === 'function') _origSave();
    };

    /* Override savePrescription */
    const _origSavePx = window.savePrescription;
    window.savePrescription = function () {
        if (currentPatientId) syncMirrorsToOriginal(currentPatientId);
        if (typeof _origSavePx === 'function') _origSavePx();
    };

    /* Override printPrescription */
    const _origPrint = window.printPrescription;
    window.printPrescription = function () {
        if (currentPatientId) syncMirrorsToOriginal(currentPatientId);
        if (typeof _origPrint === 'function') _origPrint();
    };

    /* Override printMedicalRecord */
    const _origPrintMed = window.printMedicalRecord;
    window.printMedicalRecord = function () {
        if (currentPatientId) syncMirrorsToOriginal(currentPatientId);
        if (typeof _origPrintMed === 'function') _origPrintMed();
    };

    /* printFicheTraitement — طباعة فيش العلاج للممرض */
    window.printFicheTraitement = function () {
        const pid = currentPatientId;
        const get = (id) => {
            const el = document.getElementById('mirror_fiche_' + id + '_' + pid)
                     || document.getElementById('fiche_' + id);
            return el ? el.value : '';
        };
        const patientName = (document.getElementById('mirror_rx_patient_name_' + pid)
                          || document.getElementById('rx_patient_name')
                          || document.getElementById('mirror_full_name_' + pid)
                          || {}).value || '';
        const rxDate = (document.getElementById('mirror_rx_date_' + pid)
                     || document.getElementById('rx_date')
                     || {}).value || new Date().toLocaleDateString('fr-DZ');

        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<title>Fiche de traitement</title>
<style>
  body{font-family:Arial,sans-serif;padding:28px;color:#1e293b;direction:rtl;}
  h1{color:#0ea5e9;font-size:1.4rem;border-bottom:3px solid #0ea5e9;padding-bottom:8px;}
  .pi{background:#f0f9ff;padding:8px 14px;border-radius:8px;margin-bottom:16px;font-size:.9rem;}
  .sec h3{background:#0ea5e9;color:#fff;padding:5px 12px;border-radius:6px;font-size:.85rem;margin-bottom:6px;}
  .sec p{white-space:pre-wrap;background:#f8fafc;padding:8px 12px;border-radius:6px;border-right:3px solid #0ea5e9;min-height:36px;font-size:.85rem;margin:0 0 12px;}
  .footer{margin-top:24px;border-top:1px solid #e2e8f0;padding-top:12px;font-size:.75rem;color:#64748b;}
  @media print{body{padding:8px;}}
</style>
</head>
<body>
<h1>💉 Fiche de traitement</h1>
<div class="pi"><strong>👤 المريض:</strong> ${patientName} &nbsp;&nbsp; <strong>📅 التاريخ:</strong> ${rxDate}</div>
<div class="sec"><h3>🩺 التشخيص / Diagnostic</h3><p>${get('diagnostic') || '—'}</p></div>
<div class="sec"><h3>💊 الأدوية والعلاجات / Médicaments &amp; traitements</h3><p>${get('medications') || '—'}</p></div>
<div class="footer">MedChifaGiz — وثيقة داخلية للاستخدام الطبي فقط</div>
<script>window.onload=function(){window.print();}<\/script>
</body></html>`);
        win.document.close();
    };

    /* ── دالة مساعدة: إظهار toast للمريض الحالي ── */
    function pifShowToast(msg, type) {
        if (typeof showAddPatientToast === 'function') {
            showAddPatientToast(msg, type);
            return;
        }
        var colors = { success: '#10b981', error: '#ef4444', warn: '#f59e0b' };
        var el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
            'background:' + (colors[type] || colors.success) + ';color:#fff;padding:12px 24px;' +
            'border-radius:12px;font-family:Cairo,sans-serif;font-size:.9rem;z-index:99999;' +
            'box-shadow:0 4px 20px rgba(0,0,0,.2);direction:rtl;';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3000);
    }

    /* ── دالة مساعدة: قراءة قيمة mirror أو original ── */
    function pifGetMirror(fieldId, pid) {
        var el = document.getElementById('mirror_' + fieldId + '_' + pid)
              || document.getElementById(fieldId);
        return el ? el.value.trim() : '';
    }

    /* 📤 إرسال الوصفة للصيدلي — إرسال حقيقي لـ pharmacy_api.php */
    window.sendPrescriptionToPharmacy = function () {
        var pid = currentPatientId;
        if (!pid) { pifShowToast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }

        // مزامنة الـ mirrors أولاً
        syncMirrorsToOriginal(pid);

        var medicines = pifGetMirror('prescription', pid);
        if (!medicines) { pifShowToast('⚠️ اكتب الأدوية أولاً قبل الإرسال', 'warn'); return; }

        var fd = new FormData();
        fd.append('action',       'send_prescription');
        fd.append('patient_id',   pid);
        fd.append('medicines',    medicines);
        fd.append('patient_name', pifGetMirror('full_name', pid) || pifGetMirror('rx_patient_name', pid));
        fd.append('birth_info',   pifGetMirror('birth_info', pid));
        fd.append('diagnostic',   pifGetMirror('reason_exam', pid));
        fd.append('notes',        pifGetMirror('doctor_notes', pid));
        fd.append('rx_date',      pifGetMirror('rx_date', pid) || new Date().toISOString().split('T')[0]);
        fd.append('rx_time',      new Date().toTimeString().slice(0, 5));

        fetch(window.PHARMACY_API_URL || 'pharmacy_api.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.success) {
                pifShowToast('✅ تم إرسال الوصفة إلى الصيدلية', 'success');
            } else {
                pifShowToast('❌ ' + (res.message || 'تعذّر إرسال الوصفة'), 'error');
            }
        })
        .catch(function () { pifShowToast('❌ خطأ في الاتصال بالصيدلية', 'error'); });
    };

    /* 📤 إرسال fiche de traitement للممرض — إرسال حقيقي لـ nurse_treatment_api.php */
    window.sendFicheToNurse = function () {
        var pid = currentPatientId;
        if (!pid) { pifShowToast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }

        // مزامنة الـ mirrors أولاً
        syncMirrorsToOriginal(pid);

        var medications = pifGetMirror('fiche_medications', pid);
        if (!medications) { pifShowToast('⚠️ اكتب العلاجات أولاً قبل الإرسال', 'warn'); return; }

        var fd = new FormData();
        fd.append('action',       'send_treatment');
        fd.append('patient_id',   pid);
        fd.append('treatments',   medications);
        fd.append('patient_name', pifGetMirror('full_name', pid));
        fd.append('birth_info',   pifGetMirror('birth_info', pid));
        fd.append('diagnostic',   pifGetMirror('fiche_diagnostic', pid));
        fd.append('motif',        pifGetMirror('reason_exam', pid));
        fd.append('room',         pifGetMirror('room_number', pid));
        fd.append('admission_date', pifGetMirror('admission_date', pid));

        fetch(window.NURSE_API_URL || 'nurse_treatment_api.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.success) {
                pifShowToast('✅ تم إرسال fiche العلاج إلى الممرض', 'success');
            } else {
                pifShowToast('❌ ' + (res.message || 'تعذّر إرسال fiche العلاج'), 'error');
            }
        })
        .catch(function () { pifShowToast('❌ خطأ في الاتصال بلوحة الممرض', 'error'); });
    };

    /* 📤 إرسال طلب تحاليل للمخبر — إرسال حقيقي لـ lab_radiology_api.php */
    window.sendToLab = function () {
        var pid = currentPatientId;
        if (!pid) { pifShowToast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }

        syncMirrorsToOriginal(pid);

        var analysisText = pifGetMirror('medical_tests', pid);
        if (!analysisText) { pifShowToast('⚠️ اكتب التحاليل المطلوبة أولاً', 'warn'); return; }

        var fd = new FormData();
        fd.append('action',        'send_lab_request');
        fd.append('patient_id',    pid);
        fd.append('analysis_text', analysisText);

        fetch(window.LAB_RADIOLOGY_API_URL || 'lab_radiology_api.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.success) {
                pifShowToast('✅ تم إرسال طلب التحاليل إلى المخبر', 'success');
            } else {
                pifShowToast('❌ ' + (res.message || 'تعذّر إرسال طلب المخبر'), 'error');
            }
        })
        .catch(function () { pifShowToast('❌ خطأ في الاتصال بالمخبر', 'error'); });
    };

    /* 📤 إرسال طلب أشعة — إرسال حقيقي لـ lab_radiology_api.php */
    window.sendToRadiology = function () {
        var pid = currentPatientId;
        if (!pid) { pifShowToast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }

        syncMirrorsToOriginal(pid);

        var radiologyText = pifGetMirror('radiology', pid);
        if (!radiologyText) { pifShowToast('⚠️ اكتب فحوصات الأشعة المطلوبة أولاً', 'warn'); return; }

        var fd = new FormData();
        fd.append('action',         'send_radiology_request');
        fd.append('patient_id',     pid);
        fd.append('radiology_text', radiologyText);

        fetch(window.LAB_RADIOLOGY_API_URL || 'lab_radiology_api.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.success) {
                pifShowToast('✅ تم إرسال طلب الأشعة', 'success');
            } else {
                pifShowToast('❌ ' + (res.message || 'تعذّر إرسال طلب الأشعة'), 'error');
            }
        })
        .catch(function () { pifShowToast('❌ خطأ في الاتصال بقسم الأشعة', 'error'); });
    };

    /* ──────────────────────────────────────────────────────
       START
    ────────────────────────────────────────────────────── */
    // حقن CSS الخاص بقسم التقرير الطبي
    (function injectRapportCSS() {
        if (document.getElementById('pif-rapport-styles')) return;
        const s = document.createElement('style');
        s.id = 'pif-rapport-styles';
        s.textContent = `
/* ═══ RAPPORT MÉDICAL STYLES ═══ */
.pif-rapport-sheet{background:#fff;border-radius:14px;border:1px solid rgba(14,165,233,.15);overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.04);font-family:'Times New Roman',serif;}
body.dark-mode .pif-rapport-sheet{background:#1e293b;border-color:rgba(255,255,255,.07);}

.pif-rapport-header{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;padding:14px 18px 12px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:2px solid rgba(14,165,233,.2);}
body.dark-mode .pif-rapport-header{background:linear-gradient(135deg,#0f172a,#1e293b);}

.pif-rapport-header-left{text-align:right;direction:rtl;}
.pif-rapport-institution{font-size:.7rem;font-weight:700;color:#0f172a;line-height:1.55;font-family:'Cairo',sans-serif;}
body.dark-mode .pif-rapport-institution{color:#e2e8f0;}
.pif-rapport-institution .inst-main{font-size:.75rem;font-weight:800;color:#0ea5e9;border-bottom:1px solid #0ea5e9;padding-bottom:2px;margin-bottom:4px;display:block;}
.pif-rapport-institution .inst-service{font-size:.65rem;color:#64748b;font-style:italic;text-decoration:underline;}

.pif-rapport-logo{display:flex;flex-direction:column;align-items:center;gap:4px;}
.pif-rapport-logo-chu{width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(14,165,233,.35);}
.pif-rapport-logo-chu span{font-size:1.35rem;font-weight:900;color:#fff;letter-spacing:-1px;font-family:'Arial Black',sans-serif;}
.pif-rapport-logo-sub{font-size:.5rem;color:#64748b;text-align:center;line-height:1.3;font-family:'Cairo',sans-serif;max-width:68px;}

.pif-rapport-header-right{text-align:left;direction:ltr;font-family:'Cairo',sans-serif;}
.pif-rapport-doctor-name{font-size:.72rem;font-weight:700;color:#0f172a;line-height:1.7;}
body.dark-mode .pif-rapport-doctor-name{color:#e2e8f0;}
.pif-rapport-doctor-name span:first-child{display:block;font-size:.65rem;color:#64748b;font-weight:600;}

.pif-rapport-title-bar{text-align:center;padding:13px 18px;border-bottom:1px solid rgba(14,165,233,.1);background:#fafcff;}
body.dark-mode .pif-rapport-title-bar{background:#0f172a;}
.pif-rapport-title-bar h2{font-size:1.05rem;font-weight:900;color:#0f172a;letter-spacing:3px;text-transform:uppercase;margin:0;font-family:'Times New Roman',serif;}
body.dark-mode .pif-rapport-title-bar h2{color:#f1f5f9;}
.rapport-title-line{width:56px;height:3px;background:linear-gradient(90deg,#0ea5e9,#06b6d4);margin:8px auto 0;border-radius:3px;}

.pif-rapport-patient-info{padding:12px 18px;border-bottom:1px solid rgba(14,165,233,.08);display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;direction:ltr;font-family:'Cairo',sans-serif;}
.pif-rapport-info-row{display:flex;align-items:baseline;gap:6px;font-size:.8rem;}
.pif-rapport-info-row label{font-weight:700;color:#475569;white-space:nowrap;font-size:.76rem!important;margin-bottom:0!important;}
body.dark-mode .pif-rapport-info-row label{color:#94a3b8;}
.pif-rapport-info-row input{border:none!important;border-bottom:1px dashed rgba(14,165,233,.4)!important;border-radius:0!important;background:transparent!important;padding:2px 4px!important;font-size:.82rem!important;font-weight:600!important;color:#0f172a!important;flex:1;min-width:0;outline:none!important;box-shadow:none!important;font-family:'Cairo',sans-serif;}
body.dark-mode .pif-rapport-info-row input{color:#f1f5f9!important;border-bottom-color:rgba(14,165,233,.3)!important;}
.pif-rapport-info-row input:focus{border-bottom-color:#0ea5e9!important;box-shadow:none!important;background:rgba(14,165,233,.03)!important;}

.pif-rapport-body{padding:14px 18px;border-bottom:1px solid rgba(14,165,233,.08);min-height:180px;background:repeating-linear-gradient(transparent,transparent 31px,rgba(14,165,233,.06) 31px,rgba(14,165,233,.06) 32px);}
body.dark-mode .pif-rapport-body{background:repeating-linear-gradient(transparent,transparent 31px,rgba(255,255,255,.04) 31px,rgba(255,255,255,.04) 32px);}
.pif-rapport-body-label{font-size:.7rem;font-weight:700;color:#0ea5e9;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;font-family:'Cairo',sans-serif;display:flex;align-items:center;gap:6px;}
.pif-rapport-body-label::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(14,165,233,.3),transparent);}
.pif-rapport-body textarea{width:100%!important;min-height:160px!important;border:none!important;border-radius:0!important;background:transparent!important;resize:vertical!important;font-size:.9rem!important;font-family:'Times New Roman',Times,serif!important;color:#1e293b!important;line-height:32px!important;padding:0 4px!important;outline:none!important;box-shadow:none!important;}
body.dark-mode .pif-rapport-body textarea{color:#e2e8f0!important;}

.pif-rapport-footer{display:flex;justify-content:flex-end;padding:12px 18px 16px;background:#fafcff;}
body.dark-mode .pif-rapport-footer{background:#0f172a;}
.pif-rapport-signature-block{text-align:center;}
.pif-rapport-signature-label{font-size:.7rem;font-weight:700;color:#475569;margin-bottom:36px;font-family:'Cairo',sans-serif;}
body.dark-mode .pif-rapport-signature-label{color:#94a3b8;}
.pif-rapport-signature-line{width:130px;border-top:1px solid #94a3b8;margin:0 auto;padding-top:4px;font-size:.62rem;color:#94a3b8;font-family:'Cairo',sans-serif;}

@media(max-width:520px){
  .pif-rapport-header{grid-template-columns:1fr;}
  .pif-rapport-header-left,.pif-rapport-header-right{text-align:center;direction:ltr;}
  .pif-rapport-patient-info{grid-template-columns:1fr;}
}`;
        document.head.appendChild(s);
    })();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
