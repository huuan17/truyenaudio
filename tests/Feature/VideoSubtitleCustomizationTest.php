<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\VideoSubtitleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class VideoSubtitleCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private $videoSubtitleService;
    private $testVideoPath;
    private $testOutputDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->videoSubtitleService = new VideoSubtitleService();
        
        // Create test directories
        $this->testOutputDir = storage_path('app/temp/test_subtitle_customization');
        File::makeDirectory($this->testOutputDir, 0755, true);
        
        // Create a simple test video (1 second black video)
        $this->testVideoPath = $this->testOutputDir . '/test_video.mp4';
        $this->createTestVideo();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testOutputDir)) {
            File::deleteDirectory($this->testOutputDir);
        }
        
        parent::tearDown();
    }

    private function createTestVideo()
    {
        // Create a simple 5-second black video for testing
        $cmd = "ffmpeg -f lavfi -i color=black:size=640x480:duration=5 -c:v libx264 -t 5 \"{$this->testVideoPath}\" -y";
        exec($cmd);
    }

    /** @test */
    public function test_subtitle_with_custom_font_size()
    {
        $options = [
            'font_size' => 'large', // Should convert to 32
            'output_path' => $this->testOutputDir . '/test_large_font.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Đây là subtitle với font size lớn',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with large font size');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_custom_colors()
    {
        $options = [
            'font_color' => 'yellow',
            'outline_color' => 'red',
            'background_color' => 'transparent_black',
            'output_path' => $this->testOutputDir . '/test_colors.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle với màu sắc tùy chỉnh',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with custom colors');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_different_positions()
    {
        $positions = ['top', 'center', 'bottom', 'top-left', 'bottom-right'];
        
        foreach ($positions as $position) {
            $options = [
                'position' => $position,
                'output_path' => $this->testOutputDir . "/test_position_{$position}.mp4"
            ];

            $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
                $this->testVideoPath,
                "Subtitle ở vị trí {$position}",
                5,
                $options
            );

            $this->assertTrue($result['success'], "Should create video with position: {$position}");
            $this->assertFileExists($options['output_path']);
        }
    }

    /** @test */
    public function test_subtitle_with_outline_and_shadow()
    {
        $options = [
            'outline_width' => 3,
            'outline_color' => 'blue',
            'shadow' => true,
            'shadow_x' => 3,
            'shadow_y' => 3,
            'shadow_color' => 'gray',
            'output_path' => $this->testOutputDir . '/test_outline_shadow.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle với viền và bóng đổ',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with outline and shadow');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_background_box()
    {
        $options = [
            'background_color' => 'solid_black',
            'box_border_width' => 8,
            'font_color' => 'white',
            'output_path' => $this->testOutputDir . '/test_background_box.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle với nền đen đặc',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with background box');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_bold_and_italic()
    {
        $options = [
            'bold' => true,
            'italic' => true,
            'font_size' => 28,
            'output_path' => $this->testOutputDir . '/test_bold_italic.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle in đậm và nghiêng',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with bold and italic text');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_custom_margin()
    {
        $options = [
            'margin' => 100,
            'position' => 'bottom',
            'output_path' => $this->testOutputDir . '/test_custom_margin.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle với margin tùy chỉnh',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with custom margin');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_with_hex_colors()
    {
        $options = [
            'font_color' => '#FF6600', // Orange
            'outline_color' => '#0066FF', // Blue
            'output_path' => $this->testOutputDir . '/test_hex_colors.mp4'
        ];

        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $this->testVideoPath,
            'Subtitle với màu hex',
            5,
            $options
        );

        $this->assertTrue($result['success'], 'Should create video with hex colors');
        $this->assertFileExists($options['output_path']);
    }

    /** @test */
    public function test_subtitle_options_processing()
    {
        // Test that string values are properly converted
        $options = [
            'font_size' => 'extra-large', // Should convert to 40
            'outline_width' => '5', // Should convert to int 5
            'margin' => '75', // Should convert to int 75
        ];

        $reflection = new \ReflectionClass($this->videoSubtitleService);
        $method = $reflection->getMethod('processSubtitleOptions');
        $method->setAccessible(true);

        $processed = $method->invoke($this->videoSubtitleService, $options);

        $this->assertEquals(40, $processed['font_size']);
        $this->assertEquals(5, $processed['outline_width']);
        $this->assertEquals(75, $processed['margin']);
    }

    /** @test */
    public function test_color_conversion_to_ass_format()
    {
        $reflection = new \ReflectionClass($this->videoSubtitleService);
        $method = $reflection->getMethod('convertColorToASS');
        $method->setAccessible(true);

        // Test hex color conversion
        $assColor = $method->invoke($this->videoSubtitleService, '#FF0000'); // Red
        $this->assertEquals('&H0000FF&', $assColor); // ASS format is BGR

        // Test named color conversion
        $assWhite = $method->invoke($this->videoSubtitleService, 'white');
        $this->assertEquals('&HFFFFFF&', $assWhite);
    }

    /** @test */
    public function test_position_alignment_mapping()
    {
        $reflection = new \ReflectionClass($this->videoSubtitleService);
        $method = $reflection->getMethod('getAlignmentFromPosition');
        $method->setAccessible(true);

        $this->assertEquals(2, $method->invoke($this->videoSubtitleService, 'bottom'));
        $this->assertEquals(8, $method->invoke($this->videoSubtitleService, 'top'));
        $this->assertEquals(5, $method->invoke($this->videoSubtitleService, 'center'));
        $this->assertEquals(1, $method->invoke($this->videoSubtitleService, 'bottom-left'));
        $this->assertEquals(9, $method->invoke($this->videoSubtitleService, 'top-right'));
    }
}
