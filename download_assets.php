<?php

echo "=== Downloading Local Assets ===\n";

// Assets to download
$assets = [
    // CSS Files
    'css/bootstrap-5.3.0.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'css/bootstrap-4.6.2.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
    'css/fontawesome-6.0.0.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'css/fontawesome-free.min.css' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css',
    'css/adminlte.min.css' => 'https://cdn.jsdelivr.net/npm/admin-lte@3/dist/css/adminlte.min.css',
    'css/select2.min.css' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    
    // JS Files
    'js/jquery-3.7.1.min.js' => 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'js/bootstrap-5.3.0.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'js/bootstrap-4.6.2.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
    'js/adminlte.min.js' => 'https://cdn.jsdelivr.net/npm/admin-lte@3/dist/js/adminlte.min.js',
    'js/select2.min.js' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
];

// Create directories
$directories = ['public/assets/css', 'public/assets/js', 'public/assets/fonts'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: {$dir}\n";
    }
}

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
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false) {
        file_put_contents($destination, $data);
        echo "✅ Downloaded successfully\n";
        return true;
    } else {
        echo "❌ Failed to download (HTTP: {$httpCode})\n";
        return false;
    }
}

// Download all assets
$successCount = 0;
$totalCount = count($assets);

foreach ($assets as $localPath => $url) {
    $destination = "public/assets/{$localPath}";
    
    // Create directory if not exists
    $dir = dirname($destination);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (downloadFile($url, $destination)) {
        $successCount++;
    }
    
    echo "---\n";
}

echo "\n=== Download Summary ===\n";
echo "Successfully downloaded: {$successCount}/{$totalCount} files\n";

// Download FontAwesome fonts
echo "\n=== Downloading FontAwesome Fonts ===\n";
$fontAwesomeUrls = [
    'fonts/fa-solid-900.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2',
    'fonts/fa-regular-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-regular-400.woff2',
    'fonts/fa-brands-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-brands-400.woff2',
];

foreach ($fontAwesomeUrls as $localPath => $url) {
    $destination = "public/assets/{$localPath}";
    downloadFile($url, $destination);
    echo "---\n";
}

echo "\n✅ Asset download completed!\n";
echo "\nNext steps:\n";
echo "1. Update layout files to use local assets\n";
echo "2. Test offline functionality\n";
echo "3. Consider using Laravel Mix for asset compilation\n";

?>
