<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTube;

class YouTubeUploader
{
    private Google_Client $client;
    private Google_Service_YouTube $youtube;

    public function __construct(string $clientId, string $clientSecret, string $refreshToken)
    {
        $this->client = new Google_Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setAccessType('offline');
        $this->client->setScopes(['https://www.googleapis.com/auth/youtube.upload']);
        $this->client->setPrompt('consent');

        // Refresh access token via refresh_token and handle errors explicitly
        $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (!is_array($token) || isset($token['error'])) {
            $err = is_array($token) && isset($token['error']) ? $token['error'] : 'unknown_error';
            throw new \RuntimeException('Failed to refresh YouTube access token: ' . $err);
        }
        // Set the fresh token array
        $this->client->setAccessToken($token);

        $this->youtube = new Google_Service_YouTube($this->client);
    }

    /**
     * Upload a video file to YouTube
     * Returns [success=>bool, post_id=>string|null, url=>string|null, error=>string|null]
     */
    public function upload(string $videoPath, string $title, string $description = '', array $tags = [], string $privacy = 'private', ?string $categoryId = null): array
    {
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Video file không tồn tại: ' . $videoPath];
        }

        $privacyStatus = in_array($privacy, ['public', 'private', 'unlisted']) ? $privacy : 'private';

        $snippet = new \Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);
        if (!empty($tags)) {
            $snippet->setTags($tags);
        }
        if ($categoryId) {
            $snippet->setCategoryId($categoryId);
        }

        $status = new \Google_Service_YouTube_VideoStatus();
        $status->setPrivacyStatus($privacyStatus);

        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        try {
            $chunkSizeBytes = 5 * 1024 * 1024; // 5MB
            $this->client->setDefer(true);

            $insertRequest = $this->youtube->videos->insert('status,snippet', $video);

            $media = new \Google_Http_MediaFileUpload(
                $this->client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));

            $status = false;
            $handle = fopen($videoPath, 'rb');
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);

            $this->client->setDefer(false);

            if ($status instanceof \Google_Service_YouTube_Video) {
                $videoId = $status->getId();
                return [
                    'success' => true,
                    'post_id' => $videoId,
                    'url' => 'https://www.youtube.com/watch?v=' . $videoId
                ];
            }

            return ['success' => false, 'error' => 'Không nhận được phản hồi video từ API'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

