<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG CANCEL CRAWL FUNCTIONALITY ===\n";

// Test 1: Check route registration
echo "1. 🛣️ Route Registration Check:\n";
$routes = app('router')->getRoutes();
$cancelCrawlRoute = null;

foreach ($routes as $route) {
    if (str_contains($route->uri(), 'cancel-crawl')) {
        $cancelCrawlRoute = $route;
        break;
    }
}

if ($cancelCrawlRoute) {
    echo "  ✅ Cancel crawl route found:\n";
    echo "    URI: " . $cancelCrawlRoute->uri() . "\n";
    echo "    Methods: " . implode(', ', $cancelCrawlRoute->methods()) . "\n";
    echo "    Name: " . $cancelCrawlRoute->getName() . "\n";
    echo "    Action: " . $cancelCrawlRoute->getActionName() . "\n";
} else {
    echo "  ❌ Cancel crawl route not found\n";
}

// Test 2: Check controller method exists
echo "\n2. 🎮 Controller Method Check:\n";
$controllerFile = app_path('Http/Controllers/Admin/StoryController.php');
if (file_exists($controllerFile)) {
    $controllerContent = file_get_contents($controllerFile);
    
    if (strpos($controllerContent, 'function cancelCrawl') !== false) {
        echo "  ✅ cancelCrawl method exists in StoryController\n";
        
        // Extract method signature
        preg_match('/public function cancelCrawl\([^)]*\)/', $controllerContent, $matches);
        if (!empty($matches)) {
            echo "    Method signature: " . $matches[0] . "\n";
        }
    } else {
        echo "  ❌ cancelCrawl method not found in StoryController\n";
    }
} else {
    echo "  ❌ StoryController file not found\n";
}

