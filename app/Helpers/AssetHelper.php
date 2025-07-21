<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Get asset URL with version for cache busting
     */
    public static function asset($path)
    {
        $fullPath = public_path($path);
        
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            return asset($path) . '?v=' . $version;
        }
        
        return asset($path);
    }

    /**
     * Get local asset or fallback to CDN
     */
    public static function assetWithFallback($localPath, $cdnUrl)
    {
        $fullPath = public_path($localPath);
        
        if (file_exists($fullPath)) {
            return self::asset($localPath);
        }
        
        return $cdnUrl;
    }

    /**
     * Check if running in local environment
     */
    public static function isLocal()
    {
        return app()->environment('local');
    }

    /**
     * Get CSS asset with fallback
     */
    public static function css($localPath, $cdnUrl = null)
    {
        if ($cdnUrl && !self::isLocal()) {
            return self::assetWithFallback($localPath, $cdnUrl);
        }
        
        return self::asset($localPath);
    }

    /**
     * Get JS asset with fallback
     */
    public static function js($localPath, $cdnUrl = null)
    {
        if ($cdnUrl && !self::isLocal()) {
            return self::assetWithFallback($localPath, $cdnUrl);
        }
        
        return self::asset($localPath);
    }
}
