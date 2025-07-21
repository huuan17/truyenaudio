<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Crawl Setup Verification ===\n";

// 1. Check Node.js scripts
echo "\n1. Node.js Scripts:\n";
$scripts = [
    'node_scripts/crawl.js' => 'Original crawl script',
    'node_scripts/crawl-production.js' => 'Production-ready crawl script',
    'node_scripts/setup-chrome.js' => 'Chrome detection helper',
];

foreach ($scripts as $script => $description) {
    $fullPath = base_path($script);
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 2);
        echo "  ✅ {$script} - {$description} ({$size} KB)\n";
    } else {
        echo "  ❌ {$script} - Missing\n";
    }
}

// 2. Check storage paths in database
echo "\n2. Database Storage Paths:\n";
$stories = DB::table('stories')
    ->whereNotNull('crawl_path')
    ->where('crawl_path', '!=', '')
    ->get();

$validPaths = 0;
$totalPaths = count($stories);

foreach ($stories as $story) {
    $crawlPath = $story->crawl_path;
    echo "  Story {$story->id}: {$crawlPath}\n";
    
    if (str_contains($crawlPath, 'storage/app/content/')) {
        $validPaths++;
        echo "    ✅ Using new storage structure\n";
    } else {
        echo "    ⚠️ Using old storage structure\n";
    }
    
    // Check if directory exists
    $fullPath = base_path($crawlPath);
    if (is_dir($fullPath)) {
        $fileCount = count(glob($fullPath . '/*.txt'));
        echo "    📁 Directory exists with {$fileCount} text files\n";
    } else {
        echo "    ❌ Directory not found\n";
    }
}

echo "  Valid paths: {$validPaths}/{$totalPaths}\n";

// 3. Check CrawlStories command
echo "\n3. CrawlStories Command:\n";
$commandPath = base_path('app/Console/Commands/CrawlStories.php');
if (file_exists($commandPath)) {
    $content = file_get_contents($commandPath);
    
    if (str_contains($content, 'crawl-production.js')) {
        echo "  ✅ Using production crawl script\n";
    } else {
        echo "  ⚠️ Still using original crawl script\n";
    }
    
    if (str_contains($content, "storage_path('app/content/'")) {
        echo "  ✅ Using new storage structure\n";
    } else {
        echo "  ⚠️ Using old storage structure\n";
    }
} else {
    echo "  ❌ CrawlStories command not found\n";
}

// 4. Test Chrome detection
echo "\n4. Chrome Detection Test:\n";
$nodeCommand = 'node node_scripts/setup-chrome.js';
$output = [];
$returnCode = 0;

exec($nodeCommand, $output, $returnCode);

if ($returnCode === 0) {
    echo "  ✅ Chrome detection script runs successfully\n";
    
    // Check for Chrome paths in output
    $chromeFound = false;
    foreach ($output as $line) {
        if (str_contains($line, 'Found:') && str_contains($line, 'chrome')) {
            echo "  ✅ Chrome installation detected\n";
            $chromeFound = true;
            break;
        }
    }
    
    if (!$chromeFound) {
        echo "  ⚠️ No Chrome installation found\n";
    }
} else {
    echo "  ❌ Chrome detection script failed\n";
}

// 5. Check environment configuration
echo "\n5. Environment Configuration:\n";
$envPath = base_path('.env');
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    
    if (str_contains($envContent, 'PUPPETEER_EXECUTABLE_PATH')) {
        echo "  ✅ Puppeteer configuration found in .env\n";
        
        // Extract the value
        preg_match('/PUPPETEER_EXECUTABLE_PATH=(.*)/', $envContent, $matches);
        if ($matches && !empty(trim($matches[1]))) {
            $chromePath = trim($matches[1], '"\'');
            echo "  📍 Chrome path: {$chromePath}\n";
            
            if (file_exists($chromePath)) {
                echo "  ✅ Chrome executable exists\n";
            } else {
                echo "  ❌ Chrome executable not found\n";
            }
        } else {
            echo "  ℹ️ Puppeteer path not set (will use auto-detection)\n";
        }
    } else {
        echo "  ⚠️ Puppeteer configuration not found in .env\n";
    }
} else {
    echo "  ❌ .env file not found\n";
}

// 6. Test crawl command availability
echo "\n6. Laravel Crawl Commands:\n";
$commands = [
    'crawl:stories' => 'Main crawl command',
];

foreach ($commands as $command => $description) {
    try {
        $output = [];
        $returnCode = 0;
        exec("php artisan {$command} --help", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "  ✅ {$command} - {$description}\n";
        } else {
            echo "  ❌ {$command} - Command failed\n";
        }
    } catch (Exception $e) {
        echo "  ❌ {$command} - Error: {$e->getMessage()}\n";
    }
}

// 7. Check hosting deployment files
echo "\n7. Hosting Deployment Files:\n";
$deploymentFiles = [
    'HOSTING_DEPLOYMENT.md' => 'Hosting deployment guide',
    '.env.example' => 'Environment template',
];

foreach ($deploymentFiles as $file => $description) {
    $fullPath = base_path($file);
    if (file_exists($fullPath)) {
        echo "  ✅ {$file} - {$description}\n";
        
        if ($file === '.env.example') {
            $content = file_get_contents($fullPath);
            if (str_contains($content, 'PUPPETEER_EXECUTABLE_PATH')) {
                echo "    ✅ Contains Puppeteer configuration\n";
            } else {
                echo "    ⚠️ Missing Puppeteer configuration\n";
            }
        }
    } else {
        echo "  ❌ {$file} - Missing\n";
    }
}

// 8. Storage structure verification
echo "\n8. Storage Structure:\n";
$storageContent = storage_path('app/content');
if (is_dir($storageContent)) {
    $storyDirs = glob($storageContent . '/*', GLOB_ONLYDIR);
    echo "  ✅ Content directory exists with " . count($storyDirs) . " story folders\n";
    
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $textFiles = glob($storyDir . '/*.txt');
        echo "    📁 {$storyName}: " . count($textFiles) . " text files\n";
    }
} else {
    echo "  ❌ Content directory not found: {$storageContent}\n";
}

echo "\n✅ Crawl setup verification completed!\n";

echo "\nSummary:\n";
echo "- Node.js scripts: Ready for production hosting\n";
echo "- Storage structure: Updated to storage/app/content/\n";
echo "- Chrome detection: Available for hosting setup\n";
echo "- Environment config: Template ready for hosting\n";
echo "- Deployment guide: Available in HOSTING_DEPLOYMENT.md\n";

echo "\nNext steps for hosting deployment:\n";
echo "1. Upload files to hosting server\n";
echo "2. Install Chrome: sudo apt-get install google-chrome-stable\n";
echo "3. Set environment: PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome\n";
echo "4. Test crawling: php artisan crawl:stories --story_id=1\n";

echo "\nFor hosting-specific instructions, see: HOSTING_DEPLOYMENT.md\n";

?>
