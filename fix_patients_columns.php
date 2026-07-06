<?php
/**
 * fix_patients_columns.php — MedChifaGiz
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * شغّل هذا الملف مرة واحدة فقط من المتصفح:
 *   http://yoursite.com/fix_patients_columns.php
 *
 * يضيف عمودَي admission_date و residency_status إلى جدول patients
 * إذا لم يكونا موجودَين — بدون حذف أو تعديل أي شيء آخر.
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 */

session_start();

// حماية: فقط الأدمن أو من لديه session
if (!isset($_SESSION['user_id'])) {
    die('<p style="color:red;font-family:Arial;">❌ يجب تسجيل الدخول أولاً</p>');
}

require 'db.php';

$results = [];

// ── 1. عمود admission_date ──────────────────────────────────
try {
    $check = $pdo->query("SHOW COLUMNS FROM patients LIKE 'admission_date'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN admission_date DATE NULL DEFAULT NULL AFTER phone");
        $results[] = ['col' => 'admission_date', 'status' => '✅ تمت الإضافة بنجاح'];
    } else {
        $results[] = ['col' => 'admission_date', 'status' => 'ℹ️ موجود مسبقاً — لم يُعدَّل'];
    }
} catch (PDOException $e) {
    $results[] = ['col' => 'admission_date', 'status' => '❌ خطأ: ' . $e->getMessage()];
}

// ── 2. عمود residency_status ───────────────────────────────
try {
    $check = $pdo->query("SHOW COLUMNS FROM patients LIKE 'residency_status'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN residency_status VARCHAR(50) NULL DEFAULT NULL AFTER admission_date");
        $results[] = ['col' => 'residency_status', 'status' => '✅ تمت الإضافة بنجاح'];
    } else {
        $results[] = ['col' => 'residency_status', 'status' => 'ℹ️ موجود مسبقاً — لم يُعدَّل'];
    }
} catch (PDOException $e) {
    $results[] = ['col' => 'residency_status', 'status' => '❌ خطأ: ' . $e->getMessage()];
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إصلاح جدول patients — MedChifaGiz</title>
<style>
  body { font-family: 'Cairo', Arial, sans-serif; padding: 40px; background: #f8fafc; color: #0f172a; }
  h2   { color: #0ea5e9; margin-bottom: 20px; }
  .box { background: #fff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); max-width: 540px; }
  .row { padding: 10px 0; border-bottom: 1px solid #e2e8f0; display: flex; gap: 12px; align-items: center; }
  .row:last-child { border-bottom: none; }
  .col { font-weight: 700; color: #334155; min-width: 160px; }
  .ok  { color: #10b981; }
  .info{ color: #64748b; }
  .err { color: #ef4444; }
  .note { margin-top: 20px; font-size: .82rem; color: #94a3b8; background: #f1f5f9; padding: 10px 14px; border-radius: 8px; }
  a    { color: #0ea5e9; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
<div class="box">
  <h2><i>🔧</i> إصلاح جدول patients</h2>

  <?php foreach ($results as $r): ?>
  <div class="row">
    <span class="col"><?= htmlspecialchars($r['col']) ?></span>
    <span><?= $r['status'] ?></span>
  </div>
  <?php endforeach; ?>

  <div class="note">
    ✅ اكتمل — يمكنك الآن حذف هذا الملف من السيرفر.<br>
    <a href="dr_dashboard.php">← العودة إلى لوحة التحكم</a>
  </div>
</div>
</body>
</html>
