<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

echo "=== Debug Chapter Deletion Issue ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Story: {$story->title}\n";
echo "Folder: {$story->folder_name}\n\n";

// 1. Check database chapters
echo "1. DATABASE CHAPTERS:\n";
$chapters = Chapter::where('story_id', $story->id)
    ->orderBy('chapter_number')
    ->get();

echo "  Total chapters in DB: " . count($chapters) . "\n";

if (count($chapters) > 0) {
    $minChapter = $chapters->min('chapter_number');
    $maxChapter = $chapters->max('chapter_number');
    echo "  Chapter range in DB: {$minChapter} - {$maxChapter}\n";
    
    echo "  First 10 chapters in DB:\n";
    foreach ($chapters->take(10) as $chapter) {
        echo "    - Chapter {$chapter->chapter_number}: {$chapter->title}\n";
    }
    
    echo "  Missing chapters 1-40 in DB:\n";
    $existingNumbers = $chapters->pluck('chapter_number')->toArray();
    $missing = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!in_array($i, $existingNumbers)) {
            $missing[] = $i;
        }
    }
    
    if (count($missing) > 0) {
        echo "    Missing: " . implode(', ', array_slice($missing, 0, 20)) . 
             (count($missing) > 20 ? '... (' . count($missing) . ' total)' : '') . "\n";
    } else {
        echo "    ✅ Chapters 1-40 still exist in DB\n";
    }
}

// 2. Check storage files
echo "\n2. STORAGE FILES:\n";
$contentDir = storage_path('app/content/' . $story->folder_name);
echo "  Directory: {$contentDir}\n";

if (is_dir($contentDir)) {
    $files = glob($contentDir . '/chuong-*.txt');
    echo "  Total files in storage: " . count($files) . "\n";
    
    if (count($files) > 0) {
        // Extract chapter numbers from filenames
        $fileNumbers = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/chuong-(\d+)\.txt$/', $filename, $matches)) {
                $fileNumbers[] = (int)$matches[1];
            }
        }
        
        sort($fileNumbers);
        $minFile = min($fileNumbers);
        $maxFile = max($fileNumbers);
        echo "  File range in storage: {$minFile} - {$maxFile}\n";
        
        echo "  First 10 files in storage:\n";
        foreach (array_slice($fileNumbers, 0, 10) as $num) {
            $file = $contentDir . "/chuong-{$num}.txt";
            $size = round(filesize($file) / 1024, 2);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "    - chuong-{$num}.txt ({$size} KB, modified: {$modified})\n";
        }
        
        echo "  Files 1-40 in storage:\n";
        $files1to40 = [];
        for ($i = 1; $i <= 40; $i++) {
            if (in_array($i, $fileNumbers)) {
                $files1to40[] = $i;
            }
        }
        
        if (count($files1to40) > 0) {
            echo "    ❌ Still exist: " . implode(', ', array_slice($files1to40, 0, 20)) . 
                 (count($files1to40) > 20 ? '... (' . count($files1to40) . ' total)' : '') . "\n";
        } else {
            echo "    ✅ Files 1-40 have been deleted from storage\n";
        }
    }
} else {
    echo "  ❌ Directory not found\n";
}

// 3. Compare database vs storage
echo "\n3. COMPARISON:\n";
if (isset($existingNumbers) && isset($fileNumbers)) {
    $dbOnly = array_diff($existingNumbers, $fileNumbers);
    $filesOnly = array_diff($fileNumbers, $existingNumbers);
    
    echo "  Chapters in DB but not in files: " . count($dbOnly) . "\n";
    if (count($dbOnly) > 0) {
        echo "    " . implode(', ', array_slice($dbOnly, 0, 10)) . 
             (count($dbOnly) > 10 ? '...' : '') . "\n";
    }
    
    echo "  Files in storage but not in DB: " . count($filesOnly) . "\n";
    if (count($filesOnly) > 0) {
        echo "    " . implode(', ', array_slice($filesOnly, 0, 10)) . 
             (count($filesOnly) > 10 ? '...' : '') . "\n";
    }
    
    if (count($filesOnly) > 0) {
        echo "  ⚠️ ISSUE: Files exist without corresponding database records\n";
        echo "  💡 These files should be cleaned up\n";
    }
}

// 4. Check chapter deletion method
echo "\n4. CHAPTER DELETION ANALYSIS:\n";
echo "  Checking if Chapter model has file deletion logic...\n";

// Check if Chapter model has delete method that handles files
$chapterModel = new Chapter();
$reflection = new ReflectionClass($chapterModel);

if ($reflection->hasMethod('delete')) {
    echo "  ✅ Chapter model has delete method\n";
} else {
    echo "  ⚠️ Chapter model uses default delete method\n";
}

// Check for file_path field
$chapter = Chapter::where('story_id', $story->id)->first();
if ($chapter) {
    echo "  Chapter file_path example: " . ($chapter->file_path ?? 'NULL') . "\n";
    
    if ($chapter->file_path) {
        $fullPath = storage_path('app/' . $chapter->file_path);
        echo "  Full file path: {$fullPath}\n";
        echo "  File exists: " . (File::exists($fullPath) ? 'YES' : 'NO') . "\n";
    }
}

// 5. Recommendations
echo "\n5. RECOMMENDATIONS:\n";

if (isset($filesOnly) && count($filesOnly) > 0) {
    echo "  🔧 CLEANUP NEEDED:\n";
    echo "    - " . count($filesOnly) . " orphaned files need to be deleted\n";
    echo "    - Files exist without database records\n";
    echo "    - This happens when chapters are deleted from DB but files aren't cleaned up\n";
    
    echo "\n  📝 SOLUTIONS:\n";
    echo "    1. Manual cleanup: Delete orphaned files\n";
    echo "    2. Fix Chapter deletion to include file cleanup\n";
    echo "    3. Create cleanup command for orphaned files\n";
    
    echo "\n  🗑️ MANUAL CLEANUP COMMANDS:\n";
    echo "    Delete files 1-40:\n";
    for ($i = 1; $i <= min(10, count($files1to40)); $i++) {
        $num = $files1to40[$i-1];
        echo "      rm \"{$contentDir}/chuong-{$num}.txt\"\n";
    }
    if (count($files1to40) > 10) {
        echo "      ... and " . (count($files1to40) - 10) . " more files\n";
    }
}

echo "\n6. NEXT STEPS:\n";
echo "  - Check Chapter deletion controller/method\n";
echo "  - Implement file cleanup in Chapter deletion\n";
echo "  - Create orphaned file cleanup command\n";
echo "  - Test chapter deletion with file cleanup\n";

echo "\n✅ Debug completed!\n";

?>
