<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneratedVideo;
use App\Models\Channel;
use App\Services\TikTokApiService;
use Illuminate\Support\Facades\Log;

class PublishVideosToChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:publish-to-channels {--dry-run : Show what would be published without actually publishing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish videos to their assigned channels automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No videos will be actually published');
        }

        // Get videos ready for channel publishing
        $videos = GeneratedVideo::readyForChannelPublish()
                                ->with(['channel'])
                                ->get();

        if ($videos->isEmpty()) {
            $this->info('✅ No videos ready for channel publishing');
            return 0;
        }

        $this->info("📹 Found {$videos->count()} videos ready for channel publishing");

        $successCount = 0;
        $errorCount = 0;

        foreach ($videos as $video) {
            $this->line("Processing: {$video->title}");

            if (!$video->channel) {
                $this->error("❌ No channel assigned for video: {$video->title}");
                $this->updateVideoError($video, 'No channel assigned', $dryRun);
                $errorCount++;
                continue;
            }

            if (!$video->fileExists()) {
                $this->error("❌ Video file not found: {$video->file_path}");
                $this->updateVideoError($video, 'Video file not found', $dryRun);
                $errorCount++;
                continue;
            }

            if ($dryRun) {
                $this->info("🔄 Would publish to channel: {$video->channel->name} ({$video->channel->platform})");
                continue;
            }

            // Publish based on platform
            $result = $this->publishToChannel($video);

            if ($result['success']) {
                $video->update([
                    'channel_published_at' => now(),
                    'channel_publish_error' => null
                ]);

                $this->info("✅ Successfully published: {$video->title}");
                $successCount++;

                Log::info("Video published to channel", [
                    'video_id' => $video->id,
                    'video_title' => $video->title,
                    'channel_id' => $video->channel->id,
                    'channel_name' => $video->channel->name,
                    'platform' => $video->channel->platform
                ]);
            } else {
                $this->updateVideoError($video, $result['error'], false);
                $this->error("❌ Failed to publish: {$video->title} - {$result['error']}");
                $errorCount++;
            }
        }

        if (!$dryRun) {
            $this->info("\n📊 Publishing Summary:");
            $this->info("✅ Successful: {$successCount}");
            $this->info("❌ Failed: {$errorCount}");
        }

        return 0;
    }

    /**
     * Publish video to channel based on platform
     */
    private function publishToChannel(GeneratedVideo $video)
    {
        $channel = $video->channel;

        try {
            switch ($channel->platform) {
                case 'tiktok':
                    return $this->publishToTikTok($video, $channel);

                case 'youtube':
                    return $this->publishToYouTube($video, $channel);

                default:
                    return [
                        'success' => false,
                        'error' => "Unsupported platform: {$channel->platform}"
                    ];
            }
        } catch (\Exception $e) {
            Log::error("Error publishing video to channel", [
                'video_id' => $video->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Publish to TikTok
     */
    private function publishToTikTok(GeneratedVideo $video, Channel $channel)
    {
        $tiktokService = app(TikTokApiService::class);

        // Get access token from channel credentials
        $credentials = $channel->api_credentials;
        if (!isset($credentials['access_token'])) {
            return [
                'success' => false,
                'error' => 'No access token found for TikTok channel'
            ];
        }

        $result = $tiktokService->uploadVideo(
            $credentials['access_token'],
            $video->file_path,
            $video->title,
            $video->description ?? '',
            $channel->default_privacy ?? 'PUBLIC_TO_EVERYONE'
        );

        return $result;
    }

    /**
     * Publish to YouTube
     */
    private function publishToYouTube(GeneratedVideo $video, Channel $channel)
    {
        // TODO: Implement YouTube publishing
        return [
            'success' => false,
            'error' => 'YouTube publishing not implemented yet'
        ];
    }

    /**
     * Update video with error
     */
    private function updateVideoError(GeneratedVideo $video, string $error, bool $dryRun)
    {
        if (!$dryRun) {
            $video->update([
                'channel_publish_error' => $error
            ]);
        }
    }
}
