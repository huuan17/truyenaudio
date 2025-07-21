<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\SlugHelper;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'bio',
        'avatar',
        'email',
        'website',
        'facebook',
        'twitter',
        'instagram',
        'birth_date',
        'nationality',
        'achievements',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'achievements' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($author) {
            if (empty($author->slug)) {
                $author->slug = SlugHelper::createUniqueSlug($author->name, static::class);
            }
        });

        static::updating(function ($author) {
            if ($author->isDirty('name') && empty($author->slug)) {
                $author->slug = SlugHelper::createUniqueSlug($author->name, static::class, $author->id);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope for active authors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get stories by this author
     */
    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Get published stories by this author
     */
    public function publishedStories()
    {
        return $this->hasMany(Story::class)->where('is_public', true)->where('is_active', true);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Default avatar using initials
        $initials = collect(explode(' ', $this->name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
            
        return "https://ui-avatars.com/api/?name={$initials}&size=200&background=007bff&color=ffffff";
    }

    /**
     * Get formatted birth date
     */
    public function getFormattedBirthDateAttribute()
    {
        return $this->birth_date ? $this->birth_date->format('d/m/Y') : null;
    }

    /**
     * Get age
     */
    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    /**
     * Get social media links
     */
    public function getSocialLinksAttribute()
    {
        $links = [];
        
        if ($this->website) {
            $links['website'] = [
                'url' => $this->website,
                'icon' => 'fas fa-globe',
                'label' => 'Website'
            ];
        }
        
        if ($this->facebook) {
            $links['facebook'] = [
                'url' => $this->facebook,
                'icon' => 'fab fa-facebook',
                'label' => 'Facebook'
            ];
        }
        
        if ($this->twitter) {
            $links['twitter'] = [
                'url' => $this->twitter,
                'icon' => 'fab fa-twitter',
                'label' => 'Twitter'
            ];
        }
        
        if ($this->instagram) {
            $links['instagram'] = [
                'url' => $this->instagram,
                'icon' => 'fab fa-instagram',
                'label' => 'Instagram'
            ];
        }
        
        return $links;
    }

    /**
     * Get SEO meta title
     */
    public function getSeoTitleAttribute()
    {
        return $this->meta_title ?: $this->name . ' - Tác giả truyện audio';
    }

    /**
     * Get SEO meta description
     */
    public function getSeoDescriptionAttribute()
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }
        
        $storiesCount = $this->publishedStories()->count();
        return "Tìm hiểu về tác giả {$this->name}. Đọc và nghe {$storiesCount} truyện audio của {$this->name} tại Audio Lara.";
    }

    /**
     * Get SEO keywords
     */
    public function getSeoKeywordsAttribute()
    {
        if ($this->meta_keywords) {
            return $this->meta_keywords;
        }
        
        return $this->name . ', tác giả, truyện audio, sách nói, văn học';
    }
}
