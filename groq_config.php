<?php
/**
 * groq_config.php
 * ════════════════════════════════════════════════════════════════
 *  إعدادات خدمة Groq الخاصة بميزة «توليد التقارير الطبية بالذكاء الاصطناعي».
 *  هذا الملف مستقل تماماً ولا يؤثر على أي جزء آخر من مشروع MedChifaGiz.
 *
 *  أين أضع مفتاح Groq؟
 *   1) (المفضّل) عبر متغيّر بيئة على الخادم:   GROQ_API_KEY=gsk_xxxxxxxx
 *   2) أو ضعه مباشرة في الثابت GROQ_API_KEY_FALLBACK بالأسفل.
 *
 *  كيف أغيّر النموذج أو جودة التوليد لاحقاً؟
 *   عدّل الثوابت GROQ_MODEL / GROQ_TEMPERATURE / GROQ_MAX_TOKENS فقط.
 *   نماذج إنتاجية حالية لدى Groq:
 *     - llama-3.3-70b-versatile   (الأفضل للجودة — مُستخدم افتراضياً)
 *     - llama-3.1-8b-instant      (الأسرع، جودة أقل)
 * ════════════════════════════════════════════════════════════════
 */

if (!defined('GROQ_CONFIG_LOADED')) {
    define('GROQ_CONFIG_LOADED', true);

    /* ── المفتاح الاحتياطي (إن لم تستعمل متغيّر البيئة) ── */
    if (!defined('GROQ_API_KEY_FALLBACK')) {
        define('GROQ_API_KEY_FALLBACK', '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');   // ← ضع مفتاح Groq هنا إذا لزم
    }

    /* ── نقطة النهاية (متوافقة مع واجهة OpenAI) ── */
    define('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');

    /* ── النموذج وإعدادات التوليد ── */
    define('GROQ_MODEL',        'llama-3.3-70b-versatile');
    define('GROQ_TEMPERATURE',  0.25);   // منخفضة لتقليل «الهلوسة» والالتزام بالحقائق
    define('GROQ_MAX_TOKENS',   2600);
    define('GROQ_TIMEOUT_SEC',  60);
}

/**
 * إرجاع مفتاح Groq الفعّال: متغيّر البيئة أولاً ثم القيمة الاحتياطية.
 */
function groq_api_key(): string
{
    $env = getenv('GROQ_API_KEY');
    if ($env !== false && trim($env) !== '') {
        return trim($env);
    }
    return trim(GROQ_API_KEY_FALLBACK);
}
