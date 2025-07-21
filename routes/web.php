<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\CrawlController;
use App\Http\Controllers\TextToSpeechController;
use App\Http\Controllers\Admin\UserController;
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
        Route::post('/stories/{story}/smart-crawl', [StoryController::class, 'smartCrawl'])->name('stories.smart-crawl');

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
        Route::post('/video-generator/generate-batch', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'generateBatch'])->name('video-generator.generate-batch');
        Route::delete('/video-generator/delete', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'delete'])->name('video-generator.delete');
        Route::get('/video-generator/download/{platform}/{filename}', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'download'])->name('video-generator.download');

        // AJAX endpoints for video generator
        Route::post('/video-generator/calculate-duration', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'calculateDuration'])->name('video-generator.calculate-duration');
        Route::post('/video-generator/validate-media', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'validateMedia'])->name('video-generator.validate-media');
        Route::get('/video-generator/logo-library', [App\Http\Controllers\Admin\VideoGeneratorController::class, 'getLogoLibrary'])->name('video-generator.logo-library');

        // Video Queue Management
        Route::get('/video-queue', [App\Http\Controllers\Admin\VideoQueueController::class, 'index'])->name('video-queue.index');
        Route::get('/video-queue/status', [App\Http\Controllers\Admin\VideoQueueController::class, 'status'])->name('video-queue.status');
        Route::post('/video-queue/{taskId}/cancel', [App\Http\Controllers\Admin\VideoQueueController::class, 'cancel'])->name('video-queue.cancel');
        Route::post('/video-queue/{taskId}/retry', [App\Http\Controllers\Admin\VideoQueueController::class, 'retry'])->name('video-queue.retry');
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

        Route::get('/settings', function() {
            return view('admin.settings.index');
        })->name('settings.index');

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

        Route::get('/help', function() {
            return view('admin.help.index');
        })->name('help.index');

        Route::get('/help/{slug}', function($slug) {
            return view('admin.help.show', compact('slug'));
        })->name('help.show');

        Route::get('/help-quick-reference', function() {
            return view('admin.help.quick-reference');
        })->name('help.quick-reference');



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
