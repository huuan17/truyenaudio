<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CRAWL LOG MONITORING ===\n";

// Test 1: Check recent crawl logs
echo "1. 📋 Recent Crawl Logs (Last 50 entries):\n";
try {
    $logFile = storage_path('logs/laravel.log');
    
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        
        // Filter crawl-related logs
        $crawlLogs = [];
        foreach ($lines as $line) {
            if (strpos($line, 'crawl') !== false || 
                strpos($line, 'Crawl') !== false ||
                strpos($line, 'chapter') !== false ||
                strpos($line, 'Chapter') !== false ||
                strpos($line, 'story') !== false) {
                $crawlLogs[] = $line;
            }
        }
        
        $recentLogs = array_slice($crawlLogs, -50);
        
        echo "  Found " . count($crawlLogs) . " crawl-related log entries\n";
        echo "  Showing last " . count($recentLogs) . " entries:\n\n";
        
        foreach ($recentLogs as $log) {
            if (!empty(trim($log))) {
                // Color code based on log level
                if (strpos($log, 'ERROR') !== false) {
                    echo "  ❌ " . substr($log, 0, 150) . "...\n";
                } elseif (strpos($log, 'WARNING') !== false || strpos($log, 'WARN') !== false) {
                    echo "  ⚠️ " . substr($log, 0, 150) . "...\n";
                } elseif (strpos($log, 'INFO') !== false) {
                    echo "  ✅ " . substr($log, 0, 150) . "...\n";
                } else {
                    echo "  📝 " . substr($log, 0, 150) . "...\n";
                }
            }
        }
    } else {
        echo "  ❌ Laravel log file not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error reading logs: " . $e->getMessage() . "\n";
}

