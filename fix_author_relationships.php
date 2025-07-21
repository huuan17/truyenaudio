<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Author;
use App\Models\Story;
use Illuminate\Support\Str;

echo "Fixing author-story relationships...\n";

// Lấy tất cả stories
$stories = Story::all();

foreach ($stories as $story) {
    echo "\nProcessing story: {$story->title}\n";
    echo "  Current author field: {$story->author}\n";
    echo "  Current author_id: {$story->author_id}\n";
    
    if (!empty($story->author)) {
        // Tìm author theo tên
        $author = Author::where('name', $story->author)->first();
        
        if (!$author) {
            // Tạo author mới nếu chưa có
            $author = Author::create([
                'name' => $story->author,
                'slug' => Str::slug($story->author),
                'bio' => "Tác giả của truyện \"{$story->title}\" và nhiều tác phẩm khác.",
                'is_active' => true,
                'meta_title' => $story->author . ' - Tác giả truyện audio',
                'meta_description' => "Tìm hiểu về tác giả {$story->author}. Đọc và nghe truyện audio của {$story->author} tại Audio Lara.",
                'meta_keywords' => $story->author . ', tác giả, truyện audio, sách nói'
            ]);
            echo "  → Created new author: {$author->name} (ID: {$author->id})\n";
        } else {
            echo "  → Found existing author: {$author->name} (ID: {$author->id})\n";
        }
        
        // Cập nhật story với đúng author_id
        if ($story->author_id != $author->id) {
            $story->update(['author_id' => $author->id]);
            echo "  → Updated story author_id from {$story->author_id} to {$author->id}\n";
        } else {
            echo "  → Story already has correct author_id\n";
        }
    } else {
        echo "  → No author field, skipping\n";
    }
}

echo "\n=== Final Statistics ===\n";

$authors = Author::withCount('stories')->get();
foreach ($authors as $author) {
    echo "Author: {$author->name} (slug: {$author->slug}) - {$author->stories_count} stories\n";
    
    $stories = $author->stories;
    foreach ($stories as $story) {
        echo "  - {$story->title}\n";
    }
}

echo "\nDone!\n";
