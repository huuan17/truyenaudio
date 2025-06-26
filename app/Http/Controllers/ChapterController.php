<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ChapterController extends Controller
{
    // Hiển thị danh sách chương theo truyện
    public function index($storyId)
    {
        $story = Story::findOrFail($storyId);
        $chapters = Chapter::where('story_id', $storyId)
            ->orderBy('chapter_number')
            ->paginate(20);

        return view('chapters.index', compact('story', 'chapters'));
    }

    // Hiển thị chi tiết chương
    public function show(Chapter $chapter)
    {
        return view('chapters.show', compact('chapter'));
    }

    // Hiển thị form tạo chương
    public function create(Request $request)
    {
        $storyId = $request->route('story_id') ?? $request->get('story_id');

        if ($storyId) {
            $story = Story::findOrFail($storyId);
            return view('chapters.create', compact('story'));
        }

        $stories = Story::all();
        return view('chapters.create', compact('stories'));
    }

    // Lưu chương mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'story_id' => 'required|exists:stories,id',
            'chapter_number' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $chapter = Chapter::create($validated);

        return redirect()->route('stories.chapters', $chapter->story)
            ->with('success', 'Đã thêm chương mới.');
    }

    // Hiển thị form sửa chương
    public function edit(Chapter $chapter)
    {
        // Đảm bảo có content để hiển thị (từ DB hoặc file)
        if (!$chapter->hasContentInDatabase() && $chapter->file_path) {
            // Nếu không có content trong DB nhưng có file, đọc từ file
            $chapter->content = $chapter->content; // Sẽ trigger accessor để đọc từ file
        }

        return view('chapters.edit', compact('chapter'));
    }

    // Cập nhật chương
    public function update(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $chapter->update($validated);

        return redirect()->route('stories.chapters', $chapter->story)
            ->with('success', 'Cập nhật chương thành công.');
    }

    // Xóa chương
    public function destroy(Chapter $chapter)
    {
        $chapter->delete();

        return back()->with('success', 'Đã xóa chương.');
    }

    // ✅ API lưu chương từ crawl.js
    public function storeFromCrawler(Request $request)
    {
        $validated = $request->validate([
            'story_id' => 'required|exists:stories,id',
            'chapter_number' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Chapter::updateOrCreate(
            [
                'story_id' => $validated['story_id'],
                'chapter_number' => $validated['chapter_number'],
            ],
            [
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_crawled' => true,
                'crawled_at' => now(),
            ]
        );

        return response()->json(['message' => 'Chapter saved'], 200);
    }

    /**
     * Chuyển đổi chapter thành audio
     */
    public function convertToTts(Request $request, Chapter $chapter)
    {
        $request->validate([
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
        ]);

        // Kiểm tra xem chapter có thể chuyển đổi không
        if (!$chapter->canConvertToTts()) {
            $message = 'Chapter này không thể chuyển đổi TTS.';

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return back()->with('error', $message);
        }

        // Chạy command TTS
        try {
            Artisan::queue('vbee:chapter-tts', [
                '--chapter_id' => $chapter->id,
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
            ]);

            $message = "Đã bắt đầu chuyển đổi chapter {$chapter->chapter_number} thành audio.";

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Lỗi khi bắt đầu chuyển đổi: ' . $e->getMessage();

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }
    }

    /**
     * Chuyển đổi tất cả chapters của story thành audio
     */
    public function convertAllToTts(Request $request, Story $story)
    {
        $request->validate([
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
            'only_pending' => 'boolean',
        ]);

        // Đếm số chapters có thể chuyển đổi
        $query = $story->chapters();

        if ($request->only_pending) {
            $query->where('audio_status', 'pending');
        } else {
            $query->where('audio_status', '!=', 'processing');
        }

        $chaptersCount = $query->count();

        if ($chaptersCount === 0) {
            return back()->with('error', 'Không có chapter nào để chuyển đổi.');
        }

        // Chạy command TTS cho tất cả chapters
        try {
            Artisan::queue('vbee:chapter-tts', [
                '--story_id' => $story->id,
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
            ]);

            $message = $request->only_pending
                ? "Đã bắt đầu chuyển đổi {$chaptersCount} chapter(s) chưa xử lý của truyện '{$story->title}' thành audio."
                : "Đã bắt đầu chuyển đổi tất cả {$chaptersCount} chapter(s) của truyện '{$story->title}' thành audio.";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi bắt đầu chuyển đổi: ' . $e->getMessage());
        }
    }

    /**
     * Lấy nội dung chapter để hiển thị trong modal
     */
    public function getContent(Chapter $chapter)
    {
        if (!$chapter->hasReadableContent()) {
            return response()->json(['error' => 'Chapter không có nội dung'], 404);
        }

        $content = $chapter->content;

        if (empty($content)) {
            return response()->json(['error' => 'Không thể đọc nội dung chapter'], 500);
        }

        return response()->json(['content' => $content]);
    }
}
