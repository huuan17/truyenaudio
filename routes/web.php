<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\CrawlController;

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

// Dashboard
Route::resource('/', DashboardController::class);

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

// Audio file serving route
Route::get('/audio/{path}', function ($path) {
    $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
    $fullPath = base_path($audioBasePath . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// Genres management
Route::resource('genres', GenreController::class);

// Legacy crawl routes
Route::get('/crawl', [CrawlController::class, 'index'])->name('crawl.index');
Route::post('/crawl/run/{story}', [CrawlController::class, 'run'])->name('crawl.run');
