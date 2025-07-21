<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG STORY 5 CRAWL ISSUE ===\n";

// Test 1: Check story details
echo "1. 📚 Story ID 5 Details:\n";
try {
    $story = \App\Models\Story::find(5);
    
    if ($story) {
        echo "  ✅ Story found:\n";
        echo "    ID: {$story->id}\n";
        echo "    Title: {$story->title}\n";
        echo "    Slug: {$story->slug}\n";
        echo "    URL: '{$story->url}'\n";
        echo "    Folder: {$story->folder_name}\n";
        echo "    Crawl Path: {$story->crawl_path}\n";
        echo "    Status: {$story->crawl_status}\n";
        echo "    Job ID: {$story->crawl_job_id}\n";
        echo "    Start Chapter: {$story->start_chapter}\n";
        echo "    End Chapter: {$story->end_chapter}\n";
        echo "    Created: {$story->created_at}\n";
        echo "    Updated: {$story->updated_at}\n";
    } else {
        echo "  ❌ Story ID 5 not found\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "  ❌ Error finding story: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check storage directory
echo "\n2. 📁 Storage Directory Check:\n";
$expectedDir = storage_path('app/content/' . $story->folder_name);
echo "  Expected directory: {$expectedDir}\n";

if (is_dir($expectedDir)) {
    echo "  ✅ Directory exists\n";
    
    $txtFiles = glob($expectedDir . '/*.txt');
    echo "  Text files: " . count($txtFiles) . "\n";
    
    if (count($txtFiles) > 0) {
        echo "  Sample files:\n";
        foreach (array_slice($txtFiles, 0, 5) as $file) {
            $size = round(filesize($file) / 1024, 1);
            echo "    " . basename($file) . " ({$size}KB)\n";
        }
    } else {
        echo "  ❌ No text files found\n";
    }
    
    // Check permissions
    if (is_writable($expectedDir)) {
        echo "  ✅ Directory is writable\n";
    } else {
        echo "  ❌ Directory is not writable\n";
    }
} else {
    echo "  ❌ Directory does not exist\n";
    echo "  Creating directory...\n";
    try {
        mkdir($expectedDir, 0755, true);
        echo "  ✅ Directory created\n";
    } catch (\Exception $e) {
        echo "  ❌ Failed to create directory: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check story URL
echo "\n3. 🌐 Story URL Check:\n";
if (empty($story->url)) {
    echo "  ❌ Story URL is empty\n";
    
    // Try to generate URL
    $possibleUrls = [
        "https://truyencom.com/{$story->slug}",
        "https://truyencom.com/truyen/{$story->slug}",
        "https://truyencom.com/than-dao-dan-ton",
    ];
    
    echo "  Testing possible URLs:\n";
    foreach ($possibleUrls as $testUrl) {
        echo "    Testing: {$testUrl}\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY => true, // HEAD request only
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "      HTTP Code: {$httpCode}\n";
        
        if ($httpCode == 200) {
            echo "      ✅ URL works! Updating story...\n";
            $story->update(['url' => $testUrl]);
            echo "      ✅ Story URL updated to: {$testUrl}\n";
            break;
        }
    }
} else {
    echo "  ✅ Story URL exists: {$story->url}\n";
    
    // Test URL accessibility
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $story->url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true,
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  HTTP Response: {$httpCode}\n";
    if ($httpCode == 200) {
        echo "  ✅ URL is accessible\n";
    } else {
        echo "  ❌ URL is not accessible\n";
    }
}

// Test 4: Check crawl command manually
echo "\n4. 🧪 Manual Crawl Command Test:\n";
$story = $story->fresh(); // Refresh story data

if (!empty($story->url)) {
    $baseUrl = rtrim($story->url, '/') . '/chuong-';
    $outputFolder = storage_path('app/content/' . $story->folder_name);
    $scriptPath = base_path('node_scripts/crawl.js');
    
    echo "  Command components:\n";
    echo "    Script: {$scriptPath}\n";
    echo "    Base URL: {$baseUrl}\n";
    echo "    Output: {$outputFolder}\n";
    echo "    Start: {$story->start_chapter}\n";
    echo "    End: {$story->end_chapter}\n";
    
    // Test single chapter first
    $testChapter = $story->start_chapter;
    $command = sprintf(
        'node %s %s %d %d %s %d',
        escapeshellarg($scriptPath),
        escapeshellarg($baseUrl),
        $testChapter,
        $testChapter,
        escapeshellarg($outputFolder),
        1 // single mode
    );
    
    echo "  Test command: {$command}\n";
    echo "  ⚠️ Running test command for chapter {$testChapter}...\n";
    
    $output = [];
    $exitCode = null;
    exec($command . ' 2>&1', $output, $exitCode);
    
    echo "  Command results:\n";
    echo "    Exit code: {$exitCode}\n";
    echo "    Output lines: " . count($output) . "\n";
    
    if (!empty($output)) {
        echo "  Command output:\n";
        foreach ($output as $line) {
            echo "    " . $line . "\n";
        }
    }
    
    // Check if file was created
    $expectedFile = $outputFolder . '/chuong-' . $testChapter . '.txt';
    if (file_exists($expectedFile)) {
        $fileSize = filesize($expectedFile);
        echo "  ✅ Test file created: {$expectedFile} ({$fileSize} bytes)\n";
        
        if ($fileSize > 0) {
            $content = file_get_contents($expectedFile);
            echo "  Content preview: " . substr($content, 0, 100) . "...\n";
        }
    } else {
        echo "  ❌ Test file not created: {$expectedFile}\n";
    }
} else {
    echo "  ❌ Cannot test - story URL is empty\n";
}

// Test 5: Check job queue
echo "\n5. 🔄 Job Queue Check:\n";
try {
    $activeJobs = \DB::table('jobs')->where('id', $story->crawl_job_id)->first();
    if ($activeJobs) {
        echo "  ✅ Job {$story->crawl_job_id} found in queue\n";
        echo "    Queue: {$activeJobs->queue}\n";
        echo "    Attempts: {$activeJobs->attempts}\n";
        echo "    Created: {$activeJobs->created_at}\n";
    } else {
        echo "  ❌ Job {$story->crawl_job_id} not found in active queue\n";
    }
    
    $failedJobs = \DB::table('failed_jobs')->where('id', $story->crawl_job_id)->first();
    if ($failedJobs) {
        echo "  ❌ Job {$story->crawl_job_id} found in failed jobs\n";
        echo "    Failed at: {$failedJobs->failed_at}\n";
        echo "    Exception: " . substr($failedJobs->exception, 0, 200) . "...\n";
    } else {
        echo "  ✅ Job not in failed queue\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking jobs: " . $e->getMessage() . "\n";
}

// Test 6: Check Laravel logs for this specific job
echo "\n6. 📋 Job-Specific Logs:\n";
try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        
        // Filter logs for job ID 40 and story ID 5
        $relevantLogs = [];
        foreach ($lines as $line) {
            if (strpos($line, 'story ID: 5') !== false || 
                strpos($line, 'job ID: 40') !== false ||
                (strpos($line, '2025-07-14 17:11') !== false && 
                 (strpos($line, 'crawl') !== false || strpos($line, 'Crawl') !== false))) {
                $relevantLogs[] = $line;
            }
        }
        
        echo "  Found " . count($relevantLogs) . " relevant log entries:\n";
        foreach ($relevantLogs as $log) {
            if (!empty(trim($log))) {
                echo "    " . $log . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error reading logs: " . $e->getMessage() . "\n";
}

// Test 7: Recommendations
echo "\n7. 💡 Issue Analysis & Recommendations:\n";

$issues = [];
$fixes = [];

if (empty($story->url)) {
    $issues[] = "Story URL is empty";
    $fixes[] = "Set valid story URL";
}

if (!is_dir($expectedDir)) {
    $issues[] = "Storage directory missing";
    $fixes[] = "Create storage directory";
}

if (!file_exists(base_path('node_scripts/crawl.js'))) {
    $issues[] = "Crawl script missing";
    $fixes[] = "Verify crawl script exists";
}

$txtFiles = is_dir($expectedDir) ? glob($expectedDir . '/*.txt') : [];
if (count($txtFiles) === 0) {
    $issues[] = "No files created during crawl";
    $fixes[] = "Debug Node.js script execution";
}

echo "  Issues detected:\n";
foreach ($issues as $issue) {
    echo "    ❌ {$issue}\n";
}

echo "  Recommended fixes:\n";
foreach ($fixes as $fix) {
    echo "    🔧 {$fix}\n";
}

// Test 8: Next steps
echo "\n8. 🚀 Next Steps:\n";
echo "  A. Immediate actions:\n";
echo "    1. Ensure story URL is set and accessible\n";
echo "    2. Verify storage directory exists and is writable\n";
echo "    3. Test Node.js script manually with single chapter\n";
echo "    4. Check for detailed error logs\n";
echo "  \n";
echo "  B. Debug commands:\n";
echo "    # Test single chapter manually\n";
if (isset($command)) {
    echo "    {$command}\n";
}
echo "    \n";
echo "    # Monitor logs in real-time\n";
echo "    tail -f storage/logs/laravel.log | grep -E '(story ID: 5|job ID: 40)'\n";
echo "    \n";
echo "    # Check storage directory\n";
echo "    ls -la {$expectedDir}\n";

echo "\n📋 SUMMARY:\n";
$storyHasUrl = !empty($story->url);
$dirExists = is_dir($expectedDir);
$scriptExists = file_exists(base_path('node_scripts/crawl.js'));
$hasFiles = count($txtFiles) > 0;

echo "Story has URL: " . ($storyHasUrl ? "✅ Yes" : "❌ No") . "\n";
echo "Directory exists: " . ($dirExists ? "✅ Yes" : "❌ No") . "\n";
echo "Script exists: " . ($scriptExists ? "✅ Yes" : "❌ No") . "\n";
echo "Files created: " . ($hasFiles ? "✅ Yes (" . count($txtFiles) . ")" : "❌ No") . "\n";

if (!$storyHasUrl || !$dirExists || !$scriptExists) {
    echo "\n❌ CRITICAL ISSUES DETECTED:\n";
    echo "  Fix the above issues before crawling\n";
} else if (!$hasFiles) {
    echo "\n⚠️ CRAWL EXECUTION ISSUE:\n";
    echo "  Setup is correct but files not created\n";
    echo "  Likely Node.js script execution problem\n";
} else {
    echo "\n✅ SETUP LOOKS GOOD:\n";
    echo "  All components are in place\n";
}

echo "\n✅ Story 5 crawl issue debugging completed!\n";

?>
