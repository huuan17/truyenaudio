<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    
    protected function schedule(Schedule $schedule): void
    {
        // Update crawl status every hour
        $schedule->command('stories:update-crawl-status')->hourly();

        // Cancel stuck TTS requests every 30 minutes
        $schedule->call(function () {
            \App\Models\Chapter::where('audio_status', 'processing')
                ->where('tts_started_at', '<', now()->subMinutes(30))
                ->update([
                    'audio_status' => 'error',
                    'tts_error' => 'Timeout - auto cancelled'
                ]);
        })->everyThirtyMinutes();

        // Cleanup failed crawl jobs mỗi 6 giờ
        $schedule->command('crawl:manage clear')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Auto-monitor và recovery stuck crawl jobs mỗi 30 phút
        $schedule->command('crawl:monitor auto --timeout=120')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Crawl monitoring completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Crawl monitoring failed');
            });

        // Auto-update story status mỗi giờ
        $schedule->command('stories:update-status')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Story status update completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Story status update failed');
            });

        // Auto-fix incorrect crawl status mỗi 2 giờ
        $schedule->command('crawl:fix-status')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Crawl status fix completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Crawl status fix failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
