<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use App\Models\Channel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduledPostController extends Controller
{
    /**
     * Display a listing of scheduled posts
     */
    public function index(Request $request)
    {
        $query = ScheduledPost::with('channel');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->channel_id);
        }

        // Filter by platform
        if ($request->filled('platform')) {
            $query->whereHas('channel', function($q) use ($request) {
                $q->where('platform', $request->platform);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('scheduled_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('scheduled_at', '<=', $request->date_to);
        }

        $posts = $query->orderBy('scheduled_at', 'desc')->paginate(20);

        // Get filter options
        $channels = Channel::active()->select('id', 'name', 'platform')->get();
        $statuses = ['pending', 'processing', 'uploaded', 'failed', 'cancelled'];

        return view('admin.scheduled-posts.index', compact('posts', 'channels', 'statuses'));
    }

    /**
     * Show the form for creating a new scheduled post
     */
    public function create(Request $request)
    {
        $channels = Channel::active()->get();
        
        // Pre-fill from request (when coming from video generator)
        $videoPath = $request->get('video_path');
        $videoType = $request->get('video_type', 'tiktok');
        $channelId = $request->get('channel_id');

        return view('admin.scheduled-posts.create', compact('channels', 'videoPath', 'videoType', 'channelId'));
    }

    /**
     * Store a newly created scheduled post
     */
    public function store(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|exists:channels,id',
            'video_path' => 'required|string',
            'video_type' => 'required|in:tiktok,story,custom',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
            'category' => 'nullable|string',
            'privacy' => 'required|in:public,private,unlisted',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'timezone' => 'required|string'
        ]);

        try {
            // Combine date and time
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->scheduled_date . ' ' . $request->scheduled_time,
                $request->timezone
            )->utc();

            // Prepare tags
            $tags = [];
            if ($request->tags) {
                $tags = array_map('trim', explode(',', $request->tags));
            }

            $post = ScheduledPost::create([
                'channel_id' => $request->channel_id,
                'video_path' => $request->video_path,
                'video_type' => $request->video_type,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $tags,
                'category' => $request->category,
                'privacy' => $request->privacy,
                'scheduled_at' => $scheduledAt,
                'timezone' => $request->timezone,
                'status' => 'pending'
            ]);

            return redirect()->route('admin.scheduled-posts.index')
                ->with('success', "Đã lên lịch đăng video thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified scheduled post
     */
    public function show(ScheduledPost $scheduledPost)
    {
        $scheduledPost->load('channel');
        return view('admin.scheduled-posts.show', compact('scheduledPost'));
    }

    /**
     * Show the form for editing the specified scheduled post
     */
    public function edit(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->isPending()) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa bài đăng đang chờ.');
        }

        $channels = Channel::active()->get();
        return view('admin.scheduled-posts.edit', compact('scheduledPost', 'channels'));
    }

    /**
     * Update the specified scheduled post
     */
    public function update(Request $request, ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->isPending()) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa bài đăng đang chờ.');
        }

        $request->validate([
            'channel_id' => 'required|exists:channels,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
            'category' => 'nullable|string',
            'privacy' => 'required|in:public,private,unlisted',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'timezone' => 'required|string'
        ]);

        try {
            // Combine date and time
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->scheduled_date . ' ' . $request->scheduled_time,
                $request->timezone
            )->utc();

            // Prepare tags
            $tags = [];
            if ($request->tags) {
                $tags = array_map('trim', explode(',', $request->tags));
            }

            $scheduledPost->update([
                'channel_id' => $request->channel_id,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $tags,
                'category' => $request->category,
                'privacy' => $request->privacy,
                'scheduled_at' => $scheduledAt,
                'timezone' => $request->timezone
            ]);

            return redirect()->route('admin.scheduled-posts.show', $scheduledPost)
                ->with('success', "Đã cập nhật lịch đăng thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified scheduled post
     */
    public function destroy(ScheduledPost $scheduledPost)
    {
        if ($scheduledPost->isProcessing()) {
            return back()->with('error', 'Không thể xóa bài đăng đang xử lý.');
        }

        try {
            $scheduledPost->delete();
            return redirect()->route('admin.scheduled-posts.index')
                ->with('success', "Đã xóa lịch đăng thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Cancel scheduled post
     */
    public function cancel(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->isPending()) {
            return back()->with('error', 'Chỉ có thể hủy bài đăng đang chờ.');
        }

        $scheduledPost->cancel();
        return back()->with('success', 'Đã hủy lịch đăng!');
    }

    /**
     * Retry failed post
     */
    public function retry(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->canRetry()) {
            return back()->with('error', 'Không thể thử lại bài đăng này.');
        }

        $scheduledPost->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        return back()->with('success', 'Đã đặt lại trạng thái để thử lại!');
    }

    /**
     * Post immediately
     */
    public function postNow(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->isPending()) {
            return back()->with('error', 'Chỉ có thể đăng ngay bài đăng đang chờ.');
        }

        $scheduledPost->update([
            'scheduled_at' => now(),
            'status' => 'pending'
        ]);

        return back()->with('success', 'Đã đặt lịch đăng ngay!');
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:cancel,delete,retry',
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:scheduled_posts,id'
        ]);

        try {
            $posts = ScheduledPost::whereIn('id', $request->post_ids);
            $count = 0;

            switch ($request->action) {
                case 'cancel':
                    $count = $posts->where('status', 'pending')->update(['status' => 'cancelled']);
                    break;
                case 'delete':
                    $count = $posts->whereNotIn('status', ['processing'])->delete();
                    break;
                case 'retry':
                    $count = $posts->where('status', 'failed')->update([
                        'status' => 'pending',
                        'error_message' => null
                    ]);
                    break;
            }

            return back()->with('success', "Đã thực hiện {$request->action} cho {$count} bài đăng!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats()
    {
        $stats = [
            'total' => ScheduledPost::count(),
            'pending' => ScheduledPost::where('status', 'pending')->count(),
            'processing' => ScheduledPost::where('status', 'processing')->count(),
            'uploaded' => ScheduledPost::where('status', 'uploaded')->count(),
            'failed' => ScheduledPost::where('status', 'failed')->count(),
            'ready_to_post' => ScheduledPost::readyToPost()->count(),
            'today_uploads' => ScheduledPost::where('uploaded_at', '>=', now()->startOfDay())->count(),
            'this_week_uploads' => ScheduledPost::where('uploaded_at', '>=', now()->startOfWeek())->count(),
        ];

        return response()->json($stats);
    }
}
