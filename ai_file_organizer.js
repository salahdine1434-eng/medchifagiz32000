/**
 * ai_file_organizer.js
 * ════════════════════════════════════════════════════════════════
 *  منطق واجهة «تنظيم الملفات تلقائياً» (مدمجة داخل dr_dashboard.php).
 *  • يقرأ نقطة النهاية من #afo-root[data-endpoint].
 *  • تحميل كسول: يجلب البيانات أول مرة يُفتح فيها القسم فقط (class="active").
 *  • زر «عرض الملف الطبي» يستدعي نفس آلية الداشبورد: openArchiveRecord(id).
 *  • يعيد استخدام Groq في الخادم — لا اتصال جديد من المتصفّح.
 * ════════════════════════════════════════════════════════════════
 */
(function () {
    "use strict";

    var els = {};
    var endpoint = "ai_file_organizer_api.php";
    var organizing = false;
    var loadedOnce = false;

    ready(init);

    function init() {
        var root = document.getElementById("afo-root");
        if (root && root.getAttribute("data-endpoint")) {
            endpoint = root.getAttribute("data-endpoint");
        }

        els.btn      = document.getElementById("afoOrganizeBtn");
        els.progress = document.getElementById("afoProgress");
        els.pText    = document.getElementById("afoProgressText");
        els.pCount   = document.getElementById("afoProgressCount");
        els.pFill    = document.getElementById("afoProgressFill");
        els.notice   = document.getElementById("afoNotice");
        els.files    = document.getElementById("afoFiles");
        els.empty    = document.getElementById("afoEmpty");
        els.statOrg  = document.getElementById("statOrganized");
        els.statHigh = document.getElementById("statHigh");
        els.statInc  = document.getElementById("statIncomplete");
        els.statFup  = document.getElementById("statFollowup");

        if (!els.btn) { return; } // القسم غير موجود في هذه الصفحة

        els.btn.addEventListener("click", startOrganize);

        // تحميل كسول: ننتظر أن يصبح قسم .ai-content مفعّلاً (active) قبل جلب البيانات.
        var panel = document.getElementById("fileOrganizer");
        if (panel && !panel.classList.contains("afo-standalone-loaded")) {
            if (panel.classList.contains("active")) {
                loadOnce();
            } else {
                watchActivation(panel);
            }
        } else {
            loadOnce(); // صفحة مستقلة بدون toggle
        }
    }

    function watchActivation(panel) {
        var obs = new MutationObserver(function () {
            if (panel.classList.contains("active") && !loadedOnce) {
                loadOnce();
            }
        });
        obs.observe(panel, { attributes: true, attributeFilter: ["class"] });
    }

    function loadOnce() {
        if (loadedOnce) { return; }
        loadedOnce = true;
        loadList();
    }

    /* ── جلب التحليلات المحفوظة ── */
    function loadList() {
        post({ action: "list" })
            .then(function (data) {
                if (!data || !data.success) {
                    return showNotice(data && data.message ? data.message : "تعذّر جلب البيانات.", "error");
                }
                renderStats(data.stats);
                renderFiles(data.files);
                updateButtonHint(data.stats);
            })
            .catch(function () { showNotice("تعذّر الاتصال بالخادم.", "error"); });
    }

    /* ── التنظيم على دفعات ── */
    function startOrganize() {
        if (organizing) { return; }
        loadedOnce = true;
        organizing = true;
        hideNotice();
        setButtonBusy(true);
        els.progress.hidden = false;
        setProgress(0, "جارٍ تحضير الملفات…", "");

        var totalProcessed = 0;
        var totalToDo = null;

        function step() {
            post({ action: "organize", batch: 4 })
                .then(function (data) {
                    if (!data || !data.success) {
                        return finish(false, data && data.message ? data.message : "فشل التحليل.");
                    }
                    totalProcessed += (data.processed || 0);
                    if (totalToDo === null) {
                        totalToDo = totalProcessed + (data.remaining || 0);
                    }
                    var denom = totalToDo > 0 ? totalToDo : 1;
                    var pct = Math.min(100, Math.round((totalProcessed / denom) * 100));
                    setProgress(
                        pct,
                        data.done ? "اكتمل التحليل" : "جارٍ تحليل الملفات عبر الذكاء الاصطناعي…",
                        totalToDo > 0 ? (totalProcessed + " / " + totalToDo) : ""
                    );

                    if (data.done) {
                        finish(true, totalProcessed > 0
                            ? ("تم تنظيم " + totalProcessed + " ملفاً بنجاح.")
                            : "كل الملفات منظّمة ومحدّثة بالفعل.");
                    } else {
                        step();
                    }
                })
                .catch(function () { finish(false, "انقطع الاتصال أثناء التحليل."); });
        }
        step();
    }

    function finish(ok, message) {
        organizing = false;
        setButtonBusy(false);
        setTimeout(function () { els.progress.hidden = true; }, ok ? 700 : 0);
        showNotice(message, ok ? "success" : "error");
        loadList();
    }

    /* ── العرض ── */
    function renderStats(s) {
        if (!s) { return; }
        animateNumber(els.statOrg, s.organized || 0);
        animateNumber(els.statHigh, s.high_priority || 0);
        animateNumber(els.statInc, s.incomplete || 0);
        animateNumber(els.statFup, s.followup || 0);
    }

    function renderFiles(files) {
        if (!files || !files.length) {
            els.files.innerHTML = "";
            els.files.appendChild(els.empty);
            els.empty.hidden = false;
            return;
        }
        els.empty.hidden = true;
        els.files.innerHTML = files.map(buildCard).join("");
    }

    /* ── قاموس عرض للتعريب (واجهة فقط — لا يمسّ البيانات أو الـ API أو قاعدة البيانات) ──
       يُستعمل فقط لاستبدال أي نص إنجليزي ظاهر داخل البطاقة بمقابله العربي عند العرض.
       أي نص عربي أو غير معروف يمرّ كما هو دون تغيير. */
    var AFO_AR = {
        // الأولوية والحالة
        "high": "عالية", "medium": "متوسطة", "low": "منخفضة",
        "high priority": "أولوية عالية", "medium priority": "أولوية متوسطة", "low priority": "أولوية منخفضة",
        "unknown": "غير محدّد", "n/a": "غير محدّد", "na": "غير محدّد", "none": "لا يوجد",
        "other": "أخرى", "others": "أخرى", "general": "عام", "uncategorized": "غير مصنّف", "misc": "متفرّقات",
        "complete": "مكتمل", "completed": "مكتمل", "incomplete": "غير مكتمل",
        "pending": "قيد الانتظار", "follow up": "تحتاج متابعة", "followup": "تحتاج متابعة", "follow-up": "تحتاج متابعة",
        // مجلّدات الأرشفة الشائعة
        "archive": "الأرشيف", "archived": "مؤرشف", "patients": "المرضى",
        "records": "السجلّات", "medical records": "السجلّات الطبية", "reports": "التقارير",
        "files": "الملفّات", "documents": "المستندات",
        // التخصّصات الشائعة
        "cardiology": "أمراض القلب", "neurology": "طب الأعصاب", "dermatology": "الأمراض الجلدية",
        "pediatrics": "طب الأطفال", "pediatric": "طب الأطفال",
        "orthopedics": "جراحة العظام", "orthopaedics": "جراحة العظام", "orthopedic": "جراحة العظام", "orthopaedic": "جراحة العظام",
        "gynecology": "النساء والتوليد", "obstetrics": "التوليد", "ophthalmology": "طب العيون",
        "ent": "الأنف والأذن والحنجرة", "otolaryngology": "الأنف والأذن والحنجرة", "psychiatry": "الطب النفسي", "oncology": "الأورام",
        "radiology": "الأشعة", "urology": "المسالك البولية", "gastroenterology": "الجهاز الهضمي",
        "endocrinology": "الغدد الصمّاء", "nephrology": "أمراض الكلى", "pulmonology": "الأمراض الصدرية",
        "general medicine": "الطب العام", "general practice": "الطب العام", "general practitioner": "الطب العام", "family medicine": "طب الأسرة",
        "internal medicine": "الطب الباطني", "surgery": "الجراحة", "surgical": "الجراحة",
        "dentistry": "طب الأسنان", "dental": "طب الأسنان", "rheumatology": "أمراض الروماتيزم", "hematology": "أمراض الدم",
        "immunology": "المناعة", "infectious disease": "الأمراض المعدية", "anesthesiology": "التخدير",
        "laboratory": "المختبر", "physiotherapy": "العلاج الطبيعي", "cardiologist": "أخصائي القلب",
        // فئات الأمراض الشائعة
        "hypertension": "ارتفاع ضغط الدم", "diabetes": "السكري", "diabetes mellitus": "داء السكري",
        "asthma": "الربو", "anemia": "فقر الدم", "allergy": "حساسية", "infection": "عدوى",
        "fracture": "كسر", "migraine": "الصداع النصفي", "obesity": "السمنة", "depression": "الاكتئاب",
        "anxiety": "القلق", "arthritis": "التهاب المفاصل", "pneumonia": "ذات الرئة",
        "bronchitis": "التهاب الشُّعب الهوائية", "influenza": "الإنفلونزا", "flu": "الإنفلونزا",
        "covid-19": "كوفيد-19", "covid": "كوفيد-19", "hypothyroidism": "قصور الغدة الدرقية",
        "hyperthyroidism": "فرط نشاط الغدة الدرقية", "gastritis": "التهاب المعدة", "ulcer": "قرحة",
        "dermatitis": "التهاب الجلد", "eczema": "الإكزيما", "hepatitis": "التهاب الكبد",
        "cancer": "سرطان", "tumor": "ورم", "stroke": "سكتة دماغية", "epilepsy": "الصرع",
        // اختصارات وفحوص شائعة
        "ecg": "تخطيط القلب", "ekg": "تخطيط القلب", "mri": "الرنين المغناطيسي",
        "ct": "الأشعة المقطعية", "ct scan": "الأشعة المقطعية", "x-ray": "الأشعة السينية", "xray": "الأشعة السينية",
        "bp": "ضغط الدم", "cbc": "تعداد الدم الكامل", "ultrasound": "الموجات فوق الصوتية",
        "lab": "المختبر", "lab results": "نتائج المختبر", "blood test": "تحليل الدم", "prescription": "وصفة طبية",
        // إصابات وأعراض شائعة
        "injury": "إصابة", "joint": "مفصل", "joint injury": "إصابة بالمفصل", "joint pain": "ألم المفاصل",
        "sprain": "التواء", "strain": "شدّ عضلي", "wound": "جرح", "burn": "حرق", "bruise": "كدمة",
        "back pain": "ألم الظهر", "neck pain": "ألم الرقبة", "chest pain": "ألم الصدر", "abdominal pain": "ألم البطن",
        "headache": "صداع", "dizziness": "دوخة", "nausea": "غثيان", "vomiting": "تقيّؤ", "fatigue": "إرهاق",
        "fever": "حمّى", "cough": "سعال", "shortness of breath": "ضيق في التنفّس", "rash": "طفح جلدي",
        "swelling": "تورّم", "inflammation": "التهاب", "bleeding": "نزيف", "dehydration": "جفاف",
        // أجهزة الجسم وصفات طبية
        "respiratory": "الجهاز التنفّسي", "cardiac": "قلبي", "cardiovascular": "القلب والأوعية",
        "renal": "كلوي", "hepatic": "كبدي", "neurological": "عصبي", "musculoskeletal": "العضلي الهيكلي",
        "diabetic": "سكري", "chronic": "مزمن", "acute": "حادّ", "benign": "حميد", "malignant": "خبيث",
        // تخصّصات إضافية وأقسام
        "neurosurgery": "جراحة الأعصاب", "general surgery": "الجراحة العامة", "plastic surgery": "الجراحة التجميلية",
        "physical therapy": "العلاج الطبيعي", "emergency": "الطوارئ", "icu": "العناية المركّزة",
        "traumatology": "طب الإصابات", "vascular": "الأوعية الدموية", "geriatrics": "طب الشيخوخة",
        "nutrition": "التغذية", "audiology": "السمعيات", "neonatology": "حديثي الولادة",
        // مصطلحات واجهة قد تَرِد كقيَم
        "medical record": "الملف الطبي", "record": "السجل",
        "summary": "الملخص", "keywords": "الكلمات المفتاحية", "keyword": "كلمة مفتاحية",
        "missing information": "معلومات ناقصة", "missing info": "معلومات ناقصة",
        "category": "التصنيف", "specialty": "التخصص", "speciality": "التخصص", "priority": "الأولوية",
        "status": "الحالة", "notes": "ملاحظات", "note": "ملاحظة", "diagnosis": "التشخيص", "treatment": "العلاج",
        "patient": "مريض", "doctor": "طبيب", "visit": "زيارة", "appointment": "موعد", "date": "التاريخ",
        "urgent": "عاجل", "critical": "حرِج", "normal": "عادي", "routine": "روتيني", "stable": "مستقرّ"
    };

    var AFO_UNK = "غير محدّد";

    function afoIsArabic(s) { return /[\u0600-\u06FF]/.test(s); }
    function afoHasLatin(s) { return /[A-Za-z]/.test(s); }
    function afoEscRe(x)   { return x.replace(/[.*+?^${}()|[\]\\\-]/g, "\\$&"); }

    // مفاتيح القاموس مرتّبة من الأطول للأقصر لمعالجة العبارات المركّبة أولاً
    var AFO_KEYS = Object.keys(AFO_AR).sort(function (a, b) { return b.length - a.length; });

    /* حقول تصنيفية ذرّية (تخصّص/تصنيف/أولوية/كلمة مفتاحية/مقطع مسار):
       معروف ← العربية، عربي ← كما هو، إنجليزي غير معروف ← «غير محدّد». */
    function trStrict(value) {
        var s = (value == null ? "" : String(value)).trim();
        if (!s) { return AFO_UNK; }
        var hit = AFO_AR[s.toLowerCase()];
        if (hit) { return hit; }
        if (afoIsArabic(s)) { return s; }
        if (afoHasLatin(s)) { return AFO_UNK; }
        return s; // أرقام/رموز فقط (مثل سنة الأرشفة)
    }

    /* نصوص حرّة (ملخّص/متابعة/معلومات ناقصة): استبدال المصطلحات المعروفة
       ككلمات كاملة دون إتلاف الجملة، مع الحفاظ على النص العربي كما هو. */
    function trText(value) {
        var s = (value == null ? "" : String(value));
        if (!s) { return ""; }
        for (var i = 0; i < AFO_KEYS.length; i++) {
            var k = AFO_KEYS[i];
            var re = new RegExp("(^|[^A-Za-z])(" + afoEscRe(k) + ")(?![A-Za-z])", "gi");
            s = s.replace(re, function (m, p1) { return p1 + AFO_AR[k]; });
        }
        return s;
    }

    /* نص حرّ نهائي للعرض: بعد ترجمة المصطلحات المعروفة، إن بقي أي حرف لاتيني
       (إنجليزي غير معروف) نعرض «غير محدّد» بدل إظهار أي كلمة إنجليزية. */
    function trFree(value) {
        var s = (value == null ? "" : String(value)).trim();
        if (!s) { return ""; }
        s = trText(s);
        return afoHasLatin(s) ? AFO_UNK : s;
    }

    function trPath(value) {
        var s = (value == null ? "" : String(value)).trim();
        if (!s) { return ""; }
        return s.split(/[\/\\>›]+/).map(function (seg) {
            seg = seg.trim();
            return seg ? trStrict(seg) : "";
        }).filter(Boolean).join(" / ");
    }

    function buildCard(f) {
        var prio = f.priority || "medium";
        var prioLabel = prio === "high" ? "أولوية عالية" : (prio === "low" ? "أولوية منخفضة" : "أولوية متوسطة");
        var prioIcon  = prio === "high" ? "fa-circle-exclamation"
                      : (prio === "low" ? "fa-circle-check" : "fa-circle-half-stroke");

        var kw = (f.keywords || []).map(trStrict).filter(function (v, i, a) {
            return v && a.indexOf(v) === i;
        }).map(function (k) {
            return '<span class="afo-kw">' + esc(k) + "</span>";
        }).join("");

        var missing = "";
        if (f.is_incomplete && f.missing_info && f.missing_info.length) {
            missing = '<div class="afo-missing"><i class="fas fa-circle-info afo-ico"></i>' +
                      '<span><strong>معلومات ناقصة:</strong> ' +
                      esc(trFree(f.missing_info.join("، "))) + "</span></div>";
        }

        var followup = "";
        if (f.followup_required && f.followup) {
            followup = '<div class="afo-followup"><i class="fas fa-bell afo-ico"></i>' +
                       '<span>' + esc(trFree(f.followup)) + "</span></div>";
        }

        var specText = f.specialty_ar ? trStrict(f.specialty_ar) : trStrict(f.specialty);

        var path = f.suggested_path
            ? '<div class="afo-path"><i class="fas fa-folder-open afo-ico"></i><span>' +
              esc(trPath(f.suggested_path)) + "</span></div>"
            : "";

        var rid = parseInt(f.medical_record_id, 10) || 0;

        return (
            '<article class="afo-card afo-card--' + esc(prio) + '">' +
                '<div class="afo-card-top">' +
                    '<div class="afo-patient">' +
                        '<span class="afo-avatar">' + esc(initial(f.patient_name)) + "</span>" +
                        '<div class="afo-patient-meta">' +
                            '<div class="afo-name">' + esc(f.patient_name || "مريض") + "</div>" +
                            '<div class="afo-spec"><i class="fas fa-user-doctor afo-ico"></i>' +
                                esc(specText) + "</div>" +
                        "</div>" +
                    "</div>" +
                    '<span class="afo-badge afo-badge--' + esc(prio) + '">' +
                        '<i class="fas ' + prioIcon + ' afo-ico"></i>' + prioLabel + "</span>" +
                "</div>" +
                (f.disease_category
                    ? '<div class="afo-cat"><i class="fas fa-stethoscope afo-ico"></i>' + esc(trStrict(f.disease_category)) + "</div>"
                    : "") +
                '<p class="afo-summary"><i class="fas fa-file-lines afo-ico"></i><span>' + esc(trFree(f.summary || "—")) + "</span></p>" +
                (kw ? '<div class="afo-kws"><i class="fas fa-tags afo-ico afo-kws-ico"></i>' + kw + "</div>" : "") +
                path + followup + missing +
                '<div class="afo-card-actions">' +
                    '<button type="button" class="afo-view" data-rid="' + rid + '">عرض الملف الطبي</button>' +
                "</div>" +
            "</article>"
        );
    }

    // تفويض النقر لزر «عرض الملف الطبي» → نفس آلية الداشبورد.
    document.addEventListener("click", function (e) {
        var btn = e.target.closest ? e.target.closest(".afo-view") : null;
        if (!btn) { return; }
        var id = parseInt(btn.getAttribute("data-rid"), 10) || 0;
        if (!id) { return; }
        if (typeof window.openArchiveRecord === "function") {
            window.openArchiveRecord(id); // يفتح نفس الـ Modal المستخدم في أرشيف المرضى
        } else {
            window.open("view_record.php?id=" + id, "_blank"); // احتياطي للوضع المستقل
        }
    });

    function updateButtonHint(s) {
        if (!s || !els.btn) { return; }
        var label = els.btn.querySelector(".afo-btn-label");
        if (!label) { return; }
        label.textContent = s.pending > 0
            ? ("تنظيم الملفات تلقائياً (" + s.pending + " بانتظار التحليل)")
            : "إعادة الفحص / تنظيم الملفات";
    }

    /* ── أدوات الواجهة ── */
    function setButtonBusy(busy) {
        if (!els.btn) { return; }
        els.btn.disabled = busy;
        els.btn.classList.toggle("is-busy", busy);
    }
    function setProgress(pct, text, count) {
        els.pFill.style.width = pct + "%";
        els.pText.textContent = text;
        els.pCount.textContent = count || "";
    }
    function showNotice(msg, type) {
        els.notice.textContent = msg;
        els.notice.className = "afo-notice afo-notice--" + (type || "info");
        els.notice.hidden = false;
    }
    function hideNotice() { els.notice.hidden = true; }

    function animateNumber(el, target) {
        if (!el) { return; }
        var start = parseInt(el.textContent, 10) || 0;
        if (start === target) { el.textContent = String(target); return; }
        var steps = 16, i = 0, inc = (target - start) / steps;
        var timer = setInterval(function () {
            i++;
            el.textContent = String(Math.round(start + inc * i));
            if (i >= steps) { el.textContent = String(target); clearInterval(timer); }
        }, 22);
    }

    /* ── طلب POST بنمط الداشبورد ── */
    function post(params) {
        var body = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + "=" + encodeURIComponent(params[k]);
        }).join("&");
        return fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body
        }).then(function (r) { return r.json(); });
    }

    function ready(fn) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", fn);
        } else { fn(); }
    }
    function esc(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;")
            .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
    function initial(name) {
        name = (name || "").trim();
        return name ? name.charAt(0) : "؟";
    }
})();
