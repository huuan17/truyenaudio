<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Chapter::with('story');

        // Filter by story
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        // Filter by status
        if ($request->filled('text_status')) {
            if ($request->text_status === 'has_content') {
                $query->whereNotNull('content');
            } elseif ($request->text_status === 'no_content') {
                $query->whereNull('content');
            }
        }

        if ($request->filled('audio_status')) {
            if ($request->audio_status === 'has_audio') {
                $query->whereNotNull('audio_file_path');
            } elseif ($request->audio_status === 'no_audio') {
                $query->whereNull('audio_file_path');
            }
        }

        if ($request->filled('video_status')) {
            if ($request->video_status === 'has_video') {
                $query->whereNotNull('video_file_path');
            } elseif ($request->video_status === 'no_video') {
                $query->whereNull('video_file_path');
            }
        }

        $chapters = $query->orderBy('story_id')->orderBy('chapter_number')->paginate(20);
        $stories = Story::orderBy('title')->get();

        return view('admin.chapters.index', compact('chapters', 'stories'));
    }

    public function show(Chapter $chapter)
    {
        return view('admin.chapters.show', compact('chapter'));
    }

    public function edit(Chapter $chapter)
    {
        return view('admin.chapters.edit', compact('chapter'));
    }

    public function update(Request $request, Chapter $chapter)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $chapter->update($request->only(['title', 'content']));

        return redirect()->route('admin.chapters.index')->with('success', 'Chapter đã được cập nhật thành công!');
    }

    public function destroy(Chapter $chapter)
    {
        // Delete associated files if they exist
        if ($chapter->audio_file_path && file_exists(storage_path('app/' . $chapter->audio_file_path))) {
            unlink(storage_path('app/' . $chapter->audio_file_path));
        }

        if ($chapter->video_file_path && file_exists(storage_path('app/' . $chapter->video_file_path))) {
            unlink(storage_path('app/' . $chapter->video_file_path));
        }

        $chapter->delete();
        return redirect()->route('admin.chapters.index')->with('success', 'Chapter đã được xóa thành công!');
    }

    // TTS functionality for individual chapters
    public function convertToAudio(Request $request, Chapter $chapter)
    {
        try {
            // Check if chapter already has audio
            if ($chapter->audio_file_path && file_exists(storage_path('app/' . $chapter->audio_file_path))) {
                return response()->json(['success' => false, 'message' => 'Chapter đã có file audio']);
            }

            // Process TTS conversion
            $result = $this->processChapterTts($chapter);

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Chuyển đổi thành công']);
            } else {
                return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi chuyển đổi']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    // Batch TTS conversion
    public function batchConvertToAudio(Request $request)
    {
        $request->validate([
            'chapter_ids' => 'required|array',
            'chapter_ids.*' => 'exists:chapters,id'
        ]);

        try {
            $chapters = Chapter::whereIn('id', $request->chapter_ids)->get();
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($chapters as $chapter) {
                // Skip if already has audio
                if ($chapter->audio_file_path && file_exists(storage_path('app/' . $chapter->audio_file_path))) {
                    $skippedCount++;
                    continue;
                }

                $result = $this->processChapterTts($chapter);
                if ($result) {
                    $processedCount++;
                }
            }

            $message = "Đã xử lý {$processedCount} chương";
            if ($skippedCount > 0) {
                $message .= ", bỏ qua {$skippedCount} chương đã có audio";
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    private function processChapterTts(Chapter $chapter)
    {
        // Implementation for TTS processing
        // This would call your TTS service
        return true; // Placeholder
    }
}
