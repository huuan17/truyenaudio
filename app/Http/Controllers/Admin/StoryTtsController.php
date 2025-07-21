<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryTtsController extends Controller
{
    /**
     * Chuyển đổi TTS cho một story với giới hạn số chương
     */
    public function convertStoryToTts(Request $request, Story $story)
    {
        $request->validate([
            'voice' => 'nullable|string',
            'bitrate' => 'nullable|numeric|in:64,128,192,256,320',
            'speed' => 'nullable|numeric|in:0.5,1.0,1.5,2.0',
            'volume' => 'nullable|numeric|in:1.0,1.5,2.0',
            'limit_chapters' => 'nullable|integer|min:1',
            'start_from' => 'nullable|integer|min:1',
            'chapter_ids' => 'nullable|array',
            'chapter_ids.*' => 'integer|exists:chapters,id',
        ]);

        // Lấy default settings từ story
        $defaultVoice = $story->default_tts_voice ?? 'hn_female_ngochuyen_full_48k-fhg';
        $defaultBitrate = $story->default_tts_bitrate ?? 128;
        $defaultSpeed = $story->default_tts_speed ?? 1.0;
        $defaultVolume = $story->default_tts_volume ?? 1.0;
        
        // Sử dụng giá trị từ request hoặc default từ story
        $voice = $request->input('voice', $defaultVoice);
        $bitrate = $request->input('bitrate', $defaultBitrate);
        $speed = $request->input('speed', $defaultSpeed);
        $volume = $request->input('volume', $defaultVolume);
        
        // Xác định chapters cần chuyển đổi
        $query = Chapter::where('story_id', $story->id);
        
        // Nếu có chapter_ids, chỉ chuyển đổi những chapters được chọn
        if ($request->has('chapter_ids') && !empty($request->chapter_ids)) {
            $query->whereIn('id', $request->chapter_ids);
        } else {
            // Nếu không có chapter_ids, áp dụng các filter khác
            
            // Bỏ qua chapters đã có audio nếu không force
            if (!$request->has('force') || !$request->force) {
                $query->where(function($q) {
                    $q->whereNull('audio_status')
                      ->orWhere('audio_status', '')
                      ->orWhere('audio_status', 'failed')
                      ->orWhere('audio_status', 'cancelled');
                });
            }
            
            // Áp dụng start_from nếu có
            if ($request->has('start_from') && $request->start_from > 0) {
                $query->where('chapter_number', '>=', $request->start_from);
            }
            
            // Sắp xếp theo số chapter
            $query->orderBy('chapter_number', 'asc');
            
            // Giới hạn số lượng chapter nếu có
            if ($request->has('limit_chapters') && $request->limit_chapters > 0) {
                $query->limit($request->limit_chapters);
            }
        }
        
        // Lấy danh sách chapters cần chuyển đổi
        $chapters = $query->get();
        
        if ($chapters->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có chapter nào cần chuyển đổi TTS'
            ]);
        }
        
        // Cập nhật trạng thái chapters thành pending
        foreach ($chapters as $chapter) {
            $chapter->update([
                'audio_status' => 'pending',
                'tts_progress' => 0,
                'tts_error' => null
            ]);
        }
        
        // Queue TTS job for each chapter individually
        foreach ($chapters as $chapter) {
            \App\Jobs\ProcessChapterTtsJob::dispatch($chapter->id, $voice, $bitrate, $speed, $volume);
        }
        
        // Log thông tin
        Log::info("Đã thêm {$chapters->count()} chapters của truyện '{$story->title}' vào queue TTS");
        
        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$chapters->count()} chapters vào queue TTS",
            'chapters_count' => $chapters->count()
        ]);
    }
    
    /**
     * Hủy tất cả TTS jobs của một story
     */
    public function cancelStoryTts(Request $request, Story $story)
    {
        try {
            // Đếm số chapters đang xử lý hoặc đang chờ
            $count = Chapter::where('story_id', $story->id)
                ->whereIn('audio_status', ['processing', 'pending'])
                ->count();
            
            // Cập nhật trạng thái chapters
            Chapter::where('story_id', $story->id)
                ->whereIn('audio_status', ['processing', 'pending'])
                ->update([
                    'audio_status' => 'none',
                    'tts_progress' => 0,
                    'tts_started_at' => null,
                    'tts_error' => 'Cancelled by user'
                ]);
            
            // Xóa jobs từ queue (nếu có thể)
            // Lưu ý: Đây là cách đơn giản, có thể không hoạt động với tất cả queue drivers
            DB::table('jobs')
                ->where('payload', 'like', "%story_id.*{$story->id}%")
                ->delete();
            
            Log::info("Đã hủy TTS cho truyện '{$story->title}' ({$count} chapters) bởi user " . auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => "Đã hủy TTS cho {$count} chapters của truyện '{$story->title}'",
                'cancelled_count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi khi hủy TTS cho truyện '{$story->title}': " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Lấy thông tin TTS của một story
     */
    public function getStoryTtsInfo(Story $story)
    {
        $stats = [
            'total' => Chapter::where('story_id', $story->id)->count(),
            'completed' => Chapter::where('story_id', $story->id)->where('audio_status', 'completed')->count(),
            'processing' => Chapter::where('story_id', $story->id)->where('audio_status', 'processing')->count(),
            'pending' => Chapter::where('story_id', $story->id)->where('audio_status', 'pending')->count(),
            'failed' => Chapter::where('story_id', $story->id)->where('audio_status', 'failed')->count(),
            'cancelled' => Chapter::where('story_id', $story->id)->where('audio_status', 'cancelled')->count(),
            'none' => Chapter::where('story_id', $story->id)->where(function($q) {
                $q->whereNull('audio_status')->orWhere('audio_status', '');
            })->count(),
        ];
        
        // Lấy 5 chapters gần đây nhất được cập nhật
        $recentChapters = Chapter::where('story_id', $story->id)
            ->whereNotNull('audio_status')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'chapter_number', 'title', 'audio_status', 'tts_progress', 'tts_error', 'updated_at']);
        
        return response()->json([
            'success' => true,
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'slug' => $story->slug,
                'default_tts_voice' => $story->default_tts_voice,
                'default_tts_bitrate' => $story->default_tts_bitrate,
                'default_tts_speed' => $story->default_tts_speed,
                'default_tts_volume' => $story->default_tts_volume,
            ],
            'stats' => $stats,
            'recent_chapters' => $recentChapters,
        ]);
    }
}
