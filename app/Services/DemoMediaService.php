<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DemoMediaService
{
    private $demoPath;
    
    public function __construct()
    {
        $this->demoPath = public_path('assets/demo');
    }

    /**
     * Get demo image based on template settings
     */
    public function getDemoImage($templateSettings = [])
    {
        // Create unique filename based on settings to cache different versions
        $settingsHash = md5(json_encode($templateSettings));
        $demoImagePath = $this->demoPath . '/images/demo-image-' . $settingsHash . '.jpg';

        // Always recreate demo image to reflect current settings
        $this->createDemoImage($demoImagePath, $templateSettings);

        return $demoImagePath;
    }

    /**
     * Get demo video based on template settings
     */
    public function getDemoVideo($templateSettings = [])
    {
        $demoVideoPath = $this->demoPath . '/videos/demo-video.mp4';
        
        // Create demo video if not exists
        if (!File::exists($demoVideoPath)) {
            $this->createDemoVideo($demoVideoPath, $templateSettings);
        }
        
        return $demoVideoPath;
    }

    /**
     * Get demo audio based on template settings
     */
    public function getDemoAudio($templateSettings = [])
    {
        $demoAudioPath = $this->demoPath . '/audio/demo-audio.mp3';
        
        // Create demo audio if not exists
        if (!File::exists($demoAudioPath)) {
            $this->createDemoAudio($demoAudioPath, $templateSettings);
        }
        
        return $demoAudioPath;
    }

    /**
     * Create demo image with template branding
     */
    private function createDemoImage($path, $settings = [])
    {
        try {
            // Ensure directory exists
            $dir = dirname($path);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            // Get resolution from template settings
            $resolution = $settings['resolution'] ?? '1920x1080';
            list($width, $height) = explode('x', $resolution);
            $width = (int) $width;
            $height = (int) $height;
            
            $image = imagecreate($width, $height);
            
            // Colors based on template settings
            $bgColor = $this->getTemplateColor($settings, 'background', [52, 152, 219]); // Blue
            $textColor = $this->getTemplateColor($settings, 'text', [255, 255, 255]); // White
            $accentColor = $this->getTemplateColor($settings, 'accent', [241, 196, 15]); // Yellow
            
            $background = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
            $white = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
            $accent = imagecolorallocate($image, $accentColor[0], $accentColor[1], $accentColor[2]);
            
            // Fill background
            imagefill($image, 0, 0, $background);
            
            // Add main title
            $title = 'DEMO TEMPLATE';
            $this->addTextToImage($image, $title, 5, $white, $width/2 - 200, $height/2 - 100);
            
            // Add platform info
            $platform = strtoupper($settings['platform'] ?? 'TIKTOK');
            $this->addTextToImage($image, $platform, 4, $accent, $width/2 - 100, $height/2 - 50);
            
            // Add subtitle area indicator with template settings
            $subtitlePos = $settings['subtitle_position'] ?? 'bottom';
            $subtitleCoords = $this->getSubtitlePosition($subtitlePos, $width, $height);
            $subtitleSize = $this->getImageFontSize($settings['subtitle_size'] ?? 24, $width);
            $this->addTextToImage($image, 'Subtitle tiếng Việt ở đây', $subtitleSize, $white, $subtitleCoords[0], $subtitleCoords[1]);

            // Add logo area indicator (if enabled)
            if ($settings['enable_logo'] ?? false) {
                $logoPos = $settings['logo_position'] ?? 'bottom-right';
                $logoCoords = $this->getLogoPosition($logoPos, $width, $height);
                $logoSize = $this->getImageFontSize($settings['logo_size'] ?? 100, $width);
                $this->addTextToImage($image, 'LOGO', $logoSize, $accent, $logoCoords[0], $logoCoords[1]);
            }

            // Add resolution indicator
            $this->addTextToImage($image, $resolution, 2, $accent, 20, 20);
            
            // Save image
            imagejpeg($image, $path, 90);
            imagedestroy($image);
            
            Log::info('Demo image created', ['path' => $path, 'settings' => $settings]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create demo image', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create demo video
     */
    private function createDemoVideo($path, $settings = [])
    {
        try {
            // Ensure directory exists
            $dir = dirname($path);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $duration = $settings['custom_duration'] ?? 30;
            $resolution = $settings['resolution'] ?? '1920x1080';
            $fps = $settings['fps'] ?? 25;
            
            // Create demo video with template colors
            $bgColor = $this->getTemplateColorHex($settings, 'background', '3498db'); // Blue
            
            $cmd = "ffmpeg -f lavfi -i color={$bgColor}:size={$resolution}:duration={$duration} " .
                   "-c:v libx264 -pix_fmt yuv420p -r {$fps} \"{$path}\" -y";

            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0) {
                Log::info('Demo video created', ['path' => $path, 'settings' => $settings]);
            } else {
                Log::error('Failed to create demo video', ['return_code' => $returnCode]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to create demo video', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create demo audio
     */
    private function createDemoAudio($path, $settings = [])
    {
        try {
            // Ensure directory exists
            $dir = dirname($path);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $duration = $settings['custom_duration'] ?? 30;
            $frequency = 440; // A4 note
            
            // Create simple tone for demo
            $cmd = "ffmpeg -f lavfi -i sine=frequency={$frequency}:duration={$duration} " .
                   "-c:a mp3 -b:a 128k \"{$path}\" -y";

            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0) {
                Log::info('Demo audio created', ['path' => $path, 'settings' => $settings]);
            } else {
                Log::error('Failed to create demo audio', ['return_code' => $returnCode]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to create demo audio', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get template color from settings
     */
    private function getTemplateColor($settings, $type, $default)
    {
        $colorMap = [
            'background' => $settings['background_color'] ?? null,
            'text' => $settings['subtitle_color'] ?? null,
            'accent' => $settings['accent_color'] ?? null
        ];
        
        $color = $colorMap[$type] ?? null;
        
        if ($color && strpos($color, '#') === 0) {
            return $this->hexToRgb($color);
        }
        
        return $default;
    }

    /**
     * Get template color as hex
     */
    private function getTemplateColorHex($settings, $type, $default)
    {
        $rgb = $this->getTemplateColor($settings, $type, []);
        
        if (empty($rgb)) {
            return $default;
        }
        
        return sprintf('%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 6) {
            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2))
            ];
        }
        
        return [0, 0, 0];
    }

    /**
     * Add text to image
     */
    private function addTextToImage($image, $text, $font, $color, $x, $y)
    {
        imagestring($image, $font, $x, $y, $text, $color);
    }

    /**
     * Get logo position coordinates
     */
    private function getLogoPosition($position, $width, $height)
    {
        $margin = 50;

        switch ($position) {
            case 'top-left':
                return [$margin, $margin];
            case 'top-right':
                return [$width - 150, $margin];
            case 'bottom-left':
                return [$margin, $height - 100];
            case 'bottom-right':
            default:
                return [$width - 150, $height - 100];
        }
    }

    /**
     * Get subtitle position coordinates
     */
    private function getSubtitlePosition($position, $width, $height)
    {
        $margin = 50;

        switch ($position) {
            case 'top':
                return [$width/2 - 200, $margin + 50];
            case 'center':
                return [$width/2 - 200, $height/2];
            case 'bottom':
            default:
                return [$width/2 - 200, $height - 150];
        }
    }

    /**
     * Convert template font size to image font size
     */
    private function getImageFontSize($templateSize, $width)
    {
        // Scale font size based on resolution
        $baseWidth = 1920;
        $scale = $width / $baseWidth;

        // Convert template size (px) to image font size (1-5)
        $scaledSize = ($templateSize * $scale) / 20;
        return max(1, min(5, round($scaledSize)));
    }
}
