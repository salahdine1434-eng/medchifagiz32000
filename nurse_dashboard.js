/* ================================================================
   nurse_dashboard.js — MedChifaGiz Nurse Portal
   All interactions, dummy data, real-time clock
================================================================ */

'use strict';

/* ════════════════════════════════════════════════════════════
   NURSE DEPARTMENT STATE
════════════════════════════════════════════════════════════ */
let nurseDept = localStorage.getItem('nurseDept') || 'مصلحة الطب الداخلي';

function getNurseDept() { return nurseDept; }

function editNurseDept() {
  const editRow = document.getElementById('nurseDeptEditRow');
  const display = document.getElementById('nurseDeptDisplay');
  const input   = document.getElementById('nurseDeptInput');
  if (!editRow) return;
  editRow.style.display = 'flex';
  display.closest('.profile-dept-row').style.display = 'none';
  input.value = nurseDept;
  input.focus();
}

function saveNurseDept() {
  const input = document.getElementById('nurseDeptInput');
  const val   = (input?.value || '').trim();
  if (!val) { showToast('الرجاء إدخال اسم المصلحة'); return; }
  nurseDept = val;
  localStorage.setItem('nurseDept', nurseDept);
  document.getElementById('nurseDeptDisplay').textContent = nurseDept;
  cancelNurseDept();
  // Update all patients dept
  PATIENTS.forEach(p => p.dept = nurseDept);
  TREATMENTS_ALL.forEach(t => { /* dept not on treatments */ });
  showToast('✓ تم حفظ المصلحة');
}

function cancelNurseDept() {
  const editRow = document.getElementById('nurseDeptEditRow');
  const deptRow = document.querySelector('.profile-dept-row');
  if (editRow) editRow.style.display = 'none';
  if (deptRow) deptRow.style.display = 'flex';
}

function applyNurseDept() {
  // Sync all patients to nurse dept
  PATIENTS.forEach(p => p.dept = nurseDept);
}

