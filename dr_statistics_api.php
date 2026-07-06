<?php
/* ================================================================
   MedChifaGiz — dr_statistics_api.php
   نقطة نهاية مستقلة (AJAX) تُرجع بيانات الإحصائيات بصيغة JSON.
   - PHP + MySQL (PDO) + Prepared Statements
   - كل البيانات حقيقية من جدول medical_records
   - لا تلمس أي ملف أو منطق آخر في النظام
   ضع هذا الملف في نفس مجلد dr_dashboard.php
================================================================ */

session_start();
header('Content-Type: application/json; charset=utf-8');

/* ── التحقق من الجلسة (نفس منطق dr_dashboard.php) ── */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

require 'db.php'; // يوفّر $pdo (PDO)

try {
    /* ── جلب معرّف الطبيب (نفس منطق لوحة التحكم) ── */
    if (isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1) {
        $docId = (int) $_SESSION['staff_id'];
    } else {
        $stmtDoc = $pdo->prepare("
            SELECT doctors.id
            FROM doctors
            JOIN users ON doctors.user_id = users.id
            WHERE doctors.user_id = ?
        ");
        $stmtDoc->execute([$_SESSION['user_id']]);
        $docId = (int) $stmtDoc->fetchColumn();
    }

    if (!$docId) {
        echo json_encode(['ok' => false, 'error' => 'doctor_not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ════════════════════════════════════════════════
       القسم 1 — إحصائيات الملفات الطبية (4 قيم)
    ════════════════════════════════════════════════ */
    // إجمالي الملفات الطبية
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE doctor_id = ?");
    $stmt->execute([$docId]);
    $totalFiles = (int) $stmt->fetchColumn();

    // الملفات المنشأة هذا الأسبوع (أسبوع يبدأ الأحد — mode 0)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM medical_records
        WHERE doctor_id = ?
          AND YEARWEEK(created_at, 0) = YEARWEEK(CURDATE(), 0)
    ");
    $stmt->execute([$docId]);
    $createdThisWeek = (int) $stmt->fetchColumn();

    // الملفات المحدّثة هذا الشهر
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM medical_records
        WHERE doctor_id = ?
          AND YEAR(updated_at)  = YEAR(CURDATE())
          AND MONTH(updated_at) = MONTH(CURDATE())
    ");
    $stmt->execute([$docId]);
    $updatedThisMonth = (int) $stmt->fetchColumn();

    // الملفات التي تمت معاينتها اليوم
    // ملاحظة: لا يوجد عمود مخصص للمعاينة، نعتمد updated_at = اليوم كمؤشر
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM medical_records
        WHERE doctor_id = ?
          AND DATE(updated_at) = CURDATE()
    ");
    $stmt->execute([$docId]);
    $reviewedToday = (int) $stmt->fetchColumn();

    /* ════════════════════════════════════════════════
       القسم 2 — نشاط الطبيب الأسبوعي (الأحد → السبت)
       عدد الملفات (المعاينات) المُنشأة في كل يوم من الأسبوع الحالي
    ════════════════════════════════════════════════ */
    $stmt = $pdo->prepare("
        SELECT DAYOFWEEK(created_at) AS dow, COUNT(*) AS cnt
        FROM medical_records
        WHERE doctor_id = ?
          AND YEARWEEK(created_at, 0) = YEARWEEK(CURDATE(), 0)
        GROUP BY DAYOFWEEK(created_at)
    ");
    $stmt->execute([$docId]);
    // DAYOFWEEK: 1=الأحد ... 7=السبت
    $dow = array_fill(1, 7, 0);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dow[(int) $row['dow']] = (int) $row['cnt'];
    }
    $weekly = [$dow[1], $dow[2], $dow[3], $dow[4], $dow[5], $dow[6], $dow[7]];

    /* ════════════════════════════════════════════════
       القسم 3 — المرضى المقيمون / غير المقيمين
    ════════════════════════════════════════════════ */
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN residency_status = 'مقيم'      THEN 1 ELSE 0 END) AS resident,
            SUM(CASE WHEN residency_status = 'غير مقيم'  THEN 1 ELSE 0 END) AS non_resident
        FROM medical_records
        WHERE doctor_id = ?
    ");
    $stmt->execute([$docId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $resAll = [(int) $r['resident'], (int) $r['non_resident']];

    /* ════════════════════════════════════════════════
       القسم 4 — الرجال المقيمون / غير المقيمين
    ════════════════════════════════════════════════ */
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN residency_status = 'مقيم'      THEN 1 ELSE 0 END) AS resident,
            SUM(CASE WHEN residency_status = 'غير مقيم'  THEN 1 ELSE 0 END) AS non_resident
        FROM medical_records
        WHERE doctor_id = ? AND gender = 'ذكر'
    ");
    $stmt->execute([$docId]);
    $rm = $stmt->fetch(PDO::FETCH_ASSOC);
    $resMen = [(int) $rm['resident'], (int) $rm['non_resident']];

    /* ════════════════════════════════════════════════
       القسم 5 — النساء المقيمات / غير المقيمات
    ════════════════════════════════════════════════ */
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN residency_status = 'مقيم'      THEN 1 ELSE 0 END) AS resident,
            SUM(CASE WHEN residency_status = 'غير مقيم'  THEN 1 ELSE 0 END) AS non_resident
        FROM medical_records
        WHERE doctor_id = ? AND gender = 'أنثى'
    ");
    $stmt->execute([$docId]);
    $rw = $stmt->fetch(PDO::FETCH_ASSOC);
    $resWomen = [(int) $rw['resident'], (int) $rw['non_resident']];

    /* ── الإخراج النهائي ── */
    echo json_encode([
        'ok' => true,
        'files' => [
            'total'         => $totalFiles,
            'created_week'  => $createdThisWeek,
            'updated_month' => $updatedThisMonth,
            'reviewed_today'=> $reviewedToday,
        ],
        'weekly'    => $weekly,        // [الأحد..السبت]
        'residency' => $resAll,        // [مقيم, غير مقيم]
        'men'       => $resMen,        // [مقيم, غير مقيم]
        'women'     => $resWomen,      // [مقيمة, غير مقيمة]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE);
    exit;
}
