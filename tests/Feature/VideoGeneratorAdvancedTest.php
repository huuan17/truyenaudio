<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class VideoGeneratorAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        Storage::fake('public');
    }

    /** @test */
    public function it_can_access_video_generator_with_new_features()
    {
        $response = $this->actingAs($this->user)
                         ->get(route('admin.video-generator.index'));

        $response->assertStatus(200);
        $response->assertSee('Logo & Watermark');
        $response->assertSee('Cài đặt thời lượng video');
        $response->assertSee('Chế độ hiển thị');
    }

    /** @test */
    public function it_validates_new_media_settings()
    {
        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate'), [
                             'platform' => 'tiktok',
                             'media_type' => 'images',
                             'audio_source' => 'tts',
                             'tts_text' => 'Test content',
                             'duration_based_on' => 'custom',
                             'custom_duration' => 30,
                             'enable_logo' => true,
                             'logo_source' => 'library',
                             'selected_logo' => 'logo1.png'
                         ]);

        // Should redirect to queue (validation passed)
        $response->assertRedirect(route('admin.video-queue.index'));
    }

    /** @test */
    public function it_validates_mixed_media_settings()
    {
        $image = UploadedFile::fake()->image('test.jpg');
        $video = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate'), [
                             'platform' => 'youtube',
                             'media_type' => 'mixed',
                             'mixed_mode' => 'overlay',
                             'mixed_media' => [$image, $video],
                             'overlay_position' => 'top-right',
                             'overlay_size' => 'medium',
                             'audio_source' => 'tts',
                             'tts_text' => 'Test overlay content',
                             'duration_based_on' => 'audio'
                         ]);

        $response->assertRedirect(route('admin.video-queue.index'));
    }

    /** @test */
    public function it_can_calculate_duration_via_ajax()
    {
        $response = $this->actingAs($this->user)
                         ->postJson(route('admin.video-generator.calculate-duration'), [
                             'duration_based_on' => 'images',
                             'default_image_duration' => 3,
                             'transition_duration' => 0.5,
                             'images' => [
                                 ['duration' => 3],
                                 ['duration' => 4],
                                 ['duration' => 2.5]
                             ]
                         ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'image_count' => 3
        ]);
        $response->assertJsonStructure([
            'success',
            'duration',
            'formatted_duration',
            'image_count',
            'settings'
        ]);
    }

    /** @test */
    public function it_can_validate_media_files_via_ajax()
    {
        $image = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
                         ->postJson(route('admin.video-generator.validate-media'), [
                             'media_type' => 'images',
                             'images' => [$image]
                         ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'results' => [
                'images' => [
                    '*' => [
                        'valid',
                        'size',
                        'type',
                        'name'
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function it_can_get_logo_library_via_ajax()
    {
        $response = $this->actingAs($this->user)
                         ->getJson(route('admin.video-generator.logo-library'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'logos'
        ]);
    }

    /** @test */
    public function it_validates_logo_settings()
    {
        $logoFile = UploadedFile::fake()->image('custom-logo.png');

        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate'), [
                             'platform' => 'tiktok',
                             'media_type' => 'images',
                             'images' => [UploadedFile::fake()->image('test.jpg')],
                             'audio_source' => 'tts',
                             'tts_text' => 'Test with custom logo',
                             'duration_based_on' => 'images',
                             'enable_logo' => true,
                             'logo_source' => 'upload',
                             'logo_file' => $logoFile,
                             'logo_position' => 'bottom-right',
                             'logo_size' => 'custom',
                             'logo_width' => 150,
                             'logo_height' => 150,
                             'logo_opacity' => 0.8
                         ]);

        $response->assertRedirect(route('admin.video-queue.index'));
    }

    /** @test */
    public function it_validates_subtitle_settings()
    {
        $subtitleFile = UploadedFile::fake()->create('subtitles.srt', 100, 'text/plain');

        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate'), [
                             'platform' => 'youtube',
                             'media_type' => 'images',
                             'images' => [UploadedFile::fake()->image('test.jpg')],
                             'audio_source' => 'tts',
                             'tts_text' => 'Test with subtitles',
                             'duration_based_on' => 'audio',
                             'enable_subtitle' => true,
                             'subtitle_source' => 'upload',
                             'subtitle_file' => $subtitleFile,
                             'subtitle_position' => 'bottom',
                             'subtitle_size' => 'large',
                             'subtitle_color' => 'white',
                             'subtitle_background' => 'black'
                         ]);

        $response->assertRedirect(route('admin.video-queue.index'));
    }

    /** @test */
    public function it_can_generate_for_both_platforms()
    {
        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate'), [
                             'platform' => 'both',
                             'media_type' => 'images',
                             'images' => [UploadedFile::fake()->image('test.jpg')],
                             'audio_source' => 'tts',
                             'tts_text' => 'Test for both platforms',
                             'duration_based_on' => 'images',
                             'both_output_prefix' => 'test-video'
                         ]);

        $response->assertRedirect(route('admin.video-queue.index'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('cả TikTok và YouTube', session('success'));
    }

    /** @test */
    public function it_validates_batch_generation_with_new_features()
    {
        $response = $this->actingAs($this->user)
                         ->post(route('admin.video-generator.generate-batch'), [
                             'platform' => 'tiktok',
                             'media_type' => 'images',
                             'batch_mode' => 'template',
                             'template_texts' => "Video 1 content\n---\nVideo 2 content\n---\nVideo 3 content",
                             'images' => [
                                 UploadedFile::fake()->image('test1.jpg'),
                                 UploadedFile::fake()->image('test2.jpg')
                             ],
                             'duration_based_on' => 'images',
                             'default_image_duration' => 4,
                             'batch_priority' => 'normal',
                             'batch_delay' => 5
                         ]);

        $response->assertRedirect(route('admin.video-queue.index'));
    }
}
