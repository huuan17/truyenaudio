<?php

echo "=== Storage Structure Migration ===\n";

// Define new storage structure
$newStructure = [
    'content' => 'storage/app/content',      // Text files (.txt)
    'audio' => 'storage/app/audio',          // Audio files (.mp3)
    'video' => 'storage/app/video',          // Video files (.mp4)
    'images' => 'storage/app/images',        // Image files
    'temp' => 'storage/app/temp',            // Temporary files
];

// Create new directories
echo "\n1. Creating new directory structure...\n";
foreach ($newStructure as $type => $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✅ Created: {$path}\n";
    } else {
        echo "✅ Exists: {$path}\n";
    }
}

// Migration mapping
$migrations = [
    // Text content files
    'storage/truyen/tien-nghich' => 'storage/app/content/tien-nghich',
    'storage/truyen/co-nang-huyen-hoc' => 'storage/app/content/co-nang-huyen-hoc',
    
    // Audio files
    'storage/truyen/mp3-tien-nghich' => 'storage/app/audio/tien-nghich',
    'storage/truyen/mp3-co-nang-huyen-hoc' => 'storage/app/audio/co-nang-huyen-hoc',
    
    // Existing app folders (move to organized structure)
    'storage/app/truyen' => 'storage/app/content/app-backup',
    'storage/app/videos' => 'storage/app/video/generated',
    'storage/app/video_assets' => 'storage/app/video/assets',
    'storage/app/logos' => 'storage/app/images/logos',
];

echo "\n2. Migrating files...\n";
$totalFiles = 0;
$totalSize = 0;

foreach ($migrations as $source => $destination) {
    if (is_dir($source)) {
        echo "\n📁 Migrating: {$source} → {$destination}\n";
        
        // Create destination directory
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Count files and size
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $fileCount = 0;
        $dirSize = 0;
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $fileCount++;
                $dirSize += $file->getSize();
            }
        }
        
        $totalFiles += $fileCount;
        $totalSize += $dirSize;
        
        echo "   Files: {$fileCount}, Size: " . formatBytes($dirSize) . "\n";
        
        // Move files
        if ($fileCount > 0) {
            $result = moveDirectory($source, $destination);
            if ($result) {
                echo "   ✅ Migration completed\n";
            } else {
                echo "   ❌ Migration failed\n";
            }
        }
    } else {
        echo "⚠️ Source not found: {$source}\n";
    }
}

echo "\n3. Migration Summary:\n";
echo "Total files migrated: {$totalFiles}\n";
echo "Total size: " . formatBytes($totalSize) . "\n";

// Create symlinks for backward compatibility
echo "\n4. Creating backward compatibility symlinks...\n";
$symlinks = [
    'storage/truyen' => '../app/content',
    'public/storage/truyen' => '../../storage/app/content',
];

foreach ($symlinks as $link => $target) {
    if (!file_exists($link)) {
        $linkDir = dirname($link);
        if (!is_dir($linkDir)) {
            mkdir($linkDir, 0755, true);
        }
        
        if (symlink($target, $link)) {
            echo "✅ Created symlink: {$link} → {$target}\n";
        } else {
            echo "❌ Failed to create symlink: {$link}\n";
        }
    } else {
        echo "⚠️ Symlink already exists: {$link}\n";
    }
}

// Generate path mapping for code updates
echo "\n5. Generating path mapping for code updates...\n";
$pathMapping = [
    // Old paths → New paths
    'storage_path(\'truyen\')' => 'storage_path(\'app/content\')',
    'storage/truyen/' => 'storage/app/content/',
    'storage\\truyen\\' => 'storage\\app\\content\\',
    'truyen/' => 'content/',
    'mp3-' => 'audio/',
    '/mp3/' => '/audio/',
    '\\mp3\\' => '\\audio\\',
];

echo "Path mapping generated:\n";
foreach ($pathMapping as $old => $new) {
    echo "  '{$old}' → '{$new}'\n";
}

echo "\n✅ Storage migration completed!\n";
echo "\nNext steps:\n";
echo "1. Update code to use new paths\n";
echo "2. Update database paths if needed\n";
echo "3. Test file access\n";
echo "4. Remove old directories after verification\n";

// Helper functions
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function moveDirectory($source, $destination) {
    try {
        // Use robocopy on Windows for better performance
        if (PHP_OS_FAMILY === 'Windows') {
            $source = str_replace('/', '\\', $source);
            $destination = str_replace('/', '\\', $destination);
            
            $command = "robocopy \"{$source}\" \"{$destination}\" /E /MOVE /R:3 /W:1";
            exec($command, $output, $returnCode);
            
            // Robocopy return codes: 0-7 are success
            return $returnCode <= 7;
        } else {
            // Use mv on Unix systems
            $command = "mv \"{$source}\" \"{$destination}\"";
            exec($command, $output, $returnCode);
            return $returnCode === 0;
        }
    } catch (Exception $e) {
        echo "Error moving directory: " . $e->getMessage() . "\n";
        return false;
    }
}

?>
