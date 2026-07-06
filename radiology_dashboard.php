<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role'] != 'doctor') { header("Location: login.php"); exit; }
require 'db.php';

$stmt = $pdo->prepare("SELECT doctors.*, users.full_name FROM doctors JOIN users ON doctors.user_id = users.id WHERE doctors.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
if (!$doctor || $doctor['is_profile_complete'] == 0) { header("Location: complete_doctor_profile.php"); exit(); }

// Radiology stats (mock-ready — replace with real queries when table exists)
$totalRequests  = 248;
$pendingCount   = 34;
$completedCount = 201;
$urgentCount    = 13;

// Sample requests — replace with DB query
$requests = [
    ['id'=>1,'name'=>'أمينة بن موسى','age'=>52,'gender'=>'أنثى','room'=>'204','doctor'=>'د. كريم بن سعيد','type'=>'CT Scan','date'=>'2026-05-21','device'=>'Siemens SOMATOM','tech'=>'عمر دالي','status'=>'urgent','notes'=>''],
    ['id'=>2,'name'=>'يوسف تواتي','age'=>38,'gender'=>'ذكر','room'=>'118','doctor'=>'د. سميرة حاج','type'=>'MRI Brain','date'=>'2026-05-21','device'=>'GE SIGNA','tech'=>'نادية رزقالة','status'=>'pending','notes'=>''],
    ['id'=>3,'name'=>'فاطمة زروق','age'=>61,'gender'=>'أنثى','room'=>'312','doctor'=>'د. مراد أمين','type'=>'Chest X-Ray','date'=>'2026-05-20','device'=>'Philips DigitalDiagnost','tech'=>'عمر دالي','status'=>'completed','notes'=>'نتائج طبيعية. لا توجد عملية قلبية رئوية حادة.'],
    ['id'=>4,'name'=>'خالد مجدوب','age'=>45,'gender'=>'ذكر','room'=>'207','doctor'=>'د. كريم بن سعيد','type'=>'Ultrasound','date'=>'2026-05-22','device'=>'Samsung RS85','tech'=>'نادية رزقالة','status'=>'scheduled','notes'=>''],
    ['id'=>5,'name'=>'سارة بواشاوي','age'=>29,'gender'=>'أنثى','room'=>'401','doctor'=>'د. سميرة حاج','type'=>'CT Scan','date'=>'2026-05-20','device'=>'Siemens SOMATOM','tech'=>'عمر دالي','status'=>'completed','notes'=>'الزائدة الدودية طبيعية. لا توجد تجمعات داخل البطن.'],
    ['id'=>6,'name'=>'نسيم وقيل','age'=>57,'gender'=>'ذكر','room'=>'109','doctor'=>'د. مراد أمين','type'=>'MRI Spine','date'=>'2026-05-21','device'=>'GE SIGNA','tech'=>'نادية رزقالة','status'=>'urgent','notes'=>''],
    ['id'=>7,'name'=>'حياة بوزيدي','age'=>34,'gender'=>'أنثى','room'=>'215','doctor'=>'د. كريم بن سعيد','type'=>'X-Ray Knee','date'=>'2026-05-23','device'=>'Philips DigitalDiagnost','tech'=>'عمر دالي','status'=>'scheduled','notes'=>''],
    ['id'=>8,'name'=>'رشيد خليف','age'=>70,'gender'=>'ذكر','room'=>'320','doctor'=>'د. سميرة حاج','type'=>'CT Chest','date'=>'2026-05-19','device'=>'Siemens SOMATOM','tech'=>'نادية رزقالة','status'=>'completed','notes'=>'تغيرات انفيزيمية خفيفة. يُنصح بالمتابعة.'],
    ['id'=>9,'name'=>'مريم بلحاج','age'=>42,'gender'=>'أنثى','room'=>'102','doctor'=>'د. مراد أمين','type'=>'Mammography','date'=>'2026-05-22','device'=>'Siemens Mammomat','tech'=>'عمر دالي','status'=>'pending','notes'=>''],
    ['id'=>10,'name'=>'بلال حمدي','age'=>25,'gender'=>'ذكر','room'=>'308','doctor'=>'د. كريم بن سعيد','type'=>'Ultrasound','date'=>'2026-05-21','device'=>'Samsung RS85','tech'=>'نادية رزقالة','status'=>'pending','notes'=>''],
];

$statusLabels = ['pending'=>'قيد الانتظار','scheduled'=>'مجدول','completed'=>'مكتمل','urgent'=>'عاجل'];
$statusClass  = ['pending'=>'chronic','scheduled'=>'followup','completed'=>'followup','urgent'=>'urgent'];
$statusIcon   = ['pending'=>'fa-clock','scheduled'=>'fa-calendar-check','completed'=>'fa-check-circle','urgent'=>'fa-exclamation-triangle'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قسم الأشعة - MedChifaGiz</title>
    <link rel="stylesheet" href="dr_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════
           RADIOLOGY — Scoped overrides & additions
           بنفس الهوية البصرية لـ dr_dashboard
        ═══════════════════════════════════════ */

        /* ── Search & Filter Bar ── */
        .rad-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .rad-search {
            position: relative;
            flex: 1;
            min-width: 220px;
        }
        .rad-search i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .rad-search input {
            width: 100%;
            padding: 11px 42px 11px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.88rem;
            color: var(--text-primary);
            background: var(--bg-card);
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
        }
        .rad-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
        }
        .rad-filter {
            padding: 11px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            color: var(--text-secondary);
            background: var(--bg-card);
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: var(--transition);
        }
        .rad-filter:focus { outline: none; border-color: var(--primary); }

        /* ── Radiology Table ── */
        .rad-table-wrap {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-card);
            overflow: hidden;
        }
        .rad-table-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .rad-table-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .rad-count-badge {
            background: rgba(14,165,233,0.1);
            color: var(--primary);
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid rgba(14,165,233,0.2);
        }
        .rad-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .rad-table th {
            padding: 11px 16px;
            background: var(--bg-main);
            color: var(--text-secondary);
            font-weight: 600;
            text-align: right;
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
            font-family: 'Cairo', sans-serif;
            white-space: nowrap;
        }
        .rad-table td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            font-family: 'Cairo', sans-serif;
            vertical-align: middle;
        }
        .rad-table tbody tr:last-child td { border-bottom: none; }
        .rad-table tbody tr {
            cursor: pointer;
            transition: var(--transition);
        }
        .rad-table tbody tr:hover td {
            background: rgba(14,165,233,0.03);
        }
        .rad-patient-cell { display: flex; align-items: center; gap: 10px; }
        .rad-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; color: #fff; font-weight: 700;
            flex-shrink: 0;
            border: 2px solid rgba(14,165,233,0.2);
        }
        .rad-patient-name { font-weight: 700; font-size: 0.88rem; color: var(--text-primary); }
        .rad-patient-sub  { font-size: 0.74rem; color: var(--text-muted); margin-top: 1px; }
        .rad-type-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(14,165,233,0.08);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 600;
            border: 1px solid rgba(14,165,233,0.15);
        }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.76rem; font-weight: 600; white-space: nowrap; display:inline-flex; align-items:center; gap:5px; }
        .status-badge.urgent   { background: rgba(239,68,68,0.1); color: var(--urgent-red); }
        .status-badge.chronic  { background: rgba(245,158,11,0.1); color: var(--warn-orange); }
        .status-badge.followup { background: rgba(16,185,129,0.1); color: var(--emerald); }
        .action-btn { padding: 6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:0.76rem; font-weight:600; transition:var(--transition); display:inline-flex; align-items:center; gap:4px; }
        .action-btn.edit { background: rgba(14,165,233,0.1); color: var(--primary); }
        .action-btn.edit:hover { background: var(--primary); color: #fff; }

        /* ── MODAL ── */
        .rad-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.52);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .rad-modal-overlay.active { display: flex; }
        .rad-modal {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 720px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-card);
            animation: slideUp 0.3s ease;
        }
        .rad-modal-head {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .rad-modal-head h3 { color: #fff; font-size: 1rem; font-weight: 700; }
        .rad-modal-close {
            background: rgba(255,255,255,0.2); border:none; color:#fff;
            width: 32px; height:32px; border-radius: 8px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; font-size:1rem; transition: var(--transition);
        }
        .rad-modal-close:hover { background: rgba(255,255,255,0.35); }
        .rad-modal-body { flex:1; overflow-y:auto; padding: 24px; display:flex; flex-direction:column; gap: 20px; }
        .rad-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex; gap: 10px;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        .rad-section-title {
            font-size: 0.78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--primary);
            margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .rad-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .rad-info-item {
            background: var(--bg-main);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            border: 1px solid var(--border);
        }
        .rad-info-label { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 3px; font-weight: 600; }
        .rad-info-value { font-size: 0.88rem; color: var(--text-primary); font-weight: 700; }
        .rad-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            color: var(--text-primary);
            background: var(--bg-main);
            font-family: 'Cairo', sans-serif;
            resize: vertical;
            line-height: 1.6;
            transition: var(--transition);
        }
        .rad-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }

        /* Upload */
        .rad-upload-area {
            border: 2px dashed rgba(14,165,233,0.35);
            border-radius: var(--radius-md);
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: rgba(14,165,233,0.03);
        }
        .rad-upload-area:hover { border-color: var(--primary); background: rgba(14,165,233,0.06); }
        .rad-upload-area i { font-size: 2.4rem; color: var(--primary); margin-bottom: 8px; display:block; }
        .rad-upload-area p { font-size: 0.85rem; color: var(--text-secondary); }
        .rad-upload-area span { color: var(--primary); font-weight: 600; cursor:pointer; }
        .rad-upload-area small { display:block; margin-top:4px; font-size:0.75rem; color: var(--text-muted); }
        .rad-preview-grid { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        .rad-preview-thumb {
            position: relative; width: 72px; height: 72px;
            border-radius: var(--radius-sm);
            background: #1e293b;
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex; align-items:center; justify-content:center;
        }
        .rad-preview-thumb img { width:100%; height:100%; object-fit:cover; }
        .rad-preview-thumb i { font-size: 1.4rem; color: #64748b; }
        .rad-preview-del {
            position: absolute; top: 3px; right: 3px;
            background: rgba(239,68,68,0.85); border:none; border-radius:4px;
            width: 18px; height: 18px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size: 0.65rem;
            transition: var(--transition);
        }
        .rad-preview-del:hover { background: var(--urgent-red); }

        /* ── CALENDAR PAGE ── */
        .rad-cal-wrap {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-card);
            overflow: hidden;
        }
        .rad-cal-nav {
            padding: 16px 22px;
            display: flex; align-items:center; justify-content:space-between;
            border-bottom: 1px solid var(--border);
        }
        .rad-cal-nav h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
        .rad-cal-nav-btns { display:flex; gap:6px; }
        .rad-cal-nav-btn {
            width: 34px; height: 34px;
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            display: flex; align-items:center; justify-content:center;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .rad-cal-nav-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        .rad-cal-days-header {
            display: grid; grid-template-columns: repeat(7,1fr);
            background: var(--bg-main);
            border-bottom: 1px solid var(--border);
        }
        .rad-cal-day-name {
            padding: 10px 6px; text-align:center;
            font-size: 0.72rem; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .05em;
        }
        .rad-cal-grid { display: grid; grid-template-columns: repeat(7,1fr); }
        .rad-cal-cell {
            min-height: 84px; padding: 8px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background .15s;
        }
        .rad-cal-cell:hover { background: rgba(14,165,233,0.04); }
        .rad-cal-cell.rad-other { opacity: .4; }
        .rad-cal-num {
            font-size: 0.82rem; font-weight: 600;
            color: var(--text-primary);
            width: 26px; height: 26px;
            display: flex; align-items:center; justify-content:center;
            border-radius: 50%;
        }
        .rad-cal-cell.rad-today .rad-cal-num {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
        }
        .rad-cal-event {
            font-size: 0.68rem; margin-top: 3px; padding: 2px 6px;
            border-radius: 4px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; font-weight: 600;
        }
        .rad-ev-blue   { background: rgba(14,165,233,0.15); color: #0284c7; }
        .rad-ev-red    { background: rgba(239,68,68,0.12); color: var(--urgent-red); }
        .rad-ev-green  { background: rgba(16,185,129,0.12); color: var(--emerald); }

        .rad-cal-legend {
            display: flex; gap: 16px; padding: 12px 22px;
            border-top: 1px solid var(--border);
            font-size: 0.78rem; color: var(--text-muted);
        }
        .rad-legend-dot {
            width: 10px; height: 10px; border-radius: 3px;
            display: inline-block; margin-left: 5px;
        }

        /* ── Hidden rows ── */
        .rad-row-hidden { display: none !important; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .rad-info-grid { grid-template-columns: 1fr 1fr; }
            .rad-modal { max-width: 98vw; }
            .rad-table th:nth-child(4),
            .rad-table td:nth-child(4) { display:none; }
        }
    </style>
</head>
<body>

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-logo">
        <img src="medchifagz.png" alt="MedChifaGiz">
        <div class="sidebar-logo-text">
            <span class="brand">MedChifaGiz</span>
            <span class="tagline">المنصة الطبية الذكية</span>
        </div>
    </div>

    <div class="sidebar-doctor">
        <div class="sidebar-doctor-avatar"><i class="fas fa-user-md"></i></div>
        <div class="sidebar-doctor-info">
            <div class="doc-name">د. <?= htmlspecialchars($doctor['full_name']) ?></div>
            <div class="doc-role"><?= htmlspecialchars($doctor['specialty'] ?? 'طبيب') ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="snav-label">القائمة الرئيسية</p>
        <div class="snav-group" id="sng-home">
            <div class="snav-header" onclick="snavToggle('home')">
                <i class="fas fa-th-large"></i><span>الرئيسية</span>
                <i class="fas fa-chevron-down snav-arrow"></i>
            </div>
            <div class="snav-body" id="snb-home">
                <div class="snav-item" onclick="window.location='dr_dashboard.php'">
                    <i class="fas fa-home"></i><span>لوحة التحكم</span>
                </div>
                <div class="snav-item" onclick="window.location='dr_dashboard.php'">
                    <i class="fas fa-users"></i><span>مرضى اليوم</span>
                </div>
                <div class="snav-item" onclick="window.location='dr_dashboard.php'">
                    <i class="fas fa-calendar-check"></i><span>المواعيد القادمة</span>
                </div>
            </div>
        </div>

        <p class="snav-label">إدارة المرضى</p>
        <div class="snav-group" id="sng-ai">
            <div class="snav-header" onclick="snavToggle('ai')">
                <i class="fas fa-brain"></i><span>مركز البيانات</span>
                <i class="fas fa-chevron-down snav-arrow"></i>
            </div>
            <div class="snav-body" id="snb-ai">
                <div class="snav-item" onclick="window.location='dr_dashboard.php'">
                    <i class="fas fa-archive"></i><span>أرشيف المرضى</span>
                </div>
            </div>
        </div>

        <!-- ── قسم الأشعة — مفتوح افتراضياً ── -->
        <p class="snav-label">الأشعة والتصوير</p>
        <div class="snav-group" id="sng-rad">
            <div class="snav-header snav-open" onclick="snavToggle('rad')">
                <i class="fas fa-x-ray"></i><span>الأشعة</span>
                <i class="fas fa-chevron-down snav-arrow"></i>
            </div>
            <div class="snav-body snb-open" id="snb-rad">
                <div class="snav-item snav-item-active" onclick="showRadPage('dashboard')">
                    <i class="fas fa-th-large"></i><span>لوحة التحكم</span>
                </div>
                <div class="snav-item" onclick="showRadPage('queue')">
                    <i class="fas fa-list-check"></i><span>قائمة الانتظار</span>
                </div>
                <div class="snav-item" onclick="showRadPage('dashboard')">
                    <i class="fas fa-file-medical"></i><span>التقارير</span>
                </div>
                <div class="snav-item" onclick="showRadPage('calendar')">
                    <i class="fas fa-calendar"></i><span>التقويم</span>
                </div>
            </div>
        </div>

        <p class="snav-label">أخرى</p>
        <div class="snav-group" id="sng-msg">
            <div class="snav-header" onclick="snavToggle('msg')">
                <i class="fas fa-comments"></i><span>الرسائل</span>
                <i class="fas fa-chevron-down snav-arrow"></i>
            </div>
            <div class="snav-body" id="snb-msg">
                <div class="snav-item"><i class="fas fa-inbox"></i><span>الوارد</span></div>
            </div>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i><span>تسجيل الخروج</span>
        </a>
    </div>
</aside>

<!-- ══════════════ TOP BAR ══════════════ -->
<header class="top-bar">
    <div class="header-content">
        <div class="header-left">
            <h1 id="rad-page-title">قسم الأشعة والتصوير الطبي</h1>
            <span class="header-subtitle" id="rad-page-sub">إدارة طلبات الأشعة وتقارير التصوير</span>
        </div>
        <div class="header-actions">
            <button class="notification-btn" title="الإشعارات">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <button class="theme-toggle" onclick="document.body.classList.toggle('dark-mode')" title="تبديل المظهر">
                <i class="fas fa-moon"></i>
            </button>
            <button class="btn-primary" onclick="openRadModal(null)">
                <i class="fas fa-plus"></i> طلب جديد
            </button>
        </div>
    </div>
</header>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<main class="main-content">

    <!-- ───────── DASHBOARD PAGE ───────── -->
    <div id="rad-page-dashboard" class="interface active">

        <!-- Stats Cards -->
        <div class="cards-grid" style="margin-bottom:28px">
            <div class="main-card">
                <div class="card-icon"><i class="fas fa-x-ray"></i></div>
                <h3>إجمالي الطلبات</h3>
                <div class="card-count"><?= $totalRequests ?></div>
            </div>
            <div class="main-card">
                <div class="card-icon" style="background:rgba(245,158,11,0.1)">
                    <i class="fas fa-clock" style="color:var(--warn-orange)"></i>
                </div>
                <h3>قيد الانتظار</h3>
                <div class="card-count" style="color:var(--warn-orange)"><?= $pendingCount ?></div>
            </div>
            <div class="main-card">
                <div class="card-icon" style="background:rgba(16,185,129,0.1)">
                    <i class="fas fa-check-circle" style="color:var(--emerald)"></i>
                </div>
                <h3>مكتملة</h3>
                <div class="card-count" style="color:var(--emerald)"><?= $completedCount ?></div>
            </div>
            <div class="main-card">
                <div class="card-icon" style="background:rgba(239,68,68,0.1)">
                    <i class="fas fa-exclamation-triangle" style="color:var(--urgent-red)"></i>
                </div>
                <h3>حالات عاجلة</h3>
                <div class="card-count" style="color:var(--urgent-red)"><?= $urgentCount ?></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="rad-toolbar">
            <div class="rad-search">
                <i class="fas fa-search"></i>
                <input type="text" id="radSearch" placeholder="ابحث عن مريض أو طبيب أو نوع الفحص..." oninput="radFilter()">
            </div>
            <select class="rad-filter" id="radStatusFilter" onchange="radFilter()">
                <option value="">كل الحالات</option>
                <option value="pending">قيد الانتظار</option>
                <option value="scheduled">مجدول</option>
                <option value="completed">مكتمل</option>
                <option value="urgent">عاجل</option>
            </select>
            <select class="rad-filter" id="radTypeFilter" onchange="radFilter()">
                <option value="">كل الأنواع</option>
                <option value="CT">CT Scan</option>
                <option value="MRI">MRI</option>
                <option value="X-Ray">X-Ray</option>
                <option value="Ultrasound">Ultrasound</option>
                <option value="Mammography">Mammography</option>
            </select>
        </div>

        <!-- Requests Table -->
        <div class="rad-table-wrap">
            <div class="rad-table-header">
                <h3><i class="fas fa-list" style="color:var(--primary);margin-left:8px"></i>قائمة طلبات الأشعة</h3>
                <span class="rad-count-badge" id="radRowCount"><?= count($requests) ?> طلب</span>
            </div>
            <div style="overflow-x:auto">
                <table class="rad-table" id="radTable">
                    <thead>
                        <tr>
                            <th>المريض</th>
                            <th>نوع الفحص</th>
                            <th>الطبيب المعالج</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="radTbody">
                        <?php foreach ($requests as $r): ?>
                        <tr onclick="openRadModal(<?= $r['id'] ?>)"
                            data-name="<?= htmlspecialchars($r['name']) ?>"
                            data-doctor="<?= htmlspecialchars($r['doctor']) ?>"
                            data-type="<?= htmlspecialchars($r['type']) ?>"
                            data-status="<?= $r['status'] ?>">
                            <td>
                                <div class="rad-patient-cell">
                                    <div class="rad-avatar"><?= mb_substr($r['name'],0,1,'UTF-8') ?></div>
                                    <div>
                                        <div class="rad-patient-name"><?= htmlspecialchars($r['name']) ?></div>
                                        <div class="rad-patient-sub"><?= $r['age'] ?> سنة · <?= $r['gender'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="rad-type-badge">
                                    <i class="fas fa-scan"></i>
                                    <?= htmlspecialchars($r['type']) ?>
                                </span>
                            </td>
                            <td style="color:var(--text-secondary)"><?= htmlspecialchars($r['doctor']) ?></td>
                            <td style="color:var(--text-secondary);white-space:nowrap"><?= $r['date'] ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass[$r['status']] ?>">
                                    <i class="fas <?= $statusIcon[$r['status']] ?>"></i>
                                    <?= $statusLabels[$r['status']] ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn edit" onclick="event.stopPropagation();openRadModal(<?= $r['id'] ?>)">
                                    <i class="fas fa-eye"></i> عرض
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ───────── QUEUE PAGE ───────── -->
    <div id="rad-page-queue" class="interface">
        <div class="page-header">
            <h2>قائمة انتظار الأشعة</h2>
            <p>الطلبات المعلقة والمجدولة بحسب الأولوية</p>
        </div>
        <div class="bookings-list">
            <?php foreach ($requests as $r):
                if (!in_array($r['status'],['pending','urgent'])) continue; ?>
            <div class="booking-item" onclick="openRadModal(<?= $r['id'] ?>)" style="cursor:pointer">
                <div class="booking-patient">
                    <div class="rad-avatar" style="width:46px;height:46px;font-size:1rem"><?= mb_substr($r['name'],0,1,'UTF-8') ?></div>
                    <div>
                        <h4><?= htmlspecialchars($r['name']) ?></h4>
                        <p><?= htmlspecialchars($r['type']) ?> · غرفة <?= $r['room'] ?></p>
                    </div>
                </div>
                <span class="status-badge <?= $statusClass[$r['status']] ?>">
                    <i class="fas <?= $statusIcon[$r['status']] ?>"></i>
                    <?= $statusLabels[$r['status']] ?>
                </span>
                <div style="color:var(--text-muted);font-size:0.82rem"><?= $r['date'] ?></div>
                <button class="btn-primary" onclick="event.stopPropagation();openRadModal(<?= $r['id'] ?>)" style="font-size:0.82rem;padding:8px 16px">
                    <i class="fas fa-edit"></i> كتابة تقرير
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ───────── CALENDAR PAGE ───────── -->
    <div id="rad-page-calendar" class="interface">
        <div class="page-header">
            <h2>تقويم مواعيد الأشعة</h2>
            <p>جدول الفحوصات والمواعيد الطبية</p>
        </div>
        <div class="rad-cal-wrap">
            <div class="rad-cal-nav">
                <h3>مايو 2026</h3>
                <div class="rad-cal-nav-btns">
                    <button class="rad-cal-nav-btn"><i class="fas fa-chevron-right"></i></button>
                    <button class="rad-cal-nav-btn"><i class="fas fa-chevron-left"></i></button>
                </div>
            </div>
            <div class="rad-cal-days-header">
                <div class="rad-cal-day-name">أحد</div>
                <div class="rad-cal-day-name">إثنين</div>
                <div class="rad-cal-day-name">ثلاثاء</div>
                <div class="rad-cal-day-name">أربعاء</div>
                <div class="rad-cal-day-name">خميس</div>
                <div class="rad-cal-day-name">جمعة</div>
                <div class="rad-cal-day-name">سبت</div>
            </div>
            <div class="rad-cal-grid" id="radCalGrid"></div>
            <div class="rad-cal-legend">
                <span><span class="rad-legend-dot" style="background:rgba(14,165,233,0.3)"></span>مجدول</span>
                <span><span class="rad-legend-dot" style="background:rgba(239,68,68,0.25)"></span>عاجل</span>
                <span><span class="rad-legend-dot" style="background:rgba(16,185,129,0.25)"></span>مكتمل</span>
            </div>
        </div>
    </div>

</main><!-- /main-content -->

<!-- ══════════════ RADIOLOGY MODAL ══════════════ -->
<div class="rad-modal-overlay" id="radModalOverlay" onclick="radCloseOnBg(event)">
    <div class="rad-modal" role="dialog" aria-modal="true" aria-labelledby="radModalTitle">
        <div class="rad-modal-head">
            <h3 id="radModalTitle">تفاصيل طلب الأشعة</h3>
            <button class="rad-modal-close" onclick="closeRadModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="rad-modal-body" id="radModalBody"></div>
        <div class="rad-modal-footer">
            <button class="btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة
            </button>
            <button class="btn-secondary" id="radDownloadBtn">
                <i class="fas fa-download"></i> تحميل PDF
            </button>
            <button class="btn-primary" onclick="saveRadReport()">
                <i class="fas fa-save"></i> حفظ التقرير
            </button>
        </div>
    </div>
</div>

<!-- ══════════════ SCRIPTS ══════════════ -->
<script>
// ── Data ──
const radRequests = <?= json_encode($requests, JSON_UNESCAPED_UNICODE) ?>;

const statusLabels = {
    pending:'قيد الانتظار', scheduled:'مجدول',
    completed:'مكتمل', urgent:'عاجل'
};
const statusClass = {
    pending:'chronic', scheduled:'followup',
    completed:'followup', urgent:'urgent'
};
const statusIcon = {
    pending:'fa-clock', scheduled:'fa-calendar-check',
    completed:'fa-check-circle', urgent:'fa-exclamation-triangle'
};

// ── Page switcher ──
function showRadPage(page) {
    document.querySelectorAll('.interface').forEach(p => p.classList.remove('active'));
    const el = document.getElementById('rad-page-' + page);
    if (el) el.classList.add('active');

    const titles = {
        dashboard: ['قسم الأشعة والتصوير الطبي', 'إدارة طلبات الأشعة وتقارير التصوير'],
        queue:     ['قائمة انتظار الأشعة', 'الطلبات المعلقة والعاجلة بحسب الأولوية'],
        calendar:  ['تقويم مواعيد الأشعة', 'جدول الفحوصات والمواعيد الطبية']
    };
    if (titles[page]) {
        document.getElementById('rad-page-title').textContent = titles[page][0];
        document.getElementById('rad-page-sub').textContent   = titles[page][1];
    }

    // Update sidebar active
    document.querySelectorAll('.snav-item').forEach(i => i.classList.remove('snav-item-active'));
    const map = {dashboard:0, queue:1, calendar:3};
    const items = document.querySelectorAll('#snb-rad .snav-item');
    if (map[page] !== undefined && items[map[page]]) items[map[page]].classList.add('snav-item-active');
}

// ── Sidebar Accordion ──
function snavToggle(id) {
    const header = document.querySelector('#sng-' + id + ' .snav-header');
    const body   = document.getElementById('snb-' + id);
    if (!body) return;
    const isOpen = body.classList.contains('snb-open');
    body.classList.toggle('snb-open', !isOpen);
    if (header) header.classList.toggle('snav-open', !isOpen);
}

// ── Filter ──
function radFilter() {
    const q  = document.getElementById('radSearch').value.toLowerCase();
    const st = document.getElementById('radStatusFilter').value;
    const tp = document.getElementById('radTypeFilter').value.toLowerCase();
    let count = 0;
    document.querySelectorAll('#radTbody tr').forEach(row => {
        const name   = (row.dataset.name   || '').toLowerCase();
        const doctor = (row.dataset.doctor || '').toLowerCase();
        const type   = (row.dataset.type   || '').toLowerCase();
        const status = (row.dataset.status || '');
        const matchQ  = !q  || name.includes(q) || doctor.includes(q) || type.includes(q);
        const matchSt = !st || status === st;
        const matchTp = !tp || type.includes(tp.toLowerCase());
        const show = matchQ && matchSt && matchTp;
        row.classList.toggle('rad-row-hidden', !show);
        if (show) count++;
    });
    document.getElementById('radRowCount').textContent = count + ' طلب';
}

// ── Modal ──
let currentRequestId = null;

function openRadModal(id) {
    currentRequestId = id;
    const r = id ? radRequests.find(x => x.id === id) : null;
    const overlay = document.getElementById('radModalOverlay');

    document.getElementById('radModalTitle').textContent = r
        ? 'طلب أشعة — ' + r.name
        : 'طلب أشعة جديد';

    document.getElementById('radModalBody').innerHTML = buildModalContent(r);
    overlay.classList.add('active');
}

function buildModalContent(r) {
    const name     = r ? r.name    : '';
    const age      = r ? r.age     : '';
    const gender   = r ? r.gender  : '';
    const room     = r ? r.room    : '';
    const doctor   = r ? r.doctor  : '';
    const type     = r ? r.type    : '';
    const date     = r ? r.date    : '';
    const device   = r ? r.device  : '';
    const tech     = r ? r.tech    : '';
    const notes    = r ? r.notes   : '';
    const status   = r ? r.status  : 'pending';

    return `
    <div>
      <div class="rad-section-title"><i class="fas fa-user-circle"></i>معلومات المريض</div>
      <div class="rad-info-grid">
        <div class="rad-info-item">
          <div class="rad-info-label">الاسم الكامل</div>
          <div class="rad-info-value">${name || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">العمر</div>
          <div class="rad-info-value">${age ? age + ' سنة' : '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">الجنس</div>
          <div class="rad-info-value">${gender || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">رقم الغرفة</div>
          <div class="rad-info-value">${room ? 'غرفة ' + room : '—'}</div>
        </div>
        <div class="rad-info-item" style="grid-column:1/-1">
          <div class="rad-info-label">الطبيب المعالج</div>
          <div class="rad-info-value">${doctor || '—'}</div>
        </div>
      </div>
    </div>

    <div>
      <div class="rad-section-title"><i class="fas fa-x-ray"></i>معلومات الفحص</div>
      <div class="rad-info-grid">
        <div class="rad-info-item">
          <div class="rad-info-label">نوع الأشعة</div>
          <div class="rad-info-value">${type || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">تاريخ الفحص</div>
          <div class="rad-info-value">${date || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">الجهاز المستخدم</div>
          <div class="rad-info-value">${device || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">التقني المسؤول</div>
          <div class="rad-info-value">${tech || '—'}</div>
        </div>
        <div class="rad-info-item">
          <div class="rad-info-label">الحالة</div>
          <div class="rad-info-value">
            <span class="status-badge ${statusClass[status] || 'chronic'}">
              <i class="fas ${statusIcon[status] || 'fa-clock'}"></i>
              ${statusLabels[status] || status}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="rad-section-title"><i class="fas fa-file-medical-alt"></i>تقرير الأشعة</div>
      <textarea class="rad-textarea" id="radReportText" placeholder="اكتب نتائج وتفسيرات الأشعة هنا...">${notes}</textarea>
    </div>

    <div>
      <div class="rad-section-title"><i class="fas fa-images"></i>الصور الطبية</div>
      <div class="rad-upload-area" id="radDropZone"
           ondragover="event.preventDefault()"
           ondrop="radHandleDrop(event)"
           onclick="document.getElementById('radFileInput').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>اسحب وأفلت الصور هنا، أو <span>تصفح الملفات</span></p>
        <small>يدعم DICOM، JPEG، PNG</small>
      </div>
      <input type="file" id="radFileInput" multiple accept="image/*"
             style="display:none" onchange="radHandleFiles(this.files)">
      <div class="rad-preview-grid" id="radPreviewGrid"></div>
    </div>`;
}

function closeRadModal() {
    document.getElementById('radModalOverlay').classList.remove('active');
}

function radCloseOnBg(e) {
    if (e.target === document.getElementById('radModalOverlay')) closeRadModal();
}

function saveRadReport() {
    const text = document.getElementById('radReportText');
    if (text && text.value.trim()) {
        alert('✅ تم حفظ التقرير بنجاح');
    } else {
        alert('⚠️ الرجاء كتابة محتوى التقرير أولاً');
    }
}

// ── File upload ──
function radHandleDrop(e) {
    e.preventDefault();
    radHandleFiles(e.dataTransfer.files);
}
function radHandleFiles(files) {
    const grid = document.getElementById('radPreviewGrid');
    Array.from(files).forEach(f => {
        const wrap = document.createElement('div');
        wrap.className = 'rad-preview-thumb';
        if (f.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            wrap.appendChild(img);
        } else {
            wrap.innerHTML = '<i class="fas fa-file-image"></i>';
        }
        const del = document.createElement('button');
        del.className = 'rad-preview-del';
        del.innerHTML = '<i class="fas fa-times"></i>';
        del.setAttribute('aria-label','حذف الصورة');
        del.onclick = () => wrap.remove();
        wrap.appendChild(del);
        grid.appendChild(wrap);
    });
}

// ── Calendar ──
function buildRadCalendar() {
    const events = {
        '2026-05-21': [{t:'CT — أمينة بن موسى', c:'rad-ev-red'},{t:'MRI — يوسف تواتي',c:'rad-ev-blue'},{t:'MRI — نسيم وقيل',c:'rad-ev-red'}],
        '2026-05-22': [{t:'Ultrasound — خالد مجدوب',c:'rad-ev-blue'},{t:'Mammography — مريم',c:'rad-ev-blue'}],
        '2026-05-23': [{t:'X-Ray — حياة بوزيدي',c:'rad-ev-blue'}],
        '2026-05-20': [{t:'Chest X-Ray — فاطمة',c:'rad-ev-green'},{t:'CT — سارة بواشاوي',c:'rad-ev-green'}],
        '2026-05-19': [{t:'CT Chest — رشيد خليف',c:'rad-ev-green'}],
        '2026-05-26': [{t:'MRI Brain — إحالة',c:'rad-ev-blue'}],
        '2026-05-28': [{t:'Bone Scan — TBD',c:'rad-ev-blue'}],
    };
    const grid = document.getElementById('radCalGrid');
    if (!grid) return;
    const firstDay = new Date(2026,4,1).getDay();
    const cells = [];
    for (let i=0; i<firstDay; i++) cells.push({d:new Date(2026,3,30-firstDay+i+1), other:true});
    for (let i=1; i<=31; i++) cells.push({d:new Date(2026,4,i), other:false});
    let rem = 7 - cells.length%7; if (rem===7) rem=0;
    for (let i=1; i<=rem; i++) cells.push({d:new Date(2026,5,i), other:true});

    grid.innerHTML = cells.map(c => {
        const key = `2026-${String(c.d.getMonth()+1).padStart(2,'0')}-${String(c.d.getDate()).padStart(2,'0')}`;
        const evs = (events[key]||[]).slice(0,2).map(e=>
            `<div class="rad-cal-event ${e.c}">${e.t}</div>`).join('');
        const today = key==='2026-05-21' ? 'rad-today' : '';
        const other = c.other ? 'rad-other' : '';
        return `<div class="rad-cal-cell ${today} ${other}">
            <div class="rad-cal-num">${c.d.getDate()}</div>${evs}</div>`;
    }).join('');
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function() {
    buildRadCalendar();
    // Open Radiology accordion by default
    const snbRad = document.getElementById('snb-rad');
    const sngRad = document.querySelector('#sng-rad .snav-header');
    if (snbRad) snbRad.classList.add('snb-open');
    if (sngRad) sngRad.classList.add('snav-open');
});
</script>
</body>
</html>
