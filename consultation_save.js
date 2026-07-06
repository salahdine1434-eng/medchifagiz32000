/* ============================================================================
 * consultation_save.js
 * ----------------------------------------------------------------------------
 * مرحلة "حفظ وعرض الاستشارة" (طرف المُرسِل فقط).
 *
 * يكمل هذا الملف ما يلي دون تعديل dr_dashboard.js أو التصميم/CSS:
 *   1) إكمال زر "إنشاء الاستشارة": حفظ حقيقي في create_consultation.php،
 *      ثم رسالة نجاح + إغلاق النافذة + تحديث قائمة "المرسلة" فوراً.
 *   2) عرض الاستشارات المحفوظة من get_consultations.php داخل تبويب
 *      "المرسلة"، مصنّفة داخلية/خارجية حسب consultation_scope.
 *
 * لا يلمس: نظام البحث عن الأطباء/المرضى، المرفقات، الخصوصية، الردود،
 * المحادثات، الإشعارات، أو أي جزء آخر. يستعمل العناصر الموجودة فقط.
 *
 * ملاحظة تقنية: زر الإنشاء يملك مسبقاً معالجاً ناقصاً داخل dr_dashboard.js
 * (يرسل البيانات لكنه يكتفي بـ console.log). حتى لا نعبث بذلك الملف ولا
 * نُرسِل الطلب مرتين، نستبدل الزر بنسخة نظيفة (تُزيل المعالج القديم) ثم
 * نربط معالجاً كاملاً هنا يرسل نفس الحمولة تماماً.
 * ========================================================================== */
