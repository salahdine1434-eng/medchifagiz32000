<?php

require_once 'db.php';

function createSuperAdminNotification(
    $title,
    $message,
    $type = 'info',
    $relatedId = null,
    $relatedType = null
) {
    global $pdo;

    try {

        // جلب جميع حسابات Super Admin
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE role = 'super_admin'
        ");

        $stmt->execute();

        $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // إرسال الإشعار لكل Super Admin
        foreach ($superAdmins as $admin) {

            $insert = $pdo->prepare("
                INSERT INTO super_admin_notifications (
                    super_admin_id,
                    title,
                    message,
                    type,
                    related_id,
                    related_type
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $admin['id'],
                $title,
                $message,
                $type,
                $relatedId,
                $relatedType
            ]);
        }

    } catch (Exception $e) {

        error_log(
            'Super Admin Notification Error: ' .
            $e->getMessage()
        );

    }
}