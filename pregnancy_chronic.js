/* ============================================================
   MedChifaGiz — Smart Pregnancy & Chronic Disease Center
   JavaScript — Scoped to #VPR section
   
   AI INTEGRATION POINTS:
   Search for "// ⚡ AI_INTEGRATION:" comments to find where
   to plug in your Gemini / Claude API key.
   ============================================================ */

"use strict";

/* ──────────────────────────────────────────
   CONFIGURATION
   ⚡ AI_INTEGRATION: Set your Gemini API key here
   ────────────────────────────────────────── */
const PC_CONFIG = {
  // ⚡ AI_INTEGRATION: Replace with your actual Gemini API key
  GEMINI_API_KEY: "YOUR_GEMINI_API_KEY_HERE",
  GEMINI_ENDPOINT: "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent",
  
  // Current pregnancy week (replace with DB value if available)
  // ⚡ AI_INTEGRATION: Fetch from DB — $patient['pregnancy_week']
  CURRENT_WEEK: 24,
  
  // Patient chronic diseases from DB
  CHRONIC_DISEASES: window.PATIENT_DATA ? window.PATIENT_DATA.chronic_diseases : "",
};

/* ──────────────────────────────────────────
   PREGNANCY TAB
────────────────────────────────────────── */

/** Switch main tabs (حمل / مزمنة) */
function pcSwitchTab(tabName) {
  const tabs = document.querySelectorAll('#VPR .pc-tab-btn');
  const panels = document.querySelectorAll('#VPR .pc-tab-panel');
  tabs.forEach(t => t.classList.remove('active'));
  panels.forEach(p => { p.style.display = 'none'; });
  document.querySelector(`#VPR [data-tab="${tabName}"]`).classList.add('active');
  const panel = document.getElementById(`pc-panel-${tabName}`);
  if (panel) {
    panel.style.display = 'block';
    panel.classList.remove('pc-animate');
    void panel.offsetWidth;
    panel.classList.add('pc-animate');
  }
}

/** Build pregnancy week timeline */
function pcBuildTimeline() {
  const track = document.getElementById('pc-tl-track');
  if (!track) return;
  track.innerHTML = '';
  const week = PC_CONFIG.CURRENT_WEEK;
  
  const trimesterColors = [
    { weeks: [1,13],  bg: '#06d6a0', label: 'ث١', labelColor: '#065f46' },
    { weeks: [14,27], bg: '#00b4d8', label: 'ث٢', labelColor: '#164e63' },
    { weeks: [28,40], bg: '#7c3aed', label: 'ث٣', labelColor: '#4c1d95' },
  ];
  
  let lastTrimester = -1;
  for (let w = 1; w <= 40; w++) {
    let tri = 0;
    if (w >= 14 && w <= 27) tri = 1;
    if (w >= 28) tri = 2;
    
    if (tri !== lastTrimester) {
      const lbl = document.createElement('div');
      lbl.className = 'tl-trimester-label';
      lbl.style.background = trimesterColors[tri].bg + '22';
      lbl.style.color = trimesterColors[tri].labelColor;
      lbl.style.border = `1px solid ${trimesterColors[tri].bg}55`;
      lbl.textContent = trimesterColors[tri].label;
      track.appendChild(lbl);
      lastTrimester = tri;
    }
    
    const item = document.createElement('div');
    item.className = 'tl-week' + (w < week ? ' done' : '') + (w === week ? ' current' : '');
    item.innerHTML = `<div class="tl-dot">${w === week ? '★' : w}</div><div class="tl-num">${w}</div>`;
    item.title = pcGetWeekInfo(w).title;
    item.onclick = () => pcShowWeekDetail(w);
    track.appendChild(item);
  }
  
  // Scroll to current week
  setTimeout(() => {
    const cur = track.querySelector('.tl-week.current');
    if (cur) cur.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }, 300);
}

