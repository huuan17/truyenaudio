<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestUploadLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:test-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and display all upload limits configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Upload Limits Configuration Test');
        $this->line('');

        // PHP Settings
        $this->info('📋 PHP Settings:');
        $phpSettings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
        ];

        foreach ($phpSettings as $setting => $value) {
            $this->line("  {$setting}: {$value}");
        }

        // Parse sizes
        $uploadMaxBytes = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxBytes = $this->parseSize(ini_get('post_max_size'));
        $memoryLimitBytes = $this->parseSize(ini_get('memory_limit'));

        $this->line('');
        $this->info('📊 Parsed Sizes (bytes):');
        $this->line("  upload_max_filesize: " . number_format($uploadMaxBytes) . " bytes (" . $this->formatBytes($uploadMaxBytes) . ")");
        $this->line("  post_max_size: " . number_format($postMaxBytes) . " bytes (" . $this->formatBytes($postMaxBytes) . ")");
        $this->line("  memory_limit: " . number_format($memoryLimitBytes) . " bytes (" . $this->formatBytes($memoryLimitBytes) . ")");

        // Recommendations
        $this->line('');
        $this->info('💡 Recommendations:');
        $recommendedMaxTotal = min($postMaxBytes * 0.9, $memoryLimitBytes * 0.8);
        $this->line("  Max total upload: " . $this->formatBytes($recommendedMaxTotal) . " (90% of post_max_size)");
        $this->line("  Max single file: " . $this->formatBytes($uploadMaxBytes));
        $this->line("  Max files: " . ini_get('max_file_uploads'));

        // Laravel Validation Limits
        $this->line('');
        $this->info('🎯 Laravel Validation Limits (KB):');
        $validationLimits = [
            'Images' => '51200 KB (50MB)',
            'Videos' => '512000 KB (500MB)',
            'Audio files' => '512000 KB (500MB)',
            'Logo files' => '51200 KB (50MB)',
            'Subtitle files' => '10240 KB (10MB)',
            'General files' => '512000 KB (500MB)'
        ];

        foreach ($validationLimits as $type => $limit) {
            $this->line("  {$type}: {$limit}");
        }

        // Status Check
        $this->line('');
        $this->info('✅ Status Check:');

        if ($uploadMaxBytes >= 500 * 1024 * 1024) {
            $this->line("  ✅ upload_max_filesize: OK (≥500MB)");
        } else {
            $this->error("  ❌ upload_max_filesize: TOO LOW (<500MB)");
        }

        if ($postMaxBytes >= 1024 * 1024 * 1024) {
            $this->line("  ✅ post_max_size: OK (≥1GB)");
        } else {
            $this->error("  ❌ post_max_size: TOO LOW (<1GB)");
        }

        if (ini_get('max_file_uploads') >= 50) {
            $this->line("  ✅ max_file_uploads: OK (≥50)");
        } else {
            $this->error("  ❌ max_file_uploads: TOO LOW (<50)");
        }

        // .htaccess check
        $htaccessPath = public_path('.htaccess');
        if (file_exists($htaccessPath)) {
            $htaccessContent = file_get_contents($htaccessPath);
            if (strpos($htaccessContent, 'php_value upload_max_filesize') !== false) {
                $this->line("  ✅ .htaccess: Upload limits configured");
            } else {
                $this->warn("  ⚠️  .htaccess: No upload limits found");
            }
        } else {
            $this->warn("  ⚠️  .htaccess: File not found");
        }

        $this->line('');
        $this->info('🎉 Upload limits test completed!');

        return 0;
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
