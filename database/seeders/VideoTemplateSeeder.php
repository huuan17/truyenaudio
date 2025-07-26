<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VideoTemplate;
use App\Models\User;

class VideoTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }

        if (!$adminUser) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        // Template 1: TikTok Viral Content - Complete Media Setup
        VideoTemplate::create([
            'name' => 'TikTok Viral Content - Complete',
            'description' => 'Template tạo video viral TikTok với đầy đủ tùy chọn media, audio và subtitle',
            'category' => 'tiktok',
            'settings' => [
                'platform' => 'tiktok',
                'media_type' => 'images', // Will be overridden by user choice
                'enable_subtitle' => true,
                'subtitle_source' => 'auto',
                'subtitle_position' => 'bottom',
                'subtitle_size' => 'large',
                'subtitle_color' => 'white',
                'subtitle_background' => 'solid_black',
                'subtitle_timing_mode' => 'image_sync',
                'subtitle_per_image' => 'sentence',
                'tiktok_resolution' => '1080x1920',
                'tiktok_fps' => 30,
                'tiktok_duration' => 60,
                'default_image_duration' => 3,
                'default_transition_effect' => 'slide',
                'transition_duration' => 0.5,
                'audio_source' => 'tts', // Will be overridden by user choice
                'tts_voice' => 'vi-VN-HoaiMyNeural',
                'tts_speed' => 1.2,
                'tts_volume' => 100,
                'enable_logo' => false,
                'duration_based_on' => 'images',
                'sync_with_audio' => true
            ],
            'required_inputs' => [
                [
                    'name' => 'media_type_choice',
                    'type' => 'select',
                    'label' => 'Loại media chính',
                    'placeholder' => 'Chọn loại media cho video',
                    'options' => [
                        'images' => 'Chỉ hình ảnh',
                        'video' => 'Chỉ video',
                        'mixed' => 'Kết hợp ảnh + video'
                    ]
                ],
                [
                    'name' => 'audio_source_choice',
                    'type' => 'select',
                    'label' => 'Nguồn âm thanh',
                    'placeholder' => 'Chọn nguồn âm thanh',
                    'options' => [
                        'tts' => 'Text-to-Speech (VBee API)',
                        'upload' => 'Upload file âm thanh',
                        'none' => 'Không có âm thanh'
                    ]
                ],
                [
                    'name' => 'duration_control',
                    'type' => 'select',
                    'label' => 'Kiểm soát độ dài video',
                    'placeholder' => 'Chọn cách kiểm soát độ dài video',
                    'options' => [
                        'auto_images' => 'Tự động theo số ảnh',
                        'audio_length' => 'Theo độ dài âm thanh',
                        'video_length' => 'Theo độ dài video nền',
                        'fixed_duration' => 'Thời gian cố định'
                    ]
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'script_text',
                    'type' => 'textarea',
                    'label' => 'Nội dung kịch bản (cho TTS)',
                    'placeholder' => 'Nhập nội dung kịch bản nếu dùng TTS...'
                ],
                [
                    'name' => 'background_images',
                    'type' => 'images',
                    'label' => 'Hình ảnh nền',
                    'placeholder' => 'Upload hình ảnh nếu chọn loại media ảnh hoặc mixed'
                ],
                [
                    'name' => 'background_video',
                    'type' => 'video',
                    'label' => 'Video nền',
                    'placeholder' => 'Upload video nếu chọn loại media video hoặc mixed'
                ],
                [
                    'name' => 'audio_file',
                    'type' => 'audio',
                    'label' => 'File âm thanh',
                    'placeholder' => 'Upload file âm thanh nếu chọn upload'
                ],
                [
                    'name' => 'subtitle_text',
                    'type' => 'textarea',
                    'label' => 'Phụ đề tùy chỉnh',
                    'placeholder' => 'Nhập phụ đề tùy chỉnh (tùy chọn)'
                ],
                [
                    'name' => 'hashtags',
                    'type' => 'text',
                    'label' => 'Hashtags TikTok',
                    'placeholder' => '#viral #trending #fyp #foryou'
                ],
                [
                    'name' => 'output_name',
                    'type' => 'text',
                    'label' => 'Tên file output',
                    'placeholder' => 'tiktok_viral_video'
                ],
                [
                    'name' => 'fixed_duration_seconds',
                    'type' => 'number',
                    'label' => 'Độ dài cố định (giây)',
                    'placeholder' => 'Nhập số giây (vd: 30, 60, 180)'
                ],
                [
                    'name' => 'image_duration_seconds',
                    'type' => 'number',
                    'label' => 'Thời gian mỗi ảnh (giây)',
                    'placeholder' => 'Thời gian hiển thị mỗi ảnh (vd: 3, 5)'
                ],
                [
                    'name' => 'max_video_duration',
                    'type' => 'number',
                    'label' => 'Giới hạn độ dài video (giây)',
                    'placeholder' => 'Giới hạn tối đa cho video (vd: 60, 180)'
                ]
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => $adminUser->id
        ]);

        // Template 2: YouTube Educational - Complete Setup
        VideoTemplate::create([
            'name' => 'YouTube Educational - Complete',
            'description' => 'Template tạo video giáo dục YouTube với đầy đủ tùy chọn media, audio, logo và subtitle',
            'category' => 'education',
            'settings' => [
                'platform' => 'youtube',
                'media_type' => 'mixed', // Default to mixed for educational content
                'enable_subtitle' => true,
                'subtitle_source' => 'auto',
                'subtitle_position' => 'bottom',
                'subtitle_size' => 'medium',
                'subtitle_color' => 'white',
                'subtitle_background' => 'none',
                'subtitle_outline' => true,
                'subtitle_timing_mode' => 'auto',
                'youtube_resolution' => '1920x1080',
                'youtube_fps' => 30,
                'youtube_quality' => 'high',
                'default_image_duration' => 5,
                'default_transition_effect' => 'fade',
                'transition_duration' => 1.0,
                'audio_source' => 'tts',
                'tts_voice' => 'vi-VN-NamMinhNeural',
                'tts_speed' => 1.0,
                'tts_volume' => 100,
                'enable_logo' => true,
                'logo_position' => 'top-right',
                'logo_size' => 'small',
                'logo_opacity' => 0.8,
                'mixed_mode' => 'sequence',
                'sequence_image_duration' => 5,
                'sequence_video_duration' => 'full'
            ],
            'required_inputs' => [
                [
                    'name' => 'media_setup',
                    'type' => 'select',
                    'label' => 'Cách setup media',
                    'placeholder' => 'Chọn cách setup media cho video',
                    'options' => [
                        'images_only' => 'Chỉ dùng hình ảnh',
                        'video_only' => 'Chỉ dùng video',
                        'mixed_sequence' => 'Kết hợp ảnh + video (tuần tự)',
                        'mixed_overlay' => 'Kết hợp ảnh + video (overlay)'
                    ]
                ],
                [
                    'name' => 'audio_method',
                    'type' => 'select',
                    'label' => 'Phương thức âm thanh',
                    'placeholder' => 'Chọn cách tạo âm thanh',
                    'options' => [
                        'tts_vbee' => 'Text-to-Speech (VBee API)',
                        'upload_audio' => 'Upload file âm thanh',
                        'video_audio' => 'Dùng âm thanh từ video',
                        'mixed_audio' => 'Kết hợp TTS + background music'
                    ]
                ],
                [
                    'name' => 'subtitle_method',
                    'type' => 'select',
                    'label' => 'Phương thức phụ đề',
                    'placeholder' => 'Chọn cách tạo phụ đề',
                    'options' => [
                        'auto_from_tts' => 'Tự động từ TTS',
                        'manual_input' => 'Nhập thủ công',
                        'upload_srt' => 'Upload file SRT',
                        'no_subtitle' => 'Không có phụ đề'
                    ]
                ],
                [
                    'name' => 'video_duration_control',
                    'type' => 'select',
                    'label' => 'Kiểm soát độ dài video',
                    'placeholder' => 'Chọn cách kiểm soát độ dài video',
                    'options' => [
                        'content_based' => 'Theo nội dung (ảnh/video)',
                        'audio_sync' => 'Đồng bộ với âm thanh',
                        'video_sync' => 'Theo video nền',
                        'custom_length' => 'Độ dài tùy chỉnh'
                    ]
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'lesson_content',
                    'type' => 'textarea',
                    'label' => 'Nội dung bài học (cho TTS)',
                    'placeholder' => 'Nhập nội dung bài học nếu dùng TTS...'
                ],
                [
                    'name' => 'lesson_images',
                    'type' => 'images',
                    'label' => 'Hình ảnh minh họa',
                    'placeholder' => 'Upload hình ảnh minh họa'
                ],
                [
                    'name' => 'lesson_videos',
                    'type' => 'video',
                    'label' => 'Video minh họa',
                    'placeholder' => 'Upload video minh họa'
                ],
                [
                    'name' => 'audio_file',
                    'type' => 'audio',
                    'label' => 'File âm thanh',
                    'placeholder' => 'Upload file âm thanh nếu chọn upload'
                ],
                [
                    'name' => 'background_music',
                    'type' => 'audio',
                    'label' => 'Nhạc nền',
                    'placeholder' => 'Upload nhạc nền (cho mixed audio)'
                ],
                [
                    'name' => 'subtitle_text',
                    'type' => 'textarea',
                    'label' => 'Phụ đề thủ công',
                    'placeholder' => 'Nhập phụ đề nếu chọn manual input'
                ],
                [
                    'name' => 'subtitle_file',
                    'type' => 'file',
                    'label' => 'File SRT',
                    'placeholder' => 'Upload file SRT nếu có'
                ],
                [
                    'name' => 'logo_image',
                    'type' => 'image',
                    'label' => 'Logo tùy chỉnh',
                    'placeholder' => 'Upload logo riêng (tùy chọn)'
                ],
                [
                    'name' => 'video_title',
                    'type' => 'text',
                    'label' => 'Tiêu đề video',
                    'placeholder' => 'Tiêu đề hấp dẫn cho video giáo dục'
                ],
                [
                    'name' => 'video_description',
                    'type' => 'textarea',
                    'label' => 'Mô tả video',
                    'placeholder' => 'Mô tả chi tiết về nội dung video...'
                ],
                [
                    'name' => 'youtube_tags',
                    'type' => 'text',
                    'label' => 'Tags YouTube',
                    'placeholder' => 'education, tutorial, học tập, kiến thức'
                ],
                [
                    'name' => 'custom_video_length',
                    'type' => 'number',
                    'label' => 'Độ dài video tùy chỉnh (giây)',
                    'placeholder' => 'Nhập độ dài mong muốn (vd: 300, 600, 900)'
                ],
                [
                    'name' => 'lesson_image_duration',
                    'type' => 'number',
                    'label' => 'Thời gian mỗi ảnh bài học (giây)',
                    'placeholder' => 'Thời gian hiển thị mỗi ảnh (vd: 5, 8, 10)'
                ],
                [
                    'name' => 'sync_tolerance',
                    'type' => 'number',
                    'label' => 'Dung sai đồng bộ (giây)',
                    'placeholder' => 'Dung sai khi đồng bộ với âm thanh (vd: 2, 5)'
                ]
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => $adminUser->id
        ]);

        // Template 3: Marketing Product Video - Professional Setup
        VideoTemplate::create([
            'name' => 'Marketing Product Video - Pro',
            'description' => 'Template marketing chuyên nghiệp với đầy đủ tùy chọn media, audio, effects và CTA',
            'category' => 'marketing',
            'settings' => [
                'platform' => 'both',
                'media_type' => 'mixed',
                'mixed_mode' => 'sequence',
                'enable_subtitle' => true,
                'subtitle_source' => 'auto',
                'subtitle_position' => 'center',
                'subtitle_size' => 'xlarge',
                'subtitle_color' => 'yellow',
                'subtitle_background' => 'solid_black',
                'subtitle_timing_mode' => 'custom_timing',
                'subtitle_duration' => 2.5,
                'subtitle_delay' => 0.3,
                'subtitle_fade' => 'both',
                'tiktok_resolution' => '1080x1920',
                'youtube_resolution' => '1920x1080',
                'default_image_duration' => 4,
                'sequence_video_duration' => 6,
                'default_transition_effect' => 'zoom',
                'transition_duration' => 0.8,
                'audio_source' => 'mixed',
                'tts_voice' => 'vi-VN-HoaiMyNeural',
                'tts_speed' => 1.1,
                'tts_volume' => 120,
                'enable_logo' => true,
                'logo_position' => 'bottom-right',
                'logo_size' => 'medium',
                'logo_opacity' => 0.9
            ],
            'required_inputs' => [
                [
                    'name' => 'content_strategy',
                    'type' => 'select',
                    'label' => 'Chiến lược nội dung',
                    'placeholder' => 'Chọn chiến lược nội dung marketing',
                    'options' => [
                        'product_showcase' => 'Giới thiệu sản phẩm',
                        'before_after' => 'Trước/Sau sử dụng',
                        'testimonial' => 'Khách hàng review',
                        'tutorial' => 'Hướng dẫn sử dụng',
                        'comparison' => 'So sánh với đối thủ'
                    ]
                ],
                [
                    'name' => 'media_composition',
                    'type' => 'select',
                    'label' => 'Thành phần media',
                    'placeholder' => 'Chọn cách kết hợp media',
                    'options' => [
                        'images_only' => 'Chỉ hình ảnh sản phẩm',
                        'videos_only' => 'Chỉ video demo',
                        'mixed_sequence' => 'Ảnh + Video tuần tự',
                        'mixed_overlay' => 'Ảnh chính + Video overlay',
                        'split_screen' => 'Chia đôi màn hình'
                    ]
                ],
                [
                    'name' => 'audio_strategy',
                    'type' => 'select',
                    'label' => 'Chiến lược âm thanh',
                    'placeholder' => 'Chọn cách setup âm thanh',
                    'options' => [
                        'tts_only' => 'Chỉ giọng đọc TTS',
                        'music_only' => 'Chỉ nhạc nền',
                        'tts_with_music' => 'TTS + Nhạc nền',
                        'voice_over' => 'Upload voice-over',
                        'mixed_audio' => 'Kết hợp nhiều nguồn'
                    ]
                ],
                [
                    'name' => 'marketing_duration_strategy',
                    'type' => 'select',
                    'label' => 'Chiến lược độ dài video',
                    'placeholder' => 'Chọn cách kiểm soát độ dài video marketing',
                    'options' => [
                        'platform_optimal' => 'Tối ưu theo platform',
                        'content_driven' => 'Theo nội dung sản phẩm',
                        'audio_matched' => 'Khớp với âm thanh',
                        'fixed_marketing' => 'Độ dài marketing cố định'
                    ]
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'product_script',
                    'type' => 'textarea',
                    'label' => 'Kịch bản sản phẩm (cho TTS)',
                    'placeholder' => 'Viết kịch bản giới thiệu sản phẩm hấp dẫn...'
                ],
                [
                    'name' => 'product_images',
                    'type' => 'images',
                    'label' => 'Hình ảnh sản phẩm',
                    'placeholder' => 'Upload hình ảnh sản phẩm chất lượng cao'
                ],
                [
                    'name' => 'product_videos',
                    'type' => 'video',
                    'label' => 'Video demo sản phẩm',
                    'placeholder' => 'Upload video demo/sử dụng sản phẩm'
                ],
                [
                    'name' => 'voice_over_file',
                    'type' => 'audio',
                    'label' => 'File voice-over',
                    'placeholder' => 'Upload file voice-over chuyên nghiệp'
                ],
                [
                    'name' => 'background_music',
                    'type' => 'audio',
                    'label' => 'Nhạc nền',
                    'placeholder' => 'Upload nhạc nền phù hợp với sản phẩm'
                ],
                [
                    'name' => 'call_to_action',
                    'type' => 'text',
                    'label' => 'Call to Action',
                    'placeholder' => 'Đặt hàng ngay! Liên hệ: 0123456789'
                ],
                [
                    'name' => 'brand_logo',
                    'type' => 'image',
                    'label' => 'Logo thương hiệu',
                    'placeholder' => 'Upload logo thương hiệu'
                ],
                [
                    'name' => 'custom_subtitle',
                    'type' => 'textarea',
                    'label' => 'Phụ đề tùy chỉnh',
                    'placeholder' => 'Nhập phụ đề marketing tùy chỉnh'
                ],
                [
                    'name' => 'product_price',
                    'type' => 'text',
                    'label' => 'Giá sản phẩm',
                    'placeholder' => 'Chỉ 299.000đ - Giảm 50%!'
                ],
                [
                    'name' => 'contact_info',
                    'type' => 'text',
                    'label' => 'Thông tin liên hệ',
                    'placeholder' => 'Hotline: 0123456789 | Website: example.com'
                ],
                [
                    'name' => 'hashtags_tiktok',
                    'type' => 'text',
                    'label' => 'Hashtags TikTok',
                    'placeholder' => '#sanpham #sale #trending #muangay'
                ],
                [
                    'name' => 'youtube_tags',
                    'type' => 'text',
                    'label' => 'Tags YouTube',
                    'placeholder' => 'sản phẩm, review, mua sắm, khuyến mãi'
                ],
                [
                    'name' => 'tiktok_target_duration',
                    'type' => 'number',
                    'label' => 'Độ dài TikTok mục tiêu (giây)',
                    'placeholder' => 'Độ dài tối ưu cho TikTok (15, 30, 60)'
                ],
                [
                    'name' => 'youtube_target_duration',
                    'type' => 'number',
                    'label' => 'Độ dài YouTube mục tiêu (giây)',
                    'placeholder' => 'Độ dài tối ưu cho YouTube (60, 120, 300)'
                ],
                [
                    'name' => 'product_showcase_time',
                    'type' => 'number',
                    'label' => 'Thời gian showcase sản phẩm (giây)',
                    'placeholder' => 'Thời gian dành cho mỗi sản phẩm (3, 5, 8)'
                ],
                [
                    'name' => 'cta_duration',
                    'type' => 'number',
                    'label' => 'Thời gian Call-to-Action (giây)',
                    'placeholder' => 'Thời gian hiển thị CTA cuối video (3, 5)'
                ]
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => $adminUser->id
        ]);

        // Template 4: Duration Control Demo
        VideoTemplate::create([
            'name' => 'Duration Control Demo',
            'description' => 'Template demo để test các tùy chọn kiểm soát độ dài video',
            'category' => 'tutorial',
            'settings' => [
                'platform' => 'both',
                'media_type' => 'images',
                'enable_subtitle' => true,
                'subtitle_source' => 'auto',
                'subtitle_position' => 'bottom',
                'subtitle_size' => 'medium',
                'subtitle_color' => 'white',
                'subtitle_background' => 'solid_black',
                'subtitle_timing_mode' => 'auto',
                'tiktok_resolution' => '1080x1920',
                'youtube_resolution' => '1920x1080',
                'default_image_duration' => 4,
                'default_transition_effect' => 'fade',
                'transition_duration' => 0.5,
                'audio_source' => 'tts',
                'tts_voice' => 'vi-VN-NamMinhNeural',
                'tts_speed' => 1.0,
                'tts_volume' => 100,
                'duration_based_on' => 'images', // Will be overridden by user choice
                'sync_with_audio' => false
            ],
            'required_inputs' => [
                [
                    'name' => 'duration_strategy',
                    'type' => 'select',
                    'label' => 'Chiến lược độ dài video',
                    'placeholder' => 'Chọn cách kiểm soát độ dài video',
                    'options' => [
                        'auto_images' => 'Tự động theo số ảnh',
                        'audio_sync' => 'Đồng bộ với âm thanh',
                        'fixed_time' => 'Thời gian cố định',
                        'custom_control' => 'Kiểm soát tùy chỉnh'
                    ]
                ],
                [
                    'name' => 'demo_content',
                    'type' => 'textarea',
                    'label' => 'Nội dung demo',
                    'placeholder' => 'Nhập nội dung để test duration control...'
                ]
            ],
            'optional_inputs' => [
                [
                    'name' => 'demo_images',
                    'type' => 'images',
                    'label' => 'Ảnh demo',
                    'placeholder' => 'Upload ảnh để test (2-5 ảnh)'
                ],
                [
                    'name' => 'demo_audio',
                    'type' => 'audio',
                    'label' => 'Audio demo',
                    'placeholder' => 'Upload audio để test sync'
                ],
                [
                    'name' => 'fixed_duration_value',
                    'type' => 'number',
                    'label' => 'Độ dài cố định (giây)',
                    'placeholder' => 'Nhập số giây (15, 30, 60, 120)'
                ],
                [
                    'name' => 'image_display_time',
                    'type' => 'number',
                    'label' => 'Thời gian hiển thị mỗi ảnh (giây)',
                    'placeholder' => 'Thời gian cho mỗi ảnh (2, 3, 5, 8)'
                ],
                [
                    'name' => 'sync_tolerance_value',
                    'type' => 'number',
                    'label' => 'Dung sai đồng bộ (giây)',
                    'placeholder' => 'Dung sai khi sync với audio (1, 2, 3)'
                ],
                [
                    'name' => 'max_duration_limit',
                    'type' => 'number',
                    'label' => 'Giới hạn độ dài tối đa (giây)',
                    'placeholder' => 'Giới hạn tối đa (60, 120, 180, 300)'
                ]
            ],
            'is_active' => true,
            'is_public' => true,
            'created_by' => $adminUser->id
        ]);

        $this->command->info('Video templates seeded successfully!');
    }
}
