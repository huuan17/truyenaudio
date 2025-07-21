<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG CRAWL FUNCTIONALITY ===\n";

// Test 1: Find Mục thần ký story
echo "1. 📚 Finding Mục thần ký Story:\n";
try {
    $story = \App\Models\Story::where('title', 'LIKE', '%Mục thần ký%')
                              ->orWhere('title', 'LIKE', '%muc than ky%')
                              ->first();
    
    if (!$story) {
        $story = \App\Models\Story::where('slug', 'muc-than-ky')->first();
    }
    
    if (!$story) {
        // Get latest story (might be the one just added)
        $story = \App\Models\Story::latest()->first();
        echo "  Using latest story instead:\n";
    }
    
    if ($story) {
        echo "  ✅ Story found:\n";
        echo "    ID: {$story->id}\n";
        echo "    Title: {$story->title}\n";
        echo "    Slug: {$story->slug}\n";
        echo "    URL: {$story->url}\n";
        echo "    Folder: {$story->folder_name}\n";
        echo "    Crawl Status: {$story->crawl_status}\n";
        echo "    Start Chapter: {$story->start_chapter}\n";
        echo "    End Chapter: {$story->end_chapter}\n";
        echo "    Current Chapters: " . $story->chapters()->count() . "\n";
        echo "    Created: {$story->created_at}\n";
        echo "    Updated: {$story->updated_at}\n";
    } else {
        echo "  ❌ No story found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error finding story: " . $e->getMessage() . "\n";
}

// Test 2: Check crawl command
echo "\n2. 🔧 Crawl Command Check:\n";
$crawlCommandFile = app_path('Console/Commands/CrawlStories.php');
if (file_exists($crawlCommandFile)) {
    $commandContent = file_get_contents($crawlCommandFile);
    
    echo "  ✅ CrawlStories command exists\n";
    
    // Check for key functionality
    $checks = [
        'function handle()' => 'Handle method exists',
        'storage_path' => 'Uses storage path',
        'mkdir' => 'Creates directories',
        'file_put_contents' => 'Writes files',
        'curl_exec' => 'Makes HTTP requests',
        'DOMDocument' => 'Parses HTML'
    ];
    
    foreach ($checks as $check => $description) {
        if (strpos($commandContent, $check) !== false) {
            echo "    ✅ {$description}: Found\n";
        } else {
            echo "    ❌ {$description}: Missing\n";
        }
    }
} else {
    echo "  ❌ CrawlStories command not found\n";
}

// Test 3: Check storage directory
echo "\n3. 📁 Storage Directory Check:\n";
if (isset($story) && $story) {
    $storageBasePath = storage_path('app/content');
    $storyStoragePath = storage_path('app/content/' . $story->folder_name);
    
    echo "  Storage paths:\n";
    echo "    Base: {$storageBasePath}\n";
    echo "    Story: {$storyStoragePath}\n";
    
    // Check if base directory exists
    if (is_dir($storageBasePath)) {
        echo "    ✅ Base content directory exists\n";
        
        $subdirs = glob($storageBasePath . '/*', GLOB_ONLYDIR);
        echo "    Subdirectories: " . count($subdirs) . "\n";
        foreach (array_slice($subdirs, 0, 5) as $dir) {
            $dirName = basename($dir);
            $fileCount = count(glob($dir . '/*.txt'));
            echo "      {$dirName}: {$fileCount} files\n";
        }
    } else {
        echo "    ❌ Base content directory not found\n";
        echo "    Creating base directory...\n";
        try {
            mkdir($storageBasePath, 0755, true);
            echo "    ✅ Base directory created\n";
        } catch (\Exception $e) {
            echo "    ❌ Failed to create base directory: " . $e->getMessage() . "\n";
        }
    }
    
    // Check story specific directory
    if (is_dir($storyStoragePath)) {
        echo "    ✅ Story directory exists\n";
        $txtFiles = glob($storyStoragePath . '/*.txt');
        echo "    Text files: " . count($txtFiles) . "\n";
    } else {
        echo "    ❌ Story directory not found\n";
    }
}

// Test 4: Test HTTP request to story URL
echo "\n4. 🌐 HTTP Request Test:\n";
if (isset($story) && $story && $story->url) {
    try {
        echo "  Testing HTTP request to: {$story->url}\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $story->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "  Request results:\n";
        echo "    HTTP Code: {$httpCode}\n";
        echo "    Response Size: " . strlen($response) . " bytes\n";
        
        if ($error) {
            echo "    ❌ cURL Error: {$error}\n";
        } else if ($httpCode == 200) {
            echo "    ✅ Request successful\n";
            
            // Check for chapter links
            $chapterCount = substr_count($response, 'chuong-');
            echo "    Chapter links found: {$chapterCount}\n";
            
            // Check for content selector
            if (strpos($response, 'chapter-c') !== false) {
                echo "    ✅ Content selector 'chapter-c' found\n";
            } else {
                echo "    ⚠️ Content selector 'chapter-c' not found\n";
            }
        } else {
            echo "    ⚠️ HTTP Error: {$httpCode}\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error testing HTTP request: " . $e->getMessage() . "\n";
    }
}

// Test 5: Check crawl job status
echo "\n5. 🔄 Crawl Job Status:\n";
try {
    $activeJobs = \DB::table('jobs')->count();
    $failedJobs = \DB::table('failed_jobs')->count();
    
    echo "  Active jobs: {$activeJobs}\n";
    echo "  Failed jobs: {$failedJobs}\n";
    
    if ($activeJobs > 0) {
        echo "  Recent jobs:\n";
        $jobs = \DB::table('jobs')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            echo "    Job ID: {$job->id}, Queue: {$job->queue}, Attempts: {$job->attempts}\n";
        }
    }
    
    if ($failedJobs > 0) {
        echo "  Recent failed jobs:\n";
        $failed = \DB::table('failed_jobs')->orderBy('failed_at', 'desc')->take(3)->get();
        foreach ($failed as $job) {
            echo "    Failed at: {$job->failed_at}\n";
            echo "    Exception: " . substr($job->exception, 0, 100) . "...\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking jobs: " . $e->getMessage() . "\n";
}

// Test 6: Test crawl command manually
echo "\n6. 🧪 Manual Crawl Test:\n";
if (isset($story) && $story) {
    try {
        echo "  Testing crawl command for story ID: {$story->id}\n";
        
        // Test if command exists
        $commandExists = \Artisan::all()['crawl:stories'] ?? null;
        if ($commandExists) {
            echo "  ✅ crawl:stories command exists\n";
            
            // Test command signature
            echo "  Command signature: " . $commandExists->getSignature() . "\n";
            
            // Don't actually run the command, just test if it's callable
            echo "  ✅ Command is callable\n";
        } else {
            echo "  ❌ crawl:stories command not found\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error testing crawl command: " . $e->getMessage() . "\n";
    }
}

// Test 7: Check Laravel logs
echo "\n7. 📋 Laravel Logs Check:\n";
try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        echo "  ✅ Laravel log file exists\n";
        echo "  File size: " . round(filesize($logFile) / 1024, 1) . " KB\n";
        
        // Get recent log entries
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -20);
        
        echo "  Recent log entries (last 5):\n";
        foreach (array_slice($recentLines, -5) as $line) {
            if (!empty(trim($line))) {
                echo "    " . substr($line, 0, 100) . "...\n";
            }
        }
    } else {
        echo "  ❌ Laravel log file not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking logs: " . $e->getMessage() . "\n";
}

// Test 8: Recommendations
echo "\n8. 💡 Troubleshooting Recommendations:\n";
echo "  A. Check crawl command execution:\n";
echo "    php artisan crawl:stories --story_id=" . ($story->id ?? 'X') . " --smart\n";
echo "  \n";
echo "  B. Check storage permissions:\n";
echo "    - Ensure storage/app/content is writable\n";
echo "    - Check folder permissions (755 for directories)\n";
echo "  \n";
echo "  C. Check network connectivity:\n";
echo "    - Test story URL accessibility\n";
echo "    - Check for blocking/rate limiting\n";
echo "  \n";
echo "  D. Monitor logs:\n";
echo "    tail -f storage/logs/laravel.log\n";
echo "    Look for crawl-related errors\n";

echo "\n📋 SUMMARY:\n";
$storyFound = isset($story) && $story;
$commandExists = file_exists($crawlCommandFile);
$storageExists = is_dir(storage_path('app/content'));

echo "Story found: " . ($storyFound ? "✅ Yes" : "❌ No") . "\n";
echo "Crawl command exists: " . ($commandExists ? "✅ Yes" : "❌ No") . "\n";
echo "Storage directory exists: " . ($storageExists ? "✅ Yes" : "❌ No") . "\n";
echo "Active jobs: " . (\DB::table('jobs')->count()) . "\n";
echo "Failed jobs: " . (\DB::table('failed_jobs')->count()) . "\n";

if ($storyFound && $commandExists && $storageExists) {
    echo "\n🔍 LIKELY ISSUES:\n";
    echo "  1. Crawl command may have errors during execution\n";
    echo "  2. Network issues preventing content download\n";
    echo "  3. Website structure changes affecting selectors\n";
    echo "  4. Storage permission issues\n";
    echo "\n🔧 NEXT STEPS:\n";
    echo "  1. Run crawl command manually to see errors\n";
    echo "  2. Check Laravel logs for detailed error messages\n";
    echo "  3. Test HTTP requests to story chapters\n";
    echo "  4. Verify storage directory permissions\n";
} else {
    echo "\n❌ SETUP ISSUES DETECTED:\n";
    echo "  Fix missing components before debugging crawl\n";
}

echo "\n✅ Crawl functionality debugging completed!\n";

?>
