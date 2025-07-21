<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Reorganizing Video Storage Structure ===\n";

// Define new organized video structure
$newVideoStructure = [
    'storage/app/videos' => [
        'generated' => 'Generated videos from TikTok/YouTube generator',
        'assets' => 'Video assets and source materials',
        'templates' => 'Video templates and backgrounds',
        'temp' => 'Temporary video processing files',
        'exports' => 'Final exported videos ready for upload',
    ]
];

echo "\n1. Current video structure analysis:\n";

// Analyze current structure
$currentVideoPaths = [
    'storage/app/video' => 'Current video folder',
    'storage/app/videos' => 'Empty videos folder',
];

foreach ($currentVideoPaths as $path => $description) {
    $fullPath = base_path($path);
    if (is_dir($fullPath)) {
        $items = glob($fullPath . '/*');
        $fileCount = 0;
        $dirCount = 0;
        $totalSize = 0;
        
        foreach ($items as $item) {
            if (is_file($item)) {
                $fileCount++;
                $totalSize += filesize($item);
            } elseif (is_dir($item)) {
                $dirCount++;
                // Count files in subdirectories
                $subFiles = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($item, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($subFiles as $subFile) {
                    if ($subFile->isFile()) {
                        $fileCount++;
                        $totalSize += $subFile->getSize();
                    }
                }
            }
        }
        
        echo "  {$path}: {$dirCount} dirs, {$fileCount} files, " . formatBytes($totalSize) . "\n";
        
        // List subdirectories
        $subdirs = glob($fullPath . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $subdirName = basename($subdir);
            $subFiles = glob($subdir . '/*');
            echo "    └── {$subdirName}/ (" . count($subFiles) . " items)\n";
        }
    } else {
        echo "  {$path}: Not found\n";
    }
}

echo "\n2. Creating new organized video structure...\n";

// Create new structure in storage/app/videos
$baseVideoPath = storage_path('app/videos');
foreach ($newVideoStructure['storage/app/videos'] as $subdir => $description) {
    $subdirPath = $baseVideoPath . '/' . $subdir;
    if (!is_dir($subdirPath)) {
        mkdir($subdirPath, 0755, true);
        echo "✅ Created: videos/{$subdir}/ - {$description}\n";
    } else {
        echo "✅ Exists: videos/{$subdir}/ - {$description}\n";
    }
}

echo "\n3. Migrating files from storage/app/video to storage/app/videos...\n";

$migrations = [
    'storage/app/video/generated' => 'storage/app/videos/generated',
    'storage/app/video/assets' => 'storage/app/videos/assets',
];

$totalMigrated = 0;
$totalSize = 0;

foreach ($migrations as $source => $destination) {
    $sourcePath = base_path($source);
    $destPath = base_path($destination);
    
    if (is_dir($sourcePath)) {
        echo "Migrating: {$source} → {$destination}\n";
        
        // Create destination if not exists
        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }
        
        // Move files
        $files = glob($sourcePath . '/*');
        foreach ($files as $file) {
            $fileName = basename($file);
            $destFile = $destPath . '/' . $fileName;
            
            if (is_file($file)) {
                if (!file_exists($destFile)) {
                    if (rename($file, $destFile)) {
                        $fileSize = filesize($destFile);
                        $totalSize += $fileSize;
                        $totalMigrated++;
                        echo "  ✅ Moved: {$fileName} (" . formatBytes($fileSize) . ")\n";
                    } else {
                        echo "  ❌ Failed to move: {$fileName}\n";
                    }
                } else {
                    echo "  ⚠️ Already exists: {$fileName}\n";
                }
            }
        }
        
        // Remove empty source directory
        if (is_dir($sourcePath) && count(glob($sourcePath . '/*')) === 0) {
            rmdir($sourcePath);
            echo "  🗑️ Removed empty directory: {$source}\n";
        }
    } else {
        echo "⚠️ Source not found: {$source}\n";
    }
}

echo "\nMigration summary: {$totalMigrated} files, " . formatBytes($totalSize) . "\n";

echo "\n4. Removing old video directory if empty...\n";
$oldVideoPath = base_path('storage/app/video');
if (is_dir($oldVideoPath)) {
    $remainingItems = glob($oldVideoPath . '/*');
    if (count($remainingItems) === 0) {
        rmdir($oldVideoPath);
        echo "✅ Removed empty directory: storage/app/video\n";
    } else {
        echo "⚠️ Directory not empty, keeping: storage/app/video\n";
        foreach ($remainingItems as $item) {
            echo "    Remaining: " . basename($item) . "\n";
        }
    }
}

echo "\n5. Updating configuration...\n";

// Update config/constants.php
$configPath = base_path('config/constants.php');
if (file_exists($configPath)) {
    $configContent = file_get_contents($configPath);
    $oldVideoPath = "'VIDEO' => 'storage/app/video/',";
    $newVideoPath = "'VIDEO' => 'storage/app/videos/',";
    
    if (str_contains($configContent, $oldVideoPath)) {
        $configContent = str_replace($oldVideoPath, $newVideoPath, $configContent);
        file_put_contents($configPath, $configContent);
        echo "✅ Updated config/constants.php\n";
    } else {
        echo "⚠️ Config already updated or pattern not found\n";
    }
}

echo "\n6. Checking database references...\n";

// Check for any database references to old video paths
$tables = ['chapters', 'stories', 'video_generations', 'scheduled_posts'];
$foundReferences = false;

foreach ($tables as $table) {
    try {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            $columns = DB::getSchemaBuilder()->getColumnListing($table);
            
            foreach ($columns as $column) {
                if (str_contains($column, 'video') || str_contains($column, 'path') || str_contains($column, 'file')) {
                    $records = DB::table($table)
                        ->whereNotNull($column)
                        ->where($column, 'LIKE', '%storage/app/video/%')
                        ->get();
                    
                    if ($records->count() > 0) {
                        echo "  Found {$records->count()} records in {$table}.{$column} with old video paths\n";
                        $foundReferences = true;
                        
                        // Update paths
                        foreach ($records as $record) {
                            $oldPath = $record->$column;
                            $newPath = str_replace('storage/app/video/', 'storage/app/videos/', $oldPath);
                            
                            DB::table($table)
                                ->where('id', $record->id)
                                ->update([$column => $newPath]);
                            
                            echo "    Updated: {$oldPath} → {$newPath}\n";
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "  ⚠️ Could not check table {$table}: " . $e->getMessage() . "\n";
    }
}

if (!$foundReferences) {
    echo "  ✅ No database references to old video paths found\n";
}

echo "\n7. Final structure verification...\n";

$finalStructure = storage_path('app/videos');
if (is_dir($finalStructure)) {
    echo "✅ New video structure created:\n";
    $subdirs = glob($finalStructure . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        $subdirName = basename($subdir);
        $fileCount = count(glob($subdir . '/*'));
        echo "  📁 videos/{$subdirName}/ ({$fileCount} items)\n";
    }
} else {
    echo "❌ New video structure not found\n";
}

echo "\n✅ Video structure reorganization completed!\n";
echo "\nNew organized structure:\n";
echo "📁 storage/app/videos/\n";
echo "  ├── generated/    (Generated videos from TikTok/YouTube)\n";
echo "  ├── assets/       (Video assets and source materials)\n";
echo "  ├── templates/    (Video templates and backgrounds)\n";
echo "  ├── temp/         (Temporary processing files)\n";
echo "  └── exports/      (Final videos ready for upload)\n";

// Helper function
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

?>
