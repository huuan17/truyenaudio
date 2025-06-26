<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chapter_id', 'audio_id', 'image_id', 'overlay_video_id',
        'file_path', 'render_status'
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function audio()
    {
        return $this->belongsTo(Audio::class);
    }

    public function image()
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function overlayVideo()
    {
        return $this->belongsTo(MediaAsset::class, 'overlay_video_id');
    }

    public function youtubeUpload()
    {
        return $this->hasOne(YoutubeUpload::class);
    }
}
