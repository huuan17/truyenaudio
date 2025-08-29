<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Channel;
use Illuminate\Support\Facades\URL;

class YoutubeAuthController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen to connect YouTube
     */
    public function connect(Request $request, Channel $channel)
    {
        if (!$channel->isYouTube()) {
            return redirect()->route('admin.channels.edit', $channel)
                ->with('error', 'Chỉ hỗ trợ kết nối OAuth cho kênh YouTube.');
        }

        // Check dependency
        if (!class_exists('Google_Client')) {
            return redirect()->route('admin.channels.edit', $channel)
                ->with('error', 'Thiếu thư viện Google API Client. Vui lòng cài đặt: composer require google/apiclient:^2.15');
        }

        // Prefer per-channel OAuth client if available in DB, otherwise fallback to .env
        $creds = $channel->api_credentials ?: [];
        $clientId = $creds['client_id'] ?? config('services.youtube.client_id');
        $clientSecret = $creds['client_secret'] ?? config('services.youtube.client_secret');
        $redirectUri = route('admin.channels.youtube.callback');

        if (!$clientId || !$clientSecret) {
            return redirect()->route('admin.channels.edit', $channel)
                ->with('error', 'Thiếu client_id/client_secret cho YouTube (trong DB hoặc .env).');
        }

        $client = new \Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline'); // to get refresh_token
        $client->setPrompt('consent'); // force consent to get refresh_token
        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.upload',
        ]);

        // Persist channel id in session and state
        $request->session()->put('youtube_oauth_channel_id', $channel->id);
        $state = (string) $channel->id;
        $client->setState($state);

        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    /**
     * OAuth callback: exchange code for tokens and store refresh_token in channel
     */
    public function callback(Request $request)
    {
        $error = $request->query('error');
        if ($error) {
            return $this->finishWithFlash('error', 'Người dùng đã hủy hoặc lỗi OAuth: ' . $error);
        }

        if (!class_exists('Google_Client')) {
            return $this->finishWithFlash('error', 'Thiếu thư viện Google API Client. Vui lòng cài đặt: composer require google/apiclient:^2.15');
        }

        $code = $request->query('code');
        if (!$code) {
            return $this->finishWithFlash('error', 'Thiếu mã xác thực OAuth (code).');
        }

        // Prefer client from channel DB if exists (must match refresh flow)
        $channelIdFromState = (int) ($request->query('state') ?? $request->session()->get('youtube_oauth_channel_id'));
        $channel = $channelIdFromState ? Channel::find($channelIdFromState) : null;
        $creds = $channel && $channel->api_credentials ? $channel->api_credentials : [];

        $clientId = $creds['client_id'] ?? config('services.youtube.client_id');
        $clientSecret = $creds['client_secret'] ?? config('services.youtube.client_secret');
        $redirectUri = route('admin.channels.youtube.callback');

        if (!$clientId || !$clientSecret) {
            return $this->finishWithFlash('error', 'Thiếu client_id/client_secret cho YouTube (trong DB hoặc .env).');
        }

        $client = new \Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Throwable $e) {
            return $this->finishWithFlash('error', 'Không đổi code lấy token được: ' . $e->getMessage());
        }

        if (isset($token['error'])) {
            return $this->finishWithFlash('error', 'Lỗi OAuth: ' . ($token['error_description'] ?? $token['error']));
        }

        $refreshToken = $token['refresh_token'] ?? null;
        if (!$refreshToken) {
            // Sometimes Google only returns access_token on subsequent consent. Try to refresh using current token if any.
            return $this->finishWithFlash('warning', 'Không nhận được refresh_token. Hãy chọn "consent" lần đầu hoặc xóa cấp quyền cũ trong Google Account rồi thử lại.');
        }

        // Determine channel
        $channelId = (int) ($request->query('state') ?? $request->session()->pull('youtube_oauth_channel_id'));
        $channel = Channel::find($channelId);
        if (!$channel) {
            return $this->finishWithFlash('error', 'Không xác định được kênh để lưu token.');
        }
        if (!$channel->isYouTube()) {
            return $this->finishWithFlash('error', 'Kênh không phải YouTube.');
        }

        // Merge credentials and save
        $creds = $channel->api_credentials ?: [];
        $creds['client_id'] = $clientId;
        $creds['client_secret'] = $clientSecret;
        $creds['refresh_token'] = $refreshToken;
        $channel->api_credentials = $creds;
        $channel->save();

        return redirect()->route('admin.channels.edit', $channel)
            ->with('success', 'Kết nối YouTube thành công! Đã lưu refresh_token.');
    }

    private function finishWithFlash(string $level, string $message)
    {
        // Try to get channel from session to redirect nicely
        $channelId = session()->pull('youtube_oauth_channel_id');
        if ($channelId && ($channel = Channel::find($channelId))) {
            return redirect()->route('admin.channels.edit', $channel)->with($level, $message);
        }
        return redirect()->route('admin.channels.index')->with($level, $message);
    }
}

