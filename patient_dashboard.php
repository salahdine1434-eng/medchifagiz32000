<?php
session_start();

if(!isset($_SESSION['user_email'])){
    header("Location: login.php");
    exit();
}

require "db.php";

// نظام Online بسيط: بمجرد فتح الداشبورد (أي بعد تسجيل الدخول) يصبح المستخدم متصلاً.
// يُضبط = 0 لاحقاً في logout.php عند تسجيل الخروج.
$pdo->prepare("UPDATE users SET is_online = 1 WHERE id = ?")->execute([$_SESSION['user_id']]);

// جلب الإشعارات
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$count = $stmt->fetchColumn();
/* جلب بيانات السجل الطبي */
$stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedChifaGiz</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="patient_dashboard.css?v=100">
  <!-- يومياتي Daily Health Tracker styles -->
  <link rel="stylesheet" href="daily_journal.css?v=1">
  <!-- التواصل الطبي - Medical Communication styles -->
  <link rel="stylesheet" href="patient_medcomm.css?v=1">
</head>
<body>

<!-- ===================== APP ===================== -->
<div id="APP" style="display:block;">

<!-- ═══ OLD HEADER — hidden by CSS, IDs kept for JS ═══ -->
<header class="TB" style="display:none!important;visibility:hidden;">
  <div class="LOGO">
    <div class="LI" id="LOGO-BTN" onclick="opPanel('LE')" style="cursor:pointer;">
      <img id="LOGO-IMG" style="display:none;width:60%;height:60%;object-fit:cover;border-radius:11px;">
      <img src="medchifagz.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:11px;display:block;">
    </div>
    <div>
      <div class="LT2" id="APP-NAME" onclick="opPanel('LE')">MedChifaGiz</div>
      <div style="font-size:9px;color:#9ca3af;" data-t="appSub">التطبيق الطبي الذكي</div>
    </div>
    <span id="DT" data-t="dashboard">لوحة تحكم المريض</span>
  </div>
  <div class="TR">
    <div class="PI" onclick="opPanel('PP2')">
      <div class="AV" id="TAV">م</div>
      <span class="PN" id="TNM">المريض</span>
    </div>
    <div style="position:relative;">
      <button class="IB" id="BN" onclick="opPanel('NP')"><i class="fas fa-bell"></i><span class="NB" id="NBADGE"><?= $count ?></span></button>
    </div>
    <div style="position:relative;">
      <button class="IB" id="BLG"><i class="fas fa-globe"></i></button>
    </div>
    <button class="IB" id="BDK"><i class="fas fa-moon"></i></button>
    <button class="IB RD" id="BLO"><i class="fas fa-sign-out-alt"></i></button>
  </div>
</header>

<!-- ═══════════════════════════════════════════════════════
     PATIENT SIDEBAR — مطابق بصرياً لـ Dashboard الطبيب
═══════════════════════════════════════════════════════ -->

<!-- Mobile Toggle Button -->
<button class="pt-sidebar-toggle" id="ptSidebarToggle" onclick="ptToggleSidebar()">
  <i class="fas fa-bars"></i>
</button>

<!-- Mobile Overlay -->
<div class="pt-sidebar-overlay" id="ptSidebarOverlay" onclick="ptCloseSidebar()"></div>

<!-- Sidebar -->
<aside class="pt-sidebar" id="ptSidebar">

  <!-- Logo Section -->
  <div class="pt-sidebar-logo" style="cursor:pointer;" onclick="opPanel('LE')">
    <img src="medchifagz.png" alt="MedChifaGiz">
    <div class="pt-sidebar-logo-text">
      <span class="pt-brand">MedChifaGiz</span>
      <span class="pt-tagline" data-t="appSub">التطبيق الطبي الذكي</span>
    </div>
  </div>

  <!-- Patient Card -->
  <div class="pt-sidebar-patient" onclick="opPanel('PP2')">
    <div class="pt-sidebar-patient-avatar" id="ptSbAvatar">م</div>
    <div class="pt-sidebar-patient-info">
      <div class="pt-pat-name" id="ptSbName">المريض</div>
      <div class="pt-pat-role">مريض • MedChifaGiz</div>
    </div>
    <i class="fas fa-chevron-left" style="font-size:0.6rem;color:var(--pt-text-muted);"></i>
  </div>

  <!-- Nav -->
  <nav class="pt-sidebar-nav">

    <!-- 1. بطاقتي الشخصية (محتوى السجل الطبي) -->
    <div class="pt-snav-item pt-active" data-v="VRD" onclick="ptNavTo('VRD',this)">
      <i class="fas fa-id-card"></i>
      <span>بطاقتي الشخصية</span>
    </div>

    <!-- 2. الخدمات (محتوى الرئيسية: أطباء، صيدلية...) -->
    <div class="pt-snav-item" data-v="VH" onclick="ptNavTo('VH',this)">
      <i class="fas fa-th-large"></i>
      <span>الخدمات</span>
    </div>

    <!-- 3. يومياتي -->
    <div class="pt-snav-item" data-v="VDL" onclick="ptNavTo('VDL',this)">
      <i class="fas fa-heartbeat"></i>
      <span data-t="dailyNav">يومياتي</span>
    </div>

    <!-- 4. أدويتي -->
    <div class="pt-snav-item" data-v="VDR" onclick="ptNavTo('VDR',this)">
      <i class="fas fa-pills"></i>
      <span data-t="drugsNav">أدويتي</span>
    </div>

    <!-- 5. الحالات الصحية — Dropdown -->
    <div class="pt-snav-group" id="grp-health">
      <div class="pt-snav-group-header" onclick="ptToggleGroup('grp-health')">
        <i class="fas fa-stethoscope"></i>
        <span>الحالات الصحية</span>
        <i class="fas fa-chevron-left pt-snav-arrow"></i>
      </div>
      <div class="pt-snav-sub">
        <div class="pt-snav-sub-item" data-v="VPR" data-tab="preg" onclick="ptNavToTab('VPR','preg',this)">
          <i class="fas fa-baby-carriage"></i>
          <span>الحوامل</span>
        </div>
        <div class="pt-snav-sub-item" data-v="VPR" data-tab="chronic" onclick="ptNavToTab('VPR','chronic',this)">
          <i class="fas fa-heartbeat"></i>
          <span>الأمراض المزمنة</span>
        </div>
      </div>
    </div>

    <!-- 6. النتائج — Dropdown -->
    <div class="pt-snav-group" id="grp-results">
      <div class="pt-snav-group-header" onclick="ptToggleGroup('grp-results')">
        <i class="fas fa-microscope"></i>
        <span>النتائج</span>
        <i class="fas fa-chevron-left pt-snav-arrow"></i>
      </div>
      <div class="pt-snav-sub">
        <div class="pt-snav-sub-item" data-v="VRC" onclick="ptNavTo('VRC',this)">
          <i class="fas fa-qrcode"></i>
          <span>التحاليل</span>
        </div>
        <div class="pt-snav-sub-item" data-v="VXRAY" onclick="ptNavTo('VXRAY',this)">
          <i class="fas fa-x-ray"></i>
          <span>الأشعة</span>
        </div>
      </div>
    </div>

    <!-- 7. الذكاء الاصطناعي -->
    <div class="pt-snav-item" data-v="VAI" onclick="ptNavTo('VAI',this)">
      <i class="fas fa-robot"></i>
      <span data-t="aiNav">الذكاء الاصطناعي</span>
    </div>

    <!-- 8. الخدمات الصحية (coming soon) -->
    <div class="pt-snav-item" data-v="VSV" onclick="ptNavTo('VSV',this)">
      <i class="fas fa-briefcase-medical"></i>
      <span>الخدمات الصحية</span>
    </div>

    <!-- 9. التواصل الطبي -->
    <div class="pt-snav-item" data-v="VMED" onclick="ptNavTo('VMED',this)">
      <i class="fas fa-comment-medical"></i>
      <span>التواصل الطبي</span>
    </div>

  </nav>

  <!-- Footer -->
  <div class="pt-sidebar-footer">
    <div class="pt-logout-item" onclick="document.getElementById('BLO').click()">
      <i class="fas fa-sign-out-alt"></i>
      <span>تسجيل الخروج</span>
    </div>
  </div>

</aside>

<!-- ═══════════════════════════════════════
     NEW TOP HEADER — مطابق لـ Header الطبيب
═══════════════════════════════════════ -->
<header class="pt-top-bar">
  <div class="pt-header-content">

    <div class="pt-header-left">
      <h1 id="ptPageTitle" data-t="dashboard">لوحة تحكم المريض</h1>
      <div class="pt-header-subtitle" data-t="appSub">التطبيق الطبي الذكي</div>
    </div>

    <div class="pt-header-actions">

      <!-- Profile -->
      <div class="pt-profile-pill" onclick="opPanel('PP2')">
        <div class="pt-profile-avatar" id="ptHdrAvatar">م</div>
        <span class="pt-profile-name" id="ptHdrName">المريض</span>
        <i class="fas fa-chevron-down"></i>
      </div>

      <!-- Notifications -->
      <div class="pt-action-btn" onclick="opPanel('NP');" title="الإشعارات" style="position:relative;">
        <i class="fas fa-bell"></i>
        <?php if($count > 0): ?>
        <span class="pt-notif-badge"><?= $count ?></span>
        <?php endif; ?>
      </div>

      <!-- Language dropdown (reuse existing logic) -->
      <div class="pt-action-btn" style="position:relative;" title="اللغة" onclick="document.getElementById('BLG').click()">
        <i class="fas fa-globe"></i>
      </div>

      <!-- Dark mode (reuse existing button) -->
      <div class="pt-action-btn" title="الوضع الليلي" onclick="document.getElementById('BDK').click()">
        <i class="fas fa-moon"></i>
      </div>

      <!-- Logout -->
      <div class="pt-action-btn" title="تسجيل الخروج" style="color:#ef4444;border-color:rgba(239,68,68,0.2);" onclick="document.getElementById('BLO').click()">
        <i class="fas fa-sign-out-alt"></i>
      </div>

    </div>
  </div>
</header>

<!-- ═══ MAIN CONTENT WRAPPER ═══ -->
<main class="pt-main">
<div class="MN">

<!-- ===== HOME VIEW ===== -->
<div class="VW" id="VH">



<!-- DOCTORS -->
<div class="CD" id="CDO">
  <div class="CH"><div class="CI">👨‍⚕️</div><div><div class="CT" data-t="doctors">الأطباء</div><div class="CS" data-t="doctorsSub">احجز موعدك أو تواصل مع طبيبك</div></div><i class="fas fa-chevron-left CA"></i></div>
  <div class="CB">
    <div class="FB"><select class="FS" id="WDO" onchange="ldCom(this,'CDO'); loadDoctors()"><option value="" data-t="wilaya">📍 الولاية</option></select><select class="FS" id="COM_CDO" onchange="loadDoctors()"><option value="" data-t="commune">البلدية</option></select><select class="FS" id="SPF" onchange="loadDoctors()"><option value="" data-t="allSpecs">🩺 جميع التخصصات</option></select></div>
    
    <div id="do-lst">
     <div id="pagination" style="display:none;">
    <button id="prevBtn" onclick="prevPage()">➡️السابق</button>
    <button id="nextBtn" onclick="nextPage()">التالي ⬅️</button>
</div>
  <div class="IL" id="doctorsContainer"></div>
</div>

    <button class="btn-doctors btn-near"
    onclick="getNearby('doctors','map')">
    📍 الأطباء القريبين مني
</button>

<button class="btn-doctors btn-other"
    onclick="openNearby('doctors','map')">
    🌍 أطباء من Google Maps
</button>


     <div id="map" style="display:none; height: 250px; margin-top: 20px; border-radius: 12px; overflow: hidden;"></div>
    </div>
    
    <div id="do-cht" style="display:none;">
      
      <div class="CSC" id="DO_SCR">
        <button class="BK" onclick="clCV('DO_SCR','DO_CVS')"><i class="fas fa-arrow-right"></i> <span data-t="back">رجوع</span></button>
        <div class="CB2" style="flex:1;"><div class="CM" id="DO_MSGS"><div class="MS S"><div class="MN2" data-t="you">أنت</div>صباح الخير دكتور</div><div class="MS R"><div class="MN2" data-t="doctor">الطبيب</div>صباح النور، موعدك غداً 10:00</div></div><div class="CI2"><input id="DO_IN" data-ph="writeMsgPH" placeholder="اكتب رسالتك..."><button class="BT BP" onclick="sc('DO_IN','DO_MSGS',T('doctor'))"><i class="fas fa-paper-plane"></i></button></div></div>
      </div>
    </div>
  </div>

  <!-- PHARMACY -->
<div class="CD" id="CPH">
  <div class="CH">
    <div class="CI">💊</div>
    <div>
      <div class="CT" data-t="pharmacy">الصيدلية</div>
      <div class="CS" data-t="pharmacySub">ابحث عن الأدوية واطلبها</div>
    </div>
    <i class="fas fa-chevron-left CA"></i>
  </div>

  <div class="CB">
    <div class="FB">
      <select class="FS" id="WPH" onchange="ldComGeneric(this,'COM_CPH'); loadSection('pharmacies','ph-lst','WPH','COM_CPH')">
        <option value="" data-t="wilaya">📍 الولاية</option>
      </select>
      <select class="FS" id="COM_CPH"
    onchange="loadSection('pharmacies','ph-lst','WPH','COM_CPH')">
        <option value="" data-t="commune">البلدية</option>
      </select>
    </div>

   

    <div id="ph-lst">

  <!-- 🔽 هنا تحطي pagination -->
<div id="ph-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('pharmacies','ph-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('pharmacies','ph-lst')">التالي⬅️</button>
</div>
<div id="ph-lst-results" class="IL"></div>
</div>
      
<div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('pharmacies','map-pharmacies')">
    📍 صيدليات قريبة مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('pharmacies','map-pharmacies')">
    🌍 صيدليات من Google Maps
  </button>



</div>
<div id="map-pharmacies" style="display:none; height: 250px; margin-top: 20px; border-radius: 12px; overflow: hidden;"></div>


   
  </div>
</div>
<!-- LABS -->
<div class="CD" id="CLB">
  
  <div class="CH"><div class="CI">🔬</div><div><div class="CT" data-t="labs">المخابر</div><div class="CS" data-t="labsSub">التحاليل والفحوصات المخبرية</div></div><i class="fas fa-chevron-left CA"></i></div>
  <div class="CB">
    
    <div class="FB">

  <!-- الولاية -->
  <select class="FS" id="WLB"
    onchange="ldComGeneric(this,'COM_CLB'); loadSection('labs','lb-lst','WLB','COM_CLB')">
    <option value="">📍 الولاية</option>
  </select>

  <!-- البلدية -->
  <select class="FS" id="COM_CLB"
    onchange="loadSection('labs','lb-lst','WLB','COM_CLB')">
    <option value="">البلدية</option>
  </select>

</div>
    
   <div id="lb-lst">
 <div id="lb-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('labs','lb-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('labs','lb-lst')">التالي ⬅️</button>
</div>
  <div id="lb-lst-results"></div>
</div>
    <div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('labs','map-labs')">
    📍 مخابر قريبة مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('labs','map-labs')">
    🌍 مخابر من Google Maps
  </button>

</div>
    <div id="map-labs" style="display:none; height:250px; margin-top:20px;"></div>
  </div>
</div>

<!-- NURSES -->
<div class="CD" id="CNR">
  <div class="CH"><div class="CI">🩺</div><div><div class="CT" data-t="nurses">الرعاية المنزلية</div><div class="CS" data-t="nursesSub">رعاية طبية في منزلك</div></div><i class="fas fa-chevron-left CA"></i></div>
  <div class="CB">
    <div class="FB">
      <select class="FS" id="WNR" onchange="ldComGeneric(this,'COM_CNR'); loadSection('nurses','nr-lst','WNR','COM_CNR')"><option value="" data-t="wilaya">📍 الولاية</option></select>
      <select class="FS" id="COM_CNR" onchange="loadSection('nurses','nr-lst','WNR','COM_CNR')"><option value="" data-t="commune">البلدية</option></select>
    </div>
    <div id="nr-lst">
<div id="nr-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('nurses','nr-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('nurses','nr-lst')">التالي ⬅️</button>
</div>
      <div id="nr-lst-results" class="IL"></div>
    </div>
    <div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('nurses','map-nurses')">
    📍 ممرضين قريبين مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('nurses','map-nurses')">
    🌍 ممرضين من Google Maps
  </button>

</div>
    <div id="map-nurses" style="display:none; height:250px; margin-top:20px;"></div>
  </div>
</div>

<!-- BLOOD -->
<div class="CD" id="CBL">

  <div class="CH">
    <div class="CI">🩸</div>
    <div>
      <div class="CT">التبرع بالدم</div>
      <div class="CS">ابحث عن متبرعين حسب الزمرة</div>
    </div>
    <i class="fas fa-chevron-left CA"></i>
  </div>

  <div class="CB">
    
    <div class="FB">

      <!-- الولاية -->
      <select class="FS" id="WBL"
        onchange="ldComGeneric(this,'COM_CBL'); loadSection('donors','bl-lst','WBL','COM_CBL')">
        <option value="">📍 الولاية</option>
      </select>

      <!-- البلدية -->
      <select class="FS" id="COM_CBL"
        onchange="loadSection('donors','bl-lst','WBL','COM_CBL')">
        <option value="">البلدية</option>
      </select>

      <!-- الزمرة (مصصحح) -->
      <select class="FS" id="BL_GRP"
        onchange="loadSection('donors','bl-lst','WBL','COM_CBL')">
        
        <option value="">🩸 جميع الزمر</option>
        <option value="A+">A+</option>
        <option value="A-">A-</option>
        <option value="B+">B+</option>
        <option value="B-">B-</option>
        <option value="AB+">AB+</option>
        <option value="AB-">AB-</option>
        <option value="O+">O+</option>
        <option value="O-">O-</option>

      </select>

    </div>

    <div id="bl-lst">
     <div id="bl-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('donors','bl-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('donors','bl-lst')">التالي ⬅️</button>
</div>
      <div id="bl-lst-results" class="IL"></div>
    </div>
<div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('donors','map-donors')">
    🩸 متبرعين قريبين مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('donors','map-donors')">
    🌍 متبرعين من Google Maps
  </button>

</div>
    <div id="map-donors" style="display:none; height:250px; margin-top:20px;"></div>

  </div>
</div>

<!-- CLINICS -->
<div class="CD" id="CCL">
  <div class="CH"><div class="CI">🏥</div><div><div class="CT" data-t="clinics">العيادات والمصحات</div><div class="CS" data-t="clinicsSub">ابحث عن العيادة المناسبة</div></div><i class="fas fa-chevron-left CA"></i></div>
  <div class="CB">
    <div class="FB">
      <select class="FS" id="WCL" onchange="ldComGeneric(this,'COM_CCL'); loadSection('clinics','cl-lst','WCL','COM_CCL')"><option value="" data-t="wilaya">📍 الولاية</option></select>
      <select class="FS" id="COM_CCL" onchange="loadSection('clinics','cl-lst','WCL','COM_CCL')"><option value="" data-t="commune">البلدية</option></select>
    </div>
    <div id="cl-lst">
    <div id="cl-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('clinics','cl-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('clinics','cl-lst')">التالي ⬅️</button>
</div>
      <div id="cl-lst-results" class="IL"></div>
    </div>
<div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('clinics','map-clinics')">
    🏥 عيادات قريبة مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('clinics','map-clinics')">
    🌍 عيادات من Google Maps
  </button>
    <div id="map-clinics" style="display:none; height:250px; margin-top:20px;"></div>
  </div>
</div>

</div>

