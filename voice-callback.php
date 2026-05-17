<?php
// ==========================================
// voice-callback.php
// Africa's Talking Voice — outbound advisory callback
//
// Two modes:
//   POST ?action=initiate  — trigger outbound call
//   POST (no action param) — callStartUrl / callBackUrl handler
//
// Supports interactive GetDigits for drill-down.
// Bilingual: English + Kiswahili (sw-KE via Google TTS)
// ==========================================

$config = require __DIR__ . '/../config.php';

$LANG_CODES = [
    'en'  => 'en-US',
    'sw'  => 'sw-KE',
];

// ==========================================
// MODE 1: INITIATE OUTBOUND CALL
// POST ?action=initiate
// Body: { "phoneNumber": "+254...", "farmId": "...", "lang": "sw" }
// ==========================================
if (($_GET['action'] ?? null) === 'initiate') {
    header('Content-Type: application/json');

    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $to     = $body['phoneNumber'] ?? '';
    $farmId = $body['farmId']      ?? '';
    $lang   = $body['lang']        ?? 'en';

    if (!$to || !$farmId) {
        http_response_code(400);
        echo json_encode(['error' => 'phoneNumber and farmId required']);
        exit;
    }

    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST']
        . '/api/voice-callback.php';

    $callStartUrl = $baseUrl
        . '?farmId=' . urlencode($farmId)
        . '&lang='   . urlencode($lang)
        . '&step=greeting';

    $ch = curl_init('https://voice.africastalking.com/call');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'username'     => $config['AT_USERNAME'],
            'from'         => $config['AT_PHONE'],
            'to'           => $to,
            'callStartUrl' => $callStartUrl,
        ]),
        CURLOPT_HTTPHEADER => [
            'apiKey: ' . $config['AT_API_KEY'],
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($httpCode);
    echo $result;
    exit;
}

// ==========================================
// MODE 2: VOICE XML HANDLER
// ==========================================
header('Content-Type: text/xml');

$farmId = $_GET['farmId'] ?? '';
$lang   = $_GET['lang']   ?? 'en';
$step   = $_GET['step']   ?? 'greeting';
$voice  = $LANG_CODES[$lang] ?? 'en-US';
$digits = $_POST['dtmfDigits'] ?? '';

if (!$farmId) xml_say("No farm data found. Goodbye.", $voice);

// --- Fetch farm data ---
$ch = curl_init($config['ADVISORY_API_URL']);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$json = curl_exec($ch);
curl_close($ch);

$all_farms = json_decode($json, true) ?? [];
$farm      = $all_farms[$farmId] ?? null;

if (!$farm) xml_say("Farm data not available. Please try again later.", $voice);

$advisory  = $farm['advisory']  ?? [];
$farm_name = $farm['metadata']['farmName'] ?? $farmId;

// --- Detect farm type ---
$aqua_species = ['Seaweed', 'Oysters', 'Mud Crab', 'Milkfish', 'Shrimp'];
$farm_type = detect_farm_type($farm, $advisory, $aqua_species);

// --- Callback URL builder ---
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . '/api/voice-callback.php'
    . '?farmId=' . urlencode($farmId)
    . '&lang='   . urlencode($lang);

// ==========================================
// GREETING — summary then menu via GetDigits
// ==========================================
if ($step === 'greeting') {
    $summary = build_summary($farm_type, $farm_name, $advisory, $aqua_species);
    $menu    = build_menu_prompt($farm_type);

    xml_response([
        say($voice, $summary),
        '<GetDigits timeout="10" finishOnKey="#" numDigits="1" callBackUrl="' . esc($baseUrl . '&step=menu') . '">',
        '  ' . say($voice, $menu),
        '</GetDigits>',
        say($voice, "No input received. Goodbye."),
    ]);
}

