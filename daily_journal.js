/* ================================================================
   MedChifaGiz — daily_journal.js
   Daily Health Tracker Logic — "يومياتي"
   ================================================================ */

(function(){
'use strict';

/* ──────────────────────────────────────────────────────────────
   STATE
────────────────────────────────────────────────────────────── */
var DJ = {
  mood: 0,
  symptoms: [],
  pain: 0,
  medication: '',
  activity: '',
  sleep_hours: 7,
  sleep_quality: '',
  water_cups: 0,
  nutrition: [],
  saved: false
};

/* ──────────────────────────────────────────────────────────────
   INIT — called when VDL view becomes active
────────────────────────────────────────────────────────────── */
function djInit() {
  djRenderDate();
  djInitMood();
  djInitSymptoms();
  djInitPainSlider();
  djInitOptionBtns();
  djInitSleep();
  djInitWater();
  djInitNutrition();
  djLoadHistory();   // populate history tab
}

/* ──────────────────────────────────────────────────────────────
   DATE BADGE
────────────────────────────────────────────────────────────── */
function djRenderDate() {
  var el = document.getElementById('DJ_DATE');
  if (!el) return;
  var d = new Date();
  var days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  el.textContent = days[d.getDay()] + ' ' + d.toLocaleDateString('ar-DZ');
}

/* ──────────────────────────────────────────────────────────────
   MOOD
────────────────────────────────────────────────────────────── */
function djInitMood() {
  document.querySelectorAll('.DJ-MOOD-BTN').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.DJ-MOOD-BTN').forEach(function(b){ b.classList.remove('DJ-SEL'); });
      btn.classList.add('DJ-SEL');
      DJ.mood = parseInt(btn.getAttribute('data-mood'));
    });
  });
}

/* ──────────────────────────────────────────────────────────────
   SYMPTOMS
────────────────────────────────────────────────────────────── */
function djInitSymptoms() {
  document.querySelectorAll('.DJ-SYM').forEach(function(item) {
    item.addEventListener('click', function() {
      item.classList.toggle('DJ-SEL');
      var sym = item.getAttribute('data-sym');
      var idx = DJ.symptoms.indexOf(sym);
      if (idx === -1) DJ.symptoms.push(sym);
      else DJ.symptoms.splice(idx, 1);
    });
  });
}

/* ──────────────────────────────────────────────────────────────
   PAIN SLIDER
────────────────────────────────────────────────────────────── */
function djInitPainSlider() {
  var slider = document.getElementById('DJ_PAIN');
  var display = document.getElementById('DJ_PAIN_VAL');
  if (!slider || !display) return;
  function updatePain() {
    DJ.pain = parseInt(slider.value);
    display.textContent = DJ.pain;
    var colors = ['#06d6a0','#06d6a0','#06d6a0','#3fce88','#a3d977','#f59e0b','#f97316','#ef4444','#dc2626','#b91c1c','#991b1b'];
    display.style.color = colors[DJ.pain] || '#1a2340';
  }
  slider.addEventListener('input', updatePain);
  updatePain();
}

/* ──────────────────────────────────────────────────────────────
   OPTION BUTTONS (medication / activity)
────────────────────────────────────────────────────────────── */
function djInitOptionBtns() {
  // Medication
  djBindGroup('DJ-MED-BTN', function(btn, val) {
    DJ.medication = val;
    var colorMap = { 'yes':'DJ-SEL-GREEN', 'late':'DJ-SEL-ORANGE', 'no':'DJ-SEL-RED' };
    btn.className = btn.className.replace(/DJ-SEL(-\w+)?/g,'').trim() + ' ' + (colorMap[val] || 'DJ-SEL');
  });
  // Activity
  djBindGroup('DJ-ACT-BTN2', function(btn, val) {
    DJ.activity = val;
    var colorMap = { 'low':'DJ-SEL-ORANGE', 'medium':'DJ-SEL', 'high':'DJ-SEL-GREEN' };
    btn.className = btn.className.replace(/DJ-SEL(-\w+)?/g,'').trim() + ' ' + (colorMap[val] || 'DJ-SEL');
  });
}

function djBindGroup(cls, cb) {
  var btns = document.querySelectorAll('.' + cls);
  btns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      btns.forEach(function(b){ b.className = b.className.replace(/DJ-SEL(-\w+)?/g,'').trim(); });
      var val = btn.getAttribute('data-val');
      cb(btn, val);
    });
  });
}

/* ──────────────────────────────────────────────────────────────
   SLEEP
────────────────────────────────────────────────────────────── */
function djInitSleep() {
  var numEl = document.getElementById('DJ_SLEEP_NUM');
  if (!numEl) return;
  numEl.textContent = DJ.sleep_hours;

  document.getElementById('DJ_SLEEP_UP') && document.getElementById('DJ_SLEEP_UP').addEventListener('click', function() {
    if (DJ.sleep_hours < 24) { DJ.sleep_hours++; numEl.textContent = DJ.sleep_hours; }
  });
  document.getElementById('DJ_SLEEP_DN') && document.getElementById('DJ_SLEEP_DN').addEventListener('click', function() {
    if (DJ.sleep_hours > 0) { DJ.sleep_hours--; numEl.textContent = DJ.sleep_hours; }
  });

  // Sleep quality buttons
  djBindGroup('DJ-SQ-BTN', function(btn, val) {
    DJ.sleep_quality = val;
    btn.className = btn.className.replace(/DJ-SEL(-\w+)?/g,'').trim() + ' DJ-SEL';
  });
}

