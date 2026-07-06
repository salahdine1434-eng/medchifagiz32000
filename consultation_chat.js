/* ============================================================================
 * consultation_chat.js
 * ----------------------------------------------------------------------------
 * مرحلة المحادثة الحقيقية بين الأطباء (اعتماداً على قاعدة البيانات فقط).
 *
 * - عند فتح استشارة (النقر على بطاقتها) يجلب رسائلها مرتبة زمنياً ويرسمها.
 * - عند الضغط على "إرسال" يحفظ الرسالة في قاعدة البيانات ثم يعيد تحميل المحادثة.
 * - عند الضغط على أيقونة المشبك (زر موجود أصلاً في الواجهة) يفتح اختيار صورة
 *   (jpg/jpeg/png/webp فقط)، يرفعها إلى upload_consultation_files.php،
 *   وتُضاف كرسالة من نوع image في نفس المحادثة عند الطرفين.
 * - يظهر لكل طرف (المُنشئ / الطبيب المختار / أي مشارك) نفس الرسائل.
 * - يحدّث عدّاد الرسائل داخل الاستشارة.
 * - بدون WebSocket/Firebase، بدون إشعارات، بدون تعديل تصميم/CSS، وبدون أي
 *   زر جديد — فقط تفعيل زر المشبك الموجود أصلاً في .cd-composer-bar.
 *
 * مستقل تماماً: يقرأ case_id من data-case-id للبطاقة (يرسمها consultation_save.js)،
 * ويستعمل عناصر المحادثة الموجودة كما هي دون تغيير HTML.
 * ========================================================================== */
