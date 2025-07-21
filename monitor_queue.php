<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUEUE MONITOR ===\n";
echo "Press Ctrl+C to stop monitoring\n\n";

$lastJobCount = -1;
$lastStoryStatus = [];

while (true) {
    $currentTime = date('H:i:s');
    
    // Check queue
    $jobCount = DB::table('jobs')->count();
    
    // Check stories with crawl jobs
    $storiesWithJobs = DB::table('stories')
        ->whereNotNull('crawl_job_id')
        ->select('id', 'title', 'crawl_status', 'crawl_job_id')
        ->get()
        ->keyBy('id')
        ->toArray();
    
    // Only print if something changed
    if ($jobCount != $lastJobCount || $storiesWithJobs != $lastStoryStatus) {
        echo "[$currentTime] Jobs in queue: $jobCount\n";
        
        if (count($storiesWithJobs) > 0) {
            echo "Stories with active crawl jobs:\n";
            foreach ($storiesWithJobs as $story) {
                $statusLabels = [
                    1 => 'Chưa crawl',
                    2 => 'Đã crawl', 
                    3 => 'Đang crawl',
                    4 => 'Lỗi crawl',
                    5 => 'Cần crawl lại'
                ];
                $statusLabel = $statusLabels[$story->crawl_status] ?? 'Unknown';
                echo "  - Story {$story->id}: {$story->title} | Status: {$statusLabel} | Job: {$story->crawl_job_id}\n";
            }
        } else {
            echo "No stories with active crawl jobs\n";
        }
        
        echo "\n";
        
        $lastJobCount = $jobCount;
        $lastStoryStatus = $storiesWithJobs;
    }
    
    sleep(5); // Check every 5 seconds
}
