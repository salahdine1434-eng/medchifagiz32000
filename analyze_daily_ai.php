<?php
/**
 * MedChifaGiz — analyze_daily_ai.php
 * AI-powered health journal analysis using Groq API
 * Model: llama-3.3-70b-versatile
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'not_logged_in']);
    exit();
}

// ── Load API key from config ──────────────────────────────────
$envFile = __DIR__ . '/config/.env';
$groqKey = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            if (trim($k) === 'GROQ_API_KEY') {
                $groqKey = trim($v);
                break;
            }
        }
    }
}

// Fallback: environment variable
if (!$groqKey) {
    $groqKey = getenv('GROQ_API_KEY');
}

// ── Read request body ─────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['entry'])) {
    echo json_encode(['ok' => false, 'msg' => 'invalid_payload']);
    exit();
}

$e     = $body['entry'];
$score = isset($body['score']) ? (int)$body['score'] : 0;

// ── Build Arabic medical prompt ───────────────────────────────
$prompt = buildArabicPrompt($e, $score);

// ── Call Groq API ─────────────────────────────────────────────
if ($groqKey) {
    $aiResult = callGroqAPI($groqKey, $prompt);
} else {
    $aiResult = null;
}

if ($aiResult && $aiResult['ok']) {
    echo json_encode([
        'ok'     => true,
        'source' => 'ai',
        'data'   => $aiResult['data']
    ]);
} else {
    // Return error so JS can fallback to rule-based
    echo json_encode([
        'ok'     => false,
        'source' => 'fallback',
        'msg'    => isset($aiResult['msg']) ? $aiResult['msg'] : 'api_unavailable'
    ]);
}

// ═════════════════════════════════════════════════════════════
// FUNCTIONS
// ═════════════════════════════════════════════════════════════

/**
 * Build the Arabic medical analysis prompt
 */
