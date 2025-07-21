<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Story;
use App\Models\Chapter;
use App\Helpers\ChapterHelper;
use App\Jobs\CrawlStoryJob;
use App\Services\CrawlSchedulingService;
use App\Traits\SortableTrait;
use App\Traits\HasToastMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    use SortableTrait, HasToastMessages;
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Story::query();

        // Handle search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Handle filter
        switch ($request->filter) {
            case 'visible':
                $query->visible();
                break;
            case 'hidden':
                $query->hidden();
                break;
            case 'inactive':
                $query->where('is_active', false);
                break;
            default:
                // Show all stories
                break;
        }

        // Apply sorting
        $allowedSorts = ['title', 'author', 'created_at', 'updated_at'];
        $query = $this->applySorting($query, $request, $allowedSorts, 'created_at', 'desc');

        $stories = $query->with('authorModel')->paginate(15);

        // Get counts for filter buttons
        $totalCount = Story::count();
        $visibleCount = Story::visible()->count();
        $hiddenCount = Story::hidden()->count();
        $inactiveCount = Story::where('is_active', false)->count();

        return view('admin.stories.index', compact(
            'stories',
            'totalCount',
            'visibleCount',
            'hiddenCount',
            'inactiveCount'
        ));
    }

    public function create()
    {
        $allGenres = Genre::all();
        return view('admin.stories.create', compact('allGenres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|unique:stories,slug',
            'author'        => 'nullable|string|max:255',
            'author_id'     => 'nullable|exists:authors,id',
            'source_url'    => 'nullable|url',
            'start_chapter' => 'required|integer|min:1',
            'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
            'default_tts_voice' => 'required|string',
            'default_tts_bitrate' => 'required|integer|in:64,128,192,256,320',
            'default_tts_speed' => 'required|numeric|in:0.5,1.0,1.5,2.0',
            'default_tts_volume' => 'required|numeric|in:1.0,1.5,2.0',
        ]);
        
        $textBasePath = config('constants.STORAGE_PATHS.TEXT');
        $folder_name = $request->slug;
        
        $story = new Story($request->except('folder_name'));
        $story->folder_name = $folder_name;
        $story->crawl_path = $textBasePath . $folder_name;

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = time() . '-' . $file->getClientOriginalName();
            $file->move(public_path('uploads/covers'), $filename);
            $story->cover_image = 'uploads/covers/' . $filename;
        }
        $story->crawl_status = $request->input('crawl_status', 0);
        $story->save();

        // Sync genres
        if ($request->has('genres')) {
            $story->genres()->sync($request->genres);
        }

        // Pre-create chapter files and storage directory
        $this->preCreateChapterFiles($story);

        // Auto crawl if enabled
        if ($request->input('auto_crawl') && $story->start_chapter && $story->end_chapter) {
            $this->addToCrawlQueue($story);
        }

        return $this->toastSuccess('🎉 Truyện đã được tạo thành công!', 'admin.stories.index');
    }

    public function show(Story $story)
    {
        $chapterCount = $story->chapters()->count();
        $latestChapters = $story->chapters()->orderBy('chapter_number', 'desc')->limit(5)->get();

        // Count video files
        $videoDir = storage_path('app/videos/' . $story->folder_name);
        $videoCount = 0;
        if (File::isDirectory($videoDir)) {
            $videoCount = count(File::glob($videoDir . '/*.mp4'));
        }

        // Count audio files
        $audioDir = storage_path('app/content/' . $story->folder_name . '/audio');
        $audioCount = 0;
        if (File::isDirectory($audioDir)) {
            $audioCount = count(File::glob($audioDir . '/*.mp3'));
        }

        return view('admin.stories.show', compact('story', 'chapterCount', 'latestChapters', 'videoCount', 'audioCount'));
    }

    public function edit(Story $story)
    {
        $allGenres = Genre::all();
        return view('admin.stories.edit', compact('story', 'allGenres'));
    }

    public function update(Request $request, Story $story)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|unique:stories,slug,' . $story->id,
            'author'        => 'nullable|string|max:255',
            'author_id'     => 'nullable|exists:authors,id',
            'source_url'    => 'nullable|url',
            'start_chapter' => 'required|integer|min:1',
            'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
            'default_tts_voice' => 'required|string',
            'default_tts_bitrate' => 'required|integer|in:64,128,192,256,320',
            'default_tts_speed' => 'required|numeric|in:0.5,1.0,1.5,2.0',
            'default_tts_volume' => 'required|numeric|in:1.0,1.5,2.0',
        ]);

        if ($request->hasFile('cover')) {
            // Delete old cover if exists
            if ($story->cover_image && file_exists(public_path($story->cover_image))) {
                unlink(public_path($story->cover_image));
            }
            
            $file = $request->file('cover');
            $filename = time() . '-' . $file->getClientOriginalName();
            $file->move(public_path('uploads/covers'), $filename);
            $story->cover_image = 'uploads/covers/' . $filename;
        }

        // Check if chapter range changed
        $oldStartChapter = $story->start_chapter;
        $oldEndChapter = $story->end_chapter;
        $newStartChapter = $request->start_chapter;
        $newEndChapter = $request->end_chapter;

        $story->update($request->except(['cover', 'genres']));

        // Sync genres
        if ($request->has('genres')) {
            $story->genres()->sync($request->genres);
        }

        // Pre-create new chapter files if range expanded
        if ($newStartChapter < $oldStartChapter || $newEndChapter > $oldEndChapter) {
            \Log::info("Chapter range expanded, pre-creating new files", [
                'story_id' => $story->id,
                'old_range' => "{$oldStartChapter}-{$oldEndChapter}",
                'new_range' => "{$newStartChapter}-{$newEndChapter}"
            ]);
            $this->preCreateChapterFiles($story);
        }

        return $this->toastSuccess('✅ Truyện đã được cập nhật thành công!', 'admin.stories.index');
    }

    public function destroy(Story $story)
    {
        // Delete cover image if exists
        if ($story->cover_image && file_exists(public_path($story->cover_image))) {
            unlink(public_path($story->cover_image));
        }

        $story->delete();
        return $this->toastSuccess('🗑️ Truyện đã được xóa thành công!', 'admin.stories.index');
    }

    // Chapter management
    public function chapters(Request $request, Story $story)
    {
        $query = $story->chapters()->with('video');

        // Search theo tiêu đề chapter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->get('sort', 'chapter_number');
        $sortDirection = $request->get('direction', 'asc');

        // Validate sort parameters
        $allowedSorts = ['chapter_number', 'title', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'chapter_number';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        $chapters = $query->orderBy($sortBy, $sortDirection)->paginate(20);

        return view('admin.stories.chapters', compact('story', 'chapters'));
    }

    // Crawl functionality
    public function showCrawlForm(Story $story)
    {
        return view('admin.stories.crawl', compact('story'));
    }

    public function crawl(Request $request, Story $story)
    {
        $request->validate([
            'start_chapter' => 'required|integer|min:1',
            'end_chapter' => 'required|integer|min:1|gte:start_chapter',
        ]);

        try {
            $startChapter = $request->start_chapter;
            $endChapter = $request->end_chapter;
            
            // Update story with new chapter range
            $story->update([
                'start_chapter' => $startChapter,
                'end_chapter' => $endChapter,
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED') // Reset to not crawled
            ]);

            // Option 1: Use Queue Job with smart scheduling (recommended for production)
            if (config('queue.default') !== 'sync') {
                // Check if there's already a crawl job running for this story
                if ($this->hasActiveCrawlJob($story)) {
                    Log::info("Crawl job already exists for story ID: {$story->id}, skipping dispatch");
                    return redirect()->route('admin.stories.index')
                        ->with('warning', 'Truyện này đã có job crawl đang chạy. Vui lòng đợi job hiện tại hoàn thành.');
                }

                // Calculate optimal delay to prevent server overload
                $optimalDelay = CrawlSchedulingService::calculateOptimalDelay($story);
                $estimatedDuration = CrawlSchedulingService::calculateCrawlDuration($story);

                if ($optimalDelay > 0) {
                    CrawlStoryJob::dispatch($story->id)
                                ->delay(now()->addSeconds($optimalDelay));

                    Log::info("Smart crawl job scheduled", [
                        'story_id' => $story->id,
                        'delay_seconds' => $optimalDelay,
                        'estimated_duration' => $estimatedDuration,
                        'scheduled_time' => now()->addSeconds($optimalDelay)->format('Y-m-d H:i:s')
                    ]);
                } else {
                    CrawlStoryJob::dispatch($story->id);
                    Log::info("Crawl job dispatched immediately for story ID: {$story->id}");
                }
            } else {
                // Option 2: Direct background process for development
                $command = sprintf(
                    'php %s crawl:stories --story_id=%d > %s 2>&1',
                    base_path('artisan'),
                    $story->id,
                    storage_path('logs/crawl_' . $story->id . '_' . time() . '.log')
                );

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows: Start background process
                    $cmd = 'start /B ' . $command;
                    pclose(popen($cmd, "r"));
                } else {
                    // Linux/Mac: Background process
                    exec($command . ' &');
                }

                Log::info("Background crawl process started for story ID: {$story->id}");
            }

            return $this->toastSuccess('🕷️ Crawl đã được khởi chạy thành công!', 'admin.stories.index');
        } catch (\Exception $e) {
            return $this->toastError('❌ Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // TTS functionality
    public function showTtsForm(Story $story)
    {
        $chapters = $story->chapters()->orderBy('chapter_number')->get();
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_phuthang_stor80dt_48k-fhg' => 'Anh Khôi (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)',
            'sg_female_tuongvy_call_44k-fhg' => 'Tường Vy (Nữ - Sài Gòn)'
        ];
        return view('admin.stories.tts', compact('story', 'chapters', 'voices'));
    }

    public function tts(Request $request, Story $story)
    {
        $request->validate([
            'conversion_type' => 'required|in:all,single,multiple,pending_only',
            'chapters' => 'required_if:conversion_type,single,multiple',
        ]);

        try {
            $conversionType = $request->conversion_type;
            $chapterNumbers = $request->chapters;

            if ($conversionType === 'all') {
                $chapters = $story->chapters;
            } elseif ($conversionType === 'pending_only') {
                $chapters = $story->chapters()->where(function($q) {
                    $q->where('audio_status', '!=', 'done')
                      ->orWhereNull('audio_status')
                      ->orWhere('audio_file_path', '')
                      ->orWhereNull('audio_file_path');
                })->get();
            } else {
                $parsedNumbers = ChapterHelper::parseChapterNumbers($chapterNumbers);
                $chapters = $story->chapters()->whereIn('chapter_number', $parsedNumbers)->get();
            }

            $processedCount = 0;
            $skippedCount = 0;

            foreach ($chapters as $chapter) {
                // Skip if already has audio
                if ($chapter->audio_file_path && file_exists(storage_path('app/' . $chapter->audio_file_path))) {
                    $skippedCount++;
                    continue;
                }

                // Process TTS
                $result = $this->processChapterTts($chapter);
                if ($result) {
                    $processedCount++;
                }
            }

            $message = "Đã xử lý {$processedCount} chương";
            if ($skippedCount > 0) {
                $message .= ", bỏ qua {$skippedCount} chương đã có audio";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    private function processChapterTts(Chapter $chapter)
    {
        // Implementation for TTS processing
        // This would call your TTS service
        return true; // Placeholder
    }

    // Scan chapters functionality
    public function showScanForm(Story $story)
    {
        return view('admin.stories.scan', compact('story'));
    }

    public function scanChapters(Request $request, Story $story)
    {
        $request->validate([
            'include_content' => 'boolean'
        ]);

        try {
            // Set unlimited execution time for large chapter scans
            set_time_limit(0);
            ini_set('memory_limit', '1G');

            $includeContent = $request->boolean('include_content', false);
            $startTime = microtime(true);

            // Use storage_path to get correct absolute path
            $storyPath = storage_path('app/content/' . $story->folder_name);

            if (!is_dir($storyPath)) {
                return back()->with('error', 'Thư mục truyện không tồn tại: ' . $storyPath);
            }

            $files = glob($storyPath . '/*.txt');
            $totalFiles = count($files);
            $scannedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            \Log::info("Starting chapter scan for story: {$story->title}, Total files: {$totalFiles}");

            // Process files in batches to prevent memory issues
            $batchSize = 100;
            $fileChunks = array_chunk($files, $batchSize);

            foreach ($fileChunks as $chunkIndex => $fileChunk) {
                \Log::info("Processing batch " . ($chunkIndex + 1) . "/" . count($fileChunks));

                foreach ($fileChunk as $file) {
                    try {
                        $filename = basename($file, '.txt');

                        // Extract chapter number from filename
                        if (preg_match('/(\d+)/', $filename, $matches)) {
                            $chapterNumber = (int)$matches[1];

                            // Check if chapter already exists
                            $existingChapter = $story->chapters()->where('chapter_number', $chapterNumber)->first();

                            if (!$existingChapter) {
                                // Calculate relative path for database storage
                                $relativePath = 'content/' . $story->folder_name . '/chuong-' . $chapterNumber . '.txt';

                                // Get file size safely
                                $fileSize = file_exists($file) ? filesize($file) : 0;

                                // Read content safely if requested
                                $content = '';
                                if ($includeContent && $fileSize > 0 && $fileSize < 10 * 1024 * 1024) { // Max 10MB per file
                                    $content = file_get_contents($file);
                                }

                                $chapterData = [
                                    'story_id' => $story->id,
                                    'chapter_number' => $chapterNumber,
                                    'title' => 'Chương ' . $chapterNumber,
                                    'file_path' => $relativePath,
                                    'file_size' => $fileSize,
                                    'is_crawled' => true,
                                    'crawled_at' => now(),
                                    'content' => $content,
                                ];

                                Chapter::create($chapterData);
                                $scannedCount++;

                                // Log progress every 50 chapters
                                if ($scannedCount % 50 === 0) {
                                    \Log::info("Scanned {$scannedCount} chapters so far...");
                                }
                            } else {
                                $skippedCount++;
                            }
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        \Log::error("Error processing file {$file}: " . $e->getMessage());
                        continue; // Continue with next file
                    }
                }

                // Clear memory after each batch
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            \Log::info("Chapter scan completed for story: {$story->title}", [
                'total_files' => $totalFiles,
                'scanned' => $scannedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount,
                'execution_time' => $executionTime . 's'
            ]);

            $message = "Quét hoàn tất! Thêm {$scannedCount} chương mới";
            if ($skippedCount > 0) {
                $message .= ", bỏ qua {$skippedCount} chương đã tồn tại";
            }
            if ($errorCount > 0) {
                $message .= ", {$errorCount} lỗi";
            }
            $message .= ". Thời gian: {$executionTime}s";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error("Chapter scan failed for story: {$story->title}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // Video generation functionality
    public function showVideoForm(Story $story)
    {
        // Kiểm tra ảnh nền
        $hasImage = $story->image && file_exists(public_path('images/stories/' . $story->image));

        // Kiểm tra file audio
        $audioDir = storage_path('app/truyen/mp3-' . $story->folder_name);
        $audioFiles = [];
        if (File::isDirectory($audioDir)) {
            $audioFiles = File::glob($audioDir . '/*.mp3');
            $audioFiles = array_map('basename', $audioFiles);
        }

        // Kiểm tra overlay videos
        $overlayDir = storage_path('app/video_assets');
        $overlayFiles = [];
        if (File::isDirectory($overlayDir)) {
            $overlayFiles = File::glob($overlayDir . '/*.mp4');
            $overlayFiles = array_map('basename', $overlayFiles);
        }

        // Kiểm tra video đã tạo
        $videoDir = storage_path('app/videos/' . $story->folder_name);
        $existingVideos = [];
        if (File::isDirectory($videoDir)) {
            $existingVideos = File::glob($videoDir . '/*.mp4');
            $existingVideos = array_map('basename', $existingVideos);
        }

        // Danh sách logo có sẵn
        $logos = $this->getAvailableLogos();

        return view('admin.stories.video', compact('story', 'hasImage', 'audioFiles', 'overlayFiles', 'existingVideos', 'logos'));
    }

    // Upload overlay video
    public function uploadOverlay(Request $request)
    {
        $request->validate([
            'overlay_video' => 'required|file|mimes:mp4,avi,mov,wmv|max:51200', // Max 50MB
        ]);

        try {
            $file = $request->file('overlay_video');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;

            // Lưu file vào thư mục video_assets
            $overlayDir = storage_path('app/video_assets');
            if (!File::isDirectory($overlayDir)) {
                File::makeDirectory($overlayDir, 0755, true);
            }

            $file->move($overlayDir, $fileName);

            return response()->json([
                'success' => true,
                'message' => "Upload thành công: {$originalName}",
                'filename' => $fileName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi upload: ' . $e->getMessage()
            ], 500);
        }
    }

    // Xóa overlay video
    public function deleteOverlay(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $filePath = storage_path('app/video_assets/' . $request->filename);

            if (File::exists($filePath)) {
                File::delete($filePath);
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa file: {$request->filename}"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File không tồn tại'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xóa file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateVideo(Request $request, Story $story)
    {
        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'overlay_video' => 'nullable|file|mimes:mp4,avi,mov|max:102400', // 100MB max
        ]);

        try {
            $chapter = Chapter::findOrFail($request->chapter_id);
            
            // Handle overlay video upload if provided
            $overlayPath = null;
            if ($request->hasFile('overlay_video')) {
                $overlayFile = $request->file('overlay_video');
                $overlayFilename = time() . '_overlay_' . $overlayFile->getClientOriginalName();
                $overlayPath = $overlayFile->storeAs('videos/overlays', $overlayFilename);
            }

            // Generate video using the existing logic
            $result = $this->processVideoGeneration($story, $chapter, $overlayPath);

            if ($result['success']) {
                return back()->with('success', 'Video đã được tạo thành công: ' . $result['filename']);
            } else {
                return back()->with('error', 'Có lỗi xảy ra khi tạo video: ' . $result['error']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    private function processVideoGeneration($story, $chapter, $overlayPath = null)
    {
        // Implementation for video generation
        // This would call your video generation service
        return ['success' => true, 'filename' => 'test.mp4']; // Placeholder
    }

    /**
     * Create storage directory for a story
     * This prevents permission issues when Node.js script tries to create files
     */
    private function preCreateChapterFiles(Story $story)
    {
        try {
            // Create storage directory only
            $storageDir = storage_path('app/content/' . $story->folder_name);

            if (!is_dir($storageDir)) {
                if (mkdir($storageDir, 0755, true)) {
                    \Log::info("Created storage directory for story: {$story->title}", [
                        'story_id' => $story->id,
                        'directory' => $storageDir,
                        'permissions' => '0755'
                    ]);
                    return true;
                } else {
                    \Log::error("Failed to create storage directory for story: {$story->title}", [
                        'story_id' => $story->id,
                        'directory' => $storageDir
                    ]);
                    return false;
                }
            } else {
                \Log::info("Storage directory already exists for story: {$story->title}", [
                    'story_id' => $story->id,
                    'directory' => $storageDir
                ]);
                return true;
            }

        } catch (\Exception $e) {
            \Log::error("Error creating storage directory for story: {$story->title}", [
                'story_id' => $story->id,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Lấy danh sách logo có sẵn
     */
    private function getAvailableLogos()
    {
        $logoDir = storage_path('app/logos');
        $logos = [];

        if (File::isDirectory($logoDir)) {
            $logoFiles = File::glob($logoDir . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);

            foreach ($logoFiles as $logoPath) {
                $logos[] = [
                    'name' => basename($logoPath),
                    'display_name' => pathinfo(basename($logoPath), PATHINFO_FILENAME),
                    'url' => route('admin.logo.serve', basename($logoPath)),
                    'path' => $logoPath
                ];
            }
        }

        return $logos;
    }

    /**
     * Get real-time status for multiple stories
     */
    public function getStatus(Request $request)
    {
        $request->validate([
            'story_ids' => 'required|array',
            'story_ids.*' => 'integer|exists:stories,id'
        ]);

        $stories = Story::whereIn('id', $request->story_ids)
            ->withCount('chapters')
            ->get();

        $statusLabels = config('constants.CRAWL_STATUS.LABELS');
        $statusColors = config('constants.CRAWL_STATUS.COLORS');

        $result = $stories->map(function ($story) use ($statusLabels, $statusColors) {
            return [
                'id' => $story->id,
                'slug' => $story->slug, // Add slug for URL generation
                'crawl_status' => $story->crawl_status,
                'status_label' => $statusLabels[$story->crawl_status] ?? 'Unknown',
                'status_color' => $statusColors[$story->crawl_status] ?? 'secondary',
                'chapter_count' => $story->chapters_count,
                'start_chapter' => $story->start_chapter,
                'end_chapter' => $story->end_chapter,
            ];
        });

        // Get pending jobs count for each story
        $pendingJobs = [];
        $jobs = \DB::table('jobs')->get();

        foreach ($request->story_ids as $storyId) {
            $count = 0;
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);

                // Check CrawlStoryJob
                if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
                    $jobData = unserialize($payload['data']['command']);
                    if (isset($jobData->storyId) && $jobData->storyId == $storyId) {
                        $count++;
                    }
                }

                // Check crawl:stories command
                if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'Illuminate\\Queue\\CallQueuedHandler') {
                    $command = unserialize($payload['data']['command']);
                    if (isset($command->data['story_id']) && $command->data['story_id'] == $storyId) {
                        $count++;
                    }
                }
            }
            $pendingJobs[$storyId] = $count;
        }

        return response()->json([
            'success' => true,
            'stories' => $result,
            'pending_jobs' => $pendingJobs
        ]);
    }

    /**
     * Cancel crawl job for a story
     */
    public function cancelCrawl(Story $story)
    {
        try {
            // Check if story is currently being crawled
            if ($story->crawl_status != config('constants.CRAWL_STATUS.VALUES.CRAWLING')) {
                return back()->with('error', 'Truyện không đang trong quá trình crawl.');
            }

            // Check if job ID exists
            if (!$story->crawl_job_id) {
                return back()->with('error', 'Không tìm thấy job ID để hủy.');
            }

            // Find and delete the job from queue
            $job = \DB::table('jobs')->where('id', $story->crawl_job_id)->first();

            if ($job) {
                // Delete job from queue
                \DB::table('jobs')->where('id', $story->crawl_job_id)->delete();
                Log::info("Deleted crawl job {$story->crawl_job_id} for story {$story->id}");
            }

            // Update story status
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                'crawl_job_id' => null
            ]);

            Log::info("Cancelled crawl for story ID: {$story->id}");

            return back()->with('success', 'Đã hủy tác vụ crawl thành công!');

        } catch (\Exception $e) {
            Log::error("Failed to cancel crawl for story {$story->id}: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi hủy crawl: ' . $e->getMessage());
        }
    }

    /**
     * Update story status based on actual progress
     */
    public function updateStatus(Story $story)
    {
        try {
            // First try auto-fix for completed stories
            $autoFixed = $story->autoFixCrawlStatus();

            if ($autoFixed) {
                Log::info("Story status auto-fixed to CRAWLED", [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                    'user_id' => auth()->id()
                ]);

                return redirect()->route('admin.stories.index')
                    ->with('success', "✅ Trạng thái đã được cập nhật thành 'Đã crawl' cho truyện '{$story->title}'!");
            }

            // If not auto-fixed, use command
            $exitCode = Artisan::call('stories:update-status', [
                '--story' => $story->id
            ]);

            $output = Artisan::output();

            // Check if status was updated
            if (strpos($output, 'Updated status to:') !== false) {
                Log::info("Story status manually updated", [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                    'user_id' => auth()->id()
                ]);

                return redirect()->route('admin.stories.index')
                    ->with('success', "✅ Đã cập nhật trạng thái cho truyện '{$story->title}'!");
            } else {
                return redirect()->route('admin.stories.index')
                    ->with('info', "ℹ️ Trạng thái của truyện '{$story->title}' đã chính xác!");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update story status", [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', "❌ Lỗi khi cập nhật trạng thái: {$e->getMessage()}");
        }
    }

    /**
     * Toggle public status of a story
     */
    public function togglePublic(Story $story)
    {
        try {
            $oldStatus = $story->is_public;
            $newStatus = !$oldStatus;

            $story->update(['is_public' => $newStatus]);

            $statusText = $newStatus ? 'Public (hiển thị trên website)' : 'Private (ẩn khỏi website)';

            Log::info("Story public status toggled", [
                'story_id' => $story->id,
                'story_title' => $story->title,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('success', "✅ Đã chuyển truyện '{$story->title}' thành {$statusText}!");

        } catch (\Exception $e) {
            Log::error("Failed to toggle story public status", [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', "❌ Lỗi khi thay đổi trạng thái: {$e->getMessage()}");
        }
    }

    /**
     * Smart crawl - only crawl missing chapters
     */
    public function smartCrawl(Request $request, Story $story)
    {
        // Handle GET request - show confirmation page or redirect
        if ($request->isMethod('GET')) {
            return $this->showSmartCrawlInfo($story);
        }

        try {
            // Check if story is already being crawled
            if ($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.CRAWLING')) {
                return back()->with('error', 'Truyện đang được crawl. Vui lòng đợi hoặc hủy crawl hiện tại.');
            }

            // Get existing chapters
            $existingChapters = $story->chapters()->pluck('chapter_number')->toArray();

            // Calculate missing chapters
            $allChapters = range($story->start_chapter, $story->end_chapter);
            $missingChapters = array_diff($allChapters, $existingChapters);

            if (empty($missingChapters)) {
                // All chapters exist, update status to CRAWLED if not already
                if ($story->crawl_status != config('constants.CRAWL_STATUS.VALUES.CRAWLED')) {
                    $story->update([
                        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                        'crawl_job_id' => null
                    ]);

                    Log::info("Story status updated to CRAWLED (all chapters exist)", [
                        'story_id' => $story->id,
                        'title' => $story->title,
                        'total_chapters' => count($allChapters),
                        'existing_chapters' => count($existingChapters)
                    ]);

                    return back()->with('success', 'Tất cả các chương đã được crawl. Trạng thái đã được cập nhật thành "Đã crawl".');
                }

                return back()->with('info', 'Tất cả các chương đã được crawl. Không có chương nào thiếu.');
            }

            // Update story status and dispatch job
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED')
            ]);

            // Dispatch smart crawl job
            if (config('queue.default') !== 'sync') {
                // Check if there's already a crawl job running for this story
                if ($this->hasActiveCrawlJob($story)) {
                    Log::info("Smart crawl job already exists for story ID: {$story->id}, skipping dispatch");
                    return redirect()->route('admin.stories.index')
                        ->with('warning', 'Truyện này đã có job crawl đang chạy. Vui lòng đợi job hiện tại hoàn thành.');
                }

                // Calculate optimal delay for smart crawl
                $optimalDelay = CrawlSchedulingService::calculateOptimalDelay($story);
                $estimatedDuration = CrawlSchedulingService::calculateCrawlDuration($story);

                if ($optimalDelay > 0) {
                    CrawlStoryJob::dispatch($story->id)
                                ->delay(now()->addSeconds($optimalDelay));

                    Log::info("Smart re-crawl job scheduled", [
                        'story_id' => $story->id,
                        'delay_seconds' => $optimalDelay,
                        'estimated_duration' => $estimatedDuration,
                        'scheduled_time' => now()->addSeconds($optimalDelay)->format('Y-m-d H:i:s')
                    ]);
                } else {
                    CrawlStoryJob::dispatch($story->id);
                    Log::info("Smart re-crawl job dispatched immediately for story ID: {$story->id}");
                }
            } else {
                // Fallback: Background process
                $command = sprintf(
                    'php %s crawl:stories --story_id=%d > %s 2>&1',
                    base_path('artisan'),
                    $story->id,
                    storage_path('logs/crawl_' . $story->id . '_' . time() . '.log')
                );

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $cmd = 'start /B ' . $command;
                    pclose(popen($cmd, "r"));
                } else {
                    exec($command . ' &');
                }

                Log::info("Background smart crawl process started for story ID: {$story->id}");
            }

            $missingCount = count($missingChapters);
            return back()->with('success', "Đã khởi chạy crawl cho {$missingCount} chương thiếu!");

        } catch (\Exception $e) {
            Log::error("Failed to start smart crawl for story {$story->id}: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi khởi chạy crawl: ' . $e->getMessage());
        }
    }

    /**
     * Remove story from crawl queue
     */
    public function removeFromQueue(Story $story)
    {
        try {
            // Find and delete all jobs for this story
            $deletedJobs = 0;

            // Delete by job ID if available
            if ($story->crawl_job_id) {
                $deleted = \DB::table('jobs')->where('id', $story->crawl_job_id)->delete();
                $deletedJobs += $deleted;
            }

            // Also search for jobs by story ID in payload
            $jobs = \DB::table('jobs')->get();
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);

                // Check CrawlStoryJob
                if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
                    $jobData = unserialize($payload['data']['command']);
                    if (isset($jobData->storyId) && $jobData->storyId == $story->id) {
                        \DB::table('jobs')->where('id', $job->id)->delete();
                        $deletedJobs++;
                    }
                }

                // Check crawl:stories command
                if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'Illuminate\\Queue\\CallQueuedHandler') {
                    $command = unserialize($payload['data']['command']);
                    if (isset($command->data['story_id']) && $command->data['story_id'] == $story->id) {
                        \DB::table('jobs')->where('id', $job->id)->delete();
                        $deletedJobs++;
                    }
                }
            }

            // Update story status
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                'crawl_job_id' => null
            ]);

            Log::info("Removed {$deletedJobs} crawl jobs for story ID: {$story->id}");

            if ($deletedJobs > 0) {
                return back()->with('success', "Đã xóa {$deletedJobs} job(s) khỏi queue!");
            } else {
                return back()->with('info', 'Không tìm thấy job nào trong queue cho truyện này.');
            }

        } catch (\Exception $e) {
            Log::error("Failed to remove jobs from queue for story {$story->id}: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa khỏi queue: ' . $e->getMessage());
        }
    }

    /**
     * Show smart crawl information page
     */
    private function showSmartCrawlInfo(Story $story)
    {
        // Get existing chapters
        $existingChapters = $story->chapters()->pluck('chapter_number')->toArray();

        // Calculate missing chapters
        $allChapters = range($story->start_chapter, $story->end_chapter);
        $missingChapters = array_diff($allChapters, $existingChapters);

        // Get pending jobs count
        $pendingJobs = 0;
        $jobs = \DB::table('jobs')->get();

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);

            // Check CrawlStoryJob
            if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
                try {
                    $jobData = unserialize($payload['data']['command']);
                    if (isset($jobData->storyId) && $jobData->storyId == $story->id) {
                        $pendingJobs++;
                    }
                } catch (\Exception $e) {
                    // Skip if can't unserialize
                }
            }
        }

        $data = [
            'story' => $story,
            'existing_chapters' => $existingChapters,
            'missing_chapters' => $missingChapters,
            'total_chapters' => count($allChapters),
            'existing_count' => count($existingChapters),
            'missing_count' => count($missingChapters),
            'pending_jobs' => $pendingJobs,
            'status_label' => config('constants.CRAWL_STATUS.LABELS')[$story->crawl_status] ?? 'Unknown'
        ];

        return view('admin.stories.smart-crawl', $data);
    }

    /**
     * Check if story has an active crawl job
     */
    private function hasActiveCrawlJob(Story $story): bool
    {
        // Check if story is currently crawling
        if ($story->crawl_status === config('constants.CRAWL_STATUS.VALUES.CRAWLING')) {
            return true;
        }

        // Check if there are pending crawl jobs for this story in the queue
        $pendingJobs = DB::table('jobs')
            ->where('queue', 'crawl')
            ->get();

        foreach ($pendingJobs as $job) {
            $payload = json_decode($job->payload, true);
            $storyId = $payload['data']['storyId'] ?? null;

            if ($storyId == $story->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Search stories for AJAX requests
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $stories = Story::where('title', 'like', "%{$query}%")
            ->orWhere('author', 'like', "%{$query}%")
            ->withCount([
                'chapters',
                'chapters as audio_chapters_count' => function($q) {
                    $q->whereNotNull('audio_file_path');
                }
            ])
            ->orderBy('title', 'asc')
            ->limit(20)
            ->get([
                'id', 'title', 'author', 'slug',
                'default_tts_voice', 'default_tts_bitrate',
                'default_tts_speed', 'default_tts_volume'
            ]);

        return response()->json($stories);
    }

    /**
     * Add story to crawl queue automatically
     */
    private function addToCrawlQueue(Story $story)
    {
        try {
            // Check if there's already a crawl job running for this story
            if ($this->hasActiveCrawlJob($story)) {
                Log::info("Auto crawl job already exists for story ID: {$story->id}, skipping dispatch");
                return;
            }

            // Calculate optimal delay to prevent server overload
            $optimalDelay = CrawlSchedulingService::calculateOptimalDelay($story);
            $estimatedDuration = CrawlSchedulingService::calculateCrawlDuration($story);

            if ($optimalDelay > 0) {
                CrawlStoryJob::dispatch($story->id)
                            ->delay(now()->addSeconds($optimalDelay));

                Log::info("Auto crawl job scheduled for new story", [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                    'delay_seconds' => $optimalDelay,
                    'estimated_duration' => $estimatedDuration,
                    'scheduled_time' => now()->addSeconds($optimalDelay)->format('Y-m-d H:i:s')
                ]);
            } else {
                CrawlStoryJob::dispatch($story->id);
                Log::info("Auto crawl job dispatched immediately for new story", [
                    'story_id' => $story->id,
                    'story_title' => $story->title
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to add story to crawl queue", [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
