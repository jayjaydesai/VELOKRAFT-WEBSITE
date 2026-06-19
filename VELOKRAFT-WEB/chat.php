<?php
/**
 * Velokraft Parts Assistant — backend
 * Reads ANTHROPIC_API_KEY from the environment (set it in Railway → Variables).
 * The website's chat widget POSTs { "messages": [...] } here.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Assistant not configured']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
$messages = (isset($in['messages']) && is_array($in['messages'])) ? $in['messages'] : [];

// Selected site language (en/et/de/pl) — assistant replies in this language.
$langCode = isset($in['lang']) && is_string($in['lang']) ? strtolower(trim($in['lang'])) : 'en';
$langNames = ['en' => 'English', 'et' => 'Estonian', 'de' => 'German', 'pl' => 'Polish'];
$langName = isset($langNames[$langCode]) ? $langNames[$langCode] : 'English';

// Abuse guards: only keep the last 12 turns, cap each message length.
$messages = array_slice($messages, -12);
foreach ($messages as &$m) {
    if (isset($m['content']) && is_string($m['content'])) {
        $m['content'] = substr($m['content'], 0, 2000);
    }
}
unset($m);

if (count($messages) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No messages']);
    exit;
}

$system = "You are the Velokraft Parts Assistant, an AI helper on the website of Velokraft Auto Parts — a parts supplier in Tallinn, Estonia serving workshops, dealers, fleets and car owners across Europe.\n"
        . "Your role: help visitors describe what they need and guide them to the right next step. When the vehicle is missing, ask for make, model, year and engine, plus the part or symptom.\n"
        . "CRITICAL — you have NO parts catalogue, database or cross-reference tool and cannot look anything up. NEVER state, confirm or guess what a specific part number refers to — not its part type, not its brand, not its vehicle fitment. You cannot verify part numbers, so you must not invent an answer for one. If you are not certain, say so plainly.\n"
        . "Part numbers exist in different systems: vehicle-maker OE numbers and manufacturer catalogue numbers (e.g. MANN, Bosch, Febi, Mahle). Do NOT assume any number is an 'OE number' or claim which system or brand it belongs to.\n"
        . "When someone gives a part number or asks what a part fits, do NOT identify it yourself. Explain that our team can cross-reference the number and confirm the exact part, fitment and availability, and ask for the vehicle details to pass on.\n"
        . "You CANNOT see live stock or prices. For availability, pricing, ordering and number look-ups, direct them to send an enquiry via the form on this page or contact sales@velokraft.eu / +372 5848 2192. Never invent prices, stock, part identities or compatibility.\n"
        . "Velokraft carries: engine, suspension & steering, body & exterior, electrical & ignition, brakes, cooling, transmission, and filters/maintenance parts, for many makes including BMW, Mercedes, VW, Audi, Toyota, Ford, Volvo, Renault, Škoda and Peugeot.\n"
        . "You may share general automotive knowledge — how a part works, what a category covers, what info pins down a fit. Keep replies short — 2 to 5 sentences. Be warm, honest and professional.\n"
        . "IMPORTANT: The visitor has selected {$langName} as their language. Reply in {$langName}, unless the visitor clearly writes to you in a different language — in that case, match the language they used. Keep technical part names accurate.";

$payload = [
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 1000,
    'system'     => $system,
    'messages'   => $messages,
];

$ctx = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => "content-type: application/json\r\n"
                         . "x-api-key: {$apiKey}\r\n"
                         . "anthropic-version: 2023-06-01",
        'content'       => json_encode($payload),
        'ignore_errors' => true,
        'timeout'       => 30,
    ],
]);

$resp = @file_get_contents('https://api.anthropic.com/v1/messages', false, $ctx);
if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream unreachable']);
    exit;
}

$data = json_decode($resp, true);
$text = '';
if (isset($data['content']) && is_array($data['content'])) {
    foreach ($data['content'] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }
}

if ($text === '') {
    http_response_code(502);
    echo json_encode(['error' => 'No reply']);
    exit;
}

echo json_encode(['reply' => $text]);
