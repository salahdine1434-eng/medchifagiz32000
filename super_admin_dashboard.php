<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ── معالجة تسجيل الخروج ──
if (isset($_POST['logout_confirmed']) && $_POST['logout_confirmed'] === '1') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cp = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $cp['path'], $cp['domain'], $cp['secure'], $cp['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

function hasPermission($permission) {

    // Super Admin عنده جميع الصلاحيات
    if ($_SESSION['role'] === 'super_admin') {
        return true;
    }

    // إذا لم توجد صلاحيات
    if (!isset($_SESSION['permissions'])) {
        return false;
    }

    // التحقق من الصلاحية المطلوبة
    return isset($_SESSION['permissions'][$permission]) &&
           $_SESSION['permissions'][$permission] === true;
}


require_once "db.php";
require_once 'super_admin_notifications_helper.php';
$totalUsers = $pdo->query("
SELECT COUNT(*) FROM users
")->fetchColumn();

$totalPatients = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role='patient'
")->fetchColumn();

$totalDoctors = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role='doctor'
")->fetchColumn();
$newUsers = $pdo->query("
SELECT COUNT(*) FROM users
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();
$totalPending = $pdo->query("
SELECT COUNT(*) FROM users
WHERE status='pending'
")->fetchColumn();
$admins = $pdo->query("
SELECT
    id,
    full_name,
    email,
    role,
    account_status,
    permissions,
    created_at
FROM users
WHERE role IN ('super_admin','admin','moderator')
ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$totalInstitutions = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role IN ('clinic','pharmacy','lab')
")->fetchColumn();
$totalClinics = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role='clinic'
")->fetchColumn();

$totalPharmacies = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role='pharmacy'
")->fetchColumn();

$totalLabs = $pdo->query("
SELECT COUNT(*) FROM users
WHERE role='lab'
")->fetchColumn();
$latestUsers = $pdo->query("
SELECT full_name, role, status, created_at
FROM users
ORDER BY id DESC
LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$latestActivities = $pdo->query("
SELECT full_name, role, created_at
FROM users
ORDER BY id DESC
LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);
$approvedUsers = $pdo->query("
SELECT
u.id,
u.full_name,
u.email,
u.phone,
u.role,
u.created_at,
u.status,
u.account_status,
c.institution_type
FROM users u
LEFT JOIN clinic_profiles c ON c.user_id = u.id
WHERE u.status='approved'
ORDER BY u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$pendingStmt = $pdo->prepare("
SELECT
u.id,
u.full_name,
u.email,
u.phone,
u.role,
u.created_at,

COALESCE(d.wilaya, p.wilaya, c.wilaya, l.wilaya) AS wilaya,

d.specialty,

COALESCE(
d.license_number,
p.license_number,
c.license_number,
l.license_number
) AS license_number,

COALESCE(
d.license_file,
p.license_file,
c.license_file,
l.license_file
) AS license_file,
c.institution_type,
d.workplace,
d.experience

FROM users u

LEFT JOIN doctors d
ON d.user_id = u.id

LEFT JOIN pharmacy_profiles p
ON p.user_id = u.id

LEFT JOIN clinic_profiles c
ON c.user_id = u.id

LEFT JOIN lab_profiles l
ON l.user_id = u.id

WHERE u.status='pending'

ORDER BY u.id DESC
");

$pendingStmt->execute();
$pendingUsers = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// ── إشعارات طلبات التسجيل المعلقة ──
// يُشغَّل فقط إذا كان المستخدم مسجل الدخول (super_admin)
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'super_admin') {

    // خريطة أنواع الحسابات بالعربية
    $roleLabelsNotif = [
        'doctor'   => 'طبيب',
        'hospital' => 'مستشفى',
        'lab'      => 'مخبر تحاليل',
        'pharmacy' => 'صيدلية',
        'clinic'   => 'عيادة',
    ];

    foreach ($pendingUsers as $pendingUser) {

        // تحقق: هل يوجد إشعار مسبق لهذا الطلب (نفس الـ user_id) ولم يُحذف؟
        // نستخدم عمود related_id إن وُجد، أو نبحث في message عن معرّف الطلب
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM super_admin_notifications
            WHERE super_admin_id = ?
              AND title = 'طلب تسجيل جديد'
              AND message LIKE ?
        ");

        $likePattern = '%[ID:' . $pendingUser['id'] . ']%';
        $checkStmt->execute([$_SESSION['user_id'], $likePattern]);
        $alreadyExists = (int)$checkStmt->fetchColumn();

        if ($alreadyExists === 0) {
            // إنشاء الإشعار لأول مرة فقط
            $accountType = $roleLabelsNotif[$pendingUser['role']] ?? $pendingUser['role'];
            $message = 'تقدّم ' . $pendingUser['full_name'] . ' بطلب تسجيل كـ' . $accountType . '. [ID:' . $pendingUser['id'] . ']';

            createSuperAdminNotification(
                'طلب تسجيل جديد',
                $message
            );
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (
    $_SESSION['role'] != 'super_admin' &&
    $_SESSION['role'] != 'admin' &&
    $_SESSION['role'] != 'moderator'
) {
    die("Access Denied");
}

$_roleMap = ['super_admin'=>'Super Admin','admin'=>'Admin','moderator'=>'Moderator'];
$currentRoleLabel = $_roleMap[$_SESSION['role'] ?? ''] ?? 'Admin';
$currentUserName  = $_SESSION['name'] ?? $currentRoleLabel;
$currentRole      = $_SESSION['role'] ?? 'admin';

// ── ألوان الأدوار ──
$_roleColors = [
    'super_admin' => ['color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.15)', 'border'=>'rgba(245,158,11,.35)', 'icon'=>'fa-crown',      'gradient'=>'#f59e0b,#ef4444'],
    'admin'       => ['color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.15)', 'border'=>'rgba(59,130,246,.35)',  'icon'=>'fa-user-tie',   'gradient'=>'#3b82f6,#2563eb'],
    'moderator'   => ['color'=>'#a78bfa', 'bg'=>'rgba(167,139,250,.15)','border'=>'rgba(167,139,250,.35)', 'icon'=>'fa-user-check', 'gradient'=>'#a78bfa,#7c3aed'],
];
$rc = $_roleColors[$currentRole] ?? $_roleColors['admin'];
$roleColor   = $rc['color'];
$roleBg      = $rc['bg'];
$roleBorder  = $rc['border'];
$roleIcon    = $rc['icon'];
$roleGradient= $rc['gradient'];


// Avatar الافتراضي: أول حرف من الاسم (مُبقى للاستخدام في سجل الصيانة)
$avatarInitial = mb_substr($currentUserName, 0, 1, 'UTF-8');

// ── شعار المنصة ──
try {
    $stmtLogo = $pdo->prepare("SELECT `value` FROM platform_settings WHERE `key` = 'logo_path'");
    $stmtLogo->execute();
    $platformLogoPath = $stmtLogo->fetchColumn() ?: null;
} catch (Exception $e) {
    $platformLogoPath = null;
}

// ── تحديد نوع الـ PANEL label بحسب الدور ──
$_panelLabels = [
    'super_admin' => 'SUPER ADMIN PANEL',
    'admin'       => 'ADMIN PANEL',
    'moderator'   => 'MODERATOR PANEL',
];
$panelLabel = $_panelLabels[$currentRole] ?? 'ADMIN PANEL';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedChifaGiz — <?= htmlspecialchars($currentRoleLabel) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="responsive.css">
<style>
/* ── ألوان الدور الديناميكية ── */
:root {
  --role-color: <?= $roleColor ?>;
  --role-bg: <?= $roleBg ?>;
  --role-border: <?= $roleBorder ?>;
  --role-gradient: <?= $roleGradient ?>;
}
</style>
<script>
/* تطبيق المظهر قبل رسم الصفحة لمنع الوميض */
(function(){
  var t = localStorage.getItem('mcg_theme');
  if(t === 'dark'){
    document.documentElement.setAttribute('data-theme','dark');
  } else {
    document.documentElement.setAttribute('data-theme','light');
  }
})();
</script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:15px;overflow-x:hidden;max-width:100%}
body{
  font-family:'Cairo',sans-serif;
  background:#0b0f1a;
  color:#e2e8f0;
  min-height:100vh;
  overflow-x:hidden;
  max-width:100%;
}
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
body.light .sidebar{background:#ffffff !important;border-left:1px solid #d1dce8 !important;}
body.light .sidebar-logo,body.light .sidebar-profile{border-bottom-color:#e8eef6}
body.light .logo-brand{color:#1e293b}
body.light .logo-brand em{color:#0284c7}
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
body.light .modal-close{background:rgba(0,0,0,.06);color:#64748b}
body.light .toast{background:#1e3a5f;color:#fff}
body.light .maintenance-card{background:#fff;border-color:#d1dce8}

/* BACKGROUND */
.bg-mesh{
  position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(ellipse 60% 40% at 20% 10%, rgba(14,165,233,.07) 0%, transparent 60%),
    radial-gradient(ellipse 40% 50% at 80% 80%, rgba(99,102,241,.06) 0%, transparent 60%);
}

/* SIDEBAR */
.sidebar{
  position:fixed;top:0;right:0;
  width:var(--sidebar-w);height:100vh;
  background:linear-gradient(180deg,#0d1526 0%,#0b1220 100%);
  border-left:1px solid var(--border);
  display:flex;flex-direction:column;
  z-index:100;overflow:hidden;
  transition:width .28s ease,transform .28s ease,box-shadow .28s ease;
}
.sidebar-glow{
  position:absolute;top:-60px;right:-60px;
  width:200px;height:200px;
  background:radial-gradient(circle,rgba(14,165,233,.12) 0%,transparent 70%);
  pointer-events:none;
}
.sidebar-logo{
  display:flex;align-items:center;gap:12px;
  padding:22px 20px 18px;
  border-bottom:1px solid var(--border);
}
.logo-icon{
  width:40px;height:40px;
  background:linear-gradient(135deg,#0ea5e9,#6366f1);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:#fff;
  box-shadow:0 4px 14px rgba(14,165,233,.35);
  flex-shrink:0;
}
.logo-brand{font-size:16px;font-weight:800;color:#fff;letter-spacing:-.3px;display:block}
.logo-brand em{color:#0ea5e9;font-style:normal}
.logo-sub{font-size:10.5px;color:var(--text-muted);letter-spacing:.5px;display:block;margin-top:1px}
.sidebar-profile{
  display:flex;align-items:center;gap:12px;
  padding:16px 20px;
  border-bottom:1px solid var(--border);
}
.profile-avatar{
    position:relative;
    width:60px;
    height:60px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
}

.profile-avatar span{
    width:auto;
    height:auto;
    background:transparent !important;
    border-radius:0;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:none;
}

.profile-avatar span i{
    font-size:48px;
    color:var(--role-color);
}

.avatar-ring,
.online-dot{
    display:none;
}
.avatar-ring{
  position:absolute;inset:-2px;border-radius:50%;
  border:2px solid transparent;
  background:linear-gradient(#0b1220,#0b1220) padding-box,
             linear-gradient(135deg,var(--role-gradient)) border-box;
}
.online-dot{
  position:absolute;bottom:1px;left:1px;
  width:10px;height:10px;border-radius:50%;
  background:#10b981;border:2px solid #0b1220;
}
.profile-name{
    display:block;
    font-size:15px;
    font-weight:700;
    color:var(--text-primary) !important;
    opacity:1 !important;
    visibility:visible !important;
    text-shadow:none !important;
}
.profile-role{
  font-size:11px;color:var(--role-color);display:block;margin-top:2px;
  display:flex;align-items:center;gap:4px;font-weight:600;
}
.profile-role i{font-size:9px}
.sidebar-nav{
  flex:1;overflow-y:auto;padding:12px 12px 8px;
  scrollbar-width:thin;scrollbar-color:rgba(14,165,233,.35) transparent;
}
.sidebar-nav::-webkit-scrollbar{width:4px}
.sidebar-nav::-webkit-scrollbar-track{background:transparent}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(14,165,233,.3);border-radius:999px}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:10px;
  cursor:pointer;color:var(--text-muted);
  transition:all .18s ease;text-decoration:none;
  position:relative;margin-bottom:2px;
  font-size:13.5px;font-weight:500;
}
.nav-item::before,.nav-item::after,
.nav-icon::before,.nav-icon::after,
.sidebar-nav::before,.sidebar-nav::after,
.sidebar-footer::before,.sidebar-footer::after,
.sidebar-logo::before,.sidebar-logo::after,
.sidebar-profile::before,.sidebar-profile::after{display:none!important;content:none!important}
.nav-item:hover{background:var(--bg-hover);color:var(--text)}
.nav-item.active{
  background:linear-gradient(135deg,rgba(14,165,233,.18),rgba(99,102,241,.12));
  color:var(--accent2);
  border:1px solid rgba(14,165,233,.2);
}
.nav-item.active .nav-icon{background:rgba(14,165,233,.15);color:var(--accent)}
.nav-icon{
  width:32px;height:32px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;color:var(--text-dim);
  background:rgba(255,255,255,.04);
  flex-shrink:0;transition:color .18s;
}
.nav-item.danger{color:#f87171}
.nav-item.danger .nav-icon{color:#f87171;background:rgba(248,113,113,.1)}
.nav-item.danger:hover{background:rgba(239,68,68,.12)}
.nav-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:18px;
  height:18px;
  padding:0 5px;
  border-radius:999px;
  background:#ef4444;
  color:#fff;
  font-size:10px;
  font-weight:700;
  line-height:1;
  margin-right:auto;
  margin-left:2px;
  box-shadow:0 0 6px rgba(239,68,68,.45);
  flex-shrink:0;
}
body.light .nav-badge{
  background:#dc2626;
  box-shadow:0 0 6px rgba(220,38,38,.35);
}
.sidebar-footer{padding:12px;border-top:1px solid var(--border)}

/* MAIN */
.main{margin-right:var(--sidebar-w);min-height:100vh;position:relative;z-index:1;transition:margin-right .28s ease}
.sidebar.collapsed{width:0;overflow:hidden;border-left:none}
.sidebar.collapsed ~ .main{margin-right:0}

/* HEADER */
.header{
  position:sticky;top:0;z-index:50;
  height:var(--header-h);
  background:rgba(11,15,26,.85);
  backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;
  justify-content:space-between;
  padding:0 24px;gap:16px;
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
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;width:160px;
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
}
/* Super Admin / Admin / Moderator role badge in header */
.role-badge{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--role-bg);
  border:1px solid var(--role-border);
  color:var(--role-color);font-size:11px;font-weight:700;
  padding:4px 10px;border-radius:20px;
}
/* backward compat alias */
.super-badge{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--role-bg);
  border:1px solid var(--role-border);
  color:var(--role-color);font-size:11px;font-weight:700;
  padding:4px 10px;border-radius:20px;
}

/* SECTIONS */
.section{display:none;padding:28px 28px 40px;animation:fadeIn .25s ease}
.section.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

/* WELCOME BAR */
.welcome-bar{
  background:linear-gradient(135deg,#0d1f35 0%,#111a2e 100%);
  border:1px solid var(--border2);border-radius:var(--radius);
  padding:22px 24px;
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:24px;position:relative;overflow:hidden;
}
.welcome-bar::before{
  content:'';position:absolute;top:-40px;left:-40px;
  width:200px;height:200px;
  background:radial-gradient(circle,rgba(14,165,233,.1) 0%,transparent 70%);
  pointer-events:none;
}
.welcome-bar h1{font-size:22px;font-weight:800;color:#fff;line-height:1.2}
.welcome-bar p{font-size:13px;color:var(--text-muted);margin-top:5px}
.welcome-bar p strong{color:var(--accent2)}
.welcome-icon{
  width:56px;height:56px;border-radius:14px;
  background:linear-gradient(135deg,rgba(245,158,11,.2),rgba(239,68,68,.15));
  border:1px solid rgba(245,158,11,.3);
  display:flex;align-items:center;justify-content:center;
  font-size:24px;color:#f59e0b;flex-shrink:0;
}

/* STATS GRID */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:16px;margin-bottom:28px;
}
.stat-card{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);padding:20px;
  position:relative;overflow:hidden;
  transition:transform .2s,box-shadow .2s;cursor:default;
}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.3)}
.stat-card::before{
  content:'';position:absolute;top:0;right:0;
  width:80px;height:80px;
  background:radial-gradient(circle, var(--card-color,#0ea5e9) 0%,transparent 70%);
  opacity:.12;pointer-events:none;
}
.stat-icon{
  width:42px;height:42px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--card-color,#0ea5e9);
  margin-bottom:14px;
  background:rgba(14,165,233,.12);
}
.stat-value{font-size:28px;font-weight:800;color:#fff;line-height:1}
.stat-label{font-size:12px;color:var(--text-muted);margin-top:4px}
.stat-sub{font-size:11px;color:var(--text-dim);margin-top:4px}

/* SECTION HEADER */
.section-header{
  display:flex;align-items:flex-start;justify-content:space-between;
  margin-bottom:20px;flex-wrap:wrap;gap:12px;
}
.section-title{font-size:20px;font-weight:800;color:#fff}
.section-sub{font-size:13px;color:var(--text-muted);margin-top:3px}
.section-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

/* BUTTONS */
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
  color:var(--text-muted);font-family:'Cairo',sans-serif;font-size:13px;font-weight:600;
  cursor:pointer;transition:all .18s;
}
.btn-secondary:hover{background:var(--bg-hover);color:var(--text)}
.btn-success{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:7px;border:none;
  background:rgba(16,185,129,.15);color:#34d399;
  font-family:'Cairo',sans-serif;font-size:12px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.btn-success:hover{background:rgba(16,185,129,.25)}
.btn-danger{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:7px;border:none;
  background:rgba(239,68,68,.12);color:#f87171;
  font-family:'Cairo',sans-serif;font-size:12px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.btn-danger:hover{background:rgba(239,68,68,.22)}
.btn-warning{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:7px;border:none;
  background:rgba(245,158,11,.12);color:#fbbf24;
  font-family:'Cairo',sans-serif;font-size:12px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.btn-warning:hover{background:rgba(245,158,11,.22)}
.btn-info{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:7px;border:none;
  background:rgba(14,165,233,.12);color:#38bdf8;
  font-family:'Cairo',sans-serif;font-size:12px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.btn-info:hover{background:rgba(14,165,233,.22)}

/* TABLE */
.data-table-wrap{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);overflow:hidden;
}
.data-table{width:100%;border-collapse:collapse}
.data-table th{
  padding:11px 16px;text-align:right;
  font-size:11px;font-weight:700;color:var(--text-dim);
  letter-spacing:.6px;text-transform:uppercase;
  background:rgba(255,255,255,.02);border-bottom:1px solid var(--border);
}
.data-table td{
  padding:12px 16px;font-size:13px;color:var(--text);
  border-bottom:1px solid var(--border);vertical-align:middle;
}
.data-table tbody tr:last-child td{border-bottom:none}
.data-table tbody tr:hover{background:var(--bg-hover)}
.td-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap}

/* BADGES */
.badge{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;
  font-size:11px;font-weight:600;
}
.badge-active{background:rgba(16,185,129,.12);color:#34d399}
.badge-inactive{background:rgba(239,68,68,.1);color:#f87171}
.badge-pending{background:rgba(245,158,11,.12);color:#fbbf24}
.badge-approved{background:rgba(16,185,129,.12);color:#34d399}
.badge-rejected{background:rgba(239,68,68,.1);color:#f87171}
.type-badge{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;
  font-size:11px;font-weight:600;
  background:rgba(99,102,241,.12);color:#a5b4fc;
}

/* SEARCH BAR */
.search-bar{
  display:flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.05);border:1px solid var(--border);
  border-radius:8px;padding:7px 12px;
}
.search-bar i{color:var(--text-dim);font-size:13px;flex-shrink:0}
.search-bar input{
  background:none;border:none;outline:none;
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;width:200px;
}
.search-bar input::placeholder{color:var(--text-dim)}

/* ACTIVITY LOG */
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
.act-user{font-size:11px;color:var(--accent2);margin-top:2px;display:flex;align-items:center;gap:4px}

/* SETTINGS */
.settings-layout{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
.settings-profile-card{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);padding:24px 20px;text-align:center;
}
.settings-avatar-big{position:relative;width:72px;height:72px;margin:0 auto 14px}
.settings-avatar-big span{
  width:72px;height:72px;border-radius:50%;
  background:linear-gradient(135deg,#f59e0b,#ef4444);
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
.settings-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
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
  display:flex;align-items:center;gap:12px;padding:14px;
  background:rgba(255,255,255,.03);
  border:2px dashed var(--border2);border-radius:10px;cursor:pointer;
  transition:border-color .15s;
}
.logo-upload:hover{border-color:var(--accent)}
.logo-upload i{font-size:24px;color:var(--text-dim)}
.logo-upload span{font-size:13px;color:var(--text-muted)}

/* MAINTENANCE - NEW DESIGN */
.maintenance-page-header{
  margin-bottom:28px;
  padding-bottom:22px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:flex-start;justify-content:space-between;
  flex-wrap:wrap;gap:14px;
}
.maintenance-page-header-left{display:flex;align-items:center;gap:16px}
.maintenance-header-icon{
  width:52px;height:52px;border-radius:14px;flex-shrink:0;
  background:linear-gradient(135deg,rgba(14,165,233,.2),rgba(99,102,241,.15));
  border:1px solid rgba(14,165,233,.25);
  display:flex;align-items:center;justify-content:center;
  font-size:22px;color:var(--accent2);
  box-shadow:0 4px 16px rgba(14,165,233,.15);
}
.maintenance-header-text h2{font-size:22px;font-weight:800;color:var(--text);line-height:1.2}
.maintenance-header-text p{font-size:13px;color:var(--text-muted);margin-top:5px;line-height:1.5}
.maintenance-header-badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 14px;border-radius:20px;
  font-size:12px;font-weight:700;
  background:rgba(16,185,129,.12);color:#34d399;
  border:1px solid rgba(16,185,129,.2);
  transition:all .3s ease;
}
.maintenance-header-badge.active-maintenance{
  background:rgba(239,68,68,.1);color:#f87171;
  border-color:rgba(239,68,68,.2);
}
.maintenance-header-badge .pulse-dot{
  width:8px;height:8px;border-radius:50%;
  background:#34d399;
  box-shadow:0 0 0 0 rgba(52,211,153,.4);
  animation:pulse-green 2s infinite;
  flex-shrink:0;
}
.maintenance-header-badge.active-maintenance .pulse-dot{
  background:#f87171;
  box-shadow:0 0 0 0 rgba(248,113,113,.4);
  animation:pulse-red 2s infinite;
}
@keyframes pulse-green{
  0%{box-shadow:0 0 0 0 rgba(52,211,153,.4)}
  70%{box-shadow:0 0 0 8px rgba(52,211,153,0)}
  100%{box-shadow:0 0 0 0 rgba(52,211,153,0)}
}
@keyframes pulse-red{
  0%{box-shadow:0 0 0 0 rgba(248,113,113,.4)}
  70%{box-shadow:0 0 0 8px rgba(248,113,113,0)}
  100%{box-shadow:0 0 0 0 rgba(248,113,113,0)}
}

/* STATUS BIG CARD */
.maint-status-card{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  margin-bottom:28px;
  position:relative;
  transition:border-color .4s ease;
}
.maint-status-card.is-on{border-color:rgba(239,68,68,.3);}
.maint-status-card::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(ellipse 50% 60% at 5% 50%, rgba(14,165,233,.06) 0%, transparent 60%),
    radial-gradient(ellipse 30% 40% at 95% 20%, rgba(99,102,241,.05) 0%, transparent 60%);
  transition:all .4s ease;
}
.maint-status-card.is-on::before{
  background:
    radial-gradient(ellipse 50% 60% at 5% 50%, rgba(239,68,68,.06) 0%, transparent 60%),
    radial-gradient(ellipse 30% 40% at 95% 20%, rgba(245,158,11,.04) 0%, transparent 60%);
}
.maint-card-top{
  padding:32px 36px 28px;
  display:flex;align-items:center;justify-content:space-between;
  gap:24px;flex-wrap:wrap;
  border-bottom:1px solid var(--border);
  position:relative;z-index:1;
}
.maint-status-left{display:flex;align-items:center;gap:20px}
.maint-big-icon{
  width:72px;height:72px;border-radius:18px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:28px;
  background:rgba(16,185,129,.12);color:#34d399;
  border:1px solid rgba(16,185,129,.2);
  box-shadow:0 8px 24px rgba(16,185,129,.15);
  transition:all .4s ease;
}
.maint-big-icon.danger{
  background:rgba(239,68,68,.12);color:#f87171;
  border-color:rgba(239,68,68,.2);
  box-shadow:0 8px 24px rgba(239,68,68,.15);
}
.maint-status-info h3{
  font-size:20px;font-weight:800;
  color:#34d399;line-height:1.2;
  transition:color .4s ease;
}
.maint-status-info h3.danger{color:#f87171}
.maint-status-info p{font-size:13px;color:var(--text-muted);margin-top:5px;line-height:1.5}
.maint-toggle-group{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.maint-card-bottom{
  display:grid;grid-template-columns:repeat(4,1fr);
  position:relative;z-index:1;
}
@media(max-width:900px){.maint-card-bottom{grid-template-columns:repeat(2,1fr)}}
.maint-info-cell{
  padding:20px 24px;
  border-left:1px solid var(--border);
  display:flex;flex-direction:column;gap:6px;
  transition:background .15s;
}
.maint-info-cell:last-child{border-left:none}
.maint-info-cell:hover{background:rgba(255,255,255,.02)}
.maint-info-label{
  font-size:11px;font-weight:700;color:var(--text-dim);
  letter-spacing:.6px;text-transform:uppercase;
  display:flex;align-items:center;gap:6px;
}
.maint-info-label i{font-size:12px;color:var(--accent)}
.maint-info-value{font-size:15px;font-weight:700;color:var(--text)}
.maint-info-sub{font-size:11.5px;color:var(--text-muted)}

/* ═══════════════════════════════════
   MAINTENANCE SETTINGS SECTION
═══════════════════════════════════ */
.maint-settings-section{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  margin-bottom:28px;
  position:relative;
}
.maint-settings-section::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(ellipse 40% 50% at 100% 0%, rgba(14,165,233,.05) 0%, transparent 60%),
    radial-gradient(ellipse 30% 40% at 0% 100%, rgba(99,102,241,.04) 0%, transparent 60%);
}
.maint-settings-header{
  padding:22px 28px 18px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;
  position:relative;z-index:1;
}
.maint-settings-header-icon{
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  background:linear-gradient(135deg,rgba(14,165,233,.18),rgba(99,102,241,.14));
  border:1px solid rgba(14,165,233,.22);
  display:flex;align-items:center;justify-content:center;
  font-size:17px;color:var(--accent2);
}
.maint-settings-header h3{
  font-size:16px;font-weight:800;color:var(--text);
}
.maint-settings-header p{
  font-size:12px;color:var(--text-muted);margin-top:2px;
}
.maint-settings-body{
  padding:24px 28px;
  position:relative;z-index:1;
}
.maint-settings-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
  margin-bottom:20px;
}
@media(max-width:720px){.maint-settings-grid{grid-template-columns:1fr}}
.maint-field{display:flex;flex-direction:column;gap:7px}
.maint-field.full{grid-column:1/-1}
.maint-field label{
  font-size:12px;font-weight:700;
  color:var(--text-muted);
  display:flex;align-items:center;gap:6px;
}
.maint-field label i{font-size:11px;color:var(--accent);opacity:.85}
.maint-field input[type=datetime-local],
.maint-field select,
.maint-field textarea{
  padding:10px 14px;
  border-radius:9px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--border2);
  color:var(--text);
  font-family:'Cairo',sans-serif;
  font-size:13px;
  outline:none;
  transition:border-color .18s,background .18s;
  width:100%;
}
.maint-field input[type=datetime-local]:focus,
.maint-field select:focus,
.maint-field textarea:focus{
  border-color:var(--accent);
  background:rgba(14,165,233,.06);
}
.maint-field select option{background:#1a2540;color:var(--text)}
.maint-field textarea{resize:vertical;min-height:90px;line-height:1.7}
/* Toggle switch row */
.maint-toggle-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 18px;
  background:rgba(255,255,255,.03);
  border:1px solid var(--border2);
  border-radius:10px;
  margin-bottom:20px;
}
.maint-toggle-label{display:flex;align-items:center;gap:12px}
.maint-toggle-icon{
  width:38px;height:38px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;
  background:rgba(16,185,129,.12);color:#34d399;
  border:1px solid rgba(16,185,129,.2);
  transition:all .35s ease;
}
.maint-toggle-icon.on{
  background:rgba(239,68,68,.12);color:#f87171;
  border-color:rgba(239,68,68,.2);
}
.maint-toggle-text strong{
  font-size:13.5px;font-weight:700;color:var(--text);display:block;
}
.maint-toggle-text span{
  font-size:12px;color:var(--text-muted);display:block;margin-top:2px;
}
/* Custom toggle switch */
.toggle-switch{
  position:relative;
  width:52px;height:28px;
  flex-shrink:0;
}
.toggle-switch input{display:none}
.toggle-track{
  position:absolute;inset:0;border-radius:14px;
  background:rgba(255,255,255,.1);
  border:1px solid var(--border2);
  cursor:pointer;
  transition:all .28s ease;
}
.toggle-track::after{
  content:'';
  position:absolute;
  top:3px;right:3px;
  width:20px;height:20px;border-radius:50%;
  background:var(--text-dim);
  transition:all .28s ease;
  box-shadow:0 2px 6px rgba(0,0,0,.3);
}
.toggle-switch input:checked ~ .toggle-track{
  background:rgba(239,68,68,.25);
  border-color:rgba(239,68,68,.45);
}
.toggle-switch input:checked ~ .toggle-track::after{
  right:auto;left:3px;
  background:#f87171;
  box-shadow:0 2px 10px rgba(239,68,68,.4);
}
/* Divider */
.maint-settings-divider{
  height:1px;background:var(--border);margin:0 0 20px;
}
/* Action buttons row */
.maint-settings-actions{
  display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  padding-top:4px;
}
.btn-maint-save{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:10px;border:none;
  background:linear-gradient(135deg,#0ea5e9,#0284c7);
  color:#fff;font-family:'Cairo',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;transition:all .2s;
  box-shadow:0 4px 16px rgba(14,165,233,.28);
}
.btn-maint-save:hover{filter:brightness(1.12);transform:translateY(-1px);box-shadow:0 6px 20px rgba(14,165,233,.38)}
.btn-maint-enable{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:10px;border:none;
  background:linear-gradient(135deg,#ef4444,#dc2626);
  color:#fff;font-family:'Cairo',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;transition:all .2s;
  box-shadow:0 4px 16px rgba(239,68,68,.28);
}
.btn-maint-enable:hover{filter:brightness(1.12);transform:translateY(-1px);box-shadow:0 6px 20px rgba(239,68,68,.38)}
.btn-maint-disable{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:10px;
  background:rgba(16,185,129,.12);
  border:1px solid rgba(16,185,129,.28);
  color:#34d399;font-family:'Cairo',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;transition:all .2s;
}
.btn-maint-disable:hover{background:rgba(16,185,129,.22);border-color:rgba(16,185,129,.45);transform:translateY(-1px)}

/* Light mode overrides */
body.light .maint-settings-section{background:#fff;border-color:#d1dce8}
body.light .maint-settings-header{border-bottom-color:#e8eef6}
body.light .maint-settings-header h3{color:#1e293b}
body.light .maint-settings-header p{color:#64748b}
body.light .maint-toggle-row{background:rgba(0,0,0,.02);border-color:#d1dce8}
body.light .maint-toggle-text strong{color:#1e293b}
body.light .maint-toggle-text span{color:#64748b}
body.light .toggle-track{background:rgba(0,0,0,.06);border-color:#c0cedf}
body.light .maint-field label{color:#64748b}
body.light .maint-field input[type=datetime-local],
body.light .maint-field select,
body.light .maint-field textarea{background:rgba(0,0,0,.03);border-color:#c0cedf;color:#1e293b}
body.light .maint-settings-divider{background:#e8eef6}
body.light .maint-field input[type=datetime-local]:focus,
body.light .maint-field select:focus,
body.light .maint-field textarea:focus{background:rgba(2,132,199,.04);border-color:#0284c7}

/* ═══════════════════════════════════
   MAINTENANCE MESSAGE PREVIEW SECTION
═══════════════════════════════════ */
.maint-msg-section{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  margin-bottom:28px;
  position:relative;
}
.maint-msg-section::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(ellipse 50% 40% at 100% 100%,rgba(99,102,241,.05) 0%,transparent 60%);
}
.maint-section-hd{
  padding:20px 28px 16px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;
  position:relative;z-index:1;
}
.maint-section-hd-icon{
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:17px;
}
.maint-section-hd h3{font-size:15px;font-weight:800;color:var(--text)}
.maint-section-hd p{font-size:12px;color:var(--text-muted);margin-top:2px}
.maint-msg-body{padding:24px 28px;position:relative;z-index:1}
.maint-msg-textarea{
  width:100%;
  min-height:120px;
  padding:14px 16px;
  border-radius:10px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--border2);
  color:var(--text);
  font-family:'Cairo',sans-serif;
  font-size:14px;
  line-height:1.8;
  outline:none;
  resize:vertical;
  transition:border-color .18s,background .18s;
  margin-bottom:20px;
}
.maint-msg-textarea:focus{border-color:var(--accent);background:rgba(14,165,233,.04)}
.maint-preview-label{
  display:flex;align-items:center;gap:7px;
  font-size:11.5px;font-weight:700;color:var(--accent2);
  letter-spacing:.5px;text-transform:uppercase;
  margin-bottom:14px;
}
.maint-preview-label i{font-size:13px}
.maint-preview-box{
  background:linear-gradient(135deg,#0d1a2e 0%,#0f1f38 100%);
  border:1px solid rgba(14,165,233,.2);
  border-radius:14px;
  padding:32px 28px;
  text-align:center;
  position:relative;
  overflow:hidden;
}
.maint-preview-box::before{
  content:'';position:absolute;
  top:-60px;left:50%;transform:translateX(-50%);
  width:300px;height:200px;
  background:radial-gradient(circle,rgba(14,165,233,.12) 0%,transparent 70%);
  pointer-events:none;
}
.maint-preview-icon{
  width:64px;height:64px;border-radius:16px;
  background:rgba(239,68,68,.12);
  border:1px solid rgba(239,68,68,.25);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;color:#f87171;
  margin:0 auto 16px;
  box-shadow:0 8px 24px rgba(239,68,68,.15);
}
.maint-preview-title{
  font-size:20px;font-weight:800;color:#fff;
  margin-bottom:10px;
}
.maint-preview-title span{color:#38bdf8}
.maint-preview-msg{
  font-size:14px;color:#94a3b8;line-height:1.9;
  max-width:520px;margin:0 auto 18px;
  white-space:pre-wrap;
}
.maint-preview-badge{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 18px;border-radius:20px;
  font-size:12px;font-weight:700;
  background:rgba(239,68,68,.1);
  border:1px solid rgba(239,68,68,.25);
  color:#f87171;
}
.maint-preview-badge .pulse-dot{
  width:7px;height:7px;border-radius:50%;background:#f87171;
  animation:pulse-red 2s infinite;
}
.maint-msg-actions{
  display:flex;align-items:center;gap:10px;
  margin-top:20px;flex-wrap:wrap;
}

/* ═══════════════════════════════════
   MAINTENANCE ACCESS PERMISSIONS
═══════════════════════════════════ */
.maint-access-section{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  margin-bottom:28px;
  position:relative;
}
.maint-access-body{padding:24px 28px;position:relative;z-index:1}
.maint-access-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:12px;
}
@media(max-width:800px){.maint-access-grid{grid-template-columns:1fr 1fr}}
@media(max-width:500px){.maint-access-grid{grid-template-columns:1fr}}
.maint-access-item{
  display:flex;align-items:center;gap:13px;
  padding:14px 16px;
  border:1px solid var(--border2);
  border-radius:11px;
  background:rgba(255,255,255,.03);
  cursor:pointer;
  transition:all .18s;
  position:relative;
  overflow:hidden;
}
.maint-access-item::before{
  content:'';position:absolute;inset:0;
  background:var(--item-color,rgba(14,165,233,.06));
  opacity:0;transition:opacity .18s;
}
.maint-access-item:hover{border-color:rgba(14,165,233,.3)}
.maint-access-item:hover::before{opacity:1}
.maint-access-item.enabled{
  border-color:rgba(14,165,233,.35);
  background:rgba(14,165,233,.06);
}
.maint-access-item.enabled::before{opacity:0}
.maint-access-item input[type=checkbox]{display:none}
.maint-access-check{
  width:20px;height:20px;border-radius:6px;flex-shrink:0;
  border:1.5px solid var(--border2);
  background:rgba(255,255,255,.04);
  display:flex;align-items:center;justify-content:center;
  font-size:9px;color:transparent;
  transition:all .18s;
  position:relative;z-index:1;
}
.maint-access-item.enabled .maint-access-check{
  background:var(--accent);
  border-color:var(--accent);
  color:#fff;
}
.maint-access-icon{
  width:36px;height:36px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;
  transition:all .18s;
  position:relative;z-index:1;
}
.maint-access-info{flex:1;position:relative;z-index:1}
.maint-access-name{font-size:13px;font-weight:700;color:var(--text);display:block}
.maint-access-sub{font-size:11px;color:var(--text-muted);display:block;margin-top:2px}
.maint-access-status{
  font-size:10px;font-weight:700;
  padding:2px 8px;border-radius:10px;
  position:relative;z-index:1;
  transition:all .18s;
}
.maint-access-status.on{background:rgba(16,185,129,.15);color:#34d399}
.maint-access-status.off{background:rgba(248,113,113,.1);color:#f87171}

/* Locked items (always on) */
.maint-access-item.locked{cursor:not-allowed;opacity:.75}
.maint-access-item.locked:hover{border-color:var(--border2)}
.maint-access-item.locked::before{opacity:0!important}

/* ═══════════════════════════════════
   MAINTENANCE LOG TABLE
═══════════════════════════════════ */
.maint-log-section{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  margin-bottom:28px;
}
.maint-log-table-wrap{overflow-x:auto}
.maint-log-table{width:100%;border-collapse:collapse;min-width:820px}
.maint-log-table th{
  padding:11px 16px;text-align:right;
  font-size:11px;font-weight:700;color:var(--text-dim);
  letter-spacing:.6px;text-transform:uppercase;
  background:rgba(255,255,255,.02);
  border-bottom:1px solid var(--border);
  white-space:nowrap;
}
.maint-log-table td{
  padding:13px 16px;font-size:13px;color:var(--text);
  border-bottom:1px solid var(--border);
  vertical-align:middle;
}
.maint-log-table tbody tr:last-child td{border-bottom:none}
.maint-log-table tbody tr:hover{background:var(--bg-hover)}
.maint-log-type{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;
  background:rgba(99,102,241,.12);color:#a5b4fc;
  white-space:nowrap;
}
.maint-log-reason{
  max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  color:var(--text-muted);
}
.maint-log-admin{display:flex;align-items:center;gap:7px;white-space:nowrap}
.maint-log-admin-av{
  width:26px;height:26px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;color:#fff;
}

/* Light overrides */
body.light .maint-msg-section,body.light .maint-access-section,body.light .maint-log-section{background:#fff;border-color:#d1dce8}
body.light .maint-section-hd{border-bottom-color:#e8eef6}
body.light .maint-section-hd h3{color:#1e293b}
body.light .maint-section-hd p{color:#64748b}
body.light .maint-msg-textarea{background:rgba(0,0,0,.03);border-color:#c0cedf;color:#1e293b}
body.light .maint-msg-textarea:focus{background:rgba(2,132,199,.04);border-color:#0284c7}
body.light .maint-preview-box{background:linear-gradient(135deg,#dbeafe,#eff6ff);border-color:#bfdbfe}
body.light .maint-preview-title{color:#1e293b}
body.light .maint-preview-msg{color:#475569}
body.light .maint-access-item{background:rgba(0,0,0,.02);border-color:#d1dce8}
body.light .maint-access-item.enabled{background:rgba(2,132,199,.06);border-color:rgba(2,132,199,.3)}
body.light .maint-access-name{color:#1e293b}
body.light .maint-access-sub{color:#64748b}
body.light .maint-log-table th{color:#94a3b8;background:rgba(0,0,0,.02)}
body.light .maint-log-table td{color:#1e293b;border-bottom-color:#e2e8f0}
body.light .maint-log-table tbody tr:hover{background:#f1f5f9}
body.light .maint-log-reason{color:#64748b}

/* old refs kept for JS compat */
.maintenance-card{display:none}
.maintenance-alert{display:none!important}
body.light .maintenance-card{display:none}
body.light .maintenance-page-header{border-bottom-color:#d1dce8}
body.light .maintenance-header-icon{background:rgba(2,132,199,.1);border-color:rgba(2,132,199,.2)}
body.light .maintenance-header-text h2{color:#1e293b}
body.light .maintenance-header-text p{color:#64748b}
body.light .maintenance-header-badge{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.2)}
body.light .maintenance-header-badge.active-maintenance{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2)}
body.light .maint-status-card{background:#fff;border-color:#d1dce8}
body.light .maint-card-top{border-bottom-color:#d1dce8}
body.light .maint-info-cell{border-left-color:#d1dce8}
body.light .maint-info-cell:hover{background:rgba(0,0,0,.02)}
body.light .maint-info-value{color:#1e293b}
body.light .maint-info-sub{color:#64748b}

/* MODAL */
.modal-overlay{
  display:none;position:fixed;inset:0;z-index:200;
  background:rgba(0,0,0,.7);backdrop-filter:blur(4px);
  align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--bg-card2);border:1px solid var(--border2);
  border-radius:var(--radius);padding:28px;
  width:100%;max-width:520px;
  position:relative;animation:fadeIn .2s ease;
  max-height:90vh;overflow-y:auto;
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
.modal-field input:focus,.modal-field select:focus{border-color:var(--accent)}
.modal-field select option{background:#1a2540;color:var(--text)}
.modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px}
.modal-info-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:10px 14px;background:var(--bg-card2);border-radius:8px;
  border:1px solid var(--border);margin-bottom:10px;
}
.modal-info-row span:first-child{font-size:12px;color:var(--text-muted)}
.modal-info-row span:last-child{font-size:13px;font-weight:600;color:var(--text)}

/* TOAST */
.toast{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
  background:#1a2540;border:1px solid var(--border2);
  border-radius:10px;padding:12px 22px;
  font-size:13.5px;color:#fff;font-weight:600;
  opacity:0;pointer-events:none;
  transition:all .3s ease;z-index:999;
  box-shadow:0 8px 24px rgba(0,0,0,.4);white-space:nowrap;
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-dim)}
.empty-state i{font-size:36px;margin-bottom:12px;display:block;color:var(--border2)}
.empty-state p{font-size:14px}

/* SCROLLBAR */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}
body.light ::-webkit-scrollbar-thumb{background:#c8d8e8}
body.light ::-webkit-scrollbar-track{background:transparent}

/* TWO-COL GRID */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:900px){.two-col{grid-template-columns:1fr}.settings-layout{grid-template-columns:1fr}}

/* ═══════════════════════════════════
   REQUEST MODAL STYLES
═══════════════════════════════════ */
.req-header-card{
  display:flex;align-items:center;gap:16px;
  padding:18px;
  background:linear-gradient(135deg,rgba(14,165,233,.1),rgba(99,102,241,.08));
  border:1px solid rgba(14,165,233,.2);
  border-radius:12px;margin-bottom:18px;
}
.req-avatar{
  width:56px;height:56px;border-radius:14px;flex-shrink:0;
  background:linear-gradient(135deg,#0ea5e9,#6366f1);
  display:flex;align-items:center;justify-content:center;
  font-size:22px;color:#fff;
  box-shadow:0 4px 14px rgba(14,165,233,.3);
}
.req-name{font-size:17px;font-weight:800;color:#fff}
.req-section-label{
  font-size:11.5px;font-weight:700;
  color:var(--accent);letter-spacing:.6px;text-transform:uppercase;
  display:flex;align-items:center;gap:7px;
  margin:16px 0 10px;padding-bottom:7px;
  border-bottom:1px solid var(--border);
}
.req-info-grid{display:flex;flex-direction:column;gap:6px}
.req-info-grid .modal-info-row span:first-child{
  display:flex;align-items:center;gap:7px;
  font-size:12px;color:var(--text-muted);min-width:140px;
}
.req-info-grid .modal-info-row span:first-child i{
  width:16px;text-align:center;color:var(--text-dim);
}
.req-type-grid{
  display:flex;flex-wrap:wrap;gap:8px;
}
.req-type-chip{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;
  background:rgba(255,255,255,.04);
  border:1px solid var(--border2);color:var(--text-muted);
  cursor:default;transition:all .15s;
}
.req-type-chip.active{
  background:rgba(14,165,233,.15);
  border-color:rgba(14,165,233,.4);
  color:var(--accent2);
}
.req-docs-list{display:flex;flex-direction:column;gap:6px}
.req-doc-item{
  display:flex;align-items:center;gap:10px;
  padding:9px 14px;
  background:rgba(16,185,129,.06);
  border:1px solid rgba(16,185,129,.15);
  border-radius:8px;
}
.req-doc-icon{
  width:30px;height:30px;border-radius:7px;
  background:rgba(16,185,129,.12);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;color:#34d399;flex-shrink:0;
}
.req-doc-item span{font-size:13px;font-weight:600;color:var(--text);flex:1}
.req-doc-check{color:#34d399;font-size:15px}
.req-reject-note{
  display:flex;align-items:flex-start;gap:12px;
  padding:14px;margin-top:14px;
  background:rgba(239,68,68,.08);
  border:1px solid rgba(239,68,68,.2);
  border-radius:10px;color:#f87171;
  font-size:13px;
}
.req-reject-note i{font-size:18px;flex-shrink:0;margin-top:2px}
.req-divider{
  height:1px;background:var(--border);
  margin:18px 0 14px;
}
.req-reject-field{margin-bottom:12px}
.req-reject-field label{
  display:flex;align-items:center;gap:6px;
  font-size:12.5px;font-weight:700;color:var(--text-muted);margin-bottom:8px;
}
.req-reject-field textarea{
  width:100%;padding:10px 12px;border-radius:8px;
  background:rgba(239,68,68,.05);
  border:1px solid rgba(239,68,68,.25);
  color:var(--text);font-family:'Cairo',sans-serif;font-size:13px;
  outline:none;resize:vertical;min-height:75px;
  transition:border-color .15s;
}
.req-reject-field textarea:focus{border-color:#f87171;background:rgba(239,68,68,.08)}
.req-modal-actions{
  display:flex;align-items:center;justify-content:flex-end;
  gap:10px;flex-wrap:wrap;
}
.req-btn-approve{
  display:inline-flex;align-items:center;gap:7px;
  padding:10px 20px;border-radius:9px;border:none;
  background:linear-gradient(135deg,#10b981,#059669);
  color:#fff;font-family:'Cairo',sans-serif;font-size:13px;font-weight:700;
  cursor:pointer;transition:all .18s;
  box-shadow:0 4px 14px rgba(16,185,129,.3);
}
.req-btn-approve:hover{filter:brightness(1.1);transform:translateY(-1px)}
.req-btn-reject{
  display:inline-flex;align-items:center;gap:7px;
  padding:10px 20px;border-radius:9px;
  background:rgba(239,68,68,.12);
  border:1px solid rgba(239,68,68,.25);
  color:#f87171;font-family:'Cairo',sans-serif;font-size:13px;font-weight:700;
  cursor:pointer;transition:all .18s;
}
.req-btn-reject:hover{background:rgba(239,68,68,.22)}
.req-btn-confirm-reject{
  display:inline-flex;align-items:center;gap:7px;
  padding:10px 20px;border-radius:9px;border:none;
  background:linear-gradient(135deg,#ef4444,#dc2626);
  color:#fff;font-family:'Cairo',sans-serif;font-size:13px;font-weight:700;
  cursor:pointer;transition:all .18s;
  box-shadow:0 4px 14px rgba(239,68,68,.3);
}
.req-btn-confirm-reject:hover{filter:brightness(1.1)}

/* ═══════════════════════════════════
   ADMINS PAGE STYLES
═══════════════════════════════════ */
.admins-stats-row{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:14px;margin-bottom:22px;
}
@media(max-width:900px){.admins-stats-row{grid-template-columns:repeat(2,1fr)}}
.admin-stat-chip{
  display:flex;align-items:center;gap:12px;
  padding:14px 16px;
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);
  position:relative;overflow:hidden;
}
.admin-stat-chip::before{
  content:'';position:absolute;top:-20px;right:-20px;
  width:80px;height:80px;
  background:radial-gradient(circle,var(--chip-color) 0%,transparent 70%);
  opacity:.1;pointer-events:none;
}
.admin-stat-icon{
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:16px;
}
.admin-stat-val{font-size:24px;font-weight:800;color:#fff;line-height:1}
.admin-stat-lbl{font-size:11px;color:var(--text-muted);margin-top:2px}

/* Add/Edit admin form */
.admin-form-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;
}
@media(max-width:600px){.admin-form-grid{grid-template-columns:1fr}}

/* Permissions box */
.admin-perms-box{
  background:rgba(14,165,233,.04);
  border:1px solid rgba(14,165,233,.15);
  border-radius:12px;padding:18px;margin-bottom:18px;
}
.admin-perms-title{
  font-size:13px;font-weight:700;color:var(--accent2);
  display:flex;align-items:center;gap:8px;margin-bottom:14px;
}
.admin-perms-preset{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;
}
.admin-preset-btn{
  padding:4px 12px;border-radius:20px;border:1px solid var(--border2);
  background:rgba(255,255,255,.05);color:var(--text-muted);
  font-family:'Cairo',sans-serif;font-size:11.5px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.admin-preset-btn:hover{background:rgba(14,165,233,.12);border-color:rgba(14,165,233,.3);color:var(--accent2)}
.admin-perms-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:6px;
}
@media(max-width:560px){.admin-perms-grid{grid-template-columns:1fr}}
.admin-perm-group-label{
  grid-column:1/-1;
  font-size:10.5px;font-weight:700;letter-spacing:.6px;
  color:var(--text-dim);text-transform:uppercase;
  padding:6px 0 2px;margin-top:6px;
  border-bottom:1px solid var(--border);
}
.admin-perm-item{
  display:flex;align-items:center;gap:9px;
  padding:8px 10px;border-radius:8px;cursor:pointer;
  transition:background .13s;
}
.admin-perm-item:hover{background:rgba(14,165,233,.07)}
.admin-perm-item input[type=checkbox]{display:none}
.admin-perm-check{
  width:18px;height:18px;border-radius:5px;flex-shrink:0;
  border:1.5px solid var(--border2);
  background:rgba(255,255,255,.04);
  display:flex;align-items:center;justify-content:center;
  font-size:9px;color:transparent;
  transition:all .15s;
}
.admin-perm-item input:checked ~ .admin-perm-check{
  background:var(--accent);border-color:var(--accent);color:#fff;
}
.admin-perm-label{font-size:12.5px;color:var(--text-muted)}
.admin-perm-item input:checked ~ .admin-perm-check ~ .admin-perm-label{color:var(--text);font-weight:600}

body.light .admin-perms-box{background:rgba(2,132,199,.03);border-color:rgba(2,132,199,.15)}
body.light .admin-stat-chip{background:#fff;border-color:#d1dce8}
body.light .admin-stat-val{color:#1e293b}

/* ═══════════════════════════════════
   PROFESSIONAL USER ACTION BUTTONS
═══════════════════════════════════ */
.btn-action{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:8px;border:1px solid transparent;
  font-family:'Cairo',sans-serif;font-size:12px;font-weight:600;
  cursor:pointer;transition:all .18s ease;white-space:nowrap;
}
.btn-action span{font-size:12px}
.btn-action-view{
  background:rgba(14,165,233,.1);
  border-color:rgba(14,165,233,.25);
  color:#38bdf8;
}
.btn-action-view:hover{background:rgba(14,165,233,.22);border-color:rgba(14,165,233,.45);transform:translateY(-1px)}
.btn-action-edit{
  background:rgba(245,158,11,.1);
  border-color:rgba(245,158,11,.25);
  color:#fbbf24;
}
.btn-action-edit:hover{background:rgba(245,158,11,.22);border-color:rgba(245,158,11,.45);transform:translateY(-1px)}
.btn-action-disable{
  background:rgba(239,68,68,.08);
  border-color:rgba(239,68,68,.2);
  color:#f87171;
}
.btn-action-disable:hover{background:rgba(239,68,68,.18);border-color:rgba(239,68,68,.4);transform:translateY(-1px)}
.btn-action-enable{
  background:rgba(16,185,129,.1);
  border-color:rgba(16,185,129,.25);
  color:#34d399;
}
.btn-action-enable:hover{background:rgba(16,185,129,.2);border-color:rgba(16,185,129,.45);transform:translateY(-1px)}
.btn-action-delete{
  background:rgba(239,68,68,.07);
  border-color:rgba(239,68,68,.18);
  color:#fc8181;
}
.btn-action-delete:hover{background:rgba(239,68,68,.18);border-color:rgba(239,68,68,.4);color:#f87171;transform:translateY(-1px)}

body.light .btn-action-view{background:rgba(2,132,199,.07);border-color:rgba(2,132,199,.2);color:#0284c7}
body.light .btn-action-edit{background:rgba(217,119,6,.07);border-color:rgba(217,119,6,.2);color:#b45309}
body.light .btn-action-disable{background:rgba(220,38,38,.06);border-color:rgba(220,38,38,.18);color:#dc2626}
body.light .btn-action-enable{background:rgba(5,150,105,.07);border-color:rgba(5,150,105,.2);color:#059669}
body.light .btn-action-delete{background:rgba(220,38,38,.05);border-color:rgba(220,38,38,.15);color:#dc2626}

/* ═══════════════════════════════════
   ENSURE SINGLE-ROW ACTIONS IN ADMINS TABLE
═══════════════════════════════════ */
#section-admins .td-actions {
  flex-wrap: nowrap !important;
  gap: 5px;
}
#section-admins .btn-action {
  padding: 6px 10px;
  font-size: 11.5px;
  white-space: nowrap;
}
#section-admins .btn-action span {
  font-size: 11.5px;
}
#section-admins .data-table th:last-child,
#section-admins .data-table td:last-child {
  min-width: 250px;
}
/* Slightly reduce email column on admins table to give actions more room */
#section-admins .data-table td:nth-child(3) {
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ═══════════════════════════════════
   ENSURE SINGLE-ROW ACTIONS IN INSTITUTIONS TABLE
═══════════════════════════════════ */
#section-institutions .td-actions {
  flex-wrap: nowrap !important;
  gap: 5px;
}
#section-institutions .btn-action {
  padding: 6px 10px;
  font-size: 11.5px;
  white-space: nowrap;
  flex-shrink: 0;
}
#section-institutions .btn-action span {
  font-size: 11.5px;
}
#section-institutions .data-table th:last-child,
#section-institutions .data-table td:last-child {
  min-width: 260px;
  white-space: nowrap;
}
/* Compress less-critical columns slightly to free up space */
#section-institutions .data-table th:nth-child(4),
#section-institutions .data-table td:nth-child(4) {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
#section-institutions .data-table th:nth-child(6),
#section-institutions .data-table td:nth-child(6) {
  white-space: nowrap;
}
/* Responsive: allow horizontal scroll on small screens */
#section-institutions .data-table-wrap {
  overflow-x: auto;
}
#section-institutions .data-table {
  min-width: 900px;
}

/* ═══════════════════════════════════
   INSTITUTIONS PAGE — STATS & FILTER
═══════════════════════════════════ */
.inst-stats-row{
  display:flex;flex-wrap:wrap;gap:12px;
  margin-bottom:18px;
}
.inst-stat-chip{
  display:flex;align-items:center;gap:10px;
  padding:12px 16px;
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);
  flex:1;min-width:130px;
  position:relative;overflow:hidden;
  transition:transform .18s,box-shadow .18s;
}
.inst-stat-chip::before{
  content:'';position:absolute;top:-20px;right:-20px;
  width:70px;height:70px;
  background:radial-gradient(circle,var(--chip-clr) 0%,transparent 70%);
  opacity:.1;pointer-events:none;
}
.inst-stat-icon{
  width:38px;height:38px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:15px;
}
.inst-stat-val{font-size:22px;font-weight:800;color:var(--text);line-height:1}
.inst-stat-lbl{font-size:11px;color:var(--text-muted);margin-top:2px}

/* Filter tabs */
.inst-filter-tabs{
  display:flex;flex-wrap:wrap;gap:8px;
  margin-bottom:18px;padding:14px 16px;
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);
}
.inst-tab{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 16px;border-radius:8px;
  border:1px solid var(--border2);
  background:rgba(255,255,255,.04);
  color:var(--text-muted);
  font-family:'Cairo',sans-serif;font-size:13px;font-weight:600;
  cursor:pointer;transition:all .18s;
}
.inst-tab:hover{background:var(--bg-hover);color:var(--text);border-color:var(--accent)}
.inst-tab.active{
  background:linear-gradient(135deg,rgba(14,165,233,.18),rgba(99,102,241,.12));
  border-color:rgba(14,165,233,.4);
  color:var(--accent2);
  box-shadow:0 2px 10px rgba(14,165,233,.12);
}
.inst-tab i{font-size:12px}

body.light .inst-stat-chip{background:#fff;border-color:#d1dce8}
body.light .inst-stat-val{color:#1e293b}
body.light .inst-filter-tabs{background:#fff;border-color:#d1dce8}
body.light .inst-tab{background:rgba(0,0,0,.03);border-color:#c0cedf;color:#64748b}
body.light .inst-tab:hover{background:#e8f0fa;color:#1e293b}
body.light .inst-tab.active{background:linear-gradient(135deg,rgba(2,132,199,.12),rgba(99,102,241,.08));border-color:rgba(2,132,199,.35);color:#0284c7}
.header-btn{
    position: relative;
}

.notifications-dropdown{
    display: none;
    position: absolute;
    top: 60px;
    left: 0;
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    z-index: 9999;
    padding: 10px;
}

.notification-item{
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.notification-item:last-child{
    border-bottom: none;
}

.notification-item.unread{
    background: #f0f9ff;
}

.notification-title{
    font-weight: 700;
    margin-bottom: 5px;
}

.notification-message{
    color: #64748b;
    font-size: 14px;
    margin-bottom: 5px;
}

.notification-time{
    font-size: 12px;
    color: #94a3b8;
}

.notification-empty{
    text-align: center;
    padding: 20px;
    color: #64748b;
}
</style>
</head>
<body>
<script>
/* تطبيق class على body قبل رسم أي عنصر */
(function(){
  if(localStorage.getItem('mcg_theme') !== 'dark'){
    document.body.classList.add('light');
  }
})();
</script>
<div class="bg-mesh"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-glow"></div>
  <div class="sidebar-logo">
    <?php if ($platformLogoPath): ?>
      <div class="logo-icon" style="background:transparent;padding:0;width:38px;height:38px;overflow:hidden;border-radius:8px;">
        <img src="<?= htmlspecialchars($platformLogoPath) ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
      </div>
    <?php else: ?>
      <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <?php endif; ?>
    <div>
      <span class="logo-brand">Med<em>Chifa</em>Giz</span>
      <span class="logo-sub"><?= htmlspecialchars($panelLabel) ?></span>
    </div>
  </div>
  <div class="sidebar-profile">
    <div class="profile-avatar" id="sidebarAvatarContainer">
      <span id="sidebarAvatarSpan" style="background:linear-gradient(135deg,rgba(14,165,233,.25),rgba(99,102,241,.20));display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-circle-user" style="font-size:26px;color:var(--role-color);"></i>
      </span>
      <div class="avatar-ring"></div>
      <div class="online-dot"></div>
    </div>
    <div>
      <span class="profile-name"><?= htmlspecialchars($currentUserName) ?></span>
      <span class="profile-role"><i class="fas <?= $roleIcon ?>"></i> <?= htmlspecialchars($currentRoleLabel) ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item active" onclick="switchSection('dashboard',this)">
      <div class="nav-icon"><i class="fas fa-gauge-high"></i></div>
      الرئيسية
    </div>
   <?php if(
    hasPermission('viewRequests') ||
    hasPermission('manageRequests')
): ?>

<div class="nav-item" onclick="switchSection('requests',this)">
  <div class="nav-icon"><i class="fas fa-file-circle-check"></i></div>
  طلبات التسجيل
  <?php if((int)$totalPending > 0): ?>
  <span class="nav-badge"><?= (int)$totalPending ?></span>
  <?php endif; ?>
</div>

<?php endif; ?>
    <?php if(
    hasPermission('viewUsers') ||
   hasPermission('manageUsers') ||
    hasPermission('viewInstitutions') ||
    hasPermission('manageInstitutions')
): ?>

<div class="nav-item" onclick="switchSection('institutions',this)">
  <div class="nav-icon"><i class="fas fa-users-rectangle"></i></div>
  إدارة الحسابات
</div>
<?php if(
    hasPermission('viewAdmins') ||
    hasPermission('manageAdmins')
): ?>

<div class="nav-item" onclick="switchSection('admins',this)">
  <div class="nav-icon"><i class="fas fa-user-shield"></i></div>
  إدارة المسؤولين
</div>

<?php endif; ?>
<?php endif; ?>
    <?php if(hasPermission('viewStats')): ?>

<div class="nav-item" onclick="switchSection('stats',this)">
    <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
    إحصائيات المنصة
</div>

<?php endif; ?>
    <?php if(hasPermission('viewActivities')): ?>

<div class="nav-item" onclick="switchSection('activity',this)">
  <div class="nav-icon"><i class="fas fa-list-check"></i></div>
  سجل النشاطات
</div>

<?php endif; ?>
<?php if(hasPermission('manageSettings')): ?>

<div class="nav-item" onclick="switchSection('settings',this)">
  <div class="nav-icon"><i class="fas fa-sliders"></i></div>
  إعدادات المنصة
</div>

<?php endif; ?>




  <?php if(hasPermission('manageMaintenance')): ?>

<div class="nav-item" onclick="switchSection('maintenance',this)">
  <div class="nav-icon"><i class="fas fa-screwdriver-wrench"></i></div>
  وضع الصيانة
</div>

<?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="nav-item danger" onclick="openModal('logoutConfirmModal')">
      <div class="nav-icon"><i class="fas fa-right-from-bracket"></i></div>
      تسجيل الخروج
    </div>
  </div>
</aside>

<!-- LOGOUT CONFIRM MODAL -->
<div class="modal-overlay" id="logoutConfirmModal">
  <div class="modal" style="max-width:420px;text-align:center">
    <button class="modal-close" onclick="closeModal('logoutConfirmModal')"><i class="fas fa-times"></i></button>
    <div style="display:flex;align-items:center;justify-content:center;margin-bottom:16px">
      <div style="width:56px;height:56px;border-radius:14px;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);display:flex;align-items:center;justify-content:center;font-size:24px;color:#f87171">
        <i class="fas fa-right-from-bracket"></i>
      </div>
    </div>
    <div class="modal-title" style="justify-content:center;border-bottom:none;padding-bottom:4px">
      تأكيد تسجيل الخروج
    </div>
    <p style="font-size:13.5px;color:var(--text-muted);margin:10px 0 24px;line-height:1.7">
      هل أنت متأكد أنك تريد تسجيل الخروج من منصة MedChifaGiz؟
    </p>
    <div class="modal-actions" style="justify-content:center;gap:12px">
      <button class="btn-secondary" onclick="closeModal('logoutConfirmModal')">
        <i class="fas fa-times"></i> إلغاء
      </button>
      <form method="POST" action="" style="margin:0;padding:0">
        <input type="hidden" name="logout_confirmed" value="1">
        <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#ef4444,#dc2626);border-color:#ef4444">
          <i class="fas fa-right-from-bracket"></i> تسجيل الخروج
        </button>
      </form>
    </div>
  </div>
</div>

<!-- MAIN -->
<div class="main" id="mainContent">

  <!-- HEADER -->
  <header class="header">
    <div class="header-right">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
      <span class="header-title" id="headerTitle">لوحة التحكم</span>
      <span class="super-badge role-badge"><i class="fas <?= $roleIcon ?>"></i> <?= htmlspecialchars($currentRoleLabel) ?></span>
    </div>
    <div class="header-center">
      <div>
        <div class="header-time" id="headerTime">00:00</div>
        <div class="header-date" id="headerDate"></div>
      </div>
    </div>
    <div class="header-left">
      <div class="header-search">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" placeholder="بحث سريع...">
      </div>
      <button class="header-btn" onclick="toggleTheme()"><i class="fas fa-circle-half-stroke"></i></button>
      <?php
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM super_admin_notifications
    WHERE super_admin_id = ?
    AND is_read = 0
    AND (
        title != 'طلب تسجيل جديد'
        OR (
            title = 'طلب تسجيل جديد'
            AND CAST(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(message, '[ID:', -1),
                    ']', 1
                ) AS UNSIGNED
            ) IN (
                SELECT id FROM users WHERE status = 'pending'
            )
        )
    )
");

$stmt->execute([$_SESSION['user_id']]);

$unreadCount = $stmt->fetchColumn();
?>

<div style="position:relative; display:inline-block;">

    <button class="header-btn" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>

        <?php if($unreadCount > 0): ?>
            <span class="btn-badge">
                <?= $unreadCount ?>
            </span>
        <?php endif; ?>
    </button>

    <div class="notifications-dropdown" id="notificationsDropdown">

        <?php
        $stmt = $pdo->prepare("
           SELECT id, title, message, created_at, is_read
            FROM super_admin_notifications
            WHERE super_admin_id = ?
              AND (
                  title != 'طلب تسجيل جديد'
                  OR (
                      title = 'طلب تسجيل جديد'
                      AND CAST(
                          SUBSTRING_INDEX(
                              SUBSTRING_INDEX(message, '[ID:', -1),
                              ']', 1
                          ) AS UNSIGNED
                      ) IN (
                          SELECT id FROM users WHERE status = 'pending'
                      )
                  )
              )
            ORDER BY created_at DESC
            LIMIT 10
        ");

        $stmt->execute([$_SESSION['user_id']]);

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($notifications as $notification) {

    if ($notification['is_read'] == 0) {

        $update = $pdo->prepare("
            UPDATE super_admin_notifications
            SET is_read = 1
            WHERE id = ?
        ");

        $update->execute([$notification['id']]);
    }
}
        ?>

        <?php if(empty($notifications)): ?>

            <div class="notification-empty">
                لا توجد إشعارات جديدة
            </div>

        <?php else: ?>

            <?php foreach($notifications as $notification): ?>

                <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">

                    <div class="notification-title">
                        <?= htmlspecialchars($notification['title']) ?>
                    </div>

                    <div class="notification-message">
                        <?= htmlspecialchars(preg_replace('/\s*\[ID:\d+\]/', '', $notification['message'])) ?>
                    </div>

                    <div class="notification-time">
                        <?= $notification['created_at'] ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>
    </div>
  </header>

  <!-- ══════════ DASHBOARD ══════════ -->
  <section class="section active" id="section-dashboard">
    <div class="welcome-bar">
      <div>
        <h1>مرحباً، <?= htmlspecialchars($currentUserName) ?> <span class="wave">👋</span></h1>
       <p>
لديك
<strong><?= $totalPending ?> طلبات تسجيل معلقة</strong>
تنتظر المراجعة اليوم.
</p>
      </div>
      <div class="welcome-icon"><i class="fas fa-crown"></i></div>
    </div>
    <div class="stats-grid">
      <div class="stat-card" style="--card-color:#0ea5e9">
        <div class="stat-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9"><i class="fas fa-users"></i></div>
       <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-label">إجمالي الحسابات</div>
       
      </div>
      <div class="stat-card" style="--card-color:#a78bfa">
        <div class="stat-icon" style="background:rgba(167,139,250,.12);color:#a78bfa"><i class="fas fa-hospital-user"></i></div>
        <div class="stat-value"><?= $totalPatients ?></div>
        <div class="stat-label">المرضى المسجلون</div>
       
      </div>
      <div class="stat-card" style="--card-color:#34d399">
        <div class="stat-icon" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fas fa-user-doctor"></i></div>
        <div class="stat-value"><?= $totalDoctors ?></div>
        <div class="stat-label">الأطباء</div>
       
      </div>
      <div class="stat-card" style="--card-color:#fb923c">
        <div class="stat-icon" style="background:rgba(251,146,60,.12);color:#fb923c"><i class="fas fa-hospital"></i></div>
<div class="stat-value"><?= $totalClinics ?></div>
        <div class="stat-label">مؤسسات صحية</div>
     
      </div>
       <div class="stat-card" style="--card-color:#fb923c">
        <div class="stat-icon" style="background:rgba(251,146,60,.12);color:#fb923c"><i class="fas fa-pills"></i></div>
<div class="stat-value"><?= $totalPharmacies ?></div>
        <div class="stat-label">الصيدليات</div>
     

      </div>
      <div class="stat-card" style="--card-color:#fb923c">
        <div class="stat-icon" style="background:rgba(251,146,60,.12);color:#fb923c"><i class="fas fa-flask"></i></div>
<div class="stat-value"><?= $totalLabs ?></div>
        <div class="stat-label">المخابر</div>
     

      </div>
      <div class="stat-card" style="--card-color:#38bdf8">
        <div class="stat-icon" style="background:rgba(56,189,248,.12);color:#38bdf8"><i class="fas fa-user-plus"></i></div>
      <div class="stat-value"><?= $newUsers ?></div>
        <div class="stat-label">حسابات جديدة</div>
      
      </div>
      <div class="stat-card" style="--card-color:#fbbf24">
        <div class="stat-icon" style="background:rgba(251,191,36,.12);color:#fbbf24"><i class="fas fa-hourglass-half"></i></div>
<div class="stat-value"><?= $totalPending ?></div>
        <div class="stat-label">طلبات معلقة</div>
   
      </div>
    </div>

    <div class="two-col">
      <!-- آخر التسجيلات -->
      <div>
        <div class="section-header">
          <div>
            <div class="section-title">آخر التسجيلات</div>
            <div class="section-sub">أحدث الحسابات المسجلة على المنصة</div>
          </div>
        </div>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>الاسم</th><th>النوع</th><th>التاريخ</th><th>الحالة</th></tr>
            </thead>
           <tbody>

<?php foreach($latestUsers as $user): ?>

<tr>

<td><?= htmlspecialchars($user['full_name']) ?></td>

<td>

<?php
switch($user['role']){

    case 'doctor':
        echo 'طبيب';
        break;

    case 'patient':
        echo 'مريض';
        break;

    case 'pharmacy':
        echo 'صيدلية';
        break;

    case 'clinic':
        echo 'عيادة';
        break;

    case 'lab':
        echo 'مخبر';
        break;

    case 'super_admin':
        echo 'سوبر أدمن';
        break;

    default:
        echo $user['role'];
}
?>

</td>

<td><?= htmlspecialchars($user['created_at']) ?></td>

<td>

<?php if($user['status'] == 'approved'): ?>

<span class="badge badge-active">
مفعّل
</span>

<?php elseif($user['status'] == 'pending'): ?>

<span class="badge badge-pending">
قيد المراجعة
</span>

<?php elseif($user['status'] == 'rejected'): ?>

<span class="badge badge-inactive">
مرفوض
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>
          </table>
        </div>
      </div>

      <!-- آخر النشاطات -->
      <div>
        <div class="section-header">
          <div>
            <div class="section-title">آخر النشاطات</div>
            <div class="section-sub">العمليات الأخيرة على المنصة</div>
          </div>
        </div>
        <div class="activity-list" id="dashActivityList">
         
           
          <?php foreach($latestActivities as $activity): ?>

<div class="activity-item">

    <div class="act-icon"
         style="background:rgba(16,185,129,.15);color:#34d399">
        <i class="fas fa-user-plus"></i>
    </div>

    <div class="act-body">

        <div class="act-title">
    <?php
switch($activity['role']) {
    case 'doctor':
        echo 'تسجيل طبيب جديد';
        break;

    case 'patient':
        echo 'تسجيل مريض جديد';
        break;

    case 'clinic':
        echo 'تسجيل عيادة جديدة';
        break;

    case 'pharmacy':
        echo 'تسجيل صيدلية جديدة';
        break;

    case 'lab':
        echo 'تسجيل مخبر جديد';
        break;

    default:
        echo $activity['role'];
}
?>
</div>

        <div class="act-sub">
            <?= htmlspecialchars($activity['full_name']) ?>
        </div>

        <div class="act-time">
            <i class="fas fa-clock"></i>
           <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
        </div>

    </div>

</div>

<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════ REGISTRATION REQUESTS ══════════ -->
  <section class="section" id="section-requests">
    <div class="section-header">
      <div>
        <div class="section-title">طلبات التسجيل</div>
        <div class="section-sub">مراجعة وقبول أو رفض طلبات التسجيل الجديدة</div>
      </div>
      <div class="section-actions">
        <div class="search-bar">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" placeholder="بحث في الطلبات...">
        </div>
      </div>
    </div>
    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>اسم الجهة / المستخدم</th><th>النوع</th><th>المدينة</th><th>تاريخ الطلب</th><th>الحالة</th><th>الإجراءات</th></tr>
        </thead>
       <tbody id="requestsTableBody">

<?php foreach($pendingUsers as $user): ?>

<tr>
    <td><?= $user['id'] ?></td>

    <td><?= htmlspecialchars($user['full_name']) ?></td>

    <td>

<?php

switch($user['role']){

    case 'doctor':
        echo 'طبيب';
        break;

    case 'patient':
        echo 'مريض';
        break;

    case 'pharmacy':
        echo 'صيدلية';
        break;

    case 'clinic':
        echo 'عيادة';
        break;

    case 'lab':
        echo 'مخبر';
        break;

    case 'super_admin':
        echo 'سوبر أدمن';
        break;

    default:
        echo $user['role'];

}

?>

</td>

    <td>—</td>

    <td><?= $user['created_at'] ?></td>

    <td>
        <span class="badge badge-pending">
            قيد المراجعة
        </span>
    </td>

    <td class="td-actions">

        <button class="btn-success">
            موافقة
        </button>

        <button class="btn-danger">
            رفض
        </button>

    </td>
</tr>

<?php endforeach; ?>

</tbody>
      </table>
    </div>
  </section>


  <!-- ══════════ INSTITUTIONS ══════════ -->
  <section class="section" id="section-institutions">
    <div class="section-header">
      <div>
        <div class="section-title">إدارة الحسابات</div>
        <div class="section-sub">إدارة جميع حسابات منصة MedChifaGiz — مرضى، أطباء، مؤسسات</div>
      </div>
      <div class="section-actions">
        <div class="search-bar">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" placeholder="بحث بالاسم أو البريد أو المدينة..." id="instSearchInput">
        </div>
      </div>
    </div>

    <!-- Stats chips -->
   

    <!-- Type filter tabs -->
    <div class="inst-filter-tabs" id="instFilterTabs">
      <button class="inst-tab active" data-type="الكل" onclick="filterInstByType(this,'الكل')">
        <i class="fas fa-th-large"></i> الكل
      </button>
      <button class="inst-tab" data-type="مريض" onclick="filterInstByType(this,'مريض')">
        <i class="fas fa-user-injured"></i> مريض
      </button>
      <button class="inst-tab" data-type="طبيب" onclick="filterInstByType(this,'طبيب')">
        <i class="fas fa-user-doctor"></i> طبيب
      </button>
      <button class="inst-tab" data-type="مستشفى" onclick="filterInstByType(this,'مستشفى')">
        <i class="fas fa-hospital"></i> مستشفى
      </button>
      <button class="inst-tab" data-type="عيادة" onclick="filterInstByType(this,'عيادة')">
        <i class="fas fa-clinic-medical"></i> عيادة
      </button>
      <button class="inst-tab" data-type="صيدلية" onclick="filterInstByType(this,'صيدلية')">
        <i class="fas fa-prescription-bottle-medical"></i> صيدلية
      </button>
      <button class="inst-tab" data-type="مخبر تحاليل" onclick="filterInstByType(this,'مخبر تحاليل')">
        <i class="fas fa-flask"></i> مخبر تحاليل
      </button>
    </div>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>الاسم / الجهة</th>
            <th style="width:120px">التصنيف</th>
            <th style="max-width:150px">البريد الإلكتروني</th>
            <th style="width:90px">المدينة</th>
            <th style="width:110px">تاريخ التسجيل</th>
            <th style="width:80px">الحالة</th>
            <th style="width:260px;min-width:260px">الإجراءات</th>
          </tr>
        </thead>
        <tbody id="institutionsTableBody"></tbody>
      </table>
    </div>
  </section>

  <!-- ══════════ STATS ══════════ -->
  <section class="section" id="section-stats">
    <div class="section-header">
      <div>
        <div class="section-title">إحصائيات المنصة</div>
        <div class="section-sub">نظرة شاملة على أداء وحجم منصة MedChifaGiz</div>
      </div>
    </div>

    <!-- Chart Row 1: Bar + Doughnut -->
    <div class="two-col" style="margin-bottom:20px">
      <!-- Bar Chart: Registrations by Month -->
      <div class="settings-group">
        <div class="settings-group-title"><i class="fas fa-chart-bar"></i> التسجيلات حسب الأشهر</div>
        <div style="position:relative;height:260px">
          <canvas id="chartMonthlyRegistrations"></canvas>
        </div>
      </div>
      <!-- Doughnut Chart: Account Distribution -->
      <div class="settings-group">
        <div class="settings-group-title"><i class="fas fa-chart-pie"></i> توزيع الحسابات حسب النوع</div>
        <div style="display:flex;align-items:center;gap:16px">
          <div style="position:relative;height:240px;flex:1;min-width:0">
            <canvas id="chartAccountDistribution"></canvas>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0" id="donutLegend">
            <!-- legend injected by JS -->
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Row 2: Line Chart full width -->
    <div class="settings-group">
      <div class="settings-group-title"><i class="fas fa-chart-line"></i> نمو المنصة خلال السنة</div>
      <div style="position:relative;height:280px">
        <canvas id="chartPlatformGrowth"></canvas>
      </div>
    </div>
  </section>

  <!-- ══════════ ACTIVITY LOG ══════════ -->
  <section class="section" id="section-activity">
    <div class="section-header">
      <div>
        <div class="section-title">سجل النشاطات</div>
        <div class="section-sub">جميع العمليات المنفذة على منصة MedChifaGiz</div>
      </div>
    </div>
    <div class="activity-list" id="fullActivityList"></div>
  </section>

  <!-- ══════════ SETTINGS ══════════ -->
  <section class="section" id="section-settings">
    <div class="section-header">
      <div>
        <div class="section-title">إعدادات المنصة</div>
        <div class="section-sub">إدارة البيانات الأساسية لمنصة MedChifaGiz</div>
      </div>
    </div>
    <div class="settings-layout">
      <div class="settings-profile-card">
        <!-- شعار المنصة -->
        <div class="settings-avatar-big">
          <?php if ($platformLogoPath): ?>
            <span><img src="<?= htmlspecialchars($platformLogoPath) ?>" style="width:100%;height:100%;object-fit:contain;border-radius:50%"></span>
          <?php else: ?>
            <span><i class="fas fa-heartbeat"></i></span>
          <?php endif; ?>
        </div>
        <h3>MedChifaGiz</h3>
        <p>منصة صحية جزائرية</p>
        <input type="file" id="logoFileInput" accept="image/png,image/jpeg,image/jpg,image/svg+xml" style="display:none" onchange="uploadLogo(this)">
        <button class="btn-secondary" style="width:100%;justify-content:center" onclick="document.getElementById('logoFileInput').click()">
          <i class="fas fa-image"></i> تغيير الشعار
        </button>

      </div>
      <div class="settings-groups">
        <div class="settings-group">
          <div class="settings-group-title"><i class="fas fa-info-circle"></i> بيانات المنصة</div>
          <div class="settings-field-grid">
            <div class="settings-field">
              <label>اسم المنصة</label>
              <input type="text" id="settingPlatformName" value="MedChifaGiz">
            </div>
            <div class="settings-field">
              <label>البريد الإلكتروني</label>
              <input type="email" id="settingEmail" value="admin@medchifagiz.dz">
            </div>
            <div class="settings-field">
              <label>رقم الهاتف</label>
              <input type="tel" id="settingPhone" value="+213 555 123 456">
            </div>
            <div class="settings-field">
              <label>الموقع الإلكتروني</label>
              <input type="text" id="settingWebsite" value="www.medchifagiz.dz">
            </div>
            <div class="settings-field full">
              <label>شعار المنصة</label>
              <div class="logo-upload" onclick="document.getElementById('logoFileInput').click()" style="cursor:pointer">
                <i class="fas fa-cloud-arrow-up"></i>
                <span>اسحب الصورة هنا أو انقر للرفع — PNG, JPG, SVG (max 2MB)</span>
              </div>
            </div>
            <div class="settings-field full">
              <label>سياسة الاستخدام</label>
              <textarea id="settingPolicy" style="min-height:100px">يُلزم المستخدمون باحترام الشروط والأحكام المعمول بها في منصة MedChifaGiz. يُحظر نشر أي معلومات طبية مضللة أو الاستخدام غير المشروع للمنصة. تحتفظ الإدارة بحق تعطيل أي حساب يُخالف هذه السياسة.</textarea>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end">
            <button class="btn-primary" onclick="saveSettings()"><i class="fas fa-floppy-disk"></i> حفظ الإعدادات</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════ ADMINS ══════════ -->
  <section class="section" id="section-admins">
    <div class="section-header">
      <div>
        <div class="section-title">إدارة المسؤولين</div>
        <div class="section-sub">إدارة حسابات وصلاحيات مسؤولي منصة MedChifaGiz</div>
      </div>
      <div class="section-actions">
        <div class="search-bar">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" placeholder="بحث في المسؤولين..." id="adminsSearchInput">
        </div>
<?php if(hasPermission('manageAdmins')): ?>

<button class="btn-primary" onclick="openModal('addAdminModal')">
    <i class="fas fa-user-plus"></i> إضافة مسؤول
</button>

<?php endif; ?>
      </div>
    </div>

    <!-- Stats mini -->
    <div class="admins-stats-row">
      <div class="admin-stat-chip" style="--chip-color:#0ea5e9">
        <div class="admin-stat-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9"><i class="fas fa-users-cog"></i></div>
        <div><div class="admin-stat-val" id="adminStatTotal">0</div><div class="admin-stat-lbl">إجمالي المسؤولين</div></div>
      </div>
      <div class="admin-stat-chip" style="--chip-color:#a78bfa">
        <div class="admin-stat-icon" style="background:rgba(167,139,250,.12);color:#a78bfa"><i class="fas fa-crown"></i></div>
        <div><div class="admin-stat-val" id="adminStatSuper">0</div><div class="admin-stat-lbl">Super Admin</div></div>
      </div>
      <div class="admin-stat-chip" style="--chip-color:#34d399">
        <div class="admin-stat-icon" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fas fa-user-tie"></i></div>
        <div><div class="admin-stat-val" id="adminStatAdmin">0</div><div class="admin-stat-lbl">مسؤولون</div></div>
      </div>
      <div class="admin-stat-chip" style="--chip-color:#38bdf8">
        <div class="admin-stat-icon" style="background:rgba(56,189,248,.12);color:#38bdf8"><i class="fas fa-user-check"></i></div>
        <div><div class="admin-stat-val" id="adminStatActive">0</div><div class="admin-stat-lbl">نشط</div></div>
      </div>
    </div>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>المسؤول</th>
            <th>البريد الإلكتروني</th>
            <th>الدور</th>
            <th style="width:80px">الحالة</th>
            <th style="width:110px">تاريخ الإنشاء</th>
            <th style="width:260px">الإجراءات</th>
          </tr>
        </thead>
        <tbody id="adminsTableBody"></tbody>
      </table>
    </div>
  </section>

  <!-- ══════════ MAINTENANCE ══════════ -->
  <section class="section" id="section-maintenance">

    <!-- PAGE HEADER -->
    <div class="maintenance-page-header">
      <div class="maintenance-page-header-left">
        <div class="maintenance-header-icon">
          <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="maintenance-header-text">
          <h2>وضع الصيانة</h2>
          <p>التحكم الكامل في حالة تشغيل المنصة — تفعيل أو إيقاف الوصول للمستخدمين</p>
        </div>
      </div>
      <div class="maintenance-header-badge" id="maintHeaderBadge">
        <span class="pulse-dot"></span>
        <span id="maintHeaderBadgeText">المنصة تعمل بشكل طبيعي</span>
      </div>
    </div>

    <!-- BIG STATUS CARD -->
    <div class="maint-status-card" id="maintStatusCard">

      <!-- Top: icon + status text -->
      <div class="maint-card-top">
        <div class="maint-status-left">
          <div class="maint-big-icon" id="maintBigIcon">
            <i class="fas fa-shield-check" id="maintBigIconInner"></i>
          </div>
          <div class="maint-status-info">
            <h3 id="maintStatusTitle">المنصة تعمل بشكل طبيعي</h3>
            <p id="maintStatusDesc">جميع الخدمات متاحة للمستخدمين. لتفعيل وضع الصيانة يرجى ملء إعدادات الصيانة أدناه ثم الضغط على زر التفعيل.</p>
          </div>
        </div>
        <!-- أزرار التفعيل منقولة إلى قسم الإعدادات — hidden compat -->
        <div style="display:none">
          <button id="maintenanceEnableBtn"></button>
          <button id="maintenanceDisableBtn"></button>
        </div>
      </div>

      <!-- Bottom: 4 info cells -->
      <div class="maint-card-bottom">
        <div class="maint-info-cell">
          <div class="maint-info-label"><i class="fas fa-circle-dot"></i> حالة المنصة</div>
          <div class="maint-info-value" id="maintCellStatus">نشطة — تعمل</div>
          <div class="maint-info-sub" id="maintCellStatusSub">جميع الخدمات متاحة</div>
        </div>
        <div class="maint-info-cell">
          <div class="maint-info-label"><i class="fas fa-calendar-check"></i> آخر صيانة</div>
          <div class="maint-info-value" id="maintCellLastDate">—</div>
          <div class="maint-info-sub" id="maintCellLastDuration">لا توجد صيانة سابقة</div>
        </div>
        <div class="maint-info-cell">
          <div class="maint-info-label"><i class="fas fa-user-shield"></i> آخر مسؤول</div>
          <div class="maint-info-value" id="maintCellLastAdmin">—</div>
          <div class="maint-info-sub" id="maintCellLastAdminRole"></div>
        </div>
        <div class="maint-info-cell">
          <div class="maint-info-label"><i class="fas fa-hourglass-half"></i> مدة الصيانة الحالية</div>
          <div class="maint-info-value" id="maintDurationVal">—</div>
          <div class="maint-info-sub" id="maintDurationSub">لا توجد صيانة نشطة</div>
        </div>
      </div>

    </div>

    <!-- ══════════ MAINTENANCE SETTINGS SECTION ══════════ -->
    <div class="maint-settings-section" id="maintSettingsSection">

      <!-- Section Header -->
      <div class="maint-settings-header">
        <div class="maint-settings-header-icon">
          <i class="fas fa-gear"></i>
        </div>
        <div>
          <h3>إعدادات وضع الصيانة</h3>
          <p>ضبط تفاصيل وجدولة صيانة المنصة بشكل احترافي</p>
        </div>
      </div>

      <div class="maint-settings-body">

        <!-- Date & Type Fields -->
        <div class="maint-settings-grid">
          <div class="maint-field">
            <label><i class="fas fa-calendar-plus"></i> تاريخ بداية الصيانة</label>
            <input type="datetime-local" id="maintStartDate">
          </div>
          <div class="maint-field">
            <label><i class="fas fa-calendar-check"></i> تاريخ نهاية الصيانة</label>
            <input type="datetime-local" id="maintEndDate">
          </div>
          <div class="maint-field">
            <label><i class="fas fa-tag"></i> نوع الصيانة</label>
            <select id="maintType">
              <option value="" disabled selected>— اختر نوع الصيانة —</option>
              <option value="technical">🔧 صيانة تقنية</option>
              <option value="system_update">⚙️ تحديث النظام</option>
              <option value="db_update">🗄️ تحديث قاعدة البيانات</option>
              <option value="security_update">🔒 تحديث أمني</option>
              <option value="other">📋 أخرى</option>
            </select>
          </div>
          <div class="maint-field" style="align-self:end">
            <!-- Spacer or future field -->
          </div>
          <div class="maint-field full">
            <label><i class="fas fa-comment-lines"></i> سبب الصيانة</label>
            <textarea id="maintReason" placeholder="أدخل وصفاً واضحاً لسبب الصيانة — سيُعرض للمستخدمين عند محاولة الوصول إلى المنصة أثناء فترة الصيانة..."></textarea>
          </div>
        </div>

        <div class="maint-settings-divider"></div>

        <!-- Action Buttons -->
        <div class="maint-settings-actions">
          <button class="btn-maint-save" onclick="saveMaintSettings()">
            <i class="fas fa-floppy-disk"></i>
            حفظ الإعدادات
          </button>
          <button class="btn-maint-enable" id="maintSettingsBtnEnable" onclick="applyMaintFromSettings(true)">
            <i class="fas fa-triangle-exclamation"></i>
            تفعيل وضع الصيانة
          </button>
          <button class="btn-maint-disable" id="maintSettingsBtnDisable" onclick="applyMaintFromSettings(false)" style="display:none">
            <i class="fas fa-circle-check"></i>
            إلغاء وضع الصيانة
          </button>
        </div>

      </div>
    </div>
    <!-- END MAINTENANCE SETTINGS SECTION -->

    <!-- ══════════ MAINTENANCE MESSAGE SECTION ══════════ -->
    <div class="maint-msg-section">
      <div class="maint-section-hd">
        <div class="maint-section-hd-icon" style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.22);color:#fbbf24">
          <i class="fas fa-comment-medical"></i>
        </div>
        <div>
          <h3>رسالة المستخدمين أثناء الصيانة</h3>
          <p>أدخل الرسالة التي ستظهر للمستخدمين عند محاولة دخول المنصة أثناء وضع الصيانة</p>
        </div>
      </div>
      <div class="maint-msg-body">
        <textarea class="maint-msg-textarea" id="maintUserMessage"
          oninput="updateMaintPreview(this.value)"
          placeholder="اكتب رسالة الصيانة هنا...">منصة MedChifaGiz تخضع حالياً لأعمال صيانة وتحسين للخدمات الصحية الرقمية. نعتذر عن الإزعاج ونشكركم على تفهمكم.</textarea>

        <div class="maint-preview-label">
          <i class="fas fa-eye"></i>
          معاينة مباشرة — كما ستظهر للمستخدم
        </div>

        <!-- Live Preview Card -->
        <div class="maint-preview-box">
          <div class="maint-preview-icon">
            <i class="fas fa-screwdriver-wrench"></i>
          </div>
          <div class="maint-preview-title">
            <span>MedChifa</span>Giz — وضع الصيانة
          </div>
          <div class="maint-preview-msg" id="maintPreviewText">منصة MedChifaGiz تخضع حالياً لأعمال صيانة وتحسين للخدمات الصحية الرقمية. نعتذر عن الإزعاج ونشكركم على تفهمكم.</div>
          <div class="maint-preview-badge">
            <span class="pulse-dot"></span>
            قيد الصيانة — يُرجى العودة لاحقاً
          </div>
        </div>

        <div class="maint-msg-actions">
          <button class="btn-maint-save" onclick="saveMaintMessage()">
            <i class="fas fa-floppy-disk"></i> حفظ الرسالة
          </button>
          <button class="btn-secondary" onclick="resetMaintMessage()">
            <i class="fas fa-rotate-left"></i> استعادة النص الافتراضي
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════ MAINTENANCE ACCESS PERMISSIONS ══════════ -->
    <div class="maint-access-section">
      <div class="maint-section-hd">
        <div class="maint-section-hd-icon" style="background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.22);color:#a78bfa">
          <i class="fas fa-users-gear"></i>
        </div>
        <div>
          <h3>المستخدمون المسموح لهم بالدخول أثناء الصيانة</h3>
          <p>حدد الفئات التي تستطيع الوصول إلى المنصة عند تفعيل وضع الصيانة</p>
        </div>
      </div>
      <div class="maint-access-body">
        <div class="maint-access-grid">

          <!-- Super Admin — always enabled -->
          <div class="maint-access-item locked enabled" style="--item-color:rgba(245,158,11,.07)">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(245,158,11,.12);color:#fbbf24">
              <i class="fas fa-crown"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">Super Admin</span>
              <span class="maint-access-sub">وصول كامل دائمًا</span>
            </div>
            <span class="maint-access-status on">مفعّل</span>
          </div>

          <!-- Admin — always enabled -->
          <div class="maint-access-item locked enabled" style="--item-color:rgba(14,165,233,.07)">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(14,165,233,.12);color:#38bdf8">
              <i class="fas fa-user-tie"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">Admin</span>
              <span class="maint-access-sub">مسؤول النظام</span>
            </div>
            <span class="maint-access-status on">مفعّل</span>
          </div>

          <!-- المسؤولون — always enabled -->
          <div class="maint-access-item locked enabled" style="--item-color:rgba(99,102,241,.07)">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(99,102,241,.12);color:#a5b4fc">
              <i class="fas fa-user-shield"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">المسؤولون</span>
              <span class="maint-access-sub">جميع المشرفين</span>
            </div>
            <span class="maint-access-status on">مفعّل</span>
          </div>

          <!-- الأطباء — toggleable -->
          <div class="maint-access-item" id="accessDoctors" onclick="toggleAccessItem(this,'accessDoctors')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(52,211,153,.12);color:#34d399">
              <i class="fas fa-user-doctor"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">الأطباء</span>
              <span class="maint-access-sub">الأطباء المسجلون</span>
            </div>
            <span class="maint-access-status off" id="accessDoctorsStatus">معطّل</span>
          </div>

          <!-- المرضى — toggleable -->
          <div class="maint-access-item" id="accessPatients" onclick="toggleAccessItem(this,'accessPatients')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(167,139,250,.12);color:#a78bfa">
              <i class="fas fa-hospital-user"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">المرضى</span>
              <span class="maint-access-sub">المرضى المسجلون</span>
            </div>
            <span class="maint-access-status off" id="accessPatientsStatus">معطّل</span>
          </div>

          <!-- الصيدليات — toggleable -->
          <div class="maint-access-item" id="accessPharmacies" onclick="toggleAccessItem(this,'accessPharmacies')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(251,146,60,.12);color:#fb923c">
              <i class="fas fa-prescription-bottle-medical"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">الصيدليات</span>
              <span class="maint-access-sub">الصيدليات المسجلة</span>
            </div>
            <span class="maint-access-status off" id="accessPharmaciesStatus">معطّل</span>
          </div>

          <!-- المخابر — toggleable -->
          <div class="maint-access-item" id="accessLabs" onclick="toggleAccessItem(this,'accessLabs')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(16,185,129,.12);color:#34d399">
              <i class="fas fa-flask"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">المخابر</span>
              <span class="maint-access-sub">مخابر التحاليل المسجلة</span>
            </div>
            <span class="maint-access-status off" id="accessLabsStatus">معطّل</span>
          </div>

          <!-- المستشفيات — toggleable -->
          <div class="maint-access-item" id="accessHospitals" onclick="toggleAccessItem(this,'accessHospitals')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(239,68,68,.12);color:#f87171">
              <i class="fas fa-hospital"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">المستشفيات</span>
              <span class="maint-access-sub">المستشفيات المسجلة</span>
            </div>
            <span class="maint-access-status off" id="accessHospitalsStatus">معطّل</span>
          </div>

          <!-- العيادات — toggleable -->
          <div class="maint-access-item" id="accessClinics" onclick="toggleAccessItem(this,'accessClinics')">
            <div class="maint-access-check"><i class="fas fa-check"></i></div>
            <div class="maint-access-icon" style="background:rgba(99,102,241,.12);color:#a5b4fc">
              <i class="fas fa-clinic-medical"></i>
            </div>
            <div class="maint-access-info">
              <span class="maint-access-name">العيادات</span>
              <span class="maint-access-sub">العيادات الخاصة المسجلة</span>
            </div>
            <span class="maint-access-status off" id="accessClinicsStatus">معطّل</span>
          </div>

        </div>

        <div class="maint-settings-divider" style="margin-top:20px"></div>
        <div class="maint-settings-actions">
          <button class="btn-maint-save" onclick="saveAccessSettings()">
            <i class="fas fa-floppy-disk"></i> حفظ إعدادات الوصول
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════ MAINTENANCE LOG ══════════ -->
    <div class="maint-log-section">
      <div class="maint-section-hd" style="border-bottom:1px solid var(--border)">
        <div class="maint-section-hd-icon" style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.22);color:#34d399">
          <i class="fas fa-rectangle-list"></i>
        </div>
        <div>
          <h3>سجل عمليات الصيانة</h3>
          <p>سجل كامل بجميع عمليات وضع الصيانة السابقة والجارية</p>
        </div>
      </div>
      <div class="maint-log-table-wrap">
        <table class="maint-log-table">
          <thead>
            <tr>
              <th>#</th>
              <th>تاريخ التفعيل</th>
              <th>تاريخ الإيقاف</th>
              <th>المسؤول</th>
              <th>نوع الصيانة</th>
              <th>السبب</th>
              <th>الحالة</th>
            </tr>
          </thead>
          <tbody id="maintLogTbody">
            <!-- يتم تحميل السجلات من قاعدة البيانات -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- hidden compat elements for JS -->
    <div class="maintenance-card" id="maintenanceIcon" style="display:none"></div>
    <div id="maintenanceStatusText" style="display:none"></div>
    <div id="maintenanceDesc" style="display:none"></div>
    <div class="maintenance-alert" id="maintenanceAlert" style="display:none!important"></div>

  </section>

</div><!-- /main -->

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- MODAL: عرض طلب تسجيل -->
<div class="modal-overlay" id="viewRequestModal">
  <div class="modal" style="max-width:580px">
    <button class="modal-close" onclick="closeModal('viewRequestModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-file-circle-check"></i> تفاصيل طلب التسجيل</div>
    <div id="viewRequestContent"></div>
  </div>
</div>


<!-- MODAL: عرض حساب -->
<div class="modal-overlay" id="viewInstModal">
  <div class="modal" style="max-width:560px">
    <button class="modal-close" onclick="closeModal('viewInstModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-id-card"></i> تفاصيل الحساب</div>
    <div id="viewInstContent"></div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('viewInstModal')"><i class="fas fa-times"></i> إغلاق</button>
    </div>
  </div>
</div>

<!-- MODAL: تعديل حساب -->
<div class="modal-overlay" id="editInstModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('editInstModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-pen"></i> تعديل بيانات الحساب</div>
    <div class="modal-field"><label>الاسم الكامل / اسم الجهة</label><input type="text" id="editInstName"></div>
    <div class="modal-field"><label>المدينة</label><input type="text" id="editInstCity"></div>
    <div class="modal-field"><label>النوع</label>
      <select id="editInstType">
        <option>مريض</option><option>طبيب</option><option>مستشفى</option><option>عيادة</option><option>صيدلية</option><option>مخبر تحاليل</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('editInstModal')">إلغاء</button>
      <button class="btn-primary" onclick="saveEditInst()"><i class="fas fa-floppy-disk"></i> حفظ</button>
    </div>
  </div>
</div>

<!-- MODAL: إضافة مسؤول -->
<div class="modal-overlay" id="addAdminModal">
  <div class="modal" style="max-width:600px">
    <button class="modal-close" onclick="closeModal('addAdminModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-user-plus"></i> إضافة مسؤول جديد</div>

    <div class="admin-form-grid">
      <div class="modal-field">
        <label>الاسم الكامل <span style="color:#f87171">*</span></label>
        <input type="text" id="addAdminName" placeholder="مثال: أحمد بن علي">
      </div>
      <div class="modal-field">
        <label>البريد الإلكتروني <span style="color:#f87171">*</span></label>
        <input type="email" id="addAdminEmail" placeholder="admin@medchifagiz.dz">
      </div>
      <div class="modal-field">
        <label>كلمة المرور <span style="color:#f87171">*</span></label>
        <div style="position:relative">
          <input type="password" id="addAdminPass" placeholder="8 أحرف على الأقل" style="padding-left:38px">
          <button onclick="togglePassVis('addAdminPass',this)" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="modal-field">
        <label>الدور <span style="color:#f87171">*</span></label>
        <select id="addAdminRole" onchange="updatePermPreset('add')">
          <option value="moderator">Moderator — مشرف</option>
          <option value="admin">Admin — مسؤول</option>
          <option value="super_admin">Super Admin — مسؤول أعلى</option>
        </select>
      </div>
    </div>

    <div class="admin-perms-box">
      <div class="admin-perms-title"><i class="fas fa-shield-halved"></i> الصلاحيات</div>
      <div class="admin-perms-preset">
        <span style="font-size:12px;color:var(--text-muted)">تعيين سريع:</span>
        <button class="admin-preset-btn" onclick="applyPreset('add','none')">لا شيء</button>
        <button class="admin-preset-btn" onclick="applyPreset('add','read')">قراءة فقط</button>
        <button class="admin-preset-btn" onclick="applyPreset('add','moderate')">إشراف</button>
        <button class="admin-preset-btn" onclick="applyPreset('add','full')">كامل</button>
      </div>
      <div class="admin-perms-grid" id="addAdminPerms">
      </div>
    </div>

    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('addAdminModal')"><i class="fas fa-times"></i> إلغاء</button>
      <button class="btn-primary" onclick="saveNewAdmin()"><i class="fas fa-floppy-disk"></i> حفظ المسؤول</button>
    </div>
  </div>
</div>

<!-- MODAL: تعديل مسؤول -->
<div class="modal-overlay" id="editAdminModal">
  <div class="modal" style="max-width:600px">
    <button class="modal-close" onclick="closeModal('editAdminModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-user-pen"></i> تعديل بيانات المسؤول</div>
    <input type="hidden" id="editAdminId">
    <div class="admin-form-grid">
      <div class="modal-field">
        <label>الاسم الكامل <span style="color:#f87171">*</span></label>
        <input type="text" id="editAdminName">
      </div>
      <div class="modal-field">
        <label>البريد الإلكتروني <span style="color:#f87171">*</span></label>
        <input type="email" id="editAdminEmail">
      </div>
      <div class="modal-field">
        <label>كلمة مرور جديدة <span style="color:var(--text-dim);font-weight:400">(اتركها فارغة للإبقاء على الحالية)</span></label>
        <div style="position:relative">
          <input type="password" id="editAdminPass" placeholder="اتركها فارغة لعدم التغيير" style="padding-left:38px">
          <button onclick="togglePassVis('editAdminPass',this)" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="modal-field">
        <label>الدور <span style="color:#f87171">*</span></label>
        <select id="editAdminRole" onchange="updatePermPreset('edit')">
          <option value="moderator">Moderator — مشرف</option>
          <option value="admin">Admin — مسؤول</option>
          <option value="super_admin">Super Admin — مسؤول أعلى</option>
        </select>
      </div>
    </div>
    <div class="admin-perms-box">
      <div class="admin-perms-title"><i class="fas fa-shield-halved"></i> الصلاحيات</div>
      <div class="admin-perms-preset">
        <span style="font-size:12px;color:var(--text-muted)">تعيين سريع:</span>
        <button class="admin-preset-btn" onclick="applyPreset('edit','none')">لا شيء</button>
        <button class="admin-preset-btn" onclick="applyPreset('edit','read')">قراءة فقط</button>
        <button class="admin-preset-btn" onclick="applyPreset('edit','moderate')">إشراف</button>
        <button class="admin-preset-btn" onclick="applyPreset('edit','full')">كامل</button>
      </div>
      <div class="admin-perms-grid" id="editAdminPerms">
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('editAdminModal')"><i class="fas fa-times"></i> إلغاء</button>
      <button class="btn-primary" onclick="saveEditAdmin()"><i class="fas fa-floppy-disk"></i> حفظ التعديلات</button>
    </div>
  </div>
</div>

<!-- MODAL: عرض مسؤول -->
<div class="modal-overlay" id="viewAdminModal">
  <div class="modal" style="max-width:500px">
    <button class="modal-close" onclick="closeModal('viewAdminModal')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-user-shield"></i> بيانات المسؤول</div>
    <div id="viewAdminContent"></div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal('viewAdminModal')">إغلاق</button>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════════════
   ADMINS — HELPERS
══════════════════════════════════════ */
const permsList = [

  {key:'viewUsers',   label:'عرض الحسابات',   group:'users'},
{key:'manageUsers', label:'إدارة الحسابات', group:'users'},
  {key:'viewRequests',   label:'عرض الطلبات',          group:'requests'},
  {key:'manageRequests', label:'إدارة الطلبات',         group:'requests'},
 
  {key:'viewStats',      label:'عرض الإحصائيات',       group:'system'},
  {key:'manageSettings', label:'إعدادات المنصة',        group:'system'},
  {key:'manageMaintenance',label:'وضع الصيانة',         group:'system'},
  {key:'viewActivities', label:'عرض سجل النشاطات', group:'system'},
  {key:'viewAdmins',     label:'عرض المسؤولين',         group:'system'},
  {key:'manageAdmins',   label:'إدارة المسؤولين',       group:'system'},
];

const permPresets = {
  none:     {viewUsers:false,manageUsers:false,viewRequests:false,manageRequests:false,viewInstitutions:false,manageInstitutions:false,viewStats:false,manageSettings:false,manageMaintenance:false,manageAdmins:false},
  read:     {viewUsers:true,manageUsers:false,viewRequests:true,manageRequests:false,viewInstitutions:true,manageInstitutions:false,viewStats:true,manageSettings:false,manageMaintenance:false,manageAdmins:false},
  moderate: {viewUsers:true,manageUsers:false,viewRequests:true,manageRequests:true,viewInstitutions:true,manageInstitutions:false,viewStats:true,manageSettings:false,manageMaintenance:false,manageAdmins:false},
  full:     {viewUsers:true,manageUsers:false,viewRequests:true,manageRequests:true,viewInstitutions:true,manageInstitutions:true,viewStats:true,manageSettings:true,manageMaintenance:true,manageAdmins:true},
};

const rolePresets = {moderator:'moderate', admin:'moderate', super_admin:'full'};

function buildPermsHTML(prefix){
  const groups = {users:'المستخدمون',requests:'الطلبات',system:'النظام'};
  let html='';
  Object.entries(groups).forEach(([g,glabel])=>{
    html+=`<div class="admin-perm-group-label">${glabel}</div>`;
    permsList.filter(p=>p.group===g).forEach(p=>{
      html+=`<label class="admin-perm-item">
        <input type="checkbox" id="${prefix}Perm_${p.key}" class="admin-perm-cb">
        <span class="admin-perm-check"><i class="fas fa-check"></i></span>
        <span class="admin-perm-label">${p.label}</span>
      </label>`;
    });
  });
  return html;
}

function getPermsFromForm(prefix){
  const perms={};
  permsList.forEach(p=>{ perms[p.key]=document.getElementById(`${prefix}Perm_${p.key}`)?.checked||false; });
  return perms;
}

function setPermsToForm(prefix, perms){
  permsList.forEach(p=>{
    const el=document.getElementById(`${prefix}Perm_${p.key}`);
    if(el) el.checked = !!perms[p.key];
  });
}

function applyPreset(prefix, preset){
  setPermsToForm(prefix, permPresets[preset]);
}

function updatePermPreset(prefix){
  const role = document.getElementById(`${prefix}AdminRole`).value;
  applyPreset(prefix, rolePresets[role]||'moderate');
}

function togglePassVis(inputId, btn){
  const el=document.getElementById(inputId);
  if(el.type==='password'){el.type='text';btn.innerHTML='<i class="fas fa-eye-slash"></i>';}
  else{el.type='password';btn.innerHTML='<i class="fas fa-eye"></i>';}
}

const roleLabels = {super_admin:'Super Admin', admin:'Admin', moderator:'Moderator'};
const roleColors = {super_admin:'#f59e0b', admin:'#0ea5e9', moderator:'#a78bfa'};
const roleBg    = {super_admin:'rgba(245,158,11,.12)', admin:'rgba(14,165,233,.1)', moderator:'rgba(167,139,250,.1)'};
const roleIcons = {super_admin:'fa-crown', admin:'fa-user-tie', moderator:'fa-user-check'};

/* ══════════════════════════════════════
   RENDER ADMINS
══════════════════════════════════════ */
function renderAdmins(data){
  data = data || state.admins;
  const tbody = document.getElementById('adminsTableBody');
  if(!tbody) return;
  tbody.innerHTML = data.map((a,i)=>{
    const sc = a.status==='active'?'badge-active':'badge-inactive';
    const sl = a.status==='active'?'نشط':'معطّل';
    const rc = roleColors[a.role]||'#7a8fa6';
    const rb = roleBg[a.role]||'rgba(122,143,166,.1)';
    const ri = roleIcons[a.role]||'fa-user';
    const rl = roleLabels[a.role]||a.role;
    const initials = a.name.split(' ').slice(0,2).map(w=>w[0]).join('');
    return `<tr>
      <td style="color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:12px">${String(i+1).padStart(2,'0')}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,${rc}33,${rc}55);border:1px solid ${rc}44;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:${rc};flex-shrink:0">${initials}</div>
          <span style="font-weight:600">${a.name}</span>
        </div>
      </td>
      <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace">${a.email}</td>
      <td>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;background:${rb};color:${rc};border:1px solid ${rc}33">
          <i class="fas ${ri}" style="font-size:10px"></i> ${rl}
        </span>
      </td>
      <td><span class="badge ${sc}">${sl}</span></td>
      <td style="color:var(--text-muted);font-size:12px">${a.date}</td>
      <td><div class="td-actions" style="flex-wrap:nowrap">
        <button class="btn-action btn-action-view" onclick="viewAdmin(${a.id})"><i class="fas fa-eye"></i><span>عرض</span></button>
      <button class="btn-action btn-action-edit" onclick="editAdmin(${a.id})">
    <i class="fas fa-pen"></i>
    <span>تعديل</span>
</button>
        ${a.role !== 'super_admin'
  ? (
      a.status === 'active'
      ? '<button class="btn-action btn-action-disable" onclick="toggleAdmin(' + a.id + ')"><i class="fas fa-ban"></i><span>تعطيل</span></button>'
      : '<button class="btn-action btn-action-enable" onclick="toggleAdmin(' + a.id + ')"><i class="fas fa-circle-check"></i><span>تفعيل</span></button>'
    )
  : ''
}
        ${a.role!=='super_admin'?`<button class="btn-action btn-action-delete" onclick="deleteAdmin(${a.id})"><i class="fas fa-trash-can"></i><span>حذف</span></button>`:''}
      </div></td>
    </tr>`;
  }).join('');

  // Update mini stats
  document.getElementById('adminStatTotal').textContent = state.admins.length;
  document.getElementById('adminStatSuper').textContent = state.admins.filter(a=>a.role==='super_admin').length;
document.getElementById('adminStatAdmin').textContent =
state.admins.length;
  document.getElementById('adminStatActive').textContent = state.admins.filter(a=>a.status==='active').length;
}

function viewAdmin(id){
  const a = state.admins.find(x=>x.id===id);
  console.log(a);
console.log(a.perms);
  if(!a) return;
  const sc=a.status==='active'?'badge-active':'badge-inactive';
  const sl=a.status==='active'?'نشط':'معطّل';
  const rc=roleColors[a.role]||'#7a8fa6';
  const rb=roleBg[a.role]||'rgba(122,143,166,.1)';
  const ri=roleIcons[a.role]||'fa-user';
  const rl=roleLabels[a.role]||a.role;
  const initials=a.name.split(' ').slice(0,2).map(w=>w[0]).join('');
  
  const activePerms=permsList.filter(p=>a.perms[p.key]);
  document.getElementById('viewAdminContent').innerHTML=`
    <div style="display:flex;align-items:center;gap:14px;padding:16px;background:var(--bg-card2);border:1px solid var(--border);border-radius:12px;margin-bottom:16px">
      <div style="width:52px;height:52px;border-radius:13px;background:linear-gradient(135deg,${rc}33,${rc}66);border:1.5px solid ${rc}55;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:${rc};flex-shrink:0">${initials}</div>
      <div>
        <div style="font-size:16px;font-weight:800;color:var(--text)">${a.name}</div>
        <div style="font-size:12px;color:var(--accent2);margin-top:3px;font-family:'JetBrains Mono',monospace">${a.email}</div>
        <div style="margin-top:6px;display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:${rb};color:${rc}"><i class="fas ${ri}" style="font-size:9px"></i> ${rl}</div>
      </div>
    </div>
    <div class="modal-info-row"><span>الحالة</span><span class="badge ${sc}">${sl}</span></div>
    <div class="modal-info-row"><span>تاريخ الإنشاء</span><span>${a.date}</span></div>
    <div style="margin-top:14px">
      <div style="font-size:11.5px;font-weight:700;color:var(--accent);letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;padding-bottom:6px;border-bottom:1px solid var(--border)"><i class="fas fa-shield-halved"></i> الصلاحيات الممنوحة (${activePerms.length}/${permsList.length})</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        ${activePerms.length?activePerms.map(p=>`<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:rgba(14,165,233,.1);border:1px solid rgba(14,165,233,.2);color:var(--accent2);font-size:11.5px;font-weight:600"><i class="fas fa-check" style="font-size:9px"></i>${p.label}</span>`).join(''):'<span style="color:var(--text-dim);font-size:13px">لا توجد صلاحيات ممنوحة</span>'}
      </div>
    </div>`;
  openModal('viewAdminModal');
}

let editingAdminId=null;
function editAdmin(id, focusPerms=false){

  if (
      userRole !== 'super_admin' &&
      !userPermissions.manageAdmins
  ) {
      showToast('ليس لديك صلاحية لتعديل المسؤولين');
      return;
  }

  const a=state.admins.find(x=>x.id===id);

  if(!a) return;

  editingAdminId=id;

  document.getElementById('editAdminId').value=id;
  document.getElementById('editAdminName').value=a.name;
  document.getElementById('editAdminEmail').value=a.email;
  document.getElementById('editAdminPass').value='';
  document.getElementById('editAdminRole').value=a.role;

  setPermsToForm('edit', a.perms);

  openModal('editAdminModal');

  if(focusPerms){
      setTimeout(()=>{
          document.getElementById('editAdminPerms')
                  .scrollIntoView({behavior:'smooth'});
      },300);
  }
}

function saveNewAdmin(){
if (
    userRole !== 'super_admin' &&
    userPermissions.manageAdmins !== true
) {
    showToast('ليس لديك صلاحية لإضافة مسؤولين');
    return;
}
  const name=document.getElementById('addAdminName').value.trim();
  const email=document.getElementById('addAdminEmail').value.trim();
  const pass=document.getElementById('addAdminPass').value;
  const role=document.getElementById('addAdminRole').value;

  if(!name || !email || !pass){
    showToast('⚠️ يرجى ملء جميع الحقول الإجبارية');
    return;
  }
const permissions = JSON.stringify(getPermsFromForm('add'));
  fetch('add_admin.php',{
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded'
    },
    body:
      'name='+encodeURIComponent(name)+
      '&email='+encodeURIComponent(email)+
      '&password='+encodeURIComponent(pass)+
     '&role='+encodeURIComponent(role)+
'&permissions='+encodeURIComponent(permissions)
  })
.then(r => r.json())
.then(data => {

    console.log(data);

 if(data.success){

    state.admins.unshift({
        id: Date.now(),
        name: name,
        email: email,
        role: role,
        status: 'active',
        date: new Date().toISOString().split('T')[0],
        perms: getPermsFromForm('add')
    });

    renderAdmins();

    closeModal('addAdminModal');

    showToast('✅ تم إضافة المسؤول');

}else{

    showToast(data.message);

}

});

}

function saveEditAdmin(){
if (
    userRole !== 'super_admin' &&
    !userPermissions.manageAdmins
) {
    showToast('ليس لديك صلاحية لتعديل المسؤولين');
    return;
}
  const a = state.admins.find(x => x.id == editingAdminId);

  if(!a) return;

  const name  = document.getElementById('editAdminName').value.trim();
  const email = document.getElementById('editAdminEmail').value.trim();
  const role  = document.getElementById('editAdminRole').value;

  if(!name || !email){

    showToast('⚠️ يرجى ملء الحقول الإجبارية');
    return;

  }
const permissions = getPermsFromForm('edit');
console.log('saveEditAdmin started');
console.log(permissions);
 fetch('update_user.php' ,{

    method:'POST',

    headers:{
      'Content-Type':'application/x-www-form-urlencoded'
    },

    body:
      'id=' + editingAdminId +
      '&name=' + encodeURIComponent(name) +
      '&email=' + encodeURIComponent(email) +
      '&role=' + encodeURIComponent(role)
      + '&permissions=' + encodeURIComponent(JSON.stringify(permissions))

  })

  .then(r => r.json())

  .then(data => {

    if(data.success){

      a.name = name;
      a.email = email;
      a.role = role;
      a.perms = permissions;

      renderAdmins();

      closeModal('editAdminModal');

      showToast('✏️ تم تعديل بيانات ' + a.name);

      addActivity(
        'fa-user-pen',
        'rgba(14,165,233,.15)',
        '#38bdf8',
        'تعديل مسؤول',
        'تم تعديل بيانات ' + a.name
      );

    }else{

      alert('فشل التعديل');

    }

  });

}

function toggleAdmin(id){
if (
      userRole !== 'super_admin' &&
      !userPermissions.manageAdmins
  ) {
      showToast('ليس لديك صلاحية لتعطيل المسؤولين');
      return;
  }
  const a = state.admins.find(x => x.id == id);

  if(!a) return;

  if(a.role === 'super_admin'){
    showToast('⚠️ لا يمكن تعطيل حساب Super Admin');
    return;
  }

  const newStatus = a.status === 'active'
      ? 'inactive'
      : 'active';

  fetch('toggle_user_status.php', {

    method:'POST',

    headers:{
      'Content-Type':'application/x-www-form-urlencoded'
    },

    body:'id=' + id + '&status=' + newStatus

  })

  .then(r => r.json())

  .then(data => {

    if(data.success){

      a.status = newStatus;

      renderAdmins();

      const action = newStatus === 'active'
          ? 'تفعيل'
          : 'تعطيل';

      showToast(
        (newStatus === 'active' ? '🟢' : '🔴')
        + ' تم ' + action + ' حساب ' + a.name
      );

      addActivity(
        newStatus === 'active' ? 'fa-lock-open' : 'fa-lock',
        newStatus === 'active'
          ? 'rgba(16,185,129,.15)'
          : 'rgba(239,68,68,.12)',
        newStatus === 'active'
          ? '#34d399'
          : '#f87171',
        action + ' مسؤول',
        'تم ' + action + ' حساب ' + a.name
      );

    }else{

      alert(data.message || 'فشل العملية');

    }

  });

}

function deleteAdmin(id){
if (
    userRole !== 'super_admin' &&
    !userPermissions.manageAdmins
) {
    showToast('ليس لديك صلاحية لحذف المسؤولين');
    return;
}
  const a = state.admins.find(x => x.id == id);

  if(!a) return;

  if(a.role === 'super_admin'){
    showToast('⚠️ لا يمكن حذف حساب Super Admin');
    return;
  }

  if(!confirm('هل أنت متأكد من حذف هذا المسؤول؟')){
    return;
  }

  fetch('delete_user.php', {
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded'
    },
    body:'id=' + id
  })
  .then(r => r.json())
  .then(data => {

    if(data.success){

      state.admins = state.admins.filter(x => x.id != id);

      renderAdmins();

      showToast('🗑️ تم حذف المسؤول ' + a.name);

      addActivity(
        'fa-trash',
        'rgba(239,68,68,.12)',
        '#f87171',
        'حذف مسؤول',
        'تم حذف حساب ' + a.name
      );

    }else{

      alert(data.message || 'فشل الحذف');

    }

  });

}


const state = {
  maintenanceOn: false,
 requests: <?= json_encode(array_map(function($u){
   return [
    'id' => $u['id'],
    'name' => $u['full_name'],
    'fullName' => $u['full_name'],
    'email' => $u['email'],
    'phone' => $u['phone'],
    'wilaya' => $u['wilaya'],
    'specialty' => $u['specialty'],
    'license_number' => $u['license_number'],
    'workplace' => $u['workplace'],
    'experience' => $u['experience'],
    'license_file' => $u['license_file'],
    'docs' => !empty($u['license_file']) ? [$u['license_file']] : [],
    'type' => ($u['role'] === 'clinic'
        ? ($u['institution_type'] === 'hospital' ? 'مستشفى' : 'عيادة')
        : [
            'patient'  => 'مريض',
            'doctor'   => 'طبيب',
            'pharmacy' => 'صيدلية',
            'lab'      => 'مخبر تحاليل',
          ][$u['role']] ?? $u['role']),
    'city' => $u['wilaya'],
    'date' => $u['created_at'],
    'status' => 'pending'
];
}, $pendingUsers), JSON_UNESCAPED_UNICODE); ?>,
approvedUsers: <?= json_encode(array_map(function($u){

    return [
        'id' => $u['id'],
        'name' => $u['full_name'],
        'email' => $u['email'],
        'phone' => $u['phone'],
       'type' => match($u['role']){
    'patient'  => 'مريض',
    'doctor'   => 'طبيب',
    'clinic'   => ($u['institution_type'] === 'hospital' ? 'مستشفى' : 'عيادة'),
    'pharmacy' => 'صيدلية',
    'lab'      => 'مخبر تحاليل',
    default    => $u['role']
},
        'date' => $u['created_at'],
        'status' => 'active',
        'account_status' => $u['account_status']
    ];

}, $approvedUsers), JSON_UNESCAPED_UNICODE); ?>,
  users: [
    {id:1, name:'د. كريم بلعيد', email:'k.belaid@med.dz', phone:'0770 112 001', type:'طبيب', status:'active', date:'2025-01-15', lastLogin:'اليوم، 10:42'},
    {id:2, name:'ليلى حمداوي', email:'l.hamdawi@gmail.com', phone:'0551 234 567', type:'مريض', status:'active', date:'2025-01-12', lastLogin:'أمس، 18:30'},
    {id:3, name:'محمد أوعمر', email:'m.ouamer@clinic.dz', phone:'0661 445 778', type:'ممرض', status:'active', date:'2025-01-10', lastLogin:'2025-01-14، 09:15'},
    {id:4, name:'فريدة زروال', email:'f.zeroual@pharm.dz', phone:'0790 887 321', type:'صيدلانية', status:'inactive', date:'2025-01-08', lastLogin:'2025-01-07، 14:00'},
    {id:5, name:'د. عمر بوالصوف', email:'o.boussaouf@med.dz', phone:'0559 001 223', type:'طبيب', status:'active', date:'2025-01-05', lastLogin:'اليوم، 08:55'},
    {id:6, name:'نجوى سعيدي', email:'n.saidi@gmail.com', phone:'0662 554 119', type:'مريض', status:'active', date:'2025-01-03', lastLogin:'2025-01-13، 16:45'},
    {id:7, name:'يوسف حرشاوي', email:'y.harchaoui@lab.dz', phone:'0791 332 880', type:'مخبر', status:'inactive', date:'2024-12-28', lastLogin:'2024-12-27، 11:00'},
    {id:8, name:'أسماء قرباج', email:'a.kerbach@gmail.com', phone:'0553 998 441', type:'مريض', status:'active', date:'2024-12-20', lastLogin:'أمس، 20:10'},
    {id:9, name:'د. سامي بلحسين', email:'s.belhocine@med.dz', phone:'0770 665 002', type:'طبيب', status:'active', date:'2024-12-15', lastLogin:'اليوم، 07:30'},
    {id:10, name:'رشيد تومي', email:'r.toumi@gmail.com', phone:'0561 771 334', type:'مريض', status:'active', date:'2024-12-10', lastLogin:'2025-01-12، 12:00'},
  ],
  institutions: [
    /* ── مرضى ── */
    {id:101, name:'ليلى حمداوي',       type:'مريض',         city:'الجزائر',   email:'l.hamdawi@gmail.com',          phone:'0551 234 567', date:'2025-01-12', status:'active',   verified:'verified', docs:['بطاقة الهوية']},
    {id:102, name:'نجوى سعيدي',        type:'مريض',         city:'وهران',     email:'n.saidi@gmail.com',            phone:'0662 554 119', date:'2025-01-03', status:'active',   verified:'verified', docs:['بطاقة الهوية']},
    {id:103, name:'رشيد تومي',         type:'مريض',         city:'سطيف',      email:'r.toumi@gmail.com',            phone:'0561 771 334', date:'2024-12-10', status:'active',   verified:'verified', docs:['بطاقة الهوية']},
    {id:104, name:'أسماء قرباج',       type:'مريض',         city:'تلمسان',    email:'a.kerbach@gmail.com',          phone:'0553 998 441', date:'2024-12-20', status:'active',   verified:'verified', docs:['بطاقة الهوية']},
    /* ── أطباء ── */
    {id:201, name:'د. كريم بلعيد',     type:'طبيب',         city:'الجزائر',   email:'k.belaid@med.dz',              phone:'0770 112 001', date:'2025-01-15', status:'active',   verified:'verified', docs:['بطاقة الهوية','رخصة الممارسة']},
    {id:202, name:'د. عمر بوالصوف',    type:'طبيب',         city:'وهران',     email:'o.boussaouf@med.dz',           phone:'0559 001 223', date:'2025-01-05', status:'active',   verified:'verified', docs:['بطاقة الهوية','رخصة الممارسة']},
    {id:203, name:'د. سامي بلحسين',    type:'طبيب',         city:'الجزائر',   email:'s.belhocine@med.dz',           phone:'0770 665 002', date:'2024-12-15', status:'active',   verified:'verified', docs:['بطاقة الهوية','رخصة الممارسة']},
    {id:204, name:'د. نورة حمزة',      type:'طبيب',         city:'بجاية',     email:'n.hamza@medchifa.dz',          phone:'0559 874 231', date:'2025-01-05', status:'inactive', verified:'pending',  docs:['بطاقة الهوية','رخصة الممارسة']},
    /* ── مستشفيات ── */
    {id:1,   name:'مستشفى مصطفى باشا', type:'مستشفى',       city:'الجزائر',   email:'chum@sante.dz',                phone:'021 23 45 67', date:'2024-09-10', status:'active',   verified:'verified', docs:['ترخيص وزارة الصحة','السجل التجاري','شهادة المطابقة','هوية المدير']},
    {id:2,   name:'مستشفى بن عكنون',   type:'مستشفى',       city:'الجزائر',   email:'hba@sante.dz',                 phone:'021 91 23 45', date:'2024-09-15', status:'active',   verified:'verified', docs:['ترخيص وزارة الصحة','السجل التجاري','شهادة المطابقة']},
    /* ── عيادات ── */
    {id:3,   name:'عيادة الشفاء',      type:'عيادة',         city:'الجزائر',   email:'clinique.shifa@gmail.com',     phone:'021 67 89 01', date:'2024-11-01', status:'active',   verified:'verified', docs:['رخصة الممارسة','السجل التجاري','هوية الطبيب المسؤول']},
    {id:4,   name:'عيادة الأمل',       type:'عيادة',         city:'سطيف',      email:'clinique.amel@gmail.com',      phone:'036 92 11 44', date:'2024-11-15', status:'active',   verified:'verified', docs:['رخصة الممارسة','هوية الطبيب المسؤول']},
    {id:5,   name:'عيادة الرشيد',      type:'عيادة',         city:'بجاية',     email:'clinique.rachid@yahoo.fr',     phone:'034 21 78 90', date:'2024-12-10', status:'active',   verified:'pending',  docs:['رخصة الممارسة']},
    /* ── صيدليات ── */
    {id:6,   name:'صيدلية النور',      type:'صيدلية',        city:'وهران',     email:'pharm.nour@gmail.com',         phone:'041 33 22 11', date:'2024-11-05', status:'active',   verified:'verified', docs:['رخصة الصيدلي','السجل التجاري']},
    {id:7,   name:'صيدلية الصباح',     type:'صيدلية',        city:'سطيف',      email:'pharmacie.sabah@gmail.com',    phone:'036 84 55 20', date:'2025-01-09', status:'active',   verified:'verified', docs:['رخصة الصيدلي','السجل التجاري','هوية الصيدلاني']},
    {id:8,   name:'صيدلية الحياة',     type:'صيدلية',        city:'تلمسان',    email:'pharm.hayat@outlook.com',      phone:'043 27 61 84', date:'2024-12-20', status:'inactive', verified:'rejected', docs:['رخصة الصيدلي']},
    /* ── مخابر ── */
    {id:9,   name:'مخبر بن سليمان',   type:'مخبر تحاليل',  city:'قسنطينة',   email:'labo.bensalim@med.dz',         phone:'031 64 78 90', date:'2024-11-10', status:'active',   verified:'verified', docs:['رخصة الممارسة','السجل التجاري','شهادة الاعتماد']},
    {id:10,  name:'مخبر التحليل الغربي',type:'مخبر تحاليل', city:'تلمسان',    email:'labo.gharb@gmail.com',         phone:'043 20 54 77', date:'2024-12-01', status:'inactive', verified:'pending',  docs:['رخصة الممارسة']},
    {id:11,  name:'مخبر تحاليل الأمان',type:'مخبر تحاليل',  city:'وهران',     email:'labo.amane@gmail.com',         phone:'041 55 32 08', date:'2025-01-12', status:'active',   verified:'verified', docs:['رخصة الممارسة','شهادة الاعتماد','السجل التجاري']},
  ],
admins: <?= json_encode(array_map(function($a){

    return [
        'id' => (int)$a['id'],
        'name' => $a['full_name'],
        'email' => $a['email'],
        'role' => $a['role'],
        'status' => $a['account_status'] === 'active' ? 'active' : 'inactive',
        'date' => substr($a['created_at'],0,10),
     'perms' => json_decode($a['permissions'] ?? '{}', true) ?: []
    ];

}, $admins), JSON_UNESCAPED_UNICODE); ?>,
 activities: []

};
fetch('get_activities.php')
  .then(r => r.json())
  .then(data => {
    state.activities = Array.isArray(data) ? data : [];
    renderActivity();
  })
  .catch(() => {
    state.activities = [];
    renderActivity();
  });

/* ══════════════════════════════════════
   NAVIGATION
══════════════════════════════════════ */
const sectionTitles = {
  dashboard: 'لوحة التحكم',
  requests: 'طلبات التسجيل',
  users: 'إدارة الحسابات',
  institutions: 'إدارة الحسابات',
  stats: 'إحصائيات المنصة',
  activity: 'سجل النشاطات',
  settings: 'إعدادات المنصة',
  admins: 'إدارة المسؤولين',
  maintenance: 'وضع الصيانة'
};
const userPermissions = <?= json_encode($_SESSION['permissions'] ?? []); ?>;
const userRole = <?= json_encode($_SESSION['role']); ?>;
const _currentRoleLabel = <?= json_encode($currentRoleLabel); ?>;
const _currentUserName  = <?= json_encode($currentUserName); ?>;
function switchSection(id, el) {
  const sectionPermissions = {
    requests: ['viewRequests', 'manageRequests'],
    institutions: ['viewUsers', 'manageUsers'],
    stats: ['viewStats'],
    activity: ['viewActivities'],
    settings: ['manageSettings'],
admins: ['viewAdmins', 'manageAdmins'],
    maintenance: ['manageMaintenance']
};

if (userRole !== 'super_admin' && sectionPermissions[id]) {

    const allowed = sectionPermissions[id].some(
        permission => userPermissions[permission] === true
    );

    if (!allowed) {
        showToast('ليس لديك صلاحية للوصول إلى هذا القسم');
        return;
    }
}
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('section-'+id).classList.add('active');
  if(el) el.classList.add('active');
  document.getElementById('headerTitle').textContent = sectionTitles[id] || id;
}

function toggleSidebar() {
  var sidebar  = document.getElementById('sidebar');
  var overlay  = document.getElementById('sidebarOverlay');
  var isMobile = window.innerWidth <= 768;
  if (isMobile) {
    var isOpen = sidebar.classList.contains('mobile-open');
    sidebar.classList.toggle('mobile-open', !isOpen);
    if (overlay) overlay.classList.toggle('active', !isOpen);
  } else {
    sidebar.classList.toggle('collapsed');
  }
}

function toggleTheme() {
  const isLight = document.body.classList.toggle('light');
  localStorage.setItem('mcg_theme', isLight ? 'light' : 'dark');
}

/* ══════════════════════════════════════
   CLOCK
══════════════════════════════════════ */
function updateClock() {
  const now = new Date();
  const t = now.toLocaleTimeString('ar-DZ',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
  const d = now.toLocaleDateString('ar-DZ',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
  document.getElementById('headerTime').textContent = t;
  document.getElementById('headerDate').textContent = d;
}
updateClock();
setInterval(updateClock, 1000);

/* ══════════════════════════════════════
   TOAST
══════════════════════════════════════ */
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 3000);
}

/* ══════════════════════════════════════
   MODAL
══════════════════════════════════════ */
function openModal(id) { document.getElementById(id).classList.add('open') }
function closeModal(id) { document.getElementById(id).classList.remove('open') }
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click', e=>{ if(e.target===o) o.classList.remove('open') });
});

/* ══════════════════════════════════════
   RENDER REQUESTS
══════════════════════════════════════ */
function renderRequests(data) {
  data = data || state.requests;
  const tbody = document.getElementById('requestsTableBody');
  tbody.innerHTML = data.map((r,i)=>{
    const sc = r.status==='approved'?'badge-approved':r.status==='rejected'?'badge-rejected':'badge-pending';
    const sl = r.status==='approved'?'موافق عليه':r.status==='rejected'?'مرفوض':'معلق';
    return `<tr>
      <td style="color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:12px">${String(i+1).padStart(2,'0')}</td>
      <td style="font-weight:600">${r.name}</td>
      <td><span class="type-badge">${r.type}</span></td>
      <td style="color:var(--text-muted)">${r.city}</td>
      <td style="color:var(--text-muted);font-size:12px">${r.date}</td>
      <td><span class="badge ${sc}">${sl}</span></td>
      <td><div class="td-actions">
        <button class="btn-info" onclick="viewRequest(${r.id})"><i class="fas fa-eye"></i> عرض الطلب</button>
      </div></td>
    </tr>`;
  }).join('');
}

function viewRequest(id) {
  const r = state.requests.find(x=>x.id===id);
  if(!r) return;
  const sc = r.status==='approved'?'badge-approved':r.status==='rejected'?'badge-rejected':'badge-pending';
  const sl = r.status==='approved'?'موافق عليه':r.status==='rejected'?'مرفوض':'معلق';

  // Account type icon
  const typeIcons = {
    'مريض':'fa-user-injured','طبيب':'fa-user-doctor','عيادة':'fa-clinic-medical',
    'مستشفى':'fa-hospital','صيدلية':'fa-prescription-bottle-medical',
    'مخبر تحاليل':'fa-flask'
  };
  const tIcon = typeIcons[r.type] || 'fa-id-card';

  // Docs list
  const docsHTML = (r.docs || []).map(d => `
  <div class="req-doc-item">
    <div class="req-doc-icon">
      <i class="fas fa-file-alt"></i>
    </div>

    <a href="${d}" target="_blank"
       style="color:var(--accent2);font-weight:600;text-decoration:none">
       عرض الوثيقة
    </a>

    <span class="req-doc-check">
      <i class="fas fa-check-circle"></i>
    </span>
  </div>
`).join('');
  // Reject reason block (if already rejected)
  const rejectedBlock = r.status==='rejected' && r.rejectReason ? `
    <div class="req-reject-note">
      <i class="fas fa-times-circle"></i>
      <div><div style="font-weight:700;margin-bottom:4px">سبب الرفض</div><div style="font-size:12.5px">${r.rejectReason}</div></div>
    </div>` : '';

  document.getElementById('viewRequestContent').innerHTML = `
    <!-- Header card -->
    <div class="req-header-card">
      <div class="req-avatar"><i class="fas ${tIcon}"></i></div>
      <div>
        <div class="req-name">${r.fullName || r.name}</div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap">
          <span class="type-badge">${r.type}</span>
          <span class="badge ${sc}">${sl}</span>
        </div>
      </div>
    </div>

    <!-- Section: معلومات الحساب -->
    <div class="req-section-label"><i class="fas fa-user-circle"></i> معلومات الحساب</div>
    <div class="req-info-grid">
      <div class="modal-info-row"><span><i class="fas fa-id-badge"></i> الاسم الكامل</span><span style="font-weight:700">${r.fullName || r.name}</span></div>
      <div class="modal-info-row"><span><i class="fas fa-envelope"></i> البريد الإلكتروني</span><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--accent2)">${r.email || '—'}</span></div>
      <div class="modal-info-row"><span><i class="fas fa-phone"></i> رقم الهاتف</span><span style="font-family:'JetBrains Mono',monospace;font-size:13px">${r.phone || '—'}</span></div>
      <div class="modal-info-row"><span><i class="fas fa-map-marker-alt"></i> الولاية</span><span>${r.city}</span></div>
      <div class="modal-info-row"><span><i class="fas fa-calendar-alt"></i> تاريخ التسجيل</span><span>${r.date}</span></div>
    </div>

  

    <!-- Section: الوثائق -->
    <div class="req-section-label"><i class="fas fa-paperclip"></i> الوثائق المرفوعة</div>
    <div class="req-docs-list">
      ${docsHTML || '<div style="color:var(--text-dim);font-size:13px;padding:10px">لا توجد وثائق مرفوعة</div>'}
    </div>

    ${rejectedBlock}

    <!-- Actions -->
    ${r.status==='pending' ? `
    <div class="req-action-zone" id="reqActionZone_${r.id}">
      <div class="req-divider"></div>
      <div id="rejectReasonBlock_${r.id}" class="req-reject-field" style="display:none">
        <label for="rejectReasonInput_${r.id}"><i class="fas fa-comment-alt"></i> سبب الرفض <span style="color:#f87171">*</span></label>
        <textarea id="rejectReasonInput_${r.id}" placeholder="أدخل سبب رفض الطلب بوضوح..." rows="3"></textarea>
        <div id="rejectReasonError_${r.id}" style="color:#f87171;font-size:12px;display:none;margin-top:4px"><i class="fas fa-exclamation-circle"></i> هذا الحقل إجباري</div>
      </div>
      <div class="req-modal-actions">
        <button class="req-btn-reject" id="rejectBtn_${r.id}" onclick="toggleRejectField(${r.id})">
          <i class="fas fa-times-circle"></i> رفض الطلب
        </button>
        <button class="req-btn-confirm-reject" id="confirmRejectBtn_${r.id}" style="display:none" onclick="confirmReject(${r.id})">
          <i class="fas fa-ban"></i> تأكيد الرفض
        </button>
        <button class="req-btn-approve" onclick="approveRequestFromModal(${r.id})">
          <i class="fas fa-check-circle"></i> قبول الطلب
        </button>
      </div>
    </div>` : ''}
  `;
  openModal('viewRequestModal');
}

function toggleRejectField(id) {
  const block = document.getElementById(`rejectReasonBlock_${id}`);
  const rejectBtn = document.getElementById(`rejectBtn_${id}`);
  const confirmBtn = document.getElementById(`confirmRejectBtn_${id}`);
  const isVisible = block.style.display !== 'none';
  block.style.display = isVisible ? 'none' : 'block';
  rejectBtn.style.display = isVisible ? 'inline-flex' : 'none';
  confirmBtn.style.display = isVisible ? 'none' : 'inline-flex';
}

function confirmReject(id) {
  const textarea = document.getElementById(`rejectReasonInput_${id}`);
  const errEl = document.getElementById(`rejectReasonError_${id}`);
  const reason = textarea.value.trim();
  if(!reason) {
    errEl.style.display = 'block';
    textarea.style.borderColor = '#f87171';
    return;
  }
  errEl.style.display = 'none';
  rejectRequest(id, reason);
  closeModal('viewRequestModal');
}

function approveRequestFromModal(id) {
  approveRequest(id);
  closeModal('viewRequestModal');
}

function approveRequest(id){
if (
    userRole !== 'super_admin' &&
    userPermissions.manageRequests !== true
) {
    showToast('ليس لديك صلاحية للموافقة على الطلبات');
    return;
}
fetch('update_request_status.php',{
    method:'POST',
    headers:{
        'Content-Type':'application/x-www-form-urlencoded'
    },
    body:'id='+id+'&status=approved'
})
.then(r=>r.json())
.then(data=>{

    const r = state.requests.find(x=>x.id===id);

    if(!r) return;

    r.status='approved';

    renderRequests();

    showToast  (`تمت الموافقة على ${r.name}`);

});

}

function rejectRequest(id,reason){
  if (
    userRole !== 'super_admin' &&
    userPermissions.manageRequests !== true
) {
    showToast('ليس لديك صلاحية لرفض الطلبات');
    return;
}

if(!reason.trim()){
    alert('اكتب سبب الرفض');
    return;
}

fetch('update_request_status.php',{
    method:'POST',
    headers:{
        'Content-Type':'application/x-www-form-urlencoded'
    },
    body:'id='+id+
         '&status=rejected'+
         '&reason='+encodeURIComponent(reason)
})
.then(r=>r.json())
.then(data=>{

    const r = state.requests.find(x=>x.id===id);

    if(!r) return;

    r.status='rejected';

    renderRequests();

    showToast(`تم رفض الطلب  ${r.name}`);

});

}

/* ══════════════════════════════════════
   RENDER INSTITUTIONS
══════════════════════════════════════ */
let instActiveType = 'الكل';

const instTypeConfig = {
  'مريض':          {icon:'fa-user-injured',               color:'#a78bfa', bg:'rgba(167,139,250,.12)',  chipColor:'#a78bfa'},
  'طبيب':          {icon:'fa-user-doctor',                 color:'#34d399', bg:'rgba(52,211,153,.12)',   chipColor:'#34d399'},
  'مستشفى':       {icon:'fa-hospital',                    color:'#f87171', bg:'rgba(239,68,68,.12)',    chipColor:'#f87171'},
  'عيادة':         {icon:'fa-clinic-medical',              color:'#38bdf8', bg:'rgba(14,165,233,.12)',   chipColor:'#38bdf8'},
  'صيدلية':        {icon:'fa-prescription-bottle-medical', color:'#34d399', bg:'rgba(16,185,129,.12)',   chipColor:'#34d399'},
  'مخبر تحاليل':  {icon:'fa-flask',                       color:'#a78bfa', bg:'rgba(167,139,250,.12)',  chipColor:'#a78bfa'},
};

const verifiedConfig = {
  'verified': {label:'موثّقة',   cls:'badge-active',    icon:'fa-circle-check'},
  'pending':  {label:'قيد المراجعة', cls:'badge-pending', icon:'fa-clock'},
  'rejected': {label:'مرفوضة',  cls:'badge-rejected',   icon:'fa-circle-xmark'},
};

function buildInstStats() {
  const row = document.getElementById('instStatsRow');
  if(!row) return;
  const types = ['مريض','طبيب','مستشفى','عيادة','صيدلية','مخبر تحاليل'];
  const total = state.institutions.length;
  const active = state.institutions.filter(x=>x.status==='active').length;
  let html = `
    <div class="inst-stat-chip" style="--chip-clr:#0ea5e9">
      <div class="inst-stat-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9"><i class="fas fa-building-columns"></i></div>
      <div><div class="inst-stat-val">${total}</div><div class="inst-stat-lbl">إجمالي المؤسسات</div></div>
    </div>
    <div class="inst-stat-chip" style="--chip-clr:#34d399">
      <div class="inst-stat-icon" style="background:rgba(16,185,129,.12);color:#34d399"><i class="fas fa-circle-check"></i></div>
      <div><div class="inst-stat-val">${active}</div><div class="inst-stat-lbl">نشطة</div></div>
    </div>`;
  types.forEach(t => {
    const cfg = instTypeConfig[t];
    const cnt = state.institutions.filter(x=>x.type===t).length;
    html += `
    <div class="inst-stat-chip" style="--chip-clr:${cfg.chipColor}">
      <div class="inst-stat-icon" style="background:${cfg.bg};color:${cfg.color}"><i class="fas ${cfg.icon}"></i></div>
      <div><div class="inst-stat-val">${cnt}</div><div class="inst-stat-lbl">${t}</div></div>
    </div>`;
  });
  row.innerHTML = html;
}

function filterInstByType(btn, type) {
  instActiveType = type;
  document.querySelectorAll('.inst-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  applyInstFilter();
}

function applyInstFilter() {
  const q = (document.getElementById('instSearchInput')?.value||'').trim().toLowerCase();
  let data = state.approvedUsers;
  if(instActiveType !== 'الكل') data = data.filter(x=>x.type===instActiveType);
  if(q) data = data.filter(x=>x.name.toLowerCase().includes(q)||x.city.toLowerCase().includes(q)||(x.email||'').toLowerCase().includes(q));
  renderInstitutions(data);
}

function renderInstitutions(data) {
data = data || state.approvedUsers;

  const tbody = document.getElementById('institutionsTableBody');
  if(!tbody) return;
  if(data.length===0){
    tbody.innerHTML=`<tr><td colspan="8"><div class="empty-state"><i class="fas fa-users-rectangle"></i><p>لا توجد حسابات مطابقة لمعايير البحث</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = data.map((inst,i)=>{
    const sc = inst.account_status==='active'
    ? 'badge-active'
    : 'badge-inactive';

const sl = inst.account_status==='active'
    ? 'نشط'
    : 'معطّل';
    const cfg = instTypeConfig[inst.type] || {icon:'fa-user',color:'#7a8fa6',bg:'rgba(122,143,166,.1)'};
    const initials = inst.name.trim().split(' ').slice(0,2).map(w=>w[0]).join('');
    return `<tr>
      <td style="color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:12px">${String(i+1).padStart(2,'0')}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;background:${cfg.bg};color:${cfg.color}">
            <i class="fas ${cfg.icon}"></i>
          </div>
          <div>
            <span style="font-weight:600;display:block">${inst.name}</span>
            ${inst.phone?`<span style="font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace">${inst.phone}</span>`:''}
          </div>
        </div>
      </td>
      <td>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;background:${cfg.bg};color:${cfg.color}">
          <i class="fas ${cfg.icon}" style="font-size:10px"></i> ${inst.type}
        </span>
      </td>
      <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace">${inst.email||'—'}</td>
      <td style="color:var(--text-muted)"><i class="fas fa-map-marker-alt" style="color:var(--text-dim);font-size:11px;margin-left:4px"></i>${inst.city}</td>
      <td style="color:var(--text-muted);font-size:12px">${inst.date}</td>
      <td><span class="badge ${sc}">${sl}</span></td>
      <td><div class="td-actions">
        <button class="btn-action btn-action-view" onclick="viewInst(${inst.id})"><i class="fas fa-eye"></i><span>عرض</span></button>
        <button class="btn-action btn-action-edit" onclick="editInst(${inst.id})"><i class="fas fa-pen"></i><span>تعديل</span></button>
      ${inst.account_status==='active'
          ?`<button class="btn-action btn-action-disable" onclick="toggleInst(${inst.id})"><i class="fas fa-ban"></i><span>تعطيل</span></button>`
          :`<button class="btn-action btn-action-enable" onclick="toggleInst(${inst.id})"><i class="fas fa-circle-check"></i><span>تفعيل</span></button>`}
        <button class="btn-action btn-action-delete" onclick="deleteInst(${inst.id})"><i class="fas fa-trash-can"></i><span>حذف</span></button>
      </div></td>
    </tr>`;
  }).join('');
}

function viewInst(id) {
const inst = state.approvedUsers.find(x=>x.id==id);
  if(!inst) return;
  const sc = inst.status==='active'?'badge-active':'badge-inactive';
  const sl = inst.status==='active'?'نشط':'معطّل';
  const sdot = inst.status==='active'?'#10b981':'#ef4444';
  const cfg = instTypeConfig[inst.type] || {icon:'fa-user',color:'#7a8fa6',bg:'rgba(122,143,166,.1)'};

  const docsHTML = (inst.docs||[]).map(d=>`
    <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.15);border-radius:8px">
      <div style="width:30px;height:30px;border-radius:7px;background:rgba(16,185,129,.12);display:flex;align-items:center;justify-content:center;font-size:12px;color:#34d399;flex-shrink:0"><i class="fas fa-file-alt"></i></div>
      <span style="font-size:13px;font-weight:600;color:var(--text);flex:1">${d}</span>
      <i class="fas fa-circle-check" style="color:#34d399;font-size:14px"></i>
    </div>`).join('');

  document.getElementById('viewInstContent').innerHTML = `
    <!-- Header -->
    <div style="background:linear-gradient(135deg,${cfg.bg.replace('.12)','.18)')},rgba(99,102,241,.06));border:1px solid ${cfg.color}33;border-radius:14px;padding:20px;margin-bottom:18px;display:flex;align-items:center;gap:16px">
      <div style="width:60px;height:60px;border-radius:15px;background:${cfg.bg};border:1.5px solid ${cfg.color}44;display:flex;align-items:center;justify-content:center;font-size:26px;color:${cfg.color};flex-shrink:0;box-shadow:0 4px 16px ${cfg.color}22">
        <i class="fas ${cfg.icon}"></i>
      </div>
      <div style="flex:1">
        <div style="font-size:18px;font-weight:800;color:var(--text);margin-bottom:5px">${inst.name}</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;background:${cfg.bg};color:${cfg.color}">
            <i class="fas ${cfg.icon}" style="font-size:10px"></i> ${inst.type}
          </span>
          <span class="badge ${sc}" style="display:inline-flex;align-items:center;gap:4px">
            <span style="width:6px;height:6px;border-radius:50%;background:${sdot};display:inline-block"></span>${sl}
          </span>
        </div>
      </div>
    </div>

    <!-- Section: معلومات الحساب -->
    <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:.7px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--border)">
      <i class="fas fa-info-circle"></i> معلومات الحساب
    </div>
    <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:18px">
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-signature" style="color:var(--text-dim);width:14px;text-align:center"></i> الاسم</span>
        <span style="font-weight:700">${inst.name}</span>
      </div>
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas ${cfg.icon}" style="color:${cfg.color};width:14px;text-align:center"></i> التصنيف</span>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;background:${cfg.bg};color:${cfg.color}">${inst.type}</span>
      </div>
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-map-pin" style="color:var(--text-dim);width:14px;text-align:center"></i> الولاية</span>
        <span style="font-weight:600">${inst.city}</span>
      </div>
    </div>

    <!-- Section: معلومات الاتصال -->
    <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:.7px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--border)">
      <i class="fas fa-address-book"></i> معلومات الاتصال
    </div>
    <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:18px">
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-phone" style="color:var(--text-dim);width:14px;text-align:center"></i> الهاتف</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px">${inst.phone || '—'}</span>
      </div>
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-envelope" style="color:var(--text-dim);width:14px;text-align:center"></i> البريد الإلكتروني</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--accent2)">${inst.email || '—'}</span>
      </div>
    </div>

    <!-- Section: حالة الحساب -->
    <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:.7px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--border)">
      <i class="fas fa-shield-halved"></i> حالة الحساب
    </div>
    <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:18px">
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-circle-dot" style="color:${sdot};width:14px;text-align:center"></i> الحالة الحالية</span>
        <span class="badge ${sc}">${sl}</span>
      </div>
      <div class="modal-info-row">
        <span style="display:flex;align-items:center;gap:7px"><i class="fas fa-calendar-plus" style="color:var(--text-dim);width:14px;text-align:center"></i> تاريخ التسجيل</span>
        <span style="font-weight:600">${inst.date}</span>
      </div>
    </div>

    <!-- Section: الوثائق -->
    <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:.7px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--border)">
      <i class="fas fa-paperclip"></i> الوثائق المرفوعة (${(inst.docs||[]).length})
    </div>
    <div style="display:flex;flex-direction:column;gap:7px">
      ${docsHTML || '<div style="color:var(--text-dim);font-size:13px;padding:10px 4px">لا توجد وثائق مرفوعة</div>'}
    </div>
  `;
  openModal('viewInstModal');
}

let editingInstId = null;
function editInst(id) {
  if (
    userRole !== 'super_admin' &&
userPermissions.manageUsers !== true
) {
    showToast('ليس لديك صلاحية لتعديل الحسابات');
    return;
}
 const inst = state.approvedUsers.find(x=>x.id==id);
  if(!inst) return;
  editingInstId = id;
  document.getElementById('editInstName').value = inst.name;
  document.getElementById('editInstCity').value = inst.city;
  document.getElementById('editInstType').value = inst.type;
  openModal('editInstModal');
}

function saveEditInst() {
const inst = state.approvedUsers.find(x=>x.id==editingInstId);
  if(!inst) return;
  inst.name = document.getElementById('editInstName').value;
  inst.city = document.getElementById('editInstCity').value;
  inst.type = document.getElementById('editInstType').value;
  fetch('update_user.php',{
    method:'POST',
    headers:{
        'Content-Type':'application/x-www-form-urlencoded'
    },
    body:
        'id=' + editingInstId +
        '&name=' + encodeURIComponent(inst.name)
})
.then(r=>r.json())
.then(data=>{
    console.log(data);
});
  closeModal('editInstModal');
  applyInstFilter();
  showToast(`✏️ تم تعديل بيانات ${inst.name}`);
  addActivity('fa-pen','rgba(14,165,233,.15)','#38bdf8','تعديل مؤسسة',`تم تعديل بيانات ${inst.name}`);
}

function toggleInst(id) {
if (
    userRole !== 'super_admin' &&
   userPermissions.manageUsers !== true
) {
    showToast('ليس لديك صلاحية لتعطيل أو تفعيل الحسابات');
    return;
}
    const inst = state.approvedUsers.find(x => x.id == id);

    if(!inst) return;

    const newStatus =
        inst.account_status === 'active'
        ? 'inactive'
        : 'active';

    fetch('toggle_user_status.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:'id=' + id + '&status=' + newStatus
    })
    .then(r=>r.json())
    .then(data=>{

        if(data.success){

            inst.account_status = newStatus;

            applyInstFilter();

            showToast(
                newStatus === 'active'
                ? 'تم تفعيل الحساب'
                : 'تم تعطيل الحساب'
            );

        }

    });

}

function deleteInst(id) {
  if (
    userRole !== 'super_admin' &&
   userPermissions.manageUsers !== true
) {
    showToast('ليس لديك صلاحية لحذف الحسابات');
    return;
}

  if(!confirm('هل أنت متأكد من حذف هذا الحساب؟')){
      return;
  }

  fetch('delete_user.php', {
      method:'POST',
      headers:{
          'Content-Type':'application/x-www-form-urlencoded'
      },
      body:'id=' + id
  })
  .then(r=>r.json())
  .then(data=>{

      if(data.success){

          state.approvedUsers =
              state.approvedUsers.filter(x => x.id != id);

          applyInstFilter();

          showToast('تم حذف الحساب بنجاح');

      }else{

          showToast('فشل الحذف');

      }

  });

}

/* ══════════════════════════════════════
   RENDER ACTIVITY
══════════════════════════════════════ */
function renderActivity() {
  const list = document.getElementById('fullActivityList');
  if (!state.activities || state.activities.length === 0) {
    list.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-sub)"><i class="fas fa-history" style="font-size:2rem;margin-bottom:10px;display:block"></i>لا توجد نشاطات مسجّلة حتى الآن</div>';
    return;
  }
  list.innerHTML = state.activities.map(a=>`
    <div class="activity-item">
      <div class="act-icon" style="background:${a.bg};color:${a.color}"><i class="fas ${a.icon}"></i></div>
      <div class="act-body">
        <div class="act-title">${a.title}</div>
        <div class="act-sub">${a.desc}</div>
        <div style="display:flex;gap:14px;align-items:center;margin-top:4px">
          <div class="act-time"><i class="fas fa-clock"></i> ${a.time}</div>
          <div class="act-user"><i class="fas fa-user-shield"></i> ${a.user}</div>
        </div>
      </div>
    </div>`).join('');
}

function addActivity(icon, bg, color, title, desc) {
  const now = new Date();
  const timeStr = now.toLocaleTimeString('ar-DZ',{hour:'2-digit',minute:'2-digit',hour12:false});
  state.activities.unshift({icon,bg,color,title,desc,user:_currentRoleLabel,time:`اليوم، ${timeStr}`});
  // update dashboard activity list too
  const dashList = document.getElementById('dashActivityList');
  const item = document.createElement('div');
  item.className='activity-item';
  item.style.animation='fadeIn .3s ease';
  item.innerHTML=`
    <div class="act-icon" style="background:${bg};color:${color}"><i class="fas ${icon}"></i></div>
    <div class="act-body">
      <div class="act-title">${title}</div>
      <div class="act-sub">${desc}</div>
      <div class="act-time"><i class="fas fa-clock"></i> اليوم، ${timeStr}</div>
    </div>`;
  dashList.insertBefore(item, dashList.firstChild);
  // keep only 4 in dashboard
  while(dashList.children.length > 4) dashList.removeChild(dashList.lastChild);
  // re-render full activity list if visible
  renderActivity();
  fetch('save_activity.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body:
        'icon=' + encodeURIComponent(icon) +
        '&bg=' + encodeURIComponent(bg) +
        '&color=' + encodeURIComponent(color) +
        '&title=' + encodeURIComponent(title) +
        '&description=' + encodeURIComponent(desc) +
        '&user_name=' + encodeURIComponent(_currentRoleLabel)
});
}

/* ══════════════════════════════════════
   MAINTENANCE
══════════════════════════════════════ */
let _maintStartTime = null;
let _maintTimerInterval = null;

function _maintFormatDuration(ms){
  const secs = Math.floor(ms/1000);
  const m = Math.floor(secs/60); const s = secs%60;
  if(m < 60) return `${m} دقيقة ${s} ثانية`;
  const h = Math.floor(m/60); const rm = m%60;
  return `${h} ساعة ${rm} دقيقة`;
}

function toggleMaintenance(on) {
  state.maintenanceOn = on;
  const enableBtn = document.getElementById('maintenanceEnableBtn');
  const disableBtn = document.getElementById('maintenanceDisableBtn');
  // New UI elements
  const card = document.getElementById('maintStatusCard');
  const badge = document.getElementById('maintHeaderBadge');
  const badgeText = document.getElementById('maintHeaderBadgeText');
  const bigIcon = document.getElementById('maintBigIcon');
  const bigIconInner = document.getElementById('maintBigIconInner');
  const title = document.getElementById('maintStatusTitle');
  const desc2 = document.getElementById('maintStatusDesc');
  const cellStatus = document.getElementById('maintCellStatus');
  const cellStatusSub = document.getElementById('maintCellStatusSub');
  const durationVal = document.getElementById('maintDurationVal');
  const durationSub = document.getElementById('maintDurationSub');
  const lastDate = document.getElementById('maintCellLastDate');

  if(on) {
    _maintStartTime = Date.now();
    if(_maintTimerInterval) clearInterval(_maintTimerInterval);
    _maintTimerInterval = setInterval(()=>{
      const elapsed = Date.now() - _maintStartTime;
      if(durationVal) durationVal.textContent = _maintFormatDuration(elapsed);
      if(durationSub) durationSub.textContent = 'الصيانة جارية الآن';
    }, 1000);

    // Card style
    if(card){ card.classList.add('is-on'); }
    // Badge
    if(badge){ badge.classList.add('active-maintenance'); }
    if(badgeText){ badgeText.textContent = 'وضع الصيانة مفعّل'; }
    // Big icon
    if(bigIcon){ bigIcon.className = 'maint-big-icon danger'; }
    if(bigIconInner){ bigIconInner.className = 'fas fa-screwdriver-wrench'; }
    // Title & desc
    if(title){ title.textContent = 'المنصة في وضع الصيانة'; title.className = 'danger'; }
    if(desc2){ desc2.textContent = 'وضع الصيانة مفعّل. المستخدمون لا يستطيعون الوصول إلى الخدمات في الوقت الحالي.'; }
    // Info cells
    if(cellStatus){ cellStatus.textContent = 'وضع الصيانة'; cellStatus.style.color='#f87171'; }
    if(cellStatusSub){ cellStatusSub.textContent = 'الوصول موقف مؤقتاً'; }
    if(durationVal){ durationVal.textContent = '00 دقيقة 00 ثانية'; }
    if(durationSub){ durationSub.textContent = 'الصيانة جارية الآن'; }
    // Buttons (hidden compat stubs — التحكم الفعلي في syncMaintSettingsUI)
    if(enableBtn) enableBtn.style.display='none';
    if(disableBtn) disableBtn.style.display='inline-flex';
    showToast('⚠️ تم تفعيل وضع الصيانة');
    addActivity('fa-screwdriver-wrench','rgba(239,68,68,.12)','#f87171','تفعيل وضع الصيانة','المنصة الآن في وضع الصيانة');
  } else {
    // Save last maintenance date
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    if(_maintTimerInterval){ clearInterval(_maintTimerInterval); _maintTimerInterval=null; }
    if(lastDate) lastDate.textContent = dateStr;

    // Card style
    if(card){ card.classList.remove('is-on'); }
    // Badge
    if(badge){ badge.classList.remove('active-maintenance'); }
    if(badgeText){ badgeText.textContent = 'المنصة تعمل بشكل طبيعي'; }
    // Big icon
    if(bigIcon){ bigIcon.className = 'maint-big-icon'; }
    if(bigIconInner){ bigIconInner.className = 'fas fa-shield-check'; }
    // Title & desc
    if(title){ title.textContent = 'المنصة تعمل بشكل طبيعي'; title.className = ''; }
    if(desc2){ desc2.textContent = 'جميع الخدمات متاحة للمستخدمين. لتفعيل وضع الصيانة يرجى ملء إعدادات الصيانة أدناه ثم الضغط على زر التفعيل.'; }
    // Info cells
    if(cellStatus){ cellStatus.textContent = 'نشطة — تعمل'; cellStatus.style.color=''; }
    if(cellStatusSub){ cellStatusSub.textContent = 'جميع الخدمات متاحة'; }
    if(durationVal){ durationVal.textContent = '—'; }
    if(durationSub){ durationSub.textContent = 'لا توجد صيانة نشطة'; }
    // Buttons (hidden compat stubs)
    if(enableBtn) enableBtn.style.display='inline-flex';
    if(disableBtn) disableBtn.style.display='none';
    _maintStartTime = null;
    showToast('✅ تم إيقاف وضع الصيانة');
    addActivity('fa-check-circle','rgba(16,185,129,.15)','#34d399','إيقاف وضع الصيانة','المنصة تعمل بشكل طبيعي');
  }
}

/* ══════════════════════════════════════
   SETTINGS SAVE
══════════════════════════════════════ */
function saveSettings() {
  const data = new URLSearchParams({
    platform_name: document.getElementById('settingPlatformName').value,
    email:         document.getElementById('settingEmail').value,
    phone:         document.getElementById('settingPhone').value,
    website:       document.getElementById('settingWebsite').value,
    policy:        document.getElementById('settingPolicy').value,
  });
  fetch('save_platform_settings.php', { method:'POST', body: data })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast('💾 تم حفظ إعدادات المنصة بنجاح');
        addActivity('fa-gear','rgba(14,165,233,.12)','#38bdf8','حفظ إعدادات المنصة','تم تحديث البيانات الأساسية للمنصة');
      } else {
        showToast('❌ فشل الحفظ: ' + (res.message || ''));
      }
    })
    .catch(() => showToast('❌ تعذّر الاتصال بالخادم'));
}

function uploadLogo(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('action', 'upload_logo');
  fd.append('logo', file);
  fetch('save_platform_settings.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        // عرض الشعار الجديد في بطاقة الإعدادات
        const avatar = document.querySelector('#section-settings .settings-avatar-big span');
        if (avatar) avatar.innerHTML = `<img src="${res.logo_path}" style="width:100%;height:100%;object-fit:contain;border-radius:50%">`;
        // تحديث الشعار في السايدبار مباشرة
        const sidebarLogoIcon = document.querySelector('.sidebar-logo .logo-icon');
        if (sidebarLogoIcon) {
          sidebarLogoIcon.innerHTML = `<img src="${res.logo_path}" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:6px;">`;
          sidebarLogoIcon.style.cssText = 'background:transparent;padding:0;width:38px;height:38px;overflow:hidden;border-radius:8px;display:flex;align-items:center;justify-content:center;';
        }
        showToast('🖼️ تم تحديث الشعار بنجاح');
        addActivity('fa-image','rgba(14,165,233,.12)','#38bdf8','تحديث الشعار','تم رفع شعار جديد للمنصة');
      } else {
        showToast('❌ ' + (res.message || 'فشل رفع الشعار'));
      }
    })
    .catch(() => showToast('❌ تعذّر رفع الشعار'));
  input.value = '';
}


function loadPlatformSettings() {
  fetch('get_platform_settings.php')
    .then(r => r.json())
    .then(s => {
      if (s.platform_name) document.getElementById('settingPlatformName').value = s.platform_name;
      if (s.email)         document.getElementById('settingEmail').value         = s.email;
      if (s.phone)         document.getElementById('settingPhone').value         = s.phone;
      if (s.website)       document.getElementById('settingWebsite').value       = s.website;
      if (s.policy)        document.getElementById('settingPolicy').value        = s.policy;
      if (s.logo_path) {
        // تحديث الشعار في قسم الإعدادات
        const avatar = document.querySelector('#section-settings .settings-avatar-big span');
        if (avatar) avatar.innerHTML = `<img src="${s.logo_path}" style="width:100%;height:100%;object-fit:contain;border-radius:50%">`;
        // تحديث الشعار في السايدبار
        const sidebarLogoIcon = document.querySelector('.sidebar-logo .logo-icon');
        if (sidebarLogoIcon) {
          sidebarLogoIcon.innerHTML = `<img src="${s.logo_path}" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:6px;">`;
          sidebarLogoIcon.style.cssText = 'background:transparent;padding:0;width:38px;height:38px;overflow:hidden;border-radius:8px;display:flex;align-items:center;justify-content:center;';
        }
      }
    })
    .catch(() => {});
}

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', ()=>{
  // Inject permissions checkboxes into modals
  document.getElementById('addAdminPerms').innerHTML = buildPermsHTML('add');
  document.getElementById('editAdminPerms').innerHTML = buildPermsHTML('edit');

  renderRequests();
  renderInstitutions();
  renderActivity();
  renderAdmins();
  loadPlatformSettings();
  loadMaintState();

  // search institutions (unified accounts)
  document.getElementById('instSearchInput').addEventListener('input', applyInstFilter);

  // search admins
  document.getElementById('adminsSearchInput').addEventListener('input', function(){
    const q = this.value.trim().toLowerCase();
    const filtered = q ? state.admins.filter(a=>a.name.toLowerCase().includes(q)||a.email.toLowerCase().includes(q)) : state.admins;
    renderAdmins(filtered);
  });
});

/* ══════════════════════════════════════
   CHART.JS — STATS PAGE
══════════════════════════════════════ */
let _chartsInited = false;

function initStatsCharts() {
  if (_chartsInited) return;
  _chartsInited = true;

  const isDark = !document.body.classList.contains('light');
  const gridColor  = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
  const tickColor  = isDark ? '#7a8fa6' : '#64748b';
  const tooltipBg  = isDark ? '#1a2540' : '#fff';
  const tooltipFg  = isDark ? '#e2e8f0' : '#1e293b';

  Chart.defaults.font.family = "'Cairo', sans-serif";

  /* ─── 1. BAR CHART: Registrations by Month ─── */
  fetch('stats_data.php')
.then(r => r.json())
.then(data => {


    const stats = data.monthly;
    const roles = data.roles;
const growth = data.growth;
const patientsGrowth = data.patientsGrowth;
const doctorsGrowth = data.doctorsGrowth;
    const monthlyData = Array(12).fill(0);

    stats.forEach(item => {
      monthlyData[item.month - 1] = parseInt(item.total);
    });
  const barCtx = document.getElementById('chartMonthlyRegistrations').getContext('2d');
  const barGrad = barCtx.createLinearGradient(0, 0, 0, 260);
  barGrad.addColorStop(0, 'rgba(14,165,233,.85)');
  barGrad.addColorStop(1, 'rgba(14,165,233,.2)');

  new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: ['يناير','فبراير','مارس','أبريل','ماي','جوان','جويلية','أوت','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
      datasets: [{
        label: 'عدد التسجيلات',
       data: monthlyData,
        backgroundColor: barGrad,
        borderColor: '#0ea5e9',
        borderWidth: 1.5,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: tooltipBg,
          titleColor: tooltipFg,
          bodyColor: tickColor,
          borderColor: 'rgba(14,165,233,.3)',
          borderWidth: 1,
          padding: 10,
          callbacks: {
            label: ctx => ` ${ctx.parsed.y} تسجيل`
          }
        }
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: { color: tickColor, font: { size: 11 } }
        },
        y: {
          beginAtZero: true,
          grid: { color: gridColor },
          ticks: {
            color: tickColor, font: { size: 11 },
            callback: v => v + ''
          }
        }
      }
    }
  });

  /* ─── 2. DOUGHNUT CHART: Account Distribution ─── */
  const donutCtx = document.getElementById('chartAccountDistribution').getContext('2d');
 const donutLabels = [
  'المرضى',
  'الأطباء',
  'الصيدليات',
  'العيادات',
  'المخابر',
  'Super Admin',
  'Admin',
  'Moderator'
];
const roleMap = {};

roles.forEach(item => {
    roleMap[item.role] = parseInt(item.total);
});

const donutValues = [
    roleMap.patient || 0,
    roleMap.doctor || 0,
    roleMap.pharmacy || 0,
    roleMap.clinic || 0,
    roleMap.lab || 0,
    roleMap.super_admin || 0,
    roleMap.admin || 0,
    roleMap.moderator || 0
];
  const donutColors  = ['#a78bfa','#34d399','#38bdf8','#0ea5e9','#fb923c','#fbbf24','#f87171'];

  new Chart(donutCtx, {
    type: 'doughnut',
    data: {
      labels: donutLabels,
      datasets: [{
        data: donutValues,
        backgroundColor: donutColors.map(c => c + 'cc'),
        borderColor: donutColors,
        borderWidth: 2,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '66%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: tooltipBg,
          titleColor: tooltipFg,
          bodyColor: tickColor,
          borderColor: 'rgba(14,165,233,.3)',
          borderWidth: 1,
          padding: 10,
          callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString('ar-DZ')} حساب`
          }
        }
      }
    }
  });


  // Build custom legend
  const legendEl = document.getElementById('donutLegend');
  const total = donutValues.reduce((a, b) => a + b, 0);
  legendEl.innerHTML = donutLabels.map((lbl, i) => {
    const pct = Math.round(donutValues[i] / total * 100);
    return `<div style="display:flex;align-items:center;gap:7px;white-space:nowrap">
      <div style="width:10px;height:10px;border-radius:3px;background:${donutColors[i]};flex-shrink:0"></div>
      <span style="font-size:12px;color:var(--text-muted)">${lbl}</span>
      <span style="font-size:12px;font-weight:700;color:var(--text);margin-right:auto;padding-right:6px">${pct}%</span>
    </div>`;
  }).join('');

  /* ─── 3. LINE CHART: Platform Growth ─── */
  const lineCtx = document.getElementById('chartPlatformGrowth').getContext('2d');
  const gradTotal = lineCtx.createLinearGradient(0, 0, 0, 280);
  gradTotal.addColorStop(0, 'rgba(14,165,233,.25)');
  gradTotal.addColorStop(1, 'rgba(14,165,233,0)');
  const gradPatients = lineCtx.createLinearGradient(0, 0, 0, 280);
  gradPatients.addColorStop(0, 'rgba(167,139,250,.2)');
  gradPatients.addColorStop(1, 'rgba(167,139,250,0)');
  const gradDoctors = lineCtx.createLinearGradient(0, 0, 0, 280);
  gradDoctors.addColorStop(0, 'rgba(52,211,153,.2)');
  gradDoctors.addColorStop(1, 'rgba(52,211,153,0)');

  new Chart(lineCtx, {
    type: 'line',
    data: {
      labels: ['يناير','فبراير','مارس','أبريل','ماي','جوان','جويلية','أوت','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
      datasets: [
       {
    label: 'إجمالي المستخدمين',
    data: growth,
          borderColor: '#0ea5e9',
          backgroundColor: gradTotal,
          borderWidth: 2.5,
          pointBackgroundColor: '#0ea5e9',
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: true,
          tension: 0.4
        },
        {
          label: 'المرضى',
         data: patientsGrowth,
          borderColor: '#a78bfa',
          backgroundColor: gradPatients,
          borderWidth: 2,
          pointBackgroundColor: '#a78bfa',
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          tension: 0.4
        },
        {
          label: 'الأطباء',
         data: doctorsGrowth,
          borderColor: '#34d399',
          backgroundColor: gradDoctors,
          borderWidth: 2,
          pointBackgroundColor: '#34d399',
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            color: tickColor,
            font: { size: 12 },
            usePointStyle: true,
            pointStyleWidth: 10,
            padding: 16
          }
        },
        tooltip: {
          backgroundColor: tooltipBg,
          titleColor: tooltipFg,
          bodyColor: tickColor,
          borderColor: 'rgba(14,165,233,.3)',
          borderWidth: 1,
          padding: 12,
          callbacks: {
            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('ar-DZ')}`
          }
        }
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: { color: tickColor, font: { size: 11 } }
        },
        y: {
          beginAtZero: false,
          grid: { color: gridColor },
          ticks: {
            color: tickColor, font: { size: 11 },
            callback: v => v.toLocaleString('ar-DZ')
          }
        }
      }
    }
 });

  // إغلاق .then(stats => {
  });

}

/* ══════════════════════════════════════
   MAINTENANCE SETTINGS — SECTION JS
══════════════════════════════════════ */

/* تحديث واجهة أزرار قسم الإعدادات فقط */
function syncMaintSettingsUI(isOn){
  const btnEnable  = document.getElementById('maintSettingsBtnEnable');
  const btnDisable = document.getElementById('maintSettingsBtnDisable');
  if(btnEnable)  btnEnable.style.display  = isOn ? 'none'        : 'inline-flex';
  if(btnDisable) btnDisable.style.display = isOn ? 'inline-flex' : 'none';
}

/* حفظ الإعدادات فقط (بدون تفعيل) */
function saveMaintSettings(){
  const start  = document.getElementById('maintStartDate').value;
  const end    = document.getElementById('maintEndDate').value;
  const type   = document.getElementById('maintType').value;
  const reason = (document.getElementById('maintReason').value||'').trim();
  if(start && end && new Date(end) <= new Date(start)){
    showToast('⚠️ تاريخ النهاية يجب أن يكون بعد تاريخ البداية'); return;
  }
  if(!type){ showToast('⚠️ يرجى اختيار نوع الصيانة'); return; }
  const data = new URLSearchParams({
    action:'save_settings', start_date:start, end_date:end,
    maint_type:type, reason:reason
  });
  fetch('maintenance_action.php',{method:'POST',body:data})
    .then(r=>r.json())
    .then(res=>{
      if(res.success){
        showToast('💾 تم حفظ إعدادات الصيانة بنجاح');
        addActivity('fa-gear','rgba(14,165,233,.15)','#38bdf8','حفظ إعدادات الصيانة','تم تحديث إعدادات وضع الصيانة');
      } else { showToast('❌ '+(res.message||'فشل الحفظ')); }
    })
    .catch(()=>showToast('❌ تعذّر الاتصال بالخادم'));
}

/* التفعيل/الإيقاف من خلال قسم الإعدادات مع التحقق الكامل */
function applyMaintFromSettings(enable){
  if(enable){
    const start  = (document.getElementById('maintStartDate')||{}).value || '';
    const end    = (document.getElementById('maintEndDate')||{}).value   || '';
    const type   = (document.getElementById('maintType')||{}).value      || '';
    const reason = ((document.getElementById('maintReason')||{}).value||'').trim();
    if(!start){ _flashField('maintStartDate','⚠️ يرجى تحديد تاريخ بداية الصيانة'); return; }
    if(!end)  { _flashField('maintEndDate','⚠️ يرجى تحديد تاريخ نهاية الصيانة');   return; }
    if(new Date(end)<=new Date(start)){ _flashField('maintEndDate','⚠️ تاريخ النهاية يجب أن يكون بعد تاريخ البداية'); return; }
    if(!type)  { _flashField('maintType','⚠️ يرجى اختيار نوع الصيانة');            return; }
    if(!reason){ _flashField('maintReason','⚠️ يرجى كتابة سبب الصيانة');           return; }

    const data = new URLSearchParams({
      action:'enable', start_date:start, end_date:end, maint_type:type, reason:reason
    });
    fetch('maintenance_action.php',{method:'POST',body:data})
      .then(r=>r.json())
      .then(res=>{
        if(res.success){
          toggleMaintenance(true);
          syncMaintSettingsUI(true);
          renderMaintLog();
        } else { showToast('❌ '+(res.message||'فشل التفعيل')); }
      })
      .catch(()=>showToast('❌ تعذّر الاتصال بالخادم'));
  } else {
    fetch('maintenance_action.php',{method:'POST',body:new URLSearchParams({action:'disable'})})
      .then(r=>r.json())
      .then(res=>{
        if(res.success){
          toggleMaintenance(false);
          syncMaintSettingsUI(false);
          renderMaintLog();
        } else { showToast('❌ '+(res.message||'فشل الإيقاف')); }
      })
      .catch(()=>showToast('❌ تعذّر الاتصال بالخادم'));
  }
}

/* تظليل الحقل الناقص وإظهار toast */
function _flashField(fieldId, msg){
  showToast(msg);
  const el = document.getElementById(fieldId);
  if(!el) return;
  const orig = el.style.borderColor;
  el.style.borderColor = '#f87171';
  el.style.boxShadow   = '0 0 0 3px rgba(248,113,113,.2)';
  el.focus();
  setTimeout(()=>{ el.style.borderColor = orig; el.style.boxShadow = ''; }, 2200);
}

// تعديل desc الصيانة عند الإيقاف ليتناسب مع الواجهة الجديدة
// (patch بعد DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function(){
  const desc2 = document.getElementById('maintStatusDesc');
  if(desc2 && !state.maintenanceOn){
    desc2.textContent = 'جميع الخدمات متاحة للمستخدمين. لتفعيل وضع الصيانة يرجى ملء إعدادات الصيانة أدناه ثم الضغط على زر التفعيل.';
  }
});


/* ══════════════════════════════════════
   MAINTENANCE MESSAGE + ACCESS — JS
══════════════════════════════════════ */
const MAINT_DEFAULT_MSG = 'منصة MedChifaGiz تخضع حالياً لأعمال صيانة وتحسين للخدمات الصحية الرقمية. نعتذر عن الإزعاج ونشكركم على تفهمكم.';

function updateMaintPreview(val){
  const el = document.getElementById('maintPreviewText');
  if(el) el.textContent = val.trim() || MAINT_DEFAULT_MSG;
}

function saveMaintMessage(){
  const val = (document.getElementById('maintUserMessage')||{}).value || '';
  if(!val.trim()){ showToast('⚠️ لا يمكن حفظ رسالة فارغة'); return; }
  fetch('maintenance_action.php',{method:'POST',body:new URLSearchParams({action:'save_message',message:val})})
    .then(r=>r.json())
    .then(res=>{
      if(res.success){
        showToast('✅ تم حفظ رسالة الصيانة');
        addActivity('fa-comment-medical','rgba(245,158,11,.15)','#fbbf24','تعديل رسالة الصيانة','تم تحديث رسالة المستخدمين أثناء الصيانة');
      } else { showToast('❌ '+(res.message||'فشل الحفظ')); }
    })
    .catch(()=>showToast('❌ تعذّر الاتصال بالخادم'));
}

function resetMaintMessage(){
  const ta = document.getElementById('maintUserMessage');
  if(ta){ ta.value = MAINT_DEFAULT_MSG; updateMaintPreview(MAINT_DEFAULT_MSG); }
  showToast('↩️ تم استعادة النص الافتراضي');
}

function toggleAccessItem(el, id){
  if(el.classList.contains('locked')) return;
  const isOn = el.classList.toggle('enabled');
  const statusEl = document.getElementById(id + 'Status');
  if(statusEl){
    statusEl.textContent = isOn ? 'مفعّل' : 'معطّل';
    statusEl.className = 'maint-access-status ' + (isOn ? 'on' : 'off');
  }
}

function saveAccessSettings(){
  const doctors     = document.getElementById('accessDoctors')?.classList.contains('enabled')    ? '1':'0';
  const patients    = document.getElementById('accessPatients')?.classList.contains('enabled')   ? '1':'0';
  const pharmacies  = document.getElementById('accessPharmacies')?.classList.contains('enabled') ? '1':'0';
  const labs        = document.getElementById('accessLabs')?.classList.contains('enabled')       ? '1':'0';
  const hospitals   = document.getElementById('accessHospitals')?.classList.contains('enabled')  ? '1':'0';
  const clinics     = document.getElementById('accessClinics')?.classList.contains('enabled')    ? '1':'0';
  const data = new URLSearchParams({
    action:'save_access',
    access_doctors:doctors, access_patients:patients,
    access_pharmacies:pharmacies, access_labs:labs,
    access_hospitals:hospitals, access_clinics:clinics
  });
  fetch('maintenance_action.php',{method:'POST',body:data})
    .then(r=>r.json())
    .then(res=>{
      if(res.success){
        showToast('✅ تم حفظ إعدادات الوصول بنجاح');
        addActivity('fa-users-gear','rgba(167,139,250,.15)','#a78bfa','تعديل صلاحيات الوصول','تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة');
      } else { showToast('❌ '+(res.message||'فشل الحفظ')); }
    })
    .catch(()=>showToast('❌ تعذّر الاتصال بالخادم'));
}

/* ══════════════════════════════════════
   MAINTENANCE LOG RENDER
══════════════════════════════════════ */
const _maintTypeLabels = {
  technical:'صيانة تقنية', system_update:'تحديث النظام',
  db_update:'تحديث قاعدة البيانات', security_update:'تحديث أمني', other:'أخرى'
};
const _maintTypeIcons = {
  technical:'fa-wrench', system_update:'fa-gear',
  db_update:'fa-database', security_update:'fa-lock', other:'fa-triangle-exclamation'
};

function renderMaintLog(logs){
  const tbody = document.getElementById('maintLogTbody');
  if(!tbody) return;
  if(!logs || !logs.length){
    tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted)"><i class="fas fa-rectangle-list" style="margin-left:6px"></i>لا توجد عمليات صيانة مسجّلة</td></tr>';
    return;
  }
  tbody.innerHTML = logs.map((r,i)=>{
    const num    = String(i+1).padStart(2,'0');
    const icon   = _maintTypeIcons[r.maint_type] || 'fa-gear';
    const label  = _maintTypeLabels[r.maint_type] || (r.maint_type||'—');
    const isOn   = !r.ended_at;
    const startD = r.started_at ? r.started_at.split(' ')[0] : '—';
    const startT = r.started_at ? r.started_at.split(' ')[1]?.slice(0,5) : '';
    const endD   = r.ended_at   ? r.ended_at.split(' ')[0]   : '—';
    const endT   = r.ended_at   ? r.ended_at.split(' ')[1]?.slice(0,5) : '';
    const av     = (r.admin_name||'?').charAt(0);
    const dur    = r.duration_min!=null ? r.duration_min+' دقيقة' : '—';
    const badge  = isOn
      ? `<span class="badge badge-pending"><i class="fas fa-hourglass-half" style="font-size:9px"></i> جارية</span>`
      : `<span class="badge badge-active"><i class="fas fa-circle-check" style="font-size:9px"></i> منتهية</span>`;
    return `<tr>
      <td style="color:var(--text-dim);font-size:12px">${num}</td>
      <td>
        <div style="font-size:13px;font-weight:600;color:var(--text)">${startD}</div>
        ${startT?`<div style="font-size:11px;color:var(--text-muted)">${startT}</div>`:''}
      </td>
      <td>
        ${r.ended_at
          ? `<div style="font-size:13px;font-weight:600;color:var(--text)">${endD}</div><div style="font-size:11px;color:var(--text-muted)">${endT}</div>`
          : `<span style="color:var(--text-dim);font-size:12px">—</span>`}
      </td>
      <td>
        <div class="maint-log-admin">
          <div class="maint-log-admin-av" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">${av}</div>
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--text)">${r.admin_name||'—'}</div>
            <div style="font-size:11px;color:var(--text-muted)">${r.admin_role||''}</div>
          </div>
        </div>
      </td>
      <td><span class="maint-log-type"><i class="fas ${icon}"></i> ${label}</span></td>
      <td><div class="maint-log-reason" title="${r.reason||''}">${r.reason||'—'}</div></td>
      <td>${badge}</td>
    </tr>`;
  }).join('');
}

function loadMaintState(){
  fetch('maintenance_action.php',{method:'POST',body:new URLSearchParams({action:'get_state'})})
    .then(r=>r.json())
    .then(res=>{
      if(!res.success) return;
      const s = res.state;
      // تحميل الحقول
      if(s.start_date){ const el=document.getElementById('maintStartDate'); if(el) el.value=s.start_date; }
      if(s.end_date)  { const el=document.getElementById('maintEndDate');   if(el) el.value=s.end_date; }
      if(s.maint_type){ const el=document.getElementById('maintType');      if(el) el.value=s.maint_type; }
      if(s.reason)    { const el=document.getElementById('maintReason');    if(el) el.value=s.reason; }
      // رسالة المستخدمين
      if(s.user_message){
        const ta=document.getElementById('maintUserMessage');
        if(ta){ ta.value=s.user_message; updateMaintPreview(s.user_message); }
      }
      // صلاحيات الوصول
      const accMap = {
        accessDoctors:'access_doctors',
        accessPatients:'access_patients',
        accessPharmacies:'access_pharmacies',
        accessLabs:'access_labs',
        accessHospitals:'access_hospitals',
        accessClinics:'access_clinics'
      };
      Object.entries(accMap).forEach(([elId,key])=>{
        if(s[key]==='1'){
          const el=document.getElementById(elId);
          if(el && !el.classList.contains('locked')){
            el.classList.add('enabled');
            const st=document.getElementById(elId+'Status');
            if(st){ st.textContent='مفعّل'; st.className='maint-access-status on'; }
          }
        }
      });
      // حالة الصيانة
      if(s.is_on==='1'){
        if(s.started_at){ _maintStartTime = new Date(s.started_at).getTime(); }
        toggleMaintenance(true);
        syncMaintSettingsUI(true);
      }
      // السجل
      renderMaintLog(res.logs);

      // تحديث خلية "آخر صيانة" و"آخر مسؤول" من آخر سجل منتهٍ
      if(res.logs && res.logs.length){
        const lastDone = res.logs.find(r => r.ended_at);
        if(lastDone){
          const ld = document.getElementById('maintCellLastDate');
          if(ld) ld.textContent = lastDone.started_at ? lastDone.started_at.split(' ')[0] : '—';
          const ldur = document.getElementById('maintCellLastDuration');
          if(ldur) ldur.textContent = lastDone.duration_min != null ? 'مدة الصيانة: ' + lastDone.duration_min + ' دقيقة' : '';
          const la = document.getElementById('maintCellLastAdmin');
          if(la) la.textContent = lastDone.admin_name || '—';
          const lar = document.getElementById('maintCellLastAdminRole');
          if(lar) lar.textContent = lastDone.admin_role || '';
        }
      }
    })
    .catch(()=>{});
}

// Hook into switchSection to init charts when stats page is opened
const _origSwitch = switchSection;
switchSection = function(id, el) {
  _origSwitch(id, el);
  if (id === 'stats') {
    // Load Chart.js lazily then init
    if (typeof Chart === 'undefined') {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
      s.onload = initStatsCharts;
      document.head.appendChild(s);
    } else {
      setTimeout(initStatsCharts, 50);
    }
  }
};
function toggleNotifications() {

    const dropdown = document.getElementById('notificationsDropdown');

    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
    }

}
</script>
</body>
</html>
