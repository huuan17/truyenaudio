<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Helpers\SlugHelper;

class Story extends Model
{
    use SoftDeletes;



    protected $fillable = [
        'title',
        'slug',
        'author',
        'author_id',
        'description',
        'cover_image',
        'source_url',
        'start_chapter',
        'end_chapter',
        'crawl_status',
        'crawl_job_id',
        'crawl_path',
        'status',
        'folder_name',
        'is_public',
        'is_active',
        'auto_crawl',
        'auto_tts',
        'default_tts_voice',
        'default_tts_bitrate',
        'default_tts_speed',
        'default_tts_volume',
        'missing_chapters_info',
    ];

    protected $casts = [
        'crawl_status' => 'integer',
        'start_chapter' => 'integer',
        'end_chapter' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'auto_crawl' => 'boolean',
        'auto_tts' => 'boolean',
        'default_tts_bitrate' => 'integer',
        'default_tts_speed' => 'float',
        'default_tts_volume' => 'float',
        'missing_chapters_info' => 'array',
    ];

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * Get the author of the story.
     */
    public function authorModel()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    /**
     * Get the author of the story (alias for backward compatibility).
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Scope để lấy stories public (hiển thị ở frontend)
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope để lấy stories active (đang hoạt động)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope để lấy stories hiển thị ở frontend (public + active)
     */
    public function scopeVisible($query)
    {
        return $query->where('is_public', true)->where('is_active', true);
    }

    /**
     * Scope để lấy stories ẩn (không hiển thị ở frontend)
     */
    public function scopeHidden($query)
    {
        return $query->where(function($q) {
            $q->where('is_public', false)->orWhere('is_active', false);
        });
    }

    /**
     * Generate slug from title
     */
    public function getSlugAttribute($value)
    {
        return $value ?: SlugHelper::createSlug($this->title);
    }

    /**
     * Check if story is visible to public
     */
    public function isVisible()
    {
        return $this->is_public && $this->is_active;
    }

    /**
     * Get visibility status text
     */
    public function getVisibilityStatusAttribute()
    {
        if ($this->is_public && $this->is_active) {
            return 'Công khai';
        } elseif (!$this->is_public && $this->is_active) {
            return 'Riêng tư';
        } elseif ($this->is_public && !$this->is_active) {
            return 'Tạm dừng';
        } else {
            return 'Ẩn';
        }
    }

    /**
     * Get auto crawl status text
     */
    public function getAutoCrawlStatusAttribute()
    {
        return $this->auto_crawl ? 'Bật' : 'Tắt';
    }

    /**
     * Get auto TTS status text
     */
    public function getAutoTtsStatusAttribute()
    {
        return $this->auto_tts ? 'Bật' : 'Tắt';
    }

    /**
     * Check if story should auto crawl
     */
    public function shouldAutoCrawl()
    {
        return $this->auto_crawl && $this->is_active;
    }

    /**
     * Check if story should auto convert to TTS
     */
    public function shouldAutoTts()
    {
        return $this->auto_tts && $this->is_active;
    }

