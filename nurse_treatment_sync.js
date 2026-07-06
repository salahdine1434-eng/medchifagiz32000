/* ════════════════════════════════════════════════════════════════
   MedChifaGiz — مزامنة علاجات الممرض الحقيقية (الطبيب → الممرض)
   ملف إضافي مستقل. يُحمَّل بعد nurse_dashboard.js مباشرة:
       <script src="nurse_dashboard.js"></script>
       <script src="nurse_treatment_sync.js"></script>   ← أضف هذا السطر فقط

   إضافة فقط: لا يحذف أي سطر، لا يغيّر HTML أو CSS.
   يتجاوز (override) دوالّ عامة موجودة مع استدعاء الأصل بداخلها:
       renderWard()  → يضيف بطاقات مرضى الطبيب الحقيقيين في الجناح
       switchSection()→ يعيد الجلب عند دخول جناح الرجال/النساء (مزامنة)
       openPatientFile() → يحدّث علاجات المريض من القاعدة قبل العرض
       pfToggleTreatment() → عند التأكيد يضبط الحالة completed في القاعدة
   لا يستعمل LocalStorage للبيانات الحقيقية. لا بيانات Demo.
   ════════════════════════════════════════════════════════════════ */
(function nurseRealSync() {
  'use strict';

  var NURSE_API_URL = window.NURSE_API_URL || 'nurse_treatment_api.php';
  var REAL_ID_BASE  = 900000;   // معرّفات المرضى الحقيقيين = REAL_ID_BASE + db.id (لا تتعارض مع الـ demo)

  var PALETTE = ['#3b82f6', '#ec4899', '#8b5cf6', '#f59e0b', '#10b981', '#0ea5e9', '#ef4444', '#f97316'];

  function colorFor(id) { return PALETTE[(parseInt(id, 10) || 0) % PALETTE.length]; }

  function joinParts() {
    var out = [];
    for (var i = 0; i < arguments.length; i++) {
      var v = (arguments[i] || '').toString().trim();
      if (v) out.push(v);
    }
    return out.join(' · ');
  }

  /* تحويل صف القاعدة إلى كائن مريض بنفس شكل PATIENTS */
  function rowToPatient(row) {
    var ward = (row.aile === 'women') ? 'women' : 'men';
    var treatments = Array.isArray(row.treatments) ? row.treatments.map(function (t) {
      return {
        name:  t.name || t.medicament || '—',
        dose:  joinParts(t.dose, t.freq, t.duree),
        time:  t.heure || t.time || '',
        type:  t.type || 'pill',
        notes: t.instructions || t.notes || '',
        done:  (row.status === 'completed')
      };
    }) : [];

    return {
      id:        REAL_ID_BASE + parseInt(row.id, 10),
      _real:     true,
      _dbid:     parseInt(row.id, 10),
      _patientId: parseInt(row.patient_id, 10),
      _dbStatus: row.status || 'pending',
      name:      row.patient_name || ('مريض #' + row.patient_id),
      initials:  (row.patient_name || '?').trim().charAt(0),
      color:     colorFor(row.id),
      dob:       row.birth_info || '—',
      room:      row.room || '—',
      bed:       '—',
      admitted:  row.admission_date || '—',
      doctor:    row.doctor_name || '—',
      dept:      row.service || (window.nurseDept || ''),
      ward:      ward,
      reason:    row.motif || row.diagnostic || '—',
      status:    (row.status === 'completed') ? 'stable' : 'new',
      treatments: treatments
    };
  }

  /* دمج المرضى الحقيقيين داخل مصفوفة PATIENTS العامة (إزالة القديم أولاً) */
  function syncIntoPatients(rows) {
    if (typeof PATIENTS === 'undefined') return;
    for (var i = PATIENTS.length - 1; i >= 0; i--) {
      if (PATIENTS[i] && PATIENTS[i]._real) PATIENTS.splice(i, 1);
    }
    rows.forEach(function (row) { PATIENTS.push(rowToPatient(row)); });
  }

  var _lastFetch = [];
  function fetchReal() {
    return fetch(NURSE_API_URL + '?action=list', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success && Array.isArray(res.data)) {
          _lastFetch = res.data;
          syncIntoPatients(res.data);
        }
        return _lastFetch;
      })
      .catch(function (e) { console.warn('[NURSE-SYNC] fetch failed:', e); return _lastFetch; });
  }
  window.refreshNurseRealTreatments = fetchReal;

  /* ════════ override: renderWard — يضيف بطاقات المرضى الحقيقيين ════════ */
  var _origRenderWard = window.renderWard;
  if (typeof _origRenderWard === 'function') {
    window.renderWard = function (gender) {
      _origRenderWard.call(this, gender);   // الأصل أولاً (لا يُكسر شيء)

      var elId = gender === 'men' ? 'wardMenGrid' : 'wardWomenGrid';
      var el = document.getElementById(elId);
      if (!el || typeof PATIENTS === 'undefined') return;

      PATIENTS.filter(function (p) { return p._real && p.ward === gender; })
        .forEach(function (p) {
          var card = document.createElement('div');
          card.className = 'ward-room occupied clickable-room';
          card.setAttribute('onclick', "openPatientFile(" + p.id + ", '" + gender + "')");
          card.innerHTML =
            '<div class="wr-number">غرفة ' + p.room + '</div>' +
            '<div class="wr-bed-icon">🛏️</div>' +
            '<div class="wr-name">' + p.name + '</div>' +
            '<div class="wr-view-btn"><i class="fas fa-folder-open"></i> عرض الملف</div>';
          el.appendChild(card);
        });
    };
  }

  /* ════════ override: switchSection — مزامنة عند دخول الجناح ════════ */
  var _origSwitchSection = window.switchSection;
  if (typeof _origSwitchSection === 'function') {
    window.switchSection = function (name, elArg) {
      _origSwitchSection.call(this, name, elArg);
      if (name === 'ward-men' || name === 'ward-women') {
        var gender = (name === 'ward-men') ? 'men' : 'women';
        fetchReal().then(function () {
          if (typeof window.renderWard === 'function') window.renderWard(gender);
        });
      }
    };
  }

  /* ════════ override: openPatientFile — تحديث العلاجات من القاعدة قبل العرض ════════ */
  var _origOpenPatientFile = window.openPatientFile;
  if (typeof _origOpenPatientFile === 'function') {
    window.openPatientFile = function (id, fromSection) {
      if (id >= REAL_ID_BASE) {
        // مريض حقيقي → اجلب أحدث علاجاته (تعديل/حذف الطبيب ينعكس تلقائياً)
        fetchReal().then(function () {
          _origOpenPatientFile.call(window, id, fromSection);
        });
      } else {
        _origOpenPatientFile.call(window, id, fromSection);
      }
    };
  }

  /* ════════ override: pfToggleTreatment — تأكيد التنفيذ يضبط completed بالقاعدة ════════ */
  var _origPfToggle = window.pfToggleTreatment;
  if (typeof _origPfToggle === 'function') {
    window.pfToggleTreatment = function (idx) {
      _origPfToggle.call(this, idx);   // السلوك/الشكل الأصلي يبقى كما هو

      try {
        var id = window.currentPatientFileId;
        if (id && id >= REAL_ID_BASE && typeof PATIENTS !== 'undefined') {
          var p = PATIENTS.find(function (x) { return x.id === id; });
          if (p && p._patientId) {
            var fd = new FormData();
            fd.append('action', 'confirm');
            fd.append('patient_id', p._patientId);
            fetch(NURSE_API_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
              .catch(function (e) { console.warn('[NURSE-SYNC] confirm failed:', e); });
          }
        }
      } catch (e) { console.warn('[NURSE-SYNC] confirm error:', e); }
    };
  }

  /* ════════ تحميل أولي عند فتح اللوحة ════════ */
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
      fetchReal().then(function () {
        // إن كنا داخل جناح حالياً، أعد الرسم
        var menActive   = document.getElementById('section-ward-men');
        var womenActive = document.getElementById('section-ward-women');
        if (menActive && menActive.classList.contains('active') && window.renderWard) window.renderWard('men');
        if (womenActive && womenActive.classList.contains('active') && window.renderWard) window.renderWard('women');
      });
    }, 200);
  });
})();
