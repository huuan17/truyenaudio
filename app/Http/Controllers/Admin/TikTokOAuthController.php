<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TikTokApiService;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokOAuthController extends Controller
{
    private $tikTokService;

    public function __construct(TikTokApiService $tikTokService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->tikTokService = $tikTokService;
    }

    /**
     * Redirect user to TikTok authorization page
     */
    public function redirectToTikTok(Request $request)
    {
        $channelId = $request->get('channel_id');
        
        if (!$channelId) {
            return redirect()->route('admin.channels.index')
                ->with('error', 'Channel ID không hợp lệ');
        }

        $channel = Channel::find($channelId);
        if (!$channel || $channel->platform !== 'tiktok') {
            return redirect()->route('admin.channels.index')
                ->with('error', 'Kênh TikTok không tồn tại');
        }

        // Tạo state để verify callback
        $state = Str::random(32);
        session(['tiktok_oauth_state' => $state, 'tiktok_channel_id' => $channelId]);

        $authUrl = $this->tikTokService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle TikTok OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            // Verify state parameter
            $state = $request->get('state');
            $sessionState = session('tiktok_oauth_state');
            $channelId = session('tiktok_channel_id');

            if (!$state || !$sessionState || $state !== $sessionState) {
                return redirect()->route('admin.channels.index')
                    ->with('error', 'OAuth state không hợp lệ');
            }

            if (!$channelId) {
                return redirect()->route('admin.channels.index')
                    ->with('error', 'Channel ID không tồn tại trong session');
            }

            $channel = Channel::find($channelId);
            if (!$channel) {
                return redirect()->route('admin.channels.index')
                    ->with('error', 'Kênh không tồn tại');
            }

            // Check for authorization code
            $code = $request->get('code');
            if (!$code) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description');
                
                Log::warning('TikTok OAuth Error', [
                    'error' => $error,
                    'description' => $errorDescription
                ]);

                return redirect()->route('admin.channels.show', $channel)
                    ->with('error', 'Người dùng từ chối ủy quyền hoặc có lỗi xảy ra: ' . ($errorDescription ?: $error));
            }

            // Exchange code for access token
            $tokenResult = $this->tikTokService->getAccessToken($code);

            if (!$tokenResult['success']) {
                Log::error('TikTok Token Exchange Failed', $tokenResult);
                
                return redirect()->route('admin.channels.show', $channel)
                    ->with('error', 'Không thể lấy access token: ' . $tokenResult['error']);
            }

            // Get user info
            $userResult = $this->tikTokService->getUserInfo($tokenResult['access_token']);
            
            if (!$userResult['success']) {
                Log::warning('TikTok User Info Failed', $userResult);
                // Continue anyway, user info is not critical
            }

            // Update channel with credentials
            $apiCredentials = [
                'access_token' => $tokenResult['access_token'],
                'refresh_token' => $tokenResult['refresh_token'],
                'expires_in' => $tokenResult['expires_in'],
                'expires_at' => now()->addSeconds($tokenResult['expires_in']),
                'scope' => $tokenResult['scope'],
                'open_id' => $tokenResult['open_id'],
            ];

            // Add user info if available
            if ($userResult['success']) {
                $user = $userResult['user'];
                $apiCredentials['user_info'] = $user;
                
                // Update channel info
                $channel->update([
                    'channel_id' => $user['open_id'],
                    'username' => $user['username'] ?? $user['display_name'] ?? null,
                    'api_credentials' => $apiCredentials,
                ]);
            } else {
                $channel->update([
                    'api_credentials' => $apiCredentials,
                ]);
            }

            // Clear session
            session()->forget(['tiktok_oauth_state', 'tiktok_channel_id']);

            return redirect()->route('admin.channels.show', $channel)
                ->with('success', 'Đã kết nối TikTok thành công! Kênh đã sẵn sàng để upload video.');

        } catch (\Exception $e) {
            Log::error('TikTok OAuth Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.channels.index')
                ->with('error', 'Có lỗi xảy ra trong quá trình xác thực: ' . $e->getMessage());
        }
    }

    /**
     * Test TikTok connection
     */
    public function testConnection(Channel $channel)
    {
        try {
            if ($channel->platform !== 'tiktok') {
                return response()->json([
                    'success' => false,
                    'error' => 'Kênh này không phải là TikTok'
                ]);
            }

            $credentials = $channel->api_credentials;
            if (!$credentials || !isset($credentials['access_token'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kênh chưa được kết nối với TikTok'
                ]);
            }

            // Check if token is expired
            if (isset($credentials['expires_at']) && now()->gt($credentials['expires_at'])) {
                // Try to refresh token
                $refreshResult = $this->refreshTokenInternal($channel);
                if (!$refreshResult['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Access token đã hết hạn và không thể refresh: ' . $refreshResult['error']
                    ]);
                }
                $credentials = $channel->fresh()->api_credentials;
            }

            // Test connection by getting user info
            $userResult = $this->tikTokService->getUserInfo($credentials['access_token']);

            if ($userResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kết nối TikTok thành công',
                    'data' => [
                        'platform' => 'TikTok',
                        'username' => $userResult['user']['username'] ?? $userResult['user']['display_name'],
                        'display_name' => $userResult['user']['display_name'],
                        'open_id' => $userResult['user']['open_id'],
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Không thể kết nối với TikTok: ' . $userResult['error']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TikTok Connection Test Exception', [
                'channel_id' => $channel->id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh access token (AJAX endpoint)
     */
    public function refreshToken(Channel $channel)
    {
        try {
            if ($channel->platform !== 'tiktok') {
                return response()->json([
                    'success' => false,
                    'error' => 'Kênh này không phải là TikTok'
                ]);
            }

            $result = $this->refreshTokenInternal($channel);
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('TikTok Token Refresh AJAX Exception', [
                'channel_id' => $channel->id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Start OAuth flow for new channel creation
     */
    public function startOAuthForNewChannel(Request $request)
    {
        try {
            $request->validate([
                'client_key' => 'required|string',
                'client_secret' => 'required|string'
            ]);

            // Store client credentials in session for OAuth flow
            $state = Str::random(32);
            session([
                'tiktok_oauth_state' => $state,
                'tiktok_new_channel_client_key' => $request->client_key,
                'tiktok_new_channel_client_secret' => $request->client_secret,
                'tiktok_oauth_for_new_channel' => true
            ]);

            // Create temporary TikTok service with provided credentials
            $tempService = new \App\Services\TikTokService(
                $request->client_key,
                $request->client_secret,
                route('admin.channels.tiktok.oauth.callback') // Use new callback route
            );

            $authUrl = $tempService->getAuthorizationUrl($state);

            Log::info('TikTok OAuth URL Generated with PKCE', [
                'client_key' => $request->client_key,
                'redirect_uri' => route('admin.channels.tiktok.oauth.callback'),
                'auth_url' => $authUrl,
                'state' => $state,
                'has_code_verifier' => session()->has('tiktok_code_verifier')
            ]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);

        } catch (\Exception $e) {
            Log::error('TikTok OAuth Start for New Channel Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle OAuth callback for new channel creation
     */
    public function callbackForNewChannel(Request $request)
    {
        try {
            Log::info('TikTok OAuth Callback Received', [
                'all_params' => $request->all(),
                'session_state' => session('tiktok_oauth_state'),
                'session_for_new_channel' => session('tiktok_oauth_for_new_channel'),
                'has_code_verifier' => session()->has('tiktok_code_verifier')
            ]);

            // Verify state parameter
            $state = $request->get('state');
            $sessionState = session('tiktok_oauth_state');
            $isForNewChannel = session('tiktok_oauth_for_new_channel');

            if (!$state || !$sessionState || $state !== $sessionState || !$isForNewChannel) {
                Log::warning('TikTok OAuth State Mismatch', [
                    'received_state' => $state,
                    'session_state' => $sessionState,
                    'is_for_new_channel' => $isForNewChannel
                ]);

                return redirect()->route('admin.channels.create')
                    ->with('error', 'OAuth state không hợp lệ');
            }

            // Get stored credentials
            $clientKey = session('tiktok_new_channel_client_key');
            $clientSecret = session('tiktok_new_channel_client_secret');

            if (!$clientKey || !$clientSecret) {
                return redirect()->route('admin.channels.create')
                    ->with('error', 'Thiếu thông tin client credentials');
            }

            // Check for authorization code
            $code = $request->get('code');
            if (!$code) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description');

                Log::warning('TikTok OAuth Error for New Channel', [
                    'error' => $error,
                    'description' => $errorDescription
                ]);

                return redirect()->route('admin.channels.create')
                    ->with('error', 'Người dùng từ chối ủy quyền hoặc có lỗi xảy ra: ' . ($errorDescription ?: $error));
            }

            // Create temporary TikTok service
            $tempService = new \App\Services\TikTokService(
                $clientKey,
                $clientSecret,
                route('admin.channels.tiktok.oauth.callback')
            );

            // Exchange code for access token
            $tokenResult = $tempService->getAccessToken($code);

            if (!$tokenResult['success']) {
                Log::error('TikTok Token Exchange Failed for New Channel', $tokenResult);

                return redirect()->route('admin.channels.create')
                    ->with('error', 'Không thể lấy access token: ' . $tokenResult['error']);
            }

            // Clear session data
            session()->forget([
                'tiktok_oauth_state',
                'tiktok_new_channel_client_key',
                'tiktok_new_channel_client_secret',
                'tiktok_oauth_for_new_channel'
            ]);

            // Redirect back to create form with tokens
            return redirect()->route('admin.channels.create', [
                'oauth_success' => 1,
                'access_token' => $tokenResult['access_token'],
                'refresh_token' => $tokenResult['refresh_token']
            ])->with('success', 'Đã lấy được Access Token và Refresh Token từ TikTok thành công!');

        } catch (\Exception $e) {
            Log::error('TikTok OAuth Callback Exception for New Channel', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.channels.create')
                ->with('error', 'Có lỗi xảy ra trong quá trình OAuth: ' . $e->getMessage());
        }
    }

    /**
     * Internal refresh token method
     */
    private function refreshTokenInternal(Channel $channel)
    {
        try {
            $credentials = $channel->api_credentials;
            if (!$credentials || !isset($credentials['refresh_token'])) {
                return [
                    'success' => false,
                    'error' => 'Không có refresh token'
                ];
            }

            $refreshResult = $this->tikTokService->refreshAccessToken($credentials['refresh_token']);

            if ($refreshResult['success']) {
                // Update credentials
                $credentials['access_token'] = $refreshResult['access_token'];
                $credentials['refresh_token'] = $refreshResult['refresh_token'];
                $credentials['expires_in'] = $refreshResult['expires_in'];
                $credentials['expires_at'] = now()->addSeconds($refreshResult['expires_in']);

                $channel->update(['api_credentials' => $credentials]);

                return [
                    'success' => true,
                    'message' => 'Token đã được refresh thành công'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $refreshResult['error']
                ];
            }

        } catch (\Exception $e) {
            Log::error('TikTok Token Refresh Exception', [
                'channel_id' => $channel->id,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Disconnect TikTok account
     */
    public function disconnect(Channel $channel)
    {
        try {
            if ($channel->platform !== 'tiktok') {
                return redirect()->back()
                    ->with('error', 'Kênh này không phải là TikTok');
            }

            $channel->update([
                'api_credentials' => null,
                'is_active' => false
            ]);

            return redirect()->route('admin.channels.show', $channel)
                ->with('success', 'Đã ngắt kết nối TikTok thành công');

        } catch (\Exception $e) {
            Log::error('TikTok Disconnect Exception', [
                'channel_id' => $channel->id,
                'message' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Get TikTok Channel ID from user info
     */
    public function getChannelId(Request $request)
    {
        try {
            $request->validate([
                'access_token' => 'required|string',
                'client_key' => 'required|string',
                'client_secret' => 'required|string'
            ]);

            // Create temporary TikTok service
            $tempService = new \App\Services\TikTokService(
                $request->client_key,
                $request->client_secret
            );

            // Get user info
            $userResult = $tempService->getUserInfo($request->access_token);

            if (!$userResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Không thể lấy thông tin user: ' . $userResult['error']
                ]);
            }

            $user = $userResult['user'];

            // Extract channel info
            $channelId = $user['open_id'] ?? null;
            $username = $user['username'] ?? $user['display_name'] ?? null;

            if (!$channelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Không tìm thấy Channel ID trong thông tin user'
                ]);
            }

            return response()->json([
                'success' => true,
                'channel_id' => $channelId,
                'username' => $username,
                'user_info' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('TikTok Get Channel ID Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
