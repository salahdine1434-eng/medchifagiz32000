/* ============================================================================
 * consultation_details.js
 * ----------------------------------------------------------------------------
 * المرحلة الثانية — ربط صفحة تفاصيل الاستشارة بقاعدة البيانات.
 *
 * عند النقر على أي بطاقة استشارة (تحمل data-case-id) يجلب التفاصيل من
 * get_consultation_details.php ويملأ العناصر الموجودة في #caseDetail
 * (بالاعتماد على الـ classes فقط — دون تغيير HTML أو CSS أو إضافة عناصر).
 *
 * كما يفعّل الأزرار الموجودة:
 *   - تغيير الحالة / إغلاق الحالة → update_consultation_status.php
 *   - إضافة طبيب مشارك / إضافة مرفق → رسالة "لاحقاً" (لا بنية لها بعد)
 *   - طباعة → يطبع بيانات الاستشارة الحالية
 *
 * لا يمسّ: إنشاء الاستشارة، القائمة، البحث، المحادثة، التصميم.
 * ========================================================================== */
(function () {
    "use strict";

    var DETAILS_URL  = "get_consultation_details.php";
    var STATUS_URL   = "update_consultation_status.php";
    var MESSAGES_URL = "get_consultation_messages.php";
    var REPLY_URL    = "reply_consultation.php";

    var lastData = null;       // آخر استشارة مُحمّلة (للطباعة والأزرار)
    var chatLoadedFor = null;  // معرّف آخر استشارة حُمّلت محادثتها (لتفادي التكرار)

    /* ---------- أدوات ---------- */
    function esc(s) {
        if (s === null || s === undefined) return "";
        return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
            .replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    }
    function notify(msg, type) {
        if (typeof window.showAddPatientToast === "function") { window.showAddPatientToast(msg, type); return; }
        if (typeof window.armShowToast === "function")        { window.armShowToast(msg, type);        return; }
        try {
            var t = document.createElement("div");
            t.textContent = msg;
            t.style.cssText = "position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:200000;"+
                "padding:12px 20px;border-radius:12px;font-family:'Cairo',sans-serif;font-size:0.9rem;font-weight:700;"+
                "color:#fff;box-shadow:0 8px 24px rgba(15,23,42,.25);background:"+(type==="error"?"#ef4444":"#10b981")+";";
            document.body.appendChild(t);
            setTimeout(function(){ t.style.transition="opacity .3s"; t.style.opacity="0"; }, 2600);
            setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 3000);
        } catch(e){ alert(msg); }
    }
    function fmtDate(s){ if(!s) return "—"; return String(s).split(" ")[0] || "—"; }
    function fmtDateTime(s){
        if(!s) return "—";
        var p = String(s).split(" ");
        var d = p[0] || "";
        var t = (p[1] || "").slice(0,5);
        return t ? (d + " " + t) : (d || "—");
    }

    /* خرائط الألوان (مطابقة لباليت المشروع الموجود في CSS) */
    var STATUS_META = {
        new:       { cls:"case-status-new",     bg:"rgba(14,165,233,.12)",  fg:"#0284c7", dot:"#0ea5e9" },
        in_review: { cls:"case-status-review",  bg:"rgba(245,158,11,.15)",  fg:"#d97706", dot:"#f59e0b" },
        answered:  { cls:"case-status-replied", bg:"rgba(16,185,129,.15)",  fg:"#059669", dot:"#10b981" },
        closed:    { cls:"case-status-closed",  bg:"rgba(148,163,184,.18)", fg:"#64748b", dot:"#94a3b8" }
    };
    var PRIO_META = {
        normal:   { cls:"normal", bg:"rgba(16,185,129,.1)", fg:"#059669", dot:"#10b981" },
        urgent:   { cls:"medium", bg:"rgba(245,158,11,.1)", fg:"#d97706", dot:"#f59e0b" },
        critical: { cls:"urgent", bg:"rgba(239,68,68,.1)",  fg:"#dc2626", dot:"#ef4444" }
    };
    var ALL_STATUS_CLASSES = ["case-status-new","case-status-review","case-status-replied","case-status-closed"];
    var ALL_PRIO_CLASSES   = ["normal","medium","urgent"];

    /* ---------- أدوات تعبئة (تحافظ على البنية، لا تغيّر HTML الملف) ---------- */
    function setText(el, val){
        if(!el) return;
        el.textContent = (val === null || val === undefined || val === "") ? "—" : String(val);
    }
    function fillKeepIcon(el, text){
        if(!el) return;
        var icon = el.querySelector("i");
        var iconHTML = icon ? icon.outerHTML : "";
        el.innerHTML = iconHTML + " " + esc(text);
    }
    // شارة تحافظ على النقطة .dot وتضبط النص + لون سطري حسب الحالة
    function fillStatusBadge(el, label, status, applyInlineColor){
        if(!el) return;
        var dot = el.querySelector(".dot");
        var dotHTML = dot ? dot.outerHTML : "";
        el.innerHTML = dotHTML + " " + esc(label);
        if(applyInlineColor){
            var m = STATUS_META[status];
            if(m){
                el.style.background = m.bg;
                el.style.color = m.fg;
                var d = el.querySelector(".dot");
                if(d) d.style.background = m.dot;
            }
        }
    }
    function fillPriorityBadge(el, label, priority){
        if(!el) return;
        var dot = el.querySelector(".dot");
        var dotHTML = dot ? dot.outerHTML : "";
        el.innerHTML = dotHTML + " " + esc(label);
        var m = PRIO_META[priority];
        if(m){
            el.style.background = m.bg;
            el.style.color = m.fg;
            var d = el.querySelector(".dot");
            if(d) d.style.background = m.dot;
        }
    }
    // قيمة الأولوية داخل شبكة المعلومات: تحافظ على .pdot وتضبط صنف اللون + النص
    function fillPriorityInline(el, priority, label){
        if(!el) return;
        var m = PRIO_META[priority] || PRIO_META.normal;
        ALL_PRIO_CLASSES.forEach(function(c){ el.classList.remove(c); });
        el.classList.add(m.cls); // يلوّن .pdot تلقائياً عبر CSS الموجود
        var pdot = el.querySelector(".pdot");
        var pdotHTML = pdot ? pdot.outerHTML : "";
        el.innerHTML = pdotHTML + " " + esc(label);
    }
    // تحويل سطر Skeleton إلى نص مقروء (أنماط سطرية فقط لإلغاء مظهر التحميل)
    function fillSkelText(el, text){
        if(!el) return;
        el.textContent = (text === null || text === undefined || text === "") ? "—" : String(text);
        el.style.background = "none";
        el.style.animation = "none";
        el.style.height = "auto";
        el.style.width = "auto";
        el.style.color = "var(--text-primary, #0f172a)";
        el.style.fontWeight = "700";
        el.style.fontSize = "0.85rem";
    }
    function setStatusClass(header, status){
        if(!header) return;
        var m = STATUS_META[status] || STATUS_META.new;
        ALL_STATUS_CLASSES.forEach(function(c){ header.classList.remove(c); });
        header.classList.add(m.cls);
    }

    /* ---------- تعبئة الصفحة ---------- */
    function fillDetail(c){
        lastData = c;
        var root = document.getElementById("caseDetail");
        if(!root) return;

        /* شريط الإجراءات */
        fillKeepIcon(root.querySelector(".cd-actionbar .cd-ab-num"), c.case_number);
        fillStatusBadge(root.querySelector(".cd-actionbar .cd-status-badge"), c.status_label, c.status, true);

        /* رأس الحالة */
        var header = root.querySelector(".cd-header");
        setStatusClass(header, c.status);
        if(header){
            fillKeepIcon(header.querySelector(".cd-case-num"), c.case_number);
            fillStatusBadge(header.querySelector(".cd-status-badge"), c.status_label, c.status, false); // اللون من صنف الرأس
        }

        /* شبكة المعلومات (8 عناصر بالترتيب) */
        var mv = root.querySelectorAll(".cd-header-meta .cd-meta-value");
        if(mv.length >= 8){
            setText(mv[0], c.type_label);
            fillPriorityInline(mv[1], c.priority, c.priority_label);
            setText(mv[2], c.status_label);
            setText(mv[3], fmtDateTime(c.created_at));
            setText(mv[4], fmtDateTime(c.updated_at));
            setText(mv[5], c.counts.attachments);
            setText(mv[6], c.counts.participants);
            setText(mv[7], c.counts.messages);
        }

        /* عنوان الاستشارة وتفاصيل الحالة (البطاقة المضافة) */
        setText(root.querySelector(".cd-case-subject"), c.title);
        setText(root.querySelector(".cd-case-desc"), c.description);

        /* المشاركون: المُرسِل + الطبيب الرئيسي (إن وُجد) + كل طبيب مشارك حقيقي
           (consultation_participants) — تُعاد كتابة الشبكة بالكامل من بيانات
           الخادم في كل تحميل، فتظهر أي إضافة جديدة مباشرة. */
        renderParticipants(root, c);

        /* بطاقة المريض */
        fillPatient(root.querySelector(".cd-patient-locked"), c);

        /* الشريط الجانبي (7 صفوف) */
        var rows = root.querySelectorAll(".cd-side-body .cd-side-row");
        if(rows.length >= 7){
            fillStatusBadge(rows[0].querySelector(".cd-side-badge"), c.status_label, c.status, true);
            fillPriorityBadge(rows[1].querySelector(".cd-side-badge"), c.priority_label, c.priority);
            setText(rows[2].querySelector(".cd-side-num"), c.counts.participants);
            setText(rows[3].querySelector(".cd-side-num"), c.counts.messages);
            setText(rows[4].querySelector(".cd-side-num"), c.counts.attachments);
            setText(rows[5].querySelector(".cd-side-val"), fmtDate(c.created_at));
            setText(rows[6].querySelector(".cd-side-val"), fmtDate(c.updated_at));
        }

        /* سجل العمليات — أول عنصر (تم إنشاء الحالة) ببيانات حقيقية */
        var firstTl = root.querySelector(".cd-timeline .cd-tl-item");
        if(firstTl){
            fillSkelText(firstTl.querySelector(".cd-tl-doctor .cd-line"), c.creator_name || "—");
            setText(firstTl.querySelector(".cd-tl-time"), fmtDateTime(c.created_at));
        }
    }

    /* ---------- المشاركون في الحالة (بطاقات ديناميكية) ----------
       تُبنى كل بطاقة بنفس بنية HTML الأصلية (cd-part-card / cd-part-av /
       cd-part-info / small.role-*) الموجودة أصلاً في الملف، فقط تُنشأ
       ديناميكياً بعدد يطابق البيانات الحقيقية بدل 3 بطاقات ثابتة. */
    function buildPartCard(name, avatarExtraClass, roleClass, roleLabel){
        var card = document.createElement("div");
        card.className = "cd-part-card";

        var av = document.createElement("span");
        av.className = "cd-part-av" + (avatarExtraClass ? (" " + avatarExtraClass) : "");
        av.innerHTML = '<i class="fas fa-user-md"></i>';

        var info = document.createElement("span");
        info.className = "cd-part-info";

        var nameEl = document.createElement("span");
        nameEl.textContent = name || "—";
        nameEl.style.fontWeight = "700";
        nameEl.style.fontSize = "0.85rem";
        nameEl.style.color = "var(--text-primary, #0f172a)";

        var small = document.createElement("small");
        small.className = roleClass;
        small.textContent = roleLabel;

        info.appendChild(nameEl);
        info.appendChild(small);

        card.appendChild(av);
        card.appendChild(info);
        return card;
    }

    function renderParticipants(root, c){
        var grid = root.querySelector(".cd-part-grid");
        if(!grid) return;

        grid.innerHTML = "";
        grid.appendChild(buildPartCard(c.creator_name || "—", "", "role-send", "الطبيب المرسل"));

        if(c.assigned_doctor_name && String(c.assigned_doctor_name).trim() !== ""){
            grid.appendChild(buildPartCard(c.assigned_doctor_name, "consult", "role-consult", "الطبيب الرئيسي"));
        }

        (c.participants || []).forEach(function(p){
            grid.appendChild(buildPartCard(p.full_name, "consult", "role-consult", "طبيب مشارك"));
        });
    }

    function fillPatient(box, c){
        if(!box) return;
        var b = box.querySelector("b");
        var span = box.querySelector("span");
        if(intToBool(c.hide_patient_identity)){
            if(b) b.textContent = "بيانات المريض مخفية";
            if(span) span.textContent = "تم تفعيل الخصوصية لهذه الحالة، لذا لن تظهر أي بيانات شخصية للمريض.";
        } else {
            if(b) b.textContent = c.patient_name ? c.patient_name : "مريض هذه الحالة";
            if(span) span.textContent = c.patient_name ? "" : "لا يتوفّر اسم مسجَّل لهذا المريض.";
        }
    }
    function intToBool(v){ return String(v) === "1" || v === 1 || v === true; }

    /* ---------- جلب التفاصيل ---------- */
    function loadDetails(id){
        if(!id) return;
        fetch(DETAILS_URL + "?id=" + encodeURIComponent(id), { credentials:"same-origin" })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if(data && data.success && data.consultation){
                    fillDetail(data.consultation);
                    loadMessages(id);           // تحميل/تحديث المحادثة الحقيقية لهذه الحالة
                } else {
                    notify((data && data.message) ? data.message : "تعذّر جلب تفاصيل الاستشارة", "error");
                }
            })
            .catch(function(e){ console.error("loadDetails failed", e); notify("تعذّر الاتصال بالخادم.", "error"); });
    }

    /* ================= المحادثة الحقيقية (Chat) ================= */

    function chatWindowEl(){
        var root = document.getElementById("caseDetail");
        return root ? root.querySelector(".cd-chat-window") : null;
    }
    function composerEls(){
        var root = document.getElementById("caseDetail");
        if(!root) return null;
        var composer = root.querySelector(".cd-composer");
        if(!composer) return null;
        return {
            composer: composer,
            textarea: composer.querySelector("textarea"),
            sendBtn:  composer.querySelector(".cd-send-btn"),
            charCount: composer.querySelector(".cd-char-count")
        };
    }

    // يبني عنصر رسالة واحد بنفس بنية HTML الأصلية (cd-msg / cd-msg-av / cd-msg-body ...)
    function buildMsgNode(m){
        var wrap = document.createElement("div");
        wrap.className = "cd-msg " + (m.mine ? "sent" : "received");

        var av = document.createElement("span");
        av.className = "cd-msg-av";
        av.innerHTML = '<i class="fas ' + (m.mine ? "fa-user" : "fa-user-md") + '"></i>';

        var body = document.createElement("span");
        body.className = "cd-msg-body";

        var meta = document.createElement("span");
        meta.className = "cd-msg-meta";
        var b = document.createElement("b");
        b.textContent = m.mine ? "أنت" : (m.sender_name || "—");
        var timeSpan = document.createElement("span");
        timeSpan.textContent = fmtDateTime(m.created_at);
        meta.appendChild(b);
        meta.appendChild(timeSpan);

        var bubble = document.createElement("span");
        bubble.className = "cd-bubble";
        bubble.style.whiteSpace = "pre-wrap";
        bubble.textContent = m.message || "";

        body.appendChild(meta);
        body.appendChild(bubble);
        wrap.appendChild(av);
        wrap.appendChild(body);
        return wrap;
    }

    function renderMessages(list){
        var win = chatWindowEl();
        if(!win) return;
        win.innerHTML = "";
        var root = document.getElementById("caseDetail");
        var empty = root ? root.querySelector(".panel-chat .cd-empty") : null;

        if(!list || !list.length){
            if(empty) empty.classList.remove("mc-hidden");
            return;
        }
        if(empty) empty.classList.add("mc-hidden");

        list.forEach(function(m){
            win.appendChild(buildMsgNode(m));
        });
        win.scrollTop = win.scrollHeight;
    }

    function updateMessagesCount(count){
        var root = document.getElementById("caseDetail");
        if(!root) return;
        var mv = root.querySelectorAll(".cd-header-meta .cd-meta-value");
        if(mv.length >= 8) setText(mv[7], count);
        var rows = root.querySelectorAll(".cd-side-body .cd-side-row");
        if(rows.length >= 7) setText(rows[3].querySelector(".cd-side-num"), count);
        if(lastData) lastData.counts = Object.assign({}, lastData.counts, { messages: count });
    }

    function loadMessages(id){
        if(!id) return;
        chatLoadedFor = id;
        fetch(MESSAGES_URL + "?id=" + encodeURIComponent(id), { credentials:"same-origin" })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if(chatLoadedFor !== id) return; // تبديل الاستشارة أثناء الجلب
                if(data && data.success){
                    renderMessages(data.messages || []);
                    if(data.counts) updateMessagesCount(data.counts.messages);
                } else {
                    // لا نعرض تنبيهاً هنا: هذا الاستدعاء يُنفَّذ فقط بعد أن أكّد
                    // loadDetails بنجاح أن الاستشارة موجودة ومصرَّح بعرضها، لذا
                    // أي فشل في جلب المحادثة تحديداً ليس فشلاً حقيقياً في
                    // الاستشارة نفسها ولا ينبغي أن يُظهر رسالة مضلِّلة مثل
                    // "الاستشارة غير موجودة" فوق بيانات مُحمَّلة بنجاح.
                    console.warn("loadMessages: ", (data && data.message) || "تعذّر جلب المحادثة");
                }
            })
            .catch(function(e){ console.error("loadMessages failed", e); });
    }

    function sendMessage(){
        if(!lastData){ notify("افتح استشارة أولاً.", "error"); return; }
        var els = composerEls();
        if(!els || !els.textarea) return;
        var text = els.textarea.value.trim();
        if(!text) return;

        els.sendBtn && (els.sendBtn.disabled = true);

        fetch(REPLY_URL, {
            method:"POST",
            headers:{ "Content-Type":"application/json" },
            credentials:"same-origin",
            body: JSON.stringify({ id: lastData.id, message: text })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(data && data.success){
                els.textarea.value = "";
                if(els.charCount) els.charCount.textContent = "0 / 2000";
                var win = chatWindowEl();
                var root = document.getElementById("caseDetail");
                var empty = root ? root.querySelector(".panel-chat .cd-empty") : null;
                if(empty) empty.classList.add("mc-hidden");
                if(win){
                    win.appendChild(buildMsgNode(data.message_data));
                    win.scrollTop = win.scrollHeight;
                }
                if(data.counts) updateMessagesCount(data.counts.messages);
            } else {
                notify((data && data.message) ? data.message : "تعذّر إرسال الرسالة", "error");
            }
        })
        .catch(function(e){ console.error("sendMessage failed", e); notify("تعذّر الاتصال بالخادم.", "error"); })
        .finally(function(){ els.sendBtn && (els.sendBtn.disabled = false); });
    }

    function bindComposer(){
        var els = composerEls();
        if(!els) return;
        if(els.sendBtn) els.sendBtn.addEventListener("click", sendMessage);
        if(els.textarea){
            els.textarea.addEventListener("keydown", function(e){
                if(e.key === "Enter" && !e.shiftKey){
                    e.preventDefault();
                    sendMessage();
                }
            });
            els.textarea.addEventListener("input", function(){
                if(els.charCount){
                    var len = els.textarea.value.length;
                    els.charCount.textContent = len + " / 2000";
                }
            });
        }
    }

    /* ---------- تحديث الحالة ---------- */
    function updateStatus(id, status){
        if(!id) return;
        fetch(STATUS_URL, {
            method:"POST",
            headers:{ "Content-Type":"application/json" },
            credentials:"same-origin",
            body: JSON.stringify({ id:id, status:status })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(data && data.success){
                notify(data.message || "تم تحديث الحالة.", "success");
                loadDetails(id);                                   // إعادة تعبئة التفاصيل
                if(typeof window.cnsReloadSent === "function"){    // تحديث البطاقة في القائمة
                    window.cnsReloadSent();
                }
            } else {
                notify((data && data.message) ? data.message : "تعذّر تحديث الحالة", "error");
            }
        })
        .catch(function(e){ console.error("updateStatus failed", e); notify("تعذّر الاتصال بالخادم.", "error"); });
    }

    var STATUS_ORDER = ["new","in_review","answered","closed"];
    function cycleStatus(){
        if(!lastData){ notify("افتح استشارة أولاً.", "error"); return; }
        var idx = STATUS_ORDER.indexOf(lastData.status);
        var next = STATUS_ORDER[(idx + 1) % STATUS_ORDER.length];
        updateStatus(lastData.id, next);
    }
    function closeCase(){
        if(!lastData){ notify("افتح استشارة أولاً.", "error"); return; }
        if(lastData.status === "closed"){ notify("الحالة مغلقة بالفعل.", "error"); return; }
        updateStatus(lastData.id, "closed");
    }

    /* ---------- الطباعة ---------- */
    function printCase(){
        if(!lastData){ notify("افتح استشارة أولاً.", "error"); return; }
        var c = lastData;
        var patient = intToBool(c.hide_patient_identity) ? "مخفي (خصوصية مفعّلة)" : (c.patient_name || "—");
        var rows = [
            ["رقم الحالة", c.case_number],
            ["نوع الاستشارة", c.type_label],
            ["النطاق", c.scope_label],
            ["الحالة", c.status_label],
            ["الأولوية", c.priority_label],
            ["الطبيب المُرسِل", c.creator_name || "—"],
            ["الطبيب المختار", c.assigned_doctor_name || "—"],
            ["المريض", patient],
            ["تاريخ الإنشاء", fmtDateTime(c.created_at)],
            ["آخر تحديث", fmtDateTime(c.updated_at)]
        ];
        var trs = rows.map(function(r){
            return "<tr><th>"+esc(r[0])+"</th><td>"+esc(r[1])+"</td></tr>";
        }).join("");
        var html =
            "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'><title>"+esc(c.case_number)+"</title>"+
            "<style>body{font-family:'Cairo',Arial,sans-serif;padding:30px;color:#0f172a;}"+
            "h1{font-size:20px;margin:0 0 4px;}h2{font-size:14px;color:#0ea5e9;margin:0 0 20px;font-weight:normal;}"+
            "table{width:100%;border-collapse:collapse;margin-bottom:20px;}"+
            "th,td{border:1px solid #cbd5e1;padding:10px 12px;text-align:right;font-size:13px;}"+
            "th{background:#f1f5f9;width:190px;}"+
            ".desc{border:1px solid #cbd5e1;border-radius:8px;padding:12px;font-size:13px;line-height:1.8;white-space:pre-wrap;}"+
            ".lbl{font-weight:700;margin:0 0 8px;font-size:13px;}</style></head><body>"+
            "<h1>تفاصيل الاستشارة الطبية</h1><h2>MedChifaGiz — "+esc(c.case_number)+"</h2>"+
            "<table>"+trs+"</table>"+
            "<p class='lbl'>موضوع الاستشارة</p><div class='desc'>"+esc(c.title || "—")+"</div>"+
            "<p class='lbl' style='margin-top:16px;'>تفاصيل الحالة</p><div class='desc'>"+esc(c.description || "—")+"</div>"+
            "</body></html>";
        var w = window.open("", "_blank");
        if(!w){ notify("فضلاً اسمح بالنوافذ المنبثقة لإتمام الطباعة.", "error"); return; }
        w.document.write(html);
        w.document.close();
        w.focus();
        setTimeout(function(){ try{ w.print(); }catch(e){} }, 350);
    }

    /* ================= إضافة طبيب مشارك =================
       تعيد استخدام نفس نظام البحث عن الأطباء الموجود أصلاً في نافذة إنشاء
       الاستشارة (قسم "الأطباء المشاركون") عبر الوحدة المشتركة
       window.cnsCreateDoctorSearchWidget المُعرَّفة في dr_dashboard.js —
       بدون أي نظام بحث جديد وبدون أي كود مكرر. نفس نداء البحث تماماً
       (search_doctors.php)، فقط الإجراء عند الاختيار مختلف: حفظ فوري في
       قاعدة البيانات عبر add_consultation_participant.php بدل تجميع Chips. */
    var ADD_PARTICIPANT_URL = "add_consultation_participant.php";
    var addPartWidget = null;

    function addPartBox(){
        var root = document.getElementById("caseDetail");
        return root ? root.querySelector("#cdAddPartBox") : null;
    }
    function addPartSearchInput(){
        var root = document.getElementById("caseDetail");
        return root ? root.querySelector("#cdAddPartDoctorSearch") : null;
    }
    function addPartResults(){
        var root = document.getElementById("caseDetail");
        return root ? root.querySelector("#cdAddPartDoctorResults") : null;
    }

    // نفس صيغة معرّفات search_doctors.php: "clinic_<id>" لطبيب عيادة، "user_<id>" لطبيب خاص
    function doctorCompositeId(id, type){
        return (type === "clinic_staff" ? "clinic_" : "user_") + id;
    }

    // معرّفات الأطباء الممنوع اختيارهم كمشارك جديد: المُنشئ، الطبيب الرئيسي، والمشاركون الحاليون
    function buildExcludedIds(c){
        var set = new Set();
        if(c.created_by) set.add(doctorCompositeId(c.created_by, c.creator_type || "private"));
        if(c.assigned_doctor_id) set.add(doctorCompositeId(c.assigned_doctor_id, c.assigned_doctor_type || "clinic_staff"));
        (c.participants || []).forEach(function(p){ set.add(doctorCompositeId(p.id, p.type)); });
        return set;
    }
    function excludedLabelFor(c, id){
        if(c.created_by && id === doctorCompositeId(c.created_by, c.creator_type || "private")) return "الطبيب المُنشئ";
        if(c.assigned_doctor_id && id === doctorCompositeId(c.assigned_doctor_id, c.assigned_doctor_type || "clinic_staff")) return "الطبيب الرئيسي";
        return "مُضاف مسبقاً";
    }

    function ensureAddPartWidget(){
        if(addPartWidget) return addPartWidget;
        var input = addPartSearchInput();
        var results = addPartResults();
        if(!input || !results || typeof window.cnsCreateDoctorSearchWidget !== "function") return null;

        addPartWidget = window.cnsCreateDoctorSearchWidget({
            inputEl: input,
            resultsEl: results,
            getExcludedIds: function(){ return lastData ? buildExcludedIds(lastData) : new Set(); },
            excludedLabel: function(id){ return lastData ? excludedLabelFor(lastData, id) : "غير متاح"; },
            onPick: function(doc){ addParticipant(doc); }
        });
        addPartWidget.bindInput('<div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية لإضافته كمشارك.</div>');
        return addPartWidget;
    }

    function openAddPartBox(){
        if(!lastData){ notify("افتح استشارة أولاً.", "error"); return; }
        var box = addPartBox();
        if(!box) return;

        ensureAddPartWidget();

        var input = addPartSearchInput();
        if(input) input.value = "";
        var results = addPartResults();
        if(results) results.innerHTML = '<div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية لإضافته كمشارك.</div>';

        box.classList.remove("mc-hidden");
        if(input) input.focus();
    }
    function closeAddPartBox(){
        var box = addPartBox();
        if(box) box.classList.add("mc-hidden");
    }

    function addParticipant(doc){
        if(!lastData) return;
        fetch(ADD_PARTICIPANT_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ case_id: lastData.id, doctor_id: doc.id })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(data && data.success){
                notify(data.message || "تمت إضافة المشارك.", "success");
                closeAddPartBox();
                loadDetails(lastData.id); // إعادة تحميل كامل التفاصيل: المشاركون + العدّادات محدّثة من الخادم
            } else {
                notify((data && data.message) ? data.message : "تعذّر إضافة المشارك", "error");
            }
        })
        .catch(function(e){ console.error("addParticipant failed", e); notify("تعذّر الاتصال بالخادم.", "error"); });
    }

    /* ---------- التهيئة ---------- */
    function bindButtons(){
        var root = document.getElementById("caseDetail");
        if(!root) return;
        var b;
        if((b = root.querySelector(".cd-action-btn.status")))    b.addEventListener("click", cycleStatus);
        if((b = root.querySelector(".cd-action-btn.closecase"))) b.addEventListener("click", closeCase);
        if((b = root.querySelector(".cd-action-btn.print")))     b.addEventListener("click", printCase);
        if((b = root.querySelector(".cd-action-btn.adddoc")))    b.addEventListener("click", openAddPartBox);
        if((b = root.querySelector("#cdAddPartCloseBtn")))       b.addEventListener("click", function(e){
            e.stopPropagation();
            closeAddPartBox();
        });
        if((b = root.querySelector(".cd-action-btn.addfile")))   b.addEventListener("click", function(){
            notify("سيتم تفعيل إضافة المرفقات في المرحلة القادمة", "success");
        });
    }

    function init(){
        bindButtons();
        bindComposer();
        // النقر على أي بطاقة استشارة → جلب تفاصيلها (تفويض حدث)
        document.addEventListener("click", function(e){
            if(!e.target || !e.target.closest) return;
            var link = e.target.closest(".case-card-link");
            if(!link) return;
            var id = link.getAttribute("data-case-id");
            if(id) loadDetails(id);
        });
    }

    if(document.readyState === "loading"){
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
