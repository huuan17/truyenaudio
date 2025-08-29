<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class VBeeService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.vbee.access_token', env('VBEE_ACCESS_TOKEN'));
        $this->baseUrl = config('services.vbee.base_url', env('VBEE_BASE_URL', 'https://api.vbee.vn'));

        Log::info('VBee Service initialized', [
            'api_key_configured' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey ?? ''),
            'base_url' => $this->baseUrl,
            'env_access_token' => env('VBEE_ACCESS_TOKEN') ? 'SET' : 'NOT_SET',
            'config_access_token' => config('services.vbee.access_token') ? 'SET' : 'NOT_SET'
        ]);
    }

    /**
     * Convert text to speech using VBee API
     */
    public function textToSpeech($text, $outputPath, $options = [])
    {
        try {
            // Ensure text is a string
            if (!is_string($text)) {
                $text = (string) $text;
            }

            Log::info('VBee TTS: Starting text to speech conversion', [
                'text_length' => strlen($text),
                'output_path' => $outputPath,
                'options' => $options
            ]);

            // Default options
            $voice = $options['voice'] ?? 'hn_female_ngochuyen_full_48k-fhg';
            $speed = $options['speed'] ?? 1.0;
            $volume = $options['volume'] ?? 1.0;

            // For demo purposes, create a simple audio file using system TTS or fallback
            if (!$this->apiKey) {
                Log::warning('VBee API key not configured, using fallback TTS');
                return $this->createFallbackTTS($text, $outputPath, $options);
            }

            Log::info('VBee TTS: API key configured, attempting VBee API call', [
                'api_key_length' => strlen($this->apiKey),
                'base_url' => $this->baseUrl,
                'voice' => $voice,
                'text_preview' => substr($text, 0, 50)
            ]);

            // Make API request to VBee
            $requestData = [
                'app_id' => config('services.vbee.app_id', env('VBEE_APP_ID')),
                'input_text' => $text,
                'voice_code' => $voice,
                'speed_rate' => $speed,
                'audio_type' => 'mp3',
                'callback_url' => config('app.url') . '/api/vbee/callback'
            ];

            Log::info('VBee TTS: Making API request', [
                'url' => $this->baseUrl . '/synthesize',
                'request_data' => array_merge($requestData, ['input_text' => substr($text, 0, 50) . '...'])
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/tts', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('VBee TTS: API response received', [
                    'response_keys' => array_keys($responseData ?? []),
                    'status' => $responseData['status'] ?? 'unknown'
                ]);

                // Check if request was successful and get request_id
                if (isset($responseData['status']) && $responseData['status'] == 1 && isset($responseData['result']['request_id'])) {
                    $requestId = $responseData['result']['request_id'];

                    Log::info('VBee TTS: Request submitted successfully, polling for result', [
                        'request_id' => $requestId
                    ]);

                    // Poll for result (wait up to 60 seconds)
                    for ($i = 0; $i < 60; $i++) {
                        sleep(2);

                        $statusResponse = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json'
                        ])->get($this->baseUrl . '/tts/' . $requestId);

                        if ($statusResponse->successful()) {
                            $statusData = $statusResponse->json();

                            Log::info('VBee TTS: Polling status', [
                                'attempt' => $i + 1,
                                'request_id' => $requestId,
                                'status_data' => $statusData
                            ]);

                            if (isset($statusData['result']['status']) && ($statusData['result']['status'] == 'COMPLETED' || $statusData['result']['status'] == 'SUCCESS')) {
                                if (isset($statusData['result']['audio_url']) || isset($statusData['result']['audio_link'])) {
                                    $audioUrl = $statusData['result']['audio_url'] ?? $statusData['result']['audio_link'];
                                    // Download audio from URL
                                    $audioResponse = Http::get($audioUrl);
                                    if ($audioResponse->successful()) {
                                        file_put_contents($outputPath, $audioResponse->body());

                                        Log::info('VBee TTS: Successfully created audio file from URL', [
                                            'output_path' => $outputPath,
                                            'file_size' => filesize($outputPath),
                                            'audio_url' => $audioUrl,
                                            'polling_attempts' => $i + 1,
                                            'status' => $statusData['result']['status']
                                        ]);

                                        return $outputPath;
                                    }
                                }
                            } elseif (isset($statusData['result']['status']) && $statusData['result']['status'] == 'FAILED') {
                                Log::error('VBee TTS: Request failed', [
                                    'request_id' => $requestId,
                                    'status_data' => $statusData
                                ]);
                                break;
                            }
                        } else {
                            Log::warning('VBee TTS: Status polling failed', [
                                'attempt' => $i + 1,
                                'request_id' => $requestId,
                                'status_code' => $statusResponse->status(),
                                'response' => $statusResponse->body()
                            ]);
                        }
                    }

                    Log::warning('VBee TTS: Polling timeout or failed', [
                        'request_id' => $requestId,
                        'polling_attempts' => 60
                    ]);
                }

                Log::error('VBee TTS: API response missing audio data or failed', [
                    'response' => $responseData
                ]);

                // Fallback to local TTS
                return $this->createFallbackTTS($text, $outputPath, $options);
            } else {
                Log::error('VBee TTS: API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                // Fallback to local TTS
                return $this->createFallbackTTS($text, $outputPath, $options);
            }

        } catch (\Exception $e) {
            Log::error('VBee TTS: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to local TTS
            return $this->createFallbackTTS($text, $outputPath, $options);
        }
    }

    /**
     * Create fallback TTS using system tools or silent audio
     */
    private function createFallbackTTS($text, $outputPath, $options = [])
    {
        try {
            // Ensure text is a string
            if (!is_string($text)) {
                $text = (string) $text;
            }

            Log::info('VBee TTS: Using fallback TTS method', [
                'text_length' => strlen($text),
                'output_path' => $outputPath
            ]);

            // Calculate duration based on text length (rough estimate: 150 words per minute)
            $wordCount = str_word_count($text);
            $duration = max(3, $wordCount / 2.5); // Minimum 3 seconds, ~150 WPM

            // Try to use Windows SAPI for TTS (if available)
            if (PHP_OS_FAMILY === 'Windows') {
                $result = $this->createWindowsTTS($text, $outputPath, $duration);
                if ($result) {
                    return $result;
                }
            }

            // Fallback: Create silent audio with calculated duration
            return $this->createSilentAudio($outputPath, $duration);

        } catch (\Exception $e) {
            Log::error('VBee TTS: Fallback TTS failed', [
                'error' => $e->getMessage()
            ]);
            
            // Last resort: Create 5-second silent audio
            return $this->createSilentAudio($outputPath, 5);
        }
    }

    /**
     * Create TTS using Windows SAPI
     */
    private function createWindowsTTS($text, $outputPath, $duration)
    {
        try {
            // Ensure text is a string
            if (!is_string($text)) {
                $text = (string) $text;
            }

            // Create a temporary VBS script for Windows TTS
            $vbsScript = sys_get_temp_dir() . '/tts_' . uniqid() . '.vbs';
            $wavPath = str_replace('.mp3', '.wav', $outputPath);
            
            $vbsContent = 'Set objVoice = CreateObject("SAPI.SpVoice")' . "\n";
            $vbsContent .= 'Set objFile = CreateObject("SAPI.SpFileStream")' . "\n";
            $vbsContent .= 'objFile.Open "' . str_replace('/', '\\', $wavPath) . '", 3' . "\n";
            $vbsContent .= 'Set objVoice.AudioOutputStream = objFile' . "\n";

            // Try to set Vietnamese voice if available
            $vbsContent .= 'Set objVoices = objVoice.GetVoices()' . "\n";
            $vbsContent .= 'For i = 0 To objVoices.Count - 1' . "\n";
            $vbsContent .= '    Set objVoiceItem = objVoices.Item(i)' . "\n";
            $vbsContent .= '    If InStr(LCase(objVoiceItem.GetDescription()), "vietnamese") > 0 Or InStr(LCase(objVoiceItem.GetDescription()), "vietnam") > 0 Then' . "\n";
            $vbsContent .= '        Set objVoice.Voice = objVoiceItem' . "\n";
            $vbsContent .= '        Exit For' . "\n";
            $vbsContent .= '    End If' . "\n";
            $vbsContent .= 'Next' . "\n";

            $vbsContent .= 'objVoice.Rate = 0' . "\n";
            $vbsContent .= 'objVoice.Volume = 100' . "\n";
            $vbsContent .= 'objVoice.Speak "' . addslashes($text) . '"' . "\n";
            $vbsContent .= 'objFile.Close' . "\n";
            
            file_put_contents($vbsScript, $vbsContent);
            
            // Execute VBS script
            $command = 'cscript //nologo "' . $vbsScript . '"';
            exec($command, $output, $returnCode);
            
            // Clean up VBS script
            unlink($vbsScript);
            
            if ($returnCode === 0 && file_exists($wavPath)) {
                // Convert WAV to MP3 using FFmpeg
                $ffmpegCommand = 'ffmpeg -i "' . $wavPath . '" -acodec mp3 -ab 128k "' . $outputPath . '" -y';
                exec($ffmpegCommand, $ffmpegOutput, $ffmpegReturn);
                
                // Clean up WAV file
                if (file_exists($wavPath)) {
                    unlink($wavPath);
                }
                
                if ($ffmpegReturn === 0 && file_exists($outputPath)) {
                    Log::info('VBee TTS: Windows SAPI TTS successful', [
                        'output_path' => $outputPath,
                        'file_size' => filesize($outputPath)
                    ]);
                    return $outputPath;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('VBee TTS: Windows TTS failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create silent audio file with specified duration
     */
    private function createSilentAudio($outputPath, $duration)
    {
        try {
            // Create silent audio using FFmpeg
            $command = sprintf(
                'ffmpeg -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t %s -c:a mp3 -b:a 128k "%s" -y',
                $duration,
                $outputPath
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                Log::info('VBee TTS: Created silent audio as fallback', [
                    'output_path' => $outputPath,
                    'duration' => $duration,
                    'file_size' => filesize($outputPath)
                ]);
                return $outputPath;
            }
            
            Log::error('VBee TTS: Failed to create silent audio', [
                'command' => $command,
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('VBee TTS: Silent audio creation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
