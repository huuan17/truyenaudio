<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\CrawlController;
use App\Http\Controllers\TextToSpeechController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\QueueHelpController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// CSRF refresh route
Route::get('/csrf-token', function() {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

// Clear session route for debugging
Route::get('/clear-session', function() {
    session()->flush();
    session()->regenerate();
    return redirect()->route('login')->with('success', 'Session đã được xóa. Vui lòng đăng nhập lại.');
})->name('clear.session');

// Debug session and CSRF token
Route::get('/debug-session', function() {
    return response()->json([
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_token' => session()->token(),
        'user_authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'session_data' => session()->all(),
    ]);
})->name('debug.session');

// Frontend routes (public)
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/search', [App\Http\Controllers\HomeController::class, 'search'])->name('search');
Route::get('/hot', [App\Http\Controllers\HomeController::class, 'hot'])->name('stories.hot');
Route::get('/completed', [App\Http\Controllers\HomeController::class, 'completed'])->name('stories.completed');
Route::get('/recent', [App\Http\Controllers\HomeController::class, 'recent'])->name('stories.recent');
Route::get('/genre/{slug}', [App\Http\Controllers\HomeController::class, 'genre'])->name('genre.show');
Route::get('/authors', [App\Http\Controllers\HomeController::class, 'authors'])->name('authors.index');
Route::get('/author/{slug}', [App\Http\Controllers\HomeController::class, 'author'])->name('author.show');
Route::get('/story/{slug}', [App\Http\Controllers\HomeController::class, 'story'])->name('story.show');
Route::get('/story/{storySlug}/chapter/{chapterNumber}', [App\Http\Controllers\HomeController::class, 'chapter'])->name('chapter.show');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    // Admin root redirect
    Route::get('/admin', function() {
        return redirect()->route('admin.dashboard');
    });

    // Admin routes with /admin prefix
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Stories management
        Route::resource('stories', StoryController::class);
        Route::get('/stories-search', [StoryController::class, 'search'])->name('stories.search');

        // Story maintenance
        Route::get('/stories/{story}/maintenance', [StoryController::class, 'maintenance'])->name('stories.maintenance');
        Route::post('/stories/{story}/fix-chapter-count', [StoryController::class, 'fixChapterCount'])->name('stories.fix-chapter-count');
        Route::post('/stories/{story}/update-crawl-status', [StoryController::class, 'updateCrawlStatus'])->name('stories.update-crawl-status');
        Route::post('/stories/{story}/cancel-pending-tts', [StoryController::class, 'cancelPendingTTS'])->name('stories.cancel-pending-tts');
        Route::post('/stories/{story}/reset-stuck-tts', [StoryController::class, 'resetStuckTTS'])->name('stories.reset-stuck-tts');

        // Story chapters management
        Route::get('/stories/{story}/chapters', [StoryController::class, 'chapters'])->name('stories.chapters');

        // Story crawling
        Route::get('/stories/{story}/crawl', [StoryController::class, 'showCrawlForm'])->name('stories.crawl.form');
        Route::post('/stories/{story}/crawl', [StoryController::class, 'crawl'])->name('stories.crawl');

        // Smart crawl (re-crawl missing chapters only)
        Route::get('/stories/{story}/smart-crawl', [StoryController::class, 'smartCrawl'])->name('stories.smart-crawl');
        Route::post('/stories/{story}/smart-crawl', [StoryController::class, 'executeSmartCrawl'])->name('stories.execute-smart-crawl');

        // Cancel crawl and queue management
        Route::post('/stories/{story}/cancel-crawl', [StoryController::class, 'cancelCrawl'])->name('stories.cancel-crawl');
        Route::post('/stories/{story}/remove-from-queue', [StoryController::class, 'removeFromQueue'])->name('stories.remove-from-queue');

        // Toggle story public status
        Route::post('/stories/{story}/toggle-public', [StoryController::class, 'togglePublic'])->name('stories.toggle-public');

        // Update story status
        Route::post('/stories/{story}/update-status', [StoryController::class, 'updateStatus'])->name('stories.update-status');

        // Get stories status (for real-time updates)
        Route::post('/stories/status', [StoryController::class, 'getStatus'])->name('stories.status');

        // Story text-to-speech
        Route::get('/stories/{story}/tts', [StoryController::class, 'showTtsForm'])->name('stories.tts.form');
        Route::post('/stories/{story}/tts', [StoryController::class, 'tts'])->name('stories.tts');

        // Story chapter scanning
        Route::get('/stories/{story}/scan', [StoryController::class, 'showScanForm'])->name('stories.scan.form');
        Route::post('/stories/{story}/scan', [StoryController::class, 'scanChapters'])->name('stories.scan');

        // Story video generation
        Route::get('/stories/{story}/video', [StoryController::class, 'showVideoForm'])->name('stories.video');
        Route::post('/stories/{story}/video', [StoryController::class, 'generateVideo'])->name('stories.video.generate');

        // Video overlay management
        Route::post('/overlay/upload', [StoryController::class, 'uploadOverlay'])->name('overlay.upload');
        Route::delete('/overlay/delete', [StoryController::class, 'deleteOverlay'])->name('overlay.delete');

        // Chapters management
        Route::resource('chapters', ChapterController::class);
        Route::get('/chapters/create/{story_id?}', [ChapterController::class, 'create'])->name('chapters.create');
        Route::get('/chapters/story/{story_id}', [ChapterController::class, 'indexByStory'])->name('chapters.index.by-story');
        Route::get('/chapters', [ChapterController::class, 'indexAll'])->name('chapters.index');

        // Chapter TTS routes
        Route::post('/chapters/{chapter}/tts', [App\Http\Controllers\Admin\ChapterController::class, 'convertToTts'])->name('chapters.tts');
        Route::post('/stories/{story}/chapters/tts-all', [App\Http\Controllers\Admin\ChapterController::class, 'convertAllToTts'])->name('chapters.tts.all');
        Route::post('/chapters/bulk-tts', [App\Http\Controllers\Admin\ChapterController::class, 'bulkTts'])->name('chapters.bulk-tts');
        Route::post('/chapters/bulk-delete', [App\Http\Controllers\Admin\ChapterController::class, 'bulkDelete'])->name('chapters.bulk-delete');
        Route::get('/chapters/tts-status-summary/{story}', [App\Http\Controllers\Admin\ChapterController::class, 'getTtsStatusSummary'])->name('chapters.tts-status-summary');
        Route::get('/chapters/bulk-tts-tasks/{story}', [App\Http\Controllers\Admin\ChapterController::class, 'getBulkTtsTasks'])->name('chapters.bulk-tts-tasks');
        Route::post('/chapters/cancel-all-tts', [App\Http\Controllers\Admin\ChapterController::class, 'cancelAllTts'])->name('chapters.cancel-all-tts');

        // Chapter content route
        Route::get('/chapters/{chapter}/content', [ChapterController::class, 'getContent'])->name('chapters.content');

        // Genres management
        Route::resource('genres', GenreController::class);

        // Authors management
        Route::resource('authors', App\Http\Controllers\Admin\AuthorController::class);
        Route::patch('/authors/{author}/toggle-status', [App\Http\Controllers\Admin\AuthorController::class, 'toggleStatus'])->name('authors.toggle-status');

        // Logo Management
        Route::get('/logos', [App\Http\Controllers\Admin\LogoController::class, 'index'])->name('logos.index');
        Route::post('/logos/upload', [App\Http\Controllers\Admin\LogoController::class, 'upload'])->name('logos.upload');
        Route::delete('/logos/delete', [App\Http\Controllers\Admin\LogoController::class, 'delete'])->name('logos.delete');
        Route::get('/logos/download/{filename}', [App\Http\Controllers\Admin\LogoController::class, 'download'])->name('logos.download');
        Route::get('/logos/serve/{filename}', [App\Http\Controllers\Admin\LogoController::class, 'serve'])->name('logo.serve');
        Route::get('/logos/api', [App\Http\Controllers\Admin\LogoController::class, 'getLogos'])->name('logos.api');

        // Channel Management
        Route::resource('channels', App\Http\Controllers\Admin\ChannelController::class);
        Route::patch('/channels/{channel}/toggle-status', [App\Http\Controllers\Admin\ChannelController::class, 'toggleStatus'])->name('channels.toggle-status');
        Route::post('/channels/{channel}/test-connection', [App\Http\Controllers\Admin\ChannelController::class, 'testConnection'])->name('channels.test-connection');
        Route::get('/channels-api', [App\Http\Controllers\Admin\ChannelController::class, 'getChannelsApi'])->name('channels.api');

        // YouTube OAuth connect/callback
        Route::get('/channels/{channel}/youtube/connect', [App\Http\Controllers\Admin\YoutubeAuthController::class, 'connect'])->name('channels.youtube.connect');
        Route::get('/channels/youtube/callback', [App\Http\Controllers\Admin\YoutubeAuthController::class, 'callback'])->name('channels.youtube.callback');

        // TikTok OAuth for channel creation
        Route::post('/channels/tiktok/oauth/start', [App\Http\Controllers\Admin\TikTokOAuthController::class, 'startOAuthForNewChannel'])->name('channels.tiktok.oauth.start');
        Route::get('/channels/tiktok/oauth/callback', [App\Http\Controllers\Admin\TikTokOAuthController::class, 'callbackForNewChannel'])->name('channels.tiktok.oauth.callback');
        Route::post('/channels/tiktok/get-channel-id', [App\Http\Controllers\Admin\TikTokOAuthController::class, 'getChannelId'])->name('channels.tiktok.get-channel-id');

        // Scheduled Posts Management
        Route::resource('scheduled-posts', App\Http\Controllers\Admin\ScheduledPostController::class);
        Route::patch('/scheduled-posts/{scheduledPost}/cancel', [App\Http\Controllers\Admin\ScheduledPostController::class, 'cancel'])->name('scheduled-posts.cancel');
        Route::patch('/scheduled-posts/{scheduledPost}/retry', [App\Http\Controllers\Admin\ScheduledPostController::class, 'retry'])->name('scheduled-posts.retry');
        Route::patch('/scheduled-posts/{scheduledPost}/post-now', [App\Http\Controllers\Admin\ScheduledPostController::class, 'postNow'])->name('scheduled-posts.post-now');
        Route::post('/scheduled-posts/bulk-action', [App\Http\Controllers\Admin\ScheduledPostController::class, 'bulkAction'])->name('scheduled-posts.bulk-action');
        Route::get('/scheduled-posts-stats', [App\Http\Controllers\Admin\ScheduledPostController::class, 'getDashboardStats'])->name('scheduled-posts.stats');

        // Universal Video Generator
        Route::get('/video-generator', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'index'])->name('video-generator.index');
        Route::get('/video-generator/status', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'status'])->name('video-generator.status');
        Route::post('/video-generator/generate', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'generate'])->name('video-generator.generate');
        Route::delete('/video-generator/delete', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'delete'])->name('video-generator.delete');
        Route::get('/video-generator/download/{platform}/{filename}', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'download'])->name('video-generator.download');

        // AJAX endpoints for video generator
        Route::post('/video-generator/calculate-duration', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'calculateDuration'])->name('video-generator.calculate-duration');

        // Video Management
        Route::get('/videos', [App\Http\Controllers\Admin\VideoManagementController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}', [App\Http\Controllers\Admin\VideoManagementController::class, 'show'])->name('videos.show');
        Route::get('/videos/{video}/edit', [App\Http\Controllers\Admin\VideoManagementController::class, 'edit'])->name('videos.edit');
        Route::put('/videos/{video}', [App\Http\Controllers\Admin\VideoManagementController::class, 'update'])->name('videos.update');
        Route::delete('/videos/{video}', [App\Http\Controllers\Admin\VideoManagementController::class, 'destroy'])->name('videos.destroy');
        Route::get('/videos/{video}/download', [App\Http\Controllers\Admin\VideoManagementController::class, 'download'])->name('videos.download');
        Route::get('/videos/{video}/preview', [App\Http\Controllers\Admin\VideoManagementController::class, 'preview'])->name('videos.preview');
        Route::post('/videos/bulk-action', [App\Http\Controllers\Admin\VideoManagementController::class, 'bulkAction'])->name('videos.bulk-action');
        Route::post('/video-generator/validate-media', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'validateMedia'])->name('video-generator.validate-media');
        Route::get('/video-generator/logo-library', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'getLogoLibrary'])->name('video-generator.logo-library');
        Route::post('/video-generator/generate-from-template', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'generateFromTemplate'])->name('video-generator.generate-from-template');
        Route::post('/video-generator/generate-batch-from-template', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'generateBatchFromTemplate'])->name('video-generator.generate-batch-from-template');

        // Video Publishing Management
        Route::prefix('video-publishing')->name('video-publishing.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\VideoPublishingController::class, 'index'])->name('index');
            Route::get('/scheduled', [App\Http\Controllers\Admin\VideoPublishingController::class, 'scheduled'])->name('scheduled');
            Route::post('/bulk-action', [App\Http\Controllers\Admin\VideoPublishingController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{videoPublishing}', [App\Http\Controllers\Admin\VideoPublishingController::class, 'show'])->name('show');
            Route::get('/{videoPublishing}/edit', [App\Http\Controllers\Admin\VideoPublishingController::class, 'edit'])->name('edit');
            Route::put('/{videoPublishing}', [App\Http\Controllers\Admin\VideoPublishingController::class, 'update'])->name('update');
            Route::post('/{videoPublishing}/publish', [App\Http\Controllers\Admin\VideoPublishingController::class, 'publish'])->name('publish');
            Route::post('/{videoPublishing}/cancel', [App\Http\Controllers\Admin\VideoPublishingController::class, 'cancel'])->name('cancel');
            Route::post('/{videoPublishing}/retry', [App\Http\Controllers\Admin\VideoPublishingController::class, 'retry'])->name('retry');
            Route::post('/{videoPublishing}/sync-status', [App\Http\Controllers\Admin\VideoPublishingController::class, 'syncStatus'])->name('sync-status');
        });

        // Video Template routes
        Route::resource('video-templates', App\Http\Controllers\Admin\VideoTemplateController::class)
              ->middleware(\App\Http\Middleware\LogRequests::class);
        Route::get('/video-templates/{videoTemplate}/use', [App\Http\Controllers\Admin\VideoTemplateController::class, 'use'])->name('video-templates.use');
        Route::post('/video-templates/{videoTemplate}/duplicate', [App\Http\Controllers\Admin\VideoTemplateController::class, 'duplicate'])->name('video-templates.duplicate');
        Route::post('/video-templates/generate-preview', [App\Http\Controllers\Admin\VideoTemplateController::class, 'generatePreview'])->name('video-templates.generate-preview');
        Route::post('/video-templates/save-layout', [App\Http\Controllers\Admin\VideoTemplateController::class, 'saveLayout'])->name('video-templates.save-layout');

        // Video Preview routes
        Route::post('/video-preview/upload', [App\Http\Controllers\Admin\VideoPreviewController::class, 'uploadFiles'])->name('video-preview.upload');
        Route::post('/video-preview/generate', [App\Http\Controllers\Admin\VideoPreviewController::class, 'generatePreview'])->name('video-preview.generate');
        Route::delete('/video-preview/delete', [App\Http\Controllers\Admin\VideoPreviewController::class, 'deletePreview'])->name('video-preview.delete');

        // Log Viewer routes
        Route::get('/logs', [App\Http\Controllers\Admin\LogViewerController::class, 'index'])->name('logs.index');
        Route::get('/logs/download', [App\Http\Controllers\Admin\LogViewerController::class, 'download'])->name('logs.download');
        Route::post('/logs/clear', [App\Http\Controllers\Admin\LogViewerController::class, 'clear'])->name('logs.clear');
        Route::get('/logs/ajax', [App\Http\Controllers\Admin\LogViewerController::class, 'ajax'])->name('logs.ajax');

        // Upload limits check route
        Route::get('/check-upload-limits', function() {
            $postMaxSize = ini_get('post_max_size');
            $uploadMaxFilesize = ini_get('upload_max_filesize');
            $maxFileUploads = ini_get('max_file_uploads');
            $memoryLimit = ini_get('memory_limit');
            $maxExecutionTime = ini_get('max_execution_time');

            // Parse sizes to bytes
            $parseSize = function($size) {
                $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
                $size = preg_replace('/[^0-9\.]/', '', $size);
                if ($unit) {
                    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
                }
                return round($size);
            };

            $postMaxSizeBytes = $parseSize($postMaxSize);
            $uploadMaxFilesizeBytes = $parseSize($uploadMaxFilesize);
            $memoryLimitBytes = $parseSize($memoryLimit);

            return response()->json([
                'limits' => [
                    'post_max_size' => $postMaxSize,
                    'post_max_size_bytes' => $postMaxSizeBytes,
                    'upload_max_filesize' => $uploadMaxFilesize,
                    'upload_max_filesize_bytes' => $uploadMaxFilesizeBytes,
                    'max_file_uploads' => (int)$maxFileUploads,
                    'memory_limit' => $memoryLimit,
                    'memory_limit_bytes' => $memoryLimitBytes,
                    'max_execution_time' => (int)$maxExecutionTime
                ],
                'recommendations' => [
                    'max_total_upload' => min($postMaxSizeBytes * 0.9, $memoryLimitBytes * 0.8),
                    'max_single_file' => $uploadMaxFilesizeBytes,
                    'max_files' => (int)$maxFileUploads
                ],
                'formatted' => [
                    'max_total_upload' => number_format(min($postMaxSizeBytes * 0.9, $memoryLimitBytes * 0.8) / 1024 / 1024, 1) . 'MB',
                    'max_single_file' => number_format($uploadMaxFilesizeBytes / 1024 / 1024, 1) . 'MB'
                ]
            ]);
        })->name('check-upload-limits');

        // Test audio library route
        Route::get('/test-audio-library', function() {
            $audioLibrary = \App\Models\AudioLibrary::take(5)->get();

            $results = [];
            foreach ($audioLibrary as $audio) {
                $results[] = [
                    'id' => $audio->id,
                    'title' => $audio->title,
                    'file_path' => $audio->file_path,
                    'file_exists' => $audio->fileExists(),
                    'full_path' => $audio->getFullPath(),
                    'usage_count' => $audio->getUsageCount(),
                    'last_used_at' => $audio->getLastUsedAt()
                ];
            }

            return response()->json([
                'message' => 'Audio library test completed',
                'total_audio_files' => \App\Models\AudioLibrary::count(),
                'sample_files' => $results
            ]);
        })->name('test-audio-library');

        // Test logging route
        Route::get('/test-logging', function() {
            $logger = new \App\Services\CustomLoggerService();

            $logger->logInfo('video-template', 'Test log entry created', [
                'test_data' => 'This is a test',
                'timestamp' => now(),
                'user' => auth()->user()->name ?? 'Guest'
            ]);

            $logger->logError('video-template', 'Test error log entry', [
                'error_type' => 'test_error',
                'test_data' => 'This is a test error'
            ], new \Exception('This is a test exception'));

            return response()->json([
                'message' => 'Test logs created successfully',
                'check_command' => 'php artisan logs:view video-template'
            ]);
        })->name('test-logging');

        // Audio Library routes (custom routes first to avoid conflicts)
        Route::post('/audio-library/store-multiple', [App\Http\Controllers\Admin\AudioLibraryController::class, 'storeMultiple'])
             ->name('audio-library.store-multiple')
             ->middleware('handle.large.uploads');
        Route::post('/audio-library/bulk-action', [App\Http\Controllers\Admin\AudioLibraryController::class, 'bulkAction'])->name('audio-library.bulk-action');
        Route::get('/audio-library/export', [App\Http\Controllers\Admin\AudioLibraryController::class, 'export'])->name('audio-library.export');
        Route::get('/audio-library/batch-list', [App\Http\Controllers\Admin\AudioLibraryController::class, 'batchList'])->name('audio-library.batch-list');
        Route::get('/audio-library/batch-status/{batch}', [App\Http\Controllers\Admin\AudioLibraryController::class, 'batchStatus'])->name('audio-library.batch-status');
        Route::get('/api/audio-library/batch-status/{batch}', [App\Http\Controllers\Admin\AudioLibraryController::class, 'getBatchStatus'])->name('audio-library.api-batch-status');
        Route::get('/audio-library/{audioLibrary}/download', [App\Http\Controllers\Admin\AudioLibraryController::class, 'download'])->name('audio-library.download');
        Route::get('/audio-library/{audioLibrary}/stream', [App\Http\Controllers\Admin\AudioLibraryController::class, 'stream'])->name('audio-library.stream');
        Route::post('/audio-library/import-story', [App\Http\Controllers\Admin\AudioLibraryController::class, 'importStoryAudios'])->name('audio-library.import-story');
        Route::get('/api/audio-library/for-video-generator', [App\Http\Controllers\Admin\AudioLibraryController::class, 'getForVideoGenerator'])->name('audio-library.for-video-generator');
        Route::get('/api/audio-library/random-background-music', [App\Http\Controllers\Admin\AudioLibraryController::class, 'getRandomBackgroundMusic'])->name('audio-library.random-background-music');
        Route::get('/api/audio-library/upload-limits', [App\Http\Controllers\Admin\AudioLibraryController::class, 'getUploadLimits'])->name('audio-library.upload-limits');

        // System Configuration routes
        Route::get('/system/upload-config', [App\Http\Controllers\Admin\SystemConfigController::class, 'uploadConfig'])->name('system.upload-config');
        Route::get('/system/upload-config-api', [App\Http\Controllers\Admin\SystemConfigController::class, 'getUploadConfig'])->name('system.upload-config-api');
        Route::get('/system/test-upload-limits', [App\Http\Controllers\Admin\SystemConfigController::class, 'testUploadLimits'])->name('system.test-upload-limits');
        Route::get('/system/generate-instructions', [App\Http\Controllers\Admin\SystemConfigController::class, 'generateInstructions'])->name('system.generate-instructions');

        // Audio Library resource routes (must be last)
        Route::resource('audio-library', App\Http\Controllers\Admin\AudioLibraryController::class);

        // Video Queue Management
        Route::get('/video-queue', [App\Http\Controllers\Admin\VideoQueueController::class, 'index'])->name('video-queue.index');
        Route::get('/video-queue/status', [App\Http\Controllers\Admin\VideoQueueController::class, 'status'])->name('video-queue.status');
        Route::get('/video-queue/worker-status', [App\Http\Controllers\Admin\VideoQueueController::class, 'getWorkerStatus'])->name('video-queue.worker-status');
        Route::get('/video-queue/{taskId}', [App\Http\Controllers\Admin\VideoQueueController::class, 'show'])->name('video-queue.show');
        Route::post('/video-queue/{taskId}/cancel', [App\Http\Controllers\Admin\VideoQueueController::class, 'cancel'])->name('video-queue.cancel');
        Route::post('/video-queue/{taskId}/retry', [App\Http\Controllers\Admin\VideoQueueController::class, 'retry'])->name('video-queue.retry');

        // Temporary route to create GeneratedVideo record
        Route::get('/create-generated-video/{filename}', function($filename) {
            try {
                $fullPath = storage_path('app/videos/' . $filename);

                if (!File::exists($fullPath)) {
                    return response()->json(['error' => 'Video file not found: ' . $fullPath], 404);
                }

                $video = new \App\Models\GeneratedVideo();
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

                return response()->json([
                    'success' => true,
                    'message' => 'GeneratedVideo created successfully',
                    'video_id' => $video->id,
                    'file_path' => $video->file_path,
                    'file_size' => number_format($video->file_size / 1024 / 1024, 2) . ' MB'
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->name('create-generated-video');
        Route::delete('/video-queue/{taskId}', [App\Http\Controllers\Admin\VideoQueueController::class, 'delete'])->name('video-queue.delete');
        Route::post('/video-queue/clear-completed', [App\Http\Controllers\Admin\VideoQueueController::class, 'clearCompleted'])->name('video-queue.clear-completed');

        // TikTok Video Generator (Disabled - Controller not found)
        // Route::get('/tiktok', [App\Http\Controllers\Admin\TiktokVideoController::class, 'index'])->name('tiktok.index');
        // Route::post('/tiktok/generate', [App\Http\Controllers\Admin\TiktokVideoController::class, 'generate'])->name('tiktok.generate');
        // Route::delete('/tiktok/delete', [App\Http\Controllers\Admin\TiktokVideoController::class, 'delete'])->name('tiktok.delete');
        // Route::get('/tiktok/download/{filename}', [App\Http\Controllers\Admin\TiktokVideoController::class, 'download'])->name('tiktok.download');
        // Route::get('/tiktok/status', [App\Http\Controllers\Admin\TiktokVideoController::class, 'status'])->name('tiktok.status');

        // User management (Admin only)
        Route::middleware(['admin'])->group(function () {
            Route::resource('users', UserController::class);
        });

        // Additional admin routes (placeholders for missing routes)
        Route::get('/roles', function() {
            return redirect()->route('admin.users.index')->with('info', 'Quản lý vai trò đang được phát triển');
        })->name('roles.index');

        // Settings Management
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/create', [SettingsController::class, 'create'])->name('settings.create');
        Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
        Route::get('/settings/{id}/edit', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{id}', [SettingsController::class, 'destroy'])->name('settings.destroy');
        Route::post('/settings/initialize', [SettingsController::class, 'initialize'])->name('settings.initialize');

        // Queue Workers Help
        Route::get('/help/queue-workers', [QueueHelpController::class, 'index'])->name('help.queue-workers');
        Route::get('/help/queue-status', [QueueHelpController::class, 'getQueueStatus'])->name('help.queue-status');
        Route::post('/help/queue-command', [QueueHelpController::class, 'executeCommand'])->name('help.queue-command');
        Route::post('/help/create-queue-tables', [QueueHelpController::class, 'createQueueTables'])->name('help.create-queue-tables');

        // TTS Monitor
        Route::get('/tts-monitor', [App\Http\Controllers\Admin\TtsMonitorController::class, 'index'])->name('tts-monitor.index');
        Route::get('/tts-monitor/status', [App\Http\Controllers\Admin\TtsMonitorController::class, 'status'])->name('tts-monitor.status');
        Route::post('/tts-monitor/add-story', [App\Http\Controllers\Admin\TtsMonitorController::class, 'addStory'])->name('tts-monitor.add-story');
        Route::post('/tts-monitor/cancel-job/{job}', [App\Http\Controllers\Admin\TtsMonitorController::class, 'cancelJob'])->name('tts-monitor.cancel-job');
        Route::post('/tts-monitor/clear-failed', [App\Http\Controllers\Admin\TtsMonitorController::class, 'clearFailed'])->name('tts-monitor.clear-failed');
        Route::get('/tts-monitor/job-details/{job}', [App\Http\Controllers\Admin\TtsMonitorController::class, 'jobDetails'])->name('tts-monitor.job-details');

        // Crawl Monitor
        Route::get('/crawl-monitor', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'index'])->name('crawl-monitor.index');
        Route::get('/crawl-monitor/status', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'status'])->name('crawl-monitor.status');
        Route::get('/crawl-monitor/queue-details', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'queueDetails'])->name('crawl-monitor.queue-details');
        Route::get('/crawl-monitor/add-story', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'addStory'])->name('crawl-monitor.add-story');
        Route::post('/crawl-monitor/recover', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'recover'])->name('crawl-monitor.recover');
        Route::post('/crawl-monitor/stop', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'stop'])->name('crawl-monitor.stop');
        Route::post('/crawl-monitor/prioritize-job', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'prioritizeJob'])->name('crawl-monitor.prioritize-job');
        Route::post('/crawl-monitor/delay-job', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'delayJob'])->name('crawl-monitor.delay-job');
        Route::post('/crawl-monitor/delete-job', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'deleteJob'])->name('crawl-monitor.delete-job');
        Route::post('/crawl-monitor/rebalance-queue', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'rebalanceQueue'])->name('crawl-monitor.rebalance-queue');
        Route::post('/crawl-monitor/update-story-status', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'updateStoryStatus'])->name('crawl-monitor.update-story-status');
        Route::post('/crawl-monitor/clear-queue', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'clearQueue'])->name('crawl-monitor.clear-queue');
        Route::post('/crawl-monitor/add-to-queue', [App\Http\Controllers\Admin\CrawlMonitorController::class, 'addToQueue'])->name('crawl-monitor.add-to-queue');

        Route::get('/help', [HelpController::class, 'index'])->name('help.index');
        Route::get('/help/{section}', [HelpController::class, 'show'])->name('help.show');

        Route::get('/help-quick-reference', function() {
            return view('admin.help.quick-reference');
        })->name('help.quick-reference');

        // Test tracking route
        Route::get('/test-tracking', function() {
            return view('test-tracking');
        })->name('test.tracking');



        // Story TTS Management
        Route::get('/stories/{story}/tts-info', [App\Http\Controllers\Admin\StoryTtsController::class, 'getStoryTtsInfo'])->name('stories.tts-info');
        Route::post('/stories/{story}/convert-tts', [App\Http\Controllers\Admin\StoryTtsController::class, 'convertStoryToTts'])->name('stories.convert-tts');
        Route::post('/stories/{story}/cancel-tts', [App\Http\Controllers\Admin\StoryTtsController::class, 'cancelStoryTts'])->name('stories.cancel-tts');

        // Legacy crawl routes
        Route::get('/crawl', [CrawlController::class, 'index'])->name('crawl.index');
        Route::post('/crawl/run/{story}', [CrawlController::class, 'run'])->name('crawl.run');
    });

    // Audio file serving route (outside admin prefix for direct access)
    Route::get('/audio/{path}', function ($path) {
        $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
        $fullPath = base_path($audioBasePath . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    })->where('path', '.*')->name('audio.serve');
});
