<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedVideo;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VideoManagementController extends Controller
{
    /**
     * Display a listing of videos
     */
    public function index(Request $request)
    {
        $query = GeneratedVideo::with(['task', 'channel'])->orderBy('created_at', 'desc');

        // Filter by platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel publish status
        if ($request->filled('channel_status')) {
            switch ($request->channel_status) {
                case 'not_publishing':
                    $query->where('publish_to_channel', false);
                    break;
                case 'waiting_channel':
                    $query->where('publish_to_channel', true)
                          ->whereNull('channel_published_at')
                          ->whereNotNull('channel_id');
                    break;
                case 'published_channel':
                    $query->whereNotNull('channel_published_at');
                    break;
                case 'error_channel':
                    $query->whereNotNull('channel_publish_error');
                    break;
            }
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Sort
        if ($request->filled('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->get('direction', 'asc');

            if (in_array($sortField, ['title', 'platform', 'status', 'created_at', 'scheduled_at'])) {
                $query->orderBy($sortField, $sortDirection);
            }
        }

        $videos = $query->paginate(20)->withQueryString();
        $channels = Channel::active()->get();

        return view('admin.videos.index', compact('videos', 'channels'));
    }

    /**
     * Show video details
     */
    public function show(GeneratedVideo $video)
    {
        $video->load('task');
        return view('admin.videos.show', compact('video'));
    }

    /**
     * Show edit form
     */
    public function edit(GeneratedVideo $video)
    {
        $channels = Channel::active()->where('platform', $video->platform)->get();
        return view('admin.videos.edit', compact('video', 'channels'));
    }

    /**
     * Update video
     */
    public function update(Request $request, GeneratedVideo $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:generated,scheduled,published,failed',
            'scheduled_at' => 'nullable|date|after:now',
            'channel_id' => 'nullable|exists:channels,id',
            'publish_to_channel' => 'boolean',
            'auto_publish' => 'boolean',
        ]);

        $data = $request->only(['title', 'description', 'status', 'channel_id']);

        // Handle channel publishing settings
        $data['publish_to_channel'] = $request->boolean('publish_to_channel');
        $data['auto_publish'] = $request->boolean('auto_publish');

        // Handle scheduling
        if ($request->status === 'scheduled' && $request->filled('scheduled_at')) {
            $data['scheduled_at'] = Carbon::parse($request->scheduled_at);
        } elseif ($request->status !== 'scheduled') {
            $data['scheduled_at'] = null;
        }

        // Handle publishing
        if ($request->status === 'published' && $video->status !== 'published') {
            $data['published_at'] = now();
        }

        // Reset channel publish status if channel settings changed
        if ($request->has('channel_id') || $request->has('publish_to_channel')) {
            $data['channel_published_at'] = null;
            $data['channel_publish_error'] = null;
        }

        $video->update($data);

        return redirect()->route('admin.videos.index')
                        ->with('success', 'Video đã được cập nhật thành công!');
    }

    /**
     * Download video file
     */
    public function download(GeneratedVideo $video)
    {
        if (!$video->fileExists()) {
            abort(404, 'File video không tồn tại');
        }

        return Response::download($video->file_path, $video->file_name);
    }

    /**
     * Preview video
     */
    public function preview(GeneratedVideo $video)
    {
        if (!$video->fileExists()) {
            abort(404, 'File video không tồn tại');
        }

        $file = File::get($video->file_path);
        $type = File::mimeType($video->file_path);

        return Response::make($file, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline; filename="' . $video->file_name . '"'
        ]);
    }

    /**
     * Delete video
     */
    public function destroy(GeneratedVideo $video)
    {
        // Delete file if exists
        if ($video->fileExists()) {
            File::delete($video->file_path);
        }

        // Delete thumbnail if exists
        if ($video->thumbnail_path && File::exists($video->thumbnail_path)) {
            File::delete($video->thumbnail_path);
        }

        $video->delete();

        return redirect()->route('admin.videos.index')
                        ->with('success', 'Video đã được xóa thành công!');
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,schedule,publish,enable_channel,disable_channel',
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:generated_videos,id',
            'scheduled_at' => 'nullable|date|after:now',
            'channel_id' => 'nullable|exists:channels,id',
        ]);

        $videos = GeneratedVideo::whereIn('id', $request->video_ids)->get();

        switch ($request->action) {
            case 'delete':
                foreach ($videos as $video) {
                    if ($video->fileExists()) {
                        File::delete($video->file_path);
                    }
                    if ($video->thumbnail_path && File::exists($video->thumbnail_path)) {
                        File::delete($video->thumbnail_path);
                    }
                    $video->delete();
                }
                $message = 'Đã xóa ' . count($videos) . ' video thành công!';
                break;

            case 'schedule':
                if (!$request->filled('scheduled_at')) {
                    return back()->withErrors(['scheduled_at' => 'Vui lòng chọn thời gian đăng']);
                }

                $scheduledAt = Carbon::parse($request->scheduled_at);
                GeneratedVideo::whereIn('id', $request->video_ids)
                             ->update([
                                 'status' => 'scheduled',
                                 'scheduled_at' => $scheduledAt
                             ]);
                $message = 'Đã lên lịch ' . count($videos) . ' video thành công!';
                break;

            case 'publish':
                GeneratedVideo::whereIn('id', $request->video_ids)
                             ->update([
                                 'status' => 'published',
                                 'published_at' => now(),
                                 'scheduled_at' => null
                             ]);
                $message = 'Đã đăng ' . count($videos) . ' video thành công!';
                break;

            case 'enable_channel':
                $updateData = [
                    'publish_to_channel' => true,
                    'channel_published_at' => null,
                    'channel_publish_error' => null
                ];

                if ($request->filled('channel_id')) {
                    $updateData['channel_id'] = $request->channel_id;
                }

                GeneratedVideo::whereIn('id', $request->video_ids)->update($updateData);
                $message = 'Đã bật đăng kênh cho ' . count($videos) . ' video thành công!';
                break;

            case 'disable_channel':
                GeneratedVideo::whereIn('id', $request->video_ids)
                             ->update([
                                 'publish_to_channel' => false,
                                 'channel_published_at' => null,
                                 'channel_publish_error' => null
                             ]);
                $message = 'Đã tắt đăng kênh cho ' . count($videos) . ' video thành công!';
                break;
        }

        return redirect()->route('admin.videos.index')->with('success', $message);
    }
}