<!-- CIVIL PROTECTION -->
<div class="CD" id="CCV">
  <div class="CH"><div class="CI">🚑</div><div><div class="CT" data-t="civil">الحماية المدنية والإسعاف</div><div class="CS" data-t="civilSub">أرقام الطوارئ</div></div><i class="fas fa-chevron-left CA"></i></div>
  <div class="CB">
    <div class="FB">
      <select class="FS" id="WCV" onchange="ldComGeneric(this,'COM_CCV'); loadSection('civil_protection','cv-lst','WCV','COM_CCV')"><option value="" data-t="wilaya">📍 الولاية</option></select>
      <select class="FS" id="COM_CCV" onchange="loadSection('civil_protection','cv-lst','WCV','COM_CCV')"><option value="" data-t="commune">البلدية</option></select>
    </div>
    <div id="cv-lst">
      <div id="cv-lst-pagination" style="display:none;">
    <button class="prev" onclick="prevSection('civil_protection','cv-lst')">➡️ السابق</button>
    <button class="next" onclick="nextSection('civil_protection','cv-lst')">التالي ⬅️</button>
</div>
      <div id="cv-lst-results" class="IL"></div>
    </div>
    <div class="actions-vertical">

  <button class="btn-doctors btn-near"
    onclick="getNearby('civil_protection','map-civil_protection')">
    🚑 حماية مدنية قريبة مني
  </button>

  <button class="btn-doctors btn-other"
    onclick="openNearby('civil_protection','map-civil_protection')">
    🌍 حماية مدنية من Google Maps
  </button>

</div>
    <div id="map-civil_protection" style="display:none; height:250px; margin-top:20px;"></div>
  </div>
</div>
<!-- SPORTS -->
<div class="CD" id="CSP">

<!-- Header -->
<div class="CH">

  <div class="CI">🏋️</div>

  <div>
    <div class="CT">الصحة والرياضة</div>
    <div class="CS">الرياضة والحياة الصحية</div>
  </div>

  <i class="fas fa-chevron-left CA"></i>

</div>

<div class="CB">

  <!-- الفلاتر -->
  <div class="FB">

    <!-- الولاية -->
    <select class="FS" id="WSP"

      onchange="ldComGeneric(this,'COM_CSP');

      loadSection(
        'sport_health',
        'sp-lst',
        'WSP',
        'COM_CSP',
        'sport'
      )">

      <option value="">📍 الولاية</option>

    </select>

    <!-- البلدية -->
    <select class="FS" id="COM_CSP"

      onchange="loadSection(
        'sport_health',
        'sp-lst',
        'WSP',
        'COM_CSP',
        'sport'
      )">

      <option value="">البلدية</option>

    </select>

  </div>

  <!-- Tab -->
  <div class="TS">

    

  </div>

  <!-- =================== رياضة =================== -->
  <div id="sp-nt">

    <div id="sp-lst-pagination" style="display:none;">

      <button class="prev"
        onclick="prevSection('sport_health','sp-lst')">

        ➡️ السابق

      </button>

      <button class="next"
        onclick="nextSection('sport_health','sp-lst')">

        التالي ⬅️

      </button>

    </div>

    <div id="sp-lst-results"></div>

  </div>

  <!-- الأزرار -->
  <div class="actions-vertical">

    <button class="btn-doctors btn-near"
      onclick="getNearby('sport_health','map-sport_health')">

      🏃 مراكز قريبة مني

    </button>

    <button class="btn-doctors btn-other"
      onclick="openNearby('sport_health','map-sport_health')">

      🌍 مراكز من Google Maps

    </button>

  </div>

  <!-- الخريطة -->
  <div id="map-sport_health"
    style="display:none;height:250px;margin-top:20px;">

  </div>

</div>

</div>



</div><!-- end VH -->

<!-- ===== AI VIEW ===== -->
<div class="VW" id="VAI">
  <div style="width:100%;height:75vh;background:#fff;border-radius:20px;overflow:hidden;">
    <iframe
        src="http://localhost/fix/chatbot_kh/"
        style="width:100%;height:100%;border:none;">
    </iframe>
</div>
</div>

<!-- ===== RECORD VIEW = بطاقتي الشخصية ===== -->
<div class="VW A" id="VRD">

<?php if(isset($patient['medical_completed']) && $patient['medical_completed'] == 1): ?>
<div class="MEDICAL_RECORD" id="medicalRecordPDF">

    <div class="MR_TITLE">
        <i class="fas fa-file-medical"></i>
        السجل الطبي الرقمي
    </div>

    <div class="MR_SECTION">
        <h3>👤 معلومات شخصية</h3>

        <div class="MR_GRID">
            <div class="mini-card">
                <span>الاسم الكامل</span>
                <b><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']) ?></b>
            </div>

            <div class="mini-card">
                <span>تاريخ الميلاد</span>
                <b><?= htmlspecialchars($patient['birth_date']) ?></b>
            </div>

            <div class="mini-card">
                <span>زمرة الدم</span>
                <b style="color:#dc2626"><?= htmlspecialchars($patient['blood_type']) ?></b>
            </div>

            <div class="mini-card">
                <span>الوزن / الطول</span>
                <b><?= htmlspecialchars($patient['weight']) ?> كغ / <?= htmlspecialchars($patient['height']) ?> سم</b>
            </div>
        </div>
    </div>

    <div class="MR_SECTION">
        <h3>🩺 معلومات طبية</h3>

<div class="MR_GRID">

    <div class="mini-card">
        <span>الأمراض المزمنة</span>
        <div class="badge-box">
            <?= !empty($patient['chronic_diseases']) ? htmlspecialchars($patient['chronic_diseases']) : 'لا يوجد' ?>
        </div>
    </div>

    <div class="mini-card">
        <span>الأدوية الحالية</span>
        <div class="badge-box">
            <?= !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : 'لا يوجد' ?>
        </div>
    </div>

    <div class="mini-card">
        <span>الطبيب المعالج</span>
        <div class="badge-box">
            <?= !empty($patient['medications']) ? htmlspecialchars($patient['medications']) : 'لا يوجد' ?>
        </div>
    </div>

    <div class="mini-card">
        <span>ملاحظات صحية</span>
        <div class="badge-box">
            <?= !empty($patient['health_notes']) ? htmlspecialchars($patient['health_notes']) : 'لا يوجد' ?>
        </div>
    </div>

</div>

</div>
<div class="MR_SECTION">
    <h3>🚑 معلومات الطوارئ</h3>

    <div class="MR_GRID">
        <div class="mini-card full">
            <span>جهة الاتصال</span>
            <b>
                <?= htmlspecialchars($patient['emergency_name'] ?: 'غير محدد') ?>
                —
                <?= htmlspecialchars($patient['emergency_phone'] ?: 'لا يوجد') ?>
            </b>
        </div>
    </div>
</div>
<div class="RECORD_BTNS">

  <button class="BT BO" onclick="editMedicalRecord()">
    <i class="fas fa-edit"></i> تعديل المعلومات
  </button>

  <button class="BT BP" onclick="document.getElementById('MCARD').style.display='flex'">
    <i class="fas fa-id-card"></i> بطاقتي الصحية
  </button>
<button class="BT BD" onclick="downloadMedicalRecord()">
   ⬇️ تحميل و طباعة
</button>
 <button class="BT BR" onclick="resetMedicalForm()">
  🗑️ مسح السجل
</button>

</div>

<?php else: ?>

   <div id="recordWizard" style="
max-width:650px;
margin:20px auto;
background:#ffffff;
padding:20px;
border-radius:22px;
border:1px solid #dbeafe;
box-shadow:0 10px 30px rgba(0,180,216,.14);
">

 <div class="ST" style="justify-content:center;font-size:28px;font-weight:800;margin-bottom:20px;gap:10px;">
    <i class="fas fa-id-card" style="color:#00b4d8;"></i>
    <span>إنشاء السجل الطبي الرقمي</span>
  </div>

  <div style="height:8px;background:#e5e7eb;border-radius:20px;overflow:hidden;margin-bottom:15px;">
    <div id="wizardBar" style="height:100%;width:20%;background:linear-gradient(135deg,#06d6a0,#00b4d8);border-radius:20px;transition:.3s;"></div>
  </div>

  <div class="record-step">
    <div class="FG"><label class="FL">الاسم</label><input class="FI" id="nom"></div>
    <div class="FG"><label class="FL">اللقب</label><input class="FI" id="prenom"></div>
    <div class="FR">
      <div class="FG"><label class="FL">تاريخ الميلاد</label><input type="date" class="FI" id="birth"></div>
      <div class="FG">
        <label class="FL">الجنس</label>
       <select class="FI" id="gender">
  <option value="" selected disabled>اختر الجنس</option>
  <option>ذكر</option>
  <option>أنثى</option>
</select>
      </div>
    </div>
    <button class="BT BP" onclick="nextRecordStep()">التالي</button>
  </div>

  <div class="record-step" style="display:none;">
    <div class="FG"><label class="FL">فصيلة الدم</label><input class="FI" id="blood"></div>
    <div class="FR">
      <div class="FG"><label class="FL">الوزن</label><input class="FI" id="weight"></div>
      <div class="FG"><label class="FL">الطول</label><input class="FI" id="height"></div>
    </div>
    <div class="FG"><label class="FL">رقم الهاتف</label><input class="FI" id="phone"></div>
    <button class="BT BO" onclick="prevRecordStep()">السابق</button>
    <button class="BT BP" onclick="nextRecordStep()">التالي</button>
  </div>

  <div class="record-step" style="display:none;">
    <div class="FG"><label class="FL">الأمراض المزمنة</label><textarea class="FI FTA" id="chronic"></textarea></div>
    <div class="FG"><label class="FL">الأدوية الحالية</label><textarea class="FI FTA" id="allergy"></textarea></div>
    <button class="BT BO" onclick="prevRecordStep()">السابق</button>
    <button class="BT BP" onclick="nextRecordStep()">التالي</button>
  </div>

  <div class="record-step" style="display:none;">
    <div class="FG"><label class="FL">الطبيب المعالج</label><textarea class="FI FTA" id="meds"></textarea></div>
    <div class="FG"><label class="FL">ملاحظات صحية</label><textarea class="FI FTA" id="notes"></textarea></div>
    <button class="BT BO" onclick="prevRecordStep()">السابق</button>
    <button class="BT BP" onclick="nextRecordStep()">التالي</button>
  </div>

  <div class="record-step" style="display:none;">
    <div class="FG"><label class="FL">شخص للطوارئ</label><input class="FI" id="urgentName"></div>
    <div class="FG"><label class="FL">رقمه</label><input class="FI" id="urgentPhone"></div>
    <button class="BT BO" onclick="prevRecordStep()">السابق</button>
    <button class="BT BP" onclick="saveMedicalRecord()">حفظ السجل</button>
  </div>

</div>

<?php endif; ?>

</div>

<!-- ===== DRUGS VIEW ===== -->
<div class="VW" id="VDR">

  <!-- ══════════════════════════════════════════════════════════
       SMART MEDICATION CENTER — أدويتي
  ══════════════════════════════════════════════════════════ -->

  <!-- Hero Header -->
  <div class="SMC-HERO">
    <div class="SMC-HERO-BG"></div>
    <div class="SMC-HERO-CONTENT">
      <div class="SMC-HERO-ICON"><i class="fas fa-capsules"></i></div>
      <div class="SMC-HERO-TEXT">
        <div class="SMC-HERO-TITLE">Smart Medication Center</div>
        <div class="SMC-HERO-SUB">مركز إدارة أدويتك الذكي</div>
      </div>
      <div class="SMC-HERO-BADGE"><i class="fas fa-shield-alt"></i> AI Powered</div>
    </div>
    <div class="SMC-HERO-STATS">
      <div class="SMC-STAT">
        <div class="SMC-STAT-NUM" id="SMC_TOTAL_COUNT">0</div>
        <div class="SMC-STAT-LBL">دواء</div>
      </div>
      <div class="SMC-STAT-DIV"></div>
      <div class="SMC-STAT">
        <div class="SMC-STAT-NUM" id="SMC_TODAY_COUNT">0</div>
        <div class="SMC-STAT-LBL">اليوم</div>
      </div>
      <div class="SMC-STAT-DIV"></div>
      <div class="SMC-STAT">
        <div class="SMC-STAT-NUM" id="SMC_STREAK">0</div>
        <div class="SMC-STAT-LBL">يوم متتالي</div>
      </div>
    </div>
  </div>

  <!-- Current Medication Hero Card -->
  <div class="SMC-CURRENT-CARD" id="SMC_CURRENT_CARD">
    <div class="SMC-CURRENT-GLOW"></div>
    <div class="SMC-CURRENT-TOP">
      <div class="SMC-PILL-ICON"><i class="fas fa-pills"></i></div>
      <div class="SMC-CURRENT-INFO">
        <div class="SMC-CURRENT-NAME" id="SMC_CURRENT_NAME">أدويتي الحالية</div>
        <div class="SMC-CURRENT-DESC" id="SMC_CURRENT_DESC">من سجلك الطبي</div>
      </div>
      <button class="SMC-AI-QUICK-BTN" onclick="smcAnalyzeAI()" title="تحليل AI">
        <i class="fas fa-brain"></i>
      </button>
    </div>
    <div class="SMC-MEDS-CHIPS" id="SMC_MEDS_CHIPS">
      <!-- Populated by JS -->
    </div>
    <div class="SMC-CURRENT-FOOTER">
      <span class="SMC-SYNC-TAG"><i class="fas fa-sync-alt"></i> من السجل الطبي</span>
      <span class="SMC-TIME-TAG" id="SMC_LAST_UPDATE">—</span>
    </div>
  </div>

  <!-- Next Dose Countdown Card -->
  <div id="SMC_NEXT_DOSE_WRAP" class="SMC-NEXT-DOSE-CARD" style="display:none;">
    <div class="SMC-ND-GLOW"></div>
    <div class="SMC-ND-LEFT">
      <div class="SMC-ND-ICON"><i class="fas fa-hourglass-half"></i></div>
      <div>
        <div class="SMC-ND-LABEL">الجرعة القادمة</div>
        <div class="SMC-ND-MED-NAME" id="SMC_NEXT_NAME">—</div>
        <div class="SMC-ND-META">
          <span id="SMC_NEXT_DOSE_VAL"></span>
          <span class="SMC-ND-AT-TIME" id="SMC_NEXT_TIME"></span>
        </div>
      </div>
    </div>
    <div class="SMC-ND-RIGHT">
      <div class="SMC-ND-CD-LABEL">بعد</div>
      <div class="SMC-ND-COUNTDOWN" id="SMC_NEXT_CD">—</div>
    </div>
  </div>

  <!-- Daily Medication Timeline -->
  <div class="SMC-SECTION-TITLE">
    <i class="fas fa-clock"></i> جدول الأدوية اليومي
  </div>
  <div class="SMC-TIMELINE" id="SMC_TIMELINE">
    <!-- Populated by JS -->
  </div>

  <!-- Analytics Cards Row -->
  <div class="SMC-SECTION-TITLE">
    <i class="fas fa-chart-bar"></i> إحصائيات الالتزام
  </div>
  <div class="SMC-ANALYTICS-ROW">
    <div class="SMC-AN-CARD SMC-AN-GREEN">
      <div class="SMC-AN-ICON"><i class="fas fa-check-circle"></i></div>
      <div class="SMC-AN-NUM" id="SMC_COMPLIANCE_PCT">—</div>
      <div class="SMC-AN-LBL">الالتزام</div>
    </div>
    <div class="SMC-AN-CARD SMC-AN-CYAN">
      <div class="SMC-AN-ICON"><i class="fas fa-calendar-week"></i></div>
      <div class="SMC-AN-NUM" id="SMC_WEEK_TAKEN">—</div>
      <div class="SMC-AN-LBL">هذا الأسبوع</div>
    </div>
    <div class="SMC-AN-CARD SMC-AN-ORANGE">
      <div class="SMC-AN-ICON"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="SMC-AN-NUM" id="SMC_MISSED">—</div>
      <div class="SMC-AN-LBL">فائتة</div>
    </div>
  </div>

  <!-- Quick Add Medication Form -->
  <div class="SMC-SECTION-TITLE">
    <i class="fas fa-plus-circle"></i> إضافة دواء جديد
  </div>
  <div class="SMC-QUICK-ADD">
    <div class="SMC-QA-GLOW"></div>
    <div class="SMC-QA-GRID">
      <div class="SMC-QA-FIELD">
        <label class="SMC-QA-LBL"><i class="fas fa-capsules"></i> اسم الدواء</label>
        <input type="text" class="SMC-QA-IN" id="SMC_MED_NAME" placeholder="مثال: باراسيتامول">
      </div>
      <div class="SMC-QA-FIELD">
        <label class="SMC-QA-LBL"><i class="fas fa-prescription-bottle"></i> الجرعة</label>
        <input type="text" class="SMC-QA-IN" id="SMC_MED_DOSE" placeholder="مثال: 500mg">
      </div>
      <div class="SMC-QA-FIELD">
  <label class="SMC-QA-LBL">
    <i class="fas fa-clock"></i> ساعة الدواء
  </label>
  <input
    type="time"
    class="SMC-QA-IN"
    id="SMC_MED_TIME"
    value="08:00"
  >
