<?php
/**
 * maintenance_check.php
 * أضف هذا السطر في بداية أي صفحة يجب حمايتها أثناء الصيانة:
 *   require_once 'maintenance_check.php';
 *
 * Super Admin و Admin و Moderator يدخلون دائماً.
 */

// لا نشغّل هذا الملف مباشرةً
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) { exit; }

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    if (!isset($pdo)) require_once __DIR__ . '/db.php';

    $isOn = false;
    $stmt = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`='is_on'");
    $stmt->execute();
    $row = $stmt->fetchColumn();
    $isOn = ($row === '1');

    if (!$isOn) return; // المنصة شغّالة — لا شيء

    $role = $_SESSION['role'] ?? '';

    // دائماً مسموح
    if (in_array($role, ['super_admin','admin','moderator'])) return;

    // تحقق من الأدوار القابلة للإعداد
    $allowed = [];
    $accessStmt = $pdo->query("
        SELECT `key`,`value` FROM maintenance_settings
        WHERE `key` IN ('access_doctors','access_patients','access_pharmacies','access_labs','access_hospitals','access_clinics')
    ");
    foreach($accessStmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k=>$v){
        $allowed[$k] = ($v === '1');
    }

    $blocked = false;
    if ($role === 'doctor'   && empty($allowed['access_doctors']))    $blocked = true;
    if ($role === 'patient'  && empty($allowed['access_patients']))   $blocked = true;
    if ($role === 'pharmacy' && empty($allowed['access_pharmacies'])) $blocked = true;
    if ($role === 'lab'      && empty($allowed['access_labs']))       $blocked = true;
    if ($role === 'hospital' && empty($allowed['access_hospitals']))  $blocked = true;
    if ($role === 'clinic'   && empty($allowed['access_clinics']))    $blocked = true;
    if ($role === '' || !in_array($role,['doctor','patient','pharmacy','lab','hospital','clinic'])) $blocked = true;

    if ($blocked) {
        $msgRow = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`='user_message'");
        $msgRow->execute();
        $msg = $msgRow->fetchColumn() ?: 'منصة MedChifaGiz تخضع حالياً لأعمال صيانة. نعتذر عن الإزعاج.';
        $msg = htmlspecialchars($msg, ENT_QUOTES);
        http_response_code(503);
        header('Retry-After: 3600');
        echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MedChifaGiz — وضع الصيانة</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;
     background:#0f172a;font-family:Tahoma,Arial,sans-serif;color:#e2e8f0}
.card{background:#1e293b;border:1px solid rgba(255,255,255,.08);border-radius:20px;
      padding:48px 40px;max-width:520px;width:90%;text-align:center}
.icon{font-size:48px;margin-bottom:20px}
h1{font-size:22px;font-weight:700;margin-bottom:12px;color:#f1f5f9}
h1 em{color:#0ea5e9;font-style:normal}
p{font-size:14px;color:#94a3b8;line-height:1.7;margin-bottom:20px}
.badge{display:inline-flex;align-items:center;gap:8px;background:rgba(239,68,68,.1);
       border:1px solid rgba(239,68,68,.3);color:#f87171;padding:6px 16px;
       border-radius:99px;font-size:13px}
.dot{width:8px;height:8px;border-radius:50%;background:#f87171;
     animation:pulse 1.4s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
</style>
</head>
<body>
<div class="card">
  <div class="icon">🔧</div>
  <h1><em>MedChifa</em>Giz — وضع الصيانة</h1>
  <p>$msg</p>
  <div class="badge"><span class="dot"></span>قيد الصيانة — يُرجى العودة لاحقاً</div>
</div>
</body>
</html>
HTML;
        exit;
    }

} catch (Exception $e) {
    // في حالة خطأ DB لا نوقف المنصة
}