// ==========================================
// MENU — farmer pressed a digit
// ==========================================
if ($step === 'menu') {
    if ($digits === '0') {
        $summary = build_summary($farm_type, $farm_name, $advisory, $aqua_species);
        $menu    = build_menu_prompt($farm_type);
        xml_response([
            say($voice, $summary),
            '<GetDigits timeout="10" finishOnKey="#" numDigits="1" callBackUrl="' . esc($baseUrl . '&step=menu') . '">',
            '  ' . say($voice, $menu),
            '</GetDigits>',
            say($voice, "Goodbye."),
        ]);
    }

    $detail = get_detail($farm_type, $digits, $advisory, $aqua_species);
    xml_response([
        say($voice, $detail),
        '<GetDigits timeout="8" finishOnKey="#" numDigits="1" callBackUrl="' . esc($baseUrl . '&step=menu') . '">',
        '  ' . say($voice, "Press 0 to hear the menu again, or hang up."),
        '</GetDigits>',
        say($voice, "Thank you. Goodbye."),
    ]);
}

xml_say("Thank you for calling. Goodbye.", $voice);

// ==========================================
// FARM TYPE DETECTION
// ==========================================
function detect_farm_type($farm, $advisory, $aqua_species) {
    if (isset($farm['fisheries'])) return 'coastal';
    foreach ($advisory as $crop => $adv) {
        if ($crop === 'Marine' || in_array($crop, $aqua_species)) return 'coastal';
    }
    $soil_keys = ['soil_health', 'carbon_sequestration', 'regenerative', 'supply_chain'];
    foreach ($soil_keys as $key) { if (isset($farm[$key])) return 'soil'; }
    $soil_adv_keys = ['Soil Health', 'Carbon', 'Supply Chain', 'Regenerative'];
    foreach ($advisory as $key => $adv) { if (in_array($key, $soil_adv_keys)) return 'soil'; }
    return 'terrestrial';
}

// ==========================================
// SUMMARY BUILDERS
// ==========================================
function build_summary($farm_type, $farm_name, $advisory, $aqua_species) {
    $lines = ["Advisory update for $farm_name."];
    switch ($farm_type) {
        case 'coastal':
            $marine = $advisory['Marine']['full_response'] ?? $advisory['Marine']['dashboard_advice'] ?? null;
            if ($marine) { $s = $marine['safety_status'] ?? null; if ($s) $lines[] = "Fishing conditions: $s."; }
            $c = 0;
            foreach ($aqua_species as $crop) {
                $hl = $advisory[$crop]['dashboard_advice']['headline'] ?? null;
                if ($hl && $c < 2) { $lines[] = "$crop: $hl"; $c++; }
            }
            break;
        case 'soil':
            $hl = $advisory['Soil Health']['dashboard_advice']['headline'] ?? null;
            if ($hl) $lines[] = "Soil health: $hl";
            $hl = $advisory['Carbon']['dashboard_advice']['headline'] ?? null;
            if ($hl) $lines[] = "Carbon: $hl";
            break;
        case 'terrestrial':
            $c = 0;
            foreach ($advisory as $crop => $adv) {
                $hl = $adv['dashboard_advice']['headline'] ?? null;
                if ($hl && $c < 2) { $lines[] = "$crop: $hl"; $c++; }
            }
            break;
    }
    return implode(' ', $lines);
}

function build_menu_prompt($farm_type) {
    switch ($farm_type) {
        case 'coastal':     return "Press 1 for marine fishing. Press 2 for aquaculture. Press 3 for carbon credits. Press 0 to repeat.";
        case 'soil':        return "Press 1 for soil health. Press 2 for carbon sequestration. Press 3 for supply chain. Press 0 to repeat.";
        case 'terrestrial': return "Press 1 for your first crop. Press 2 for your second crop. Press 3 for your third crop. Press 0 to repeat.";
    }
    return "Press 0 to repeat.";
}

// ==========================================
// DETAIL HANDLERS
// ==========================================
function get_detail($farm_type, $digits, $advisory, $aqua_species) {
    switch ($farm_type) {
        case 'coastal':  return coastal_detail($digits, $advisory, $aqua_species);
        case 'soil':     return soil_detail($digits, $advisory);
        default:         return terrestrial_detail($digits, $advisory);
    }
}

