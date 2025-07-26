<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneratedVideo;
use Illuminate\Support\Facades\File;

class GenerateVideoThumbnail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:generate-thumbnail {video_id? : Video ID to generate thumbnail for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnail for video files using ffmpeg';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $videoId = $this->argument('video_id');

        if ($videoId) {
            $video = GeneratedVideo::find($videoId);
            if (!$video) {
                $this->error("Video with ID {$videoId} not found");
                return 1;
            }
            $this->generateThumbnailForVideo($video);
        } else {
            // Generate thumbnails for all videos without thumbnails
            $videos = GeneratedVideo::whereNull('thumbnail_path')
                                  ->orWhere(function($query) {
                                      $query->whereNotNull('thumbnail_path')
                                           ->whereRaw('NOT EXISTS (SELECT 1 FROM dual WHERE ? IS NOT NULL)', [null]);
                                  })
                                  ->get();

            $this->info("Found {$videos->count()} videos without thumbnails");

            foreach ($videos as $video) {
                $this->generateThumbnailForVideo($video);
            }
        }

        return 0;
    }

    /**
     * Generate thumbnail for a specific video
     */
    private function generateThumbnailForVideo(GeneratedVideo $video)
    {
        if (!$video->fileExists()) {
            $this->warn("Video file not found: {$video->file_path}");
            return;
        }

        $this->info("Generating thumbnail for: {$video->title}");

        // Create thumbnails directory
        $thumbnailDir = storage_path('app/public/thumbnails');
        if (!File::isDirectory($thumbnailDir)) {
            File::makeDirectory($thumbnailDir, 0755, true);
        }

        // Generate thumbnail filename
        $thumbnailName = 'video_' . $video->id . '_' . time() . '.jpg';
        $thumbnailPath = $thumbnailDir . '/' . $thumbnailName;

        // Use ffmpeg to generate thumbnail at 1 second mark
        $cmd = "ffmpeg -i \"{$video->file_path}\" -ss 00:00:01 -vframes 1 -q:v 2 \"{$thumbnailPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($thumbnailPath)) {
            // Update video record with thumbnail path
            $video->update([
                'thumbnail_path' => $thumbnailPath
            ]);

            $this->info("✓ Thumbnail generated: {$thumbnailName}");
        } else {
            $this->error("✗ Failed to generate thumbnail for video ID: {$video->id}");
        }
    }
}
