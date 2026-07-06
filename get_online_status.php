<?php
/* ================================================================
   get_online_status.php
   يُرجِع حالة الاتصال (online) لمستخدم أو عدة مستخدمين اعتماداً على
   last_seen: متصل إذا كان آخر ظهور خلال آخر 90 ثانية.
   الاستعمال:
     get_online_status.php?user_id=80          => {"80": true}
     get_online_status.php?user_ids=80,188,50  => {"80":true,"188":false,...}
================================================================ */
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

/* جمع المعرّفات المطلوبة من user_id أو user_ids */
$ids = [];
if (isset($_GET['user_ids'])) {
    foreach (explode(',', $_GET['user_ids']) as $x) {
        $x = intval(trim($x));
        if ($x > 0) $ids[] = $x;
    }
} elseif (isset($_GET['user_id'])) {
    $x = intval($_GET['user_id']);
    if ($x > 0) $ids[] = $x;
}

$ids = array_values(array_unique($ids));

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

/* عتبة الاتصال: 90 ثانية */
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id,
               (last_seen IS NOT NULL AND last_seen >= (NOW() - INTERVAL 90 SECOND)) AS online
        FROM users
        WHERE id IN ($placeholders)";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

$out = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $out[(string)$r['id']] = (bool) intval($r['online']);
}

echo json_encode($out);
