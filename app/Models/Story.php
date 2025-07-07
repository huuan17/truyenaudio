<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Story extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'description',
        'cover_image',
        'source_url',
        'start_chapter',
        'end_chapter',
        'crawl_status',
        'crawl_path',
        'status',
        'folder_name',
    ];

    protected $casts = [
        'crawl_status' => 'integer',
        'start_chapter' => 'integer',
        'end_chapter' => 'integer',
    ];

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Generate slug from title
     */
    public function getSlugAttribute($value)
    {
        return $value ?: Str::slug($this->title);
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

        static::creating(function ($story) {
            if (empty($story->slug)) {
                $story->slug = Str::slug($story->title);

                // Ensure unique slug
                $originalSlug = $story->slug;
                $counter = 1;
                while (static::where('slug', $story->slug)->exists()) {
                    $story->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($story) {
            if ($story->isDirty('title') && empty($story->slug)) {
                $story->slug = Str::slug($story->title);

                // Ensure unique slug
                $originalSlug = $story->slug;
                $counter = 1;
                while (static::where('slug', $story->slug)->where('id', '!=', $story->id)->exists()) {
                    $story->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}