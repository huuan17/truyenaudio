<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function stories()
    {
        return $this->belongsToMany(Story::class);
    }

    /**
     * Generate slug from name
     */
    public function getSlugAttribute($value)
    {
        return $value ?: Str::slug($this->name);
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
                $genre->slug = Str::slug($genre->name);

                // Ensure unique slug
                $originalSlug = $genre->slug;
                $counter = 1;
                while (static::where('slug', $genre->slug)->exists()) {
                    $genre->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($genre) {
            if ($genre->isDirty('name') && empty($genre->slug)) {
                $genre->slug = Str::slug($genre->name);

                // Ensure unique slug
                $originalSlug = $genre->slug;
                $counter = 1;
                while (static::where('slug', $genre->slug)->where('id', '!=', $genre->id)->exists()) {
                    $genre->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}



    

    

