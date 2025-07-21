<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PreCreateChapterFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stories:create-directories
                            {--story_id= : Specific story ID to process}
                            {--all : Process all stories}';

    /**
     * The console command description.
     */
    protected $description = 'Create storage directories for stories to prevent permission issues during crawl';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📁 Starting creation of storage directories...');

        // Determine which stories to process
        if ($this->option('story_id')) {
            $stories = Story::where('id', $this->option('story_id'))->get();
            if ($stories->isEmpty()) {
                $this->error("Story with ID {$this->option('story_id')} not found.");
                return 1;
            }
        } elseif ($this->option('all')) {
            $stories = Story::all();
        } else {
            // Default: process stories without storage directories
            $stories = Story::all()->filter(function ($story) {
                $storageDir = storage_path('app/content/' . $story->folder_name);
                return !is_dir($storageDir);
            });
        }

        if ($stories->isEmpty()) {
            $this->info('No stories found to process.');
            return 0;
        }

        $this->info("Found {$stories->count()} stories to process.");

        $totalCreated = 0;
        $totalStories = 0;

        foreach ($stories as $story) {
            $this->info("Processing: {$story->title} (ID: {$story->id})");

            $created = $this->createStorageDirectory($story);
            if ($created) {
                $totalCreated++;
                $this->line("  ✅ Created directory for {$story->title}");
            } else {
                $this->line("  ⚠️ Directory already exists for {$story->title}");
            }
            $totalStories++;
        }

        $this->info("🎉 Completed! Processed {$totalStories} stories, created {$totalCreated} directories total.");
        return 0;
    }

    /**
     * Create storage directory for a story
     */
    private function createStorageDirectory(Story $story)
    {
        try {
            // Create storage directory only
            $storageDir = storage_path('app/content/' . $story->folder_name);

            if (!is_dir($storageDir)) {
                if (File::makeDirectory($storageDir, 0755, true)) {
                    $this->line("  📁 Created directory: {$storageDir}");

                    \Log::info("Created storage directory for story", [
                        'story_id' => $story->id,
                        'story_title' => $story->title,
                        'storage_directory' => $storageDir,
                        'permissions' => '0755'
                    ]);

                    return true;
                } else {
                    $this->error("  ❌ Failed to create directory: {$storageDir}");

                    \Log::error("Failed to create storage directory", [
                        'story_id' => $story->id,
                        'story_title' => $story->title,
                        'storage_directory' => $storageDir
                    ]);

                    return false;
                }
            } else {
                $this->line("  ℹ️ Directory already exists: {$storageDir}");
                return false; // Not created, already exists
            }

        } catch (\Exception $e) {
            $this->error("  ❌ Error processing {$story->title}: " . $e->getMessage());

            \Log::error("Error creating storage directory", [
                'story_id' => $story->id,
                'story_title' => $story->title,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return false;
        }
    }


}