/** Week info data */
function pcGetWeekInfo(w) {
  const data = {
    4:  { title: 'التعلق بجدار الرحم', size: 'بذرة خشخاش', desc: 'الجنين يتعلق بجدار الرحم. تبدأ رحلة الحمل!' },
    8:  { title: 'القلب يبدأ بالنبض', size: 'حبة عنب', desc: 'يبدأ قلب الجنين بالنبض. تشكل الأعضاء الرئيسية.' },
    12: { title: 'اكتمال الأعضاء', size: 'ليمونة', desc: 'نهاية الثلث الأول. تتشكل جميع الأعضاء الرئيسية.' },
    16: { title: 'بداية الحركة', size: 'أفوكادو', desc: 'قد تشعرين ببعض الحركات الخفيفة. تتطور الملامح.' },
    20: { title: 'منتصف الحمل', size: 'موزة', desc: 'يمكن معرفة جنس الجنين بالسونار. الحواس تتطور.' },
    24: { title: 'الجنين يسمع صوتك', size: 'ذرة', desc: 'الجنين يسمع أصواتاً خارجية. الرئتان تتطوران.' },
    28: { title: 'الثلث الثالث', size: 'باذنجانة', desc: 'بداية الثلث الأخير. تزداد حركة الجنين.' },
    32: { title: 'استعداد للولادة', size: 'خس روماني', desc: 'الجنين يضع رأسه للأسفل. الرئتان تنضجان.' },
    36: { title: 'شبه جاهز', size: 'خربز صغير', desc: 'الجنين يزن حوالي 2.7 كغ. جاهز تقريباً.' },
    40: { title: 'الولادة المتوقعة', size: 'بطيخ صغير', desc: 'موعد الولادة! الجنين مكتمل النمو.' },
  };
  
  // Find closest milestone
  let closest = data[40];
  let minDiff = Infinity;
  for (const [wk, info] of Object.entries(data)) {
    const diff = Math.abs(parseInt(wk) - w);
    if (diff < minDiff) { minDiff = diff; closest = info; }
  }
  return closest;
}

/** Show week detail popup info */
function pcShowWeekDetail(w) {
  const info = pcGetWeekInfo(w);
  const card = document.getElementById('pc-week-detail');
  if (!card) return;
  card.innerHTML = `
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <div style="font-size:24px;">🍼</div>
      <div>
        <div style="font-size:13px;font-weight:800;color:#1a2340;">الأسبوع ${w} — ${info.title}</div>
        <div style="font-size:11px;color:#9ca3af;">الحجم: ${info.size || 'يتطور'}</div>
      </div>
    </div>
    <div style="font-size:12px;color:#6b7280;line-height:1.7;">${info.desc}</div>
  `;
  card.style.display = 'block';
}

/** Compute AI score (0-100) from pregnancy data */
function pcComputePregnancyScore() {
  const week = PC_CONFIG.CURRENT_WEEK;
  // Simple heuristic — in real app, call Gemini API
  // ⚡ AI_INTEGRATION: Replace this with Gemini API call
  const scores = { 1: 95, 2: 90, 3: 82 }; // By trimester
  const tri = week <= 13 ? 1 : week <= 27 ? 2 : 3;
  return scores[tri] + Math.floor(Math.random() * 5 - 2);
}

/** Update hero card with live data */
function pcUpdateHero() {
  const week = PC_CONFIG.CURRENT_WEEK;
  
  // Update week display
  const weekEls = document.querySelectorAll('.pc-hero-week');
  weekEls.forEach(el => el.textContent = `الأسبوع ${week}`);
  
  // Update progress circle
  const circ = document.getElementById('pc-score-circle-bar');
  if (circ) {
    const score = pcComputePregnancyScore();
    const pct = score / 100;
    const R = 28;
    const C = 2 * Math.PI * R;
    circ.style.strokeDasharray = C;
    circ.style.strokeDashoffset = C * (1 - pct);
    const scoreText = document.getElementById('pc-score-text');
    if (scoreText) scoreText.textContent = score;
  }
  
  // Compute EDD (Estimated Due Date)
  // ⚡ AI_INTEGRATION: Use actual LMP date from DB if available
  const today = new Date();
  const daysLeft = (40 - week) * 7;
  const edd = new Date(today.getTime() + daysLeft * 86400000);
  const eddEl = document.getElementById('pc-edd');
  if (eddEl) {
    eddEl.textContent = edd.toLocaleDateString('ar-DZ', { day: '2-digit', month: 'long', year: 'numeric' });
  }
  
  // Trimester label
  const triEl = document.getElementById('pc-trimester-label');
  if (triEl) {
    const labels = ['الثلث الأول', 'الثلث الثاني', 'الثلث الثالث'];
    const tri = week <= 13 ? 0 : week <= 27 ? 1 : 2;
    triEl.textContent = labels[tri];
  }
  
  // Fetus size description
  const info = pcGetWeekInfo(week);
  const fsizeEl = document.getElementById('pc-fetus-size');
  if (fsizeEl) fsizeEl.textContent = info.size || '—';
  const fdescEl = document.getElementById('pc-fetus-desc');
  if (fdescEl) fdescEl.textContent = info.desc;
}

