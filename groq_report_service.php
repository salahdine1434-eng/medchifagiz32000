<?php
/**
 * groq_report_service.php
 * ════════════════════════════════════════════════════════════════
 *  طبقة النقل (Transport Layer) للاتصال بـ Groq API.
 *  المسؤولية الوحيدة: إرسال الرسائل واستلام النص الناتج.
 *  لا تعرف شيئاً عن قاعدة البيانات ولا عن واجهة المستخدم → كود نظيف وقابل للاختبار.
 * ════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/groq_config.php';

class GroqReportService
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey   = $apiKey ?? groq_api_key();
        $this->endpoint = GROQ_API_ENDPOINT;
        $this->model    = $model  ?? GROQ_MODEL;
    }

    /** هل تم ضبط المفتاح؟ */
    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function modelName(): string
    {
        return $this->model;
    }

    /**
     * تنفيذ طلب إكمال محادثة على Groq.
     *
     * @param array $messages رسائل بنمط OpenAI: [['role'=>'system'|'user','content'=>'...'], ...]
     * @return array{success:bool, content:?string, message:?string}
     */
    public function complete(array $messages): array
    {
        if (!$this->isConfigured()) {
            return $this->fail('مفتاح Groq غير مُعدّ. يرجى ضبط GROQ_API_KEY في إعدادات الخادم.');
        }

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => GROQ_TEMPERATURE,
            'max_tokens'  => GROQ_MAX_TOKENS,
            'top_p'       => 0.9,
            'stream'      => false,
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => GROQ_TIMEOUT_SEC,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $raw    = curl_exec($ch);
        $errNo  = curl_errno($ch);
        $errMsg = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /* ── خطأ شبكة / مهلة ── */
        if ($errNo !== 0) {
            error_log("Groq cURL error ($errNo): $errMsg");
            return $this->fail('تعذّر الاتصال بخدمة الذكاء الاصطناعي. تحقّق من اتصال الخادم بالإنترنت.');
        }

        $data = json_decode($raw, true);

        /* ── خطأ من واجهة Groq ── */
        if ($status < 200 || $status >= 300) {
            $apiMsg = $data['error']['message'] ?? ('HTTP ' . $status);
            error_log("Groq API error [$status]: $apiMsg | body: " . substr((string)$raw, 0, 500));

            // رسائل ودّية للحالات الشائعة دون كشف تفاصيل حساسة.
            if ($status === 401) {
                return $this->fail('مفتاح Groq غير صالح أو منتهي الصلاحية.');
            }
            if ($status === 429) {
                return $this->fail('تم تجاوز حدّ الاستخدام مؤقتاً. حاول بعد قليل.');
            }
            return $this->fail('فشل توليد التقرير من خدمة الذكاء الاصطناعي.');
        }

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return $this->fail('لم يُستلَم أي محتوى من النموذج.');
        }

        return ['success' => true, 'content' => trim($content), 'message' => null];
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'content' => null, 'message' => $msg];
    }
}
