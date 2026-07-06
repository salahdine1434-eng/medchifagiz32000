<?php
/**
 * get_admin_notifications.php
 * جلب إشعارات لوحة الأدمن ديناميكياً (AJAX / polling)
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'notifications' => [], 'total' => 0]);
    exit;
}

/* ── helpers ── */
function mcg_count_api(PDO $pdo, string $sql): int {
    try {
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        error_log('[MCG admin notif] ' . $e->getMessage());
        return 0;
    }
}
function mcg_columns_api(PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table)
        );
        return array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) { return []; }
}
function mcg_pick_api(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (in_array(strtolower($c), $cols, true)) return $c;
    }
    return null;
}

$notifications = [];

/* 1) حسابات معطّلة */
$nInactive = mcg_count_api($pdo,
    "SELECT COUNT(*) FROM clinic_staff WHERE account_status = 'inactive'"
);
if ($nInactive > 0) {
    $notifications[] = ['icon' => '🟡', 'class' => 'warn',
        'text' => 'يوجد ' . $nInactive . ' حساب/حسابات معطّلة'];
}

/* 2) مصالح بدون مسؤول */
$nNoAdmin = mcg_count_api($pdo,
    "SELECT COUNT(*) FROM services s
     WHERE NOT EXISTS (
         SELECT 1 FROM clinic_staff cs
         WHERE cs.service_id = s.id AND cs.role = 'service_admin'
     )"
);
if ($nNoAdmin > 0) {
    $notifications[] = ['icon' => '🔵', 'class' => 'info',
        'text' => 'يوجد ' . $nNoAdmin . ' مصلحة/مصالح بدون مسؤول'];
}

$staffCols = mcg_columns_api($pdo, 'clinic_staff');
$hasRole   = in_array('role', $staffCols, true);

/* 3) موظفون لم يسجّلوا الدخول منذ أكثر من 7 أيام */
$loginCol = mcg_pick_api($staffCols,
    ['last_login','last_login_at','lastlogin','last_seen','last_activity']);
if ($loginCol !== null) {
    $nIdle = mcg_count_api($pdo,
        "SELECT COUNT(*) FROM clinic_staff
         WHERE `$loginCol` IS NOT NULL
           AND `$loginCol` < (NOW() - INTERVAL 7 DAY)"
    );
    if ($nIdle > 0) {
        $notifications[] = ['icon' => '🟠', 'class' => 'warn',
            'text' => 'يوجد ' . $nIdle . ' موظف/موظفين لم يسجّلوا الدخول منذ أكثر من 7 أيام'];
    }
}

/* 4) أطباء بدون تخصص */
$specCol = mcg_pick_api($staffCols,
    ['specialty','speciality','specialite','specialization','specialty_name']);
if ($specCol !== null && $hasRole) {
    $nNoSpec = mcg_count_api($pdo,
        "SELECT COUNT(*) FROM clinic_staff
         WHERE role = 'doctor'
           AND (`$specCol` IS NULL OR TRIM(`$specCol`) = '')"
    );
    if ($nNoSpec > 0) {
        $notifications[] = ['icon' => '🔴', 'class' => 'danger',
            'text' => 'يوجد ' . $nNoSpec . ' طبيب بدون تخصص'];
    }
}

echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'total'         => count($notifications),
], JSON_UNESCAPED_UNICODE);
