<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\File;

echo "=== Debug Crawl Direct Test ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Story info:\n";
echo "  Title: {$story->title}\n";
echo "  Source URL: {$story->source_url}\n";
echo "  Chapter range: {$story->start_chapter} - {$story->end_chapter}\n";
echo "  Folder: {$story->folder_name}\n";

// Test parameters
$baseUrl = $story->source_url;
$startChapter = $story->start_chapter;
$endChapter = $story->end_chapter;
$outputFolder = storage_path('app/content/' . $story->folder_name);

echo "\nCrawl parameters:\n";
echo "  Base URL: {$baseUrl}\n";
echo "  Start: {$startChapter}\n";
echo "  End: {$endChapter}\n";
echo "  Output: {$outputFolder}\n";

// Create output directory
if (!File::isDirectory($outputFolder)) {
    File::makeDirectory($outputFolder, 0755, true);
    echo "  ✅ Created output directory\n";
} else {
    echo "  ✅ Output directory exists\n";
}

// Test script path
$scriptPath = base_path('node_scripts/crawl_basic.js');
echo "\nScript info:\n";
echo "  Script path: {$scriptPath}\n";
echo "  Script exists: " . (File::exists($scriptPath) ? 'YES' : 'NO') . "\n";

if (File::exists($scriptPath)) {
    $scriptSize = round(File::size($scriptPath) / 1024, 2);
    echo "  Script size: {$scriptSize} KB\n";
}

// Build command
$command = sprintf(
    'node %s %s %d %d %s %d',
    escapeshellarg($scriptPath),
    escapeshellarg($baseUrl),
    $startChapter,
    min($startChapter + 2, $endChapter), // Test with max 3 chapters
    escapeshellarg($outputFolder),
    0 // Not single mode
);

echo "\nCommand to execute:\n";
echo "  {$command}\n";

echo "\nExecuting command...\n";

$output = [];
$exitCode = 0;
exec($command . ' 2>&1', $output, $exitCode);

echo "Exit code: {$exitCode}\n";
echo "Output:\n";
foreach ($output as $line) {
    echo "  {$line}\n";
}

// Check results
echo "\nChecking results:\n";
$files = glob($outputFolder . '/*.txt');
echo "  Files created: " . count($files) . "\n";

if (count($files) > 0) {
    echo "  ✅ Success! Files found:\n";
    foreach ($files as $file) {
        $filename = basename($file);
        $size = round(filesize($file) / 1024, 2);
        echo "    - {$filename} ({$size} KB)\n";
        
        // Show preview
        $content = file_get_contents($file);
        $preview = substr($content, 0, 100);
        echo "      Preview: {$preview}...\n";
    }
} else {
    echo "  ❌ No files created\n";
    
    echo "\nTroubleshooting:\n";
    echo "  1. Check Node.js: node --version\n";
    echo "  2. Check Puppeteer: npm list puppeteer\n";
    echo "  3. Test URL manually: {$baseUrl}{$startChapter}.html\n";
    echo "  4. Check Chrome installation\n";
}

echo "\n✅ Debug completed!\n";

?>
