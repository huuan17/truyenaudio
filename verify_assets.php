<?php

echo "=== Verifying Local Assets ===\n";

$requiredAssets = [
    // CSS Files
    'public/assets/css/bootstrap-5.3.0.min.css',
    'public/assets/css/bootstrap-4.6.2.min.css',
    'public/assets/css/fontawesome-local.css',
    'public/assets/css/adminlte.min.css',
    'public/assets/css/select2.min.css',
    
    // JS Files
    'public/assets/js/jquery-3.7.1.min.js',
    'public/assets/js/bootstrap-5.3.0.bundle.min.js',
    'public/assets/js/bootstrap-4.6.2.bundle.min.js',
    'public/assets/js/adminlte.min.js',
    'public/assets/js/select2.min.js',
    
    // Font Files
    'public/assets/fonts/fa-solid-900.woff2',
    'public/assets/fonts/fa-regular-400.woff2',
    'public/assets/fonts/fa-brands-400.woff2',
];

$missingAssets = [];
$totalSize = 0;

foreach ($requiredAssets as $asset) {
    if (file_exists($asset)) {
        $size = filesize($asset);
        $sizeKB = round($size / 1024, 2);
        echo "✅ {$asset} ({$sizeKB} KB)\n";
        $totalSize += $size;
    } else {
        echo "❌ Missing: {$asset}\n";
        $missingAssets[] = $asset;
    }
}

echo "\n=== Summary ===\n";
echo "Total assets: " . count($requiredAssets) . "\n";
echo "Found: " . (count($requiredAssets) - count($missingAssets)) . "\n";
echo "Missing: " . count($missingAssets) . "\n";
echo "Total size: " . round($totalSize / 1024 / 1024, 2) . " MB\n";

if (empty($missingAssets)) {
    echo "\n✅ All assets are available locally!\n";
    echo "Your application can now work offline (except for Google Fonts)\n";
} else {
    echo "\n❌ Some assets are missing:\n";
    foreach ($missingAssets as $asset) {
        echo "  - {$asset}\n";
    }
}

// Check if layouts are updated
echo "\n=== Checking Layout Updates ===\n";

$adminLayout = file_get_contents('resources/views/layouts/app.blade.php');
$frontendLayout = file_get_contents('resources/views/layouts/frontend.blade.php');

$checks = [
    'Admin Layout - Local jQuery' => strpos($adminLayout, 'assets/js/jquery-3.7.1.min.js') !== false,
    'Admin Layout - Local Bootstrap JS' => strpos($adminLayout, 'assets/js/bootstrap-4.6.2.bundle.min.js') !== false,
    'Admin Layout - Local AdminLTE' => strpos($adminLayout, 'assets/css/adminlte.min.css') !== false,
    'Admin Layout - Local FontAwesome' => strpos($adminLayout, 'assets/css/fontawesome-local.css') !== false,
    'Frontend Layout - Local Bootstrap CSS' => strpos($frontendLayout, 'assets/css/bootstrap-5.3.0.min.css') !== false,
    'Frontend Layout - Local Bootstrap JS' => strpos($frontendLayout, 'assets/js/bootstrap-5.3.0.bundle.min.js') !== false,
];

foreach ($checks as $check => $result) {
    echo ($result ? "✅" : "❌") . " {$check}\n";
}

echo "\n=== Recommendations ===\n";
echo "1. Test your application with internet disconnected\n";
echo "2. Consider using Laravel Mix for asset compilation\n";
echo "3. Implement asset versioning for cache busting\n";
echo "4. Consider using a local font instead of Google Fonts for full offline support\n";

?>
