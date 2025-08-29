<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VideoPublishing extends Model
{
    use HasFactory;

    protected $table = 'video_publishing';

    protected $fillable = [
        'generated_video_id',
        'channel_id',
        'platform',
        'status',
        'publish_mode',
        'post_title',
        'post_description',
        'post_tags',
        'post_privacy',
        'post_category',
        'scheduled_at',
        'published_at',
        'platform_post_id',
        'platform_url',
        'platform_metadata',
        'error_message',
        'retry_count',
        'last_retry_at',
        'created_by'
    ];

    protected $casts = [
        'post_tags' => 'array',
        'platform_metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Publish mode constants
    const MODE_AUTO = 'auto';
    const MODE_SCHEDULED = 'scheduled';
    const MODE_MANUAL = 'manual';

    /**
     * Relationships
     */
    public function generatedVideo(): BelongsTo
    {
        return $this->belongsTo(GeneratedVideo::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function scopeScheduledForNow($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now());
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_PUBLISHING => 'warning',
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'dark',
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_SCHEDULED => 'Đã lên lịch',
            self::STATUS_PUBLISHING => 'Đang đăng',
            self::STATUS_PUBLISHED => 'Đã đăng',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];

        return $texts[$this->status] ?? 'Không xác định';
    }

    public function getPlatformIconAttribute()
    {
        $icons = [
            'youtube' => 'fab fa-youtube',
            'tiktok' => 'fab fa-tiktok',
            'facebook' => 'fab fa-facebook',
            'instagram' => 'fab fa-instagram',
        ];

        return $icons[$this->platform] ?? 'fas fa-video';
    }

    /**
     * Helper methods
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retry_count < 3;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function canDelete(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CANCELLED, self::STATUS_FAILED]);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && 
               $this->scheduled_at && 
               $this->scheduled_at->isPast();
    }

    public function markAsPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    public function markAsPublished(string $platformPostId = null, string $platformUrl = null): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }
}
