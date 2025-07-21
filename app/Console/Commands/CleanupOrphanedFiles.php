<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupOrphanedFiles extends Command
{
    protected $signature = 'cleanup:orphaned-files {story_id?} {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Cleanup orphaned content files that exist in storage but not in database';

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be deleted');
        }
        
        $this->info('🧹 Starting orphaned files cleanup...');
        
        if ($storyId) {
            $stories = Story::where('id', $storyId)->get();
            if ($stories->isEmpty()) {
                $this->error("❌ Story ID {$storyId} not found");
                return 1;
            }
        } else {
            $stories = Story::all();
        }
        
        $totalOrphaned = 0;
        $totalDeleted = 0;
        
        foreach ($stories as $story) {
            $this->info("\n📚 Processing story: {$story->title} (ID: {$story->id})");
            
            $contentDir = storage_path('app/content/' . $story->folder_name);
            
            if (!is_dir($contentDir)) {
                $this->warn("  ⚠️ Content directory not found: {$contentDir}");
                continue;
            }
            
            // Get all content files
            $files = glob($contentDir . '/chuong-*.txt');
            $this->info("  📄 Found " . count($files) . " content files");
            
            // Get chapter numbers from database
            $dbChapters = Chapter::where('story_id', $story->id)
                ->pluck('chapter_number')
                ->toArray();
            $this->info("  🗄️ Found " . count($dbChapters) . " chapters in database");
            
            // Find orphaned files
            $orphanedFiles = [];
            foreach ($files as $file) {
                $filename = basename($file);
                if (preg_match('/chuong-(\d+)\.txt$/', $filename, $matches)) {
                    $chapterNumber = (int)$matches[1];
                    if (!in_array($chapterNumber, $dbChapters)) {
                        $orphanedFiles[] = [
                            'file' => $file,
                            'chapter_number' => $chapterNumber,
                            'size' => filesize($file)
                        ];
                    }
                }
            }
            
            if (empty($orphanedFiles)) {
                $this->info("  ✅ No orphaned files found");
                continue;
            }
            
            $this->warn("  🗑️ Found " . count($orphanedFiles) . " orphaned files:");
            
            $totalSize = 0;
            foreach ($orphanedFiles as $orphan) {
                $size = round($orphan['size'] / 1024, 2);
                $totalSize += $orphan['size'];
                $this->line("    - chuong-{$orphan['chapter_number']}.txt ({$size} KB)");
            }
            
            $totalSizeMB = round($totalSize / 1024 / 1024, 2);
            $this->info("  📊 Total size: {$totalSizeMB} MB");
            
            $totalOrphaned += count($orphanedFiles);
            
            if (!$dryRun) {
                if ($this->confirm("Delete these " . count($orphanedFiles) . " orphaned files?")) {
                    $deleted = 0;
                    foreach ($orphanedFiles as $orphan) {
                        try {
                            unlink($orphan['file']);
                            $deleted++;
                            $this->line("    ✅ Deleted: chuong-{$orphan['chapter_number']}.txt");
                        } catch (\Exception $e) {
                            $this->error("    ❌ Failed to delete chuong-{$orphan['chapter_number']}.txt: " . $e->getMessage());
                        }
                    }
                    $this->info("  🗑️ Deleted {$deleted} files");
                    $totalDeleted += $deleted;
                } else {
                    $this->info("  ⏭️ Skipped deletion for this story");
                }
            }
        }
        
        $this->info("\n📊 SUMMARY:");
        $this->info("  Total orphaned files found: {$totalOrphaned}");
        
        if (!$dryRun) {
            $this->info("  Total files deleted: {$totalDeleted}");
            if ($totalDeleted > 0) {
                $this->info("✅ Cleanup completed successfully!");
            }
        } else {
            $this->info("🔍 Dry run completed - no files were deleted");
            $this->info("💡 Run without --dry-run to actually delete files");
        }
        
        return 0;
    }
}
