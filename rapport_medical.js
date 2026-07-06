/**
 * rapport_medical.js
 * ═══════════════════════════════════════════════════════════════
 * يضيف قسم "التقرير الطبي / Rapport médical" تلقائياً
 * بين "الوصفة الطبية" (pif-sec-4) و"المواعيد القادمة" (pif-sec-6)
 * في بطاقة كل مريض.
 *
 * كيفية الإضافة قبل </body> بعد patient_inline_v2.js:
 *   <script src="rapport_medical.js"></script>
 *
 * المميزات:
 *  - نموذج التقرير بتنسيق ورق CHU رسمي (مطابق للصورة)
 *  - حقول قابلة للكتابة (اسم المريض، السن، الطبيب المعالج، المحتوى)
 *  - زر حفظ (AJAX مشابه لـ savePrescription الأصلي)
 *  - زر طباعة يفتح نافذة بتنسيق A4 رسمي
 *  - تكامل كامل مع نظام الـ mirrors والـ accordion
 * ═══════════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       CONFIG — بيانات المستشفى (عدّلها حسب إعداداتك)
    ────────────────────────────────────────────────────────── */
    const HOSPITAL_CONFIG = {
        name:    window.HOSPITAL_NAME    || 'Centre Hospitalo-Universitaire - Hassani Abdelkader Sidi Bel Abbes',
        service: window.HOSPITAL_SERVICE || 'Service de Médecine Interne',
        chef:    window.CHEF_SERVICE     || 'Pr. ST HEBRI',
        city:    window.HOSPITAL_CITY    || 'Sidi Bel Abbes',
    };

    /* ──────────────────────────────────────────────────────────
       SECTION DEFINITION — نفس بنية SECTIONS في patient_inline.js
    ────────────────────────────────────────────────────────── */
    const RAPPORT_SECTION_COLOR = {
        gradient: '#7c3aed, #a855f7',
        glow: 'rgba(124, 58, 237, 0.22)',
    };

    /* ──────────────────────────────────────────────────────────
       MAIN INIT — يستمع لحدث pif:opened لإضافة القسم
    ────────────────────────────────────────────────────────── */
    function init() {
        // يستمع لكل مرة يُفتح فيها ملف مريض
        document.addEventListener('pif:opened', function (e) {
            const pid = e.detail && e.detail.patientId;
            if (!pid) return;
            setTimeout(function () {
                injectRapportSection(pid, e.detail.patientName || '');
            }, 120);
        });

        // fallback: نراقب DOM مباشرة لو pif:opened لم يُطلق
        document.addEventListener('click', function (e) {
            const hd = e.target.closest('.patient-item-row, .patient-expand-btn');
            if (!hd) return;
            const item = hd.closest('.patient-item');
            if (!item) return;
            const pid = item.getAttribute('data-patient-id');
            if (!pid) return;
            setTimeout(function () {
                injectRapportSection(pid, item.getAttribute('data-patient-name') || '');
            }, 350);
        });
    }

    /* ──────────────────────────────────────────────────────────
       INJECT — يُدرج قسم التقرير في المكان الصحيح
    ────────────────────────────────────────────────────────── */
    function injectRapportSection(patientId, patientName) {
        const acc = document.getElementById('pif-acc-' + patientId);
        if (!acc) return;

        // تحقق: هل القسم موجود بالفعل؟
        if (document.getElementById('pif-sec-rapport-' + patientId)) return;

        // ابحث عن قسم pif-sec-4 (الوصفة) لنضع التقرير بعده
        const rx = document.getElementById('pif-sec-4-' + patientId);
        if (!rx) return; // لم يُبنَ الـ accordion بعد، ننتظر

        // بناء الـ section element
        const section = buildRapportSection(patientId, patientName);

        // إدراجه بعد الوصفة وقبل المواعيد
        rx.insertAdjacentElement('afterend', section);

        // تلوين الأيقونة
        styleRapportIcon(section);

        // ربط الأحداث
        wireRapportEvents(section, patientId, patientName);

        // إضافة زر حفظ لـ Fiche de traitement إن وُجد القسم pif-sec-3
        injectFicheSaveButton(patientId);
    }

    /* ──────────────────────────────────────────────────────────
       INJECT FICHE SAVE BUTTON — يُضيف زر حفظ لقسم الفيش
       القسم pif-sec-3-{id} في patient_inline.js هو Fiche de traitement
       الحقول: fiche_diagnostic_{id} / fiche_medications_{id}
    ────────────────────────────────────────────────────────── */
    function injectFicheSaveButton(patientId) {
        /* ابحث عن قسم الفيش — يحمل عادةً id="pif-sec-3-{id}" أو data-sec="fiche" */
        const ficheSection = document.getElementById('pif-sec-3-' + patientId)
                          || (function(){
                                const all = document.querySelectorAll('#pif-acc-' + patientId + ' .pif-section');
                                for (let s of all) {
                                    const title = s.querySelector('.pif-sec-title');
                                    if (title && title.textContent.toLowerCase().includes('fiche')) return s;
                                }
                                return null;
                             })();
        if (!ficheSection) return;

        /* تجنب الإضافة المزدوجة */
        if (ficheSection.querySelector('.fiche-save-btn-injected')) return;

        /* أضف IDs للحقول الموجودة إن لم تكن مُعرَّفة */
        const diagEl = ficheSection.querySelector('textarea[placeholder*="تشخيص"], textarea[placeholder*="diagnostic"], textarea[placeholder*="Diagnostic"]');
        if (diagEl && !diagEl.id) diagEl.id = 'fiche_diagnostic_' + patientId;

        const medsEl = ficheSection.querySelector('textarea[placeholder*="دواء"], textarea[placeholder*="دوية"], textarea[placeholder*="Paracetamol"], textarea[placeholder*="médic"]');
        if (medsEl && !medsEl.id) medsEl.id = 'fiche_medications_' + patientId;

        /* أضف زر الحفظ في شريط الأزرار */
        const actionsRow = ficheSection.querySelector('.pif-actions-row, .pif-rapport-actions, .pif-sec-body > div:last-child');
        if (!actionsRow) return;

        const saveBtn = document.createElement('button');
        saveBtn.className   = 'pif-btn pif-btn-success fiche-save-btn-injected';
        saveBtn.id          = 'fiche-save-btn-' + patientId;
        saveBtn.style.cssText = 'margin-left:6px;';
        saveBtn.innerHTML   = '<i class="fas fa-save" style="margin-left:5px;"></i> حفظ الفيش';
        saveBtn.onclick     = function() {
            if (typeof window.saveFicheTraitement === 'function') {
                window.saveFicheTraitement(patientId);
            }
        };

        actionsRow.insertBefore(saveBtn, actionsRow.firstChild);
    }

    /* ──────────────────────────────────────────────────────────
       BUILD — بناء عنصر القسم كاملاً
    ────────────────────────────────────────────────────────── */
    function buildRapportSection(patientId, patientName) {
        const todayDate = new Date().toLocaleDateString('fr-DZ', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });

        const div = document.createElement('div');
        div.className = 'pif-section';
        div.id = 'pif-sec-rapport-' + patientId;

        /* ── Header ── */
        const header = document.createElement('div');
        header.className = 'pif-sec-header';
        header.setAttribute('role', 'button');
        header.setAttribute('aria-expanded', 'false');
        header.innerHTML = `
            <div class="pif-sec-icon" id="rapport-icon-${patientId}">
                <i class="fas fa-file-medical-alt"></i>
            </div>
            <span class="pif-sec-title">التقرير الطبي / Rapport médical</span>
            <span class="pif-sec-status" id="rapport-status-${patientId}">فارغ</span>
            <i class="fas fa-chevron-down pif-sec-arrow"></i>
        `;

        header.addEventListener('click', function () {
            toggleRapportSection(div, patientId);
        });

        /* ── Body ── */
        const body = document.createElement('div');
        body.className = 'pif-sec-body';

        const inner = document.createElement('div');
        inner.className = 'pif-sec-body-inner';
        inner.style.padding = '0'; // نتحكم نحن في الـ padding

        inner.innerHTML = buildRapportHTML(patientId, patientName, todayDate);

        body.appendChild(inner);

        /* ── Actions Row ── */
        const actRow = document.createElement('div');
        actRow.className = 'pif-rapport-actions';
        actRow.innerHTML = `
            <button class="pif-btn pif-btn-success" id="rapport-save-btn-${patientId}"
                    onclick="saveRapportMedical('${patientId}')">
                <i class="fas fa-save" style="margin-left:6px;"></i> حفظ التقرير
            </button>
            <button class="pif-btn pif-btn-ghost" id="rapport-print-btn-${patientId}"
                    onclick="printRapportMedical('${patientId}')">
                <i class="fas fa-print" style="margin-left:6px;"></i> طباعة PDF
            </button>
        `;

        body.appendChild(actRow);

        div.appendChild(header);
        div.appendChild(body);
        return div;
    }

    /* ──────────────────────────────────────────────────────────
       HTML — محتوى التقرير (نموذج CHU)
    ────────────────────────────────────────────────────────── */
    function buildRapportHTML(patientId, patientName, date) {
        return `
        <div class="pif-rapport-sheet">

            <!-- ══ ترويسة CHU ══ -->
            <div class="pif-rapport-header">

                <!-- يسار: بيانات المستشفى -->
                <div class="pif-rapport-header-left">
                    <div class="pif-rapport-institution">
                        <div class="inst-main">${HOSPITAL_CONFIG.name}</div>
                        <div>Médecin chef service <strong>${HOSPITAL_CONFIG.chef}</strong></div>
                        <div class="inst-service">${HOSPITAL_CONFIG.service}</div>
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

                <!-- يمين: اسم الطبيب -->
                <div class="pif-rapport-header-right">
                    <div class="pif-rapport-doctor-name">
                        <span>Médecin traitant :</span>
                        <input type="text"
                               id="rapport_doctor_name_${patientId}"
                               value="${window.DOCTOR_NAME || ''}"
                               placeholder="Dr. ..."
                               style="width:100%;font-size:0.8rem;font-weight:700;border:none;border-bottom:1px dashed rgba(14,165,233,0.4);background:transparent;outline:none;color:inherit;padding:2px 4px;font-family:Cairo,sans-serif;">
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
                           value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Patient(e) :</label>
                    <input type="text"
                           id="rapport_patient_name_${patientId}"
                           value="${patientName}"
                           placeholder="اسم المريض...">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Age :</label>
                    <input type="text"
                           id="rapport_age_${patientId}"
                           placeholder="السن...">
                </div>
                <div class="pif-rapport-info-row">
                    <label>Médecin traitant :</label>
                    <input type="text"
                           id="rapport_doctor_${patientId}"
                           value="${window.DOCTOR_NAME || ''}"
                           placeholder="Dr. ...">
                </div>
            </div>

            <!-- ══ محتوى التقرير ══ -->
            <div class="pif-rapport-body">
                <div class="pif-rapport-body-label">
                    <i class="fas fa-pen-alt" style="font-size:.65rem;"></i>
                    محتوى التقرير
                </div>
                <textarea id="rapport_content_${patientId}"
                          rows="10"
                          placeholder="اكتب محتوى التقرير الطبي هنا...&#10;&#10;يمكن ذكر: التشخيص، الأعراض، العلاج المتبع، التوصيات، الاستشارات..."
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

    /* ──────────────────────────────────────────────────────────
       STYLE ICON — تلوين الأيقونة بنمط مميز
    ────────────────────────────────────────────────────────── */
    function styleRapportIcon(section) {
        const icon = section.querySelector('.pif-sec-icon');
        if (icon) {
            icon.style.background  = `linear-gradient(135deg, ${RAPPORT_SECTION_COLOR.gradient})`;
            icon.style.color       = '#fff';
            icon.style.boxShadow   = `0 4px 10px ${RAPPORT_SECTION_COLOR.glow}`;
        }
    }

    /* ──────────────────────────────────────────────────────────
       WIRE EVENTS — ربط أحداث التغيير
    ────────────────────────────────────────────────────────── */
    function wireRapportEvents(section, patientId) {
        // تحديث Status Badge عند الكتابة
        const textArea = section.querySelector(`#rapport_content_${patientId}`);
        if (textArea) {
            textArea.addEventListener('input', function () {
                updateRapportStatus(patientId);
            });
        }

        // نفس الشيء لكل الحقول
        section.querySelectorAll('input, textarea').forEach(function (el) {
            el.addEventListener('input', function () { updateRapportStatus(patientId); });
        });
    }

    /* ──────────────────────────────────────────────────────────
       STATUS BADGE
    ────────────────────────────────────────────────────────── */
    function updateRapportStatus(patientId) {
        const statusEl = document.getElementById('rapport-status-' + patientId);
        if (!statusEl) return;

        const content = document.getElementById('rapport_content_' + patientId);
        const hasContent = content && content.value.trim().length > 0;

        statusEl.textContent = hasContent ? '✓ مكتمل' : 'فارغ';
        statusEl.classList.toggle('done', hasContent);
    }

    /* ──────────────────────────────────────────────────────────
       TOGGLE SECTION — فتح/إغلاق القسم
    ────────────────────────────────────────────────────────── */
    function toggleRapportSection(sectionEl, patientId) {
        const isOpen = sectionEl.classList.contains('pif-open');

        // أغلق بقية الأقسام في نفس الـ accordion
        const acc = sectionEl.closest('.pif-accordion');
        if (acc) {
            acc.querySelectorAll('.pif-section').forEach(function (s) {
                s.classList.remove('pif-open');
            });
        }

        if (!isOpen) {
            sectionEl.classList.add('pif-open');
            sectionEl.style.boxShadow = `0 8px 28px ${RAPPORT_SECTION_COLOR.glow}`;

            // إضافة لون مميز للـ icon عند الفتح (يعاد لأن الـ CSS يغير اللون)
            setTimeout(function () { styleRapportIcon(sectionEl); }, 50);
        } else {
            sectionEl.style.boxShadow = '';
        }
    }

    /* ──────────────────────────────────────────────────────────
       SAVE — حفظ التقرير في قاعدة البيانات
    ────────────────────────────────────────────────────────── */
    window.saveRapportMedical = function (patientId) {
        const btn = document.getElementById('rapport-save-btn-' + patientId);

        // جمع البيانات
        const data = collectRapportData(patientId);

        // UI: حالة التحميل
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:6px;"></i> جاري الحفظ...';
        }

        // AJAX request
        const formData = new FormData();
        formData.append('action',         'save_rapport_medical');
        formData.append('patient_id',     patientId);
        formData.append('rapport_date',   data.date);
        formData.append('rapport_patient',data.patientName);
        formData.append('rapport_age',    data.age);
        formData.append('rapport_doctor', data.doctor);
        formData.append('rapport_content',data.content);

        // نضيف CSRF token إذا كان موجوداً
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) formData.append('_token', csrfToken.getAttribute('content'));
        fetch(window.RAPPORT_SAVE_URL || 'save_rapport.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (btn) {
                if (res.success !== false) {
                    btn.innerHTML = '<i class="fas fa-check" style="margin-left:6px;"></i> تم الحفظ!';
                    btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
                    setTimeout(function () {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save" style="margin-left:6px;"></i> حفظ التقرير';
                        btn.style.background = '';
                    }, 2500);
                } else {
                    showRapportError(btn, res.message || 'خطأ في الحفظ');
                }
            }
        })
        .catch(function (err) {
            console.warn('[RapportMedical] Save error:', err);
            // fallback: حفظ محلي كـ sessionStorage
            sessionStorage.setItem('rapport_' + patientId, JSON.stringify(data));
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check" style="margin-left:6px;"></i> محفوظ محلياً';
                btn.style.background = 'linear-gradient(135deg,#f59e0b,#fbbf24)';
                setTimeout(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save" style="margin-left:6px;"></i> حفظ التقرير';
                    btn.style.background = '';
                }, 2500);
            }
        });
    };

    function showRapportError(btn, msg) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exclamation-triangle" style="margin-left:6px;"></i> ' + msg;
        btn.style.background = 'linear-gradient(135deg,#ef4444,#f87171)';
        setTimeout(function () {
            btn.innerHTML = '<i class="fas fa-save" style="margin-left:6px;"></i> حفظ التقرير';
            btn.style.background = '';
        }, 3000);
    }

    /* ──────────────────────────────────────────────────────────
       COLLECT DATA — جمع بيانات التقرير من الحقول
    ────────────────────────────────────────────────────────── */
    function collectRapportData(patientId) {
        function val(id) {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        }
        return {
            date:        val('rapport_date_'         + patientId),
            patientName: val('rapport_patient_name_' + patientId),
            age:         val('rapport_age_'          + patientId),
            /* BUG FIX: prefer the patient-info row input; fallback to header input */
            doctor:      val('rapport_doctor_'       + patientId) || val('rapport_doctor_name_' + patientId),
            content:     val('rapport_content_'      + patientId),
            doctorName:  val('rapport_doctor_name_'  + patientId),
        };
    }

    /* ──────────────────────────────────────────────────────────
       PRINT — طباعة التقرير بتنسيق A4 رسمي
    ────────────────────────────────────────────────────────── */
    window.printRapportMedical = function (patientId) {
        const data = collectRapportData(patientId);

        // تنسيق التاريخ للعرض
        const displayDate = data.date
            ? new Date(data.date).toLocaleDateString('fr-DZ', {
                  day: '2-digit', month: '2-digit', year: 'numeric'
              })
            : new Date().toLocaleDateString('fr-DZ');

        const win = window.open('', '_blank', 'width=850,height=1100');
        if (!win) { alert('يرجى السماح بالنوافذ المنبثقة لطباعة التقرير.'); return; }

        win.document.write(`<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Rapport Médical — ${data.patientName || 'Patient'}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Times New Roman', Times, serif;
    color: #000;
    background: #fff;
  }

  .page {
    width: 210mm;
    min-height: 297mm;
    padding: 18mm 20mm 20mm;
    margin: 0 auto;
    position: relative;
  }

  /* ── Header ── */
  .rp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #000;
    gap: 16px;
  }

  .rp-inst {
    font-size: 11px;
    line-height: 1.7;
    flex: 1;
  }

  .rp-inst .inst-title {
    font-weight: 700;
    font-size: 11.5px;
    text-decoration: underline;
    display: block;
    margin-bottom: 2px;
  }

  .rp-logo {
    border: 2px solid #555;
    padding: 10px 16px;
    text-align: center;
    min-width: 80px;
  }

  .rp-logo .logo-text {
    font-size: 22px;
    font-weight: 900;
    font-family: Arial, Helvetica, sans-serif;
    letter-spacing: -1px;
    color: #1a1a1a;
    line-height: 1;
  }

  .rp-logo .logo-sub {
    font-size: 7px;
    color: #555;
    line-height: 1.4;
    margin-top: 4px;
    text-align: center;
  }

  .rp-doctor {
    font-size: 11px;
    line-height: 1.7;
    text-align: right;
    flex: 1;
  }

  .rp-doctor .doc-label {
    text-decoration: underline;
    display: block;
    font-weight: 700;
  }

  /* ── Titre ── */
  .rp-title {
    text-align: center;
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 5px;
    text-transform: uppercase;
    margin: 22px 0 8px;
  }

  .rp-title-line {
    width: 80px;
    border-top: 3px solid #000;
    margin: 0 auto 22px;
  }

  /* ── Patient Info ── */
  .rp-patient-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 30px;
    margin-bottom: 20px;
    font-size: 12px;
  }

  .rp-patient-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    border-bottom: 1px solid #bbb;
    padding-bottom: 3px;
  }

  .rp-patient-row .row-label {
    font-weight: 700;
    white-space: nowrap;
  }

  .rp-patient-row .row-val {
    flex: 1;
    min-height: 16px;
  }

  /* ── Content ── */
  .rp-content-area {
    min-height: 160mm;
    font-size: 13px;
    line-height: 2;
    white-space: pre-wrap;
    padding: 0 4px;
    background: repeating-linear-gradient(
      transparent, transparent 31px, #ddd 31px, #ddd 32px
    );
  }

  /* ── Signature ── */
  .rp-signature {
    position: absolute;
    bottom: 22mm;
    right: 22mm;
    text-align: center;
  }

  .rp-signature .sig-title {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 38px;
  }

  .rp-signature .sig-line {
    width: 130px;
    border-top: 1px solid #000;
    margin: 0 auto;
    padding-top: 4px;
    font-size: 10px;
    color: #555;
  }

  @media print {
    body { margin: 0; }
    .page { width: 100%; padding: 15mm 18mm 18mm; }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>

<!-- زر الطباعة (يختفي عند الطباعة) -->
<div class="no-print" style="text-align:center;padding:14px;background:#f0f9ff;border-bottom:1px solid #bae6fd;">
  <button onclick="window.print()"
          style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;font-weight:700;box-shadow:0 4px 12px rgba(14,165,233,.3);">
    🖨️ طباعة / Imprimer
  </button>
  <button onclick="window.close()"
          style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:10px 20px;border-radius:8px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;font-weight:700;margin-right:10px;">
    ✕ إغلاق
  </button>
</div>

<div class="page">

  <!-- ══ Header ══ -->
  <div class="rp-header">
    <div class="rp-inst">
      <span class="inst-title">${escapeHtml(HOSPITAL_CONFIG.name)}</span>
      Médecin chef service ${escapeHtml(HOSPITAL_CONFIG.chef)}<br>
      <span style="text-decoration:underline;font-style:italic;">${escapeHtml(HOSPITAL_CONFIG.service)}</span>
    </div>
    <div class="rp-logo">
      <div class="logo-text">CHU</div>
      <div class="logo-sub">
        المركز الاستشفائي<br>الجامعي<br>عبد القادر حساني
      </div>
    </div>
    <div class="rp-doctor">
      <span class="doc-label">Médecin traitant :</span>
      ${escapeHtml(data.doctorName || data.doctor || '')}
    </div>
  </div>

  <!-- ══ Titre ══ -->
  <div class="rp-title">RAPPORT MÉDICAL</div>
  <div class="rp-title-line"></div>

  <!-- ══ Patient Info ══ -->
  <div class="rp-patient-grid">
    <div class="rp-patient-row">
      <span class="row-label">Le :</span>
      <span class="row-val">${escapeHtml(displayDate)}</span>
    </div>
    <div class="rp-patient-row">
      <span class="row-label">Patient(e) :</span>
      <span class="row-val">${escapeHtml(data.patientName)}</span>
    </div>
    <div class="rp-patient-row">
      <span class="row-label">Age :</span>
      <span class="row-val">${escapeHtml(data.age)}</span>
    </div>
    <div class="rp-patient-row">
      <span class="row-label">Médecin traitant :</span>
      <span class="row-val">${escapeHtml(data.doctor)}</span>
    </div>
  </div>

  <!-- ══ Content ══ -->
  <div class="rp-content-area">${escapeHtml(data.content)}</div>

  <!-- ══ Signature ══ -->
  <div class="rp-signature">
    <div class="sig-title">Médecin traitant</div>
    <div class="sig-line">Signature &amp; Cachet</div>
  </div>

</div>

<script>
  // طباعة تلقائية بعد تحميل الصفحة
  window.addEventListener('load', function () {
    // نأخذ وقتاً قصيراً للرندرة الكاملة
    setTimeout(function () { window.print(); }, 400);
  });
<\/script>
</body>
</html>`);
        win.document.close();
    };

    /* ──────────────────────────────────────────────────────────
       HELPER — HTML Escape
    ────────────────────────────────────────────────────────── */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ──────────────────────────────────────────────────────────
       LOAD EXISTING DATA — تحميل التقرير المحفوظ من الـ Server
    ────────────────────────────────────────────────────────── */
    function loadSavedRapport(patientId) {
        // ✅ FIX: اجلب دائماً من الـ server أولاً (البيانات الحقيقية من DB)
        const _baseUrl = window.RAPPORT_LOAD_URL || 'rapport_medical_api.php?action=load_rapport_medical';
        const _sep     = _baseUrl.includes('?') ? '&' : '?';
        const url      = _baseUrl + _sep + 'patient_id=' + encodeURIComponent(patientId);

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res && res.data) {
                    fillRapportFields(patientId, res.data);
                } else {
                    // لا يوجد في DB — جرب sessionStorage كـ fallback
                    const cached = sessionStorage.getItem('rapport_' + patientId);
                    if (cached) {
                        try { fillRapportFields(patientId, JSON.parse(cached)); } catch (e) { /* ignore */ }
                    }
                }
            })
            .catch(function () {
                // فشل الـ server — جرب sessionStorage
                const cached = sessionStorage.getItem('rapport_' + patientId);
                if (cached) {
                    try { fillRapportFields(patientId, JSON.parse(cached)); } catch (e) { /* ignore */ }
                }
            });
    }

    function fillRapportFields(patientId, data) {
        function setVal(id, val) {
            const el = document.getElementById(id);
            if (el && val) el.value = val;
        }
        setVal('rapport_date_'         + patientId, data.rapport_date    || data.date);
        setVal('rapport_patient_name_' + patientId, data.rapport_patient || data.patientName);
        setVal('rapport_age_'          + patientId, data.rapport_age     || data.age);
        /* BUG FIX: fill both the patient-info row AND the header doctor inputs */
        setVal('rapport_doctor_'       + patientId, data.rapport_doctor  || data.doctor);
        setVal('rapport_doctor_name_'  + patientId, data.rapport_doctor  || data.doctor);
        setVal('rapport_content_'      + patientId, data.rapport_content || data.content);
        updateRapportStatus(patientId);
    }

    /* ──────────────────────────────────────────────────────────
       pif:opened — تحميل البيانات المحفوظة بعد بناء القسم
    ────────────────────────────────────────────────────────── */
    document.addEventListener('pif:opened', function (e) {
        const pid = e.detail && e.detail.patientId;
        if (!pid) return;
        setTimeout(function () {
            loadSavedRapport(pid);
            /* تحميل بطاقة العلاج إن كانت الدالة موجودة */
            if (typeof window.loadFicheTraitement === 'function') {
                window.loadFicheTraitement(pid);
            }
        }, 400);
    });

    /* ──────────────────────────────────────────────────────────
       START
    ────────────────────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
