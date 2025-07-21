<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkTtsTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'story_id',
        'chapter_ids',
        'total_chapters',
        'completed_count',
        'failed_count',
        'current_chapter_id',
        'current_chapter_title',
        'progress',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at'
    ];

    protected $casts = [
        'chapter_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'progress' => 'decimal:2'
    ];

    protected $dates = [
        'started_at',
        'completed_at',
        'failed_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user who created this bulk task
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story this bulk task belongs to
     */
    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the current chapter being processed
     */
    public function currentChapter()
    {
        return $this->belongsTo(Chapter::class, 'current_chapter_id');
    }

    /**
     * Get all chapters in this bulk task
     */
    public function chapters()
    {
        return Chapter::whereIn('id', $this->chapter_ids ?? []);
    }

    /**
     * Scope for active tasks
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Scope for completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed tasks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if task is active
     */
    public function isActive()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if task is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if task is failed
     */
    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if task is cancelled
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        return round($this->progress, 2);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'badge-secondary';
            case self::STATUS_PROCESSING:
                return 'badge-primary';
            case self::STATUS_COMPLETED:
                return 'badge-success';
            case self::STATUS_FAILED:
                return 'badge-danger';
            case self::STATUS_CANCELLED:
                return 'badge-warning';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get status display text
     */
    public function getStatusDisplayAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Chờ xử lý';
            case self::STATUS_PROCESSING:
                return 'Đang xử lý';
            case self::STATUS_COMPLETED:
                return 'Hoàn thành';
            case self::STATUS_FAILED:
                return 'Thất bại';
            case self::STATUS_CANCELLED:
                return 'Đã hủy';
            default:
                return 'Không xác định';
        }
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();
        $duration = $this->started_at->diffInSeconds($endTime);

        if ($duration < 60) {
            return $duration . ' giây';
        } elseif ($duration < 3600) {
            return round($duration / 60, 1) . ' phút';
        } else {
            return round($duration / 3600, 1) . ' giờ';
        }
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_chapters == 0) {
            return 0;
        }

        return round(($this->completed_count / $this->total_chapters) * 100, 2);
    }

    /**
     * Get remaining chapters count
     */
    public function getRemainingChaptersAttribute()
    {
        return $this->total_chapters - $this->completed_count - $this->failed_count;
    }

    /**
     * Cancel the bulk task
     */
    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'failed_at' => now()
        ]);
    }

    /**
     * Restart the bulk task
     */
    public function restart()
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'progress' => 0,
            'completed_count' => 0,
            'failed_count' => 0,
            'current_chapter_id' => null,
            'current_chapter_title' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null
        ]);
    }

    /**
     * Get estimated time remaining
     */
    public function getEstimatedTimeRemainingAttribute()
    {
        if (!$this->isActive() || $this->completed_count == 0) {
            return null;
        }

        $elapsed = $this->started_at->diffInSeconds(now());
        $avgTimePerChapter = $elapsed / $this->completed_count;
        $remainingSeconds = $avgTimePerChapter * $this->remaining_chapters;

        if ($remainingSeconds < 60) {
            return round($remainingSeconds) . ' giây';
        } elseif ($remainingSeconds < 3600) {
            return round($remainingSeconds / 60) . ' phút';
        } else {
            return round($remainingSeconds / 3600, 1) . ' giờ';
        }
    }
}
