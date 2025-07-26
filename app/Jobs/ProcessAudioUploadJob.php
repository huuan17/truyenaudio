<?php

namespace App\Jobs;

use App\Models\AudioLibrary;
use App\Models\AudioUploadBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProcessAudioUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes per file
    public $tries = 3;

    protected $batchId;
    protected $fileData;
    protected $fileIndex;
    protected $uploadSettings;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId, $fileData, $fileIndex, $uploadSettings)
    {
        $this->batchId = $batchId;
        $this->fileData = $fileData;
        $this->fileIndex = $fileIndex;
        $this->uploadSettings = $uploadSettings;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->updateFileStatus('processing', 'Đang xử lý file...');

            // Move file from temp to permanent location
            $tempPath = $this->fileData['temp_path'];
            $fileName = time() . '_' . $this->fileIndex . '_' . Str::slug($this->fileData['title']) . '.' . $this->fileData['extension'];
            $filePath = 'audio-library/' . $fileName;

            // Move file
            if (!Storage::disk('public')->exists($tempPath)) {
                throw new \Exception('Temp file not found: ' . $tempPath);
            }

            Storage::disk('public')->move($tempPath, $filePath);

            // Update status to analyzing
            $this->updateFileStatus('processing', 'Đang phân tích metadata...');

            // Get audio metadata
            $fullPath = Storage::disk('public')->path($filePath);
            $metadata = $this->getAudioMetadata($fullPath);

            // Update status to saving
            $this->updateFileStatus('processing', 'Đang lưu vào database...');

            // Create AudioLibrary record
            $audio = AudioLibrary::create([
                'title' => $this->fileData['title'],
                'description' => $this->uploadSettings['description'] ?: "Audio file: {$this->fileData['title']}",
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_extension' => $this->fileData['extension'],
                'file_size' => $this->fileData['size'],
                'duration' => $metadata['duration'] ?? 0,
                'format' => $metadata['format'] ?? strtoupper($this->fileData['extension']),
                'bitrate' => $metadata['bitrate'] ?? null,
                'sample_rate' => $metadata['sample_rate'] ?? null,
                'category' => $this->uploadSettings['category'],
                'source_type' => $this->uploadSettings['source_type'],
                'language' => $this->uploadSettings['language'],
                'voice_type' => $this->uploadSettings['voice_type'],
                'mood' => $this->uploadSettings['mood'],
                'tags' => $this->uploadSettings['tags'] ?? [],
                'metadata' => array_merge($metadata, [
                    'batch_upload' => true,
                    'batch_id' => $this->batchId,
                    'batch_index' => $this->fileIndex,
                    'original_filename' => $this->fileData['original_name']
                ]),
                'is_public' => $this->uploadSettings['is_public'] ?? false,
                'uploaded_by' => $this->uploadSettings['user_id']
            ]);

            // Update status to completed
            $this->updateFileStatus('completed', 'Upload thành công!', $audio->id);

        } catch (\Exception $e) {
            Log::error('Audio upload job failed', [
                'batch_id' => $this->batchId,
                'file_index' => $this->fileIndex,
                'error' => $e->getMessage()
            ]);

            // Update status to failed
            $this->updateFileStatus('failed', 'Lỗi: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateFileStatus('failed', 'Upload thất bại: ' . $exception->getMessage());
    }

    /**
     * Update file status in batch
     */
    private function updateFileStatus($status, $message, $audioId = null)
    {
        $batch = AudioUploadBatch::find($this->batchId);
        if ($batch) {
            $files = $batch->files;
            $files[$this->fileIndex]['status'] = $status;
            $files[$this->fileIndex]['message'] = $message;
            $files[$this->fileIndex]['updated_at'] = now();
            
            if ($audioId) {
                $files[$this->fileIndex]['audio_id'] = $audioId;
            }

            $batch->update(['files' => $files]);

            // Update overall batch status
            $this->updateBatchStatus($batch);
        }
    }

    /**
     * Update overall batch status
     */
    private function updateBatchStatus($batch)
    {
        $files = $batch->files;
        $totalFiles = count($files);
        $completedFiles = collect($files)->where('status', 'completed')->count();
        $failedFiles = collect($files)->where('status', 'failed')->count();
        $processingFiles = collect($files)->where('status', 'processing')->count();

        $progress = ($completedFiles + $failedFiles) / $totalFiles * 100;

        $status = 'processing';
        if ($completedFiles + $failedFiles === $totalFiles) {
            $status = $failedFiles > 0 ? 'completed_with_errors' : 'completed';
        }

        $batch->update([
            'status' => $status,
            'progress' => $progress,
            'completed_files' => $completedFiles,
            'failed_files' => $failedFiles,
            'processing_files' => $processingFiles
        ]);
    }

    /**
     * Get audio metadata using ffprobe
     */
    private function getAudioMetadata($filePath)
    {
        try {
            // Try to get metadata using getID3 if available
            if (class_exists('\getID3')) {
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($filePath);
                
                return [
                    'duration' => $fileInfo['playtime_seconds'] ?? 0,
                    'bitrate' => $fileInfo['audio']['bitrate'] ?? null,
                    'sample_rate' => $fileInfo['audio']['sample_rate'] ?? null,
                    'format' => $fileInfo['fileformat'] ?? 'unknown',
                    'channels' => $fileInfo['audio']['channels'] ?? null,
                ];
            }

            // Fallback: basic file info
            return [
                'duration' => 0,
                'bitrate' => null,
                'sample_rate' => null,
                'format' => 'unknown',
                'channels' => null,
            ];
        } catch (\Exception $e) {
            Log::warning('Could not extract audio metadata', ['error' => $e->getMessage()]);
            return [
                'duration' => 0,
                'bitrate' => null,
                'sample_rate' => null,
                'format' => 'unknown',
                'channels' => null,
            ];
        }
    }
}
