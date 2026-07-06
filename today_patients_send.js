/* ════════════════════════════════════════════════════════════════
   MedChifaGiz — توحيد إرسال "مرضى اليوم" مع "إضافة مريض"
   ملف إضافي مستقل. يُحمَّل بعد patient_inline.js مباشرة:
       <script src="patient_inline.js"></script>
       <script src="today_patients_send.js"></script>   ← موجود مسبقاً

   • يتجاوز (override) دوال الإرسال الأربع في patient_inline.js فترسل
     patient_id = medical_records.id (نفس "إضافة مريض" بالضبط) — فتظهر
     بيانات المريض الحقيقية في لوحات المخبر/الأشعة/الصيدلية/الممرض بدل #ID.
   • نفس منطق طابور "إضافة مريض": إذا لم يُحفظ الملف بعد، يُسجَّل الطلب
     كـ Pending (sessionStorage) وتظهر رسالة نجاح، ثم تُرسَل كل الطلبات
     تلقائياً عند الضغط على "حفظ الملف" (window.tpsFlushSendQueue).

   • medical_records.id = data-medical-record-id للبطاقة المفتوحة.
   • patients.id        = data-patient-id (لاحقة حقول mirror + وسم الطابور).
   ════════════════════════════════════════════════════════════════ */
(function todayPatientsSendUnify() {
    'use strict';

    if (window.__todayPatientsSendUnify) return;
    window.__todayPatientsSendUnify = true;

    var PHARMACY_URL  = window.PHARMACY_API_URL       || 'pharmacy_api.php';
    var NURSE_URL     = window.NURSE_API_URL          || 'nurse_treatment_api.php';
    var LAB_RADIO_URL = window.LAB_RADIOLOGY_API_URL  || 'lab_radiology_api.php';
    var FICHE_URL     = window.FICHE_SAVE_URL         || 'fiche_traitement_api.php';

    var QKEY = 'tps_send_pending';   // طابور الطلبات المعلّقة (نفس فكرة apf_send_pending)

    function toast(msg, type) {
        if (typeof window.pifShowToast === 'function') return window.pifShowToast(msg, type);
        if (typeof window.showAddPatientToast === 'function') return window.showAddPatientToast(msg, type);
        alert(msg);
    }

    function gv(id) {
        var el = document.getElementById(id);
        return el ? (el.value || '').toString().trim() : '';
    }

    function aileFromGender(g) {
        if (g === 'ذكر') return 'men';
        if (g === 'أنثى') return 'women';
        return '';
    }

    function activeItem() {
        return document.querySelector('#todayPatients .patient-item.expanded');
    }

    /* patients.id — لاحقة حقول البطاقة (mirror_*_<pid>) */
    function readPid() {
        var item = activeItem();
        if (item) {
            var v = item.getAttribute('data-patient-id');
            if (v) return parseInt(v, 10) || 0;
        }
        var pidEl = document.getElementById('medical_patient_id');
        return pidEl ? (parseInt(pidEl.value, 10) || 0) : 0;
    }

    /* medical_records.id — هو patient_id المُرسَل للـ APIs (نفس إضافة مريض) */
    function recordId() {
        var item = activeItem();
        if (!item) return 0;
        var v = item.getAttribute('data-medical-record-id');
        return v ? (parseInt(v, 10) || 0) : 0;
    }

    function patientCommon(pid) {
        var gender = gv('mirror_gender_' + pid);
        var item = activeItem();
        var fallbackName = item ? (item.getAttribute('data-patient-name') || '') : '';
        return {
            patient_name: gv('mirror_full_name_' + pid) || gv('mirror_rx_patient_name_' + pid) || fallbackName,
            birth_info:   gv('mirror_birth_info_' + pid),
            gender:       gender,
            room:         gv('mirror_room_number_' + pid),
            service:      window.HOSPITAL_SERVICE || '',
            aile:         aileFromGender(gender),
            doctor_name:  window.DOCTOR_NAME || '',
            diagnostic:   gv('mirror_fiche_diagnostic_' + pid)
        };
    }

    /* ── الطابور: تخزين/قراءة ── */
    function readQueue() {
        try { return JSON.parse(sessionStorage.getItem(QKEY) || '[]'); }
        catch (e) { return []; }
    }
    function writeQueue(q) {
        try { sessionStorage.setItem(QKEY, JSON.stringify(q)); } catch (e) {}
    }
    function enqueue(url, fields, pid) {
        var q = readQueue();
        q.push({ url: url, fields: fields, pid: pid });
        writeQueue(q);
    }

    /* إرسال فعلي (مع patient_id = medical_records.id) */
    function doSend(url, fields, recId, okMsg) {
        var fd = new FormData();
        Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
        fd.append('patient_id', recId);
        if (okMsg !== false) toast('📤 جاري الإرسال...', 'info');
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (okMsg === false) return;
                if (res && res.success) toast('✅ ' + (res.message || okMsg), 'success');
                else toast('❌ ' + (res && res.message ? res.message : 'فشل الإرسال'), 'error');
            })
            .catch(function () { if (okMsg !== false) toast('❌ تعذّر الاتصال بالخادم', 'error'); });
    }

    /* إرسال فوري دائماً:
       - إن كان data-medical-record-id موجوداً → يُرسَل به (أدق).
       - وإلا → يُرسَل بـ pid (patients.id = currentPatientId) مباشرةً
         وهو نفس ما كانت تفعله patient_inline.js الأصلية قبل التعديل. */
    function sendOrQueue(url, fields, pid, okMsg) {
        var recId = recordId();
        var effectiveId = recId > 0 ? recId : pid;
        if (effectiveId > 0) {
            doSend(url, fields, effectiveId, okMsg);
        } else {
            toast('⚠️ لا يوجد مريض مفتوح', 'warn');
        }
    }

    /* تفريغ الطابور بالـ medical_records.id الحقيقي بعد حفظ الملف */
    window.tpsFlushSendQueue = function (recId, pid) {
        recId = parseInt(recId, 10) || 0;
        pid   = parseInt(pid, 10) || 0;
        if (recId <= 0) return;
        var q = readQueue();
        if (!q.length) return;
        var remaining = [];
        q.forEach(function (item) {
            // أرسل فقط طلبات هذا المريض (نفس patients.id)؛ أبقِ الباقي
            if (!pid || item.pid === pid) {
                doSend(item.url, item.fields, recId, false);
            } else {
                remaining.push(item);
            }
        });
        writeQueue(remaining);
    };

    /* ════════ إرسال للمخبر ════════ */
    window.sendToLab = function () {
        var pid = readPid();
        if (pid <= 0) { toast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }
        var text = gv('mirror_medical_tests_' + pid);
        if (!text) { toast('⚠️ اكتب التحاليل المطلوبة أولاً', 'warn'); return; }
        sendOrQueue(LAB_RADIO_URL, {
            action:        'send_lab_request',
            analysis_text: text
        }, pid, 'تم إرسال طلب التحاليل إلى المخبر');
    };

    /* ════════ إرسال للأشعة ════════ */
    window.sendToRadiology = function () {
        var pid = readPid();
        if (pid <= 0) { toast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }
        var text = gv('mirror_radiology_' + pid);
        if (!text) { toast('⚠️ اكتب فحوصات الأشعة المطلوبة أولاً', 'warn'); return; }
        sendOrQueue(LAB_RADIO_URL, {
            action:         'send_radiology_request',
            radiology_text: text
        }, pid, 'تم إرسال طلب الأشعة');
    };

    /* ════════ إرسال الوصفة للصيدلية ════════ */
    window.sendPrescriptionToPharmacy = function () {
        var pid = readPid();
        if (pid <= 0) { toast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }
        var medicines = gv('mirror_prescription_' + pid);
        if (!medicines) { toast('⚠️ اكتب الأدوية أولاً قبل الإرسال', 'warn'); return; }
        var c = patientCommon(pid);
        sendOrQueue(PHARMACY_URL, {
            action:       'send_prescription',
            medicines:    medicines,
            patient_name: c.patient_name,
            birth_info:   c.birth_info,
            gender:       c.gender,
            room:         c.room,
            service:      c.service,
            aile:         c.aile,
            doctor_name:  c.doctor_name,
            diagnostic:   c.diagnostic,
            rx_date:      gv('mirror_rx_date_' + pid),
            notes:        gv('mirror_doctor_notes_' + pid)
        }, pid, 'تم إرسال الوصفة إلى الصيدلية');
    };

    /* ════════ إرسال fiche للممرض ════════ */
    window.sendFicheToNurse = function () {
        var pid = readPid();
        if (pid <= 0) { toast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }
        var medications = gv('mirror_fiche_medications_' + pid);
        if (!medications) { toast('⚠️ اكتب العلاجات أولاً قبل الإرسال', 'warn'); return; }
        var c = patientCommon(pid);
        sendOrQueue(NURSE_URL, {
            action:         'send_treatment',
            treatments:     medications,
            patient_name:   c.patient_name,
            birth_info:     c.birth_info,
            gender:         c.gender,
            room:           c.room,
            service:        c.service,
            aile:           c.aile,
            doctor_name:    c.doctor_name,
            motif:          gv('mirror_reason_exam_' + pid),
            diagnostic:     c.diagnostic,
            admission_date: gv('mirror_admission_date_' + pid)
        }, pid, 'تم إرسال fiche العلاج إلى الممرض');
    };

    /* ════════ حفظ بطاقة العلاج — يحتاج سجلاً محفوظاً (خارج طابور الإرسال) ════════ */
    window.saveFicheTraitement = function () {
        var pid = readPid();
        if (pid <= 0) { toast('⚠️ لا يوجد مريض مفتوح', 'warn'); return; }
        var rid = recordId();
        if (rid <= 0) { toast('⚠️ احفظ ملف المريض أولاً', 'warn'); return; }
        var diag = gv('mirror_fiche_diagnostic_' + pid);
        var meds = gv('mirror_fiche_medications_' + pid);
        if (!diag && !meds) { toast('⚠️ اكتب التشخيص أو العلاجات أولاً', 'warn'); return; }
        doSend(FICHE_URL, {
            action:            'save_fiche',
            fiche_diagnostic:  diag,
            fiche_medications: meds,
            medical_record_id: rid
        }, rid, 'تم حفظ بطاقة العلاج بنجاح');
    };
})();
