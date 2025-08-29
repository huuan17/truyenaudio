<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VBeeService;
use Illuminate\Support\Facades\Log;

class TestVBeeCommand extends Command
{
    protected $signature = 'test:vbee {text=Hello}';
    protected $description = 'Test VBee TTS service';

    public function handle()
    {
        $text = $this->argument('text');
        $this->info("Testing VBee TTS with text: {$text}");
        
        $vbeeService = new VBeeService();
        
        $outputPath = storage_path('app/temp/test_tts.mp3');
        
        try {
            $result = $vbeeService->textToSpeech($text, $outputPath);
            
            if ($result) {
                $this->info("TTS created successfully: {$result}");
                $this->info("File size: " . filesize($result) . " bytes");
            } else {
                $this->error("TTS creation failed");
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
            Log::error('VBee test failed', ['error' => $e->getMessage()]);
        }
    }
}
