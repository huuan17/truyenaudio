<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YoutubeUpload extends Model
{
    use SoftDeletes;

    protected $fillable = ['video_id', 'youtube_video_id', 'status', 'error_message'];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
