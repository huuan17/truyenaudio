<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoGenerationTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'platform',
        'type',
        'status',
        'priority',
        'parameters',
        'result',
        'progress',
        'estimated_duration',
        'started_at',
        'completed_at',
        'batch_id',
        'batch_index',
        'total_in_batch'
    ];

    protected $casts = [
        'parameters' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer'
    ];

    /**
     * Task statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Task types
     */
    const TYPE_SINGLE = 'single';
    const TYPE_BATCH = 'batch';

    /**
     * Priority levels
     */
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get tasks in the same batch
     */
    public function batchTasks()
    {
        return $this->where('batch_id', $this->batch_id)
                   ->orderBy('batch_index');
    }

    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing tasks
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
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
     * Scope for specific platform
     */
    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get estimated completion time
     */
    public function getEstimatedCompletionAttribute()
    {
        if ($this->status === self::STATUS_PENDING) {
            // Calculate based on queue position and average processing time
            $queuePosition = self::pending()
                               ->where('created_at', '<', $this->created_at)
                               ->count();
            
            $avgProcessingTime = $this->estimated_duration ?: 300; // Default 5 minutes
            
            return now()->addSeconds($queuePosition * $avgProcessingTime);
        }
        
        if ($this->status === self::STATUS_PROCESSING && $this->started_at) {
            $elapsed = now()->diffInSeconds($this->started_at);
            $estimatedTotal = $this->estimated_duration ?: 300;
            $remaining = max(0, $estimatedTotal - $elapsed);
            
            return now()->addSeconds($remaining);
        }
        
        return $this->completed_at;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        return min(100, max(0, $this->progress));
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_PROCESSING => 'badge-info',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-light'
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Đang chờ',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => 'Không xác định'
        };
    }

    /**
     * Get platform display name
     */
    public function getPlatformDisplayAttribute()
    {
        return match($this->platform) {
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            default => ucfirst($this->platform)
        };
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            self::TYPE_SINGLE => 'Đơn lẻ',
            self::TYPE_BATCH => 'Hàng loạt',
            default => ucfirst($this->type)
        };
    }

    /**
     * Check if task can be cancelled
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * Check if task can be retried
     */
    public function canBeRetried()
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Cancel the task
     */
    public function cancel()
    {
        if ($this->canBeCancelled()) {
            $this->update([
                'status' => self::STATUS_CANCELLED,
                'completed_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            $seconds = $this->started_at->diffInSeconds($this->completed_at);
            
            if ($seconds < 60) {
                return "{$seconds} giây";
            } elseif ($seconds < 3600) {
                $minutes = floor($seconds / 60);
                $remainingSeconds = $seconds % 60;
                return "{$minutes}m {$remainingSeconds}s";
            } else {
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                return "{$hours}h {$minutes}m";
            }
        }
        
        return null;
    }

    /**
     * Get batch progress if this is a batch task
     */
    public function getBatchProgressAttribute()
    {
        if (!$this->batch_id) {
            return null;
        }

        $batchTasks = self::where('batch_id', $this->batch_id)->get();
        $completed = $batchTasks->where('status', self::STATUS_COMPLETED)->count();
        $total = $batchTasks->count();
        
        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }
}
