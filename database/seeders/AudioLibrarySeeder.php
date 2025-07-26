<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AudioLibrary;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AudioLibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $adminUser = User::where('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }

        if (!$adminUser) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Create sample audio library entries
        $sampleAudios = [
            [
                'title' => 'Nhạc nền thư giãn - Peaceful Morning',
                'description' => 'Nhạc nền thư giãn phù hợp cho video meditation, yoga, hoặc nội dung tĩnh lặng',
                'category' => 'music',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => null,
                'mood' => 'calm',
                'tags' => ['music', 'background', 'relaxing', 'peaceful', 'meditation'],
                'is_public' => true,
                'duration' => 180, // 3 minutes
                'format' => 'MP3',
                'bitrate' => 128,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Upbeat Background Music - Energy Boost',
                'description' => 'Nhạc nền sôi động cho video marketing, workout, hoặc nội dung năng lượng cao',
                'category' => 'music',
                'source_type' => 'upload',
                'language' => 'en',
                'voice_type' => null,
                'mood' => 'energetic',
                'tags' => ['music', 'background', 'upbeat', 'energetic', 'positive'],
                'is_public' => true,
                'duration' => 120,
                'format' => 'MP3',
                'bitrate' => 192,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Cinematic Epic Music - Hero Journey',
                'description' => 'Nhạc nền điện ảnh hùng tráng cho video trailer, giới thiệu dự án lớn',
                'category' => 'music',
                'source_type' => 'upload',
                'language' => 'en',
                'voice_type' => null,
                'mood' => 'epic',
                'tags' => ['music', 'background', 'cinematic', 'epic', 'dramatic'],
                'is_public' => true,
                'duration' => 240,
                'format' => 'MP3',
                'bitrate' => 256,
                'sample_rate' => 48000
            ],
            [
                'title' => 'Nature Sounds - Forest Rain',
                'description' => 'Âm thanh tự nhiên: tiếng mưa rừng, phù hợp cho video thư giãn, thiền',
                'category' => 'music',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => null,
                'mood' => 'calm',
                'tags' => ['nature', 'rain', 'forest', 'ambient', 'relaxing'],
                'is_public' => true,
                'duration' => 300,
                'format' => 'MP3',
                'bitrate' => 128,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Story Background Music - Gentle Piano',
                'description' => 'Nhạc nền piano nhẹ nhàng cho video kể chuyện, audiobook',
                'category' => 'music',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => null,
                'mood' => 'gentle',
                'tags' => ['music', 'background', 'story', 'piano', 'gentle'],
                'is_public' => true,
                'duration' => 200,
                'format' => 'MP3',
                'bitrate' => 160,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Giọng đọc nữ - Hướng dẫn sản phẩm',
                'description' => 'Giọng đọc nữ chuyên nghiệp cho video giới thiệu sản phẩm và dịch vụ',
                'category' => 'voice',
                'source_type' => 'tts',
                'language' => 'vi',
                'voice_type' => 'female',
                'mood' => 'neutral',
                'tags' => ['giọng đọc', 'nữ', 'sản phẩm', 'hướng dẫn'],
                'is_public' => true,
                'duration' => 45,
                'format' => 'MP3',
                'bitrate' => 128,
                'sample_rate' => 22050
            ],
            [
                'title' => 'Hiệu ứng âm thanh - Notification Bell',
                'description' => 'Âm thanh chuông thông báo cho video tutorial và presentation',
                'category' => 'effect',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => null,
                'mood' => 'neutral',
                'tags' => ['hiệu ứng', 'chuông', 'notification', 'bell'],
                'is_public' => true,
                'duration' => 3,
                'format' => 'MP3',
                'bitrate' => 128,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Podcast Intro - Tech Talk',
                'description' => 'Intro chuyên nghiệp cho podcast về công nghệ',
                'category' => 'podcast',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => 'male',
                'mood' => 'energetic',
                'tags' => ['podcast', 'intro', 'tech', 'công nghệ'],
                'is_public' => true,
                'duration' => 15,
                'format' => 'MP3',
                'bitrate' => 192,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Nhạc nền Marketing - Upbeat Corporate',
                'description' => 'Nhạc nền năng động cho video marketing và quảng cáo doanh nghiệp',
                'category' => 'marketing',
                'source_type' => 'upload',
                'language' => 'vi',
                'voice_type' => null,
                'mood' => 'energetic',
                'tags' => ['marketing', 'corporate', 'upbeat', 'quảng cáo'],
                'is_public' => true,
                'duration' => 120,
                'format' => 'MP3',
                'bitrate' => 160,
                'sample_rate' => 44100
            ],
            [
                'title' => 'Giọng đọc nam - Tutorial Voice',
                'description' => 'Giọng đọc nam chuyên nghiệp cho video hướng dẫn và tutorial',
                'category' => 'tutorial',
                'source_type' => 'tts',
                'language' => 'vi',
                'voice_type' => 'male',
                'mood' => 'neutral',
                'tags' => ['tutorial', 'hướng dẫn', 'giọng nam', 'education'],
                'is_public' => true,
                'duration' => 60,
                'format' => 'MP3',
                'bitrate' => 128,
                'sample_rate' => 22050
            ]
        ];

        foreach ($sampleAudios as $audioData) {
            // Create a dummy file path (in real scenario, you would have actual audio files)
            $fileName = time() . '_' . str_replace(' ', '_', strtolower($audioData['title'])) . '.mp3';
            $filePath = 'audio-library/' . $fileName;

            // Create the audio library entry
            AudioLibrary::create([
                'title' => $audioData['title'],
                'description' => $audioData['description'],
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_extension' => 'mp3',
                'file_size' => rand(1024 * 100, 1024 * 1024 * 5), // Random size between 100KB - 5MB
                'duration' => $audioData['duration'],
                'format' => $audioData['format'],
                'bitrate' => $audioData['bitrate'],
                'sample_rate' => $audioData['sample_rate'],
                'category' => $audioData['category'],
                'source_type' => $audioData['source_type'],
                'language' => $audioData['language'],
                'voice_type' => $audioData['voice_type'],
                'mood' => $audioData['mood'],
                'tags' => $audioData['tags'],
                'metadata' => [
                    'created_by_seeder' => true,
                    'sample_data' => true
                ],
                'is_public' => $audioData['is_public'],
                'is_active' => true,
                'uploaded_by' => $adminUser->id,
                'usage_count' => rand(0, 10),
                'last_used_at' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null
            ]);
        }

        $this->command->info('Audio library seeded successfully with ' . count($sampleAudios) . ' sample audio files!');
    }
}