/* ════════════════════════════════════════════════════════════
   DUMMY DATA
════════════════════════════════════════════════════════════ */
const PATIENTS = [
  {
    id: 1, name: 'خالد رشيد', initials: 'خ', color: '#3b82f6',
    dob: '12 مارس 1968', room: '03', bed: '01-A',
    admitted: '08 مايو 2025', doctor: 'د. أحمد بن علي',
    dept: 'الطب الداخلي', ward: 'men',
    reason: 'التهاب رئوي حاد — متابعة بعد دخول طارئ',
    status: 'watch',
    treatments: [
     
    ]
  },
  {
    id: 2, name: 'مريم حسين', initials: 'م', color: '#ec4899',
    dob: '22 يونيو 1975', room: '07', bed: '02-B',
    admitted: '10 مايو 2025', doctor: 'د. سارة عمار',
    dept: 'القلبية والأوعية', ward: 'women',
    reason: 'ارتفاع ضغط الدم — مراقبة مكثفة',
    status: 'watch',
    treatments: [
     
    ]
  },
  {
    id: 3, name: 'سارة بلقاسم', initials: 'س', color: '#8b5cf6',
    dob: '05 سبتمبر 1990', room: '08', bed: '01-A',
    admitted: '11 مايو 2025', doctor: 'د. يوسف قادري',
    dept: 'الغدد الصماء',   ward: 'women',
    reason: 'السكري من النوع الأول — ضبط الجرعة',
    status: 'stable',
    treatments: [
      { name: 'إنسولين نوفولوغ', dose: '10 وحدات SC قبل الأكل', time: '10:00', type: 'inject', notes: 'راقبي السكر بعده', done: false },
      { name: 'قياس السكر',       dose: 'كل 4 ساعات',              time: '06:00', type: 'measure', notes: 'هدف: 80–140', done: true },
    ]
  },
  {
    id: 4, name: 'عمر طيب', initials: 'ع', color: '#f59e0b',
    dob: '18 نوفمبر 1955', room: '12', bed: '02-A',
    admitted: '06 مايو 2025', doctor: 'د. كريم لعرابي',
    dept: 'الجراحة العامة',  ward: 'men',
    reason: 'ما بعد عملية استئصال زائدة',
    status: 'stable',
    treatments: [
     
    ]
  },
  {
    id: 5, name: 'حسن بوزيد', initials: 'ح', color: '#ef4444',
    dob: '30 يناير 1942', room: '14', bed: '01-A',
    admitted: '07 مايو 2025', doctor: 'د. نادية بوخالفة',
    dept: 'العناية المركزة', ward: 'men',
    reason: 'قصور القلب الاحتقاني — حالة حرجة',
    status: 'critical',
    treatments: [
     
    ]
  },
  {
    id: 6, name: 'ليلى معمر', initials: 'ل', color: '#10b981',
    dob: '14 أبريل 1988', room: '01', bed: '02-B',
    admitted: '12 مايو 2025', doctor: 'د. سارة عمار',
    dept: 'التوليد والنساء', ward: 'women',
    reason: 'متابعة ما بعد الولادة — دخول اليوم',
    status: 'new',
    treatments: [
      { name: 'مراجعة الملف', dose: 'مرة اليوم', time: '15:00', type: 'measure', notes: 'دخول جديد', done: false },
    ]
  },
  {
    id: 7, name: 'يوسف جاب الله', initials: 'ي', color: '#0ea5e9',
    dob: '09 يوليو 1972', room: '05', bed: '01-B',
    admitted: '09 مايو 2025', doctor: 'د. أحمد بن علي',
    dept: 'الأمراض المعدية', ward: 'men',
    reason: 'تيفوئيد — مراقبة يومية',
    status: 'stable',
    treatments: [
     
    ]
  },
  {
    id: 8, name: 'زينب قاسمي', initials: 'ز', color: '#f97316',
    dob: '27 فبراير 1965', room: '09', bed: '01-A',
    admitted: '10 مايو 2025', doctor: 'د. كريم لعرابي',
    dept: 'الجهاز الهضمي',  ward: 'women',
    reason: 'تليف الكبد — متابعة دورية',
    status: 'watch',
    treatments: [

    ]
  },
  {
    id: 9, name: 'محمد بلعيد', initials: 'م', color: '#3b82f6',
    dob: '15 يونيو 1980', room: '01', bed: '01-A',
    admitted: '09 مايو 2025', doctor: 'د. أحمد بن علي',
    dept: 'الطب الداخلي', ward: 'men',
    reason: 'التهاب المفاصل — متابعة دورية',
    status: 'stable',
    treatments: [
      { name: 'إيبوبروفين 400mg', dose: 'قرص ثلاث مرات يومياً', time: '08:00', type: 'pill', notes: 'مع الطعام', done: true },
    ]
  },
  {
    id: 10, name: 'رشيد عيساوي', initials: 'ر', color: '#f59e0b',
    dob: '03 مارس 1972', room: '04', bed: '01-B',
    admitted: '11 مايو 2025', doctor: 'د. كريم لعرابي',
    dept: 'الجراحة العامة', ward: 'men',
    reason: 'كسر في الساق — جبيرة وإعادة تأهيل',
    status: 'stable',
    treatments: [
      
    ]
  },
  {
    id: 11, name: 'هشام تومي', initials: 'ه', color: '#0ea5e9',
    dob: '22 أغسطس 1988', room: '07', bed: '01-A',
    admitted: '08 مايو 2025', doctor: 'د. يوسف قادري',
    dept: 'الأمراض المعدية', ward: 'men',
    reason: 'التهاب رئوي — علاج بالمضادات الحيوية',
    status: 'watch',
    treatments: [

    ]
  },
  {
    id: 12, name: 'أمين بوطالب', initials: 'أ', color: '#8b5cf6',
    dob: '11 ديسمبر 1960', room: '08', bed: '02-A',
    admitted: '10 مايو 2025', doctor: 'د. نادية بوخالفة',
    dept: 'الجهاز الهضمي', ward: 'men',
    reason: 'قرحة معدة — علاج دوائي',
    status: 'stable',
    treatments: [
      
    ]
  },
  {
    id: 13, name: 'حميد مقراني', initials: 'ح', color: '#10b981',
    dob: '07 يناير 1955', room: '10', bed: '01-A',
    admitted: '07 مايو 2025', doctor: 'د. أحمد بن علي',
    dept: 'الجهاز التنفسي', ward: 'men',
    reason: 'الربو — ضيق تنفس حاد',
    status: 'watch',
    treatments: [

    ]
  },
  {
    id: 14, name: 'كمال زروق', initials: 'ك', color: '#06b6d4',
    dob: '19 سبتمبر 1975', room: '16', bed: '01-B',
    admitted: '12 مايو 2025', doctor: 'د. سارة عمار',
    dept: 'الطب الداخلي', ward: 'men',
    reason: 'السكري النوع الثاني — تعديل الجرعة',
    status: 'stable',
    treatments: [
     
    ]
  },
  {
    id: 15, name: 'نور بن زارة', initials: 'ن', color: '#ec4899',
    dob: '28 فبراير 1992', room: '03', bed: '01-A',
    admitted: '11 مايو 2025', doctor: 'د. يوسف قادري',
    dept: 'الأمراض المعدية', ward: 'women',
    reason: 'التهاب اللوزتين — علاج بالمضادات الحيوية',
    status: 'stable',
    treatments: [
     
    ]
  },
  {
    id: 16, name: 'خيرة قاسم', initials: 'خ', color: '#f97316',
    dob: '14 يوليو 1969', room: '05', bed: '02-A',
    admitted: '09 مايو 2025', doctor: 'د. أحمد بن علي',
    dept: 'الجهاز التنفسي', ward: 'women',
    reason: 'التهاب القصبات — متابعة',
    status: 'watch',
    treatments: [
     
    ]
  },
  {
    id: 17, name: 'هناء دحماني', initials: 'ه', color: '#8b5cf6',
    dob: '02 نوفمبر 1983', room: '11', bed: '01-B',
    admitted: '10 مايو 2025', doctor: 'د. سارة عمار',
    dept: 'الطب الداخلي', ward: 'women',
    reason: 'فقر الدم — نقل دم وعلاج داعم',
    status: 'stable',
    treatments: [
    
    ]
  },
];

const TREATMENTS_ALL = [
 
];

const WARD_ROOMS = {
  men: [
    { num:'01', patient:'محمد بلعيد',    detail:'جناح الرجال', bed:'01-A', status:'occupied' },
    { num:'02', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'03', patient:'خالد رشيد',     detail:'التهاب رئوي', bed:'01-A', status:'occupied' },
    { num:'04', patient:'رشيد عيساوي',   detail:'كسر ساق',    bed:'01-B', status:'occupied' },
    { num:'05', patient:'يوسف جاب الله', detail:'تيفوئيد',     bed:'01-B', status:'occupied' },
    { num:'06', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'07', patient:'هشام تومي',     detail:'الأمراض المعدية',bed:'01-A',status:'occupied'},
    { num:'08', patient:'أمين بوطالب',   detail:'الجهاز الهضمي',bed:'02-A',status:'occupied' },
    { num:'09', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'10', patient:'حميد مقراني',   detail:'الجهاز التنفسي',bed:'01-A',status:'occupied'},
    { num:'11', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'12', patient:'عمر طيب',       detail:'جراحة عامة',  bed:'02-A', status:'occupied' },
    { num:'13', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'14', patient:'حسن بوزيد',     detail:'حالة حرجة',   bed:'01-A', status:'urgent'   },
    { num:'15', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'16', patient:'كمال زروق',     detail:'الطب الداخلي',bed:'01-B', status:'occupied' },
  ],
  women: [
    { num:'01', patient:'ليلى معمر',     detail:'ما بعد الولادة',bed:'02-B',status:'occupied' },
    { num:'02', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'03', patient:'نور بن زارة',   detail:'أمراض معدية', bed:'01-A', status:'occupied' },
    { num:'04', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'05', patient:'خيرة قاسم',     detail:'الجهاز التنفسي',bed:'02-A',status:'occupied'},
    { num:'06', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'07', patient:'مريم حسين',     detail:'ارتفاع ضغط',  bed:'02-B', status:'occupied' },
    { num:'08', patient:'سارة بلقاسم',   detail:'سكري',        bed:'01-A', status:'occupied' },
    { num:'09', patient:'زينب قاسمي',    detail:'أمراض الكبد', bed:'01-A', status:'occupied' },
    { num:'10', patient:null,             detail:'',             bed:'',     status:'empty'    },
    { num:'11', patient:'هناء دحماني',   detail:'الطب الداخلي',bed:'01-B', status:'occupied' },
    { num:'12', patient:null,             detail:'',             bed:'',     status:'empty'    },
  ]
};