/* ──────────────────────────────────────────────────────────────
   WATER CUPS
────────────────────────────────────────────────────────────── */
function djInitWater() {
  var cups = document.querySelectorAll('.DJ-CUP');
  var numEl = document.getElementById('DJ_WATER_NUM');
  function updateCups(n) {
    DJ.water_cups = n;
    if (numEl) numEl.textContent = n;
    cups.forEach(function(c, i) {
      c.classList.toggle('ON', i < n);
    });
  }
  cups.forEach(function(cup, i) {
    cup.addEventListener('click', function() {
      updateCups(DJ.water_cups === i + 1 ? i : i + 1);
    });
  });
}

/* ──────────────────────────────────────────────────────────────
   NUTRITION
────────────────────────────────────────────────────────────── */
function djInitNutrition() {
  document.querySelectorAll('.DJ-NUT-BTN').forEach(function(btn) {
    btn.addEventListener('click', function() {
      btn.classList.toggle('DJ-SEL');
      var val = btn.getAttribute('data-val');
      var idx = DJ.nutrition.indexOf(val);
      if (idx === -1) DJ.nutrition.push(val);
      else DJ.nutrition.splice(idx, 1);
    });
  });
}

/* ──────────────────────────────────────────────────────────────
   TAB SWITCHING
────────────────────────────────────────────────────────────── */
window.djTab = function(tab) {
  document.querySelectorAll('.DJ-TAB').forEach(function(t){ t.classList.remove('A'); });
  document.querySelectorAll('.DJ-TABPANEL').forEach(function(p){ p.style.display='none'; });
  var activeTab = document.querySelector('.DJ-TAB[data-tab="'+tab+'"]');
  var activePanel = document.getElementById('DJ_PANEL_'+tab);
  if (activeTab) activeTab.classList.add('A');
  if (activePanel) activePanel.style.display = 'block';
  if (tab === 'history') djLoadHistory();
};

/* ──────────────────────────────────────────────────────────────
   COLLECT ALL FORM DATA
────────────────────────────────────────────────────────────── */
function djCollect() {
  return {
    mood:          DJ.mood,
    feel_text:     (document.getElementById('DL_FEEL')||{}).value || '',
    bp:            (document.getElementById('DL_BP')||{}).value || '',
    sugar:         (document.getElementById('DL_SG')||{}).value || '',
    heart_rate:    (document.getElementById('DL_HR')||{}).value || '',
    temp:          (document.getElementById('DL_TM')||{}).value || '',
    spo2:          (document.getElementById('DL_SPO2')||{}).value || '',
    weight:        (document.getElementById('DL_WEIGHT')||{}).value || '',
    symptoms:      DJ.symptoms.slice(),
    pain:          DJ.pain,
    medication:    DJ.medication,
    sleep_hours:   DJ.sleep_hours,
    sleep_quality: DJ.sleep_quality,
    water_cups:    DJ.water_cups,
    activity:      DJ.activity,
    nutrition:     DJ.nutrition.slice(),
    notes:         (document.getElementById('DL_NOTE')||{}).value || ''
  };
}

