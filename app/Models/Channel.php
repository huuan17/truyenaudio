<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\SlugHelper;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'platform',
        'channel_id',
        'username',
        'description',
        'logo_path',
        'logo_config',
        'api_credentials',
        'upload_settings',
        'default_privacy',
        'default_tags',
        'default_category',
        'is_active',
        'auto_upload',
        'last_upload_at',
        'metadata'
    ];

    protected $casts = [
        'logo_config' => 'array',
        'api_credentials' => 'encrypted:array',
        'upload_settings' => 'array',
        'default_tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'auto_upload' => 'boolean',
        'last_upload_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function scheduledPosts()
    {
        return $this->hasMany(ScheduledPost::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Accessors & Mutators
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            return route('admin.logo.serve', basename($this->logo_path));
        }
        return null;
    }

    public function getDefaultLogoConfigAttribute()
    {
        return $this->logo_config ?: [
            'position' => 'bottom-right',
            'size' => 100,
            'opacity' => 1.0
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($channel) {
            if (empty($channel->slug)) {
                $channel->slug = SlugHelper::createUniqueSlug($channel->name, static::class);
            }
        });
    }

    /**
     * Helper methods
     */
    public function isTikTok()
    {
        return $this->platform === 'tiktok';
    }

    public function isYouTube()
    {
        return $this->platform === 'youtube';
    }

    public function hasValidCredentials()
    {
        if (empty($this->api_credentials)) {
            return false;
        }

        if ($this->platform === 'tiktok') {
            return isset($this->api_credentials['access_token']) &&
                   !empty($this->api_credentials['access_token']);
        }

        if ($this->platform === 'youtube') {
            $creds = $this->api_credentials ?: [];
            $refresh = $creds['refresh_token'] ?? null;
            $clientId = $creds['client_id'] ?? config('services.youtube.client_id');
            $clientSecret = $creds['client_secret'] ?? config('services.youtube.client_secret');
            return !empty($refresh) && !empty($clientId) && !empty($clientSecret);
        }

        return !empty($this->api_credentials);
    }

    public function getUploadCount($period = '30 days')
    {
        return $this->scheduledPosts()
            ->where('status', 'uploaded')
            ->where('uploaded_at', '>=', now()->sub($period))
            ->count();
    }
}
