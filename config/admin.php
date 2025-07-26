<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard Configuration
    |--------------------------------------------------------------------------
    */

    'name' => 'Quản lý truyện Audio',
    'version' => '1.0.0',
    'logo' => '/assets/images/logo.png',

    /*
    |--------------------------------------------------------------------------
    | TTS (Text-to-Speech) Settings
    |--------------------------------------------------------------------------
    */
    'tts' => [
        'default_voice' => 'vi-VN-HoaiMyNeural',
        'default_speed' => 1.0,
        'default_volume' => 100,
        'default_bitrate' => 128,
        
        'voices' => [
            'vi-VN-HoaiMyNeural' => 'Hoài My (Nữ)',
            'vi-VN-NamMinhNeural' => 'Nam Minh (Nam)',
            'vi-VN-ThuHienNeural' => 'Thu Hiền (Nữ)',
            'vi-VN-TrungNeural' => 'Trung (Nam)',
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ)',
            'hn_male_xuantin_full_48k-fhg' => 'Xuân Tín (Nam)',
            'sg_female_minhquy_full_48k-fhg' => 'Minh Quý (Nữ)',
            'sg_male_xuankien_full_48k-fhg' => 'Xuân Kiên (Nam)',
        ],

        'speeds' => [
            0.5 => '0.5x (Chậm)',
            0.75 => '0.75x',
            1.0 => '1x (Bình thường)',
            1.25 => '1.25x',
            1.5 => '1.5x',
            2.0 => '2x (Nhanh)',
        ],

        'volumes' => [
            50 => '50%',
            75 => '75%',
            100 => '100% (Bình thường)',
            125 => '125%',
            150 => '150%',
            200 => '200%',
        ],

        'bitrates' => [
            64 => '64 kbps',
            128 => '128 kbps (Khuyến nghị)',
            192 => '192 kbps',
            256 => '256 kbps',
            320 => '320 kbps',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Generation Settings
    |--------------------------------------------------------------------------
    */
    'video' => [
        'resolutions' => [
            '1080x1920' => 'TikTok (1080x1920)',
            '1920x1080' => 'YouTube Landscape (1920x1080)',
            '1080x1080' => 'Instagram Square (1080x1080)',
            '720x1280' => 'Mobile Portrait (720x1280)',
            '1280x720' => 'HD Landscape (1280x720)',
        ],

        'fps_options' => [
            24 => '24 FPS (Cinema)',
            30 => '30 FPS (Standard)',
            60 => '60 FPS (Smooth)',
        ],

        'quality_options' => [
            'low' => 'Thấp (Nhanh)',
            'medium' => 'Trung bình',
            'high' => 'Cao (Khuyến nghị)',
            'ultra' => 'Siêu cao (Chậm)',
        ],

        'transition_effects' => [
            'none' => 'Không có',
            'fade' => 'Fade',
            'slide' => 'Slide',
            'zoom' => 'Zoom',
            'dissolve' => 'Dissolve',
            'wipe' => 'Wipe',
        ],

        'image_positions' => [
            'center' => 'Giữa',
            'top' => 'Trên',
            'bottom' => 'Dưới',
            'left' => 'Trái',
            'right' => 'Phải',
            'top-left' => 'Trên trái',
            'top-right' => 'Trên phải',
            'bottom-left' => 'Dưới trái',
            'bottom-right' => 'Dưới phải',
        ],

        'default_duration' => 30,
        'max_duration' => 300,
        'min_duration' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_file_size' => '50MB',
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_audio_types' => ['mp3', 'wav', 'aac', 'm4a'],
        'allowed_video_types' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'txt'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 20,
        'per_page_options' => [10, 20, 50, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'theme' => 'default',
        'sidebar_collapsed' => false,
        'show_breadcrumbs' => true,
        'show_tooltips' => true,
        'animation_speed' => 300,
        
        'colors' => [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'success' => '#56ab2f',
            'danger' => '#ff416c',
            'warning' => '#ffa726',
            'info' => '#26c6da',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawl Settings
    |--------------------------------------------------------------------------
    */
    'crawl' => [
        'delay_between_chapters' => 2, // seconds
        'max_concurrent_crawls' => 3,
        'timeout' => 30, // seconds
        'retry_attempts' => 3,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'default_priority' => 5,
        'high_priority' => 1,
        'low_priority' => 10,
        'retry_after' => 300, // seconds
        'max_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl' => 3600, // 1 hour
        'tags' => [
            'stories' => 'stories',
            'chapters' => 'chapters',
            'audio' => 'audio',
            'videos' => 'videos',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'toast_duration' => 5000, // milliseconds
        'show_success' => true,
        'show_errors' => true,
        'show_warnings' => true,
        'position' => 'top-right',
    ],
];
