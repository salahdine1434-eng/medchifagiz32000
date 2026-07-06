<?php
$host = " https://php-myadmin.net/index.php";
$dbname = "icei_42351031_rafik22";
$user = "icei_42351031";
$pass = "LEtRSvD61KBq";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // تسجيل الخطأ الحقيقي في سجل الخادم دون كشفه للمستخدم
    error_log("Database connection failed: " . $e->getMessage());
    // إرجاع JSON نظيف بدل نص/HTML يكسر JSON.parse
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(
        ['success' => false, 'message' => 'تعذّر الاتصال بقاعدة البيانات'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
