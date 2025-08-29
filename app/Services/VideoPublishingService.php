<?php

namespace App\Services;

use App\Models\GeneratedVideo;
use App\Models\VideoPublishing;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class VideoPublishingService
{
    /**
     * Create publishing records for a generated video
     */
    public function createPublishingRecords(GeneratedVideo $video, array $publishingOptions = [])
    {
        Log::info('Creating publishing records for video', [
            'video_id' => $video->id,
            'video_title' => $video->title,
            'publishing_options' => $publishingOptions
        ]);

        $publishingRecords = [];

        // Get platforms from publishing options or default platforms
        $platforms = $publishingOptions['platforms'] ?? ['youtube'];

        foreach ($platforms as $platform) {
            $publishingRecord = $this->createPublishingRecord($video, $platform, $publishingOptions);
            if ($publishingRecord) {
                $publishingRecords[] = $publishingRecord;
            }
        }

        Log::info('Publishing records created', [
            'video_id' => $video->id,
            'records_created' => count($publishingRecords)
        ]);

        return $publishingRecords;
    }

    /**
     * Create a single publishing record for a platform
     */
    private function createPublishingRecord(GeneratedVideo $video, string $platform, array $options = [])
    {
        try {
            // Get channel for this platform
            $channel = $this->getChannelForPlatform($platform, $options);

            // Determine publishing mode and status
            $publishMode = $options['publish_mode'] ?? VideoPublishing::MODE_MANUAL;
            $status = $this->determineInitialStatus($publishMode, $options);

            // Create the publishing record
            $publishing = VideoPublishing::create([
                'generated_video_id' => $video->id,
                'channel_id' => $channel?->id,
                'platform' => $platform,
                'status' => $status,
                'publish_mode' => $publishMode,
                'post_title' => $this->generatePostTitle($video, $platform, $options),
                'post_description' => $this->generatePostDescription($video, $platform, $options),
                'post_tags' => $this->generatePostTags($video, $platform, $options),
                'post_privacy' => $options['privacy'] ?? 'private',
                'post_category' => $options['category'] ?? null,
                'scheduled_at' => $this->getScheduledTime($publishMode, $options),
                'created_by' => auth()->id(),
            ]);

            Log::info('Publishing record created', [
                'publishing_id' => $publishing->id,
                'video_id' => $video->id,
                'platform' => $platform,
                'status' => $status
            ]);

            // If auto-publish is enabled, trigger publishing
            if ($publishMode === VideoPublishing::MODE_AUTO) {
                $this->triggerAutoPublish($publishing);
            }

            return $publishing;

        } catch (\Exception $e) {
            Log::error('Failed to create publishing record', [
                'video_id' => $video->id,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get channel for platform
     */
    private function getChannelForPlatform(string $platform, array $options = [])
    {
        // Check if specific channel is provided
        if (isset($options['channels'][$platform])) {
            return Channel::find($options['channels'][$platform]);
        }

        // Get default channel for platform
        return Channel::where('platform', $platform)
                     ->where('is_active', true)
                     ->first();
    }

    /**
     * Determine initial status based on publish mode
     */
    private function determineInitialStatus(string $publishMode, array $options = [])
    {
        switch ($publishMode) {
            case VideoPublishing::MODE_AUTO:
                return VideoPublishing::STATUS_DRAFT; // Will be changed to publishing immediately
            case VideoPublishing::MODE_SCHEDULED:
                return VideoPublishing::STATUS_SCHEDULED;
            default:
                return VideoPublishing::STATUS_DRAFT;
        }
    }

    /**
     * Generate post title
     */
    private function generatePostTitle(GeneratedVideo $video, string $platform, array $options = [])
    {
        if (isset($options['titles'][$platform])) {
            return $options['titles'][$platform];
        }

        // Use video title as default
        return $video->title ?: 'Video không có tiêu đề';
    }

    /**
     * Generate post description
     */
    private function generatePostDescription(GeneratedVideo $video, string $platform, array $options = [])
    {
        if (isset($options['descriptions'][$platform])) {
            return $options['descriptions'][$platform];
        }

        // Generate default description based on platform
        $description = "Video được tạo tự động";
        
        if ($video->task && $video->task->story) {
            $description = "Truyện: " . $video->task->story->title;
        }

        // Add platform-specific hashtags
        $hashtags = $this->getPlatformHashtags($platform);
        if (!empty($hashtags)) {
            $description .= "\n\n" . implode(' ', $hashtags);
        }

        return $description;
    }

    /**
     * Generate post tags
     */
    private function generatePostTags(GeneratedVideo $video, string $platform, array $options = [])
    {
        if (isset($options['tags'][$platform])) {
            return $options['tags'][$platform];
        }

        $tags = [];

        // Add story-related tags
        if ($video->task && $video->task->story) {
            $tags[] = $video->task->story->category->name ?? 'truyện';
        }

        // Add platform-specific tags
        $platformTags = $this->getPlatformTags($platform);
        $tags = array_merge($tags, $platformTags);

        return array_unique($tags);
    }

    /**
     * Get scheduled time
     */
    private function getScheduledTime(string $publishMode, array $options = [])
    {
        if ($publishMode === VideoPublishing::MODE_SCHEDULED) {
            return $options['scheduled_at'] ?? now()->addHour();
        }

        return null;
    }

    /**
     * Get platform-specific hashtags
     */
    private function getPlatformHashtags(string $platform): array
    {
        $hashtags = [
            'youtube' => ['#truyện', '#audio', '#video'],
            'tiktok' => ['#truyện', '#viral', '#fyp'],
            'facebook' => ['#truyện', '#video', '#entertainment'],
            'instagram' => ['#truyện', '#story', '#video'],
        ];

        return $hashtags[$platform] ?? [];
    }

    /**
     * Get platform-specific tags
     */
    private function getPlatformTags(string $platform): array
    {
        $tags = [
            'youtube' => ['truyện', 'audio book', 'entertainment'],
            'tiktok' => ['truyện', 'viral', 'entertainment'],
            'facebook' => ['truyện', 'video', 'entertainment'],
            'instagram' => ['truyện', 'story', 'video'],
        ];

        return $tags[$platform] ?? [];
    }

    /**
     * Trigger auto-publish (placeholder)
     */
    private function triggerAutoPublish(VideoPublishing $publishing)
    {
        Log::info('Triggering auto-publish', [
            'publishing_id' => $publishing->id,
            'platform' => $publishing->platform
        ]);

        // TODO: Implement actual auto-publishing logic
        // For now, just mark as publishing
        $publishing->markAsPublishing();
    }

    /**
     * Update publishing record with new options
     */
    public function updatePublishingRecord(VideoPublishing $publishing, array $options)
    {
        $updateData = [];

        if (isset($options['post_title'])) {
            $updateData['post_title'] = $options['post_title'];
        }

        if (isset($options['post_description'])) {
            $updateData['post_description'] = $options['post_description'];
        }

        if (isset($options['post_tags'])) {
            $updateData['post_tags'] = $options['post_tags'];
        }

        if (isset($options['post_privacy'])) {
            $updateData['post_privacy'] = $options['post_privacy'];
        }

        if (isset($options['scheduled_at'])) {
            $updateData['scheduled_at'] = $options['scheduled_at'];
            $updateData['status'] = VideoPublishing::STATUS_SCHEDULED;
            $updateData['publish_mode'] = VideoPublishing::MODE_SCHEDULED;
        }

        if (isset($options['channel_id'])) {
            $updateData['channel_id'] = $options['channel_id'];
        }

        if (!empty($updateData)) {
            $publishing->update($updateData);

            Log::info('Publishing record updated', [
                'publishing_id' => $publishing->id,
                'updates' => $updateData
            ]);
        }

        return $publishing;
    }
}
