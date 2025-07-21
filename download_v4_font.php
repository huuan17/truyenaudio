<?php

echo "=== Downloading FontAwesome v4 Compatibility Font ===\n";

$v4Font = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-v4compatibility.woff2';
$destination = 'public/assets/fonts/fa-v4compatibility.woff2';

function downloadFile($url, $destination) {
    echo "Downloading: {$url}\n";
    echo "To: {$destination}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false) {
        file_put_contents($destination, $data);
        $size = round(strlen($data) / 1024, 2);
        echo "✅ Downloaded successfully ({$size} KB)\n";
        return true;
    } else {
        echo "❌ Failed to download (HTTP: {$httpCode})\n";
        return false;
    }
}

if (!file_exists($destination)) {
    downloadFile($v4Font, $destination);
} else {
    echo "✅ Font already exists: {$destination}\n";
}

echo "\n✅ FontAwesome setup is now 100% complete!\n";

?>
