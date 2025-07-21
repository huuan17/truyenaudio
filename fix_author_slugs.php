<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Author;
use Illuminate\Support\Str;

echo "Fixing author slugs...\n";

$authors = Author::all();

foreach ($authors as $author) {
    echo "Author: {$author->name}\n";
    echo "  Current slug: {$author->slug}\n";
    
    if (empty($author->slug)) {
        $newSlug = Str::slug($author->name);
        $author->update(['slug' => $newSlug]);
        echo "  → Updated slug to: {$newSlug}\n";
    } else {
        echo "  → Slug OK\n";
    }
}

echo "\nDone!\n";

// Test author relationships
echo "\nTesting author relationships:\n";
$story = \App\Models\Story::with('author')->where('slug', 'tien-nghich')->first();
if ($story && $story->author_id) {
    $author = $story->author;
    if ($author) {
        echo "Story: {$story->title}\n";
        echo "Author: {$author->name}\n";
        echo "Author slug: {$author->slug}\n";
        echo "Author URL: /author/{$author->slug}\n";
    } else {
        echo "Author relationship not loaded\n";
    }
} else {
    echo "Story not found or no author_id\n";
}
