<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Chapter;
use App\Models\Story;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Paths Status...\n\n";

// 1. Check content files
echo "1. Content Files Status:\n";
$chaptersWithContent = Chapter::whereNotNull('file_path')->get();
$contentExists = 0;
$contentMissing = 0;

foreach ($chaptersWithContent as $chapter) {
    if ($chapter->hasContentFile()) {
        $contentExists++;
    } else {
        $contentMissing++;
        echo "  ❌ Missing: Chapter {$chapter->id} - {$chapter->file_path}\n";
    }
}

echo "  ✅ Content files exist: {$contentExists}\n";
echo "  ❌ Content files missing: {$contentMissing}\n\n";

// 2. Check audio files
echo "2. Audio Files Status:\n";
$chaptersWithAudio = Chapter::whereNotNull('audio_file_path')->get();
$audioExists = 0;
$audioMissing = 0;

foreach ($chaptersWithAudio as $chapter) {
    $audioPath = storage_path('app/' . $chapter->audio_file_path);
    if (file_exists($audioPath)) {
        $audioExists++;
    } else {
        $audioMissing++;
        echo "  ❌ Missing: Chapter {$chapter->id} - {$chapter->audio_file_path}\n";
    }
}

echo "  ✅ Audio files exist: {$audioExists}\n";
echo "  ❌ Audio files missing: {$audioMissing}\n\n";

// 3. Check path formats
echo "3. Path Format Analysis:\n";
$absoluteContent = Chapter::where('file_path', 'LIKE', 'C:%')->count();
$absoluteAudio = Chapter::where('audio_file_path', 'LIKE', 'C:%')->count();
$relativeContent = Chapter::where('file_path', 'LIKE', 'content/%')->count();
$relativeAudio = Chapter::where('audio_file_path', 'LIKE', 'audio/%')->count();

echo "  📋 Content paths:\n";
echo "    - Absolute paths (C:): {$absoluteContent}\n";
echo "    - Relative paths (content/): {$relativeContent}\n";
echo "  📋 Audio paths:\n";
echo "    - Absolute paths (C:): {$absoluteAudio}\n";
echo "    - Relative paths (audio/): {$relativeAudio}\n\n";

// 4. Check storage structure
echo "4. Storage Structure:\n";
$contentDir = storage_path('app/content');
$audioDir = storage_path('app/audio');

echo "  📁 Content directory: " . (is_dir($contentDir) ? '✅ Exists' : '❌ Missing') . "\n";
echo "  📁 Audio directory: " . (is_dir($audioDir) ? '✅ Exists' : '❌ Missing') . "\n";

if (is_dir($contentDir)) {
    $contentStories = array_filter(scandir($contentDir), function($item) use ($contentDir) {
        return $item !== '.' && $item !== '..' && is_dir($contentDir . '/' . $item);
    });
    echo "    - Content stories: " . count($contentStories) . " (" . implode(', ', array_slice($contentStories, 0, 3)) . "...)\n";
}

if (is_dir($audioDir)) {
    $audioStories = array_filter(scandir($audioDir), function($item) use ($audioDir) {
        return $item !== '.' && $item !== '..' && is_dir($audioDir . '/' . $item);
    });
    echo "    - Audio stories: " . count($audioStories) . " (" . implode(', ', array_slice($audioStories, 0, 3)) . "...)\n";
}

echo "\n";

// 5. Sample working examples
echo "5. Sample Working Examples:\n";
$workingChapter = Chapter::whereNotNull('file_path')
    ->whereNotNull('audio_file_path')
    ->where('file_path', 'LIKE', 'content/%')
    ->where('audio_file_path', 'LIKE', 'audio/%')
    ->first();

if ($workingChapter) {
    echo "  📋 Chapter {$workingChapter->id}:\n";
    echo "    - Content: {$workingChapter->file_path} (" . ($workingChapter->hasContentFile() ? '✅' : '❌') . ")\n";
    echo "    - Audio: {$workingChapter->audio_file_path} (" . (file_exists(storage_path('app/' . $workingChapter->audio_file_path)) ? '✅' : '❌') . ")\n";
    echo "    - Audio URL: {$workingChapter->audio_url}\n";
}

echo "\n";

// 6. Summary
echo "🎯 Summary:\n";
$totalChapters = Chapter::count();
$chaptersWithBothFiles = Chapter::whereNotNull('file_path')
    ->whereNotNull('audio_file_path')
    ->count();

echo "  📊 Total chapters: {$totalChapters}\n";
echo "  📊 Chapters with both content & audio: {$chaptersWithBothFiles}\n";
echo "  📊 Content files working: {$contentExists}/{$relativeContent}\n";
echo "  📊 Audio files working: {$audioExists}/{$relativeAudio}\n";

$successRate = $totalChapters > 0 ? round(($contentExists + $audioExists) / ($totalChapters * 2) * 100, 1) : 0;
echo "  📊 Overall success rate: {$successRate}%\n";

if ($absoluteContent == 0 && $absoluteAudio == 0 && $contentMissing == 0 && $audioMissing == 0) {
    echo "\n🎉 All paths are fixed and working correctly!\n";
} else {
    echo "\n⚠️  Some issues remain to be fixed.\n";
}

echo "\n✨ Done!\n";
