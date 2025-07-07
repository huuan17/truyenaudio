<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Story;
use App\Models\Chapter;
use App\Helpers\ChapterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class StoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $stories = Story::latest()->paginate(10);
        return view('admin.stories.index', compact('stories'));
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
            'source_url'    => 'required|url',
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

        // Sync genres
        if ($request->has('genres')) {
            $story->genres()->sync($request->genres);
        }

        return redirect()->route('admin.stories.index')->with('success', 'Truyện đã được tạo thành công!');
    }

    public function show(Story $story)
    {
        $chapterCount = $story->chapters()->count();
        $latestChapters = $story->chapters()->orderBy('chapter_number', 'desc')->limit(5)->get();
        return view('admin.stories.show', compact('story', 'chapterCount', 'latestChapters'));
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
            'source_url'    => 'required|url',
            'start_chapter' => 'required|integer|min:1',
            'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
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

        $story->update($request->except(['cover', 'genres']));

        // Sync genres
        if ($request->has('genres')) {
            $story->genres()->sync($request->genres);
        }

        return redirect()->route('admin.stories.index')->with('success', 'Truyện đã được cập nhật thành công!');
    }

    public function destroy(Story $story)
    {
        // Delete cover image if exists
        if ($story->cover_image && file_exists(public_path($story->cover_image))) {
            unlink(public_path($story->cover_image));
        }

        $story->delete();
        return redirect()->route('admin.stories.index')->with('success', 'Truyện đã được xóa thành công!');
    }

    // Chapter management
    public function chapters(Story $story)
    {
        $chapters = $story->chapters()->paginate(20);
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
                'crawl_status' => 1 // Set to crawling
            ]);

            // Run crawl command
            Artisan::call('crawl:story', [
                'story_id' => $story->id,
                '--start' => $startChapter,
                '--end' => $endChapter
            ]);

            return redirect()->route('admin.stories.index')->with('success', 'Crawl đã được khởi chạy thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // TTS functionality
    public function showTtsForm(Story $story)
    {
        $chapters = $story->chapters()->orderBy('chapter_number')->get();
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_manhtung_full_48k-fhg' => 'Mạnh Tùng (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)'
        ];
        return view('admin.stories.tts', compact('story', 'chapters', 'voices'));
    }

    public function tts(Request $request, Story $story)
    {
        $request->validate([
            'conversion_type' => 'required|in:all,single,multiple',
            'chapter_numbers' => 'required_if:conversion_type,single,multiple',
        ]);

        try {
            $conversionType = $request->conversion_type;
            $chapterNumbers = $request->chapter_numbers;

            if ($conversionType === 'all') {
                $chapters = $story->chapters;
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
            $includeContent = $request->boolean('include_content', false);
            $textBasePath = config('constants.STORAGE_PATHS.TEXT');
            $storyPath = $textBasePath . $story->folder_name;

            if (!is_dir($storyPath)) {
                return back()->with('error', 'Thư mục truyện không tồn tại: ' . $storyPath);
            }

            $files = glob($storyPath . '/*.txt');
            $scannedCount = 0;

            foreach ($files as $file) {
                $filename = basename($file, '.txt');
                
                // Extract chapter number from filename
                if (preg_match('/(\d+)/', $filename, $matches)) {
                    $chapterNumber = (int)$matches[1];
                    
                    // Check if chapter already exists
                    $existingChapter = $story->chapters()->where('chapter_number', $chapterNumber)->first();
                    
                    if (!$existingChapter) {
                        $chapterData = [
                            'story_id' => $story->id,
                            'chapter_number' => $chapterNumber,
                            'title' => 'Chương ' . $chapterNumber,
                            'file_path' => str_replace(storage_path('app/'), '', $file),
                            'file_size' => filesize($file),
                            'scan_status' => 'completed',
                            'scanned_at' => now(),
                        ];

                        if ($includeContent) {
                            $chapterData['content'] = file_get_contents($file);
                        }

                        Chapter::create($chapterData);
                        $scannedCount++;
                    }
                }
            }

            return back()->with('success', "Đã quét và thêm {$scannedCount} chương mới.");
        } catch (\Exception $e) {
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
}
