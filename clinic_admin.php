<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* ════════════════════════════════════════════════════════════
   قاعدة البيانات — يجب تضمينها هنا حتى يكون $pdo متاحاً في الصفحة.
   كان غيابها هو السبب الحقيقي للصفحة البيضاء عند إضافة استعلامات PHP.
   ════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/db.php';

/* ════════════════════════════════════════════════════════════
   نظام التنبيهات (MedChifaGiz)
   كل استعلام مستقل داخل try/catch، لذا لا يمكن لأي خطأ SQL
   (عمود/جدول غير موجود) أن يُبيّض الصفحة مرة أخرى — يرجع 0 فقط.
   ════════════════════════════════════════════════════════════ */
if (!function_exists('mcg_count')) {
    function mcg_count(PDO $pdo, string $sql): int {
        try {
            $stmt = $pdo->query($sql);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('[MCG notifications] ' . $e->getMessage());
            return 0;
        }
    }
}

$mcgNotifications = [];

/* 1) حسابات معطّلة */
$nInactive = mcg_count($pdo, "
    SELECT COUNT(*) FROM clinic_staff
    WHERE account_status = 'inactive'
");
if ($nInactive > 0) {
    $mcgNotifications[] = [
        'icon'  => '🟡',
        'class' => 'warn',
        'text'  => 'يوجد ' . $nInactive . ' حساب/حسابات معطّلة',
    ];
}

/* 2) مصالح بدون مسؤول (Service Admin)
   ملاحظة: يفترض ربط clinic_staff.service_id ← services.id
   إن اختلف اسم العمود في قاعدتك، عدّل سطري service_id / s.id فقط. */
$nNoAdmin = mcg_count($pdo, "
    SELECT COUNT(*) FROM services s
    WHERE NOT EXISTS (
        SELECT 1 FROM clinic_staff cs
        WHERE cs.service_id = s.id
          AND cs.role = 'service_admin'
    )
");
if ($nNoAdmin > 0) {
    $mcgNotifications[] = [
        'icon'  => '🔵',
        'class' => 'info',
        'text'  => 'يوجد ' . $nNoAdmin . ' مصلحة/مصالح بدون مسؤول',
    ];
}

/* ── التحقق من أسماء الأعمدة الفعلية في clinic_staff قبل أي استعلام ──
   يمنع خطأ Unknown column نهائياً ويتكيّف مع تسمية قاعدتك. */
if (!function_exists('mcg_columns')) {
    function mcg_columns(PDO $pdo, string $table): array {
        try {
            $stmt = $pdo->query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table)
            );
            return array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            error_log('[MCG columns] ' . $e->getMessage());
            return [];
        }
    }
}
if (!function_exists('mcg_pick')) {
    function mcg_pick(array $cols, array $candidates): ?string {
        foreach ($candidates as $c) {
            if (in_array(strtolower($c), $cols, true)) return $c;
        }
        return null;
    }
}

$mcgStaffCols = mcg_columns($pdo, 'clinic_staff');
$mcgHasRole   = in_array('role', $mcgStaffCols, true);