</div>
      <div class="SMC-QA-FIELD">
        <label class="SMC-QA-LBL"><i class="fas fa-clock"></i> التوقيت</label>

        <select class="SMC-QA-IN SMC-QA-SEL" id="SMC_MED_TIMING">
          <option value="morning">🌅 الصباح</option>
          <option value="noon">☀️ الظهر</option>
          <option value="evening">🌆 المساء</option>
          <option value="night">🌙 الليل</option>
          <option value="asneeded">💊 عند الحاجة</option>
        </select>
      </div>
      <div class="SMC-QA-FIELD">
        <label class="SMC-QA-LBL"><i class="fas fa-utensils"></i> قبل/بعد الأكل</label>
        <select class="SMC-QA-IN SMC-QA-SEL" id="SMC_MED_FOOD">
          <option value="after">🍽️ بعد الأكل</option>
          <option value="before">⏰ قبل الأكل</option>
          <option value="with">🥘 مع الأكل</option>
          <option value="any">🔄 أي وقت</option>
        </select>
      </div>
    </div>
    <div class="SMC-QA-FIELD" style="margin-top:10px;">
      <label class="SMC-QA-LBL"><i class="fas fa-sticky-note"></i> ملاحظات (اختياري)</label>
      <input type="text" class="SMC-QA-IN" id="SMC_MED_NOTE" placeholder="تعليمات خاصة...">
    </div>
    <button class="SMC-ADD-BTN" onclick="smcAddMed()">
      <i class="fas fa-plus"></i> إضافة الدواء
    </button>
  </div>

  <!-- Medication List -->
  <div class="SMC-SECTION-TITLE">
    <i class="fas fa-list-ul"></i> قائمة أدويتي
  </div>
  <div id="SMC_MED_LIST">
    <div class="SMC-EMPTY-STATE">
      <i class="fas fa-pills"></i>
      <div>لا توجد أدوية مسجلة بعد</div>
      <div style="font-size:11px;color:#9ca3af;margin-top:4px;">أضف دواءك الأول من الأعلى</div>
    </div>
  </div>

  <!-- ══ AI MEDICATION ASSISTANT ══════════════════════════════ -->
  <div class="SMC-SECTION-TITLE SMC-AI-TITLE">
    <div class="SMC-AI-TITLE-ICON"><i class="fas fa-brain"></i></div>
    <div>
      <div>MedChifaGiz AI Medication Assistant</div>
      <div style="font-size:11px;font-weight:400;color:#94a3b8;">مساعد الأدوية الذكي</div>
    </div>
  </div>

  <div class="SMC-AI-CARD" id="SMC_AI_CARD">
    <div class="SMC-AI-GLOW"></div>
    <div class="SMC-AI-HEADER">
      <div class="SMC-AI-AVATAR">
        <i class="fas fa-robot"></i>
        <div class="SMC-AI-PULSE"></div>
      </div>
      <div class="SMC-AI-INTRO">
        <div class="SMC-AI-NAME">MedBot AI</div>
        <div class="SMC-AI-TAGLINE">تحليل ذكي لنظام أدويتك</div>
      </div>
      <div class="SMC-AI-MODEL-BADGE">Groq · LLaMA</div>
    </div>

    <!-- Input Area -->
    <div class="SMC-AI-INPUT-AREA">
      <input type="text" class="SMC-AI-INPUT" id="SMC_AI_QUERY"
        placeholder="اسأل عن أدويتك... مثال: هل أتناول باراسيتامول قبل أو بعد الأكل؟">
      <button class="SMC-AI-SEND-BTN" onclick="smcAskAI()" id="SMC_AI_SEND">
        <i class="fas fa-paper-plane"></i>
      </button>
    </div>

    <!-- Quick Questions -->
    <div class="SMC-AI-QUICK-Q">
      <div class="SMC-AI-QUICK-LBL">أسئلة سريعة:</div>
      <div class="SMC-AI-CHIPS">
        <button class="SMC-AI-CHIP" onclick="smcQuickAsk('هل يوجد تفاعل بين أدويتي؟')">⚠️ تفاعلات</button>
        <button class="SMC-AI-CHIP" onclick="smcQuickAsk('ما هي أفضل أوقات تناول أدويتي؟')">⏰ التوقيت</button>
        <button class="SMC-AI-CHIP" onclick="smcQuickAsk('ما الطعام الذي يجب تجنبه مع أدويتي؟')">🍔 الغذاء</button>
        <button class="SMC-AI-CHIP" onclick="smcQuickAsk('ما الآثار الجانبية المحتملة لأدويتي؟')">💊 آثار جانبية</button>
      </div>
    </div>

    <!-- Loading State -->
    <div class="SMC-AI-LOADING" id="SMC_AI_LOADING" style="display:none;">
      <div class="SMC-AI-DOTS">
        <span></span><span></span><span></span>
      </div>
      <div class="SMC-AI-LOADING-TXT">يحلل MedBot AI أدويتك...</div>
    </div>

    <!-- AI Results Area -->
    <div id="SMC_AI_RESULT" style="display:none;">

      <!-- AI Summary -->
      <div class="SMC-AI-SUMMARY-CARD" id="SMC_AI_SUMMARY_CARD">
        <div class="SMC-AI-SUM-ICON"><i class="fas fa-comment-medical"></i></div>
        <div class="SMC-AI-SUM-TEXT" id="SMC_AI_SUMMARY_TEXT"></div>
      </div>

      <!-- Recommendations -->
      <div class="SMC-AI-REC-SECTION" id="SMC_AI_RECS_WRAP" style="display:none;">
        <div class="SMC-AI-REC-TITLE"><i class="fas fa-star"></i> توصيات MedBot</div>
        <div id="SMC_AI_RECS"></div>
      </div>

      <!-- Warnings -->
      <div class="SMC-AI-WARN-SECTION" id="SMC_AI_WARNS_WRAP" style="display:none;">
        <div class="SMC-AI-WARN-TITLE"><i class="fas fa-exclamation-triangle"></i> تحذيرات مهمة</div>
        <div id="SMC_AI_WARNS"></div>
      </div>

      <!-- Interaction Alerts -->
      <div class="SMC-AI-INTER-SECTION" id="SMC_AI_INTER_WRAP" style="display:none;">
        <div class="SMC-AI-INTER-TITLE"><i class="fas fa-bolt"></i> تنبيهات التفاعل</div>
        <div id="SMC_AI_INTER"></div>
      </div>

    </div><!-- /SMC_AI_RESULT -->

  </div><!-- /SMC_AI_CARD -->

</div><!-- /VDR -->

<!-- ===== DAILY VIEW — يومياتي Daily Health Tracker ===== -->
<div class="VW" id="VDL">

  <!-- Page title -->
  <div class="ST"><i class="fas fa-heartbeat" style="color:#00b4d8;"></i> <span data-t="dailyTrack">يومياتي - تتبع حالتي اليومية</span></div>

  <!-- Tab bar: Form only (السجل tab removed per request) -->
  <div class="DJ-TABS">
    <button class="DJ-TAB A" data-tab="form" onclick="djTab('form')"><i class="fas fa-edit"></i> تسجيل اليوم</button>
  </div>

  <!-- ── WEEKLY CALENDAR ─────────────────────────────── -->
  <div id="DJ_WEEKLY_CAL" class="DJ-WEEKLY-CAL-WRAP">
    <!-- Rendered by weekly_calendar.js -->
  </div>

  <!-- ── SELECTED DAY DATA PANEL ────────────────────── -->
  <div id="DJ_DAY_DATA_PANEL" class="DJ-DAY-DATA-PANEL" style="display:none;">
    <div class="DJ-CARD">
      <div class="DJ-STITLE" id="DJ_DAY_DATA_TITLE"><i class="fas fa-calendar-check"></i> بيانات اليوم المختار</div>
      <div id="DJ_DAY_DATA_CONTENT"></div>
    </div>
  </div>

  <!-- ── FORM PANEL ──────────────────────────────────── -->
  <div id="DJ_PANEL_form" class="DJ-TABPANEL">

    <!-- Date Badge -->
    <div class="DJ-DATE-BADGE">
      <span><i class="fas fa-calendar-day" style="margin-left:5px;"></i>اليوم</span>
      <span id="DJ_DATE"></span>
    </div>

    <!-- 1. MOOD TRACKER -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-smile"></i> الحالة العامة</div>
      <div class="DJ-MOOD-ROW">
        <button class="DJ-MOOD-BTN" data-mood="5"><span class="DJ-EMO">😁</span>ممتاز</button>
        <button class="DJ-MOOD-BTN" data-mood="4"><span class="DJ-EMO">🙂</span>جيد</button>
        <button class="DJ-MOOD-BTN" data-mood="3"><span class="DJ-EMO">😐</span>عادي</button>
        <button class="DJ-MOOD-BTN" data-mood="2"><span class="DJ-EMO">😔</span>متعب</button>
        <button class="DJ-MOOD-BTN" data-mood="1"><span class="DJ-EMO">😞</span>سيء</button>
      </div>
      <div class="FG" style="margin-bottom:0;">
        <textarea class="FI FTA" id="DL_FEEL" placeholder="صف حالتك اليوم..." style="min-height:58px;"></textarea>
      </div>
    </div>

    <!-- 2. VITAL SIGNS -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-heartbeat"></i> المؤشرات الحيوية</div>
      <div class="DJ-VITALS-GRID">
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-tachometer-alt"></i> ضغط الدم</div>
          <input class="DJ-VITAL-IN" id="DL_BP" placeholder="120/80">
          <div class="DJ-VITAL-UNIT">mmHg</div>
        </div>
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-candy-cane"></i> السكر</div>
          <input class="DJ-VITAL-IN" id="DL_SG" placeholder="110">
          <div class="DJ-VITAL-UNIT">mg/dL</div>
        </div>
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-heart"></i> النبض</div>
          <input class="DJ-VITAL-IN" id="DL_HR" placeholder="72">
          <div class="DJ-VITAL-UNIT">bpm</div>
        </div>
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-thermometer-half"></i> الحرارة</div>
          <input class="DJ-VITAL-IN" id="DL_TM" placeholder="37.0">
          <div class="DJ-VITAL-UNIT">°C</div>
        </div>
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-lungs"></i> الأكسجين SpO2</div>
          <input class="DJ-VITAL-IN" id="DL_SPO2" placeholder="98">
          <div class="DJ-VITAL-UNIT">%</div>
        </div>
        <div class="DJ-VITAL">
          <div class="DJ-VITAL-LBL"><i class="fas fa-weight"></i> الوزن (اختياري)</div>
          <input class="DJ-VITAL-IN" id="DL_WEIGHT" placeholder="70">
          <div class="DJ-VITAL-UNIT">kg</div>
        </div>
      </div>
    </div>

    <!-- 3. SYMPTOMS -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-stethoscope"></i> الأعراض</div>
      <div class="DJ-SYM-GRID">
        <label class="DJ-SYM" data-sym="headache">      <input type="checkbox"><span class="DJ-SYM-CHK"></span>🤕 صداع</label>
        <label class="DJ-SYM" data-sym="dizziness">     <input type="checkbox"><span class="DJ-SYM-CHK"></span>😵 دوخة</label>
        <label class="DJ-SYM" data-sym="chest_pain">    <input type="checkbox"><span class="DJ-SYM-CHK"></span>💔 ألم صدر</label>
        <label class="DJ-SYM" data-sym="breathless">    <input type="checkbox"><span class="DJ-SYM-CHK"></span>😮‍💨 ضيق تنفس</label>
        <label class="DJ-SYM" data-sym="severe_fatigue"><input type="checkbox"><span class="DJ-SYM-CHK"></span>😩 تعب شديد</label>
        <label class="DJ-SYM" data-sym="nausea">        <input type="checkbox"><span class="DJ-SYM-CHK"></span>🤢 غثيان</label>
        <label class="DJ-SYM" data-sym="cough">         <input type="checkbox"><span class="DJ-SYM-CHK"></span>😷 سعال</label>
        <label class="DJ-SYM" data-sym="fever">         <input type="checkbox"><span class="DJ-SYM-CHK"></span>🌡️ حمى</label>
        <label class="DJ-SYM" data-sym="swelling">      <input type="checkbox"><span class="DJ-SYM-CHK"></span>🦵 تورم الرجلين</label>
        <label class="DJ-SYM" data-sym="other">         <input type="checkbox"><span class="DJ-SYM-CHK"></span>➕ أخرى</label>
      </div>
    </div>

    <!-- 4. PAIN LEVEL -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-band-aid"></i> مستوى الألم</div>
      <div class="DJ-PAIN-ROW">
        <span style="font-size:11px;color:#9ca3af;">0</span>
        <input type="range" class="DJ-PAIN-SLIDER" id="DJ_PAIN" min="0" max="10" value="0">
        <span style="font-size:11px;color:#9ca3af;">10</span>
        <div class="DJ-PAIN-VAL" id="DJ_PAIN_VAL">0</div>
      </div>
      <div class="DJ-PAIN-LABELS"><span>لا ألم</span><span>ألم متوسط</span><span>ألم شديد</span></div>
    </div>

    <!-- 5. MEDICATION -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-pills"></i> الدواء</div>
      <div class="DJ-BTN-GROUP">
        <button class="DJ-OPT-BTN DJ-MED-BTN" data-val="yes"> ✅ تناولته</button>
        <button class="DJ-OPT-BTN DJ-MED-BTN" data-val="late">⏰ متأخر</button>
        <button class="DJ-OPT-BTN DJ-MED-BTN" data-val="no">  ❌ لم أتناوله</button>
      </div>
    </div>

    <!-- 6. SLEEP -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-moon"></i> النوم</div>
      <div class="DJ-SLEEP-ROW">
        <div>
          <div class="FL" style="margin-bottom:6px;">عدد الساعات</div>
          <div class="DJ-SLEEP-HRS-WRAP">
            <button class="DJ-SLEEP-BTN" id="DJ_SLEEP_DN">−</button>
            <div class="DJ-SLEEP-NUM" id="DJ_SLEEP_NUM">7</div>
            <button class="DJ-SLEEP-BTN" id="DJ_SLEEP_UP">+</button>
            <span style="font-size:11px;color:#9ca3af;">ساعة</span>
          </div>
        </div>
        <div>
          <div class="FL" style="margin-bottom:6px;">جودة النوم</div>
          <div style="display:flex;flex-direction:column;gap:4px;">
            <button class="DJ-OPT-BTN DJ-SQ-BTN" data-val="excellent" style="font-size:10px;">😴 ممتاز</button>
            <button class="DJ-OPT-BTN DJ-SQ-BTN" data-val="good"      style="font-size:10px;">🙂 جيد</button>
            <button class="DJ-OPT-BTN DJ-SQ-BTN" data-val="fair"      style="font-size:10px;">😐 متقطع</button>
            <button class="DJ-OPT-BTN DJ-SQ-BTN" data-val="poor"      style="font-size:10px;">😞 سيء</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 7. WATER -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-tint"></i> الماء اليومي</div>
      <div class="DJ-WATER-ROW">
        <div class="DJ-WATER-CUPS">
          <span class="DJ-CUP" data-i="0">🥤</span>
          <span class="DJ-CUP" data-i="1">🥤</span>
          <span class="DJ-CUP" data-i="2">🥤</span>
          <span class="DJ-CUP" data-i="3">🥤</span>
          <span class="DJ-CUP" data-i="4">🥤</span>
          <span class="DJ-CUP" data-i="5">🥤</span>
          <span class="DJ-CUP" data-i="6">🥤</span>
          <span class="DJ-CUP" data-i="7">🥤</span>
        </div>
        <div class="DJ-WATER-NUM" id="DJ_WATER_NUM">0</div>
        <span style="font-size:11px;color:#9ca3af;">أكواب</span>
      </div>
    </div>

    <!-- 8. ACTIVITY -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-running"></i> النشاط البدني</div>
      <div class="DJ-BTN-GROUP">
        <button class="DJ-OPT-BTN DJ-ACT-BTN2" data-val="low">    🛋️ قليل</button>
        <button class="DJ-OPT-BTN DJ-ACT-BTN2" data-val="medium">  🚶 متوسط</button>
        <button class="DJ-OPT-BTN DJ-ACT-BTN2" data-val="high">    🏃 نشيط</button>
      </div>
    </div>

    <!-- 9. NUTRITION -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-utensils"></i> التغذية</div>
      <div class="DJ-BTN-GROUP" style="flex-wrap:wrap;">
        <button class="DJ-OPT-BTN DJ-NUT-BTN" data-val="healthy"      style="min-width:calc(50% - 3px);">🥗 غذاء صحي</button>
        <button class="DJ-OPT-BTN DJ-NUT-BTN" data-val="high_sugar"   style="min-width:calc(50% - 3px);">🍬 سكريات كثيرة</button>
        <button class="DJ-OPT-BTN DJ-NUT-BTN" data-val="high_fat"     style="min-width:calc(50% - 3px);">🧈 دهون كثيرة</button>
        <button class="DJ-OPT-BTN DJ-NUT-BTN" data-val="no_appetite"  style="min-width:calc(50% - 3px);">😶 فقدان شهية</button>
      </div>
    </div>

    <!-- 10. NOTES -->
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-sticky-note"></i> ملاحظات إضافية</div>
      <textarea class="FI FTA" id="DL_NOTE" placeholder="أي ملاحظات صحية إضافية..." style="min-height:65px;margin-bottom:0;"></textarea>
    </div>

    <!-- SAVE BUTTON -->
    <button class="DJ-SAVE-BTN" onclick="analyzeDaily()">
      <i class="fas fa-brain"></i> حفظ وتحليل حالتي الصحية
    </button>

    <!-- RESULTS AREA (filled by JS) -->
    <div id="DLR"></div>

  </div><!-- /DJ_PANEL_form -->

  <!-- ── HISTORY PANEL ────────────────────────────────── -->
  <div id="DJ_PANEL_history" class="DJ-TABPANEL" style="display:none;">
    <div class="DJ-CARD">
      <div class="DJ-STITLE"><i class="fas fa-history"></i> سجل الأيام السابقة</div>
      <div id="DJ_HIST_LIST">
        <div class="DJ-LOADING"><div class="DJ-SPINNER"></div></div>
      </div>
    </div>
  </div>

</div><!-- /VDL -->

