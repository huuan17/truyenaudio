<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUEUE STATUS ===\n";
echo "Jobs in queue: " . DB::table('jobs')->count() . "\n";

$jobs = DB::table('jobs')->select('queue', DB::raw('count(*) as count'))->groupBy('queue')->get();
echo "Jobs by queue:\n";
foreach ($jobs as $job) {
    echo "  {$job->queue}: {$job->count}\n";
}

echo "\n=== RECENT JOBS ===\n";
$recentJobs = DB::table('jobs')
    ->select('id', 'queue', 'payload', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentJobs as $job) {
    $payload = json_decode($job->payload, true);
    $jobClass = $payload['displayName'] ?? 'Unknown';
    echo "ID: {$job->id} | Queue: {$job->queue} | Job: {$jobClass} | Created: {$job->created_at}\n";
}

echo "\n=== STORIES WITH CRAWL JOBS ===\n";
$storiesWithJobs = DB::table('stories')
    ->whereNotNull('crawl_job_id')
    ->select('id', 'title', 'crawl_status', 'crawl_job_id')
    ->get();

foreach ($storiesWithJobs as $story) {
    echo "Story {$story->id}: {$story->title} | Status: {$story->crawl_status} | Job ID: {$story->crawl_job_id}\n";
}