/** Symptom slider change handler */
function pcUpdateSymptom(id, value) {
  const labels = ['طبيعي', 'خفيف', 'متوسط', 'مرتفع', 'شديد'];
  const valEl = document.getElementById(`pc-sym-val-${id}`);
  if (valEl) valEl.textContent = labels[Math.round(value)] || value;
  pcEvaluateSymptoms();
}

/** Evaluate symptoms and show AI assessment */
function pcEvaluateSymptoms() {
  const sliders = document.querySelectorAll('#VPR .symptom-slider');
  let total = 0;
  sliders.forEach(s => total += parseInt(s.value));
  const avg = sliders.length > 0 ? total / sliders.length : 0;
  
  const resultEl = document.getElementById('pc-symptom-result');
  if (!resultEl) return;
  
  // ⚡ AI_INTEGRATION: Replace this logic with Gemini API call
  // Send symptoms data to Gemini and get professional assessment
  let cls, icon, text;
  if (avg <= 1) {
    cls = 'green'; icon = '✅';
    text = 'الأعراض طبيعية جداً. استمري بالنمط الصحي الحالي. كل شيء يسير بشكل رائع!';
  } else if (avg <= 2.5) {
    cls = 'yellow'; icon = '⚠️';
    text = 'بعض الأعراض تستحق المتابعة. أخبري طبيبتك في الموعد القادم. احرصي على الراحة والترطيب.';
  } else {
    cls = 'red'; icon = '🚨';
    text = 'أعراض تستدعي الانتباه. يُنصح بالتواصل مع طبيبك في أقرب وقت.';
  }
  
  resultEl.className = `ai-symptom-result ${cls} pc-animate`;
  resultEl.innerHTML = `<span style="font-size:18px;flex-shrink:0">${icon}</span><div>${text}</div>`;
  resultEl.style.display = 'flex';
}

/** Toggle reminder done state */
function pcToggleReminder(el) {
  el.classList.toggle('done');
  el.innerHTML = el.classList.contains('done') ? '✓' : '';
}

/** Pregnancy AI chat */
async function pcSendPregChat() {
  const input = document.getElementById('pc-preg-chat-in');
  if (!input || !input.value.trim()) return;
  const msg = input.value.trim();
  input.value = '';
  
  const msgs = document.getElementById('pc-preg-chat-msgs');
  if (!msgs) return;
  
  // Add user message
  const userEl = document.createElement('div');
  userEl.className = 'pc-chat-msg user';
  userEl.textContent = msg;
  msgs.appendChild(userEl);
  msgs.scrollTop = msgs.scrollHeight;
  
  // Loading
  const loadEl = document.createElement('div');
  loadEl.className = 'pc-chat-msg bot';
  loadEl.innerHTML = '<div class="pc-loading"><div class="pc-spinner"></div> يفكر...</div>';
  msgs.appendChild(loadEl);
  msgs.scrollTop = msgs.scrollHeight;
  
  try {
    // ⚡ AI_INTEGRATION: Replace mock response with actual Gemini API call
    // Example call:
    // const response = await pcCallGemini(
    //   `أنت مساعد طبي متخصص في متابعة الحمل. 
    //    الحامل في الأسبوع ${PC_CONFIG.CURRENT_WEEK}. 
    //    أجب باللغة العربية بشكل مختصر ومشجع.
    //    السؤال: ${msg}`,
    //   PC_CONFIG.GEMINI_API_KEY
    // );
    
    const mockResponses = {
      'غثيان': 'الغثيان شائع في بداية الحمل. جربي تناول الزنجبيل، واشربي الماء بكميات صغيرة متكررة. تناولي بسكويت جاف قبل النهوض صباحاً. 🌱',
      'حركة': `في الأسبوع ${PC_CONFIG.CURRENT_WEEK} حركة الجنين طبيعية جداً! عدي الحركات: 10 حركات خلال ساعتين = طبيعي. إذا قلّت الحركة تواصلي مع طبيبتك. 🩺`,
      'ألم': 'بعض الآلام الخفيفة طبيعية. لكن ألم شديد أو مستمر يستوجب مراجعة الطبيب فوراً. 💊',
    };
    
    const found = Object.keys(mockResponses).find(k => msg.includes(k));
    const reply = found ? mockResponses[found] : 
      `شكراً لسؤالك عن "${msg}". 
       في الأسبوع ${PC_CONFIG.CURRENT_WEEK} من الحمل، أنصحك باستشارة طبيبتك للحصول على إجابة دقيقة خاصة بحالتك. 🩺
       
       لتفعيل الذكاء الاصطناعي الكامل، أضف Gemini API key في ملف الإعدادات.`;
    
    loadEl.textContent = reply;
  } catch (err) {
    loadEl.textContent = 'حدث خطأ في الاتصال. تحقق من إعدادات الشبكة.';
    console.error('AI Chat error:', err);
  }
  
  msgs.scrollTop = msgs.scrollHeight;
}

