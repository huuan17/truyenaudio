<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use App\Traits\SortableTrait;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    use SortableTrait;
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

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Apply sorting
        $allowedSorts = ['title', 'chapter_number', 'story_id', 'created_at', 'updated_at'];
        $query = $this->applySorting($query, $request, $allowedSorts, 'story_id', 'asc');

        // Secondary sort by chapter_number if not already sorting by it
        if ($request->get('sort', 'story_id') !== 'chapter_number') {
            $query->orderBy('chapter_number', 'asc');
        }

        $chapters = $query->paginate(20);
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

    /**
     * Bulk TTS conversion for selected chapters with queue management
     */
    public function bulkTts(Request $request)
    {
        $request->validate([
            'chapter_ids' => 'required|array|min:1|max:50', // Limit to 50 chapters
            'chapter_ids.*' => 'exists:chapters,id',
            'story_id' => 'required|exists:stories,id'
        ]);

        try {
            $chapterIds = $request->chapter_ids;
            $storyId = $request->story_id;
            $userId = auth()->id();

            // Verify all chapters belong to the specified story
            $chapters = Chapter::whereIn('id', $chapterIds)
                              ->where('story_id', $storyId)
                              ->get();

            if ($chapters->count() !== count($chapterIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số chapter không thuộc về story này hoặc không tồn tại.'
                ], 400);
            }

            // Check if user has any active bulk TTS tasks
            $activeTasks = \App\Models\BulkTtsTask::where('user_id', $userId)
                                                  ->active()
                                                  ->count();

            if ($activeTasks > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã có task TTS đang chạy. Vui lòng chờ hoàn thành hoặc hủy task hiện tại.'
                ], 400);
            }

            // Validate chapters have content
            $validChapterIds = [];
            $errors = [];

            foreach ($chapters as $chapter) {
                if (empty($chapter->content)) {
                    $errors[] = "Chương {$chapter->chapter_number}: Không có nội dung";
                    continue;
                }

                // Reset chapter status
                $chapter->update([
                    'audio_status' => 'pending',
                    'tts_progress' => 0,
                    'tts_error' => null,
                    'tts_started_at' => null,
                    'tts_completed_at' => null
                ]);

                $validChapterIds[] = $chapter->id;
            }

            if (empty($validChapterIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có chapter nào hợp lệ để thực hiện TTS.',
                    'errors' => $errors
                ], 400);
            }

            // Create bulk TTS task
            $bulkTask = \App\Models\BulkTtsTask::create([
                'user_id' => $userId,
                'story_id' => $storyId,
                'chapter_ids' => $validChapterIds,
                'total_chapters' => count($validChapterIds),
                'status' => \App\Models\BulkTtsTask::STATUS_PENDING,
                'started_at' => now()
            ]);

            // Dispatch the first job to start the queue
            \App\Jobs\ProcessBulkTtsJob::dispatch($bulkTask->id, $validChapterIds, 0)
                                       ->onQueue('tts');

            $message = "Đã tạo task TTS cho " . count($validChapterIds) . " chương.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " chương bị bỏ qua: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'bulk_task_id' => $bulkTask->id,
                'total_chapters' => count($validChapterIds),
                'skipped_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bulk TTS creation failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'chapter_ids' => $chapterIds ?? [],
                'story_id' => $storyId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete selected chapters
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'chapter_ids' => 'required|array|min:1',
            'chapter_ids.*' => 'exists:chapters,id',
            'story_id' => 'required|exists:stories,id'
        ]);

        try {
            $chapterIds = $request->chapter_ids;
            $storyId = $request->story_id;

            // Verify all chapters belong to the specified story
            $chapters = Chapter::whereIn('id', $chapterIds)
                              ->where('story_id', $storyId)
                              ->get();

            if ($chapters->count() !== count($chapterIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số chapter không thuộc về story này hoặc không tồn tại.'
                ], 400);
            }

            $deletedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($chapters as $chapter) {
                try {
                    // Delete associated files
                    $this->deleteChapterFiles($chapter);

                    // Delete chapter record
                    $chapter->delete();
                    $deletedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Chương {$chapter->chapter_number}: {$e->getMessage()}";
                    $errorCount++;
                }
            }

            $message = "Đã xóa thành công {$deletedCount} chương.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} chương gặp lỗi: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk TTS tasks for a story
     */
    public function getBulkTtsTasks(Request $request, $storyId)
    {
        $tasks = \App\Models\BulkTtsTask::where('story_id', $storyId)
                                        ->with(['user', 'story', 'currentChapter'])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(10);

        return response()->json([
            'success' => true,
            'tasks' => $tasks
        ]);
    }

    /**
     * Cancel bulk TTS task
     */
    public function cancelBulkTtsTask(Request $request, $taskId)
    {
        $task = \App\Models\BulkTtsTask::findOrFail($taskId);

        // Check permission
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền hủy task này.'
            ], 403);
        }

        if (!$task->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Task này không thể hủy vì đã hoàn thành hoặc thất bại.'
            ], 400);
        }

        $task->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy task TTS thành công.'
        ]);
    }

    /**
     * Restart bulk TTS task
     */
    public function restartBulkTtsTask(Request $request, $taskId)
    {
        $task = \App\Models\BulkTtsTask::findOrFail($taskId);

        // Check permission
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền restart task này.'
            ], 403);
        }

        if ($task->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Task này đang chạy, không thể restart.'
            ], 400);
        }

        // Check if user has other active tasks
        $activeTasks = \App\Models\BulkTtsTask::where('user_id', $task->user_id)
                                              ->where('id', '!=', $task->id)
                                              ->active()
                                              ->count();

        if ($activeTasks > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã có task TTS khác đang chạy.'
            ], 400);
        }

        $task->restart();

        // Dispatch the job again
        \App\Jobs\ProcessBulkTtsJob::dispatch($task->id, $task->chapter_ids, 0)
                                   ->onQueue('tts');

        return response()->json([
            'success' => true,
            'message' => 'Đã restart task TTS thành công.'
        ]);
    }

    /**
     * Get bulk TTS task status
     */
    public function getBulkTtsTaskStatus(Request $request, $taskId)
    {
        $task = \App\Models\BulkTtsTask::with(['currentChapter', 'story'])
                                       ->findOrFail($taskId);

        // Get chapters progress
        $chapters = Chapter::whereIn('id', $task->chapter_ids)
                          ->select('id', 'chapter_number', 'title', 'audio_status', 'tts_progress', 'tts_error')
                          ->orderBy('chapter_number')
                          ->get();

        return response()->json([
            'success' => true,
            'task' => $task,
            'chapters' => $chapters
        ]);
    }

    /**
     * Cancel all TTS operations for selected chapters
     */
    public function cancelAllTts(Request $request)
    {
        $request->validate([
            'chapter_ids' => 'required|array|min:1',
            'chapter_ids.*' => 'exists:chapters,id',
            'story_id' => 'required|exists:stories,id'
        ]);

        try {
            $chapterIds = $request->chapter_ids;
            $storyId = $request->story_id;
            $userId = auth()->id();

            // Verify all chapters belong to the specified story
            $chapters = Chapter::whereIn('id', $chapterIds)
                              ->where('story_id', $storyId)
                              ->get();

            if ($chapters->count() !== count($chapterIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số chapter không thuộc về story này hoặc không tồn tại.'
                ], 400);
            }

            $cancelledCount = 0;
            $skippedCount = 0;
            $cancelledTasks = [];

            // 1. Cancel active bulk TTS tasks for this story
            $activeBulkTasks = \App\Models\BulkTtsTask::where('story_id', $storyId)
                                                      ->where('user_id', $userId)
                                                      ->active()
                                                      ->get();

            foreach ($activeBulkTasks as $task) {
                // Check if task contains any of the selected chapters
                $taskChapterIds = $task->chapter_ids ?? [];
                $hasSelectedChapters = !empty(array_intersect($taskChapterIds, $chapterIds));

                if ($hasSelectedChapters) {
                    $task->cancel();
                    $cancelledTasks[] = $task->id;
                    \Illuminate\Support\Facades\Log::info("Cancelled bulk TTS task {$task->id} by user request");
                }
            }

            // 2. Reset individual chapter statuses
            foreach ($chapters as $chapter) {
                $currentStatus = $chapter->audio_status ?? 'pending';

                // Only cancel chapters that are pending or processing
                if (in_array($currentStatus, ['pending', 'processing'])) {
                    $chapter->update([
                        'audio_status' => 'pending',
                        'tts_progress' => 0,
                        'tts_error' => null,
                        'tts_started_at' => null,
                        'tts_completed_at' => null
                    ]);
                    $cancelledCount++;
                } else {
                    $skippedCount++;
                }
            }

            // 3. Clear any pending TTS jobs from queue (if using database queue)
            $this->clearPendingTtsJobs($chapterIds);

            $message = "Đã hủy TTS cho {$cancelledCount} chương.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} chương đã hoàn thành hoặc thất bại nên không thể hủy.";
            }
            if (!empty($cancelledTasks)) {
                $message .= " Đã hủy " . count($cancelledTasks) . " bulk task.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'cancelled_count' => $cancelledCount,
                'skipped_count' => $skippedCount,
                'cancelled_tasks' => $cancelledTasks
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cancel all TTS failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'chapter_ids' => $chapterIds ?? [],
                'story_id' => $storyId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear pending TTS jobs from queue
     */
    private function clearPendingTtsJobs($chapterIds)
    {
        try {
            // If using database queue, we can delete pending jobs
            if (config('queue.default') === 'database') {
                \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('queue', 'tts')
                    ->where('payload', 'LIKE', '%ProcessBulkTtsJob%')
                    ->delete();

                \Illuminate\Support\Facades\Log::info('Cleared pending TTS jobs from database queue');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear pending TTS jobs: ' . $e->getMessage());
        }
    }

    /**
     * Get TTS status summary for a story
     */
    public function getTtsStatusSummary(Request $request, $storyId)
    {
        try {
            // Get active bulk tasks
            $activeTasks = \App\Models\BulkTtsTask::where('story_id', $storyId)
                                                  ->active()
                                                  ->count();

            // Get chapters by status
            $statusCounts = Chapter::where('story_id', $storyId)
                                  ->selectRaw('audio_status, COUNT(*) as count')
                                  ->groupBy('audio_status')
                                  ->pluck('count', 'audio_status')
                                  ->toArray();

            $processingCount = $statusCounts['processing'] ?? 0;
            $pendingCount = $statusCounts['pending'] ?? 0;
            $completedCount = $statusCounts['completed'] ?? 0;
            $failedCount = $statusCounts['failed'] ?? 0;

            return response()->json([
                'success' => true,
                'summary' => [
                    'active_tasks' => $activeTasks,
                    'processing_chapters' => $processingCount,
                    'pending_chapters' => $pendingCount,
                    'completed_chapters' => $completedCount,
                    'failed_chapters' => $failedCount,
                    'has_active_tts' => $activeTasks > 0 || $processingCount > 0 || $pendingCount > 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin TTS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chapter content for viewing
     */
    public function getContent(Chapter $chapter)
    {
        try {
            // Use the content attribute which automatically handles DB and file content
            $content = $chapter->content;

            if (empty($content)) {
                return response()->json([
                    'success' => false,
                    'content' => 'Không có nội dung để hiển thị.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'content' => $content
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'content' => 'Lỗi khi tải nội dung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel individual chapter TTS
     */
    public function cancelChapterTts(Request $request, $chapterId)
    {
        try {
            $chapter = Chapter::findOrFail($chapterId);
            $userId = auth()->id();

            // Check permission
            if (!auth()->user()->isAdmin() && $chapter->story->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thao tác với chapter này.'
                ], 403);
            }

            $currentStatus = $chapter->audio_status ?? 'pending';

            // Only allow cancellation for pending or processing chapters
            if (!in_array($currentStatus, ['pending', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chapter này không thể hủy TTS vì đã hoàn thành hoặc thất bại.'
                ], 400);
            }

            // Find and cancel any bulk tasks containing this chapter
            $cancelledTasks = [];
            $bulkTasks = \App\Models\BulkTtsTask::where('story_id', $chapter->story_id)
                                                ->active()
                                                ->get();

            foreach ($bulkTasks as $task) {
                $taskChapterIds = $task->chapter_ids ?? [];
                if (in_array($chapter->id, $taskChapterIds)) {
                    // Remove this chapter from the task or cancel if it's the only one
                    $remainingChapterIds = array_diff($taskChapterIds, [$chapter->id]);

                    if (empty($remainingChapterIds)) {
                        // Cancel the entire task if this was the only chapter
                        $task->cancel();
                        $cancelledTasks[] = $task->id;
                        \Illuminate\Support\Facades\Log::info("Cancelled bulk TTS task {$task->id} - no remaining chapters");
                    } else {
                        // Update task to remove this chapter
                        $task->update([
                            'chapter_ids' => $remainingChapterIds,
                            'total_chapters' => count($remainingChapterIds)
                        ]);
                        \Illuminate\Support\Facades\Log::info("Removed chapter {$chapter->id} from bulk TTS task {$task->id}");
                    }
                }
            }

            // Reset chapter status
            $chapter->update([
                'audio_status' => 'pending',
                'tts_progress' => 0,
                'tts_error' => null,
                'tts_started_at' => null,
                'tts_completed_at' => null
            ]);

            // Clear any specific jobs for this chapter (if using database queue)
            $this->clearChapterTtsJobs($chapter->id);

            $message = "Đã hủy TTS cho chương {$chapter->chapter_number}.";
            if (!empty($cancelledTasks)) {
                $message .= " Đã hủy " . count($cancelledTasks) . " bulk task liên quan.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'chapter_id' => $chapter->id,
                'new_status' => 'pending',
                'cancelled_tasks' => $cancelledTasks
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cancel chapter TTS failed: ' . $e->getMessage(), [
                'chapter_id' => $chapterId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear TTS jobs for a specific chapter
     */
    private function clearChapterTtsJobs($chapterId)
    {
        try {
            if (config('queue.default') === 'database') {
                // This is a simplified approach - in practice you might need more sophisticated job filtering
                \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('queue', 'tts')
                    ->where('payload', 'LIKE', "%chapter_id\":{$chapterId}%")
                    ->delete();

                \Illuminate\Support\Facades\Log::info("Cleared TTS jobs for chapter {$chapterId}");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to clear TTS jobs for chapter {$chapterId}: " . $e->getMessage());
        }
    }

    /**
     * Delete files associated with a chapter
     */
    private function deleteChapterFiles(Chapter $chapter)
    {
        // Delete audio file
        if ($chapter->audio_file_path) {
            $audioPath = storage_path('app/' . $chapter->audio_file_path);
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        // Delete text file
        if ($chapter->file_path) {
            $textPath = storage_path('app/' . $chapter->file_path);
            if (file_exists($textPath)) {
                unlink($textPath);
            }
        }

        // Delete video files if any
        if ($chapter->video) {
            $videoPath = $chapter->video->file_path;
            if ($videoPath) {
                $fullVideoPath = storage_path('app/' . $videoPath);
                if (file_exists($fullVideoPath)) {
                    unlink($fullVideoPath);
                }
            }
            $chapter->video->delete();
        }
    }
}
