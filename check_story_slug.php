<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK STORY SLUG FOR ID 3 ===\n";

try {
    $story = \App\Models\Story::find(3);
    
    if ($story) {
        echo "Story ID 3 found:\n";
        echo "  ID: " . $story->id . "\n";
        echo "  Title: " . $story->title . "\n";
        echo "  Slug: " . $story->slug . "\n";
        echo "  Crawl Status: " . $story->crawl_status . "\n";
        
        // Generate correct cancel crawl URL
        $cancelUrl = route('admin.stories.cancel-crawl', $story);
        echo "  Correct Cancel URL: " . $cancelUrl . "\n";
        
        // Test if slug resolves back to story
        $storyBySlug = \App\Models\Story::where('slug', $story->slug)->first();
        if ($storyBySlug && $storyBySlug->id == $story->id) {
            echo "  ✅ Slug resolves correctly\n";
        } else {
            echo "  ❌ Slug resolution issue\n";
        }
        
        // Check crawl status constants
        $crawlStatuses = config('constants.CRAWL_STATUS.VALUES');
        if (isset($crawlStatuses['CRAWLING']) && $story->crawl_status == $crawlStatuses['CRAWLING']) {
            echo "  ✅ Story is in CRAWLING status - cancel should be available\n";
        } else {
            echo "  ⚠️ Story is not in CRAWLING status - cancel may not be available\n";
        }
        
    } else {
        echo "❌ Story ID 3 not found\n";
    }
    
    // List all stories for reference
    echo "\nAll stories:\n";
    $stories = \App\Models\Story::select('id', 'title', 'slug', 'crawl_status')->get();
    foreach ($stories as $s) {
        echo "  ID: {$s->id}, Title: {$s->title}, Slug: {$s->slug}, Status: {$s->crawl_status}\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
