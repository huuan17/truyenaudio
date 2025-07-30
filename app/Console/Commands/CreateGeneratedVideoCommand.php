<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneratedVideo;
use Illuminate\Support\Facades\File;

class CreateGeneratedVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'video:create-generated-record {filename}';

    /**
     * The console command description.
     */
    protected $description = 'Create GeneratedVideo record for existing video file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $fullPath = storage_path('app/videos/' . $filename);
        
        if (!File::exists($fullPath)) {
            $this->error("Video file not found: " . $fullPath);
            return 1;
        }
        
        try {
            $video = new GeneratedVideo();
            $video->title = 'Video tiếng Việt - Test subtitle UTF-8';
            $video->description = 'Video được tạo từ template với subtitle tiếng Việt UTF-8 encoding';
            $video->platform = 'tiktok';
            $video->media_type = 'images';
            $video->file_path = 'videos/' . $filename;
            $video->file_name = $filename;
            $video->file_size = File::size($fullPath);
            $video->duration = 30;
            $video->metadata = [
                'generation_parameters' => ['platform' => 'tiktok', 'media_type' => 'images'],
                'subtitle_text' => 'Nếu bạn đã làm theo đúng quy trình tạo phụ đề từ tiếng Việt...',
                'created_via' => 'manual_fix'
            ];
            $video->status = 'generated';
            $video->task_id = 112;
            $video->save();
            
            $this->info("✅ GeneratedVideo created successfully with ID: " . $video->id);
            $this->info("📁 File path: " . $video->file_path);
            $this->info("📊 File size: " . number_format($video->file_size / 1024 / 1024, 2) . ' MB');
            $this->info("🎬 Platform: " . $video->platform);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
