<?php
header('Content-Type: text/plain');
$config = require __DIR__ . '/../config.php';

function fetch_data($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $json = curl_exec($ch);
    curl_close($ch);
    if (!$json) return [];
    return json_decode($json, true) ?? [];
}

function ussd_response($message, $end = false) {
    $message = mb_substr($message, 0, 182);
    echo ($end ? "END " : "CON ") . $message;
    exit;
}

function trigger_update($farmId, $source = 'ussd', $crops = null) {
    global $config;
    $payload = ['farmId' => $farmId, 'source' => $source];
    if ($crops) $payload['crops'] = $crops;

    $ch = curl_init($config['ADVISORY_GENERATE_URL']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Agro-Secret: ' . $config['AGRO_SECRET']
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['response' => $response, 'httpCode' => $httpCode];
}

// ==========================================
// BILINGUAL SUPPORT (English / Kiswahili)
// ==========================================
function t($en, $sw) {
    global $L;
    return $L === 'sw' ? $sw : $en;
}

function coastal_menu($farm_name) {
    return "$farm_name:\n"
        . "0. " . t("Previous", "Rudi") . "\n"
        . "1. " . t("Aquaculture", "Ufugaji wa Majini") . "\n"
        . "2. " . t("Marine Fishing", "Uvuvi wa Baharini") . "\n"
        . "3. " . t("Carbon Credits", "Mikopo ya Kaboni") . "\n"
        . "4. " . t("Activity Logs", "Kumbukumbu") . "\n"
        . "5. " . t("Update All Data", "Sasisha Data") . "\n";
}

function soil_menu($farm_name) {
    return "$farm_name:\n"
        . "0. " . t("Previous", "Rudi") . "\n"
        . "1. " . t("Soil Health", "Afya ya Udongo") . "\n"
        . "2. " . t("Carbon Sequestration", "Uhifadhi wa Kaboni") . "\n"
        . "3. " . t("Supply Chain", "Msururu wa Ugavi") . "\n"
        . "4. " . t("Regenerative Practices", "Kilimo Endelevu") . "\n"
        . "5. " . t("Update All Data", "Sasisha Data") . "\n";
}

// Read USSD POST params
$sessionId   = $_POST['sessionId']   ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$serviceCode = $_POST['serviceCode'] ?? '';
$text        = trim($_POST['text']   ?? '');
$parts       = $text === '' ? [] : explode("*", $text);

// ==========================================
// STEP 0: Language Selection
// ==========================================
if (count($parts) === 0) {
    ussd_response("Welcome / Karibu\n1. English\n2. Kiswahili");
}

$L = (intval($parts[0]) === 2) ? 'sw' : 'en';

// ==========================================
// STEP 1: List farms
// ==========================================
$data = fetch_data($config['ADVISORY_API_URL']);

$farms = [];
foreach ($data as $key => $value) {
    if (isset($value['metadata']['farmName'])) {
        $farms[$key] = $value['metadata']['farmName'];
    }
}

if (count($parts) === 1) {
    if (empty($farms)) ussd_response(t("No farms found.", "Hakuna mashamba."), true);
    $menu = "== " . t("Farm Advisory", "Ushauri wa Shamba") . " ==\n"
          . t("Select farm:", "Chagua shamba:") . "\n";
    $i = 1;
    foreach ($farms as $key => $name) {
        $menu .= "$i. $name\n";
        $i++;
    }
    ussd_response($menu);
}

// --- Get farm selection (parts[1]) ---
$farm_index = intval($parts[1]) - 1;
$farm_keys  = array_keys($farms);
if (!isset($farm_keys[$farm_index])) {
    ussd_response(t("Invalid farm selection.", "Chaguo batili."), true);
}

$farm_key  = $farm_keys[$farm_index];
$farm_name = $farms[$farm_key];
$farm_data = $data[$farm_key];
$advisory  = $farm_data['advisory'] ?? [];

// --- Detect farm type ---
$aqua_species = ['Seaweed', 'Oyster', 'Oysters', 'Crab', 'Mud Crab',
                 'Sea Cucumber', 'Shrimp', 'Lobster', 'Milkfish'];

$is_coastal_farm = isset($farm_data['fisheries']);
if (!$is_coastal_farm) {
    foreach ($advisory as $crop => $adv) {
        if ($crop === 'Marine' || in_array($crop, $aqua_species)) {
            $is_coastal_farm = true;
            break;
        }
    }
}

$is_soil_farm = false;
$soil_data_keys = ['soil_health', 'carbon_sequestration', 'regenerative', 'supply_chain'];
foreach ($soil_data_keys as $key) {
    if (isset($farm_data[$key])) { $is_soil_farm = true; break; }
}
if (!$is_soil_farm) {
    $soil_advisory_keys = ['Soil Health', 'Carbon', 'Supply Chain', 'Regenerative'];
    foreach ($advisory as $key => $adv) {
        if (in_array($key, $soil_advisory_keys)) { $is_soil_farm = true; break; }
    }
}

$aqua_crops = [];
foreach ($advisory as $crop => $adv) {
    if ($crop !== 'Marine' && in_array($crop, $aqua_species)) {
        $aqua_crops[] = $crop;
    }
}

// ==========================================
// STEP 2: Main menu (parts[2])
// ==========================================
if (count($parts) === 2) {
    if ($is_coastal_farm) {
        ussd_response(coastal_menu($farm_name));
    } elseif ($is_soil_farm) {
        ussd_response(soil_menu($farm_name));
    } else {
        if (empty($advisory)) ussd_response(t("No advisory data available.", "Hakuna ushauri."), true);
        $menu = "$farm_name " . t("Advisory", "Ushauri") . ":\n"
              . "0. " . t("Previous", "Rudi") . "\n";
        $i = 1;
        foreach ($advisory as $crop => $adv) {
            $menu .= "$i. $crop\n";
            $i++;
        }
        $menu .= "$i. " . t("Update Advisories", "Sasisha Ushauri") . "\n";
        ussd_response($menu);
    }
}

// --- Back to farm list ---
if (count($parts) === 3 && $parts[2] === '0') {
    $menu = "== " . t("Farm Advisory", "Ushauri wa Shamba") . " ==\n"
          . t("Select farm:", "Chagua shamba:") . "\n";
    $i = 1;
    foreach ($farms as $key => $name) { $menu .= "$i. $name\n"; $i++; }
    ussd_response($menu);
}

// ==========================================
// COASTAL — Step 2 selections (parts[2])
// ==========================================
if ($is_coastal_farm && count($parts) === 3) {
    $sel = intval($parts[2]);
    $back = "\n\n0. " . t("Previous", "Rudi");

    if ($sel === 1) {
        if (empty($aqua_crops)) ussd_response(t("No aquaculture species.", "Hakuna spishi.") . $back, false);
        $menu = t("Aquaculture Species:", "Spishi za Ufugaji:") . "\n0. " . t("Previous", "Rudi") . "\n";
        $i = 1;
        foreach ($aqua_crops as $crop) { $menu .= "$i. $crop\n"; $i++; }
        $menu .= "$i. " . t("Update Aquaculture", "Sasisha Ufugaji") . "\n";
        ussd_response($menu);
    }

    if ($sel === 2) {
        $marine = $advisory['Marine']['full_response'] ?? $advisory['Marine']['dashboard_advice'] ?? null;
        if (!$marine) {
            ussd_response(t("No fishing advisory.", "Hakuna ushauri wa uvuvi.") . "\n\n1. " . t("Update Marine", "Sasisha Uvuvi") . "\n0. " . t("Previous", "Rudi"), false);
        }
        $menu = t("Marine Fishing:", "Uvuvi wa Baharini:") . "\n0. " . t("Previous", "Rudi") . "\n";
        $menu .= "1. " . t("Location", "Mahali") . "\n";
        $menu .= "2. " . t("Primary Zone", "Eneo Kuu") . "\n";
        $menu .= "3. " . t("Surface Strategy", "Mkakati wa Juu") . "\n";
        $menu .= "4. " . t("Deep Strategy", "Mkakati wa Kina") . "\n";
        $menu .= "5. " . t("Reef Strategy", "Mkakati wa Miamba") . "\n";
        $menu .= "6. " . t("Safety Status", "Hali ya Usalama") . "\n";
        $menu .= "7. " . t("Captain's Note", "Ujumbe wa Nahodha") . "\n";
        $menu .= "8. " . t("Update Marine", "Sasisha Uvuvi") . "\n";
        ussd_response($menu);
    }

    if ($sel === 3) ussd_response(t("Carbon Credits:", "Mikopo ya Kaboni:") . "\n" . t("Coming soon - Track seaweed CO2.", "Inakuja hivi karibuni.") . $back, false);

    if ($sel === 4) {
        ussd_response(t("Activity Logs:", "Kumbukumbu:") . "\n0. " . t("Previous", "Rudi") . "\n1. " . t("Log Harvest", "Andika Mavuno") . "\n2. " . t("Log Catch", "Andika Samaki") . "\n3. " . t("View Recent", "Angalia Hivi Karibuni") . "\n");
    }

    if ($sel === 5) {
        $result = trigger_update($farm_key, 'ussd');
        if ($result['httpCode'] === 200) {
            $decoded = json_decode($result['response'], true);
            $gen = $decoded['crops_generating'] ?? 0;
            ussd_response(t("Updating $gen advisories!\nCheck back in 2 min.", "Inasasisha ushauri $gen!\nRudi baada ya dakika 2."), true);
        }
        ussd_response(t("Update failed. Try again.", "Imeshindwa. Jaribu tena."), true);
    }
}

// ==========================================
// COASTAL — Aquaculture species → factors (parts[3])
// ==========================================
if ($is_coastal_farm && count($parts) === 4 && intval($parts[2]) === 1) {
    $sel = intval($parts[3]);
    if ($sel === 0) ussd_response(coastal_menu($farm_name));

    if ($sel === count($aqua_crops) + 1) {
        $result = trigger_update($farm_key, 'ussd', $aqua_crops);
        if ($result['httpCode'] === 200) ussd_response(t("Updating aquaculture!\nCheck back in 2 min.", "Inasasisha ufugaji!\nRudi baada ya dakika 2."), true);
        ussd_response(t("Update failed. Try again.", "Imeshindwa. Jaribu tena."), true);
    }

    $idx = $sel - 1;
    if (!isset($aqua_crops[$idx])) ussd_response(t("Invalid selection.", "Chaguo batili."), true);
    $crop_name = $aqua_crops[$idx];
    $crop_adv = $advisory[$crop_name]['dashboard_advice'] ?? [];
    if (empty($crop_adv)) ussd_response(t("No advisory for $crop_name.", "Hakuna ushauri wa $crop_name.") . "\n\n0. " . t("Previous", "Rudi"), false);

    $menu = "$crop_name:\n0. " . t("Previous", "Rudi") . "\n";
    $i = 1;
    foreach ($crop_adv as $factor => $details) { $menu .= "$i. " . ucfirst($factor) . "\n"; $i++; }
    ussd_response($menu);
}

// ==========================================
// COASTAL — Aquaculture factor detail (parts[4])
// ==========================================
if ($is_coastal_farm && count($parts) === 5 && intval($parts[2]) === 1) {
    $sel = intval($parts[4]);
    $idx = intval($parts[3]) - 1;
    $crop_name = $aqua_crops[$idx] ?? null;
    $crop_adv = $crop_name ? ($advisory[$crop_name]['dashboard_advice'] ?? []) : [];

    if ($sel === 0) {
        $menu = "$crop_name:\n0. " . t("Previous", "Rudi") . "\n";
        $i = 1;
        foreach ($crop_adv as $factor => $details) { $menu .= "$i. " . ucfirst($factor) . "\n"; $i++; }
        ussd_response($menu);
    }

    $factor_keys = array_keys($crop_adv);
    $fidx = $sel - 1;
    if (!isset($factor_keys[$fidx])) ussd_response(t("Invalid selection.", "Chaguo batili."), true);
    ussd_response(ucfirst($factor_keys[$fidx]) . ":\n" . $crop_adv[$factor_keys[$fidx]] . "\n\n0. " . t("Previous", "Rudi"), false);
}

// --- COASTAL: Back from factor detail ---
if ($is_coastal_farm && count($parts) === 6 && intval($parts[2]) === 1 && $parts[5] === '0') {
    $idx = intval($parts[3]) - 1;
    $crop_name = $aqua_crops[$idx] ?? null;
    $crop_adv = $crop_name ? ($advisory[$crop_name]['dashboard_advice'] ?? []) : [];
    $menu = "$crop_name:\n0. " . t("Previous", "Rudi") . "\n";
    $i = 1;
    foreach ($crop_adv as $factor => $details) { $menu .= "$i. " . ucfirst($factor) . "\n"; $i++; }
    ussd_response($menu);
}

// ==========================================
// COASTAL — Marine Fishing drill-down (parts[3])
// ==========================================
if ($is_coastal_farm && count($parts) === 4 && intval($parts[2]) === 2) {
    $sel = intval($parts[3]);
    $marine = $advisory['Marine']['full_response'] ?? $advisory['Marine']['dashboard_advice'] ?? [];
    $back = "\n\n0. " . t("Previous", "Rudi");

    if ($sel === 0) ussd_response(coastal_menu($farm_name));
    if ($sel === 1) ussd_response(t("Location:", "Mahali:") . "\n" . ($marine['identified_location'] ?? $marine['headline'] ?? t('No data', 'Hakuna data')) . $back, false);
    if ($sel === 2) {
        $msg = t("Primary Zone Today:", "Eneo Kuu Leo:") . "\n" . ($marine['primary_zone_today'] ?? t('No data', 'Hakuna data'));
        $reason = $marine['geographic_reasoning'] ?? '';
        if ($reason) $msg .= "\n\n" . t("Why:", "Kwa nini:") . " $reason";
        ussd_response("$msg$back", false);
    }
    if ($sel === 3) ussd_response(t("Surface Strategy:", "Mkakati wa Juu:") . "\n" . ($marine['surface_strategy'] ?? $marine['water'] ?? t('No data', 'Hakuna data')) . $back, false);
    if ($sel === 4) ussd_response(t("Deep Strategy:", "Mkakati wa Kina:") . "\n" . ($marine['deep_strategy'] ?? $marine['pest'] ?? t('No data', 'Hakuna data')) . $back, false);
    if ($sel === 5) ussd_response(t("Reef Strategy:", "Mkakati wa Miamba:") . "\n" . ($marine['reef_strategy'] ?? $marine['microclimate'] ?? t('No data', 'Hakuna data')) . $back, false);
    if ($sel === 6) ussd_response(t("Safety Status:", "Hali ya Usalama:") . "\n" . ($marine['safety_status'] ?? 'UNKNOWN') . $back, false);
    if ($sel === 7) ussd_response(t("Captain's Note:", "Ujumbe wa Nahodha:") . "\n" . ($marine['captain_note'] ?? $marine['outlook'] ?? t('No data', 'Hakuna data')) . $back, false);
    if ($sel === 8) {
        $result = trigger_update($farm_key, 'ussd', ['Marine']);
        if ($result['httpCode'] === 200) ussd_response(t("Updating fishing advisory!\nCheck back in 2 min.", "Inasasisha ushauri wa uvuvi!\nRudi baada ya dakika 2."), true);
        ussd_response(t("Update failed. Try again.", "Imeshindwa. Jaribu tena."), true);
    }
}

// --- COASTAL: Back from Marine detail ---
if ($is_coastal_farm && count($parts) === 5 && intval($parts[2]) === 2 && $parts[4] === '0') {
    $menu = t("Marine Fishing:", "Uvuvi wa Baharini:") . "\n0. " . t("Previous", "Rudi") . "\n";
    $menu .= "1. " . t("Location", "Mahali") . "\n2. " . t("Primary Zone", "Eneo Kuu") . "\n";
    $menu .= "3. " . t("Surface Strategy", "Mkakati wa Juu") . "\n4. " . t("Deep Strategy", "Mkakati wa Kina") . "\n";
    $menu .= "5. " . t("Reef Strategy", "Mkakati wa Miamba") . "\n6. " . t("Safety Status", "Hali ya Usalama") . "\n";
    $menu .= "7. " . t("Captain's Note", "Ujumbe wa Nahodha") . "\n8. " . t("Update Marine", "Sasisha Uvuvi") . "\n";
    ussd_response($menu);
}

// --- COASTAL: Carbon/Logs back handlers ---
if ($is_coastal_farm && count($parts) === 4 && intval($parts[2]) === 3 && $parts[3] === '0') ussd_response(coastal_menu($farm_name));

if ($is_coastal_farm && count($parts) === 4 && intval($parts[2]) === 4) {
    $sel = intval($parts[3]);
    $back = "\n\n0. " . t("Previous", "Rudi");
    if ($sel === 0) ussd_response(coastal_menu($farm_name));
    if ($sel === 1) ussd_response(t("Log Harvest:", "Andika Mavuno:") . "\n" . t("Coming soon.", "Inakuja hivi karibuni.") . $back, false);
    if ($sel === 2) ussd_response(t("Log Catch:", "Andika Samaki:") . "\n" . t("Coming soon.", "Inakuja hivi karibuni.") . $back, false);
    if ($sel === 3) ussd_response(t("View Recent:", "Angalia Hivi Karibuni:") . "\n" . t("No logs yet.", "Hakuna kumbukumbu bado.") . $back, false);
}

if ($is_coastal_farm && count($parts) === 5 && intval($parts[2]) === 4 && $parts[4] === '0') {
    ussd_response(t("Activity Logs:", "Kumbukumbu:") . "\n0. " . t("Previous", "Rudi") . "\n1. " . t("Log Harvest", "Andika Mavuno") . "\n2. " . t("Log Catch", "Andika Samaki") . "\n3. " . t("View Recent", "Angalia Hivi Karibuni") . "\n");
}

// ==========================================
// SOIL FARM FLOW (parts[2])
// ==========================================
if ($is_soil_farm && count($parts) === 3) {
    $sel = intval($parts[2]);
    $back = "\n\n0. " . t("Previous", "Rudi");

    if ($sel === 0) {
        $menu = "== " . t("Farm Advisory", "Ushauri wa Shamba") . " ==\n" . t("Select farm:", "Chagua shamba:") . "\n";
        $i = 1;
        foreach ($farms as $key => $name) { $menu .= "$i. $name\n"; $i++; }
        ussd_response($menu);
    }
    if ($sel === 1) {
        $soil = $advisory['Soil Health']['dashboard_advice'] ?? [];
        if (empty($soil)) ussd_response(t("No soil data.", "Hakuna data ya udongo.") . $back, false);
        $menu = t("Soil Health:", "Afya ya Udongo:") . "\n0. " . t("Previous", "Rudi") . "\n";
        $i = 1;
        foreach ($soil as $metric => $value) { $menu .= "$i. " . ucfirst($metric) . "\n"; $i++; }
        ussd_response($menu);
    }
    if ($sel === 2) {
        $carbon = $advisory['Carbon']['dashboard_advice'] ?? [];
        if (empty($carbon)) ussd_response(t("No carbon data.", "Hakuna data ya kaboni.") . $back, false);
        $headline = $carbon['headline'] ?? t('No data.', 'Hakuna data.');
        $seq = $carbon['sequestered_tons'] ?? 'N/A';
        $cr = $carbon['carbon_credits'] ?? 'N/A';
        ussd_response(t("Carbon:", "Kaboni:") . "\n$headline\n\n" . t("Sequestered:", "Imehifadhiwa:") . " $seq " . t("tons", "tani") . "\n" . t("Credits:", "Mikopo:") . " $cr$back", false);
    }
    if ($sel === 3) {
        $supply = $advisory['Supply Chain']['dashboard_advice'] ?? [];
        $status = $supply['dpp_status'] ?? t('Not verified', 'Haijathibitishwa');
        $origin = $supply['origin_verified'] ?? t('No', 'Hapana');
        ussd_response(t("Supply Chain:", "Msururu wa Ugavi:") . "\nEU DPP: $status\n" . t("Origin:", "Asili:") . " $origin$back", false);
    }
    if ($sel === 4) {
        $regen = $advisory['Regenerative']['dashboard_advice'] ?? [];
        $headline = $regen['headline'] ?? t('No data.', 'Hakuna data.');
        ussd_response(t("Regenerative:", "Kilimo Endelevu:") . "\n$headline$back", false);
    }
    if ($sel === 5) {
        $result = trigger_update($farm_key, 'ussd');
        if ($result['httpCode'] === 200) {
            $decoded = json_decode($result['response'], true);
            $gen = $decoded['crops_generating'] ?? 0;
            ussd_response(t("Updating $gen advisories!\nCheck back in 2 min.", "Inasasisha ushauri $gen!\nRudi baada ya dakika 2."), true);
        }
        ussd_response(t("Update failed. Try again.", "Imeshindwa. Jaribu tena."), true);
    }
}

if ($is_soil_farm && count($parts) === 4 && $parts[3] === '0') ussd_response(soil_menu($farm_name));

// ==========================================
// TERRESTRIAL FARM FLOW
// ==========================================
if (!$is_coastal_farm && !$is_soil_farm && count($parts) === 3) {
    $sel = intval($parts[2]);
    $crop_count = count($advisory);

    if ($sel === $crop_count + 1) {
        $result = trigger_update($farm_key);
        if ($result['httpCode'] === 200) {
            $decoded = json_decode($result['response'], true);
            $gen = $decoded['crops_generating'] ?? 0;
            $upd = $decoded['crops_updated'] ?? 0;
            $total = $upd + $gen;
            if ($total > 0) ussd_response(t("Updating $total crops!\nCheck back in 2 min.", "Inasasisha mazao $total!\nRudi baada ya dakika 2."), true);
            ussd_response(t("No crops found.", "Hakuna mazao."), true);
        }
        ussd_response(t("Update failed. Try again.", "Imeshindwa. Jaribu tena."), true);
    }
}

if (!$is_coastal_farm && !$is_soil_farm) {
    $crop_index = intval($parts[2] ?? 0) - 1;
    $crop_keys = array_keys($advisory);
    $crop_name = $crop_keys[$crop_index] ?? null;
    $crop_adv = $crop_name ? ($advisory[$crop_name]['dashboard_advice'] ?? []) : [];
}

if (!$is_coastal_farm && !$is_soil_farm && count($parts) === 3 && $crop_name) {
    if (empty($crop_adv)) ussd_response(t("No factors available.", "Hakuna data."), true);
    $menu = "$farm_name - $crop_name:\n0. " . t("Previous", "Rudi") . "\n";
    $i = 1;
    foreach ($crop_adv as $factor => $details) { $menu .= "$i. " . ucfirst($factor) . "\n"; $i++; }
    ussd_response($menu);
}

if (!$is_coastal_farm && !$is_soil_farm && count($parts) === 4 && $parts[3] === '0') {
    $menu = "$farm_name " . t("Advisory", "Ushauri") . ":\n0. " . t("Previous", "Rudi") . "\n";
    $i = 1;
    foreach ($advisory as $crop => $adv) { $menu .= "$i. $crop\n"; $i++; }
    $menu .= "$i. " . t("Update Advisories", "Sasisha Ushauri") . "\n";
    ussd_response($menu);
}

if (!$is_coastal_farm && !$is_soil_farm && count($parts) === 4 && $crop_name) {
    $factor_index = intval($parts[3]) - 1;
    $factor_keys = array_keys($crop_adv);
    if (!isset($factor_keys[$factor_index])) ussd_response(t("Invalid selection.", "Chaguo batili."), true);
    $factor_name = $factor_keys[$factor_index];
    ussd_response(ucfirst($factor_name) . ":\n" . $crop_adv[$factor_name] . "\n\n0. " . t("Previous", "Rudi"), false);
}

if (!$is_coastal_farm && !$is_soil_farm && count($parts) === 5 && $parts[4] === '0' && $crop_name) {
    $menu = "$farm_name - $crop_name:\n0. " . t("Previous", "Rudi") . "\n";
    $i = 1;
    foreach ($crop_adv as $factor => $details) { $menu .= "$i. " . ucfirst($factor) . "\n"; $i++; }
    ussd_response($menu);
}

// Catch-all — unexpected input or unhandled state
ussd_response(t("Session error. Please try again.", "Hitilafu. Jaribu tena."), true);
