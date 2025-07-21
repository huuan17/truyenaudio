<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;

class TestMissingChaptersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:missing-chapters {story_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and log missing chapters for a story';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storyId = $this->argument('story_id');
        $story = Story::find($storyId);

        if (!$story) {
            $this->error("Story with ID {$storyId} not found");
            return Command::FAILURE;
        }

        $this->info("Testing missing chapters for story: {$story->title}");
        $this->line(str_repeat('=', 60));

        // Find missing chapters
        $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
        $existingChapters = $story->chapters()->pluck('chapter_number')->toArray();
        $allChapters = range($story->start_chapter, $story->end_chapter);
        $missingChapters = array_diff($allChapters, $existingChapters);

        $this->info("Expected total chapters: {$expectedTotal}");
        $this->info("Chapters in database: " . count($existingChapters));
        $this->info("Missing chapters: " . count($missingChapters));

        if (!empty($missingChapters)) {
            $this->warn("Missing chapter numbers: " . implode(', ', array_slice($missingChapters, 0, 20)));
            if (count($missingChapters) > 20) {
                $this->warn("... and " . (count($missingChapters) - 20) . " more");
            }

            // Test crawl a few missing chapters
            $testChapters = array_slice($missingChapters, 0, 3);
            $this->info("\nTesting crawl for first 3 missing chapters...");

            foreach ($testChapters as $chapterNum) {
                $this->line("Testing chapter {$chapterNum}:");
                
                // Test URL
                $testUrl = str_replace('{chapter}', $chapterNum, $story->source_url) . $chapterNum . '.html';
                $this->line("  URL: {$testUrl}");

                // Test crawl command
                $command = "node \"node_scripts/crawl_original_cjs.cjs\" \"{$story->source_url}\" {$chapterNum} {$chapterNum} \"storage/app/content/{$story->folder_name}\" 1";
                $this->line("  Command: {$command}");

                $output = [];
                $returnCode = 0;
                exec($command . " 2>&1", $output, $returnCode);

                if ($returnCode === 0) {
                    $this->info("  ✅ Crawl successful");
                    
                    // Check if file was created
                    $filePath = storage_path("app/content/{$story->folder_name}/chuong-{$chapterNum}.txt");
                    if (file_exists($filePath)) {
                        $fileSize = filesize($filePath);
                        $this->info("  📄 File created: {$fileSize} bytes");
                    } else {
                        $this->warn("  ⚠️ File not created");
                    }
                } else {
                    $this->error("  ❌ Crawl failed (exit code: {$returnCode})");
                    $this->line("  Output: " . implode("\n  ", $output));
                    
                    // Log this as missing at source
                    $this->warn("  🔍 Chapter {$chapterNum} may not exist at source");
                }
            }

            // Update story with missing chapters info
            $notFoundChapters = [];
            foreach ($testChapters as $chapterNum) {
                $filePath = storage_path("app/content/{$story->folder_name}/chuong-{$chapterNum}.txt");
                if (!file_exists($filePath)) {
                    $notFoundChapters[] = $chapterNum;
                }
            }

            if (!empty($notFoundChapters)) {
                $story->update([
                    'missing_chapters_info' => [
                        'chapters' => $missingChapters,
                        'last_check' => now()->toDateTimeString(),
                        'reason' => 'not_found_at_source',
                        'tested_chapters' => $testChapters,
                        'confirmed_missing' => $notFoundChapters
                    ]
                ]);

                $this->warn("\n📝 Updated story with missing chapters info");
                $this->warn("Confirmed missing at source: " . implode(', ', $notFoundChapters));
                
                // Log to Laravel log
                \Log::warning("Chapters not found at source during manual test", [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                    'missing_chapters' => $missingChapters,
                    'tested_chapters' => $testChapters,
                    'confirmed_missing' => $notFoundChapters,
                    'source_url_pattern' => $story->source_url,
                    'message' => 'These chapters may not exist at the crawl source or have different URL structure'
                ]);
            }

        } else {
            $this->info("✅ No missing chapters found!");
        }

        return Command::SUCCESS;
    }
}
