<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Chapter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing Chapter Paths...\n\n";

// 1. Fix content file_path (absolute to relative)
echo "1. Fixing content file_path...\n";
$contentChapters = Chapter::where('file_path', 'LIKE', 'C:%')->get();
$contentFixed = 0;

foreach ($contentChapters as $chapter) {
    $oldPath = $chapter->file_path;
    
    // Convert: C:\xampp\htdocs\audio-lara\storage/truyen/co-nang-huyen-hoc/chuong-1.txt
    // To: content/co-nang-huyen-hoc/chuong-1.txt
    if (strpos($oldPath, 'storage/truyen/') !== false) {
        $relativePath = str_replace('C:\xampp\htdocs\audio-lara\storage/truyen/', 'content/', $oldPath);
        $relativePath = str_replace('\\', '/', $relativePath);
        
        $chapter->file_path = $relativePath;
        $chapter->save();
        
        $contentFixed++;
        echo "  ✅ Chapter {$chapter->id}: {$oldPath} → {$relativePath}\n";
    }
}

echo "  📊 Fixed {$contentFixed} content paths\n\n";

// 2. Fix audio_file_path (old structure to new structure)
echo "2. Fixing audio_file_path...\n";
$audioChapters = Chapter::where('audio_file_path', 'LIKE', 'truyen/%')->get();
$audioFixed = 0;

foreach ($audioChapters as $chapter) {
    $oldPath = $chapter->audio_file_path;
    
    // Convert: truyen/mp3-tien-nghich/chuong_1.mp3
    // To: audio/tien-nghich/chuong_1.mp3
    if (strpos($oldPath, 'truyen/mp3-') !== false) {
        $relativePath = str_replace('truyen/mp3-', 'audio/', $oldPath);
        
        $chapter->audio_file_path = $relativePath;
        $chapter->save();
        
        $audioFixed++;
        echo "  ✅ Chapter {$chapter->id}: {$oldPath} → {$relativePath}\n";
    }
}

echo "  📊 Fixed {$audioFixed} audio paths\n\n";

// 3. Check for any remaining absolute paths
echo "3. Checking for remaining issues...\n";
$remainingContent = Chapter::where('file_path', 'LIKE', 'C:%')->count();
$remainingAudio = Chapter::where('audio_file_path', 'LIKE', 'C:%')->count();

echo "  📋 Remaining absolute content paths: {$remainingContent}\n";
echo "  📋 Remaining absolute audio paths: {$remainingAudio}\n\n";

// 4. Summary
echo "🎉 Path fixing completed!\n";
echo "  ✅ Content paths fixed: {$contentFixed}\n";
echo "  ✅ Audio paths fixed: {$audioFixed}\n";
echo "  ⚠️  Remaining issues: " . ($remainingContent + $remainingAudio) . "\n\n";

// 5. Show sample of fixed paths
echo "📋 Sample of current paths:\n";
$sampleContent = Chapter::whereNotNull('file_path')->take(3)->get(['id', 'chapter_number', 'file_path']);
$sampleAudio = Chapter::whereNotNull('audio_file_path')->take(3)->get(['id', 'chapter_number', 'audio_file_path']);

echo "  Content paths:\n";
foreach ($sampleContent as $chapter) {
    echo "    - Chapter {$chapter->id}: {$chapter->file_path}\n";
}

echo "  Audio paths:\n";
foreach ($sampleAudio as $chapter) {
    echo "    - Chapter {$chapter->id}: {$chapter->audio_file_path}\n";
}

echo "\n✨ Done!\n";
