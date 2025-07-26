<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VideoTemplate;

class AdvancedVideoTemplateSeeder extends Seeder
{
    public function run()
    {
        // Advanced Marketing Template with Channel Integration
        VideoTemplate::create([
            'name' => 'Marketing Pro với Channel Upload',
            'description' => 'Template marketing chuyên nghiệp với tính năng upload tự động lên kênh',
            'category' => 'marketing',
            'settings' => [
                'platform' => 'tiktok',
                'media_type' => 'mixed',
                'duration_based_on' => 'audio',
                'sync_with_audio' => true,
                'enable_logo' => true,
                'logo_position' => 'bottom-right',
                'logo_size' => 80,
                'logo_opacity' => 0.9,
                'audio_volume' => 18, // Default 18dB
                'subtitle_position' => 'bottom',
                'subtitle_size' => 28,
                'subtitle_color' => '#FFFFFF',
                'subtitle_background' => '#000000',
                'quality' => 'high'
            ],
            'required_inputs' => [
                [
                    'name' => 'product_name',
                    'type' => 'text',
                    'label' => 'Tên sản phẩm',
                    'placeholder' => 'Nhập tên sản phẩm...'
                ],
                [
                    'name' => 'marketing_script',
                    'type' => 'textarea',
                    'label' => 'Nội dung marketing',
                    'placeholder' => 'Nhập nội dung quảng cáo sản phẩm...'
                ],
                [
                    'name' => 'product_images',
                    'type' => 'images',
                    'label' => 'Hình ảnh sản phẩm',
                    'placeholder' => 'Upload 3-5 hình ảnh sản phẩm'
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'overlay_images',
                    'type' => 'images',
                    'label' => 'Ảnh overlay',
                    'placeholder' => 'Upload ảnh để chèn vào video (logo, badge, etc.)'
                ],
                [
                    'name' => 'per_image_captions',
                    'type' => 'textarea',
                    'label' => 'Chú thích từng ảnh',
                    'placeholder' => 'Nhập chú thích cho từng ảnh, mỗi dòng một ảnh'
                ]
            ],
            'channel_metadata_template' => [
                'title_template' => '{{product_name}} - {{template_name}} #{{sequence}}',
                'description_template' => '{{marketing_script}}\n\n#{{product_name}} #marketing #sale',
                'tags_template' => ['{{product_name}}', 'marketing', 'sale', 'product'],
                'hashtags_template' => ['#{{product_name}}', '#marketing', '#sale', '#viral']
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => 1
        ]);

        // Educational Template with Per-Image Subtitles
        VideoTemplate::create([
            'name' => 'Giáo dục với Phụ đề Thông minh',
            'description' => 'Template giáo dục với phụ đề đồng bộ theo từng ảnh',
            'category' => 'education',
            'settings' => [
                'platform' => 'youtube',
                'media_type' => 'images',
                'duration_based_on' => 'images',
                'image_duration' => 4,
                'enable_logo' => false,
                'audio_volume' => 18,
                'subtitle_position' => 'bottom',
                'subtitle_size' => 24,
                'subtitle_color' => '#FFFFFF',
                'subtitle_background' => '#000000',
                'quality' => 'high'
            ],
            'required_inputs' => [
                [
                    'name' => 'lesson_title',
                    'type' => 'text',
                    'label' => 'Tiêu đề bài học',
                    'placeholder' => 'Nhập tiêu đề bài học...'
                ],
                [
                    'name' => 'lesson_images',
                    'type' => 'images',
                    'label' => 'Hình ảnh bài học',
                    'placeholder' => 'Upload hình ảnh minh họa bài học'
                ],
                [
                    'name' => 'image_explanations',
                    'type' => 'textarea',
                    'label' => 'Giải thích từng ảnh',
                    'placeholder' => 'Nhập giải thích cho từng ảnh, mỗi dòng một ảnh'
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'lesson_audio',
                    'type' => 'audio',
                    'label' => 'Audio bài giảng',
                    'placeholder' => 'Upload file audio bài giảng (tùy chọn)'
                ]
            ],
            'channel_metadata_template' => [
                'title_template' => 'Bài học: {{lesson_title}} - {{template_name}} #{{sequence}}',
                'description_template' => 'Bài học về {{lesson_title}}\n\nHọc tập hiệu quả với video minh họa chi tiết.',
                'tags_template' => ['{{lesson_title}}', 'education', 'learning', 'tutorial'],
                'hashtags_template' => ['#education', '#learning', '#tutorial', '#knowledge']
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => 1
        ]);

        // Advanced Content Template with Image Overlays
        VideoTemplate::create([
            'name' => 'Nội dung Nâng cao với Overlay',
            'description' => 'Template với hiệu ứng overlay ảnh và chuyển cảnh nâng cao',
            'category' => 'general',
            'settings' => [
                'platform' => 'none',
                'media_type' => 'mixed',
                'duration_based_on' => 'custom',
                'custom_duration' => 60,
                'enable_logo' => true,
                'logo_position' => 'top-right',
                'logo_size' => 100,
                'logo_opacity' => 0.8,
                'audio_volume' => 18,
                'quality' => 'very_high',
                'resolution' => '1920x1080',
                'fps' => 30
            ],
            'required_inputs' => [
                [
                    'name' => 'main_content',
                    'type' => 'textarea',
                    'label' => 'Nội dung chính',
                    'placeholder' => 'Nhập nội dung chính của video...'
                ],
                [
                    'name' => 'background_video',
                    'type' => 'video',
                    'label' => 'Video nền',
                    'placeholder' => 'Upload video làm nền'
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'overlay_config',
                    'type' => 'textarea',
                    'label' => 'Cấu hình overlay (JSON)',
                    'placeholder' => 'Nhập cấu hình overlay dạng JSON...'
                ],
                [
                    'name' => 'transition_effects',
                    'type' => 'select',
                    'label' => 'Hiệu ứng chuyển cảnh',
                    'options' => [
                        'fade' => 'Fade in/out',
                        'slide' => 'Slide',
                        'zoom' => 'Zoom',
                        'rotate' => 'Rotate'
                    ]
                ]
            ],
            'channel_metadata_template' => [
                'title_template' => '{{template_name}} #{{sequence}} - Nội dung chất lượng cao',
                'description_template' => '{{main_content}}\n\nVideo được tạo với hiệu ứng chuyên nghiệp.',
                'tags_template' => ['content', 'professional', 'high-quality'],
                'hashtags_template' => ['#content', '#professional', '#video']
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => 1
        ]);
    }
}
