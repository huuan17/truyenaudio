<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'author', 'source_url', 'description'];

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }
}