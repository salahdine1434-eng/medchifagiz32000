/* ================================================================
   MedChifaGiz — weekly_calendar.js
   Weekly Horizontal Calendar for "يومياتي"
   Front-End Only | Mock Data | RTL | Arabic
   Ready for DB integration: replace djWcGetData() with AJAX call
   ================================================================ */

(function () {
  'use strict';

  /* ──────────────────────────────────────────────────────────────
     MOCK DATA
     Later: replace with AJAX/fetch from DB endpoint.
     Key format: "YYYY-M-D" (matching existing djDateKey format)
  ────────────────────────────────────────────────────────────── */
  var journalData = {
    '2026-5-29': {
      mood: 'جيد 🙂',
      moodScore: 4,
      note: 'اليوم شعرت بتحسن ملحوظ، ذهبت للمشي وشربت الماء الكافي.',
      vitals: { bp: '120/80', hr: '72', temp: '36.8' },
      symptoms: [],
      medication: 'yes',
      sleep: 7,
      water: 6
    },
    '2026-5-28': {
      mood: 'متعب 😔',
      moodScore: 2,
      note: 'صداع خفيف وتعب طوال اليوم. نمت قليلاً بعد الظهر.',
      vitals: { bp: '130/85', hr: '80', temp: '37.2' },
      symptoms: ['headache', 'severe_fatigue'],
      medication: 'late',
      sleep: 5,
      water: 4
    },
    '2026-5-27': {
      mood: 'ممتاز 😁',
      moodScore: 5,
      note: 'نشاط جيد جداً، تمارين صباحية وتغذية صحية.',
      vitals: { bp: '118/76', hr: '68', temp: '36.6' },
      symptoms: [],
      medication: 'yes',
      sleep: 8,
      water: 8
    },
    '2026-5-26': {
      mood: 'عادي 😐',
      moodScore: 3,
      note: 'يوم عادي بدون أي أعراض مميزة.',
      vitals: { bp: '122/79', hr: '74', temp: '36.9' },
      symptoms: [],
      medication: 'yes',
      sleep: 7,
      water: 5
    }
  };

  /* ──────────────────────────────────────────────────────────────
     DATE HELPERS  (matching djDateKey in daily_journal.js)
  ────────────────────────────────────────────────────────────── */
  function wcDateKey(d) {
    return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
  }

  function wcFormatDisplay(d) {
    // Returns "29/05" style
    var dd = String(d.getDate()).padStart(2, '0');
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    return dd + '/' + mm;
  }

  /* ──────────────────────────────────────────────────────────────
     DATA ACCESS — swap this function's body for real AJAX later
     API contract: returns entry object or null
  ────────────────────────────────────────────────────────────── */
  function djWcGetData(dateKey, callback) {
    // ── MOCK (front-end only) ──
    var entry = journalData[dateKey] || null;
    callback(entry);

    /*
    ── FUTURE DB INTEGRATION (uncomment & remove mock above) ──
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_journal_day.php?date=' + dateKey, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        try { callback(JSON.parse(xhr.responseText)); }
        catch (e) { callback(null); }
      }
    };
    xhr.send();
    */
  }

  /* ──────────────────────────────────────────────────────────────
     MOOD COLOR HELPER
  ────────────────────────────────────────────────────────────── */
  function wcMoodColor(score) {
    var map = {
      5: '#06d6a0', 4: '#00b4d8', 3: '#f59e0b', 2: '#f97316', 1: '#ef4444'
    };
    return map[score] || '#9ca3af';
  }

  /* ──────────────────────────────────────────────────────────────
     SYMPTOM LABELS
  ────────────────────────────────────────────────────────────── */
  var symLabels = {
    headache: 'صداع', dizziness: 'دوخة', chest_pain: 'ألم صدر',
    breathless: 'ضيق تنفس', severe_fatigue: 'تعب شديد',
    nausea: 'غثيان', cough: 'سعال', fever: 'حمى',
    swelling: 'تورم الأرجل', other: 'أخرى'
  };

  /* ──────────────────────────────────────────────────────────────
     RENDER WEEKLY CALENDAR
  ────────────────────────────────────────────────────────────── */
  var WC_SELECTED_KEY = null;

  function wcRender() {
    var container = document.getElementById('DJ_WEEKLY_CAL');
    if (!container) return;

    var today = new Date();
    var dayNamesShort = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    // Build 7 days (today + 6 previous)
    var days = [];    for (var i = 6; i >= 0; i--) {
      var d = new Date(today);
      d.setDate(today.getDate() - i);
      days.push({
        date: new Date(d),
        key: wcDateKey(d),
        dayName: dayNamesShort[d.getDay()],
        display: wcFormatDisplay(d),
        isToday: i === 0
      });
    }

    // Build HTML
    var html = '<div class="DJ-WC-HEADER"><i class="fas fa-calendar-week" style="color:#00b4d8;margin-left:6px;"></i> الأسبوع الحالي</div>';
    html += '<div class="DJ-WC-DAYS">';

    days.forEach(function (day) {
      var hasData = !!journalData[day.key];
    var isActive = WC_SELECTED_KEY !== null && day.key === WC_SELECTED_KEY;
      var cls = 'DJ-WC-DAY' + (isActive ? ' DJ-WC-ACTIVE' : '') + (day.isToday ? ' DJ-WC-TODAY' : '');
      var dotHtml = hasData
        ? '<span class="DJ-WC-DOT" style="background:' + wcMoodColor((journalData[day.key] || {}).moodScore) + ';"></span>'
        : '<span class="DJ-WC-DOT DJ-WC-DOT-EMPTY"></span>';

      html += '<div class="' + cls + '" data-key="' + day.key + '" onclick="djWcSelect(\'' + day.key + '\')">';
      html += '<div class="DJ-WC-DAYNAME">' + day.dayName + '</div>';
      html += '<div class="DJ-WC-DATE">' + day.display + '</div>';
      html += dotHtml;
      html += '</div>';
    });

    html += '</div>';
    container.innerHTML = html;
  }

  /* ──────────────────────────────────────────────────────────────
     SELECT A DAY — called on click
  ────────────────────────────────────────────────────────────── */
  window.djWcSelect = function (dateKey) {
    WC_SELECTED_KEY = dateKey;

    // Update active state without full re-render (performance)
    document.querySelectorAll('.DJ-WC-DAY').forEach(function (el) {
      el.classList.toggle('DJ-WC-ACTIVE', el.getAttribute('data-key') === dateKey);
    });

    // Show data panel
    djWcGetData(dateKey, function (entry) {
      wcRenderDayPanel(dateKey, entry);
    });
  };

  /* ──────────────────────────────────────────────────────────────
     RENDER DAY DATA PANEL
  ────────────────────────────────────────────────────────────── */
  function wcRenderDayPanel(dateKey, entry) {
    var panel = document.getElementById('DJ_DAY_DATA_PANEL');
    var titleEl = document.getElementById('DJ_DAY_DATA_TITLE');
    var contentEl = document.getElementById('DJ_DAY_DATA_CONTENT');
    if (!panel || !contentEl) return;

    // Format date for display
    var parts = dateKey.split('-');
    var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    var dayNamesShort = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    var dateLabel = dayNamesShort[d.getDay()] + ' — ' + d.toLocaleDateString('ar-DZ');

    if (titleEl) {
      titleEl.innerHTML = '<i class="fas fa-calendar-check"></i> ' + dateLabel;
    }

    panel.style.display = 'block';

    // No data for this day
    if (!entry) {
      contentEl.innerHTML =
        '<div class="DJ-WC-EMPTY">' +
        '<i class="fas fa-calendar-times" style="font-size:28px;color:#9ca3af;margin-bottom:8px;display:block;"></i>' +
        '<div style="font-size:13px;font-weight:700;color:#9ca3af;">لا توجد تسجيلات لهذا اليوم</div>' +
        '<div style="font-size:11px;color:#c4c4c4;margin-top:4px;">سجّل حالتك من خلال النموذج أدناه</div>' +
        '</div>';
      return;
    }

    // Build data rows
    var rows = '';

    // Mood
    if (entry.mood) {
      rows += wcDataRow('fas fa-smile', 'المزاج', entry.mood, wcMoodColor(entry.moodScore));
    }

    // Note
    if (entry.note) {
      rows += '<div class="DJ-WC-NOTE"><i class="fas fa-sticky-note" style="color:#00b4d8;margin-left:6px;"></i>' + entry.note + '</div>';
    }

    // Vitals
    if (entry.vitals) {
      var v = entry.vitals;
      var vitHtml = '';
      if (v.bp)   vitHtml += '<span class="DJ-WC-VIT"><i class="fas fa-tachometer-alt"></i> ' + v.bp + ' <small>mmHg</small></span>';
      if (v.hr)   vitHtml += '<span class="DJ-WC-VIT"><i class="fas fa-heart"></i> ' + v.hr + ' <small>bpm</small></span>';
      if (v.temp) vitHtml += '<span class="DJ-WC-VIT"><i class="fas fa-thermometer-half"></i> ' + v.temp + '° <small>C</small></span>';
      if (vitHtml) rows += '<div class="DJ-WC-VITALS-ROW">' + vitHtml + '</div>';
    }

    // Symptoms
    if (entry.symptoms && entry.symptoms.length) {
      var symList = entry.symptoms.map(function (s) { return symLabels[s] || s; }).join('، ');
      rows += wcDataRow('fas fa-stethoscope', 'الأعراض', symList, '#ef4444');
    }

    // Medication
    if (entry.medication) {
      var medMap = { yes: '✅ تناولته', late: '⏰ متأخر', no: '❌ لم يتناوله' };
      var medColor = entry.medication === 'yes' ? '#06d6a0' : entry.medication === 'late' ? '#f59e0b' : '#ef4444';
      rows += wcDataRow('fas fa-pills', 'الدواء', medMap[entry.medication] || '-', medColor);
    }

    // Sleep
    if (entry.sleep !== undefined) {
      rows += wcDataRow('fas fa-moon', 'النوم', entry.sleep + ' ساعات', '#0077b6');
    }

    // Water
    if (entry.water !== undefined) {
      rows += wcDataRow('fas fa-tint', 'الماء', entry.water + ' أكواب', '#00b4d8');
    }

    contentEl.innerHTML = rows;
    // Smooth scroll to panel
    setTimeout(function () {
      panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 80);
  }

  function wcDataRow(icon, label, value, color) {
    return '<div class="DJ-WC-ROW">' +
      '<span class="DJ-WC-ROW-LBL"><i class="' + icon + '" style="color:' + (color || '#00b4d8') + ';"></i> ' + label + '</span>' +
      '<span class="DJ-WC-ROW-VAL" style="color:' + (color || '#1a2340') + ';">' + value + '</span>' +
      '</div>';
  }

  /* ──────────────────────────────────────────────────────────────
     INIT — called after VDL becomes visible
  ────────────────────────────────────────────────────────────── */
  function wcInit() {
    // Reset selection so no day appears pre-selected
    WC_SELECTED_KEY = null;
    wcRender();
    // Panel stays hidden until user taps a day
  }

  /* ──────────────────────────────────────────────────────────────
     HOOK INTO EXISTING VIEW SWITCHER
  ────────────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    // Patch into sidebar nav clicks (ptNavTo)
    document.querySelectorAll('[data-v="VDL"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTimeout(wcInit, 80);
      });
    });

    // Patch into bottom NV nav (existing NV buttons)
    document.querySelectorAll('.NV').forEach(function (btn) {
      if (btn.getAttribute('data-v') === 'VDL') {
        btn.addEventListener('click', function () {
          setTimeout(wcInit, 80);
        });
      }
    });

    // If VDL is already active on page load
    var vdl = document.getElementById('VDL');
    if (vdl && vdl.classList.contains('A')) {
      setTimeout(wcInit, 100);
    }
  });

})();
