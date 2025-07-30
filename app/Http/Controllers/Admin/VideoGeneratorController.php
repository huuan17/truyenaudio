<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Services\VideoGenerationService;
use App\Models\VideoGenerationTask;
use App\Models\VideoTemplate;
use App\Models\AudioLibrary;

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
        if (!in_array($platform, ['tiktok', 'youtube', 'both', 'none'])) {
            return back()->with('error', 'Platform không hợp lệ')->withInput();
        }

        // Platform-specific validation
        $validationRules = $this->getValidationRules($platform, 'single');
        $request->validate($validationRules);

        try {
            // Process all video settings
            $videoSettings = $this->processVideoSettings($request);

            // Add processed settings to request
            $request->merge(['processed_settings' => $videoSettings]);

            $videoService = new VideoGenerationService();

            if ($platform === 'both') {
                // Generate for both platforms
                $tiktokTask = $videoService->queueSingleVideo('tiktok', $request, auth()->id());
                $youtubeTask = $videoService->queueSingleVideo('youtube', $request, auth()->id());

                return redirect()->route('admin.video-queue.index')->with('success',
                    "Video đã được thêm vào hàng đợi cho cả TikTok và YouTube! " .
                    "Mã task TikTok: #{$tiktokTask->id}, YouTube: #{$youtubeTask->id}. " .
                    "Thời gian ước tính: " .
                    (($tiktokTask->estimated_duration + $youtubeTask->estimated_duration) ?
                        round(($tiktokTask->estimated_duration + $youtubeTask->estimated_duration)/60) . " phút" : "10-20 phút") . ". " .
                    "Bạn có thể theo dõi tiến trình tại đây."
                );
            } elseif ($platform === 'none') {
                // Generate video without channel publishing
                $task = $videoService->queueSingleVideo('none', $request, auth()->id());

                // Redirect to video management instead of queue monitor
                return redirect()->route('admin.videos.index')->with('success',
                    "Video đã được thêm vào hàng đợi xử lý! Mã task: #{$task->id}. " .
                    "Thời gian ước tính: " . ($task->estimated_duration ? round($task->estimated_duration/60) . " phút" : "5-10 phút") . ". " .
                    "Video sẽ được lưu trữ mà không đăng lên kênh nào. " .
                    "Bạn có thể theo dõi tiến trình tại Video Queue hoặc xem video đã tạo tại đây."
                );
            } else {
                // Generate for single platform
                $task = $videoService->queueSingleVideo($platform, $request, auth()->id());

                $platformName = $platform === 'tiktok' ? 'TikTok' : 'YouTube';

                // Redirect to video queue monitor with success message
                return redirect()->route('admin.video-queue.index')->with('success',
                    "Video {$platformName} đã được thêm vào hàng đợi xử lý! Mã task: #{$task->id}. " .
                    "Thời gian ước tính: " . ($task->estimated_duration ? round($task->estimated_duration/60) . " phút" : "5-10 phút") . ". " .
                    "Bạn có thể theo dõi tiến trình tại đây."
                );
            }

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
        if (!in_array($platform, ['tiktok', 'youtube', 'both', 'none'])) {
            return back()->with('error', 'Platform không hợp lệ')->withInput();
        }

        // Platform-specific validation
        $validationRules = $this->getValidationRules($platform, 'batch');
        $request->validate($validationRules);

        try {
            // Process all video settings for batch
            $videoSettings = $this->processVideoSettings($request);

            // Add processed settings to request
            $request->merge(['processed_settings' => $videoSettings]);

            $videoService = new VideoGenerationService();

            if ($platform === 'both') {
                // Generate batch for both platforms
                $tiktokBatch = $videoService->queueBatchVideos('tiktok', $request, auth()->id());
                $youtubeBatch = $videoService->queueBatchVideos('youtube', $request, auth()->id());

                $totalCount = $tiktokBatch['total_count'] + $youtubeBatch['total_count'];

                return redirect()->route('admin.video-queue.index')->with('success',
                    "Đã thêm {$totalCount} video cho cả TikTok và YouTube vào hàng đợi xử lý! " .
                    "TikTok Batch ID: {$tiktokBatch['batch_id']}, YouTube Batch ID: {$youtubeBatch['batch_id']}. " .
                    "Các video sẽ được xử lý tuần tự để tránh quá tải máy chủ. " .
                    "Bạn có thể theo dõi tiến trình tại đây."
                );
            } elseif ($platform === 'none') {
                // Generate batch videos without channel publishing
                $batchResult = $videoService->queueBatchVideos('none', $request, auth()->id());

                // Redirect to video management instead of queue monitor
                return redirect()->route('admin.videos.index')->with('success',
                    "Đã thêm {$batchResult['total_count']} video vào hàng đợi xử lý! " .
                    "Batch ID: {$batchResult['batch_id']}. " .
                    "Các video sẽ được xử lý tuần tự để tránh quá tải máy chủ. " .
                    "Video sẽ được lưu trữ mà không đăng lên kênh nào. " .
                    "Bạn có thể theo dõi tiến trình tại Video Queue hoặc xem video đã tạo tại đây."
                );
            } else {
                // Generate batch for single platform
                $batchResult = $videoService->queueBatchVideos($platform, $request, auth()->id());

                $platformName = $platform === 'tiktok' ? 'TikTok' : 'YouTube';

                // Redirect to video queue monitor with success message
                return redirect()->route('admin.video-queue.index')->with('success',
                    "Đã thêm {$batchResult['total_count']} video {$platformName} vào hàng đợi xử lý! " .
                    "Batch ID: {$batchResult['batch_id']}. " .
                    "Các video sẽ được xử lý tuần tự để tránh quá tải máy chủ. " .
                    "Bạn có thể theo dõi tiến trình tại đây."
                );
            }

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
            'platform' => 'required|in:tiktok,youtube,both,none',

            // Media content rules
            'media_type' => 'required|in:images,video,mixed',
            'images' => 'nullable|array|max:20',
            'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:51200', // 50MB per image
            'background_video' => 'nullable|file|mimes:mp4,avi,mov|max:512000', // 500MB
            'mixed_media' => 'nullable|array|max:30',
            'mixed_media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,avi,mov|max:512000', // 500MB
            'remove_video_audio' => 'nullable|boolean',

            // Individual image settings
            'default_image_duration' => 'nullable|numeric|between:0.5,30',
            'default_transition_effect' => 'nullable|in:fade,slide,zoom,dissolve,wipe,none',
            'transition_duration' => 'nullable|numeric|between:0.1,2',

            // Mixed mode settings
            'mixed_mode' => 'nullable|in:sequence,overlay,split',
            'sequence_image_duration' => 'nullable|numeric|between:1,30',
            'sequence_video_duration' => 'nullable|in:full,5,10,15,custom',
            'custom_video_seconds' => 'nullable|numeric|between:1,300',

            // Overlay settings
            'overlay_position' => 'nullable|in:top-left,top-right,top-center,bottom-left,bottom-right,bottom-center,center',
            'overlay_size' => 'nullable|in:small,medium,large,custom',
            'overlay_width' => 'nullable|numeric|between:10,80',
            'overlay_height' => 'nullable|numeric|between:10,80',
            'overlay_opacity' => 'nullable|numeric|between:0.1,1',
            'overlay_timing' => 'nullable|in:full,start,end,middle,custom',
            'overlay_start_time' => 'nullable|numeric|min:0',
            'overlay_end_time' => 'nullable|numeric|min:0',

            // Split settings
            'split_layout' => 'nullable|in:horizontal,vertical,pip',
            'split_ratio' => 'nullable|in:50:50,60:40,70:30,80:20',

            // Audio content rules
            'audio_source' => 'nullable|in:tts,upload,library,none',
            'tts_text' => 'nullable|required_if:audio_source,tts|string|min:10|max:5000',
            'tts_voice' => 'nullable|string',
            'tts_speed' => 'nullable|numeric|between:0.5,2.0',
            'tts_volume' => 'nullable|numeric|between:0.5,2.0',
            'audio_file' => 'nullable|required_if:audio_source,upload|file|mimes:mp3,wav,aac|max:512000', // 500MB
            'library_audio_id' => 'nullable|required_if:audio_source,library|exists:audio_libraries,id',
            'audio_volume' => 'nullable|numeric|between:0.5,1.5',
            'audio_fade' => 'nullable|in:none,in,out,both',

            // Subtitle rules
            'enable_subtitle' => 'nullable|boolean',
            'subtitle_source' => 'nullable|required_if:enable_subtitle,1|in:auto,manual,upload',
            'subtitle_text' => 'nullable|string|max:10000',
            'subtitle_file' => 'nullable|file|mimes:srt|max:10240', // 10MB
            'subtitle_position' => 'nullable|in:bottom,top,center',
            'subtitle_size' => 'nullable', // Allow both string and numeric values from templates
            'subtitle_color' => 'nullable|string', // Allow hex colors from templates
            'subtitle_background' => 'nullable|string', // Allow hex colors from templates
            'subtitle_outline' => 'nullable|boolean',

            // Subtitle timing rules
            'subtitle_timing_mode' => 'nullable|in:auto,image_sync,custom_timing',
            'subtitle_per_image' => 'nullable|in:auto,sentence,word_count',
            'words_per_image' => 'nullable|integer|between:5,50',
            'subtitle_duration' => 'nullable|numeric|between:1,10',
            'subtitle_delay' => 'nullable|numeric|between:0,3',
            'subtitle_fade' => 'nullable|in:none,in,out,both',

            // Logo rules
            'enable_logo' => 'nullable|boolean',
            'logo_source' => 'nullable|required_if:enable_logo,1|in:library,upload',
            'selected_logo' => 'nullable|string',
            'logo_file' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:51200', // 50MB
            'logo_position' => 'nullable|in:top-left,top-right,top-center,bottom-left,bottom-right,bottom-center,center,center-left,center-right',
            'logo_size' => 'nullable', // Allow both string and numeric values from templates
            'logo_width' => 'nullable|numeric|between:50,500',
            'logo_height' => 'nullable|numeric|between:50,500',
            'logo_opacity' => 'nullable|numeric|between:0.3,1.0',
            'logo_margin' => 'nullable|numeric|between:10,50',
            'logo_duration' => 'nullable|in:full,start,end,custom',
            'logo_start_time' => 'nullable|numeric|min:0',
            'logo_end_time' => 'nullable|numeric|min:0',

            // Duration settings
            'duration_based_on' => 'nullable|in:images,video,audio,custom',
            'custom_duration' => 'nullable|required_if:duration_based_on,custom|numeric|between:5,600',
            'content_behavior' => 'nullable|in:loop,freeze,fade,crop',
            'sync_with_audio' => 'nullable|boolean',
            'auto_adjust_images' => 'nullable|boolean',

            // Video information
            'video_title' => 'required|string|max:100',
            'video_description' => 'nullable|string|max:5000',
            'video_keywords' => 'nullable|string|max:500',
            'video_location' => 'nullable|string|max:255',
            'video_thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:51200', // 50MB
            'video_license' => 'nullable|in:standard,creative_commons',
            'video_made_for_kids' => 'nullable|boolean',

            // TikTok specific info
            'tiktok_hashtags' => 'nullable|string|max:100',
            'tiktok_category' => 'nullable|string',
            'tiktok_privacy' => 'nullable|in:public,friends,private',
            'tiktok_allow_comments' => 'nullable|boolean',
            'tiktok_allow_duet' => 'nullable|boolean',
            'tiktok_allow_stitch' => 'nullable|boolean',

            // YouTube specific info
            'youtube_tags' => 'nullable|string|max:500',
            'youtube_category' => 'nullable|string',
            'youtube_privacy' => 'nullable|in:public,unlisted,private',
            'youtube_language' => 'nullable|string|max:5',
            'youtube_allow_comments' => 'nullable|boolean',
            'youtube_allow_ratings' => 'nullable|boolean',
            'youtube_notify_subscribers' => 'nullable|boolean',

            // Channel and scheduling
            'channel_id' => 'nullable|exists:channels,id',
            'schedule_post' => 'nullable|boolean',
            'scheduled_date' => 'nullable|required_if:schedule_post,1|date',
            'scheduled_time' => 'nullable|required_if:schedule_post,1|date_format:H:i',
            'output_name' => 'nullable|string|max:100',

            // Background audio from library
            'background_audio_id' => 'nullable|exists:audio_libraries,id'
        ];

        // Add platform-specific rules
        if ($platform === 'tiktok') {
            $platformRules = $this->getTiktokValidationRules($mode);
        } elseif ($platform === 'youtube') {
            $platformRules = $this->getYoutubeValidationRules($mode);
        } elseif ($platform === 'none') {
            $platformRules = $this->getNoneValidationRules($mode);
        } else {
            $platformRules = $this->getBothPlatformValidationRules($mode);
        }

        // Add batch-specific rules
        if ($mode === 'batch') {
            $batchRules = $this->getBatchValidationRules();
            $baseRules = array_merge($baseRules, $batchRules);
        }

        return array_merge($baseRules, $platformRules);
    }

    /**
     * Get batch-specific validation rules
     */
    private function getBatchValidationRules()
    {
        return [
            'batch_mode' => 'required|in:multiple_content,template',
            'batch_count' => 'nullable|numeric|between:2,20',

            // Multiple content mode
            'batch_texts' => 'nullable|array|max:20',
            'batch_texts.*' => 'nullable|string|max:5000',
            'batch_audio_files' => 'nullable|array|max:20',
            'batch_audio_files.*' => 'nullable|file|mimes:mp3,wav,aac,m4a|max:512000', // 500MB
            'batch_images' => 'nullable|array|max:20',
            'batch_images.*' => 'nullable|array|max:10',
            'batch_images.*.*' => 'file|mimes:jpg,jpeg,png,gif|max:51200', // 50MB
            'batch_background_videos' => 'nullable|array|max:20',
            'batch_background_videos.*' => 'nullable|file|mimes:mp4,avi,mov|max:512000', // 500MB
            'batch_subtitles' => 'nullable|array|max:20',
            'batch_subtitles.*' => 'nullable|string|max:2000',
            'batch_subtitle_timing' => 'nullable|array|max:20',
            'batch_subtitle_timing.*' => 'nullable|in:auto,image_sync,custom_timing',
            'batch_output_names' => 'nullable|array|max:20',
            'batch_output_names.*' => 'nullable|string|max:100',
            'batch_durations' => 'nullable|array|max:20',
            'batch_durations.*' => 'nullable|numeric|between:5,300',
            'batch_transitions' => 'nullable|array|max:20',
            'batch_transitions.*' => 'nullable|in:fade,slide,zoom,none',
            'batch_volumes' => 'nullable|array|max:20',
            'batch_volumes.*' => 'nullable|numeric|between:0,200',

            // Template mode
            'template_texts' => 'nullable|string|max:50000',
            'template_auto_name' => 'nullable|boolean',
            'batch_videos.*.tts_text' => 'nullable|string|min:10|max:5000',
            'batch_videos.*.output_name' => 'nullable|string|max:255',
            'template_texts' => 'nullable|string|max:50000',
            'template_auto_name' => 'nullable|boolean',
            'batch_priority' => 'nullable|in:low,normal,high',
            'batch_delay' => 'nullable|in:0,5,10,30',
            'batch_notify' => 'nullable|boolean',
        ];
    }

    /**
     * Get both platform validation rules
     */
    private function getBothPlatformValidationRules($mode)
    {
        return [
            'both_output_prefix' => 'nullable|string|max:255',
            'tiktok_resolution' => 'nullable|in:1080x1920,720x1280,1080x1080',
            'tiktok_fps' => 'nullable|in:24,30,60',
            'tiktok_duration' => 'nullable|in:15,30,60,180,600',
            'tiktok_output_name' => 'nullable|string|max:255',
            'youtube_resolution' => 'nullable|in:1920x1080,1280x720,1080x1920,1080x1080',
            'youtube_fps' => 'nullable|in:24,30,60',
            'youtube_quality' => 'nullable|in:medium,high,very_high',
            'youtube_output_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get none platform validation rules (no channel publishing)
     */
    private function getNoneValidationRules($mode)
    {
        return [
            'none_resolution' => 'nullable|in:1920x1080,1080x1920,1280x720,1080x1080',
            'none_fps' => 'nullable|in:24,30,60',
            'none_quality' => 'nullable|in:medium,high,very_high',
            'none_output_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get TikTok-specific validation rules
     */
    private function getTiktokValidationRules($mode)
    {
        if ($mode === 'single') {
            return [
                'tiktok_resolution' => 'nullable|in:1080x1920,720x1280,1080x1080',
                'tiktok_fps' => 'nullable|in:24,30,60',
                'tiktok_duration' => 'nullable|in:15,30,60,180,600',
                'tiktok_output_name' => 'nullable|string|max:255',
            ];
        } else { // batch
            return [
                'tiktok_resolution' => 'nullable|in:1080x1920,720x1280,1080x1080',
                'tiktok_fps' => 'nullable|in:24,30,60',
                'tiktok_duration' => 'nullable|in:15,30,60,180,600',
                'tiktok_output_names' => 'nullable|array',
                'tiktok_output_names.*' => 'nullable|string|max:255',
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
                'youtube_resolution' => 'nullable|in:1920x1080,1280x720,1080x1920,1080x1080',
                'youtube_fps' => 'nullable|in:24,30,60',
                'youtube_quality' => 'nullable|in:medium,high,very_high',
                'youtube_output_name' => 'nullable|string|max:255',
            ];
        } else { // batch
            return [
                'youtube_resolution' => 'nullable|in:1920x1080,1280x720,1080x1920,1080x1080',
                'youtube_fps' => 'nullable|in:24,30,60',
                'youtube_quality' => 'nullable|in:medium,high,very_high',
                'youtube_output_names' => 'nullable|array',
                'youtube_output_names.*' => 'nullable|string|max:255',
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
        $logoDir = public_path('assets/logos');

        if (!File::isDirectory($logoDir)) {
            return [];
        }

        $files = File::files($logoDir);
        $logos = [];

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif'])) {
                $logos[] = [
                    'filename' => $file->getFilename(),
                    'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'path' => $file->getPathname(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'url' => asset('assets/logos/' . $file->getFilename())
                ];
            }
        }

        return $logos;
    }

    /**
     * Process individual image settings
     */
    private function processImageSettings($request)
    {
        $imageSettings = [];

        if ($request->has('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imageSettings[$index] = [
                    'duration' => $request->input("images.{$index}.duration", $request->input('default_image_duration', 3)),
                    'transition' => $request->input("images.{$index}.transition", $request->input('default_transition_effect', 'slide')),
                    'transition_duration' => $request->input("images.{$index}.transition_duration", $request->input('transition_duration', 0.5)),
                ];
            }
        }

        return $imageSettings;
    }

    /**
     * Calculate total video duration
     */
    private function calculateVideoDuration($request, $imageSettings = [])
    {
        $durationBasis = $request->input('duration_based_on', 'images');
        $totalDuration = 0;

        switch ($durationBasis) {
            case 'images':
                foreach ($imageSettings as $setting) {
                    $totalDuration += floatval($setting['duration']);
                    $totalDuration += floatval($setting['transition_duration']);
                }
                break;

            case 'video':
                // This would need video duration detection
                $totalDuration = $request->input('detected_video_duration', 30);
                break;

            case 'audio':
                // This would need audio duration detection
                $totalDuration = $request->input('detected_audio_duration', 30);
                break;

            case 'custom':
                $totalDuration = $request->input('custom_duration', 30);
                break;
        }

        return $totalDuration;
    }

    /**
     * Process mixed media settings
     */
    private function processMixedMediaSettings($request)
    {
        $mixedMode = $request->input('mixed_mode', 'sequence');
        $settings = ['mode' => $mixedMode];

        switch ($mixedMode) {
            case 'sequence':
                $settings['image_duration'] = $request->input('sequence_image_duration', 4);
                $settings['video_duration'] = $request->input('sequence_video_duration', 'full');
                if ($settings['video_duration'] === 'custom') {
                    $settings['custom_video_seconds'] = $request->input('custom_video_seconds', 8);
                }
                break;

            case 'overlay':
                $settings['position'] = $request->input('overlay_position', 'top-right');
                $settings['size'] = $request->input('overlay_size', 'medium');
                $settings['opacity'] = $request->input('overlay_opacity', 0.9);
                $settings['timing'] = $request->input('overlay_timing', 'middle');

                if ($settings['size'] === 'custom') {
                    $settings['width'] = $request->input('overlay_width', 30);
                    $settings['height'] = $request->input('overlay_height', 30);
                }

                if ($settings['timing'] === 'custom') {
                    $settings['start_time'] = $request->input('overlay_start_time', 0);
                    $settings['end_time'] = $request->input('overlay_end_time', 10);
                }
                break;

            case 'split':
                $settings['layout'] = $request->input('split_layout', 'horizontal');
                $settings['ratio'] = $request->input('split_ratio', '50:50');
                break;
        }

        return $settings;
    }

    /**
     * Process logo settings
     */
    private function processLogoSettings($request)
    {
        if (!$request->input('enable_logo')) {
            return null;
        }

        $logoSettings = [
            'enabled' => true,
            'source' => $request->input('logo_source', 'library'),
            'position' => $request->input('logo_position', 'top-right'),
            'size' => $request->input('logo_size', 'medium'),
            'opacity' => $request->input('logo_opacity', 1.0),
            'margin' => $request->input('logo_margin', 20),
            'duration' => $request->input('logo_duration', 'full'),
        ];

        // Handle logo file
        if ($logoSettings['source'] === 'library') {
            $logoSettings['file'] = $request->input('selected_logo');
        } elseif ($logoSettings['source'] === 'upload' && $request->hasFile('logo_file')) {
            $logoFile = $request->file('logo_file');
            $logoPath = $logoFile->store('logos', 'public');
            $logoSettings['file'] = $logoPath;
        }

        // Handle custom size
        if ($logoSettings['size'] === 'custom') {
            $logoSettings['width'] = $request->input('logo_width', 100);
            $logoSettings['height'] = $request->input('logo_height', 100);
        }

        // Handle custom duration
        if ($logoSettings['duration'] === 'custom') {
            $logoSettings['start_time'] = $request->input('logo_start_time', 0);
            $logoSettings['end_time'] = $request->input('logo_end_time', 10);
        }

        return $logoSettings;
    }

    /**
     * Process subtitle settings
     */
    private function processSubtitleSettings($request)
    {
        if (!$request->input('enable_subtitle')) {
            return null;
        }

        $subtitleSettings = [
            'enabled' => true,
            'source' => $request->input('subtitle_source', 'auto'),
            'position' => $request->input('subtitle_position', 'bottom'),
            'size' => $request->input('subtitle_size', 'medium'),
            'color' => $request->input('subtitle_color', 'white'),
            'background' => $request->input('subtitle_background', 'none'),
            'outline' => $request->input('subtitle_outline', false),
        ];

        // Handle subtitle content
        switch ($subtitleSettings['source']) {
            case 'manual':
                $subtitleSettings['text'] = $request->input('subtitle_text');
                break;

            case 'upload':
                if ($request->hasFile('subtitle_file')) {
                    $subtitleFile = $request->file('subtitle_file');
                    $subtitlePath = $subtitleFile->store('subtitles', 'public');
                    $subtitleSettings['file'] = $subtitlePath;
                }
                break;

            case 'auto':
                // Auto-generated from TTS text
                $subtitleSettings['text'] = $request->input('tts_text');
                break;
        }

        return $subtitleSettings;
    }

    /**
     * Process audio settings
     */
    private function processAudioSettings($request)
    {
        $audioSource = $request->input('audio_source', 'none'); // Default to 'none' instead of 'tts'
        \Log::info('Processing audio settings', [
            'audio_source' => $audioSource,
            'has_tts_text' => !empty($request->input('tts_text')),
            'tts_text_preview' => substr($request->input('tts_text', ''), 0, 50)
        ]);

        $audioSettings = ['source' => $audioSource];

        switch ($audioSource) {
            case 'tts':
                $audioSettings['text'] = $request->input('tts_text');
                $audioSettings['voice'] = $request->input('tts_voice');
                $audioSettings['speed'] = $request->input('tts_speed', 1.0);
                $audioSettings['volume'] = $request->input('tts_volume', 1.0);
                break;

            case 'upload':
                if ($request->hasFile('audio_file')) {
                    $audioFile = $request->file('audio_file');
                    $audioPath = $audioFile->store('audio', 'public');
                    $audioSettings['file'] = $audioPath;
                    $audioSettings['volume'] = $request->input('audio_volume', 1.0);
                    $audioSettings['fade'] = $request->input('audio_fade', 'none');
                }
                break;

            case 'none':
                // No audio
                break;
        }

        return $audioSettings;
    }

    /**
     * Process video information settings
     */
    private function processVideoInfoSettings($request)
    {
        $videoInfo = [
            'title' => $request->input('video_title'),
            'description' => $request->input('video_description'),
            'keywords' => $request->input('video_keywords'),
            'location' => $request->input('video_location'),
            'license' => $request->input('video_license', 'standard'),
            'made_for_kids' => $request->input('video_made_for_kids', false),
        ];

        // Handle thumbnail upload
        if ($request->hasFile('video_thumbnail')) {
            $thumbnailFile = $request->file('video_thumbnail');
            $thumbnailPath = $thumbnailFile->store('thumbnails', 'public');
            $videoInfo['thumbnail'] = $thumbnailPath;
        }

        // TikTok specific settings
        $videoInfo['tiktok'] = [
            'hashtags' => $request->input('tiktok_hashtags'),
            'category' => $request->input('tiktok_category'),
            'privacy' => $request->input('tiktok_privacy', 'public'),
            'allow_comments' => $request->input('tiktok_allow_comments', true),
            'allow_duet' => $request->input('tiktok_allow_duet', true),
            'allow_stitch' => $request->input('tiktok_allow_stitch', true),
        ];

        // YouTube specific settings
        $videoInfo['youtube'] = [
            'tags' => $request->input('youtube_tags'),
            'category' => $request->input('youtube_category'),
            'privacy' => $request->input('youtube_privacy', 'public'),
            'language' => $request->input('youtube_language', 'vi'),
            'allow_comments' => $request->input('youtube_allow_comments', true),
            'allow_ratings' => $request->input('youtube_allow_ratings', true),
            'notify_subscribers' => $request->input('youtube_notify_subscribers', false),
        ];

        return $videoInfo;
    }

    /**
     * Validate and process all video settings
     */
    private function processVideoSettings($request)
    {
        $settings = [
            'media_type' => $request->input('media_type'),
            'duration_based_on' => $request->input('duration_based_on'),
            'sync_with_audio' => $request->input('sync_with_audio', false),
            'auto_adjust_images' => $request->input('auto_adjust_images', false),
        ];

        // Process individual components
        $settings['video_info'] = $this->processVideoInfoSettings($request);
        $settings['images'] = $this->processImageSettings($request);
        $settings['mixed_media'] = $this->processMixedMediaSettings($request);
        $settings['logo'] = $this->processLogoSettings($request);
        $settings['subtitle'] = $this->processSubtitleSettings($request);
        $settings['audio'] = $this->processAudioSettings($request);

        // Calculate duration
        $settings['total_duration'] = $this->calculateVideoDuration($request, $settings['images']);

        return $settings;
    }

    /**
     * AJAX endpoint for calculating video duration
     */
    public function calculateDuration(Request $request)
    {
        try {
            $imageSettings = $this->processImageSettings($request);
            $totalDuration = $this->calculateVideoDuration($request, $imageSettings);

            return response()->json([
                'success' => true,
                'duration' => $totalDuration,
                'formatted_duration' => $this->formatDuration($totalDuration),
                'image_count' => count($imageSettings),
                'settings' => $imageSettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * AJAX endpoint for validating media files
     */
    public function validateMedia(Request $request)
    {
        try {
            $mediaType = $request->input('media_type');
            $results = [];

            if ($mediaType === 'images' && $request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $results['images'][$index] = [
                        'valid' => $image->isValid(),
                        'size' => $this->formatFileSize($image->getSize()),
                        'type' => $image->getMimeType(),
                        'name' => $image->getClientOriginalName()
                    ];
                }
            }

            if ($mediaType === 'video' && $request->hasFile('background_video')) {
                $video = $request->file('background_video');
                $results['video'] = [
                    'valid' => $video->isValid(),
                    'size' => $this->formatFileSize($video->getSize()),
                    'type' => $video->getMimeType(),
                    'name' => $video->getClientOriginalName()
                ];
            }

            if ($mediaType === 'mixed' && $request->hasFile('mixed_media')) {
                foreach ($request->file('mixed_media') as $index => $file) {
                    $results['mixed'][$index] = [
                        'valid' => $file->isValid(),
                        'size' => $this->formatFileSize($file->getSize()),
                        'type' => $file->getMimeType(),
                        'name' => $file->getClientOriginalName(),
                        'is_image' => str_starts_with($file->getMimeType(), 'image/'),
                        'is_video' => str_starts_with($file->getMimeType(), 'video/')
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * AJAX endpoint for getting logo library
     */
    public function getLogoLibrary()
    {
        try {
            $logos = $this->getAvailableLogos();

            return response()->json([
                'success' => true,
                'logos' => $logos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format duration in human readable format
     */
    private function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%d:%04.1f', $minutes, $remainingSeconds);
        } else {
            return sprintf('%.1fs', $remainingSeconds);
        }
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
     * Generate video from template
     */
    public function generateFromTemplate(Request $request)
    {
        // Log immediately to catch all calls
        file_put_contents(storage_path('logs/template-debug.log'),
            "[" . date('Y-m-d H:i:s') . "] generateFromTemplate called\n" .
            "Method: " . $request->method() . "\n" .
            "URL: " . $request->fullUrl() . "\n" .
            "Template ID: " . $request->input('template_id') . "\n" .
            "Has files: " . ($request->hasFile('inputs') ? 'yes' : 'no') . "\n" .
            "All inputs: " . json_encode($request->input('inputs', [])) . "\n\n",
            FILE_APPEND
        );

        $logger = new \App\Services\CustomLoggerService();
        $logger->logInfo('video-template', '=== GENERATE FROM TEMPLATE METHOD CALLED ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'template_id' => $request->input('template_id'),
            'has_files' => $request->hasFile('inputs'),
            'all_inputs' => $request->input('inputs', [])
        ]);

        try {
            file_put_contents(storage_path('logs/template-debug.log'),
                "[" . date('Y-m-d H:i:s') . "] Starting template processing\n",
                FILE_APPEND
            );

            $logger->logInfo('video-template', 'Starting template video generation', [
                'template_id' => $request->template_id,
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token', 'images', 'background_video'])
            ]);

            $template = VideoTemplate::findOrFail($request->template_id);

            $logger->logInfo('video-template', 'Template loaded successfully', [
                'template_name' => $template->name,
                'template_category' => $template->category
            ]);

            // Validate template inputs
            file_put_contents(storage_path('logs/template-debug.log'),
                "[" . date('Y-m-d H:i:s') . "] Before validation\n",
                FILE_APPEND
            );

            $this->validateTemplateInputs($request, $template);

            file_put_contents(storage_path('logs/template-debug.log'),
                "[" . date('Y-m-d H:i:s') . "] After validation\n",
                FILE_APPEND
            );

            $logger->logInfo('video-template', 'Template inputs validated successfully');

            // Merge template settings with user inputs
            $mergedRequest = $this->mergeTemplateData($request, $template);

            file_put_contents(storage_path('logs/template-debug.log'),
                "[" . date('Y-m-d H:i:s') . "] After mergeTemplateData\n" .
                "Merged duration_based_on: " . ($mergedRequest->input('duration_based_on') ?? 'NULL') . "\n" .
                "Merged custom_duration: " . ($mergedRequest->input('custom_duration') ?? 'NULL') . "\n" .
                "Merged image_duration: " . ($mergedRequest->input('image_duration') ?? 'NULL') . "\n" .
                "Merged slide_duration: " . ($mergedRequest->input('slide_duration') ?? 'NULL') . "\n",
                FILE_APPEND
            );

            $logger->logInfo('video-template', 'Template data merged successfully', [
                'merged_platform' => $mergedRequest->input('platform'),
                'merged_settings_count' => count($mergedRequest->all()),
                'subtitle_size' => $mergedRequest->input('subtitle_size'),
                'duration_based_on' => $mergedRequest->input('duration_based_on'),
                'custom_duration' => $mergedRequest->input('custom_duration'),
                'subtitle_color' => $mergedRequest->input('subtitle_color'),
                'subtitle_background' => $mergedRequest->input('subtitle_background'),
                'logo_size' => $mergedRequest->input('logo_size')
            ]);

            // Use existing generation logic
            $result = $this->generate($mergedRequest);

            $logger->logInfo('video-template', 'Template video generation completed successfully');

            return $result;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $logger->logError('video-template', 'Validation failed for template generation', [
                'template_id' => $request->template_id,
                'validation_errors' => $e->errors(),
                'request_data' => $request->except(['_token', 'images', 'background_video'])
            ], $e);

            // Preserve all input data including nested inputs
            $inputData = $request->all();

            // Handle file inputs - store file names for display
            if ($request->hasFile('inputs')) {
                foreach ($request->file('inputs') as $inputName => $files) {
                    if (is_array($files)) {
                        $fileNames = [];
                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $fileNames[] = $file->getClientOriginalName();
                            }
                        }
                        $inputData['inputs'][$inputName . '_files'] = $fileNames;
                    } else {
                        if ($files && $files->isValid()) {
                            $inputData['inputs'][$inputName . '_file'] = $files->getClientOriginalName();
                        }
                    }
                }
            }

            return back()->withErrors($e->errors())->withInput($inputData)->with('error', 'Dữ liệu nhập vào không hợp lệ. Vui lòng kiểm tra lại.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $logger->logError('video-template', 'Template not found', [
                'template_id' => $request->template_id
            ], $e);

            return back()->with('error', 'Template không tồn tại hoặc đã bị xóa.')->withInput();

        } catch (\Exception $e) {
            $logger->logError('video-template', 'Unexpected error during template generation', [
                'template_id' => $request->template_id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_data' => $request->except(['_token', 'images', 'background_video']),
                'background_audio_id' => $request->background_audio_id
            ], $e);

            // Check for specific error types
            $errorMessage = 'Có lỗi xảy ra khi tạo video từ template. Vui lòng thử lại sau.';

            if (strpos($e->getMessage(), 'fileExists') !== false) {
                $errorMessage = 'Lỗi kiểm tra file audio. Vui lòng chọn lại file audio hoặc bỏ qua phần audio.';
            } elseif (strpos($e->getMessage(), 'AudioLibrary') !== false) {
                $errorMessage = 'Lỗi xử lý thư viện audio. Vui lòng thử lại hoặc không sử dụng nhạc nền.';
            } elseif (strpos($e->getMessage(), 'background_audio') !== false) {
                $errorMessage = 'Lỗi xử lý nhạc nền. Vui lòng chọn lại file audio hoặc bỏ qua phần nhạc nền.';
            }

            // Preserve all input data including nested inputs
            $inputData = $request->all();

            // Handle file inputs - store file names for display
            if ($request->hasFile('inputs')) {
                foreach ($request->file('inputs') as $inputName => $files) {
                    if (is_array($files)) {
                        $fileNames = [];
                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $fileNames[] = $file->getClientOriginalName();
                            }
                        }
                        $inputData['inputs'][$inputName . '_files'] = $fileNames;
                    } else {
                        if ($files && $files->isValid()) {
                            $inputData['inputs'][$inputName . '_file'] = $files->getClientOriginalName();
                        }
                    }
                }
            }

            return back()->with('error', $errorMessage)->withInput($inputData);
        }
    }

    /**
     * Validate template inputs
     */
    private function validateTemplateInputs(Request $request, VideoTemplate $template)
    {
        $rules = [];

        // Validate required inputs
        if ($template->required_inputs) {
            foreach ($template->required_inputs as $input) {
                $fieldName = "inputs.{$input['name']}";

                switch ($input['type']) {
                    case 'text':
                    case 'textarea':
                    case 'url':
                        $rules[$fieldName] = 'required|string|max:10000';
                        break;
                    case 'number':
                        $rules[$fieldName] = 'required|numeric';
                        break;
                    case 'audio':
                        $rules[$fieldName] = 'required|file|mimes:mp3,wav,aac,m4a|max:512000'; // 500MB
                        break;
                    case 'image':
                        $rules[$fieldName] = 'required|file|mimes:jpg,jpeg,png,gif|max:51200'; // 50MB
                        break;
                    case 'images':
                        $rules[$fieldName] = 'required|array';
                        $rules[$fieldName . '.*'] = 'file|mimes:jpg,jpeg,png,gif|max:51200'; // 50MB
                        break;
                    case 'video':
                        $rules[$fieldName] = 'required|file|mimes:mp4,avi,mov|max:512000'; // 500MB
                        break;
                    case 'select':
                        $rules[$fieldName] = 'required|string';
                        break;
                    case 'checkbox':
                        $rules[$fieldName] = 'nullable|boolean';
                        break;
                    case 'file':
                        $rules[$fieldName] = 'required|file|max:512000'; // 500MB max
                        break;
                }
            }
        }

        // Validate optional inputs (if provided)
        if ($template->optional_inputs) {
            foreach ($template->optional_inputs as $input) {
                $fieldName = "inputs.{$input['name']}";

                switch ($input['type']) {
                    case 'text':
                    case 'textarea':
                    case 'url':
                        $rules[$fieldName] = 'nullable|string|max:10000';
                        break;
                    case 'number':
                        $rules[$fieldName] = 'nullable|numeric';
                        break;
                    case 'audio':
                        $rules[$fieldName] = 'nullable|file|mimes:mp3,wav,aac,m4a|max:512000'; // 500MB
                        break;
                    case 'image':
                        $rules[$fieldName] = 'nullable|file|mimes:jpg,jpeg,png,gif|max:51200'; // 50MB
                        break;
                    case 'images':
                        $rules[$fieldName] = 'nullable|array';
                        $rules[$fieldName . '.*'] = 'file|mimes:jpg,jpeg,png,gif|max:51200'; // 50MB
                        break;
                    case 'video':
                        $rules[$fieldName] = 'nullable|file|mimes:mp4,avi,mov|max:512000'; // 500MB
                        break;
                    case 'file':
                        $rules[$fieldName] = 'nullable|file|max:512000'; // 500MB
                        break;
                    case 'select':
                        $rules[$fieldName] = 'nullable|string';
                        break;
                    case 'checkbox':
                        $rules[$fieldName] = 'nullable|boolean';
                        break;
                }
            }
        }

        $logger = new \App\Services\CustomLoggerService();

        try {
            $logger->logDebug('video-template', 'Starting template input validation', [
                'template_id' => $template->id,
                'rules_count' => count($rules),
                'input_data_keys' => array_keys($request->input('inputs', []))
            ]);

            $request->validate($rules);

            $logger->logInfo('video-template', 'Template input validation passed successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $logger->logError('video-template', 'Template input validation failed', [
                'template_id' => $template->id,
                'validation_errors' => $e->errors(),
                'failed_rules' => array_keys($rules),
                'input_data' => $request->input('inputs', [])
            ], $e);

            throw $e;
        }
    }

    /**
     * Merge template settings with user inputs
     */
    private function mergeTemplateData(Request $request, VideoTemplate $template)
    {
        file_put_contents(storage_path('logs/template-debug.log'),
            "[" . date('Y-m-d H:i:s') . "] mergeTemplateData called\n" .
            "Template ID: " . $template->id . "\n" .
            "Template settings: " . json_encode($template->settings) . "\n",
            FILE_APPEND
        );

        $logger = new \App\Services\CustomLoggerService();

        try {
            $logger->logDebug('video-template', 'Starting template data merge', [
                'template_id' => $template->id,
                'template_settings_keys' => array_keys($template->settings ?? []),
                'user_inputs_keys' => array_keys($request->input('inputs', []))
            ]);

            // Start with template settings
            $data = $template->settings ?? [];

            // Set default values FIRST, but don't override template settings
            $defaults = [
                'tts_speed' => 1.0,
                'tts_volume' => 18, // Default to 18dB as per requirements
                'audio_volume' => 18, // Default to 18dB as per requirements
                'logo_opacity' => 1.0,
                'logo_margin' => 20,
                'duration_based_on' => 'images', // Default only if not set in template
                'custom_duration' => 30, // Default only if not set in template
                'video_title' => $template->generateVideoName(), // Auto-generate name
                'platform' => 'none',
                'audio_source' => 'none', // Default to no audio to avoid TTS requirement
            ];

            // Merge defaults first, then template settings (template settings take priority)
            $data = array_merge($defaults, $data);

            $logger->logInfo('video-template', 'Template settings after merge', [
                'duration_based_on' => $data['duration_based_on'] ?? 'not_set',
                'custom_duration' => $data['custom_duration'] ?? 'not_set',
                'image_duration' => $data['image_duration'] ?? 'not_set',
                'platform' => $data['platform'] ?? 'not_set',
                'media_type' => $data['media_type'] ?? 'not_set'
            ]);

            // Map template inputs to actual form fields
            $inputs = $request->input('inputs', []);

            foreach ($inputs as $inputName => $inputValue) {
                $logger->logDebug('video-template', "Mapping input: {$inputName}", [
                    'input_value_type' => gettype($inputValue),
                    'input_value_preview' => is_string($inputValue) ? substr($inputValue, 0, 100) : $inputValue,
                    'current_audio_source' => $data['audio_source'] ?? 'not_set',
                    'has_file' => $request->hasFile("inputs.{$inputName}"),
                    'file_info' => $request->hasFile("inputs.{$inputName}") ? [
                        'original_name' => $request->file("inputs.{$inputName}")->getClientOriginalName(),
                        'mime_type' => $request->file("inputs.{$inputName}")->getMimeType(),
                        'size' => $request->file("inputs.{$inputName}")->getSize()
                    ] : null
                ]);

                // Map common input names to form fields
                $this->mapTemplateInput($data, $inputName, $inputValue, $request);

                $logger->logDebug('video-template', "After mapping {$inputName}", [
                    'audio_source' => $data['audio_source'] ?? 'not_set',
                    'has_tts_text' => !empty($data['tts_text']),
                    'tts_text_preview' => !empty($data['tts_text']) ? substr($data['tts_text'], 0, 50) : 'none',
                    'has_product_video' => !empty($data['product_video']),
                    'has_images' => !empty($data['images']),
                    'media_type' => $data['media_type'] ?? 'not_set'
                ]);
            }

            // Post-process script_text based on audio_source
            if (!empty($data['script_text'])) {
                if ($data['audio_source'] === 'tts') {
                    // Only use for TTS if audio_source is explicitly TTS
                    $data['tts_text'] = $data['script_text'];
                    $logger->logDebug('video-template', 'Script text mapped to TTS text', [
                        'audio_source' => $data['audio_source'],
                        'script_text_preview' => substr($data['script_text'], 0, 50)
                    ]);
                } else {
                    // Use for subtitles instead
                    $data['subtitle_text'] = $data['script_text'];
                    $logger->logDebug('video-template', 'Script text mapped to subtitle text', [
                        'audio_source' => $data['audio_source'],
                        'script_text_preview' => substr($data['script_text'], 0, 50)
                    ]);
                }
                // Remove script_text to avoid confusion
                unset($data['script_text']);
            }

            // Apply template background music if not overridden by user
            if (!$request->has('background_audio_id') && $template->background_music_type !== 'none') {
                $backgroundMusic = $template->getBackgroundMusic();
                if ($backgroundMusic) {
                    switch ($backgroundMusic['type']) {
                        case 'library':
                        case 'random':
                            $data['background_audio_id'] = $backgroundMusic['id'];
                            $data['audio_volume'] = $backgroundMusic['volume'];
                            $logger->logDebug('video-template', 'Applied template background music', [
                                'type' => $backgroundMusic['type'],
                                'id' => $backgroundMusic['id'],
                                'title' => $backgroundMusic['title'] ?? 'Unknown',
                                'volume' => $backgroundMusic['volume']
                            ]);
                            break;

                        case 'file':
                            // For uploaded files, we need to handle differently
                            // Could copy to temp location or reference directly
                            $logger->logDebug('video-template', 'Template has uploaded background music file', [
                                'path' => $backgroundMusic['path'],
                                'volume' => $backgroundMusic['volume']
                            ]);
                            break;
                    }
                }
            }

            $logger->logInfo('video-template', 'Template data merge completed successfully', [
                'merged_data_keys' => array_keys($data),
                'final_platform' => $data['platform'] ?? 'not_set',
                'final_audio_source' => $data['audio_source'] ?? 'not_set',
                'has_tts_text' => !empty($data['tts_text']),
                'has_script_text' => !empty($data['script_text']),
                'has_subtitle_text' => !empty($data['subtitle_text']),
                'tts_text_preview' => !empty($data['tts_text']) ? substr($data['tts_text'], 0, 50) : 'none',
                'subtitle_text_preview' => !empty($data['subtitle_text']) ? substr($data['subtitle_text'], 0, 50) : 'none'
            ]);

        } catch (\Exception $e) {
            $logger->logError('video-template', 'Error during template data merge', [
                'template_id' => $template->id,
                'error_message' => $e->getMessage()
            ], $e);

            throw $e;
        }



        // Ensure TTS values are within valid ranges
        if (isset($data['tts_speed'])) {
            $data['tts_speed'] = max(0.5, min(2.0, (float)$data['tts_speed']));
        }
        if (isset($data['tts_volume'])) {
            $data['tts_volume'] = max(0.5, min(2.0, (float)$data['tts_volume']));
        }
        if (isset($data['audio_volume'])) {
            $data['audio_volume'] = max(0.5, min(1.5, (float)$data['audio_volume']));
        }
        if (isset($data['logo_opacity'])) {
            $data['logo_opacity'] = max(0.3, min(1.0, (float)$data['logo_opacity']));
        }

        // Create a new request with merged data
        $mergedRequest = new Request();
        $mergedRequest->merge($data);

        // Debug: Log duration settings in merged request
        $logger->logInfo('video-template', 'Merged request duration settings', [
            'duration_based_on' => $mergedRequest->input('duration_based_on'),
            'custom_duration' => $mergedRequest->input('custom_duration'),
            'image_duration' => $mergedRequest->input('image_duration'),
            'sync_with_audio' => $mergedRequest->input('sync_with_audio'),
            'all_data_keys' => array_keys($data)
        ]);

        // Copy files from original request - handle nested inputs structure
        if ($request->hasFile('inputs')) {
            foreach ($request->file('inputs') as $inputName => $file) {
                if (is_array($file)) {
                    // Handle multiple files (like images array)
                    foreach ($file as $index => $singleFile) {
                        $mergedRequest->files->set("inputs.{$inputName}.{$index}", $singleFile);
                    }

                    // Also copy to top-level for VideoGenerationService compatibility
                    if (in_array($inputName, ['images', 'product_images', 'background_images'])) {
                        foreach ($file as $index => $singleFile) {
                            $mergedRequest->files->set("images.{$index}", $singleFile);
                        }
                    } else {
                        // Check if files are images by mime type
                        $isImageArray = true;
                        foreach ($file as $singleFile) {
                            if (!str_starts_with($singleFile->getMimeType(), 'image/')) {
                                $isImageArray = false;
                                break;
                            }
                        }
                        if ($isImageArray) {
                            foreach ($file as $index => $singleFile) {
                                $mergedRequest->files->set("images.{$index}", $singleFile);
                            }
                        }
                    }
                } else {
                    // Handle single file
                    $mergedRequest->files->set("inputs.{$inputName}", $file);

                    // Also copy to top-level for VideoGenerationService compatibility
                    if (in_array($inputName, ['background_video', 'product_video', 'audio_file', 'logo_file'])) {
                        $mergedRequest->files->set($inputName, $file);
                    } else {
                        // Check if file is an image by mime type
                        if (str_starts_with($file->getMimeType(), 'image/')) {
                            // Single image - set as images array
                            $mergedRequest->files->set('images', [$file]);
                        }
                    }
                }
            }
        }

        // Also copy any other files not in inputs structure
        foreach ($request->files->all() as $key => $files) {
            if ($key !== 'inputs') {
                if (is_array($files)) {
                    foreach ($files as $subKey => $file) {
                        $mergedRequest->files->set($key . '.' . $subKey, $file);
                    }
                } else {
                    $mergedRequest->files->set($key, $files);
                }
            }
        }

        // Handle background audio from library
        if ($request->has('background_audio_id') && !empty($request->background_audio_id)) {
            $audioId = $request->background_audio_id;

            try {
                $audio = \App\Models\AudioLibrary::find($audioId);

                if ($audio) {
                    // Check if file exists
                    if ($audio->fileExists()) {
                        $logger->logInfo('video-template', 'Background audio selected from library', [
                            'audio_id' => $audioId,
                            'audio_title' => $audio->title,
                            'audio_duration' => $audio->duration,
                            'audio_file_path' => $audio->file_path
                        ]);

                        // Set the audio file path for video generation
                        $mergedRequest->merge([
                            'background_audio_id' => $audioId,
                            'background_audio_path' => $audio->getFullPath(),
                            'background_audio_duration' => $audio->duration,
                            'use_background_audio' => true,
                            'audio_source' => 'library',
                            'library_audio_id' => $audioId
                        ]);

                        // Override default audio_source if background audio is selected
                        $data['audio_source'] = 'library';
                        $data['library_audio_id'] = $audioId;

                        // Increment usage count
                        $audio->incrementUsage();

                    } else {
                        $logger->logError('video-template', 'Background audio file not found', [
                            'audio_id' => $audioId,
                            'audio_title' => $audio->title,
                            'audio_file_path' => $audio->file_path,
                            'full_path' => $audio->getFullPath()
                        ]);
                    }
                } else {
                    $logger->logError('video-template', 'Background audio not found in database', [
                        'audio_id' => $audioId
                    ]);
                }

            } catch (\Exception $e) {
                $logger->logError('video-template', 'Error processing background audio', [
                    'audio_id' => $audioId,
                    'error_message' => $e->getMessage()
                ], $e);
            }
        }

        file_put_contents(storage_path('logs/template-debug.log'),
            "[" . date('Y-m-d H:i:s') . "] mergeTemplateData completed successfully\n" .
            "Merged duration_based_on: " . ($mergedRequest->input('duration_based_on') ?? 'NULL') . "\n" .
            "Merged custom_duration: " . ($mergedRequest->input('custom_duration') ?? 'NULL') . "\n",
            FILE_APPEND
        );

        return $mergedRequest;
    }

    /**
     * Map template input to form field
     */
    private function mapTemplateInput(&$data, $inputName, $inputValue, $request)
    {
        // Common mappings from template input names to video generator form fields
        $mappings = [
            // Text content mappings - conditional based on audio_source
            'script_text' => 'script_text', // Keep as script_text, will be mapped later based on audio_source
            'lesson_content' => 'script_text',
            'product_script' => 'script_text',
            'subtitle_text' => 'subtitle_text',
            'custom_subtitle' => 'subtitle_text',

            // Media mappings
            'background_images' => 'images',
            'lesson_images' => 'images',
            'product_images' => 'images',
            'product_media' => 'images',
            'titktok_1_anh' => 'images', // Template specific mapping
            'titktok_1_sub' => 'subtitle_text', // Template specific subtitle mapping
            'background_video' => 'background_video',
            'lesson_videos' => 'background_video',
            'product_videos' => 'background_video',

            // Audio mappings
            'audio_file' => 'audio_file',
            'voice_over_file' => 'audio_file',
            'background_music' => 'audio_file',
            'background_audio_id' => 'background_audio_id',

            // Logo mappings
            'logo_image' => 'logo_file',
            'brand_logo' => 'logo_file',

            // Video info mappings
            'video_title' => 'video_title',
            'video_description' => 'video_description',
            'youtube_tags' => 'youtube_tags',
            'hashtags_tiktok' => 'tiktok_hashtags',
            'hashtags' => 'tiktok_hashtags',

            // Output name mapping
            'output_name' => 'tiktok_output_name',

            // Special mappings
            'call_to_action' => 'subtitle_text',
            'product_price' => 'subtitle_text',
            'contact_info' => 'subtitle_text',

            // File mappings
            'subtitle_file' => 'subtitle_file',

            // Duration mappings
            'fixed_duration_seconds' => 'custom_duration',
            'image_duration_seconds' => 'default_image_duration',
            'max_video_duration' => 'max_duration',
            'custom_video_length' => 'custom_duration',
            'lesson_image_duration' => 'default_image_duration',
            'sync_tolerance' => 'sync_tolerance',
            'tiktok_target_duration' => 'tiktok_duration',
            'youtube_target_duration' => 'youtube_duration',
            'product_showcase_time' => 'default_image_duration',
            'cta_duration' => 'cta_duration',

            // Demo template mappings
            'demo_content' => 'tts_text',
            'demo_images' => 'images',
            'demo_audio' => 'audio_file',
            'fixed_duration_value' => 'custom_duration',
            'image_display_time' => 'default_image_duration',
            'sync_tolerance_value' => 'sync_tolerance',
            'max_duration_limit' => 'max_duration',
        ];

        if (isset($mappings[$inputName])) {
            $formField = $mappings[$inputName];

            // Handle file inputs
            if ($request->hasFile("inputs.{$inputName}")) {
                $file = $request->file("inputs.{$inputName}");



                // Special handling for images array
                if ($formField === 'images' && is_array($file)) {
                    $data[$formField] = $file;
                } else {
                    $data[$formField] = $file;
                }
            } else {
                // Handle text inputs
                if (!empty($inputValue)) {
                    // Special handling for TTS text mapping
                    if ($formField === 'tts_text') {
                        // Only set TTS text if audio_source is explicitly set to TTS
                        // Otherwise, store as subtitle text or skip
                        if (isset($data['audio_source']) && $data['audio_source'] === 'tts') {
                            $data[$formField] = $inputValue;
                        } else {
                            // Store as subtitle text instead for non-TTS usage
                            $data['subtitle_text'] = $inputValue;
                        }
                    } else {
                        $data[$formField] = $inputValue;
                    }
                }
            }
        } else {
            // Auto-mapping for template inputs based on type
            if ($request->hasFile("inputs.{$inputName}")) {
                $file = $request->file("inputs.{$inputName}");

                // Auto-map image/images type inputs to 'images' field
                if (is_array($file)) {
                    // Multiple files - map to images
                    $data['images'] = $file;
                } else {
                    // Single file - check if it's an image or video
                    $mimeType = $file->getMimeType();
                    if (str_starts_with($mimeType, 'image/')) {
                        // Single image - convert to array for consistency
                        $data['images'] = [$file];
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        // Video file - map to appropriate video field based on input name
                        if (str_contains($inputName, 'video') || str_contains($inputName, 'background')) {
                            $data['product_video'] = $file;
                        } else {
                            $data[$inputName] = $file;
                        }
                    } else {
                        // Other file types
                        $data[$inputName] = $file;
                    }
                }
            } elseif (!empty($inputValue)) {
                // Direct mapping for text inputs
                $data[$inputName] = $inputValue;
            }
        }

        // Special handling for template choice inputs
        $this->handleTemplateChoices($data, $inputName, $inputValue);

        // Special handling for platform-specific output names
        if ($inputName === 'output_name' && !empty($inputValue)) {
            $platform = $data['platform'] ?? 'tiktok';
            if ($platform === 'tiktok') {
                $data['tiktok_output_name'] = $inputValue;
            } elseif ($platform === 'youtube') {
                $data['youtube_output_name'] = $inputValue;
            } elseif ($platform === 'both') {
                $data['tiktok_output_name'] = $inputValue . '_tiktok';
                $data['youtube_output_name'] = $inputValue . '_youtube';
            }
        }
    }

    /**
     * Handle template choice inputs that affect settings
     */
    private function handleTemplateChoices(&$data, $inputName, $inputValue)
    {
        switch ($inputName) {
            case 'media_type_choice':
                $data['media_type'] = $inputValue;
                break;

            case 'audio_source_choice':
                $data['audio_source'] = $inputValue;
                break;

            case 'media_setup':
                if ($inputValue === 'images_only') {
                    $data['media_type'] = 'images';
                } elseif ($inputValue === 'video_only') {
                    $data['media_type'] = 'video';
                } elseif (str_contains($inputValue, 'mixed')) {
                    $data['media_type'] = 'mixed';
                    if ($inputValue === 'mixed_sequence') {
                        $data['mixed_mode'] = 'sequence';
                    } elseif ($inputValue === 'mixed_overlay') {
                        $data['mixed_mode'] = 'overlay';
                    } elseif ($inputValue === 'split_screen') {
                        $data['mixed_mode'] = 'split';
                    }
                }
                break;

            case 'audio_method':
                if ($inputValue === 'tts_vbee') {
                    $data['audio_source'] = 'tts';
                } elseif ($inputValue === 'upload_audio') {
                    $data['audio_source'] = 'upload';
                } elseif ($inputValue === 'video_audio') {
                    $data['audio_source'] = 'video';
                    $data['remove_video_audio'] = false;
                } elseif ($inputValue === 'mixed_audio') {
                    $data['audio_source'] = 'mixed';
                }
                break;

            case 'subtitle_method':
                if ($inputValue === 'auto_from_tts') {
                    $data['enable_subtitle'] = true;
                    $data['subtitle_source'] = 'auto';
                } elseif ($inputValue === 'manual_input') {
                    $data['enable_subtitle'] = true;
                    $data['subtitle_source'] = 'manual';
                } elseif ($inputValue === 'upload_srt') {
                    $data['enable_subtitle'] = true;
                    $data['subtitle_source'] = 'upload';
                } elseif ($inputValue === 'no_subtitle') {
                    $data['enable_subtitle'] = false;
                }
                break;

            case 'duration_control':
                $this->handleDurationControl($data, $inputValue);
                break;

            case 'video_duration_control':
                $this->handleVideoDurationControl($data, $inputValue);
                break;

            case 'marketing_duration_strategy':
                $this->handleMarketingDurationStrategy($data, $inputValue);
                break;

            case 'duration_strategy':
                $this->handleDurationStrategy($data, $inputValue);
                break;

            case 'content_strategy':
            case 'media_composition':
            case 'audio_strategy':
                // Store these for reference but don't directly map to settings
                $data["template_{$inputName}"] = $inputValue;
                break;
        }
    }

    /**
     * Handle duration control for TikTok template
     */
    private function handleDurationControl(&$data, $inputValue)
    {
        switch ($inputValue) {
            case 'auto_images':
                $data['duration_based_on'] = 'images';
                $data['sync_with_audio'] = false;
                break;

            case 'audio_length':
                $data['duration_based_on'] = 'audio';
                $data['sync_with_audio'] = true;
                break;

            case 'video_length':
                $data['duration_based_on'] = 'video';
                $data['sync_with_audio'] = false;
                break;

            case 'fixed_duration':
                $data['duration_based_on'] = 'custom';
                $data['sync_with_audio'] = false;
                break;
        }
    }

    /**
     * Handle video duration control for YouTube template
     */
    private function handleVideoDurationControl(&$data, $inputValue)
    {
        switch ($inputValue) {
            case 'content_based':
                $data['duration_based_on'] = 'images';
                $data['auto_adjust_images'] = true;
                break;

            case 'audio_sync':
                $data['duration_based_on'] = 'audio';
                $data['sync_with_audio'] = true;
                break;

            case 'video_sync':
                $data['duration_based_on'] = 'video';
                $data['sync_with_audio'] = false;
                break;

            case 'custom_length':
                $data['duration_based_on'] = 'custom';
                $data['sync_with_audio'] = false;
                break;
        }
    }

    /**
     * Handle marketing duration strategy
     */
    private function handleMarketingDurationStrategy(&$data, $inputValue)
    {
        switch ($inputValue) {
            case 'platform_optimal':
                // Set optimal durations for each platform
                $data['tiktok_duration'] = 30; // Optimal for TikTok
                $data['youtube_duration'] = 120; // Optimal for YouTube
                $data['duration_based_on'] = 'custom';
                break;

            case 'content_driven':
                $data['duration_based_on'] = 'images';
                $data['auto_adjust_images'] = true;
                break;

            case 'audio_matched':
                $data['duration_based_on'] = 'audio';
                $data['sync_with_audio'] = true;
                break;

            case 'fixed_marketing':
                $data['duration_based_on'] = 'custom';
                $data['sync_with_audio'] = false;
                break;
        }
    }

    /**
     * Handle duration strategy for demo template
     */
    private function handleDurationStrategy(&$data, $inputValue)
    {
        switch ($inputValue) {
            case 'auto_images':
                $data['duration_based_on'] = 'images';
                $data['sync_with_audio'] = false;
                break;

            case 'audio_sync':
                $data['duration_based_on'] = 'audio';
                $data['sync_with_audio'] = true;
                break;

            case 'fixed_time':
                $data['duration_based_on'] = 'custom';
                $data['sync_with_audio'] = false;
                break;

            case 'custom_control':
                $data['duration_based_on'] = 'custom';
                $data['sync_with_audio'] = false;
                $data['auto_adjust_images'] = true;
                break;
        }
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
