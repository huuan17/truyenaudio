<?php

/**
 * PHP Upload Configuration Helper
 * 
 * This script helps configure PHP settings for large file uploads
 */

class PHPUploadConfig
{
    /**
     * Get current PHP upload settings
     */
    public static function getCurrentSettings()
    {
        return [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'memory_limit' => ini_get('memory_limit'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'php_ini_path' => php_ini_loaded_file()
        ];
    }

    /**
     * Get recommended settings for large uploads
     */
    public static function getRecommendedSettings()
    {
        return [
            'upload_max_filesize' => '500M',  // 500MB per file
            'post_max_size' => '2G',          // 2GB total POST size
            'max_execution_time' => '300',    // 5 minutes
            'max_input_time' => '300',        // 5 minutes
            'memory_limit' => '1G',           // 1GB memory
            'max_file_uploads' => '100'       // 100 files max
        ];
    }

    /**
     * Parse size to bytes
     */
    public static function parseSize($size)
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
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if current settings support large uploads
     */
    public static function checkLargeUploadSupport()
    {
        $current = self::getCurrentSettings();
        $recommended = self::getRecommendedSettings();
        
        $issues = [];
        
        // Check upload_max_filesize
        if (self::parseSize($current['upload_max_filesize']) < self::parseSize('100M')) {
            $issues[] = [
                'setting' => 'upload_max_filesize',
                'current' => $current['upload_max_filesize'],
                'recommended' => $recommended['upload_max_filesize'],
                'issue' => 'Too small for large files (< 100MB)'
            ];
        }

        // Check post_max_size
        if (self::parseSize($current['post_max_size']) < self::parseSize('200M')) {
            $issues[] = [
                'setting' => 'post_max_size',
                'current' => $current['post_max_size'],
                'recommended' => $recommended['post_max_size'],
                'issue' => 'Too small for multiple large files (< 200MB)'
            ];
        }

        // Check max_execution_time
        if ($current['max_execution_time'] > 0 && $current['max_execution_time'] < 300) {
            $issues[] = [
                'setting' => 'max_execution_time',
                'current' => $current['max_execution_time'],
                'recommended' => $recommended['max_execution_time'],
                'issue' => 'Too short for large uploads (< 5 minutes)'
            ];
        }

        // Check memory_limit
        if (self::parseSize($current['memory_limit']) < self::parseSize('512M')) {
            $issues[] = [
                'setting' => 'memory_limit',
                'current' => $current['memory_limit'],
                'recommended' => $recommended['memory_limit'],
                'issue' => 'Too small for processing large files (< 512MB)'
            ];
        }

        return [
            'supported' => empty($issues),
            'issues' => $issues,
            'current' => $current,
            'recommended' => $recommended
        ];
    }

    /**
     * Generate php.ini configuration snippet
     */
    public static function generateConfigSnippet()
    {
        $recommended = self::getRecommendedSettings();
        
        $snippet = "; PHP Configuration for Large File Uploads\n";
        $snippet .= "; Add these settings to your php.ini file\n\n";
        
        foreach ($recommended as $setting => $value) {
            $snippet .= "{$setting} = {$value}\n";
        }
        
        $snippet .= "\n; Additional settings for better performance\n";
        $snippet .= "output_buffering = Off\n";
        $snippet .= "zlib.output_compression = Off\n";
        
        return $snippet;
    }

    /**
     * Get optimal settings based on use case
     */
    public static function getOptimalSettings($useCase = 'audio_library')
    {
        switch ($useCase) {
            case 'audio_library':
                return [
                    'upload_max_filesize' => '500M',  // Large audio files
                    'post_max_size' => '1G',          // Multiple files
                    'max_execution_time' => '600',    // 10 minutes for processing
                    'max_input_time' => '300',        // 5 minutes upload
                    'memory_limit' => '1G',           // Audio processing
                    'max_file_uploads' => '50'        // Batch uploads
                ];
                
            case 'video_processing':
                return [
                    'upload_max_filesize' => '2G',    // Large video files
                    'post_max_size' => '4G',          // Multiple videos
                    'max_execution_time' => '1800',   // 30 minutes
                    'max_input_time' => '600',        // 10 minutes upload
                    'memory_limit' => '2G',           // Video processing
                    'max_file_uploads' => '20'        // Fewer but larger files
                ];
                
            default:
                return self::getRecommendedSettings();
        }
    }
}

// Usage example:
if (php_sapi_name() === 'cli') {
    echo "=== PHP Upload Configuration Check ===\n\n";
    
    $check = PHPUploadConfig::checkLargeUploadSupport();
    
    echo "Current Settings:\n";
    foreach ($check['current'] as $setting => $value) {
        echo "  {$setting}: {$value}\n";
    }
    
    echo "\nLarge Upload Support: " . ($check['supported'] ? 'YES' : 'NO') . "\n";
    
    if (!$check['supported']) {
        echo "\nIssues Found:\n";
        foreach ($check['issues'] as $issue) {
            echo "  - {$issue['setting']}: {$issue['current']} (recommended: {$issue['recommended']})\n";
            echo "    Issue: {$issue['issue']}\n";
        }
        
        echo "\n" . PHPUploadConfig::generateConfigSnippet();
    }
}
