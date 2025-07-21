<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== DEBUG CANCEL CRAWL - STEP 1: Route & Method Check ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "✅ Story found: {$story->title} (ID: {$story->id}, Slug: {$story->slug})\n";

// Test 1: Check route generation
echo "\n1. 🔗 Route Generation Test:\n";
try {
    $cancelRoute = route('admin.stories.cancel-crawl', $story);
    echo "  ✅ Cancel route generated: {$cancelRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Cancel route error: " . $e->getMessage() . "\n";
}

try {
    $cancelRouteById = route('admin.stories.cancel-crawl', 3);
    echo "  ✅ Cancel route by ID: {$cancelRouteById}\n";
} catch (Exception $e) {
    echo "  ❌ Cancel route by ID error: " . $e->getMessage() . "\n";
}

// Test 2: Check all routes containing cancel-crawl
echo "\n2. 📋 Route List Check:\n";
$routes = app('router')->getRoutes();
$found = false;

foreach ($routes as $route) {
    if (strpos($route->uri(), 'cancel-crawl') !== false) {
        echo "  ✅ Route found: " . $route->uri() . "\n";
        echo "    Methods: " . implode(', ', $route->methods()) . "\n";
        echo "    Name: " . $route->getName() . "\n";
        echo "    Action: " . $route->getActionName() . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "  ❌ No cancel-crawl routes found\n";
}

// Test 3: Check controller method exists
echo "\n3. 🎯 Controller Method Check:\n";
try {
    $controller = new App\Http\Controllers\Admin\StoryController();
    
    if (method_exists($controller, 'cancelCrawl')) {
        echo "  ✅ cancelCrawl method exists\n";
        
        // Get method reflection
        $reflection = new ReflectionMethod($controller, 'cancelCrawl');
        $parameters = $reflection->getParameters();
        
        echo "  Method parameters:\n";
        foreach ($parameters as $param) {
            $type = $param->getType() ? $param->getType()->getName() : 'mixed';
            echo "    - {$param->getName()}: {$type}\n";
        }
    } else {
        echo "  ❌ cancelCrawl method not found\n";
    }
} catch (Exception $e) {
    echo "  ❌ Controller check error: " . $e->getMessage() . "\n";
}

// Test 4: Test URL accessibility
echo "\n4. 🌐 URL Accessibility Test:\n";
$testUrls = [
    'http://localhost:8000/admin/stories/3/cancel-crawl',
    'http://localhost:8000/admin/stories/vo-thuong-sat-than/cancel-crawl'
];

foreach ($testUrls as $url) {
    echo "  Testing: {$url}\n";
    
    $cmd = "curl -s -o /dev/null -w \"%{http_code}\" \"{$url}\"";
    $httpCode = trim(shell_exec($cmd));
    
    switch ($httpCode) {
        case '200':
            echo "    ✅ Status: 200 (OK)\n";
            break;
        case '302':
            echo "    ✅ Status: 302 (Redirect - needs auth)\n";
            break;
        case '404':
            echo "    ❌ Status: 404 (Not Found)\n";
            break;
        case '405':
            echo "    ❌ Status: 405 (Method Not Allowed)\n";
            break;
        case '419':
            echo "    ⚠️ Status: 419 (CSRF Error)\n";
            break;
        default:
            echo "    ⚠️ Status: {$httpCode}\n";
            break;
    }
}

// Test 5: Test POST method specifically
echo "\n5. 📤 POST Method Test:\n";
$postUrl = 'http://localhost:8000/admin/stories/3/cancel-crawl';
echo "  Testing POST to: {$postUrl}\n";

$cmd = "curl -X POST -s -o /dev/null -w \"%{http_code}\" \"{$postUrl}\" -d \"_token=test\"";
$httpCode = trim(shell_exec($cmd));

echo "  POST Status: {$httpCode}\n";

if ($httpCode === '404') {
    echo "  ❌ ISSUE: POST returns 404\n";
    echo "  💡 POSSIBLE CAUSES:\n";
    echo "    - Route not registered\n";
    echo "    - Route cache issue\n";
    echo "    - Method not allowed\n";
    echo "    - Controller method missing\n";
} elseif ($httpCode === '302') {
    echo "  ✅ POST redirects (likely needs authentication)\n";
} elseif ($httpCode === '419') {
    echo "  ✅ POST blocked by CSRF (route exists)\n";
}

// Test 6: Check web.php routes file
echo "\n6. 📄 Routes File Check:\n";
$routesFile = base_path('routes/web.php');
$routesContent = file_get_contents($routesFile);

if (strpos($routesContent, 'cancel-crawl') !== false) {
    echo "  ✅ cancel-crawl found in routes/web.php\n";
    
    // Extract the line
    $lines = explode("\n", $routesContent);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'cancel-crawl') !== false) {
            echo "  Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "  ❌ cancel-crawl not found in routes/web.php\n";
}

// Test 7: Check route cache
echo "\n7. 🗂️ Route Cache Check:\n";
$routeCacheFile = base_path('bootstrap/cache/routes-v7.php');
if (file_exists($routeCacheFile)) {
    echo "  ⚠️ Route cache exists: {$routeCacheFile}\n";
    echo "  💡 Try: php artisan route:clear\n";
} else {
    echo "  ✅ No route cache file\n";
}

echo "\n📋 STEP 1 SUMMARY:\n";
echo "Route generation: " . (isset($cancelRoute) ? "✅ Working" : "❌ Failed") . "\n";
echo "Controller method: " . (method_exists('App\\Http\\Controllers\\Admin\\StoryController', 'cancelCrawl') ? "✅ Exists" : "❌ Missing") . "\n";
echo "URL accessibility: Check status codes above\n";
echo "Routes file: " . (strpos($routesContent, 'cancel-crawl') !== false ? "✅ Found" : "❌ Missing") . "\n";

if ($httpCode === '404') {
    echo "\n❌ DIAGNOSIS: 404 Error Confirmed\n";
    echo "💡 NEXT STEPS:\n";
    echo "  1. Check if route is properly registered\n";
    echo "  2. Clear route cache: php artisan route:clear\n";
    echo "  3. Check controller method implementation\n";
    echo "  4. Verify route parameters\n";
} else {
    echo "\n✅ Route appears to be working (not 404)\n";
    echo "💡 NEXT STEPS:\n";
    echo "  1. Check authentication requirements\n";
    echo "  2. Test with proper CSRF token\n";
    echo "  3. Check method implementation\n";
}

echo "\n➡️ NEXT: Run debug_cancel_crawl_step2.php for detailed testing\n";

?>
