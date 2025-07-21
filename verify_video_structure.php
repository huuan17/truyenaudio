<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Video Structure Verification ===\n";

// 1. Verify new video structure
echo "\n1. Video Directory Structure:\n";
$videoBasePath = storage_path('app/videos');

$expectedSubdirs = [
    'generated' => 'Generated videos from TikTok/YouTube generator',
    'assets' => 'Video assets and source materials',
    'templates' => 'Video templates and backgrounds',
    'temp' => 'Temporary video processing files',
    'exports' => 'Final exported videos ready for upload',
];

if (is_dir($videoBasePath)) {
    echo "✅ Base video directory exists: {$videoBasePath}\n";
    
    foreach ($expectedSubdirs as $subdir => $description) {
        $subdirPath = $videoBasePath . '/' . $subdir;
        if (is_dir($subdirPath)) {
            $fileCount = count(glob($subdirPath . '/*'));
            echo "  ✅ {$subdir}/ - {$description} ({$fileCount} items)\n";
        } else {
            echo "  ❌ {$subdir}/ - Missing\n";
        }
    }
} else {
    echo "❌ Base video directory not found: {$videoBasePath}\n";
}

// 2. Check for old video directories
echo "\n2. Old Directory Cleanup:\n";
$oldPaths = [
    'storage/app/video' => 'Old video directory',
    'storage/app/video_assets' => 'Old video assets directory',
];

foreach ($oldPaths as $path => $description) {
    $fullPath = base_path($path);
    if (is_dir($fullPath)) {
        echo "  ⚠️ {$path} still exists - {$description}\n";
        $items = glob($fullPath . '/*');
        echo "    Contains " . count($items) . " items\n";
    } else {
        echo "  ✅ {$path} removed - {$description}\n";
    }
}

// 3. Verify configuration
echo "\n3. Configuration Verification:\n";
$videoPath = config('constants.STORAGE_PATHS.VIDEO');
echo "  VIDEO path: {$videoPath}\n";

if ($videoPath === 'storage/app/videos/') {
    echo "  ✅ Configuration updated to new path\n";
} else {
    echo "  ❌ Configuration not updated\n";
}

// 4. Test video service paths
echo "\n4. Video Service Path Testing:\n";

// Test temp directory creation
$testTempDir = storage_path('app/videos/temp/test_' . uniqid());
if (mkdir($testTempDir, 0755, true)) {
    echo "  ✅ Can create temp directories: {$testTempDir}\n";
    rmdir($testTempDir);
} else {
    echo "  ❌ Cannot create temp directories\n";
}

// Test assets directory
$assetsDir = storage_path('app/videos/assets');
if (is_writable($assetsDir)) {
    echo "  ✅ Assets directory is writable: {$assetsDir}\n";
} else {
    echo "  ❌ Assets directory not writable: {$assetsDir}\n";
}

// Test generated directory
$generatedDir = storage_path('app/videos/generated');
if (is_writable($generatedDir)) {
    echo "  ✅ Generated directory is writable: {$generatedDir}\n";
} else {
    echo "  ❌ Generated directory not writable: {$generatedDir}\n";
}

// 5. Check file migrations
echo "\n5. File Migration Status:\n";
$assetsPath = storage_path('app/videos/assets');
if (is_dir($assetsPath)) {
    $assetFiles = glob($assetsPath . '/*');
    echo "  Assets migrated: " . count($assetFiles) . " files\n";
    
    foreach ($assetFiles as $file) {
        $fileName = basename($file);
        $fileSize = filesize($file);
        echo "    - {$fileName} (" . formatBytes($fileSize) . ")\n";
    }
} else {
    echo "  ❌ Assets directory not found\n";
}

// 6. Test video generation paths
echo "\n6. Video Generation Path Testing:\n";

// Simulate VideoGenerationService paths
$platforms = ['tiktok', 'youtube'];
foreach ($platforms as $platform) {
    $tempId = uniqid();
    $expectedTempPath = storage_path("app/videos/temp/{$platform}_{$tempId}");
    echo "  {$platform} temp path: {$expectedTempPath}\n";
    
    if (mkdir($expectedTempPath, 0755, true)) {
        echo "    ✅ Can create platform temp directory\n";
        rmdir($expectedTempPath);
    } else {
        echo "    ❌ Cannot create platform temp directory\n";
    }
}

// Test final output path
$outputDir = storage_path('app/videos/generated');
$testOutputFile = $outputDir . '/test_video_' . time() . '.mp4';
if (touch($testOutputFile)) {
    echo "  ✅ Can create output files in generated directory\n";
    unlink($testOutputFile);
} else {
    echo "  ❌ Cannot create output files in generated directory\n";
}

// 7. Storage usage summary
echo "\n7. Storage Usage Summary:\n";
$totalSize = 0;
$totalFiles = 0;

foreach ($expectedSubdirs as $subdir => $description) {
    $subdirPath = $videoBasePath . '/' . $subdir;
    if (is_dir($subdirPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($subdirPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $subdirSize = 0;
        $subdirFiles = 0;
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $subdirSize += $file->getSize();
                $subdirFiles++;
            }
        }
        
        $totalSize += $subdirSize;
        $totalFiles += $subdirFiles;
        
        echo "  {$subdir}/: {$subdirFiles} files, " . formatBytes($subdirSize) . "\n";
    }
}

echo "  Total: {$totalFiles} files, " . formatBytes($totalSize) . "\n";

// 8. Recommendations
echo "\n8. Recommendations:\n";

if (is_dir(base_path('storage/app/video'))) {
    echo "  📝 Remove old storage/app/video directory after verification\n";
}

if (is_dir(base_path('storage/app/video_assets'))) {
    echo "  📝 Remove old storage/app/video_assets directory after verification\n";
}

echo "  📝 Monitor temp directory for cleanup (storage/app/videos/temp/)\n";
echo "  📝 Consider setting up automated cleanup for old temp files\n";
echo "  📝 Backup important video assets before major changes\n";

echo "\n✅ Video structure verification completed!\n";

echo "\nFinal organized structure:\n";
echo "📁 storage/app/videos/\n";
echo "  ├── generated/    ✅ Final output videos\n";
echo "  ├── assets/       ✅ Source materials and uploads\n";
echo "  ├── templates/    ✅ Reusable video templates\n";
echo "  ├── temp/         ✅ Processing workspace\n";
echo "  └── exports/      ✅ Ready-to-upload videos\n";

echo "\nPath usage in code:\n";
echo "- VideoGenerationService: storage_path('app/videos/temp/')\n";
echo "- GenerateUniversalVideoCommand: storage_path('app/videos/generated/')\n";
echo "- StoryController: storage_path('app/videos/assets/')\n";
echo "- Config: 'VIDEO' => 'storage/app/videos/'\n";

// Helper function
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

?>
