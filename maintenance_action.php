<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// حماية: فقط super_admin و admin
if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['super_admin','admin','moderator'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح']);
    exit;
}

$action      = $_POST['action'] ?? '';
$adminName   = $_SESSION['name']  ?? 'Super Admin';
$adminRole   = $_SESSION['role']  ?? 'super_admin';
$adminId     = $_SESSION['user_id'] ?? 0;

// إنشاء الجداول إن لم تكن موجودة
$pdo->exec("
    CREATE TABLE IF NOT EXISTS maintenance_settings (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS maintenance_log (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        admin_id      INT,
        admin_name    VARCHAR(200),
        admin_role    VARCHAR(100),
        action_type   ENUM('enable','disable') NOT NULL,
        maint_type    VARCHAR(100),
        reason        TEXT,
        started_at    DATETIME,
        ended_at      DATETIME,
        duration_min  INT DEFAULT NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ─── دالة مساعدة: حفظ/قراءة إعداد ───
function setSetting(PDO $pdo, string $key, string $value): void {
    $s = $pdo->prepare("
        INSERT INTO maintenance_settings (`key`,`value`)
        VALUES (?,?)
        ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)
    ");
    $s->execute([$key,$value]);
}

function getSetting(PDO $pdo, string $key, string $default=''): string {
    try {
        $s = $pdo->prepare("SELECT `value` FROM maintenance_settings WHERE `key`=?");
        $s->execute([$key]);
        $r = $s->fetchColumn();
        return $r !== false ? $r : $default;
    } catch(Exception $e){ return $default; }
}

// ══════════════════════════════════════
switch ($action) {

    // ── جلب كامل حالة الصيانة ──
    case 'get_state':
        $keys = ['is_on','started_at','maint_type','reason',
                 'start_date','end_date','user_message',
                 'access_doctors','access_patients',
                 'access_pharmacies','access_labs','access_hospitals','access_clinics'];
        $result = [];
        foreach($keys as $k) $result[$k] = getSetting($pdo,$k,'');

        // سجل العمليات
        $logs = $pdo->query("
            SELECT id, admin_name, admin_role, action_type, maint_type,
                   reason, started_at, ended_at, duration_min, created_at
            FROM maintenance_log
            ORDER BY id DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success'=>true,'state'=>$result,'logs'=>$logs]);
        break;

    // ── حفظ إعدادات الصيانة فقط ──
    case 'save_settings':
        setSetting($pdo,'start_date',   $_POST['start_date']   ?? '');
        setSetting($pdo,'end_date',     $_POST['end_date']     ?? '');
        setSetting($pdo,'maint_type',   $_POST['maint_type']   ?? '');
        setSetting($pdo,'reason',       $_POST['reason']       ?? '');
        // سجل العملية فقط إذا كانت الصيانة غير مفعّلة (حفظ مسودة)
        if(getSetting($pdo,'is_on','0') !== '1'){
            $type   = $_POST['maint_type'] ?? '';
            $reason = $_POST['reason']     ?? '';
            $now    = date('Y-m-d H:i:s');
            $s = $pdo->prepare("
                INSERT INTO maintenance_log
                    (admin_id,admin_name,admin_role,action_type,maint_type,reason,started_at)
                VALUES (?,?,?,'enable',?,?,?)
            ");
            $s->execute([$adminId,$adminName,$adminRole,$type,$reason,$now]);
            // أوقفها فوراً كسجل حفظ إعدادات
            $lastId = $pdo->lastInsertId();
            $u = $pdo->prepare("UPDATE maintenance_log SET ended_at=?, duration_min=0, action_type='disable' WHERE id=?");
            $u->execute([$now,$lastId]);
        }
        echo json_encode(['success'=>true]);
        break;

    // ── تفعيل وضع الصيانة ──
    case 'enable':
        $startDate = $_POST['start_date'] ?? date('Y-m-d\TH:i');
        $endDate   = $_POST['end_date']   ?? '';
        $type      = $_POST['maint_type'] ?? '';
        $reason    = $_POST['reason']     ?? '';
        $now       = date('Y-m-d H:i:s');

        setSetting($pdo,'is_on',     '1');
        setSetting($pdo,'started_at',$now);
        setSetting($pdo,'maint_type',$type);
        setSetting($pdo,'reason',    $reason);
        setSetting($pdo,'start_date',$startDate);
        setSetting($pdo,'end_date',  $endDate);

        // سجل العملية
        $s = $pdo->prepare("
            INSERT INTO maintenance_log
                (admin_id,admin_name,admin_role,action_type,maint_type,reason,started_at)
            VALUES (?,?,?,'enable',?,?,?)
        ");
        $s->execute([$adminId,$adminName,$adminRole,$type,$reason,$now]);

        echo json_encode(['success'=>true,'started_at'=>$now]);
        break;

    // ── إيقاف وضع الصيانة ──
    case 'disable':
        $now       = date('Y-m-d H:i:s');
        $startedAt = getSetting($pdo,'started_at','');

        $durationMin = null;
        if($startedAt){
            $diff = strtotime($now) - strtotime($startedAt);
            $durationMin = max(0, (int)round($diff/60));
        }

        setSetting($pdo,'is_on',    '0');
        setSetting($pdo,'ended_at', $now);

        // تحديث آخر سجل enable بتاريخ الإيقاف
        $lastLog = $pdo->query("
            SELECT id FROM maintenance_log
            WHERE action_type='enable' AND ended_at IS NULL
            ORDER BY id DESC LIMIT 1
        ")->fetchColumn();

        if($lastLog){
            $u = $pdo->prepare("
                UPDATE maintenance_log
                SET ended_at=?, duration_min=?, action_type='disable'
                WHERE id=?
            ");
            $u->execute([$now,$durationMin,$lastLog]);
        } else {
            // أدخل سجلاً جديداً
            $type   = getSetting($pdo,'maint_type','');
            $reason = getSetting($pdo,'reason','');
            $s = $pdo->prepare("
                INSERT INTO maintenance_log
                    (admin_id,admin_name,admin_role,action_type,maint_type,reason,started_at,ended_at,duration_min)
                VALUES (?,?,?,'disable',?,?,?,?,?)
            ");
            $s->execute([$adminId,$adminName,$adminRole,$type,$reason,$startedAt,$now,$durationMin]);
        }

        echo json_encode(['success'=>true,'ended_at'=>$now,'duration_min'=>$durationMin]);
        break;

    // ── حفظ رسالة المستخدمين ──
    case 'save_message':
        $msg = trim($_POST['message'] ?? '');
        if($msg === ''){ echo json_encode(['success'=>false,'message'=>'رسالة فارغة']); break; }
        setSetting($pdo,'user_message',$msg);
        // سجل تعديل الرسالة
        $now  = date('Y-m-d H:i:s');
        $type = getSetting($pdo,'maint_type','');
        $s = $pdo->prepare("
            INSERT INTO maintenance_log
                (admin_id,admin_name,admin_role,action_type,maint_type,reason,started_at,ended_at,duration_min)
            VALUES (?,?,?,'disable',?,?,?,?,0)
        ");
        $s->execute([$adminId,$adminName,$adminRole,$type,'تعديل رسالة المستخدمين أثناء الصيانة',$now,$now]);
        echo json_encode(['success'=>true]);
        break;

    // ── حفظ إعدادات الوصول ──
    case 'save_access':
        setSetting($pdo,'access_doctors',    $_POST['access_doctors']    ?? '0');
        setSetting($pdo,'access_patients',   $_POST['access_patients']   ?? '0');
        setSetting($pdo,'access_pharmacies', $_POST['access_pharmacies'] ?? '0');
        setSetting($pdo,'access_labs',       $_POST['access_labs']       ?? '0');
        setSetting($pdo,'access_hospitals',  $_POST['access_hospitals']  ?? '0');
        setSetting($pdo,'access_clinics',    $_POST['access_clinics']    ?? '0');
        // سجل تعديل الصلاحيات
        $now  = date('Y-m-d H:i:s');
        $type = getSetting($pdo,'maint_type','');
        $s = $pdo->prepare("
            INSERT INTO maintenance_log
                (admin_id,admin_name,admin_role,action_type,maint_type,reason,started_at,ended_at,duration_min)
            VALUES (?,?,?,'disable',?,?,?,?,0)
        ");
        $s->execute([$adminId,$adminName,$adminRole,$type,'تعديل صلاحيات الوصول أثناء الصيانة',$now,$now]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'action غير معروف']);
}