/* ──────────────────────────────────────────────────────────────
   HEALTH SCORE ENGINE
────────────────────────────────────────────────────────────── */
function djCalcScore(d) {
  var score = 100;
  var alerts = [], warnings = [], tips = [];

  // BP
  if (d.bp) {
    var p = d.bp.split('/'); var sys = parseInt(p[0]), dia = parseInt(p[1]);
    if (sys >= 180 || dia >= 120) { score -= 30; alerts.push('🚨 ضغط الدم مرتفع جداً ('+d.bp+'). توجّه للطوارئ فوراً!'); }
    else if (sys >= 140 || dia >= 90) { score -= 15; warnings.push('⚠️ ضغط الدم مرتفع ('+d.bp+'). استشر طبيبك في أقرب وقت.'); }
    else if (sys < 90 || dia < 60)    { score -= 10; warnings.push('⚠️ ضغط الدم منخفض ('+d.bp+'). استرح وتناول السوائل.'); }
    else tips.push('✅ ضغط الدم طبيعي ('+d.bp+')');
  }

  // Sugar
  if (d.sugar) {
    var sg = parseInt(d.sugar);
    if (sg < 54)         { score -= 30; alerts.push('🚨 انخفاض حاد في السكر ('+d.sugar+'). تناول سكراً فوراً!'); }
    else if (sg < 70)    { score -= 15; warnings.push('⚠️ سكر منخفض ('+d.sugar+'). تناول طعاماً خفيفاً.'); }
    else if (sg > 200)   { score -= 20; warnings.push('⚠️ سكر مرتفع جداً ('+d.sugar+'). تواصل مع طبيبك.'); }
    else if (sg > 126)   { score -= 10; warnings.push('⚠️ سكر مرتفع ('+d.sugar+'). تجنب السكريات.'); }
    else tips.push('✅ سكر الدم ضمن المعدل ('+d.sugar+')');
  }

  // Heart rate
  if (d.heart_rate) {
    var hr = parseInt(d.heart_rate);
    if (hr > 120 || hr < 45) { score -= 20; alerts.push('🚨 نبض غير طبيعي ('+d.heart_rate+'). استشر طبيبك فوراً.'); }
    else if (hr > 100)       { score -= 8;  warnings.push('⚠️ النبض مرتفع ('+d.heart_rate+'). استرح وتهدّأ.'); }
    else if (hr < 55)        { score -= 5;  warnings.push('⚠️ النبض منخفض ('+d.heart_rate+'). راقب حالتك.'); }
    else tips.push('✅ النبض طبيعي ('+d.heart_rate+')');
  }

  // Temperature
  if (d.temp) {
    var tm = parseFloat(d.temp);
    if (tm >= 40)        { score -= 25; alerts.push('🚨 حمى شديدة ('+d.temp+'°). راجع الطوارئ!'); }
    else if (tm >= 38.5) { score -= 12; warnings.push('🌡️ حمى ('+d.temp+'°). تناول خافض الحرارة واشرب الماء.'); }
    else if (tm >= 37.5) { score -= 5;  warnings.push('🌡️ ارتفاع بسيط في الحرارة ('+d.temp+'°). راقب الوضع.'); }
    else if (tm > 0) tips.push('✅ درجة الحرارة طبيعية ('+d.temp+'°)');
  }

  // SpO2
  if (d.spo2) {
    var sp = parseInt(d.spo2);
    if (sp < 90)      { score -= 25; alerts.push('🚨 نسبة أكسجين منخفضة جداً ('+d.spo2+'%). طوارئ فورية!'); }
    else if (sp < 94) { score -= 12; warnings.push('⚠️ نسبة الأكسجين منخفضة ('+d.spo2+'%). استشر طبيبك.'); }
    else tips.push('✅ نسبة الأكسجين جيدة ('+d.spo2+'%)');
  }

  // Symptoms
  var dangerSym = ['chest_pain','breathless'];
  dangerSym.forEach(function(s){
    if (d.symptoms.indexOf(s) > -1) { score -= 20; alerts.push('🚨 عرض خطر: '+(s==='chest_pain'?'ألم في الصدر':'ضيق في التنفس')+'. راجع الطبيب فوراً.'); }
  });
  var warnSym = ['severe_fatigue','swelling'];
  warnSym.forEach(function(s){
    if (d.symptoms.indexOf(s) > -1) { score -= 8; warnings.push('⚠️ '+(s==='severe_fatigue'?'تعب شديد':'تورم الأرجل')+' — يستحق المتابعة.'); }
  });
  if (d.symptoms.length >= 4) { score -= 10; warnings.push('⚠️ أعراض متعددة — راقب حالتك عن كثب.'); }

  // Pain
  if (d.pain >= 8)     { score -= 15; alerts.push('🚨 مستوى ألم عالٍ جداً ('+d.pain+'/10). استشر طبيبك.'); }
  else if (d.pain >= 5){ score -= 8;  warnings.push('⚠️ مستوى ألم متوسط ('+d.pain+'/10). راقب الوضع.'); }
  else if (d.pain > 0) { score -= 3; }

  // Mood
  if (d.mood === 1) { score -= 8; warnings.push('😔 حالتك النفسية ليست جيدة. حاول التحدث مع شخص تثق به.'); }
  else if (d.mood === 2) { score -= 4; }
  else if (d.mood === 5) { tips.push('😊 مزاجك رائع اليوم! استمر على هذا الحال.'); }

  // Medication
  if (d.medication === 'no')   { score -= 10; warnings.push('💊 لم تتناول دواءك اليوم. الالتزام بالعلاج مهم جداً.'); }
  else if (d.medication === 'late') { score -= 5; tips.push('⏰ تأخرت في تناول دوائك. حاول الالتزام بالمواعيد.'); }
  else if (d.medication === 'yes') { tips.push('💊 ممتاز! تناولت دواءك في وقته.'); }

  // Sleep
  if (d.sleep_hours < 5)       { score -= 12; warnings.push('😴 نوم قليل جداً ('+d.sleep_hours+' ساعات). يؤثر على صحتك.'); }
  else if (d.sleep_hours < 7)  { score -= 5;  tips.push('😴 حاول النوم 7-8 ساعات لراحة أفضل.'); }
  else if (d.sleep_hours >= 7 && d.sleep_hours <= 9) { tips.push('✅ نوم كافٍ ('+d.sleep_hours+' ساعات).'); }

  // Water
  if (d.water_cups < 4)        { score -= 8;  warnings.push('💧 تناولت كميات ماء قليلة ('+d.water_cups+' أكواب). اشرب المزيد.'); }
  else if (d.water_cups >= 8)  { tips.push('💧 ممتاز! شربت كمية ماء كافية ('+d.water_cups+' أكواب).'); }
  else { tips.push('💧 شربت '+d.water_cups+' أكواب. حاول الوصول لـ 8.'); }

  // Activity
  if (d.activity === 'high')   { tips.push('🏃 نشاطك بدني ممتاز اليوم!'); }
  else if (d.activity === 'low'){ score -= 5; tips.push('🚶 حاول ممارسة نشاط خفيف 30 دقيقة يومياً.'); }

  // Nutrition
  if (d.nutrition.indexOf('healthy') > -1) { tips.push('🥗 تغذيتك صحية اليوم، استمر!'); }
  if (d.nutrition.indexOf('no_appetite') > -1) { score -= 8; warnings.push('🍽️ فقدان الشهية قد يكون علامة مثيرة للقلق.'); }
  if (d.nutrition.indexOf('high_sugar') > -1)  { score -= 5; tips.push('🍬 قلّل من السكريات للحفاظ على صحتك.'); }
  if (d.nutrition.indexOf('high_fat') > -1)    { score -= 4; tips.push('🧈 تجنب الدهون الزائدة لصحة قلبك.'); }

  score = Math.max(0, Math.min(100, score));

  return { score: score, alerts: alerts, warnings: warnings, tips: tips };
}

