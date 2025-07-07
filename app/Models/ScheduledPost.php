<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScheduledPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'video_path',
        'video_type',
        'title',
        'description',
        'tags',
        'category',
        'privacy',
        'scheduled_at',
        'timezone',
        'status',
        'uploaded_at',
        'platform_post_id',
        'platform_url',
        'error_message',
        'retry_count',
        'last_retry_at',
        'metadata'
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'last_retry_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReadyToPost($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUploaded($query)
    {
        return $query->where('status', 'uploaded');
    }

    /**
     * Accessors
     */
    public function getScheduledAtLocalAttribute()
    {
        return $this->scheduled_at->setTimezone($this->timezone);
    }

    public function getVideoSizeAttribute()
    {
        if (file_exists($this->video_path)) {
            return filesize($this->video_path);
        }
        return 0;
    }

    public function getVideoSizeFormattedAttribute()
    {
        $bytes = $this->video_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Status methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isUploaded()
    {
        return $this->status === 'uploaded';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function canRetry()
    {
        return $this->isFailed() && $this->retry_count < 3;
    }

    public function isReadyToPost()
    {
        return $this->isPending() && $this->scheduled_at <= now();
    }

    /**
     * Action methods
     */
    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsUploaded($platformPostId = null, $platformUrl = null)
    {
        $this->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl,
            'error_message' => null
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }
}
