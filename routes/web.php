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

        // Story chapters management
        Route::get('/stories/{story}/chapters', [StoryController::class, 'chapters'])->name('stories.chapters');

        // Story crawling
        Route::get('/stories/{story}/crawl', [StoryController::class, 'showCrawlForm'])->name('stories.crawl.form');
        Route::post('/stories/{story}/crawl', [StoryController::class, 'crawl'])->name('stories.crawl');

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
        Route::get('/chapters/story/{story_id}', [ChapterController::class, 'index'])->name('chapters.index');

        // Chapter TTS routes
        Route::post('/chapters/{chapter}/tts', [ChapterController::class, 'convertToTts'])->name('chapters.tts');
        Route::post('/stories/{story}/chapters/tts-all', [ChapterController::class, 'convertAllToTts'])->name('chapters.tts.all');

        // Chapter content route
        Route::get('/chapters/{chapter}/content', [ChapterController::class, 'getContent'])->name('chapters.content');

        // Genres management
        Route::resource('genres', GenreController::class);

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

        // TikTok Video Generator
        Route::get('/tiktok', [App\Http\Controllers\Admin\TiktokVideoController::class, 'index'])->name('tiktok.index');
        Route::post('/tiktok/generate', [App\Http\Controllers\Admin\TiktokVideoController::class, 'generate'])->name('tiktok.generate');
        Route::delete('/tiktok/delete', [App\Http\Controllers\Admin\TiktokVideoController::class, 'delete'])->name('tiktok.delete');
        Route::get('/tiktok/download/{filename}', [App\Http\Controllers\Admin\TiktokVideoController::class, 'download'])->name('tiktok.download');
        Route::get('/tiktok/status', [App\Http\Controllers\Admin\TiktokVideoController::class, 'status'])->name('tiktok.status');

        // User management (Admin only)
        Route::middleware(['admin'])->group(function () {
            Route::resource('users', UserController::class);
        });

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