// Test 3: Check story with ID 3 exists
echo "\n3. 📚 Story ID 3 Check:\n";
try {
    $story = \App\Models\Story::find(3);
    if ($story) {
        echo "  ✅ Story ID 3 exists:\n";
        echo "    Title: " . $story->title . "\n";
        echo "    Crawl Status: " . $story->crawl_status . "\n";
        echo "    Crawl Job ID: " . ($story->crawl_job_id ?? 'null') . "\n";
        
        // Check crawl status constants
        $crawlStatuses = config('constants.CRAWL_STATUS.VALUES');
        if ($crawlStatuses) {
            echo "    Available crawl statuses:\n";
            foreach ($crawlStatuses as $key => $value) {
                echo "      {$key}: {$value}\n";
            }
        }
    } else {
        echo "  ❌ Story ID 3 not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking story: " . $e->getMessage() . "\n";
}

// Test 4: Test route generation
echo "\n4. 🔗 Route Generation Test:\n";
try {
    if ($story) {
        $cancelUrl = route('admin.stories.cancel-crawl', $story);
        echo "  ✅ Cancel crawl URL generated: {$cancelUrl}\n";
        
        // Check if URL matches expected pattern
        $expectedPattern = '/admin\/stories\/3\/cancel-crawl/';
        if (preg_match($expectedPattern, $cancelUrl)) {
            echo "  ✅ URL pattern matches expected format\n";
        } else {
            echo "  ⚠️ URL pattern doesn't match expected format\n";
        }
    } else {
        echo "  ❌ Cannot generate URL - story not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error generating route: " . $e->getMessage() . "\n";
}

// Test 5: Check middleware and permissions
echo "\n5. 🔐 Middleware and Permissions Check:\n";
try {
    // Check if admin middleware is applied
    $adminRoutes = app('router')->getRoutes()->getByName('admin.stories.cancel-crawl');
    if ($adminRoutes) {
        $middleware = $adminRoutes->middleware();
        echo "  ✅ Route middleware: " . implode(', ', $middleware) . "\n";
    }
    
    // Check if user is authenticated and has admin role
    if (auth()->check()) {
        echo "  ✅ User is authenticated: " . auth()->user()->email . "\n";
        echo "  ✅ User role: " . auth()->user()->role . "\n";
    } else {
        echo "  ❌ User not authenticated\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking permissions: " . $e->getMessage() . "\n";
}

// Test 6: Simulate cancel crawl request
echo "\n6. 🧪 Simulate Cancel Crawl Request:\n";
try {
    if ($story) {
        // Create a test request
        $request = \Illuminate\Http\Request::create(
            "/admin/stories/{$story->id}/cancel-crawl",
            'POST',
            [],
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        echo "  ✅ Test request created:\n";
        echo "    Method: " . $request->method() . "\n";
        echo "    URL: " . $request->url() . "\n";
        echo "    Has CSRF token: " . ($request->header('X-CSRF-TOKEN') ? 'Yes' : 'No') . "\n";
        
        // Check if controller method can be called
        $controller = new \App\Http\Controllers\Admin\StoryController();
        if (method_exists($controller, 'cancelCrawl')) {
            echo "  ✅ Controller method is callable\n";
        } else {
            echo "  ❌ Controller method not callable\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error simulating request: " . $e->getMessage() . "\n";
}

// Test 7: Check for common 404 causes
echo "\n7. 🔍 Common 404 Causes Check:\n";

// Check route cache
if (file_exists(bootstrap_path('cache/routes.php'))) {
    echo "  ⚠️ Route cache exists - may need clearing\n";
    echo "    Run: php artisan route:clear\n";
} else {
    echo "  ✅ No route cache found\n";
}

// Check config cache
if (file_exists(bootstrap_path('cache/config.php'))) {
    echo "  ⚠️ Config cache exists - may need clearing\n";
    echo "    Run: php artisan config:clear\n";
} else {
    echo "  ✅ No config cache found\n";
}

// Check view cache
if (file_exists(storage_path('framework/views'))) {
    $viewFiles = glob(storage_path('framework/views/*.php'));
    if (!empty($viewFiles)) {
        echo "  ⚠️ View cache exists - may need clearing\n";
        echo "    Run: php artisan view:clear\n";
    } else {
        echo "  ✅ No view cache found\n";
    }
} else {
    echo "  ✅ No view cache directory\n";
}

// Test 8: Generate test URLs
echo "\n8. 🔗 Test URLs Generation:\n";
$testUrls = [
    'Stories List' => 'admin.stories.index',
    'Story Show' => $story ? ['admin.stories.show', $story] : null,
    'Cancel Crawl' => $story ? ['admin.stories.cancel-crawl', $story] : null,
    'Smart Crawl' => $story ? ['admin.stories.smart-crawl', $story] : null
];

foreach ($testUrls as $name => $routeData) {
    try {
        if ($routeData) {
            if (is_array($routeData)) {
                $url = route($routeData[0], $routeData[1]);
            } else {
                $url = route($routeData);
            }
            echo "  ✅ {$name}: {$url}\n";
        } else {
            echo "  ❌ {$name}: Cannot generate (story not found)\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ {$name}: Error - " . $e->getMessage() . "\n";
    }
}

// Test 9: Debug recommendations
echo "\n9. 💡 Debug Recommendations:\n";
echo "  A. Clear Laravel caches:\n";
echo "    php artisan route:clear\n";
echo "    php artisan config:clear\n";
echo "    php artisan view:clear\n";
echo "    php artisan cache:clear\n";
echo "  \n";
echo "  B. Check browser network tab:\n";
echo "    - Open browser dev tools (F12)\n";
echo "    - Go to Network tab\n";
echo "    - Click cancel button\n";
echo "    - Check actual request URL and response\n";
echo "  \n";
echo "  C. Check Laravel logs:\n";
echo "    tail -f storage/logs/laravel.log\n";
echo "  \n";
echo "  D. Test direct URL access:\n";
echo "    http://localhost:8000/admin/stories/3/cancel-crawl\n";

echo "\n📋 SUMMARY:\n";
$routeExists = $cancelCrawlRoute !== null;
$controllerExists = file_exists($controllerFile) && strpos(file_get_contents($controllerFile), 'function cancelCrawl') !== false;
$storyExists = isset($story) && $story !== null;

echo "Route registration: " . ($routeExists ? "✅ OK" : "❌ Missing") . "\n";
echo "Controller method: " . ($controllerExists ? "✅ OK" : "❌ Missing") . "\n";
echo "Story ID 3 exists: " . ($storyExists ? "✅ OK" : "❌ Missing") . "\n";
echo "User authenticated: " . (auth()->check() ? "✅ OK" : "❌ No") . "\n";

if ($routeExists && $controllerExists && $storyExists && auth()->check()) {
    echo "\n🎯 LIKELY CAUSE: Cache issues or middleware problems\n";
    echo "SOLUTION: Clear caches and check browser network tab\n";
} else {
    echo "\n❌ ISSUES FOUND: Check the failed items above\n";
}

echo "\n✅ Cancel crawl debugging completed!\n";

?>
