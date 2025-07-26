<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudioUploadBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_files',
        'completed_files',
        'failed_files',
        'processing_files',
        'status',
        'progress',
        'files',
        'settings',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'files' => 'array',
        'settings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the user that owns the batch
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get batch status badge
     */
    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Chờ xử lý</span>';
            case 'processing':
                return '<span class="badge badge-info"><i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý</span>';
            case 'completed':
                return '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Hoàn thành</span>';
            case 'completed_with_errors':
                return '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Hoàn thành có lỗi</span>';
            case 'failed':
                return '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Thất bại</span>';
            default:
                return '<span class="badge badge-secondary">Không xác định</span>';
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        return round($this->progress, 1);
    }

    /**
     * Check if batch is completed
     */
    public function isCompleted()
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed']);
    }

    /**
     * Check if batch is processing
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Get successful audio files
     */
    public function getSuccessfulAudios()
    {
        $successfulIds = collect($this->files)
            ->where('status', 'completed')
            ->pluck('audio_id')
            ->filter();

        return AudioLibrary::whereIn('id', $successfulIds)->get();
    }

    /**
     * Get failed files info
     */
    public function getFailedFiles()
    {
        return collect($this->files)->where('status', 'failed');
    }

    /**
     * Get processing files info
     */
    public function getProcessingFiles()
    {
        return collect($this->files)->where('status', 'processing');
    }

    /**
     * Get summary text
     */
    public function getSummaryAttribute()
    {
        $total = $this->total_files;
        $completed = $this->completed_files;
        $failed = $this->failed_files;
        $processing = $this->processing_files;

        if ($this->isCompleted()) {
            if ($failed > 0) {
                return "Hoàn thành {$completed}/{$total} files. {$failed} files thất bại.";
            } else {
                return "Hoàn thành thành công {$completed}/{$total} files.";
            }
        } else {
            return "Đang xử lý: {$completed} hoàn thành, {$processing} đang xử lý, {$failed} thất bại.";
        }
    }
}