function buildArabicPrompt($e, $score) {
    // Helpers
    $moodLabels = ['', 'سيء جداً', 'متعب', 'عادي', 'جيد', 'ممتاز'];
    $symLabels  = [
        'headache'       => 'صداع',
        'dizziness'      => 'دوخة',
        'chest_pain'     => 'ألم في الصدر',
        'breathless'     => 'ضيق في التنفس',
        'severe_fatigue' => 'تعب شديد',
        'nausea'         => 'غثيان',
        'cough'          => 'سعال',
        'fever'          => 'حمى',
        'swelling'       => 'تورم الأرجل',
        'other'          => 'أخرى',
    ];
    $nutLabels  = [
        'healthy'      => 'صحية ومتوازنة',
        'no_appetite'  => 'فقدان الشهية',
        'high_sugar'   => 'غنية بالسكر',
        'high_fat'     => 'غنية بالدهون',
        'fast_food'    => 'وجبات سريعة',
        'skipped'      => 'تخطّى وجبات',
    ];
    $actLabels  = ['low' => 'قليل', 'medium' => 'متوسط', 'high' => 'عالٍ'];
    $medLabels  = ['yes' => 'نعم، تناوله في وقته', 'late' => 'نعم، لكن بتأخير', 'no' => 'لم يتناوله'];
    $sqLabels   = ['good' => 'جيدة', 'medium' => 'متوسطة', 'bad' => 'سيئة'];

    // Decode arrays
    $symptoms  = [];
    if (!empty($e['symptoms'])) {
        $rawSym = is_array($e['symptoms']) ? $e['symptoms'] : json_decode($e['symptoms'], true);
        if (is_array($rawSym)) {
            foreach ($rawSym as $s) {
                $symptoms[] = isset($symLabels[$s]) ? $symLabels[$s] : $s;
            }
        }
    }

    $nutrition = [];
    if (!empty($e['nutrition'])) {
        $rawNut = is_array($e['nutrition']) ? $e['nutrition'] : json_decode($e['nutrition'], true);
        if (is_array($rawNut)) {
            foreach ($rawNut as $n) {
                $nutrition[] = isset($nutLabels[$n]) ? $nutLabels[$n] : $n;
            }
        }
    }

    $moodText   = isset($moodLabels[(int)($e['mood'] ?? 0)]) ? $moodLabels[(int)($e['mood'] ?? 0)] : 'غير محدد';
    $medText    = isset($medLabels[$e['medication'] ?? '']) ? $medLabels[$e['medication'] ?? ''] : 'غير محدد';
    $actText    = isset($actLabels[$e['activity'] ?? ''])   ? $actLabels[$e['activity'] ?? '']   : 'غير محدد';
    $sqText     = isset($sqLabels[$e['sleep_quality'] ?? '']) ? $sqLabels[$e['sleep_quality'] ?? ''] : 'غير محدد';

    $lines = [];
    $lines[] = "- المؤشر الصحي الحسابي اليوم: {$score}%";
    $lines[] = "- الحالة المزاجية: {$moodText}";

    if (!empty($e['feel_text'])) {
        $lines[] = "- وصف المريض بكلماته: " . substr($e['feel_text'], 0, 300);
    }
    if (!empty($e['bp']))         $lines[] = "- ضغط الدم: {$e['bp']} mmHg";
    if (!empty($e['sugar']))      $lines[] = "- سكر الدم: {$e['sugar']} mg/dL";
    if (!empty($e['heart_rate'])) $lines[] = "- معدل ضربات القلب: {$e['heart_rate']} bpm";
    if (!empty($e['temp']))       $lines[] = "- درجة الحرارة: {$e['temp']} °C";
    if (!empty($e['spo2']))       $lines[] = "- نسبة الأكسجين SpO2: {$e['spo2']}%";
    if (!empty($e['weight']))     $lines[] = "- الوزن: {$e['weight']} كغ";
    if (!empty($symptoms))        $lines[] = "- الأعراض: " . implode('، ', $symptoms);
    if (isset($e['pain']))        $lines[] = "- مستوى الألم: {$e['pain']}/10";
    $lines[] = "- الدواء: {$medText}";
    if (isset($e['sleep_hours'])) $lines[] = "- ساعات النوم: {$e['sleep_hours']} ساعات";
    $lines[] = "- جودة النوم: {$sqText}";
    if (isset($e['water_cups']))  $lines[] = "- أكواب الماء: {$e['water_cups']}";
    $lines[] = "- النشاط البدني: {$actText}";
    if (!empty($nutrition))       $lines[] = "- التغذية: " . implode('، ', $nutrition);
    if (!empty($e['notes']))      $lines[] = "- ملاحظات إضافية: " . substr($e['notes'], 0, 300);

    $dataBlock = implode("\n", $lines);

    $prompt = <<<PROMPT
أنت مساعد طبي ذكي متخصص في التحليل الصحي اليومي. مهمتك تحليل بيانات المريض وتقديم تقرير طبي مفيد باللغة العربية.

بيانات المريض لهذا اليوم:
{$dataBlock}

قم بتحليل هذه البيانات وأعد رداً بصيغة JSON فقط (بدون أي نص خارج JSON) على هذا الشكل بالضبط:

{
  "summary": "ملخص شامل لحالة المريض الصحية اليوم في جملة أو جملتين بأسلوب طبي واضح ومطمئن",
  "risk_level": "low أو medium أو high",
  "warnings": [
    "تحذير طبي 1 إن وُجد",
    "تحذير طبي 2 إن وُجد"
  ],
  "recommendations": [
    "توصية صحية عملية 1",
    "توصية صحية عملية 2",
    "توصية صحية عملية 3"
  ]
}

تعليمات مهمة:
- risk_level يجب أن يكون: low إذا كانت الحالة جيدة، medium إذا تطلبت انتباهاً، high إذا كانت تستدعي الطوارئ أو زيارة طبيب عاجلة
- warnings: قائمة فارغة [] إذا لا توجد تحذيرات، وإلا اذكر تحذيرات محددة وواضحة
- recommendations: قدّم دائماً 2-4 توصيات عملية ومخصصة للبيانات الموجودة
- لا تخترع بيانات غير موجودة، حلّل فقط ما هو متاح
- الرد يجب أن يكون JSON صحيح فقط، بدون markdown أو backticks أو أي نص إضافي
PROMPT;

    return $prompt;
}

/**
 * Call Groq API and parse response
 */
function callGroqAPI($apiKey, $prompt) {
    $payload = json_encode([
        'model'       => 'llama-3.3-70b-versatile',
        'max_tokens'  => 1024,
        'temperature' => 0.3,
        'messages'    => [
            [
                'role'    => 'user',
                'content' => $prompt
            ]
        ]
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['ok' => false, 'msg' => 'curl_error: ' . $curlErr];
    }

    if ($httpCode !== 200) {
        return ['ok' => false, 'msg' => 'http_' . $httpCode];
    }

    $resp = json_decode($response, true);
    if (!$resp || !isset($resp['choices'][0]['message']['content'])) {
        return ['ok' => false, 'msg' => 'invalid_groq_response'];
    }

    $content = trim($resp['choices'][0]['message']['content']);

    // Strip markdown code fences if present
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    $content = trim($content);

    $parsed = json_decode($content, true);
    if (!$parsed || !isset($parsed['summary'])) {
        return ['ok' => false, 'msg' => 'invalid_json_from_model'];
    }

    // Sanitize & validate
    $riskAllowed = ['low', 'medium', 'high'];
    $riskLevel   = isset($parsed['risk_level']) && in_array($parsed['risk_level'], $riskAllowed)
                   ? $parsed['risk_level'] : 'medium';

    return [
        'ok'   => true,
        'data' => [
            'summary'         => isset($parsed['summary'])         ? (string)$parsed['summary']           : '',
            'risk_level'      => $riskLevel,
            'warnings'        => isset($parsed['warnings'])        && is_array($parsed['warnings'])        ? array_values($parsed['warnings'])        : [],
            'recommendations' => isset($parsed['recommendations']) && is_array($parsed['recommendations']) ? array_values($parsed['recommendations']) : [],
        ]
    ];
}
