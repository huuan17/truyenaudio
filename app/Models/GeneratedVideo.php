<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GeneratedVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'platform',
        'media_type',
        'file_path',
        'file_name',
        'file_size',
        'duration',
        'thumbnail_path',
        'metadata',
        'status',
        'scheduled_at',
        'published_at',
        'task_id',
        'channel_id',
        'auto_publish',
        'publish_to_channel',
        'channel_published_at',
        'channel_publish_error'
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'channel_published_at' => 'datetime',
        'auto_publish' => 'boolean',
        'publish_to_channel' => 'boolean',
    ];

    /**
     * Get the task that generated this video
     */
    public function task()
    {
        return $this->belongsTo(VideoGenerationTask::class, 'task_id');
    }

    /**
     * Get the publishing records for this video
     */
    public function publishings()
    {
        return $this->hasMany(VideoPublishing::class);
    }

    /**
     * Get the channel this video belongs to
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationHumanAttribute()
    {
        if (!$this->duration) {
            return 'Unknown';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if file exists
     */
    public function fileExists()
    {
        if (empty($this->file_path)) {
            return false;
        }

        // Try both absolute and relative paths
        if (File::exists($this->file_path)) {
            return true; // Absolute path
        }

        // Try storage path
        $storagePath = storage_path('app/' . $this->file_path);
        return File::exists($storagePath);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        if (!$this->fileExists()) {
            return null;
        }

        return route('admin.videos.download', $this->id);
    }

    /**
     * Get preview URL
     */
    public function getPreviewUrlAttribute()
    {
        if (!$this->fileExists()) {
            return null;
        }

        return route('admin.videos.preview', $this->id);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path && File::exists($this->thumbnail_path)) {
            return asset('storage/' . str_replace(storage_path('app/public/'), '', $this->thumbnail_path));
        }

        return asset('images/default-video-thumbnail.jpg');
    }

    /**
     * Scope for platform
     */
    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for scheduled videos
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at');
    }

    /**
     * Scope for videos ready to publish
     */
    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for videos that should be published to channel
     */
    public function scopePublishToChannel($query)
    {
        return $query->where('publish_to_channel', true);
    }

    /**
     * Scope for videos with auto publish enabled
     */
    public function scopeAutoPublish($query)
    {
        return $query->where('auto_publish', true);
    }

    /**
     * Scope for videos ready for channel publishing
     */
    public function scopeReadyForChannelPublish($query)
    {
        return $query->where('status', 'published')
                    ->where('publish_to_channel', true)
                    ->whereNull('channel_published_at')
                    ->whereNotNull('channel_id');
    }

    /**
     * Check if video should be published to channel
     */
    public function shouldPublishToChannel()
    {
        return $this->publish_to_channel &&
               $this->channel_id &&
               $this->status === 'published' &&
               !$this->channel_published_at;
    }

    /**
     * Check if video is published to channel
     */
    public function isPublishedToChannel()
    {
        return !is_null($this->channel_published_at);
    }

    /**
     * Get channel publish status text
     */
    public function getChannelPublishStatusAttribute()
    {
        if (!$this->publish_to_channel) {
            return 'Không đăng kênh';
        }

        if (!$this->channel_id) {
            return 'Chưa chọn kênh';
        }

        if ($this->channel_published_at) {
            return 'Đã đăng kênh';
        }

        if ($this->channel_publish_error) {
            return 'Lỗi đăng kênh';
        }

        if ($this->status !== 'published') {
            return 'Chờ xuất bản';
        }

        return 'Chờ đăng kênh';
    }
}