/* ──────────────────────────────────────────────────────────────
   SAVE TO SERVER (AJAX)
────────────────────────────────────────────────────────────── */
function djSaveToServer(data, score, callback) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'save_daily_journal.php', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      try {
        var resp = JSON.parse(xhr.responseText);
        callback(resp);
      } catch(e) { callback({ ok: false }); }
    }
  };
  xhr.send(JSON.stringify({ entry: data, score: score }));
}

/* ──────────────────────────────────────────────────────────────
   MAIN SAVE & ANALYZE — AI-powered via Groq, fallback rule-based
────────────────────────────────────────────────────────────── */
window.analyzeDaily = function() {
  var data = djCollect();
  var hasData = data.bp || data.sugar || data.heart_rate || data.temp ||
                data.spo2 || data.mood || data.symptoms.length || data.feel_text;
  var dlr = document.getElementById('DLR');
  if (!dlr) return;

  if (!hasData) {
    dlr.style.display = 'block';
    dlr.innerHTML = '<div class="DJ-CARD"><div style="font-weight:800;color:#dc2626;margin-bottom:6px;">⚠️ يرجى إدخال البيانات</div><p style="font-size:12px;color:#4a5568;">أدخل على الأقل حالتك العامة أو مؤشراً حيوياً.</p></div>';
    return;
  }

  // Show spinner
  dlr.style.display = 'block';
  dlr.innerHTML = '<div class="DJ-CARD DJ-LOADING"><div class="DJ-SPINNER"></div><div style="font-size:12px;color:#9ca3af;">جاري تحليل حالتك الصحية بالذكاء الاصطناعي...</div></div>';

  // Step 1: calculate rule-based score (used for saving + AI fallback)
  var ruleResult = djCalcScore(data);

  // Step 2: save to DB first (non-blocking, same as before)
  djSaveToServer(data, ruleResult.score, function() {});

  // Step 3: call AI analysis endpoint
  djAnalyzeWithAI(data, ruleResult.score, function(aiResult) {
    if (aiResult && aiResult.ok) {
      // AI succeeded → render AI result
      djRenderAIResults(data, ruleResult.score, aiResult.data);
    } else {
      // AI failed → silently fallback to rule-based
      djRenderResults(data, ruleResult);
    }
    DJ.saved = true;
    djLoadHistory();
  });
};

/* ──────────────────────────────────────────────────────────────
   AI ANALYSIS XHR  → analyze_daily_ai.php
────────────────────────────────────────────────────────────── */
function djAnalyzeWithAI(data, score, callback) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'analyze_daily_ai.php', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.timeout = 25000; // 25s timeout — Groq is fast but give margin
  xhr.ontimeout = function() { callback(null); };
  xhr.onerror   = function() { callback(null); };
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      try {
        var resp = JSON.parse(xhr.responseText);
        callback(resp);
      } catch(e) { callback(null); }
    }
  };
  xhr.send(JSON.stringify({ entry: data, score: score }));
}

