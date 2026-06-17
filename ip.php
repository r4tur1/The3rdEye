<?php
// The3rdEye v1.0 - Advanced IP & Device Fingerprinting

function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getDeviceInfo($userAgent) {
    $os = 'Unknown OS';
    $browser = 'Unknown Browser';
    $device = 'Desktop';
    
    // OS Detection
    if (preg_match('/linux/i', $userAgent)) $os = 'Linux';
    if (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
    if (preg_match('/windows|win32/i', $userAgent)) $os = 'Windows';
    if (preg_match('/android/i', $userAgent)) { $os = 'Android'; $device = 'Mobile'; }
    if (preg_match('/iphone|ipad|ipod/i', $userAgent)) { $os = 'iOS'; $device = 'Mobile'; }
    
    // Browser Detection
    if (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
    if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) $browser = 'Chrome';
    if (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) $browser = 'Safari';
    if (preg_match('/edg/i', $userAgent)) $browser = 'Edge';
    if (preg_match('/opera|opr/i', $userAgent)) $browser = 'Opera';
    
    return [
        'os' => $os,
        'browser' => $browser,
        'device' => $device
    ];
}

$ip = getRealIpAddr();
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct Access';
$acceptLang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'Unknown';
$deviceInfo = getDeviceInfo($userAgent);
$timestamp = date('Y-m-d H:i:s');

$data = "=== The3rdEye Capture ===\n";
$data .= "Timestamp: $timestamp\n";
$data .= "IP: $ip\n";
$data .= "OS: {$deviceInfo['os']}\n";
$data .= "Browser: {$deviceInfo['browser']}\n";
$data .= "Device Type: {$deviceInfo['device']}\n";
$data .= "User-Agent: $userAgent\n";
$data .= "Referer: $referer\n";
$data .= "Language: $acceptLang\n";
$data .= "==========================\n\n";

// Save to file
$fp = fopen('ip.txt', 'a');
fwrite($fp, $data);
fclose($fp);

// Also save user agent separately for the shell script
file_put_contents('user_agent.txt', "OS: {$deviceInfo['os']} | Browser: {$deviceInfo['browser']} | Device: {$deviceInfo['device']} | UA: $userAgent");

// Append to master log
$masterDir = $_SERVER['HOME'] . '/.the3rdeye';
if (!is_dir($masterDir)) mkdir($masterDir, 0755, true);
file_put_contents("$masterDir/saved_ips.txt", "$ip - $timestamp - {$deviceInfo['os']}/{$deviceInfo['browser']}\n", FILE_APPEND);
?>