/* 3) موظفون لم يسجّلوا الدخول منذ أكثر من 7 أيام */
$loginCol = mcg_pick($mcgStaffCols, ['last_login', 'last_login_at', 'lastlogin', 'last_seen', 'last_activity']);
if ($loginCol !== null) {
    $nIdle = mcg_count($pdo, "
        SELECT COUNT(*) FROM clinic_staff
        WHERE `$loginCol` IS NOT NULL
          AND `$loginCol` < (NOW() - INTERVAL 7 DAY)
    ");
    if ($nIdle > 0) {
        $mcgNotifications[] = [
            'icon'  => '🟠',
            'class' => 'warn',
            'text'  => 'يوجد ' . $nIdle . ' موظف/موظفين لم يسجّلوا الدخول منذ أكثر من 7 أيام',
        ];
    }
}

/* 4) أطباء بدون تخصص */
$specCol = mcg_pick($mcgStaffCols, ['specialty', 'speciality', 'specialite', 'specialization', 'specialty_name']);
if ($specCol !== null && $mcgHasRole) {
    $nNoSpec = mcg_count($pdo, "
        SELECT COUNT(*) FROM clinic_staff
        WHERE role = 'doctor'
          AND (`$specCol` IS NULL OR TRIM(`$specCol`) = '')
    ");
    if ($nNoSpec > 0) {
        $mcgNotifications[] = [
            'icon'  => '🔴',
            'class' => 'danger',
            'text'  => 'يوجد ' . $nNoSpec . ' طبيب بدون تخصص',
        ];
    }
}

$mcgNotifTotal = count($mcgNotifications);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedChifaGiz — إدارة العيادة</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:15px}
body{
  font-family:'Cairo',sans-serif;
  background:#0b0f1a;
  color:#e2e8f0;
  min-height:100vh;
  overflow-x:hidden;
}

/* ═══════════════════════════════════════
   CSS VARIABLES
═══════════════════════════════════════ */
:root{
  --bg-deep:#0b0f1a;
  --bg-card:#111827;
  --bg-card2:#161d2e;
  --bg-hover:#1e2a3e;
  --border:#1e2d45;
  --border2:#243450;
  --accent:#0ea5e9;
  --accent2:#38bdf8;
  --accent-glow:rgba(14,165,233,.18);
  --text:#e2e8f0;
  --text-muted:#7a8fa6;
  --text-dim:#4a6072;
  --sidebar-w:260px;
  --header-h:64px;
  --radius:14px;
  --radius-sm:8px;
}
body.light{
  --bg-deep:#f0f4f8;
  --bg-card:#ffffff;
  --bg-card2:#f8fafc;
  --bg-hover:#e8f0fa;
  --border:#d1dce8;
  --border2:#c0cedf;
  --accent:#0284c7;
  --accent2:#0369a1;
  --text:#1e293b;
  --text-muted:#64748b;
  --text-dim:#94a3b8;
  background:#f0f4f8;
  color:#1e293b;
}
body.light .bg-mesh{
  background:
    radial-gradient(ellipse 60% 40% at 20% 10%, rgba(2,132,199,.05) 0%, transparent 60%),
    radial-gradient(ellipse 40% 50% at 80% 80%, rgba(99,102,241,.04) 0%, transparent 60%);
}
body.light .sidebar-logo,body.light .sidebar-profile{border-bottom-color:#e8eef6}
body.light .sidebar-footer{border-top-color:#e8eef6}
body.light .header{background:rgba(240,244,248,.92);border-bottom-color:#d1dce8}
body.light .header-title{color:#1e293b}
body.light .header-time{color:#0284c7}
body.light .header-date{color:#64748b}
body.light .header-search{background:rgba(0,0,0,.04);border-color:#d1dce8}
body.light .header-search input{color:#1e293b}
body.light .sidebar-toggle,body.light .header-btn{background:rgba(0,0,0,.05);color:#64748b}
body.light .welcome-bar{background:linear-gradient(135deg,#dbeafe,#eff6ff);border-color:#c0cedf}
body.light .welcome-bar h1{color:#1e293b}
body.light .stat-card{background:#fff;border-color:#d1dce8}
body.light .stat-value{color:#1e293b}
body.light .stat-label{color:#64748b}
body.light .stat-sub{color:#94a3b8}
body.light .data-table-wrap{background:#fff;border-color:#d1dce8}
body.light .data-table th{background:rgba(0,0,0,.02);color:#94a3b8}
body.light .data-table td{color:#1e293b;border-bottom-color:#d1dce8}
body.light .data-table tbody tr:hover{background:#e8f0fa}
body.light .section-title{color:#1e293b}
body.light .section-sub{color:#64748b}
body.light .team-sum-card{background:#fff;border-color:#d1dce8}
body.light .team-sum-val{color:#1e293b}
body.light .team-sum-lbl{color:#64748b}
body.light .activity-list{background:#fff;border-color:#d1dce8}
body.light .activity-item{border-bottom-color:#d1dce8}
body.light .activity-item:hover{background:#e8f0fa}
body.light .act-title{color:#1e293b}
body.light .act-sub{color:#64748b}
body.light .act-time{color:#94a3b8}
body.light .settings-group{background:#fff;border-color:#d1dce8}
body.light .settings-profile-card{background:#fff;border-color:#d1dce8}
body.light .settings-profile-card h3{color:#1e293b}
body.light .settings-profile-card p{color:#64748b}
body.light .settings-group-title{color:#0284c7}
body.light .settings-field label{color:#64748b}
body.light .settings-field input,body.light .settings-field textarea{background:rgba(0,0,0,.03);border-color:#c0cedf;color:#1e293b}
body.light .logo-upload{border-color:#c0cedf}
body.light .logo-upload span{color:#64748b}
body.light .search-bar{background:rgba(0,0,0,.04);border-color:#d1dce8}
body.light .search-bar input{color:#1e293b}
body.light .btn-secondary{background:rgba(0,0,0,.04);border-color:#c0cedf;color:#1e293b}
body.light .modal{background:#fff;border-color:#c0cedf}
body.light .modal-title{color:#1e293b;border-bottom-color:#d1dce8}
body.light .modal-field input,body.light .modal-field select,body.light .modal-field textarea{background:rgba(0,0,0,.03);border-color:#c0cedf;color:#1e293b}
body.light .modal-field select option{background:#fff;color:#1e293b}
body.light .toast{background:#1e3a5f;color:#fff}
body.light .modal-close{background:rgba(0,0,0,.06);color:#64748b}

/* بطاقة آخر دخول — الحالة الخضراء (الوضع الليلي افتراضياً) */
.last-login-ok{
  margin-top:12px;
  padding:12px 16px;
  border-radius:12px;
  background:rgba(34,197,94,.18);
  border:1px solid rgba(34,197,94,.45);
  border-right:4px solid #22c55e;
  color:#ffffff;
}
/* الوضع النهاري — يبقى كما كان تماماً */
body.light .last-login-ok{
  background:#dcfce7;
  border:none;
  border-right:4px solid #22c55e;
  color:#1e293b;
}

/* ═══════════════════════════════════════
   ROOM DISTRIBUTION — توزيع الغرف
═══════════════════════════════════════ */
.room-dist-options{
  display:flex;flex-direction:column;gap:10px;margin-top:4px;
}
.room-dist-option{
  display:block;cursor:pointer;
  border:1.5px solid var(--border2);
  border-radius:12px;
  padding:0;
  transition:border-color .18s, background .18s;
  overflow:hidden;
}
.room-dist-option:has(input:checked){
  border-color:var(--accent);
  background:rgba(14,165,233,.07);
}
.room-dist-option input[type="radio"]{
  position:absolute;opacity:0;pointer-events:none;
}
.room-dist-option-inner{
  display:flex;align-items:center;gap:14px;
  padding:13px 16px;
}
.room-dist-option-icons{
  display:flex;align-items:center;gap:6px;
  font-size:18px;flex-shrink:0;
  width:44px;justify-content:center;
}
.room-dist-option-text{
  display:flex;flex-direction:column;gap:2px;
}
.room-dist-option-title{
  font-size:13.5px;font-weight:700;color:var(--text);
}
.room-dist-option-sub{
  font-size:11.5px;color:var(--text-muted);
}
body.light .room-dist-option{border-color:#c0cedf}
body.light .room-dist-option:has(input:checked){border-color:var(--accent);background:rgba(2,132,199,.06)}
body.light .room-dist-option-title{color:#1e293b}

/* Wing Blocks */
.room-fields-section{
  display:flex;flex-direction:column;gap:12px;
  margin-top:4px;
  animation:fadeIn .2s ease;
}
.room-wing-block{
  border-radius:12px;
  padding:14px 16px;
  border:1.5px solid;
}
.room-wing-men{
  border-color:rgba(96,165,250,.25);
  background:rgba(96,165,250,.05);
}
.room-wing-women{
  border-color:rgba(244,114,182,.25);
  background:rgba(244,114,182,.05);
}
.room-wing-shared{
  border-color:rgba(52,211,153,.25);
  background:rgba(52,211,153,.05);
}
.room-wing-header{
  display:flex;align-items:center;gap:8px;
  font-size:13px;font-weight:700;color:var(--text);
}
.room-wing-men .room-wing-header{color:#93c5fd}
.room-wing-women .room-wing-header{color:#f9a8d4}
.room-wing-shared .room-wing-header{color:#6ee7b7}
body.light .room-wing-men .room-wing-header{color:#2563eb}
body.light .room-wing-women .room-wing-header{color:#db2777}
body.light .room-wing-shared .room-wing-header{color:#059669}

/* ═══════════════════════════════════════
   HAS-ROOMS TOGGLE — سؤال الغرف
═══════════════════════════════════════ */
.has-rooms-toggle-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 16px;
  border-radius:12px;
  border:1.5px solid var(--border2);
  background:var(--bg-card2);
  margin-bottom:14px;
  transition:border-color .18s,background .18s;
}
.has-rooms-toggle-row.is-yes{
  border-color:rgba(14,165,233,.35);
  background:rgba(14,165,233,.06);
}
.has-rooms-toggle-info{display:flex;align-items:center;gap:12px}
.has-rooms-toggle-icon{
  width:38px;height:38px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
  background:rgba(14,165,233,.12);color:var(--accent);
}
.has-rooms-toggle-text{display:flex;flex-direction:column;gap:2px}
.has-rooms-toggle-label{font-size:13.5px;font-weight:700;color:var(--text)}
.has-rooms-toggle-sub{font-size:11.5px;color:var(--text-muted)}
.has-rooms-radio-group{display:flex;align-items:center;gap:8px;flex-shrink:0}
.has-rooms-radio-btn{
  display:flex;align-items:center;gap:6px;
  padding:7px 16px;border-radius:8px;
  border:1.5px solid var(--border2);
  background:transparent;
  cursor:pointer;font-family:'Cairo',sans-serif;
  font-size:13px;font-weight:700;color:var(--text-muted);
  transition:all .18s;
}
.has-rooms-radio-btn input[type="radio"]{position:absolute;opacity:0;pointer-events:none}
.has-rooms-radio-btn.selected-yes{
  border-color:var(--accent);
  background:rgba(14,165,233,.14);
  color:var(--accent2);
}
.has-rooms-radio-btn.selected-no{
  border-color:rgba(100,116,139,.4);
  background:rgba(100,116,139,.1);
  color:#94a3b8;
}
body.light .has-rooms-toggle-row{background:#f8fafc;border-color:#c0cedf}
body.light .has-rooms-toggle-row.is-yes{border-color:rgba(2,132,199,.3);background:rgba(2,132,199,.05)}
body.light .has-rooms-toggle-label{color:#1e293b}
body.light .has-rooms-radio-btn{border-color:#c0cedf;color:#64748b}
body.light .has-rooms-radio-btn.selected-yes{border-color:#0284c7;background:rgba(2,132,199,.1);color:#0284c7}
body.light .has-rooms-radio-btn.selected-no{border-color:#94a3b8;background:rgba(100,116,139,.08);color:#64748b}

/* Totals Bar */
.room-totals-bar{
  display:flex;align-items:center;
  background:linear-gradient(135deg,rgba(14,165,233,.1),rgba(99,102,241,.08));
  border:1.5px solid rgba(14,165,233,.2);
  border-radius:12px;
  padding:14px 20px;
  gap:0;
  margin-top:4px;
}
.room-total-item{
  display:flex;align-items:center;gap:12px;
  flex:1;
}
.room-total-item > i{
  font-size:20px;color:var(--accent2);
  width:36px;height:36px;
  border-radius:9px;
  background:rgba(14,165,233,.15);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
}
.room-total-label{
  font-size:11.5px;color:var(--text-muted);font-weight:600;
}
.room-total-value{
  font-size:22px;font-weight:800;color:var(--accent2);line-height:1.1;
  font-family:'JetBrains Mono',monospace;
}
.room-total-divider{
  width:1px;height:40px;
  background:var(--border2);
  margin:0 20px;
  flex-shrink:0;
}
body.light .room-totals-bar{background:rgba(2,132,199,.06);border-color:rgba(2,132,199,.2)}
body.light .room-total-value{color:#0284c7}

/* ═══════════════════════════════════════
   INSTITUTION STRUCTURE — هيكل المؤسسة
═══════════════════════════════════════ */
.struct-group{
  background:var(--bg-card);
  border:1px solid var(--border2);
  border-radius:var(--radius);
  padding:20px 22px;
  margin-bottom:0;
}
.struct-group-title{
  font-size:13px;font-weight:700;
  color:var(--accent);
  display:flex;align-items:center;gap:8px;
  margin-bottom:16px;
  text-transform:uppercase;letter-spacing:.5px;
}
.struct-toggle-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 16px;
  border-radius:10px;
  border:1.5px solid var(--border2);
  background:var(--bg-card2);
  transition:border-color .18s,background .18s;
  margin-bottom:10px;
  cursor:default;
}
.struct-toggle-row:last-child{margin-bottom:0}
.struct-toggle-row:has(.struct-switch input:checked){
  border-color:rgba(14,165,233,.35);
  background:rgba(14,165,233,.05);
}
.struct-toggle-info{display:flex;align-items:center;gap:12px}
.struct-toggle-icon{
  width:38px;height:38px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
}
.struct-toggle-text{display:flex;flex-direction:column;gap:2px}
.struct-toggle-label{font-size:13.5px;font-weight:700;color:var(--text)}
.struct-toggle-sub{font-size:11.5px;color:var(--text-muted)}
/* Toggle Switch */
.struct-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.struct-switch input{opacity:0;width:0;height:0;position:absolute}
.struct-switch-slider{
  position:absolute;inset:0;
  background:rgba(255,255,255,.1);
  border-radius:24px;cursor:pointer;
  transition:background .2s;
  border:1.5px solid var(--border2);
}
.struct-switch-slider::before{
  content:'';position:absolute;
  width:16px;height:16px;
  left:3px;top:50%;transform:translateY(-50%);
  border-radius:50%;background:#7a8fa6;
  transition:transform .2s,background .2s;
}
.struct-switch input:checked + .struct-switch-slider{
  background:rgba(14,165,233,.2);
  border-color:rgba(14,165,233,.5);
}
.struct-switch input:checked + .struct-switch-slider::before{
  transform:translate(20px,-50%);
  background:#0ea5e9;
}
body.light .struct-group{background:#fff;border-color:#d1dce8}
body.light .struct-toggle-row{background:#f8fafc;border-color:#c0cedf}
body.light .struct-toggle-row:has(.struct-switch input:checked){border-color:rgba(2,132,199,.3);background:rgba(2,132,199,.04)}
body.light .struct-toggle-label{color:#1e293b}
body.light .struct-switch-slider{background:rgba(0,0,0,.06);border-color:#c0cedf}
body.light .struct-switch-slider::before{background:#94a3b8}
body.light .struct-switch input:checked + .struct-switch-slider{background:rgba(2,132,199,.15);border-color:rgba(2,132,199,.4)}
body.light .struct-switch input:checked + .struct-switch-slider::before{background:#0284c7}

/* ─ Status badge for structure state ─ */
.struct-status-bar{
  display:flex;align-items:center;gap:8px;
  margin-top:14px;padding:10px 14px;
  border-radius:8px;
  background:rgba(14,165,233,.07);
  border:1px solid rgba(14,165,233,.15);
  font-size:12px;color:var(--text-muted);
}
.struct-status-bar i{color:var(--accent);font-size:13px}
body.light .struct-status-bar{background:rgba(2,132,199,.05);border-color:rgba(2,132,199,.15)}

/* ═══════════════════════════════════════
   BACKGROUND
═══════════════════════════════════════ */
.bg-mesh{
  position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(ellipse 60% 40% at 20% 10%, rgba(14,165,233,.07) 0%, transparent 60%),
    radial-gradient(ellipse 40% 50% at 80% 80%, rgba(99,102,241,.06) 0%, transparent 60%);
}

/* ═══════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════ */
.sidebar{
  position:fixed;top:0;right:0;
  width:var(--sidebar-w);height:100vh;
  background:linear-gradient(180deg,#0d1526 0%,#0b1220 100%);
  border-left:1px solid var(--border);
  display:flex;flex-direction:column;
  z-index:100;
  overflow:hidden;
  transition:transform .3s ease;
}
.sidebar-glow{
  position:absolute;top:-60px;right:-60px;
  width:200px;height:200px;
  background:radial-gradient(circle, rgba(14,165,233,.12) 0%, transparent 70%);
  pointer-events:none;
}
.sidebar-logo{
  display:flex;
  align-items:center;
  gap:6px;
}
.logo-icon{
  width:100px;
  height:100px;
  border-radius:14px;
  overflow:hidden;
  flex-shrink:0;
  display:flex;
  align-items:center;
  justify-content:center;
}

.logo-icon img{
  width:100%;
  height:100%;
  object-fit:contain;
  display:block;
}
.logo-brand{
  font-size:16px;font-weight:800;color:#fff;letter-spacing:-.3px;
  display:block;
}
.logo-brand em{color:#0ea5e9;font-style:normal}
.logo-sub{
  font-size:10.5px;color:var(--text-muted);letter-spacing:.5px;
  display:block;margin-top:1px;
}

/* Profile */
.sidebar-profile{
  display:flex;align-items:center;gap:12px;
  padding:16px 20px;
  border-bottom:1px solid var(--border);
}
.profile-avatar{
  position:relative;width:42px;height:42px;flex-shrink:0;
}
.profile-avatar{
  position:relative;
  width:42px;
  height:42px;
  flex-shrink:0;
  display:flex;
  align-items:center;
  justify-content:center;
}

.profile-avatar i{
  font-size:42px;
  color:#d97706;
}
.avatar-ring{
  position:absolute;inset:-2px;border-radius:50%;
  border:2px solid transparent;
  background:linear-gradient(#0b1220,#0b1220) padding-box,
             linear-gradient(135deg,#0ea5e9,#6366f1) border-box;
}
.online-dot{
  position:absolute;bottom:1px;left:1px;
  width:10px;height:10px;border-radius:50%;
  background:#10b981;border:2px solid #0b1220;
}
.profile-name{font-size:13px;font-weight:700;color:#fff;display:block}
.profile-role{font-size:11px;color:var(--text-muted);display:block;margin-top:2px}

/* Nav */
.sidebar-nav{
  flex:1;
  overflow-y:auto;
  padding:12px 12px 8px;
  scrollbar-width:thin;
  scrollbar-color:rgba(14,165,233,.35) transparent;
  scroll-behavior:smooth;
}
.sidebar-nav::-webkit-scrollbar{
  width:4px;
}
.sidebar-nav::-webkit-scrollbar-track{
  background:transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb{
  background:rgba(14,165,233,.3);
  border-radius:999px;
  transition:background .2s;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover{
  background:rgba(14,165,233,.6);
}
body.light .sidebar-nav{
  scrollbar-color:rgba(2,132,199,.3) transparent;
}

/* ═══════════════════════════════════════
   TODAY ACTIVITY SECTION
═══════════════════════════════════════ */
.today-section{margin-bottom:28px}
.today-section-header{
  display:flex;align-items:center;gap:10px;
  margin-bottom:16px;
}
.today-section-title{
  font-size:17px;font-weight:800;color:#fff;
}
.today-section-badge{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;
  background:rgba(14,165,233,.12);
  border:1px solid rgba(14,165,233,.2);
  color:var(--accent2);font-size:11px;font-weight:700;
}
.today-section-badge .live-dot{
  width:7px;height:7px;border-radius:50%;
  background:#0ea5e9;
  animation:dotPulse 2s ease infinite;
}
.today-stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:16px;
}
.today-stat-card{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:20px;
  position:relative;overflow:hidden;
  transition:transform .2s,box-shadow .2s;
  cursor:default;
}
.today-stat-card:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 24px rgba(0,0,0,.3);
}
.today-stat-card::before{
  content:'';position:absolute;top:0;right:0;
  width:80px;height:80px;
  background:radial-gradient(circle, var(--tcard-color,#0ea5e9) 0%, transparent 70%);
  opacity:.1;pointer-events:none;
}
.today-stat-card .tcard-icon{
  width:42px;height:42px;border-radius:10px;
  background:rgba(var(--tcard-rgb,14,165,233),.15);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--tcard-color,#0ea5e9);
  margin-bottom:14px;
}
.today-stat-card .tcard-value{
  font-size:28px;font-weight:800;color:#fff;line-height:1;
}
.today-stat-card .tcard-label{
  font-size:12px;font-weight:700;color:var(--text-muted);margin-top:4px;
}
.today-stat-card .tcard-desc{
  font-size:11px;color:var(--text-dim);margin-top:4px;line-height:1.5;
}
body.light .today-stat-card{background:#fff;border-color:#d1dce8}
body.light .today-stat-card .tcard-value{color:#1e293b}
body.light .today-stat-card .tcard-label{color:#64748b}
body.light .today-stat-card .tcard-desc{color:#94a3b8}
body.light .today-section-title{color:#1e293b}

/* ═══════════════════════════════════════
   TWO COLUMNS: ACTIVITY + UPCOMING
═══════════════════════════════════════ */
.dash-two-cols{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
  margin-bottom:28px;
}
.dash-col-header{
  display:flex;align-items:center;gap:10px;
  padding:18px 20px 14px;
  border-bottom:1px solid var(--border);
}
.dash-col-header-icon{
  width:34px;height:34px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;
}
.dash-col-title{font-size:14px;font-weight:800;color:#fff}
.dash-col-sub{font-size:11px;color:var(--text-muted);margin-top:1px}

/* Last Activities */
.last-acts-wrap{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  overflow:hidden;
}
.last-act-item{
  display:flex;align-items:flex-start;gap:12px;
  padding:14px 20px;
  border-bottom:1px solid var(--border);
  transition:background .15s;
}
.last-act-item:last-child{border-bottom:none}
.last-act-item:hover{background:var(--bg-hover)}
.last-act-icon{
  width:34px;height:34px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:13px;
  margin-top:2px;
}
.last-act-body{flex:1;min-width:0}
.last-act-title{font-size:13px;font-weight:600;color:var(--text)}
.last-act-desc{font-size:11.5px;color:var(--text-muted);margin-top:2px;line-height:1.4}
.last-act-time{
  font-size:11px;color:var(--text-dim);
  margin-top:5px;display:flex;align-items:center;gap:4px;
  white-space:nowrap;
}

/* Upcoming */
.upcoming-wrap{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  overflow:hidden;
}
.upcoming-item{
  display:flex;align-items:flex-start;gap:12px;
  padding:14px 20px;
  border-bottom:1px solid var(--border);
  transition:background .15s;
}
.upcoming-item:last-child{border-bottom:none}
.upcoming-item:hover{background:var(--bg-hover)}
.upcoming-icon{
  width:34px;height:34px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:13px;
  margin-top:2px;
}
.upcoming-body{flex:1;min-width:0}
.upcoming-title{font-size:13px;font-weight:600;color:var(--text)}
.upcoming-time{
  font-size:11px;color:var(--text-dim);
  margin-top:5px;display:flex;align-items:center;gap:4px;
}
.upcoming-badge{
  display:inline-flex;align-items:center;
  padding:2px 9px;border-radius:20px;
  font-size:10.5px;font-weight:700;
  margin-top:5px;
}
.upcoming-badge-soon{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.upcoming-badge-today{background:rgba(14,165,233,.12);color:#38bdf8;border:1px solid rgba(14,165,233,.2)}
.upcoming-badge-pending{background:rgba(99,102,241,.12);color:#a5b4fc;border:1px solid rgba(99,102,241,.2)}

body.light .last-acts-wrap,body.light .upcoming-wrap{background:#fff;border-color:#d1dce8}
body.light .last-act-item:hover,body.light .upcoming-item:hover{background:#e8f0fa}
body.light .last-act-item,body.light .upcoming-item{border-bottom-color:#d1dce8}
body.light .last-act-title,body.light .upcoming-title{color:#1e293b}
body.light .last-act-desc{color:#64748b}
body.light .last-act-time,body.light .upcoming-time{color:#94a3b8}
body.light .dash-col-title{color:#1e293b}
body.light .dash-col-sub{color:#64748b}

@media(max-width:900px){
  .dash-two-cols{grid-template-columns:1fr}
}
@media(max-width:600px){
  .today-stats-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:400px){
  .today-stats-grid{grid-template-columns:1fr}
}
body.light .sidebar-nav::-webkit-scrollbar-thumb{
  background:rgba(2,132,199,.25);
}
body.light .sidebar-nav::-webkit-scrollbar-thumb:hover{
  background:rgba(2,132,199,.5);
}
.nav-section-label{
  font-size:10px;font-weight:700;color:var(--text-dim);
  letter-spacing:1.2px;text-transform:uppercase;
  padding:10px 10px 4px;
}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:10px;
  cursor:pointer;color:var(--text-muted);
  transition:all .18s ease;text-decoration:none;
  position:relative;margin-bottom:2px;
  font-size:13.5px;font-weight:500;
}
.nav-item:hover{background:var(--bg-hover);color:var(--text)}
.nav-item.active{
  background:linear-gradient(135deg,rgba(14,165,233,.18),rgba(99,102,241,.12));
  color:var(--accent2);
  border:1px solid rgba(14,165,233,.2);
}
.nav-item.active .nav-icon{color:var(--accent)}
.nav-icon{
  width:32px;height:32px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;color:var(--text-dim);
  background:rgba(255,255,255,.04);
  flex-shrink:0;transition:color .18s;
}
.nav-item.active .nav-icon{
  background:rgba(14,165,233,.15);
  color:var(--accent);
}
.nav-item.danger{color:#f87171}
.nav-item.danger .nav-icon{color:#f87171;background:rgba(248,113,113,.1)}
.nav-item.danger:hover{background:rgba(239,68,68,.12)}

.sidebar-footer{
  padding:12px;
  border-top:1px solid var(--border);
}

/* ═══════════════════════════════════════
   MAIN
═══════════════════════════════════════ */
.main{
  margin-right:var(--sidebar-w);
  min-height:100vh;
  position:relative;z-index:1;
}

/* ═══════════════════════════════════════
   HEADER
═══════════════════════════════════════ */
.header{
  position:sticky;top:0;z-index:50;
  height:var(--header-h);
  background:rgba(11,15,26,.85);
  backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;
  justify-content:space-between;
  padding:0 24px;
  gap:16px;
}
.header-right{display:flex;align-items:center;gap:12px}
.sidebar-toggle{
  width:36px;height:36px;border-radius:8px;border:none;
  background:rgba(255,255,255,.06);color:var(--text-muted);
  cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;
  transition:all .15s;
}
.sidebar-toggle:hover{background:var(--bg-hover);color:var(--text)}
.header-title{font-size:16px;font-weight:700;color:#fff}
.header-center{display:flex;align-items:center;gap:8px}
.header-time{font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:500;color:var(--accent2);line-height:1}
.header-date{font-size:11px;color:var(--text-muted);text-align:center;margin-top:2px}
.header-left{display:flex;align-items:center;gap:8px}
.header-search{
  display:flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.05);border:1px solid var(--border);
  border-radius:8px;padding:6px 12px;
}
.header-search i{color:var(--text-dim);font-size:13px}
.header-search input{
  background:none;border:none;outline:none;
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;
  width:160px;
}
.header-search input::placeholder{color:var(--text-dim)}
.header-btn{
  width:36px;height:36px;border-radius:8px;border:none;
  background:rgba(255,255,255,.06);color:var(--text-muted);
  cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;
  transition:all .15s;position:relative;
}
.header-btn:hover{background:var(--bg-hover);color:var(--text)}
.btn-badge{
  position:absolute;top:5px;left:5px;
  width:16px;height:16px;border-radius:50%;
  background:#ef4444;color:#fff;font-size:9px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  font-family:'Cairo',sans-serif;
}

/* ═══════════════════════════════════════
   SECTIONS
═══════════════════════════════════════ */
.section{display:none;padding:28px 28px 40px;animation:fadeIn .25s ease}
.section.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

/* ═══════════════════════════════════════
   WELCOME BAR
═══════════════════════════════════════ */
.welcome-bar{
  background:linear-gradient(135deg,#0d1f35 0%,#111a2e 100%);
  border:1px solid var(--border2);
  border-radius:var(--radius);
  padding:22px 24px;
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:24px;
  position:relative;overflow:hidden;
}
.welcome-bar::before{
  content:'';position:absolute;top:-40px;left:-40px;
  width:200px;height:200px;
  background:radial-gradient(circle,rgba(14,165,233,.1) 0%,transparent 70%);
  pointer-events:none;
}
.welcome-bar h1{font-size:22px;font-weight:800;color:#fff;line-height:1.2}
.welcome-bar h1 .wave{font-size:20px}
.welcome-bar p{font-size:13px;color:var(--text-muted);margin-top:5px}
.welcome-bar p strong{color:var(--accent2)}

/* ═══════════════════════════════════════
   STATS GRID
═══════════════════════════════════════ */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:16px;
  margin-bottom:28px;
}
.stat-card{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:20px;
  position:relative;overflow:hidden;
  transition:transform .2s,box-shadow .2s;
  cursor:default;
}
.stat-card:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 24px rgba(0,0,0,.3);
}
.stat-card::before{
  content:'';position:absolute;top:0;right:0;
  width:80px;height:80px;
  background:radial-gradient(circle, var(--card-color,#0ea5e9) 0%, transparent 70%);
  opacity:.12;pointer-events:none;
}
.stat-icon{
  width:42px;height:42px;border-radius:10px;
  background:rgba(var(--card-color-rgb,14,165,233),.15);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--card-color,#0ea5e9);
  margin-bottom:14px;
}
.stat-value{font-size:28px;font-weight:800;color:#fff;line-height:1}
.stat-label{font-size:12px;color:var(--text-muted);margin-top:4px}
.stat-sub{font-size:11px;color:var(--text-dim);margin-top:4px}

/* ═══════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════ */
.section-header{
  display:flex;align-items:flex-start;justify-content:space-between;
  margin-bottom:20px;flex-wrap:wrap;gap:12px;
}
.section-title{font-size:20px;font-weight:800;color:#fff}
.section-sub{font-size:13px;color:var(--text-muted);margin-top:3px}
.section-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

/* ═══════════════════════════════════════
   BUTTONS
═══════════════════════════════════════ */
.btn-primary{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 18px;border-radius:9px;border:none;
  background:linear-gradient(135deg,#0ea5e9,#0284c7);
  color:#fff;font-family:'Cairo',sans-serif;font-size:13px;font-weight:600;
  cursor:pointer;transition:all .18s;
  box-shadow:0 4px 14px rgba(14,165,233,.25);
}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-secondary{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 16px;border-radius:9px;
  background:rgba(255,255,255,.06);
  border:1px solid var(--border2);
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;font-weight:500;
  cursor:pointer;transition:all .18s;
}
.btn-secondary:hover{background:var(--bg-hover);border-color:var(--accent)}
.btn-icon{
  width:32px;height:32px;border-radius:8px;border:none;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:all .15s;font-size:13px;
}
.btn-edit{background:rgba(14,165,233,.12);color:var(--accent)}
.btn-edit:hover{background:rgba(14,165,233,.22)}
.btn-disable{background:rgba(239,68,68,.1);color:#f87171}
.btn-disable:hover{background:rgba(239,68,68,.2)}
.btn-view{background:rgba(16,185,129,.1);color:#34d399}
.btn-view:hover{background:rgba(16,185,129,.2)}
.btn-confirm{background:rgba(16,185,129,.1);color:#34d399;font-size:12px;padding:6px 12px;border-radius:7px;border:none;font-family:'Cairo',sans-serif;font-weight:600;cursor:pointer;transition:all .15s}
.btn-confirm:hover{background:rgba(16,185,129,.2)}
.btn-reschedule{background:rgba(245,158,11,.1);color:#fbbf24;font-size:12px;padding:6px 12px;border-radius:7px;border:none;font-family:'Cairo',sans-serif;font-weight:600;cursor:pointer;transition:all .15s}
.btn-reschedule:hover{background:rgba(245,158,11,.2)}

/* ═══════════════════════════════════════
   TABLE
═══════════════════════════════════════ */
.data-table-wrap{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  overflow:hidden;
}
.data-table{width:100%;border-collapse:collapse;font-size:13.5px}
.data-table thead tr{border-bottom:1px solid var(--border2)}
.data-table th{
  padding:13px 18px;text-align:right;
  font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;
  color:var(--text-dim);background:rgba(255,255,255,.02);
}
.data-table td{
  padding:13px 18px;border-bottom:1px solid var(--border);
  color:var(--text);vertical-align:middle;
}
.data-table tr:last-child td{border-bottom:none}
.data-table tbody tr{transition:background .15s}
.data-table tbody tr:hover{background:var(--bg-hover)}
.td-actions{display:flex;align-items:center;gap:6px}

/* Badges */
.badge{
  display:inline-flex;align-items:center;
  padding:3px 10px;border-radius:20px;
  font-size:11px;font-weight:600;
}
.badge-active{background:rgba(16,185,129,.12);color:#34d399}
.badge-inactive{background:rgba(239,68,68,.1);color:#f87171}
.badge-pending{background:rgba(245,158,11,.12);color:#fbbf24}
.badge-confirmed{background:rgba(16,185,129,.12);color:#34d399}
.badge-cancelled{background:rgba(239,68,68,.1);color:#f87171}
.role-badge{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;
  font-size:11.5px;font-weight:600;
  background:rgba(99,102,241,.12);color:#a5b4fc;
}

/* ═══════════════════════════════════════
   SEARCH BAR
═══════════════════════════════════════ */
.search-bar{
  display:flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.05);border:1px solid var(--border);
  border-radius:8px;padding:7px 12px;
}
.search-bar i{color:var(--text-dim);font-size:13px;flex-shrink:0}
.search-bar input{
  background:none;border:none;outline:none;
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;
  width:200px;
}
.search-bar input::placeholder{color:var(--text-dim)}

/* ═══════════════════════════════════════
   TEAM CARDS (فريق العمل)
═══════════════════════════════════════ */
.team-summary{
  display:grid;grid-template-columns:repeat(3,1fr);gap:16px;
  margin-bottom:24px;
}
.team-sum-card{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);padding:20px 22px;
  display:flex;align-items:center;gap:14px;
}
.team-sum-icon{
  width:46px;height:46px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;flex-shrink:0;
}
.team-sum-val{font-size:26px;font-weight:800;color:#fff;line-height:1}
.team-sum-lbl{font-size:12px;color:var(--text-muted);margin-top:2px}

/* ═══════════════════════════════════════
   ACTIVITY LOG
═══════════════════════════════════════ */
.activity-list{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);overflow:hidden;
}
.activity-item{
  display:flex;align-items:flex-start;gap:14px;
  padding:16px 20px;border-bottom:1px solid var(--border);
  transition:background .15s;
}
.activity-item:last-child{border-bottom:none}
.activity-item:hover{background:var(--bg-hover)}
.act-icon{
  width:36px;height:36px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:14px;
  margin-top:2px;
}
.act-body{flex:1}
.act-title{font-size:13.5px;font-weight:600;color:var(--text)}
.act-sub{font-size:12px;color:var(--text-muted);margin-top:2px}
.act-time{font-size:11px;color:var(--text-dim);margin-top:4px;display:flex;align-items:center;gap:4px}

/* ═══════════════════════════════════════
   SETTINGS
═══════════════════════════════════════ */
.settings-layout{
  display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;
}
.settings-profile-card{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);padding:24px 20px;
  text-align:center;
}
.settings-avatar-big{
  position:relative;width:72px;height:72px;
  margin:0 auto 14px;
}
.settings-avatar-big span{
  width:72px;height:72px;border-radius:50%;
  background:linear-gradient(135deg,#0ea5e9,#6366f1);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;font-weight:800;color:#fff;
}
.settings-profile-card h3{font-size:15px;font-weight:700;color:#fff}
.settings-profile-card p{font-size:12px;color:var(--text-muted);margin:4px 0 14px}
.settings-groups{display:flex;flex-direction:column;gap:16px}
.settings-group{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);padding:22px;
}
.settings-group-title{
  font-size:13px;font-weight:700;color:var(--accent2);
  margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:8px;
}
.settings-field-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:14px;
  margin-bottom:16px;
}
.settings-field{display:flex;flex-direction:column;gap:6px}
.settings-field label{font-size:12px;font-weight:600;color:var(--text-muted)}
.settings-field input,.settings-field textarea{
  padding:9px 12px;border-radius:8px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--border2);
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;
  outline:none;transition:border-color .15s;
}
.settings-field input:focus,.settings-field textarea:focus{border-color:var(--accent)}
.settings-field textarea{resize:vertical;min-height:70px}
.settings-field.full{grid-column:1/-1}
.logo-upload{
  display:flex;align-items:center;gap:12px;
  padding:14px;background:rgba(255,255,255,.03);
  border:2px dashed var(--border2);border-radius:10px;
  cursor:pointer;transition:border-color .15s;
}
.logo-upload:hover{border-color:var(--accent)}
.logo-upload i{font-size:24px;color:var(--text-dim)}
.logo-upload span{font-size:13px;color:var(--text-muted)}

/* ═══════════════════════════════════════
   MODAL
═══════════════════════════════════════ */
.modal-overlay{
  display:none;position:fixed;inset:0;z-index:200;
  background:rgba(0,0,0,.7);backdrop-filter:blur(4px);
  align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--bg-card2);border:1px solid var(--border2);
  border-radius:var(--radius);padding:28px;
  width:100%;max-width:500px;
  position:relative;animation:fadeIn .2s ease;
}
.modal-title{
  font-size:17px;font-weight:800;color:#fff;
  margin-bottom:20px;padding-bottom:14px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;
}
.modal-title i{color:var(--accent)}
.modal-close{
  position:absolute;top:16px;left:16px;
  width:30px;height:30px;border-radius:7px;border:none;
  background:rgba(255,255,255,.06);color:var(--text-muted);
  cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;
  transition:all .15s;
}
.modal-close:hover{background:rgba(239,68,68,.15);color:#f87171}
.modal-field{margin-bottom:14px}
.modal-field label{display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px}
.modal-field input,.modal-field select,.modal-field textarea{
  width:100%;padding:9px 12px;border-radius:8px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--border2);
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;
  outline:none;transition:border-color .15s;
}
.modal-field input:focus,.modal-field select:focus,.modal-field textarea:focus{border-color:var(--accent)}
.modal-field select option{background:#1a2540;color:var(--text)}
.modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px}

/* ═══════════════════════════════════════
   TOAST
═══════════════════════════════════════ */
.toast{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
  background:#1a2540;border:1px solid var(--border2);
  border-radius:10px;padding:12px 22px;
  font-size:13.5px;color:#fff;font-weight:600;
  opacity:0;pointer-events:none;
  transition:all .3s ease;z-index:999;
  box-shadow:0 8px 24px rgba(0,0,0,.4);
  white-space:nowrap;
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ═══════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════ */
.empty-state{
  text-align:center;padding:60px 20px;
  color:var(--text-dim);
}
.empty-state i{font-size:36px;margin-bottom:12px;display:block;color:var(--border2)}
.empty-state p{font-size:14px}

/* ═══════════════════════════════════════
   SCROLLBAR
═══════════════════════════════════════ */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

/* ═══════════════════════════════════════
   MODAL — SCROLL INSIDE (محتوى قابل للتمرير)
═══════════════════════════════════════ */
.modal-overlay{
  padding:16px;
  overflow-y:auto;
}
.modal{
  max-height:calc(100vh - 32px);
  display:flex;
  flex-direction:column;
  overflow:hidden;
}
.modal-body-scroll{
  flex:1;
  overflow-y:auto;
  padding:0 2px 4px;
  scrollbar-width:thin;
  scrollbar-color:rgba(14,165,233,.3) transparent;
}
.modal-body-scroll::-webkit-scrollbar{width:4px}
.modal-body-scroll::-webkit-scrollbar-thumb{background:rgba(14,165,233,.3);border-radius:99px}
/* Modal with sticky footer */
.modal-sticky-footer{
  flex-shrink:0;
  border-top:1px solid var(--border);
  padding:14px 0 0;
  margin-top:8px;
}

/* ═══════════════════════════════════════
   EMPTY STATES — حالات الفراغ المحسّنة
═══════════════════════════════════════ */
.empty-state-pro{
  display:flex;flex-direction:column;align-items:center;
  justify-content:center;padding:64px 24px;gap:0;
  text-align:center;
}
.empty-state-pro-ring{
  width:88px;height:88px;border-radius:50%;
  border:2px dashed var(--border2);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:20px;
  background:rgba(255,255,255,.02);
  position:relative;
}
.empty-state-pro-ring::before{
  content:'';position:absolute;inset:-8px;border-radius:50%;
  border:1px solid var(--border);
  opacity:.5;
}
.empty-state-pro-icon{
  font-size:32px;color:var(--text-dim);
}
.empty-state-pro-title{
  font-size:17px;font-weight:800;color:var(--text);
  margin-bottom:8px;
}
.empty-state-pro-sub{
  font-size:13px;color:var(--text-muted);
  margin-bottom:22px;line-height:1.6;
  max-width:340px;
}
body.light .empty-state-pro-ring{background:rgba(0,0,0,.02)}
body.light .empty-state-pro-title{color:#1e293b}
body.light .empty-state-pro-sub{color:#64748b}

/* ═══════════════════════════════════════
   PAGINATION — ترقيم الصفحات الاحترافي
═══════════════════════════════════════ */
.pagination-wrap{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 18px;
  border-top:1px solid var(--border);
  gap:12px;flex-wrap:wrap;
}
.pagination-info{
  font-size:12.5px;color:var(--text-muted);
  flex-shrink:0;
}
.pagination-controls{
  display:flex;align-items:center;gap:4px;
}
.page-btn{
  min-width:34px;height:34px;border-radius:8px;border:1px solid var(--border2);
  background:var(--bg-card2);color:var(--text-muted);
  font-family:'Cairo',sans-serif;font-size:13px;font-weight:600;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:all .15s;padding:0 10px;
}
.page-btn:hover:not(:disabled){background:var(--bg-hover);border-color:var(--accent);color:var(--text)}
.page-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(14,165,233,.3)}
.page-btn:disabled{opacity:.4;cursor:not-allowed}
.page-btn-ellipsis{
  min-width:34px;height:34px;display:flex;align-items:center;justify-content:center;
  color:var(--text-dim);font-size:13px;
}
.pagination-per-page{
  display:flex;align-items:center;gap:8px;flex-shrink:0;
}
.pagination-per-page label{font-size:12px;color:var(--text-muted)}
.pagination-per-page select{
  padding:5px 10px;border-radius:7px;border:1px solid var(--border2);
  background:var(--bg-card2);color:var(--text);
  font-family:'Cairo',sans-serif;font-size:12.5px;
  outline:none;cursor:pointer;
}
.pagination-per-page select:focus{border-color:var(--accent)}
body.light .page-btn{background:#fff;border-color:#d1dce8;color:#64748b}
body.light .page-btn:hover:not(:disabled){background:#e8f0fa;border-color:#0284c7;color:#1e293b}
body.light .page-btn.active{background:#0284c7;border-color:#0284c7;color:#fff}
body.light .pagination-info{color:#64748b}
body.light .pagination-per-page select{background:#fff;border-color:#d1dce8;color:#1e293b}

/* ═══════════════════════════════════════
   RESPONSIVE — استجابة الشاشات
═══════════════════════════════════════ */

/* Mobile overlay backdrop */
.sidebar-backdrop{
  display:none;position:fixed;inset:0;z-index:99;
  background:rgba(0,0,0,.55);backdrop-filter:blur(2px);
}
.sidebar-backdrop.visible{display:block}

/* Tablet: 768px - 1100px */
@media(max-width:1100px){
  :root{--sidebar-w:240px}
  .dash-two-cols{grid-template-columns:1fr}
  .settings-layout{grid-template-columns:1fr}
  .settings-profile-card{display:flex;align-items:center;gap:16px;text-align:right;padding:18px 20px}
  .settings-avatar-big{margin:0}
}

/* Mobile: ≤768px */
@media(max-width:768px){
  :root{--sidebar-w:280px}

  /* Sidebar becomes off-canvas */
  .sidebar{
    transform:translateX(100%);
    box-shadow:none;
    z-index:200;
  }
  .sidebar.mobile-open{
    transform:translateX(0);
    box-shadow:-8px 0 32px rgba(0,0,0,.4);
  }
  /* Remove collapse behavior on mobile — use mobile-open instead */
  .sidebar.collapsed{width:var(--sidebar-w);overflow:visible}
  .sidebar.collapsed ~ .main{margin-right:0}

  .main{margin-right:0 !important}
  .section{padding:18px 16px 32px}

  /* Header */
  .header{padding:0 14px;gap:8px}
  .header-search{display:none}
  .header-time{font-size:15px}
  .header-date{font-size:10px}

  /* Welcome bar */
  .welcome-bar{flex-direction:column;align-items:flex-start;gap:12px;padding:18px 16px}

  /* Stats grid */
  .stats-grid{grid-template-columns:repeat(2,1fr);gap:12px}
  .today-stats-grid{grid-template-columns:repeat(2,1fr);gap:12px}

  /* Two columns → one column */
  .dash-two-cols{grid-template-columns:1fr;gap:16px}

  /* Team summary */
  .team-summary{grid-template-columns:1fr}

  /* Settings */
  .settings-layout{grid-template-columns:1fr}
  .settings-profile-card{flex-direction:row;align-items:center;gap:14px;text-align:right;padding:16px 18px}
  .settings-avatar-big{margin:0}
  .settings-field-grid{grid-template-columns:1fr}

  /* Tables → horizontal scroll */
  .data-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  .data-table{min-width:560px}
  .users-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  .users-table{min-width:600px}
  .services-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  .services-table{min-width:520px}

  /* Pagination wraps on small screens */
  .pagination-wrap{flex-direction:column;align-items:flex-start;gap:10px}
  .pagination-controls{flex-wrap:wrap}
  .pagination-per-page{width:100%;justify-content:space-between}

  /* Modals */
  .modal{padding:20px 16px;margin:0}
  .modal-grid{grid-template-columns:1fr !important}
  .modal-grid .modal-field[style*="grid-column:span 2"]{grid-column:span 1 !important}
  .modal-overlay{padding:12px}

  /* Room totals bar */
  .room-totals-bar{flex-direction:column;gap:14px;padding:16px}
  .room-total-divider{width:100%;height:1px;margin:0}

  /* Users page */
  .users-page-header{flex-direction:column;align-items:flex-start;gap:10px}
  .users-toolbar{flex-direction:column;align-items:stretch}
  .users-search-wrap{min-width:0}
  .users-filter-select{min-width:0;width:100%}
  .uact-btn span{display:none}
  .uact-btn{padding:7px 9px}

  /* Services */
  .services-page-header{flex-direction:column;align-items:flex-start;gap:12px}
  .services-table th:nth-child(3),
  .services-table td:nth-child(3){display:none}
  .sact-btn span{display:none}
  .sact-btn{padding:7px 9px}

  /* Section header */
  .section-header{flex-direction:column;align-items:flex-start}
  .section-actions{width:100%}

  /* View modals grid */
  .view-user-modal-grid{grid-template-columns:1fr}
  .vsm-stats{grid-template-columns:1fr 1fr}
}

/* Extra small: ≤480px */
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .today-stats-grid{grid-template-columns:1fr}
  .create-account-btns{grid-template-columns:repeat(2,1fr)}
  .services-table th:nth-child(2),
  .services-table td:nth-child(2){display:none}
  .header-center{display:none}
  .vsm-stats{grid-template-columns:1fr}
  .room-wing-block .modal-grid{grid-template-columns:1fr}
}

/* ═══════════════════════════════════════
   PRINT CSS — أنماط الطباعة
═══════════════════════════════════════ */
@media print{
  /* إخفاء عناصر التنقل والتحكم */
  .sidebar,
  .sidebar-backdrop,
  .header,
  .bg-mesh,
  .section-actions,
  .btn-primary,
  .btn-secondary,
  .btn-icon,
  .uact-btn,
  .sact-btn,
  .btn-add-service,
  .create-account-section,
  .users-toolbar,
  .pagination-wrap,
  .modal-overlay,
  .toast,
  #themeToggleBtn{
    display:none !important;
  }

  /* تعديلات عامة */
  *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  body{
    background:#fff !important;
    color:#1e293b !important;
    font-size:12px;
  }
  .main{margin-right:0 !important;padding:0}
  .section{display:block !important;padding:0}
  .section:not(.active){display:none !important}

  /* ترويسة الطباعة */
  .print-header{
    display:flex !important;
    align-items:center;justify-content:space-between;
    padding:0 0 16px;
    border-bottom:2px solid #0284c7;
    margin-bottom:20px;
  }
  .print-header-logo{font-size:20px;font-weight:800;color:#0284c7}
  .print-header-info{font-size:11px;color:#64748b;text-align:left}

  /* الجداول */
  .data-table-wrap,
  .users-table-wrap,
  .services-table-wrap,
  .activity-list{
    border:1px solid #d1dce8 !important;
    overflow:visible !important;
    page-break-inside:avoid;
  }
  .data-table,.users-table,.services-table{
    width:100%;
    font-size:11px;
  }
  .data-table th,.users-table th,.services-table th{
    background:#f0f4f8 !important;
    color:#475569 !important;
  }
  .data-table td,.users-table td,.services-table td{
    color:#1e293b !important;
    border-bottom-color:#d1dce8 !important;
  }
  .data-table tbody tr:hover,.users-table tbody tr:hover{background:transparent !important}

  /* البطاقات */
  .stat-card,.today-stat-card,.team-sum-card{
    background:#fff !important;
    border:1px solid #d1dce8 !important;
    box-shadow:none !important;
    page-break-inside:avoid;
  }
  .stat-value,.tcard-value,.stat-label{color:#1e293b !important}

  /* الشارات */
  .badge-active,.sbadge-active{color:#059669 !important}
  .badge-inactive,.sbadge-inactive{color:#dc2626 !important}

  /* قطع الصفحات */
  .stats-grid,.today-stats-grid{page-break-after:avoid}
  .activity-item{page-break-inside:avoid}

  /* تخطيط الطباعة */
  .settings-layout{grid-template-columns:1fr}
  .dash-two-cols{grid-template-columns:1fr}
}

/* عنصر ترويسة الطباعة مخفي بشكل افتراضي */
.print-header{display:none}

/* ═══════════════════════════════════════
   SERVICES PAGE — ENHANCED
═══════════════════════════════════════ */

/* Services Page Header */
.services-page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}
.services-page-header-text {
  display: flex;
  align-items: center;
  gap: 16px;
}
.services-page-icon {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, rgba(14,165,233,.2), rgba(99,102,241,.15));
  border: 1px solid rgba(14,165,233,.25);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: var(--accent);
  flex-shrink: 0;
}
body.light .services-page-icon {
  background: linear-gradient(135deg, rgba(2,132,199,.12), rgba(99,102,241,.08));
  border-color: rgba(2,132,199,.2);
}

/* Add Service Button — Professional */
.btn-add-service {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 11px 22px;
  border-radius: 11px;
  border: none;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
  font-family: 'Cairo', sans-serif;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s cubic-bezier(.34,1.56,.64,1);
  box-shadow: 0 4px 18px rgba(14,165,233,.32), 0 1px 3px rgba(0,0,0,.2);
  position: relative;
  overflow: hidden;
}
.btn-add-service::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
  opacity: 0;
  transition: opacity .2s;
}
.btn-add-service:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(14,165,233,.45), 0 2px 6px rgba(0,0,0,.2);
}
.btn-add-service:hover::before { opacity: 1; }
.btn-add-service:active { transform: translateY(0); }
.btn-add-service .btn-add-service-icon {
  width: 30px; height: 30px;
  background: rgba(255,255,255,.18);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
  transition: transform .2s ease;
}
.btn-add-service:hover .btn-add-service-icon { transform: rotate(90deg); }

/* Services Table Wrap */
.services-table-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,.15);
}
.services-table {
  width: 100%;
  border-collapse: collapse;
}
.services-table thead tr {
  background: rgba(255,255,255,.025);
  border-bottom: 1px solid var(--border);
}
body.light .services-table thead tr {
  background: rgba(0,0,0,.02);
}
.services-table th {
  padding: 13px 18px;
  text-align: right;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: .4px;
  text-transform: uppercase;
  white-space: nowrap;
}
.services-table td {
  padding: 14px 18px;
  font-size: 13.5px;
  color: var(--text);
  border-bottom: 1px solid rgba(30,45,69,.6);
  transition: background .15s;
  vertical-align: middle;
}
body.light .services-table td {
  border-bottom-color: var(--border);
}
.services-table tbody tr {
  transition: background .15s;
}
.services-table tbody tr:hover {
  background: var(--bg-hover);
}
.services-table tbody tr:last-child td {
  border-bottom: none;
}

/* Service Name Cell */
.service-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.service-icon-badge {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.service-name-text {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}

/* Service Admin Cell */
.service-admin-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.service-admin-avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(14,165,233,.25), rgba(99,102,241,.2));
  border: 1px solid rgba(14,165,233,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  color: var(--accent2);
  flex-shrink: 0;
}
.service-admin-name {
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
}

/* Workers Count Cell */
.service-workers-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  background: rgba(14,165,233,.08);
  border: 1px solid rgba(14,165,233,.15);
  border-radius: 20px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--accent2);
}
body.light .service-workers-badge {
  background: rgba(2,132,199,.06);
  border-color: rgba(2,132,199,.18);
}

/* Service Actions */
.service-actions {
  display: flex;
  align-items: center;
  gap: 7px;
}
.sact-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid transparent;
  font-family: 'Cairo', sans-serif;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all .17s ease;
  white-space: nowrap;
}
.sact-btn span { display: inline; }
.sact-view {
  background: rgba(14,165,233,.1);
  border-color: rgba(14,165,233,.2);
  color: #38bdf8;
}
.sact-view:hover {
  background: rgba(14,165,233,.2);
  border-color: rgba(14,165,233,.4);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(14,165,233,.2);
}
.sact-edit {
  background: rgba(16,185,129,.1);
  border-color: rgba(16,185,129,.2);
  color: #34d399;
}
.sact-edit:hover {
  background: rgba(16,185,129,.2);
  border-color: rgba(16,185,129,.4);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(16,185,129,.2);
}
.sact-delete {
  background: rgba(239,68,68,.08);
  border-color: rgba(239,68,68,.18);
  color: #f87171;
}
.sact-delete:hover {
  background: rgba(239,68,68,.18);
  border-color: rgba(239,68,68,.4);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(239,68,68,.2);
}
.sact-toggle-off {
  background: rgba(245,158,11,.08);
  border-color: rgba(245,158,11,.18);
  color: #fbbf24;
}
.sact-toggle-off:hover {
  background: rgba(245,158,11,.18);
  border-color: rgba(245,158,11,.4);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(245,158,11,.2);
}
.sact-toggle-on {
  background: rgba(16,185,129,.08);
  border-color: rgba(16,185,129,.18);
  color: #34d399;
}
.sact-toggle-on:hover {
  background: rgba(16,185,129,.18);
  border-color: rgba(16,185,129,.4);
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(16,185,129,.2);
}

/* View Service Modal */
.view-service-header {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 18px;
  background: var(--bg-card2);
  border-radius: 12px;
  border: 1px solid var(--border);
  margin-bottom: 14px;
}
.view-service-header-icon {
  width: 56px; height: 56px;
  border-radius: 15px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.view-service-name {
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
}
.view-service-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 3px;
}
.view-service-fields {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.view-service-field {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-card2);
  border-radius: 10px;
  border: 1px solid var(--border);
}
.view-service-field-label {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 7px;
}
.view-service-field-label i { color: var(--accent); font-size: 13px; }
.view-service-field-val {
  font-size: 13.5px;
  color: var(--text);
  font-weight: 600;
}

/* Services Add Modal Enhanced */
.add-service-modal-icon-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: linear-gradient(135deg, rgba(14,165,233,.08), rgba(99,102,241,.06));
  border: 1px solid rgba(14,165,233,.15);
  border-radius: 12px;
  margin-bottom: 20px;
}
.add-service-modal-icon {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, rgba(14,165,233,.2), rgba(99,102,241,.15));
  border: 1px solid rgba(14,165,233,.25);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: var(--accent);
  flex-shrink: 0;
}
.add-service-modal-title { font-size: 14px; font-weight: 800; color: var(--text); }
.add-service-modal-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

/* Responsive services */
@media (max-width: 768px) {
  .services-table th:nth-child(3),
  .services-table td:nth-child(3) { display: none; }
  .sact-btn span { display: none; }
  .sact-btn { padding: 7px 9px; }
  .service-actions { gap: 5px; }
}
@media (max-width: 520px) {
  .services-table th:nth-child(2),
  .services-table td:nth-child(2) { display: none; }
}

/* ═══════════════════════════════════════
   CONFIRM DELETE MODAL — نافذة تأكيد الحذف
═══════════════════════════════════════ */
.confirm-delete-modal {
  max-width: 440px;
}
.confirm-delete-icon-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}
.confirm-delete-icon {
  width: 68px; height: 68px;
  border-radius: 50%;
  background: rgba(239,68,68,.1);
  border: 2px solid rgba(239,68,68,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; color: #f87171;
  animation: confirmShake .4s ease .1s both;
}
@keyframes confirmShake {
  0%,100% { transform: rotate(0); }
  20% { transform: rotate(-8deg) scale(1.08); }
  40% { transform: rotate(8deg) scale(1.08); }
  60% { transform: rotate(-5deg); }
  80% { transform: rotate(5deg); }
}
.confirm-delete-title {
  font-size: 18px; font-weight: 800;
  color: var(--text);
  text-align: center;
  margin-bottom: 10px;
}
.confirm-delete-msg {
  font-size: 13.5px; color: var(--text-muted);
  text-align: center;
  line-height: 1.7;
  margin-bottom: 6px;
}
.confirm-delete-service-name {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(239,68,68,.06);
  border: 1px solid rgba(239,68,68,.15);
  border-radius: 10px;
  margin: 14px 0 20px;
}
.confirm-delete-service-icon {
  width: 36px; height: 36px;
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}
.confirm-delete-service-label {
  font-size: 14px; font-weight: 700;
  color: var(--text);
}
.confirm-delete-warning {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  background: rgba(245,158,11,.06);
  border: 1px solid rgba(245,158,11,.2);
  border-radius: 10px;
  margin-bottom: 20px;
}
.confirm-delete-warning i {
  color: #fbbf24;
  font-size: 14px;
  margin-top: 1px;
  flex-shrink: 0;
}
.confirm-delete-warning span {
  font-size: 12.5px;
  color: var(--text-muted);
  line-height: 1.6;
}
.confirm-delete-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.btn-confirm-delete {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; border-radius: 9px; border: none;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff; font-family: 'Cairo', sans-serif;
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: all .18s;
  box-shadow: 0 4px 14px rgba(220,38,38,.3);
}
.btn-confirm-delete:hover {
  filter: brightness(1.1); transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(220,38,38,.4);
}
body.light .confirm-delete-icon {
  background: rgba(239,68,68,.08);
  border-color: rgba(239,68,68,.2);
}
body.light .confirm-delete-service-name {
  background: rgba(239,68,68,.04);
}
body.light .confirm-delete-warning {
  background: rgba(245,158,11,.04);
}

/* ═══════════════════════════════════════
   VIEW SERVICE MODAL — نافذة عرض المصلحة (Enhanced)
═══════════════════════════════════════ */
.vsm-hero {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 16px;
  border: 1px solid var(--border);
}
.vsm-hero-bg {
  position: absolute; inset: 0;
  opacity: .06;
  pointer-events: none;
}
.vsm-hero-content {
  position: relative;
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 20px;
}
.vsm-hero-icon {
  width: 62px; height: 62px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
  flex-shrink: 0;
  box-shadow: 0 4px 16px rgba(0,0,0,.2);
  position: relative; z-index: 1;
}
.vsm-hero-text { position: relative; z-index: 1; }
.vsm-hero-name {
  font-size: 20px; font-weight: 800;
  color: var(--text);
  line-height: 1.2;
}
.vsm-hero-id {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px; color: var(--accent2);
  margin-top: 4px; display: block;
}
.vsm-status-dot {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px;
  background: rgba(16,185,129,.12);
  border: 1px solid rgba(16,185,129,.25);
  border-radius: 20px;
  font-size: 11.5px; font-weight: 700; color: #34d399;
  margin-top: 6px;
}
.vsm-status-dot::before {
  content: ''; width: 7px; height: 7px;
  border-radius: 50%; background: #34d399;
  animation: vsmPulse 1.8s ease-in-out infinite;
}
@keyframes vsmPulse {
  0%,100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(0.7); }
}

/* Stats row */
.vsm-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 16px;
}
.vsm-stat {
  padding: 14px 16px;
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 12px;
  display: flex; flex-direction: column; gap: 4px;
  transition: border-color .2s;
}
.vsm-stat:hover { border-color: var(--accent); }
.vsm-stat-label {
  font-size: 11px; font-weight: 600;
  color: var(--text-muted);
  display: flex; align-items: center; gap: 6px;
  text-transform: uppercase; letter-spacing: .4px;
}
.vsm-stat-label i { font-size: 12px; color: var(--accent); }
.vsm-stat-value {
  font-size: 15px; font-weight: 800;
  color: var(--text);
}

/* Workers list */
.vsm-section-title {
  font-size: 12px; font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase; letter-spacing: .6px;
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 10px;
}
.vsm-section-title::after {
  content: ''; flex: 1; height: 1px;
  background: var(--border);
}
.vsm-workers-list {
  display: flex; flex-direction: column; gap: 8px;
  max-height: 200px; overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(14,165,233,.3) transparent;
  padding-left: 2px;
}
.vsm-workers-list::-webkit-scrollbar { width: 3px; }
.vsm-workers-list::-webkit-scrollbar-thumb { background: rgba(14,165,233,.3); border-radius: 999px; }
.vsm-worker-row {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px;
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 9px;
  transition: background .15s, border-color .15s;
}
.vsm-worker-row:hover { background: var(--bg-hover); border-color: var(--border2); }
.vsm-worker-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, rgba(14,165,233,.2), rgba(99,102,241,.15));
  border: 1px solid rgba(14,165,233,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; color: var(--accent2);
  flex-shrink: 0;
}
.vsm-worker-info { flex: 1; }
.vsm-worker-name {
  font-size: 13px; font-weight: 600; color: var(--text);
}
.vsm-worker-role {
  font-size: 11px; color: var(--text-muted); margin-top: 1px;
}
.vsm-no-workers {
  text-align: center; padding: 20px;
  color: var(--text-dim); font-size: 13px;
}
.vsm-no-workers i { font-size: 24px; display: block; margin-bottom: 8px; }

/* ═══════════════════════════════════════
   USERS PAGE — ENHANCED
═══════════════════════════════════════ */

/* Page Header */
.users-page-header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 16px;
}
.users-page-header-text {
  display: flex;
  align-items: center;
  gap: 16px;
}
.users-page-icon {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, rgba(14,165,233,.2), rgba(99,102,241,.15));
  border: 1px solid rgba(14,165,233,.25);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: var(--accent);
  flex-shrink: 0;
}
body.light .users-page-icon {
  background: linear-gradient(135deg, rgba(2,132,199,.12), rgba(99,102,241,.08));
  border-color: rgba(2,132,199,.2);
}

/* قسم إنشاء حساب جديد */
.create-account-section {
  background: var(--bg-card);
  border: 1px solid var(--border2);
  border-radius: var(--radius);
  padding: 22px 24px 24px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
}
.create-account-section::before {
  content: '';
  position: absolute; top: 0; right: 0; left: 0; height: 2px;
  background: linear-gradient(90deg, #0ea5e9, #6366f1, #ec4899, #10b981, #f59e0b, #6366f1);
  background-size: 300% 100%;
  animation: caGradShift 4s linear infinite;
}
@keyframes caGradShift {
  0%   { background-position: 0% 0%; }
  100% { background-position: 300% 0%; }
}
.create-account-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}
.create-account-icon {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, rgba(14,165,233,.2), rgba(99,102,241,.15));
  border: 1px solid rgba(14,165,233,.25);
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 17px; color: var(--accent);
  flex-shrink: 0;
}
.create-account-title {
  font-size: 15px; font-weight: 800; color: var(--text);
}
.create-account-sub {
  font-size: 12px; color: var(--text-muted); margin-top: 2px;
}
.create-account-btns {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
}
/* CAB = Create Account Button */
.cab {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 18px 10px 16px;
  border-radius: 14px;
  border: 1px solid transparent;
  cursor: pointer;
  font-family: 'Cairo', sans-serif;
  overflow: hidden;
  transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;
  background: var(--bg-card2);
}
.cab:hover {
  transform: translateY(-4px);
}
.cab-icon {
  width: 46px; height: 46px;
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px;
  position: relative; z-index: 1;
  transition: transform .2s ease;
}
.cab:hover .cab-icon { transform: scale(1.1); }
.cab-label {
  font-size: 12px; font-weight: 700;
  position: relative; z-index: 1;
  text-align: center;
  line-height: 1.3;
}
.cab-glow {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity .25s ease;
  border-radius: inherit;
}
.cab:hover .cab-glow { opacity: 1; }

/* Doctor — Sky Blue */
.cab-doctor {
  border-color: rgba(14,165,233,.25);
  color: #38bdf8;
}
.cab-doctor .cab-icon {
  background: rgba(14,165,233,.15);
  color: #38bdf8;
  box-shadow: 0 4px 14px rgba(14,165,233,.2);
}
.cab-doctor:hover {
  box-shadow: 0 8px 28px rgba(14,165,233,.25);
  border-color: rgba(14,165,233,.5);
}
.cab-doctor .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(14,165,233,.12) 0%, transparent 70%);
}

/* Nurse — Pink */
.cab-nurse {
  border-color: rgba(236,72,153,.22);
  color: #f472b6;
}
.cab-nurse .cab-icon {
  background: rgba(236,72,153,.14);
  color: #f472b6;
  box-shadow: 0 4px 14px rgba(236,72,153,.18);
}
.cab-nurse:hover {
  box-shadow: 0 8px 28px rgba(236,72,153,.22);
  border-color: rgba(236,72,153,.48);
}
.cab-nurse .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(236,72,153,.1) 0%, transparent 70%);
}

/* Lab — Amber */
.cab-lab {
  border-color: rgba(245,158,11,.22);
  color: #fbbf24;
}
.cab-lab .cab-icon {
  background: rgba(245,158,11,.14);
  color: #fbbf24;
  box-shadow: 0 4px 14px rgba(245,158,11,.18);
}
.cab-lab:hover {
  box-shadow: 0 8px 28px rgba(245,158,11,.22);
  border-color: rgba(245,158,11,.48);
}
.cab-lab .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(245,158,11,.1) 0%, transparent 70%);
}

/* Xray — Emerald */
.cab-xray {
  border-color: rgba(16,185,129,.22);
  color: #34d399;
}
.cab-xray .cab-icon {
  background: rgba(16,185,129,.14);
  color: #34d399;
  box-shadow: 0 4px 14px rgba(16,185,129,.18);
}
.cab-xray:hover {
  box-shadow: 0 8px 28px rgba(16,185,129,.22);
  border-color: rgba(16,185,129,.48);
}
.cab-xray .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(16,185,129,.1) 0%, transparent 70%);
}

/* Pharmacy — Violet */
.cab-pharmacy {
  border-color: rgba(167,139,250,.22);
  color: #c4b5fd;
}
.cab-pharmacy .cab-icon {
  background: rgba(167,139,250,.14);
  color: #c4b5fd;
  box-shadow: 0 4px 14px rgba(167,139,250,.18);
}
.cab-pharmacy:hover {
  box-shadow: 0 8px 28px rgba(167,139,250,.22);
  border-color: rgba(167,139,250,.48);
}
.cab-pharmacy .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(167,139,250,.1) 0%, transparent 70%);
}

/* Service Admin — Indigo */
.cab-svcadmin {
  border-color: rgba(99,102,241,.22);
  color: #a5b4fc;
}
.cab-svcadmin .cab-icon {
  background: rgba(99,102,241,.14);
  color: #a5b4fc;
  box-shadow: 0 4px 14px rgba(99,102,241,.18);
}
.cab-svcadmin:hover {
  box-shadow: 0 8px 28px rgba(99,102,241,.22);
  border-color: rgba(99,102,241,.48);
}
.cab-svcadmin .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(99,102,241,.1) 0%, transparent 70%);
}

/* Light mode overrides */
body.light .create-account-section {
  background: #fff;
  border-color: #d1dce8;
}
body.light .create-account-icon {
  background: rgba(2,132,199,.1);
  border-color: rgba(2,132,199,.2);
}
body.light .create-account-title { color: #1e293b; }
body.light .create-account-sub { color: #64748b; }
body.light .cab { background: #f8fafc; }
body.light .cab-doctor { border-color: rgba(2,132,199,.25); color: #0284c7; }
body.light .cab-doctor .cab-icon { background: rgba(2,132,199,.1); color: #0284c7; }
body.light .cab-nurse { border-color: rgba(219,39,119,.2); color: #db2777; }
body.light .cab-nurse .cab-icon { background: rgba(219,39,119,.08); color: #db2777; }
body.light .cab-lab { border-color: rgba(217,119,6,.2); color: #d97706; }
body.light .cab-lab .cab-icon { background: rgba(217,119,6,.08); color: #d97706; }
body.light .cab-xray { border-color: rgba(5,150,105,.2); color: #059669; }
body.light .cab-xray .cab-icon { background: rgba(5,150,105,.08); color: #059669; }
body.light .cab-pharmacy { border-color: rgba(124,58,237,.2); color: #7c3aed; }
body.light .cab-pharmacy .cab-icon { background: rgba(124,58,237,.08); color: #7c3aed; }
body.light .cab-svcadmin { border-color: rgba(79,70,229,.2); color: #4f46e5; }
body.light .cab-svcadmin .cab-icon { background: rgba(79,70,229,.08); color: #4f46e5; }
/* Reception Staff — Teal/Cyan */
.cab-reception {
  border-color: rgba(20,184,166,.22);
  color: #2dd4bf;
}
.cab-reception .cab-icon {
  background: rgba(20,184,166,.15);
  color: #2dd4bf;
  box-shadow: 0 4px 14px rgba(20,184,166,.2);
}
.cab-reception:hover {
  box-shadow: 0 8px 28px rgba(20,184,166,.24);
  border-color: rgba(20,184,166,.5);
}
.cab-reception .cab-glow {
  background: radial-gradient(ellipse at 50% 120%, rgba(20,184,166,.12) 0%, transparent 70%);
}
body.light .cab-reception { border-color: rgba(13,148,136,.22); color: #0d9488; }
body.light .cab-reception .cab-icon { background: rgba(13,148,136,.1); color: #0d9488; }

/* Responsive grid */
@media(max-width:1200px) { .create-account-btns { grid-template-columns: repeat(4, 1fr); } }
@media(max-width:800px)  { .create-account-btns { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:600px)  { .create-account-btns { grid-template-columns: repeat(2, 1fr); } }

/* Toolbar */
.users-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.users-search-wrap {
  position: relative;
  flex: 1;
  min-width: 220px;
}
.users-search-icon {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
  font-size: 14px;
  pointer-events: none;
}
.users-search-input {
  width: 100%;
  padding: 10px 42px 10px 40px;
  background: var(--bg-card);
  border: 1px solid var(--border2);
  border-radius: 10px;
  color: var(--text);
  font-family: 'Cairo', sans-serif;
  font-size: 13.5px;
  outline: none;
  transition: border-color .18s, box-shadow .18s;
}
.users-search-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(14,165,233,.1);
}
.users-search-input::placeholder { color: var(--text-dim); }
.users-search-clear {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 26px; height: 26px;
  border-radius: 6px;
  border: none;
  background: rgba(255,255,255,.07);
  color: var(--text-muted);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  transition: background .15s, color .15s;
}
.users-search-clear:hover { background: rgba(239,68,68,.15); color: #f87171; }
.users-filter-wrap {
  position: relative;
  flex-shrink: 0;
}
.users-filter-icon {
  position: absolute;
  right: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
  font-size: 13px;
  pointer-events: none;
}
.users-filter-select {
  padding: 10px 38px 10px 16px;
  background: var(--bg-card);
  border: 1px solid var(--border2);
  border-radius: 10px;
  color: var(--text);
  font-family: 'Cairo', sans-serif;
  font-size: 13px;
  outline: none;
  cursor: pointer;
  transition: border-color .18s;
  appearance: none;
  -webkit-appearance: none;
  min-width: 170px;
}
.users-filter-select:focus { border-color: var(--accent); }
.users-filter-select option { background: #1a2540; color: var(--text); }
body.light .users-filter-select option { background: #fff; }
.users-results-count {
  font-size: 12px;
  color: var(--text-muted);
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 8px 14px;
  white-space: nowrap;
  flex-shrink: 0;
}
body.light .users-search-input,
body.light .users-filter-select {
  background: #fff;
  border-color: #c0cedf;
  color: #1e293b;
}
body.light .users-search-input:focus,
body.light .users-filter-select:focus {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2,132,199,.1);
}
body.light .users-results-count {
  background: #fff;
  border-color: #d1dce8;
  color: #64748b;
}

/* Table */
.users-table-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}
.users-table thead tr {
  border-bottom: 2px solid var(--border2);
  background: rgba(14,165,233,.04);
}
.users-table th {
  padding: 14px 18px;
  text-align: right;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .6px;
  text-transform: uppercase;
  color: var(--text-muted);
  white-space: nowrap;
}
.users-table .col-num { width: 48px; text-align: center; color: var(--text-dim); }
.users-table .col-actions { width: 220px; }
.users-table td {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  color: var(--text);
  vertical-align: middle;
}
.users-table tr:last-child td { border-bottom: none; }
.users-table tbody tr {
  transition: background .15s;
  animation: rowFadeIn .25s ease both;
}
.users-table tbody tr:hover { background: var(--bg-hover); }
@keyframes rowFadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: none; }
}
body.light .users-table thead tr { background: rgba(2,132,199,.04); }
body.light .users-table th { color: #64748b; }
body.light .users-table td { color: #1e293b; border-bottom-color: #d1dce8; }
body.light .users-table tbody tr:hover { background: #e8f0fa; }

/* User Cell */
.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.user-avatar.inactive-avatar {
  background: linear-gradient(135deg, #334155, #475569);
  opacity: .7;
}
.user-name { font-weight: 700; font-size: 14px; color: var(--text); }
.user-dept-small { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

/* Role Badges — Enhanced */
.rbadge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 700;
  white-space: nowrap;
}
.rbadge-doctor   { background: rgba(14,165,233,.14); color: #38bdf8; border: 1px solid rgba(14,165,233,.2); }
.rbadge-nurse    { background: rgba(236,72,153,.13); color: #f472b6; border: 1px solid rgba(236,72,153,.2); }
.rbadge-lab      { background: rgba(245,158,11,.13); color: #fbbf24; border: 1px solid rgba(245,158,11,.2); }
.rbadge-xray     { background: rgba(16,185,129,.13); color: #34d399; border: 1px solid rgba(16,185,129,.2); }
.rbadge-pharmacy { background: rgba(167,139,250,.14); color: #c4b5fd; border: 1px solid rgba(167,139,250,.2); }
.rbadge-admin    { background: rgba(99,102,241,.14); color: #a5b4fc; border: 1px solid rgba(99,102,241,.2); }
.rbadge-staff    { background: rgba(100,116,139,.13); color: #94a3b8; border: 1px solid rgba(100,116,139,.2); }
.rbadge-manager  { background: rgba(251,191,36,.13); color: #fbbf24; border: 1px solid rgba(251,191,36,.2); }
.rbadge-reception { background: rgba(20,184,166,.13); color: #2dd4bf; border: 1px solid rgba(20,184,166,.2); }

/* Status Badges — Enhanced */
.sbadge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 700;
}
.sbadge-active {
  background: rgba(16,185,129,.13); color: #34d399;
  border: 1px solid rgba(16,185,129,.25);
}
.sbadge-inactive {
  background: rgba(100,116,139,.12); color: #94a3b8;
  border: 1px solid rgba(100,116,139,.2);
}
.sbadge-dot {
  width: 7px; height: 7px; border-radius: 50%;
  flex-shrink: 0;
}
.sbadge-active .sbadge-dot {
  background: #34d399;
  box-shadow: 0 0 6px rgba(52,211,153,.6);
  animation: dotPulse 2s ease infinite;
}
.sbadge-inactive .sbadge-dot { background: #64748b; }
@keyframes dotPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: .6; transform: scale(1.3); }
}

/* Action Buttons — Enhanced */
.user-actions { display: flex; align-items: center; gap: 6px; }
.uact-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 11px; border-radius: 8px;
  border: none; cursor: pointer;
  font-family: 'Cairo', sans-serif;
  font-size: 12px; font-weight: 600;
  transition: all .15s;
  white-space: nowrap;
}
.uact-view   { background: rgba(14,165,233,.12); color: #38bdf8; }
.uact-view:hover  { background: rgba(14,165,233,.24); transform: translateY(-1px); }
.uact-edit   { background: rgba(16,185,129,.12); color: #34d399; }
.uact-edit:hover  { background: rgba(16,185,129,.24); transform: translateY(-1px); }
.uact-toggle { background: rgba(245,158,11,.11); color: #fbbf24; }
.uact-toggle:hover { background: rgba(245,158,11,.22); transform: translateY(-1px); }
.uact-toggle.is-inactive { background: rgba(16,185,129,.1); color: #34d399; }
.uact-toggle.is-inactive:hover { background: rgba(16,185,129,.22); }
.uact-delete { background: rgba(239,68,68,.1); color: #f87171; }
.uact-delete:hover { background: rgba(239,68,68,.2); transform: translateY(-1px); }

/* Empty State */
.users-empty-state {
  text-align: center;
  padding: 60px 24px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
}
.users-empty-icon {
  width: 70px; height: 70px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
  border: 2px dashed var(--border2);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; color: var(--text-dim);
  margin-bottom: 6px;
}
.users-empty-title { font-size: 16px; font-weight: 700; color: var(--text); }
.users-empty-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }

/* View User Modal — Styled */
.view-user-modal-avatar {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; font-weight: 800; color: #fff;
  box-shadow: 0 6px 20px rgba(14,165,233,.3);
  flex-shrink: 0;
}
.view-user-modal-header {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 18px;
  background: var(--bg-card2);
  border-radius: 12px;
  border: 1px solid var(--border);
  margin-bottom: 16px;
}
.view-user-modal-name { font-size: 18px; font-weight: 800; color: var(--text); }
.view-user-modal-role { margin-top: 5px; }
.view-user-modal-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 16px;
}
.view-user-modal-field {
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 13px 16px;
}
.view-user-modal-field-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--text-dim);
  letter-spacing: .5px;
  text-transform: uppercase;
  margin-bottom: 5px;
}
.view-user-modal-field-val {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text);
}
body.light .view-user-modal-header,
body.light .view-user-modal-field {
  background: #f8fafc;
  border-color: #d1dce8;
}
body.light .view-user-modal-field-val { color: #1e293b; }

/* Row number */
.td-num {
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-dim);
  font-family: 'JetBrains Mono', monospace;
}

/* Responsive */
@media(max-width:900px){
  .users-table .col-dept { display: none; }
  .users-table td.hide-mobile, .users-table th.col-dept { display: none; }
}
@media(max-width:700px){
  .users-toolbar { flex-direction: column; align-items: stretch; }
  .users-search-wrap { min-width: 0; }
  .users-filter-select { min-width: 0; width: 100%; }
  .uact-btn span { display: none; }
  .uact-btn { padding: 7px 9px; }
  .view-user-modal-grid { grid-template-columns: 1fr; }
  .users-page-header { flex-direction: column; align-items: flex-start; }
}

/* ═══════════════════════════════════════
   SIDEBAR COLLAPSE
═══════════════════════════════════════ */
.sidebar{transition:width .28s ease,transform .28s ease,box-shadow .28s ease}
.main{transition:margin-right .28s ease}

.sidebar.collapsed{width:0;overflow:hidden;border-left:none}
.sidebar.collapsed ~ .main{margin-right:0}

/* Light mode sidebar — white */
body.light .sidebar{
  background:#ffffff !important;
  border-left:1px solid #d1dce8 !important;
  box-shadow:2px 0 16px rgba(0,0,0,.07);
}
body.light .sidebar-glow{display:none}
body.light .sidebar-logo{border-bottom-color:#e8eef6 !important}
body.light .sidebar-profile{border-bottom-color:#e8eef6 !important}
body.light .sidebar-footer{border-top-color:#e8eef6 !important}
body.light .logo-brand{color:#1e293b !important}
body.light .logo-brand em{color:#0284c7}
body.light .logo-sub{color:#64748b !important}
body.light .profile-name{color:#1e293b !important}
body.light .profile-role{color:#64748b !important}
body.light .nav-section-label{color:#94a3b8 !important}
body.light .nav-item{color:#64748b !important}
body.light .nav-item:hover{background:#f0f6ff !important;color:#1e293b !important}
body.light .nav-item.active{
  background:linear-gradient(135deg,rgba(2,132,199,.1),rgba(99,102,241,.07)) !important;
  color:#0284c7 !important;
  border-color:rgba(2,132,199,.2) !important;
}
body.light .nav-item.active .nav-icon{background:rgba(2,132,199,.12) !important;color:#0284c7 !important}
body.light .nav-icon{background:rgba(0,0,0,.04) !important;color:#94a3b8 !important}
body.light .nav-item.danger{color:#ef4444 !important}
body.light .nav-item.danger .nav-icon{color:#ef4444 !important;background:rgba(239,68,68,.08) !important}
body.light .nav-item.danger:hover{background:rgba(239,68,68,.07) !important}
body.light .avatar-ring{
  background:linear-gradient(#fff,#fff) padding-box,
             linear-gradient(135deg,#0ea5e9,#6366f1) border-box !important;
}
body.light .online-dot{border-color:#fff !important}
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:20px;
    margin-top:20px;
}

.dashboard-card{
    background:rgba(15,23,42,.95);
    border:1px solid rgba(148,163,184,.12);
    border-radius:22px;
    padding:22px;
    backdrop-filter:blur(12px);
    box-shadow:0 10px 30px rgba(0,0,0,.25);
}

.card-header{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:15px;
    font-weight:700;
    color:#fff;
    margin-bottom:20px;
}

.card-header i{
    color:#10b981;
    font-size:18px;
}

.card-stats{
    display:flex;
    justify-content:space-between;
    gap:15px;
    margin-bottom:18px;
}

.stat-item{
    flex:1;
    text-align:center;
    padding:15px;
    border-radius:16px;
    background:rgba(255,255,255,.03);
}

.num{
    display:block;
    font-size:28px;
    font-weight:800;
    margin-bottom:6px;
}

.active-count{
    color:#34d399;
}

.inactive-count{
    color:#f87171;
}

.card-footer{
    border-top:1px solid rgba(255,255,255,.08);
    padding-top:14px;
    color:#94a3b8;
    font-size:13px;
}

.card-footer strong{
    color:#fff;
    font-size:16px;
}
.notification-dropdown{
    position:absolute;
    top:70px;
    left:20px;
    width:340px;
    background:var(--card-bg,#fff);
    border:1px solid rgba(0,0,0,0.08);
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
    display:none;
    z-index:9999;
    overflow:hidden;
}

.notification-header{
    padding:15px;
    font-weight:bold;
    border-bottom:1px solid rgba(0,0,0,0.08);
}

#notificationList{
    max-height:400px;
    overflow-y:auto;
}

.notification-item{
    padding:12px 15px;
    border-bottom:1px solid rgba(0,0,0,0.05);
    cursor:pointer;
}

.notification-item:hover{
    background:rgba(0,0,0,0.03);
}

body:not(.light) .notification-dropdown{    background:#1f2937;
    border-color:#374151;
}

body:not(.light) .notification-header{
    border-color:#374151;
}

body:not(.light) .notification-item{
    border-color:#374151;
}

/* ════ MedChifaGiz — تنسيق عناصر التنبيهات (نهاري/ليلي) ════ */
.notification-item{
    display:flex;
    align-items:flex-start;
    gap:9px;
    font-size:13.5px;
    line-height:1.55;
    color:var(--text,#1f2937);
    transition:background .15s ease;
}
.notification-item .notif-ico{
    flex:0 0 auto;
    font-size:15px;
    line-height:1.4;
}
.notification-item .notif-txt{ flex:1; }
.notification-item.notif-danger{ border-right:3px solid #ef4444; }
.notification-item.notif-warn{   border-right:3px solid #f59e0b; }
.notification-item.notif-info{   border-right:3px solid #3b82f6; }
body:not(.light) .notification-empty{
    padding:26px 15px;
    text-align:center;
    color:var(--text-muted,#94a3b8);
    font-size:13px;
}
body.dark-mode .notification-item{ color:#e5e7eb; }
body.dark-mode .notification-item:hover{ background:rgba(255,255,255,0.05); }
body.dark-mode .notification-empty{ color:#94a3b8; }

</style>
</head>
<body>
<script>
/* الوضع الافتراضي عند أول دخول = نهاري (Light)، مع استرجاع آخر اختيار محفوظ */
(function(){
  try{
    var saved = localStorage.getItem('mcg_theme');
    if(saved === 'dark'){
      document.body.classList.remove('light');
    } else {
      document.body.classList.add('light'); // لا يوجد اختيار محفوظ → نهاري
    }
  }catch(e){
    document.body.classList.add('light');
  }
})();
</script>

<div class="bg-mesh"></div>

<!-- ترويسة الطباعة (مخفية عادةً، تظهر عند الطباعة) -->
<div class="print-header" id="printHeader">
  <div class="print-header-logo">🏥 MedChifaGiz — Clinic Admin</div>
  <div class="print-header-info">
    <div id="printDate"></div>
    <div>نظام إدارة العيادة</div>
  </div>
</div>

<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileSidebar()"></div>

<!-- ═══════════════════════════════════════════


     SIDEBAR
════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-glow"></div>
  <div class="sidebar-logo">
   <div class="logo-icon">
    <img src="medchifagz.png" alt="MedChifaGiz">
</div>
    <div class="logo-text">
      <span class="logo-brand">MedChifa<em>Giz</em></span>
      <span class="logo-sub">Clinic Admin</span>
    </div>
  </div>

  <div class="sidebar-profile">
   <div class="profile-avatar">
    <i class="fas fa-user-circle"></i>
    <div class="online-dot"></div>
</div>
    <div class="profile-info">
     <span class="profile-name">
    <?php echo htmlspecialchars($_SESSION['name'] ?? 'المستخدم'); ?>
</span>
      <span class="profile-role">مسؤول العيادة</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">عام</div>
    <a class="nav-item active" onclick="switchSection('dashboard',this)">
      <div class="nav-icon"><i class="fas fa-house-medical"></i></div>
      <span>الرئيسية</span>
    </a>

    <div class="nav-section-label">الإدارة</div>
    <a class="nav-item" id="navItemServices" onclick="switchSection('services',this)">
      <div class="nav-icon"><i class="fas fa-sitemap"></i></div>
      <span>المصالح</span>
    </a>
    <a class="nav-item" onclick="switchSection('users',this)">
      <div class="nav-icon"><i class="fas fa-users-gear"></i></div>
      <span>الطاقم الطبي</span>
    </a>
    <a class="nav-item" onclick="switchSection('statistics',this)">
      <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
      <span>الإحصائيات</span>
    </a>
    <div class="nav-section-label">العيادة</div>
    <a class="nav-item" onclick="switchSection('activity',this)">
      <div class="nav-icon"><i class="fas fa-clock-rotate-left"></i></div>
      <span>نشاط العيادة</span>
    </a>
    <a class="nav-item" onclick="switchSection('clinic-settings',this)">
      <div class="nav-icon"><i class="fas fa-gear"></i></div>
      <span>إعدادات العيادة</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a class="nav-item danger" onclick="handleLogout()" style="margin:0;border-radius:10px">
      <div class="nav-icon"><i class="fas fa-right-from-bracket"></i></div>
      <span>تسجيل الخروج</span>
    </a>
  </div>
</aside>

<!-- ═══════════════════════════════════════════
     MAIN
════════════════════════════════════════════ -->
<main class="main" id="main">

  <!-- HEADER -->
  <header class="header">
    <div class="header-right">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
      <div class="header-title" id="headerTitle">الرئيسية</div>
    </div>
    <div class="header-center">
      <div>
        <div class="header-time" id="headerTime">--:--:--</div>
        <div class="header-date" id="headerDate">جاري التحميل...</div>
      </div>
    </div>
    <div class="header-left">
      
      <button class="header-btn" id="themeToggleBtn" onclick="toggleTheme()" title="تبديل الوضع">
        <i class="fas fa-moon" id="themeIcon"></i>
      </button>
      <button class="header-btn" id="notificationBtn">
    <i class="fas fa-bell"></i>
    <span class="btn-badge" id="notificationCount" style="<?= $mcgNotifTotal > 0 ? '' : 'display:none' ?>"><?= (int) $mcgNotifTotal ?></span>
</button>

<div id="notificationDropdown" class="notification-dropdown">
    <div class="notification-header">
        التنبيهات
    </div>

    <div id="notificationList">
<?php if ($mcgNotifTotal === 0): ?>
        <div class="notification-empty">لا توجد تنبيهات حالياً</div>
<?php else: ?>
<?php foreach ($mcgNotifications as $n): ?>
        <div class="notification-item notif-<?= htmlspecialchars($n['class'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="notif-ico"><?= $n['icon'] ?></span>
            <span class="notif-txt"><?= htmlspecialchars($n['text'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
<?php endforeach; ?>
<?php endif; ?>
    </div>
</div>
    </div>
  </header>

  <!-- ══════════════════════════════
       SECTION: DASHBOARD
  ═════════════════════════════════ -->
  <section class="section active" id="section-dashboard">
    <div class="welcome-bar">
      <div class="welcome-text">
       <h1>مرحباً، <?php echo htmlspecialchars($_SESSION['name'] ?? 'المستخدم'); ?> <span class="wave">👋</span></h1>
        <p>لوحة تحكم عيادة <strong>MedChifaGiz</strong> </p>
      </div>
      <div id="lastLoginAlert"></div>
    </div>

<!-- ════ Dashboard Statistics Cards ════ -->
<style id="mdash-cards-style">
.mdc-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:16px;
  margin-bottom:28px;
}
.mdc-card{
  position:relative;
  overflow:hidden;
  background:linear-gradient(160deg,rgba(255,255,255,.045),rgba(255,255,255,.012));
  border:1px solid rgba(148,163,184,.14);
  border-radius:18px;
  padding:18px 18px 16px;
  backdrop-filter:blur(14px);
  -webkit-backdrop-filter:blur(14px);
  box-shadow:0 8px 24px rgba(0,0,0,.28);
  transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.mdc-card::before{
  content:'';
  position:absolute;
  top:0;right:0;left:0;
  height:3px;
  background:linear-gradient(270deg,var(--c,#0ea5e9),transparent);
  opacity:.9;
}
.mdc-card:hover{
  transform:translateY(-4px);
  box-shadow:0 16px 34px rgba(0,0,0,.36);
  border-color:var(--c-bd,rgba(14,165,233,.4));
}
.mdc-icon{
  width:42px;height:42px;
  display:flex;align-items:center;justify-content:center;
  border-radius:13px;
  font-size:18px;
  color:var(--c,#0ea5e9);
  background:var(--c-bg,rgba(14,165,233,.14));
  margin-bottom:14px;
}
.mdc-title{
  font-size:12.5px;
  font-weight:700;
  color:#94a3b8;
  margin-bottom:7px;
}
.mdc-value{
  font-size:32px;
  font-weight:800;
  line-height:1;
  color:#fff;
  letter-spacing:-.5px;
}
.mdc-sub{
  margin-top:9px;
  font-size:12px;
  font-weight:600;
  color:#94a3b8;
  display:flex;
  align-items:center;
  gap:7px;
  flex-wrap:wrap;
}
.mdc-sub .mdc-on{color:#34d399}
.mdc-sub .mdc-off{color:#f87171}
.mdc-sub .mdc-dot{color:#475569}
body.light .mdc-card{
  background:#fff;
  border-color:#e2e8f0;
  box-shadow:0 8px 22px rgba(15,23,42,.06);
}
body.light .mdc-value{color:#0f172a}
body.light .mdc-title,body.light .mdc-sub{color:#64748b}
@media(max-width:1024px){ .mdc-grid{grid-template-columns:repeat(2,1fr);gap:14px} }
@media(max-width:560px){ .mdc-grid{grid-template-columns:1fr} }
</style>

<div class="stats-grid mdc-grid">

  <!-- المصالح -->
  <div class="mdc-card" style="--c:#34d399;--c-bg:rgba(52,211,153,.14);--c-bd:rgba(52,211,153,.4)">
    <div class="mdc-icon"><i class="fas fa-sitemap"></i></div>
    <div class="mdc-title">المصالح</div>
    <div class="mdc-value" id="totalServicesCount">0</div>
    <div class="mdc-sub">
      <span class="mdc-on" id="activeServicesCount">0</span><span>نشطة</span>
      <span class="mdc-dot">•</span>
      <span class="mdc-off" id="inactiveServicesCount">0</span><span>معطلة</span>
    </div>
  </div>

  <!-- الطاقم الطبي -->
  <div class="mdc-card" style="--c:#38bdf8;--c-bg:rgba(56,189,248,.14);--c-bd:rgba(56,189,248,.4)">
    <div class="mdc-icon"><i class="fas fa-users"></i></div>
    <div class="mdc-title">الطاقم الطبي</div>
    <div class="mdc-value" id="totalUsersCount">0</div>
    <div class="mdc-sub">
      <span class="mdc-on" id="activeUsersCount">0</span><span>نشط</span>
      <span class="mdc-dot">•</span>
      <span class="mdc-off" id="inactiveUsersCount">0</span><span>معطل</span>
    </div>
  </div>

  <!-- مسؤولو المصالح -->
  <div class="mdc-card" style="--c:#818cf8;--c-bg:rgba(129,140,248,.14);--c-bd:rgba(129,140,248,.4)">
    <div class="mdc-icon"><i class="fas fa-desktop"></i></div>
    <div class="mdc-title">مسؤولو المصالح</div>
    <div class="mdc-value" id="totalAdminsCount">0</div>
    <div class="mdc-sub">
      <span class="mdc-on" id="activeAdminsCount">0</span><span>نشط</span>
      <span class="mdc-dot">•</span>
      <span class="mdc-off" id="inactiveAdminsCount">0</span><span>معطل</span>
    </div>
  </div>

  <!-- الغرف -->
  <div class="mdc-card" style="--c:#60a5fa;--c-bg:rgba(96,165,250,.14);--c-bd:rgba(96,165,250,.4)">
    <div class="mdc-icon"><i class="fas fa-door-open"></i></div>
    <div class="mdc-title">الغرف</div>
    <div class="mdc-value" id="totalRoomsCount">0</div>
    <div class="mdc-sub">
      <span style="color:#60a5fa" id="totalBedsCount">0</span><span>سريراً</span>
    </div>
  </div>

</div>
    <!-- ════ القسمان المتجاوران ════ -->
    <div class="dash-two-cols">

      <!-- آخر الأنشطة -->
      <div>
        <div class="last-acts-wrap">
          <div class="dash-col-header">
            <div class="dash-col-header-icon" style="background:rgba(14,165,233,.12);color:#38bdf8">
              <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div>
              <div class="dash-col-title">آخر الأنشطة</div>
              <div class="dash-col-sub">آخر العمليات التي تمت داخل العيادة</div>
            </div>
          </div>
          <!-- items -->
          <div id="dashboardRecentActivities"></div>
        </div>
      </div>

      <!-- الأنشطة القادمة -->
   <div>
  <div class="upcoming-wrap">

    <div class="dash-col-header">
      <div class="dash-col-header-icon"
           style="background:rgba(245,158,11,.12);color:#fbbf24">
        <i class="fas fa-chart-line"></i>
      </div>

      <div>
        <div class="dash-col-title">المصالح الأكثر نشاطاً</div>
        <div class="dash-col-sub">
          سيتم عرض أكثر المصالح استعمالاً للتطبيق
        </div>
      </div>
    </div>

    <div id="topServicesContainer">

      <div class="empty-state-pro" style="padding:30px 20px">
        <div class="empty-state-pro-title">
          لا توجد بيانات نشاط كافية حالياً
        </div>

        <div class="empty-state-pro-sub">
          سيتم عرض المصالح الأكثر نشاطاً بعد تشغيل لوحات Service Admin
        </div>
      </div>

    </div>

  </div>
</div>

    </div><!-- end dash-two-cols -->

  </section>

  <!-- ══════════════════════════════
       SECTION: USERS
  ═════════════════════════════════ -->
  <section class="section" id="section-users">

    <!-- Page Header -->
    <div class="users-page-header">
      <div class="users-page-header-text">
        <div class="users-page-icon"><i class="fas fa-users-gear"></i></div>
        <div>
          <h2 class="section-title">إدارة المستخدمين</h2>
          <p class="section-sub">إدارة حسابات وصلاحيات العاملين في العيادة</p>
        </div>
      </div>
    </div>

    <!-- قسم إنشاء حساب جديد -->
    <div class="create-account-section">
      <div class="create-account-header">
        <div class="create-account-icon"><i class="fas fa-user-plus"></i></div>
        <div>
          <div class="create-account-title">إنشاء حساب جديد</div>
          <div class="create-account-sub">اختر الدور الوظيفي لإضافة حساب جديد للعيادة</div>
        </div>
      </div>
      <div class="create-account-btns">
        <button class="cab cab-doctor"    onclick="openAddUserModal('طبيب',       'fa-user-doctor')">
          <div class="cab-icon"><i class="fas fa-user-doctor"></i></div>
          <div class="cab-label">إضافة طبيب</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-nurse"     onclick="openAddUserModal('ممرض/ة',     'fa-user-nurse')">
          <div class="cab-icon"><i class="fas fa-user-nurse"></i></div>
          <div class="cab-label">إضافة ممرض/ة</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-lab"       onclick="openAddUserModal('مخبر',       'fa-flask')">
          <div class="cab-icon"><i class="fas fa-flask"></i></div>
          <div class="cab-label">إضافة مخبر</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-xray"      onclick="openAddUserModal('أشعة',       'fa-x-ray')">
          <div class="cab-icon"><i class="fas fa-x-ray"></i></div>
          <div class="cab-label">إضافة أشعة</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-pharmacy"  onclick="openAddUserModal('صيدلية',     'fa-pills')">
          <div class="cab-icon"><i class="fas fa-pills"></i></div>
          <div class="cab-label">إضافة صيدلية</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-svcadmin" id="cabSvcAdmin"  onclick="openAddUserModal('Service Admin','fa-desktop')">
          <div class="cab-icon"><i class="fas fa-desktop"></i></div>
          <div class="cab-label">إضافة Service Admin</div>
          <div class="cab-glow"></div>
        </button>
        <button class="cab cab-reception"  onclick="openAddReceptionModal()">
          <div class="cab-icon"><i class="fas fa-headset"></i></div>
          <div class="cab-label">إضافة موظف استقبال</div>
          <div class="cab-glow"></div>
        </button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="users-toolbar">
      <div class="users-search-wrap">
        <i class="fas fa-magnifying-glass users-search-icon"></i>
        <input type="text" id="usersSearchInput" class="users-search-input" placeholder="ابحث بالاسم ...">
        <button class="users-search-clear" id="usersSearchClear" onclick="clearUsersSearch()" style="display:none"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="users-filter-wrap">
        <i class="fas fa-filter users-filter-icon"></i>
        <select id="usersRoleFilter" class="users-filter-select" onchange="applyUsersFilter()">
          <option value="">كل الأدوار</option>
          <option value="طبيب">🩺 طبيب</option>
          <option value="ممرضة">🏥 ممرضة</option>
          <option value="مخبر">🔬 مخبر</option>
          <option value="أشعة">☢️ أشعة</option>
          <option value="صيدلية">💊 صيدلية</option>
          <option value="موظف استقبال">🖥️ موظف استقبال</option>
          <option value="Service Admin">⚙️ Service Admin</option>
        </select>
      </div>
      <div class="users-results-count" id="usersResultsCount">0 مستخدم</div>
    </div>

    <!-- Table -->
    <div class="users-table-wrap">
      <table class="users-table">
        <thead>
          <tr>
            <th class="col-num">#</th>
            <th class="col-name">المستخدم</th>
            <th class="col-role">الدور الوظيفي</th>
            <th class="col-dept">المصلحة</th>
            <th class="col-status">الحالة</th>
            <th class="col-actions">الإجراءات</th>
          </tr>
        </thead>
        <tbody id="usersTableBody">
        </tbody>
      </table>
      <div class="users-empty-state" id="usersEmptyState" style="display:none">
        <div class="empty-state-pro">
          <div class="empty-state-pro-ring">
            <span class="empty-state-pro-icon"><i class="fas fa-user-slash"></i></span>
          </div>
          <div class="empty-state-pro-title">لا توجد نتائج</div>
          <div class="empty-state-pro-sub">لم يتم العثور على مستخدمين مطابقين للبحث أو الفلتر المحدد</div>
          <button class="btn-secondary" onclick="clearUsersSearch(); document.getElementById('usersRoleFilter').value=''; applyUsersFilter()">
            <i class="fas fa-rotate-left"></i> إعادة تعيين الفلتر
          </button>
        </div>
      </div>
      <!-- Pagination -->
      <div id="usersPaginationWrap" class="pagination-wrap" style="display:none">
        <div class="pagination-info" id="usersPaginationInfo"></div>
        <div class="pagination-controls" id="usersPaginationControls"></div>
        <div class="pagination-per-page">
          <label>عناصر في الصفحة:</label>
          <select id="usersPerPage" onchange="usersPerPageChanged()">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════
       SECTION: SERVICES
  ═════════════════════════════════ -->
  <section class="section" id="section-services">

    <!-- Page Header -->
    <div class="services-page-header">
      <div class="services-page-header-text">
        <div class="services-page-icon"><i class="fas fa-hospital"></i></div>
        <div>
          <h2 class="section-title">المصالح</h2>
          <p class="section-sub">إدارة مصالح وأقسام العيادة</p>
        </div>
      </div>
      <button class="btn-add-service" onclick="openModal('addServiceModal')">
        <div class="btn-add-service-icon"><i class="fas fa-plus"></i></div>
        <span>إضافة مصلحة جديدة</span>
      </button>
    </div>

    <!-- Services Table -->
    <div class="services-table-wrap">
      <table class="services-table">
        <thead>
          <tr>
            <th>#</th>
            <th>المصلحة</th>
            <th>المسؤول (Service Admin)</th>
            <th>عدد العاملين</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody id="servicesTableBody">
        </tbody>
      </table>
      <!-- Services Empty State -->
      <div id="servicesEmptyState" style="display:none">
        <div class="empty-state-pro">
          <div class="empty-state-pro-ring">
            <span class="empty-state-pro-icon"><i class="fas fa-hospital-slash"></i></span>
          </div>
          <div class="empty-state-pro-title">لا توجد مصالح بعد</div>
          <div class="empty-state-pro-sub">لم يتم إنشاء أي مصلحة حتى الآن. ابدأ بإضافة أول مصلحة للعيادة</div>
          <button class="btn-primary" onclick="openModal('addServiceModal')">
            <i class="fas fa-plus"></i> إضافة مصلحة جديدة
          </button>
        </div>
      </div>
      <!-- Services Pagination -->
      <div id="servicesPaginationWrap" class="pagination-wrap" style="display:none">
        <div class="pagination-info" id="servicesPaginationInfo"></div>
        <div class="pagination-controls" id="servicesPaginationControls"></div>
        <div class="pagination-per-page">
          <label>عناصر في الصفحة:</label>
          <select id="servicesPerPage" onchange="servicesPerPageChanged()">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════
       SECTION: ACTIVITY
  ═════════════════════════════════ -->
  <section class="section" id="section-activity">
  <div class="section-header">
    <div>
      <h2 class="section-title">نشاط العيادة</h2>
      <p class="section-sub">آخر العمليات المسجّلة</p>
    </div>

    <div class="section-actions">
      <button class="btn-secondary" onclick="window.print()">
        <i class="fas fa-print"></i> طباعة
      </button>
    </div>
  </div>

  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">

    <!-- سيتم ملؤها تلقائياً بواسطة addActivity() -->
    <div class="activity-list" id="activityListContainer"></div>

    <!-- Activity Empty State -->
    <div id="activityEmptyState" style="display:none">
      <div class="empty-state-pro">
        <div class="empty-state-pro-ring">
          <span class="empty-state-pro-icon">
            <i class="fas fa-clock-rotate-left"></i>
          </span>
        </div>

        <div class="empty-state-pro-title">
          لا توجد أنشطة حديثة
        </div>

        <div class="empty-state-pro-sub">
          لم يتم تسجيل أي نشاط بعد. ستظهر هنا جميع العمليات المنجزة
        </div>

        <button class="btn-secondary"
          onclick="switchSection('dashboard', document.querySelector('[onclick*=dashboard]'))">
          <i class="fas fa-house-medical"></i>
          العودة للرئيسية
        </button>
      </div>
    </div>

    <!-- Activity Pagination -->
    <div id="activityPaginationWrap" class="pagination-wrap">
      <div class="pagination-info" id="activityPaginationInfo"></div>

      <div class="pagination-controls" id="activityPaginationControls"></div>

      <div class="pagination-per-page">
        <label>عناصر في الصفحة:</label>

        <select id="activityPerPage" onchange="activityPerPageChanged()">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
      </div>
    </div>

  </div>
</section>
  <!-- ══════════════════════════════
       SECTION: CLINIC SETTINGS
  ═════════════════════════════════ -->
  <section class="section" id="section-clinic-settings">
    <div class="section-header">
      <div>
        <h2 class="section-title">إعدادات العيادة</h2>
        <p class="section-sub">معلومات وبيانات العيادة</p>
      </div>
    </div>

    <div class="settings-layout">
      <div class="settings-profile-card">
        <div class="settings-avatar-big" style="margin-bottom:14px">
          <div id="clinicAvatarWrap" style="width:72px;height:72px;background:linear-gradient(135deg,#0ea5e9,#6366f1);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:30px;color:#fff;overflow:hidden">
            <i class="fas fa-hospital" id="clinicAvatarIcon"></i>
            <img id="clinicAvatarImg" src="" alt="" style="display:none;width:72px;height:72px;object-fit:cover;border-radius:16px">
          </div>
        </div>
        <h3 id="clinicProfileName">عيادة MedChifaGiz</h3>
        <p id="clinicProfileSub">منذ 2020 — الجزائر</p>
        <button class="btn-primary" style="font-size:12px;padding:7px 14px" onclick="saveClinicSettings()">
          <i class="fas fa-save"></i> حفظ
        </button>
      </div>

      <div class="settings-groups">
        <div class="settings-group">
          <div class="settings-group-title"><i class="fas fa-hospital"></i> معلومات العيادة</div>
          <div class="settings-field-grid">
            <div class="settings-field">
              <label>اسم العيادة</label>
              <input type="text" id="clinicName" value="عيادة MedChifaGiz">
            </div>
            <div class="settings-field">
              <label>الهاتف</label>
              <input type="text" id="clinicPhone" value="0550 123 456">
            </div>
            <div class="settings-field full">
              <label>العنوان</label>
              <input type="text" id="clinicAddress" value="شارع الاستقلال، الجزائر العاصمة">
            </div>
            <div class="settings-field">
              <label>ساعات العمل — بداية</label>
              <input type="time" id="clinicTimeStart" value="07:00">
            </div>
            <div class="settings-field">
              <label>ساعات العمل — نهاية</label>
              <input type="time" id="clinicTimeEnd" value="20:00">
            </div>
          </div>
          <div class="settings-field" style="margin-bottom:16px">
            <label>الشعار</label>
            <input type="file" id="clinicLogoInput" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" style="display:none" onchange="handleClinicLogoUpload(this)">
            <div class="logo-upload" id="clinicLogoUploadBox" onclick="document.getElementById('clinicLogoInput').click()" style="cursor:pointer;position:relative;overflow:hidden;min-height:72px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px">
              <img id="clinicLogoPreview" src="" alt="" style="display:none;max-height:60px;max-width:180px;object-fit:contain;border-radius:8px">
              <i class="fas fa-cloud-arrow-up" id="clinicLogoIcon"></i>
              <span id="clinicLogoSpan">اضغط لرفع شعار العيادة (PNG, JPG)</span>
            </div>
          </div>
          <button class="btn-primary" onclick="saveClinicSettings()">
            <i class="fas fa-save"></i> حفظ التغييرات
          </button>
        </div>

        <!-- ── هيكل المؤسسة الصحية ── -->
        <div class="settings-group" style="margin-top:20px">
          <div class="settings-group-title"><i class="fas fa-building-columns"></i> هيكل المؤسسة الصحية</div>

          <div class="struct-group">
            <!-- تفعيل المصالح -->
            <div class="struct-toggle-row">
              <div class="struct-toggle-info">
                <div class="struct-toggle-icon" style="background:rgba(14,165,233,.15);color:#38bdf8">
                  <i class="fas fa-sitemap"></i>
                </div>
                <div class="struct-toggle-text">
                  <span class="struct-toggle-label">تفعيل إدارة المصالح</span>
                  <span class="struct-toggle-sub">إظهار صفحة المصالح، Service Admin، وخيار المصلحة عند إضافة مستخدم</span>
                </div>
              </div>
              <label class="struct-switch">
                <input type="checkbox" id="toggleServices" onchange="applyStructureSettings()">
                <span class="struct-switch-slider"></span>
              </label>
            </div>

            <!-- تفعيل الغرف -->
            <div class="struct-toggle-row">
              <div class="struct-toggle-info">
                <div class="struct-toggle-icon" style="background:rgba(16,185,129,.15);color:#34d399">
                  <i class="fas fa-door-open"></i>
                </div>
                <div class="struct-toggle-text">
                  <span class="struct-toggle-label">تفعيل إدارة الغرف</span>
                  <span class="struct-toggle-sub">إظهار حقول الغرف والأسرّة عند إنشاء المصالح أو بشكل مستقل</span>
                </div>
              </div>
              <label class="struct-switch">
                <input type="checkbox" id="toggleRooms" onchange="applyStructureSettings()">
                <span class="struct-switch-slider"></span>
              </label>
            </div>

            <!-- شريط الحالة -->
            <div class="struct-status-bar" id="structStatusBar">
              <i class="fas fa-circle-info"></i>
              <span id="structStatusText">جارٍ التحميل...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- ═══════════════════════════════════════════
       SECTION: الإحصائيات
  ════════════════════════════════════════════ -->
  <section class="section" id="section-statistics">
    <div class="section-header">
      <div>
        <h2 class="section-title">الإحصائيات</h2>
        <p class="section-sub">نظرة شاملة على أداء العيادة والطاقم الطبي</p>
      </div>
    </div>

    <!-- ═══ Statistics — تعديل Layout/CSS فقط (بدون أي تغيير في Logic/Data/Colors) ═══ -->
    <style>
      /* كل القواعد مُقيّدة داخل #section-statistics حتى لا تؤثر على أي قسم آخر */

      /* (2) تقليل الفراغ بين الهيدر ومحتوى الإحصائيات — رفع المحتوى للأعلى */
      #section-statistics{padding-top:14px}
      #section-statistics .section-header{margin-bottom:12px}

      /* (3) كاردات أقصر و padding داخلي أصغر (نفس العرض ونفس الألوان) */
      #section-statistics .stat-card{padding:16px !important}
      #section-statistics .stats-card-head{display:flex;align-items:center;gap:10px;margin-bottom:12px}

      #section-statistics .stats-top-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;align-items:stretch}
      #section-statistics .stats-top-row > .stat-card{display:flex;flex-direction:column}
      #section-statistics .stats-doughnut-body{display:flex;align-items:center;justify-content:center;gap:20px;flex-wrap:wrap;flex:1}
      #section-statistics .stats-doughnut-ring{position:relative;width:160px;height:160px;flex-shrink:0;margin:0 auto}

      /* (4) ترتيب عناوين الـ Legend في شبكة 2×/3× حسب المساحة المتوفرة بجانب الدائرة */
      #section-statistics #doughnutLegend{
        display:grid !important;
        grid-template-columns:repeat(auto-fit,minmax(92px,1fr)) !important;
        gap:8px 14px !important;
        align-content:center;
        flex:1 1 190px;
        min-width:0;
      }
      /* إخفاء خطوط الفصل (1px) التي يولّدها الـ JS بين عناصر الـ legend حتى تبقى الشبكة منتظمة (CSS فقط — بدون لمس JS أو البيانات) */
      #section-statistics #doughnutLegend > div[style*="height:1px"]{display:none !important}

      #section-statistics .stats-half .stats-chart-wrap{position:relative;height:200px;flex:1;min-height:190px}
      #section-statistics .stats-fullrow{margin-bottom:22px}
      #section-statistics .stats-fullrow .stats-chart-wrap{position:relative;height:270px}

      /* (1) ضمان عدم ظهور أي مربع "عناصر في الصفحة" / Pagination داخل قسم Statistics فقط */
      #section-statistics .pagination-wrap,
      #section-statistics .pagination-per-page{display:none !important}

      /* Mobile: كل كارد في سطر مستقل (الصف العلوي يديره أيضاً applyStatisticsLayout) */
      @media (max-width:768px){
        #section-statistics .stats-top-row{grid-template-columns:1fr}
        #section-statistics .stats-fullrow .stats-chart-wrap{height:240px}
      }
    </style>

    <!-- ┌── الصف العلوي: توزيع الطاقم | المصالح الأكثر نشاطاً ──┐ -->
    <div class="stats-top-row" id="stats-charts-row">

      <!-- (1) توزيع الطاقم الطبي حسب الوظيفة -->
      <div class="stat-card stats-half" style="padding:18px;cursor:default">
        <div class="stats-card-head">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(14,165,233,.15);display:flex;align-items:center;justify-content:center;font-size:15px;color:#0ea5e9;flex-shrink:0">
            <i class="fas fa-chart-pie"></i>
          </div>
          <div>
           <div style="font-size:14px;font-weight:700;color:var(--text)">توزيع الطاقم الطبي حسب الوظيفة</div>
<div style="font-size:11.5px;color:var(--text-muted)">الأطباء والممرضون وباقي الفئات</div>
          </div>
        </div>
        <div class="stats-doughnut-body">
          <!-- الدائرة -->
          <div class="stats-doughnut-ring">
            <canvas id="doughnutChartPatients"></canvas>
            <!-- النص في الوسط -->
            <div id="doughnut-center" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
<div id="staffTotalCount" style="font-size:28px;font-weight:800;color:var(--text);line-height:1;font-family:'JetBrains Mono',monospace">0</div>
<div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-top:4px">إجمالي الموظفين</div>
            </div>
          </div>
          <!-- Legend -->
          <div id="doughnutLegend" style="display:flex;flex-direction:column;gap:10px;min-width:0">
            <!-- يتم ملؤها ديناميكياً ببيانات الطاقم الحقيقية عبر renderStaffRoleDoughnut() -->
          </div>
        </div>
      </div>

      <!-- (2) المصالح الأكثر نشاطاً -->
      <div class="stat-card stats-half" style="padding:18px;cursor:default">
        <div class="stats-card-head">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(14,165,233,.15);display:flex;align-items:center;justify-content:center;font-size:15px;color:#0ea5e9;flex-shrink:0">
            <i class="fas fa-chart-line"></i>
          </div>
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--text)">المصالح الأكثر نشاطاً</div>
<div style="font-size:11.5px;color:var(--text-muted)">أكثر المصالح استخداماً للنظام</div>
          </div>
        </div>
        <div class="stats-chart-wrap">
          <canvas id="lineChartPatients"></canvas>
        </div>
      </div>

    </div>

    <!-- ┌── الصف السفلي: عدد الموظفين في كل مصلحة (Full Width) ──┐ -->
    <div class="stats-fullrow">
      <div class="stat-card" style="padding:18px;cursor:default">
        <div class="stats-card-head">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(99,102,241,.15);display:flex;align-items:center;justify-content:center;font-size:15px;color:#818cf8;flex-shrink:0">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--text)">عدد الموظفين في كل مصلحة</div>
<div style="font-size:11.5px;color:var(--text-muted)">توزيع الموظفين حسب مصالح العيادة</div>
          </div>
        </div>
        <div class="stats-chart-wrap">
          <canvas id="barChartStaff"></canvas>
        </div>
      </div>
    </div>

  </section>

</main>

<!-- ═══════════════════════════════════════════
     MODAL: إضافة مستخدم — Enhanced
════════════════════════════════════════════ -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal" style="max-width:560px;display:flex;flex-direction:column;max-height:calc(100vh - 32px);overflow:hidden">
    <button class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title" style="flex-shrink:0"><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</div>
    <div class="modal-body-scroll" style="flex:1;overflow-y:auto;padding-left:2px;padding-right:2px">
    <div style="background:linear-gradient(135deg,rgba(14,165,233,.08),rgba(99,102,241,.06));border:1px solid rgba(14,165,233,.15);border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--text-muted)">
      <i class="fas fa-circle-info" style="color:var(--accent)"></i>
      أدخل بيانات المستخدم الجديد. سيتم إرسال بريد إلكتروني بكلمة المرور المؤقتة.
    </div>
    <div class="modal-grid">
      <div class="modal-field">
        <label><i class="fas fa-user" style="color:var(--accent);margin-left:5px"></i>الاسم الكامل</label>
        <input type="text" id="newUserName" placeholder="مثال: د. أحمد بن عمر">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-envelope" style="color:var(--accent);margin-left:5px"></i>البريد الإلكتروني</label>
        <input type="email" id="newUserEmail" placeholder="email@medchifagiz.dz">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-briefcase" style="color:var(--accent);margin-left:5px"></i>الوظيفة / Role</label>
        <input type="text" id="newUserRole" readonly style="cursor:default;opacity:.85">
      </div>
      <div class="modal-field" id="addUserSpecialtyField" style="display:none">
        <label><i class="fas fa-stethoscope" style="color:var(--accent);margin-left:5px"></i>التخصص</label>
        <input type="text" id="newUserSpecialty" placeholder="مثال: أمراض القلب">
      </div>
      <div class="modal-field" id="addUserPharmacyTypeField" style="display:none">
        <label><i class="fas fa-pills" style="color:var(--accent);margin-left:5px"></i>نوع الصيدلية</label>
        <select id="newUserPharmacyType">
          <option value="صيدلية مركزية">صيدلية مركزية</option>
          <option value="صيدلية مصلحة">صيدلية مصلحة</option>
        </select>
      </div>
      <div class="modal-field" id="addUserDeptField" style="grid-column:1/-1">
        <label><i class="fas fa-sitemap" style="color:var(--accent);margin-left:5px"></i>هل لدى هذا المستخدم مصلحة؟</label>
        <div style="display:flex;gap:20px;margin:6px 0 10px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:13.5px">
            <input type="radio" name="hasDept" id="hasDeptYes" value="yes" onchange="toggleDeptInput()" style="accent-color:var(--accent);width:15px;height:15px">
            نعم
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:13.5px">
            <input type="radio" name="hasDept" id="hasDeptNo" value="no" onchange="toggleDeptInput()" checked style="accent-color:var(--accent);width:15px;height:15px">
            لا
          </label>
        </div>
        <div id="deptNameWrapper" style="display:none">
          <select id="newUserDeptName" style="width:100%">
    <option value="">اختر المصلحة</option>
</select>
          <div id="deptSearchMsg" style="font-size:12px;margin-top:5px;min-height:18px"></div>
        </div>
      </div>
      <div class="modal-field">
        <label><i class="fas fa-lock" style="color:var(--accent);margin-left:5px"></i>كلمة السر المؤقتة</label>
      <input type="password" id="newUserPassword" placeholder="••••••••">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-phone" style="color:var(--accent);margin-left:5px"></i>رقم الهاتف</label>
        <input type="text" id="newUserPhone" placeholder="0550 XXX XXX">
      </div>
    </div>
    </div><!-- end modal-body-scroll -->
    <div class="modal-actions" style="flex-shrink:0;border-top:1px solid var(--border);margin-top:8px;padding-top:14px">
      <button class="btn-secondary" onclick="closeModal('addUserModal')"><i class="fas fa-xmark"></i> إلغاء</button>
      <button class="btn-primary" onclick="addUser()">
        <i class="fas fa-user-plus"></i> إضافة المستخدم
      </button>
    </div>
  </div>
</div>

<!-- MODAL: إضافة مصلحة -->

<div class="modal-overlay" id="addServiceModal">
  <div class="modal" style="max-width:540px;max-height:90vh;display:flex;flex-direction:column;padding:0;overflow:hidden">
    <!-- Sticky Header -->
    <div style="flex-shrink:0;padding:22px 24px 14px;position:relative;border-bottom:1px solid var(--border)">
      <button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fas fa-times"></i></button>
      <div style="font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px"><i class="fas fa-hospital" style="color:var(--accent)"></i> إضافة مصلحة جديدة</div>
    </div>

    <!-- Scrollable Body -->
    <style>#addServiceModal .modal-scroll-body::-webkit-scrollbar{width:5px}#addServiceModal .modal-scroll-body::-webkit-scrollbar-track{background:transparent}#addServiceModal .modal-scroll-body::-webkit-scrollbar-thumb{background:rgba(14,165,233,.35);border-radius:999px}#addServiceModal .modal-scroll-body::-webkit-scrollbar-thumb:hover{background:rgba(14,165,233,.6)}</style>
    <div class="modal-scroll-body" style="flex:1;overflow-y:auto;padding:16px 24px;scrollbar-width:thin;scrollbar-color:rgba(14,165,233,.35) transparent">
      <div class="add-service-modal-icon-header">
        <div class="add-service-modal-icon"><i class="fas fa-hospital-user"></i></div>
        <div>
          <div class="add-service-modal-title">مصلحة جديدة</div>
          <div class="add-service-modal-sub">أضف قسمًا أو مصلحة جديدة إلى العيادة</div>
        </div>
      </div>

      <!-- الحقول الأساسية -->
      <div class="modal-field">
  <label>
    <i class="fas fa-hospital"
       style="color:var(--accent);margin-left:5px"></i>
    اسم المصلحة
  </label>

  <input
      type="text"
      id="newServiceName"
      placeholder="مثال: قسم القلب والأوعية">
</div>

      <!-- سؤال: هل تحتوي على غرف إقامة؟ -->
      <div class="has-rooms-toggle-row" id="addHasRoomsRow">
        <div class="has-rooms-toggle-info">
          <div class="has-rooms-toggle-icon"><i class="fas fa-bed"></i></div>
          <div class="has-rooms-toggle-text">
            <div class="has-rooms-toggle-label">هل تحتوي هذه المصلحة على غرف إقامة؟</div>
            <div class="has-rooms-toggle-sub">مثل: الطب الداخلي، الجراحة — الأشعة، المخبر لا تحتوي</div>
          </div>
        </div>
        <div class="has-rooms-radio-group">
          <label class="has-rooms-radio-btn" id="addHasRoomsBtnYes" onclick="setHasRooms('add','yes')">
            <input type="radio" name="addHasRooms" value="yes">
            <i class="fas fa-check-circle" style="font-size:13px"></i> نعم
          </label>
          <label class="has-rooms-radio-btn selected-no" id="addHasRoomsBtnNo" onclick="setHasRooms('add','no')">
            <input type="radio" name="addHasRooms" value="no" checked>
            <i class="fas fa-xmark-circle" style="font-size:13px"></i> لا
          </label>
        </div>
      </div>

      <!-- حقول الغرف — مخفية افتراضياً -->
      <div id="addRoomSection" style="display:none">

      <!-- نوع توزيع الغرف -->
      <div class="modal-field" id="roomDistField">
        <label><i class="fas fa-door-open" style="color:var(--accent);margin-left:5px"></i>نوع توزيع الغرف</label>
        <div class="room-dist-options">
          <label class="room-dist-option" id="rdOptWings">
            <input type="radio" name="roomDistType" value="wings" onchange="onRoomDistChange()" checked>
            <div class="room-dist-option-inner">
              <div class="room-dist-option-icons">
                <i class="fas fa-mars" style="color:#60a5fa"></i>
                <i class="fas fa-venus" style="color:#f472b6"></i>
              </div>
              <div class="room-dist-option-text">
                <span class="room-dist-option-title">جناح رجال وجناح نساء</span>
                <span class="room-dist-option-sub">مصالح مثل الطب الداخلي، طب الأطفال</span>
              </div>
            </div>
          </label>
          <label class="room-dist-option" id="rdOptShared">
            <input type="radio" name="roomDistType" value="shared" onchange="onRoomDistChange()">
            <div class="room-dist-option-inner">
              <div class="room-dist-option-icons">
                <i class="fas fa-door-closed" style="color:#34d399"></i>
              </div>
              <div class="room-dist-option-text">
                <span class="room-dist-option-title">غرف مشتركة فقط</span>
                <span class="room-dist-option-sub">مصالح مثل الأشعة، المخبر، الصيدلية</span>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- حقول جناح رجال ونساء -->
      <div id="roomFieldsWings" class="room-fields-section">
        <div class="room-wing-block room-wing-men">
          <div class="room-wing-header">
            <i class="fas fa-mars"></i>
            <span>جناح الرجال</span>
          </div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#60a5fa;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="menRooms" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#60a5fa;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="menBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
          </div>
        </div>
        <div class="room-wing-block room-wing-women">
          <div class="room-wing-header">
            <i class="fas fa-venus"></i>
            <span>جناح النساء</span>
          </div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#f472b6;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="womenRooms" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#f472b6;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="womenBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
          </div>
        </div>
      </div>

      <!-- حقول غرف مشتركة -->
      <div id="roomFieldsShared" class="room-fields-section" style="display:none">
        <div class="room-wing-block room-wing-shared">
          <div class="room-wing-header">
            <i class="fas fa-door-closed"></i>
            <span>الغرف المشتركة</span>
          </div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#34d399;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="sharedRooms" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#34d399;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="sharedBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcTotals()">
            </div>
          </div>
        </div>
      </div>

      <!-- المجاميع المحسوبة تلقائياً -->
      <div class="room-totals-bar">
        <div class="room-total-item">
          <i class="fas fa-door-open"></i>
          <div>
            <div class="room-total-label">إجمالي الغرف</div>
            <div class="room-total-value" id="totalRoomsDisplay">0</div>
          </div>
        </div>
        <div class="room-total-divider"></div>
        <div class="room-total-item">
          <i class="fas fa-bed"></i>
          <div>
            <div class="room-total-label">إجمالي الأسرّة</div>
            <div class="room-total-value" id="totalBedsDisplay">0</div>
          </div>
        </div>
      </div>
      </div><!-- /addRoomSection -->
    </div>

    <!-- Sticky Footer -->
    <div class="modal-actions" style="flex-shrink:0;border-top:1px solid var(--border);margin:0;padding:16px 24px;border-radius:0 0 var(--radius) var(--radius)">
      <button class="btn-secondary" onclick="closeModal('addServiceModal')"><i class="fas fa-xmark"></i> إلغاء</button>
      <button class="btn-primary" onclick="addService()">
        <i class="fas fa-hospital-user"></i> إضافة المصلحة
      </button>
    </div>
  </div>
</div>

<!-- MODAL: إضافة موظف استقبال -->
<div class="modal-overlay" id="addReceptionModal">
  <div class="modal" style="max-width:560px">
    <button class="modal-close" onclick="closeModal('addReceptionModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title" style="color:var(--text)">
      <i class="fas fa-headset" style="color:#2dd4bf"></i> إضافة موظف استقبال
    </div>
    <div style="background:linear-gradient(135deg,rgba(20,184,166,.1),rgba(20,184,166,.04));border:1px solid rgba(20,184,166,.2);border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--text-muted)">
      <i class="fas fa-circle-info" style="color:#2dd4bf"></i>
      سيُضاف الموظف بدور <strong style="color:#2dd4bf">موظف استقبال</strong> ومصلحة <strong style="color:#2dd4bf">الاستقبال</strong> تلقائياً.
    </div>
    <div class="modal-grid">
      <div class="modal-field">
        <label><i class="fas fa-user" style="color:#2dd4bf;margin-left:5px"></i>الاسم الكامل</label>
        <input type="text" id="recpName" placeholder="مثال: سمير بن عمار">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-envelope" style="color:#2dd4bf;margin-left:5px"></i>البريد الإلكتروني</label>
        <input type="email" id="recpEmail" placeholder="email@medchifagiz.dz">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-lock" style="color:#2dd4bf;margin-left:5px"></i>كلمة السر الأولية</label>
        <input type="password" id="recpPassword" placeholder="••••••••">
      </div>
      <div class="modal-field">
        <label><i class="fas fa-phone" style="color:#2dd4bf;margin-left:5px"></i>رقم الهاتف</label>
        <input type="text" id="recpPhone" placeholder="0550 XXX XXX">
      </div>
      <div class="modal-field" style="grid-column:span 2">
        <label><i class="fas fa-briefcase" style="color:#2dd4bf;margin-left:5px"></i>الدور الوظيفي</label>
        <input type="text" value="موظف استقبال" readonly
          style="opacity:.7;cursor:not-allowed;background:rgba(20,184,166,.06);border-color:rgba(20,184,166,.25);color:#2dd4bf;font-weight:700">
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('addReceptionModal')"><i class="fas fa-xmark"></i> إلغاء</button>
      <button class="btn-primary" onclick="addReceptionUser()"
        style="background:linear-gradient(135deg,#0d9488,#14b8a6);box-shadow:0 4px 14px rgba(20,184,166,.3)">
        <i class="fas fa-headset"></i> إضافة موظف الاستقبال
      </button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
/* ══════════════════════════════════════════════
   STATE — البيانات الحية
══════════════════════════════════════════════ */
const state = {
  users: [
    {id:1, name:'د. كريم بلعيد',   role:'طبيب',         roleIcon:'fa-user-doctor',  dept:'الطب الداخلي', active:true},
    {id:2, name:'فاطمة زهراء',     role:'ممرضة',        roleIcon:'fa-user-nurse',   dept:'جراحة العامة', active:true},
    {id:3, name:'يوسف مقراني',     role:'مخبر',         roleIcon:'fa-flask',        dept:'المخبر',        active:true},
    {id:4, name:'سامية بن علي',    role:'صيدلانية',     roleIcon:'fa-pills',        dept:'الصيدلية',      active:false},
    {id:5, name:'رضا حمدي',        role:'أشعة',         roleIcon:'fa-x-ray',        dept:'الأشعة',        active:true},
    {id:6, name:'نادية كعبوش',     role:'Service Admin',roleIcon:'fa-desktop',      dept:'الطب الداخلي', active:true},
    {id:7, name:'عمار تواتي',      role:'موظف',         roleIcon:'fa-id-badge',     dept:'الاستقبال',     active:true},
  ],
  services: [
    {id:1, name:'الطب الداخلي',   icon:'fa-heart-pulse', color:'#0ea5e9', admin:'نادية كعبوش',  workers:14, createdAt:'01/01/2023', hasRooms:true,
      roomData:{distType:'wings', menRooms:6, menBedsPerRoom:4, womenRooms:5, womenBedsPerRoom:4}, totalRooms:11, totalBeds:44},
    {id:2, name:'جراحة العامة',   icon:'fa-scalpel',     color:'#6366f1', admin:'سامي بوشقرون', workers:10, createdAt:'15/03/2023', hasRooms:true,
      roomData:{distType:'wings', menRooms:4, menBedsPerRoom:2, womenRooms:3, womenBedsPerRoom:2}, totalRooms:7,  totalBeds:14},
    {id:3, name:'المخبر',          icon:'fa-flask',       color:'#f59e0b', admin:'وليد درار',    workers:6,  createdAt:'01/06/2022', hasRooms:false,
      roomData:{distType:'shared', sharedRooms:0, sharedBedsPerRoom:0},                           totalRooms:0,  totalBeds:0},
    {id:4, name:'الأشعة',          icon:'fa-x-ray',       color:'#10b981', admin:'حنان بلقاسم', workers:5,  createdAt:'10/02/2023', hasRooms:false,
      roomData:{distType:'shared', sharedRooms:0, sharedBedsPerRoom:0},                           totalRooms:0,  totalBeds:0},
    {id:5, name:'الصيدلية',        icon:'fa-pills',       color:'#ec4899', admin:'سامية بن علي',workers:4,  createdAt:'20/04/2022', hasRooms:false,
      roomData:{distType:'shared', sharedRooms:0, sharedBedsPerRoom:0},                           totalRooms:0,  totalBeds:0},
    {id:6, name:'طب الأطفال',     icon:'fa-baby',        color:'#a78bfa', admin:'أميرة مزيان', workers:8,  createdAt:'05/09/2023', hasRooms:true,
      roomData:{distType:'wings', menRooms:3, menBedsPerRoom:3, womenRooms:3, womenBedsPerRoom:3}, totalRooms:6,  totalBeds:18},
    {id:7, name:'الاستقبال',       icon:'fa-id-card',     color:'#34d399', admin:'عمار تواتي',  workers:3,  createdAt:'01/01/2023', hasRooms:false,
      roomData:{distType:'shared', sharedRooms:0, sharedBedsPerRoom:0},                           totalRooms:0,  totalBeds:0},
  ],
  nextUserId: 8,
  nextServiceId: 8,
  editingUserId: null,
  editingServiceId: null,
  pendingDeleteServiceId: null,
  clinicSettings: {
    name: 'عيادة MedChifaGiz',
    phone: '0550 123 456',
    address: 'شارع الاستقلال، الجزائر العاصمة',
    workStart: '07:00',
    workEnd: '20:00',
  }
};

/* ══════════════════════════════════════════════
   THEME TOGGLE
══════════════════════════════════════════════ */
function toggleTheme(){
  document.body.classList.toggle('light');
  const isLight=document.body.classList.contains('light');
  document.getElementById('themeIcon').className=isLight?'fas fa-sun':'fas fa-moon';
  /* حفظ اختيار المستخدم لاسترجاعه عند الدخول مرة أخرى */
  try{ localStorage.setItem('mcg_theme', isLight ? 'light' : 'dark'); }catch(e){}
}
/* مزامنة أيقونة الوضع مع الحالة الحالية عند تحميل الصفحة */
(function(){
  const icon=document.getElementById('themeIcon');
  if(icon) icon.className=document.body.classList.contains('light')?'fas fa-sun':'fas fa-moon';
})();

/* ══════════════════════════════════════════════
   CLOCK
══════════════════════════════════════════════ */
function updateClock(){
  const now=new Date();
  const t=now.toLocaleTimeString('ar-DZ',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
  const d=now.toLocaleDateString('ar-DZ',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
  document.getElementById('headerTime').textContent=t;
  document.getElementById('headerDate').textContent=d;
}
updateClock();setInterval(updateClock,1000);

/* ══════════════════════════════════════════════
   SECTION SWITCH
══════════════════════════════════════════════ */
const titles={
  dashboard:'الرئيسية',
  users:'الطاقم الطبي',
  services:'المصالح',
  activity:'نشاط العيادة',
  'clinic-settings':'إعدادات العيادة',
  'statistics':'الإحصائيات'
};
function switchSection(id,el){
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('section-'+id).classList.add('active');
  if(el)el.classList.add('active');
  document.getElementById('headerTitle').textContent=titles[id]||'';
  if(id==='statistics'){
    setTimeout(()=>{ applyStatisticsLayout(); initStatisticsCharts(); },50);
  }
}

/* ══════════════════════════════════════════════
   SIDEBAR TOGGLE — محسّن للموبايل
══════════════════════════════════════════════ */
function toggleSidebar(){
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if(window.innerWidth <= 768){
    sidebar.classList.toggle('mobile-open');
    backdrop.classList.toggle('visible');
    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
  } else {
    sidebar.classList.toggle('collapsed');
  }
}
function closeMobileSidebar(){
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  sidebar.classList.remove('mobile-open');
  backdrop.classList.remove('visible');
  document.body.style.overflow = '';
}
// Close sidebar on nav item click (mobile)
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if(window.innerWidth <= 768) closeMobileSidebar();
  });
});

/* ══════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════ */
function showToast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2800);
}

/* ══════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════ */
function openModal(id){document.getElementById(id).classList.add('open')}

/* ══════════════════════════════════════════════
   OPEN ADD USER MODAL — فتح نافذة إضافة بدور محدد
══════════════════════════════════════════════ */
function openAddUserModal(role, icon){
  // مسح الحقول
  ['newUserName','newUserEmail','newUserPhone','newUserPassword','newUserSpecialty','newUserDeptName'].forEach(id=>{
    const el = document.getElementById(id);
    if(el) el.value = '';
  });
  // تعيين الدور كنص للعرض فقط
  const roleInput = document.getElementById('newUserRole');
  if(roleInput) roleInput.value = role;
  // إظهار/إخفاء حقل التخصص
  const specialtyField = document.getElementById('addUserSpecialtyField');
  if(specialtyField) specialtyField.style.display = (role === 'طبيب') ? '' : 'none';
  // إظهار/إخفاء حقل نوع الصيدلية
  const pharmacyTypeField = document.getElementById('addUserPharmacyTypeField');
  if(pharmacyTypeField) pharmacyTypeField.style.display = (role === 'صيدلية') ? '' : 'none';
  const pharmacyTypeSelect = document.getElementById('newUserPharmacyType');
  if(pharmacyTypeSelect) pharmacyTypeSelect.selectedIndex = 0;
  // إعادة ضبط خيار المصلحة إلى "لا"
  const noRadio = document.getElementById('hasDeptNo');
  if(noRadio){ noRadio.checked = true; }
  const wrapper = document.getElementById('deptNameWrapper');
  if(wrapper) wrapper.style.display = 'none';
  const msg = document.getElementById('deptSearchMsg');
  if(msg) msg.textContent = '';
  // إخفاء قسم المصلحة كاملاً للأدوار: أشعة، مخبر
  const deptField = document.getElementById('addUserDeptField');
  if(deptField){
    const hideForRoles = ['أشعة', 'مخبر'];
    deptField.style.display = hideForRoles.includes(role) ? 'none' : '';
  }
  openModal('addUserModal');
}

function toggleDeptInput(){
  const role = document.getElementById('newUserRole') ? document.getElementById('newUserRole').value : '';
  const hideForRoles = ['أشعة', 'مخبر'];
  // إذا كان الدور أشعة أو مخبر، لا تفعل شيئاً (القسم مخفي بالكامل)
  if(hideForRoles.includes(role)) return;
  const yes = document.getElementById('hasDeptYes').checked;
  document.getElementById('deptNameWrapper').style.display = yes ? '' : 'none';
  if(!yes){
    document.getElementById('newUserDeptName').value = '';
    document.getElementById('deptSearchMsg').textContent = '';
  }
}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
});

/* ══════════════════════════════════════════════
   LOGOUT
══════════════════════════════════════════════ */
function handleLogout(){

    showToast('جارٍ تسجيل الخروج...');

    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 1000);

}

/* ══════════════════════════════════════════════
   ROLE BADGE CLASS HELPER
══════════════════════════════════════════════ */
function getRoleBadgeClass(role){
  if(['طبيب','دكتور'].includes(role)) return 'rbadge-doctor';
  if(['ممرضة','ممرض'].includes(role)) return 'rbadge-nurse';
  if(['مخبر'].includes(role)) return 'rbadge-lab';
  if(['أشعة'].includes(role)) return 'rbadge-xray';
  if(['صيدلية','صيدلانية','صيدلي'].includes(role)) return 'rbadge-pharmacy';
  if(['Service Admin'].includes(role)) return 'rbadge-admin';
  if(['موظف استقبال'].includes(role)) return 'rbadge-reception';
  if(['مدير العيادة'].includes(role)) return 'rbadge-manager';
  return 'rbadge-staff';
}
function getAvatarLetters(name){
  const parts = name.replace(/^د\.\s*/,'').split(' ');
  return parts[0].charAt(0) + (parts[1]?parts[1].charAt(0):'');
}


/* ══════════════════════════════════════════════
   RECEPTION USER — إضافة موظف استقبال
══════════════════════════════════════════════ */
function openAddReceptionModal(){
  ['recpName','recpEmail','recpPhone'].forEach(id=>{
    const el = document.getElementById(id);
    if(el) el.value = '';
  });
  const pw = document.getElementById('recpPassword');
  if(pw) pw.value = '';
  openModal('addReceptionModal');
}

function addReceptionUser(){
  const name     = document.getElementById('recpName').value.trim();
  const email    = document.getElementById('recpEmail').value.trim();
  const phone    = document.getElementById('recpPhone').value.trim();
  const password = document.getElementById('recpPassword').value;

  if(!name){     showToast('⚠ الاسم مطلوب');      return; }
  if(!password){ showToast('⚠ كلمة السر مطلوبة'); return; }

  const fd = new FormData();
  fd.append('full_name', name);
  fd.append('email',     email);
  fd.append('phone',     phone);
  fd.append('password',  password);
  fd.append('role',      'receptionist');
  fd.append('dept_name', 'الاستقبال');

  fetch('save_clinic_staff.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(data => {
    if(!data.success){
      showToast('❌ ' + (data.message || 'فشل الحفظ'));
      return;
    }
    closeModal('addReceptionModal');
    ['recpName','recpEmail','recpPhone','recpPassword'].forEach(id=>{
      const el = document.getElementById(id);
      if(el) el.value = '';
    });
    loadClinicStaff();
    addActivity('fa-headset','rgba(20,184,166,.15)','#2dd4bf','إضافة موظف استقبال',
      `تم إضافة <strong>${name}</strong> بصلاحية موظف استقبال في مصلحة الاستقبال`);
    showToast(`تم إضافة ${name} كموظف استقبال ✓`);
  })
  .catch(() => showToast('❌ خطأ في الاتصال بالخادم'));
}

/* ══════════════════════════════════════════════
   PAGINATION STATE
══════════════════════════════════════════════ */
const pagination = {
  users:    { page: 1, perPage: 10, filtered: [] },
  services: { page: 1, perPage: 10, filtered: [] },
  activity: { page: 1, perPage: 10 },
};

/* ══════════════════════════════════════════════
   PAGINATION RENDERER — مُصيِّر الترقيم
══════════════════════════════════════════════ */
function renderPagination(key, total, controlsId, infoId, wrapId){
  const p = pagination[key];
  const totalPages = Math.ceil(total / p.perPage) || 1;
  const wrap = document.getElementById(wrapId);
  const controls = document.getElementById(controlsId);
  const info = document.getElementById(infoId);

  if(!wrap || !controls || !info) return;

  if(total <= p.perPage){
    wrap.style.display = 'none';
    return;
  }
  wrap.style.display = 'flex';

  const start = (p.page - 1) * p.perPage + 1;
  const end   = Math.min(p.page * p.perPage, total);
  info.textContent = `عرض ${start}–${end} من ${total}`;

  let html = '';
  html += `<button class="page-btn" onclick="goPage('${key}',${p.page-1})" ${p.page===1?'disabled':''}><i class="fas fa-chevron-right"></i></button>`;

  const range = getPaginationRange(p.page, totalPages);
  range.forEach(r => {
    if(r === '...'){
      html += `<span class="page-btn-ellipsis">…</span>`;
    } else {
      html += `<button class="page-btn ${r===p.page?'active':''}" onclick="goPage('${key}',${r})">${r}</button>`;
    }
  });

  html += `<button class="page-btn" onclick="goPage('${key}',${p.page+1})" ${p.page===totalPages?'disabled':''}><i class="fas fa-chevron-left"></i></button>`;
  controls.innerHTML = html;
}

function getPaginationRange(current, total){
  if(total <= 7) return Array.from({length:total},(_,i)=>i+1);
  const pages = [];
  if(current <= 4){
    pages.push(1,2,3,4,5,'...',total);
  } else if(current >= total - 3){
    pages.push(1,'...',total-4,total-3,total-2,total-1,total);
  } else {
    pages.push(1,'...',current-1,current,current+1,'...',total);
  }
  return pages;
}

function goPage(key, page){
  const p = pagination[key];
  const totalPages = Math.ceil(p.filtered ? p.filtered.length / p.perPage : 1);
  if(page < 1 || page > totalPages) return;
  p.page = page;
  if(key === 'users') renderUsersPage();
  else if(key === 'services') renderServicesPage();
  else if(key === 'activity') renderActivityPage();
}

/* ══════════════════════════════════════════════
   RENDER — جدول المستخدمين (ENHANCED + PAGINATION)
══════════════════════════════════════════════ */
function renderUsers(list){
  list = list || state.users;
  pagination.users.filtered = list;
  pagination.users.page = 1;
  renderUsersPage();
}

function renderUsersPage(){
  const list = pagination.users.filtered;
  const p    = pagination.users;
  const tbody = document.getElementById('usersTableBody');
  const empty = document.getElementById('usersEmptyState');
  const countEl = document.getElementById('usersResultsCount');
  if(countEl) countEl.textContent = `${list.length} من ${state.users.length} مستخدم`;

  if(!list.length){
    tbody.innerHTML='';
    if(empty) empty.style.display='flex';
    renderPagination('users',0,'usersPaginationControls','usersPaginationInfo','usersPaginationWrap');
    return;
  }
  if(empty) empty.style.display='none';

  const start = (p.page - 1) * p.perPage;
  const page  = list.slice(start, start + p.perPage);

  tbody.innerHTML = page.map((u, idx)=>{
    const globalIdx = start + idx;
    const roleCls = getRoleBadgeClass(u.role);
    const initials = getAvatarLetters(u.name);
    const avatarCls = u.active ? 'user-avatar' : 'user-avatar inactive-avatar';
    const statusBadge = u.active
      ? `<span class="sbadge sbadge-active"><span class="sbadge-dot"></span>نشط</span>`
      : `<span class="sbadge sbadge-inactive"><span class="sbadge-dot"></span>معطّل</span>`;
    const toggleBtn = u.active
      ? `<button class="uact-btn uact-toggle" onclick="toggleUserStatus(${u.id})" title="تعطيل الحساب"><i class="fas fa-lock"></i> <span>تعطيل</span></button>`
      : `<button class="uact-btn uact-toggle is-inactive" onclick="toggleUserStatus(${u.id})" title="تفعيل الحساب"><i class="fas fa-lock-open"></i> <span>تفعيل</span></button>`;
    return `
    <tr style="animation-delay:${idx*0.04}s">
      <td class="td-num">${globalIdx+1}</td>
      <td>
        <div class="user-cell">
          <div class="${avatarCls}">${initials}</div>
          <div>
            <div class="user-name">${u.name}</div>
            <div class="user-dept-small">${u.dept}</div>
          </div>
        </div>
      </td>
      <td><span class="rbadge ${roleCls}"><i class="fas ${u.roleIcon}"></i> ${u.role}</span></td>
      <td>${u.dept}</td>
      <td>${statusBadge}</td>
      <td>
        <div class="user-actions">
          <button class="uact-btn uact-view" onclick="viewUser(${u.id})" title="عرض المعلومات"><i class="fas fa-eye"></i> <span>عرض</span></button>
          <button class="uact-btn uact-edit" onclick="openEditUser(${u.id})" title="تعديل البيانات"><i class="fas fa-pen"></i> <span>تعديل</span></button>
          ${toggleBtn}
          <button class="uact-btn uact-delete" onclick="deleteUser(${u.id})" title="حذف المستخدم"><i class="fas fa-trash-can"></i> <span>حذف</span></button>
        </div>
      </td>
    </tr>`
  }).join('');

  renderPagination('users', list.length, 'usersPaginationControls','usersPaginationInfo','usersPaginationWrap');
}

function usersPerPageChanged(){
  pagination.users.perPage = parseInt(document.getElementById('usersPerPage').value)||10;
  pagination.users.page = 1;
  renderUsersPage();
}

/* ══════════════════════════════════════════════
   USERS STATS ROW
══════════════════════════════════════════════ */
function renderUsersStats(){
  const el = document.getElementById('usersStatsRow');
  if(!el) return;
  const total = state.users.length;
  const active = state.users.filter(u=>u.active).length;
  const inactive = total - active;
  const doctors = state.users.filter(u=>u.role==='طبيب').length;
  const stats = [
    {val:total,   lbl:'إجمالي المستخدمين', icon:'fa-users',       bg:'rgba(14,165,233,.15)',  color:'#38bdf8'},
    {val:active,  lbl:'نشطون',             icon:'fa-circle-check', bg:'rgba(16,185,129,.15)',  color:'#34d399'},
    {val:inactive,lbl:'معطّلون',           icon:'fa-circle-xmark', bg:'rgba(100,116,139,.15)', color:'#94a3b8'},
    {val:doctors, lbl:'أطباء',             icon:'fa-user-doctor',  bg:'rgba(99,102,241,.15)',  color:'#a5b4fc'},
  ];
  el.innerHTML = stats.map(s=>`
    <div class="users-stat-card">
      <div class="users-stat-dot" style="background:${s.bg};color:${s.color}"><i class="fas ${s.icon}"></i></div>
      <div class="users-stat-body">
        <div class="users-stat-val">${s.val}</div>
        <div class="users-stat-lbl">${s.lbl}</div>
      </div>
    </div>`).join('');
}

/* ══════════════════════════════════════════════
   FILTER & SEARCH — بحث وفلترة المستخدمين
══════════════════════════════════════════════ */
function applyUsersFilter(){
  const q = (document.getElementById('usersSearchInput')||{value:''}).value.trim().toLowerCase();
  const role = (document.getElementById('usersRoleFilter')||{value:''}).value;
  const clearBtn = document.getElementById('usersSearchClear');
  if(clearBtn) clearBtn.style.display = q ? 'flex' : 'none';

  let filtered = state.users;
  if(role) filtered = filtered.filter(u=>u.role===role);
  if(q)    filtered = filtered.filter(u=>
    u.name.toLowerCase().includes(q) ||
    u.role.toLowerCase().includes(q) ||
    u.dept.toLowerCase().includes(q)
  );
  renderUsers(filtered);
}
function clearUsersSearch(){
  const inp = document.getElementById('usersSearchInput');
  if(inp){ inp.value=''; }
  applyUsersFilter();
}

/* ══════════════════════════════════════════════
   VIEW USER — عرض تفاصيل المستخدم
══════════════════════════════════════════════ */
function viewUser(id){
  const u = state.users.find(x=>x.id===id);
  if(!u) return;
  const roleCls = getRoleBadgeClass(u.role);
  const initials = getAvatarLetters(u.name);
  const statusBadge = u.active
    ? `<span class="sbadge sbadge-active"><span class="sbadge-dot"></span>نشط</span>`
    : `<span class="sbadge sbadge-inactive"><span class="sbadge-dot"></span>معطّل</span>`;
  document.getElementById('viewUserModalContent').innerHTML = `
    <div class="view-user-modal-header">
      <div class="view-user-modal-avatar">${initials}</div>
      <div>
        <div class="view-user-modal-name">${u.name}</div>
        <div class="view-user-modal-role"><span class="rbadge ${roleCls}"><i class="fas ${u.roleIcon}"></i> ${u.role}</span></div>
      </div>
    </div>
    <div class="view-user-modal-grid">
      <div class="view-user-modal-field">
        <div class="view-user-modal-field-label">المصلحة</div>
        <div class="view-user-modal-field-val"><i class="fas fa-sitemap" style="color:var(--accent);margin-left:6px"></i>${u.dept}</div>
      </div>
      <div class="view-user-modal-field">
        <div class="view-user-modal-field-label">الحالة</div>
        <div class="view-user-modal-field-val">${statusBadge}</div>
      </div>
      <div class="view-user-modal-field">
        <div class="view-user-modal-field-label">رقم المعرّف</div>
        <div class="view-user-modal-field-val" style="font-family:'JetBrains Mono',monospace;color:var(--accent2)">USR-${String(u.id).padStart(4,'0')}</div>
      </div>
      <div class="view-user-modal-field">
        <div class="view-user-modal-field-label">الدور الوظيفي</div>
        <div class="view-user-modal-field-val">${u.role}</div>
      </div>
      <div class="view-user-modal-field">
    <div class="view-user-modal-field-label">البريد الإلكتروني</div>
    <div class="view-user-modal-field-val">${u.email || 'غير متوفر'}</div>
</div>

<div class="view-user-modal-field">
    <div class="view-user-modal-field-label">رقم الهاتف</div>
    <div class="view-user-modal-field-val">${u.phone || 'غير متوفر'}</div>
</div>

${u.specialty ?`
<div class="view-user-modal-field">
    <div class="view-user-modal-field-label">التخصص</div>
    <div class="view-user-modal-field-val">${u.specialty}</div>
</div>
 `: ''}

${u.pharmacyType ?` 
<div class="view-user-modal-field">
    <div class="view-user-modal-field-label">نوع الصيدلية</div>
    <div class="view-user-modal-field-val">${u.pharmacyType}</div>
</div>
 `: ''}
    </div>
  `;
  openModal('viewUserModal');
}

/* ══════════════════════════════════════════════
   DELETE USER — حذف مستخدم
══════════════════════════════════════════════ */
function deleteUser(id){

  const u = state.users.find(x => x.id === id);

  if(!u) return;

  if(!confirm(`هل أنت متأكد من حذف حساب "${u.name}"؟\nهذا الإجراء لا يمكن التراجع عنه.`)){
    return;
  }

  fetch('delete_clinic_staff.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'id=' + id
  })
  .then(res => res.json())
  .then(data => {

    if(data.success){

      state.users = state.users.filter(x => x.id !== id);
updateDashboardStats();
fetchAdminNotifications();
      applyUsersFilter();

      addActivity(
        'fa-trash',
        'rgba(239,68,68,.15)',
        '#f87171',
        'حذف مستخدم',
       ` تم حذف حساب <strong>${u.name}</strong> نهائياً `
      );

      showToast(`تم حذف حساب ${u.name} ✓`);

    }else{
      showToast('فشل حذف المستخدم');
    }

  })
  .catch(err => {
    console.error(err);
    showToast('حدث خطأ أثناء الحذف');
  });

}

/* ══════════════════════════════════════════════
   RENDER — جدول المصالح (+ PAGINATION)
══════════════════════════════════════════════ */
function renderServices(list){
  list = list || state.services;
  pagination.services.filtered = list;
  pagination.services.page = 1;
  renderServicesPage();
}

function renderServicesPage(){
  const list  = pagination.services.filtered;
  const p     = pagination.services;
  const tbody = document.getElementById('servicesTableBody');
  const emptyEl = document.getElementById('servicesEmptyState');
  if(!tbody) return;

  if(!list.length){
    tbody.innerHTML='';
    if(emptyEl) emptyEl.style.display='block';
    renderPagination('services',0,'servicesPaginationControls','servicesPaginationInfo','servicesPaginationWrap');
    return;
  }
  if(emptyEl) emptyEl.style.display='none';

  const start = (p.page - 1) * p.perPage;
  const page  = list.slice(start, start + p.perPage);

  tbody.innerHTML = page.map((s, idx)=>{
    const globalIdx = start + idx;
   const initials = s.admin
  ? s.admin.split(' ').slice(0,2).map(w=>w.charAt(0)).join('')
  : '--';
    return `
    <tr style="animation-delay:${idx*0.04}s">
      <td style="color:var(--text-muted);font-size:12px;font-weight:600;width:44px">${globalIdx+1}</td>
      <td>
        <div class="service-name-cell">
          <div class="service-icon-badge" style="background:${s.color}18;border:1px solid ${s.color}28;color:${s.color}">
            <i class="fas ${s.icon}"></i>
          </div>
          <span class="service-name-text">${s.name}</span>
        </div>
      </td>
      <td>
        <div class="service-admin-cell">
          <div class="service-admin-avatar">${initials}</div>
          <span class="service-admin-name">${s.admin || 'غير معين'}</span>
        </div>
      </td>
      <td>
        <span class="service-workers-badge">
          <i class="fas fa-users" style="font-size:11px"></i>
          ${s.workers} عامل
        </span>
      </td>
      <td>
        <div class="service-actions">
          <button class="sact-btn sact-view" onclick="viewService(${s.id})" title="عرض المصلحة">
            <i class="fas fa-eye"></i><span>عرض</span>
          </button>
          <button class="sact-btn sact-edit" onclick="openEditService(${s.id})" title="تعديل المصلحة">
            <i class="fas fa-pen"></i><span>تعديل</span>
          </button>
          <button class="sact-btn ${s.isActive!==false ? 'sact-toggle-off' : 'sact-toggle-on'}" onclick="toggleServiceStatus(${s.id})" title="${s.isActive!==false ? 'تعطيل المصلحة' : 'تفعيل المصلحة'}">
            <i class="fas ${s.isActive!==false ? 'fa-toggle-on' : 'fa-toggle-off'}"></i><span>${s.isActive!==false ? 'تعطيل' : 'تفعيل'}</span>
          </button>
          <button class="sact-btn sact-delete" onclick="deleteService(${s.id})" title="حذف المصلحة">
            <i class="fas fa-trash-can"></i><span>حذف</span>
          </button>
          
        </div>
      </td>
    </tr>`;
  }).join('');

  renderPagination('services', list.length, 'servicesPaginationControls','servicesPaginationInfo','servicesPaginationWrap');
}

function servicesPerPageChanged(){
  pagination.services.perPage = parseInt(document.getElementById('servicesPerPage').value)||10;
  pagination.services.page = 1;
  renderServicesPage();
}

/* ══════════════════════════════════════════════
   ACTIVITY PAGINATION — ترقيم سجل النشاط
══════════════════════════════════════════════ */
function renderActivityPage(){
  const container = document.getElementById('activityListContainer');
  if(!container) return;
  const allItems = Array.from(container.querySelectorAll('.activity-item'));
  const p = pagination.activity;
  const total = allItems.length;

  allItems.forEach((item, i) => {
    const start = (p.page - 1) * p.perPage;
    item.style.display = (i >= start && i < start + p.perPage) ? '' : 'none';
  });

  renderPagination('activity', total, 'activityPaginationControls','activityPaginationInfo','activityPaginationWrap');
}

function activityPerPageChanged(){
  pagination.activity.perPage = parseInt(document.getElementById('activityPerPage').value)||10;
  pagination.activity.page = 1;
  renderActivityPage();
}

/* ══════════════════════════════════════════════
   TOGGLE USER STATUS — تعطيل / تفعيل
══════════════════════════════════════════════ */
function toggleUserStatus(id){

  const u = state.users.find(x => x.id === id);
  if(!u) return;

  fetch('toggle_staff_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'id=' + encodeURIComponent(id)
  })
  .then(res => res.json())
  .then(data => {

    if(!data.success){
      showToast('❌ ' + data.message);
      return;
    }

    u.active = (data.status === 'active');
updateDashboardStats();
fetchAdminNotifications();
    applyUsersFilter();

    addActivity(
      u.active ? 'fa-lock-open' : 'fa-lock',
      u.active ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)',
      u.active ? '#34d399' : '#f87171',
      u.active ? 'تفعيل حساب' : 'تعطيل حساب',
     ` ${u.active ? 'تم تفعيل' : 'تم تعطيل'} حساب <strong>${u.name}</strong>`
    );

    showToast(
      u.active
       ?` تم تفعيل حساب ${u.name} ✓`
        :` تم تعطيل حساب ${u.name}`
    );

  })
  .catch(err => {
    console.error(err);
    showToast('❌ حدث خطأ أثناء تحديث حالة الحساب');
  });
}
/* ══════════════════════════════════════════════
   OPEN EDIT USER — فتح modal التعديل
══════════════════════════════════════════════ */
function toggleEditPharmacyTypeField(){
  const field = document.getElementById('editUserPharmacyTypeField');
  if(!field) return;
  const role = document.getElementById('editUserRole').value;
  field.style.display = (role === 'صيدلي' || role === 'صيدلانية') ? '' : 'none';
}

function openEditUser(id){
  const u = state.users.find(x=>x.id===id);
  if(!u) return;
  state.editingUserId = id;
  // ملء الحقول
  document.getElementById('editUserName').value  = u.name;
  document.getElementById('editUserRole').value  = u.role;
  document.getElementById('editUserDept').value  = u.dept;
  document.getElementById('editUserEmail').value = u.email || '';
document.getElementById('editUserPhone').value = u.phone || '';
document.getElementById('editUserSpecialty').value = u.specialty || '';
{
  const ptSel = document.getElementById('editUserPharmacyType');
  ptSel.value = u.pharmacyType || '';
  // لا توجد قيمة محفوظة مطابقة → يظهر الـ Placeholder
  if(ptSel.selectedIndex === -1) ptSel.selectedIndex = 0;
}
  toggleEditPharmacyTypeField();
  window.currentEditUserId = id;
  document.getElementById('passwordResetMessage').style.display = 'none';

openModal('editUserModal');

}

function saveEditUser(){
  const u = state.users.find(x=>x.id===state.editingUserId);
  if(!u) return;
  // جمع جميع القيم المعدلة
  const newName         = document.getElementById('editUserName').value.trim();
  const newRole         = document.getElementById('editUserRole').value.trim();
  const newDept         = document.getElementById('editUserDept').value.trim();
  const newEmail        = document.getElementById('editUserEmail').value.trim();
  const newPhone        = document.getElementById('editUserPhone').value.trim();
  const newSpecialty    = document.getElementById('editUserSpecialty').value.trim();
  const newPharmacyType = document.getElementById('editUserPharmacyType').value;
  if(!newName){ showToast('⚠ الاسم مطلوب'); return; }

  // تحويل الدور العربي إلى قيمة ENUM المطابقة في قاعدة البيانات (نفس خريطة الإضافة)
  const roleMap = {
    'طبيب':'doctor','ممرض/ة':'nurse','ممرض':'nurse','ممرضة':'nurse',
    'مخبر':'lab_technician','أشعة':'radiology_technician',
    'صيدلي':'pharmacist','صيدلانية':'pharmacist','صيدلية':'pharmacist',
    'موظف استقبال':'receptionist','Service Admin':'service_admin'
  };
  let roleForDB = roleMap[newRole];
  if(!roleForDB) roleForDB = (newRole === u.role) ? (u.originalRole || newRole) : newRole;

  const roleIcons = {
    'طبيب':'fa-user-doctor','ممرضة':'fa-user-nurse','ممرض':'fa-user-nurse',
    'مخبر':'fa-flask','أشعة':'fa-x-ray','صيدلانية':'fa-pills','صيدلي':'fa-pills',
    'Service Admin':'fa-desktop','موظف':'fa-id-badge','موظف / سكريتارية':'fa-id-badge','موظف استقبال':'fa-headset'
  };

  // إرسال القيم إلى ملف التحديث في الخادم
  fetch('update_clinic_staff.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      id:            u.id,
      full_name:     newName,
      email:         newEmail,
      phone:         newPhone,
      role:          roleForDB,
      specialty:     newSpecialty,
      pharmacy_type: newPharmacyType,
      department:    newDept
    })
  })
  .then(res => res.json())
  .then(data => {
    if(!data.success){
      showToast('❌ ' + (data.message || 'تعذّر حفظ التعديلات'));
      return;
    }
    // تحديث الحالة في الذاكرة بعد نجاح الحفظ في قاعدة البيانات
    u.name         = newName;
    u.role         = newRole;
    u.originalRole = roleForDB;
    u.roleIcon     = roleIcons[newRole] || 'fa-user';
    u.dept         = newDept;
    u.email        = newEmail;
    u.phone        = newPhone;
    u.specialty    = newSpecialty;
    u.pharmacyType = newPharmacyType;

    applyUsersFilter();
    closeModal('editUserModal');
    addActivity('fa-pen','rgba(14,165,233,.15)','#38bdf8','تعديل مستخدم',`تم تعديل بيانات <strong>${newName}</strong>`);
    showToast(`تم حفظ بيانات ${newName} ✓`);
  })
  .catch(err => {
    console.error(err);
    showToast('❌ حدث خطأ أثناء حفظ التعديلات');
  });
}
function resetUserPassword() {

    if (!window.currentEditUserId) {
showToast('❌ لم يتم تحديد المستخدم');
        return;
    }

    
const btn = document.querySelector('#editUserModal button[onclick="resetUserPassword()"]');

btn.disabled = true;
btn.innerHTML = '⏳ جاري إعادة التعيين...';
    fetch('reset_user_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: window.currentEditUserId
        })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

   showToast('✅ تم إعادة تعيين كلمة المرور وإرسالها إلى البريد الإلكتروني للمستخدم');

    //document.getElementById('passwordResetMessage').style.display = 'block';//
btn.disabled = false;
btn.innerHTML = '🔑 إعادة تعيين كلمة السر';
} else {

    showToast('❌ ' + (data.message || 'حدث خطأ'));

}
    })
   .catch(err => {
    btn.disabled = false;
btn.innerHTML = '🔑 إعادة تعيين كلمة السر';
    console.error(err);
    showToast('❌ فشل الاتصال بالخادم');
});

}
/* ══════════════════════════════════════════════
   ADD USER — إضافة مستخدم جديد
══════════════════════════════════════════════ */
function addUser(){
  const name  = document.getElementById('newUserName').value.trim();
  const email = document.getElementById('newUserEmail').value.trim();
  const role  = document.getElementById('newUserRole').value;
  const hasDept = document.getElementById('hasDeptYes').checked;
  const deptName = hasDept ? (document.getElementById('newUserDeptName').value.trim()) : '';
  const phone = document.getElementById('newUserPhone').value.trim();
  const password = document.getElementById('newUserPassword').value.trim();
  const specialty = (document.getElementById('newUserSpecialty')||{}).value?.trim() || '';
  const pharmacyType = (role === 'صيدلية') ? (document.getElementById('newUserPharmacyType')?.value || '') : '';
  // التحقق من إدخال اسم المصلحة إذا اختار "نعم"
  if(hasDept && !deptName){
    showToast('⚠ يرجى اختيار المصلحة');
    return;
  }
  if(!password){
    showToast('⚠ كلمة المرور مطلوبة');
    return;
  }
  if(!name){ showToast('⚠ الاسم مطلوب'); return; }
  // تحويل الدور العربي إلى قيمة ENUM المطابقة في قاعدة البيانات
  const roleMap = {
    'طبيب':              'doctor',
    'ممرض/ة':            'nurse',
    'ممرض':              'nurse',
    'مخبر':              'lab_technician',
    'أشعة':              'radiology_technician',
    'صيدلي':             'pharmacist',
    'صيدلية':            'pharmacist',
    'موظف استقبال':      'receptionist',
    'Service Admin':     'service_admin'
  };
  const roleForDB = roleMap[role] || role;
  const roleIcons = {
    'طبيب':'fa-user-doctor','ممرض/ة':'fa-user-nurse','ممرض':'fa-user-nurse','مخبر':'fa-flask',
    'أشعة':'fa-x-ray','صيدلية':'fa-pills','صيدلي':'fa-pills','Service Admin':'fa-desktop',
    'موظف استقبال':'fa-headset'
  };
  
  const btn = document.querySelector('#addUserModal .btn-primary');

btn.disabled = true;
btn.innerHTML = '⏳ يرجى الانتظار...';
  fetch('save_clinic_staff.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        full_name: name,
        email: email,
        phone: phone,
        password: password,
        role: roleForDB,
        specialty: specialty,
        pharmacy_type: pharmacyType,
        dept_name: deptName
    })
})
.then(response => response.json())
.then(data => {
    console.log(data);

    if (!data.success) {
        showToast('❌ ' + data.message);
        return;
    }
btn.disabled = false;
btn.innerHTML = 'إضافة المستخدم';

showToast('✅ تم إنشاء الحساب وإرسال بيانات الدخول إلى البريد الإلكتروني');
   setTimeout(() => {
    loadClinicStaff();
    loadServices();
}, 500);
    ['newUserName','newUserEmail','newUserPhone','newUserPassword','newUserSpecialty']
        .forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
    const ptEl = document.getElementById('newUserPharmacyType');
    if(ptEl) ptEl.selectedIndex = 0;

    closeModal('addUserModal');
    applyUsersFilter();

    addActivity(
        'fa-user-plus',
        'rgba(99,102,241,.15)',
        '#a5b4fc',
        'إضافة مستخدم جديد',
       ` تم إضافة <strong>${name}</strong> بصلاحية ${role} في مصلحة ${deptName}`
    );

   showToast('✅ تم إنشاء الحساب وإرسال بيانات الدخول إلى البريد الإلكتروني');
})
.catch(error => {
  btn.disabled = false;
btn.innerHTML = 'إضافة المستخدم';
    console.error(error);
    alert(error);
    showToast('❌ حدث خطأ أثناء الحفظ');
});
 
}

/* ══════════════════════════════════════════════
   OPEN EDIT SERVICE — فتح modal تعديل مصلحة
══════════════════════════════════════════════ */
function onEditRoomDistChange(){
  const val = document.querySelector('input[name="editRoomDistType"]:checked')?.value || 'wings';
  document.getElementById('editRoomFieldsWings').style.display  = val==='wings'  ? 'flex' : 'none';
  document.getElementById('editRoomFieldsShared').style.display = val==='shared' ? 'flex' : 'none';
  calcEditTotals();
}

function calcEditTotals(){
  const val = document.querySelector('input[name="editRoomDistType"]:checked')?.value || 'wings';
  let totalRooms=0, totalBeds=0;
  if(val==='wings'){
    const mr = parseInt(document.getElementById('editMenRooms').value)       || 0;
    const mb = parseInt(document.getElementById('editMenBedsPerRoom').value)  || 0;
    const wr = parseInt(document.getElementById('editWomenRooms').value)      || 0;
    const wb = parseInt(document.getElementById('editWomenBedsPerRoom').value)|| 0;
    totalRooms = mr+wr; totalBeds = (mr*mb)+(wr*wb);
  } else {
    const sr = parseInt(document.getElementById('editSharedRooms').value)       || 0;
    const sb = parseInt(document.getElementById('editSharedBedsPerRoom').value)  || 0;
    totalRooms = sr; totalBeds = sr*sb;
  }
  document.getElementById('editTotalRoomsDisplay').textContent = totalRooms;
  document.getElementById('editTotalBedsDisplay').textContent  = totalBeds;
}

function openEditService(id){
  const s = state.services.find(x=>x.id===id);
  if(!s) return;
  state.editingServiceId = id;

  // الحقول الأساسية
  document.getElementById('editServiceName').value    = s.name;

 

  // تحميل hasRooms
  const hasRooms = s.hasRooms === true;
  setHasRooms('edit', hasRooms ? 'yes' : 'no');

  if(hasRooms){
    // نوع التوزيع
    const rd = s.roomData || {distType:'wings'};
    const isWings = rd.distType !== 'shared';
    document.querySelector('input[name="editRoomDistType"][value="wings"]').checked  = isWings;
    document.querySelector('input[name="editRoomDistType"][value="shared"]').checked = !isWings;

    // إظهار/إخفاء الحقول
    document.getElementById('editRoomFieldsWings').style.display  = isWings  ? 'flex' : 'none';
    document.getElementById('editRoomFieldsShared').style.display = !isWings ? 'flex' : 'none';

    // ملء القيم
    if(isWings){
      document.getElementById('editMenRooms').value        = rd.menRooms        || 0;
      document.getElementById('editMenBedsPerRoom').value  = rd.menBedsPerRoom  || 0;
      document.getElementById('editWomenRooms').value      = rd.womenRooms      || 0;
      document.getElementById('editWomenBedsPerRoom').value= rd.womenBedsPerRoom|| 0;
    } else {
      document.getElementById('editSharedRooms').value       = rd.sharedRooms       || 0;
      document.getElementById('editSharedBedsPerRoom').value = rd.sharedBedsPerRoom || 0;
    }
    calcEditTotals();
  }
  openModal('editServiceModal');
}

function saveEditService(){
  const s = state.services.find(x=>x.id===state.editingServiceId);
  if(!s) return;
  const newName    = document.getElementById('editServiceName').value.trim();
  
  if(!newName){ showToast('⚠ اسم المصلحة مطلوب'); return; }

  const hasRooms = document.querySelector('input[name="editHasRooms"]:checked')?.value === 'yes';
  let roomData = {distType:'shared', sharedRooms:0, sharedBedsPerRoom:0};
  let totalRooms = 0, totalBeds = 0;

  if(hasRooms){
    const distType = document.querySelector('input[name="editRoomDistType"]:checked')?.value || 'wings';
    if(distType === 'wings'){
      roomData = {
        distType: 'wings',
        menRooms:        parseInt(document.getElementById('editMenRooms').value)        || 0,
        menBedsPerRoom:  parseInt(document.getElementById('editMenBedsPerRoom').value)  || 0,
        womenRooms:      parseInt(document.getElementById('editWomenRooms').value)      || 0,
        womenBedsPerRoom:parseInt(document.getElementById('editWomenBedsPerRoom').value)|| 0,
      };
    } else {
      roomData = {
        distType: 'shared',
        sharedRooms:       parseInt(document.getElementById('editSharedRooms').value)       || 0,
        sharedBedsPerRoom: parseInt(document.getElementById('editSharedBedsPerRoom').value) || 0,
      };
    }
    totalRooms = parseInt(document.getElementById('editTotalRoomsDisplay').textContent) || 0;
    totalBeds  = parseInt(document.getElementById('editTotalBedsDisplay').textContent)  || 0;
  }

  s.name       = newName;


  s.hasRooms   = hasRooms;
  s.roomData   = roomData;
  s.totalRooms = totalRooms;
  s.totalBeds  = totalBeds;

  // ── إرسال التعديلات إلى قاعدة البيانات ──
  fetch('update_service.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      id:          s.id,
      name:        newName,
      has_rooms:   hasRooms ? 1 : 0,
      room_data:   JSON.stringify(roomData),
      total_rooms: totalRooms,
      total_beds:  totalBeds
    })
  })
  .then(r => r.json())
  .then(data => {
    if(!data.success) showToast('❌ ' + (data.message || 'فشل حفظ التعديلات'));
  })
  .catch(() => showToast('❌ خطأ في الاتصال بالخادم'));

  closeModal('editServiceModal');
  renderServices();
  addActivity('fa-pen','rgba(14,165,233,.15)','#38bdf8','تعديل مصلحة',`تم تعديل بيانات مصلحة <strong>${s.name}</strong>`);
  showToast(`تم حفظ بيانات مصلحة ${s.name} ✓`);
}

/* ══════════════════════════════════════════════
   VIEW SERVICE — عرض تفاصيل المصلحة
══════════════════════════════════════════════ */
function viewService(id){
  const s = state.services.find(x=>x.id===id);
  if(!s) return;

  const workers = state.users.filter(u => u.dept === s.name);
const created = s.createdAt || s.created_at || '—'; 

const rd = s.roomData
    ? s.roomData
    : (s.room_data ? JSON.parse(s.room_data) : {});

  const workersHTML = workers.length
    ? workers.map(u => {
        const initials = u.name.replace(/^د\.\s*/,'').split(' ').slice(0,2).map(w=>w.charAt(0)).join('');
        return `
        <div class="vsm-worker-row">
          <div class="vsm-worker-avatar">${initials}</div>
          <div class="vsm-worker-info">
            <div class="vsm-worker-name">${u.name}</div>
            <div class="vsm-worker-role">${u.role}</div>
          </div>
          <span class="sbadge ${u.active ? 'sbadge-active' : 'sbadge-inactive'}" style="font-size:10.5px;padding:3px 9px">
            <span class="sbadge-dot"></span>${u.active ? 'نشط' : 'معطّل'}
          </span>
        </div>`;
      }).join('')
    : `<div class="vsm-no-workers"><i class="fas fa-users-slash"></i>لا يوجد موظفون مسجّلون في هذه المصلحة</div>`;

  // ── قسم معلومات الإقامة ──
  let residenceHTML = '';
const hasRooms =
    s.hasRooms === true ||
    s.has_rooms == 1;

  if(!hasRooms){
    residenceHTML = `
    <div class="vsm-section-title" style="margin-top:16px"><i class="fas fa-bed" style="color:var(--accent)"></i> معلومات الإقامة</div>
    <div style="display:flex;align-items:center;gap:12px;padding:16px 18px;border:1.5px dashed rgba(100,116,139,.3);border-radius:12px;background:rgba(100,116,139,.05);margin-bottom:14px">
      <div style="width:38px;height:38px;border-radius:9px;background:rgba(100,116,139,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;color:#64748b"><i class="fas fa-ban"></i></div>
      <div>
        <div style="font-size:13px;font-weight:700;color:var(--text-muted)">هذه المصلحة لا تحتوي على غرف إقامة</div>
        <div style="font-size:11.5px;color:var(--text-dim);margin-top:3px">مصلحة خدمية — لا تشتمل على أسرّة أو أجنحة للمرضى</div>
      </div>
    </div>`;
  } else if(rd.distType === 'wings'){
    const mr  = rd.menRooms        || 0;
    const mb  = rd.menBedsPerRoom  || 0;
    const wr  = rd.womenRooms       || 0;
    const wb  = rd.womenBedsPerRoom || 0;
    const totalR = mr + wr;
    const totalB = (mr * mb) + (wr * wb);

    residenceHTML = `
    <div class="vsm-section-title" style="margin-top:16px"><i class="fas fa-bed" style="color:var(--accent)"></i> معلومات الإقامة</div>
    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">

      <!-- جناح الرجال -->
      <div style="border-radius:11px;padding:13px 15px;border:1.5px solid rgba(96,165,250,.25);background:rgba(96,165,250,.05)">
        <div style="font-size:12px;font-weight:700;color:#93c5fd;display:flex;align-items:center;gap:7px;margin-bottom:10px">
          <i class="fas fa-mars"></i> جناح الرجال
          <span style="margin-right:auto;font-size:11px;padding:2px 9px;border-radius:20px;background:${mr>0?'rgba(96,165,250,.15)':'rgba(100,116,139,.12)'};color:${mr>0?'#93c5fd':'#64748b'}">${mr>0?'متوفر':'غير متوفر'}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-door-open"></i> عدد الغرف</div><div class="vsm-stat-value">${mr}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-bed"></i> أسرّة / غرفة</div><div class="vsm-stat-value">${mb}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-door-open"></i> إجمالي الغرف</div><div class="vsm-stat-value" style="color:var(--accent2)">${mr}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-bed"></i> إجمالي الأسرّة</div><div class="vsm-stat-value" style="color:var(--accent2)">${mr*mb}</div></div>
        </div>
      </div>

      <!-- جناح النساء -->
      <div style="border-radius:11px;padding:13px 15px;border:1.5px solid rgba(244,114,182,.25);background:rgba(244,114,182,.05)">
        <div style="font-size:12px;font-weight:700;color:#f9a8d4;display:flex;align-items:center;gap:7px;margin-bottom:10px">
          <i class="fas fa-venus"></i> جناح النساء
          <span style="margin-right:auto;font-size:11px;padding:2px 9px;border-radius:20px;background:${wr>0?'rgba(244,114,182,.15)':'rgba(100,116,139,.12)'};color:${wr>0?'#f9a8d4':'#64748b'}">${wr>0?'متوفر':'غير متوفر'}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-door-open"></i> عدد الغرف</div><div class="vsm-stat-value">${wr}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-bed"></i> أسرّة / غرفة</div><div class="vsm-stat-value">${wb}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-door-open"></i> إجمالي الغرف</div><div class="vsm-stat-value" style="color:var(--accent2)">${wr}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-bed"></i> إجمالي الأسرّة</div><div class="vsm-stat-value" style="color:var(--accent2)">${wr*wb}</div></div>
        </div>
      </div>

      <!-- الإجمالي -->
      <div class="room-totals-bar" style="margin-top:0">
        <div class="room-total-item">
          <i class="fas fa-door-open"></i>
          <div><div class="room-total-label">إجمالي الغرف</div><div class="room-total-value">${totalR}</div></div>
        </div>
        <div class="room-total-divider"></div>
        <div class="room-total-item">
          <i class="fas fa-bed"></i>
          <div><div class="room-total-label">إجمالي الأسرّة</div><div class="room-total-value">${totalB}</div></div>
        </div>
      </div>
    </div>`;

  } else if(rd.distType === 'shared'){
    const sr = rd.sharedRooms       || 0;
    const sb = rd.sharedBedsPerRoom || 0;
    residenceHTML = `
    <div class="vsm-section-title" style="margin-top:16px"><i class="fas fa-bed" style="color:var(--accent)"></i> معلومات الإقامة</div>
    <div style="margin-bottom:14px">
      <div style="border-radius:11px;padding:13px 15px;border:1.5px solid rgba(52,211,153,.25);background:rgba(52,211,153,.05);margin-bottom:10px">
        <div style="font-size:12px;font-weight:700;color:#6ee7b7;display:flex;align-items:center;gap:7px;margin-bottom:10px">
          <i class="fas fa-door-closed"></i> الغرف المشتركة
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-door-open"></i> عدد الغرف</div><div class="vsm-stat-value">${sr}</div></div>
          <div class="vsm-stat"><div class="vsm-stat-label"><i class="fas fa-bed"></i> أسرّة / غرفة</div><div class="vsm-stat-value">${sb}</div></div>
        </div>
      </div>
      <div class="room-totals-bar" style="margin-top:0">
        <div class="room-total-item">
          <i class="fas fa-door-open"></i>
          <div><div class="room-total-label">إجمالي الغرف</div><div class="room-total-value">${sr}</div></div>
        </div>
        <div class="room-total-divider"></div>
        <div class="room-total-item">
          <i class="fas fa-bed"></i>
          <div><div class="room-total-label">إجمالي الأسرّة</div><div class="room-total-value">${sr*sb}</div></div>
        </div>
      </div>
    </div>`;
  }

  document.getElementById('viewServiceContent').innerHTML = `
    <div class="vsm-hero">
      <div class="vsm-hero-bg" style="background:${s.color}"></div>
      <div class="vsm-hero-content">
        <div class="vsm-hero-icon" style="background:${s.color}20;border:2px solid ${s.color}35;color:${s.color}">
          <i class="fas ${s.icon}"></i>
        </div>
        <div class="vsm-hero-text">
          <div class="vsm-hero-name">${s.name}</div>
          <span class="vsm-hero-id">SVC-${String(s.id).padStart(3,'0')}</span>
         <div>
  <span
    style="
      padding:4px 10px;
      border-radius:20px;
      font-size:12px;
      font-weight:700;
      background:${s.isActive ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)'};
      color:${s.isActive ? '#34d399' : '#f87171'};
    ">
    ${s.isActive ? 'نشطة' : 'معطلة'}
  </span>
</div>
        </div>
      </div>
    </div>

    <div class="vsm-stats">
      <div class="vsm-stat">
        <div class="vsm-stat-label"><i class="fas fa-desktop"></i> المسؤول</div>
        <div class="vsm-stat-value">${s.admin}</div>
      </div>
      <div class="vsm-stat">
        <div class="vsm-stat-label"><i class="fas fa-users"></i> إجمالي العاملين</div>
        <div class="vsm-stat-value">${s.workers} عامل</div>
      </div>
      <div class="vsm-stat">
        <div class="vsm-stat-label"><i class="fas fa-calendar-plus"></i> تاريخ الإنشاء</div>
        <div class="vsm-stat-value">${created}</div>
      </div>
      <div class="vsm-stat">
        <div class="vsm-stat-label"><i class="fas fa-circle-check"></i> في النظام</div>
        <div class="vsm-stat-value" style="color:#34d399">${workers.filter(u=>u.active).length} نشط</div>
      </div>
    </div>

    ${residenceHTML}

    <div class="vsm-section-title"><i class="fas fa-id-badge" style="color:var(--accent)"></i> قائمة العاملين المرتبطين</div>
    <div class="vsm-workers-list">${workersHTML}</div>
  `;
  openModal('viewServiceModal');
}

/* ══════════════════════════════════════════════
   DELETE SERVICE — حذف مصلحة (مع نافذة تأكيد)
══════════════════════════════════════════════ */
function deleteService(id){
  const s = state.services.find(x=>x.id===id);
  if(!s) return;
  // حفظ الـ id للتأكيد لاحقاً
  state.pendingDeleteServiceId = id;

  // تعبئة نافذة التأكيد
  document.getElementById('confirmDeleteServiceIcon').className = `fas ${s.icon}`;
  document.getElementById('confirmDeleteServiceIconWrap').style.cssText = `background:${s.color}18;border:1px solid ${s.color}28;color:${s.color}`;
  document.getElementById('confirmDeleteServiceName').textContent = s.name;
  openModal('confirmDeleteServiceModal');

}

function confirmDeleteServiceNow(){
  const id = state.pendingDeleteServiceId;
  const s = state.services.find(x=>x.id===id);
  if(!s){ closeModal('confirmDeleteServiceModal'); return; }

  fetch('delete_service.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id: id })
  })
  .then(r => r.json())
  .then(data => {
    if(!data.success){
      showToast('❌ ' + (data.message || 'فشل الحذف'));
      return;
    }
    state.services = state.services.filter(x=>x.id!==id);
   fetchAdminNotifications();
    updateDashboardStats();
    state.pendingDeleteServiceId = null;
    closeModal('confirmDeleteServiceModal');
    renderServices();
    addActivity('fa-trash','rgba(239,68,68,.15)','#f87171','حذف مصلحة',`تم حذف مصلحة <strong>${s.name}</strong> نهائياً`);
    showToast(`تم حذف مصلحة ${s.name}`);
  })
  .catch(() => showToast('❌ خطأ في الاتصال بالخادم'));
}

/* ══════════════════════════════════════════════
   HAS ROOMS TOGGLE — تبديل وجود الغرف
══════════════════════════════════════════════ */
function setHasRooms(ctx, val){
  if(ctx === 'add'){
    const row     = document.getElementById('addHasRoomsRow');
    const section = document.getElementById('addRoomSection');
    const btnYes  = document.getElementById('addHasRoomsBtnYes');
    const btnNo   = document.getElementById('addHasRoomsBtnNo');
    const radioYes = document.querySelector('input[name="addHasRooms"][value="yes"]');
    const radioNo  = document.querySelector('input[name="addHasRooms"][value="no"]');
    if(val === 'yes'){
      section.style.display = 'block';
      row.classList.add('is-yes');
      btnYes.classList.remove('selected-no'); btnYes.classList.add('selected-yes');
      btnNo.classList.remove('selected-yes'); btnNo.classList.remove('selected-no');
      if(radioYes) radioYes.checked = true;
    } else {
      section.style.display = 'none';
      row.classList.remove('is-yes');
      btnNo.classList.add('selected-no'); btnNo.classList.remove('selected-yes');
      btnYes.classList.remove('selected-yes'); btnYes.classList.remove('selected-no');
      if(radioNo) radioNo.checked = true;
    }
  } else if(ctx === 'edit'){
    const row     = document.getElementById('editHasRoomsRow');
    const section = document.getElementById('editRoomSection');
    const btnYes  = document.getElementById('editHasRoomsBtnYes');
    const btnNo   = document.getElementById('editHasRoomsBtnNo');
    const radioYes = document.querySelector('input[name="editHasRooms"][value="yes"]');
    const radioNo  = document.querySelector('input[name="editHasRooms"][value="no"]');
    if(val === 'yes'){
      section.style.display = 'block';
      row.classList.add('is-yes');
      btnYes.classList.remove('selected-no'); btnYes.classList.add('selected-yes');
      btnNo.classList.remove('selected-yes'); btnNo.classList.remove('selected-no');
      if(radioYes) radioYes.checked = true;
    } else {
      section.style.display = 'none';
      row.classList.remove('is-yes');
      btnNo.classList.add('selected-no'); btnNo.classList.remove('selected-yes');
      btnYes.classList.remove('selected-yes'); btnYes.classList.remove('selected-no');
      if(radioNo) radioNo.checked = true;
    }
  }
}

/* ══════════════════════════════════════════════
   ADD SERVICE — إضافة مصلحة جديدة
══════════════════════════════════════════════ */
function onRoomDistChange(){
  const val = document.querySelector('input[name="roomDistType"]:checked').value;
  const wingsBlock  = document.getElementById('roomFieldsWings');
  const sharedBlock = document.getElementById('roomFieldsShared');
  if(val === 'wings'){
    wingsBlock.style.display  = 'flex';
    sharedBlock.style.display = 'none';
  } else {
    wingsBlock.style.display  = 'none';
    sharedBlock.style.display = 'flex';
  }
  calcTotals();
}

function calcTotals(){
  const val = document.querySelector('input[name="roomDistType"]:checked')?.value || 'wings';
  let totalRooms = 0, totalBeds = 0;
  if(val === 'wings'){
    const mr  = parseInt(document.getElementById('menRooms').value)      || 0;
    const mb  = parseInt(document.getElementById('menBedsPerRoom').value) || 0;
    const wr  = parseInt(document.getElementById('womenRooms').value)     || 0;
    const wb  = parseInt(document.getElementById('womenBedsPerRoom').value)|| 0;
    totalRooms = mr + wr;
    totalBeds  = (mr * mb) + (wr * wb);
  } else {
    const sr  = parseInt(document.getElementById('sharedRooms').value)      || 0;
    const sb  = parseInt(document.getElementById('sharedBedsPerRoom').value) || 0;
    totalRooms = sr;
    totalBeds  = sr * sb;
  }
  document.getElementById('totalRoomsDisplay').textContent = totalRooms;
  document.getElementById('totalBedsDisplay').textContent  = totalBeds;
}

function addService(){
  const name  = document.getElementById('newServiceName').value.trim();
 

  if(!name){
    showToast('⚠ اسم المصلحة مطلوب');
    return;
  }

  const hasRooms = document.querySelector('input[name="addHasRooms"]:checked')?.value === 'yes';

  let roomData = {
    distType: 'shared',
    sharedRooms: 0,
    sharedBedsPerRoom: 0
  };

  let totalRooms = 0;
  let totalBeds = 0;

  if(hasRooms){
    const distType = document.querySelector('input[name="roomDistType"]:checked')?.value || 'wings';

    if(distType === 'wings'){
      roomData = {
        distType: 'wings',
        menRooms: parseInt(document.getElementById('menRooms').value) || 0,
        menBedsPerRoom: parseInt(document.getElementById('menBedsPerRoom').value) || 0,
        womenRooms: parseInt(document.getElementById('womenRooms').value) || 0,
        womenBedsPerRoom: parseInt(document.getElementById('womenBedsPerRoom').value) || 0
      };
    } else {
      roomData = {
        distType: 'shared',
        sharedRooms: parseInt(document.getElementById('sharedRooms').value) || 0,
        sharedBedsPerRoom: parseInt(document.getElementById('sharedBedsPerRoom').value) || 0
      };
    }

    totalRooms = parseInt(document.getElementById('totalRoomsDisplay').textContent) || 0;
    totalBeds  = parseInt(document.getElementById('totalBedsDisplay').textContent) || 0;
  }

  fetch('save_service.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
 body: new URLSearchParams({
    name: name,
    has_rooms: hasRooms ? 1 : 0,
    room_data: JSON.stringify(roomData),
    total_rooms: totalRooms,
    total_beds: totalBeds
})
  })
  .then(response => response.json())
  .then(data => {

    if(!data.success){
      showToast('❌ ' + data.message);
      return;
    }

    const newService = {
      id: data.service_id,
      name,
      icon: 'fa-hospital',
      color: '#0ea5e9',
      
      workers: 0,
      createdAt: new Date().toLocaleDateString(
        'ar-DZ',
        {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        }
      ).replace(/\//g,'/'),
      hasRooms,
      roomData,
      totalRooms,
      totalBeds
    };

    state.services.push(newService);

    // مسح الحقول
    document.getElementById('newServiceName').value = '';

    [
      'menRooms',
      'menBedsPerRoom',
      'womenRooms',
      'womenBedsPerRoom',
      'sharedRooms',
      'sharedBedsPerRoom'
    ].forEach(id => {
      const el = document.getElementById(id);
      if(el) el.value = 0;
    });

    document.getElementById('totalRoomsDisplay').textContent = '0';
    document.getElementById('totalBedsDisplay').textContent = '0';

    // إعادة الإعدادات الافتراضية
    setHasRooms('add', 'no');

    const defaultRadio = document.querySelector(
      'input[name="roomDistType"][value="wings"]'
    );

    if(defaultRadio){
      defaultRadio.checked = true;
      onRoomDistChange();
    }

    closeModal('addServiceModal');

    renderServices();
    fetchAdminNotifications();
    updateDashboardStats();

    const roomMsg = hasRooms
      ?` ${totalRooms} غرفة / ${totalBeds} سرير`
      : 'بدون غرف إقامة';

    addActivity(
      'fa-sitemap',
      'rgba(16,185,129,.15)',
      '#34d399',
      'إنشاء مصلحة جديدة',
     ` تم إنشاء مصلحة <strong>${name}</strong> — ${roomMsg}`
    );

    showToast(`تم إضافة مصلحة ${name} ✓`);

  })
  .catch(error => {
    console.error(error);
    showToast('❌ حدث خطأ أثناء حفظ المصلحة');
  });
}
/* ══════════════════════════════════════════════
   CLINIC SETTINGS — حفظ إعدادات العيادة
══════════════════════════════════════════════ */

// ── رفع الشعار ──
function handleClinicLogoUpload(input){
  const file = input.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = function(e){
    const base64 = e.target.result;
    applyClinicLogo(base64);
    // حفظ الشعار فوراً في localStorage
    localStorage.setItem('mcg_clinic_logo', base64);
  };
  reader.readAsDataURL(file);
}

// ── تطبيق الشعار على الأفاتار وصندوق الرفع ──
function applyClinicLogo(base64){
  if(!base64) return;
  // الأفاتار في البطاقة الجانبية
  const avatarIcon = document.getElementById('clinicAvatarIcon');
  const avatarImg  = document.getElementById('clinicAvatarImg');
  if(avatarIcon && avatarImg){
    avatarIcon.style.display = 'none';
    avatarImg.src = base64;
    avatarImg.style.display = 'block';
  }
  // معاينة داخل صندوق الرفع
  const preview = document.getElementById('clinicLogoPreview');
  const icon    = document.getElementById('clinicLogoIcon');
  const span    = document.getElementById('clinicLogoSpan');
  if(preview){
    preview.src = base64;
    preview.style.display = 'block';
  }
  if(icon)  icon.style.display  = 'none';
  if(span)  span.style.display  = 'none';
}

function saveClinicSettings(){
  // ── جمع قيم الحقول ──
  const name      = (document.getElementById('clinicName')      || {}).value || '';
  const phone     = (document.getElementById('clinicPhone')     || {}).value || '';
  const address   = (document.getElementById('clinicAddress')   || {}).value || '';
  const timeStart = (document.getElementById('clinicTimeStart') || {}).value || '';
  const timeEnd   = (document.getElementById('clinicTimeEnd')   || {}).value || '';

  // ── الحفظ في localStorage ──
  const clinicData = { name, phone, address, timeStart, timeEnd };
  localStorage.setItem('mcg_clinic_info', JSON.stringify(clinicData));

  // ── تحديث بطاقة الملف الشخصي فوراً ──
  applyClinicInfoToUI(clinicData);

  // ── حفظ إعدادات الهيكل أيضاً ──
  applyStructureSettings(true);

  showToast('تم حفظ إعدادات العيادة ✓');
  addActivity('fa-gear','rgba(14,165,233,.15)','#38bdf8','تعديل إعدادات العيادة','تم تحديث بيانات وإعدادات العيادة');
}

// ── تطبيق بيانات العيادة على واجهة المستخدم ──
function applyClinicInfoToUI(data){
  if(!data) return;
  const profileName = document.getElementById('clinicProfileName');
  const profileSub  = document.getElementById('clinicProfileSub');
  if(profileName && data.name)    profileName.textContent = data.name;
  if(profileSub  && data.address) profileSub.textContent  = data.address;
}

// ── القراءة من localStorage عند التحميل ──
function loadClinicSettings(){
  const saved = localStorage.getItem('mcg_clinic_info');
  if(saved){
    try {
      const data = JSON.parse(saved);
      if(data.name      && document.getElementById('clinicName'))      document.getElementById('clinicName').value      = data.name;
      if(data.phone     && document.getElementById('clinicPhone'))     document.getElementById('clinicPhone').value     = data.phone;
      if(data.address   && document.getElementById('clinicAddress'))   document.getElementById('clinicAddress').value   = data.address;
      if(data.timeStart && document.getElementById('clinicTimeStart')) document.getElementById('clinicTimeStart').value = data.timeStart;
      if(data.timeEnd   && document.getElementById('clinicTimeEnd'))   document.getElementById('clinicTimeEnd').value   = data.timeEnd;
      applyClinicInfoToUI(data);
    } catch(e){ /* تجاهل أخطاء التحليل */ }
  }
  // ── تحميل الشعار المحفوظ ──
  const savedLogo = localStorage.getItem('mcg_clinic_logo');
  if(savedLogo) applyClinicLogo(savedLogo);
}

/* ══════════════════════════════════════════════
   STRUCTURE SETTINGS — هيكل المؤسسة الصحية
══════════════════════════════════════════════ */

// ── القراءة من localStorage ──
function loadStructureSettings(){
  const saved = localStorage.getItem('mcg_structure');
  if(saved){
    try {
      const s = JSON.parse(saved);
      document.getElementById('toggleServices').checked = !!s.services;
      document.getElementById('toggleRooms').checked    = !!s.rooms;
    } catch(e){
      // Default: both enabled
      document.getElementById('toggleServices').checked = true;
      document.getElementById('toggleRooms').checked    = true;
    }
  } else {
    // Default on first run: both enabled
    document.getElementById('toggleServices').checked = true;
    document.getElementById('toggleRooms').checked    = true;
  }
  applyStructureSettings(false);
}

// ── تطبيق الإعدادات ──
function applyStructureSettings(save=true){
  const servicesOn = document.getElementById('toggleServices').checked;
  const roomsOn    = document.getElementById('toggleRooms').checked;

  if(save){
    localStorage.setItem('mcg_structure', JSON.stringify({services:servicesOn, rooms:roomsOn}));
  }

  // ─ 1. Sidebar: إخفاء/إظهار عنصر "المصالح" ─
  const navServices = document.getElementById('navItemServices');
  if(navServices){
    navServices.style.display = servicesOn ? '' : 'none';
    // إذا كانت الصفحة النشطة هي المصالح وتم إخفاؤها → انتقل للرئيسية
    if(!servicesOn && document.getElementById('section-services').classList.contains('active')){
      switchSection('dashboard', document.querySelector('.nav-item.active') || document.querySelector('.nav-item'));
    }
  }

  // ─ 2. زر "Service Admin" في صفحة المستخدمين ─
  const cabSvcAdmin = document.getElementById('cabSvcAdmin');
  if(cabSvcAdmin){
    cabSvcAdmin.style.display = servicesOn ? '' : 'none';
  }

  // ─ 3. حقل المصلحة في modals الإضافة والتعديل ─
  const addDeptField  = document.getElementById('addUserDeptField');
  const editDeptField = document.getElementById('editUserDeptField');
  if(addDeptField)  addDeptField.style.display  = servicesOn ? '' : 'none';
  if(editDeptField) editDeptField.style.display = servicesOn ? '' : 'none';

  // ─ 4. حقل سؤال الغرف داخل نافذة إضافة/تعديل مصلحة ─
  const addHasRoomsRow  = document.getElementById('addHasRoomsRow');
  const editHasRoomsRow = document.getElementById('editHasRoomsRow');
  if(addHasRoomsRow)  addHasRoomsRow.style.display  = roomsOn ? '' : 'none';
  if(editHasRoomsRow) editHasRoomsRow.style.display = roomsOn ? '' : 'none';
  // إخفاء قسم الغرف إذا كان roomsOn مغلقاً
  if(!roomsOn){
    const addRoomSec  = document.getElementById('addRoomSection');
    const editRoomSec = document.getElementById('editRoomSection');
    if(addRoomSec)  addRoomSec.style.display  = 'none';
    if(editRoomSec) editRoomSec.style.display = 'none';
  }

  // ─ 5. تحديث شريط الحالة ─
  updateStructStatusBar(servicesOn, roomsOn);
}

function updateStructStatusBar(servicesOn, roomsOn){
  const bar  = document.getElementById('structStatusBar');
  const text = document.getElementById('structStatusText');
  if(!bar || !text) return;

  let msg = '';
  if(servicesOn && roomsOn){
    msg = 'الوضع الكامل — مصالح + غرف + مرضى مرتبطون بالغرف';
    bar.style.background = 'rgba(14,165,233,.07)';
    bar.style.borderColor = 'rgba(14,165,233,.15)';
  } else if(servicesOn && !roomsOn){
    msg = 'وضع المصالح فقط — بدون إدارة غرف';
    bar.style.background = 'rgba(99,102,241,.07)';
    bar.style.borderColor = 'rgba(99,102,241,.15)';
  } else if(!servicesOn && roomsOn){
    msg = 'وضع الغرف المستقلة — بدون تقسيم إلى مصالح';
    bar.style.background = 'rgba(16,185,129,.07)';
    bar.style.borderColor = 'rgba(16,185,129,.15)';
  } else {
    msg = 'الوضع البسيط — عيادة صغيرة بدون مصالح أو غرف';
    bar.style.background = 'rgba(245,158,11,.07)';
    bar.style.borderColor = 'rgba(245,158,11,.15)';
  }
  text.textContent = msg;
}

// ── تشغيل عند التحميل ──
document.addEventListener('DOMContentLoaded', () => {
    loadClinicSettings();
    loadStructureSettings();
    loadClinicStaff();
    loadServices();
    loadClinicActivities();
    loadDashboardRecentActivities();
    loadLastLoginAlert();

});

// fallback إذا كان DOMContentLoaded قد فات
if (document.readyState !== 'loading') {
    loadClinicSettings();
    loadStructureSettings();
    loadClinicStaff();
    loadServices();
    loadClinicActivities();
    loadDashboardRecentActivities();
loadLastLoginAlert();
    
}
function loadClinicActivities(){

    fetch('get_clinic_activities.php')
    .then(response => response.json())
    .then(data => {

        if(!data.success) return;

        const container = document.getElementById('activityListContainer');

        if(!container) return;

        container.innerHTML = '';

        data.activities.forEach(activity => {

            const item = document.createElement('div');

            item.className = 'activity-item';

            const date = new Date(activity.created_at);

            const timeStr = date.toLocaleString('ar-DZ');

            item.innerHTML = `
                <div class="act-icon"
                     style="background:${activity.bg};color:${activity.color}">
                    <i class="fas ${activity.icon}"></i>
                </div>

                <div class="act-body">
                    <div class="act-title">${activity.title}</div>

                    <div class="act-sub">
                        ${activity.description}
                    </div>

                    <div class="act-time">
                        <i class="fas fa-clock"></i>
                        ${timeStr}
                    </div>
                </div>
           ` ;

            container.appendChild(item);

        });

        renderActivityPage();

    })
    .catch(error => console.error(error));

}
function loadDashboardRecentActivities(){

    fetch('get_clinic_activities.php')
    .then(response => response.json())
    .then(data => {

        if(!data.success) return;

        const container = document.getElementById('dashboardRecentActivities');

        if(!container) return;

        container.innerHTML = '';

        data.activities.slice(0, 5).forEach(activity => {

            const date = new Date(activity.created_at);

            const timeStr = date.toLocaleString('ar-DZ');

            container.innerHTML += `
                <div class="last-act-item">
                    <div class="last-act-icon"
                         style="background:${activity.bg};color:${activity.color}">
                        <i class="fas ${activity.icon}"></i>
                    </div>

                    <div class="last-act-body">
                        <div class="last-act-title">
                            ${activity.title}
                        </div>

                        <div class="last-act-desc">
                            ${activity.description}
                        </div>

                        <div class="last-act-time">
                            <i class="fas fa-clock"></i>
                            ${timeStr}
                        </div>
                    </div>
                </div>
           ` ;
        });

    })
    .catch(error => console.error(error));

}
function addActivity(icon, bgColor, iconColor, title, desc){

  const now = new Date(); // <-- أضيفي هذا السطر

  const timeStr = now.toLocaleTimeString('ar-DZ',{
    hour:'2-digit',
    minute:'2-digit',
    hour12:false
  });

  const container = document.getElementById('activityListContainer');
  if(!container) return;

  const list = container;
  const item = document.createElement('div');
  item.className='activity-item';
  item.style.animation='fadeIn .3s ease';

  item.innerHTML = `
    <div class="act-icon" style="background:${bgColor};color:${iconColor}">
      <i class="fas ${icon}"></i>
    </div>
    <div class="act-body">
      <div class="act-title">${title}</div>
      <div class="act-sub">${desc}</div>
      <div class="act-time">
        <i class="fas fa-clock"></i> اليوم، ${timeStr}
      </div>
    </div>`;

  list.insertBefore(item, list.firstChild);
fetch('save_clinic_activity.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded'
  },
  body: new URLSearchParams({
    icon: icon,
    bg: bgColor,
    color: iconColor,
    title: title,
    description: desc
  })
})
.catch(err => console.error('Activity log error:', err));
  // إعادة الصفحة إلى الأولى وتحديث الترقيم
  pagination.activity.page = 1;
  renderActivityPage();
  loadDashboardRecentActivities();
}
</script>

<!-- MODAL: عرض مستخدم -->
<div class="modal-overlay" id="viewUserModal">
  <div class="modal" style="max-width:480px">
    <button class="modal-close" onclick="closeModal('viewUserModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-id-card"></i> بطاقة المستخدم</div>
    <div class="modal-body-scroll" id="viewUserModalContent"></div>
    <div class="modal-sticky-footer">
      <div class="modal-actions" style="margin-top:0">
        <button class="btn-secondary" onclick="closeModal('viewUserModal')"><i class="fas fa-xmark"></i> إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: تعديل مستخدم — Enhanced -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal" style="max-width:520px">
    <button class="modal-close" onclick="closeModal('editUserModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-user-pen"></i> تعديل بيانات المستخدم</div>
    <div class="modal-body-scroll">
    <div class="modal-grid">
      <div class="modal-field">
        <label><i class="fas fa-user" style="color:var(--accent);margin-left:5px"></i>الاسم الكامل</label>
        <input type="text" id="editUserName" placeholder="الاسم الكامل">
      </div>
      <div class="modal-field" style="grid-column:span 1">
        <label><i class="fas fa-briefcase" style="color:var(--accent);margin-left:5px"></i>الوظيفة / Role</label>
        <input type="text" id="editUserRole" placeholder="الوظيفة" onchange="toggleEditPharmacyTypeField()">
      </div>
      <div class="modal-field" id="editUserDeptField" style="grid-column:span 2">
        <label><i class="fas fa-sitemap" style="color:var(--accent);margin-left:5px"></i>المصلحة</label>
        <input type="text" id="editUserDept" placeholder="المصلحة">
        <div class="modal-field">
    <label><i class="fas fa-envelope" style="color:var(--accent);margin-left:5px"></i>البريد الإلكتروني</label>
    <input type="email" id="editUserEmail" placeholder="البريد الإلكتروني">
</div>

<div class="modal-field">
    <label><i class="fas fa-phone" style="color:var(--accent);margin-left:5px"></i>رقم الهاتف</label>
    <input type="text" id="editUserPhone" placeholder="رقم الهاتف">
</div>

<div class="modal-field" style="grid-column:span 2">
    <label><i class="fas fa-stethoscope" style="color:var(--accent);margin-left:5px"></i>التخصص</label>
    <input type="text" id="editUserSpecialty" placeholder="التخصص">
</div>

<div class="modal-field" id="editUserPharmacyTypeField" style="grid-column:span 2;display:none">
    <label><i class="fas fa-pills" style="color:var(--accent);margin-left:5px"></i>نوع الصيدلية</label>
    <select id="editUserPharmacyType">
        <option value="" disabled selected hidden>اختر نوع الصيدلية</option>
        <option value="صيدلية مركزية">صيدلية مركزية</option>
        <option value="صيدلية مصلحة">صيدلية مصلحة</option>
    </select>
</div>
      </div>
    </div>
    </div>
    <div class="modal-sticky-footer">
      <div id="passwordResetMessage" style="display:none;margin-bottom:15px;">
    <div style="
        max-width:280px;
        margin:0 auto;
        background:rgba(56,189,248,.12);
        border:1px solid rgba(56,189,248,.35);
        border-radius:10px;
        padding:10px 12px;
        text-align:center;
    ">
        <div style="
            color:#38bdf8;
            font-size:13px;
            font-weight:700;
            margin-bottom:8px;
        ">
            <i class="fas fa-key"></i>
            تم إنشاء كلمة مرور جديدة
        </div>

        <div id="generatedPasswordText" style="
            background:rgba(255,255,255,.08);
            padding:8px;
            border-radius:8px;
            font-size:16px;
            font-weight:700;
            letter-spacing:1px;
            color:white;
            margin-bottom:8px;
        ">
        </div>

        <div style="color:#94a3b8;font-size:12px;">
            يرجى إرسالها للمستخدم.
        </div>
    </div>
</div>
      <div class="modal-actions" style="margin-top:0">
        <button class="btn-secondary" onclick="closeModal('editUserModal')"><i class="fas fa-xmark"></i> إلغاء</button>
        <button type="button"
        class="btn btn-warning"
        onclick="resetUserPassword()">
    <i class="fas fa-key"></i>
    إعادة تعيين كلمة السر
</button>
        <button class="btn-primary" onclick="saveEditUser()"><i class="fas fa-floppy-disk"></i> حفظ التعديلات</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: عرض مصلحة (Enhanced) -->
<div class="modal-overlay" id="viewServiceModal">
  <div class="modal" style="max-width:520px">
    <button class="modal-close" onclick="closeModal('viewServiceModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-hospital"></i> تفاصيل المصلحة</div>
    <div class="modal-body-scroll" id="viewServiceContent" style="padding:4px 2px 8px"></div>
    <div class="modal-sticky-footer">
      <div class="modal-actions" style="margin-top:0">
        <button class="btn-secondary" onclick="closeModal('viewServiceModal')"><i class="fas fa-xmark"></i> إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: تأكيد حذف مصلحة -->
<div class="modal-overlay" id="confirmDeleteServiceModal">
  <div class="modal confirm-delete-modal">
    <button class="modal-close" onclick="closeModal('confirmDeleteServiceModal')"><i class="fas fa-times"></i></button>

    <div class="confirm-delete-icon-wrap">
      <div class="confirm-delete-icon">
        <i class="fas fa-trash-can"></i>
      </div>
    </div>

    <div class="confirm-delete-title">حذف المصلحة</div>
    <div class="confirm-delete-msg">هل أنت متأكد من حذف هذه المصلحة؟<br>هذا الإجراء لا يمكن التراجع عنه.</div>

    <div class="confirm-delete-service-name">
      <div class="confirm-delete-service-icon" id="confirmDeleteServiceIconWrap">
        <i id="confirmDeleteServiceIcon" class="fas fa-hospital"></i>
      </div>
      <div class="confirm-delete-service-label" id="confirmDeleteServiceName"></div>
    </div>

    <div class="confirm-delete-warning">
      <i class="fas fa-triangle-exclamation"></i>
      <span>قد يؤثر هذا الإجراء على الموظفين المرتبطين بها والبيانات المخزّنة في النظام.</span>
    </div>

    <div class="confirm-delete-actions">
      <button class="btn-secondary" onclick="closeModal('confirmDeleteServiceModal')">
        <i class="fas fa-xmark"></i> إلغاء
      </button>
      <button class="btn-confirm-delete" onclick="confirmDeleteServiceNow()">
        <i class="fas fa-trash-can"></i> تأكيد الحذف
      </button>
    </div>
  </div>
</div>

<!-- MODAL: تعديل مصلحة -->
<div class="modal-overlay" id="editServiceModal">
  <div class="modal" style="max-width:540px;max-height:90vh;display:flex;flex-direction:column;padding:0;overflow:hidden">
    <!-- Sticky Header -->
    <div style="flex-shrink:0;padding:22px 24px 14px;position:relative;border-bottom:1px solid var(--border)">
      <button class="modal-close" onclick="closeModal('editServiceModal')"><i class="fas fa-times"></i></button>
      <div style="font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px">
        <i class="fas fa-pen" style="color:var(--accent)"></i> تعديل بيانات المصلحة
      </div>
    </div>
    <!-- Scrollable Body -->
    <style>#editServiceModal .modal-scroll-body::-webkit-scrollbar{width:5px}#editServiceModal .modal-scroll-body::-webkit-scrollbar-thumb{background:rgba(14,165,233,.35);border-radius:999px}</style>
    <div class="modal-scroll-body" style="flex:1;overflow-y:auto;padding:16px 24px;scrollbar-width:thin;scrollbar-color:rgba(14,165,233,.35) transparent">

      <div class="add-service-modal-icon-header">
        <div class="add-service-modal-icon"><i class="fas fa-hospital-user"></i></div>
        <div>
          <div class="add-service-modal-title">تعديل المصلحة</div>
          <div class="add-service-modal-sub">تعديل بيانات المصلحة ومعلومات الإقامة</div>
        </div>
      </div>

      <!-- الحقول الأساسية -->
      <div class="modal-field">
        <label><i class="fas fa-hospital" style="color:var(--accent);margin-left:5px"></i>اسم المصلحة</label>
        <input type="text" id="editServiceName" placeholder="مثال: قسم الطوارئ">
      </div>


      <!-- سؤال: هل تحتوي على غرف إقامة؟ -->
      <div class="has-rooms-toggle-row" id="editHasRoomsRow">
        <div class="has-rooms-toggle-info">
          <div class="has-rooms-toggle-icon"><i class="fas fa-bed"></i></div>
          <div class="has-rooms-toggle-text">
            <div class="has-rooms-toggle-label">هل تحتوي هذه المصلحة على غرف إقامة؟</div>
            <div class="has-rooms-toggle-sub">مثل: الطب الداخلي، الجراحة — الأشعة، المخبر لا تحتوي</div>
          </div>
        </div>
        <div class="has-rooms-radio-group">
          <label class="has-rooms-radio-btn" id="editHasRoomsBtnYes" onclick="setHasRooms('edit','yes')">
            <input type="radio" name="editHasRooms" value="yes">
            <i class="fas fa-check-circle" style="font-size:13px"></i> نعم
          </label>
          <label class="has-rooms-radio-btn selected-no" id="editHasRoomsBtnNo" onclick="setHasRooms('edit','no')">
            <input type="radio" name="editHasRooms" value="no" checked>
            <i class="fas fa-xmark-circle" style="font-size:13px"></i> لا
          </label>
        </div>
      </div>

      <!-- حقول الغرف — مخفية افتراضياً -->
      <div id="editRoomSection" style="display:none">

      <!-- نوع توزيع الغرف -->
      <div class="modal-field">
        <label><i class="fas fa-door-open" style="color:var(--accent);margin-left:5px"></i>نوع توزيع الغرف</label>
        <div class="room-dist-options">
          <label class="room-dist-option" id="editRdOptWings">
            <input type="radio" name="editRoomDistType" value="wings" onchange="onEditRoomDistChange()" checked>
            <div class="room-dist-option-inner">
              <div class="room-dist-option-icons">
                <i class="fas fa-mars" style="color:#60a5fa"></i>
                <i class="fas fa-venus" style="color:#f472b6"></i>
              </div>
              <div class="room-dist-option-text">
                <span class="room-dist-option-title">جناح رجال وجناح نساء</span>
                <span class="room-dist-option-sub">مصالح مثل الطب الداخلي، طب الأطفال</span>
              </div>
            </div>
          </label>
          <label class="room-dist-option" id="editRdOptShared">
            <input type="radio" name="editRoomDistType" value="shared" onchange="onEditRoomDistChange()">
            <div class="room-dist-option-inner">
              <div class="room-dist-option-icons">
                <i class="fas fa-door-closed" style="color:#34d399"></i>
              </div>
              <div class="room-dist-option-text">
                <span class="room-dist-option-title">غرف مشتركة فقط</span>
                <span class="room-dist-option-sub">مصالح مثل الأشعة، المخبر، الصيدلية</span>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- حقول جناح رجال ونساء -->
      <div id="editRoomFieldsWings" class="room-fields-section">
        <div class="room-wing-block room-wing-men">
          <div class="room-wing-header"><i class="fas fa-mars"></i><span>جناح الرجال</span></div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#60a5fa;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="editMenRooms" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#60a5fa;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="editMenBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
          </div>
        </div>
        <div class="room-wing-block room-wing-women">
          <div class="room-wing-header"><i class="fas fa-venus"></i><span>جناح النساء</span></div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#f472b6;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="editWomenRooms" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#f472b6;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="editWomenBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
          </div>
        </div>
      </div>

      <!-- حقول غرف مشتركة -->
      <div id="editRoomFieldsShared" class="room-fields-section" style="display:none">
        <div class="room-wing-block room-wing-shared">
          <div class="room-wing-header"><i class="fas fa-door-closed"></i><span>الغرف المشتركة</span></div>
          <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-door-open" style="color:#34d399;margin-left:5px"></i>عدد الغرف</label>
              <input type="number" id="editSharedRooms" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
            <div class="modal-field" style="margin-bottom:0">
              <label><i class="fas fa-bed" style="color:#34d399;margin-left:5px"></i>أسرّة / غرفة</label>
              <input type="number" id="editSharedBedsPerRoom" min="0" value="0" placeholder="0" oninput="calcEditTotals()">
            </div>
          </div>
        </div>
      </div>

      <!-- إجمالي -->
      <div class="room-totals-bar">
        <div class="room-total-item">
          <i class="fas fa-door-open"></i>
          <div><div class="room-total-label">إجمالي الغرف</div><div class="room-total-value" id="editTotalRoomsDisplay">0</div></div>
        </div>
        <div class="room-total-divider"></div>
        <div class="room-total-item">
          <i class="fas fa-bed"></i>
          <div><div class="room-total-label">إجمالي الأسرّة</div><div class="room-total-value" id="editTotalBedsDisplay">0</div></div>
        </div>
      </div>
      </div><!-- /editRoomSection -->
    </div>

    <!-- Sticky Footer -->
    <div class="modal-actions" style="flex-shrink:0;border-top:1px solid var(--border);margin:0;padding:16px 24px;border-radius:0 0 var(--radius) var(--radius)">
      <button class="btn-secondary" onclick="closeModal('editServiceModal')"><i class="fas fa-xmark"></i> إلغاء</button>
      <button class="btn-primary" onclick="saveEditService()"><i class="fas fa-floppy-disk"></i> حفظ التعديلات</button>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
/* ══════════════════════════════════════════════
   STATISTICS — إعداد الرسوم البيانية
══════════════════════════════════════════════ */

// ── إعدادات مشتركة لـ Chart.js تتوافق مع RTL والتصميم الداكن ──
Chart.defaults.font.family = "'Cairo', sans-serif";
Chart.defaults.color = '#7a8fa6';

function getChartTheme() {
  const isLight = document.body.classList.contains('light');
  return {
    gridColor: isLight ? 'rgba(0,0,0,.07)' : 'rgba(255,255,255,.06)',
    tickColor: isLight ? '#64748b' : '#7a8fa6',
    tooltipBg: isLight ? '#1e293b' : '#0d1526',
    tooltipText: '#f1f5f9',
  };
}

let lineChart = null;
let barChart = null;
let doughnutChart = null;

let statsCache = null;

function initStatisticsCharts() {
  // تحميل البيانات الحقيقية من الـ APIs ثم الرسم — لا توجد أي أرقام ثابتة هنا
  Promise.all([
    fetch('get_staff_role_stats.php').then(r => r.json()).catch(() => ({ success: false })),
    fetch('get_staff_service_stats.php').then(r => r.json()).catch(() => ({ success: false })),
    fetch('get_active_services_stats.php').then(r => r.json()).catch(() => ({ success: false }))
  ]).then(([roleStats, serviceStats, activeStats]) => {
    statsCache = { roleStats, serviceStats, activeStats };
    renderStatisticsCharts();
  });
}

// إعادة الرسم من البيانات المخزّنة (عند تغيير الحجم/الوضع) دون إعادة الجلب من الخادم
function renderStatisticsCharts() {
  if (!statsCache) return;
  const theme = getChartTheme();
  renderStaffRoleDoughnut(statsCache.roleStats, theme);
  renderStaffServiceBar(statsCache.serviceStats, theme);
  renderActiveServicesChart(statsCache.activeStats, theme);
}

function _hexToRgba(hex, a) {
  const h = hex.replace('#', '');
  const r = parseInt(h.substring(0, 2), 16);
  const g = parseInt(h.substring(2, 4), 16);
  const b = parseInt(h.substring(4, 6), 16);
  return `rgba(${r},${g},${b},${a})`;
}

// (1) Doughnut — توزيع الطاقم الطبي حسب الوظيفة (بيانات حقيقية من clinic_staff)
function renderStaffRoleDoughnut(stats, theme) {
  const canvas = document.getElementById('doughnutChartPatients');
  if (!canvas) return;

  const roles = (stats && stats.success && Array.isArray(stats.roles)) ? stats.roles : [];
  const palette = {
    doctor:               '#10b981',
    nurse:                '#0ea5e9',
    lab_technician:       '#f59e0b',
    radiology_technician: '#a78bfa',
    pharmacist:           '#f472b6',
    receptionist:         '#22d3ee',
    service_admin:        '#6366f1'
  };

  const labels = roles.map(r => r.label);
  const data   = roles.map(r => Number(r.count) || 0);
  const colors = roles.map(r => palette[r.role] || '#64748b');
  const total  = data.reduce((a, b) => a + b, 0);

  if (doughnutChart) doughnutChart.destroy();
  doughnutChart = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors.map(c => _hexToRgba(c, .85)),
        borderColor: colors,
        borderWidth: 2,
        hoverBackgroundColor: colors,
        hoverBorderWidth: 3,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: theme.tooltipBg,
          titleColor: '#f1f5f9',
          bodyColor: '#94a3b8',
          padding: 12,
          cornerRadius: 10,
          rtl: true,
          textDirection: 'rtl',
          callbacks: {
            label: ctx => {
              const pct = total ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
              return ` ${ctx.parsed} موظف (${pct}%)`;
            }
          }
        }
      },
      animation: { animateRotate: true, duration: 800 },
    }
  });

  // الرقم في الوسط = إجمالي الموظفين الحقيقي
  const totalEl = document.getElementById('staffTotalCount');
  if (totalEl) totalEl.textContent = total;

  // بناء وسيلة الإيضاح (Legend) ديناميكياً من البيانات الحقيقية
  const legend = document.getElementById('doughnutLegend');
  if (legend) {
    if (!roles.length) {
      legend.innerHTML =
        '<div style="font-size:12.5px;color:var(--text-muted);text-align:center">لا يوجد طاقم مسجّل بعد</div>';
    } else {
      legend.innerHTML = roles.map((r, i) => `
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:14px;height:14px;border-radius:4px;background:${colors[i]};flex-shrink:0"></div>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:700;color:var(--text)">${r.label}</div>
            <div style="font-size:18px;font-weight:800;color:${colors[i]};font-family:'JetBrains Mono',monospace;line-height:1.2">${data[i]}</div>
          </div>
        </div>
      `).join('<div style="width:100%;height:1px;background:var(--border)"></div>');
    }
  }
}

// (2) Bar — عدد الموظفين في كل مصلحة (بيانات حقيقية من services + clinic_staff)
function renderStaffServiceBar(stats, theme) {
  const canvas = document.getElementById('barChartStaff');
  if (!canvas) return;

  const arr = (stats && stats.success && Array.isArray(stats.services)) ? stats.services : [];
  const labels = arr.map(s => s.name);
  const data   = arr.map(s => Number(s.count) || 0);

  if (barChart) barChart.destroy();
  barChart = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'العدد',
        data: data,
        backgroundColor: 'rgba(99,102,241,.8)',
        borderColor: '#6366f1',
        borderWidth: 1.5,
        borderRadius: 7,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: theme.tooltipBg,
          titleColor: theme.tooltipText,
          bodyColor: '#94a3b8',
          padding: 10,
          cornerRadius: 8,
          rtl: true,
          textDirection: 'rtl',
          callbacks: { label: ctx => ` ${ctx.parsed.y} موظف` }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: theme.tickColor, font: { size: 10, family: "'Cairo',sans-serif" }, maxRotation: 30 },
          border: { display: false }
        },
        y: {
          grid: { color: theme.gridColor },
          ticks: { color: theme.tickColor, font: { size: 11, family: "'Cairo',sans-serif" }, stepSize: 1, precision: 0 },
          border: { display: false },
          beginAtZero: true,
        }
      }
    }
  });
}

// (3) المصالح الأكثر نشاطاً — مع رسالة احترافية عند عدم كفاية البيانات (بدون أخطاء JS)
function renderActiveServicesChart(stats, theme) {
  const canvas = document.getElementById('lineChartPatients');
  if (!canvas) return;
  const wrap = canvas.parentElement;

  let emptyEl = wrap.querySelector('.stats-empty-msg');
  const hasData = stats && stats.success && stats.enough_data
                  && Array.isArray(stats.services) && stats.services.length > 0;

  if (!hasData) {
    if (lineChart) { lineChart.destroy(); lineChart = null; }
    canvas.style.display = 'none';
    if (!emptyEl) {
      emptyEl = document.createElement('div');
      emptyEl.className = 'stats-empty-msg';
      emptyEl.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;text-align:center;padding:18px;font-size:13px;font-weight:600;color:var(--text-muted);line-height:1.7';
      wrap.appendChild(emptyEl);
    }
    emptyEl.textContent = (stats && stats.message)
      ? stats.message
      : 'لا توجد بيانات كافية حالياً لتحديد المصالح الأكثر نشاطاً';
    emptyEl.style.display = 'flex';
    return;
  }

  if (emptyEl) emptyEl.style.display = 'none';
  canvas.style.display = '';

  const labels = stats.services.map(s => s.name);
  const data   = stats.services.map(s => Number(s.count) || 0);

  if (lineChart) lineChart.destroy();
  lineChart = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'عدد النشاطات',
        data: data,
        backgroundColor: 'rgba(14,165,233,.8)',
        borderColor: '#0ea5e9',
        borderWidth: 1.5,
        borderRadius: 7,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: theme.tooltipBg,
          titleColor: theme.tooltipText,
          bodyColor: '#94a3b8',
          padding: 10,
          cornerRadius: 8,
          rtl: true,
          textDirection: 'rtl',
          callbacks: { label: ctx => ` ${ctx.parsed.x} نشاط` }
        }
      },
      scales: {
        x: {
          grid: { color: theme.gridColor },
          ticks: { color: theme.tickColor, font: { size: 11, family: "'Cairo',sans-serif" }, stepSize: 1, precision: 0 },
          border: { display: false },
          beginAtZero: true,
        },
        y: {
          grid: { display: false },
          ticks: { color: theme.tickColor, font: { size: 11, family: "'Cairo',sans-serif" } },
          border: { display: false }
        }
      }
    }
  });
}

// ── استجابة الشاشة: عمود واحد على الشاشات الصغيرة ──
function applyStatisticsLayout() {
  const row = document.getElementById('stats-charts-row');
  if (!row) return;
  if (window.innerWidth < 768) {
    row.style.gridTemplateColumns = '1fr';
  } else {
    row.style.gridTemplateColumns = '1fr 1fr';
  }
}

// ── إعادة رسم عند تغيير حجم الشاشة ──
window.addEventListener('resize', () => {
  applyStatisticsLayout();
  const stats = document.getElementById('section-statistics');
  if (stats && stats.classList.contains('active')) renderStatisticsCharts();
});

// ── إعادة رسم عند تغيير الوضع (داكن/فاتح) ──
const _origToggleTheme = typeof toggleTheme === 'function' ? toggleTheme : null;
if (_origToggleTheme) {
  window.toggleTheme = function() {
    _origToggleTheme();
    const stats = document.getElementById('section-statistics');
    if (stats && stats.classList.contains('active')) {
      setTimeout(renderStatisticsCharts, 100);
    }
  };
}
function toggleServiceStatus(id){
  const s = state.services.find(x=>x.id===id);
  if(!s) return;
  const newStatus = s.isActive !== false ? 0 : 1;

  fetch('toggle_service.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id: id, is_active: newStatus })
  })
  .then(r => r.json())
  .then(data => {
    if(!data.success){
      showToast('❌ ' + (data.message || 'فشل تغيير الحالة'));
      return;
    }
    s.isActive = !!newStatus;
    updateDashboardStats();
    fetchAdminNotifications();
    renderServices();
    const label = s.isActive ? 'تفعيل' : 'تعطيل';
    addActivity(
      s.isActive ? 'fa-toggle-on' : 'fa-toggle-off',
      s.isActive ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)',
      s.isActive ? '#34d399' : '#f87171',
      `${label} مصلحة`,
      `تم ${label} مصلحة <strong>${s.name}</strong>`
    );
    showToast(`تم ${label} مصلحة ${s.name} ✓`);
  })
  .catch(() => showToast('❌ خطأ في الاتصال بالخادم'));
}
function loadClinicStaff() {

    fetch('get_clinic_staff.php')
    .then(response => response.json())
    .then(data => {

        if (!data.success) {
            showToast('❌ فشل تحميل المستخدمين');
            return;
        }

        const roleMapAr = {
            'doctor':                 'طبيب',
            'nurse':                  'ممرض/ة',
            'lab_technician':         'مخبر',
            'radiology_technician':   'أشعة',
            'pharmacist':             'صيدلي',
            'receptionist':           'موظف استقبال',
            'service_admin':          'Service Admin'
        };
        const roleIconMap = {
            'doctor':               'fa-user-doctor',
            'nurse':                'fa-user-nurse',
            'lab_technician':       'fa-flask',
            'radiology_technician': 'fa-x-ray',
            'pharmacist':           'fa-pills',
            'receptionist':         'fa-headset',
            'service_admin':        'fa-desktop'
        };
       state.users = data.users.map(user => ({
    id: user.id,
    name: user.full_name,
    role: roleMapAr[user.role] || user.role,
    originalRole: user.role,
    dept: user.service_name || '',
    active: user.account_status !== 'inactive',
    roleIcon: roleIconMap[user.role] || 'fa-user',

    email: user.email || '',
    phone: user.phone || '',
    specialty: user.specialty || '',
    pharmacyType: user.pharmacy_type || ''
}));
updateDashboardStats();
applyUsersFilter();
initStatisticsCharts();


    })
    .catch(error => {

        console.error(error);
        showToast('❌ حدث خطأ أثناء تحميل المستخدمين');

    });

}
function loadServices(){

    fetch('get_services.php')
    .then(response => response.json())
    .then(data => {

        if(!data.success){
            showToast('❌ فشل تحميل المصالح');
            return;
        }

        state.services = data.services.map(service => {

            // تحويل room_data من JSON نص إلى object
            let roomData = {};
            try {
                roomData = service.room_data ? JSON.parse(service.room_data) : {};
            } catch(e) {
                roomData = {};
            }

            return {
                id:         service.id,
                name:       service.name,
                icon:       'fa-hospital',
                color:      '#0ea5e9',

               admin: service.admin_name || 'غير معين',
             workers: parseInt(service.workers_count) || 0,
                createdAt:  service.created_at,
                isActive:   service.is_active !== undefined ? !!service.is_active : true,
                hasRooms:   service.has_rooms == 1,
                roomData:   roomData,
                totalRooms: parseInt(service.total_rooms) || 0,
                totalBeds:  parseInt(service.total_beds)  || 0
            };

        });
const deptSelect = document.getElementById('newUserDeptName');

if (deptSelect) {

    deptSelect.innerHTML =
        '<option value="">اختر المصلحة</option>';

    state.services
    .filter(service => service.isActive !== false)
    .forEach(service => {

        deptSelect.innerHTML += `
            <option value="${service.name}">
                ${service.name}
            </option>
       ` ;

    });

}
updateDashboardStats();
        renderServices();

    })
    .catch(error => {

        console.error(error);
        showToast('❌ حدث خطأ أثناء تحميل المصالح');

    });

}
function updateDashboardStats(){

    const active = state.services.filter(
        s => s.isActive !== false
    ).length;

    const inactive = state.services.filter(
        s => s.isActive === false
    ).length;

    const total = state.services.length;

    document.getElementById('activeServicesCount').textContent = active;
    document.getElementById('inactiveServicesCount').textContent = inactive;
    document.getElementById('totalServicesCount').textContent = total;

    const activeUsers = state.users.filter(
        u => u.active !== false
    ).length;

    const inactiveUsers = state.users.filter(
        u => u.active === false
    ).length;

    const totalUsers = state.users.length;

    document.getElementById('activeUsersCount').textContent = activeUsers;
    document.getElementById('inactiveUsersCount').textContent = inactiveUsers;
    document.getElementById('totalUsersCount').textContent = totalUsers;
    const serviceAdmins = state.users.filter(
    u => u.originalRole === 'service_admin'
);

const activeAdmins = serviceAdmins.filter(
    u => u.active !== false
).length;

const inactiveAdmins = serviceAdmins.filter(
    u => u.active === false
).length;

document.getElementById('activeAdminsCount').textContent = activeAdmins;
document.getElementById('inactiveAdminsCount').textContent = inactiveAdmins;
document.getElementById('totalAdminsCount').textContent = serviceAdmins.length;
const totalRooms = state.services.reduce(
    (sum, s) => sum + (parseInt(s.totalRooms) || 0),
    0
);

const totalBeds = state.services.reduce(
    (sum, s) => sum + (parseInt(s.totalBeds) || 0),
    0
);

document.getElementById('totalRoomsCount').textContent = totalRooms;
document.getElementById('totalBedsCount').textContent = totalBeds;
}
function loadServiceAdmins(){

    fetch('get_service_admins.php')
    .then(response => response.json())
    .then(data => {

        if(!data.success) return;

        const select = document.getElementById('newServiceAdmin');

        select.innerHTML =
            '<option value="">اختر مسؤول المصلحة</option>';

        data.admins.forEach(admin => {

            select.innerHTML += `
                <option value="${admin.full_name}">
                    ${admin.full_name}
                </option>
            `;

        });

    })
    .catch(error => console.error(error));

}
function loadLastLoginAlert(){

    fetch('get_clinic_last_login.php')
    .then(response => response.json())
    .then(data => {

        if(!data.success) return;

        const alertBox = document.getElementById('lastLoginAlert');
        if(!alertBox) return;

        // أداة بناء صندوق التنبيه (نفس التصميم الأصلي)
        const renderBox = (border, bg, html) => {
            alertBox.innerHTML = `
                <div style="
                    margin-top:12px;
                    padding:12px 16px;
                    border-radius:12px;
                    border-right:4px solid ${border};
                    background:${bg};
                ">${html}</div>
            `;
        };

        // 1) أول مرة على الإطلاق: لا يوجد دخول سابق
        if(data.first_login || !data.last_login){
            renderBox('#3b82f6', '#dbeafe',
                `<strong>👋 هذه أول مرة تقوم فيها بتسجيل الدخول</strong>`);
            return;
        }

        const lastLogin = new Date(data.last_login);
        const now = new Date();
        const diffDays = Math.floor(
            (now - lastLogin) / (1000 * 60 * 60 * 24)
        );

        // 2) مرّ أكثر من يوم: تنبيه أحمر
        if(diffDays >= 1){
            const dayWord = (diffDays === 1) ? 'يوم' : 'أيام';
            renderBox('#ef4444', '#fee2e2', `
                <strong>🔴 لم تقم بالدخول منذ ${diffDays} ${dayWord}</strong>
                <br>
                <small>آخر دخول سابق: ${lastLogin.toLocaleString('ar-DZ')}</small>
            `);
            return;
        }

        // 3) أقل من يوم: تنبيه أخضر مع التاريخ والوقت الحقيقي للدخول السابق
        // التنسيق يُدار عبر CSS (.last-login-ok) المرتبط بـ body.light
        // حتى يظهر باللون الصحيح من أول تحميل ويتحدث فوراً عند تبديل الوضع
        alertBox.innerHTML = `
            <div class="last-login-ok">
                <strong>🟢 آخر دخول: ${lastLogin.toLocaleString('ar-DZ')}</strong>
            </div>
        `;
    })
    .catch(error => console.error(error));

}
</script>
<script>
/* ═══════════════════════════════════════════════════
   إشعارات الأدمن — polling كل 30 ثانية بدون refresh
═══════════════════════════════════════════════════ */
const notificationBtn      = document.getElementById('notificationBtn');
const notificationDropdown = document.getElementById('notificationDropdown');
const notificationCount    = document.getElementById('notificationCount');
const notificationList     = document.getElementById('notificationList');

/* فتح / إغلاق الـ dropdown */
notificationBtn.addEventListener('click', function(e){
    e.stopPropagation();
    notificationDropdown.style.display =
        notificationDropdown.style.display === 'block' ? 'none' : 'block';
});
document.addEventListener('click', function(){
    notificationDropdown.style.display = 'none';
});

/* جلب الإشعارات من السيرفر */
function fetchAdminNotifications(){
    fetch('get_admin_notifications.php')
        .then(r => r.json())
        .then(data => {
            if(!data.success) return;

            const total = data.total;

            /* تحديث الـ badge */
            if(total > 0){
                notificationCount.textContent = total;
                notificationCount.style.display = '';
            } else {
                notificationCount.style.display = 'none';
            }

            /* تحديث القائمة */
            if(total === 0){
                notificationList.innerHTML =
                    '<div class="notification-empty">لا توجد تنبيهات حالياً</div>';
            } else {
                notificationList.innerHTML = data.notifications.map(n => `
                    <div class="notification-item notif-${n.class}">
                        <span class="notif-ico">${n.icon}</span>
                        <span class="notif-txt">${n.text}</span>
                    </div>
                `).join('');
            }
        })
        .catch(err => console.error('[admin notif]', err));
}

/* أول جلب فوري عند تحميل الصفحة، ثم كل 30 ثانية */
fetchAdminNotifications();
setInterval(fetchAdminNotifications, 30000);
document.getElementById('usersSearchInput')
?.addEventListener('input', applyUsersFilter);
document.getElementById('usersRoleFilter')
?.addEventListener('change', applyUsersFilter);
</script>
</body>
</html>
