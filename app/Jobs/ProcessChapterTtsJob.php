<?php

namespace App\Jobs;

use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProcessChapterTtsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chapterId;
    protected $voice;
    protected $bitrate;
    protected $speed;
    protected $volume;

    /**
     * Create a new job instance.
     */
    public function __construct($chapterId, $voice, $bitrate, $speed, $volume)
    {
        $this->chapterId = $chapterId;
        $this->voice = $voice;
        $this->bitrate = $bitrate;
        $this->speed = $speed;
        $this->volume = $volume;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chapter = Chapter::find($this->chapterId);

        if (!$chapter) {
            Log::error("TTS Job: Chapter not found", [
                'chapter_id' => $this->chapterId,
                'job_id' => $this->job->getJobId()
            ]);
            return;
        }

        Log::info("TTS Job: Starting TTS processing", [
            'chapter_id' => $this->chapterId,
            'chapter_number' => $chapter->chapter_number,
            'story_id' => $chapter->story_id,
            'story_title' => $chapter->story->title,
            'voice' => $this->voice,
            'bitrate' => $this->bitrate,
            'speed' => $this->speed,
            'volume' => $this->volume,
            'job_id' => $this->job->getJobId()
        ]);

        // Update chapter status to processing
        $chapter->update([
            'audio_status' => 'processing',
            'tts_progress' => 10
        ]);

        try {
            // Call the TTS command directly
            $exitCode = Artisan::call('vbee:chapter-tts', [
                '--chapter_id' => $this->chapterId,
                '--voice' => $this->voice,
                '--bitrate' => $this->bitrate,
                '--speed' => $this->speed,
                '--volume' => $this->volume,
            ]);

            $output = Artisan::output();

            Log::info("TTS Job: Command completed", [
                'chapter_id' => $this->chapterId,
                'exit_code' => $exitCode,
                'output' => $output,
                'job_id' => $this->job->getJobId()
            ]);

            // Check if TTS was successful
            $chapter->refresh();
            if ($chapter->audio_status === 'completed' && $chapter->audio_file_path) {
                Log::info("TTS Job: Successfully completed", [
                    'chapter_id' => $this->chapterId,
                    'audio_file_path' => $chapter->audio_file_path,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::warning("TTS Job: Command completed but chapter status not updated", [
                    'chapter_id' => $this->chapterId,
                    'audio_status' => $chapter->audio_status,
                    'audio_file_path' => $chapter->audio_file_path,
                    'job_id' => $this->job->getJobId()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("TTS Job: Exception occurred", [
                'chapter_id' => $this->chapterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job->getJobId()
            ]);

            // Update chapter status to failed
            $chapter->update([
                'audio_status' => 'failed',
                'tts_progress' => 0
            ]);

            throw $e;
        }
    }
}
