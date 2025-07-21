<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG CRAWL STATUS SYNCHRONIZATION ===\n";

// Test 1: Check current story status
echo "1. 📚 Current Story Status:\n";
try {
    $story = \App\Models\Story::find(5); // Thần đạo đan tôn
    
    if ($story) {
        echo "  Story details:\n";
        echo "    ID: {$story->id}\n";
        echo "    Title: {$story->title}\n";
        echo "    Crawl Status: {$story->crawl_status}\n";
        echo "    Job ID: {$story->crawl_job_id}\n";
        echo "    Updated: {$story->updated_at}\n";
        
        // Decode crawl status
        $statusLabels = [
            0 => 'NOT_CRAWLED',
            1 => 'PENDING', 
            2 => 'CRAWLED',
            3 => 'CRAWLING',
            4 => 'FAILED',
            5 => 'RE_CRAWL'
        ];
        
        $statusLabel = $statusLabels[$story->crawl_status] ?? 'UNKNOWN';
        echo "    Status Label: {$statusLabel}\n";
        
        // Check if status matches reality
        if ($story->crawl_status == 2) {
            echo "    ⚠️ Status shows CRAWLED but may still be crawling\n";
        } elseif ($story->crawl_status == 3) {
            echo "    ✅ Status shows CRAWLING (correct)\n";
        }
    } else {
        echo "  ❌ Story not found\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check active jobs
echo "\n2. 🔄 Active Jobs Check:\n";
try {
    // Check jobs table
    $activeJobs = \DB::table('jobs')->get();
    echo "  Total active jobs: " . $activeJobs->count() . "\n";
    
    if ($activeJobs->count() > 0) {
        foreach ($activeJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            
            echo "    Job ID: {$job->id}\n";
            echo "    Queue: {$job->queue}\n";
            echo "    Class: {$jobClass}\n";
            echo "    Attempts: {$job->attempts}\n";
            echo "    Created: {$job->created_at}\n";
            
            // Check if it's a crawl job
            if (strpos($jobClass, 'Crawl') !== false) {
                echo "    ✅ This is a crawl job\n";
                
                // Try to extract story ID from payload
                if (isset($payload['data']['commandData'])) {
                    $commandData = $payload['data']['commandData'];
                    if (is_string($commandData)) {
                        $commandData = unserialize($commandData);
                    }
                    if (isset($commandData[0])) {
                        echo "    Story ID in job: {$commandData[0]}\n";
                    }
                }
            }
            echo "    ---\n";
        }
    } else {
        echo "  ❌ No active jobs found\n";
    }
    
    // Check failed jobs
    $failedJobs = \DB::table('failed_jobs')->orderBy('failed_at', 'desc')->take(5)->get();
    echo "  Recent failed jobs: " . $failedJobs->count() . "\n";
    
    foreach ($failedJobs as $job) {
        echo "    Failed Job ID: {$job->id}\n";
        echo "    Failed at: {$job->failed_at}\n";
        echo "    Exception: " . substr($job->exception, 0, 100) . "...\n";
        echo "    ---\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking jobs: " . $e->getMessage() . "\n";
}

// Test 3: Check running processes
echo "\n3. 🖥️ Running Processes Check:\n";
try {
    // Check for Node.js processes
    $nodeProcesses = shell_exec('tasklist /FI "IMAGENAME eq node.exe" 2>nul') ?: '';
    if (strpos($nodeProcesses, 'node.exe') !== false) {
        echo "  ✅ Node.js processes found:\n";
        $lines = explode("\n", $nodeProcesses);
        foreach ($lines as $line) {
            if (strpos($line, 'node.exe') !== false) {
                echo "    {$line}\n";
            }
        }
    } else {
        echo "  ❌ No Node.js processes found\n";
    }
    
    // Check for PHP processes (artisan commands)
    $phpProcesses = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul') ?: '';
    if (strpos($phpProcesses, 'php.exe') !== false) {
        echo "  ✅ PHP processes found:\n";
        $lines = explode("\n", $phpProcesses);
        $phpCount = 0;
        foreach ($lines as $line) {
            if (strpos($line, 'php.exe') !== false) {
                $phpCount++;
                if ($phpCount <= 5) { // Show first 5 only
                    echo "    {$line}\n";
                }
            }
        }
        if ($phpCount > 5) {
            echo "    ... and " . ($phpCount - 5) . " more PHP processes\n";
        }
    } else {
        echo "  ❌ No PHP processes found\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking processes: " . $e->getMessage() . "\n";
}

// Test 4: Check recent logs
echo "\n4. 📋 Recent Crawl Logs:\n";
try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        
        // Get recent crawl-related logs
        $recentLogs = [];
        $currentTime = time();
        
        foreach (array_reverse($lines) as $line) {
            if (empty(trim($line))) continue;
            
            // Check if log is from today and crawl-related
            if (strpos($line, date('Y-m-d')) !== false && 
                (strpos($line, 'story ID: 5') !== false || 
                 strpos($line, 'Crawl') !== false ||
                 strpos($line, 'crawl') !== false)) {
                $recentLogs[] = $line;
                if (count($recentLogs) >= 10) break;
            }
        }
        
        if (!empty($recentLogs)) {
            echo "  Recent crawl logs (last 10):\n";
            foreach (array_reverse($recentLogs) as $log) {
                echo "    " . substr($log, 0, 150) . "...\n";
            }
        } else {
            echo "  ❌ No recent crawl logs found\n";
        }
    } else {
        echo "  ❌ Log file not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error reading logs: " . $e->getMessage() . "\n";
}

// Test 5: Check file creation activity
echo "\n5. 📁 File Creation Activity:\n";
try {
    $storageDir = storage_path('app/content/' . $story->folder_name);
    if (is_dir($storageDir)) {
        $txtFiles = glob($storageDir . '/*.txt');
        echo "  Total files: " . count($txtFiles) . "\n";
        
        // Check recent file modifications
        $recentFiles = [];
        $oneHourAgo = time() - 3600;
        
        foreach ($txtFiles as $file) {
            $mtime = filemtime($file);
            if ($mtime > $oneHourAgo) {
                $recentFiles[] = [
                    'file' => basename($file),
                    'size' => filesize($file),
                    'modified' => date('H:i:s', $mtime)
                ];
            }
        }
        
        if (!empty($recentFiles)) {
            echo "  ✅ Recently modified files (last hour):\n";
            usort($recentFiles, function($a, $b) {
                return strcmp($b['modified'], $a['modified']);
            });
            
            foreach (array_slice($recentFiles, 0, 10) as $file) {
                echo "    {$file['file']}: {$file['size']} bytes at {$file['modified']}\n";
            }
            
            if (count($recentFiles) > 10) {
                echo "    ... and " . (count($recentFiles) - 10) . " more recent files\n";
            }
            
            echo "  🔄 Files are being actively modified - crawl is likely running\n";
        } else {
            echo "  ❌ No recently modified files (crawl may have stopped)\n";
        }
    } else {
        echo "  ❌ Storage directory not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking files: " . $e->getMessage() . "\n";
}

// Test 6: Recommendations
echo "\n6. 💡 Status Sync Recommendations:\n";

$hasActiveJobs = isset($activeJobs) && $activeJobs->count() > 0;
$hasRecentFiles = isset($recentFiles) && !empty($recentFiles);
$statusIsCrawled = $story->crawl_status == 2;
$statusIsCrawling = $story->crawl_status == 3;

echo "  Current situation analysis:\n";
echo "    Story status: " . ($statusIsCrawled ? "CRAWLED" : ($statusIsCrawling ? "CRAWLING" : "OTHER")) . "\n";
echo "    Active jobs: " . ($hasActiveJobs ? "YES" : "NO") . "\n";
echo "    Recent file activity: " . ($hasRecentFiles ? "YES" : "NO") . "\n";

if ($statusIsCrawled && ($hasActiveJobs || $hasRecentFiles)) {
    echo "\n  ⚠️ STATUS MISMATCH DETECTED:\n";
    echo "    Story shows CRAWLED but crawl is still active\n";
    echo "    Recommended actions:\n";
    echo "    1. Update story status to CRAWLING (3)\n";
    echo "    2. Add cancel button functionality\n";
    echo "    3. Implement real-time status monitoring\n";
} elseif ($statusIsCrawling && !$hasActiveJobs && !$hasRecentFiles) {
    echo "\n  ⚠️ STALE CRAWLING STATUS:\n";
    echo "    Story shows CRAWLING but no active crawl detected\n";
    echo "    Recommended actions:\n";
    echo "    1. Update story status to CRAWLED (2)\n";
    echo "    2. Clean up stale job references\n";
} else {
    echo "\n  ✅ STATUS APPEARS CORRECT\n";
}

echo "\n7. 🔧 Quick Fix Commands:\n";
echo "  A. Force update status to CRAWLING:\n";
echo "    UPDATE stories SET crawl_status = 3 WHERE id = 5;\n";
echo "  \n";
echo "  B. Force update status to CRAWLED:\n";
echo "    UPDATE stories SET crawl_status = 2 WHERE id = 5;\n";
echo "  \n";
echo "  C. Clear job reference:\n";
echo "    UPDATE stories SET crawl_job_id = NULL WHERE id = 5;\n";
echo "  \n";
echo "  D. Kill all Node.js processes:\n";
echo "    taskkill /F /IM node.exe\n";

echo "\n✅ Crawl status synchronization debugging completed!\n";

?>