/* ──────────────────────────────────────────────────────────────
   RENDER AI RESULTS  (same visual structure, AI content)
────────────────────────────────────────────────────────────── */
function djRenderAIResults(data, sc, ai) {
  var dlr = document.getElementById('DLR');
  if (!dlr) return;

  // risk_level → colour palette (matches existing score-based palette)
  var riskMap = {
    'low'    : { ring: 'linear-gradient(135deg,#06d6a0,#0a9e77)', bar: 'linear-gradient(90deg,#06d6a0,#00b4d8)', txt: 'حالتك الصحية جيدة 😊' },
    'medium' : { ring: 'linear-gradient(135deg,#f59e0b,#d97706)', bar: 'linear-gradient(90deg,#f59e0b,#ef4444)', txt: 'تحتاج بعض الاهتمام ⚠️' },
    'high'   : { ring: 'linear-gradient(135deg,#ef4444,#b91c1c)', bar: 'linear-gradient(90deg,#ef4444,#991b1b)', txt: 'يلزم التدخل الطبي 🚨' }
  };
  var risk      = riskMap[ai.risk_level] || riskMap['medium'];
  var moodLabels = {5:'ممتاز 😁',4:'جيد 🙂',3:'عادي 😐',2:'متعب 😔',1:'سيء 😞',0:''};

  var html = '<div class="DJ-CARD">';

  // Score header (identical layout to djRenderResults)
  html += '<div class="DJ-RESULT-HEADER">';
  html += '<div class="DJ-SCORE-RING-WRAP">';
  html += '<div class="DJ-SCORE-RING" style="background:'+risk.ring+';">'+sc+'<small style="font-size:10px;">%</small></div>';
  html += '<div class="DJ-SCORE-LBL">المؤشر الصحي</div>';
  html += '</div>';
  html += '<div style="flex:1;padding-right:12px;">';
  html += '<div style="font-size:14px;font-weight:800;color:#1a2340;margin-bottom:6px;">'+risk.txt+'</div>';
  html += '<div class="DJ-PROGRESS-WRAP"><div style="font-size:10px;color:#9ca3af;margin-bottom:2px;">نسبة المؤشر الصحي</div>';
  html += '<div class="DJ-PROGRESS-TRACK"><div class="DJ-PROGRESS-BAR" id="DJ_PBAR" style="width:0%;background:'+risk.bar+';"></div></div></div>';
  if (data.mood) html += '<div style="font-size:11px;color:#9ca3af;margin-top:5px;">المزاج: <strong>'+(moodLabels[data.mood]||'')+'</strong></div>';
  html += '</div></div>';

  // AI badge
  html += '<div style="display:inline-flex;align-items:center;gap:5px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:20px;padding:3px 10px;font-size:10px;color:#0369a1;margin-bottom:8px;">🤖 تحليل بالذكاء الاصطناعي</div>';

  // AI Summary
  if (ai.summary) {
    html += '<div style="font-size:12px;color:#374151;background:#f8fafc;border-right:3px solid #00b4d8;padding:8px 10px;border-radius:6px;margin-bottom:8px;line-height:1.6;">'+djEscape(ai.summary)+'</div>';
  }

  // Warnings (from AI)
  if (ai.warnings && ai.warnings.length) {
    html += '<div style="font-size:11px;font-weight:800;color:#dc2626;margin:8px 0 5px;">🚨 تحذيرات</div>';
    ai.warnings.forEach(function(w) {
      html += '<div class="DJ-ALERT-ITEM">'+djEscape(w)+'</div>';
    });
  }

  // Recommendations (from AI)
  if (ai.recommendations && ai.recommendations.length) {
    html += '<div style="font-size:11px;font-weight:800;color:#0a9e77;margin:8px 0 5px;">💡 توصيات شخصية</div>';
    ai.recommendations.forEach(function(r) {
      html += '<div class="DJ-TIP-ITEM">'+djEscape(r)+'</div>';
    });
  }

  // Weekly chart (unchanged)
  html += '<div style="font-size:11px;font-weight:800;color:#00b4d8;margin:10px 0 5px;">📊 مقارنة الأسبوع الماضي</div>';
  html += djBuildWeeklyChart(sc);

  // Action buttons (unchanged)
  html += '<div class="DJ-ACTIONS">';
  html += '<button class="DJ-ACT-BTN PDF" onclick="djExportPDF()"><i class="fas fa-file-pdf"></i>حفظ PDF</button>';
  html += '<button class="DJ-ACT-BTN SHARE" onclick="shareDaily()"><i class="fas fa-share-alt"></i>مشاركة الطبيب</button>';
  html += '<button class="DJ-ACT-BTN SOS" onclick="djSOS()"><i class="fas fa-ambulance"></i>طوارئ</button>';
  html += '</div>';

  html += '</div>'; // .DJ-CARD
  dlr.innerHTML = html;

  // Animate progress bar
  setTimeout(function() {
    var bar = document.getElementById('DJ_PBAR');
    if (bar) bar.style.width = sc + '%';
  }, 50);

  // Notification if bad
  if (sc < 60 && typeof addNotification === 'function') {
    var statusTxt = risk.txt;
    addNotification('⚠️ تنبيه صحي من يومياتك', statusTxt, sc >= 50 ? 'd97706' : 'dc2626');
  }
}

