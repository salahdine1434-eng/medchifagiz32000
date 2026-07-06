/* ════════════════════════════════════════════════════════════════
   medical_reports_archive.js
   منطق ميزة «أرشيف التقارير الطبية».
   • يبني الواجهة داخل #mra-root.
   • يعرض قائمة التقارير (اسم المريض/الطبيب/التاريخ/النموذج + زر عرض).
   • بحث باسم المريض، ترتيب من الأحدث، ورسالة لطيفة عند عدم وجود تقارير.
   • Modal للعرض الكامل مع: تحميل PDF / طباعة / نسخ / حذف.
   مستقل تماماً عن «أرشيف المرضى» وعن نظام التوليد.
   ════════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    var root = document.getElementById('mra-root');
    if (!root) return;

    var ENDPOINT = root.dataset.endpoint || 'medical_reports_archive.php';
    var current = null;       // التقرير المفتوح حالياً في الـ Modal
    var loadedOnce = false;

    /* ─────────────────────────── بناء الواجهة ─────────────────────────── */
    root.innerHTML =
        '<div class="mra-toolbar">' +
            '<div class="mra-search">' +
                '<input id="mra-search-input" type="text" placeholder="ابحث باسم المريض…">' +
                '<i class="fas fa-magnifying-glass"></i>' +
            '</div>' +
            '<span class="mra-count" id="mra-count"></span>' +
        '</div>' +
        '<div id="mra-list-wrap">' +
            '<div class="mra-state"><div class="mra-spinner"></div>جارٍ تحميل الأرشيف…</div>' +
        '</div>' +

        /* النافذة */
        '<div class="mra-modal-overlay" id="mra-overlay">' +
            '<div class="mra-modal mra-print-area">' +
                '<div class="mra-modal-head">' +
                    '<div>' +
                        '<h3><i class="fas fa-file-medical"></i> التقرير الطبي</h3>' +
                        '<div class="mra-modal-sub" id="mra-modal-sub"></div>' +
                    '</div>' +
                    '<button class="mra-close" id="mra-close" title="إغلاق"><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="mra-modal-body" id="mra-modal-body"></div>' +
                '<div class="mra-modal-actions">' +
                    '<button class="mra-action-btn mra-pdf" id="mra-pdf"><i class="fas fa-file-pdf"></i> تحميل PDF</button>' +
                    '<button class="mra-action-btn" id="mra-print"><i class="fas fa-print"></i> طباعة</button>' +
                    '<button class="mra-action-btn" id="mra-copy"><i class="fas fa-copy"></i> نسخ</button>' +
                    '<button class="mra-action-btn mra-del" id="mra-delete"><i class="fas fa-trash"></i> حذف التقرير</button>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="mra-toast" id="mra-toast"></div>';

    var $ = function (id) { return document.getElementById(id); };
    var elSearch  = $('mra-search-input');
    var elCount   = $('mra-count');
    var elWrap    = $('mra-list-wrap');
    var elOverlay = $('mra-overlay');
    var elSub     = $('mra-modal-sub');
    var elBody    = $('mra-modal-body');
    var elToast   = $('mra-toast');

    /* ─────────────────────────── أدوات ─────────────────────────── */

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = (s == null ? '' : String(s));
        return d.innerHTML;
    }

    function toast(msg) {
        elToast.textContent = msg;
        elToast.classList.add('is-active');
        setTimeout(function () { elToast.classList.remove('is-active'); }, 2200);
    }

    function fmtDate(s) {
        if (!s) return '';
        return String(s).replace('T', ' ').slice(0, 16);
    }

    /* تحويل نص التقرير إلى HTML منسّق (عناوين/فقرات/تنويه) */
    function renderReport(text) {
        var lines = String(text || '').split(/\r?\n/);
        var html = '';
        var headingRe = /^\s*(?:[0-9\u0660-\u0669]+|[-•*])[\)\.\-]?\s+.+/;
        lines.forEach(function (raw) {
            var line = raw.trim();
            if (line === '') return;
            if (line.indexOf('تم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي') !== -1) {
                html += '<div class="mra-disclaimer">' + escapeHtml(line) + '</div>';
            } else if (headingRe.test(line) && line.length < 60 && !/[.،؛]$/.test(line)) {
                html += '<div class="mra-h">' + escapeHtml(line) + '</div>';
            } else {
                html += '<p>' + escapeHtml(line) + '</p>';
            }
        });
        return html;
    }

    /* ─────────────────────────── القائمة ─────────────────────────── */

    function loadList(q) {
        elWrap.innerHTML = '<div class="mra-state"><div class="mra-spinner"></div>جارٍ تحميل الأرشيف…</div>';
        var url = ENDPOINT + '?action=list' + (q ? '&q=' + encodeURIComponent(q) : '');

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { renderError(data.message || 'تعذّر تحميل الأرشيف.'); return; }
                renderList(data.reports || []);
            })
            .catch(function () { renderError('تعذّر الاتصال بالخادم.'); });
    }

    function renderError(msg) {
        elCount.textContent = '';
        elWrap.innerHTML = '<div class="mra-state">' + escapeHtml(msg) + '</div>';
    }

    function renderEmpty(isSearch) {
        elCount.textContent = '';
        elWrap.innerHTML =
            '<div class="mra-empty">' +
                '<div class="mra-empty-icon"><i class="fas fa-folder-open"></i></div>' +
                '<h3>' + (isSearch ? 'لا توجد نتائج مطابقة' : 'لا توجد تقارير محفوظة بعد') + '</h3>' +
                '<p>' + (isSearch
                    ? 'جرّب اسماً آخر أو امسح مربع البحث لعرض كل التقارير.'
                    : 'ستظهر هنا التقارير التي تولّدها وتحفظها من صفحة «توليد التقارير الطبية».') +
                '</p>' +
            '</div>';
    }

    function renderList(reports) {
        if (!reports.length) {
            renderEmpty(!!(elSearch.value && elSearch.value.trim()));
            return;
        }
        elCount.textContent = reports.length + ' تقرير';
        var html = '<div class="mra-list">';
        reports.forEach(function (r) {
            html +=
                '<div class="mra-item">' +
                    '<div class="mra-item-info">' +
                        '<div class="mra-item-name"><i class="fas fa-user-injured"></i> ' +
                            escapeHtml(r.patient_name || 'مريض غير مسمّى') + '</div>' +
                        '<div class="mra-item-meta">' +
                            '<span><i class="fas fa-user-md"></i> ' + escapeHtml(r.doctor_name || '—') + '</span>' +
                            '<span><i class="fas fa-calendar"></i> ' + escapeHtml(fmtDate(r.created_at)) + '</span>' +
                            '<span class="mra-chip"><i class="fas fa-robot"></i> ' + escapeHtml(r.model || '—') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<button class="mra-view-btn" data-id="' + escapeHtml(r.id) + '">' +
                        '<i class="fas fa-eye"></i> عرض التقرير</button>' +
                '</div>';
        });
        html += '</div>';
        elWrap.innerHTML = html;

        // ربط أزرار العرض
        var btns = elWrap.querySelectorAll('.mra-view-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function () { openReport(this.getAttribute('data-id')); });
        }
    }

    /* ─────────────────────────── العرض (Modal) ─────────────────────────── */

    function openReport(id) {
        fetch(ENDPOINT + '?action=get&id=' + encodeURIComponent(id), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { toast(data.message || 'تعذّر فتح التقرير'); return; }
                current = data.report;
                elSub.innerHTML =
                    '<span><i class="fas fa-user-injured"></i> ' + escapeHtml(current.patient_name || '—') + '</span>' +
                    '<span><i class="fas fa-user-md"></i> ' + escapeHtml(current.doctor_name || '—') + '</span>' +
                    '<span><i class="fas fa-calendar"></i> ' + escapeHtml(fmtDate(current.created_at)) + '</span>';
                elBody.innerHTML = renderReport(current.report_content);
                elOverlay.classList.add('is-active');
            })
            .catch(function () { toast('تعذّر الاتصال بالخادم'); });
    }

    function closeModal() {
        elOverlay.classList.remove('is-active');
        current = null;
    }

    /* ─────────────────── HTML للطباعة/الـ PDF ─────────────────── */

    function buildExportHtml() {
        return '<h1 style="color:#0284c7;border-bottom:3px solid #0ea5e9;padding-bottom:10px;font-size:22px;">التقرير الطبي</h1>' +
            '<div style="color:#64748b;font-size:13px;margin-bottom:22px;">' +
                'المريض: ' + escapeHtml(current.patient_name || '—') +
                ' — الطبيب: ' + escapeHtml(current.doctor_name || '—') +
                ' — التاريخ: ' + escapeHtml(fmtDate(current.created_at)) + '</div>' +
            '<style>' +
                '.mra-h{font-weight:800;color:#0284c7;margin:16px 0 4px;border-bottom:2px solid rgba(14,165,233,.2);padding-bottom:4px;}' +
                'p{margin:4px 0;}' +
                '.mra-disclaimer{margin-top:22px;padding:14px;background:#fffbeb;border:1px dashed #f59e0b;border-radius:10px;color:#92400e;font-size:13px;}' +
            '</style>' +
            renderReport(current.report_content);
    }

    /* ─────────────────────────── الإجراءات ─────────────────────────── */

    function copyReport() {
        if (!current) return;
        var text = current.report_content || '';
        navigator.clipboard.writeText(text)
            .then(function () { toast('تم نسخ التقرير ✅'); })
            .catch(function () {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); ta.remove(); toast('تم نسخ التقرير ✅');
            });
    }

    /* مستند الطباعة الكامل — مصدر واحد يستخدمه زرّا «طباعة» و«تحميل PDF» معاً. */
    function buildPrintDocument() {
        return '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">' +
            '<title>التقرير الطبي - ' + escapeHtml(current.patient_name || '') + '</title>' +
            '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">' +
            '<style>body{font-family:\'Cairo\',sans-serif;direction:rtl;color:#0f172a;line-height:2;padding:40px;max-width:800px;margin:auto;}</style>' +
            '</head><body>' + buildExportHtml() + '</body></html>';
    }

    function printReport() {
        if (!current) return;
        var w = window.open('', '_blank');
        if (!w) { toast('يرجى السماح بالنوافذ المنبثقة للطباعة'); return; }
        w.document.write(buildPrintDocument());
        w.document.close();
        w.onload = function () { w.focus(); w.print(); };
    }

    function downloadPdf() {
        if (!current) return;
        var fileName = 'تقرير_' + String(current.patient_name || 'مريض').replace(/\s+/g, '_') + '.pdf';
        if (!window.MedReportPDF) { toast('سيتم استخدام الطباعة لحفظ PDF'); printReport(); return; }
        toast('جارٍ تجهيز ملف PDF…');
        window.MedReportPDF.download({
            fileName: fileName,
            documentHtml: buildPrintDocument(),   // نفس مستند الطباعة تماماً
            onError: function () { toast('سيتم استخدام الطباعة لحفظ PDF'); printReport(); }
        });
    }

    function deleteReport() {
        if (!current) return;
        if (!window.confirm('هل أنت متأكد من حذف هذا التقرير نهائياً؟')) return;
        var id = current.id;
        var body = new URLSearchParams({ action: 'delete', id: id });
        fetch(ENDPOINT, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { toast(data.message || 'تعذّر الحذف'); return; }
                toast('تم حذف التقرير 🗑️');
                closeModal();
                loadList(elSearch.value.trim());
            })
            .catch(function () { toast('تعذّر الاتصال بالخادم'); });
    }

    /* ─────────────────────────── ربط الأحداث ─────────────────────────── */

    $('mra-close').addEventListener('click', closeModal);
    elOverlay.addEventListener('click', function (e) { if (e.target === elOverlay) closeModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && elOverlay.classList.contains('is-active')) closeModal();
    });

    $('mra-copy').addEventListener('click', copyReport);
    $('mra-print').addEventListener('click', printReport);
    $('mra-pdf').addEventListener('click', downloadPdf);
    $('mra-delete').addEventListener('click', deleteReport);

    /* بحث مع تأخير بسيط (debounce) */
    var t = null;
    elSearch.addEventListener('input', function () {
        clearTimeout(t);
        var v = elSearch.value.trim();
        t = setTimeout(function () { loadList(v); }, 280);
    });

    /* تحميل عند أول فتح للبطاقة + تحديث عند كل فتح (دون لمس سكربتات أخرى) */
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[onclick*="reportsArchive"]');
        if (trigger) { loadList(elSearch.value.trim()); }
    });

    // تحميل مبدئي عند جاهزية الصفحة
    if (!loadedOnce) { loadedOnce = true; loadList(''); }
})();
