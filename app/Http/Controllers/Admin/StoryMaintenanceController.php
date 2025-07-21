<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoryMaintenanceController extends Controller
{
    /**
     * Hiển thị trang maintenance
     */
    public function index()
    {
        // Thống kê tổng quan
        $stats = [
            'total_stories' => Story::count(),
            'completed_crawl' => Story::where('crawl_status', 1)->count(),
            'pending_crawl' => Story::where('crawl_status', 0)->count(),
            'total_chapters' => Chapter::count(),
            'chapters_with_content' => Chapter::whereNotNull('file_path')->count(),
            'chapters_with_audio' => Chapter::whereNotNull('audio_file_path')->count(),
            'pending_tts' => Chapter::where('audio_status', 'pending')->count(),
            'processing_tts' => Chapter::where('audio_status', 'processing')->count(),
        ];

        // Tìm stories có vấn đề về số chương
        $problemStories = DB::select("
            SELECT s.id, s.title, s.slug, s.start_chapter, s.end_chapter, s.crawl_status,
                   COUNT(c.id) as actual_chapters,
                   (s.end_chapter - s.start_chapter + 1) as expected_chapters
            FROM stories s
            LEFT JOIN chapters c ON s.id = c.story_id
            GROUP BY s.id, s.title, s.slug, s.start_chapter, s.end_chapter, s.crawl_status
            HAVING actual_chapters != expected_chapters AND actual_chapters > 0
            ORDER BY s.title
        ");

        // Tìm TTS requests bị stuck
        $stuckTTS = Chapter::where('audio_status', 'processing')
            ->where('tts_started_at', '<', now()->subMinutes(30))
            ->with('story:id,title,slug')
            ->take(10)
            ->get();

        return view('admin.maintenance.index', compact('stats', 'problemStories', 'stuckTTS'));
    }

    /**
     * Fix chapter counts cho một story
     */
    public function fixChapterCount(Request $request, Story $story)
    {
        $updated = $story->updateChapterCount();
        
        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật số chương cho '{$story->title}'"
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể cập nhật số chương'
        ]);
    }

    /**
     * Cập nhật crawl status cho một story
     */
    public function updateCrawlStatus(Request $request, Story $story)
    {
        $updated = $story->updateCrawlStatus();
        
        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật trạng thái crawl cho '{$story->title}'"
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Trạng thái crawl không cần cập nhật'
        ]);
    }

    /**
     * Hủy pending TTS cho một story
     */
    public function cancelPendingTTS(Request $request, Story $story)
    {
        try {
            $cancelled = $story->cancelPendingTTS();

            // Check if it's an AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã hủy {$cancelled} TTS requests cho '{$story->title}'"
                ]);
            }

            // Form submission - redirect with flash message
            return redirect()->route('admin.maintenance.index')
                ->with('success', "Đã hủy {$cancelled} TTS requests cho '{$story->title}'");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi khi hủy TTS: " . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('error', "Lỗi khi hủy TTS: " . $e->getMessage());
        }
    }

    /**
     * Hủy tất cả pending TTS
     */
    public function cancelAllPendingTTS(Request $request)
    {
        try {
            $cancelled = Chapter::where('audio_status', 'pending')
                ->update([
                    'audio_status' => 'none',
                    'tts_started_at' => null,
                    'tts_error' => 'Cancelled by maintenance'
                ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã hủy {$cancelled} TTS requests"
                ]);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('success', "Đã hủy {$cancelled} TTS requests");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi: " . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('error', "Lỗi: " . $e->getMessage());
        }
    }

    /**
     * Fix stuck TTS requests
     */
    public function fixStuckTTS(Request $request)
    {
        try {
            $fixed = Chapter::where('audio_status', 'processing')
                ->where('tts_started_at', '<', now()->subMinutes(30))
                ->update([
                    'audio_status' => 'error',
                    'tts_error' => 'Timeout - auto cancelled by maintenance'
                ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã fix {$fixed} stuck TTS requests"
                ]);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('success', "Đã fix {$fixed} stuck TTS requests");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi: " . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('error', "Lỗi: " . $e->getMessage());
        }
    }

    /**
     * Chạy maintenance tự động
     */
    public function runAutoMaintenance(Request $request)
    {
        $results = [];

        // 1. Cập nhật chapter counts
        $stories = Story::all();
        $chapterCountsFixed = 0;
        foreach ($stories as $story) {
            if ($story->updateChapterCount()) {
                $chapterCountsFixed++;
            }
        }
        $results['chapter_counts_fixed'] = $chapterCountsFixed;

        // 2. Cập nhật crawl status
        $crawlStatusFixed = 0;
        foreach ($stories as $story) {
            if ($story->updateCrawlStatus()) {
                $crawlStatusFixed++;
            }
        }
        $results['crawl_status_fixed'] = $crawlStatusFixed;

        // 3. Hủy pending TTS
        $pendingCancelled = Chapter::where('audio_status', 'pending')
            ->whereNull('tts_started_at')
            ->update(['audio_status' => 'none']);
        $results['pending_tts_cancelled'] = $pendingCancelled;

        // 4. Fix stuck TTS
        $stuckFixed = Chapter::where('audio_status', 'processing')
            ->where('tts_started_at', '<', now()->subMinutes(30))
            ->update([
                'audio_status' => 'error',
                'tts_error' => 'Timeout - auto cancelled by maintenance'
            ]);
        $results['stuck_tts_fixed'] = $stuckFixed;

        return response()->json([
            'success' => true,
            'message' => 'Auto maintenance completed',
            'results' => $results
        ]);
    }
}