<!-- ===== PREG VIEW — Smart Pregnancy & Chronic Disease Center ===== -->
<!-- MedChifaGiz v2 — Scoped under #VPR -->
<!-- ⚡ AI_INTEGRATION: See pregnancy_chronic.js for Gemini integration points -->
<div class="VW" id="VPR">

  <!-- Main Title -->
  <div class="ST">
    <i class="fas fa-heartbeat" style="color:#00b4d8;"></i>
    <span data-t="pregChronicTitle">متابعة الحوامل والأمراض المزمنة</span>
  </div>

  <!-- ══ TAB SWITCHER ══ -->
  <div class="pc-tabs">
    <button class="pc-tab-btn active" data-tab="preg" onclick="pcSwitchTab('preg')">
      🤰 متابعة الحمل
    </button>
    <button class="pc-tab-btn" data-tab="chronic" onclick="pcSwitchTab('chronic')">
      💊 الأمراض المزمنة
    </button>
  </div>

  <!-- ══════════════════════════════════
       TAB 1: PREGNANCY CENTER
  ══════════════════════════════════ -->
  <div id="pc-panel-preg" class="pc-tab-panel pc-animate">

    <!-- ══════════════════════════════════
         بطاقة الحامل — Pregnancy Card
    ══════════════════════════════════ -->

    <!-- VIEW MODE: Pregnancy Card -->
    <div id="preg-card-view" style="display:none;">
      <div class="preg-card-header">
        <div class="preg-card-avatar">🩷</div>
        <div>
          <div class="preg-card-title">بطاقة الحامل</div>
          <div class="preg-card-sub">Pregnancy Medical Card</div>
        </div>
        <button class="preg-edit-btn" onclick="pregShowForm()">
          <i class="fas fa-pen"></i> تعديل
        </button>
      </div>
      <div class="preg-summary-grid" id="preg-summary-grid"><!-- filled by JS --></div>
    </div>

    <!-- FORM MODE: Fill Pregnancy Info -->
    <div id="preg-card-form">

      <div class="preg-form-section-title">
        <span>🩷</span> بطاقة الحامل
      </div>

      <!-- ① معلومات الحمل -->
      <div class="preg-form-card">
        <div class="preg-form-card-label"><i class="fas fa-baby"></i> معلومات الحمل</div>

        <div class="preg-fields-row">
          <div class="preg-field">
            <label>Nombre de grossesse — عدد مرات الحمل</label>
            <input type="number" id="pf-grossesse" class="preg-input" min="1" max="20" placeholder="مثال: 2">
          </div>
          <div class="preg-field">
            <label>P (Parité) — عدد الولادات بأطفال أحياء</label>
            <input type="number" id="pf-parite" class="preg-input" min="0" max="20" placeholder="مثال: 1" oninput="pregToggleChildren()">
          </div>
        </div>

        <!-- قائمة الأطفال -->
        <div id="preg-children-wrap" style="display:none; margin-top:10px;">
          <div class="preg-children-label">
            <i class="fas fa-child"></i> معلومات الأطفال
          </div>
          <div id="preg-children-list"><!-- built by JS --></div>
          <button class="preg-add-child-btn" onclick="pregAddChild()">
            <i class="fas fa-plus-circle"></i> إضافة طفل
          </button>
        </div>
      </div>

      <!-- ② التاريخ الطبي للحمل -->
      <div class="preg-form-card">
        <div class="preg-form-card-label"><i class="fas fa-history"></i> التاريخ الطبي للحمل</div>

        <div class="preg-fields-row">
          <div class="preg-field">
            <label>Nombre d'avortement — عدد الإجهاضات</label>
            <input type="number" id="pf-avortement" class="preg-input" min="0" max="20" placeholder="0">
          </div>
          <div class="preg-field">
            <label>نوع الولادة السابقة</label>
            <select id="pf-birth-type" class="preg-input">
              <option value="">— اختر —</option>
              <option value="ولادة طبيعية">ولادة طبيعية</option>
              <option value="قيصرية (Césarienne)">قيصرية (Césarienne)</option>
            </select>
          </div>
        </div>

        <div class="preg-fields-row" style="margin-top:10px;">
          <div class="preg-field">
            <label>هل الأطفال ولدوا</label>
            <select id="pf-birth-timing" class="preg-input">
              <option value="">— اختر —</option>
              <option value="في وقتهم الطبيعي">في وقتهم الطبيعي</option>
              <option value="قبل الوقت (Prématuré)">قبل الوقت (Prématuré)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ③ الحالة الصحية -->
      <div class="preg-form-card">
        <div class="preg-form-card-label"><i class="fas fa-heartbeat"></i> الحالة الصحية</div>

        <div class="preg-fields-row">
          <div class="preg-field">
            <label>الحساسية</label>
            <input type="text" id="pf-allergy" class="preg-input" placeholder="مثال: بنسلين، غبار...">
          </div>
          <div class="preg-field">
            <label>زمرة الدم</label>
            <select id="pf-blood" class="preg-input">
              <option value="">— اختر —</option>
              <option>A+</option><option>A-</option>
              <option>B+</option><option>B-</option>
              <option>AB+</option><option>AB-</option>
              <option>O+</option><option>O-</option>
            </select>
          </div>
        </div>

        <div class="preg-fields-row" style="margin-top:10px;">
          <div class="preg-field preg-field-full">
            <label>الأمراض المزمنة</label>
            <textarea id="pf-chronic" class="preg-input preg-textarea" placeholder="مثال: سكري، ضغط الدم..."></textarea>
          </div>
        </div>

        <div class="preg-fields-row" style="margin-top:10px;">
          <div class="preg-field">
            <label>هل أخذتِ اللقاحات الخاصة بالحمل؟</label>
            <div class="preg-toggle-group">
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-vaccine" value="نعم"> نعم
              </label>
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-vaccine" value="لا" checked> لا
              </label>
            </div>
          </div>
          <div class="preg-field">
            <label>هل تتناولين أدوية حالياً؟</label>
            <div class="preg-toggle-group">
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-meds" value="نعم" onclick="pregToggleMeds(true)"> نعم
              </label>
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-meds" value="لا" checked onclick="pregToggleMeds(false)"> لا
              </label>
            </div>
          </div>
        </div>

        <!-- الأدوية -->
        <div id="preg-meds-wrap" style="display:none; margin-top:10px;">
          <div id="preg-meds-list"><!-- built by JS --></div>
          <button class="preg-add-child-btn" onclick="pregAddMed()">
            <i class="fas fa-plus-circle"></i> إضافة دواء
          </button>
        </div>
      </div>

      <!-- ④ نمط الحياة -->
      <div class="preg-form-card">
        <div class="preg-form-card-label"><i class="fas fa-leaf"></i> نمط الحياة — Mode de vie</div>

        <div class="preg-fields-row">
          <div class="preg-field">
            <label>التدخين</label>
            <div class="preg-toggle-group">
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-smoke" value="نعم"> نعم
              </label>
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-smoke" value="لا" checked> لا
              </label>
            </div>
          </div>
          <div class="preg-field">
            <label>الكحول</label>
            <div class="preg-toggle-group">
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-alcohol" value="نعم"> نعم
              </label>
              <label class="preg-toggle-opt">
                <input type="radio" name="pf-alcohol" value="لا" checked> لا
              </label>
            </div>
          </div>
          <div class="preg-field">
            <label>النشاط البدني</label>
            <select id="pf-activity" class="preg-input">
              <option value="ضعيف">ضعيف</option>
              <option value="متوسط" selected>متوسط</option>
              <option value="نشيط">نشيط</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <button class="preg-save-btn" onclick="pregSaveCard()">
        <i class="fas fa-save"></i> 💾 حفظ بطاقة الحامل
      </button>

    </div><!-- /preg-card-form -->

    <!-- ══════════════════════════════════
         الإضافات المفيدة
    ══════════════════════════════════ -->

    <!-- أ- المتابعة اليومية -->
    <div class="preg-extra-card">
      <div class="preg-extra-title"><span>📅</span> المتابعة اليومية</div>

      <div class="preg-daily-form">
        <div class="preg-field">
          <label>الوزن (كغ)</label>
          <input type="number" id="pd-weight" class="preg-input" step="0.1" placeholder="67.5">
        </div>
        <div class="preg-field">
          <label>ضغط الدم</label>
          <input type="text" id="pd-bp" class="preg-input" placeholder="120/80">
        </div>
        <div class="preg-field preg-field-full">
          <label>ملاحظة اليوم</label>
          <input type="text" id="pd-note" class="preg-input" placeholder="كيف تشعرين اليوم؟">
        </div>
      </div>
      <button class="preg-daily-save-btn" onclick="pregSaveDaily()">
        <i class="fas fa-plus-circle"></i> تسجيل اليوم
      </button>

      <!-- Timeline -->
      <div class="preg-timeline" id="preg-daily-timeline">
        <div class="preg-tl-item">
          <div class="preg-tl-dot"></div>
          <div class="preg-tl-content">
            <div class="preg-tl-date">اليوم الأول — مثال</div>
            <div class="preg-tl-vals">⚖️ 67 كغ &nbsp;|&nbsp; 🩸 118/76 &nbsp;|&nbsp; 📝 كل شيء بخير</div>
          </div>
        </div>
        <div class="preg-tl-item">
          <div class="preg-tl-dot"></div>
          <div class="preg-tl-content">
            <div class="preg-tl-date">اليوم الثاني — مثال</div>
            <div class="preg-tl-vals">⚖️ 67.3 كغ &nbsp;|&nbsp; 🩸 120/78 &nbsp;|&nbsp; 📝 غثيان خفيف صباحاً</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ب- موعد الولادة المتوقع -->
    <div class="preg-extra-card">
      <div class="preg-extra-title"><span>📍</span> موعد الولادة المتوقع</div>
      <div class="preg-edd-wrap">
        <div class="preg-field">
          <label>تاريخ آخر دورة شهرية (LMP)</label>
          <input type="date" id="pd-lmp" class="preg-input" oninput="pregCalcEDD()">
        </div>
        <div class="preg-edd-result" id="preg-edd-result" style="display:none;">
          <div class="preg-edd-label">📅 تاريخ الولادة المتوقع (EDD)</div>
          <div class="preg-edd-date" id="preg-edd-date">—</div>
          <div class="preg-edd-weeks" id="preg-edd-weeks"></div>
        </div>
      </div>
    </div>

    <!-- ج- نصائح صحية -->
    <div class="preg-extra-card">
      <div class="preg-extra-title"><span>💡</span> نصائح صحية</div>
      <div class="preg-tips-grid">
        <div class="preg-tip-item">
          <div class="preg-tip-icon">💧</div>
          <div class="preg-tip-text">اشربي 8-10 أكواب ماء يومياً للحفاظ على الترطيب</div>
        </div>
        <div class="preg-tip-item">
          <div class="preg-tip-icon">🩺</div>
          <div class="preg-tip-text">حافظي على مواعيد متابعة الطبيب الدورية بانتظام</div>
        </div>
        <div class="preg-tip-item">
          <div class="preg-tip-icon">💊</div>
          <div class="preg-tip-text">لا تتناولي أي دواء دون استشارة طبيبتك المختصة</div>
        </div>
        <div class="preg-tip-item">
          <div class="preg-tip-icon">🥗</div>
          <div class="preg-tip-text">تناولي وجبات متوازنة غنية بالحديد وحمض الفوليك</div>
        </div>
        <div class="preg-tip-item">
          <div class="preg-tip-icon">🚶</div>
          <div class="preg-tip-text">ممارسة المشي الخفيف 20 دقيقة يومياً مفيدة جداً</div>
        </div>
        <div class="preg-tip-item">
          <div class="preg-tip-icon">🛌</div>
          <div class="preg-tip-text">احرصي على النوم 8 ساعات وتجنبي الإجهاد المفرط</div>
        </div>
      </div>
    </div>



  </div><!-- /pc-panel-preg -->


  <!-- ══════════════════════════════════
       TAB 2: CHRONIC DISEASE CENTER
  ══════════════════════════════════ -->
  <div id="pc-panel-chronic" class="pc-tab-panel" style="display:none;">

    <!-- ① Disease Selector -->
    <div class="pc-section-card">
      <div class="pc-section-title"><span>🏥</span> اختر نوع المرض</div>
      <div class="disease-grid">
        <div class="disease-card" data-disease="diabetes" onclick="pcSelectDisease('diabetes')">
          <div class="d-icon">🍬</div>
          <div class="d-name">السكري</div>
        </div>
        <div class="disease-card" data-disease="bp" onclick="pcSelectDisease('bp')">
          <div class="d-icon">❤️</div>
          <div class="d-name">الضغط</div>
        </div>
        <div class="disease-card" data-disease="heart" onclick="pcSelectDisease('heart')">
          <div class="d-icon">🫀</div>
          <div class="d-name">القلب</div>
        </div>
        <div class="disease-card" data-disease="asthma" onclick="pcSelectDisease('asthma')">
          <div class="d-icon">🫁</div>
          <div class="d-name">الربو</div>
        </div>
        <div class="disease-card" data-disease="kidney" onclick="pcSelectDisease('kidney')">
          <div class="d-icon">🫘</div>
          <div class="d-name">الكلى</div>
        </div>
        <div class="disease-card" data-disease="other" onclick="pcSelectDisease('other')">
          <div class="d-icon">➕</div>
          <div class="d-name">آخر</div>
        </div>
      </div>

      <?php
        // ⚡ AI_INTEGRATION: Pre-highlight based on DB chronic_diseases field
        $chronic = strtolower($patient['chronic_diseases'] ?? '');
        $autoDisease = '';
        if (strpos($chronic, 'سكر') !== false || strpos($chronic, 'diabetes') !== false) $autoDisease = 'diabetes';
        elseif (strpos($chronic, 'ضغط') !== false || strpos($chronic, 'hyper') !== false) $autoDisease = 'bp';
        elseif (strpos($chronic, 'قلب') !== false || strpos($chronic, 'heart') !== false) $autoDisease = 'heart';
        if ($autoDisease): ?>
        <div style="font-size:11px;color:#9ca3af;text-align:center;margin-top:8px;padding:6px;background:rgba(0,180,216,0.05);border-radius:8px;">
          <i class="fas fa-info-circle" style="color:#00b4d8;"></i>
          تم اكتشاف:
          <strong style="color:#0077b6;">
            <?= htmlspecialchars($patient['chronic_diseases']) ?>
          </strong>
          من ملفك الطبي
        </div>
      <?php endif; ?>
    </div>

    <!-- ② Dynamic Disease Forms -->

    <!-- DIABETES FORM -->
    <div id="pc-form-diabetes" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>🍬</span> قراءات السكري</div>
      <div class="disease-form-grid">
        <div class="pc-field">
          <label>سكر صائم (mg/dL)</label>
          <input type="number" id="pc-dia-fasting" class="pc-input" placeholder="90" min="40" max="500" oninput="pcUpdateRiskMeter('diabetes')">
        </div>
        <div class="pc-field">
          <label>بعد الأكل (mg/dL)</label>
          <input type="number" id="pc-dia-postmeal" class="pc-input" placeholder="130" min="40" max="600" oninput="pcUpdateRiskMeter('diabetes')">
        </div>
        <div class="pc-field">
          <label>HbA1c (%)</label>
          <input type="number" id="pc-dia-hba1c" class="pc-input" placeholder="6.5" step="0.1" min="3" max="15" oninput="pcUpdateRiskMeter('diabetes')">
        </div>
        <div class="pc-field">
          <label>نوع الدواء</label>
          <input type="text" class="pc-input" placeholder="ميتفورمين..." value="<?= htmlspecialchars($patient['medications'] ?? '') ?>">
        </div>
        <div class="pc-field pc-input-full">
          <label>الأعراض الحالية</label>
          <input type="text" class="pc-input" placeholder="عطش، تعب، تبول متكرر...">
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('diabetes')">
        <i class="fas fa-save"></i> حفظ القراءات
      </button>
    </div>

    <!-- BLOOD PRESSURE FORM -->
    <div id="pc-form-bp" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>❤️</span> قراءات الضغط</div>
      <div class="disease-form-grid">
        <div class="pc-field">
          <label>الانقباضي SYS (mmHg)</label>
          <input type="number" id="pc-bp-sys" class="pc-input" placeholder="120" min="60" max="250" oninput="pcUpdateRiskMeter('bp')">
        </div>
        <div class="pc-field">
          <label>الانبساطي DIA (mmHg)</label>
          <input type="number" id="pc-bp-dia" class="pc-input" placeholder="80" min="40" max="150" oninput="pcUpdateRiskMeter('bp')">
        </div>
        <div class="pc-field">
          <label>النبض Pulse (bpm)</label>
          <input type="number" id="pc-bp-pulse" class="pc-input" placeholder="72" min="30" max="200">
        </div>
        <div class="pc-field">
          <label>الدواء</label>
          <input type="text" class="pc-input" placeholder="أملوديبين..." value="<?= htmlspecialchars($patient['medications'] ?? '') ?>">
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('bp')">
        <i class="fas fa-save"></i> حفظ القراءات
      </button>
    </div>

    <!-- HEART FORM -->
    <div id="pc-form-heart" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>🫀</span> متابعة القلب</div>
      <div class="disease-form-grid">
        <div class="pc-field">
          <label>النبض (bpm)</label>
          <input type="number" class="pc-input" placeholder="72" min="30" max="220">
        </div>
        <div class="pc-field">
          <label>الضغط (mmHg)</label>
          <input type="text" class="pc-input" placeholder="120/80">
        </div>
        <div class="pc-field">
          <label>ألم صدر (0-10)</label>
          <input type="number" class="pc-input" placeholder="0" min="0" max="10">
        </div>
        <div class="pc-field">
          <label>دوخة / إغماء</label>
          <select class="pc-input">
            <option value="0">لا يوجد</option>
            <option value="1">أحياناً</option>
            <option value="2">متكرر</option>
            <option value="3">شديد</option>
          </select>
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('heart')">
        <i class="fas fa-save"></i> حفظ القراءات
      </button>
    </div>

    <!-- ASTHMA FORM -->
    <div id="pc-form-asthma" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>🫁</span> متابعة الربو</div>
      <div class="disease-form-grid">
        <div class="pc-field">
          <label>ضيق التنفس (0-10)</label>
          <input type="number" class="pc-input" placeholder="0" min="0" max="10">
        </div>
        <div class="pc-field">
          <label>البخاخ اليوم</label>
          <input type="number" class="pc-input" placeholder="0" min="0" max="20">
        </div>
        <div class="pc-field">
          <label>محفزات اليوم</label>
          <input type="text" class="pc-input" placeholder="غبار، دخان، برد...">
        </div>
        <div class="pc-field">
          <label>نشاط بدني</label>
          <select class="pc-input">
            <option>طبيعي</option>
            <option>محدود</option>
            <option>محظور</option>
          </select>
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('asthma')">
        <i class="fas fa-save"></i> حفظ القراءات
      </button>
    </div>

    <!-- KIDNEY FORM -->
    <div id="pc-form-kidney" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>🫘</span> متابعة الكلى</div>
      <div class="disease-form-grid">
        <div class="pc-field">
          <label>الكرياتينين (mg/dL)</label>
          <input type="number" class="pc-input" placeholder="1.0" step="0.1">
        </div>
        <div class="pc-field">
          <label>اليوريا (mg/dL)</label>
          <input type="number" class="pc-input" placeholder="30">
        </div>
        <div class="pc-field">
          <label>ضغط الدم (mmHg)</label>
          <input type="text" class="pc-input" placeholder="120/80">
        </div>
        <div class="pc-field">
          <label>كمية الماء (لتر/يوم)</label>
          <input type="number" class="pc-input" placeholder="2.5" step="0.1">
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('kidney')">
        <i class="fas fa-save"></i> حفظ القراءات
      </button>
    </div>

    <!-- OTHER FORM -->
    <div id="pc-form-other" class="disease-form-panel pc-section-card">
      <div class="pc-section-title"><span>➕</span> مرض آخر</div>
      <div class="disease-form-grid">
        <div class="pc-field pc-input-full">
          <label>نوع المرض</label>
          <input type="text" class="pc-input" placeholder="أدخل نوع المرض المزمن...">
        </div>
        <div class="pc-field pc-input-full">
          <label>الأمراض المزمنة (من ملفك)</label>
          <input type="text" class="pc-input" value="<?= htmlspecialchars($patient['chronic_diseases'] ?? '') ?>" readonly>
        </div>
        <div class="pc-field pc-input-full">
          <label>الأعراض الحالية</label>
          <input type="text" class="pc-input" placeholder="اكتب الأعراض...">
        </div>
        <div class="pc-field pc-input-full">
          <label>الأدوية</label>
          <input type="text" class="pc-input" value="<?= htmlspecialchars($patient['medications'] ?? '') ?>">
        </div>
      </div>
      <button class="pc-save-btn" onclick="pcSaveReadings('other')">
        <i class="fas fa-save"></i> حفظ
      </button>
    </div>

    <!-- ③ AI Risk Analysis Meter -->
    <div class="pc-section-card">
      <div class="pc-section-title">
        <span>🤖</span> تحليل AI للمخاطر
        <span class="stag">ذكاء اصطناعي</span>
      </div>
      <div class="risk-meter-wrap">
        <!-- Arc Gauge -->
        <div class="risk-arc-wrap">
          <svg viewBox="0 0 160 90" width="160" height="90">
            <!-- Background arc -->
            <path d="M 10 85 A 70 70 0 0 1 150 85" fill="none" stroke="#f3f4f6" stroke-width="12" stroke-linecap="round"/>
            <!-- Green zone -->
            <path d="M 10 85 A 70 70 0 0 1 57 23" fill="none" stroke="#22c55e" stroke-width="12" stroke-linecap="round" opacity="0.5"/>
            <!-- Yellow zone -->
            <path d="M 57 23 A 70 70 0 0 1 103 23" fill="none" stroke="#f59e0b" stroke-width="12" stroke-linecap="round" opacity="0.5"/>
            <!-- Red zone -->
            <path d="M 103 23 A 70 70 0 0 1 150 85" fill="none" stroke="#ef4444" stroke-width="12" stroke-linecap="round" opacity="0.5"/>
            <!-- Labels -->
            <text x="8" y="80" fill="#22c55e" font-size="8" font-family="Cairo,sans-serif" font-weight="700">آمن</text>
            <text x="68" y="15" fill="#f59e0b" font-size="8" font-family="Cairo,sans-serif" font-weight="700">تنبيه</text>
            <text x="128" y="80" fill="#ef4444" font-size="8" font-family="Cairo,sans-serif" font-weight="700">خطر</text>
          </svg>
          <!-- Needle -->
          <div class="risk-needle" id="pc-risk-needle" style="--needle-angle:-70deg;"></div>
        </div>
        <div class="risk-status-badge green" id="pc-risk-badge">✅ مستقر</div>
        <div class="risk-explanation" id="pc-risk-explanation">
          اختر نوع المرض وأدخل قراءاتك للحصول على تحليل دقيق من الذكاء الاصطناعي.
        </div>
      </div>
    </div>

    <!-- ⑤ Medication Reminder Center -->
    <div class="pc-section-card">
      <div class="pc-section-title"><span>💊</span> تذكير الأدوية</div>
      <div class="med-reminder-list">
        <?php
          // ⚡ AI_INTEGRATION: Parse medications from $patient['medications'] and display dynamically
          $meds = explode(',', $patient['medications'] ?? 'الدواء الأساسي');
          $times = ['صباحاً 8:00', 'الظهر 13:00', 'مساءً 20:00'];
          foreach(array_slice($meds, 0, 3) as $i => $med):
            $med = trim($med);
            if(empty($med)) $med = 'الدواء المزمن';
        ?>
        <div class="med-reminder-item">
          <div class="med-pill-icon">💊</div>
          <div class="med-info">
            <strong><?= htmlspecialchars($med) ?></strong>
            <span>جرعة يومية</span>
          </div>
          <div class="med-time-badge"><?= $times[$i] ?></div>
        </div>
        <?php endforeach; ?>
        <div class="med-reminder-item">
          <div class="med-pill-icon" style="background:linear-gradient(135deg,#06d6a0,#0077b6);">💧</div>
          <div class="med-info">
            <strong>شرب الماء</strong>
            <span>8 أكواب يومياً</span>
          </div>
          <div class="med-time-badge">طوال اليوم</div>
        </div>
      </div>
    </div>

    <!-- ⑥ Smart Recommendations -->
    <div class="pc-section-card">
      <div class="pc-section-title">
        <span>✨</span> توصيات ذكية
        <span class="stag">AI</span>
      </div>
      <div class="recs-grid">
        <div class="rec-item food">
          <div class="rec-icon">🥗</div>
          <div class="rec-text">
            <strong>الأكل الصحي</strong>
            تجنب السكريات والملح الزائد
          </div>
        </div>
        <div class="rec-item sport">
          <div class="rec-icon">🚶</div>
          <div class="rec-text">
            <strong>النشاط البدني</strong>
            مشي خفيف 30 دقيقة يومياً
          </div>
        </div>
        <div class="rec-item water">
          <div class="rec-icon">💧</div>
          <div class="rec-text">
            <strong>الترطيب</strong>
            2-3 لتر ماء يومياً
          </div>
        </div>
        <div class="rec-item rest">
          <div class="rec-icon">🛌</div>
          <div class="rec-text">
            <strong>الراحة</strong>
            7-8 ساعات نوم منتظمة
          </div>
        </div>
        <div class="rec-item doctor" style="grid-column:1/-1;">
          <div class="rec-icon">🩺</div>
          <div class="rec-text">
            <strong>مراجعة الطبيب</strong>
            فحص دوري كل 3 أشهر للأمراض المزمنة
          </div>
        </div>
      </div>
    </div>



  </div><!-- /pc-panel-chronic -->

