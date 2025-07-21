<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\SlugHelper;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'title', 'description', 'content', 'is_public'];

    public function stories()
    {
        return $this->belongsToMany(Story::class);
    }

    /**
     * Scope to get only public genres
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Generate slug from name
     */
    public function getSlugAttribute($value)
    {
        // If slug exists in database, return it
        if ($value) {
            return $value;
        }

        // If no slug and name exists, generate one
        if ($this->name) {
            return SlugHelper::createSlug($this->name);
        }

        return null;
    }

    /**
     * Get route key name for model binding
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($genre) {
            if (empty($genre->slug)) {
                $genre->slug = SlugHelper::createUniqueSlug($genre->name, static::class);
            }
        });

        static::updating(function ($genre) {
            if ($genre->isDirty('name') && empty($genre->slug)) {
                $genre->slug = SlugHelper::createUniqueSlug($genre->name, static::class, $genre->id);
            }
        });
    }
}



    

    

