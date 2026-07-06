/* ════════════════════════════════════════════════════════════════
   report_pdf_export.js
   مُصدِّر PDF موثوق للتقارير الطبية (عربي + RTL).

   سبب مشكلة «الصفحات البيضاء» في النسخة السابقة:
     كان المحتوى يُلتقط من عنصر موضوع خارج الشاشة بإزاحة كبيرة
     (left:-9999px). مكتبة html2canvas تحسب منطقة الالتقاط من إحداثيات
     العنصر داخل الصفحة، فتقع منطقة الالتقاط على مساحة فارغة → صفحات بيضاء،
     رغم أن العنصر موجود وبه محتوى.

   الإصلاح (هذا الملف فقط):
     نرسم التقرير داخل iframe معزول من نفس الأصل، عند الإحداثيات (0,0)
     في مستند مستقل بلا تمرير ولا إزاحة، ننتظر تحميل الخطوط، ثم نلتقط
     جسم المستند مباشرة. هذا يضمن التقاطاً صحيحاً للنص العربي بترتيب RTL
     سليم وبنفس التنسيق (عناوين/فقرات/تنويه)، دون صفحات فارغة.

   الواجهة العامة (لم تتغير — لا حاجة لتعديل أي ملف آخر):
     window.MedReportPDF.download({ fileName, innerHtml, onError });
   ════════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    var CDN   = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    var FONTS = 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap';

    /**
     * بناء مستند HTML كامل ومستقل يحمل نفس تنسيق التقرير.
     * دالة نقية (قابلة للاختبار) — تُغلِّف المحتوى الداخلي الظاهر للمستخدم.
     */
    function buildDocumentHtml(innerHtml) {
        return '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">' +
            '<link href="' + FONTS + '" rel="stylesheet">' +
            '<style>' +
                '*{box-sizing:border-box;}' +
                'html,body{margin:0;padding:0;background:#ffffff;}' +
                'body{font-family:"Cairo","Segoe UI",Tahoma,sans-serif;direction:rtl;text-align:right;' +
                    'color:#0f172a;line-height:2;padding:24px;width:746px;}' +
                'h1{color:#0284c7;border-bottom:3px solid #0ea5e9;padding-bottom:10px;font-size:22px;margin:0 0 14px;}' +
                '.mrai-h,.mra-h{font-weight:800;color:#0284c7;margin:16px 0 4px;' +
                    'border-bottom:2px solid rgba(14,165,233,.2);padding-bottom:4px;font-size:17px;}' +
                'p{margin:4px 0;}' +
                '.mrai-disclaimer,.mra-disclaimer{margin-top:22px;padding:14px;background:#fffbeb;' +
                    'border:1px dashed #f59e0b;border-radius:10px;color:#92400e;font-size:13px;}' +
            '</style></head><body>' + (innerHtml || '') + '</body></html>';
    }

    /** تحميل مكتبة html2pdf مرة واحدة عند الحاجة. */
    function ensureLib() {
        return new Promise(function (resolve, reject) {
            if (window.html2pdf) { resolve(); return; }
            var s = document.createElement('script');
            s.src = CDN;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('html2pdf load failed')); };
            document.head.appendChild(s);
        });
    }

    /** انتظار جاهزية خطوط مستند معيّن (لضمان ظهور النص العربي). */
    function waitFonts(doc) {
        try {
            if (doc && doc.fonts && doc.fonts.ready) {
                return doc.fonts.ready.catch(function () {});
            }
        } catch (e) {}
        return Promise.resolve();
    }

    function delay(ms) {
        return new Promise(function (r) { setTimeout(r, ms); });
    }

    /**
     * تصدير التقرير إلى ملف PDF عبر iframe معزول.
     * @param {Object} opts
     * @param {string} opts.fileName       اسم الملف الناتج.
     * @param {string} [opts.documentHtml]  مستند HTML كامل (نفس قالب الطباعة) — يُستخدم كما هو.
     * @param {string} [opts.innerHtml]     بديل: محتوى داخلي يُغلَّف بالقالب الافتراضي (توافق خلفي).
     * @param {Function} [opts.onError]     استدعاء احتياطي عند الفشل.
     */
    function download(opts) {
        opts = opts || {};
        var fileName = opts.fileName || 'report.pdf';

        // نفس مستند الطباعة بالضبط إن مُرِّر، وإلا نغلّف المحتوى بالقالب الافتراضي.
        var fullHtml = (typeof opts.documentHtml === 'string' && opts.documentHtml)
            ? opts.documentHtml
            : buildDocumentHtml(opts.innerHtml);

        /* iframe معزول من نفس الأصل، مخفي بصرياً لكن مرسوم بأبعاد حقيقية.
           العرض 900px ليتّسع لجسم الطباعة (max-width:800 + هوامش auto) فيُلتقط
           مُوسَّطاً دون فراغ على اليمين أو اليسار. */
        var iframe = document.createElement('iframe');
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:900px;height:1400px;' +
            'border:0;opacity:0;z-index:-1;pointer-events:none;';
        document.body.appendChild(iframe);

        var idoc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
        idoc.open();
        idoc.write(fullHtml);
        idoc.close();

        function cleanup() {
            if (iframe && iframe.parentNode) { iframe.parentNode.removeChild(iframe); }
        }

        return ensureLib()
            .then(function () { return waitFonts(idoc); })
            .then(function () { return delay(150); })   // مهلة لإتمام التخطيط والرسم
            .then(function () {
                var body = idoc.body;
                var options = {
                    margin:   [10, 10, 10, 10],
                    filename: fileName,
                    image:    { type: 'png', quality: 1 },   // png أكثر أماناً من jpeg هنا
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        windowWidth:  body.scrollWidth  || 800,
                        windowHeight: body.scrollHeight || 1123,
                        scrollX: 0,
                        scrollY: 0
                    },
                    jsPDF:     { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak: { mode: ['css', 'legacy'] }
                };
                return window.html2pdf().set(options).from(body).save();
            })
            .then(function () { cleanup(); })
            .catch(function (err) {
                cleanup();
                if (typeof opts.onError === 'function') { opts.onError(err); }
                else { throw err; }
            });
    }

    window.MedReportPDF = {
        download: download,
        ensureLib: ensureLib,
        buildDocumentHtml: buildDocumentHtml
    };
})();