// Test 2: Analyze error patterns
echo "\n2. 🔍 Error Pattern Analysis:\n";
try {
    if (isset($crawlLogs)) {
        $errorPatterns = [
            'timeout' => 0,
            'Chrome' => 0,
            'browser' => 0,
            'chapter-c' => 0,
            'Failed to crawl' => 0,
            'Empty content' => 0,
            'Network' => 0,
            'Connection' => 0
        ];
        
        foreach ($crawlLogs as $log) {
            foreach ($errorPatterns as $pattern => $count) {
                if (stripos($log, $pattern) !== false) {
                    $errorPatterns[$pattern]++;
                }
            }
        }
        
        echo "  Error pattern frequency:\n";
        foreach ($errorPatterns as $pattern => $count) {
            if ($count > 0) {
                echo "    {$pattern}: {$count} occurrences\n";
            }
        }
        
        $totalErrors = array_sum($errorPatterns);
        if ($totalErrors === 0) {
            echo "    ✅ No common error patterns detected\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error analyzing patterns: " . $e->getMessage() . "\n";
}

// Test 3: Check current crawl status
echo "\n3. 📊 Current Crawl Status:\n";
try {
    $crawlingStories = \App\Models\Story::where('crawl_status', 3)->get();
    $failedStories = \App\Models\Story::where('crawl_status', 4)->get();
    $recentStories = \App\Models\Story::where('updated_at', '>=', now()->subHours(24))->get();
    
    echo "  Currently crawling: " . $crawlingStories->count() . " stories\n";
    foreach ($crawlingStories as $story) {
        echo "    - {$story->title} (ID: {$story->id})\n";
    }
    
    echo "  Failed crawls: " . $failedStories->count() . " stories\n";
    foreach ($failedStories as $story) {
        echo "    - {$story->title} (ID: {$story->id})\n";
    }
    
    echo "  Recently updated: " . $recentStories->count() . " stories\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error checking status: " . $e->getMessage() . "\n";
}

// Test 4: Check storage files
echo "\n4. 📁 Storage Files Check:\n";
try {
    $contentDir = storage_path('app/content');
    if (is_dir($contentDir)) {
        $storyDirs = glob($contentDir . '/*', GLOB_ONLYDIR);
        
        echo "  Story directories: " . count($storyDirs) . "\n";
        
        foreach ($storyDirs as $dir) {
            $dirName = basename($dir);
            $txtFiles = glob($dir . '/*.txt');
            $totalSize = 0;
            
            foreach ($txtFiles as $file) {
                $totalSize += filesize($file);
            }
            
            echo "    {$dirName}: " . count($txtFiles) . " files (" . round($totalSize / 1024 / 1024, 1) . " MB)\n";
        }
    } else {
        echo "  ❌ Content directory not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking storage: " . $e->getMessage() . "\n";
}

// Test 5: Generate monitoring commands
echo "\n5. 🛠️ Monitoring Commands:\n";
echo "  A. Real-time log monitoring:\n";
echo "    tail -f storage/logs/laravel.log | grep -i crawl\n";
echo "  \n";
echo "  B. Error-only monitoring:\n";
echo "    tail -f storage/logs/laravel.log | grep -i error\n";
echo "  \n";
echo "  C. Chapter progress monitoring:\n";
echo "    watch 'ls storage/app/content/*/chuong-*.txt | wc -l'\n";
echo "  \n";
echo "  D. Process monitoring:\n";
echo "    ps aux | grep -E '(crawl|node)'\n";
echo "  \n";
echo "  E. Storage space monitoring:\n";
echo "    du -sh storage/app/content/*\n";

// Test 6: Log file management
echo "\n6. 📋 Log File Management:\n";
try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logSize = filesize($logFile);
        $logSizeMB = round($logSize / 1024 / 1024, 1);
        
        echo "  Current log file size: {$logSizeMB} MB\n";
        
        if ($logSizeMB > 100) {
            echo "  ⚠️ Log file is large, consider rotating\n";
            echo "  Commands to rotate logs:\n";
            echo "    cp storage/logs/laravel.log storage/logs/laravel.log.backup\n";
            echo "    echo '' > storage/logs/laravel.log\n";
        } else {
            echo "  ✅ Log file size is manageable\n";
        }
        
        // Check log permissions
        if (is_writable($logFile)) {
            echo "  ✅ Log file is writable\n";
        } else {
            echo "  ❌ Log file is not writable\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking log file: " . $e->getMessage() . "\n";
}

// Test 7: Recommendations
echo "\n7. 💡 Monitoring Recommendations:\n";
echo "  A. Set up log rotation:\n";
echo "    - Configure logrotate for Laravel logs\n";
echo "    - Keep logs for debugging but prevent disk space issues\n";
echo "  \n";
echo "  B. Implement alerting:\n";
echo "    - Monitor for high error rates\n";
echo "    - Alert on crawl failures\n";
echo "    - Track success rates\n";
echo "  \n";
echo "  C. Performance monitoring:\n";
echo "    - Track crawl speed (chapters per minute)\n";
echo "    - Monitor memory usage\n";
echo "    - Check disk space usage\n";
echo "  \n";
echo "  D. Error handling:\n";
echo "    - Implement retry logic for failed chapters\n";
echo "    - Add circuit breaker for repeated failures\n";
echo "    - Graceful degradation on errors\n";

// Test 8: Quick health check
echo "\n8. 🏥 Quick Health Check:\n";
$healthScore = 0;
$maxScore = 6;

// Check 1: Log file exists and writable
if (file_exists(storage_path('logs/laravel.log')) && is_writable(storage_path('logs/laravel.log'))) {
    echo "  ✅ Log file accessible\n";
    $healthScore++;
} else {
    echo "  ❌ Log file issues\n";
}

// Check 2: Content directory exists
if (is_dir(storage_path('app/content'))) {
    echo "  ✅ Content directory exists\n";
    $healthScore++;
} else {
    echo "  ❌ Content directory missing\n";
}

// Check 3: Node.js available
if (!empty(shell_exec('node --version 2>&1'))) {
    echo "  ✅ Node.js available\n";
    $healthScore++;
} else {
    echo "  ❌ Node.js not available\n";
}

// Check 4: Crawl script exists
if (file_exists(base_path('node_scripts/crawl.js'))) {
    echo "  ✅ Crawl script exists\n";
    $healthScore++;
} else {
    echo "  ❌ Crawl script missing\n";
}

// Check 5: No stuck crawl processes
$stuckCrawls = \App\Models\Story::where('crawl_status', 3)
    ->where('updated_at', '<', now()->subHours(2))
    ->count();
if ($stuckCrawls === 0) {
    echo "  ✅ No stuck crawl processes\n";
    $healthScore++;
} else {
    echo "  ⚠️ {$stuckCrawls} potentially stuck crawl processes\n";
}

// Check 6: Recent successful crawls
$recentSuccess = isset($crawlLogs) && count(array_filter($crawlLogs, function($log) {
    return strpos($log, 'Successfully') !== false && 
           strpos($log, date('Y-m-d')) !== false;
})) > 0;

if ($recentSuccess) {
    echo "  ✅ Recent successful crawls detected\n";
    $healthScore++;
} else {
    echo "  ⚠️ No recent successful crawls detected\n";
}

$healthPercentage = round(($healthScore / $maxScore) * 100);
echo "\n📊 Overall Health Score: {$healthScore}/{$maxScore} ({$healthPercentage}%)\n";

if ($healthPercentage >= 80) {
    echo "🎉 System is healthy!\n";
} elseif ($healthPercentage >= 60) {
    echo "⚠️ System has minor issues\n";
} else {
    echo "❌ System needs attention\n";
}

echo "\n✅ Crawl log monitoring completed!\n";
echo "Monitor in real-time: tail -f storage/logs/laravel.log | grep -i crawl\n";

?>
