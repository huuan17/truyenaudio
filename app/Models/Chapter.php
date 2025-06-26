<?php
namespace App\Models;

use App\Helpers\ChapterHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'story_id', 'chapter_number', 'title', 'content', 'audio_status', 'is_crawled', 'file_path',
        'tts_voice', 'tts_bitrate', 'tts_speed', 'tts_started_at', 'tts_completed_at', 'audio_file_path'
    ];

    protected $casts = [
        'tts_started_at' => 'datetime',
        'tts_completed_at' => 'datetime',
        'crawled_at' => 'datetime',
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function video()
    {
        return $this->hasOne(Video::class);
    }

    /**
     * Lấy nội dung chapter từ database hoặc file
     */
    public function getContentAttribute($value)
    {
        // Nếu có content trong database, trả về luôn
        if (!empty($value)) {
            return $value;
        }

        // Nếu không có content nhưng có file_path, đọc từ file
        if (!empty($this->file_path) && file_exists($this->file_path)) {
            return file_get_contents($this->file_path);
        }

        return null;
    }

    /**
     * Kiểm tra xem chapter có nội dung trong database không
     */
    public function hasContentInDatabase()
    {
        return !empty($this->attributes['content']);
    }

    /**
     * Kiểm tra xem chapter có thể đọc được nội dung không (từ DB hoặc file)
     */
    public function hasReadableContent()
    {
        return $this->hasContentInDatabase() ||
               (!empty($this->file_path) && file_exists($this->file_path));
    }

    /**
     * Lấy kích thước file (nếu có)
     */
    public function getFileSizeAttribute()
    {
        if (!empty($this->file_path) && file_exists($this->file_path)) {
            return filesize($this->file_path);
        }
        return null;
    }

    /**
     * Lấy kích thước file dạng human readable
     */
    public function getFormattedFileSizeAttribute()
    {
        $size = $this->file_size;
        if ($size === null) return null;

        return ChapterHelper::formatFileSize($size);
    }

    public function audio()
    {
        return $this->hasOne(Audio::class);
    }

    /**
     * Kiểm tra xem chapter đã có audio chưa
     */
    public function hasAudio()
    {
        return !empty($this->audio_file_path) && file_exists($this->audio_file_path);
    }

    /**
     * Kiểm tra xem chapter có thể chuyển TTS không
     */
    public function canConvertToTts()
    {
        return $this->hasReadableContent() && $this->audio_status !== 'processing';
    }

    /**
     * Lấy trạng thái TTS dạng badge
     */
    public function getTtsStatusBadgeAttribute()
    {
        switch ($this->audio_status) {
            case 'pending':
                return '<span class="badge badge-secondary"><i class="fas fa-clock"></i> Chờ xử lý</span>';
            case 'processing':
                return '<span class="badge badge-warning"><i class="fas fa-spinner fa-spin"></i> Đang xử lý</span>';
            case 'done':
                return '<span class="badge badge-success"><i class="fas fa-check"></i> Hoàn thành</span>';
            case 'error':
                return '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Lỗi</span>';
            default:
                return '<span class="badge badge-light">Không xác định</span>';
        }
    }

    /**
     * Lấy đường dẫn audio file dự kiến
     */
    public function getExpectedAudioPathAttribute()
    {
        if ($this->story && $this->story->folder_name) {
            $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
            return base_path($audioBasePath . $this->story->folder_name . "/chuong_{$this->chapter_number}.mp3");
        }
        return null;
    }

    /**
     * Lấy URL audio để phát trên web
     */
    public function getAudioUrlAttribute()
    {
        if ($this->hasAudio()) {
            // Chuyển đổi đường dẫn file thành URL
            // Từ: C:\xampp\htdocs\audio-lara\storage/truyen/mp3-folder/file.mp3
            // Thành: http://localhost:8000/audio/mp3-folder/file.mp3

            $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
            $fullBasePath = base_path($audioBasePath);

            if (strpos($this->audio_file_path, $fullBasePath) === 0) {
                $relativePath = substr($this->audio_file_path, strlen($fullBasePath));
                $relativePath = str_replace('\\', '/', $relativePath);
                $relativePath = ltrim($relativePath, '/');

                return url('audio/' . $relativePath);
            }

            // Fallback: chuyển đổi đường dẫn thông thường
            $relativePath = str_replace(base_path(), '', $this->audio_file_path);
            $relativePath = str_replace('\\', '/', $relativePath);
            $relativePath = ltrim($relativePath, '/');

            return url($relativePath);
        }
        return null;
    }

    /**
     * Lấy tên file audio
     */
    public function getAudioFileNameAttribute()
    {
        if ($this->hasAudio()) {
            return basename($this->audio_file_path);
        }
        return null;
    }
}
