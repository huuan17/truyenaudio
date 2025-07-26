<?php

namespace App\Services;

use App\Jobs\GenerateUniversalVideoJob;
use App\Models\VideoGenerationTask;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class VideoGenerationService
{
    /**
     * Queue a single video generation task
     */
    public function queueSingleVideo($platform, Request $request, $userId)
    {
        // Create temp directory
        $tempId = uniqid();
        $tempDir = storage_path("app/videos/temp/{$platform}_{$tempId}");
        
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Prepare parameters based on platform
        $parameters = $this->prepareParameters($platform, $request, $tempDir);
        
        // Estimate duration based on content
        $estimatedDuration = $this->estimateProcessingTime($platform, $request);

        // Create task record
        $task = VideoGenerationTask::create([
            'user_id' => $userId,
            'platform' => $platform,
            'type' => VideoGenerationTask::TYPE_SINGLE,
            'status' => VideoGenerationTask::STATUS_PENDING,
            'priority' => $this->calculatePriority($request),
            'parameters' => $parameters,
            'estimated_duration' => $estimatedDuration
        ]);

        // Dispatch unified video generation job
        \App\Jobs\GenerateUniversalVideoJob::dispatch($task->id, $platform, $parameters, $tempDir)
                                          ->onQueue('video');

        return $task;
    }

    /**
     * Queue batch video generation tasks
     */
    public function queueBatchVideos($platform, Request $request, $userId)
    {
        $batchId = Str::uuid();
        $tasks = [];
        
        // Get batch data based on platform
        $batchData = $this->prepareBatchData($platform, $request);
        $totalVideos = count($batchData);

        foreach ($batchData as $index => $videoData) {
            // Create temp directory for each video
            $tempId = uniqid();
            $tempDir = storage_path("app/videos/temp/{$platform}_batch_{$tempId}");
            
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Prepare parameters for individual video
            $parameters = $this->prepareBatchVideoParameters($platform, $request, $videoData, $tempDir, $index);
            
            // Estimate duration
            $estimatedDuration = $this->estimateProcessingTime($platform, $request, $videoData);

            // Create task record
            $task = VideoGenerationTask::create([
                'user_id' => $userId,
                'platform' => $platform,
                'type' => VideoGenerationTask::TYPE_BATCH,
                'status' => VideoGenerationTask::STATUS_PENDING,
                'priority' => $this->calculatePriority($request),
                'parameters' => $parameters,
                'estimated_duration' => $estimatedDuration,
                'batch_id' => $batchId,
                'batch_index' => $index + 1,
                'total_in_batch' => $totalVideos
            ]);

            // Dispatch unified job to queue with delay to prevent overwhelming
            $delay = $index * 5; // 5 second delay between jobs
            \App\Jobs\GenerateUniversalVideoJob::dispatch($task->id, $platform, $parameters, $tempDir)
                                              ->onQueue('video')
                                              ->delay(now()->addSeconds($delay));

            $tasks[] = $task;
        }

        return [
            'batch_id' => $batchId,
            'tasks' => $tasks,
            'total_count' => $totalVideos
        ];
    }

    /**
     * Prepare parameters for single video
     */
    private function prepareParameters($platform, Request $request, $tempDir)
    {
        $outputName = $request->output_name ?: $this->generateDefaultVideoName($platform, $request);
        if (!Str::endsWith($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }

        $baseParams = [
            '--output' => $outputName,
            '--temp-dir' => $tempDir,
            '--task-id' => uniqid('video_', true),
        ];

        if ($platform === 'none') {
            // Parameters for videos without channel publishing
            $noneParams = [
                '--platform' => 'none',
                '--media-type' => $request->media_type ?: 'images',
            ];

            // Get output name from none-specific field
            if ($request->none_output_name) {
                $noneParams['--output'] = $request->none_output_name . '.mp4';
            }

            // Video settings
            if ($request->none_resolution) {
                $noneParams['--resolution'] = $request->none_resolution;
            }
            if ($request->none_fps) {
                $noneParams['--fps'] = $request->none_fps;
            }
            if ($request->none_quality) {
                $noneParams['--quality'] = $request->none_quality;
            }

            // Handle script/audio - only pass script if using TTS and have TTS text
            if ($request->tts_text && $request->audio_source === 'tts') {
                $noneParams['--script'] = $request->tts_text;
                \Log::info('VideoGenerationService: Adding TTS script for none platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'tts_text_preview' => substr($request->tts_text, 0, 50)
                ]);
            } else {
                \Log::info('VideoGenerationService: Skipping TTS script for none platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'has_script_text' => !empty($request->script_text)
                ]);
            }

            // Handle audio source
            if ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $noneParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library' && $request->library_audio_id) {
                $noneParams['--library-audio-id'] = $request->library_audio_id;
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($request->library_audio_id);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle media files based on type
            if ($request->media_type === 'images') {
                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                } elseif ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                }

                if ($imageFiles) {
                    $noneParams['--slide-duration'] = $request->slide_duration ?: 3;
                    $noneParams['--slide-transition'] = $request->slide_transition ?: 'slide';

                    // Store multiple images
                    $imagePaths = [];
                    foreach ($imageFiles as $index => $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                    }
                    $noneParams['--images'] = implode(',', $imagePaths);
                }
            } elseif ($request->hasFile('product_video') || $request->hasFile('background_video')) {
                $videoFile = $request->file('product_video') ?: $request->file('background_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $noneParams['--product-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle settings
            if ($request->boolean('enable_subtitle')) {
                $noneParams['--enable-subtitle'] = true;
                if ($request->subtitle_text) {
                    $noneParams['--subtitle-text'] = $request->subtitle_text;
                }
                if ($request->subtitle_position) {
                    $noneParams['--subtitle-position'] = $request->subtitle_position;
                }
                if ($request->subtitle_size) {
                    $noneParams['--subtitle-size'] = $request->subtitle_size;
                }
                if ($request->subtitle_color) {
                    $noneParams['--subtitle-color'] = $request->subtitle_color;
                }
            }

            return array_merge($baseParams, $noneParams);
        } elseif ($platform === 'tiktok') {
            $tiktokParams = [
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
                '--volume' => $request->volume,
                '--media-type' => $request->media_type ?: 'video',
            ];

            // Only add script if using TTS and have TTS text
            if ($request->tts_text && $request->audio_source === 'tts') {
                $tiktokParams['--script'] = $request->tts_text;
                \Log::info('VideoGenerationService: Adding TTS script for TikTok platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'tts_text_preview' => substr($request->tts_text, 0, 50)
                ]);
            } else {
                \Log::info('VideoGenerationService: Skipping TTS script for TikTok platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'has_script_text' => !empty($request->script_text)
                ]);
            }

            // Handle audio source for TikTok
            if ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $tiktokParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library' && $request->library_audio_id) {
                $tiktokParams['--library-audio-id'] = $request->library_audio_id;
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($request->library_audio_id);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle media files based on type
            if ($request->media_type === 'images') {
                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                } elseif ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                }

                if ($imageFiles) {
                    $tiktokParams['--slide-duration'] = $request->slide_duration ?: 3;
                    $tiktokParams['--slide-transition'] = $request->slide_transition ?: 'slide';

                    // Store multiple images
                    $imagePaths = [];
                    foreach ($imageFiles as $index => $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                    }
                    $tiktokParams['--product-images'] = implode(',', $imagePaths);
                }
            } elseif ($request->hasFile('product_video') || $request->hasFile('background_video')) {
                $videoFile = $request->file('product_video') ?: $request->file('background_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $tiktokParams['--product-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle (optional)
            if ($request->boolean('enable_subtitle') && $request->subtitle_text) {
                $tiktokParams['--subtitle-text'] = $request->subtitle_text;
                $tiktokParams['--subtitle-position'] = $request->subtitle_position ?: 'bottom';
                $tiktokParams['--subtitle-size'] = $request->subtitle_size ?: 24;
                $tiktokParams['--subtitle-color'] = $request->subtitle_color ?: '#FFFFFF';
                $tiktokParams['--subtitle-background'] = $request->subtitle_background ?: '#000000';
                $tiktokParams['--subtitle-font'] = $request->subtitle_font ?: 'Arial';
                $tiktokParams['--subtitle-duration'] = $request->subtitle_duration ?: 5;

                // Add timing parameters
                $tiktokParams['--subtitle-timing-mode'] = $request->subtitle_timing_mode ?: 'auto';
                $tiktokParams['--subtitle-per-image'] = $request->subtitle_per_image ?: 'auto';
                $tiktokParams['--words-per-image'] = $request->words_per_image ?: 10;
                $tiktokParams['--subtitle-delay'] = $request->subtitle_delay ?: 0.5;
                $tiktokParams['--subtitle-fade'] = $request->subtitle_fade ?: 'in';

            // Add duration control parameters
            if ($request->has('duration_based_on')) {
                $tiktokParams['--duration-based-on'] = $request->duration_based_on;
            }
            if ($request->has('custom_duration')) {
                $tiktokParams['--custom-duration'] = $request->custom_duration;
            }
            if ($request->has('sync_with_audio')) {
                $tiktokParams['--sync-with-audio'] = $request->sync_with_audio ? 'true' : 'false';
            }
            if ($request->has('max_duration')) {
                $tiktokParams['--max-duration'] = $request->max_duration;
            }
            if ($request->has('sync_tolerance')) {
                $tiktokParams['--sync-tolerance'] = $request->sync_tolerance;
            }
            }

            // Handle channel settings (optional)
            if ($request->channel_id) {
                $tiktokParams['--channel-id'] = $request->channel_id;
                $tiktokParams['--schedule-post'] = $request->boolean('schedule_post');
                if ($request->boolean('schedule_post')) {
                    $tiktokParams['--scheduled-date'] = $request->scheduled_date;
                    $tiktokParams['--scheduled-time'] = $request->scheduled_time;
                }
                $tiktokParams['--post-title'] = $request->post_title;
                $tiktokParams['--post-description'] = $request->post_description;
                $tiktokParams['--post-tags'] = $request->post_tags;
            }

            return array_merge($baseParams, $tiktokParams);
        } else { // youtube
            $youtubeParams = [
                '--video-content-type' => $request->video_content_type,
                '--image-duration' => $request->image_duration ?: 3,
                '--video-loop' => $request->boolean('video_loop'),
                '--remove-video-audio' => $request->boolean('remove_video_audio'),
            ];

            // Handle audio source
            if ($request->audio_source === 'tts' && $request->tts_text) {
                $youtubeParams['--text-content'] = $request->tts_text;
                $youtubeParams['--voice'] = $request->tts_voice;
                $youtubeParams['--bitrate'] = $request->tts_bitrate;
                $youtubeParams['--speed'] = $request->tts_speed;
                $youtubeParams['--volume'] = $request->tts_volume;
            } elseif ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $youtubeParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library' && $request->library_audio_id) {
                $youtubeParams['--library-audio-id'] = $request->library_audio_id;
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($request->library_audio_id);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle video content
            if ($request->video_content_type === 'images') {
                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                } elseif ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                }

                if ($imageFiles) {
                    $imagePaths = [];
                    foreach ($imageFiles as $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                    }
                    $youtubeParams['--images'] = implode(',', $imagePaths);
                }
            } elseif ($request->hasFile('background_video') || $request->hasFile('product_video')) {
                $videoFile = $request->file('background_video') ?: $request->file('product_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $youtubeParams['--background-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle (optional)
            if ($request->boolean('enable_subtitle') && $request->subtitle_text) {
                $youtubeParams['--subtitle-text'] = $request->subtitle_text;
                $youtubeParams['--subtitle-position'] = $request->subtitle_position ?: 'bottom';
                $youtubeParams['--subtitle-size'] = $request->subtitle_size ?: 24;
                $youtubeParams['--subtitle-color'] = $request->subtitle_color ?: '#FFFFFF';
                $youtubeParams['--subtitle-background'] = $request->subtitle_background ?: '#000000';
                $youtubeParams['--subtitle-font'] = $request->subtitle_font ?: 'Arial';
                $youtubeParams['--subtitle-duration'] = $request->subtitle_duration ?: 5;

                // Add timing parameters
                $youtubeParams['--subtitle-timing-mode'] = $request->subtitle_timing_mode ?: 'auto';
                $youtubeParams['--subtitle-per-image'] = $request->subtitle_per_image ?: 'auto';
                $youtubeParams['--words-per-image'] = $request->words_per_image ?: 10;
                $youtubeParams['--subtitle-delay'] = $request->subtitle_delay ?: 0.5;
                $youtubeParams['--subtitle-fade'] = $request->subtitle_fade ?: 'in';

                // Add duration control parameters
                if ($request->has('duration_based_on')) {
                    $youtubeParams['--duration-based-on'] = $request->duration_based_on;
                }
                if ($request->has('custom_duration')) {
                    $youtubeParams['--custom-duration'] = $request->custom_duration;
                }
                if ($request->has('sync_with_audio')) {
                    $youtubeParams['--sync-with-audio'] = $request->sync_with_audio ? 'true' : 'false';
                }
                if ($request->has('max_duration')) {
                    $youtubeParams['--max-duration'] = $request->max_duration;
                }
                if ($request->has('sync_tolerance')) {
                    $youtubeParams['--sync-tolerance'] = $request->sync_tolerance;
                }
            }

            // Handle channel settings (optional)
            if ($request->channel_id) {
                $youtubeParams['--channel-id'] = $request->channel_id;
                $youtubeParams['--schedule-post'] = $request->boolean('schedule_post');
                if ($request->boolean('schedule_post')) {
                    $youtubeParams['--scheduled-date'] = $request->scheduled_date;
                    $youtubeParams['--scheduled-time'] = $request->scheduled_time;
                }
                $youtubeParams['--post-title'] = $request->post_title;
                $youtubeParams['--post-description'] = $request->post_description;
                $youtubeParams['--post-tags'] = $request->post_tags;
            }

            return array_merge($baseParams, $youtubeParams);
        }
    }

    /**
     * Prepare batch data
     */
    private function prepareBatchData($platform, Request $request)
    {
        $batchMode = $request->input('batch_mode', 'multiple_content');
        $batchData = [];

        if ($batchMode === 'template') {
            // Template mode: split text by lines
            $templateTexts = $request->input('template_texts', '');
            $lines = array_filter(explode("\n", $templateTexts));

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $outputName = $request->boolean('template_auto_name')
                        ? "video_" . ($index + 1)
                        : null;

                    $batchData[] = [
                        'text_content' => $line,
                        'audio_source' => 'tts', // Default to TTS for template mode
                        'output_name' => $outputName,
                        'subtitle_text' => $line, // Use same text as subtitle
                    ];
                }
            }
        } else {
            // Multiple content mode: use individual inputs
            $batchTexts = $request->input('batch_texts', []);
            $batchAudioFiles = $request->file('batch_audio_files') ?: [];
            $batchImages = $request->file('batch_images') ?: [];
            $batchBackgroundVideos = $request->file('batch_background_videos') ?: [];
            $batchSubtitles = $request->input('batch_subtitles', []);
            $batchOutputNames = $request->input('batch_output_names', []);
            $batchDurations = $request->input('batch_durations', []);
            $batchTransitions = $request->input('batch_transitions', []);
            $batchVolumes = $request->input('batch_volumes', []);

            // Get the maximum count from all arrays
            $maxCount = max(
                count($batchTexts),
                count($batchAudioFiles),
                count($batchImages),
                count($batchBackgroundVideos),
                count($batchSubtitles),
                count($batchOutputNames)
            );

            for ($i = 1; $i <= $maxCount; $i++) {
                $hasContent = !empty($batchTexts[$i]) ||
                             !empty($batchAudioFiles[$i]) ||
                             !empty($batchImages[$i]) ||
                             !empty($batchBackgroundVideos[$i]);

                if ($hasContent) {
                    $audioSource = 'none';
                    if (!empty($batchTexts[$i])) {
                        $audioSource = 'tts';
                    } elseif (!empty($batchAudioFiles[$i])) {
                        $audioSource = 'upload';
                    }

                    $batchData[] = [
                        'text_content' => $batchTexts[$i] ?? null,
                        'audio_source' => $audioSource,
                        'audio_file' => $batchAudioFiles[$i] ?? null,
                        'images' => $batchImages[$i] ?? null,
                        'background_video' => $batchBackgroundVideos[$i] ?? null,
                        'subtitle_text' => $batchSubtitles[$i] ?? null,
                        'output_name' => $batchOutputNames[$i] ?? "video_$i",
                        'duration' => $batchDurations[$i] ?? null,
                        'transition' => $batchTransitions[$i] ?? 'fade',
                        'volume' => $batchVolumes[$i] ?? 100,
                    ];
                }
            }
        }

        return $batchData;
    }

    /**
     * Prepare parameters for batch video
     */
    private function prepareBatchVideoParameters($platform, Request $request, $videoData, $tempDir, $index)
    {
        $outputName = $videoData['output_name'] ?: $this->generateDefaultBatchVideoName($platform, $request, $index);
        if (!Str::endsWith($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }

        $baseParams = [
            '--output' => $outputName,
            '--temp-dir' => $tempDir,
            '--task-id' => uniqid('batch_video_', true),
        ];

        // Add subtitle parameters if enabled
        if ($request->boolean('enable_subtitle') && !empty($videoData['subtitle_text'])) {
            $baseParams = array_merge($baseParams, [
                '--subtitle-text' => $videoData['subtitle_text'],
                '--subtitle-position' => $request->subtitle_position ?: 'bottom',
                '--subtitle-size' => $request->subtitle_size ?: 24,
                '--subtitle-color' => $request->subtitle_color ?: '#FFFFFF',
                '--subtitle-background' => $request->subtitle_background ?: '#000000',
                '--subtitle-font' => $request->subtitle_font ?: 'Arial',
                '--subtitle-duration' => $request->subtitle_duration ?: 5,
            ]);
        }

        if ($platform === 'none') {
            // Parameters for videos without channel publishing
            $params = array_merge($baseParams, [
                '--platform' => 'none',
                '--media-type' => $request->media_type ?: 'images',
            ]);

            // Only add script if using TTS and have TTS text
            if (!empty($videoData['script']) && $request->audio_source === 'tts') {
                $params['--script'] = $videoData['script'];
            }

            // Video settings from none-specific fields
            if ($request->none_resolution) {
                $params['--resolution'] = $request->none_resolution;
            }
            if ($request->none_fps) {
                $params['--fps'] = $request->none_fps;
            }
            if ($request->none_quality) {
                $params['--quality'] = $request->none_quality;
            }

            // Handle file uploads
            if ($videoData['product_video']) {
                $videoPath = $this->saveUploadedFile($videoData['product_video'], $tempDir, 'product_video.' . $videoData['product_video']->getClientOriginalExtension());
                $params['--product-video'] = $videoPath;
            }

            if ($videoData['product_image']) {
                $imagePath = $this->saveUploadedFile($videoData['product_image'], $tempDir, 'product_image.' . $videoData['product_image']->getClientOriginalExtension());
                $params['--product-image'] = $imagePath;
            }

            return $params;
        } elseif ($platform === 'tiktok') {
            $params = array_merge($baseParams, [
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
                '--volume' => $request->volume,
            ]);

            // Only add script if using TTS and have TTS text
            if (!empty($videoData['script']) && $request->audio_source === 'tts') {
                $params['--script'] = $videoData['script'];
            }

            // Handle audio source for TikTok batch
            if ($videoData['audio_file']) {
                $audioPath = $this->saveUploadedFile($videoData['audio_file'], $tempDir, 'audio.' . $videoData['audio_file']->getClientOriginalExtension());
                $params['--audio-file'] = $audioPath;
            } elseif ($videoData['audio_source'] === 'library' && !empty($videoData['library_audio_id'])) {
                $params['--library-audio-id'] = $videoData['library_audio_id'];
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($videoData['library_audio_id']);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle file uploads
            if ($videoData['product_video']) {
                $videoPath = $this->saveUploadedFile($videoData['product_video'], $tempDir, 'product_video.' . $videoData['product_video']->getClientOriginalExtension());
                $params['--product-video'] = $videoPath;
            }

            if ($videoData['product_image']) {
                $imagePath = $this->saveUploadedFile($videoData['product_image'], $tempDir, 'product_image.' . $videoData['product_image']->getClientOriginalExtension());
                $params['--product-image'] = $imagePath;
            }

            return $params;
        } else { // youtube
            $params = array_merge($baseParams, [
                '--video-content-type' => $videoData['video_content_type'],
                '--image-duration' => $request->image_duration ?: 3,
                '--video-loop' => $request->boolean('video_loop'),
                '--remove-video-audio' => $request->boolean('remove_video_audio'),
            ]);

            // Handle audio
            if ($videoData['audio_source'] === 'text') {
                $params['--text'] = $videoData['text_content'];
                $params['--voice'] = $request->voice;
                $params['--bitrate'] = $request->bitrate;
                $params['--speed'] = $request->speed;
                $params['--volume'] = $request->volume;
            } elseif ($videoData['audio_file']) {
                $audioPath = $this->saveUploadedFile($videoData['audio_file'], $tempDir, 'audio.' . $videoData['audio_file']->getClientOriginalExtension());
                $params['--audio-file'] = $audioPath;
            } elseif ($videoData['audio_source'] === 'library' && !empty($videoData['library_audio_id'])) {
                $params['--library-audio-id'] = $videoData['library_audio_id'];
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($videoData['library_audio_id']);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle images and videos
            if ($videoData['images']) {
                $imagePaths = [];
                foreach ($videoData['images'] as $idx => $image) {
                    $imagePath = $this->saveUploadedFile($image, $tempDir, "image_{$idx}." . $image->getClientOriginalExtension());
                    $imagePaths[] = $imagePath;
                }
                $params['--images'] = implode(',', $imagePaths);
            }

            if ($videoData['background_video']) {
                $videoPath = $this->saveUploadedFile($videoData['background_video'], $tempDir, 'background_video.' . $videoData['background_video']->getClientOriginalExtension());
                $params['--background-video'] = $videoPath;
            }

            return $params;
        }
    }

    /**
     * Save uploaded file to temp directory
     */
    private function saveUploadedFile($file, $tempDir, $filename)
    {
        $path = $tempDir . '/' . $filename;
        $file->move($tempDir, $filename);
        return $path;
    }

    /**
     * Estimate processing time based on content
     */
    private function estimateProcessingTime($platform, Request $request, $videoData = null)
    {
        $baseTime = 180; // 3 minutes base time
        
        if ($platform === 'none') {
            // For videos without channel publishing, use similar estimation as TikTok
            $script = $videoData['script'] ?? $request->script_text ?? '';
            $scriptLength = strlen($script);

            // Estimate based on script length (roughly 1 minute per 100 characters)
            $estimatedTime = $baseTime + ($scriptLength / 100) * 60;

            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }

            // Add time based on media type
            $mediaType = $request->media_type ?: 'images';
            if ($mediaType === 'video') {
                $estimatedTime += 60; // Extra time for video processing
            }

        } elseif ($platform === 'tiktok') {
            $script = $videoData['script'] ?? $request->script_text ?? '';
            $scriptLength = strlen($script);

            // Estimate based on script length (roughly 1 minute per 100 characters)
            $estimatedTime = $baseTime + ($scriptLength / 100) * 60;

            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }

        } else { // youtube
            $estimatedTime = $baseTime;
            
            // Add time based on content type
            $contentType = $videoData['video_content_type'] ?? $request->video_content_type ?? 'images';
            
            switch ($contentType) {
                case 'images':
                    $estimatedTime += 120; // 2 minutes for image processing
                    break;
                case 'video':
                    $estimatedTime += 180; // 3 minutes for video processing
                    break;
                case 'mixed':
                    $estimatedTime += 240; // 4 minutes for mixed content
                    break;
            }
            
            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }
        }
        
        return min(1800, max(120, $estimatedTime)); // Between 2 minutes and 30 minutes
    }

    /**
     * Calculate task priority
     */
    private function calculatePriority(Request $request)
    {
        // Default normal priority
        $priority = VideoGenerationTask::PRIORITY_NORMAL;
        
        // Increase priority for single videos
        if ($request->creation_mode === 'single') {
            $priority = VideoGenerationTask::PRIORITY_HIGH;
        }
        
        return $priority;
    }

    /**
     * Get queue status
     */
    public function getQueueStatus()
    {
        return [
            'pending' => VideoGenerationTask::pending()->count(),
            'processing' => VideoGenerationTask::processing()->count(),
            'completed_today' => VideoGenerationTask::completed()
                                ->whereDate('completed_at', today())
                                ->count(),
            'failed_today' => VideoGenerationTask::failed()
                            ->whereDate('completed_at', today())
                            ->count(),
        ];
    }

    /**
     * Get user's tasks
     */
    public function getUserTasks($userId, $limit = 10)
    {
        return VideoGenerationTask::forUser($userId)
                                 ->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();
    }

    /**
     * Generate default video name with date and sequence number
     */
    private function generateDefaultVideoName($platform, Request $request)
    {
        $date = date('Y-m-d');
        $time = date('H-i');

        // Get today's video count for this platform and user
        $todayCount = VideoGenerationTask::where('user_id', auth()->id())
                                        ->where('platform', $platform)
                                        ->whereDate('created_at', today())
                                        ->count();

        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

        // Create descriptive name based on content
        $baseName = $this->generateDescriptiveName($platform, $request);

        return "{$baseName}_{$date}_{$time}_{$sequence}";
    }

    /**
     * Generate descriptive name based on content
     */
    private function generateDescriptiveName($platform, Request $request)
    {
        if ($platform === 'tiktok') {
            // For TikTok, use media type
            $mediaType = $request->media_type === 'images' ? 'slide' : 'video';
            return "tiktok_{$mediaType}";
        } else {
            // For YouTube, use content type
            $contentType = $request->video_content_type ?: 'video';
            $audioSource = $request->audio_source === 'text' ? 'tts' : 'audio';
            return "youtube_{$contentType}_{$audioSource}";
        }
    }

    /**
     * Generate default batch video name with sequence
     */
    private function generateDefaultBatchVideoName($platform, Request $request, $index)
    {
        $date = date('Y-m-d');
        $time = date('H-i');

        // Get today's video count for this platform and user
        $todayCount = VideoGenerationTask::where('user_id', auth()->id())
                                        ->where('platform', $platform)
                                        ->whereDate('created_at', today())
                                        ->count();

        $batchSequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $videoSequence = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

        // Create descriptive name based on content
        $baseName = $this->generateDescriptiveName($platform, $request);

        return "{$baseName}_batch_{$date}_{$time}_{$batchSequence}_{$videoSequence}";
    }
}
