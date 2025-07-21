<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Http\Controllers\Admin\StoryController;
use Illuminate\Http\Request;

echo "=== DEBUG SMART CRAWL - STEP 2: Routes & Controller ===\n";

$story = Story::find(3);

// Test 1: Route generation
echo "1. 🔗 Route Generation Test:\n";
try {
    $testRoute = route('test.smart-crawl', $story->slug);
    echo "  ✅ Test route: {$testRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Test route error: " . $e->getMessage() . "\n";
}

try {
    $adminRoute = route('admin.stories.smart-crawl', $story);
    echo "  ✅ Admin route: {$adminRoute}\n";
} catch (Exception $e) {
    echo "  ❌ Admin route error: " . $e->getMessage() . "\n";
}

// Test 2: Controller GET method
echo "\n2. 🎯 Controller GET Method Test:\n";
try {
    $controller = new StoryController();
    $request = new Request();
    $request->setMethod('GET');
    
    $response = $controller->smartCrawl($request, $story);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "  ✅ GET method works\n";
        echo "  View name: " . $response->getName() . "\n";
        
        $data = $response->getData();
        echo "  Data keys: " . implode(', ', array_keys($data)) . "\n";
        echo "  Missing chapters: " . $data['missing_count'] . "\n";
        echo "  Existing chapters: " . $data['existing_count'] . "\n";
        echo "  Total chapters: " . $data['total_chapters'] . "\n";
        echo "  Status label: " . $data['status_label'] . "\n";
        echo "  Pending jobs: " . $data['pending_jobs'] . "\n";
    } else {
        echo "  ❌ GET method doesn't return view\n";
        echo "  Response type: " . get_class($response) . "\n";
    }
} catch (Exception $e) {
    echo "  ❌ GET method error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 3: Controller POST method (simulate)
echo "\n3. 🚀 Controller POST Method Test:\n";
try {
    $controller = new StoryController();
    $request = new Request();
    $request->setMethod('POST');
    
    // Add fake CSRF token
    $request->merge(['_token' => 'test_token']);
    
    echo "  Testing POST request...\n";
    $response = $controller->smartCrawl($request, $story);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "  ✅ POST method works\n";
        echo "  Redirect URL: " . $response->getTargetUrl() . "\n";
        
        // Check session messages
        $successMessage = session('success');
        $errorMessage = session('error');
        $infoMessage = session('info');
        
        if ($successMessage) {
            echo "  ✅ Success: {$successMessage}\n";
        }
        if ($errorMessage) {
            echo "  ❌ Error: {$errorMessage}\n";
        }
        if ($infoMessage) {
            echo "  ℹ️ Info: {$infoMessage}\n";
        }
        
        if (!$successMessage && !$errorMessage && !$infoMessage) {
            echo "  ⚠️ No session message found\n";
        }
    } else {
        echo "  ❌ POST method doesn't return redirect\n";
        echo "  Response type: " . get_class($response) . "\n";
    }
} catch (Exception $e) {
    echo "  ❌ POST method error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

// Test 4: Check story status after POST
echo "\n4. 📊 Story Status After POST:\n";
$story = $story->fresh();
echo "  Crawl status: {$story->crawl_status}\n";
echo "  Crawl job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

$statusLabels = config('constants.CRAWL_STATUS.LABELS');
echo "  Status label: " . ($statusLabels[$story->crawl_status] ?? 'Unknown') . "\n";

// Test 5: Check queue after POST
echo "\n5. ⚡ Queue Status After POST:\n";
$jobs = DB::table('jobs')->get();
echo "  Total jobs: " . count($jobs) . "\n";

if (count($jobs) > 0) {
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "  Job: {$displayName} (ID: {$job->id}, attempts: {$job->attempts})\n";
        
        // Check if it's our story's job
        if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
            try {
                $jobData = unserialize($payload['data']['command']);
                if (isset($jobData->storyId) && $jobData->storyId == $story->id) {
                    echo "    ✅ This job is for our story (ID: {$story->id})\n";
                }
            } catch (Exception $e) {
                echo "    ⚠️ Could not parse job data\n";
            }
        }
    }
}

// Test 6: URL accessibility
echo "\n6. 🌐 URL Accessibility Test:\n";
$urls = [
    'http://localhost:8000/test-smart-crawl/vo-thuong-sat-than',
    'http://localhost:8000/admin/stories/vo-thuong-sat-than/smart-crawl'
];

foreach ($urls as $url) {
    $cmd = "curl -s -o /dev/null -w \"%{http_code}\" \"{$url}\"";
    $httpCode = trim(shell_exec($cmd));
    
    echo "  {$url}\n";
    echo "    Status: {$httpCode}\n";
    
    if ($httpCode === '200') {
        echo "    ✅ Accessible\n";
    } elseif ($httpCode === '302') {
        echo "    ✅ Redirects (needs auth)\n";
    } elseif ($httpCode === '404') {
        echo "    ❌ Not found\n";
    } else {
        echo "    ⚠️ Other status\n";
    }
}

echo "\n📋 STEP 2 SUMMARY:\n";
echo "Routes: " . (isset($testRoute) ? "✅ Working" : "❌ Failed") . "\n";
echo "GET method: " . (isset($data) ? "✅ Working" : "❌ Failed") . "\n";
echo "POST method: " . (isset($successMessage) || isset($errorMessage) ? "✅ Working" : "❌ Failed") . "\n";
echo "Queue: " . count($jobs) . " jobs\n";

if (isset($errorMessage)) {
    echo "❌ ISSUE FOUND: {$errorMessage}\n";
} elseif (isset($successMessage)) {
    echo "✅ SUCCESS: {$successMessage}\n";
} else {
    echo "⚠️ UNCLEAR: No clear success/error message\n";
}

echo "\n➡️ NEXT: Run debug_smart_crawl_step3.php to test specific issues\n";

?>
