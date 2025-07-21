<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG CHAPTER SCAN TIMEOUT ===\n";

// Test 1: Find Tiên Nghịch story
echo "1. 📚 Finding Tiên Nghịch Story:\n";
try {
    $story = \App\Models\Story::where('title', 'LIKE', '%Tiên Nghịch%')
                              ->orWhere('title', 'LIKE', '%tien nghich%')
                              ->first();
    
    if (!$story) {
        $story = \App\Models\Story::where('slug', 'tien-nghich')->first();
    }
    
    if ($story) {
        echo "  ✅ Story found:\n";
        echo "    ID: {$story->id}\n";
        echo "    Title: {$story->title}\n";
        echo "    Slug: {$story->slug}\n";
        echo "    URL: {$story->url}\n";
        echo "    Start Chapter: {$story->start_chapter}\n";
        echo "    End Chapter: {$story->end_chapter}\n";
        echo "    Current Chapters: " . $story->chapters()->count() . "\n";
    } else {
        echo "  ❌ Tiên Nghịch story not found\n";
        
        // List all stories for reference
        echo "  Available stories:\n";
        $stories = \App\Models\Story::select('id', 'title', 'slug')->take(10)->get();
        foreach ($stories as $s) {
            echo "    ID: {$s->id}, Title: {$s->title}, Slug: {$s->slug}\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error finding story: " . $e->getMessage() . "\n";
}

// Test 2: Check scan chapters functionality
echo "\n2. 🔍 Scan Chapters Functionality Check:\n";
if (isset($story) && $story) {
    try {
        // Check if scan chapters route exists
        $routes = app('router')->getRoutes();
        $scanRoute = null;
        
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'scan-chapters')) {
                $scanRoute = $route;
                break;
            }
        }
        
        if ($scanRoute) {
            echo "  ✅ Scan chapters route exists:\n";
            echo "    URI: " . $scanRoute->uri() . "\n";
            echo "    Methods: " . implode(', ', $scanRoute->methods()) . "\n";
            echo "    Name: " . $scanRoute->getName() . "\n";
        } else {
            echo "  ❌ Scan chapters route not found\n";
        }
        
        // Check controller method
        $controllerFile = app_path('Http/Controllers/Admin/StoryController.php');
        $controllerContent = file_get_contents($controllerFile);
        
        if (strpos($controllerContent, 'scanChapters') !== false) {
            echo "  ✅ scanChapters method exists in controller\n";
        } else {
            echo "  ❌ scanChapters method not found in controller\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error checking scan chapters: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check timeout settings
echo "\n3. ⏱️ Timeout Settings Check:\n";
try {
    $phpTimeouts = [
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'memory_limit' => ini_get('memory_limit'),
        'default_socket_timeout' => ini_get('default_socket_timeout')
    ];
    
    echo "  PHP Timeout Settings:\n";
    foreach ($phpTimeouts as $setting => $value) {
        echo "    {$setting}: {$value}\n";
    }
    
    // Check Laravel timeout settings
    $laravelTimeouts = [
        'HTTP timeout' => config('app.timeout', 'not set'),
        'Queue timeout' => config('queue.connections.database.retry_after', 'not set'),
        'Session lifetime' => config('session.lifetime', 'not set')
    ];
    
    echo "  Laravel Timeout Settings:\n";
    foreach ($laravelTimeouts as $setting => $value) {
        echo "    {$setting}: {$value}\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking timeouts: " . $e->getMessage() . "\n";
}

// Test 4: Test HTTP request to story URL
echo "\n4. 🌐 HTTP Request Test:\n";
if (isset($story) && $story && $story->url) {
    try {
        echo "  Testing HTTP request to: {$story->url}\n";
        
        $startTime = microtime(true);
        
        // Use cURL with timeout settings
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $story->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,           // 30 seconds timeout
            CURLOPT_CONNECTTIMEOUT => 10,    // 10 seconds connect timeout
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        curl_close($ch);
        
        $endTime = microtime(true);
        $requestTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "  Request results:\n";
        echo "    HTTP Code: {$httpCode}\n";
        echo "    Request Time: {$requestTime}ms\n";
        echo "    cURL Time: {$totalTime}s\n";
        echo "    Response Size: " . strlen($response) . " bytes\n";
        
        if ($error) {
            echo "    ❌ cURL Error: {$error}\n";
        } else if ($httpCode == 200) {
            echo "    ✅ Request successful\n";
            
            // Check if response contains chapter links
            $chapterCount = substr_count($response, 'chuong-');
            echo "    Chapter links found: {$chapterCount}\n";
        } else {
            echo "    ⚠️ HTTP Error: {$httpCode}\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error testing HTTP request: " . $e->getMessage() . "\n";
    }
}

// Test 5: Simulate scan chapters process
echo "\n5. 🔄 Simulate Scan Chapters Process:\n";
if (isset($story) && $story) {
    try {
        echo "  Simulating scan chapters for: {$story->title}\n";
        
        // Check current chapters
        $currentChapters = $story->chapters()->count();
        echo "    Current chapters in DB: {$currentChapters}\n";
        
        // Simulate timeout scenarios
        $timeoutScenarios = [
            'Fast scan (5s)' => 5,
            'Medium scan (15s)' => 15,
            'Slow scan (30s)' => 30,
            'Very slow scan (60s)' => 60
        ];
        
        foreach ($timeoutScenarios as $scenario => $seconds) {
            echo "    {$scenario}: ";
            
            if ($seconds <= 30) {
                echo "✅ Should complete successfully\n";
            } else if ($seconds <= 60) {
                echo "⚠️ May timeout on some servers\n";
            } else {
                echo "❌ Likely to timeout\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error simulating scan: " . $e->getMessage() . "\n";
    }
}

// Test 6: Check for existing timeout fixes
echo "\n6. 🔧 Existing Timeout Fixes Check:\n";
try {
    // Check if there are any timeout-related configurations
    $files = [
        'StoryController.php' => app_path('Http/Controllers/Admin/StoryController.php'),
        'CrawlStories.php' => app_path('Console/Commands/CrawlStories.php'),
        'web.php' => base_path('routes/web.php')
    ];
    
    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            $timeoutKeywords = [
                'set_time_limit',
                'ini_set',
                'timeout',
                'max_execution_time',
                'CURLOPT_TIMEOUT'
            ];
            
            $foundKeywords = [];
            foreach ($timeoutKeywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    $foundKeywords[] = $keyword;
                }
            }
            
            if (!empty($foundKeywords)) {
                echo "  ✅ {$name}: " . implode(', ', $foundKeywords) . "\n";
            } else {
                echo "  ❌ {$name}: No timeout handling found\n";
            }
        } else {
            echo "  ❌ {$name}: File not found\n";
        }
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking timeout fixes: " . $e->getMessage() . "\n";
}

// Test 7: Recommendations
echo "\n7. 💡 Timeout Fix Recommendations:\n";
echo "  A. PHP Configuration:\n";
echo "    - Increase max_execution_time to 300 (5 minutes)\n";
echo "    - Increase memory_limit to 512M or 1G\n";
echo "    - Set default_socket_timeout to 60\n";
echo "  \n";
echo "  B. Laravel Configuration:\n";
echo "    - Add set_time_limit(0) in scan chapters method\n";
echo "    - Use chunked processing for large chapter lists\n";
echo "    - Implement progress tracking and resumable scans\n";
echo "  \n";
echo "  C. HTTP Request Optimization:\n";
echo "    - Use cURL with proper timeout settings\n";
echo "    - Implement retry logic for failed requests\n";
echo "    - Add user agent and headers to avoid blocking\n";
echo "  \n";
echo "  D. Database Optimization:\n";
echo "    - Use batch inserts for multiple chapters\n";
echo "    - Add database indexes for faster queries\n";
echo "    - Use transactions for data consistency\n";

echo "\n📋 SUMMARY:\n";
$storyFound = isset($story) && $story;
$routeExists = isset($scanRoute) && $scanRoute;
$maxExecTime = ini_get('max_execution_time');

echo "Tiên Nghịch story found: " . ($storyFound ? "✅ Yes" : "❌ No") . "\n";
echo "Scan chapters route exists: " . ($routeExists ? "✅ Yes" : "❌ No") . "\n";
echo "Max execution time: {$maxExecTime} seconds\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";

if ($storyFound && $routeExists) {
    if ($maxExecTime < 300) {
        echo "\n⚠️ TIMEOUT RISK DETECTED:\n";
        echo "  Max execution time ({$maxExecTime}s) may be too low for chapter scanning\n";
        echo "  Recommend increasing to 300s (5 minutes) or more\n";
    } else {
        echo "\n✅ TIMEOUT SETTINGS ADEQUATE:\n";
        echo "  Max execution time should be sufficient for scanning\n";
    }
    
    echo "\n🔧 IMMEDIATE ACTIONS:\n";
    echo "  1. Apply timeout fixes to scan chapters method\n";
    echo "  2. Test with smaller chapter ranges first\n";
    echo "  3. Monitor server logs for timeout errors\n";
    echo "  4. Consider implementing progress tracking\n";
} else {
    echo "\n❌ SETUP ISSUES:\n";
    echo "  Fix story/route issues before addressing timeouts\n";
}

echo "\n✅ Chapter scan timeout debugging completed!\n";

?>
