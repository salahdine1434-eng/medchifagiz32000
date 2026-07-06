<?php
/**
 * ai_file_organizer.php
 * ════════════════════════════════════════════════════════════════
 *  لوحة ميزة «تنظيم الملفات تلقائياً» — تُدمَج داخل dr_dashboard.php كقسم AI.
 *
 *  طريقة الدمج داخل الداشبورد (سطر واحد، مكان بقية لوحات .ai-content):
 *      <?php define('AFO_EMBEDDED', true); include __DIR__ . '/ai_file_organizer.php'; ?>
 *
 *  • عند التضمين: تُخرِج فقط لوحة .ai-content (الجلسة والترويسة موجودة في الداشبورد).
 *  • عند فتحها مباشرةً للاختبار: تعمل كصفحة كاملة (جلسة + CSS + JS) دون أي إعداد إضافي.
 *  تُبنى البيانات عبر ai_file_organizer.js وتتصل بـ ai_file_organizer_api.php.
 * ════════════════════════════════════════════════════════════════
 */

$afoStandalone = !defined('AFO_EMBEDDED');

if ($afoStandalone) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
        header('Location: login.php');
        exit;
    }
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>تنظيم الملفات تلقائياً — MedChifaGiz</title>'
       . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">'
       . '<link rel="stylesheet" href="ai_file_organizer.css"></head><body class="afo-standalone">';
}
?>
<?php if (!defined('AFO_EMBEDDED')): ?>
<div class="ai-content" id="fileOrganizer">
<?php endif; ?>
    <div class="content-header">
        <h2 data-ar="تنظيم الملفات تلقائياً"
            data-fr="Organisation automatique des dossiers"
            data-en="Automatic File Organization">تنظيم الملفات تلقائياً</h2>
        <?php if (!$afoStandalone): ?>
        <button onclick="closeAIContent()" type="button"><i class="fas fa-times"></i></button>
        <?php endif; ?>
    </div>

    <!-- نقطة تركيب الميزة: تُبنى البيانات عبر ai_file_organizer.js -->
    <div id="afo-root" data-endpoint="ai_file_organizer_api.php">

        <div class="afo-intro">
            <p class="afo-intro-text">
                تحليل ذكي للملفات الطبية باستخدام الذكاء الاصطناعي — تصنيف، أولوية، وملخّص لكل ملف.
            </p>
            <button id="afoOrganizeBtn" class="afo-btn-primary" type="button">
                <span class="afo-btn-icon">✦</span>
                <span class="afo-btn-label">تنظيم الملفات تلقائياً</span>
            </button>
        </div>

        <!-- شريط التقدّم أثناء التحليل -->
        <div id="afoProgress" class="afo-progress" hidden>
            <div class="afo-progress-info">
                <span id="afoProgressText">جارٍ التحليل…</span>
                <span id="afoProgressCount"></span>
            </div>
            <div class="afo-progress-bar"><div id="afoProgressFill" class="afo-progress-fill"></div></div>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="afo-stats">
            <div class="afo-stat afo-stat--organized">
                <div class="afo-stat-value" id="statOrganized">0</div>
                <div class="afo-stat-label">ملفات تم تنظيمها</div>
            </div>
            <div class="afo-stat afo-stat--high">
                <div class="afo-stat-value" id="statHigh">0</div>
                <div class="afo-stat-label">أولوية عالية</div>
            </div>
            <div class="afo-stat afo-stat--incomplete">
                <div class="afo-stat-value" id="statIncomplete">0</div>
                <div class="afo-stat-label">ملفات غير مكتملة</div>
            </div>
            <div class="afo-stat afo-stat--followup">
                <div class="afo-stat-value" id="statFollowup">0</div>
                <div class="afo-stat-label">تحتاج متابعة</div>
            </div>
        </div>

        <!-- رسائل الحالة -->
        <div id="afoNotice" class="afo-notice" hidden></div>

        <!-- قائمة الملفات -->
        <div id="afoFiles" class="afo-files">
            <div class="afo-empty" id="afoEmpty">
                <div class="afo-empty-icon">🗂️</div>
                <p>لم يتم تنظيم أي ملف بعد. اضغط «تنظيم الملفات تلقائياً» لبدء التحليل.</p>
            </div>
        </div>

    </div>
</div>
<?php if (!defined('AFO_EMBEDDED')): ?>
</div>
<?php endif; ?>
<?php
if ($afoStandalone) {
    echo '<script src="ai_file_organizer.js"></script></body></html>';
}
