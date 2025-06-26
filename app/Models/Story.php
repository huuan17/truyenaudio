<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'author',
        'cover_image',
        'source_url',
        'start_chapter',
        'end_chapter',
        'crawl_status',
        'crawl_path',
    ];

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }
    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }
}