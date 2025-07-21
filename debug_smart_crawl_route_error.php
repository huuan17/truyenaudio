<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== DEBUG SMART CRAWL ROUTE ERROR ===\n";

// Test 1: Setup authentication
echo "1. 🔐 Authentication Setup:\n";
$adminUser = User::where('email', 'admin@example.com')->first();
if ($adminUser) {
    Auth::login($adminUser);
    echo "  ✅ Logged in as: {$adminUser->name}\n";
} else {
    echo "  ❌ Admin user not found\n";
    exit(1);
}

// Test 2: Test route generation
echo "\n2. 🔗 Route Generation Test:\n";
$story = Story::find(3);
echo "  Story: {$story->title} (ID: {$story->id})\n";

try {
    $smartCrawlRoute = route('admin.stories.smart-crawl', $story);
    echo "  ✅ Smart crawl route: {$smartCrawlRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Smart crawl route error: " . $e->getMessage() . "\n";
}

try {
    $cancelCrawlRoute = route('admin.stories.cancel-crawl', $story);
    echo "  ✅ Cancel crawl route: {$cancelCrawlRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Cancel crawl route error: " . $e->getMessage() . "\n";
}

try {
    $removeQueueRoute = route('admin.stories.remove-from-queue', $story);
    echo "  ✅ Remove queue route: {$removeQueueRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Remove queue route error: " . $e->getMessage() . "\n";
}

// Test 3: Check all registered routes
echo "\n3. 📋 Registered Routes Check:\n";
$routes = app('router')->getRoutes();
$storyRoutes = [];

foreach ($routes as $route) {
    if (strpos($route->uri(), 'stories') !== false && 
        (strpos($route->uri(), 'smart-crawl') !== false || 
         strpos($route->uri(), 'cancel-crawl') !== false || 
         strpos($route->uri(), 'remove-from-queue') !== false)) {
        $storyRoutes[] = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'name' => $route->getName(),
            'action' => $route->getActionName()
        ];
    }
}

if (empty($storyRoutes)) {
    echo "  ❌ No story crawl routes found\n";
} else {
    foreach ($storyRoutes as $route) {
        echo "  ✅ Route: {$route['uri']}\n";
        echo "    Name: {$route['name']}\n";
        echo "    Methods: " . implode(', ', $route['methods']) . "\n";
        echo "    Action: {$route['action']}\n";
        echo "\n";
    }
}

// Test 4: Test view rendering
echo "4. 🎨 View Rendering Test:\n";
try {
    $controller = new App\Http\Controllers\Admin\StoryController();
    $request = new Illuminate\Http\Request();
    
    // Test stories index
    $response = $controller->index($request);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "  ✅ Stories index view renders\n";
        echo "  View name: " . $response->getName() . "\n";
        
        // Try to render the view
        $content = $response->render();
        echo "  ✅ View content rendered successfully\n";
        echo "  Content length: " . strlen($content) . " characters\n";
        
        // Check for route errors in content
        if (strpos($content, 'Route [admin.stories.smart-crawl] not defined') !== false) {
            echo "  ❌ FOUND: Route error in rendered content\n";
        } else {
            echo "  ✅ No route errors in rendered content\n";
        }
        
    } else {
        echo "  ❌ Stories index doesn't return view\n";
        echo "  Response type: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ View rendering error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 5: Check route cache
echo "\n5. 🗂️ Route Cache Status:\n";
$routeCacheFile = base_path('bootstrap/cache/routes-v7.php');
if (file_exists($routeCacheFile)) {
    echo "  ⚠️ Route cache exists\n";
    echo "  File: {$routeCacheFile}\n";
    echo "  Size: " . filesize($routeCacheFile) . " bytes\n";
    echo "  Modified: " . date('Y-m-d H:i:s', filemtime($routeCacheFile)) . "\n";
} else {
    echo "  ✅ No route cache file\n";
}

// Test 6: Check view cache
echo "\n6. 📄 View Cache Status:\n";
$viewCacheDir = storage_path('framework/views');
if (is_dir($viewCacheDir)) {
    $viewFiles = glob($viewCacheDir . '/*.php');
    echo "  View cache files: " . count($viewFiles) . "\n";
    
    // Check for admin stories view cache
    $adminStoriesCache = null;
    foreach ($viewFiles as $file) {
        if (strpos(basename($file), 'admin') !== false && strpos(basename($file), 'stories') !== false) {
            $adminStoriesCache = $file;
            break;
        }
    }
    
    if ($adminStoriesCache) {
        echo "  ⚠️ Admin stories view cache exists: " . basename($adminStoriesCache) . "\n";
        echo "  Modified: " . date('Y-m-d H:i:s', filemtime($adminStoriesCache)) . "\n";
    } else {
        echo "  ✅ No admin stories view cache\n";
    }
} else {
    echo "  ✅ View cache directory doesn't exist\n";
}

// Test 7: Manual route helper test
echo "\n7. 🔧 Manual Route Helper Test:\n";
try {
    // Test with different parameters
    $tests = [
        'ID 3' => 3,
        'Story object' => $story,
        'Slug' => 'vo-thuong-sat-than'
    ];
    
    foreach ($tests as $desc => $param) {
        try {
            $route = route('admin.stories.smart-crawl', $param);
            echo "  ✅ {$desc}: {$route}\n";
        } catch (Exception $e) {
            echo "  ❌ {$desc}: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "  ❌ Manual route test error: " . $e->getMessage() . "\n";
}

echo "\n📋 SUMMARY:\n";
echo "Authentication: ✅ Working\n";
echo "Route registration: " . (count($storyRoutes) > 0 ? "✅ Found " . count($storyRoutes) . " routes" : "❌ No routes") . "\n";
echo "Route generation: Check results above\n";
echo "View rendering: Check results above\n";

echo "\n💡 RECOMMENDATIONS:\n";
echo "1. Clear all caches: php artisan optimize:clear\n";
echo "2. Check browser cache and hard refresh\n";
echo "3. Verify route names match exactly\n";
echo "4. Check for typos in view files\n";

echo "\n🌐 DIRECT TEST:\n";
echo "Try accessing: http://localhost:8000/admin/stories/3/smart-crawl\n";
echo "Expected: 302 redirect to login or 419 CSRF error\n";

echo "\n✅ Debug completed!\n";

?>
