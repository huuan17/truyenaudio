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
        'tts_voice', 'tts_bitrate', 'tts_speed', 'tts_volume', 'tts_progress', 'tts_error',
        'tts_started_at', 'tts_completed_at', 'audio_file_path'
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
        if (!empty($this->file_path)) {
            $filePath = $this->getActualFilePath();
            if ($filePath && file_exists($filePath)) {
                return file_get_contents($filePath);
            }
        }

        return null;
    }

    /**
     * Lấy đường dẫn file thực tế (xử lý cả đường dẫn cũ và mới)
     */
    public function getActualFilePath()
    {
        if (empty($this->file_path)) {
            return null;
        }

        // Kiểm tra đường dẫn gốc trước
        if (file_exists($this->file_path)) {
            return $this->file_path;
        }

        // Nếu đường dẫn là relative (không bắt đầu bằng / hoặc C:\), thử với storage_path
        if (!str_starts_with($this->file_path, '/') && !str_contains($this->file_path, ':\\')) {
            $storagePath = storage_path('app/' . $this->file_path);
            if (file_exists($storagePath)) {
                return $storagePath;
            }
        }

        // Nếu không tồn tại, thử chuyển đổi từ đường dẫn cũ sang mới
        // storage/truyen/ -> storage/app/content/
        $newPath = str_replace('storage/truyen/', 'storage/app/content/', $this->file_path);
        if (file_exists($newPath)) {
            return $newPath;
        }

        // Thử các pattern khác nếu cần
        $patterns = [
            'storage/truyen/' => 'storage/app/content/',
            '/storage/truyen/' => '/storage/app/content/',
            '\\storage\\truyen\\' => '\\storage\\app\\content\\',
        ];

        foreach ($patterns as $old => $new) {
            $testPath = str_replace($old, $new, $this->file_path);
            if (file_exists($testPath)) {
                return $testPath;
            }
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
               (!empty($this->file_path) && $this->getActualFilePath() !== null);
    }

    /**
     * Lấy kích thước file (nếu có)
     */
    public function getFileSizeAttribute()
    {
        $filePath = $this->getActualFilePath();
        if ($filePath && file_exists($filePath)) {
            return filesize($filePath);
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
        if (!$this->audio_file_path) {
            return false;
        }

        // Just check if audio_file_path is set
        // File existence check can be done separately if needed
        return !empty($this->audio_file_path);
    }

    /**
     * Kiểm tra xem chapter có thể chuyển TTS không
     */
    public function canConvertToTts()
    {
        return $this->hasReadableContent() && $this->audio_status !== 'processing';
    }



    /**
     * Lấy đường dẫn audio file dự kiến (absolute path for file operations)
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
     * Lấy relative audio path để lưu vào database
     */
    public function getRelativeAudioPathAttribute()
    {
        if ($this->story && $this->story->folder_name) {
            return "truyen/mp3-{$this->story->folder_name}/chuong_{$this->chapter_number}.mp3";
        }
        return null;
    }

    /**
     * Get audio URL for web playback
     */
    public function getAudioWebUrlAttribute()
    {
        if (!$this->audio_file_path) {
            return null;
        }

        // New structure: audio_file_path is relative to storage/app
        // e.g., "audio/tien-nghich/chuong_1.mp3"
        return asset('storage/' . $this->audio_file_path);
    }



    /**
     * Lấy URL audio để phát trên web
     */
    public function getAudioUrlAttribute()
    {
        if ($this->hasAudio()) {
            // New structure: audio_file_path is relative to storage/app
            // e.g., "audio/tien-nghich/chuong_1.mp3"
            return asset('storage/' . $this->audio_file_path);
        }
        return null;
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

    /**
     * Đọc content từ file
     */
    public function getContentFromFile()
    {
        if (!$this->file_path) {
            return $this->content; // Fallback to database content
        }

        $fullPath = storage_path('app/' . $this->file_path);

        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        return $this->content; // Fallback to database content
    }

    /**
     * Kiểm tra file content có tồn tại không
     */
    public function hasContentFile()
    {
        if (!$this->file_path) {
            return false;
        }

        $fullPath = storage_path('app/' . $this->file_path);
        return file_exists($fullPath);
    }

    /**
     * Lấy TTS status badge HTML
     */
    public function getTtsStatusBadgeAttribute()
    {
        $status = $this->audio_status ?? 'none';
        $progress = $this->tts_progress ?? 0;

        switch ($status) {
            case 'processing':
                return '
                    <div class="tts-status-container" data-chapter-id="' . $this->id . '">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                         role="progressbar"
                                         style="width: ' . $progress . '%"
                                         data-progress="' . $progress . '">
                                        ' . $progress . '%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';

            case 'done': // Keep compatibility with existing data
                if ($this->hasAudio()) {
                    return '<span class="badge badge-success"><i class="fas fa-check me-1"></i>Có audio</span>';
                }
                return '<span class="badge badge-warning"><i class="fas fa-exclamation me-1"></i>Hoàn thành nhưng không có file</span>';

            case 'error': // Keep compatibility with existing data
                $badge = '<span class="badge badge-danger"><i class="fas fa-times me-1"></i>Thất bại</span>';
                if ($this->tts_error) {
                    $badge .= '<br><small class="text-danger">' . htmlspecialchars($this->tts_error) . '</small>';
                }
                return '<div class="tts-status-container" data-chapter-id="' . $this->id . '">' . $badge . '</div>';

            case 'pending':
                return '<span class="badge badge-warning"><i class="fas fa-clock me-1"></i>Chờ TTS</span>';

            case 'none':
            default:
                if ($this->hasAudio()) {
                    return '<span class="badge badge-success"><i class="fas fa-check me-1"></i>Có audio</span>';
                }
                return '<span class="badge badge-light"><i class="fas fa-minus me-1"></i>Chưa TTS</span>';
        }
    }

    /**
     * Check if chapter TTS is in queue (pending or processing)
     */
    public function isTtsInQueue()
    {
        return in_array($this->audio_status, ['pending', 'processing']);
    }

    /**
     * Check if chapter TTS can be cancelled
     */
    public function canCancelTts()
    {
        return $this->isTtsInQueue();
    }



    /**
     * Get TTS action button HTML
     */
    public function getTtsActionButtonAttribute()
    {
        if ($this->canCancelTts()) {
            return '
                <button class="btn btn-sm btn-warning cancel-tts-btn"
                        data-chapter-id="' . $this->id . '"
                        data-chapter-title="' . htmlspecialchars($this->title) . '"
                        title="Hủy TTS cho chương này">
                    <i class="fas fa-stop me-1"></i>Hủy TTS
                </button>';
        } else {
            return '
                <button class="btn btn-sm btn-success start-tts-btn"
                        data-chapter-id="' . $this->id . '"
                        data-chapter-title="' . htmlspecialchars($this->title) . '"
                        title="Bắt đầu TTS cho chương này">
                    <i class="fas fa-volume-up me-1"></i>TTS
                </button>';
        }
    }
}