(function () {
    "use strict";

    var MESSAGES_URL = "get_consultation_messages.php";
    var REPLY_URL    = "reply_consultation.php";
    var UPLOAD_URL   = "upload_consultation_files.php";
    var MAX_CHARS    = 2000;
    var ALLOWED_IMAGE_EXT = ["jpg", "jpeg", "png", "webp"];
    var ALLOWED_IMAGE_MIME = ["image/jpeg", "image/png", "image/webp"];

    var currentCaseId = null;
    var sending = false;
    var uploading = false;
    var hiddenFileInput = null;

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
            setTimeout(function(){ t.style.transition="opacity .3s"; t.style.opacity="0"; }, 2400);
            setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 2800);
        } catch(e){ /* صامت */ }
    }
    function fmtTime(s) {
        if (!s) return "";
        var parts = String(s).split(" ");
        var time = (parts[1] || "").slice(0,5);
        return time || (parts[0] || "");
    }

    /* ---------- عناصر المحادثة (داخل #caseDetail) ---------- */
    function root()        { return document.getElementById("caseDetail"); }
    function chatWindow()  { var r=root(); return r ? r.querySelector(".cd-chat-window") : null; }
    function chatCard()    { var r=root(); return r ? r.querySelector(".panel-chat .cd-card") : null; }
    function chatEmpty()   { var c=chatCard(); return c ? c.querySelector(".cd-empty") : null; }
    function composerText(){ var r=root(); return r ? r.querySelector(".cd-composer textarea") : null; }
    function sendBtn()     { var r=root(); return r ? r.querySelector(".cd-send-btn") : null; }
    function charCount()   { var r=root(); return r ? r.querySelector(".cd-char-count") : null; }
    // زر المشبك الموجود أصلاً في شريط أدوات صندوق الكتابة (لم تتم إضافته، فقط تفعيله)
    function attachBtn()   { var r=root(); return r ? r.querySelector(".cd-composer-bar .cd-tool-btn[title=\"إرفاق ملف\"]") : null; }

    /* ---------- بناء فقاعة رسالة (نفس مارك-أب المشروع) ---------- */
    function buildBubble(m) {
        var mine = !!m.is_mine;
        var side = mine ? "sent" : "received";
        var icon = mine ? "fa-user" : "fa-user-md";
        var name = mine ? "أنت" : (m.sender_name || "طبيب");
        var bodyHtml;

        if (m.type === "image" && m.file_url) {
            // صورة قابلة للنقر لفتحها بالحجم الكامل في تبويب جديد
            bodyHtml =
                '<a href="' + esc(m.file_url) + '" target="_blank" rel="noopener" class="cd-chat-img-link">' +
                    '<img src="' + esc(m.file_url) + '" alt="صورة مرفقة" class="cd-chat-img" ' +
                        'style="max-width:220px;max-height:220px;border-radius:12px;display:block;cursor:zoom-in;object-fit:cover;">' +
                '</a>';
        } else {
            bodyHtml = '<span class="cd-bubble">' + esc(m.text) + '</span>';
        }

        return "" +
            '<div class="cd-msg ' + side + '">' +
                '<span class="cd-msg-av"><i class="fas ' + icon + '"></i></span>' +
                '<span class="cd-msg-body">' +
                    '<span class="cd-msg-meta"><b>' + esc(name) + '</b><span>' + esc(fmtTime(m.created_at)) + '</span></span>' +
                    bodyHtml +
                '</span>' +
            '</div>';
    }

    /* ---------- رسم الرسائل ---------- */
    function renderMessages(list) {
        var win = chatWindow();
        if (!win) return;
        var empty = chatEmpty();

        if (!list || list.length === 0) {
            win.innerHTML = "";
            if (empty) empty.classList.remove("mc-hidden");  // إظهار الحالة الفارغة الموجودة
            return;
        }
        if (empty) empty.classList.add("mc-hidden");

        var html = "";
        for (var i = 0; i < list.length; i++) html += buildBubble(list[i]);
        win.innerHTML = html;
        win.scrollTop = win.scrollHeight;  // آخر رسالة بالأسفل
    }

    /* ---------- تحديث عدّاد الرسائل (العناصر الموجودة فقط) ---------- */
    function updateCount(n) {
        var r = root();
        if (!r) return;
        // شبكة المعلومات: عنصر "عدد الرسائل"
        r.querySelectorAll(".cd-header-meta .cd-meta-item").forEach(function (item) {
            var lbl = item.querySelector(".cd-meta-label");
            if (lbl && lbl.textContent.indexOf("عدد الرسائل") !== -1) {
                var v = item.querySelector(".cd-meta-value");
                if (v) v.textContent = String(n);
            }
        });
        // الشريط الجانبي: صف "عدد الرسائل"
        r.querySelectorAll(".cd-side-body .cd-side-row").forEach(function (row) {
            var lbl = row.querySelector(".cd-side-label");
            if (lbl && lbl.textContent.indexOf("عدد الرسائل") !== -1) {
                var v = row.querySelector(".cd-side-num");
                if (v) v.textContent = String(n);
            }
        });
    }

    /* ---------- جلب الرسائل ---------- */
    function loadMessages(id) {
        if (!id) return;
        fetch(MESSAGES_URL + "?case_id=" + encodeURIComponent(id), { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    renderMessages(data.messages || []);
                    updateCount(data.messages_count || 0);
                } else {
                    // في حال عدم الصلاحية أو خطأ: لا نكسر الواجهة
                    renderMessages([]);
                    if (data && data.message) console.warn("messages:", data.message);
                }
            })
            .catch(function (e) { console.error("loadMessages failed", e); });
    }

    /* ---------- إرسال رسالة نصية ---------- */
    function sendMessage() {
        if (sending) return;
        if (!currentCaseId) { notify("افتح استشارة أولاً.", "error"); return; }
        var ta = composerText();
        if (!ta) return;
        var text = ta.value.trim();
        if (text === "") { notify("اكتب رسالة أولاً.", "error"); return; }

        sending = true;
        var btn = sendBtn();
        if (btn) { btn.disabled = true; btn.style.opacity = "0.7"; }

        fetch(REPLY_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ case_id: currentCaseId, message: text })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                ta.value = "";
                syncCharCount();
                loadMessages(currentCaseId);   // إعادة التحميل لعرض الترتيب الرسمي + العدّاد
            } else {
                notify((data && data.message) ? data.message : "تعذّر إرسال الرسالة", "error");
            }
        })
        .catch(function (e) { console.error("sendMessage failed", e); notify("تعذّر الاتصال بالخادم.", "error"); })
        .finally(function () {
            sending = false;
            if (btn) { btn.disabled = false; btn.style.opacity = ""; }
        });
    }

    /* ---------- عدّاد الأحرف (تفعيل العنصر الموجود) ---------- */
    function syncCharCount() {
        var ta = composerText(), cc = charCount();
        if (ta && cc) {
            var len = ta.value.length;
            cc.textContent = len + " / " + MAX_CHARS;
        }
    }

    /* ================= رفع وإرسال صورة (تفعيل زر المشبك الموجود) ================= */

    // إنشاء حقل ملف مخفي واحد (ليس عنصر واجهة جديد ظاهر — فقط آلية فتح نافذة اختيار الملفات)
    function ensureHiddenFileInput() {
        if (hiddenFileInput) return hiddenFileInput;
        hiddenFileInput = document.createElement("input");
        hiddenFileInput.type = "file";
        hiddenFileInput.accept = "image/jpeg,image/png,image/webp";
        hiddenFileInput.style.display = "none";
        document.body.appendChild(hiddenFileInput);
        hiddenFileInput.addEventListener("change", function () {
            var f = hiddenFileInput.files && hiddenFileInput.files[0];
            hiddenFileInput.value = ""; // للسماح باختيار نفس الملف مرة أخرى لاحقاً
            if (f) handlePickedImage(f);
        });
        return hiddenFileInput;
    }

    function validateImageFile(file) {
        var name = file.name || "";
        var ext = (name.split(".").pop() || "").toLowerCase();
        if (ALLOWED_IMAGE_EXT.indexOf(ext) === -1) {
            return "صيغة الملف غير مدعومة. المسموح: JPG, JPEG, PNG, WEBP فقط.";
        }
        if (file.type && ALLOWED_IMAGE_MIME.indexOf(file.type) === -1) {
            return "صيغة الملف غير مدعومة. المسموح: JPG, JPEG, PNG, WEBP فقط.";
        }
        var MAX_BYTES = 10 * 1024 * 1024;
        if (file.size > MAX_BYTES) {
            return "حجم الصورة يتجاوز الحد المسموح (10MB).";
        }
        return null;
    }

    function handlePickedImage(file) {
        if (!currentCaseId) { notify("افتح استشارة أولاً.", "error"); return; }
        if (uploading) return;

        var err = validateImageFile(file);
        if (err) { notify(err, "error"); return; }

        uploading = true;
        var btn = attachBtn();
        if (btn) { btn.disabled = true; btn.style.opacity = "0.6"; }

        var fd = new FormData();
        fd.append("file", file);
        fd.append("case_id", currentCaseId);

        fetch(UPLOAD_URL, {
            method: "POST",
            credentials: "same-origin",
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                loadMessages(currentCaseId); // إعادة التحميل: تظهر الصورة عند المرسل والطرف الآخر معاً
            } else {
                notify((data && data.message) ? data.message : "تعذّر رفع الصورة", "error");
            }
        })
        .catch(function (e) { console.error("uploadImage failed", e); notify("تعذّر الاتصال بالخادم أثناء رفع الصورة.", "error"); })
        .finally(function () {
            uploading = false;
            if (btn) { btn.disabled = false; btn.style.opacity = ""; }
        });
    }

    /* ---------- التهيئة ---------- */
    function init() {
        // النقر على أي بطاقة استشارة → فتحها + جلب رسائلها
        document.addEventListener("click", function (e) {
            if (!e.target || !e.target.closest) return;
            var link = e.target.closest(".case-card-link");
            if (!link) return;
            var id = link.getAttribute("data-case-id");
            if (id) {
                currentCaseId = id;
                loadMessages(id);
            }
        });

        // زر الإرسال
        var btn = sendBtn();
        if (btn) btn.addEventListener("click", sendMessage);

        // عدّاد الأحرف أثناء الكتابة
        var ta = composerText();
        if (ta) ta.addEventListener("input", syncCharCount);

        // زر المشبك الموجود أصلاً في الواجهة → فتح اختيار صورة ورفعها
        var clip = attachBtn();
        if (clip) {
            clip.addEventListener("click", function () {
                if (!currentCaseId) { notify("افتح استشارة أولاً.", "error"); return; }
                ensureHiddenFileInput().click();
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    // تحديث يدوي عند الحاجة
    window.cnsChatReload = function () { if (currentCaseId) loadMessages(currentCaseId); };
})();
