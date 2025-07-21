<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

function clearScreen() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function getProcessInfo() {
    // Check if crawl command is running
    $processes = [];
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>NUL');
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (strpos($line, 'php.exe') !== false) {
                    $processes[] = $line;
                }
            }
        }
    } else {
        $output = shell_exec('ps aux | grep "crawl:stories" | grep -v grep');
        if ($output) {
            $processes = explode("\n", trim($output));
        }
    }
    return $processes;
}

echo "=== Crawl Monitoring Dashboard ===\n";
echo "Press Ctrl+C to exit\n\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

$startTime = time();
$lastFileCount = 0;
$lastDbCount = 0;

while (true) {
    clearScreen();
    
    // Refresh story data
    $story = $story->fresh();
    
    echo "=== Crawl Monitoring Dashboard ===\n";
    echo "Story: {$story->title} (ID: {$story->id})\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Running for: " . gmdate('H:i:s', time() - $startTime) . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // 1. Story Status
    $statusLabels = config('constants.CRAWL_STATUS.LABELS');
    $statusColors = config('constants.CRAWL_STATUS.COLORS');
    $statusLabel = $statusLabels[$story->crawl_status] ?? 'Unknown';
    
    echo "📊 STORY STATUS:\n";
    echo "  Status: {$statusLabel} ({$story->crawl_status})\n";
    echo "  Range: {$story->start_chapter} - {$story->end_chapter} chapters\n";
    echo "  Expected Total: " . ($story->end_chapter - $story->start_chapter + 1) . " chapters\n\n";
    
    // 2. File System Status
    $contentDir = storage_path('app/content/' . $story->folder_name);
    echo "📁 FILE SYSTEM:\n";
    echo "  Directory: {$contentDir}\n";
    
    if (is_dir($contentDir)) {
        $files = glob($contentDir . '/*.txt');
        $fileCount = count($files);
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        echo "  ✅ Files: {$fileCount} (" . formatBytes($totalSize) . ")\n";
        
        if ($fileCount > $lastFileCount) {
            $newFiles = $fileCount - $lastFileCount;
            echo "  📈 New files since last check: +{$newFiles}\n";
        }
        
        if ($fileCount > 0) {
            // Get latest files
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $latestFile = basename($files[0]);
            $latestTime = date('H:i:s', filemtime($files[0]));
            echo "  📄 Latest: {$latestFile} (created: {$latestTime})\n";
            
            // Calculate progress
            $progress = ($fileCount / ($story->end_chapter - $story->start_chapter + 1)) * 100;
            $progressBar = str_repeat('█', (int)($progress / 2)) . str_repeat('░', 50 - (int)($progress / 2));
            echo "  📊 Progress: [{$progressBar}] " . number_format($progress, 2) . "%\n";
        }
        
        $lastFileCount = $fileCount;
    } else {
        echo "  ❌ Directory not found\n";
    }
    
    echo "\n";
    
    // 3. Database Status
    echo "🗄️ DATABASE:\n";
    $chapters = Chapter::where('story_id', $story->id)->get();
    $dbCount = count($chapters);
    $crawledCount = $chapters->where('is_crawled', true)->count();
    
    echo "  Total chapters: {$dbCount}\n";
    echo "  Crawled chapters: {$crawledCount}\n";
    
    if ($dbCount > $lastDbCount) {
        $newChapters = $dbCount - $lastDbCount;
        echo "  📈 New chapters since last check: +{$newChapters}\n";
    }
    
    if ($dbCount > 0) {
        $latestChapter = $chapters->sortByDesc('created_at')->first();
        echo "  📄 Latest: Chapter {$latestChapter->chapter_number} ({$latestChapter->created_at->format('H:i:s')})\n";
    }
    
    $lastDbCount = $dbCount;
    echo "\n";
    
    // 4. Process Status
    echo "⚙️ PROCESS STATUS:\n";
    $processes = getProcessInfo();
    if (count($processes) > 0) {
        echo "  ✅ PHP processes running: " . count($processes) . "\n";
        foreach (array_slice($processes, 0, 3) as $process) {
            echo "    - " . trim($process) . "\n";
        }
    } else {
        echo "  ⚠️ No crawl processes detected\n";
    }
    echo "\n";
    
    // 5. Performance Stats
    echo "📈 PERFORMANCE:\n";
    if ($fileCount > 0 && time() > $startTime) {
        $elapsed = time() - $startTime;
        $rate = $fileCount / $elapsed;
        $remaining = ($story->end_chapter - $story->start_chapter + 1) - $fileCount;
        $eta = $remaining / max($rate, 0.001);
        
        echo "  Rate: " . number_format($rate, 2) . " chapters/second\n";
        echo "  ETA: " . gmdate('H:i:s', $eta) . " remaining\n";
        echo "  Estimated completion: " . date('H:i:s', time() + $eta) . "\n";
    } else {
        echo "  Calculating...\n";
    }
    echo "\n";
    
    // 6. Quick Actions
    echo "🔧 QUICK ACTIONS:\n";
    echo "  [Ctrl+C] Exit monitoring\n";
    echo "  Manual check: php artisan crawl:stories --story_id=3\n";
    echo "  Reset story: Story::find(3)->update(['crawl_status' => 0])\n";
    echo "\n";
    
    // 7. Status Summary
    if ($story->crawl_status == 3) {
        echo "🟡 CRAWLING IN PROGRESS...\n";
    } elseif ($story->crawl_status == 1) {
        echo "🟢 CRAWL COMPLETED!\n";
    } elseif ($story->crawl_status == 2) {
        echo "🟠 NEEDS RE-CRAWL\n";
    } else {
        echo "⚪ NOT STARTED\n";
    }
    
    // Wait 5 seconds before next update
    sleep(5);
}

?>
