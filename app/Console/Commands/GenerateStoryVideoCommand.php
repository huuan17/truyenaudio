<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Story;
use App\Models\Chapter;

class GenerateStoryVideoCommand extends Command
{
    protected $signature = 'story:video:generate 
                            {story_id : Story ID}
                            {--chapter= : Specific chapter number}
                            {--overlay= : Overlay video file path}
                            {--output= : Output filename}
                            {--duration=45 : Target video duration in minutes}';

    protected $description = 'Generate video from story audio files';

    private $tempDir;
    private $story;

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $this->story = Story::findOrFail($storyId);
        
        $this->info("Generating video for story: {$this->story->title}");
        
        // Create temp directory
        $this->tempDir = storage_path('app/videos/temp/story_' . $storyId . '_' . uniqid());
        if (!File::isDirectory($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
        
        try {
            // Step 1: Prepare audio
            $this->info('Step 1: Preparing audio...');
            $audioPath = $this->prepareAudio();
            
            if (!$audioPath) {
                throw new \Exception('Failed to prepare audio');
            }
            
            // Step 2: Create video
            $this->info('Step 2: Creating video...');
            $videoPath = $this->createVideo($audioPath);
            
            if (!$videoPath) {
                throw new \Exception('Failed to create video');
            }
            
            // Step 3: Save final video
            $this->info('Step 3: Saving final video...');
            $finalPath = $this->saveFinalVideo($videoPath);
            
            $this->info("Video generated successfully: {$finalPath}");
            
            // Step 4: Cleanup merged audio (keep original files)
            $this->cleanupMergedAudio($audioPath);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error generating video: " . $e->getMessage());
            return 1;
        } finally {
            // Cleanup temp directory
            if (File::isDirectory($this->tempDir)) {
                File::deleteDirectory($this->tempDir);
            }
        }
    }

    /**
     * Prepare audio for video generation
     */
    private function prepareAudio()
    {
        $chapterNumber = $this->option('chapter');
        $targetDuration = (int)$this->option('duration') * 60; // Convert to seconds
        
        if ($chapterNumber) {
            // Single chapter audio
            return $this->prepareSingleChapterAudio($chapterNumber);
        } else {
            // Multiple chapters audio (45 minutes)
            return $this->prepareMultipleChaptersAudio($targetDuration);
        }
    }

    /**
     * Prepare single chapter audio
     */
    private function prepareSingleChapterAudio($chapterNumber)
    {
        $chapter = $this->story->chapters()->where('chapter_number', $chapterNumber)->first();
        
        if (!$chapter || !$chapter->audio_file_path) {
            throw new \Exception("Chapter {$chapterNumber} not found or has no audio");
        }
        
        $audioPath = storage_path('app/' . $chapter->audio_file_path);
        
        if (!File::exists($audioPath)) {
            throw new \Exception("Audio file not found: {$audioPath}");
        }
        
        // Copy to temp directory (keep original)
        $tempAudioPath = $this->tempDir . '/audio.mp3';
        File::copy($audioPath, $tempAudioPath);
        
        $this->info("Using single chapter audio: {$chapter->audio_file_path}");
        
        return $tempAudioPath;
    }

    /**
     * Prepare multiple chapters audio (merge to target duration)
     */
    private function prepareMultipleChaptersAudio($targetDuration)
    {
        // Get chapters with audio files
        $chapters = $this->story->chapters()
            ->whereNotNull('audio_file_path')
            ->where('audio_file_path', '!=', '')
            ->orderBy('chapter_number')
            ->get();
        
        if ($chapters->isEmpty()) {
            throw new \Exception('No chapters with audio files found');
        }
        
        $this->info("Found {$chapters->count()} chapters with audio");
        
        // Collect audio files and calculate total duration
        $audioFiles = [];
        $totalDuration = 0;
        
        foreach ($chapters as $chapter) {
            $audioPath = storage_path('app/' . $chapter->audio_file_path);
            
            if (File::exists($audioPath)) {
                $duration = $this->getAudioDuration($audioPath);
                $audioFiles[] = [
                    'path' => $audioPath,
                    'duration' => $duration,
                    'chapter' => $chapter->chapter_number
                ];
                $totalDuration += $duration;
                
                $this->info("Chapter {$chapter->chapter_number}: {$duration}s");
                
                // Stop if we have enough duration
                if ($totalDuration >= $targetDuration) {
                    break;
                }
            }
        }
        
        if (empty($audioFiles)) {
            throw new \Exception('No valid audio files found');
        }
        
        $this->info("Total audio duration: {$totalDuration}s (target: {$targetDuration}s)");
        
        // Create merged audio file (temporary)
        return $this->mergeAudioFiles($audioFiles, $targetDuration);
    }

    /**
     * Merge audio files to target duration
     */
    private function mergeAudioFiles($audioFiles, $targetDuration)
    {
        $mergedAudioPath = $this->tempDir . '/merged_audio.mp3';
        
        // Create file list for FFmpeg concat
        $fileListPath = $this->tempDir . '/audio_files.txt';
        $fileListContent = '';
        
        $currentDuration = 0;
        foreach ($audioFiles as $audioFile) {
            if ($currentDuration >= $targetDuration) {
                break;
            }
            
            $fileListContent .= "file '" . str_replace('\\', '/', $audioFile['path']) . "'\n";
            $currentDuration += $audioFile['duration'];
        }
        
        File::put($fileListPath, $fileListContent);
        
        // Merge audio files using FFmpeg concat
        $cmd = "ffmpeg -f concat -safe 0 -i \"{$fileListPath}\" -t {$targetDuration} -c copy \"{$mergedAudioPath}\" -y";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0 || !File::exists($mergedAudioPath)) {
            throw new \Exception('Failed to merge audio files');
        }
        
        $finalDuration = $this->getAudioDuration($mergedAudioPath);
        $this->info("Merged audio created: {$finalDuration}s");
        
        return $mergedAudioPath;
    }