</div><!-- /VPR -->


<!-- ===== ANALYSIS / RCODE VIEW ===== -->
<div class="VW" id="VRC">
  <div class="ST"><i class="fas fa-microscope" style="color:#00b4d8;"></i> <span data-t="analysisResults">استقبال نتائج التحاليل</span></div>

  <!-- Tabs: QR or Image -->
  <div class="RES-TABS">
    <button class="TB2 A" onclick="sw(this,'rc','qr')" data-t="qrTab">📷 رمز QR</button>
    <button class="TB2" onclick="sw(this,'rc','img')" data-t="imgTab">🖼️ صورة التحليل</button>
  </div>

  <!-- QR TAB -->
  <div id="rc-qr">
    <div style="background:rgba(255,255,255,.97);border-radius:14px;padding:14px;box-shadow:0 4px 16px rgba(0,77,180,.1);">
      <div onclick="shRC()" style="text-align:center;padding:24px;background:rgba(0,180,216,.06);border-radius:11px;border:2px dashed rgba(0,180,216,.3);cursor:pointer;margin-bottom:10px;">
        <i class="fas fa-qrcode" style="font-size:44px;color:#00b4d8;display:block;margin-bottom:9px;"></i>
        <div style="font-size:13px;font-weight:700;color:#1a2340;" data-t="scanQRLab">امسح رمز QR من المخبر</div>
        <div style="font-size:11px;color:#9ca3af;margin-top:3px;" data-t="tapSimulate">اضغط لمحاكاة المسح</div>
      </div>
      <div id="RCR" style="display:none;">
        <div style="font-weight:700;font-size:13px;color:#00b4d8;margin-bottom:9px;" data-t="labResult">📋 نتيجة التحليل - مخبر النخبة</div>
        <div class="IL">
          <div class="IC" style="cursor:default;"><div class="IH"><span class="IN" data-t="cbcTest">تحليل الدم الشامل CBC</span><span class="PL PG" data-t="normal">طبيعي</span></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:7px;font-size:11px;"><div><span style="color:#9ca3af;" data-t="hemoglobin">الهيموغلوبين: </span><strong>14.5 g/dL</strong></div><div><span style="color:#9ca3af;" data-t="platelets">الصفيحات: </span><strong>250 K/μL</strong></div></div></div>
          <div class="IC" style="cursor:default;"><div class="IH"><span class="IN" data-t="bloodSugar">سكر الدم</span><span class="PL PY" data-t="slightlyHigh">مرتفع قليلاً</span></div><div style="font-size:13px;margin-top:5px;"><strong style="font-size:16px;color:#d97706;">118</strong> mg/dL</div></div>
        </div>
        <div style="display:flex;gap:6px;margin-top:9px;flex-wrap:wrap;">
          <button class="BT BP" onclick="sa(T('saved'))"><i class="fas fa-save"></i> <span data-t="save">حفظ</span></button>
          <button class="BT BO" onclick="shareResult()"><i class="fas fa-share-alt"></i> <span data-t="share">مشاركة</span></button>
          <button class="BT BO" onclick="printResult()"><i class="fas fa-print"></i> <span data-t="print">طباعة</span></button>
          <button class="BT BO" onclick="copyResult()"><i class="fas fa-copy"></i> <span data-t="copy">نسخ</span></button>
        </div>
      </div>
    </div>
  </div>

  <!-- IMAGE TAB -->
  <div id="rc-img" style="display:none;">
    <div style="background:rgba(255,255,255,.97);border-radius:14px;padding:14px;box-shadow:0 4px 16px rgba(0,77,180,.1);">
      <!-- Upload Area -->
      <div class="IMG-UP" onclick="document.getElementById('IMG_INPUT').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p data-t="uploadAnalysis">ارفع صورة التحليل</p>
        <small data-t="uploadFormats">JPG, PNG, PDF مسموح بها</small>
      </div>
      <input type="file" id="IMG_INPUT" accept="image/*,.pdf" style="display:none;" onchange="previewImg(this)">
      <img id="IMG_PRV" class="IMG-PRV" alt="صورة التحليل">
      <!-- After image is shown -->
      <div id="IMG_ACTIONS" style="display:none;">
        <div style="font-weight:700;font-size:13px;color:#00b4d8;margin-bottom:9px;">📋 <span data-t="uploadedResult">نتيجة التحليل المرفوعة</span></div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">
          <button class="BT BP" onclick="saveImg()"><i class="fas fa-save"></i> <span data-t="save">حفظ</span></button>
          <button class="BT BO" onclick="shareImg()"><i class="fas fa-share-alt"></i> <span data-t="share">مشاركة</span></button>
          <button class="BT BO" onclick="printImg()"><i class="fas fa-print"></i> <span data-t="print">طباعة</span></button>
          <button class="BT BO" onclick="copyImg()"><i class="fas fa-copy"></i> <span data-t="copy">نسخ</span></button>
          <button class="BT BR" onclick="removeImg()"><i class="fas fa-trash"></i> <span data-t="remove">حذف</span></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== XRAY VIEW ===== -->
<div class="VW" id="VXRAY">

  <!-- ── Page Header ── -->
  <div class="xray-page-header">
    <div class="xray-page-header-icon">
      <i class="fas fa-x-ray"></i>
    </div>
    <div>
      <div class="xray-page-title">الأشعة الطبية</div>
      <div class="xray-page-subtitle">استقبال ومتابعة نتائج الأشعة والتقارير الطبية</div>
    </div>
  </div>

  <!-- ── 1. بطاقة استقبال الأشعة (QR / Imaging Code) ── -->
  <div class="xray-card" style="margin-bottom:14px;">
    <div class="ST" style="margin-bottom:12px;">
      <i class="fas fa-qrcode" style="color:#00b4d8;"></i>
      استقبال نتيجة أشعة
    </div>

    <!-- Tabs: QR / Code -->
    <div class="RES-TABS" style="margin-bottom:14px;">
      <button class="TB2 A" onclick="xraySwTab(this,'xray','qr')">
        <i class="fas fa-qrcode" style="margin-left:5px;"></i>مسح QR
      </button>
      <button class="TB2" onclick="xraySwTab(this,'xray','code')">
        <i class="fas fa-keyboard" style="margin-left:5px;"></i>رمز الأشعة
      </button>
      <button class="TB2" onclick="xraySwTab(this,'xray','upload')">
        <i class="fas fa-cloud-upload-alt" style="margin-left:5px;"></i>رفع صورة
      </button>
    </div>

    <!-- TAB: QR Scan -->
    <div id="xray-qr">
      <div onclick="xraySimulateScan()" class="xray-qr-zone">
        <div class="xray-qr-corners">
          <span class="xray-corner xray-tl"></span>
          <span class="xray-corner xray-tr"></span>
          <span class="xray-corner xray-bl"></span>
          <span class="xray-corner xray-br"></span>
        </div>
        <i class="fas fa-qrcode" style="font-size:48px;color:#00b4d8;display:block;margin-bottom:10px;opacity:.85;"></i>
        <div style="font-size:13px;font-weight:700;color:#1a2340;">امسح رمز QR من مركز الأشعة</div>
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">اضغط لمحاكاة المسح</div>
        <div class="xray-scan-line" id="xrayScanLine"></div>
      </div>
    </div>

    <!-- TAB: Imaging Code -->
    <div id="xray-code" style="display:none;">
      <div class="xray-input-row">
        <div style="flex:1;position:relative;">
          <i class="fas fa-barcode" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#00b4d8;font-size:16px;pointer-events:none;"></i>
          <input
            id="XRAY_CODE_INPUT"
            type="text"
            placeholder="أدخل رمز الأشعة أو Imaging Code"
            style="width:100%;padding:11px 40px 11px 13px;border:2px solid rgba(0,180,216,.25);border-radius:11px;font-family:'Cairo',sans-serif;font-size:13px;color:#1a2340;outline:none;background:rgba(0,180,216,.03);transition:border .2s;"
            onfocus="this.style.borderColor='#00b4d8'"
            onblur="this.style.borderColor='rgba(0,180,216,.25)'"
          >
        </div>
        <button class="BT BP" onclick="xraySimulateScan()" style="flex-shrink:0;">
          <i class="fas fa-search"></i> بحث
        </button>
      </div>
      <div style="font-size:11px;color:#9ca3af;margin-top:7px;text-align:right;">
        <i class="fas fa-info-circle" style="color:#00b4d8;margin-left:4px;"></i>
        الرمز موجود في وصل الأشعة الصادر عن المركز
      </div>
    </div>

    <!-- TAB: Upload Image -->
    <div id="xray-upload" style="display:none;">
      <div class="IMG-UP" onclick="document.getElementById('XRAY_INPUT2').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>ارفع صورة الأشعة</p>
        <small>JPG, PNG, PDF مسموح بها • X-Ray, IRM, Scanner, Echographie</small>
      </div>
      <input type="file" id="XRAY_INPUT2" accept="image/*,.pdf" style="display:none;" onchange="previewXrayUpload(this)">
      <img id="XRAY_PRV2" class="IMG-PRV" alt="صورة الأشعة">
      <div id="XRAY_UPLOAD_ACTIONS" style="display:none;margin-top:10px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="BT BP" onclick="sa('تم الحفظ ✅')"><i class="fas fa-save"></i> حفظ</button>
          <button class="BT BO" onclick="sa('تمت المشاركة ✅')"><i class="fas fa-share-alt"></i> مشاركة</button>
          <button class="BT BR" onclick="document.getElementById('XRAY_PRV2').style.display='none';document.getElementById('XRAY_UPLOAD_ACTIONS').style.display='none';document.getElementById('XRAY_INPUT2').value='';"><i class="fas fa-trash"></i> حذف</button>
        </div>
      </div>
    </div>
  </div>



  <!-- ── 3. Empty State (hidden when results exist) ── -->
  <div class="xray-card" id="xrayEmptyState" style="display:none;">
    <div class="xray-empty-state">
      <div class="xray-empty-icon">
        <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:72px;height:72px;">
          <circle cx="40" cy="40" r="38" fill="rgba(0,180,216,.07)" stroke="rgba(0,180,216,.2)" stroke-width="1.5" stroke-dasharray="4 3"/>
          <path d="M28 52 Q40 26 52 52" stroke="rgba(0,180,216,.4)" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="40" cy="34" r="8" fill="rgba(0,180,216,.12)" stroke="rgba(0,180,216,.3)" stroke-width="1.5"/>
          <line x1="35" y1="34" x2="45" y2="34" stroke="#00b4d8" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="40" y1="29" x2="40" y2="39" stroke="#00b4d8" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="xray-empty-title">لا توجد نتائج أشعة حالياً</div>
      <div class="xray-empty-sub">استخدم رمز QR أو رقم الأشعة لاستقبال نتائجك من مركز الأشعة</div>
      <button class="BT BP" style="margin-top:14px;" onclick="xraySwTab(document.querySelector('#VXRAY .RES-TABS .TB2'),'xray','qr')">
        <i class="fas fa-qrcode"></i> مسح QR الآن
      </button>
    </div>
  </div>

</div>
<!-- ===== Image Lightbox Modal ===== -->
<div id="xrayImageModal" style="display:none;position:fixed;inset:0;background:rgba(10,22,40,.92);z-index:9000;align-items:center;justify-content:center;flex-direction:column;padding:20px;" onclick="xrayCloseImageModal()">
  <button onclick="xrayCloseImageModal()" style="position:absolute;top:16px;left:16px;background:rgba(255,255,255,.15);border:none;color:#fff;width:38px;height:38px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
  <div style="font-size:13px;color:rgba(255,255,255,.6);margin-bottom:14px;font-family:'Cairo',sans-serif;">أشعة الصدر — PA Chest X-Ray • 20/05/2026</div>
  <svg viewBox="0 0 440 320" xmlns="http://www.w3.org/2000/svg" style="width:min(440px,92vw);border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.6);background:#0a1628;" onclick="event.stopPropagation()">
    <rect width="440" height="320" fill="#0a1628" rx="14"/>
    <rect x="214" y="20" width="12" height="280" fill="none" stroke="#c8d8f0" stroke-width="2" rx="3"/>
    <path d="M226 60 Q295 44 310 76" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M226 92 Q298 74 320 108" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M226 124 Q300 104 324 140" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M226 156 Q300 136 320 172" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M226 188 Q296 168 312 200" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M214 60 Q145 44 130 76" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M214 92 Q142 74 120 108" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M214 124 Q140 104 116 140" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M214 156 Q140 136 120 172" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <path d="M214 188 Q142 168 128 200" fill="none" stroke="#b0c4de" stroke-width="2"/>
    <ellipse cx="156" cy="144" rx="52" ry="76" fill="rgba(0,180,216,.1)" stroke="rgba(0,180,216,.28)" stroke-width="1.5"/>
    <ellipse cx="284" cy="144" rx="52" ry="76" fill="rgba(0,180,216,.1)" stroke="rgba(0,180,216,.28)" stroke-width="1.5"/>
    <ellipse cx="296" cy="156" rx="28" ry="24" fill="rgba(245,158,11,.15)"/>
    <ellipse cx="200" cy="170" rx="32" ry="36" fill="rgba(239,68,68,.08)" stroke="rgba(239,68,68,.2)" stroke-width="1"/>
    <text x="220" y="308" fill="#4a6fa5" font-size="10" text-anchor="middle" font-family="sans-serif">PA Chest X-Ray • MedChifaGiz • 20/05/2026</text>
  </svg>
  <div style="display:flex;gap:10px;margin-top:18px;">
    <button class="BT BP" onclick="event.stopPropagation();sa('جارٍ تحميل PDF... 📥')" style="font-family:'Cairo',sans-serif;"><i class="fas fa-file-pdf"></i> تحميل PDF</button>
    <button class="BT BO" onclick="event.stopPropagation();sa('تمت المشاركة ✅')" style="font-family:'Cairo',sans-serif;border-color:rgba(255,255,255,.3);color:#fff;"><i class="fas fa-share-alt"></i> مشاركة</button>
  </div>
</div>

<!-- ===== SERVICES VIEW ===== -->
<div class="VW" id="VSV">
  <div style="text-align:center;background:rgba(255,255,255,.97);border-radius:14px;padding:30px;box-shadow:0 4px 16px rgba(0,77,180,.1);">
    <div style="font-size:48px;margin-bottom:10px;">🏗️</div>
    <div style="font-size:18px;font-weight:800;color:#00b4d8;margin-bottom:7px;" data-t="comingSoon">قريباً</div>
    <div style="font-size:13px;color:#9ca3af;line-height:1.7;" data-t="comingSoonDesc">قسم الخدمات الصحية المتخصصة قيد التطوير.<br>ستتضمن أمراض الجهاز العصبي وخدمات أخرى.</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     التواصل الطبي — Front-End فقط، Dummy Data فقط
     لا Backend / لا API / لا Fetch / لا LocalStorage
═══════════════════════════════════════════════════════ -->
<div class="VW" id="VMED">
  <div class="medcomm-wrap">

    <!-- العمود الأيمن: قائمة المحادثات -->
    <aside class="medcomm-list">
      <div class="medcomm-list-head">
        <h3><i class="fas fa-comment-medical"></i> التواصل الطبي</h3>
        <div class="medcomm-search">
          <i class="fas fa-search"></i>
          <input type="text" id="medcommSearchInput" placeholder="ابحث عن طبيب أو تخصص..." oninput="medcommFilter(this.value)">
        </div>
      </div>
      <div class="medcomm-convos" id="medcommConvos"></div>
    </aside>

    <!-- العمود الأيسر: نافذة المحادثة -->
    <section class="medcomm-chat">
      <div class="medcomm-chat-header" id="medcommChatHeaderWrap"></div>
      <div class="medcomm-messages" id="medcommMessages"></div>
      <div class="medcomm-input-row">
        <button class="medcomm-icon-btn" title="إرفاق ملف" type="button"><i class="fas fa-paperclip"></i></button>
        <input type="text" id="medcommInput" placeholder="اكتب رسالتك هنا...">
        <button class="medcomm-send-btn" title="إرسال" type="button" onclick="medcommSend()"><i class="fas fa-paper-plane"></i></button>
        <button class="medcomm-icon-btn" id="medcommMicBtn" title="رسالة صوتية" type="button"><i class="fas fa-microphone"></i></button>
        <div class="medcomm-emoji-picker" id="medcommEmojiPicker"></div>
      </div>
    </section>

  </div>
</div>

</div><!-- end MN --></main><!-- end pt-main -->

<!-- ===== FLOATING BUTTONS (right side) ===== -->
<div class="FAB-WRAP" id="FAB-WRAP">
  <div style="position:relative;display:flex;flex-direction:column;align-items:center;">
    <button class="AINB" id="BAINB" onclick="toggleAIChat()">🧠</button>
    <span class="AINB-LBL">ممرض AI</span>
  </div>
  <div style="position:relative;display:flex;flex-direction:column;align-items:center;">
    <button class="SFB" id="BSOS"><i class="fas fa-ambulance"></i></button>
    <span class="SFB-LBL">SOS</span>
  </div>
</div>

<!-- AI Nurse Chat Bubble -->
<div class="ANC" id="ANC">
  <div class="ANC-H">
    <div class="ANC-AV">🧠</div>
    <div class="ANC-HT">
      <strong>الممرض الذكي AI</strong>
      <span>متاح 24/24 • اسألني أي سؤال طبي</span>
    </div>
    <button class="ANC-CL" onclick="toggleAIChat()">✕</button>
  </div>
  <div class="ANC-CHIPS" id="ANC-CHIPS">
    <button class="ANC-CHIP" onclick="askChip(this,'أي تخصص طبي يناسب حالتي؟')">🩺 تخصص مناسب</button>
    <button class="ANC-CHIP" onclick="askChip(this,'ما هي أعراض ارتفاع الضغط؟')">❤️ أعراض الضغط</button>
    <button class="ANC-CHIP" onclick="askChip(this,'متى يجب أن أذهب للطوارئ؟')">🚨 متى أذهب للطوارئ</button>
    <button class="ANC-CHIP" onclick="askChip(this,'ما هي أعراض السكري؟')">🍬 أعراض السكري</button>
    <button class="ANC-CHIP" onclick="askChip(this,'كيف أقيس الضغط بشكل صحيح؟')">📊 قياس الضغط</button>
    <button class="ANC-CHIP" onclick="askChip(this,'ما أسباب الصداع المستمر؟')">🤕 الصداع المستمر</button>
    <button class="ANC-CHIP" onclick="askChip(this,'ما هو طب الأطفال وأين أذهب؟')">👶 طب الأطفال</button>
    <button class="ANC-CHIP" onclick="askChip(this,'كيف أتحكم في سكر الدم؟')">💉 التحكم بالسكر</button>
  </div>
  <div class="ANC-MSGS" id="ANC-MSGS">
    <div class="ANC-MSG U">
      <div class="lbl">🧠 الممرض الذكي</div>
      مرحباً! أنا ممرضك الذكي 👋<br>يمكنني مساعدتك في:<br>• اختيار التخصص الطبي المناسب<br>• الإجابة على أسئلتك الصحية<br>• نصائح طبية عامة<br><br>⚠️ <em>للاستعلام فقط - راجع طبيبك للتشخيص</em>
    </div>
  </div>
  <div class="ANC-IN-ROW">
    <input class="ANC-IN" id="ANC-IN" placeholder="اكتب سؤالك الطبي هنا..." onkeydown="if(event.key==='Enter')sendAIChat()">
    <button class="ANC-SEND" onclick="sendAIChat()"><i class="fas fa-paper-plane"></i></button>
  </div>
