<?php
/**
 * MedChifaGiz — save_daily_journal.php
 * Saves a daily health journal entry via AJAX (JSON POST)
 */
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'not_logged_in']);
    exit();
}

require 'db.php';

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['entry'])) {
    echo json_encode(['ok' => false, 'msg' => 'invalid_payload']);
    exit();
}

$e     = $body['entry'];
$score = isset($body['score']) ? (int)$body['score'] : 0;
$uid   = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

// Symptom arrays → JSON string
$symptoms  = json_encode(isset($e['symptoms'])  ? $e['symptoms']  : []);
$nutrition = json_encode(isset($e['nutrition']) ? $e['nutrition'] : []);

// Upsert: one entry per user per day
$check = $pdo->prepare("SELECT id FROM daily_journal WHERE user_id = ? AND entry_date = ?");
$check->execute([$uid, $today]);
$existing = $check->fetch();

if ($existing) {
    $stmt = $pdo->prepare("
        UPDATE daily_journal SET
            mood          = ?,
            feel_text     = ?,
            bp            = ?,
            sugar         = ?,
            heart_rate    = ?,
            temperature   = ?,
            spo2          = ?,
            weight        = ?,
            symptoms      = ?,
            pain_level    = ?,
            medication    = ?,
            sleep_hours   = ?,
            sleep_quality = ?,
            water_cups    = ?,
            activity      = ?,
            nutrition     = ?,
            notes         = ?,
            health_score  = ?,
            updated_at    = NOW()
        WHERE user_id = ? AND entry_date = ?
    ");
    $stmt->execute([
        isset($e['mood'])          ? (int)$e['mood']          : null,
        isset($e['feel_text'])     ? substr($e['feel_text'],0,2000) : null,
        isset($e['bp'])            ? substr($e['bp'],0,20)     : null,
        isset($e['sugar'])         ? substr($e['sugar'],0,20)  : null,
        isset($e['heart_rate'])    ? substr($e['heart_rate'],0,20) : null,
        isset($e['temp'])          ? substr($e['temp'],0,10)   : null,
        isset($e['spo2'])          ? substr($e['spo2'],0,10)   : null,
        isset($e['weight'])        ? substr($e['weight'],0,10) : null,
        $symptoms,
        isset($e['pain'])          ? (int)$e['pain']           : 0,
        isset($e['medication'])    ? substr($e['medication'],0,10) : null,
        isset($e['sleep_hours'])   ? (int)$e['sleep_hours']    : null,
        isset($e['sleep_quality']) ? substr($e['sleep_quality'],0,20) : null,
        isset($e['water_cups'])    ? (int)$e['water_cups']     : 0,
        isset($e['activity'])      ? substr($e['activity'],0,20) : null,
        $nutrition,
        isset($e['notes'])         ? substr($e['notes'],0,2000) : null,
        $score,
        $uid,
        $today
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO daily_journal
            (user_id, entry_date, mood, feel_text, bp, sugar, heart_rate,
             temperature, spo2, weight, symptoms, pain_level, medication,
             sleep_hours, sleep_quality, water_cups, activity, nutrition,
             notes, health_score, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
    ");
    $stmt->execute([
        $uid,
        $today,
        isset($e['mood'])          ? (int)$e['mood']          : null,
        isset($e['feel_text'])     ? substr($e['feel_text'],0,2000) : null,
        isset($e['bp'])            ? substr($e['bp'],0,20)     : null,
        isset($e['sugar'])         ? substr($e['sugar'],0,20)  : null,
        isset($e['heart_rate'])    ? substr($e['heart_rate'],0,20) : null,
        isset($e['temp'])          ? substr($e['temp'],0,10)   : null,
        isset($e['spo2'])          ? substr($e['spo2'],0,10)   : null,
        isset($e['weight'])        ? substr($e['weight'],0,10) : null,
        $symptoms,
        isset($e['pain'])          ? (int)$e['pain']           : 0,
        isset($e['medication'])    ? substr($e['medication'],0,10) : null,
        isset($e['sleep_hours'])   ? (int)$e['sleep_hours']    : null,
        isset($e['sleep_quality']) ? substr($e['sleep_quality'],0,20) : null,
        isset($e['water_cups'])    ? (int)$e['water_cups']     : 0,
        isset($e['activity'])      ? substr($e['activity'],0,20) : null,
        $nutrition,
        isset($e['notes'])         ? substr($e['notes'],0,2000) : null,
        $score
    ]);
}

echo json_encode(['ok' => true, 'score' => $score, 'date' => $today]);
