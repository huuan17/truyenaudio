<?php

echo "=== Downloading Complete FontAwesome CSS ===\n";

// Download complete FontAwesome CSS with all icon definitions
$fontAwesomeUrls = [
    'css/fontawesome-6.4.0-all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'css/fontawesome-6.0.0-all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'css/fontawesome-5.15.4-all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
];

// Download function
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

// Create directory
if (!is_dir('public/assets/css')) {
    mkdir('public/assets/css', 0755, true);
}

// Download FontAwesome CSS files
foreach ($fontAwesomeUrls as $localPath => $url) {
    $destination = "public/assets/{$localPath}";
    downloadFile($url, $destination);
    echo "---\n";
}

echo "\n=== Fixing Font Paths ===\n";

// Fix font paths in downloaded CSS files
$cssFiles = glob('public/assets/css/fontawesome-*.min.css');

foreach ($cssFiles as $cssFile) {
    echo "Processing: {$cssFile}\n";
    
    $content = file_get_contents($cssFile);
    
    // Replace CDN font URLs with local paths
    $patterns = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/',
        '../webfonts/',
        './webfonts/',
    ];
    
    foreach ($patterns as $pattern) {
        $content = str_replace($pattern, '../fonts/', $content);
    }
    
    // Additional pattern fixes
    $content = preg_replace('/url\(["\']?\.\.\/webfonts\//', 'url("../fonts/', $content);
    $content = preg_replace('/url\(["\']?webfonts\//', 'url("../fonts/', $content);
    
    file_put_contents($cssFile, $content);
    echo "✅ Fixed font paths in {$cssFile}\n";
}

echo "\n=== Downloading Additional Font Files ===\n";

// Download additional font files that might be missing
$additionalFonts = [
    'fonts/fa-solid-900.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff',
    'fonts/fa-regular-400.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff',
    'fonts/fa-brands-400.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff',
    'fonts/fa-solid-900.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf',
];

// Create fonts directory
if (!is_dir('public/assets/fonts')) {
    mkdir('public/assets/fonts', 0755, true);
}

foreach ($additionalFonts as $localPath => $url) {
    $destination = "public/assets/{$localPath}";
    if (!file_exists($destination)) {
        downloadFile($url, $destination);
    } else {
        echo "✅ Font already exists: {$destination}\n";
    }
    echo "---\n";
}

echo "\n✅ FontAwesome download completed!\n";
echo "\nFiles downloaded:\n";
$files = array_merge(glob('public/assets/css/fontawesome-*.css'), glob('public/assets/fonts/fa-*'));
foreach ($files as $file) {
    $size = round(filesize($file) / 1024, 2);
    echo "  - {$file} ({$size} KB)\n";
}

echo "\nNext: Update layout to use fontawesome-6.4.0-all.min.css\n";

?>
