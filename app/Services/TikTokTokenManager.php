<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class TikTokTokenManager
{
    private $tikTokService;

    public function __construct(TikTokApiService $tikTokService)
    {
        $this->tikTokService = $tikTokService;
    }

    /**
     * Đảm bảo access token còn hiệu lực
     */
    public function ensureValidToken(Channel $channel)
    {
        if ($channel->platform !== 'tiktok') {
            return [
                'success' => false,
                'error' => 'Channel không phải TikTok'
            ];
        }

        $credentials = $channel->api_credentials;
        
        if (!$credentials || !isset($credentials['access_token'])) {
            return [
                'success' => false,
                'error' => 'Không có access token'
            ];
        }

        // Kiểm tra token có hết hạn không (với buffer 5 phút)
        if (isset($credentials['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($credentials['expires_at']);
            $bufferTime = now()->addMinutes(5);
            
            if ($expiresAt->lt($bufferTime)) {
                Log::info('TikTok token sắp hết hạn, đang refresh...', [
                    'channel_id' => $channel->id,
                    'expires_at' => $expiresAt->toDateTimeString()
                ]);
                
                return $this->refreshToken($channel);
            }
        }

        return [
            'success' => true,
            'access_token' => $credentials['access_token']
        ];
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Channel $channel)
    {
        try {
            $credentials = $channel->api_credentials;
            
            if (!isset($credentials['refresh_token'])) {
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

                Log::info('TikTok token refreshed successfully', [
                    'channel_id' => $channel->id,
                    'new_expires_at' => $credentials['expires_at']
                ]);

                return [
                    'success' => true,
                    'access_token' => $credentials['access_token']
                ];
            } else {
                Log::error('TikTok token refresh failed', [
                    'channel_id' => $channel->id,
                    'error' => $refreshResult['error']
                ]);

                return [
                    'success' => false,
                    'error' => $refreshResult['error']
                ];
            }

        } catch (\Exception $e) {
            Log::error('TikTok token refresh exception', [
                'channel_id' => $channel->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kiểm tra token có hợp lệ không
     */
    public function validateToken(Channel $channel)
    {
        $tokenResult = $this->ensureValidToken($channel);
        
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        // Test token bằng cách gọi user info API
        $userResult = $this->tikTokService->getUserInfo($tokenResult['access_token']);

        if ($userResult['success']) {
            return [
                'success' => true,
                'message' => 'Token hợp lệ',
                'user_info' => $userResult['user']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Token không hợp lệ: ' . $userResult['error']
            ];
        }
    }

    /**
     * Revoke token (disconnect)
     */
    public function revokeToken(Channel $channel)
    {
        try {
            // TikTok không có revoke endpoint trong API hiện tại
            // Chỉ cần xóa credentials khỏi database
            
            $channel->update([
                'api_credentials' => null,
                'is_active' => false
            ]);

            Log::info('TikTok token revoked', [
                'channel_id' => $channel->id
            ]);

            return [
                'success' => true,
                'message' => 'Đã ngắt kết nối TikTok thành công'
            ];

        } catch (\Exception $e) {
            Log::error('TikTok token revoke exception', [
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
     * Lấy thông tin token hiện tại
     */
    public function getTokenInfo(Channel $channel)
    {
        $credentials = $channel->api_credentials;
        
        if (!$credentials || !isset($credentials['access_token'])) {
            return [
                'success' => false,
                'error' => 'Không có token'
            ];
        }

        $info = [
            'has_token' => true,
            'scope' => $credentials['scope'] ?? null,
            'open_id' => $credentials['open_id'] ?? null,
        ];

        if (isset($credentials['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($credentials['expires_at']);
            $info['expires_at'] = $expiresAt->toDateTimeString();
            $info['expires_in_minutes'] = $expiresAt->diffInMinutes(now());
            $info['is_expired'] = $expiresAt->lt(now());
            $info['expires_soon'] = $expiresAt->lt(now()->addMinutes(30));
        }

        if (isset($credentials['user_info'])) {
            $info['user_info'] = $credentials['user_info'];
        }

        return [
            'success' => true,
            'data' => $info
        ];
    }
}