    /**
     * Create video from audio
     */
    private function createVideo($audioPath)
    {
        $audioDuration = $this->getAudioDuration($audioPath);
        
        // Use story image as background
        $backgroundImage = null;
        if ($this->story->image) {
            $imagePath = public_path('images/stories/' . $this->story->image);
            if (File::exists($imagePath)) {
                $backgroundImage = $imagePath;
            }
        }
        
        // If no story image, use default background
        if (!$backgroundImage) {
            $backgroundImage = public_path('images/default-story-bg.jpg');
            
            // Create default background if not exists
            if (!File::exists($backgroundImage)) {
                $this->createDefaultBackground($backgroundImage);
            }
        }
        
        $videoPath = $this->tempDir . '/video_with_audio.mp4';
        
        // Create video from image and audio
        $cmd = "ffmpeg -loop 1 -i \"{$backgroundImage}\" -i \"{$audioPath}\" " .
               "-c:v libx264 -tune stillimage -c:a aac -b:a 192k " .
               "-pix_fmt yuv420p -shortest \"{$videoPath}\" -y";
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0 || !File::exists($videoPath)) {
            throw new \Exception('Failed to create video');
        }
        
        // Add overlay if specified
        $overlayPath = $this->option('overlay');
        if ($overlayPath && File::exists($overlayPath)) {
            $videoPath = $this->addOverlay($videoPath, $overlayPath);
        }
        
        return $videoPath;
    }

    /**
     * Add overlay video
     */
    private function addOverlay($videoPath, $overlayPath)
    {
        $outputPath = $this->tempDir . '/video_with_overlay.mp4';
        
        // Overlay video on top of background
        $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$overlayPath}\" " .
               "-filter_complex \"[0:v][1:v]overlay=10:10\" " .
               "-c:a copy \"{$outputPath}\" -y";
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && File::exists($outputPath)) {
            return $outputPath;
        }
        
        $this->warn('Failed to add overlay, using original video');
        return $videoPath;
    }

    /**
     * Save final video to storage
     */
    private function saveFinalVideo($videoPath)
    {
        $outputName = $this->option('output') ?: 
                     "story_{$this->story->id}_video_" . date('Y-m-d_H-i-s') . '.mp4';
        
        if (!str_ends_with($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }
        
        $finalPath = storage_path('app/videos/generated/' . $outputName);
        
        // Ensure directory exists
        $dir = dirname($finalPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        File::copy($videoPath, $finalPath);
        
        return $finalPath;
    }

    /**
     * Cleanup merged audio file (keep original chapter audio files)
     */
    private function cleanupMergedAudio($audioPath)
    {
        // Only delete if it's a merged file (not original chapter audio)
        if (str_contains($audioPath, 'merged_audio.mp3')) {
            if (File::exists($audioPath)) {
                File::delete($audioPath);
                $this->info('Cleaned up merged audio file (original chapter audio files preserved)');
            }
        }
    }

    /**
     * Get audio duration in seconds
     */
    private function getAudioDuration($audioPath)
    {
        $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$audioPath}\"";
        $duration = trim(shell_exec($cmd));
        return (float)$duration;
    }

    /**
     * Create default background image
     */
    private function createDefaultBackground($imagePath)
    {
        // Create a simple colored background
        $width = 1920;
        $height = 1080;
        
        $image = imagecreate($width, $height);
        $backgroundColor = imagecolorallocate($image, 45, 55, 72); // Dark blue-gray
        $textColor = imagecolorallocate($image, 255, 255, 255); // White
        
        // Add story title
        $title = $this->story->title;
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($title);
        $x = ($width - $textWidth) / 2;
        $y = $height / 2;
        
        imagestring($image, $fontSize, $x, $y, $title, $textColor);
        
        // Ensure directory exists
        $dir = dirname($imagePath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);
    }
}
