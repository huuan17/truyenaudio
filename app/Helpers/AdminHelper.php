<?php

namespace App\Helpers;

class AdminHelper
{
    /**
     * Get admin configuration value
     */
    public static function config($key, $default = null)
    {
        return config("admin.{$key}", $default);
    }

    /**
     * Get TTS voices
     */
    public static function getTTSVoices()
    {
        return self::config('tts.voices', []);
    }

    /**
     * Get TTS speeds
     */
    public static function getTTSSpeeds()
    {
        return self::config('tts.speeds', []);
    }

    /**
     * Get TTS volumes
     */
    public static function getTTSVolumes()
    {
        return self::config('tts.volumes', []);
    }

    /**
     * Get TTS bitrates
     */
    public static function getTTSBitrates()
    {
        return self::config('tts.bitrates', []);
    }

    /**
     * Get default TTS settings
     */
    public static function getDefaultTTSSettings()
    {
        return [
            'voice' => self::config('tts.default_voice'),
            'speed' => self::config('tts.default_speed'),
            'volume' => self::config('tts.default_volume'),
            'bitrate' => self::config('tts.default_bitrate'),
        ];
    }

    /**
     * Get video resolutions
     */
    public static function getVideoResolutions()
    {
        return self::config('video.resolutions', []);
    }

    /**
     * Get video FPS options
     */
    public static function getVideoFPSOptions()
    {
        return self::config('video.fps_options', []);
    }

    /**
     * Get video quality options
     */
    public static function getVideoQualityOptions()
    {
        return self::config('video.quality_options', []);
    }

    /**
     * Get transition effects
     */
    public static function getTransitionEffects()
    {
        return self::config('video.transition_effects', []);
    }

    /**
     * Get image positions
     */
    public static function getImagePositions()
    {
        return self::config('video.image_positions', []);
    }

    /**
     * Get allowed file types for upload
     */
    public static function getAllowedFileTypes($type = 'image')
    {
        return self::config("uploads.allowed_{$type}_types", []);
    }

    /**
     * Get max file size
     */
    public static function getMaxFileSize()
    {
        return self::config('uploads.max_file_size', '50MB');
    }

    /**
     * Get pagination settings
     */
    public static function getPaginationSettings()
    {
        return [
            'per_page' => self::config('pagination.per_page', 20),
            'options' => self::config('pagination.per_page_options', [10, 20, 50, 100]),
        ];
    }

    /**
     * Get UI colors
     */
    public static function getUIColors()
    {
        return self::config('ui.colors', []);
    }

    /**
     * Get crawl settings
     */
    public static function getCrawlSettings()
    {
        return [
            'delay' => self::config('crawl.delay_between_chapters', 2),
            'max_concurrent' => self::config('crawl.max_concurrent_crawls', 3),
            'timeout' => self::config('crawl.timeout', 30),
            'retry_attempts' => self::config('crawl.retry_attempts', 3),
            'user_agent' => self::config('crawl.user_agent'),
        ];
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Get status badge class
     */
    public static function getStatusBadgeClass($status)
    {
        $classes = [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'pending' => 'bg-warning',
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            'crawling' => 'bg-info',
            'crawled' => 'bg-success',
        ];

        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Get priority badge class
     */
    public static function getPriorityBadgeClass($priority)
    {
        if ($priority <= 3) return 'bg-danger'; // High
        if ($priority <= 7) return 'bg-warning'; // Medium
        return 'bg-info'; // Low
    }

    /**
     * Generate breadcrumb items
     */
    public static function generateBreadcrumbs($items)
    {
        $breadcrumbs = [];
        
        foreach ($items as $item) {
            if (is_string($item)) {
                $breadcrumbs[] = ['title' => $item, 'url' => null];
            } else {
                $breadcrumbs[] = [
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? null,
                    'icon' => $item['icon'] ?? null,
                ];
            }
        }
        
        return $breadcrumbs;
    }

    /**
     * Get asset URL with version
     */
    public static function asset($path, $version = true)
    {
        $url = asset($path);
        
        if ($version) {
            $filePath = public_path($path);
            if (file_exists($filePath)) {
                $timestamp = filemtime($filePath);
                $url .= '?v=' . $timestamp;
            }
        }
        
        return $url;
    }

    /**
     * Check if feature is enabled
     */
    public static function isFeatureEnabled($feature)
    {
        return self::config("features.{$feature}", false);
    }

    /**
     * Get menu items
     */
    public static function getMenuItems()
    {
        return [
            [
                'title' => 'Dashboard',
                'url' => route('admin.dashboard'),
                'icon' => 'fas fa-tachometer-alt',
                'active' => request()->routeIs('admin.dashboard'),
            ],
            [
                'title' => 'Quản lý truyện',
                'icon' => 'fas fa-book',
                'children' => [
                    [
                        'title' => 'Danh sách truyện',
                        'url' => route('admin.stories.index'),
                        'active' => request()->routeIs('admin.stories.*'),
                    ],
                    [
                        'title' => 'Thể loại',
                        'url' => route('admin.genres.index'),
                        'active' => request()->routeIs('admin.genres.*'),
                    ],
                    [
                        'title' => 'Tác giả',
                        'url' => route('admin.authors.index'),
                        'active' => request()->routeIs('admin.authors.*'),
                    ],
                ],
            ],
            [
                'title' => 'TTS & Audio',
                'icon' => 'fas fa-microphone',
                'children' => [
                    [
                        'title' => 'TTS Monitor',
                        'url' => route('admin.tts-monitor.index'),
                        'active' => request()->routeIs('admin.tts-monitor.*'),
                    ],
                    [
                        'title' => 'Thư viện Audio',
                        'url' => route('admin.audio-library.index'),
                        'active' => request()->routeIs('admin.audio-library.*'),
                    ],
                ],
            ],
            [
                'title' => 'Video Generator',
                'icon' => 'fas fa-video',
                'children' => [
                    [
                        'title' => 'Tạo Video',
                        'url' => route('admin.video-generator.index'),
                        'active' => request()->routeIs('admin.video-generator.*'),
                    ],
                    [
                        'title' => 'Templates',
                        'url' => route('admin.video-templates.index'),
                        'active' => request()->routeIs('admin.video-templates.*'),
                    ],
                    [
                        'title' => 'Queue Monitor',
                        'url' => route('admin.video-queue.index'),
                        'active' => request()->routeIs('admin.video-queue.*'),
                    ],
                ],
            ],
            [
                'title' => 'Hệ thống',
                'icon' => 'fas fa-cogs',
                'children' => [
                    [
                        'title' => 'Người dùng',
                        'url' => route('admin.users.index'),
                        'active' => request()->routeIs('admin.users.*'),
                    ],
                    [
                        'title' => 'Crawl Monitor',
                        'url' => route('admin.crawl-monitor.index'),
                        'active' => request()->routeIs('admin.crawl-monitor.*'),
                    ],
                    [
                        'title' => 'Cài đặt',
                        'url' => route('admin.settings.index'),
                        'active' => request()->routeIs('admin.settings.*'),
                    ],
                ],
            ],
        ];
    }
}
