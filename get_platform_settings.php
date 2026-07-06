<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    $rows = $pdo->query("SELECT `key`, `value` FROM platform_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode($rows ?: new stdClass());
} catch (Exception $e) {
    echo json_encode(new stdClass());
}
