<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncAudioFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:sync {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync audio files from storage/app/audio to storage/app/public/audio for web access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $sourceDir = storage_path('app/audio');
        $targetDir = storage_path('app/public/audio');
        
        $this->info('Syncing audio files for web access...');
        $this->line("Source: {$sourceDir}");
        $this->line("Target: {$targetDir}");
        $this->line(str_repeat('=', 60));

        if (!File::isDirectory($sourceDir)) {
            $this->error("Source directory does not exist: {$sourceDir}");
            return Command::FAILURE;
        }

        // Create target directory if it doesn't exist
        if (!File::isDirectory($targetDir)) {
            if (!$dryRun) {
                File::makeDirectory($targetDir, 0755, true);
                $this->info("✅ Created target directory: {$targetDir}");
            } else {
                $this->comment("🔍 Would create target directory: {$targetDir}");
            }
        }

        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Get all story directories
        $storyDirs = File::directories($sourceDir);
        
        foreach ($storyDirs as $storyDir) {
            $storyName = basename($storyDir);
            $targetStoryDir = $targetDir . '/' . $storyName;
            
            $this->line("\nProcessing story: {$storyName}");
            
            // Create story directory in target
            if (!File::isDirectory($targetStoryDir)) {
                if (!$dryRun) {
                    File::makeDirectory($targetStoryDir, 0755, true);
                    $this->info("  📁 Created directory: {$storyName}");
                } else {
                    $this->comment("  🔍 Would create directory: {$storyName}");
                }
            }
            
            // Get all audio files in story directory
            $audioFiles = File::files($storyDir);
            
            foreach ($audioFiles as $audioFile) {
                $fileName = $audioFile->getFilename();
                $sourceFile = $audioFile->getPathname();
                $targetFile = $targetStoryDir . '/' . $fileName;
                
                // Check if target file exists and is newer
                if (File::exists($targetFile)) {
                    $sourceTime = File::lastModified($sourceFile);
                    $targetTime = File::lastModified($targetFile);
                    
                    if ($sourceTime <= $targetTime) {
                        $skippedCount++;
                        continue; // Skip if target is newer or same
                    }
                }
                
                if (!$dryRun) {
                    try {
                        File::copy($sourceFile, $targetFile);
                        $this->info("  ✅ Synced: {$fileName}");
                        $syncedCount++;
                    } catch (\Exception $e) {
                        $this->error("  ❌ Failed to sync {$fileName}: " . $e->getMessage());
                        $errorCount++;
                    }
                } else {
                    $this->comment("  🔍 Would sync: {$fileName}");
                    $syncedCount++;
                }
            }
        }

        $this->line(str_repeat('=', 60));
        
        if ($dryRun) {
            $this->warn("DRY RUN - No files were actually synced");
            $this->info("Would sync: {$syncedCount} files");
        } else {
            $this->info("Sync completed!");
            $this->info("Synced: {$syncedCount} files");
        }
        
        $this->info("Skipped: {$skippedCount} files (already up to date)");
        
        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount} files failed to sync");
        }

        return Command::SUCCESS;
    }
}
