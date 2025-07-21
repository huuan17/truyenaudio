<?php

echo "=== Font Awesome Webfonts Setup ===\n";

$webfontsDir = 'public/assets/webfonts/';

// Create directory if not exists
if (!is_dir($webfontsDir)) {
    mkdir($webfontsDir, 0755, true);
    echo "✅ Created webfonts directory: {$webfontsDir}\n";
}

// Font Awesome 6.4.0 webfonts - using jsDelivr CDN
$webfonts = [
    'fa-solid-900.woff2' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-solid-900.woff2',
    'fa-solid-900.woff' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-solid-900.woff',
    'fa-solid-900.ttf' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-solid-900.ttf',
    'fa-regular-400.woff2' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-regular-400.woff2',
    'fa-regular-400.woff' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-regular-400.woff',
    'fa-regular-400.ttf' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-regular-400.ttf',
    'fa-brands-400.woff2' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-brands-400.woff2',
    'fa-brands-400.woff' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-brands-400.woff',
    'fa-brands-400.ttf' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/fa-brands-400.ttf',
];

$downloadedCount = 0;
$totalSize = 0;
$errors = [];

foreach ($webfonts as $filename => $url) {
    $filePath = $webfontsDir . $filename;
    
    if (file_exists($filePath)) {
        $size = round(filesize($filePath) / 1024, 2);
        echo "✅ Already exists: {$filename} ({$size} KB)\n";
        $totalSize += filesize($filePath);
        continue;
    }
    
    echo "📥 Downloading: {$filename}...\n";
    
    // Use file_get_contents with context for simple download
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: */*',
            ],
            'timeout' => 30,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    
    if ($data !== false && strlen($data) > 0) {
        file_put_contents($filePath, $data);
        $size = round(filesize($filePath) / 1024, 2);
        echo "  ✅ Downloaded: {$filename} ({$size} KB)\n";
        $downloadedCount++;
        $totalSize += filesize($filePath);
    } else {
        $error = error_get_last();
        $errorMsg = $error ? $error['message'] : 'Unknown error';
        echo "  ❌ Failed: {$filename} - {$errorMsg}\n";
        $errors[] = $filename;
    }
}

echo "\n📊 Download Summary:\n";
echo "  Downloaded: {$downloadedCount} files\n";
echo "  Failed: " . count($errors) . " files\n";
echo "  Total size: " . round($totalSize / 1024 / 1024, 2) . " MB\n";

if (!empty($errors)) {
    echo "\n❌ Failed downloads:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

// Verify webfonts directory
echo "\n📁 Webfonts Directory Contents:\n";
$files = glob($webfontsDir . '*');
if (empty($files)) {
    echo "  ⚠️ No files found in webfonts directory\n";
} else {
    foreach ($files as $file) {
        if (is_file($file)) {
            $filename = basename($file);
            $size = round(filesize($file) / 1024, 2);
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            echo "  📄 {$filename} ({$size} KB) - {$ext}\n";
        }
    }
}

// Test Font Awesome CSS path
echo "\n🔍 Font Awesome CSS Check:\n";
$cssFile = 'public/assets/css/fontawesome-6.4.0-all.min.css';
if (file_exists($cssFile)) {
    $size = round(filesize($cssFile) / 1024, 2);
    echo "  ✅ CSS file exists: {$cssFile} ({$size} KB)\n";
    
    // Check if CSS references webfonts correctly
    $cssContent = file_get_contents($cssFile);
    if (strpos($cssContent, '../webfonts/') !== false) {
        echo "  ✅ CSS references webfonts correctly\n";
    } else {
        echo "  ⚠️ CSS may not reference webfonts correctly\n";
    }
} else {
    echo "  ❌ CSS file not found: {$cssFile}\n";
}

// Test fallback CSS
echo "\n🎨 Icon Fallback CSS Check:\n";
$fallbackCss = 'public/assets/css/icon-fallback.css';
if (file_exists($fallbackCss)) {
    $size = round(filesize($fallbackCss) / 1024, 2);
    echo "  ✅ Fallback CSS exists: {$fallbackCss} ({$size} KB)\n";
} else {
    echo "  ❌ Fallback CSS not found: {$fallbackCss}\n";
}

echo "\n✅ Font Awesome webfonts setup completed!\n";

if ($downloadedCount > 0) {
    echo "\n🎉 Success! Icons should now display properly.\n";
    echo "📝 Next steps:\n";
    echo "  1. Clear browser cache\n";
    echo "  2. Test frontend: http://localhost:8000\n";
    echo "  3. Test admin: http://localhost:8000/admin\n";
} else if (count($errors) > 0) {
    echo "\n⚠️ Some downloads failed. You can:\n";
    echo "  1. Try running this script again\n";
    echo "  2. Download fonts manually from https://fontawesome.com\n";
    echo "  3. Use the emoji fallback icons (already configured)\n";
}

echo "\n📋 Font files needed:\n";
foreach ($webfonts as $filename => $url) {
    $status = file_exists($webfontsDir . $filename) ? '✅' : '❌';
    echo "  {$status} {$filename}\n";
}

?>
