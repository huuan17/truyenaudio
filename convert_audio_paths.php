<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Converting absolute audio paths to relative paths...\n";

// Get all chapters with audio paths
$chapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->get();

$converted = 0;

foreach ($chapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    
    echo "Chapter {$chapter->id}: {$audioPath}\n";
    
    // Skip if already relative path
    if (!str_contains($audioPath, 'storage/')) {
        echo "  -> Already relative, skipping\n";
        continue;
    }
    
    // Extract relative path from absolute path
    $relativePath = substr($audioPath, strpos($audioPath, 'storage/') + 8);
    
    // Update to relative path
    DB::table('chapters')
        ->where('id', $chapter->id)
        ->update(['audio_file_path' => $relativePath]);
        
    echo "  -> Converted to: {$relativePath}\n";
    $converted++;
}

echo "\nConversion completed! Converted {$converted} audio paths.\n";

// Test a few converted paths
echo "\nTesting converted paths:\n";
$testChapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->limit(3)
    ->get();

foreach ($testChapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    $fullPath = public_path('storage/' . $audioPath);
    $exists = file_exists($fullPath);
    
    echo "Chapter {$chapter->id}: {$audioPath}\n";
    echo "  Full path: {$fullPath}\n";
    echo "  Exists: " . ($exists ? 'YES' : 'NO') . "\n";
    echo "  URL: " . asset('storage/' . $audioPath) . "\n\n";
}
