<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

try {
    $stmt = $pdo->query("
        SELECT
            icon,
            bg,
            color,
            title,
            description AS `desc`,
            user_name AS user,
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS time
        FROM activity_logs
        ORDER BY created_at DESC
        LIMIT 50
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([]);
}