/** Add chip question to pregnancy chat */
function pcPregChip(text) {
  const input = document.getElementById('pc-preg-chat-in');
  if (input) { input.value = text; pcSendPregChat(); }
}

/* ──────────────────────────────────────────
   CHRONIC DISEASE TAB
────────────────────────────────────────── */

let pcSelectedDisease = null;
let pcReadingsData = {
  diabetes: { labels: [], fasting: [], postMeal: [] },
  bp: { labels: [], sys: [], dia: [] },
  heart: { labels: [], pulse: [] },
};

/** Select a disease card */
function pcSelectDisease(disease) {
  pcSelectedDisease = disease;
  
  // Update card UI
  document.querySelectorAll('#VPR .disease-card').forEach(c => c.classList.remove('selected'));
  const card = document.querySelector(`#VPR [data-disease="${disease}"]`);
  if (card) card.classList.add('selected');
  
  // Hide all panels
  document.querySelectorAll('#VPR .disease-form-panel').forEach(p => p.classList.remove('active'));
  
  // Show selected panel
  const panel = document.getElementById(`pc-form-${disease}`);
  if (panel) {
    panel.classList.add('active');
    panel.classList.remove('pc-animate');
    void panel.offsetWidth;
    panel.classList.add('pc-animate');
  }
  
  // Update chart
  pcRenderChart(disease);
  pcUpdateRiskMeter(disease);
}

/** Save disease readings */
function pcSaveReadings(disease) {
  const now = new Date().toLocaleDateString('ar-DZ', { month: 'short', day: 'numeric' });
  
  if (disease === 'diabetes') {
    const f = parseFloat(document.getElementById('pc-dia-fasting')?.value) || 0;
    const p = parseFloat(document.getElementById('pc-dia-postmeal')?.value) || 0;
    pcReadingsData.diabetes.labels.push(now);
    pcReadingsData.diabetes.fasting.push(f);
    pcReadingsData.diabetes.postMeal.push(p);
    if (pcReadingsData.diabetes.labels.length > 7) {
      pcReadingsData.diabetes.labels.shift();
      pcReadingsData.diabetes.fasting.shift();
      pcReadingsData.diabetes.postMeal.shift();
    }
  } else if (disease === 'bp') {
    const s = parseFloat(document.getElementById('pc-bp-sys')?.value) || 0;
    const d = parseFloat(document.getElementById('pc-bp-dia')?.value) || 0;
    pcReadingsData.bp.labels.push(now);
    pcReadingsData.bp.sys.push(s);
    pcReadingsData.bp.dia.push(d);
    if (pcReadingsData.bp.labels.length > 7) {
      pcReadingsData.bp.labels.shift();
      pcReadingsData.bp.sys.shift();
      pcReadingsData.bp.dia.shift();
    }
  }
  
  pcRenderChart(disease);
  pcUpdateRiskMeter(disease);
  
  // Use existing sa() toast if available
  if (typeof sa === 'function') sa('تم حفظ القراءات ✓');
}

