<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Jobs\CrawlStoryJob;
use Illuminate\Support\Facades\Artisan;

echo "=== DEBUG SMART CRAWL - STEP 4: CrawlStoryJob Deep Dive ===\n";

$story = Story::find(3);

// Test 1: Check CrawlStoryJob class
echo "1. 🔍 CrawlStoryJob Class Check:\n";
try {
    $job = new CrawlStoryJob($story->id);
    echo "  ✅ CrawlStoryJob instantiated successfully\n";
    echo "  Story ID: {$story->id}\n";
    echo "  Job timeout: " . (property_exists($job, 'timeout') ? $job->timeout : 'default') . "\n";
    echo "  Job tries: " . (property_exists($job, 'tries') ? $job->tries : 'default') . "\n";
} catch (Exception $e) {
    echo "  ❌ CrawlStoryJob instantiation error: " . $e->getMessage() . "\n";
}

// Test 2: Check crawl:stories command directly
echo "\n2. 🎯 Direct Crawl Command Test:\n";
echo "  Testing: php artisan crawl:stories --story_id={$story->id}\n";

try {
    // Capture output
    $output = '';
    $exitCode = Artisan::call('crawl:stories', [
        '--story_id' => $story->id
    ], $output);
    
    echo "  Exit code: {$exitCode}\n";
    echo "  Output length: " . strlen(Artisan::output()) . " characters\n";
    
    $artisanOutput = Artisan::output();
    if ($artisanOutput) {
        echo "  Output preview:\n";
        $lines = explode("\n", $artisanOutput);
        foreach (array_slice($lines, 0, 10) as $line) {
            if (trim($line)) {
                echo "    " . trim($line) . "\n";
            }
        }
        if (count($lines) > 10) {
            echo "    ... (" . (count($lines) - 10) . " more lines)\n";
        }
    } else {
        echo "  ❌ No output from crawl command\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Crawl command error: " . $e->getMessage() . "\n";
}

// Test 3: Check story status before and after
echo "\n3. 📊 Story Status Before/After:\n";
$beforeStatus = $story->crawl_status;
$beforeChapters = $story->chapters()->count();

echo "  Before:\n";
echo "    Status: {$beforeStatus}\n";
echo "    Chapters: {$beforeChapters}\n";

// Refresh story
$story = $story->fresh();
$afterStatus = $story->crawl_status;
$afterChapters = $story->chapters()->count();

echo "  After:\n";
echo "    Status: {$afterStatus}\n";
echo "    Chapters: {$afterChapters}\n";
echo "    Change: " . ($afterChapters - $beforeChapters) . " chapters\n";

// Test 4: Check crawl command exists and is working
echo "\n4. 🔧 Crawl Command Verification:\n";
try {
    $commands = Artisan::all();
    $crawlCommand = null;
    
    foreach ($commands as $name => $command) {
        if ($name === 'crawl:stories') {
            $crawlCommand = $command;
            break;
        }
    }
    
    if ($crawlCommand) {
        echo "  ✅ crawl:stories command found\n";
        echo "  Command class: " . get_class($crawlCommand) . "\n";
        echo "  Description: " . $crawlCommand->getDescription() . "\n";
    } else {
        echo "  ❌ crawl:stories command not found\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Command verification error: " . $e->getMessage() . "\n";
}

// Test 5: Check if crawl command has story_id option
echo "\n5. ⚙️ Command Options Check:\n";
try {
    $helpOutput = '';
    Artisan::call('help', ['command_name' => 'crawl:stories']);
    $helpText = Artisan::output();
    
    if (strpos($helpText, '--story_id') !== false) {
        echo "  ✅ --story_id option found\n";
    } else {
        echo "  ❌ --story_id option not found\n";
        echo "  Help text preview:\n";
        $lines = explode("\n", $helpText);
        foreach (array_slice($lines, 0, 15) as $line) {
            if (trim($line)) {
                echo "    " . trim($line) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "  ❌ Help command error: " . $e->getMessage() . "\n";
}

// Test 6: Manual job execution with detailed logging
echo "\n6. 🧪 Manual Job Execution Test:\n";
try {
    echo "  Creating new CrawlStoryJob...\n";
    $job = new CrawlStoryJob($story->id);
    
    echo "  Setting up job environment...\n";
    // Create a mock job instance for getJobId()
    $mockJob = new class {
        public function getJobId() {
            return 'test_job_' . time();
        }
    };
    
    // Set the job property if it exists
    if (property_exists($job, 'job')) {
        $reflection = new ReflectionClass($job);
        $jobProperty = $reflection->getProperty('job');
        $jobProperty->setAccessible(true);
        $jobProperty->setValue($job, $mockJob);
    }
    
    echo "  Executing job handle method...\n";
    
    // Capture any output
    ob_start();
    $startTime = microtime(true);
    
    $job->handle();
    
    $endTime = microtime(true);
    $output = ob_get_clean();
    
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    echo "  ✅ Job executed successfully\n";
    echo "  Execution time: " . number_format($executionTime, 2) . "ms\n";
    
    if ($output) {
        echo "  Job output:\n";
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (trim($line)) {
                echo "    " . trim($line) . "\n";
            }
        }
    } else {
        echo "  ⚠️ No output from job execution\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Manual job execution error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n";
    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 5) as $frame) {
        if (isset($frame['file']) && isset($frame['line'])) {
            echo "    " . $frame['file'] . ":" . $frame['line'] . "\n";
        }
    }
}

// Test 7: Check final story state
echo "\n7. 📋 Final Story State:\n";
$story = $story->fresh();
echo "  Final status: {$story->crawl_status}\n";
echo "  Final chapters: " . $story->chapters()->count() . "\n";
echo "  Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

$statusLabels = config('constants.CRAWL_STATUS.LABELS');
echo "  Status label: " . ($statusLabels[$story->crawl_status] ?? 'Unknown') . "\n";

echo "\n📋 STEP 4 SUMMARY:\n";
echo "Job instantiation: " . (isset($job) ? "✅ Success" : "❌ Failed") . "\n";
echo "Crawl command: " . (isset($exitCode) ? "✅ Executed (code: {$exitCode})" : "❌ Failed") . "\n";
echo "Execution time: " . (isset($executionTime) ? number_format($executionTime, 2) . "ms" : "Unknown") . "\n";
echo "Chapter change: " . ($afterChapters - $beforeChapters) . "\n";

if (isset($executionTime) && $executionTime < 100) {
    echo "❌ ISSUE: Job executes too quickly (< 100ms)\n";
    echo "💡 LIKELY CAUSE: Job exits early due to condition or error\n";
} elseif (($afterChapters - $beforeChapters) === 0) {
    echo "❌ ISSUE: No new chapters added\n";
    echo "💡 LIKELY CAUSE: Crawl command not working or no missing chapters\n";
} else {
    echo "✅ Job appears to be working correctly\n";
}

echo "\n➡️ NEXT: Check crawl command implementation details\n";

?>
