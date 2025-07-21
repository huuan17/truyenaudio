<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function () {
            Cache::forget('settings');
        });

        static::deleted(function () {
            Cache::forget('settings');
        });
    }

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        $settings = self::getAllCached();
        return $settings[$key] ?? $default;
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings cached
     */
    public static function getAllCached()
    {
        return Cache::remember('settings', 3600, function () {
            return self::where('is_active', true)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return self::where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get value with type casting
     */
    public function getTypedValue()
    {
        switch ($this->type) {
            case 'boolean':
                return (bool) $this->value;
            case 'json':
                return json_decode($this->value, true);
            case 'integer':
                return (int) $this->value;
            case 'float':
                return (float) $this->value;
            default:
                return $this->value;
        }
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for group
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get available groups
     */
    public static function getGroups()
    {
        return [
            'general' => 'Thông tin chung',
            'seo' => 'SEO & Meta Tags',
            'tracking' => 'Tracking & Analytics',
            'social' => 'Mạng xã hội',
            'appearance' => 'Giao diện',
        ];
    }

    /**
     * Get available types
     */
    public static function getTypes()
    {
        return [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'boolean' => 'Boolean',
            'json' => 'JSON',
            'url' => 'URL',
            'email' => 'Email',
            'code' => 'Code',
        ];
    }
}