/** Render mini chart for disease readings */
function pcRenderChart(disease) {
  const canvas = document.getElementById('pc-chart-canvas');
  if (!canvas) return;
  
  const ctx = canvas.getContext('2d');
  const w = canvas.width = canvas.offsetWidth || 280;
  const h = canvas.height = 140;
  ctx.clearRect(0, 0, w, h);
  
  let labels = [], datasets = [];
  
  if (disease === 'diabetes') {
    labels = pcReadingsData.diabetes.labels.length > 0 
      ? pcReadingsData.diabetes.labels 
      : ['يوم 1','يوم 2','يوم 3','يوم 4','يوم 5'];
    datasets = [
      { color: '#00b4d8', data: pcReadingsData.diabetes.fasting.length > 0 ? pcReadingsData.diabetes.fasting : [90,95,88,102,87] },
      { color: '#f59e0b', data: pcReadingsData.diabetes.postMeal.length > 0 ? pcReadingsData.diabetes.postMeal : [130,145,120,160,125] },
    ];
  } else if (disease === 'bp') {
    labels = pcReadingsData.bp.labels.length > 0
      ? pcReadingsData.bp.labels
      : ['يوم 1','يوم 2','يوم 3','يوم 4','يوم 5'];
    datasets = [
      { color: '#ef4444', data: pcReadingsData.bp.sys.length > 0 ? pcReadingsData.bp.sys : [120,125,118,130,122] },
      { color: '#0077b6', data: pcReadingsData.bp.dia.length > 0 ? pcReadingsData.bp.dia : [80,82,78,85,79] },
    ];
  } else if (disease === 'heart') {
    labels = ['يوم 1','يوم 2','يوم 3','يوم 4','يوم 5'];
    datasets = [{ color: '#f72585', data: [72,75,70,78,73] }];
  } else {
    labels = ['يوم 1','يوم 2','يوم 3','يوم 4','يوم 5'];
    datasets = [{ color: '#06d6a0', data: [75,80,72,85,77] }];
  }
  
  // Draw grid lines
  ctx.strokeStyle = 'rgba(0,0,0,0.06)';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = 15 + (h - 30) / 4 * i;
    ctx.beginPath(); ctx.moveTo(30, y); ctx.lineTo(w - 10, y); ctx.stroke();
  }
  
  // Draw each dataset
  datasets.forEach(ds => {
    const vals = ds.data;
    const max = Math.max(...vals) * 1.15;
    const min = Math.min(...vals) * 0.85;
    const range = max - min || 1;
    const xStep = (w - 40) / (vals.length - 1 || 1);
    
    // Fill gradient
    const grad = ctx.createLinearGradient(0, 15, 0, h - 15);
    grad.addColorStop(0, ds.color + '40');
    grad.addColorStop(1, ds.color + '05');
    
    ctx.beginPath();
    vals.forEach((v, i) => {
      const x = 30 + i * xStep;
      const y = 15 + (h - 30) * (1 - (v - min) / range);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.lineTo(30 + (vals.length - 1) * xStep, h - 15);
    ctx.lineTo(30, h - 15);
    ctx.closePath();
    ctx.fillStyle = grad;
    ctx.fill();
    
    // Line
    ctx.beginPath();
    ctx.strokeStyle = ds.color;
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    vals.forEach((v, i) => {
      const x = 30 + i * xStep;
      const y = 15 + (h - 30) * (1 - (v - min) / range);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();
    
    // Dots
    vals.forEach((v, i) => {
      const x = 30 + i * xStep;
      const y = 15 + (h - 30) * (1 - (v - min) / range);
      ctx.beginPath();
      ctx.arc(x, y, 4, 0, Math.PI * 2);
      ctx.fillStyle = '#fff';
      ctx.fill();
      ctx.strokeStyle = ds.color;
      ctx.lineWidth = 2;
      ctx.stroke();
    });
  });
  
  // X labels
  ctx.fillStyle = '#9ca3af';
  ctx.font = '9px Cairo, sans-serif';
  ctx.textAlign = 'center';
  const xStep = (w - 40) / (labels.length - 1 || 1);
  labels.forEach((l, i) => {
    ctx.fillText(l, 30 + i * xStep, h - 3);
  });
}

/** Update AI risk meter */
function pcUpdateRiskMeter(disease) {
  const needle = document.getElementById('pc-risk-needle');
  const badge = document.getElementById('pc-risk-badge');
  const expl = document.getElementById('pc-risk-explanation');
  if (!needle || !badge) return;
  
  // ⚡ AI_INTEGRATION: Replace this with Gemini API call for real risk analysis
  // Send: disease type + latest readings + HbA1c + symptoms
  // Get: risk level (0-100), status text, explanation
  
  let risk = 0, statusClass = 'green', statusText = '✅ مستقر', explText = '';
  
  if (disease === 'diabetes') {
    const fasting = parseFloat(document.getElementById('pc-dia-fasting')?.value) || 0;
    const hba1c = parseFloat(document.getElementById('pc-dia-hba1c')?.value) || 0;
    if (fasting > 0) {
      if (fasting > 200 || hba1c > 8) { risk = 80; statusClass = 'red'; statusText = '🚨 خطر'; explText = 'مستوى السكر مرتفع جداً. يُنصح بمراجعة الطبيب فوراً.'; }
      else if (fasting > 130 || hba1c > 6.5) { risk = 50; statusClass = 'yellow'; statusText = '⚠️ انتبه'; explText = 'مستوى السكر مرتفع قليلاً. راقب نظامك الغذائي.'; }
      else { risk = 20; statusClass = 'green'; statusText = '✅ مستقر'; explText = 'مستوى السكر ضمن الحدود الطبيعية. استمر بالمحافظة على نمطك الصحي.'; }
    } else {
      risk = 15; statusClass = 'green'; statusText = '✅ مستقر'; explText = 'أدخل قراءاتك للحصول على تحليل دقيق.';
    }
  } else if (disease === 'bp') {
    const sys = parseFloat(document.getElementById('pc-bp-sys')?.value) || 0;
    if (sys > 0) {
      if (sys > 160) { risk = 85; statusClass = 'red'; statusText = '🚨 خطر'; explText = 'ضغط الدم مرتفع جداً. مراجعة طبية عاجلة مطلوبة.'; }
      else if (sys > 140) { risk = 55; statusClass = 'yellow'; statusText = '⚠️ انتبه'; explText = 'ضغط الدم فوق الطبيعي. قلل الملح وراقب التوتر.'; }
      else { risk = 18; statusClass = 'green'; statusText = '✅ مستقر'; explText = 'ضغط الدم طبيعي. استمر بنمط حياتك الصحي.'; }
    } else {
      risk = 18; statusClass = 'green'; statusText = '✅ مستقر'; explText = 'أدخل قراءات الضغط للتحليل.';
    }
  } else {
    risk = 25; statusClass = 'green'; statusText = '✅ مستقر'; explText = 'أدخل قراءاتك للحصول على تحليل دقيق من الذكاء الاصطناعي.';
  }
  
  // Animate needle: -80deg (green) to +80deg (red)
  const angle = -80 + (risk / 100) * 160;
  needle.style.setProperty('--needle-angle', angle + 'deg');
  
  badge.className = `risk-status-badge ${statusClass}`;
  badge.textContent = statusText;
  if (expl) expl.textContent = explText;
}

/** Chronic AI chat */
async function pcSendChronicChat() {
  const input = document.getElementById('pc-ch-chat-in');
  if (!input || !input.value.trim()) return;
  const msg = input.value.trim();
  input.value = '';
  
  const msgs = document.getElementById('pc-ch-chat-msgs');
  if (!msgs) return;
  
  const userEl = document.createElement('div');
  userEl.className = 'pc-chat-msg user';
  userEl.textContent = msg;
  msgs.appendChild(userEl);
  
  const loadEl = document.createElement('div');
  loadEl.className = 'pc-chat-msg bot';
  loadEl.innerHTML = '<div class="pc-loading"><div class="pc-spinner"></div> يحلل...</div>';
  msgs.appendChild(loadEl);
  msgs.scrollTop = msgs.scrollHeight;
  
  // ⚡ AI_INTEGRATION: Replace mock with actual Gemini API call
  // const context = `أنت طبيب ذكاء اصطناعي متخصص بالأمراض المزمنة.
  //   المرض: ${pcSelectedDisease || 'غير محدد'}
  //   أجب باللغة العربية بشكل مختصر ومفيد.
  //   السؤال: ${msg}`;
  // const reply = await pcCallGemini(context, PC_CONFIG.GEMINI_API_KEY);
  
  setTimeout(() => {
    const replies = {
      'سكر': 'للتحكم بسكر الدم: تناول وجبات صغيرة متكررة، تجنب السكريات المكررة، مارس رياضة خفيفة 30 دقيقة يومياً. 💉',
      'ضغط': 'لتخفيض الضغط: قلل الملح، ابتعد عن التدخين، مارس التنفس العميق، اشرب الماء الكافي. ❤️',
      'دواء': 'التزم بمواعيد الدواء بانتظام. لا تتوقف عن الدواء دون استشارة طبيبك. 💊',
    };
    const found = Object.keys(replies).find(k => msg.includes(k));
    loadEl.textContent = found ? replies[found] : `سؤال مهم عن "${msg}". 
    الإجابة الدقيقة تعتمد على وضعك الصحي الكامل. 
    لتفعيل الذكاء الاصطناعي الكامل، أضف Gemini API key في إعدادات النظام. 🩺`;
    msgs.scrollTop = msgs.scrollHeight;
  }, 1200);
}

function pcChronicChip(text) {
  const input = document.getElementById('pc-ch-chat-in');
  if (input) { input.value = text; pcSendChronicChat(); }
}

/* ──────────────────────────────────────────
   GEMINI API HELPER
   ⚡ AI_INTEGRATION: Main API call function
────────────────────────────────────────── */
async function pcCallGemini(prompt, apiKey) {
  // ⚡ AI_INTEGRATION: This function is ready — just provide a valid API key
  const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=${apiKey}`;
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contents: [{ parts: [{ text: prompt }] }],
      generationConfig: { temperature: 0.7, maxOutputTokens: 300 },
      safetySettings: [
        { category: "HARM_CATEGORY_HARASSMENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
        { category: "HARM_CATEGORY_DANGEROUS_CONTENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" }
      ]
    })
  });
  if (!response.ok) throw new Error(`Gemini API error: ${response.status}`);
  const data = await response.json();
  return data.candidates?.[0]?.content?.parts?.[0]?.text || 'لا توجد استجابة من الذكاء الاصطناعي.';
}

/* ──────────────────────────────────────────
   INIT
────────────────────────────────────────── */
function pcInit() {
  // Build timeline
  pcBuildTimeline();
  
  // Update hero
  pcUpdateHero();
  
  // Initial symptom evaluate
  pcEvaluateSymptoms();
  
  // Pre-select disease based on patient data
  // ⚡ AI_INTEGRATION: Parse PC_CONFIG.CHRONIC_DISEASES from DB
  const cdStr = (PC_CONFIG.CHRONIC_DISEASES || '').toLowerCase();
  if (cdStr.includes('سكر') || cdStr.includes('diabetes')) {
    pcSelectDisease('diabetes');
  } else if (cdStr.includes('ضغط') || cdStr.includes('pressure') || cdStr.includes('hyper')) {
    pcSelectDisease('bp');
  } else if (cdStr.includes('قلب') || cdStr.includes('heart')) {
    pcSelectDisease('heart');
  }
  
  // Resize chart on window resize
  window.addEventListener('resize', () => {
    if (pcSelectedDisease) pcRenderChart(pcSelectedDisease);
  });
  
  // Keyboard support for chats
  document.getElementById('pc-preg-chat-in')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') pcSendPregChat();
  });
  document.getElementById('pc-ch-chat-in')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') pcSendChronicChat();
  });
}

// Run init when VPR section becomes visible
// Hook into existing navigation system
const _origShowView = window.showView || null;
document.addEventListener('DOMContentLoaded', function () {
  // Watch for VPR becoming visible
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(m => {
      if (m.target.id === 'VPR' && m.target.style.display !== 'none') {
        pcInit();
        observer.disconnect();
      }
    });
  });
  const vpr = document.getElementById('VPR');
  if (vpr) {
    observer.observe(vpr, { attributes: true, attributeFilter: ['style'] });
    // Also init if already visible
    if (vpr.style.display !== 'none') pcInit();
  }
  
  // Also expose globally for direct calls
  window.pcInit = pcInit;
});
