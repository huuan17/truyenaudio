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
        // Sử dụng default settings từ story nếu không có trong request
        $story = $chapter->story;
        $defaultVoice = $story->default_tts_voice ?? 'hn_female_ngochuyen_full_48k-fhg';
        $defaultBitrate = $story->default_tts_bitrate ?? 128;
        $defaultSpeed = $story->default_tts_speed ?? 1.0;
        $defaultVolume = $story->default_tts_volume ?? 1.0;

        $request->validate([
            'voice' => 'nullable|string',
            'bitrate' => 'nullable|numeric|in:64,128,192,256,320',
            'speed' => 'nullable|numeric|in:0.5,1.0,1.5,2.0',
            'volume' => 'nullable|numeric|in:1.0,1.5,2.0',
        ]);

        // Sử dụng giá trị từ request hoặc default từ story
        $voice = $request->input('voice', $defaultVoice);
        $bitrate = $request->input('bitrate', $defaultBitrate);
        $speed = $request->input('speed', $defaultSpeed);
        $volume = $request->input('volume', $defaultVolume);

        // Kiểm tra xem chapter có thể chuyển đổi không
        if (!$chapter->canConvertToTts()) {
            $message = 'Chapter này không thể chuyển đổi TTS.';

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return back()->with('error', $message);
        }

        // Chạy command TTS với settings đã xử lý
        try {
            \App\Jobs\ProcessChapterTtsJob::dispatchSync($chapter->id, $voice, $bitrate, $speed, $volume);

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
        // Sử dụng default settings từ story nếu không có trong request
        $defaultVoice = $story->default_tts_voice ?? 'hn_female_ngochuyen_full_48k-fhg';
        $defaultBitrate = $story->default_tts_bitrate ?? 128;
        $defaultSpeed = $story->default_tts_speed ?? 1.0;
        $defaultVolume = $story->default_tts_volume ?? 1.0;

        $request->validate([
            'voice' => 'nullable|string',
            'bitrate' => 'nullable|numeric|in:64,128,192,256,320',
            'speed' => 'nullable|numeric|in:0.5,1.0,1.5,2.0',
            'volume' => 'nullable|numeric|in:1.0,1.5,2.0',
            'only_pending' => 'boolean',
        ]);

        // Sử dụng giá trị từ request hoặc default từ story
        $voice = $request->input('voice', $defaultVoice);
        $bitrate = $request->input('bitrate', $defaultBitrate);
        $speed = $request->input('speed', $defaultSpeed);
        $volume = $request->input('volume', $defaultVolume);

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

        // Chạy command TTS cho tất cả chapters với settings đã xử lý
        try {
            // Queue TTS job for each chapter individually
            $chapters = $story->chapters()->whereNull('audio_file_path')->get();
            foreach ($chapters as $chapter) {
                \App\Jobs\ProcessChapterTtsJob::dispatch($chapter->id, $voice, $bitrate, $speed, $volume);
            }

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
