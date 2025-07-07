<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TiktokVideoController extends Controller
{
    /**
     * Hiển thị form tạo video TikTok
     */
    public function index()
    {
        // Lấy danh sách video đã tạo
        $videoDir = storage_path('app/tiktok_videos');
        $existingVideos = [];
        
        if (File::isDirectory($videoDir)) {
            $existingVideos = File::glob($videoDir . '/*.mp4');
            $existingVideos = array_map(function($path) {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => File::size($path),
                    'created' => File::lastModified($path)
                ];
            }, $existingVideos);
            
            // Sắp xếp theo thời gian tạo mới nhất
            usort($existingVideos, function($a, $b) {
                return $b['created'] - $a['created'];
            });
        }

        // Danh sách giọng đọc VBee
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_manhtung_full_48k-fhg' => 'Mạnh Tùng (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)'
        ];

        // Danh sách logo có sẵn
        $logos = $this->getAvailableLogos();

        // Danh sách kênh TikTok
        $channels = \App\Models\Channel::active()
            ->where('platform', 'tiktok')
            ->select('id', 'name', 'username', 'logo_config', 'default_privacy', 'default_tags', 'default_category')
            ->get();

        return view('admin.tiktok.index', compact('existingVideos', 'voices', 'logos', 'channels'));
    }

    /**
     * Xử lý tạo video TikTok
     */
    public function generate(Request $request)
    {
        $request->validate([
            'script_text' => 'required|string|min:10',
            'product_video' => 'required|file|mimes:mp4,avi,mov|max:102400', // 100MB
            'product_image' => 'nullable|file|mimes:jpg,jpeg,png|max:10240', // 10MB
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
            'volume' => 'required|numeric|between:-30,30',
            'use_logo' => 'boolean',
            'logo_file' => 'nullable|string',
            'logo_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
            'logo_size' => 'nullable|numeric|between:50,500',
            'channel_id' => 'nullable|exists:channels,id',
            'schedule_post' => 'boolean',
            'scheduled_date' => 'nullable|required_if:schedule_post,1|date',
            'scheduled_time' => 'nullable|required_if:schedule_post,1|date_format:H:i',
            'post_title' => 'nullable|string|max:255',
            'post_description' => 'nullable|string',
            'post_tags' => 'nullable|string',
            'output_name' => 'nullable|string|max:100'
        ]);

        try {
            // Tạo thư mục tạm thời
            $tempId = uniqid();
            $tempDir = storage_path("app/temp/tiktok_{$tempId}");
            File::makeDirectory($tempDir, 0755, true);

            // Upload và lưu file
            $productVideoPath = $this->saveUploadedFile($request->file('product_video'), $tempDir, 'product_video.mp4');
            
            $productImagePath = null;
            if ($request->hasFile('product_image')) {
                $productImagePath = $this->saveUploadedFile($request->file('product_image'), $tempDir, 'product_image.jpg');
            }

            // Tạo tên file output
            $outputName = $request->output_name ?: 'tiktok_review_' . date('Y-m-d_H-i-s') . '.mp4';
            if (!Str::endsWith($outputName, '.mp4')) {
                $outputName .= '.mp4';
            }

            // Xử lý logo từ kênh hoặc manual
            $logoParams = [];
            $selectedChannel = null;

            if ($request->channel_id) {
                $selectedChannel = \App\Models\Channel::find($request->channel_id);
                if ($selectedChannel && $selectedChannel->logo_config) {
                    $logoConfig = $selectedChannel->logo_config;
                    $logoPath = storage_path('app/logos/' . $logoConfig['logo_file']);
                    if (File::exists($logoPath)) {
                        $logoParams = [
                            '--logo' => $logoPath,
                            '--logo-position' => $logoConfig['position'] ?: 'bottom-right',
                            '--logo-size' => $logoConfig['size'] ?: 100
                        ];
                    }
                }
            } elseif ($request->use_logo && $request->logo_file) {
                $logoPath = storage_path('app/logos/' . $request->logo_file);
                if (File::exists($logoPath)) {
                    $logoParams = [
                        '--logo' => $logoPath,
                        '--logo-position' => $request->logo_position ?: 'bottom-right',
                        '--logo-size' => $request->logo_size ?: 100
                    ];
                }
            }

            // Chạy command xử lý video TikTok
            $exitCode = Artisan::call('tiktok:generate', array_merge([
                '--script' => $request->script_text,
                '--product-video' => $productVideoPath,
                '--product-image' => $productImagePath,
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
                '--volume' => $request->volume,
                '--output' => $outputName,
                '--temp-dir' => $tempDir
            ], $logoParams));

            if ($exitCode === 0) {
                // Xóa thư mục tạm thời
                File::deleteDirectory($tempDir);

                $message = "Đã tạo video TikTok thành công: {$outputName}";

                // Nếu có lên lịch đăng
                if ($request->schedule_post && $selectedChannel) {
                    $this->schedulePost($request, $selectedChannel, $outputPath);
                    $message .= " và đã lên lịch đăng!";
                }

                return redirect()->route('admin.tiktok.index')
                    ->with('success', $message);
            } else {
                $output = Artisan::output();
                return back()->with('error', "Lỗi khi tạo video: {$output}")
                    ->withInput();
            }

        } catch (\Exception $e) {
            // Cleanup nếu có lỗi
            if (isset($tempDir) && File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Xóa video đã tạo
     */
    public function delete(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $filePath = storage_path('app/tiktok_videos/' . $request->filename);
            
            if (File::exists($filePath)) {
                File::delete($filePath);
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa video: {$request->filename}"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Video không tồn tại'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download video
     */
    public function download($filename)
    {
        $filePath = storage_path('app/tiktok_videos/' . $filename);
        
        if (!File::exists($filePath)) {
            abort(404, 'Video không tồn tại');
        }

        return response()->download($filePath);
    }

    /**
     * Lưu file upload
     */
    private function saveUploadedFile($file, $directory, $filename)
    {
        $path = $directory . '/' . $filename;
        $file->move($directory, $filename);
        return $path;
    }

    /**
     * Lấy thông tin chi tiết video
     */
    public function show($filename)
    {
        $filePath = storage_path('app/tiktok_videos/' . $filename);
        
        if (!File::exists($filePath)) {
            abort(404, 'Video không tồn tại');
        }

        $videoInfo = [
            'name' => $filename,
            'path' => $filePath,
            'size' => File::size($filePath),
            'size_formatted' => $this->formatBytes(File::size($filePath)),
            'created' => File::lastModified($filePath),
            'created_formatted' => date('d/m/Y H:i:s', File::lastModified($filePath))
        ];

        return view('admin.tiktok.show', compact('videoInfo'));
    }

    /**
     * Format bytes thành đơn vị dễ đọc
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
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

    /**
     * API endpoint để kiểm tra trạng thái xử lý
     */
    public function status(Request $request)
    {
        $tempId = $request->get('temp_id');
        $statusFile = storage_path("app/temp/tiktok_{$tempId}/status.json");
        
        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);
            return response()->json($status);
        }

        return response()->json([
            'status' => 'not_found',
            'message' => 'Không tìm thấy thông tin xử lý'
        ], 404);
    }

    /**
     * Lên lịch đăng video
     */
    private function schedulePost($request, $channel, $videoPath)
    {
        try {
            // Combine date and time
            $scheduledAt = \Carbon\Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->scheduled_date . ' ' . $request->scheduled_time,
                'Asia/Ho_Chi_Minh'
            )->utc();

            // Prepare tags
            $tags = [];
            if ($request->post_tags) {
                $tags = array_map('trim', explode(',', $request->post_tags));
            } elseif ($channel->default_tags) {
                $tags = $channel->default_tags;
            }

            \App\Models\ScheduledPost::create([
                'channel_id' => $channel->id,
                'video_path' => $videoPath,
                'video_type' => 'tiktok',
                'title' => $request->post_title ?: 'TikTok Video - ' . now()->format('Y-m-d H:i'),
                'description' => $request->post_description,
                'tags' => $tags,
                'category' => $channel->default_category,
                'privacy' => $channel->default_privacy,
                'scheduled_at' => $scheduledAt,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error scheduling post: ' . $e->getMessage());
        }
    }
}
