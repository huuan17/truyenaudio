<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING CHAPTERS COUNT ===\n";

$story = App\Models\Story::find(3);

echo "Story: {$story->title}\n";

// Method 1: Direct DB query
$directCount = DB::table('chapters')->where('story_id', 3)->count();
echo "Direct DB query: {$directCount}\n";

// Method 2: Eloquent relationship
$relationshipCount = $story->chapters()->count();
echo "Eloquent relationship: {$relationshipCount}\n";

// Method 3: Load relationship and count
$loadedCount = $story->chapters->count();
echo "Loaded relationship: {$loadedCount}\n";

// Check if there are any soft deletes or conditions
echo "\nChecking chapters table structure...\n";
$sampleChapters = DB::table('chapters')
    ->where('story_id', 3)
    ->select('id', 'story_id', 'chapter_number', 'deleted_at')
    ->orderBy('id')
    ->limit(5)
    ->get();

echo "Sample chapters:\n";
foreach ($sampleChapters as $chapter) {
    echo "  ID: {$chapter->id}, Story: {$chapter->story_id}, Chapter: {$chapter->chapter_number}, Deleted: " . ($chapter->deleted_at ?? 'NULL') . "\n";
}

// Check for soft deletes
$softDeletedCount = DB::table('chapters')
    ->where('story_id', 3)
    ->whereNotNull('deleted_at')
    ->count();

echo "\nSoft deleted chapters: {$softDeletedCount}\n";

// Check the Chapter model for any global scopes
echo "\nChecking Chapter model...\n";
$chapterModel = new App\Models\Chapter();
echo "Chapter model class: " . get_class($chapterModel) . "\n";

// Check if SoftDeletes trait is used
$traits = class_uses_recursive(App\Models\Chapter::class);
echo "Traits used: " . implode(', ', array_keys($traits)) . "\n";