(function () {
    "use strict";

    var CREATE_URL = "create_consultation.php";
    var LIST_URL   = "get_consultations.php";

    /* ---------- أدوات مساعدة ---------- */

    function esc(str) {
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    // رسالة موحّدة تعيد استخدام نظام التنبيهات الموجود إن وُجد، وإلا تنبيه بسيط
    function notify(msg, type) {
        if (typeof window.showAddPatientToast === "function") { window.showAddPatientToast(msg, type); return; }
        if (typeof window.armShowToast === "function")        { window.armShowToast(msg, type);        return; }
        // احتياطي بسيط (بأنماط سطرية فقط — لا يمسّ ملف CSS)
        try {
            var t = document.createElement("div");
            t.textContent = msg;
            t.style.cssText =
                "position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:200000;" +
                "padding:12px 20px;border-radius:12px;font-family:'Cairo',sans-serif;font-size:0.9rem;" +
                "font-weight:700;color:#fff;box-shadow:0 8px 24px rgba(15,23,42,.25);" +
                "background:" + (type === "error" ? "#ef4444" : "#10b981") + ";";
            document.body.appendChild(t);
            setTimeout(function () { t.style.transition = "opacity .3s"; t.style.opacity = "0"; }, 2600);
            setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 3000);
        } catch (e) { alert(msg); }
    }

    function fmtDate(s) {
        if (!s) return "—";
        // "YYYY-MM-DD HH:MM:SS" → "YYYY-MM-DD"
        var d = String(s).split(" ")[0];
        return d || "—";
    }

    /* ---------- خرائط العرض (مطابقة لقيم enum و CSS الموجودة) ---------- */

    var TYPE_ICON = {
        medical_opinion:  "fa-user-md",
        urgent_opinion:   "fa-bolt",
        case_discussion:  "fa-comments",
        patient_transfer: "fa-exchange-alt",
        radiology_review: "fa-x-ray",
        lab_review:       "fa-vials",
        follow_up:        "fa-heart-pulse"
    };
    var STATUS_CLASS = {
        new:       "case-status-new",
        in_review: "case-status-review",
        answered:  "case-status-replied",
        closed:    "case-status-closed"
    };
    // أولوية قاعدة البيانات (normal/urgent/critical) → صنف CSS للبطاقة (normal/medium/urgent)
    var PRIO_CLASS = { normal: "normal", urgent: "medium", critical: "urgent" };

    /* ---------- بناء بطاقة حالة (نفس مارك-أب المشروع تماماً) ---------- */

    function buildCard(c) {
        var statusClass = STATUS_CLASS[c.status] || "case-status-new";
        var typeIcon    = TYPE_ICON[c.type] || "fa-notes-medical";
        var prioClass   = PRIO_CLASS[c.priority] || "normal";
        var isInbox     = (c.box === "inbox");
        // اسم الطبيب المعروض: الواردة → الطبيب المُرسِل، المرسلة → الطبيب المستشار
        var nameSrc     = c.display_doctor_name || (isInbox ? c.creator_name : c.assigned_doctor_name);
        var docName     = (nameSrc && String(nameSrc).trim() !== "") ? esc(nameSrc) : "—";
        var docLabel    = isInbox ? "الطبيب المُرسِل" : "الطبيب المستشار";

        return "" +
        '<label for="caseOpen" class="case-card-link" data-scope="' + esc(c.scope) + '" data-case-id="' + esc(c.id) + '">' +
          '<article class="case-card ' + statusClass + '">' +
            '<span class="case-accent"></span>' +
            '<div class="case-head">' +
              '<span class="case-num"><i class="fas fa-hashtag"></i> ' + esc(c.case_number) + '</span>' +
              '<span class="case-badge status"><span class="dot"></span> ' + esc(c.status_label) + '</span>' +
            '</div>' +
            '<div class="case-tags">' +
              '<span class="case-tag type"><i class="fas ' + typeIcon + '"></i> ' + esc(c.type_label) + '</span>' +
              '<span class="case-tag prio ' + prioClass + '"><span class="pdot"></span> ' + esc(c.priority_label) + '</span>' +
            '</div>' +
            '<div class="case-sender">' +
              '<span class="case-avatar"><i class="fas fa-user-md"></i></span>' +
              '<span class="case-sender-info">' +
                '<span style="font-weight:700;font-size:0.86rem;color:var(--text-primary,#0f172a);">' + docName + '</span>' +
                '<small>' + docLabel + '</small>' +
              '</span>' +
            '</div>' +
            '<div class="case-foot">' +
              '<span class="case-date"><i class="fas fa-calendar-day"></i> ' + esc(fmtDate(c.created_at)) + '</span>' +
              '<span class="case-metrics">' +
                '<span class="case-metric"><i class="fas fa-paperclip"></i> —</span>' +
                '<span class="case-metric"><i class="fas fa-user-group"></i> —</span>' +
              '</span>' +
            '</div>' +
          '</article>' +
        '</label>';
    }

    /* ---------- عناصر القائمة (معمّمة: sent + inbox) ---------- */

    function viewOf(box)  { return document.querySelector('.cslt-view[data-status="' + box + '"]'); }
    function listOf(box)  { var v = viewOf(box); return v ? v.querySelector(".consult-list") : null; }

    function currentScope() {
        // راديوهات cslt-scope في الصفحة بلا سمة value، لذا نعتمد على الـ ID المُحدَّد
        var ext = document.getElementById("cslt-scope-external");
        if (ext && ext.checked) return "external";
        var intl = document.getElementById("cslt-scope-internal");
        if (intl && intl.checked) return "internal";
        // احتياط: إن وُجدت قيمة صريحة نستعملها
        var r = document.querySelector('input[name="cslt-scope"]:checked');
        if (r && (r.value === "internal" || r.value === "external")) return r.value;
        return "internal";
    }

    // نصوص الحالة الفارغة لكل صندوق
    var EMPTY_TEXT = {
        sent: {
            icon: "fa-paper-plane",
            title: { internal: "لا توجد استشارات داخلية مرسلة", external: "لا توجد استشارات خارجية مرسلة" },
            desc: "ابدأ بإنشاء استشارة جديدة لتظهر هنا كبطاقة حالة.",
            btn: true
        },
        inbox: {
            icon: "fa-inbox",
            title: { internal: "لا توجد استشارات واردة داخلية", external: "لا توجد استشارات واردة خارجية" },
            desc: "ستظهر هنا الاستشارات التي يرسلها إليك أطباء آخرون.",
            btn: false
        }
    };

    // حالة فارغة داخل تبويب معيّن (تعيد استخدام أصناف التصميم الموجودة)
    function ensureEmptyNode(box) {
        var v = viewOf(box);
        if (!v) return null;
        // "المغلقة": الحالة الفارغة موجودة أصلاً بشكل ثابت في الـ HTML (نفس التصميم) —
        // نُعيد استخدامها كما هي (تبديل الظهور فقط) دون إنشاء عنصر جديد أو تغيير نصها.
        if (box === "closed") {
            return v.querySelector(".cslt-empty");
        }
        var empty = v.querySelector(".cns-box-empty");
        if (!empty) {
            var t = EMPTY_TEXT[box] || EMPTY_TEXT.sent;
            empty = document.createElement("div");
            empty.className = "cslt-empty cns-box-empty";
            empty.innerHTML =
                '<div class="cslt-empty-icon"><i class="fas ' + t.icon + '"></i></div>' +
                '<h3></h3>' +
                '<p>' + t.desc + '</p>' +
                (t.btn ? '<label for="cncOpen" class="cslt-empty-btn"><i class="fas fa-plus"></i> إنشاء استشارة جديدة</label>' : '');
            empty.style.display = "none";
            v.appendChild(empty);
        }
        return empty;
    }

    // إظهار/إخفاء البطاقات حسب النطاق النشط + إدارة الحالة الفارغة (لصندوق واحد)
    function applyScopeFilter(box) {
        var list = listOf(box);
        if (!list) return;
        var scope = currentScope();
        var visible = 0;
        list.querySelectorAll(".case-card-link").forEach(function (card) {
            var match = card.getAttribute("data-scope") === scope;
            card.style.display = match ? "" : "none";
            if (match) visible++;
        });
        var empty = ensureEmptyNode(box);
        if (empty) {
            empty.style.display = (visible === 0) ? "" : "none";
            if (box !== "closed") {
                var h = empty.querySelector("h3");
                var t = EMPTY_TEXT[box] || EMPTY_TEXT.sent;
                if (h) h.textContent = t.title[scope] || t.title.internal;
            }
        }
    }

    // تطبيق الفلترة على الصناديق الثلاثة معاً (الواردة/المرسلة/المغلقة)
    function applyAllFilters() {
        applyScopeFilter("sent");
        applyScopeFilter("inbox");
        applyScopeFilter("closed");
    }

    // رسم قائمة استشارات داخل صندوق معيّن
    function renderInto(box, consultations) {
        var list = listOf(box);
        if (!list) return;
        var html = "";
        (consultations || []).forEach(function (c) {
            c.box = box;                     // وسم الصندوق للبطاقة
            html += buildCard(c);
        });
        list.innerHTML = html;               // يستبدل أي بطاقات تجريبية سابقة
        applyScopeFilter(box);
    }

    // جلب الاستشارات (مرسلة + واردة + مغلقة) من الخادم وعرضها
    function loadAll() {
        return fetch(LIST_URL, { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    renderInto("sent",   data.sent   || data.consultations || []);
                    renderInto("inbox",  data.inbox  || []);
                    renderInto("closed", data.closed || []);
                }
                return data;
            })
            .catch(function (e) { console.error("loadAll failed", e); });
    }
    var loadSent = loadAll;   // للتوافق مع النداءات السابقة

    /* ---------- إنشاء الاستشارة ---------- */

    function readPayload() {
        function val(id) { var el = document.getElementById(id); return el ? el.value.trim() : ""; }
        function checked(name) { var el = document.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : ""; }

        var scope = checked("cnc-scope") || "internal";

        // معرّف الطبيب: من القائمة الرئيسية (يُزامن معها البحث الحي)، مع احتياطي للحقل المخفي
        var doctorId = val("consultationDoctor");
        if (!doctorId && scope === "external") {
            var h = document.getElementById("cncExtSelectedDoctorId");
            doctorId = h ? h.value.trim() : "";
        }

        var hideEl = document.querySelector('.cnc-privacy-row input[type="checkbox"]');

        return {
            patient_id:            val("consultationPatient"),
            assigned_doctor_id:    doctorId,
            consultation_scope:    scope,
            consultation_type:     checked("cnc-type"),
            title:                 val("consultationTitle"),
            description:           val("consultationDescription"),
            priority:              checked("cnc-priority") || "normal",
            hide_patient_identity: (hideEl && hideEl.checked) ? 1 : 0
        };
    }

    function validate(p) {
        // مرحلة الاختبار: الإجباري فقط = الطبيب، المريض، العنوان، التفاصيل.
        // المريض اليدوي id="0" مقبول ("0" نصية غير فارغة فلا تُرفض).
        // النوع/الأولوية/الخصوصية اختيارية (يطبّق الخادم قيماً افتراضية).
        var errs = [];
        if (!p.assigned_doctor_id) errs.push("يرجى اختيار الطبيب.");
        if (!p.patient_id)         errs.push("يرجى اختيار المريض.");
        if (!p.title)              errs.push("يرجى إدخال موضوع الاستشارة.");
        if (!p.description)        errs.push("يرجى إدخال تفاصيل الحالة.");
        return errs;
    }

    // إعادة تعيين الحقول البسيطة فقط (لا نلمس أنظمة البحث/المرفقات/المشاركين)
    function resetSimpleFields() {
        ["consultationTitle", "consultationDescription", "consultationPatient", "consultationDoctor"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = "";
        });
        var hide = document.querySelector('.cnc-privacy-row input[type="checkbox"]');
        if (hide) hide.checked = false;
    }

    function closeModal() {
        var cb = document.getElementById("cncOpen");
        if (cb) cb.checked = false;
    }

    function handleCreate(btn) {
        if (btn.dataset.busy === "1") return;   // منع النقر المزدوج
        var payload = readPayload();

        var errs = validate(payload);
        if (errs.length) { notify(errs[0], "error"); return; }

        btn.dataset.busy = "1";
        btn.classList.add("btn-loading");   // حالة تحميل (صنف موجود مسبقاً)

        fetch(CREATE_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                notify(data.message || "تم إنشاء الاستشارة بنجاح.", "success");
                var scope = payload.consultation_scope;
                resetSimpleFields();
                closeModal();

                // الانتقال إلى تبويب "المرسلة" + نطاق الاستشارة المنشأة، ثم تحديث القائمة
                var sentTab = document.getElementById("cslt-status-sent");
                if (sentTab) sentTab.checked = true;
                var scopeTab = document.getElementById(scope === "external" ? "cslt-scope-external" : "cslt-scope-internal");
                if (scopeTab) scopeTab.checked = true;

                loadAll().then(applyAllFilters);
            } else {
                var msg = (data && data.errors && data.errors.length) ? data.errors.join(" — ")
                        : (data && data.message) ? data.message
                        : "تعذّر إنشاء الاستشارة.";
                notify(msg, "error");
            }
        })
        .catch(function (e) {
            console.error("create failed", e);
            notify("تعذّر الاتصال بالخادم.", "error");
        })
        .finally(function () {
            btn.dataset.busy = "0";
            btn.classList.remove("btn-loading");
        });
    }

    /* ---------- التهيئة ---------- */

    function init() {
        // 1) استبدال زر الإنشاء بنسخة نظيفة لإزالة المعالج الناقص في dr_dashboard.js
        var oldBtn = document.getElementById("createConsultationBtn");
        if (oldBtn) {
            var freshBtn = oldBtn.cloneNode(true);
            oldBtn.parentNode.replaceChild(freshBtn, oldBtn);
            freshBtn.addEventListener("click", function () { handleCreate(freshBtn); });
        }

        // 2) فلترة القائمة عند تبديل تبويب النطاق (داخلية/خارجية)
        document.querySelectorAll('input[name="cslt-scope"]').forEach(function (r) {
            r.addEventListener("change", applyAllFilters);
        });
        // إعادة الفلترة عند العودة إلى تبويب "المرسلة"
        var sentTab = document.getElementById("cslt-status-sent");
        if (sentTab) sentTab.addEventListener("change", applyAllFilters);

        // 3) تحميل الاستشارات المحفوظة وعرضها
        loadSent();
    }

    // نضمن التشغيل بعد أن يكون dr_dashboard.js قد ربط معالجه (لنستبدله بأمان)
    function boot() { setTimeout(init, 0); }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }

    // إتاحة تحديث يدوي عند الحاجة
    window.cnsReloadSent = loadSent;
})();
