<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * Show upload configuration page
     */
    public function uploadConfig()
    {
        // Include the PHP config helper
        require_once base_path('config/php-upload-config.php');
        
        $check = \PHPUploadConfig::checkLargeUploadSupport();
        $current = $check['current'];
        $recommended = \PHPUploadConfig::getOptimalSettings('audio_library');
        
        return view('admin.system.upload-config', compact('check', 'current', 'recommended'));
    }

    /**
     * Get upload configuration via API
     */
    public function getUploadConfig()
    {
        require_once base_path('config/php-upload-config.php');
        
        $check = \PHPUploadConfig::checkLargeUploadSupport();
        
        return response()->json([
            'supported' => $check['supported'],
            'current' => $check['current'],
            'recommended' => \PHPUploadConfig::getOptimalSettings('audio_library'),
            'issues' => $check['issues'],
            'config_snippet' => \PHPUploadConfig::generateConfigSnippet()
        ]);
    }

    /**
     * Test upload limits
     */
    public function testUploadLimits(Request $request)
    {
        $testSize = $request->get('size', '100M');
        
        // Parse test size
        $testSizeBytes = $this->parseSize($testSize);
        
        require_once base_path('config/php-upload-config.php');
        $current = \PHPUploadConfig::getCurrentSettings();
        
        $uploadMaxBytes = \PHPUploadConfig::parseSize($current['upload_max_filesize']);
        $postMaxBytes = \PHPUploadConfig::parseSize($current['post_max_size']);
        
        $results = [
            'test_size' => $testSize,
            'test_size_bytes' => $testSizeBytes,
            'can_upload_single' => $testSizeBytes <= $uploadMaxBytes,
            'can_upload_multiple' => $testSizeBytes <= $postMaxBytes,
            'limits' => [
                'upload_max_filesize' => $current['upload_max_filesize'],
                'upload_max_bytes' => $uploadMaxBytes,
                'post_max_size' => $current['post_max_size'], 
                'post_max_bytes' => $postMaxBytes
            ],
            'recommendations' => []
        ];
        
        // Add recommendations
        if (!$results['can_upload_single']) {
            $results['recommendations'][] = "Increase upload_max_filesize to at least {$testSize}";
        }
        
        if (!$results['can_upload_multiple']) {
            $results['recommendations'][] = "Increase post_max_size to at least {$testSize}";
        }
        
        if ($results['can_upload_single'] && $results['can_upload_multiple']) {
            $results['recommendations'][] = "Current configuration supports {$testSize} uploads";
        }
        
        return response()->json($results);
    }

    /**
     * Generate configuration instructions
     */
    public function generateInstructions()
    {
        require_once base_path('config/php-upload-config.php');
        
        $current = \PHPUploadConfig::getCurrentSettings();
        $recommended = \PHPUploadConfig::getOptimalSettings('audio_library');
        $snippet = \PHPUploadConfig::generateConfigSnippet();
        
        $instructions = [
            'php_ini_path' => $current['php_ini_path'],
            'config_snippet' => $snippet,
            'steps' => [
                '1. Backup your current php.ini file',
                '2. Open php.ini in a text editor (as Administrator on Windows)',
                '3. Find and update the following settings (or add them if not found):',
                '4. Save the file',
                '5. Restart your web server (Apache/Nginx)',
                '6. Verify changes by refreshing this page'
            ],
            'xampp_steps' => [
                '1. Stop Apache in XAMPP Control Panel',
                '2. Open C:\\xampp\\php\\php.ini in Notepad (as Administrator)',
                '3. Update the settings shown below',
                '4. Save the file',
                '5. Start Apache in XAMPP Control Panel',
                '6. Refresh this page to verify'
            ],
            'settings_to_update' => []
        ];
        
        foreach ($recommended as $setting => $value) {
            $currentValue = $current[$setting] ?? 'Not set';
            $instructions['settings_to_update'][] = [
                'setting' => $setting,
                'current' => $currentValue,
                'recommended' => $value,
                'needs_update' => $currentValue !== $value
            ];
        }
        
        return response()->json($instructions);
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
}
