<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// 🔴 حط API KEY جديد هنا (مهم!)
$apiKey = "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";

// 🧠 استقبال البيانات
$message = trim($_POST['message'] ?? '');
$isImage = isset($_FILES['image']) && $_FILES['image']['error'] === 0 && $_FILES['image']['size'] > 0;
if ($isImage) {
    error_log("IMAGE DETECTED");
}
$imageBase64 = null;
$imagePath = null; // مهم

if ($isImage) {

    // 🔥 حفظ الصورة
    $targetDir = "uploads/";

    if(!is_dir($targetDir)){
        mkdir($targetDir);
    }

    $fileName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if(move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)){
        $imagePath = $targetFile;
    }

    // (اختياري) base64
    $imageData = file_get_contents($_FILES['image']['tmp_name']);
    $imageBase64 = base64_encode($imageData);
}
$healthKeywords = [
    "pain","fever","headache","stomach","cough","nausea",
    "وجع","ألم","رأسي","بطني","سعال","حرارة"
];

$isHealth = false;

foreach ($healthKeywords as $word) {
    if (strpos(mb_strtolower($message), $word) !== false) {
        $isHealth = true;
        break;
    }
}

$isFirstMessage = empty($_POST['history']);

if ($isFirstMessage && !$isHealth) {
    echo json_encode([
        "reply" => $lang === "english" 
    ? "I am a health assistant and only answer health-related questions."
    : ($lang === "french"
        ? "Je suis un assistant de santé et je réponds فقط على الأسئلة الصحية."
        : "أنا مساعد صحي وأجيب فقط على الأسئلة المتعلقة بالصحة")
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$lang = detectLanguage($message);

function detectLanguage($text) {
    if (preg_match('/[ء-ي]/u', $text)) {
        return "arabic";
    }

    if (preg_match('/[éèêàçùôî]/iu', $text)) {
        return "french";
    }

    if (preg_match('/[a-zA-Z]/', $text)) {
        return "english";
    }

    return "english";
}

$lang = detectLanguage($message);
$isSymptomMessage = false;

$symptomKeywords = [
    "pain","fever","headache","nausea","vomiting","cough","chest","stomach","skin","eye","tooth","diabetes",
    "symptom","hurt","hurts","sick","dizzy","fatigue",
    "ألم","يوجعني","توجعني","حرارة","حمى","صداع","غثيان","قيء","سعال","صدر","بطن","معدة","جلد","عين","سن","سكر","دوخة","تعب",
    "mal","douleur","fièvre","nausée","toux","ventre","dent","oeil","peau","fatigue","vertige"
];

$lowerMessage = mb_strtolower($message, 'UTF-8');

foreach ($symptomKeywords as $word) {
    if (mb_strpos($lowerMessage, $word) !== false) {
        $isSymptomMessage = true;
        break;
    }
}

if ($message === "") {
    echo json_encode([
        "reply" => "Please write a health question."
    ]);
    exit;
}

// 🧠 history (بدون أخطاء)
$history = [];

if (isset($_POST['history'])) {
    $decoded = json_decode($_POST['history'], true);
    if (is_array($decoded)) {
        $history = $decoded;
    }
}

// 🌐 API URL
$url = "https://api.groq.com/openai/v1/chat/completions";

// 🧠 SYSTEM PROMPT (Interactive diagnosis + specialty)
$systemPrompt = "
You are MedChifagiz AI, a professional health assistant.

IMPORTANT RULES:
- Ask only ONE short question if needed
- Do not ask more than 3 questions total
- If the user describes symptoms, you MUST ask at least ONE question before giving a diagnosis
When you have enough information:
- Give a natural answer like a real doctor
- Include possible cause, advice, and doctor type naturally
- Do NOT use labels or lists

- Do not stay in an endless question loop.
- If enough information is already available, do not ask more questions.
- Be direct and concise.

STRICT LANGUAGE RULE:
- Detect the language from the LAST user message only
- If the last user message is in English, reply ONLY in English
- If the last user message is in French, reply ONLY in French
- If the last user message is in Arabic or Algerian Darija, reply ONLY in Arabic
- NEVER mix languages
- Always reply in the same language as the user's last message
- Do not mix languages


Examples:
- User: What is diabetes? -> Reply in English
- User: J'ai mal au ventre -> Reply in French
- User: بطني توجعني -> Reply in Arabic

========================
TOPIC RULE
========================
- Answer ONLY questions related to health, medicine, symptoms, nutrition, fitness, and well-being
- If the question is NOT related to health, you MUST refuse and reply ONLY with:
أنا مساعد صحي وأجيب فقط على الأسئلة المتعلقة بالصحة.
- This rule is STRICT and cannot be ignored.

========================
SMART MODE
========================
You must choose ONE mode:
- NEVER mix modes (choose only one mode based on the user's input)

1. GENERAL HEALTH MODE
Use this mode if the user asks a general medical question, such as:
- What is diabetes?
- What are symptoms of anemia?
- What causes headache?

In this mode:
- Answer the question directly and clearly
- Do NOT ask for more context if the question is already clear
- Do NOT ask follow-up questions IF the question is general and clear
- If the user describes symptoms, DO NOT stay in this mode; switch to INTERACTIVE DIAGNOSIS MODE
- Stay in this mode ONLY for general health questions without personal symptoms
- Keep the answer simple and useful
- Answer directly
- Give a clear explanation
- Give simple advice

2. INTERACTIVE DIAGNOSIS MODE
Use this mode if the user describes symptoms, pain, discomfort, or a health complaint.

========================
INTERACTIVE DIAGNOSIS MODE
========================
- Ask ONLY ONE short relevant medical question per response
- NEVER ask 2 questions in the same response
- NEVER use numbered lists
- NEVER format as list
- NEVER repeat the same question
- NEVER rephrase the same question again
- Wait for the user's answer before continuing
- Each next question must depend on the user's previous answer

QUESTION LIMIT:
- Ask maximum 3 questions only
- After 2 or 3 user answers, STOP asking questions

During diagnosis mode:
- If you are still asking a question, do NOT give final explanation
- Do NOT give final advice
- Do NOT give specialty yet
- Do NOT write Next Question label
- Ask the question only, in a natural doctor style
3. IMAGE ANALYSIS MODE

Use this mode if the user uploads or sends an image (skin, wound, medical report, etc.)

IMAGE ANALYSIS MODE RULES:

- If the user uploads an image, you MUST analyze the image first
- Decide whether the image is:
  - a skin problem
  - a wound or injury
  - a medical report / analysis
  - a medicine photo
  - unclear / low quality

- If the image is clear:
  - describe briefly what is visible
  - give possible medical explanation in simple language
  - do NOT give a final diagnosis too early
  - if needed, ask ONLY ONE short relevant question
  - then give advice
  - then give the final specialty

- If the image is unclear:
  - say that the image is not clear
  - ask the user to send a clearer image
  - do NOT invent details

- If the image shows danger signs:
  - say clearly that urgent medical care may be needed
  - keep the warning short and serious

- For medical report images:
  - explain the visible values in simple words
  - mention high / low / abnormal values if visible
  - do NOT invent numbers that are not visible
  - if some values are unreadable, say so clearly

- NEVER ask multiple questions
- NEVER mix image mode with general mode

================================

FINAL STEP:

After enough information:
- STOP asking questions
- Give a natural explanation (like a real doctor)
- Give simple advice
- Mention when to see a doctor if needed
Then include ONE final specialty naturally in the answer

If enough information is available, this is the final answer.
After the final answer, do NOT ask more questions.

========================
SPECIALTY RULE:

- Choose ONE most relevant medical specialty based on the symptoms
- Write the specialty in English

- Include it naturally in the sentence, not as a label

Example:
The most suitable specialist is a gastroenterologist.


CRITICAL SPECIALTY SELECTION:

- You MUST choose the MOST relevant specialty based on symptoms
- DO NOT default to General Practitioner unless there is truly NO clear symptom

- You are NOT allowed to choose General Practitioner if any symptom clearly points to a system (stomach, skin, chest, etc.)

Examples:
- stomach pain → Gastroenterologist
- skin rash → Dermatologist
- chest pain → Cardiologist
- tooth pain → Dentist
- breathing issues → Pulmonologist
- hormonal or diabetes → Endocrinologist

- Only use General Practitioner when symptoms are vague or unclear

FINAL RULE:
You MUST think before choosing specialty
Do NOT always return the same one

========================
STYLE RULE
========================
- Be clear
- Be natural
- Be medically careful
- Be concise but helpful
- Do NOT use symbols like #
- Do NOT use numbered lists
- Do NOT make the answer too short
- Sound like a real doctor

========================
FINAL ANSWER FORMAT
========================
For GENERAL HEALTH MODE or FINAL DIAGNOSIS MODE, use this structure:

Title:
...

Explanation:
...

Advice:
- ...
- ...

When to see a doctor:
...

Warning:
⚠️ same language as user

SPECIALTY: [doctor type in English]

========================
QUESTION MODE FORMAT
========================
If you are still in diagnosis mode and still need more information:
- Ask only ONE short question
- Do NOT include Explanation
- Do NOT include Advice
- Do NOT include SPECIALTY
- Do NOT include Next Question label
";
// 🧠 simple symptom detection
// simple symptom detection
$symptomsKeywords = [
    "pain", "fever", "headache", "dizzy", "cough",
    "stomach", "chest", "tired", "vomit", "nausea",
    "خفقان", "ألم", "صداع", "دوخة", "حرارة", "سعال", "تعب"
];

$isSymptomMessage = false;
$lowerMessage = mb_strtolower($message, 'UTF-8');

foreach ($symptomsKeywords as $word) {
    if (mb_strpos($lowerMessage, mb_strtolower($word, 'UTF-8')) !== false) {
        $isSymptomMessage = true;
        break;
    }
}
// 🧠 بناء الرسائل
$modeInstruction = $isSymptomMessage
    ? "The current user message looks like symptoms. You MUST use interactive diagnosis mode and ask only ONE follow-up question if information is not enough."
    : "The current user message looks like a general health question. Answer directly and clearly.";
if ($isImage) {
    $modeInstruction = "The user provided an image. You MUST use IMAGE ANALYSIS MODE ONLY. NEVER use any other mode.";
}
elseif ($isSymptomMessage) {
    $modeInstruction = "This user message describes symptoms. Use INTERACTIVE DIAGNOSIS MODE. Ask only one short medical follow-up question at a time. Do not give final specialty too early. After 2 or 3 answers maximum, stop asking and give a short explanation, advice, and exactly one final specialty.";
}
else {
    $modeInstruction = "This user message is a general health question. You MUST answer directly and clearly. DO NOT ask any questions. DO NOT use diagnosis mode.";
}

$messages = [
    ["role" => "system", "content" => $systemPrompt],
    ["role" => "system", "content" => $modeInstruction]
];

$messages[] = [
    "role" => "system",
    "content" => "You MUST reply ONLY in " . $lang . ". 
    Do NOT translate. 
    Do NOT mix languages. 
    If the user writes in Arabic, reply ONLY in Arabic.
    If the user writes in French, reply ONLY in French.
    If the user writes in English, reply ONLY in English.
    This is a strict rule."
];

$messages[] = [
    "role" => "system",
    "content" => "Using the wrong language is a critical error."
];

$history = array_slice($history, -6);

foreach ($history as $msg) {
    if (isset($msg["role"]) && isset($msg["content"])) {
        $messages[] = $msg;
    }
}

if ($isImage) {
    $message = "The user uploaded an image. Analyze it. " . $message;
}

$messages[] = [
    "role" => "user",
    "content" => $message
];


// 📦 الطلب
$data = [
    "model" => "llama-3.1-8b-instant",
    "temperature" => 0.2,
    "max_tokens" => 700,
    "messages" => $messages
];

// 🔐 headers
$headers = [
    "Authorization: Bearer " . $apiKey,
    "Content-Type: application/json"
];

// 🔄 CURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

// ❌ CURL ERROR
if ($response === false) {
    echo json_encode([
        "reply" => "Server error: " . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// 📥 decode
$result = json_decode($response, true);

// ❌ API ERROR
if (isset($result["error"])) {
    echo json_encode([
        "reply" => "API Error: " . $result["error"]["message"]
    ]);
    exit;
}

// ❌ EMPTY RESPONSE
if (!isset($result["choices"][0]["message"]["content"])) {
    echo json_encode([
        "reply" => "No response from AI"
    ]);
    exit;
}

// ✅ نجيب الرد
$content = $result["choices"][0]["message"]["content"];
$nonHealthTriggers = ["marry","marriage","capital","joke","love","relationship"];

$lower = mb_strtolower($message);

foreach($nonHealthTriggers as $word){
    if(strpos($lower, $word) !== false){
        echo json_encode([
            "reply" => $lang === "english"
                ? "I am a health assistant and only answer health-related questions."
                : ($lang === "french"
                    ? "Je suis un assistant de santé et je réponds uniquement aux questions de santé."
                    : "أنا مساعد صحي وأجيب فقط على الأسئلة المتعلقة بالصحة")
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


// 🔥 استخراج SPECIALTY
// 🧠 تنظيف النص (مهم بزاف)
$specialty = "";

// استخراج SPECIALTY من الرد
if (preg_match('/SPECIALTY:\s*(.+)/i', $content, $matches)) {
    $specialty = trim($matches[1]);

    // نحذف SPECIALTY من النص باش ما يبانش للمستخدم
    $content = preg_replace('/SPECIALTY:\s*(.+)/i', '', $content);
}





// ✅ الرد النهائي
echo json_encode([
    "reply" => trim($content),
    "specialty" => $specialty ?? null,
    "image" => $imagePath ?? null
], JSON_UNESCAPED_UNICODE);

exit;