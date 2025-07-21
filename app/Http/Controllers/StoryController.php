<?php

namespace App\Http\Controllers;
use App\Models\Genre;
use App\Models\Story;
use App\Models\Chapter;
use App\Helpers\ChapterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class StoryController extends Controller
{
    public function index()
    {
        $stories = Story::latest()->paginate(10);
        return view('stories.index', compact('stories'));
    }

    public function create()
    {
        $allGenres = Genre::all();
        return view('stories.create', compact('allGenres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|unique:stories,slug',
            'author'        => 'nullable|string|max:255',
            'source_url'    => 'nullable|url',
            'start_chapter' => 'required|integer|min:1',
            'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
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
        $story->genres()->sync($request->genres ?? []);

        // Pre-create chapter files and storage directory
        $this->preCreateChapterFiles($story);

        return redirect()->route('stories.index')->with('success', '✅ Thêm truyện thành công!');
    }

    public function edit(Story $story)
    {
        $allGenres = Genre::all();
        return view('stories.create', compact('story', 'allGenres'));
    }

    public function update(Request $request, Story $story)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|unique:stories,slug,' . $story->id,
            'source_url'    => 'nullable|url',
            'start_chapter' => 'required|integer|min:1',
            'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
        ]);
        
        $textBasePath = config('constants.STORAGE_PATHS.TEXT');
        $folder_name = $request->slug;
        
        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = time() . '-' . $file->getClientOriginalName();
            $file->move(public_path('uploads/covers'), $filename);
            $story->cover_image = 'uploads/covers/' . $filename;
        }

        $story->update($request->except('folder_name'));
        $story->folder_name = $folder_name;
        $story->crawl_path = $textBasePath . $folder_name;
        $story->save();
        
        $story->genres()->sync($request->genres ?? []);

        return redirect()->route('stories.index')->with('success', '✅ Cập nhật truyện thành công!');
    }

    public function destroy(Story $story)
    {
        $story->delete();
        return redirect()->route('stories.index')->with('success', '🗑️ Đã xóa mềm truyện!');
    }

    // Hiển thị danh sách chapter của truyện
    public function chapters(Request $request, Story $story)
    {
        $query = Chapter::where('story_id', $story->id)->with('video');

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

        return view('stories.chapters', compact('story', 'chapters'));
    }

    /**
     * Đếm số lượng chapters theo loại content
     */
    private function getContentTypeCounts($storyId)
    {
        $totalChapters = Chapter::where('story_id', $storyId)->count();

        // Chapters có text content
        $textChapters = Chapter::where('story_id', $storyId)
            ->where(function($q) {
                $q->whereNotNull('content')
                  ->where('content', '!=', '')
                  ->orWhere(function($subQ) {
                      $subQ->whereNotNull('file_path')
                           ->where('file_path', '!=', '');
                  });
            })->count();

        // Chapters có audio
        $audioChapters = Chapter::where('story_id', $storyId)
            ->where('audio_status', 'done')
            ->whereNotNull('audio_file_path')
            ->where('audio_file_path', '!=', '')
            ->count();

        // Chapters có video
        $videoChapters = Chapter::where('story_id', $storyId)
            ->whereHas('video', function($q) {
                $q->where('render_status', 'done')
                  ->whereNotNull('file_path')
                  ->where('file_path', '!=', '');
            })->count();

        // Chapters không có content
        $noContentChapters = Chapter::where('story_id', $storyId)
            ->where(function($q) {
                $q->where('content', '')
                  ->orWhereNull('content');
            })->where(function($q) {
                $q->where('file_path', '')
                  ->orWhereNull('file_path');
            })->count();

        return [
            'all' => $totalChapters,
            'text' => $textChapters,
            'audio' => $audioChapters,
            'video' => $videoChapters,
            'no_content' => $noContentChapters,
        ];
    }
    
    // Hiển thị form crawl truyện
    public function showCrawlForm(Story $story)
    {
        return view('stories.crawl', compact('story'));
    }
    
    // Thực hiện crawl truyện
    public function crawl(Request $request, Story $story)
    {
        $request->validate([
            'start_chapter' => 'required|integer|min:1',
            'end_chapter' => 'required|integer|min:1|gte:start_chapter',
        ]);
        
        // Cập nhật thông tin chapter range nếu có thay đổi
        if ($story->start_chapter != $request->start_chapter || 
            $story->end_chapter != $request->end_chapter) {
            
            $story->start_chapter = $request->start_chapter;
            $story->end_chapter = $request->end_chapter;
            $story->save();
        }
        
        // Chạy command crawl
        Artisan::queue('crawl:stories', [
            '--story_id' => $story->id
        ]);
        
        return redirect()->route('stories.show', $story)
            ->with('success', "Đã bắt đầu crawl truyện '{$story->title}' từ chương {$request->start_chapter} đến {$request->end_chapter}.");
    }
    
    // Hiển thị form text-to-speech
    public function showTtsForm(Story $story)
    {
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_phuthang_stor80dt_48k-fhg' => 'Anh Khôi (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)',
            'sg_female_tuongvy_call_44k-fhg' => 'Tường Vy (Nữ - Sài Gòn)'
        ];
        
        return view('stories.tts', compact('story', 'voices'));
    }
    
    // Thực hiện text-to-speech
    public function tts(Request $request, Story $story)
    {
        $request->validate([
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
            'conversion_type' => 'required|in:all,single,multiple,pending_only',
            'chapters' => 'required_if:conversion_type,single,multiple|nullable|string',
        ]);

        $conversionType = $request->conversion_type;
        $chaptersToProcess = [];

        // Xử lý theo loại chuyển đổi
        switch ($conversionType) {
            case 'all':
                $chaptersToProcess = $story->chapters()->pluck('chapter_number')->toArray();
                break;

            case 'pending_only':
                $chaptersToProcess = $story->chapters()
                    ->where(function($q) {
                        $q->where('audio_status', '!=', 'done')
                          ->orWhereNull('audio_status')
                          ->orWhere('audio_file_path', '')
                          ->orWhereNull('audio_file_path');
                    })
                    ->pluck('chapter_number')->toArray();
                break;

            case 'single':
            case 'multiple':
                $chaptersInput = trim($request->chapters);
                $chaptersToProcess = ChapterHelper::parseChapterNumbers($chaptersInput);
                break;
        }

        if (empty($chaptersToProcess)) {
            return redirect()->back()->with('error', 'Không có chapter nào để chuyển đổi.');
        }

        // Lọc bỏ các chapters đã có audio (trừ khi là 'all')
        if ($conversionType !== 'all') {
            $existingAudioChapters = $story->chapters()
                ->where('audio_status', 'done')
                ->whereNotNull('audio_file_path')
                ->where('audio_file_path', '!=', '')
                ->pluck('chapter_number')->toArray();

            $chaptersToProcess = array_diff($chaptersToProcess, $existingAudioChapters);
        }

        if (empty($chaptersToProcess)) {
            return redirect()->back()->with('error', 'Tất cả chapters đã có audio. Không có gì để chuyển đổi.');
        }

        // Chạy TTS cho từng chapter
        $successCount = 0;
        foreach ($chaptersToProcess as $chapterNumber) {
            $chapter = $story->chapters()->where('chapter_number', $chapterNumber)->first();
            if ($chapter && $chapter->canConvertToTts()) {
                try {
                    \App\Jobs\ProcessChapterTtsJob::dispatch($chapter->id, $request->voice, $request->bitrate, $request->speed, 1.0);
                    $successCount++;
                } catch (\Exception $e) {
                    \Log::error("Failed to queue TTS for chapter {$chapterNumber}: " . $e->getMessage());
                }
            }
        }

        $message = "Đã bắt đầu chuyển đổi {$successCount} chapter(s) của truyện '{$story->title}' thành audio.";

        if ($successCount < count($chaptersToProcess)) {
            $skippedCount = count($chaptersToProcess) - $successCount;
            $message .= " ({$skippedCount} chapter(s) đã bỏ qua do không thể chuyển đổi)";
        }

        return redirect()->route('stories.show', $story)->with('success', $message);
    }



    // Hiển thị form quét chapter
    public function showScanForm(Story $story)
    {
        return view('stories.scan', compact('story'));
    }

    // Thực hiện quét chapter
    public function scanChapters(Request $request, Story $story)
    {
        $request->validate([
            'force' => 'nullable|boolean',
            'with_content' => 'nullable|boolean',
        ]);

        // Chuẩn bị tham số cho command
        $params = [
            'story_id' => $story->id,
        ];

        if ($request->filled('force') && $request->force) {
            $params['--force'] = true;
        }

        if ($request->filled('with_content') && $request->with_content) {
            $params['--with-content'] = true;
        }

        // Chạy command
        Artisan::queue('chapters:scan', $params);

        $forceMessage = $request->filled('force') && $request->force ? "quét lại tất cả" : "quét chapter mới";
        $contentMessage = $request->filled('with_content') && $request->with_content ? " (bao gồm nội dung)" : " (chỉ thông tin file)";

        $message = "Đã bắt đầu {$forceMessage} chapter của truyện '{$story->title}' từ storage{$contentMessage}.";

        return redirect()->route('stories.show', $story)
            ->with('success', $message);
    }

    // Hiển thị form tạo video
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

        return view('stories.video', compact('story', 'hasImage', 'audioFiles', 'overlayFiles', 'existingVideos'));
    }

    // Tạo video
    public function generateVideo(Request $request, Story $story)
    {
        $request->validate([
            'chapter_number' => 'nullable|integer|min:1',
            'overlay_file' => 'nullable|string',
            'output_name' => 'nullable|string|max:255',
        ]);

        // Chuẩn bị tham số cho command
        $params = [
            'story_id' => $story->id,
        ];

        if ($request->filled('chapter_number')) {
            $params['--chapter'] = $request->chapter_number;
        }

        if ($request->filled('overlay_file')) {
            $overlayPath = storage_path('app/videos/assets/' . $request->overlay_file);
            $params['--overlay'] = $overlayPath;
        }

        if ($request->filled('output_name')) {
            $params['--output'] = $request->output_name;
        }

        // Chạy command story video generation
        Artisan::queue('story:video:generate', $params);

        $chapterText = $request->filled('chapter_number') ? " chương {$request->chapter_number}" : "";
        $message = "Đã bắt đầu tạo video{$chapterText} cho truyện '{$story->title}'. Quá trình này có thể mất vài phút.";

        return redirect()->route('stories.video', $story)
            ->with('success', $message);
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

            // Lưu file vào thư mục videos/assets
            $overlayDir = storage_path('app/videos/assets');
            if (!File::isDirectory($overlayDir)) {
                File::makeDirectory($overlayDir, 0755, true);
            }

            $file->move($overlayDir, $fileName);

            return response()->json([
                'success' => true,
                'message' => "Đã upload video overlay: {$originalName}",
                'filename' => $fileName
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi upload file: ' . $e->getMessage()
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
                'message' => 'Lỗi khi xóa file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Hiển thị chi tiết truyện
    public function show(Story $story)
    {
        $chapterCount = Chapter::where('story_id', $story->id)->count();
        $latestChapters = Chapter::where('story_id', $story->id)
            ->orderBy('chapter_number', 'desc')
            ->take(5)
            ->get();

        return view('stories.show', compact('story', 'chapterCount', 'latestChapters'));
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
}



