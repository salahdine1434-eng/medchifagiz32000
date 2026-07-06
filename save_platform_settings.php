<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    // إنشاء الجدول إن لم يكن موجوداً
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS platform_settings (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'upload_logo') {
        // رفع الشعار
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'فشل رفع الملف']);
            exit;
        }

        $file     = $_FILES['logo'];
        $allowed  = ['image/png','image/jpeg','image/jpg','image/svg+xml'];
        $ext_map  = ['image/png'=>'png','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/svg+xml'=>'svg'];

        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'حجم الملف يتجاوز 2MB']);
            exit;
        }

        $dir = __DIR__ . '/uploads/logos/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = $ext_map[$file['type']];
        $filename = 'platform_logo_' . time() . '.' . $ext;
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'فشل حفظ الملف']);
            exit;
        }

        $path = 'uploads/logos/' . $filename;

        $stmt = $pdo->prepare("
            INSERT INTO platform_settings (`key`, `value`)
            VALUES ('logo_path', ?)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ");
        $stmt->execute([$path]);

        echo json_encode(['success' => true, 'logo_path' => $path]);
        exit;
    }

    // حفظ البيانات العامة
    $fields = [
        'platform_name' => $_POST['platform_name'] ?? '',
        'email'         => $_POST['email'] ?? '',
        'phone'         => $_POST['phone'] ?? '',
        'website'       => $_POST['website'] ?? '',
        'policy'        => $_POST['policy'] ?? '',
    ];

    $stmt = $pdo->prepare("
        INSERT INTO platform_settings (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");

    foreach ($fields as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
