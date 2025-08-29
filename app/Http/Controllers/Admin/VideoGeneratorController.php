<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Services\VideoGenerationService;
use App\Services\VideoPublishingService;
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
        // Debug form submission
        Log::info('🚀 VIDEO GENERATION: Form submitted', [
            'all_input_keys' => array_keys($request->all()),
            'subtitle_options' => [
                'subtitle_color' => $request->input('subtitle_color'),
                'subtitle_position' => $request->input('subtitle_position'),
                'subtitle_background' => $request->input('subtitle_background'),
                'subtitle_size' => $request->input('subtitle_size'),
            ],
            'image_order_mapping' => $request->input('image_order_mapping'),
            'library_audio_id' => $request->input('library_audio_id'),
            'slide_duration' => $request->input('slide_duration'),
            'slide_transition' => $request->input('slide_transition'),
        ]);

        try {
            // Set longer execution time for video generation
            set_time_limit(300); // 5 minutes

            // 🔥 DEBUG: Log all input data
            Log::info('🔥🔥🔥 VIDEO GENERATOR DEBUG: Raw request data', [
                'all_input' => $request->all(),
                'files' => $request->allFiles(),
                'platform' => $request->input('platform'),
                'media_type' => $request->input('media_type'),
                'audio_source' => $request->input('audio_source'),
                'library_audio_id' => $request->input('library_audio_id'),
                'library_audio_id_type' => gettype($request->input('library_audio_id')),
                'library_audio_id_empty' => empty($request->input('library_audio_id')),
                'slide_duration' => $request->input('slide_duration'),
                'slide_transition' => $request->input('slide_transition'),
                'output_name' => $request->input('output_name'),
                'tts_text' => $request->input('tts_text'),
                'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
                'has_audio_file' => $request->hasFile('audio_file'),
                'request_method' => $request->method(),
                'request_url' => $request->url(),
                'user_agent' => $request->userAgent()
            ]);

        $platform = $request->input('platform');

        // Validate platform
        if (!in_array($platform, ['tiktok', 'youtube', 'both', 'none'])) {
            return back()->with('error', 'Platform không hợp lệ')->withInput();
        }

            // Auto-fix common validation issues
            $this->autoFixValidationIssues($request);

            // Platform-specific validation
            $validationRules = $this->getValidationRules($platform, 'single');
            $request->validate($validationRules);

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
     * Auto-fix common validation issues
     */
    private function autoFixValidationIssues(Request $request)
    {
        // Fix TTS validation: if audio_source is tts but no tts_text, switch to none
        if ($request->input('audio_source') === 'tts' && empty($request->input('tts_text'))) {
            $request->merge(['audio_source' => 'none']);
            Log::info('🔧 AUTO-FIX: Changed audio_source from tts to none (no tts_text provided)');
        }

        // Fix library audio: if audio_source is library but no library_audio_id, switch to none
        if ($request->input('audio_source') === 'library' && empty($request->input('library_audio_id'))) {
            $request->merge(['audio_source' => 'none']);
            Log::info('🔧 AUTO-FIX: Changed audio_source from library to none (no library_audio_id provided)');
        }

        // Fix upload audio: if audio_source is upload but no audio file, switch to none
        if ($request->input('audio_source') === 'upload' && !$request->hasFile('audio_file')) {
            $request->merge(['audio_source' => 'none']);
            Log::info('🔧 AUTO-FIX: Changed audio_source from upload to none (no audio_file provided)');
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
            'mixed_images' => 'nullable|array|max:20',
            'mixed_images.*' => 'file|mimes:jpg,jpeg,png,gif|max:51200', // 50MB per image
            'mixed_videos' => 'nullable|array|max:10',
            'mixed_videos.*' => 'file|mimes:mp4,avi,mov|max:512000', // 500MB per video
            'remove_video_audio' => 'nullable|boolean',

            // Individual image settings
            'default_image_duration' => 'nullable|numeric|between:0.5,30',
            'default_transition_effect' => 'nullable|in:fade,slide,zoom,dissolve,wipe,none',
            'transition_duration' => 'nullable|numeric|between:0.1,2',

            // Mixed mode settings
            'mixed_mode' => 'nullable|in:sequence,overlay,split',
            'sequence_strategy' => 'nullable|in:even_distribution,alternating',
            'image_display_duration' => 'nullable|numeric|between:0.5,10',
            'image_distribution_mode' => 'nullable|in:auto_even,custom_timing',
            'image_timings' => 'nullable|array',
            'image_timings.*' => 'nullable|numeric|between:0,300',
            'sequence_image_duration' => 'nullable|numeric|between:1,30',
            'sequence_video_duration' => 'nullable|in:full,5,10,15,custom',
            'custom_video_seconds' => 'nullable|numeric|between:1,300',
            'mixed_image_duration' => 'nullable|numeric|between:0.5,30',
            'mixed_image_transition' => 'nullable|in:none,fade,slide,zoom,dissolve',
            'mixed_content_behavior' => 'nullable|in:loop,hold_last,black_screen',
            'mixed_auto_adjust_images' => 'nullable|boolean',
            'mixed_image_durations' => 'nullable|array',
            'mixed_image_durations.*' => 'nullable|numeric|between:0.5,30',
            'mixed_video_durations' => 'nullable|array',
            'mixed_video_durations.*' => 'nullable|in:full,5,10,15,30,custom',
            'mixed_video_custom_durations' => 'nullable|array',
            'mixed_video_custom_durations.*' => 'nullable|numeric|between:1,300',

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
            'audio_source' => 'nullable|in:tts,upload,library,none,video_original',
            'tts_text' => 'nullable|required_if:audio_source,tts|string|min:1|max:5000',
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
            'tiktok_channel_id' => 'nullable|exists:channels,id',
            'youtube_channel_id' => 'nullable|exists:channels,id',
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
            // Get arrays from form
            $imageDurations = $request->input('image_durations', []);
            $imageTransitions = $request->input('image_transitions', []);
            $transitionDuration = $request->input('transition_duration', 0.5);

            // Default values
            $defaultDuration = $request->input('default_image_duration', 3);
            $defaultTransition = $request->input('default_transition_effect', 'slide');

            foreach ($request->file('images') as $index => $image) {
                $imageSettings[$index] = [
                    'duration' => isset($imageDurations[$index]) ? $imageDurations[$index] : $defaultDuration,
                    'transition' => isset($imageTransitions[$index]) ? $imageTransitions[$index] : $defaultTransition,
                    'transition_duration' => $transitionDuration,
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

        // Common mixed media settings
        $settings['image_duration'] = $request->input('mixed_image_duration', 3);
        $settings['image_transition'] = $request->input('mixed_image_transition', 'fade');
        $settings['content_behavior'] = $request->input('mixed_content_behavior', 'loop');
        $settings['auto_adjust_images'] = $request->boolean('mixed_auto_adjust_images', true);

        // Individual durations
        $settings['image_durations'] = $request->input('mixed_image_durations', []);
        $settings['video_durations'] = $request->input('mixed_video_durations', []);
        $settings['video_custom_durations'] = $request->input('mixed_video_custom_durations', []);

        switch ($mixedMode) {
            case 'sequence':
                $settings['sequence_image_duration'] = $request->input('sequence_image_duration', 4);
                $settings['sequence_video_duration'] = $request->input('sequence_video_duration', 'full');
                if ($settings['sequence_video_duration'] === 'custom') {
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

            if ($mediaType === 'mixed') {
                // Handle legacy mixed_media upload
                if ($request->hasFile('mixed_media')) {
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

                // Handle separate mixed_images upload
                if ($request->hasFile('mixed_images')) {
                    foreach ($request->file('mixed_images') as $index => $image) {
                        $results['mixed_images'][$index] = [
                            'valid' => $image->isValid(),
                            'size' => $this->formatFileSize($image->getSize()),
                            'type' => $image->getMimeType(),
                            'name' => $image->getClientOriginalName()
                        ];
                    }
                }

                // Handle separate mixed_videos upload
                if ($request->hasFile('mixed_videos')) {
                    foreach ($request->file('mixed_videos') as $index => $video) {
                        $results['mixed_videos'][$index] = [
                            'valid' => $video->isValid(),
                            'size' => $this->formatFileSize($video->getSize()),
                            'type' => $video->getMimeType(),
                            'name' => $video->getClientOriginalName()
                        ];
                    }
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

        // Use consistent path with video generation
        $filePath = storage_path("app/videos/{$filename}");

        // Fallback to old platform-specific directories for backward compatibility
        if (!File::exists($filePath)) {
            $directory = $platform === 'tiktok' ? 'tiktok_videos' : 'youtube_videos';
            $filePath = storage_path("app/{$directory}/{$filename}");
        }

        Log::info('VIDEO DOWNLOAD: Attempting download', [
            'platform' => $platform,
            'filename' => $filename,
            'primary_path' => storage_path("app/videos/{$filename}"),
            'fallback_path' => storage_path("app/{$directory}/{$filename}"),
            'file_exists' => File::exists($filePath)
        ]);

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
     * Generate batch videos from template
     */
    public function generateBatchFromTemplate(Request $request)
    {
        $logger = new \App\Services\CustomLoggerService();

        try {
            $logger->logInfo('video-template-batch', 'Starting batch template video generation', [
                'template_id' => $request->template_id,
                'user_id' => auth()->id(),
                'batch_count' => count($request->input('batch_videos', []))
            ]);

            // Validate basic request
            $request->validate([
                'template_id' => 'required|exists:video_templates,id',
                'batch_videos' => 'required|array|min:1|max:20',
                'batch_videos.*.video_name' => 'required|string|max:255',
                'batch_videos.*.inputs' => 'nullable|array',
                'batch_background_audio_id' => 'nullable|exists:audio_libraries,id',
                'batch_channel_id' => 'nullable|exists:channels,id'
            ]);

            $template = VideoTemplate::findOrFail($request->template_id);
            $batchVideos = $request->input('batch_videos', []);
            $results = [];
            $errors = [];

            $logger->logInfo('video-template-batch', 'Template loaded, processing batch', [
                'template_name' => $template->name,
                'video_count' => count($batchVideos)
            ]);

            // Process each video in the batch
            foreach ($batchVideos as $index => $videoData) {
                try {
                    $logger->logInfo('video-template-batch', "Processing video {$index}", [
                        'video_name' => $videoData['video_name']
                    ]);

                    // Create individual request for this video
                    $individualRequest = $this->createIndividualRequestFromBatch($request, $videoData, $template, $index);


	                    // Debug: what inputs/files does the individual request carry?
	                    try {
	                        $dbgLogger = new \App\Services\CustomLoggerService();
	                        $dbgInputs = $individualRequest->input('inputs', []);
	                        $dbgFiles = $individualRequest->files->get('inputs', []);
	                        $dbgLogger->logDebug('video-template-batch', 'Pre-validate individual payload', [
	                            'index' => $index,
	                            'video_name' => $videoData['video_name'] ?? null,
	                            'input_keys' => is_array($dbgInputs) ? array_keys($dbgInputs) : [],
	                            'file_keys' => is_array($dbgFiles) ? array_keys($dbgFiles) : [],
	                            'has_file_titktok_1_anh' => is_array($dbgFiles) && array_key_exists('titktok_1_anh', $dbgFiles),
	                        ]);
	                    } catch (\Throwable $e) {}

                    // Validate template inputs for this video
                    $this->validateTemplateInputs($individualRequest, $template);

                    // Merge template data
                    $mergedRequest = $this->mergeTemplateData($individualRequest, $template);

                    // Generate video
                    $result = $this->generate($mergedRequest);

                    if ($result->getStatusCode() === 302) {
                        $results[] = [
                            'index' => $index,
                            'video_name' => $videoData['video_name'],
                            'status' => 'success',
                            'message' => 'Video đã được tạo thành công'
                        ];

                        $logger->logInfo('video-template-batch', "Video {$index} generated successfully", [
                            'video_name' => $videoData['video_name']
                        ]);
                    } else {
                        throw new \Exception('Video generation failed with status: ' . $result->getStatusCode());
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'video_name' => $videoData['video_name'] ?? "Video {$index}",
                        'error' => $e->getMessage()
                    ];

                    $logger->logError('video-template-batch', "Failed to generate video {$index}", [
                        'video_name' => $videoData['video_name'] ?? "Video {$index}",
                        'error' => $e->getMessage()
                    ], $e);
                }
            }

            // Prepare response message
            $successCount = count($results);
            $errorCount = count($errors);
            $totalCount = count($batchVideos);

            if ($successCount === $totalCount) {
                $message = "Tất cả {$totalCount} video đã được tạo thành công!";
                $alertType = 'success';
            } elseif ($successCount > 0) {
                $message = "Đã tạo thành công {$successCount}/{$totalCount} video. {$errorCount} video gặp lỗi.";
                $alertType = 'warning';
            } else {
                $message = "Không thể tạo video nào. Vui lòng kiểm tra lại dữ liệu.";
                $alertType = 'error';
            }

            $logger->logInfo('video-template-batch', 'Batch processing completed', [
                'total_videos' => $totalCount,
                'successful' => $successCount,
                'failed' => $errorCount
            ]);

            return back()->with($alertType, $message)
                         ->with('batch_results', $results)
                         ->with('batch_errors', $errors);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $logger->logError('video-template-batch', 'Validation failed', [
                'template_id' => $request->template_id,
                'errors' => $e->errors()
            ], $e);

            return back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $logger->logError('video-template-batch', 'Template not found', [
                'template_id' => $request->template_id
            ], $e);

            return back()->with('error', 'Template không tồn tại hoặc đã bị xóa.')->withInput();

        } catch (\Exception $e) {
            $logger->logError('video-template-batch', 'Unexpected error during batch generation', [
                'template_id' => $request->template_id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ], $e);

            return back()->with('error', 'Có lỗi xảy ra khi tạo batch video: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Create individual request from batch data
     */
    private function createIndividualRequestFromBatch(Request $batchRequest, array $videoData, VideoTemplate $template, ?int $videoIndex = null)
    {
        // Create new request with individual video data
        $requestData = [
            'template_id' => $batchRequest->template_id,
            'video_name' => $videoData['video_name'],
            'inputs' => $videoData['inputs'] ?? [],
            'background_audio_id' => $batchRequest->batch_background_audio_id,
            'channel_id' => $batchRequest->batch_channel_id
        ];

        // Create new request instance
        $individualRequest = new Request($requestData);

        // Handle file uploads for this specific video
        $batchFiles = $batchRequest->file('batch_videos');
        if (is_array($batchFiles)) {

            // Prefer the explicit index from the loop; fall back to searching by value
            $effectiveIndex = $videoIndex;
            if ($effectiveIndex === null) {
                $searched = array_search($videoData, $batchRequest->input('batch_videos', []));
                if ($searched !== false) {
                    $effectiveIndex = $searched;
                }
            }

            if ($effectiveIndex !== null && isset($batchFiles[$effectiveIndex]['inputs'])) {
                $individualFiles = [];
                foreach ($batchFiles[$effectiveIndex]['inputs'] as $inputName => $files) {
                    $individualFiles[$inputName] = $files;
                }
                // Attach files for this video's inputs
                $individualRequest->files->set('inputs', $individualFiles);

                // Ensure array presence for multi-file (images) inputs so 'required|array' passes
                try {
                    $templateInputs = array_merge($template->required_inputs ?? [], $template->optional_inputs ?? []);
                    $multiImageNames = [];
                    foreach ($templateInputs as $tplInput) {
                        if (($tplInput['type'] ?? null) === 'images' && isset($tplInput['name'])) {
                            $multiImageNames[] = $tplInput['name'];
                        }
                    }

                    $currentInputs = $individualRequest->input('inputs', []);
                    foreach ($individualFiles as $name => $files) {
                        if (in_array($name, $multiImageNames, true)) {
                            if (!array_key_exists($name, $currentInputs)) {
                                // Add placeholder array entries matching file count
                                $placeholders = [];
                                if (is_array($files)) {
                                    $placeholders = array_fill(0, count($files), '__uploaded__');
                                } else {
                                    // Single file provided where template expects multiple; still set one placeholder
                                    $placeholders = ['__uploaded__'];
                                }
                                $currentInputs[$name] = $placeholders;
                            }
                        }
                    }
                    if (!empty($currentInputs)) {
                        $individualRequest->merge(['inputs' => $currentInputs]);
                    }
                } catch (\Throwable $e) {
                    // Fail-safe: do not block if placeholder merge fails
                }

            // Normalize keys: accept both spaces and underscores variants matching template names

        // Debug logs for batch: confirm inputs/files are attached correctly
        try {
            $logger = new \App\Services\CustomLoggerService();
            $inputsBag = $individualRequest->input('inputs', []);
            $filesBag = $individualRequest->files->get('inputs', []);

            $checkName = 'titktok_1_anh';
            $inFiles = is_array($filesBag) && array_key_exists($checkName, $filesBag);
            $fileInfo = null;
            if ($inFiles) {
                $f = $filesBag[$checkName];
                if (is_array($f)) {
                    $names = [];
                    foreach ($f as $ff) {
                        $names[] = method_exists($ff, 'getClientOriginalName') ? $ff->getClientOriginalName() : null;
                    }
                    $fileInfo = [
                        'count' => count($f),
                        'names' => $names,
                    ];
                } else {
                    $fileInfo = [
                        'single' => method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : null,
                    ];
                }
            }

            $logger->logDebug('video-template-batch', 'Individual request prepared (batch)', [
                'video_index_param' => $videoIndex,
                'effective_index' => isset($effectiveIndex) ? $effectiveIndex : null,
                'input_keys' => is_array($inputsBag) ? array_keys($inputsBag) : [],
                'file_keys' => is_array($filesBag) ? array_keys($filesBag) : [],
                'specific_check' => [
                    'name' => $checkName,
                    'in_input' => is_array($inputsBag) && array_key_exists($checkName, $inputsBag),
                    'in_files' => $inFiles,
                    'file_info' => $fileInfo,
                ],
            ]);
        } catch (\Throwable $e) {
            // ignore logging failure
        }

            try {
                $allInputs = array_merge($template->required_inputs ?? [], $template->optional_inputs ?? []);
                $inputsBag = $individualRequest->input('inputs', []);
                $filesBag = $individualRequest->files->get('inputs', []);

                foreach ($allInputs as $inp) {
                    if (!isset($inp['name'])) continue;
                    $name = $inp['name'];
                    $spaceVariant = str_replace('_', ' ', $name);
                    $underscoreVariant = str_replace(' ', '_', $name);

                    // Map from space variant to canonical name
                    if (!array_key_exists($name, $inputsBag) && array_key_exists($spaceVariant, $inputsBag)) {
                        $inputsBag[$name] = $inputsBag[$spaceVariant];
                    }
                    if (!array_key_exists($name, $filesBag) && array_key_exists($spaceVariant, $filesBag)) {
                        $filesBag[$name] = $filesBag[$spaceVariant];
                    }

                    // Map from underscore variant to canonical name (handles case template has spaces)
                    if (!array_key_exists($name, $inputsBag) && array_key_exists($underscoreVariant, $inputsBag)) {
                        $inputsBag[$name] = $inputsBag[$underscoreVariant];
                    }
                    if (!array_key_exists($name, $filesBag) && array_key_exists($underscoreVariant, $filesBag)) {
                        $filesBag[$name] = $filesBag[$underscoreVariant];
                    }
                }

                if (!empty($inputsBag)) {
                    $individualRequest->merge(['inputs' => $inputsBag]);
                }
                if (!empty($filesBag)) {
                    $individualRequest->files->set('inputs', $filesBag);
                }
            } catch (\Throwable $e) {
                // No-op if normalization fails
            }

            }
        }

        return $individualRequest;
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
                        // In batch mode, rely on presence of uploaded files; array() is not required for Validator to see file array
                        $rules[$fieldName] = 'required';
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