</div>

<!-- Bottom nav — hidden, kept for JS compatibility -->
<nav class="BB" style="display:none!important;">
  <button class="NV A" data-v="VH"><i class="fas fa-home"></i><span data-t="home">الرئيسية</span></button>
  <button class="NV" data-v="VAI"><i class="fas fa-robot"></i><span data-t="aiNav">الذكاء AI</span></button>
  <button class="NV" data-v="VRD"><i class="fas fa-id-card"></i><span data-t="recordNav">السجل</span></button>
  <button class="NV" data-v="VDL"><i class="fas fa-heartbeat"></i><span data-t="dailyNav">يومياتي</span></button>
  <button class="NV" data-v="VDR"><i class="fas fa-pills"></i><span data-t="drugsNav">أدويتي</span></button>
  <button class="NV" data-v="VPR"><i class="fas fa-baby-carriage"></i><span data-t="pregNav">حوامل</span></button>
  <button class="NV" data-v="VRC"><i class="fas fa-qrcode"></i><span data-t="analysisNav">تحاليل</span></button>
  <button class="NV" data-v="VXRAY" style="display:none!important;visibility:hidden;position:absolute;pointer-events:none;"><i class="fas fa-x-ray"></i><span>أشعة</span></button>
  <button class="NV" data-v="VSV"><i class="fas fa-briefcase-medical"></i><span data-t="servicesNav">خدمات</span></button>
</nav>

</div><!-- end APP -->

<script>
window.CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
</script>
<script>
/* ═══════════════════════════════════════════════════
   PATIENT SIDEBAR — Navigation & Toggle Logic
   لا يُعدّل أي Logic موجود — يتكامل معه فقط
═══════════════════════════════════════════════════ */

/* ── Sidebar toggle (mobile) ── */
function ptToggleSidebar() {
    var sb = document.getElementById('ptSidebar');
    var ov = document.getElementById('ptSidebarOverlay');
    sb.classList.toggle('pt-sidebar-open');
    ov.classList.toggle('pt-active');
}
function ptCloseSidebar() {
    var sb = document.getElementById('ptSidebar');
    var ov = document.getElementById('ptSidebarOverlay');
    sb.classList.remove('pt-sidebar-open');
    ov.classList.remove('pt-active');
}

/* ── Sidebar navigation — delegates to existing NV logic ── */
function ptNavTo(viewId, el) {
    // Remove active from all sidebar items
    document.querySelectorAll('.pt-snav-item').forEach(function(item) {
        item.classList.remove('pt-active');
    });
    // Set active on clicked item
    if (el) el.classList.add('pt-active');

    // Update page title
    var titles = {
        'VH':    'الخدمات',
        'VAI':   'الذكاء الاصطناعي',
        'VRD':   'بطاقتي الشخصية',
        'VDL':   'يومياتي',
        'VDR':   'أدويتي',
        'VPR':   'الحالات الصحية',
        'VRC':   'التحاليل',
        'VXRAY': 'الأشعة',
        'VSV':   'الخدمات الصحية',
        'VMED':  'التواصل الطبي'
    };
    var titleEl = document.getElementById('ptPageTitle');
    if (titleEl && titles[viewId]) titleEl.textContent = titles[viewId];

    // تهيئة قسم التواصل الطبي عند أول فتح (Dummy Data فقط)
    if (viewId === 'VMED' && typeof medcommInit === 'function') {
        medcommInit();
    }

    // Delegate to existing nav system
    var existingBtn = document.querySelector('.NV[data-v="' + viewId + '"]');
    if (existingBtn) {
        existingBtn.click();
    } else {
        // Fallback: hide all views and show target via both class AND style
        document.querySelectorAll('.VW').forEach(function(v) {
            v.classList.remove('A');
            v.style.display = 'none';
        });
        var target = document.getElementById(viewId);
        if (target) {
            target.classList.add('A');
            target.style.display = 'block';
        }
    }

    // Close sidebar on mobile
    if (window.innerWidth <= 1024) ptCloseSidebar();
}

/* ── Sync sidebar with existing NV buttons ── */
document.querySelectorAll('.NV').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var viewId = this.getAttribute('data-v');
        document.querySelectorAll('.pt-snav-item').forEach(function(item) {
            item.classList.remove('pt-active');
        });
        var sbItem = document.querySelector('.pt-snav-item[data-v="' + viewId + '"]');
        if (sbItem) sbItem.classList.add('pt-active');
    });
});

/* ── Sync patient name from existing JS ── */
(function syncPatientInfo() {
    function doSync() {
        var nameEl = document.getElementById('TNM');
        var avatarEl = document.getElementById('TAV');
        if (nameEl) {
            var name = nameEl.textContent || 'المريض';
            var el1 = document.getElementById('ptSbName');
            var el2 = document.getElementById('ptHdrName');
            if (el1) el1.textContent = name;
            if (el2) el2.textContent = name;
        }
        if (avatarEl) {
            var av = avatarEl.textContent || 'م';
            var av1 = document.getElementById('ptSbAvatar');
            var av2 = document.getElementById('ptHdrAvatar');
            if (av1) av1.textContent = av;
            if (av2) av2.textContent = av;
        }
    }
    // Sync on load and observe changes
    window.addEventListener('load', doSync);
    setTimeout(doSync, 500);
    setTimeout(doSync, 1500);
    var obs = new MutationObserver(doSync);
    var target = document.getElementById('TNM');
    if (target) obs.observe(target, { childList: true, subtree: true, characterData: true });
})();

/* ── Sync notification badge ── */
(function syncBadge() {
    function doSync() {
        var badge = document.getElementById('NBADGE');
        var ptBadge = document.getElementById('ptHdrBadge');
        if (badge && ptBadge) {
            ptBadge.textContent = badge.textContent;
            ptBadge.style.display = badge.textContent ? 'flex' : 'none';
        }
    }
    window.addEventListener('load', doSync);
    setTimeout(doSync, 500);
})();

/* ── Dark mode sync with existing BDK ── */
var _origBDK = document.getElementById('BDK');
if (_origBDK) {
    _origBDK.addEventListener('click', function() {
        setTimeout(function() {
            var isDark = document.body.classList.contains('DK') || document.body.classList.contains('dark-mode');
            var ptBars = document.querySelector('.pt-top-bar');
            var ptSb = document.getElementById('ptSidebar');
        }, 100);
    });
}
</script>
<!-- ===== OVERLAY BACKDROP ===== -->
<div class="OVLAY" id="OVLAY" onclick="closeAllPanels()"></div>

<!-- ===== NOTIFICATION PANEL ===== -->
<div class="NP" id="NP">
  <div class="NP-H">
    <h3><i class="fas fa-bell"></i> الإشعارات</h3>
    <button class="NP-CL" onclick="closeAllPanels()">✕</button>
  </div>

  <div class="NP-BD" id="NP-LIST">
    <?php if(!empty($notifications)): ?>
  
  <?php foreach($notifications as $n): ?>

    <div class="NI2 <?= $n['is_read'] ? 'read' : 'unread' ?>">
      
      <div class="NI2-IC">
        <i class="fas fa-calendar-check"></i>
      </div>

      <div style="flex:1;">
        <div class="NI2-TT">📅 موعد طبي</div>
       <div class="NI2-ST"><?= isset($n['message']) ? nl2br($n['message']) : '' ?></div>
        <div class="NI2-TM"><?= $n['created_at'] ?></div>
      </div>

    </div>

  <?php endforeach; ?>

<?php else: ?>

  <p>ماكان حتى إشعار</p>

<?php endif; ?>
  </div>

  <div class="NP-FT">
    <button onclick="markAllRead()">تحديد الكل كمقروء</button>
  </div>
</div>
<!-- ===== PROFILE PANEL ===== -->
<div class="PP2" id="PP2">
  <div class="PP2-H">
    <button class="PP2-CL" onclick="closeAllPanels()">✕</button>
    <div class="PP2-AV" onclick="document.getElementById('PROF-IMG-IN').click()">
      <img id="PROF-IMG-DISP" alt="">
      <span id="PROF-AV-TXT">م</span>
      <div class="PP2-AV-OVL"><i class="fas fa-camera"></i></div>
    </div>
    <input type="file" id="PROF-IMG-IN" accept="image/*" style="display:none;" onchange="setProfImg(this)">
    <div class="PP2-NM" id="PP2-NM">المريض</div>
    <div class="PP2-ID">MED-2024-0047 • نشط</div>
  </div>
  <div class="PP2-BD">
    <div class="PP2-SEC">
      <div class="PP2-SL">👤 المعلومات الشخصية</div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC"><i class="fas fa-user"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الاسم الكامل</div>
          <input class="PP2-EI" id="PP2-FN" value="المريض" oninput="updateProfileName(this.value)">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC"><i class="fas fa-phone"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">رقم الهاتف</div>
          <input class="PP2-EI" id="PP2-PH" value="0555 123 456">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC"><i class="fas fa-birthday-cake"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">تاريخ الميلاد</div>
          <input class="PP2-EI" id="PP2-BD2" value="15/03/1985">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC"><i class="fas fa-map-marker-alt"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الولاية</div>
          <input class="PP2-EI" id="PP2-WL" value="تلمسان">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
    </div>
    <div class="PP2-SEC">
      <div class="PP2-SL">🏥 المعلومات الطبية</div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-tint"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">زمرة الدم</div>
          <input class="PP2-EI" id="PP2-BG" value="O+" style="color:#ef4444;font-weight:900;">
        </div>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC"><i class="fas fa-weight"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الوزن / الطول</div>
          <input class="PP2-EI" id="PP2-WH" value="78kg / 178cm">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-procedures"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">أمراض مزمنة</div>
          <input class="PP2-EI" id="PP2-CH" value="السكري نوع 2، ارتفاع الضغط">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><i class="fas fa-allergies"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الأدوية الحالية</div>
          <input class="PP2-EI" id="PP2-AL" value="بنسلين">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
    </div>
    <div class="PP2-SEC">
      <div class="PP2-SL">🆘 جهة الاتصال الطارئة</div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-phone-alt"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الاسم</div>
          <input class="PP2-EI" id="PP2-EN" value="فاطمة بن علي (الزوجة)">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
      <div class="PP2-IT">
        <div class="PP2-IT-IC" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-phone"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="PP2-IT-L">الرقم</div>
          <input class="PP2-EI" id="PP2-EP" value="0661 987 654">
        </div>
        <i class="fas fa-pen" style="color:#00b4d8;font-size:11px;cursor:pointer;"></i>
      </div>
    </div>
    <button class="BT BP" style="width:100%;justify-content:center;margin-top:4px;" onclick="saveProfile()">
      <i class="fas fa-save"></i> حفظ البروفايل
    </button>
    <div style="height:24px;"></div>
  </div>
</div>

<!-- ===== LOGO EDITOR PANEL ===== -->
<div class="LE" id="LE">
  <div class="LE-H">
    <h3>🎨 تعديل الشعار والاسم</h3>
    <button class="LE-CL" onclick="closeAllPanels()">✕</button>
  </div>
  <div class="LE-BD">
    <div style="text-align:center;margin-bottom:20px;">
      <div class="LE-LGP" onclick="document.getElementById('LOGO-FILE-IN').click()">
        <img id="LE-LGP-IMG" style="display:none;">
        <span id="LE-LGP-EMJ">🏥</span>
        <div class="LE-LGP-OVL"><i class="fas fa-camera"></i></div>
      </div>
      <input type="file" id="LOGO-FILE-IN" accept="image/*" style="display:none;" onchange="setLogoImg(this)">
      <div style="font-size:12px;color:#9ca3af;margin-top:6px;">اضغط لتغيير الشعار</div>
    </div>
    <div class="LE-SL">اسم التطبيق</div>
    <input class="LE-IN" id="LE-NAME" value="MedChifaGiz" placeholder="اسم التطبيق" oninput="updateAppName(this.value)">
    <div class="LE-SL">اختر رمزاً</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
      <button onclick="setLogoEmoji(this,'🏥')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🏥</button>
      <button onclick="setLogoEmoji(this,'💊')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">💊</button>
      <button onclick="setLogoEmoji(this,'🩺')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🩺</button>
      <button onclick="setLogoEmoji(this,'❤️')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">❤️</button>
      <button onclick="setLogoEmoji(this,'🧬')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🧬</button>
      <button onclick="setLogoEmoji(this,'🔬')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🔬</button>
      <button onclick="setLogoEmoji(this,'🫀')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🫀</button>
      <button onclick="setLogoEmoji(this,'🧪')" style="width:44px;height:44px;border-radius:10px;border:1.5px solid rgba(0,180,216,.2);background:rgba(0,180,216,.06);font-size:22px;cursor:pointer;">🧪</button>
    </div>
    <div class="LE-SL">الوصف / Slogan</div>
    <input class="LE-IN" id="LE-SUB" value="التطبيق الطبي الذكي" placeholder="وصف قصير" oninput="document.querySelector('[data-t=appSub]').textContent=this.value">
    <button class="BT BP" style="width:100%;justify-content:center;" onclick="closeAllPanels();sa(T('saved'))">
      <i class="fas fa-save"></i> حفظ
    </button>
  </div>
</div>

<!-- MODALS -->
<div class="MO" id="MSOS"><div class="MB" style="position:relative;"><button class="MCL" onclick="clM('MSOS')">✕</button><div class="MI">🆘</div><h3 style="color:#dc2626;" data-t="sosTitle">نداء طوارئ SOS</h3><p data-t="sosSub">سيتم إرسال رسالة طوارئ فورية للحماية المدنية وجهة الاتصال الطارئة.</p><div class="FG"><select class="FI"><option data-t="personalEmergency">رقم الطوارئ الشخصي</option><option data-t="civilProt">الحماية المدنية (14)</option><option data-t="both">كلاهما</option></select></div><div class="MBS" style="margin-top:8px;"><button class="BT BR" onclick="clM('MSOS');sa(T('sosSent'))"><i class="fas fa-paper-plane"></i> <span data-t="sendSos">إرسال SOS</span></button><button class="BT BO" onclick="clM('MSOS');sa(T('sosCancelled'))"><span data-t="mistake">أخطأت ❌</span></button></div></div></div>
<div class="MO" id="MLGO"><div class="MB"><div class="MI">👋</div><h3 data-t="logoutTitle">تسجيل الخروج</h3><p data-t="logoutSub">هل أنت متأكد أنك تريد الخروج من حسابك؟</p><div class="MBS"><button class="BT BR" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> <span data-t="yesLogout">نعم، خروج</span></button><button class="BT BO" onclick="clM('MLGO')" data-t="cancel">إلغاء</button></div></div></div>
<div class="MO" id="MAL"><div class="MB"><div class="MI">✅</div><p id="ALTXT"></p><button class="BT BP" style="margin-top:8px;" onclick="clM('MAL')" data-t="ok">حسناً</button></div></div>
<div class="MO" id="MEDIT">
  <div class="MB" style="max-width:650px;max-height:85vh;overflow:auto;position:relative;">

    <button class="MCL" onclick="clM('MEDIT')">✕</button>

<div class="MI LOGO-EDIT">
  <img src="medchifagz.png" alt="logo">
</div>
    <h3 style="color:#00b4d8;">تعديل السجل الطبي</h3>

    <div style="display:flex;gap:8px;margin:15px 0;flex-wrap:wrap;justify-content:center;">
<button class="BT editBtn ACTIVE-EDIT" onclick="openEditTab('tab1')">🪪 شخصية</button>
<button class="BT editBtn" onclick="openEditTab('tab2')">❤️ طبية</button>
<button class="BT editBtn" onclick="openEditTab('tab3')">🚨 طوارئ</button>
    </div>

<!-- TAB1 -->
<div class="editTab" id="tab1">

  <div class="FG">
    <label class="EL">الاسم</label>
    <input class="FI" id="edit_first_name" placeholder="الاسم">
  </div>

  <div class="FG">
    <label class="EL">اللقب</label>
    <input class="FI" id="edit_last_name" placeholder="اللقب">
  </div>

  <div class="FG">
    <label class="EL">تاريخ الميلاد</label>
    <input type="date" class="FI" id="edit_birth_date">
  </div>

  <div class="FG">
    <label class="EL">فصيلة الدم</label>
    <input class="FI" id="edit_blood_type" placeholder="مثال: O+">
  </div>

  <div class="FR">
    <div class="FG">
      <label class="EL">الوزن (كغ)</label>
      <input class="FI" id="edit_weight" placeholder="60">
    </div>

    <div class="FG">
      <label class="EL">الطول (سم)</label>
      <input class="FI" id="edit_height" placeholder="170">
    </div>
  </div>

</div>

<!-- TAB2 -->
<div class="editTab" id="tab2" style="display:none;">

  <div class="FG">
    <label class="EL">الأمراض المزمنة</label>
    <textarea class="FI FTA" id="edit_chronic" placeholder="مثال: السكري، الضغط..."></textarea>
  </div>

  <div class="FG">
    <label class="EL">الأدوية الحالية</label>
    <textarea class="FI FTA" id="edit_allergies" placeholder="مثال: حساسية من البنسلين..."></textarea>
  </div>

  <div class="FG">
    <label class="EL">الطبيب المعالج</label>
    <textarea class="FI FTA" id="edit_medications" placeholder="الأدوية التي يستعملها حالياً"></textarea>
  </div>

  <div class="FG">
    <label class="EL">ملاحظات صحية</label>
    <textarea class="FI FTA" id="edit_notes" placeholder="أي ملاحظات مهمة للطبيب"></textarea>
  </div>

</div>

<!-- TAB3 -->
<div class="editTab" id="tab3" style="display:none;">

  <div class="FG">
    <label class="EL">اسم شخص الطوارئ</label>
    <input class="FI" id="edit_emergency_name" placeholder="الاسم الكامل">
  </div>

  <div class="FG">
    <label class="EL">رقم هاتفه</label>
    <input class="FI" id="edit_emergency_phone" placeholder="05XXXXXXXX">
  </div>

</div>

<div class="MBS" style="margin-top:15px;">
  <button class="BT BP" onclick="saveEditedMedicalRecord()">💾 حفظ التعديل</button>
  <button class="BT BO" onclick="clM('MEDIT')">إلغاء</button>
</div>

</div>
</div>

<div class="MO" id="MCARD">
  <div class="MB" style="max-width:700px;width:78%;padding:0;overflow:hidden;position:relative;">

    <button class="MCL" onclick="document.getElementById('MCARD').style.display='none'" style="z-index:5;">✕</button>

    <div style="
