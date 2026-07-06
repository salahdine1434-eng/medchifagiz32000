<?php
/**
 * MedChifaGiz — analyze_medication_ai.php
 * AI-powered medication analysis using Groq API
 * Smart Medication Center — أدويتي
 * Model: llama-3.3-70b-versatile
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'not_logged_in']);
    exit();
}

// ── Load Groq API key from config (same as analyze_daily_ai.php) ──
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
if (!$groqKey) {
    $groqKey = getenv('GROQ_API_KEY');
}

// ── Read request body ─────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body) {
    echo json_encode(['ok' => false, 'msg' => 'invalid_payload']);
    exit();
}

$medications   = isset($body['medications'])   ? (string)$body['medications']   : '';
$medList       = isset($body['med_list'])      ? (array)$body['med_list']       : [];
$userQuestion  = isset($body['question'])      ? (string)$body['question']      : '';
$patientData   = isset($body['patient'])       ? (array)$body['patient']        : [];

// ── Build prompt ──────────────────────────────────────────────
$prompt = buildMedPrompt($medications, $medList, $userQuestion, $patientData);

// ── Call Groq API ─────────────────────────────────────────────
if ($groqKey) {
    $aiResult = callGroqAPI($groqKey, $prompt);
} else {
    $aiResult = null;
}

if ($aiResult && $aiResult['ok']) {
    echo json_encode(['ok' => true, 'data' => $aiResult['data']]);
} else {
    echo json_encode([
        'ok'  => false,
        'msg' => isset($aiResult['msg']) ? $aiResult['msg'] : 'api_unavailable'
    ]);
}

// ═════════════════════════════════════════════════════════════
// FUNCTIONS
// ═════════════════════════════════════════════════════════════

function buildMedPrompt($medications, $medList, $userQuestion, $patientData) {
    $medListText = '';
    if (!empty($medList)) {
        foreach ($medList as $m) {
            $name    = isset($m['name'])   ? $m['name']   : '';
            $dose    = isset($m['dose'])   ? $m['dose']   : '';
            $timing  = isset($m['timing']) ? $m['timing'] : '';
            $food    = isset($m['food'])   ? $m['food']   : '';
            $note    = isset($m['note'])   ? $m['note']   : '';
            $medListText .= "  - {$name}";
            if ($dose)   $medListText .= " ({$dose})";
            if ($timing) $medListText .= " — {$timing}";
            if ($food)   $medListText .= " — {$food}";
            if ($note)   $medListText .= " — ملاحظة: {$note}";
            $medListText .= "\n";
        }
    }

    $allMeds = trim($medications . "\n" . $medListText);
    if (!$allMeds) $allMeds = 'لا توجد أدوية مسجلة';

    $patientInfo = '';
    if (!empty($patientData)) {
        if (!empty($patientData['chronic_diseases'])) $patientInfo .= "- الأمراض المزمنة: {$patientData['chronic_diseases']}\n";
        if (!empty($patientData['allergies']))        $patientInfo .= "- الحساسيات: {$patientData['allergies']}\n";
        if (!empty($patientData['blood_type']))       $patientInfo .= "- فصيلة الدم: {$patientData['blood_type']}\n";
    }

    $questionSection = $userQuestion
        ? "سؤال المريض: {$userQuestion}"
        : "قدّم تحليلاً شاملاً لأدوية المريض مع توصيات عملية وتحذيرات مهمة وتنبيهات التفاعل.";

    $prompt = <<<PROMPT
أنت مساعد صيدلاني ذكي متخصص في تحليل الأدوية وتقديم النصائح الطبية الآمنة. اسمك MedBot AI.

بيانات المريض:
{$patientInfo}
قائمة الأدوية:
{$allMeds}

{$questionSection}

قم بتحليل هذه الأدوية وأعد رداً بصيغة JSON فقط (بدون أي نص خارج JSON) على هذا الشكل بالضبط:

{
  "summary": "ملخص طبي واضح ومطمئن للإجابة على سؤال المريض أو تقديم نظرة عامة عن أدويته (جملة أو جملتان)",
  "recommendations": [
    "توصية عملية 1 مخصصة للأدوية المذكورة",
    "توصية عملية 2",
    "توصية عملية 3"
  ],
  "warnings": [
    "تحذير مهم 1 إن وُجد",
    "تحذير مهم 2 إن وُجد"
  ],
  "interactions": [
    "تنبيه تفاعل 1 إن وُجد",
    "تنبيه تفاعل 2 إن وُجد"
  ],
  "tips": [
    "نصيحة مفيدة 1 (مثل: اشرب ماء أكثر، تجنب القهوة...)",
    "نصيحة مفيدة 2"
  ]
}

تعليمات مهمة:
- إذا لا توجد تحذيرات أو تفاعلات، اترك القائمة فارغة []
- قدّم دائماً 2-4 توصيات مخصصة للأدوية المذكورة
- اذكر دائماً نصيحتين عمليتين على الأقل
- كن محدداً بشأن كل دواء مذكور
- لا تخترع أدوية غير موجودة في القائمة
- تذكير: تحذيرات الطوارئ مثل ألم الصدر الشديد أو صعوبة التنفس تستدعي طبيباً فوراً
- الرد JSON صحيح فقط، بدون markdown أو backticks أو أي نص إضافي
PROMPT;

    return $prompt;
}

function callGroqAPI($apiKey, $prompt) {
    $payload = json_encode([
        'model'       => 'llama-3.3-70b-versatile',
        'max_tokens'  => 1200,
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
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr)        return ['ok' => false, 'msg' => 'curl_error: ' . $curlErr];
    if ($httpCode !== 200) return ['ok' => false, 'msg' => 'http_' . $httpCode];

    $resp = json_decode($response, true);
    if (!$resp || !isset($resp['choices'][0]['message']['content'])) {
        return ['ok' => false, 'msg' => 'invalid_groq_response'];
    }

    $content = trim($resp['choices'][0]['message']['content']);
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    $content = trim($content);

    $parsed = json_decode($content, true);
    if (!$parsed || !isset($parsed['summary'])) {
        return ['ok' => false, 'msg' => 'invalid_json_from_model'];
    }

    return [
        'ok'   => true,
        'data' => [
            'summary'         => (string)($parsed['summary'] ?? ''),
            'recommendations' => array_values((array)($parsed['recommendations'] ?? [])),
            'warnings'        => array_values((array)($parsed['warnings']        ?? [])),
            'interactions'    => array_values((array)($parsed['interactions']    ?? [])),
            'tips'            => array_values((array)($parsed['tips']            ?? [])),
        ]
    ];
}
