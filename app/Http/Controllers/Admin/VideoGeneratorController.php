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
        if (!in_array($platform, ['tiktok', 'youtube', 'both'])) {
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
        if (!in_array($platform, ['tiktok', 'youtube', 'both'])) {
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
            'platform' => 'required|in:tiktok,youtube,both',

            // Media content rules
            'media_type' => 'required|in:images,video,mixed',
            'images' => 'nullable|array|max:20',
            'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:10240',
            'background_video' => 'nullable|file|mimes:mp4,avi,mov|max:512000',
            'mixed_media' => 'nullable|array|max:30',
            'mixed_media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,avi,mov|max:512000',
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
            'audio_source' => 'required|in:tts,upload,none',
            'tts_text' => 'nullable|required_if:audio_source,tts|string|min:10|max:5000',
            'tts_voice' => 'nullable|string',
            'tts_speed' => 'nullable|numeric|between:0.5,2.0',
            'tts_volume' => 'nullable|numeric|between:1.0,2.0',
            'audio_file' => 'nullable|required_if:audio_source,upload|file|mimes:mp3,wav,aac|max:102400',
            'audio_volume' => 'nullable|numeric|between:0.5,1.5',
            'audio_fade' => 'nullable|in:none,in,out,both',

            // Subtitle rules
            'enable_subtitle' => 'nullable|boolean',
            'subtitle_source' => 'nullable|required_if:enable_subtitle,1|in:auto,manual,upload',
            'subtitle_text' => 'nullable|string|max:10000',
            'subtitle_file' => 'nullable|file|mimes:srt|max:1024',
            'subtitle_position' => 'nullable|in:bottom,top,center',
            'subtitle_size' => 'nullable|in:small,medium,large,xlarge',
            'subtitle_color' => 'nullable|in:white,black,yellow,red,blue,green',
            'subtitle_background' => 'nullable|in:none,black,white,solid_black,solid_white',
            'subtitle_outline' => 'nullable|boolean',

            // Logo rules
            'enable_logo' => 'nullable|boolean',
            'logo_source' => 'nullable|required_if:enable_logo,1|in:library,upload',
            'selected_logo' => 'nullable|string',
            'logo_file' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:5120',
            'logo_position' => 'nullable|in:top-left,top-right,top-center,bottom-left,bottom-right,bottom-center,center,center-left,center-right',
            'logo_size' => 'nullable|in:small,medium,large,xlarge,custom',
            'logo_width' => 'nullable|numeric|between:50,500',
            'logo_height' => 'nullable|numeric|between:50,500',
            'logo_opacity' => 'nullable|numeric|between:0.3,1.0',
            'logo_margin' => 'nullable|numeric|between:10,50',
            'logo_duration' => 'nullable|in:full,start,end,custom',
            'logo_start_time' => 'nullable|numeric|min:0',
            'logo_end_time' => 'nullable|numeric|min:0',

            // Duration settings
            'duration_based_on' => 'required|in:images,video,audio,custom',
            'custom_duration' => 'nullable|required_if:duration_based_on,custom|numeric|between:5,600',
            'content_behavior' => 'nullable|in:loop,freeze,fade,crop',
            'sync_with_audio' => 'nullable|boolean',
            'auto_adjust_images' => 'nullable|boolean',

            // Video information
            'video_title' => 'required|string|max:100',
            'video_description' => 'nullable|string|max:5000',
            'video_keywords' => 'nullable|string|max:500',
            'video_location' => 'nullable|string|max:255',
            'video_thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
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
            'output_name' => 'nullable|string|max:100'
        ];

        // Add platform-specific rules
        if ($platform === 'tiktok') {
            $platformRules = $this->getTiktokValidationRules($mode);
        } elseif ($platform === 'youtube') {
            $platformRules = $this->getYoutubeValidationRules($mode);
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
            'batch_count' => 'nullable|numeric|between:2,10',
            'batch_videos' => 'nullable|array|max:10',
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
        $audioSource = $request->input('audio_source', 'tts');
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
