<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CustomLoggerService;
use Carbon\Carbon;

class ViewCustomLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:view
                            {context=video-template : Log context to view}
                            {--days=3 : Number of days to show}
                            {--errors-only : Show only errors}
                            {--tail=50 : Number of recent lines to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View custom application logs with daily rotation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $context = $this->argument('context');
        $days = $this->option('days');
        $errorsOnly = $this->option('errors-only');
        $tail = $this->option('tail');

        $logger = new CustomLoggerService();

        $this->info("📋 Viewing logs for context: {$context}");
        $this->line('');

        if ($errorsOnly) {
            $this->showRecentErrors($logger, $context);
        } else {
            $this->showLogFiles($logger, $context, $days, $tail);
        }
    }

    /**
     * Show recent errors
     */
    private function showRecentErrors($logger, $context)
    {
        $this->info('🚨 Recent Errors (Last 24 hours):');
        $this->line('');

        $errors = $logger->getRecentErrors($context, 24);

        if (empty($errors)) {
            $this->info('✅ No errors found in the last 24 hours!');
            return;
        }

        foreach ($errors as $error) {
            $this->line("⏰ {$error['timestamp']->format('Y-m-d H:i:s')}");
            $this->error($error['message']);
            $this->line('');
        }

        $this->info("Total errors: " . count($errors));
    }

    /**
     * Show log files
     */
    private function showLogFiles($logger, $context, $days, $tail)
    {
        $files = $logger->getLogFiles($context, $days);

        if (empty($files)) {
            $this->warn("No log files found for context: {$context}");
            return;
        }

        foreach ($files as $file) {
            $this->info("📅 Date: {$file['date']} | Size: " . $this->formatBytes($file['size']));
            $this->line('');

            $lines = explode("\n", $file['content']);
            $lines = array_filter($lines); // Remove empty lines

            // Show only recent lines if tail option is used
            if ($tail && count($lines) > $tail) {
                $lines = array_slice($lines, -$tail);
                $this->comment("... showing last {$tail} lines ...");
            }

            foreach ($lines as $line) {
                if (strpos($line, 'ERROR.') !== false) {
                    $this->error($line);
                } elseif (strpos($line, 'WARNING.') !== false) {
                    $this->warn($line);
                } elseif (strpos($line, 'INFO.') !== false) {
                    $this->info($line);
                } else {
                    $this->line($line);
                }
            }

            $this->line('');
            $this->line(str_repeat('-', 80));
            $this->line('');
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