    /**
     * Get visibility status badge class
     */
    public function getVisibilityBadgeClassAttribute()
    {
        if ($this->is_public && $this->is_active) {
            return 'badge-success';
        } elseif (!$this->is_public && $this->is_active) {
            return 'badge-warning';
        } elseif ($this->is_public && !$this->is_active) {
            return 'badge-secondary';
        } else {
            return 'badge-danger';
        }
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
                $story->slug = SlugHelper::createUniqueSlug($story->title, static::class);
            }
        });

        static::updating(function ($story) {
            if ($story->isDirty('title') && empty($story->slug)) {
                $story->slug = SlugHelper::createUniqueSlug($story->title, static::class, $story->id);
            }
        });
    }

    /**
     * Check if crawl is actually complete based on chapter count
     */
    public function isCrawlComplete()
    {
        $expectedTotal = $this->end_chapter - $this->start_chapter + 1;
        $actualChapters = $this->chapters()->count();

        return $actualChapters >= $expectedTotal;
    }

    /**
     * Check if files are complete in storage
     */
    public function areFilesComplete()
    {
        $expectedTotal = $this->end_chapter - $this->start_chapter + 1;
        $storageDir = storage_path('app/content/' . $this->folder_name);

        if (!File::isDirectory($storageDir)) {
            return false;
        }

        $files = File::glob($storageDir . '/chapter_*.txt');
        return count($files) >= $expectedTotal;
    }

    /**
     * Get crawl progress information
     */
    public function getCrawlProgress()
    {
        $expectedTotal = $this->end_chapter - $this->start_chapter + 1;
        $actualChapters = $this->chapters()->count();
        $crawledChapters = $this->chapters()->where('is_crawled', true)->count();

        // Check files
        $storageDir = storage_path('app/content/' . $this->folder_name);
        $fileCount = 0;
        if (File::isDirectory($storageDir)) {
            $files = File::glob($storageDir . '/chapter_*.txt');
            $fileCount = count($files);
        }

        return [
            'expected_total' => $expectedTotal,
            'chapters_in_db' => $actualChapters,
            'crawled_chapters' => $crawledChapters,
            'files_in_storage' => $fileCount,
            'db_complete' => $actualChapters >= $expectedTotal,
            'files_complete' => $fileCount >= $expectedTotal,
            'progress_percentage' => $expectedTotal > 0 ? round(($actualChapters / $expectedTotal) * 100, 2) : 0,
            'is_stuck' => $this->crawl_status == config('constants.CRAWL_STATUS.VALUES.CRAWLING') &&
                         $this->updated_at->diffInMinutes(now()) > 120
        ];
    }

    /**
     * Smart status update based on actual progress
     */
    public function updateCrawlStatusSmart()
    {
        $progress = $this->getCrawlProgress();

        if ($progress['db_complete']) {
            // Crawl is complete
            $this->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                'crawl_job_id' => null
            ]);
            return 'completed';

        } elseif ($progress['files_complete']) {
            // Files are ready, need import
            return 'needs_import';

        } elseif ($progress['chapters_in_db'] > 0 || $progress['files_in_storage'] > 0) {
            // Partial progress, mark for re-crawl
            $this->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                'crawl_job_id' => null
            ]);
            return 'partial';

        } else {
            // No progress, reset
            $this->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                'crawl_job_id' => null
            ]);
            return 'reset';
        }
    }

    /**
     * Auto-fix crawl status if incorrect
     */
    public function autoFixCrawlStatus()
    {
        $expectedTotal = $this->end_chapter - $this->start_chapter + 1;
        $actualChapters = $this->chapters()->count();
        $isComplete = $actualChapters >= $expectedTotal;

        // If story is complete but not marked as CRAWLED
        if ($isComplete && $this->crawl_status != config('constants.CRAWL_STATUS.VALUES.CRAWLED')) {
            $oldStatus = $this->crawl_status;

            $this->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                'crawl_job_id' => null
            ]);

            \Log::info("Auto-fixed crawl status for story: {$this->title}", [
                'story_id' => $this->id,
                'expected_total' => $expectedTotal,
                'actual_chapters' => $actualChapters,
                'old_status' => $oldStatus,
                'new_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED')
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get TTS settings with fallback to defaults
     */
    public function getTtsSettings()
    {
        return [
            'voice' => $this->default_tts_voice ?? 'hn_female_ngochuyen_full_48k-fhg',
            'bitrate' => $this->default_tts_bitrate ?? 128,
            'speed' => $this->default_tts_speed ?? 1.0,
            'volume' => $this->default_tts_volume ?? 1.0,
        ];
    }

    /**
     * Get formatted TTS settings for display
     */
    public function getFormattedTtsSettings()
    {
        $settings = $this->getTtsSettings();

        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_phuthang_stor80dt_48k-fhg' => 'Anh Khôi (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)',
            'sg_female_tuongvy_call_44k-fhg' => 'Tường Vy (Nữ - Sài Gòn)'
        ];

        return [
            'voice' => $voices[$settings['voice']] ?? 'Không xác định',
            'bitrate' => $settings['bitrate'] . ' kbps',
            'speed' => $settings['speed'] . 'x',
            'volume' => ($settings['volume'] * 100) . '%',
        ];
    }

    /**
     * Get TTS progress information
     */
    public function getTtsProgress()
    {
        $totalChapters = $this->chapters()->count();

        if ($totalChapters === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'pending' => 0,
                'processing' => 0,
                'failed' => 0,
                'progress_percentage' => 0,
                'status' => config('constants.TTS_STATUS.VALUES.NOT_STARTED')
            ];
        }

        $completedChapters = $this->chapters()->where('audio_status', 'completed')->count();
        $pendingChapters = $this->chapters()->where('audio_status', 'pending')->count();
        $processingChapters = $this->chapters()->where('audio_status', 'processing')->count();
        $failedChapters = $this->chapters()->where('audio_status', 'failed')->count();

        // Determine overall status
        if ($processingChapters > 0) {
            $status = config('constants.TTS_STATUS.VALUES.PROCESSING');
        } elseif ($pendingChapters > 0) {
            $status = config('constants.TTS_STATUS.VALUES.PENDING');
        } elseif ($completedChapters === $totalChapters) {
            $status = config('constants.TTS_STATUS.VALUES.COMPLETED');
        } elseif ($failedChapters > 0 && $completedChapters > 0) {
            $status = config('constants.TTS_STATUS.VALUES.PARTIAL');
        } elseif ($failedChapters > 0) {
            $status = config('constants.TTS_STATUS.VALUES.FAILED');
        } else {
            $status = config('constants.TTS_STATUS.VALUES.NOT_STARTED');
        }

        return [
            'total' => $totalChapters,
            'completed' => $completedChapters,
            'pending' => $pendingChapters,
            'processing' => $processingChapters,
            'failed' => $failedChapters,
            'progress_percentage' => $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100, 1) : 0,
            'status' => $status
        ];
    }

    /**
     * Get missing chapters information
     */
    public function getMissingChaptersAttribute()
    {
        if (!$this->missing_chapters_info) {
            return null;
        }

        return [
            'chapters' => $this->missing_chapters_info['chapters'] ?? [],
            'count' => count($this->missing_chapters_info['chapters'] ?? []),
            'last_check' => $this->missing_chapters_info['last_check'] ?? null,
            'reason' => $this->missing_chapters_info['reason'] ?? 'unknown'
        ];
    }

    /**
     * Check if story has missing chapters that couldn't be found at source
     */
    public function hasMissingChaptersAtSource()
    {
        return !empty($this->missing_chapters_info['chapters'] ?? []);
    }

    /**
     * Get missing chapters display text for admin interface
     */
    public function getMissingChaptersDisplayText()
    {
        if (!$this->hasMissingChaptersAtSource()) {
            return null;
        }

        $info = $this->missing_chapters;
        $chapters = $info['chapters'];

        if (count($chapters) <= 5) {
            return 'Thiếu chương: ' . implode(', ', $chapters);
        } else {
            $first5 = array_slice($chapters, 0, 5);
            $remaining = count($chapters) - 5;
            return 'Thiếu chương: ' . implode(', ', $first5) . ' và ' . $remaining . ' chương khác';
        }
    }

    /**
     * Tự động cập nhật số chương dựa trên chapters thực tế
     */
    public function updateChapterCount()
    {
        $chapters = $this->chapters();
        $count = $chapters->count();

        if ($count > 0) {
            $this->start_chapter = $chapters->min('chapter_number');
            $this->end_chapter = $chapters->max('chapter_number');
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Kiểm tra và cập nhật trạng thái crawl
     */
    public function updateCrawlStatus()
    {
        $actualChapters = $this->chapters()->count();
        $expectedChapters = $this->end_chapter - $this->start_chapter + 1;

        if ($actualChapters >= $expectedChapters && $this->crawl_status == 0) {
            $this->crawl_status = 1;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Hủy tất cả TTS requests đang pending cho story này
     */
    public function cancelPendingTTS()
    {
        return $this->chapters()
            ->whereIn('audio_status', ['pending', 'processing'])
            ->update([
                'audio_status' => 'none',
                'tts_started_at' => null,
                'tts_error' => 'Cancelled by user'
            ]);
    }
}