<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class CheckAutoCrawlStatus extends Command
{
    protected $signature = 'crawl:check-auto {--fix : Fix issues automatically}';
    protected $description = 'Check auto crawl system status and diagnose issues';

    public function handle()
    {
        $this->info('🔍 Checking Auto Crawl System Status...');
        $this->newLine();

        // 1. Check queue configuration
        $this->checkQueueConfig();
        
        // 2. Check stories with auto_crawl enabled
        $this->checkAutoCrawlStories();
        
        // 3. Check pending jobs
        $this->checkPendingJobs();
        
        // 4. Check failed jobs
        $this->checkFailedJobs();
        
        // 5. Check queue worker status
        $this->checkQueueWorkerStatus();
        
        // 6. Provide recommendations
        $this->provideRecommendations();
    }

    private function checkQueueConfig()
    {
        $this->info('📋 1. Queue Configuration:');
        
        $queueConnection = config('queue.default');
        $this->line("   Default connection: {$queueConnection}");
        
        if ($queueConnection === 'sync') {
            $this->warn('   ⚠️  Queue is set to SYNC - jobs will run immediately, not in background');
        } else {
            $this->info('   ✅ Queue is properly configured for background processing');
        }
        
        $this->newLine();
    }

    private function checkAutoCrawlStories()
    {
        $this->info('📚 2. Stories with Auto Crawl:');
        
        $autoCrawlStories = Story::where('auto_crawl', true)->get();
        
        if ($autoCrawlStories->isEmpty()) {
            $this->warn('   ⚠️  No stories have auto_crawl enabled');
        } else {
            $this->info("   Found {$autoCrawlStories->count()} stories with auto_crawl enabled:");
            
            foreach ($autoCrawlStories as $story) {
                $statusText = $this->getCrawlStatusText($story->crawl_status);
                $this->line("   - ID: {$story->id} | {$story->title} | Status: {$statusText}");
            }
        }
        
        $this->newLine();
    }

    private function checkPendingJobs()
    {
        $this->info('⏳ 3. Pending Jobs:');
        
        $pendingJobs = DB::table('jobs')->where('queue', 'crawl')->count();
        $allPendingJobs = DB::table('jobs')->count();
        
        $this->line("   Crawl queue: {$pendingJobs} jobs");
        $this->line("   All queues: {$allPendingJobs} jobs");
        
        if ($pendingJobs > 0) {
            $this->warn("   ⚠️  There are {$pendingJobs} crawl jobs waiting to be processed");
            $this->line("   💡 You need to start a queue worker to process these jobs");
        } else {
            $this->info('   ✅ No pending crawl jobs');
        }
        
        $this->newLine();
    }

    private function checkFailedJobs()
    {
        $this->info('❌ 4. Failed Jobs:');
        
        $failedJobs = DB::table('failed_jobs')->count();
        
        if ($failedJobs > 0) {
            $this->warn("   ⚠️  There are {$failedJobs} failed jobs");
            
            if ($this->option('fix')) {
                $this->line('   🔧 Clearing failed jobs...');
                DB::table('failed_jobs')->delete();
                $this->info('   ✅ Failed jobs cleared');
            } else {
                $this->line('   💡 Run with --fix to clear failed jobs');
            }
        } else {
            $this->info('   ✅ No failed jobs');
        }
        
        $this->newLine();
    }

    private function checkQueueWorkerStatus()
    {
        $this->info('🔄 5. Queue Worker Status:');
        
        // Check if there are any recent job executions
        $recentJobs = DB::table('jobs')
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
            
        if ($recentJobs > 0) {
            $this->info('   ✅ Queue appears to be active (recent jobs found)');
        } else {
            $this->warn('   ⚠️  No recent queue activity detected');
            $this->line('   💡 Queue worker may not be running');
        }
        
        $this->newLine();
    }

    private function provideRecommendations()
    {
        $this->info('💡 6. Recommendations:');
        
        $queueConnection = config('queue.default');
        $pendingJobs = DB::table('jobs')->where('queue', 'crawl')->count();
        $autoCrawlStories = Story::where('auto_crawl', true)->count();
        
        if ($queueConnection === 'sync') {
            $this->warn('   🔧 Change QUEUE_CONNECTION from "sync" to "database" in .env file');
        }
        
        if ($pendingJobs > 0) {
            $this->warn('   🔧 Start queue worker: php artisan queue:work --queue=crawl');
            $this->line('   📝 Or use: start-queue-worker.bat and choose option 2');
        }
        
        if ($autoCrawlStories === 0) {
            $this->warn('   🔧 Enable auto_crawl for stories in admin panel');
        }
        
        $this->info('   📋 To start auto crawl manually: php artisan auto:crawl-stories');
        $this->info('   🧪 To test auto crawl: php artisan auto:crawl-stories --dry-run');
        
        $this->newLine();
        $this->info('🎯 Quick Start Guide:');
        $this->line('   1. Run: start-queue-worker.bat');
        $this->line('   2. Choose option 2 (Crawl queue only)');
        $this->line('   3. Keep the window open to process crawl jobs');
        $this->line('   4. Enable auto_crawl for stories in admin panel');
    }

    private function getCrawlStatusText($status)
    {
        $statuses = [
            0 => 'Not Crawled',
            1 => 'Crawling',
            2 => 'Completed',
            3 => 'Failed',
            4 => 'Cancelled'
        ];
        
        return $statuses[$status] ?? 'Unknown';
    }
}
