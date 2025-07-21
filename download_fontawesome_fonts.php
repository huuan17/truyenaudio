<?php

echo "=== Font Awesome Webfonts Download ===\n";

// Font Awesome 6.4.0 webfonts URLs
$webfonts = [
    'fa-solid-900.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2',
    'fa-solid-900.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff',
    'fa-solid-900.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf',
    'fa-regular-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2',
    'fa-regular-400.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff',
    'fa-regular-400.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.ttf',
    'fa-brands-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2',
    'fa-brands-400.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff',
    'fa-brands-400.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.ttf',
];

$webfontsDir = 'public/assets/webfonts/';

// Create directory if not exists
if (!is_dir($webfontsDir)) {
    mkdir($webfontsDir, 0755, true);
    echo "✅ Created webfonts directory: {$webfontsDir}\n";
}

$downloadedCount = 0;
$totalSize = 0;

foreach ($webfonts as $filename => $url) {
    $filePath = $webfontsDir . $filename;
    
    if (file_exists($filePath)) {
        $size = round(filesize($filePath) / 1024, 2);
        echo "✅ Already exists: {$filename} ({$size} KB)\n";
        $totalSize += filesize($filePath);
        continue;
    }
    
    echo "📥 Downloading: {$filename}...\n";
    
    // Download with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false && empty($error)) {
        file_put_contents($filePath, $data);
        $size = round(filesize($filePath) / 1024, 2);
        echo "  ✅ Downloaded: {$filename} ({$size} KB)\n";
        $downloadedCount++;
        $totalSize += filesize($filePath);
    } else {
        echo "  ❌ Failed: {$filename} (HTTP: {$httpCode}, Error: {$error})\n";
    }
}

echo "\n📊 Download Summary:\n";
echo "  Downloaded: {$downloadedCount} files\n";
echo "  Total size: " . round($totalSize / 1024 / 1024, 2) . " MB\n";

// Verify webfonts directory
echo "\n📁 Webfonts Directory:\n";
$files = glob($webfontsDir . '*');
foreach ($files as $file) {
    $filename = basename($file);
    $size = round(filesize($file) / 1024, 2);
    echo "  📄 {$filename} ({$size} KB)\n";
}

echo "\n✅ Font Awesome webfonts setup completed!\n";

?>
