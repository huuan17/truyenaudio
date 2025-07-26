<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\PostTooLargeException;

class HandleLargeUploads
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a large upload request
        if ($request->is('admin/audio-library/store-multiple')) {
            // Get PHP limits
            $postMaxSize = $this->parseSize(ini_get('post_max_size'));
            $uploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
            
            // Estimate total size from Content-Length header
            $contentLength = $request->header('Content-Length', 0);
            
            if ($contentLength > $postMaxSize) {
                return response()->json([
                    'error' => 'Upload quá lớn',
                    'message' => 'Tổng kích thước files vượt quá giới hạn cho phép (' . $this->formatBytes($postMaxSize) . '). Vui lòng chọn ít files hơn hoặc files nhỏ hơn.',
                    'limits' => [
                        'post_max_size' => $this->formatBytes($postMaxSize),
                        'upload_max_filesize' => $this->formatBytes($uploadMaxFilesize),
                        'current_size' => $this->formatBytes($contentLength)
                    ]
                ], 413);
            }
        }

        try {
            return $next($request);
        } catch (PostTooLargeException $e) {
            if ($request->expectsJson() || $request->is('admin/audio-library/store-multiple')) {
                return response()->json([
                    'error' => 'Upload quá lớn',
                    'message' => 'Tổng kích thước files vượt quá giới hạn cho phép. Vui lòng chọn ít files hơn hoặc files nhỏ hơn.',
                    'limits' => [
                        'post_max_size' => ini_get('post_max_size'),
                        'upload_max_filesize' => ini_get('upload_max_filesize')
                    ]
                ], 413);
            }

            return redirect()->back()
                           ->withErrors(['upload' => 'Upload quá lớn. Vui lòng chọn ít files hơn hoặc files nhỏ hơn.'])
                           ->withInput();
        }
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