function coastal_detail($digits, $advisory, $aqua_species) {
    if ($digits === '1') {
        $m = $advisory['Marine']['full_response'] ?? $advisory['Marine']['dashboard_advice'] ?? [];
        $p = [];
        if ($v = $m['safety_status'] ?? null)     $p[] = "Safety: $v.";
        if ($v = $m['primary_zone_today'] ?? null) $p[] = "Primary zone: $v.";
        if ($v = $m['surface_strategy'] ?? null)   $p[] = "Surface strategy: $v.";
        if ($v = $m['deep_strategy'] ?? null)      $p[] = "Deep strategy: $v.";
        if ($v = $m['captain_note'] ?? $m['outlook'] ?? null) $p[] = "Captain's note: $v.";
        return $p ? implode(' ', $p) : "No marine data available.";
    }
    if ($digits === '2') {
        $p = [];
        foreach ($aqua_species as $crop) {
            $hl = $advisory[$crop]['dashboard_advice']['headline'] ?? null;
            if ($hl) $p[] = "$crop: $hl";
        }
        return $p ? implode('. ', $p) . '.' : "No aquaculture data available.";
    }
    if ($digits === '3') return "Carbon credits tracking is coming soon.";
    return "Invalid selection. Press 1 for marine, 2 for aquaculture, 3 for carbon.";
}

function soil_detail($digits, $advisory) {
    if ($digits === '1') {
        $soil = $advisory['Soil Health']['dashboard_advice'] ?? [];
        if (empty($soil)) return "No soil health data available.";
        $p = [];
        foreach ($soil as $k => $v) { if (is_string($v)) $p[] = ucfirst($k) . ": $v"; }
        return $p ? implode('. ', $p) . '.' : "No soil health data available.";
    }
    if ($digits === '2') {
        $c = $advisory['Carbon']['dashboard_advice'] ?? [];
        $p = [];
        if ($v = $c['headline'] ?? null)        $p[] = $v;
        if ($v = $c['sequestered_tons'] ?? null) $p[] = "Sequestered: $v tons.";
        if ($v = $c['carbon_credits'] ?? null)   $p[] = "Carbon credits: $v.";
        return $p ? implode(' ', $p) : "No carbon data available.";
    }
    if ($digits === '3') {
        $s = $advisory['Supply Chain']['dashboard_advice'] ?? [];
        $p = [];
        if ($v = $s['dpp_status'] ?? null)     $p[] = "EU DPP status: $v.";
        if ($v = $s['origin_verified'] ?? null) $p[] = "Origin verified: $v.";
        return $p ? implode(' ', $p) : "No supply chain data available.";
    }
    return "Invalid selection. Press 1 for soil, 2 for carbon, 3 for supply chain.";
}

function terrestrial_detail($digits, $advisory) {
    $keys = array_keys($advisory);
    $idx = intval($digits) - 1;
    if ($idx < 0 || !isset($keys[$idx])) return "Invalid selection.";
    $crop = $keys[$idx];
    $adv = $advisory[$crop]['dashboard_advice'] ?? [];
    if (empty($adv)) return "No advisory data for $crop.";
    $p = ["$crop advisory."];
    foreach ($adv as $f => $d) { if (is_string($d)) $p[] = ucfirst($f) . ": $d"; }
    return implode('. ', $p) . '.';
}

// ==========================================
// XML HELPERS
// ==========================================
function say($voice, $text) {
    return '<Say voice="' . esc($voice) . '">' . esc($text) . '</Say>';
}

function xml_response($actions) {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n<Response>\n";
    foreach ($actions as $a) echo "  $a\n";
    echo "</Response>\n";
    exit;
}

function xml_say($text, $voice = 'en-US') { xml_response([say($voice, $text)]); }

function esc($str) { return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8'); }
