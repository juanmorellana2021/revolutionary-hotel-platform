<?php
/**
 * Mobile Batch Translation API
 * Accepts array of strings, returns translated map.
 * Cache-first: checks DB, uses AI for misses, saves back to DB.
 * POST JSON: { "strings": ["text1","text2",...], "lang": "en" }
 */
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(240);   // AI model can take 60-120s on cold start
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'POST only']); exit();
}

$input   = json_decode(file_get_contents('php://input'), true);
$strings = $input['strings'] ?? [];
$lang    = $input['lang']    ?? 'en';

$validLangs = ['en','pt','fr','de','it','zh','ja'];
if (!in_array($lang, $validLangs) || empty($strings)) {
    echo json_encode(['success'=>true,'translations'=>[]]); exit();
}

// DB connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4',
        'hoteluser','hotelpass123',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>'DB unavailable']); exit();
}

$translations = [];   // original => translated
$needsAI      = [];   // strings not in cache

// ── Step 1: Check DB cache ────────────────────────────────────────────────────
foreach ($strings as $str) {
    $str = trim($str);
    if (strlen($str) < 2) { $translations[$str] = $str; continue; }
    $hash = md5($str);
    try {
        $stmt = $pdo->prepare("
            SELECT translated_text FROM translations_cache
            WHERE content_type='ui_mobile' AND target_lang=? AND MD5(original_text)=?
            LIMIT 1
        ");
        $stmt->execute([$lang, $hash]);
        $row = $stmt->fetch();
        if ($row) {
            $translations[$str] = $row['translated_text'];
        } else {
            $needsAI[] = $str;
        }
    } catch (Exception $e) {
        $needsAI[] = $str;
    }
}

// ── Step 2: Batch AI translation for cache misses ─────────────────────────────
if (!empty($needsAI)) {
    $langNames = [
        'en'=>'English','pt'=>'Portuguese','fr'=>'French',
        'de'=>'German','it'=>'Italian','zh'=>'Chinese','ja'=>'Japanese'
    ];
    $targetLanguage = $langNames[$lang] ?? 'English';

    // Send as JSON array — AI returns JSON array in same order
    $jsonList  = json_encode(array_values($needsAI), JSON_UNESCAPED_UNICODE);
    $prompt    = "Translate the following JSON array of UI strings from Spanish to {$targetLanguage}. "
               . "Output ONLY a valid JSON array with exactly the same number of elements in the same order. "
               . "No explanations, no extra text, just the JSON array.\n\n{$jsonList}";

    $aiPayload = json_encode([
        'model'      => 'qwen2.5:7b',
        'prompt'     => $prompt,
        'stream'     => false,
        'keep_alive' => '5m',
        'options'    => ['temperature'=>0.2, 'num_predict'=>2000, 'num_ctx'=>2048]
    ]);

    $ch = curl_init('http://72.60.1.16:11434/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $aiPayload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,   // RunPod is fast, same server as Sofia
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $aiResponse = curl_exec($ch);
    $aiError    = curl_error($ch);
    $aiCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $aiTranslated = [];
    if (!$aiError && $aiCode === 200) {
        $aiResult = json_decode($aiResponse, true);
        $rawText  = trim($aiResult['response'] ?? '');

        // Extract JSON array from response (AI sometimes wraps with text)
        if (preg_match('/\[.*\]/s', $rawText, $m)) {
            $parsed = json_decode($m[0], true);
            if (is_array($parsed) && count($parsed) === count($needsAI)) {
                $aiTranslated = $parsed;
            }
        }
        // Fallback: if JSON parse failed, use original strings
        if (empty($aiTranslated)) {
            $aiTranslated = $needsAI; // show originals rather than break
        }
    } else {
        $aiTranslated = $needsAI; // AI unavailable → show original
    }

    // ── Step 3: Map results + save to DB cache ────────────────────────────────
    foreach ($needsAI as $i => $original) {
        $translated = trim($aiTranslated[$i] ?? $original);
        $translations[$original] = $translated;

        // Save to DB
        if ($translated !== $original) {
            try {
                $pdo->prepare("
                    INSERT INTO translations_cache
                        (content_type, content_id, original_text, target_lang, translated_text)
                    VALUES ('ui_mobile', 0, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE translated_text=VALUES(translated_text), created_at=NOW()
                ")->execute([$original, $lang, $translated]);
            } catch (Exception $e) { /* cache save fail is OK */ }
        }
    }
}

echo json_encode(['success'=>true, 'lang'=>$lang, 'translations'=>$translations]);