/* ──────────────────────────────────────────────────────────────
   XSS HELPER
────────────────────────────────────────────────────────────── */
function djEscape(str) {
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

/* ──────────────────────────────────────────────────────────────
   RENDER RESULTS
────────────────────────────────────────────────────────────── */
function djRenderResults(data, result) {
  var dlr = document.getElementById('DLR');
  if (!dlr) return;

  var sc = result.score;
  var ringColor = sc >= 75 ? 'linear-gradient(135deg,#06d6a0,#0a9e77)'
                : sc >= 50 ? 'linear-gradient(135deg,#f59e0b,#d97706)'
                : 'linear-gradient(135deg,#ef4444,#b91c1c)';
  var barColor  = sc >= 75 ? 'linear-gradient(90deg,#06d6a0,#00b4d8)'
                : sc >= 50 ? 'linear-gradient(90deg,#f59e0b,#ef4444)'
                : 'linear-gradient(90deg,#ef4444,#991b1b)';
  var statusTxt = sc >= 75 ? 'حالتك الصحية جيدة 😊'
                : sc >= 50 ? 'تحتاج بعض الاهتمام ⚠️'
                : 'يلزم التدخل الطبي 🚨';
  var moodLabels = {5:'ممتاز 😁',4:'جيد 🙂',3:'عادي 😐',2:'متعب 😔',1:'سيء 😞',0:''};

  var html = '<div class="DJ-CARD">';
  // Score header
  html += '<div class="DJ-RESULT-HEADER">';
  html += '<div class="DJ-SCORE-RING-WRAP">';
  html += '<div class="DJ-SCORE-RING" style="background:'+ringColor+';">'+sc+'<small style="font-size:10px;">%</small></div>';
  html += '<div class="DJ-SCORE-LBL">المؤشر الصحي</div>';
  html += '</div>';
  html += '<div style="flex:1;padding-right:12px;">';
  html += '<div style="font-size:14px;font-weight:800;color:#1a2340;margin-bottom:6px;">'+statusTxt+'</div>';
  html += '<div class="DJ-PROGRESS-WRAP"><div style="font-size:10px;color:#9ca3af;margin-bottom:2px;">نسبة المؤشر الصحي</div>';
  html += '<div class="DJ-PROGRESS-TRACK"><div class="DJ-PROGRESS-BAR" id="DJ_PBAR" style="width:0%;background:'+barColor+';"></div></div></div>';
  if (data.mood) html += '<div style="font-size:11px;color:#9ca3af;margin-top:5px;">المزاج: <strong>'+moodLabels[data.mood]+'</strong></div>';
  html += '</div></div>';

  // Alerts
  if (result.alerts.length) {
    html += '<div style="font-size:11px;font-weight:800;color:#dc2626;margin:8px 0 5px;">🚨 تحذيرات عاجلة</div>';
    result.alerts.forEach(function(a){ html += '<div class="DJ-ALERT-ITEM">'+a+'</div>'; });
  }
  // Warnings
  if (result.warnings.length) {
    html += '<div style="font-size:11px;font-weight:800;color:#d97706;margin:8px 0 5px;">⚠️ تنبيهات</div>';
    result.warnings.forEach(function(w){ html += '<div class="DJ-WARN-ITEM">'+w+'</div>'; });
  }
  // Tips
  if (result.tips.length) {
    html += '<div style="font-size:11px;font-weight:800;color:#0a9e77;margin:8px 0 5px;">💡 نصائح شخصية</div>';
    result.tips.forEach(function(t){ html += '<div class="DJ-TIP-ITEM">'+t+'</div>'; });
  }

  // Weekly chart
  html += '<div style="font-size:11px;font-weight:800;color:#00b4d8;margin:10px 0 5px;">📊 مقارنة الأسبوع الماضي</div>';
  html += djBuildWeeklyChart(sc);

  // Action buttons
  html += '<div class="DJ-ACTIONS">';
  html += '<button class="DJ-ACT-BTN PDF" onclick="djExportPDF()"><i class="fas fa-file-pdf"></i>حفظ PDF</button>';
  html += '<button class="DJ-ACT-BTN SHARE" onclick="shareDaily()"><i class="fas fa-share-alt"></i>مشاركة الطبيب</button>';
  html += '<button class="DJ-ACT-BTN SOS" onclick="djSOS()"><i class="fas fa-ambulance"></i>طوارئ</button>';
  html += '</div>';

  html += '</div>'; // .DJ-CARD
  dlr.innerHTML = html;

  // Animate progress bar
  setTimeout(function() {
    var bar = document.getElementById('DJ_PBAR');
    if (bar) bar.style.width = sc + '%';
  }, 50);

  // Notification if bad
  if (sc < 60 && typeof addNotification === 'function') {
    addNotification('⚠️ تنبيه صحي من يومياتك', statusTxt, sc >= 50 ? 'd97706' : 'dc2626');
  }
}

/* ──────────────────────────────────────────────────────────────
   WEEKLY CHART
────────────────────────────────────────────────────────────── */
function djBuildWeeklyChart(todayScore) {
  var history = djGetLocalHistory();
  var days7   = [];
  var today   = new Date();
  var dayNames = ['أح','اث','ثل','أر','خم','جم','سب'];

  for (var i = 6; i >= 0; i--) {
    var d = new Date(today);
    d.setDate(today.getDate() - i);
    var key = djDateKey(d);
    var score = (i === 0) ? todayScore : (history[key] ? history[key].score : null);
    days7.push({ label: dayNames[d.getDay()], score: score, isToday: i === 0 });
  }

  var html = '<div class="DJ-CHART-WRAP"><div class="DJ-CHART-BARS">';
  days7.forEach(function(day) {
    var h = day.score !== null ? Math.round((day.score / 100) * 64) + 6 : 4;
    var cls = 'DJ-BAR' + (day.isToday ? ' TODAY' : '');
    var valTxt = day.score !== null ? day.score + '%' : '-';
    html += '<div class="DJ-BAR-COL">';
    html += '<div class="DJ-BAR-VAL">'+valTxt+'</div>';
    html += '<div class="'+cls+'" style="height:'+h+'px;"></div>';
    html += '<div class="DJ-BAR-DAY">'+day.label+'</div>';
    html += '</div>';
  });
  html += '</div></div>';

  // Save today to local history
  var entry = djCollect();
  history[djDateKey(today)] = { score: todayScore, mood: entry.mood, ts: Date.now() };
  try { localStorage.setItem('dj_history', JSON.stringify(history)); } catch(e) {}

  return html;
}

/* ──────────────────────────────────────────────────────────────
   LOCAL HISTORY HELPERS
────────────────────────────────────────────────────────────── */
function djGetLocalHistory() {
  try { return JSON.parse(localStorage.getItem('dj_history') || '{}'); } catch(e) { return {}; }
}
function djDateKey(d) {
  return d.getFullYear() + '-' + (d.getMonth()+1) + '-' + d.getDate();
}

/* ──────────────────────────────────────────────────────────────
   LOAD HISTORY TAB
────────────────────────────────────────────────────────────── */
function djLoadHistory() {
  var container = document.getElementById('DJ_HIST_LIST');
  if (!container) return;
  var history = djGetLocalHistory();
  var keys = Object.keys(history).sort(function(a,b){ return new Date(b)-new Date(a); });

  if (!keys.length) {
    container.innerHTML = '<div style="text-align:center;padding:20px;font-size:12px;color:#9ca3af;">لا توجد سجلات سابقة بعد.</div>';
    return;
  }

  var moodEmoji = {5:'😁',4:'🙂',3:'😐',2:'😔',1:'😞',0:'—'};
  var html = '';
  keys.slice(0, 14).forEach(function(k) {
    var e = history[k];
    var sc = e.score;
    var ringColor = sc >= 75 ? '#06d6a0' : sc >= 50 ? '#f59e0b' : '#ef4444';
    var dateLabel = new Date(k).toLocaleDateString('ar-DZ');
    html += '<div class="DJ-HIST-ITEM">';
    html += '<div class="DJ-HIST-SCORE" style="background:'+ringColor+';">'+sc+'%</div>';
    html += '<div class="DJ-HIST-META"><div class="DJ-HIST-DATE">'+dateLabel+'</div>';
    html += '<div class="DJ-HIST-MOOD">المزاج: '+(moodEmoji[e.mood]||'—')+'</div></div>';
    html += '<i class="fas fa-chevron-left DJ-HIST-ARROW"></i>';
    html += '</div>';
  });
  container.innerHTML = html;
}

/* ──────────────────────────────────────────────────────────────
   SHARE DAILY  (replaces old shareDaily)
────────────────────────────────────────────────────────────── */
window.shareDaily = function() {
  var d = djCollect();
  var moodLabels = {5:'ممتاز',4:'جيد',3:'عادي',2:'متعب',1:'سيء',0:''};
  var lines = [
    '📊 تقرير صحتي اليومي — MedChifaGiz',
    '📅 ' + new Date().toLocaleDateString('ar-DZ'),
    '─────────────────────'
  ];
  if (d.mood)       lines.push('😊 الحالة: ' + (moodLabels[d.mood]||''));
  if (d.bp)         lines.push('🩺 ضغط الدم: ' + d.bp);
  if (d.sugar)      lines.push('🍬 السكر: ' + d.sugar);
  if (d.heart_rate) lines.push('❤️ النبض: ' + d.heart_rate);
  if (d.temp)       lines.push('🌡️ الحرارة: ' + d.temp + '°');
  if (d.spo2)       lines.push('💨 SpO2: ' + d.spo2 + '%');
  if (d.pain)       lines.push('😣 الألم: ' + d.pain + '/10');
  if (d.water_cups) lines.push('💧 الماء: ' + d.water_cups + ' أكواب');
  if (d.sleep_hours)lines.push('😴 النوم: ' + d.sleep_hours + ' ساعات');
  if (d.symptoms && d.symptoms.length) lines.push('🩹 الأعراض: ' + d.symptoms.join('، '));
  if (d.notes)      lines.push('📝 ملاحظات: ' + d.notes);
  lines.push('─────────────────────');
  lines.push('🏥 تم الإرسال عبر MedChifaGiz');

  var text = lines.join('\n');
  if (navigator.share) {
    navigator.share({ title: 'تقرير صحتي اليومي', text: text }).catch(function(){});
  } else {
    navigator.clipboard.writeText(text).then(function(){ if(typeof sa==='function') sa('تم نسخ التقرير 📋'); });
  }
};

/* ──────────────────────────────────────────────────────────────
   EXPORT PDF  (browser print)
────────────────────────────────────────────────────────────── */
window.djExportPDF = function() {
  var d = djCollect();
  var moodLabels = {5:'ممتاز 😁',4:'جيد 🙂',3:'عادي 😐',2:'متعب 😔',1:'سيء 😞',0:'—'};
  var symLabels  = { headache:'صداع', dizziness:'دوخة', chest_pain:'ألم صدر', breathless:'ضيق تنفس',
                     severe_fatigue:'تعب شديد', nausea:'غثيان', cough:'سعال', fever:'حمى',
                     swelling:'تورم الأرجل', other:'أخرى' };

  var html = '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8">'
    + '<style>*{font-family:Arial,sans-serif;}body{padding:30px;color:#1a2340;}'
    + 'h1{color:#0077b6;border-bottom:2px solid #00b4d8;padding-bottom:8px;}'
    + 'h2{color:#00b4d8;font-size:14px;margin-top:18px;}'
    + 'table{width:100%;border-collapse:collapse;margin-top:8px;}'
    + 'td{padding:7px 10px;border:1px solid #e5e7eb;font-size:13px;}'
    + 'td:first-child{font-weight:700;background:#f0f9ff;width:40%;}'
    + '.footer{margin-top:30px;font-size:11px;color:#9ca3af;text-align:center;}'
    + '@media print{button{display:none;}}</style></head><body>';
  html += '<h1>📋 تقرير صحتي اليومي — MedChifaGiz</h1>';
  html += '<p style="font-size:13px;color:#9ca3af;">📅 ' + new Date().toLocaleDateString('ar-DZ') + '</p>';

  html += '<h2>الحالة العامة</h2><table>';
  html += '<tr><td>المزاج</td><td>'+(moodLabels[d.mood]||'—')+'</td></tr>';
  if (d.feel_text) html += '<tr><td>الوصف</td><td>'+d.feel_text+'</td></tr>';
  html += '</table>';

  html += '<h2>المؤشرات الحيوية</h2><table>';
  if(d.bp)         html += '<tr><td>ضغط الدم</td><td>'+d.bp+' mmHg</td></tr>';
  if(d.sugar)      html += '<tr><td>السكر</td><td>'+d.sugar+' mg/dL</td></tr>';
  if(d.heart_rate) html += '<tr><td>النبض</td><td>'+d.heart_rate+' bpm</td></tr>';
  if(d.temp)       html += '<tr><td>درجة الحرارة</td><td>'+d.temp+' °C</td></tr>';
  if(d.spo2)       html += '<tr><td>نسبة الأكسجين SpO2</td><td>'+d.spo2+' %</td></tr>';
  if(d.weight)     html += '<tr><td>الوزن</td><td>'+d.weight+' kg</td></tr>';
  html += '</table>';

  html += '<h2>الأعراض</h2><table>';
  var symList = d.symptoms.map(function(s){ return symLabels[s]||s; }).join('، ');
  html += '<tr><td>الأعراض المُبلَّغ عنها</td><td>'+(symList||'لا أعراض')+'</td></tr>';
  html += '<tr><td>مستوى الألم</td><td>'+d.pain+' / 10</td></tr>';
  html += '</table>';

  html += '<h2>نمط الحياة</h2><table>';
  html += '<tr><td>الدواء</td><td>'+(d.medication==='yes'?'تناولته ✅':d.medication==='late'?'متأخر ⏰':'لم يتناول ❌')+'</td></tr>';
  html += '<tr><td>ساعات النوم</td><td>'+d.sleep_hours+' ساعات</td></tr>';
  html += '<tr><td>جودة النوم</td><td>'+(d.sleep_quality||'—')+'</td></tr>';
  html += '<tr><td>الماء</td><td>'+d.water_cups+' أكواب</td></tr>';
  html += '<tr><td>النشاط البدني</td><td>'+(d.activity==='high'?'نشيط':d.activity==='medium'?'متوسط':d.activity==='low'?'قليل':'—')+'</td></tr>';
  html += '</table>';

  if(d.notes) { html += '<h2>ملاحظات إضافية</h2><p style="font-size:13px;background:#f0f9ff;padding:10px;border-radius:8px;">'+d.notes+'</p>'; }

  html += '<div class="footer">تم إنشاؤه بواسطة MedChifaGiz — '+new Date().toLocaleString('ar-DZ')+'</div>';
  html += '</body></html>';

  var win = window.open('', '_blank');
  if (win) { win.document.write(html); win.document.close(); win.print(); }
};

/* ──────────────────────────────────────────────────────────────
   SOS EMERGENCY
────────────────────────────────────────────────────────────── */
window.djSOS = function() {
  if (confirm('هل تريد الاتصال بالطوارئ؟ سيتم الاتصال على 1021')) {
    window.location.href = 'tel:1021';
  }
};

/* ──────────────────────────────────────────────────────────────
   HOOK INTO VIEW SWITCHING — wait for DOM
────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  // Expose aliases called by patient_dashboard.js stubs
  window._djAnalyze = window.analyzeDaily;
  window._djShare   = window.shareDaily;

  // Patch into the existing showView / NV button mechanism
  document.querySelectorAll('.NV').forEach(function(btn) {
    if (btn.getAttribute('data-v') === 'VDL') {
      btn.addEventListener('click', function() {
        setTimeout(djInit, 60); // slight delay so VDL is visible
      });
    }
  });
  // Also init if VDL is already active on page load
  var vdl = document.getElementById('VDL');
  if (vdl && vdl.classList.contains('A')) djInit();
});

})(); // end IIFE
