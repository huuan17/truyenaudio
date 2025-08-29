<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    /**
     * Display a listing of channels
     */
    public function index()
    {
        $channels = Channel::with('scheduledPosts')
            ->withCount(['scheduledPosts as pending_posts_count' => function($query) {
                $query->where('status', 'pending');
            }])
            ->withCount(['scheduledPosts as uploaded_posts_count' => function($query) {
                $query->where('status', 'uploaded');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.channels.index', compact('channels'));
    }

    /**
     * Show the form for creating a new channel
     */
    public function create()
    {
        $logos = $this->getAvailableLogos();
        return view('admin.channels.create', compact('logos'));
    }

    /**
     * Store a newly created channel
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|in:tiktok,youtube',
            'channel_id' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo_file' => 'nullable|string',
            'logo_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
            'logo_size' => 'nullable|numeric|between:50,500',
            'logo_opacity' => 'nullable|numeric|between:0,1',
            'default_privacy' => 'required|in:public,private,unlisted',
            'default_tags' => 'nullable|string',
            'default_category' => 'nullable|string',
            'auto_upload' => 'boolean',

            // API Credentials
            'tiktok_client_key' => 'nullable|string',
            'tiktok_client_secret' => 'nullable|string',
            'tiktok_access_token' => 'nullable|string',
            'tiktok_refresh_token' => 'nullable|string',
            'youtube_client_id' => 'nullable|string',
            'youtube_client_secret' => 'nullable|string',
            'youtube_refresh_token' => 'nullable|string',
        ]);

        try {
            // Prepare logo config
            $logoConfig = null;
            if ($request->logo_file) {
                $logoConfig = [
                    'logo_file' => $request->logo_file,
                    'position' => $request->logo_position ?: 'bottom-right',
                    'size' => $request->logo_size ?: 100,
                    'opacity' => $request->logo_opacity ?: 1.0
                ];
            }

            // Prepare API credentials
            $apiCredentials = [];
            if ($request->platform === 'tiktok') {
                if ($request->tiktok_client_key || $request->tiktok_access_token) {
                    $apiCredentials = [];

                    // Always store client credentials if provided
                    if ($request->tiktok_client_key) {
                        $apiCredentials['client_key'] = $request->tiktok_client_key;
                    }
                    if ($request->tiktok_client_secret) {
                        $apiCredentials['client_secret'] = $request->tiktok_client_secret;
                    }

                    // Store tokens if provided (from OAuth flow)
                    if ($request->tiktok_access_token) {
                        $apiCredentials['access_token'] = $request->tiktok_access_token;
                    }
                    if ($request->tiktok_refresh_token) {
                        $apiCredentials['refresh_token'] = $request->tiktok_refresh_token;
                    }
                }
            } elseif ($request->platform === 'youtube') {
                if ($request->youtube_client_id) {
                    $apiCredentials = [
                        'client_id' => $request->youtube_client_id,
                        'client_secret' => $request->youtube_client_secret,
                        'refresh_token' => $request->youtube_refresh_token,
                    ];
                }
            }

            // Prepare tags
            $defaultTags = [];
            if ($request->default_tags) {
                $defaultTags = array_map('trim', explode(',', $request->default_tags));
            }

            $channel = Channel::create([
                'name' => $request->name,
                'platform' => $request->platform,
                'channel_id' => $request->channel_id,
                'username' => $request->username,
                'description' => $request->description,
                'logo_config' => $logoConfig,
                'api_credentials' => $apiCredentials,
                'default_privacy' => $request->default_privacy,
                'default_tags' => $defaultTags,
                'default_category' => $request->default_category,
                'auto_upload' => $request->boolean('auto_upload'),
                'is_active' => true
            ]);

            return redirect()->route('admin.channels.index')
                ->with('success', "Đã tạo kênh {$channel->name} thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified channel
     */
    public function show(Channel $channel)
    {
        $channel->load(['scheduledPosts' => function($query) {
            $query->orderBy('scheduled_at', 'desc')->limit(20);
        }]);

        $stats = [
            'total_posts' => $channel->scheduledPosts()->count(),
            'pending_posts' => $channel->scheduledPosts()->where('status', 'pending')->count(),
            'uploaded_posts' => $channel->scheduledPosts()->where('status', 'uploaded')->count(),
            'failed_posts' => $channel->scheduledPosts()->where('status', 'failed')->count(),
            'uploads_this_month' => $channel->getUploadCount('30 days'),
            'uploads_this_week' => $channel->getUploadCount('7 days'),
        ];

        return view('admin.channels.show', compact('channel', 'stats'));
    }

    /**
     * Show the form for editing the specified channel
     */
    public function edit(Channel $channel)
    {
        $logos = $this->getAvailableLogos();
        return view('admin.channels.edit', compact('channel', 'logos'));
    }

    /**
     * Update the specified channel
     */
    public function update(Request $request, Channel $channel)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|in:tiktok,youtube',
            'channel_id' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo_file' => 'nullable|string',
            'logo_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
            'logo_size' => 'nullable|numeric|between:50,500',
            'logo_opacity' => 'nullable|numeric|between:0,1',
            'default_privacy' => 'required|in:public,private,unlisted',
            'default_tags' => 'nullable|string',
            'default_category' => 'nullable|string',
            'auto_upload' => 'boolean',
            'is_active' => 'boolean',

            // TikTok API credentials (manual update)
            'tiktok_access_token' => 'nullable|string',
            'tiktok_refresh_token' => 'nullable|string',
            'tiktok_open_id' => 'nullable|string',
            'clear_tiktok_credentials' => 'boolean',

            // YouTube credentials (manual update)
            'youtube_client_id' => 'nullable|string',
            'youtube_client_secret' => 'nullable|string',
            'youtube_refresh_token' => 'nullable|string',
            'clear_youtube_credentials' => 'boolean',
        ]);

        try {
            // Prepare logo config
            $logoConfig = $channel->logo_config;
            if ($request->logo_file) {
                $logoConfig = [
                    'logo_file' => $request->logo_file,
                    'position' => $request->logo_position ?: 'bottom-right',
                    'size' => $request->logo_size ?: 100,
                    'opacity' => $request->logo_opacity ?: 1.0
                ];
            } elseif ($request->has('remove_logo')) {
                $logoConfig = null;
            }

            // Prepare tags
            $defaultTags = [];
            if ($request->default_tags) {
                $defaultTags = array_map('trim', explode(',', $request->default_tags));
            }

            // Handle TikTok API credentials update
            $apiCredentials = $channel->api_credentials;


	            // Track if credentials changed
	            $apiCredsModified = false;

            if ($channel->platform === 'tiktok') {
                // Clear credentials if requested
                if ($request->boolean('clear_tiktok_credentials')) {
                    $apiCredentials = null;
                } else {
                    // Update individual credentials if provided
                    if ($request->filled('tiktok_access_token')) {
                        if (!$apiCredentials) {
                            $apiCredentials = [];
                        }
                        $apiCredentials['access_token'] = $request->tiktok_access_token;
                        $apiCredentials['updated_at'] = now()->toDateTimeString();
                        $apiCredentials['updated_manually'] = true;
                    }

                    if ($request->filled('tiktok_refresh_token')) {
                        if (!$apiCredentials) {
                            $apiCredentials = [];
                        }
                        $apiCredentials['refresh_token'] = $request->tiktok_refresh_token;
                        $apiCredentials['updated_at'] = now()->toDateTimeString();
                        $apiCredentials['updated_manually'] = true;
                    }

                    if ($request->filled('tiktok_open_id')) {
                        if (!$apiCredentials) {
                            $apiCredentials = [];
                        }

	            // Handle YouTube API credentials update
	            if ($channel->platform === 'youtube') {
	                if ($request->boolean('clear_youtube_credentials')) {
	                    $apiCredentials = null;
	                } else {
	                    if (!$apiCredentials) { $apiCredentials = []; }
	                    // Only update fields that are provided
	                    if ($request->filled('youtube_client_id')) {
	                        $apiCredentials['client_id'] = $request->youtube_client_id;
	                        $apiCredentials['updated_at'] = now()->toDateTimeString();
	                        $apiCredentials['updated_manually'] = true;
	                    }
	                    if ($request->filled('youtube_client_secret')) {
	                        $apiCredentials['client_secret'] = $request->youtube_client_secret;
	                        $apiCredentials['updated_at'] = now()->toDateTimeString();
	                        $apiCredentials['updated_manually'] = true;

	            if ($channel->platform === 'youtube' && (
	                $request->boolean('clear_youtube_credentials') ||
	                $request->filled('youtube_client_id') ||
	                $request->filled('youtube_client_secret') ||
	                $request->filled('youtube_refresh_token')
	            )) {
	                $updateData['api_credentials'] = $apiCredentials;
	            }

	                    }
	                    if ($request->filled('youtube_refresh_token')) {
	                        $apiCredentials['refresh_token'] = $request->youtube_refresh_token;
	                        $apiCredentials['updated_at'] = now()->toDateTimeString();
	                        $apiCredentials['updated_manually'] = true;
	                    }
	                }
	            }

                        $apiCredentials['open_id'] = $request->tiktok_open_id;
                        $apiCredentials['updated_at'] = now()->toDateTimeString();
                        $apiCredentials['updated_manually'] = true;
                    }
                }
            }

            $updateData = [
                'name' => $request->name,
                'platform' => $request->platform,
                'channel_id' => $request->channel_id,
                'username' => $request->username,
                'description' => $request->description,
                'logo_config' => $logoConfig,
                'default_privacy' => $request->default_privacy,
                'default_tags' => $defaultTags,
                'default_category' => $request->default_category,
                'auto_upload' => $request->boolean('auto_upload'),
                'is_active' => $request->boolean('is_active', true)
            ];

            // Only update api_credentials if it was modified
            if ($channel->platform === 'tiktok' && (
                $request->boolean('clear_tiktok_credentials') ||
                $request->filled('tiktok_access_token') ||
                $request->filled('tiktok_refresh_token') ||
                $request->filled('tiktok_open_id')
            )) {
                $updateData['api_credentials'] = $apiCredentials;
            }

            $channel->update($updateData);

            // Prepare success message
            $successMessage = "Đã cập nhật kênh {$channel->name} thành công!";

            if ($channel->platform === 'tiktok') {
                if ($request->boolean('clear_tiktok_credentials')) {
                    $successMessage .= " TikTok credentials đã được xóa.";
                } elseif ($request->filled('tiktok_access_token') ||
                         $request->filled('tiktok_refresh_token') ||
                         $request->filled('tiktok_open_id')) {
                    $successMessage .= " TikTok credentials đã được cập nhật.";
                }
            }

            return redirect()->route('admin.channels.show', $channel)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified channel
     */
    public function destroy(Channel $channel)
    {
        try {
            $channelName = $channel->name;

            // Check if channel has pending posts
            $pendingPosts = $channel->scheduledPosts()->where('status', 'pending')->count();
            if ($pendingPosts > 0) {
                return back()->with('error', "Không thể xóa kênh có {$pendingPosts} bài đăng đang chờ.");
            }

            $channel->delete();

            return redirect()->route('admin.channels.index')
                ->with('success', "Đã xóa kênh {$channelName} thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Toggle channel status
     */
    public function toggleStatus(Channel $channel)
    {
        $channel->update(['is_active' => !$channel->is_active]);

        $status = $channel->is_active ? 'kích hoạt' : 'tạm dừng';
        return back()->with('success', "Đã {$status} kênh {$channel->name}!");
    }

    /**
     * Test API connection
     */
    public function testConnection(Channel $channel)
    {
        try {
            if (!$channel->hasValidCredentials()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API credentials'
                ]);
            }

            // Test connection based on platform
            if ($channel->isTikTok()) {
                $result = $this->testTikTokConnection($channel);
            } elseif ($channel->isYouTube()) {
                $result = $this->testYouTubeConnection($channel);
            } else {
                throw new \Exception('Platform không được hỗ trợ');
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get available logos
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
     * Test TikTok API connection
     */
    private function testTikTokConnection(Channel $channel)
    {
        // Delegate to TikTokOAuthController for actual testing
        $oauthController = app(\App\Http\Controllers\Admin\TikTokOAuthController::class);
        $response = $oauthController->testConnection($channel);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response->getData(true);
        }

        return [
            'success' => false,
            'message' => 'Không thể kiểm tra kết nối TikTok'
        ];
    }

    /**
     * Test YouTube API connection (real check with Google API)
     */
    private function testYouTubeConnection(Channel $channel)
    {
        try {
            if (!class_exists('Google_Client')) {
                return [
                    'success' => false,
                    'message' => 'Thiếu Google API Client. Vui lòng cài đặt: composer require google/apiclient:^2.15'
                ];
            }

            $creds = $channel->api_credentials ?: [];
            $clientId = $creds['client_id'] ?? config('services.youtube.client_id');
            $clientSecret = $creds['client_secret'] ?? config('services.youtube.client_secret');
            $refreshToken = $creds['refresh_token'] ?? null;

            if (!$clientId || !$clientSecret || !$refreshToken) {
                return [
                    'success' => false,
                    'message' => 'Thiếu client_id/client_secret/refresh_token cho YouTube',
                    'data' => [
                        'has_client_id' => !empty($clientId),
                        'has_client_secret' => !empty($clientSecret),
                        'has_refresh_token' => !empty($refreshToken),
                    ]
                ];
            }

            $client = new \Google_Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setAccessType('offline');
            $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly']);

            // Try refreshing access token
            $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (!is_array($token) || isset($token['error'])) {
                $err = is_array($token) && isset($token['error']) ? $token['error'] : 'unknown_error';
                return [
                    'success' => false,
                    'message' => 'Lỗi refresh token: ' . $err
                ];
            }

            $client->setAccessToken($token);
            $youtube = new \Google_Service_YouTube($client);

            // Fetch authenticated user's channel info
            $response = $youtube->channels->listChannels('snippet,statistics', ['mine' => true]);
            $items = [];
            if ($response && $response->getItems()) {
                foreach ($response->getItems() as $item) {
                    $snippet = $item->getSnippet();
                    $stats = $item->getStatistics();
                    $items[] = [
                        'channel_id' => $item->getId(),
                        'title' => $snippet ? $snippet->getTitle() : null,
                        'subscriber_count' => $stats ? $stats->getSubscriberCount() : null,
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Kết nối YouTube API thành công',
                'data' => [
                    'token_expires_in' => $token['expires_in'] ?? null,
                    'channels' => $items,
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Lỗi kiểm tra YouTube API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get channels for API (used in video generators)
     */
    public function getChannelsApi()
    {
        $channels = Channel::active()
            ->select('id', 'name', 'platform', 'username', 'logo_config', 'default_privacy', 'default_tags', 'default_category')
            ->get()
            ->map(function($channel) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'platform' => $channel->platform,
                    'username' => $channel->username,
                    'logo_config' => $channel->default_logo_config,
                    'defaults' => [
                        'privacy' => $channel->default_privacy,
                        'tags' => $channel->default_tags,
                        'category' => $channel->default_category
                    ]
                ];
            });

        return response()->json($channels);
    }
}
