<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Services\VideoGenerationService;
use App\Models\VideoGenerationTask;

class VideoGeneratorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display unified video generator interface
     */
    public function index()
    {
        // Get existing videos from both platforms
        $tiktokVideos = $this->getExistingVideos('tiktok_videos');
        $youtubeVideos = $this->getExistingVideos('youtube_videos');
        
        // Voice options (shared between platforms)
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_phuthang_stor80dt_48k-fhg' => 'Anh Khôi (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)',
            'sg_female_tuongvy_call_44k-fhg' => 'Tường Vy (Nữ - Sài Gòn)'
        ];

        // Available logos
        $logos = $this->getAvailableLogos();

        // Get channels for both platforms
        $tiktokChannels = Channel::active()
            ->where('platform', 'tiktok')
            ->select('id', 'name', 'username', 'logo_config', 'default_privacy', 'default_tags', 'default_category')
            ->get();
            
        $youtubeChannels = Channel::active()
            ->where('platform', 'youtube')
            ->select('id', 'name', 'username', 'logo_config', 'default_privacy', 'default_tags', 'default_category')
            ->get();

        return view('admin.video-generator.index', compact(
            'tiktokVideos', 
            'youtubeVideos', 
            'voices', 
            'logos', 
            'tiktokChannels', 
            'youtubeChannels'
        ));
    }

    /**
     * Generate single video for any platform
     */
    public function generate(Request $request)
    {
        $platform = $request->input('platform');
        
        // Validate platform
        if (!in_array($platform, ['tiktok', 'youtube'])) {
            return back()->with('error', 'Platform không hợp lệ')->withInput();
        }

        // Platform-specific validation
        $validationRules = $this->getValidationRules($platform, 'single');
        $request->validate($validationRules);

        try {
            $videoService = new VideoGenerationService();
            $task = $videoService->queueSingleVideo($platform, $request, auth()->id());

            $platformName = $platform === 'tiktok' ? 'TikTok' : 'YouTube';

            // Redirect to video queue monitor with success message
            return redirect()->route('admin.video-queue.index')->with('success',
                "Video {$platformName} đã được thêm vào hàng đợi xử lý! Mã task: #{$task->id}. " .
                "Thời gian ước tính: " . ($task->estimated_duration ? round($task->estimated_duration/60) . " phút" : "5-10 phút") . ". " .
                "Bạn có thể theo dõi tiến trình tại đây."
            );

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate batch videos for any platform
     */
    public function generateBatch(Request $request)
    {
        $platform = $request->input('platform');
        
        // Validate platform
        if (!in_array($platform, ['tiktok', 'youtube'])) {
            return back()->with('error', 'Platform không hợp lệ')->withInput();
        }

        // Platform-specific validation
        $validationRules = $this->getValidationRules($platform, 'batch');
        $request->validate($validationRules);

        try {
            $videoService = new VideoGenerationService();
            $batchResult = $videoService->queueBatchVideos($platform, $request, auth()->id());

            $platformName = $platform === 'tiktok' ? 'TikTok' : 'YouTube';

            // Redirect to video queue monitor with success message
            return redirect()->route('admin.video-queue.index')->with('success',
                "Đã thêm {$batchResult['total_count']} video {$platformName} vào hàng đợi xử lý! " .
                "Batch ID: {$batchResult['batch_id']}. " .
                "Các video sẽ được xử lý tuần tự để tránh quá tải máy chủ. " .
                "Bạn có thể theo dõi tiến trình tại đây."
            );

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get platform-specific validation rules
     */
    private function getValidationRules($platform, $mode = 'single')
    {
        $baseRules = [
            'platform' => 'required|in:tiktok,youtube',
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
            'volume' => 'required|numeric|between:-30,30',
            'channel_id' => 'nullable|exists:channels,id',
            'schedule_post' => 'boolean',
            'scheduled_date' => 'nullable|required_if:schedule_post,1|date',
            'scheduled_time' => 'nullable|required_if:schedule_post,1|date_format:H:i',
            'post_title' => 'nullable|string|max:255',
            'post_description' => 'nullable|string',
            'post_tags' => 'nullable|string',
            
            // Subtitle options (shared) - all optional
            'enable_subtitle' => 'nullable|boolean',
            'subtitle_text' => 'nullable|string|max:500',
            'subtitle_position' => 'nullable|in:top,center,bottom,top-left,top-right,bottom-left,bottom-right',
            'subtitle_size' => 'nullable|numeric|between:12,72',
            'subtitle_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'subtitle_background' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'subtitle_font' => 'nullable|in:Arial,Times,Helvetica,Courier,Verdana,Georgia',
            'subtitle_duration' => 'nullable|numeric|between:1,30',
            'output_name' => 'nullable|string|max:100'
        ];

        if ($platform === 'tiktok') {
            $platformRules = $this->getTiktokValidationRules($mode);
        } else {
            $platformRules = $this->getYoutubeValidationRules($mode);
        }

        return array_merge($baseRules, $platformRules);
    }

    /**
     * Get TikTok-specific validation rules
     */
    private function getTiktokValidationRules($mode)
    {
        if ($mode === 'single') {
            return [
                'script_text' => 'required|string|min:10',
                'media_type' => 'required|in:video,images',
                'product_video' => 'required_if:media_type,video|file|mimes:mp4,avi,mov|max:102400', // 100MB
                'product_images' => 'required_if:media_type,images|array|min:1',
                'product_images.*' => 'file|mimes:jpg,jpeg,png|max:10240', // 10MB each
                'slide_duration' => 'nullable|numeric|between:1,10',
                'slide_transition' => 'nullable|in:fade,slide,zoom,none',
                'use_logo' => 'boolean',
                'logo_file' => 'nullable|string',
                'logo_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
                'logo_size' => 'nullable|numeric|between:50,500',
            ];
        } else { // batch
            return [
                'scripts' => 'required|array|min:1',
                'scripts.*' => 'required|string|min:10',
                'media_types' => 'required|array|min:1',
                'media_types.*' => 'required|in:video,images',
                'product_videos' => 'nullable|array',
                'product_videos.*' => 'nullable|file|mimes:mp4,avi,mov|max:102400',
                'product_images' => 'nullable|array',
                'product_images.*' => 'nullable|array',
                'product_images.*.*' => 'file|mimes:jpg,jpeg,png|max:10240',
                'output_names' => 'nullable|array',
                'output_names.*' => 'nullable|string|max:255',
                'subtitle_texts' => 'nullable|array',
                'subtitle_texts.*' => 'nullable|string|max:500',
            ];
        }
    }

    /**
     * Get YouTube-specific validation rules
     */
    private function getYoutubeValidationRules($mode)
    {
        if ($mode === 'single') {
            return [
                'audio_source' => 'required|in:text,file',
                'text_content' => 'required_if:audio_source,text|string|min:10',
                'audio_file' => 'required_if:audio_source,file|file|mimes:mp3,wav,m4a|max:51200',
                'video_content_type' => 'required|in:images,video,mixed',
                'images' => 'required_if:video_content_type,images,mixed|array|min:1',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:10240',
                'background_video' => 'required_if:video_content_type,video,mixed|file|mimes:mp4,avi,mov|max:512000',
                'remove_video_audio' => 'boolean',
                'transition_effects' => 'required_if:video_content_type,images,mixed|array',
                'transition_effects.*' => 'in:fade,slide,zoom,rotate,blur,wipe',
                'image_duration' => 'required_if:video_content_type,images,mixed|numeric|between:0.5,10',
                'video_loop' => 'boolean',
            ];
        } else { // batch
            return [
                'audio_sources' => 'required|array|min:1',
                'audio_sources.*' => 'required|in:text,file',
                'text_contents' => 'nullable|array',
                'text_contents.*' => 'nullable|string|min:10',
                'audio_files' => 'nullable|array',
                'audio_files.*' => 'nullable|file|mimes:mp3,wav,m4a|max:51200',
                'video_content_types' => 'required|array|min:1',
                'video_content_types.*' => 'required|in:images,video,mixed',
                'batch_images' => 'nullable|array',
                'batch_images.*' => 'nullable|array',
                'batch_background_videos' => 'nullable|array',
                'batch_background_videos.*' => 'nullable|file|mimes:mp4,avi,mov|max:512000',
                'remove_video_audio' => 'boolean',
                'transition_effects' => 'required|array',
                'transition_effects.*' => 'in:fade,slide,zoom,rotate,blur,wipe',
                'image_duration' => 'required|numeric|between:0.5,10',
                'video_loop' => 'boolean',
                'output_names' => 'nullable|array',
                'output_names.*' => 'nullable|string|max:100',
                'subtitle_texts' => 'nullable|array',
                'subtitle_texts.*' => 'nullable|string|max:500',
            ];
        }
    }

    /**
     * Get existing videos from directory
     */
    private function getExistingVideos($directory)
    {
        $videoDir = storage_path("app/{$directory}");
        
        if (!File::isDirectory($videoDir)) {
            return [];
        }

        $files = File::files($videoDir);
        $videos = [];

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['mp4', 'avi', 'mov'])) {
                $videos[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'created' => date('d/m/Y H:i', $file->getMTime()),
                    'path' => $file->getPathname()
                ];
            }
        }

        // Sort by creation time (newest first)
        usort($videos, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });

        return $videos;
    }

    /**
     * Get available logos
     */
    private function getAvailableLogos()
    {
        $logoDir = storage_path('app/logos');
        
        if (!File::isDirectory($logoDir)) {
            return [];
        }

        $files = File::files($logoDir);
        $logos = [];

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg'])) {
                $logos[] = [
                    'filename' => $file->getFilename(),
                    'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'path' => $file->getPathname()
                ];
            }
        }

        return $logos;
    }

    /**
     * Delete video files
     */
    public function delete(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:tiktok,youtube',
            'files' => 'required|array',
            'files.*' => 'required|string'
        ]);

        $platform = $request->platform;
        $directory = $platform === 'tiktok' ? 'tiktok_videos' : 'youtube_videos';
        $videoDir = storage_path("app/{$directory}");

        $deletedCount = 0;
        $errors = [];

        foreach ($request->files as $filename) {
            $filePath = $videoDir . '/' . $filename;

            if (File::exists($filePath)) {
                try {
                    File::delete($filePath);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Không thể xóa {$filename}: " . $e->getMessage();
                }
            } else {
                $errors[] = "File {$filename} không tồn tại";
            }
        }

        $platformName = $platform === 'tiktok' ? 'TikTok' : 'YouTube';

        if ($deletedCount > 0) {
            $message = "Đã xóa {$deletedCount} video {$platformName}";
            if (!empty($errors)) {
                $message .= ". Một số lỗi: " . implode(', ', $errors);
            }
            return back()->with('success', $message);
        } else {
            return back()->with('error', 'Không thể xóa video nào. ' . implode(', ', $errors));
        }
    }

    /**
     * Download video file
     */
    public function download(Request $request, $platform, $filename)
    {
        if (!in_array($platform, ['tiktok', 'youtube'])) {
            abort(404, 'Platform không hợp lệ');
        }

        $directory = $platform === 'tiktok' ? 'tiktok_videos' : 'youtube_videos';
        $filePath = storage_path("app/{$directory}/{$filename}");

        if (!File::exists($filePath)) {
            abort(404, 'File không tồn tại');
        }

        return response()->download($filePath);
    }

    /**
     * Get video generation status
     */
    public function status(Request $request)
    {
        $platform = $request->get('platform');

        if ($platform && !in_array($platform, ['tiktok', 'youtube'])) {
            return response()->json(['error' => 'Platform không hợp lệ'], 400);
        }

        $query = VideoGenerationTask::forUser(auth()->id())
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10);

        if ($platform) {
            $query->where('platform', $platform);
        }

        $tasks = $query->get()->map(function ($task) {
            return [
                'id' => $task->id,
                'platform' => $task->platform_display,
                'type' => $task->type_display,
                'status' => $task->status_display,
                'status_badge_class' => $task->status_badge_class,
                'progress' => $task->progress_percentage,
                'created_at' => $task->created_at->format('H:i:s d/m/Y'),
                'estimated_completion' => $task->estimated_completion ? $task->estimated_completion->format('H:i:s d/m/Y') : null,
                'duration' => $task->duration,
                'can_cancel' => $task->canBeCancelled(),
                'can_retry' => $task->canBeRetried(),
                'batch_progress' => $task->batch_progress
            ];
        });

        return response()->json([
            'tasks' => $tasks,
            'total_pending' => VideoGenerationTask::forUser(auth()->id())->pending()->count(),
            'total_processing' => VideoGenerationTask::forUser(auth()->id())->processing()->count(),
        ]);
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
