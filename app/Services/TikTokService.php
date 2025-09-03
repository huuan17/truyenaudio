<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TikTokService
{
    private $clientKey;
    private $clientSecret;
    private $redirectUri;
    private $sandbox;
    private $apiVersion;
    private $baseUrl;

    public function __construct($clientKey = null, $clientSecret = null, $redirectUri = null)
    {
        $this->clientKey = $clientKey ?: config('services.tiktok.client_id');
        $this->clientSecret = $clientSecret ?: config('services.tiktok.client_secret');
        $this->redirectUri = $redirectUri ?: config('services.tiktok.redirect_uri');
        $this->sandbox = config('services.tiktok.sandbox', false); // Mặc định sử dụng production
        $this->apiVersion = config('services.tiktok.api_version', 'v2');

        // Sử dụng production URL để tránh lỗi sandbox
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox-open-api.tiktok.com'
            : 'https://open-api.tiktok.com';
    }

    /**
     * Tạo authorization URL với PKCE support
     */
    public function getAuthorizationUrl($state = null)
    {
        // Generate PKCE parameters
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Store code verifier in session for later use
        session(['tiktok_code_verifier' => $codeVerifier]);

        $params = [
            'client_key' => $this->clientKey,
            'scope' => 'video.upload,video.publish,user.info.basic',
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'state' => $state ?: uniqid(),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ];

        $queryString = http_build_query($params);

        // Sử dụng production URL để tránh lỗi sandbox
        return $this->sandbox
            ? "https://sandbox-www.tiktok.com/v2/auth/authorize?{$queryString}"
            : "https://www.tiktok.com/v2/auth/authorize?{$queryString}";
    }

    /**
     * Generate code verifier for PKCE
     */
    private function generateCodeVerifier()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
        $length = random_int(43, 128); // RFC 7636 specifies 43-128 characters
        $codeVerifier = '';

        for ($i = 0; $i < $length; $i++) {
            $codeVerifier .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $codeVerifier;
    }

    /**
     * Generate code challenge from code verifier
     */
    private function generateCodeChallenge($codeVerifier)
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Exchange authorization code cho access token với PKCE
     */
    public function getAccessToken($code)
    {
        try {
            // Get code verifier from session
            $codeVerifier = session('tiktok_code_verifier');

            if (!$codeVerifier) {
                return [
                    'success' => false,
                    'error' => 'Code verifier not found in session'
                ];
            }

            $requestData = [
                'client_key' => $this->clientKey,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'code_verifier' => $codeVerifier
            ];

            $response = Http::asForm()->post("{$this->baseUrl}/v2/oauth/token/", $requestData);

            // Clear code verifier from session
            session()->forget('tiktok_code_verifier');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    return [
                        'success' => true,
                        'access_token' => $data['data']['access_token'],
                        'refresh_token' => $data['data']['refresh_token'],
                        'expires_in' => $data['data']['expires_in'],
                        'scope' => $data['data']['scope'],
                        'open_id' => $data['data']['open_id'],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to get access token',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken($refreshToken)
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/v2/oauth/token/", [
                'client_key' => $this->clientKey,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    return [
                        'success' => true,
                        'access_token' => $data['data']['access_token'],
                        'refresh_token' => $data['data']['refresh_token'],
                        'expires_in' => $data['data']['expires_in'],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to refresh token',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin user profile
     */
    public function getUserInfo($accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post("{$this->baseUrl}/v2/user/info/", [
                'fields' => 'open_id,union_id,avatar_url,display_name,username'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']['user'])) {
                    return [
                        'success' => true,
                        'user' => $data['data']['user']
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to get user info',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload video lên TikTok
     */
    public function uploadVideo($accessToken, $videoPath, $title, $description = '', $privacy = 'PUBLIC_TO_EVERYONE')
    {
        try {
            // Validate inputs
            $validation = $this->validateUploadInputs($videoPath, $title, $description);
            if (!$validation['success']) {
                return $validation;
            }

            // Bước 1: Khởi tạo upload session
            $initResponse = $this->initializeUpload($accessToken, $videoPath);

            if (!$initResponse['success']) {
                return $initResponse;
            }

            $uploadUrl = $initResponse['upload_url'];
            $publishId = $initResponse['publish_id'];

            // Bước 2: Upload video file
            $uploadResponse = $this->uploadVideoFile($uploadUrl, $videoPath);

            if (!$uploadResponse['success']) {
                return $uploadResponse;
            }

            // Bước 3: Publish video
            $publishResponse = $this->publishVideo($accessToken, $publishId, $title, $description, $privacy);

            return $publishResponse;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate upload inputs
     */
    private function validateUploadInputs($videoPath, $title, $description)
    {
        // Kiểm tra file tồn tại
        if (!file_exists($videoPath)) {
            return [
                'success' => false,
                'error' => 'Video file không tồn tại: ' . $videoPath
            ];
        }

        // Kiểm tra kích thước file (max 500MB)
        $fileSize = filesize($videoPath);
        $maxSize = 500 * 1024 * 1024; // 500MB
        if ($fileSize > $maxSize) {
            return [
                'success' => false,
                'error' => 'File quá lớn. Kích thước tối đa: 500MB, file hiện tại: ' . round($fileSize / 1024 / 1024, 2) . 'MB'
            ];
        }

        // Kiểm tra định dạng file
        $allowedExtensions = ['mp4', 'mov', 'webm'];
        $extension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'success' => false,
                'error' => 'Định dạng file không được hỗ trợ. Chỉ chấp nhận: ' . implode(', ', $allowedExtensions)
            ];
        }

        // Kiểm tra title
        if (empty(trim($title))) {
            return [
                'success' => false,
                'error' => 'Title không được để trống'
            ];
        }

        if (strlen($title) > 150) {
            return [
                'success' => false,
                'error' => 'Title quá dài. Tối đa 150 ký tự, hiện tại: ' . strlen($title)
            ];
        }

        // Kiểm tra description
        if (strlen($description) > 2200) {
            return [
                'success' => false,
                'error' => 'Description quá dài. Tối đa 2200 ký tự, hiện tại: ' . strlen($description)
            ];
        }

        return ['success' => true];
    }

    /**
     * Khởi tạo upload session
     */
    private function initializeUpload($accessToken, $videoPath)
    {
        try {
            $fileSize = filesize($videoPath);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v2/post/publish/video/init/", [
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $fileSize,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    return [
                        'success' => true,
                        'upload_url' => $data['data']['upload_url'],
                        'publish_id' => $data['data']['publish_id'],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to initialize upload',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload video file
     */
    private function uploadVideoFile($uploadUrl, $videoPath)
    {
        try {
            $response = Http::attach(
                'video', file_get_contents($videoPath), basename($videoPath)
            )->post($uploadUrl);

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => 'Failed to upload video file',
                'details' => $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Publish video
     */
    private function publishVideo($accessToken, $publishId, $title, $description, $privacy)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v2/post/publish/", [
                'publish_id' => $publishId,
                'post_info' => [
                    'title' => $title,
                    'description' => $description,
                    'privacy_level' => $privacy,
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                    'video_cover_timestamp_ms' => 1000,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    return [
                        'success' => true,
                        'publish_id' => $data['data']['publish_id'],
                        'share_url' => $data['data']['share_url'] ?? null,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to publish video',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kiểm tra trạng thái publish
     */
    public function getPublishStatus($accessToken, $publishId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post("{$this->baseUrl}/v2/post/publish/status/fetch/", [
                'publish_id' => $publishId
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    return [
                        'success' => true,
                        'status' => $data['data']['status'],
                        'fail_reason' => $data['data']['fail_reason'] ?? null,
                        'publicly_available_post_id' => $data['data']['publicly_available_post_id'] ?? null,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to get publish status',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
