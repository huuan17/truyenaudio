<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AudioLibrary extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_extension',
        'file_size',
        'duration',
        'format',
        'bitrate',
        'sample_rate',
        'category',
        'source_type',
        'source_id',
        'language',
        'voice_type',
        'mood',
        'tags',
        'metadata',
        'is_public',
        'is_active',
        'uploaded_by',
        'usage_count',
        'last_used_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    /**
     * Get the user who uploaded this audio
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the source story if source_type is 'story'
     */
    public function sourceStory(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'source_id')->where('source_type', 'story');
    }

    /**
     * Get the source chapter if source_type is 'chapter'
     */
    public function sourceChapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'source_id')->where('source_type', 'chapter');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get audio file URL
     */
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * Check if audio file exists
     */
    public function fileExists()
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get full file path
     */
    public function getFullPath()
    {
        return Storage::disk('public')->path($this->file_path);
    }

    /**
     * Get usage count
     */
    public function getUsageCount()
    {
        return $this->usage_count ?? 0;
    }

    /**
     * Get last used date
     */
    public function getLastUsedAt()
    {
        return $this->last_used_at;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        $seconds = $this->duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }

    /**
     * Search audio files
     */
    public static function search($query, $filters = [])
    {
        $builder = static::where('is_active', true);

        // Text search
        if ($query) {
            $builder->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereJsonContains('tags', $query);
            });
        }

        // Category filter
        if (isset($filters['category']) && $filters['category']) {
            $builder->where('category', $filters['category']);
        }

        // Source type filter
        if (isset($filters['source_type']) && $filters['source_type']) {
            $builder->where('source_type', $filters['source_type']);
        }

        // Language filter
        if (isset($filters['language']) && $filters['language']) {
            $builder->where('language', $filters['language']);
        }

        // Voice type filter
        if (isset($filters['voice_type']) && $filters['voice_type']) {
            $builder->where('voice_type', $filters['voice_type']);
        }

        // Duration range filter
        if (isset($filters['min_duration']) && $filters['min_duration']) {
            $builder->where('duration', '>=', $filters['min_duration']);
        }
        if (isset($filters['max_duration']) && $filters['max_duration']) {
            $builder->where('duration', '<=', $filters['max_duration']);
        }

        return $builder->orderBy('created_at', 'desc');
    }

    /**
     * Get audio categories
     */
    public static function getCategories()
    {
        return [
            'general' => 'Tổng quát',
            'story' => 'Truyện audio',
            'music' => 'Nhạc nền',
            'voice' => 'Giọng đọc',
            'effect' => 'Hiệu ứng âm thanh',
            'podcast' => 'Podcast',
            'interview' => 'Phỏng vấn',
            'presentation' => 'Thuyết trình',
            'tutorial' => 'Hướng dẫn',
            'marketing' => 'Marketing'
        ];
    }

    /**
     * Get source types
     */
    public static function getSourceTypes()
    {
        return [
            'upload' => 'Upload thủ công',
            'tts' => 'Text-to-Speech',
            'story' => 'Từ truyện',
            'chapter' => 'Từ chương truyện',
            'imported' => 'Import từ nguồn khác'
        ];
    }

    /**
     * Get voice types
     */
    public static function getVoiceTypes()
    {
        return [
            'male' => 'Giọng nam',
            'female' => 'Giọng nữ',
            'child' => 'Giọng trẻ em',
            'elderly' => 'Giọng người già',
            'robot' => 'Giọng robot/AI'
        ];
    }

    /**
     * Get mood types
     */
    public static function getMoodTypes()
    {
        return [
            'neutral' => 'Trung tính',
            'happy' => 'Vui vẻ',
            'sad' => 'Buồn',
            'dramatic' => 'Kịch tính',
            'calm' => 'Bình tĩnh',
            'energetic' => 'Năng động',
            'mysterious' => 'Bí ẩn',
            'romantic' => 'Lãng mạn'
        ];
    }
}