background:linear-gradient(135deg,#f8fffe,#e9fbff,#eefcf6);
padding:25px;
color:#16324f;
">

  <!-- HEADER -->
  <div style="
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:15px;
  margin-bottom:20px;
  flex-wrap:wrap;
  ">

    <div style="display:flex;align-items:center;gap:12px;">
      <img src='medchifagz.png' style='width:55px;height:55px;object-fit:contain'>
      <div>
        <div style="font-size:26px;font-weight:900;color:#0f766e;">
          بطاقتي الصحية
        </div>
        <div style="font-size:13px;color:#6b7280;">
          MedChifaGiz Digital Health Card
        </div>
      </div>
    </div>

    <div style="
    background:#ffffff;
    padding:8px 14px;
    border-radius:999px;
    font-weight:700;
    color:#0ea5a4;
    box-shadow:0 5px 18px rgba(0,0,0,.08);
    ">
      بطاقة رقمية ذكية
    </div>

  </div>


  <!-- CARD -->
  <div style="
  background:rgba(255,255,255,.75);
  border:1px solid rgba(255,255,255,.9);
  backdrop-filter:blur(20px);
  border-radius:28px;
  padding:22px;
  display:flex;
  gap:22px;
  align-items:center;
  flex-wrap:nowrap;
  min-width:650px;
  overflow-x:auto;
  box-shadow:0 15px 40px rgba(16,185,129,.08);
  
  ">

    <!-- avatar -->
    <div style="text-align:center;min-width:120px;">
      <label for="avatarUpload" style="cursor:pointer;">
        <img
          id="patientAvatar"
          src="default-avatar.png"
          style="
          width:110px;
          height:110px;
          border-radius:50%;
          object-fit:cover;
          border:4px solid white;
          box-shadow:0 10px 25px rgba(0,0,0,.12);
          "
        >
      </label>

      <input type="file" id="avatarUpload" accept="image/*" hidden>

      <div style="
      margin-top:8px;
      font-size:12px;
      color:#64748b;
      ">
        اضغط لتغيير الصورة
      </div>
    </div>


    <!-- infos -->
   <div style="flex:1;min-width:180px;white-space:nowrap;">

      <div style="
      font-size:28px;
      font-weight:900;
      margin-bottom:12px;
      color:#0f172a;
      ">
        <?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']) ?>
      </div>

      <div style="margin-bottom:8px;font-size:15px;">
        🆔 رقم الملف:
        <b>MED-<?= $patient['user_id'] ?></b>
      </div>

      <div style="margin-bottom:8px;font-size:15px;">
        🎂 <?= htmlspecialchars($patient['birth_date']) ?>
      </div>

      <div style="
      display:inline-block;
      background:#fff;
      color:#dc2626;
      padding:8px 18px;
      border-radius:999px;
      font-weight:900;
      margin:8px 0;
      box-shadow:0 5px 15px rgba(0,0,0,.08);
      ">
        🩸 <?= htmlspecialchars($patient['blood_type']) ?>
      </div>

      <div style="
      margin-top:10px;
      font-size:15px;
      color:#334155;
      ">
        🚨 <?= htmlspecialchars($patient['emergency_phone'] ?: 'لا يوجد') ?>
      </div>

    </div>


    <!-- QR -->
    <div style="
    width:160px;
    height:160px;
    background:white;
    border-radius:24px;
    padding:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    display:flex;
    align-items:center;
    justify-content:center;
    ">
      <img
      src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=http://192.168.1.18/fix/emergency_card.php?token=<?= $patient['emergency_token'] ?>"
      style="width:100%;height:100%;object-fit:contain;">
    </div>

  </div>


  <!-- buttons -->
  <div style="
  display:flex;
  gap:12px;
  margin-top:18px;
  flex-wrap:wrap;
  ">
    <button class="BT BP" style="flex:1;min-width:180px;">
      عرض التفاصيل
    </button>

    <button class="BT BO" style="flex:1;min-width:180px;">
      تحميل البطاقة
    </button>
  </div>

</div>
  </div>
</div>

<div id="bookingModal">

  <div class="modal-content">

    <span class="close-btn" onclick="closeBooking()">×</span>

    <h3>طلب حجز موعد</h3>

    <!-- hidden: يُعبأ عند فتح الـ popup -->
    <input type="hidden" id="bk_doctor_id">

    <div style="margin-bottom:10px;">
      <label style="display:block;font-size:13px;margin-bottom:4px;color:#555;">الاسم الكامل</label>
      <input type="text" placeholder="الاسم الكامل" id="bk_name"
             value="<?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?>"
             style="width:100%;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:10px;">
      <label style="display:block;font-size:13px;margin-bottom:4px;color:#555;">رقم الهاتف</label>
      <input type="text" placeholder="05XXXXXXXX" id="bk_phone"
             value="<?= htmlspecialchars($patient['phone'] ?? '') ?>"
             style="width:100%;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-size:13px;margin-bottom:4px;color:#555;">نوع الحالة</label>
      <select id="bk_case_type" style="width:100%;box-sizing:border-box;">
        <option value="">اختر نوع الحالة</option>
        <option value="عادية">عادية</option>
        <option value="مستعجلة">مستعجلة 🚨</option>
        <option value="مزمنة">مزمنة 🔄</option>
      </select>
    </div>

    <div id="bk_error" style="color:#dc2626;font-size:13px;margin-bottom:8px;display:none;"></div>

    <div class="buttons">
      <button class="cancel" onclick="closeBooking()">إلغاء</button>
      <button class="send" id="bk_submit_btn" onclick="sendBooking()">إرسال الطلب</button>
    </div>

  </div>

</div>
<script src="./patient_dashboard.js"></script>

<!-- SMC Patient Data for JS -->
<script>
var SMC_PATIENT_DATA = {
  medications:      <?= json_encode($patient['medications'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  chronic_diseases: <?= json_encode($patient['chronic_diseases'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  allergies:        <?= json_encode($patient['allergies'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  blood_type:       <?= json_encode($patient['blood_type'] ?? '', JSON_UNESCAPED_UNICODE) ?>
};
</script>

<script>

/* ================================================================
   DOCTORS — الكود الأصلي كما هو (لا تعديل)
   ================================================================ */


let currentPage = 1;

let allDoctors = [];
var maps = {};

var customIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
    iconSize: [35, 35]
});

function initMap(id) {
    maps[id] = L.map(id).setView([34.88, -1.31], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(maps[id]);
}

/* ── إزالة أي iframe/Google Maps وإعادة تهيئة Leaflet إذا لزم ── */
function ensureLeafletMap(mapId) {
    var mapDiv = document.getElementById(mapId);
    if (!mapDiv) return;
    if (mapDiv.querySelector('iframe') || !maps[mapId]) {
        mapDiv.innerHTML = "";
        if (maps[mapId]) { try { maps[mapId].remove(); } catch(e) {} }
        delete maps[mapId];
        maps[mapId] = L.map(mapId).setView([34.88, -1.31], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(maps[mapId]);
    }
}

initMap('map');
function fixMap() {
    maps['map'].invalidateSize();
    maps['map'].setView([34.88, -1.31], 13);
    setTimeout(() => { maps['map'].invalidateSize(); }, 500);
}
setTimeout(fixMap, 500);
setTimeout(fixMap, 1500);
setTimeout(fixMap, 3000);

function loadDoctors() {
    const wilaya    = document.getElementById("WDO").value;
    const commune   = document.getElementById("COM_CDO").value;
    const specialty = document.getElementById("SPF").value;

    // FILTER-FIX: التخصص إلزامي — إذا فارغ، امسح النتائج وأوقف الطلب
    if (!wilaya) {
        allDoctors = [];
        currentPage = 1;
        displayDoctors();
        return;
    }

    document.getElementById("map").style.display = "none";
    fetch(`get_doctors.php?wilaya=${encodeURIComponent(wilaya)}&commune=${encodeURIComponent(commune)}&specialty=${encodeURIComponent(specialty)}`)
        .then(function(r) {
            if (!r.ok) throw new Error("HTTP " + r.status);
            return r.json();
        })
        .then(function(data) {
            // FIX: لا نعيد تعيين allDoctors إلا بعد التأكد من صحة البيانات
            if (Array.isArray(data)) {
                allDoctors = data;
            } else {
                allDoctors = [];
            }
            currentPage = 1;
            // DEBUG: تتبع حالة البيانات بعد كل تحميل
            console.log("[loadDoctors] allDoctors:", allDoctors.length, "| currentPage:", currentPage);
            displayDoctors();
        })
        .catch(function(err) {
            console.error("loadDoctors error:", err);
            // FIX: لا نصفّر allDoctors عند الخطأ — نعرض رسالة بدون إفساد الحالة
            var container = document.getElementById("doctorsContainer");
            if (container) container.innerHTML = "⚠️ خطأ في تحميل الأطباء، حاول مجدداً.";
        });
}

function displayDoctors() {
    let container  = document.getElementById("doctorsContainer");
    let pagination = document.getElementById("pagination");
    container.innerHTML = "";

    if (allDoctors.length === 0) {
        // FILTER-FIX: placeholder مختلف حسب السبب
        const wilaya = document.getElementById("WDO") ? document.getElementById("WDO").value : "";

if (!wilaya) {

    container.innerHTML = '<p style="text-align:center;color:#888;padding:20px;font-size:0.95em;"></p>';

} else {
            container.innerHTML = "❌ لا يوجد أطباء";
        }
        pagination.style.display = "none";
        document.getElementById("prevBtn").style.display = "none";
        document.getElementById("nextBtn").style.display = "none";
        return;
    }

    let start = (currentPage - 1) * perPage;
    let end   = start + perPage;
    let html  = "";

    allDoctors.slice(start, end).forEach(doc => {
    html += `
    <div class="doctor-card">
        <div class="doctor-info">
            <h3>${doc.full_name}</h3>
            <p class="specialty">${doc.specialty_name}</p>
        </div>

        <div class="doctor-actions">
          <button class="btn-book" onclick="openBooking(${doc.id})">
حجز
</button>

            <button class="btn-location"
                onclick="showDoctorOnMap(${doc.lat}, ${doc.lng}, '${doc.full_name}')">
                📍 عرض الموقع
            </button>
        </div>
    </div>`;
});

    container.innerHTML = html;
    pagination.style.display = "block";
    document.getElementById("prevBtn").style.display = currentPage > 1 ? "inline-block" : "none";
    document.getElementById("nextBtn").style.display =
        (currentPage * perPage < allDoctors.length) ? "inline-block" : "none";
}

function getNearby(type, mapId) {
  
let mapDiv = document.getElementById(mapId);
mapDiv.innerHTML = "";

if (maps[mapId]) {
    maps[mapId].remove();
    delete maps[mapId];
}

maps[mapId] = L.map(mapId).setView([34.88, -1.31], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
}).addTo(maps[mapId]);
    if (!navigator.geolocation) {
        alert("❌ GPS غير مدعوم");
        return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {

        var userLat = pos.coords.latitude;
        var userLng = pos.coords.longitude;

        var url = '';
        var icon = '';
        var nameField = 'name';

        switch(type) {

            case 'doctors':
                url = 'get_doctors.php';
                icon = '👨‍⚕️';
                nameField = 'full_name';
                break;

            case 'pharmacies':
                url = 'get_locations.php?type=pharmacies';
                icon = '💊';
                break;

            case 'labs':
                url = 'get_locations.php?type=labs';
                icon = '🧪';
                break;

            case 'nurses':
                url = 'get_locations.php?type=nurses';
                icon = '🩺';
                break;

            case 'clinics':
                url = 'get_locations.php?type=clinics';
                icon = '🏥';
                break;

            case 'civil_protection':
                url = 'get_locations.php?type=civil_protection';
                icon = '🚑';
                break;

            case 'associations':
                url = 'get_locations.php?type=associations';
                icon = '🤝';
                break;

            case 'elderly':
                url = 'get_locations.php?type=elderly';
                icon = '👴';
                break;

            case 'orphans':
                url = 'get_locations.php?type=orphans';
                icon = '🧒';
                break;

            case 'sport_health':
                url = 'get_locations.php?type=sport_health';
                icon = '🏃';
                break;

            case 'donors':
                url = 'get_locations.php?type=donors';
                icon = '🩸';
                break;

            default:
                alert("❌ نوع غير معروف");
                return;
        }

        fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){

           var map = maps[mapId];
            if (!map) {
                alert("❌ الخريطة غير موجودة");
                return;
            }

            // نحذف غير markers
            maps[mapId].eachLayer(function(layer){
                if (layer instanceof L.Marker) {
                    maps[mapId].removeLayer(layer);
                }
            });

            data.forEach(function(item){

                if (item.lat && item.lng) {

                    var name = item[nameField] || item.name || '—';

                    var popup =
                        '<div style="text-align:right">' +
                        '<strong style="color:#0ea5a4;">' + icon + ' ' + name + '</strong><br>' +
                        '<span>📍 ' + (item.address ? item.address : (item.wilaya + ' - ' + item.commune)) + '</span>' +
                        '</div>';

                    L.marker([item.lat, item.lng]).addTo(map).bindPopup(popup);
                }
            });

         let nearest = null;
let minDistance = Infinity;

data.forEach(function(item){
    if (item.lat && item.lng) {
        let distance = Math.sqrt(
            Math.pow(item.lat - userLat, 2) +
            Math.pow(item.lng - userLng, 2)
        );

        if (distance < minDistance) {
            minDistance = distance;
            nearest = item;
        }
    }
});

if (nearest) {
    maps[mapId].setView([nearest.lat, nearest.lng], 13);
} else {
    maps[mapId].setView([userLat, userLng], 13);
}

            document.getElementById(mapId).style.display = "block";

            setTimeout(function(){ maps[mapId].invalidateSize(); }, 200);
        });

    }, function(){
        alert("❌ فشل تحديد الموقع");
    });
}
function openNearby(type, mapId) {
  if (!navigator.geolocation) {
    alert("❌ GPS غير مدعوم");
    return;
  }

  navigator.geolocation.getCurrentPosition(function(pos) {
    let query = "";

    switch(type) {
      case "doctors": query = "doctor"; break;
      case "pharmacies": query = "pharmacy"; break;
      case "labs": query = "medical laboratory"; break;
      case "nurses": query = "nurse"; break;
      case "clinics": query = "medical clinic"; break;
      case "civil_protection": query = "ambulance"; break;
      case "associations": query = "charity"; break;
      case "elderly": query = "elderly care"; break;
      case "orphans": query = "orphanage"; break;
      case "sport_health": query = "gym"; break;
      case "donors": query = "blood donation"; break;
      default: query = type;
    }

    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    const mapDiv = document.getElementById(mapId);
    mapDiv.style.display = "block";

    mapDiv.innerHTML = `
      <iframe
        width="100%"
        height="100%"
        style="border:0;border-radius:20px;"
        loading="lazy"
        allowfullscreen
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps?q=${encodeURIComponent(query)}&ll=${lat},${lng}&z=14&output=embed">
      </iframe>
    `;
  }, function() {
    alert("❌ تعذر تحديد موقعك");
  });
}
function searchExternal(type, mapId) {

    if (!navigator.geolocation) {
        alert("❌ GPS غير مدعوم");
        return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {

        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;

        var query = '';

        switch(type) {
            case 'doctors': query = 'hospital'; break;
            case 'pharmacies': query = 'pharmacy'; break;
            case 'labs': query = 'laboratory'; break;
            case 'nurses': query = 'nurse'; break;
            case 'clinics': query = 'clinic'; break;
            case 'civil_protection': query = 'ambulance'; break;
            case 'associations': query = 'association'; break;
            case 'elderly': query = 'elderly care'; break;
            case 'orphans': query = 'orphanage'; break;
            case 'sport_health': query = 'gym'; break;
            case 'donors': query = 'blood donation'; break;
        }

        ensureLeafletMap(mapId);

        var map = maps[mapId];

        if (!map) {
            alert("❌ الخريطة غير موجودة");
            return;
        }

        document.getElementById(mapId).style.display = "block";

        map.invalidateSize();
        map.setView([lat, lng], 13);

        var url = "https://nominatim.openstreetmap.org/search?format=json&q=" +
                  query +
                  "&bounded=1&viewbox=" +
                  (lng-0.2) + "," + (lat+0.2) + "," +
                  (lng+0.2) + "," + (lat-0.2);

        fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){

            map.eachLayer(function(l){
                if (l instanceof L.Marker) map.removeLayer(l);
            });

            if (!data.length) {
                alert("❌ لا يوجد نتائج");
                return;
            }

            data.forEach(function(p){
                L.marker([p.lat, p.lon]).addTo(map)
                .bindPopup("📍 " + p.display_name);
            });

        });

    }, function(){
        alert("❌ فشل تحديد الموقع");
    });
}
function moveToLocation(name) {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${name}, Algeria`)
        .then(r => r.json()).then(data => {
            if (data.length > 0) map.setView([data[0].lat, data[0].lon], 11);
        });
}

function showDoctorOnMap(lat, lng, name) {
    var mapDiv = document.getElementById("map");

    ensureLeafletMap('map');

    let map = maps['map'];

    mapDiv.style.display = "block";

    map.eachLayer(layer => {
        if (layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });

    L.marker([lat, lng], { icon: customIcon })
        .addTo(map)
        .bindPopup(name)
        .openPopup();

    map.setView([lat, lng], 14);

    setTimeout(() => {
        map.invalidateSize();
    }, 200);
}

// bookDoctor — تم استبداله بـ openBooking في نظام الحجز الجديد
function bookDoctor(doctorId) {
  openBooking(doctorId);
}

function nextPage() { if (currentPage * perPage < allDoctors.length) { currentPage++; displayDoctors(); } }
function prevPage() { if (currentPage > 1) { currentPage--; displayDoctors(); } }

function loadSpecialties() {
    fetch("get_specialties.php").then(r => r.json()).then(data => {
        let sel = document.getElementById("SPF");
        sel.innerHTML = '<option value="">🩺 جميع التخصصات</option>';
        data.forEach(s => sel.innerHTML += `<option value="${s.id}">${s.name_ar}</option>`);
    });
}

function loadWilayas() {

    fetch("get_wilayas.php")
    .then(r => r.json())
    .then(data => {

        // جميع select تاع الولايات
        const selects = [
            "WDO", // أطباء
            "WPH", // صيدليات
            "WLB", // مخابر
            "WNR", // ممرضين
            "WCL", // عيادات
            "WCV", // حماية مدنية
            "WCH", // جمعيات
            "WEL", // كبار السن
            "WOR",  // أيتام
            "WSP", //الصحةو الرياضة
            "WBL" //متبرع
        ];

        selects.forEach(id => {
            const sel = document.getElementById(id);
            if (!sel) return;

            sel.innerHTML = '<option value="">📍 الولاية</option>';

            data.forEach(w => {
                sel.innerHTML += `<option value="${w.name_fr}">${w.name_ar}</option>`;
            });
        });

    });
}

/* ================================================================
   OLD ldCom — خاص بالأطباء (محفوظ كما هو)
   ================================================================ */
function ldCom(select) {
    const wilaya = select.options[select.selectedIndex].text.trim();
    const communeSelect = document.getElementById("COM_CDO");
    communeSelect.innerHTML = '<option value="">البلدية</option>';
    if (!wilaya) return;
    fetch(`get_communes.php?wilaya=${encodeURIComponent(wilaya)}`)
        .then(r => r.json()).then(data => {
            data.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.name_fr;
                opt.textContent = c.name_ar;
                communeSelect.appendChild(opt);
            });
        }).catch(err => console.error("communes error:", err));
}

/* ================================================================
   GENERIC HELPERS — دوال عامة لباقي الأصناف
   ================================================================ */

/**
 * ldComGeneric — تحميل البلديات لأي select آخر غير الأطباء
 * @param {HTMLSelectElement} select       — select الولاية
 * @param {string}            communeSelId — id لـ select البلدية المقابلة
 */
function ldComGeneric(select, communeSelId) {
    const wilaya = select.options[select.selectedIndex].text.trim();
    const communeSelect = document.getElementById(communeSelId);
    communeSelect.innerHTML = '<option value="">البلدية</option>';
    if (!wilaya) return;
    fetch(`get_communes.php?wilaya=${encodeURIComponent(wilaya)}`)
        .then(r => r.json()).then(data => {
            data.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.name_fr;
                opt.textContent = c.name_ar;
                communeSelect.appendChild(opt);
            });
        }).catch(err => console.error("communes error:", err));
}

/**
 * buildLocationCard — بناء بطاقة IC بنفس تصميم الأطباء
 * يدعم: اسم، ولاية/بلدية، هاتف، بريد، ملاحظة، زمرة دم، حجز
 */
function buildLocationCard(item, type) {

    let html = '';

    html += '<div class="doctor-card">';

    // 🟢 المعلومات
    html += '<div class="doctor-info">';
    html += '<h3>' + (item.name || '—') + '</h3>';

    html += '<p class="specialty">💊 ' + (type || '') + '</p>';

    html += '<p style="font-size:13px;color:#777;">';
    html += '📍 ' + (item.wilaya || '') + (item.commune ? ' - ' + item.commune : '');
    html += '</p>';

    html += '</div>';

    // 🟢 الأزرار
    html += '<div class="doctor-actions">';

    if (item.lat && item.lng) {
        html += '<button class="btn-location" onclick="showOnMap(\'map-' + type + '\',' + item.lat + ',' + item.lng + ',\'' + (item.name || '').replace(/'/g,"\\'") + '\')">';
        html += 'عرض الموقع';
        html += '</button>';
    }

    html += '</div>';

    html += '</div>';

    return html;
}
            
/**
 * loadSection — دالة عامة لجلب بيانات أي صنف وعرضها
 * @param {string} type        — نوع الـ API (pharmacies, labs, nurses, ...)
 * @param {string} sectionId   — id الـ div الحاوي (ph-lst, lb-lst, ...)
 * @param {string} wilayaSelId — id select الولاية
 * @param {string} communeSelId— id select البلدية
 */
function loadSection(type, sectionId, wilayaSelId, communeSelId, sub='') {

    const wilaya  = document.getElementById(wilayaSelId)?.value || '';
    const commune = document.getElementById(communeSelId)?.value || '';
    const blood   = document.getElementById("BL_GRP")?.value || '';

    const container = document.getElementById(sectionId + '-results');
    if (!container) return;

    // ❌ ماكانش ولاية → ماكانش نتائج
    if (!wilaya) {
        container.innerHTML = '<div style="text-align:center;padding:20px;color:#9ca3af;"></div>';
        return;
    }

    container.innerHTML = '⏳ جاري التحميل...';

    let url = "get_locations.php?type=" + type;
    if(sub){
   url += "&sub=" + encodeURIComponent(sub);
}
    url += "&wilaya=" + encodeURIComponent(wilaya);

    if (commune) {
        url += "&commune=" + encodeURIComponent(commune);
    }

    // 👇 خاص بالمتبرعين
    if (type === 'donors' && blood) {
        url += "&blood=" + encodeURIComponent(blood);
    }

    fetch(url)
        .then(res => res.json())
     .then(data => {
console.log(data);
   if(type === 'pharmacies'){
    allData['pharmacies'] = data;
    pages['pharmacies'] = 1;
    displaySection('pharmacies','ph-lst');
    return;
}
if(type === 'labs'){
    allData['labs'] = data;
    pages['labs'] = 1;
    displaySection('labs','lb-lst'); // ✔ نفس الصيدليات
    return;
}
if(type === 'nurses'){
    allData['nurses'] = data;
    pages['nurses'] = 1;
    displaySection('nurses','nr-lst');
    return;
}
if(type === 'clinics'){
    allData['clinics'] = data;
    pages['clinics'] = 1;
    displaySection('clinics','cl-lst');
    return;
}
if(type === 'donors'){
    allData['donors'] = data;
    pages['donors'] = 1;
    displaySection('donors','bl-lst');
    return;
}
if(type === 'civil_protection'){
    allData['civil_protection'] = data;
    pages['civil_protection'] = 1;
    displaySection('civil_protection','cv-lst');
    return;
}
if(type === 'sport_health'){
    allData['sport_health'] = data;
    pages['sport_health'] = 1;
    displaySection('sport_health','sp-lst');
    return;
}
if(type === 'associations'){
    allData['associations'] = data;
    pages['associations'] = 1;
    displaySection('associations','ch-lst');
    return;
}
if(type === 'elderly'){
    allData['elderly'] = data;
    pages['elderly'] = 1;
    displaySection('elderly','el-lst');
    return;
}
if(type === 'orphans'){
    allData['orphans'] = data;
    pages['orphans'] = 1;
    displaySection('orphans','or-lst');
    return;
}
})
        .catch(() => {
            container.innerHTML = '❌ خطأ';
        });
}
/**
 * showLocationOnMap — عرض موقع أي عنصر على الخريطة
 */
function showOnMap(mapId, lat, lng, name) {

    const mapDiv = document.getElementById(mapId);

    if (!mapDiv) {
        console.log("❌ mapDiv not found:", mapId);
        return;
    }

    ensureLeafletMap(mapId);

    mapDiv.style.display = "block";

    setTimeout(() => {

        let map = maps[mapId];

        if (!map) {
            console.log("❌ map not ready:", mapId);
            return;
        }

        map.invalidateSize();

        map.eachLayer(l => {
            if (l instanceof L.Marker) map.removeLayer(l);
        });

        L.marker([lat, lng]).addTo(map).bindPopup(name).openPopup();

        map.setView([lat, lng], 14);

    }, 200);

    mapDiv.scrollIntoView({ behavior: "smooth" });
}

/**
 * bookLocation — حجز في عيادة / مخبر / ممرض
 */
function bookLocation(id, type) {
    fetch("book_location.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ location_id: id, type: type, patient_id: 1 })
    }).then(r => r.text())
      .then(() => { if(typeof sa === 'function') sa("تم إرسال طلب الحجز ✅"); else alert("تم إرسال طلب الحجز ✅"); })
      .catch(() => { if(typeof sa === 'function') sa("حدث خطأ ❌"); else alert("حدث خطأ ❌"); });
}


document.addEventListener("DOMContentLoaded", function () {
    // الأطباء
    loadSpecialties();
    loadWilayas();
    // FILTER-FIX: لا نحمّل الأطباء عند فتح الصفحة — ننتظر اختيار التخصص
    displayDoctors();

    // تحميل مسبق لكل الأصناف (الصفحة الرئيسية)
    loadSection('pharmacies',      'ph-lst',   'WPH',  'COM_CPH');
    loadSection('labs',            'lb-lst',   'WLB',  'COM_CLB');
    loadSection('nurses',          'nr-lst',   'WNR',  'COM_CNR');
    loadSection('civil_protection','cv-lst',   'WCV',  'COM_CCV');
    loadSection('clinics',         'cl-lst',   'WCL',  'COM_CCL');
    loadSection('associations',    'ch-lst',   'WCH',  'COM_CCH');
    loadSection('elderly',         'el-lst',   'WEL',  'COM_CEL');
    loadSection('orphans',         'or-lst',   'WOR',  'COM_COR');

    // متبرعو الدم — نحفظ النسخة الكاملة للتصفية
    

    // رياضة وتغذية
   
    initMap('map-labs');
initMap('map-nurses');
initMap('map-donors');
initMap('map-clinics');
initMap('map-sport_health');
initMap('map-associations');
initMap('map-elderly');
initMap('map-orphans');
initMap('map-civil_protection');
initMap('map-pharmacies');

    // FIX: تحديث الإشعارات تلقائياً كل 30 ثانية (لرؤية المواعيد المؤكدة فوراً)
    setInterval(function() {
        refreshNotifications();
    }, 30000);
});



// عدد العناصر في كل صفحة
;




let pages = {};
let allData = {};
let perPage = 3;

function displaySection(type, sectionId){
    let container = document.getElementById(sectionId + "-results");
    let pagination = document.getElementById(sectionId + "-pagination");


    
    let data = allData[type] || [];
    let page = pages[type] || 1;

    if (!data.length) {
        container.innerHTML = "❌ لا توجد نتائج";
        if(pagination) pagination.style.display = "none";
        return;
    }

    let start = (page - 1) * perPage;
    let end   = start + perPage;

    container.innerHTML = "";

    data.slice(start, end).forEach(item => {
        container.innerHTML += buildLocationCard(item, type);
    });

    if(!pagination) return;

    let prev = pagination.children[0];
    let next = pagination.children[1];

    prev.style.display = page > 1 ? "inline-block" : "none";
    next.style.display = (page * perPage < data.length) ? "inline-block" : "none";

    pagination.style.display = data.length > perPage ? "flex" : "none";
}

function nextSection(type, sectionId){
    let data = allData[type] || [];
    let page = pages[type] || 1;

    if(page * perPage < data.length){
        pages[type] = page + 1;
        displaySection(type, sectionId);
    }
}

function prevSection(type, sectionId){
    if((pages[type] || 1) > 1){
        pages[type]--;
        displaySection(type, sectionId);
    }
}

/* ================================================================
   BOOKING SYSTEM — نظام الحجز المُعاد بناؤه
   ================================================================ */

function openBooking(doctorId) {
  // تعيين doctor_id في الـ hidden field
  document.getElementById("bk_doctor_id").value = doctorId;
  // إخفاء رسالة الخطأ السابقة
  var errEl = document.getElementById("bk_error");
  if (errEl) { errEl.style.display = "none"; errEl.textContent = ""; }
  // إعادة تفعيل زر الإرسال
  var btn = document.getElementById("bk_submit_btn");
  if (btn) { btn.disabled = false; btn.textContent = "إرسال الطلب"; }
  // إظهار الـ modal
  document.getElementById("bookingModal").style.display = "block";
}

function closeBooking() {
  document.getElementById("bookingModal").style.display = "none";
}

function sendBooking() {
  var doctorId  = document.getElementById("bk_doctor_id").value;
  var name      = (document.getElementById("bk_name").value || "").trim();
  var phone     = (document.getElementById("bk_phone").value || "").trim();
  var caseType  = document.getElementById("bk_case_type").value;
  var errEl     = document.getElementById("bk_error");
  var btn       = document.getElementById("bk_submit_btn");

  // Validation
  function showErr(msg) {
    errEl.textContent = msg;
    errEl.style.display = "block";
  }

  errEl.style.display = "none";

  if (!doctorId || doctorId === "0") {
    showErr("حدث خطأ في تحديد الطبيب، أعد المحاولة.");
    return;
  }
  if (name.length < 2) {
    showErr("يرجى إدخال الاسم الكامل.");
    return;
  }
  if (!/^[0-9\+\s\-]{7,15}$/.test(phone)) {
    showErr("رقم الهاتف غير صحيح.");
    return;
  }
  if (!caseType) {
    showErr("يرجى اختيار نوع الحالة.");
    return;
  }

  btn.disabled = true;
  btn.textContent = "جاري الإرسال...";

  fetch("book_appointment.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      doctor_id: parseInt(doctorId),
      name:      name,
      phone:     phone,
      case_type: caseType
    })
  })
  .then(function(res) {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.json();
    })
  .then(function(data) {
    if (data.success) {
      // BOOKING-FIX: أغلق الـ modal فقط — لا تلمس allDoctors أو currentPage
      closeBooking();
      // BOOKING-FIX: أعد رسم القائمة الموجودة دون fetch جديد ودون reset للـ state
      // هذا يحافظ على allDoctors كاملة + currentPage + pagination كما هي
      console.log("allDoctors after booking:", allDoctors.length);
      console.log("currentPage after booking:", currentPage);
      displayDoctors();
      if (typeof sa === 'function') {
        sa(data.message);
      } else {
        alert(data.message);
      }
      // BOOKING-FIX: تحديث الإشعارات بعد الحجز
      if (typeof refreshNotifications === 'function') {
        setTimeout(refreshNotifications, 500);
      }
    } else {
      showErr(data.message || "حدث خطأ، حاول مجدداً.");
      btn.disabled = false;
      btn.textContent = "إرسال الطلب";
    }
  })
  .catch(function() {
    showErr("خطأ في الاتصال بالخادم ❌");
    btn.disabled = false;
    btn.textContent = "إرسال الطلب";
  });
}

/* ================================================================
   FIX: تحديث الإشعارات ديناميكياً عند فتح الـ panel
   ================================================================ */
function refreshNotifications() {
  fetch("get_notifications.php")
    .then(function(r) { return r.json(); })
    .then(function(result) {
      if (!result.success) return;
      var list = document.getElementById("NP-LIST");
      if (!list) return;

      var notifs = result.notifications;
      if (!notifs || notifs.length === 0) {
        list.innerHTML = "<p>ماكان حتى إشعار</p>";
      } else {
        var html = "";
        notifs.forEach(function(n) {
          var readClass = n.is_read == 1 ? "read" : "unread";
          var msgHtml = (n.message || "").replace(/\n/g, "<br>");
          var timeStr = n.created_at ? n.created_at.substring(0, 16) : "";
          html += '<div class="NI2 ' + readClass + '">' +
            '<div class="NI2-IC"><i class="fas fa-calendar-check"></i></div>' +
            '<div style="flex:1;">' +
            '<div class="NI2-TT">📅 موعد طبي</div>' +
            '<div class="NI2-ST">' + msgHtml + '</div>' +
            '<div class="NI2-TM">' + timeStr + '</div>' +
            '</div></div>';
        });
        list.innerHTML = html;
      }

      // تحديث عداد الإشعارات غير المقروءة
      var badge = document.getElementById("NC");
      if (badge) {
        if (result.unread_count > 0) {
          badge.textContent = result.unread_count;
          badge.style.display = "inline-block";
        } else {
          badge.style.display = "none";
        }
      }
      // FIX: تحديث أي عداد آخر في الواجهة
      var notifCount2 = document.querySelectorAll(".notif-count, .NB-COUNT, [id$='-notif-count']");
      notifCount2.forEach(function(el) {
        el.textContent = result.unread_count > 0 ? result.unread_count : "";
      });
    })
    .catch(function(err) {
      console.error("refreshNotifications error:", err);
    });
}

function openNotifications() {
  var panel = document.getElementById("NP");
  if (panel && panel.classList.contains("OP")) {
    closeAllPanels();
  } else {
    opPanel("NP");
    // FIX: تحديث الإشعارات من السيرفر عند الفتح
    refreshNotifications();
  }
}
function editMedicalRecord() {
  document.getElementById("edit_first_name").value = <?= json_encode($patient['first_name'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_last_name").value = <?= json_encode($patient['last_name'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_birth_date").value = <?= json_encode($patient['birth_date'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_blood_type").value = <?= json_encode($patient['blood_type'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_weight").value = <?= json_encode($patient['weight'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_height").value = <?= json_encode($patient['height'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_chronic").value = <?= json_encode($patient['chronic_diseases'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_allergies").value = <?= json_encode($patient['allergies'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_medications").value = <?= json_encode($patient['medications'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_notes").value = <?= json_encode($patient['health_notes'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_emergency_name").value = <?= json_encode($patient['emergency_name'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById("edit_emergency_phone").value = <?= json_encode($patient['emergency_phone'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

  document.getElementById("MEDIT").classList.add("OP");
}
function openEditTab(tabId){

  document.querySelectorAll(".editTab").forEach(tab=>{
    tab.style.display = "none";
  });

  document.querySelectorAll(".editBtn").forEach(btn=>{
    btn.classList.remove("ACTIVE-EDIT");
  });

  document.getElementById(tabId).style.display = "block";

  event.target.classList.add("ACTIVE-EDIT");
}

document.addEventListener("change",function(e){
  if(e.target.id==="avatarUpload"){
    const file=e.target.files[0];
    if(file){
      document.getElementById("patientAvatar").src=
      URL.createObjectURL(file);
    }
  }
});

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function downloadMedicalRecord() {
    const element = document.getElementById("medicalRecordPDF");
    const buttons = document.querySelector(".RECORD_BTNS");

    buttons.style.display = "none";

    html2canvas(element, {
        scale: 2,
        useCORS: true
    }).then(canvas => {

        const imgData = canvas.toDataURL("image/png");

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidth = 210;
        const pageHeight = 297;

        const imgWidth = pageWidth - 10;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        const finalHeight = Math.min(imgHeight, pageHeight - 10);

        pdf.addImage(imgData, 'PNG', 5, 5, imgWidth, finalHeight);
        pdf.save("السجل_الطبي.pdf");

        buttons.style.display = "flex";
    }).catch(() => {
        buttons.style.display = "flex";
    });
}
</script>
<script>
function downloadMedicalRecord() {
    const element = document.getElementById("medicalRecordPDF");
    const buttons = document.querySelector(".RECORD_BTNS");

    buttons.style.display = "none";

    html2canvas(element, {
        scale: 2,
        useCORS: true
    }).then(canvas => {

        const imgData = canvas.toDataURL("image/png");

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidth = 210;
        const pageHeight = 297;

        const imgWidth = pageWidth - 10;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        const finalHeight = Math.min(imgHeight, pageHeight - 10);

        pdf.addImage(imgData, 'PNG', 5, 5, imgWidth, finalHeight);
        pdf.save("السجل_الطبي.pdf");

        buttons.style.display = "flex";
    }).catch(() => {
        buttons.style.display = "flex";
    });
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- يومياتي Weekly Calendar -->
<script src="weekly_calendar.js?v=1"></script>
<!-- يومياتي Daily Health Tracker logic -->
<script src="daily_journal.js?v=1"></script>
<script src="pregnancy_chronic.js?v=1"></script>

<!-- ================================================================
     XRAY PAGE JS — صفحة الأشعة الطبية
     Front-End Only • No Backend
     ================================================================ -->
<script>
/* ── Tab Switcher for Xray Page ── */
function xraySwTab(btn, sec, tab) {
  // Hide all sibling tab panels
  var tabs = ['xray-qr', 'xray-code', 'xray-upload'];
  tabs.forEach(function(t) {
    var el = document.getElementById(t);
    if (el) el.style.display = 'none';
  });
  // Show selected tab
  var target = document.getElementById(sec + '-' + tab);
  if (target) target.style.display = 'block';
  // Mark active button in TB2 group
  if (btn && btn.closest) {
    var tabsRow = btn.closest('.RES-TABS');
    if (tabsRow) {
      tabsRow.querySelectorAll('.TB2').forEach(function(b) { b.classList.remove('A'); });
      btn.classList.add('A');
    }
  }
}

/* ── QR / Code Simulate Scan ── */
function xraySimulateScan() {
  // Animate scan line
  var line = document.getElementById('xrayScanLine');
  if (line) {
    line.classList.add('active');
    setTimeout(function() {
      line.classList.remove('active');
      // Show result panel (scroll to it)
      var r1 = document.getElementById('xrayResult1');
      if (r1) {
        r1.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Flash highlight
        r1.style.transition = 'box-shadow .3s';
        r1.style.boxShadow = '0 0 0 3px rgba(0,180,216,.45)';
        setTimeout(function() { r1.style.boxShadow = ''; }, 1400);
      }
      if (typeof sa === 'function') sa('تم استقبال نتيجة الأشعة بنجاح ✅');
    }, 1900);
  }
}

/* ── Filter Results ── */
function xrayFilter(status, btn) {
  // Active button
  var tabs = document.querySelectorAll('#VXRAY .xray-ftab');
  tabs.forEach(function(b) { b.classList.remove('xray-ftab-active'); });
  if (btn) btn.classList.add('xray-ftab-active');
  // Filter cards
  var cards = document.querySelectorAll('#VXRAY .xray-result-card');
  cards.forEach(function(card) {
    if (status === 'all') {
      card.style.display = '';
    } else {
      card.style.display = card.getAttribute('data-status') === status ? '' : 'none';
    }
  });
}

/* ── Image Modal ── */
function xrayOpenImageModal() {
  var modal = document.getElementById('xrayImageModal');
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}
function xrayCloseImageModal() {
  var modal = document.getElementById('xrayImageModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
}
// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') xrayCloseImageModal();
});

/* ── Upload Preview (upload tab) ── */
function previewXrayUpload(input) {
  var file = input.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('XRAY_PRV2');
    if (img) { img.src = e.target.result; img.style.display = 'block'; }
    var actions = document.getElementById('XRAY_UPLOAD_ACTIONS');
    if (actions) actions.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

/* ── Keep existing previewXray for the old input (compatibility) ── */
if (typeof previewXray === 'undefined') {
  function previewXray(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.getElementById('XRAY_PRV');
      if (img) { img.src = e.target.result; img.style.display = 'block'; }
      var actions = document.getElementById('XRAY_ACTIONS');
      if (actions) actions.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}
</script>
<!-- التواصل الطبي - Medical Communication logic (Front-End only, Dummy Data) -->
<script src="patient_medcomm.js?v=1"></script>
</body>
</html>
