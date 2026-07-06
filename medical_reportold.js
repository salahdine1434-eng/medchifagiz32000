/* ════════════════════════════════════════════════════════════════
   medical_report.js
   منطق الواجهة لميزة «توليد التقارير الطبية بالذكاء الاصطناعي».
   • يبني الواجهة داخل #mrai-root.
   • يجلب قائمة المرضى، يولّد التقرير عبر Groq، ويعرضه في بطاقة احترافية.
   • أزرار: نسخ / طباعة / حفظ / تحميل PDF.
   مستقل تماماً — لا يعدّل أي سكربت آخر في المشروع.
   ════════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    const root = document.getElementById('mrai-root');
    if (!root) return;

    const ENDPOINT = root.dataset.endpoint || 'generate_medical_report.php';
    const state = { report: '', model: '', patientName: '', recordId: 0 };

    /* ─────────────────────────── بناء الواجهة ─────────────────────────── */
    root.innerHTML = `
        <div class="mrai-panel">
            <div class="mrai-field">
                <label><i class="fas fa-user-injured"></i> اختر المريض</label>
                <select id="mrai-patient">
                    <option value="">— جارٍ تحميل قائمة المرضى… —</option>
                </select>
            </div>
            <div class="mrai-field">
                <label><i class="fas fa-notes-medical"></i> ملاحظات الطبيب (اختياري)</label>
                <textarea id="mrai-notes" rows="4" placeholder="أضف أي ملاحظات سريرية تريد إدراجها في التقرير…"></textarea>
            </div>
            <button class="mrai-generate-btn" id="mrai-generate">
                <i class="fas fa-wand-magic-sparkles"></i>
                <span>✨ توليد التقرير</span>
            </button>
            <div class="mrai-alert" id="mrai-alert"></div>
        </div>

        <div class="mrai-loading" id="mrai-loading">
            <div class="mrai-spinner"></div>
            <div>جارٍ توليد التقرير الطبي بالذكاء الاصطناعي…</div>
        </div>

        <div class="mrai-result" id="mrai-result">
            <div class="mrai-report-card mrai-print-area">
                <div class="mrai-report-head">
                    <h3><i class="fas fa-file-medical"></i> التقرير الطبي</h3>
                    <div class="mrai-report-meta" id="mrai-meta"></div>
                </div>
                <div class="mrai-report-body" id="mrai-body"></div>
                <div class="mrai-actions">
                    <button class="mrai-action-btn" id="mrai-copy"><i class="fas fa-copy"></i> نسخ</button>
                    <button class="mrai-action-btn" id="mrai-print"><i class="fas fa-print"></i> طباعة</button>
                    <button class="mrai-action-btn" id="mrai-save"><i class="fas fa-floppy-disk"></i> حفظ</button>
                    <button class="mrai-action-btn mrai-pdf" id="mrai-pdf"><i class="fas fa-file-pdf"></i> تحميل PDF</button>
                </div>
            </div>
        </div>
        <div class="mrai-toast" id="mrai-toast"></div>
    `;

    /* مراجع العناصر */
    const $ = (id) => document.getElementById(id);
    const elPatient  = $('mrai-patient');
    const elNotes    = $('mrai-notes');
    const elGenerate = $('mrai-generate');
    const elAlert    = $('mrai-alert');
    const elLoading  = $('mrai-loading');
    const elResult   = $('mrai-result');
    const elBody     = $('mrai-body');
    const elMeta     = $('mrai-meta');
    const elToast    = $('mrai-toast');

    /* ─────────────────────────── أدوات مساعدة ─────────────────────────── */

    function showAlert(msg) {
        elAlert.textContent = msg;
        elAlert.classList.add('is-active');
    }
    function clearAlert() { elAlert.classList.remove('is-active'); elAlert.textContent = ''; }

    function toast(msg) {
        elToast.textContent = msg;
        elToast.classList.add('is-active');
        setTimeout(() => elToast.classList.remove('is-active'), 2200);
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    /* تحويل نص التقرير العادي إلى HTML منسّق (عناوين + فقرات + تنويه) */
    function renderReport(text) {
        const lines = text.split(/\r?\n/);
        let html = '';
        const headingRe = /^\s*(?:[0-9\u0660-\u0669]+|[-•*])[\)\.\-]?\s+.+/; // أرقام عربية/لاتينية أو نقاط
        lines.forEach((raw) => {
            const line = raw.trim();
            if (line === '') return;
            if (line.indexOf('تم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي') !== -1) {
                html += `<div class="mrai-disclaimer">${escapeHtml(line)}</div>`;
            } else if (headingRe.test(line) && line.length < 60 && !/[.،؛]$/.test(line)) {
                html += `<div class="mrai-h">${escapeHtml(line)}</div>`;
            } else {
                html += `<p>${escapeHtml(line)}</p>`;
            }
        });
        return html;
    }

    /* ─────────────────────── جلب قائمة المرضى ─────────────────────── */

    function loadPatients() {
        fetch(`${ENDPOINT}?action=list_patients`, { credentials: 'same-origin' })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    elPatient.innerHTML = '<option value="">تعذّر تحميل المرضى</option>';
                    showAlert(data.message || 'تعذّر تحميل قائمة المرضى.');
                    return;
                }
                if (!data.patients.length) {
                    elPatient.innerHTML = '<option value="">لا يوجد مرضى مسجّلون بعد</option>';
                    return;
                }
                let opts = '<option value="">— اختر مريضاً —</option>';
                data.patients.forEach((p) => {
                    const date = (p.created_at || '').split(' ')[0];
                    const label = escapeHtml(p.full_name) + (date ? ` — ${date}` : '');
                    opts += `<option value="${p.id}">${label}</option>`;
                });
                elPatient.innerHTML = opts;
            })
            .catch(() => {
                elPatient.innerHTML = '<option value="">تعذّر الاتصال بالخادم</option>';
                showAlert('تعذّر الاتصال بالخادم لتحميل المرضى.');
            });
    }

    /* ───────────────────────── توليد التقرير ───────────────────────── */

    function generate() {
        clearAlert();
        const recordId = elPatient.value;
        if (!recordId) { showAlert('يرجى اختيار مريض أولاً.'); return; }

        elGenerate.disabled = true;
        elResult.classList.remove('is-active');
        elLoading.classList.add('is-active');

        const body = new URLSearchParams({
            action: 'generate_report',
            record_id: recordId,
            notes: elNotes.value || ''
        });

        fetch(ENDPOINT, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then((r) => r.json())
            .then((data) => {
                elLoading.classList.remove('is-active');
                elGenerate.disabled = false;
                if (!data.success) { showAlert(data.message || 'فشل توليد التقرير.'); return; }

                state.report      = data.report;
                state.model       = data.model || '';
                state.patientName = data.patient_name || '';
                state.recordId    = recordId;

                elBody.innerHTML = renderReport(data.report);
                elMeta.innerHTML =
                    `المريض: ${escapeHtml(state.patientName || '—')} • ` +
                    `${escapeHtml(data.generated_at || '')} • النموذج: ${escapeHtml(state.model)}`;
                elResult.classList.add('is-active');
                elResult.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => {
                elLoading.classList.remove('is-active');
                elGenerate.disabled = false;
                showAlert('تعذّر الاتصال بالخادم أثناء توليد التقرير.');
            });
    }

    /* ─────────────────────── إجراءات: نسخ ─────────────────────── */

    function copyReport() {
        const text = state.report || elBody.innerText;
        navigator.clipboard.writeText(text)
            .then(() => toast('تم نسخ التقرير ✅'))
            .catch(() => {
                const ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); ta.remove();
                toast('تم نسخ التقرير ✅');
            });
    }

    /* ─────────────────── إجراءات: طباعة (نافذة نظيفة) ─────────────────── */

    function buildPrintHtml() {
        return `<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
            <title>التقرير الطبي - ${escapeHtml(state.patientName)}</title>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
            <style>
                body{font-family:'Cairo',sans-serif;direction:rtl;color:#0f172a;line-height:2;padding:40px;max-width:800px;margin:auto;}
                h1{color:#0284c7;border-bottom:3px solid #0ea5e9;padding-bottom:10px;font-size:22px;}
                .meta{color:#64748b;font-size:13px;margin-bottom:24px;}
                .mrai-h{font-weight:800;color:#0284c7;margin:18px 0 4px;border-bottom:2px solid rgba(14,165,233,.2);padding-bottom:4px;}
                p{margin:4px 0;}
                .mrai-disclaimer{margin-top:24px;padding:14px;background:#fffbeb;border:1px dashed #f59e0b;border-radius:10px;color:#92400e;font-size:13px;}
            </style></head><body>
            <h1>التقرير الطبي</h1>
            <div class="meta">المريض: ${escapeHtml(state.patientName || '—')} — التاريخ: ${new Date().toLocaleDateString('ar')} — النموذج: ${escapeHtml(state.model)}</div>
            ${renderReport(state.report)}
        </body></html>`;
    }

    function printReport() {
        const w = window.open('', '_blank');
        if (!w) { toast('يرجى السماح بالنوافذ المنبثقة للطباعة'); return; }
        w.document.write(buildPrintHtml());
        w.document.close();
        w.onload = () => { w.focus(); w.print(); };
    }

    /* ─────────────────────── إجراءات: حفظ ─────────────────────── */

    function saveReport() {
        if (!state.report) return;
        const btn = $('mrai-save');
        btn.disabled = true;
        const body = new URLSearchParams({
            action: 'save_report',
            record_id: state.recordId,
            report: state.report,
            model: state.model
        });
        fetch(ENDPOINT, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then((r) => r.json())
            .then((data) => {
                btn.disabled = false;
                toast(data.success ? 'تم حفظ التقرير 💾' : (data.message || 'تعذّر الحفظ'));
            })
            .catch(() => { btn.disabled = false; toast('تعذّر الاتصال بالخادم'); });
    }

    /* ─────────────────── إجراءات: تحميل PDF ─────────────────── */
    /*
     * إصلاح مشكلة الصفحات البيضاء: نستعمل المُصدِّر المشترك report_pdf_export.js
     * الذي يُلحِق المحتوى بالـ DOM وينتظر الخطوط قبل الالتقاط. نفس التقرير الظاهر
     * يُصدَّر بنفس الترتيب ودعم RTL صحيح. لم يتغيّر تصميم التقرير.
     */
    function downloadPdf() {
        if (!state.report) return;
        const fileName = `تقرير_${(state.patientName || 'مريض').replace(/\s+/g, '_')}.pdf`;
        const inner = buildPrintHtml().match(/<body>([\s\S]*)<\/body>/)[1];

        if (!window.MedReportPDF) {
            toast('سيتم استخدام الطباعة لحفظ PDF');
            printReport();
            return;
        }
        toast('جارٍ تجهيز ملف PDF…');
        window.MedReportPDF.download({
            fileName: fileName,
            innerHtml: inner,
            onError: function () {
                toast('سيتم استخدام الطباعة لحفظ PDF');
                printReport();
            }
        });
    }

    /* ─────────────────────────── ربط الأحداث ─────────────────────────── */

    elGenerate.addEventListener('click', generate);
    $('mrai-copy').addEventListener('click', copyReport);
    $('mrai-print').addEventListener('click', printReport);
    $('mrai-save').addEventListener('click', saveReport);
    $('mrai-pdf').addEventListener('click', downloadPdf);

    /* تحميل قائمة المرضى عند جاهزية الصفحة */
    loadPatients();
})();