/* ════════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════════ */
const TYPE_META = {
  inject:   { icon: 'fas fa-syringe',       cls: 'inject',   label: 'حقنة',     color: '#ef4444' },
  pill:     { icon: 'fas fa-pills',          cls: 'pill',     label: 'دواء',     color: '#0ea5e9' },
  serum:    { icon: 'fas fa-droplet',        cls: 'serum',    label: 'مصل',      color: '#8b5cf6' },
  dressing: { icon: 'fas fa-bandage',        cls: 'dressing', label: 'ضماد',     color: '#f59e0b' },
  measure:  { icon: 'fas fa-heart-pulse',    cls: 'measure',  label: 'قياس',     color: '#10b981' },
};

const STATUS_LABELS = {
  stable:   'مستقر',
  watch:    'تحت المراقبة',
  critical: 'حالة حرجة',
  new:      'دخول جديد',
};

function getInitials(name) {
  return name.trim()[0];
}

function gradientFor(color) {
  return `linear-gradient(135deg, ${color}, ${color}cc)`;
}

/* ════════════════════════════════════════════════════════════
   CLOCK & DATETIME
════════════════════════════════════════════════════════════ */
function updateClock() {
  const now  = new Date();
  const h    = now.getHours(),
        m    = now.getMinutes(),
        s    = now.getSeconds();

  const pad  = n => String(n).padStart(2,'0');
  const time = `${pad(h)}:${pad(m)}:${pad(s)}`;

  const dayNames  = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  const monthNames= ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  const dateStr   = `${dayNames[now.getDay()]}، ${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;

  // Header
  const hTime = document.getElementById('headerTime');
  const hDate = document.getElementById('headerDate');
  if (hTime) hTime.textContent = time;
  if (hDate) hDate.textContent = dateStr;

  // Clock Widget Digital
  const cDig  = document.getElementById('clockDigital');
  const cDate = document.getElementById('clockDate');
  if (cDig)  cDig.textContent  = time;
  if (cDate) cDate.textContent  = dateStr;

  // Analog hands
  const hourDeg = (h % 12) * 30 + m * 0.5;
  const minDeg  = m * 6 + s * 0.1;
  const secDeg  = s * 6;

  const hh = document.getElementById('hourHand');
  const mh = document.getElementById('minHand');
  const sh = document.getElementById('secHand');
  if (hh) hh.style.transform = `rotate(${hourDeg}deg)`;
  if (mh) mh.style.transform = `rotate(${minDeg}deg)`;
  if (sh) sh.style.transform = `rotate(${secDeg}deg)`;
}

/* ════════════════════════════════════════════════════════════
   SECTION NAVIGATION
════════════════════════════════════════════════════════════ */
function switchSection(name, el) {
  // Sections
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  const target = document.getElementById('section-' + name);
  if (target) target.classList.add('active');

  // Nav items
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');

  // Header title
  const titles = {
    dashboard:   'لوحة التحكم',
    patients:    'قائمة المرضى',
    'ward-men':  'جناح الرجال',
    'ward-women':'جناح النساء',
    treatments:  'جدول العلاجات',
    calendar:    'التقويم',
    reports:     'التقارير',
    settings:    'الإعدادات',
  };
  // Close mobile sidebar
  document.getElementById('sidebar').classList.remove('mobile-open');

  // Section-specific render
  if (name === 'ward-men')   renderWard('men');
  if (name === 'ward-women') renderWard('women');

  // Populate settings dept field with current value
  if (name === 'settings') {
    const inp = document.getElementById('settingsDeptInput');
    if (inp) inp.value = nurseDept;
  }
}

function saveSettingsInfo() {
  const inp = document.getElementById('settingsDeptInput');
  if (inp) {
    const val = inp.value.trim();
    if (val) {
      nurseDept = val;
      localStorage.setItem('nurseDept', nurseDept);
      // Update sidebar display
      const display = document.getElementById('nurseDeptDisplay');
      if (display) display.textContent = nurseDept;
      // Sync all patients
      applyNurseDept();
    }
  }
  showToast('تم حفظ المعلومات بنجاح ✓');
}

/* ════════════════════════════════════════════════════════════
   RENDER: DASHBOARD PATIENT MINI LIST
════════════════════════════════════════════════════════════ */
function renderDashPatients() {
  const el = document.getElementById('dashPatientList');
  if (!el) return;
  el.innerHTML = PATIENTS.slice(0, 6).map(p => `
    <div class="patient-mini-item" onclick="openPatientPanel(${p.id})">
      <div class="pmi-avatar" style="background:${gradientFor(p.color)}">${p.initials}</div>
      <div class="pmi-info">
        <div class="pmi-name">${p.name}</div>
        <div class="pmi-sub"><i class="fas fa-door-open" style="font-size:.6rem;color:var(--primary)"></i> غرفة ${p.room} · ${p.dept}</div>
      </div>
      <span class="pmi-status ${p.status}">${STATUS_LABELS[p.status]}</span>
    </div>
  `).join('');
}

/* ════════════════════════════════════════════════════════════
   RENDER: PATIENTS LIST
════════════════════════════════════════════════════════════ */
function renderPatients(list) {
  const el = document.getElementById('patientsContainer');
  if (!el) return;

  if (!list || !list.length) {
    el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-subtle);font-size:.9rem;">لا توجد نتائج</div>`;
    return;
  }

  el.innerHTML = list.map(p => `
    <div class="patient-card" id="pc-${p.id}">
      <div class="pc-row" onclick="togglePatientCard(${p.id})">
        <div class="pc-avatar" style="background:${gradientFor(p.color)}">${p.initials}</div>
        <div class="pc-info">
          <div class="pc-name">${p.name}</div>
          <div class="pc-meta">
            <span class="pc-meta-item"><i class="fas fa-door-open"></i> غرفة ${p.room} · سرير ${p.bed}</span>
            <span class="pc-meta-item"><i class="fas fa-stethoscope"></i> ${p.dept}</span>
            <span class="pc-meta-item"><i class="fas fa-user-doctor"></i> ${p.doctor}</span>
          </div>
        </div>
        <div class="pc-right">
          <span class="pc-status ${p.status}">${STATUS_LABELS[p.status]}</span>
          <div class="pc-expand" id="expand-${p.id}"><i class="fas fa-chevron-down"></i></div>
        </div>
      </div>
      <div class="pc-inline" id="inline-${p.id}">
        <div class="pc-inline-inner">
          <!-- Info Grid -->
          <div class="pc-section-title"><i class="fas fa-id-card"></i> المعلومات الشخصية</div>
          <div class="pc-info-grid" style="margin-bottom:18px">
            <div class="pc-info-cell"><label>تاريخ الميلاد</label><span>${p.dob}</span></div>
            <div class="pc-info-cell"><label>تاريخ الدخول</label><span>${p.admitted}</span></div>
            <div class="pc-info-cell"><label>رقم الغرفة</label><span>${p.room}</span></div>
            <div class="pc-info-cell"><label>رقم السرير</label><span>${p.bed}</span></div>
            <div class="pc-info-cell"><label>الطبيب المعالج</label><span>${p.doctor}</span></div>
            <div class="pc-info-cell"><label>المصلحة</label><span>${p.dept}</span></div>
            <div class="pc-info-cell full"><label>سبب الدخول</label><span>${p.reason}</span></div>
          </div>
          <!-- Treatments -->
          <div class="pc-section-title"><i class="fas fa-syringe"></i> فيش العلاجات</div>
          ${p.treatments.map((t,i) => buildTreatmentRow(t, p.id, i)).join('')}
          <!-- Notes -->
          <div class="pc-section-title" style="margin-top:14px"><i class="fas fa-note-sticky"></i> ملاحظات التمريض</div>
          <textarea class="notes-area" placeholder="أضف ملاحظاتك هنا..."></textarea>
          <button class="btn-primary mt8 small" onclick="showToast('تم حفظ الملاحظة ✓')">
            <i class="fas fa-save"></i> حفظ
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function buildTreatmentRow(t, pid, idx) {
  const meta = TYPE_META[t.type] || TYPE_META.measure;
  return `
    <div class="treatment-row ${t.done ? 'done' : ''}" id="tr-${pid}-${idx}">
      <div class="tr-type-badge ${meta.cls}"><i class="${meta.icon}"></i></div>
      <div class="tr-body">
        <div class="tr-name">${t.name}</div>
        <div class="tr-dose">${t.dose}</div>
        ${t.notes ? `<div class="tr-notes">${t.notes}</div>` : ''}
      </div>
      <div class="tr-time">${t.time}</div>
      <button class="tr-done-btn ${t.done ? 'checked' : ''}"
              onclick="toggleTreatment('tr-${pid}-${idx}', this)"
              title="${t.done ? 'مكتمل' : 'تأكيد الإنجاز'}">
        <i class="fas fa-check"></i>
      </button>
    </div>
  `;
}

function togglePatientCard(id) {
  const card   = document.getElementById('pc-' + id);
  const inline = document.getElementById('inline-' + id);
  const expand = document.getElementById('expand-' + id);
  if (!card || !inline || !expand) return;

  const isOpen = inline.classList.contains('open');

  // Close all first
  document.querySelectorAll('.pc-inline.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.patient-card.expanded').forEach(el => el.classList.remove('expanded'));
  document.querySelectorAll('.pc-expand.open').forEach(el => el.classList.remove('open'));

  if (!isOpen) {
    inline.classList.add('open');
    card.classList.add('expanded');
    expand.classList.add('open');
    // Smooth scroll
    setTimeout(() => {
      card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 80);
  }
}

/* ════════════════════════════════════════════════════════════
   TREATMENT TOGGLE
════════════════════════════════════════════════════════════ */
function toggleTreatment(rowId, btn) {
  const row = document.getElementById(rowId);
  if (!row) return;

  const isDone = row.classList.contains('done');

  if (!isDone) {
    row.classList.add('done');
    btn.classList.add('checked');
    showToast('✓ تم تسجيل إنجاز العلاج');

    // Ripple animation
    btn.style.transform = 'scale(1.3)';
    setTimeout(() => { btn.style.transform = ''; }, 300);
  } else {
    row.classList.remove('done');
    btn.classList.remove('checked');
    showToast('تم إلغاء تأكيد العلاج');
  }
}

/* ════════════════════════════════════════════════════════════
   FILTER PATIENTS
════════════════════════════════════════════════════════════ */
function filterPatients(wardFilter) {
  const query = (document.getElementById('patientSearch')?.value || '').toLowerCase();
  const ward  = wardFilter || document.querySelector('.filter-select')?.value || 'all';

  let list = PATIENTS.filter(p => {
    const matchName = p.name.includes(query) || p.room.includes(query) || p.dept.includes(query);
    const matchWard = ward === 'all'    ? true
                    : ward === 'men'    ? p.ward === 'men'
                    : ward === 'women'  ? p.ward === 'women'
                    : ward === 'urgent' ? p.status === 'critical'
                    : true;
    return matchName && matchWard;
  });

  renderPatients(list);
}

/* ════════════════════════════════════════════════════════════
   RENDER: TREATMENTS (section)
════════════════════════════════════════════════════════════ */
let treatmentData = TREATMENTS_ALL.map(t => ({ ...t }));

function renderTreatments(list) {
  const el = document.getElementById('treatmentsList');
  if (!el) return;

  el.innerHTML = list.map(t => {
    const meta = TYPE_META[t.type] || TYPE_META.measure;
    return `
      <div class="treatment-card ${t.status === 'done' ? 'done' : ''}" id="tc-${t.id}">
        ${t.urgent ? '<div class="tc-urgent-bar"></div>' : ''}
        <div class="tc-main">
          <div class="tc-color-bar" style="background:${meta.color}"></div>
          <div class="tc-icon" style="background:${meta.color}18;color:${meta.color}">
            <i class="${meta.icon}"></i>
          </div>
          <div class="tc-body">
            <div class="tc-name">${t.name}</div>
            <div class="tc-dose">${t.dose}</div>
            ${t.notes ? `<div class="tc-notes">${t.notes}</div>` : ''}
          </div>
          <div class="tc-meta">
            <div class="tc-time">${t.time}</div>
            <div class="tc-patient"><i class="fas fa-user"></i> ${t.patient} · غرفة ${t.room}</div>
          </div>
          <button class="tc-done-btn ${t.status === 'done' ? 'done' : ''}"
                  onclick="toggleTreatmentCard(${t.id}, this)"
                  title="${t.status === 'done' ? 'مكتمل' : 'تأكيد الإنجاز'}">
            <i class="fas fa-check"></i>
          </button>
        </div>
      </div>
    `;
  }).join('');
}

function toggleTreatmentCard(id, btn) {
  const t = treatmentData.find(x => x.id === id);
  if (!t) return;

  const card = document.getElementById('tc-' + id);
  if (t.status === 'pending') {
    t.status = 'done';
    card.classList.add('done');
    btn.classList.add('done');
    btn.style.transform = 'scale(1.3)';
    setTimeout(() => { btn.style.transform = ''; }, 300);
    showToast('✓ تم تسجيل إنجاز العلاج');
  } else {
    t.status = 'pending';
    card.classList.remove('done');
    btn.classList.remove('done');
    showToast('تم إلغاء تأكيد العلاج');
  }
}

function filterTreatments(filter, tabEl) {
  document.querySelectorAll('.ftab').forEach(t => t.classList.remove('active'));
  if (tabEl) tabEl.classList.add('active');

  let list = treatmentData;
  if (filter === 'pending') list = treatmentData.filter(t => t.status === 'pending' && !t.urgent);
  if (filter === 'done')    list = treatmentData.filter(t => t.status === 'done');
  if (filter === 'urgent')  list = treatmentData.filter(t => t.urgent);

  renderTreatments(list);
}

/* ════════════════════════════════════════════════════════════
   RENDER: WARD GRID
════════════════════════════════════════════════════════════ */
function renderWard(gender) {
  const rooms = WARD_ROOMS[gender];
  const elId  = gender === 'men' ? 'wardMenGrid' : 'wardWomenGrid';
  const el    = document.getElementById(elId);
  if (!el) return;

  const bedIcons = { occupied: '🛏️', empty: '⬜', urgent: '🚨' };

  el.innerHTML = rooms.map(r => {
    const isClickable = r.status !== 'empty';
    // Match by name first (most accurate), fallback to room+ward
    let patientObj = null;
    if (isClickable && r.patient) {
      patientObj = PATIENTS.find(p => p.name === r.patient);
      if (!patientObj) {
        // fallback: match by room AND ward
        patientObj = PATIENTS.find(p => p.room === r.num && p.ward === gender);
      }
    }
    const clickAttr = patientObj
      ? `onclick="openPatientFile(${patientObj.id}, '${gender}')"`
      : (isClickable ? `onclick="showToast('لا توجد بيانات تفصيلية لهذا المريض')"` : '');
    return `
    <div class="ward-room ${r.status} ${isClickable ? 'clickable-room' : ''}" ${clickAttr}>
      <div class="wr-number">غرفة ${r.num}</div>
      <div class="wr-bed-icon">${bedIcons[r.status]}</div>
      <div class="wr-name">${r.patient || 'شاغر'}</div>
      ${isClickable ? '<div class="wr-view-btn"><i class="fas fa-folder-open"></i> عرض الملف</div>' : ''}
    </div>
  `;
  }).join('');
}

/* ════════════════════════════════════════════════════════════
   PATIENT PANEL (side panel)
════════════════════════════════════════════════════════════ */
function openPatientPanel(id) {
  const p = PATIENTS.find(x => x.id === id);
  if (!p) return;

  const panel   = document.getElementById('patientPanel');
  const overlay = document.getElementById('patientPanelOverlay');

  // Fill data
  document.getElementById('ppAvatar').style.background = gradientFor(p.color);
  document.getElementById('ppAvatar').textContent = p.initials;
  document.getElementById('ppName').textContent    = p.name;
  document.getElementById('ppRoom').textContent    = 'غرفة ' + p.room;
  document.getElementById('ppStatus').textContent  = STATUS_LABELS[p.status];
  document.getElementById('ppWard').textContent    = p.ward === 'men' ? 'جناح الرجال' : 'جناح النساء';
  document.getElementById('ppDOB').textContent     = p.dob;
  document.getElementById('ppRoomNo').textContent  = p.room;
  document.getElementById('ppBed').textContent     = p.bed;
  document.getElementById('ppAdmit').textContent   = p.admitted;
  document.getElementById('ppDoctor').textContent  = p.doctor;
  document.getElementById('ppDept').textContent    = p.dept;
  document.getElementById('ppReason').textContent  = p.reason;

  // Treatments
  const trEl = document.getElementById('ppTreatments');
  trEl.innerHTML = p.treatments.map((t,i) => buildTreatmentRow(t, 'pp'+p.id, i)).join('');

  panel.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closePatientPanel() {
  document.getElementById('patientPanel').classList.remove('open');
  document.getElementById('patientPanelOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

/* ════════════════════════════════════════════════════════════
   SIDEBAR TOGGLE (mobile)
════════════════════════════════════════════════════════════ */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('mobile-open');
}

/* ════════════════════════════════════════════════════════════
   NOTIFICATIONS
════════════════════════════════════════════════════════════ */
function toggleNotifs() {
  const panel = document.getElementById('notifPanel');
  panel.classList.toggle('open');

  // Close on outside click
  if (panel.classList.contains('open')) {
    setTimeout(() => {
      document.addEventListener('click', closeNotifs, { once: true });
    }, 10);
  }
}

function closeNotifs(e) {
  const panel = document.getElementById('notifPanel');
  if (!panel.contains(e.target)) {
    panel.classList.remove('open');
  }
}

/* ════════════════════════════════════════════════════════════
   PATIENT FILE PAGE
════════════════════════════════════════════════════════════ */
let currentPatientFileId = null;
let pfFromSection = 'ward-men';
let pfCalYear, pfCalMonth, pfSelectedDay, pfSelectedMonth, pfSelectedYear;
let pfWeekStart = new Date(); // Monday of current week

const PF_MONTH_NAMES = ['يناير','فبراير','مارس','أبريل','مايو','يونيو',
                        'يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];

// Treatment status storage: key = patientId_date_treatIdx => 'done'|'missed'
const treatmentStatus = JSON.parse(localStorage.getItem('treatmentStatus') || '{}');

function saveTreatmentStatus() {
  localStorage.setItem('treatmentStatus', JSON.stringify(treatmentStatus));
}

function getTreatmentKey(pid, year, month, day, idx) {
  return `${pid}_${year}_${month}_${day}_${idx}`;
}

function openPatientFile(id, fromSection) {
  const p = PATIENTS.find(x => x.id === id);
  if (!p) return;
  currentPatientFileId = id;
  pfFromSection = fromSection === 'men' ? 'ward-men'
                : fromSection === 'women' ? 'ward-women'
                : (fromSection || 'ward-men');

  // Sync dept
  p.dept = nurseDept;

  // Set back button — store section name at open time
  const backSection = pfFromSection;
  const backBtn = document.getElementById('pfBackBtn');
  if (backBtn) {
    backBtn.onclick = () => {
      // Find the matching nav item
      let navEl = null;
      document.querySelectorAll('.nav-item').forEach(n => {
        if ((n.getAttribute('onclick') || '').includes(backSection)) navEl = n;
      });
      switchSection(backSection, navEl);
    };
  }

  // Fill info
  document.getElementById('pfFullName').textContent = p.name;

  document.getElementById('pfDOB').textContent    = p.dob;
  document.getElementById('pfAdmit').textContent  = p.admitted;
  document.getElementById('pfRoom').textContent   = `غرفة ${p.room}`;
  document.getElementById('pfDoctor').textContent = p.doctor;
  document.getElementById('pfReason').textContent = p.reason;

  // Calendar: init to current week (week starts Saturday for Arabic locale)
  const today = new Date();
  pfSelectedDay   = today.getDate();
  pfSelectedMonth = today.getMonth();
  pfSelectedYear  = today.getFullYear();

  // Compute week start (Saturday = day 6; shift so week starts Sat)
  const dow = today.getDay(); // 0=Sun,6=Sat
  const diffToSat = (dow >= 6) ? 0 : -(dow + 1);
  pfWeekStart = new Date(today);
  pfWeekStart.setDate(today.getDate() + diffToSat);
  pfWeekStart.setHours(0,0,0,0);

  // Also keep pfCalYear/pfCalMonth in sync
  pfCalYear  = today.getFullYear();
  pfCalMonth = today.getMonth();

  buildPfCalendar();
  renderPfTreatments();

  // Switch to section
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.getElementById('section-patient-file').classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  // Check missed treatments alarm
  checkMissedTreatmentsAlarm();
}

function buildPfCalendar() {
  const label = document.getElementById('pfCalMonthLabel');
  const daysEl = document.getElementById('pfCalDays');
  if (!label || !daysEl) return;

  const today = new Date();

  // Build the 7 days of the current week (pfWeekStart is Monday of that week)
  const DAY_NAMES = ['الأح','الإث','الثل','الأر','الخم','الجم','السب'];

  let html = '';
  for (let i = 0; i < 7; i++) {
    const d = new Date(pfWeekStart);
    d.setDate(pfWeekStart.getDate() + i);

    const isToday    = d.toDateString() === today.toDateString();
    const isSelected = (d.getDate() === pfSelectedDay &&
                        d.getMonth() === pfSelectedMonth &&
                        d.getFullYear() === pfSelectedYear);
    const isPast     = d < new Date(today.getFullYear(), today.getMonth(), today.getDate());

    const dd = d.getDate();
    const mm = d.getMonth();
    const yy = d.getFullYear();
    const dayName = DAY_NAMES[d.getDay()];

    html += `
      <div class="pf-week-day ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''} ${isPast && !isToday ? 'past' : ''}"
           onclick="pfSelectDay(${dd}, ${mm}, ${yy})">
        <span class="pf-wd-name">${dayName}</span>
        <span class="pf-wd-num">${dd}</span>
      </div>`;
  }

  // Label: show month(s) of the week
  const startMonth = PF_MONTH_NAMES[pfWeekStart.getMonth()];
  const endDate    = new Date(pfWeekStart); endDate.setDate(pfWeekStart.getDate() + 6);
  const endMonth   = PF_MONTH_NAMES[endDate.getMonth()];
  const yearStr    = pfWeekStart.getFullYear();
  label.textContent = (startMonth === endMonth)
    ? `${startMonth} ${yearStr}`
    : `${startMonth} — ${endMonth} ${yearStr}`;

  daysEl.innerHTML = html;
}

function pfChangeWeek(dir) {
  pfWeekStart = new Date(pfWeekStart);
  pfWeekStart.setDate(pfWeekStart.getDate() + dir * 7);
  buildPfCalendar();
}

function pfSelectDay(d, m, y) {
  pfSelectedDay   = d;
  pfSelectedMonth = (m !== undefined) ? m : pfCalMonth;
  pfSelectedYear  = (y !== undefined) ? y : pfCalYear;
  buildPfCalendar();
  renderPfTreatments();
}

function pfIsToday() {
  const today = new Date();
  return pfSelectedDay === today.getDate() &&
         pfSelectedMonth === today.getMonth() &&
         pfSelectedYear  === today.getFullYear();
}

function renderPfTreatments() {
  const el = document.getElementById('pfTreatmentsList');
  if (!el || !currentPatientFileId) return;

  const p = PATIENTS.find(x => x.id === currentPatientFileId);
  if (!p) return;

  const isToday = pfIsToday();

  if (!p.treatments || !p.treatments.length) {
    el.innerHTML = `<div class="pf-no-treatments">لا توجد علاجات لهذا اليوم</div>`;
    return;
  }

  el.innerHTML = p.treatments.map((t, idx) => {
    const key    = getTreatmentKey(p.id, pfSelectedYear, pfSelectedMonth, pfSelectedDay, idx);
    const status = treatmentStatus[key] || (t.done && pfIsToday() ? 'done' : 'pending');
    const meta   = TYPE_META[t.type] || TYPE_META.measure;
    const isDone = status === 'done';
    const isMissed = status === 'missed';

    let actionBtn = '';
    if (isToday) {
      actionBtn = `<button class="pf-confirm-btn ${isDone ? 'confirmed' : ''}"
                           onclick="pfToggleTreatment(${idx})"
                           title="${isDone ? 'مكتمل — اضغط للإلغاء' : 'تأكيد العلاج'}">
                    <i class="fas fa-check"></i>
                    ${isDone ? 'تم التأكيد' : 'تأكيد العلاج'}
                   </button>`;
    } else {
      // Read-only for past days
      actionBtn = `<span class="pf-readonly-badge ${isDone ? 'done' : isMissed ? 'missed' : 'pending'}">
                    ${isDone ? '✓ منجز' : isMissed ? '✗ غير منجز' : '—'}
                   </span>`;
    }

    return `
      <div class="pf-treatment-item ${isDone ? 'done' : ''} ${isMissed ? 'missed' : ''}">
        <div class="pf-tr-icon" style="background:${meta.color}18;color:${meta.color}">
          <i class="${meta.icon}"></i>
        </div>
        <div class="pf-tr-body">
          <div class="pf-tr-name">${t.name}</div>
          <div class="pf-tr-type">${meta.label}</div>
          <div class="pf-tr-time"><i class="fas fa-clock"></i> ${t.time}</div>
          ${t.notes ? `<div class="pf-tr-notes">${t.notes}</div>` : ''}
        </div>
        <div class="pf-tr-action">
          ${actionBtn}
        </div>
      </div>
    `;
  }).join('');
}

function pfToggleTreatment(idx) {
  if (!currentPatientFileId) return;
  if (!pfIsToday()) return; // safety

  const p   = PATIENTS.find(x => x.id === currentPatientFileId);
  if (!p) return;

  const key    = getTreatmentKey(p.id, pfSelectedYear, pfSelectedMonth, pfSelectedDay, idx);
  const current = treatmentStatus[key] || 'pending';

  if (current === 'done') {
    treatmentStatus[key] = 'pending';
    showToast('تم إلغاء تأكيد العلاج');
  } else {
    treatmentStatus[key] = 'done';
    showToast('✓ تم تأكيد العلاج بنجاح');
  }
  saveTreatmentStatus();
  renderPfTreatments();
}

/* ════════════════════════════════════════════════════════════
   MISSED TREATMENTS ALARM
════════════════════════════════════════════════════════════ */
function checkMissedTreatmentsAlarm() {
  // Check all patients for missed treatments (past day, not confirmed)
  const now   = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  let hasMissed = false;

  PATIENTS.forEach(p => {
    p.treatments.forEach((t, idx) => {
      // Check yesterday
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      const key = getTreatmentKey(p.id, yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), idx);
      const status = treatmentStatus[key];
      if (!status || status === 'pending') {
        // Mark as missed
        treatmentStatus[key] = 'missed';
        hasMissed = true;
      }
    });
  });

  if (hasMissed) {
    saveTreatmentStatus();
    triggerMissedAlarm();
  }
}

let alarmInterval = null;

function triggerMissedAlarm() {
  // Show notification
  showToast('⚠ توجد علاجات غير منجزة من الأمس!');

  // Play alarm using Web Audio API (beep pattern)
  if (alarmInterval) return; // already playing
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    alarmInterval = setInterval(() => {
      const osc  = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.4);
    }, 1500);

    // Stop after 10 seconds (or user dismisses)
    setTimeout(() => stopAlarm(), 10000);
  } catch(e) {
    console.warn('Audio not available:', e);
  }
}

function stopAlarm() {
  if (alarmInterval) {
    clearInterval(alarmInterval);
    alarmInterval = null;
  }
}

/* ════════════════════════════════════════════════════════════
   CALENDAR
════════════════════════════════════════════════════════════ */
let calYear = 2025, calMonth = 4; // May 2025

function buildCalendar(year, month) {
  const el = document.getElementById('calendarBig');
  if (!el) return;

  const monthNames = ['يناير','فبراير','مارس','أبريل','مايو','يونيو',
                       'يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  const dayNames   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];

  const today     = new Date();
  const firstDay  = new Date(year, month, 1).getDay();
  const daysInMonth    = new Date(year, month+1, 0).getDate();
  const daysInPrevMonth= new Date(year, month, 0).getDate();

  // Event days (dummy)
  const eventDays = [3, 7, 10, 12, 14, 16, 18, 21, 24, 26];

  let daysHTML = '';
  // Prev month days
  for (let i = firstDay - 1; i >= 0; i--) {
    daysHTML += `<div class="cal-day other-month">${daysInPrevMonth - i}</div>`;
  }
  // Current month
  for (let d = 1; d <= daysInMonth; d++) {
    const isToday = (today.getDate() === d && today.getMonth() === month && today.getFullYear() === year);
    const hasEv   = eventDays.includes(d);
    daysHTML += `<div class="cal-day ${isToday ? 'today' : ''} ${hasEv ? 'has-event' : ''}" onclick="calSelectDay(${d},${month},${year})">${d}</div>`;
  }
  // Fill remaining
  const total = Math.ceil((firstDay + daysInMonth) / 7) * 7;
  let nextDay = 1;
  for (let i = firstDay + daysInMonth; i < total; i++) {
    daysHTML += `<div class="cal-day other-month">${nextDay++}</div>`;
  }

  el.innerHTML = `
    <div class="cal-header">
      <div class="cal-title">${monthNames[month]} ${year}</div>
      <div class="cal-nav">
        <button onclick="changeMonth(-1)"><i class="fas fa-chevron-right"></i></button>
        <button onclick="changeMonth(1)"><i class="fas fa-chevron-left"></i></button>
      </div>
    </div>
    <div class="cal-weekdays">
      ${dayNames.map(d => `<div class="cal-weekday">${d.slice(0,3)}</div>`).join('')}
    </div>
    <div class="cal-days">${daysHTML}</div>
  `;
}

function changeMonth(dir) {
  calMonth += dir;
  if (calMonth > 11) { calMonth = 0; calYear++; }
  if (calMonth < 0)  { calMonth = 11; calYear--; }
  buildCalendar(calYear, calMonth);
}

function calSelectDay(d, month, year) {
  // Highlight selected day
  document.querySelectorAll('#calendarBig .cal-day').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');

  // Show treatments for selected day in cal events panel
  const calEventsEl = document.getElementById('calEvents');
  if (!calEventsEl) return;

  const today = new Date();
  const isToday = (d === today.getDate() && month === today.getMonth() && year === today.getFullYear());

  // Build treatments for this date across all patients
  let html = '';
  PATIENTS.forEach(p => {
    p.treatments.forEach((t, idx) => {
      const key    = getTreatmentKey(p.id, year, month, d, idx);
      const status = treatmentStatus[key] || (t.done && isToday ? 'done' : 'pending');
      const isDone = status === 'done';
      html += `
        <div class="schedule-item ${isDone ? 'done' : ''}">
          <div class="sch-time">${t.time}</div>
          <div class="sch-dot ${isDone ? 'done' : ''}"></div>
          <div class="sch-body">
            <strong>${t.name} — غرفة ${p.room}</strong>
            <span>${p.name}</span>
          </div>
        </div>`;
    });
  });

  const label = document.querySelector('#section-calendar .panel-title span');
  if (label) label.textContent = `علاجات يوم ${d} ${PF_MONTH_NAMES[month]}`;

  calEventsEl.innerHTML = html || `<div style="text-align:center;padding:20px;color:var(--text-subtle)">لا توجد علاجات لهذا اليوم</div>`;
}

/* ════════════════════════════════════════════════════════════
   TOAST
════════════════════════════════════════════════════════════ */
function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('show'), 2500);
}

/* ════════════════════════════════════════════════════════════
   SAVE NOTE
════════════════════════════════════════════════════════════ */
function saveNote() {
  const val = document.getElementById('shiftNotes')?.value;
  if (!val?.trim()) { showToast('الرجاء كتابة ملاحظة أولاً'); return; }
  showToast('✓ تم حفظ الملاحظة بنجاح');
}

/* ════════════════════════════════════════════════════════════
   LOGOUT
════════════════════════════════════════════════════════════ */
function handleLogout() {
  if (confirm('هل تريد تسجيل الخروج؟')) {
    showToast('جاري تسجيل الخروج...');
  }
}

/* ════════════════════════════════════════════════════════════
   PARTICLES BACKGROUND
════════════════════════════════════════════════════════════ */
function createParticles() {
  const container = document.getElementById('bgParticles');
  if (!container) return;

  const colors = ['#0ea5e9', '#06b6d4', '#3b82f6', '#10b981', '#8b5cf6'];

  for (let i = 0; i < 18; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    const size    = Math.random() * 5 + 2;
    const color   = colors[Math.floor(Math.random() * colors.length)];
    const delay   = Math.random() * 20;
    const duration= Math.random() * 20 + 15;
    const leftPos = Math.random() * 100;

    p.style.cssText = `
      width: ${size}px; height: ${size}px;
      background: ${color};
      left: ${leftPos}%;
      animation-duration: ${duration}s;
      animation-delay: ${delay}s;
      box-shadow: 0 0 ${size*2}px ${color}88;
      opacity: 0;
    `;
    container.appendChild(p);
  }
}

/* ════════════════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  // Apply nurse dept to all patients
  applyNurseDept();

  // Init sidebar dept display
  const deptDisplay = document.getElementById('nurseDeptDisplay');
  if (deptDisplay) deptDisplay.textContent = nurseDept;

  // Clock
  updateClock();
  setInterval(updateClock, 1000);

  // Dashboard (no longer renders patient mini list)

  // Patients
  renderPatients(PATIENTS);

  // Treatments
  renderTreatments(treatmentData);

  // Calendar
  buildCalendar(calYear, calMonth);

  // Particles
  createParticles();

  // Keyboard
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closePatientPanel();
      document.getElementById('notifPanel')?.classList.remove('open');
      stopAlarm();
    }
  });

  // Progress bar animation on reports
  document.querySelectorAll('.progress-fill').forEach(fill => {
    const target = fill.style.width;
    fill.style.width = '0%';
    setTimeout(() => { fill.style.width = target; }, 300);
  });

  // Check missed treatments on load
  checkMissedTreatmentsAlarm();
});
