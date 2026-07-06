<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'فشل رفع الملف']);
    exit;
}

$file    = $_FILES['profile_picture'];
$allowed = ['image/png','image/jpeg','image/jpg','image/gif','image/webp'];
$extMap  = ['image/png'=>'png','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'];

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'حجم الملف يتجاوز 2MB']);
    exit;
}

$dir = __DIR__ . '/uploads/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$ext      = $extMap[$file['type']];
$filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$dest     = $dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ الملف']);
    exit;
}

$path = 'uploads/avatars/' . $filename;

try {
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute([$path, $_SESSION['user_id']]);
    // تحديث الجلسة
    $_SESSION['profile_picture'] = $path;
    echo json_encode(['success' => true, 'picture_path' => $path]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
