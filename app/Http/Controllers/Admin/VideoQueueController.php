<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VideoGenerationTask;
use App\Services\VideoGenerationService;

class VideoQueueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display video generation queue status
     */
    public function index(Request $request)
    {
        $videoService = new VideoGenerationService();
        
        // Get queue statistics
        $queueStatus = $videoService->getQueueStatus();
        
        // Get user's tasks with pagination
        $userTasks = VideoGenerationTask::forUser(auth()->id())
                                       ->orderBy('created_at', 'desc')
                                       ->paginate(20);
        
        // Get all tasks for admin view (optional)
        $allTasks = null;
        if (auth()->user()->isAdmin()) {
            $allTasks = VideoGenerationTask::with('user')
                                          ->orderBy('created_at', 'desc')
                                          ->paginate(50);
        }
        
        return view('admin.video-queue.index', compact('queueStatus', 'userTasks', 'allTasks'));
    }

    /**
     * Get real-time queue status via AJAX
     */
    public function status()
    {
        $videoService = new VideoGenerationService();
        $queueStatus = $videoService->getQueueStatus();
        
        // Get user's recent tasks
        $userTasks = VideoGenerationTask::forUser(auth()->id())
                                       ->orderBy('created_at', 'desc')
                                       ->limit(10)
                                       ->get()
                                       ->map(function ($task) {
                                           return [
                                               'id' => $task->id,
                                               'platform' => $task->platform_display,
                                               'type' => $task->type_display,
                                               'status' => $task->status,
                                               'status_display' => $task->status_display,
                                               'status_badge_class' => $task->status_badge_class,
                                               'progress' => $task->progress_percentage,
                                               'created_at' => $task->created_at->format('H:i:s d/m/Y'),
                                               'estimated_completion' => $task->estimated_completion ? $task->estimated_completion->format('H:i:s d/m/Y') : null,
                                               'duration' => $task->duration,
                                               'can_cancel' => $task->canBeCancelled(),
                                               'can_retry' => $task->canBeRetried(),
                                               'batch_progress' => $task->batch_progress
                                           ];
                                       });
        
        return response()->json([
            'queue_status' => $queueStatus,
            'user_tasks' => $userTasks
        ]);
    }

    /**
     * Cancel a pending task
     */
    public function cancel(Request $request, $taskId)
    {
        $task = VideoGenerationTask::findOrFail($taskId);
        
        // Check if user owns this task or is admin
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện'], 403);
        }
        
        if ($task->cancel()) {
            return response()->json(['success' => true, 'message' => 'Đã hủy task thành công']);
        } else {
            return response()->json(['success' => false, 'message' => 'Không thể hủy task này']);
        }
    }

    /**
     * Retry a failed task
     */
    public function retry(Request $request, $taskId)
    {
        $task = VideoGenerationTask::findOrFail($taskId);
        
        // Check if user owns this task or is admin
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện'], 403);
        }
        
        if (!$task->canBeRetried()) {
            return response()->json(['success' => false, 'message' => 'Task này không thể retry']);
        }
        
        try {
            // Create new task with same parameters
            $newTask = VideoGenerationTask::create([
                'user_id' => $task->user_id,
                'platform' => $task->platform,
                'type' => $task->type,
                'status' => VideoGenerationTask::STATUS_PENDING,
                'priority' => $task->priority,
                'parameters' => $task->parameters,
                'estimated_duration' => $task->estimated_duration,
                'batch_id' => $task->batch_id,
                'batch_index' => $task->batch_index,
                'total_in_batch' => $task->total_in_batch
            ]);
            
            // Dispatch new unified job
            $tempDir = storage_path("app/temp/{$task->platform}_retry_" . uniqid());
            \App\Jobs\GenerateUniversalVideoJob::dispatch($newTask->id, $task->platform, $task->parameters, $tempDir)
                                              ->onQueue('video');
            
            return response()->json([
                'success' => true, 
                'message' => 'Đã tạo task mới để retry',
                'new_task_id' => $newTask->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi retry: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete completed/failed tasks
     */
    public function delete(Request $request, $taskId)
    {
        $task = VideoGenerationTask::findOrFail($taskId);
        
        // Check if user owns this task or is admin
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện'], 403);
        }
        
        // Only allow deletion of completed/failed/cancelled tasks
        if (!in_array($task->status, [
            VideoGenerationTask::STATUS_COMPLETED,
            VideoGenerationTask::STATUS_FAILED,
            VideoGenerationTask::STATUS_CANCELLED
        ])) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể xóa task đã hoàn thành hoặc thất bại']);
        }
        
        try {
            $task->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa task thành công']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xóa: ' . $e->getMessage()]);
        }
    }

    /**
     * Get task details
     */
    public function show($taskId)
    {
        $task = VideoGenerationTask::findOrFail($taskId);
        
        // Check if user owns this task or is admin
        if ($task->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Không có quyền xem task này');
        }
        
        return response()->json([
            'id' => $task->id,
            'platform' => $task->platform_display,
            'type' => $task->type_display,
            'status' => $task->status_display,
            'status_badge_class' => $task->status_badge_class,
            'progress' => $task->progress_percentage,
            'parameters' => $task->parameters,
            'result' => $task->result,
            'created_at' => $task->created_at->format('H:i:s d/m/Y'),
            'started_at' => $task->started_at ? $task->started_at->format('H:i:s d/m/Y') : null,
            'completed_at' => $task->completed_at ? $task->completed_at->format('H:i:s d/m/Y') : null,
            'estimated_completion' => $task->estimated_completion ? $task->estimated_completion->format('H:i:s d/m/Y') : null,
            'duration' => $task->duration,
            'batch_id' => $task->batch_id,
            'batch_progress' => $task->batch_progress,
            'can_cancel' => $task->canBeCancelled(),
            'can_retry' => $task->canBeRetried()
        ]);
    }

    /**
     * Clear completed tasks (admin only)
     */
    public function clearCompleted(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện'], 403);
        }
        
        try {
            $deletedCount = VideoGenerationTask::whereIn('status', [
                VideoGenerationTask::STATUS_COMPLETED,
                VideoGenerationTask::STATUS_FAILED,
                VideoGenerationTask::STATUS_CANCELLED
            ])->whereDate('completed_at', '<', now()->subDays(7))->delete();
            
            return response()->json([
                'success' => true, 
                'message' => "Đã xóa {$deletedCount} task cũ"
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xóa: ' . $e->getMessage()]);
        }
    }

    /**
     * Get batch status
     */
    public function batchStatus($batchId)
    {
        $batchTasks = VideoGenerationTask::where('batch_id', $batchId)
                                        ->orderBy('batch_index')
                                        ->get();
        
        if ($batchTasks->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Batch không tồn tại']);
        }
        
        // Check if user owns any task in this batch or is admin
        $userOwns = $batchTasks->contains('user_id', auth()->id());
        if (!$userOwns && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền xem batch này'], 403);
        }
        
        $completed = $batchTasks->where('status', VideoGenerationTask::STATUS_COMPLETED)->count();
        $failed = $batchTasks->where('status', VideoGenerationTask::STATUS_FAILED)->count();
        $processing = $batchTasks->where('status', VideoGenerationTask::STATUS_PROCESSING)->count();
        $pending = $batchTasks->where('status', VideoGenerationTask::STATUS_PENDING)->count();
        $total = $batchTasks->count();
        
        return response()->json([
            'batch_id' => $batchId,
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'processing' => $processing,
            'pending' => $pending,
            'progress_percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'tasks' => $batchTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'batch_index' => $task->batch_index,
                    'status' => $task->status_display,
                    'status_badge_class' => $task->status_badge_class,
                    'progress' => $task->progress_percentage,
                    'duration' => $task->duration
                ];
            })
        ]);
    }
}
