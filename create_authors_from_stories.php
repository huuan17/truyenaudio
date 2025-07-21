<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Author;
use App\Models\Story;
use Illuminate\Support\Str;

echo "Creating authors from existing story data...\n";

// Lấy tất cả stories có trường author
$stories = Story::whereNotNull('author')
    ->where('author', '!=', '')
    ->get();

echo "Found " . $stories->count() . " stories with author data.\n";

$createdAuthors = 0;
$updatedStories = 0;

foreach ($stories as $story) {
    $authorName = trim($story->author);
    
    if (empty($authorName)) {
        continue;
    }

    echo "Processing story: {$story->title} - Author: {$authorName}\n";

    // Tìm hoặc tạo author
    $author = Author::where('name', $authorName)->first();
    
    if (!$author) {
        // Tạo author mới
        $author = Author::create([
            'name' => $authorName,
            'slug' => Str::slug($authorName),
            'bio' => "Tác giả của truyện \"{$story->title}\" và nhiều tác phẩm khác.",
            'is_active' => true,
            'meta_title' => $authorName . ' - Tác giả truyện audio',
            'meta_description' => "Tìm hiểu về tác giả {$authorName}. Đọc và nghe truyện audio của {$authorName} tại Audio Lara.",
            'meta_keywords' => $authorName . ', tác giả, truyện audio, sách nói'
        ]);
        
        $createdAuthors++;
        echo "  → Created author: {$authorName}\n";
    } else {
        echo "  → Found existing author: {$authorName}\n";
    }

    // Cập nhật story với author_id nếu chưa có
    if (!$story->author_id) {
        $story->update(['author_id' => $author->id]);
        $updatedStories++;
        echo "  → Linked story to author\n";
    } else {
        echo "  → Story already linked to an author\n";
    }
}

echo "\nCompleted!\n";
echo "- Created {$createdAuthors} new authors\n";
echo "- Updated {$updatedStories} stories with author links\n";

// Hiển thị thống kê
$totalAuthors = Author::count();
$storiesWithAuthors = Story::whereNotNull('author_id')->count();
$storiesWithoutAuthors = Story::whereNull('author_id')->count();

echo "\nCurrent statistics:\n";
echo "- Total authors: {$totalAuthors}\n";
echo "- Stories with authors: {$storiesWithAuthors}\n";
echo "- Stories without authors: {$storiesWithoutAuthors}\n";

// Hiển thị một số authors đã tạo
echo "\nSample authors created:\n";
$sampleAuthors = Author::limit(5)->get();
foreach ($sampleAuthors as $author) {
    $storiesCount = $author->stories()->count();
    echo "- {$author->name} ({$storiesCount} stories) - /author/{$author->slug}\n";
}

echo "\nDone!\n";
