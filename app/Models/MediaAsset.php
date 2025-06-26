<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use SoftDeletes;

    protected $fillable = ['type', 'name', 'file_path', 'note'];

    public function usedAsImageInVideos()
    {
        return $this->hasMany(Video::class, 'image_id');
    }

    public function usedAsOverlayInVideos()
    {
        return $this->hasMany(Video::class, 'overlay_video_id');
    }
}
