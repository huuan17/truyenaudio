<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audio extends Model
{
    use SoftDeletes;

    protected $fillable = ['chapter_id', 'file_path', 'duration_seconds', 'tts_provider'];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function video()
    {
        return $this->hasOne(Video::class);
    }
}
