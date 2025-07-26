<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'settings',
        'required_inputs',
        'optional_inputs',
        'thumbnail',
        'is_active',
        'is_public',
        'created_by',
        'usage_count',
        'last_used_at',
        'default_channel_id',
        'channel_metadata_template',
        'background_music_type',
        'background_music_file',
        'background_music_library_id',
        'background_music_random_tag',
        'background_music_volume'
    ];

    protected $casts = [
        'settings' => 'array',
        'required_inputs' => 'array',
        'optional_inputs' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'last_used_at' => 'datetime',
        'channel_metadata_template' => 'array'
    ];

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the default channel for this template
     */
    public function defaultChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'default_channel_id');
    }

    /**
     * Get the background music from library
     */
    public function backgroundMusicLibrary(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AudioLibrary::class, 'background_music_library_id');
    }

    /**
     * Increment usage count and update last used timestamp
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Generate auto video name from template
     */
    public function generateVideoName($sequence = null)
    {
        $sequence = $sequence ?: ($this->usage_count + 1);
        return $this->name . ' #' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate channel metadata from template
     */
    public function generateChannelMetadata($inputs = [])
    {
        $template = $this->channel_metadata_template ?: [];
        $metadata = [];

        // Generate title
        if (isset($template['title_template'])) {
            $metadata['title'] = $this->processTemplate($template['title_template'], $inputs);
        } else {
            $metadata['title'] = $this->generateVideoName();
        }

        // Generate description
        if (isset($template['description_template'])) {
            $metadata['description'] = $this->processTemplate($template['description_template'], $inputs);
        }

        // Generate tags
        if (isset($template['tags_template'])) {
            $metadata['tags'] = $this->processTagsTemplate($template['tags_template'], $inputs);
        }

        // Generate hashtags
        if (isset($template['hashtags_template'])) {
            $metadata['hashtags'] = $this->processHashtagsTemplate($template['hashtags_template'], $inputs);
        }

        return $metadata;
    }

    /**
     * Process template string with input variables
     */
    private function processTemplate($template, $inputs)
    {
        $processed = $template;

        // Replace template variables like {{input_name}}
        foreach ($inputs as $key => $value) {
            $processed = str_replace('{{' . $key . '}}', $value, $processed);
        }

        // Replace template name
        $processed = str_replace('{{template_name}}', $this->name, $processed);

        // Replace sequence number
        $processed = str_replace('{{sequence}}', $this->usage_count + 1, $processed);

        return $processed;
    }

    /**
     * Process tags template
     */
    private function processTagsTemplate($template, $inputs)
    {
        if (is_array($template)) {
            $tags = [];
            foreach ($template as $tag) {
                $tags[] = $this->processTemplate($tag, $inputs);
            }
            return $tags;
        }

        return explode(',', $this->processTemplate($template, $inputs));
    }

    /**
     * Process hashtags template
     */
    private function processHashtagsTemplate($template, $inputs)
    {
        $hashtags = $this->processTagsTemplate($template, $inputs);

        // Ensure hashtags start with #
        return array_map(function($tag) {
            $tag = trim($tag);
            return str_starts_with($tag, '#') ? $tag : '#' . $tag;
        }, $hashtags);
    }

    /**
     * Get background music for video generation
     */
    public function getBackgroundMusic()
    {
        switch ($this->background_music_type) {
            case 'upload':
                if ($this->background_music_file && \Storage::disk('public')->exists($this->background_music_file)) {
                    return [
                        'type' => 'file',
                        'path' => storage_path('app/public/' . $this->background_music_file),
                        'volume' => $this->background_music_volume ?? 30
                    ];
                }
                break;

            case 'library':
                if ($this->backgroundMusicLibrary && $this->backgroundMusicLibrary->fileExists()) {
                    return [
                        'type' => 'library',
                        'id' => $this->background_music_library_id,
                        'path' => $this->backgroundMusicLibrary->getFullPath(),
                        'title' => $this->backgroundMusicLibrary->title,
                        'volume' => $this->background_music_volume ?? 30
                    ];
                }
                break;

            case 'random':
                $randomAudio = $this->getRandomAudioByTag($this->background_music_random_tag);
                if ($randomAudio) {
                    return [
                        'type' => 'random',
                        'id' => $randomAudio->id,
                        'path' => $randomAudio->getFullPath(),
                        'title' => $randomAudio->title,
                        'tag' => $this->background_music_random_tag,
                        'volume' => $this->background_music_volume ?? 30
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Get random audio by tag
     */
    private function getRandomAudioByTag($tag)
    {
        if (!$tag) return null;

        return \App\Models\AudioLibrary::where('is_public', true)
                                      ->where('category', $tag)
                                      ->inRandomOrder()
                                      ->first();
    }

    /**
     * Get available background music tags
     */
    public static function getBackgroundMusicTags()
    {
        return [
            'music' => 'Nhạc nền chung',
            'relaxing' => 'Nhạc thư giãn',
            'story' => 'Nhạc cho truyện',
            'upbeat' => 'Nhạc sôi động',
            'cinematic' => 'Nhạc điện ảnh',
            'nature' => 'Âm thanh tự nhiên',
            'corporate' => 'Nhạc doanh nghiệp',
            'emotional' => 'Nhạc cảm xúc',
            'action' => 'Nhạc hành động',
            'ambient' => 'Nhạc không gian'
        ];
    }

    /**
     * Get background music info for display
     */
    public function getBackgroundMusicInfo()
    {
        switch ($this->background_music_type) {
            case 'upload':
                return [
                    'type' => 'Upload',
                    'name' => basename($this->background_music_file ?? 'Unknown'),
                    'volume' => $this->background_music_volume ?? 30
                ];

            case 'library':
                return [
                    'type' => 'Thư viện',
                    'name' => $this->backgroundMusicLibrary->title ?? 'Unknown',
                    'volume' => $this->background_music_volume ?? 30
                ];

            case 'random':
                $tags = self::getBackgroundMusicTags();
                return [
                    'type' => 'Random',
                    'name' => $tags[$this->background_music_random_tag] ?? $this->background_music_random_tag,
                    'volume' => $this->background_music_volume ?? 30
                ];

            default:
                return [
                    'type' => 'Không có',
                    'name' => 'Không sử dụng nhạc nền',
                    'volume' => 0
                ];
        }
    }

    /**
     * Get templates by category
     */
    public static function getByCategory($category = null)
    {
        $query = static::where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('usage_count', 'desc')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Get popular templates
     */
    public static function getPopular($limit = 10)
    {
        return static::where('is_active', true)
                    ->where('usage_count', '>', 0)
                    ->orderBy('usage_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get template categories
     */
    public static function getCategories()
    {
        return [
            'general' => 'Tổng quát',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'marketing' => 'Marketing',
            'education' => 'Giáo dục',
            'entertainment' => 'Giải trí',
            'news' => 'Tin tức',
            'tutorial' => 'Hướng dẫn'
        ];
    }

    /**
     * Get required input types
     */
    public static function getInputTypes()
    {
        return [
            'text' => 'Văn bản',
            'textarea' => 'Văn bản dài',
            'audio' => 'File âm thanh',
            'image' => 'Hình ảnh',
            'images' => 'Nhiều hình ảnh',
            'video' => 'Video',
            'url' => 'Đường dẫn',
            'number' => 'Số',
            'select' => 'Lựa chọn',
            'checkbox' => 'Tích chọn'
        ];
    }
}
