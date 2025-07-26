<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioLibrary;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AudioLibraryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of audio files
     */
    public function index(Request $request)
    {
        $query = $request->get('search');
        $category = $request->get('category');
        $sourceType = $request->get('source_type');
        $language = $request->get('language');
        $voiceType = $request->get('voice_type');
        $minDuration = $request->get('min_duration');
        $maxDuration = $request->get('max_duration');

        $filters = compact('category', 'sourceType', 'language', 'voiceType', 'minDuration', 'maxDuration');

        $audioFiles = AudioLibrary::search($query, $filters)
                                 ->with('uploader')
                                 ->paginate(20);

        $categories = AudioLibrary::getCategories();
        $sourceTypes = AudioLibrary::getSourceTypes();
        $voiceTypes = AudioLibrary::getVoiceTypes();
        $languages = ['vi' => 'Tiếng Việt', 'en' => 'English'];

        // Get statistics
        $stats = [
            'total_files' => AudioLibrary::where('is_active', true)->count(),
            'total_duration' => AudioLibrary::where('is_active', true)->sum('duration'),
            'total_size' => AudioLibrary::where('is_active', true)->sum('file_size'),
            'story_audios' => AudioLibrary::where('is_active', true)->where('source_type', 'story')->count(),
        ];

        return view('admin.audio-library.index', compact(
            'audioFiles', 'categories', 'sourceTypes', 'voiceTypes', 'languages',
            'query', 'category', 'sourceType', 'language', 'voiceType',
            'minDuration', 'maxDuration', 'stats'
        ));
    }

    /**
     * Show the form for creating a new audio file
     */
    public function create()
    {
        $categories = AudioLibrary::getCategories();
        $sourceTypes = AudioLibrary::getSourceTypes();
        $voiceTypes = AudioLibrary::getVoiceTypes();
        $moodTypes = AudioLibrary::getMoodTypes();
        $languages = ['vi' => 'Tiếng Việt', 'en' => 'English'];

        return view('admin.audio-library.create', compact(
            'categories', 'sourceTypes', 'voiceTypes', 'moodTypes', 'languages'
        ));
    }

    /**
     * Store a newly created audio file
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'audio_file' => 'required|file|mimes:mp3,wav,aac,m4a,ogg|max:102400', // 100MB max
            'category' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getCategories())),
            'source_type' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getSourceTypes())),
            'language' => 'required|string|in:vi,en',
            'voice_type' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getVoiceTypes())),
            'mood' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getMoodTypes())),
            'tags' => 'nullable|string',
            'is_public' => 'nullable|boolean'
        ]);

        $audioFile = $request->file('audio_file');

        // Generate title from filename if not provided
        $title = $request->title;
        if (empty($title)) {
            $title = $this->generateTitleFromFilename($audioFile->getClientOriginalName());
        }

        $fileName = time() . '_' . Str::slug($title) . '.' . $audioFile->getClientOriginalExtension();
        $filePath = $audioFile->storeAs('audio-library', $fileName, 'public');

        // Get audio metadata
        $fullPath = Storage::disk('public')->path($filePath);
        $metadata = $this->getAudioMetadata($fullPath);

        // Process tags
        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        AudioLibrary::create([
            'title' => $title,
            'description' => $request->description ?: "Audio file: {$title}",
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_extension' => $audioFile->getClientOriginalExtension(),
            'file_size' => $audioFile->getSize(),
            'duration' => $metadata['duration'] ?? 0,
            'format' => $metadata['format'] ?? strtoupper($audioFile->getClientOriginalExtension()),
            'bitrate' => $metadata['bitrate'] ?? null,
            'sample_rate' => $metadata['sample_rate'] ?? null,
            'category' => $request->category,
            'source_type' => $request->source_type,
            'language' => $request->language,
            'voice_type' => $request->voice_type,
            'mood' => $request->mood,
            'tags' => $tags,
            'metadata' => $metadata,
            'is_public' => $request->boolean('is_public'),
            'uploaded_by' => auth()->id()
        ]);

        return redirect()->route('admin.audio-library.index')
                        ->with('success', 'Audio file đã được thêm vào thư viện thành công!');
    }

    /**
     * Store multiple audio files using queue jobs
     */
    public function storeMultiple(Request $request)
    {
        // Custom validation for multiple files with size limits
        $request->validate([
            'audio_files' => 'required|array|min:1|max:50',
            'audio_files.*' => 'required|file|mimes:mp3,wav,aac,m4a,ogg|max:102400', // 100MB max each
            'category' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getCategories())),
            'source_type' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getSourceTypes())),
            'language' => 'required|string|in:vi,en',
            'voice_type' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getVoiceTypes())),
            'mood' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getMoodTypes())),
            'tags' => 'nullable|string',
            'is_public' => 'nullable|boolean'
        ]);

        // Additional validation for total size using PHP settings
        $audioFiles = $request->file('audio_files');
        $totalSize = 0;
        foreach ($audioFiles as $file) {
            $totalSize += $file->getSize();
        }

        // Get PHP post_max_size and use 90% of it for safety
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        $maxTotalSize = $postMaxSize * 0.9; // 90% of post_max_size for form overhead

        if ($totalSize > $maxTotalSize) {
            $totalSizeMB = round($totalSize / 1024 / 1024, 1);
            $maxTotalSizeMB = round($maxTotalSize / 1024 / 1024, 1);
            $postMaxSizeMB = round($postMaxSize / 1024 / 1024, 1);

            return redirect()->back()
                           ->withErrors(['audio_files' => "Tổng kích thước files ({$totalSizeMB}MB) vượt quá giới hạn {$maxTotalSizeMB}MB (90% của post_max_size {$postMaxSizeMB}MB). Vui lòng chọn ít files hơn hoặc tăng post_max_size trong php.ini."])
                           ->withInput();
        }

        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        // Create upload batch
        $batch = \App\Models\AudioUploadBatch::create([
            'user_id' => auth()->id(),
            'total_files' => count($audioFiles),
            'status' => 'pending',
            'files' => [],
            'settings' => [
                'category' => $request->category,
                'source_type' => $request->source_type,
                'language' => $request->language,
                'voice_type' => $request->voice_type,
                'mood' => $request->mood,
                'tags' => $tags,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public'),
                'user_id' => auth()->id()
            ],
            'started_at' => now()
        ]);

        $files = [];

        // Store files temporarily and prepare job data
        foreach ($audioFiles as $index => $audioFile) {
            try {
                // Generate title and temp filename
                $title = $this->generateTitleFromFilename($audioFile->getClientOriginalName());
                $tempFileName = 'temp_' . $batch->id . '_' . $index . '_' . time() . '.' . $audioFile->getClientOriginalExtension();
                $tempPath = 'temp/audio-uploads/' . $tempFileName;

                // Store file temporarily
                $audioFile->storeAs('temp/audio-uploads', $tempFileName, 'public');

                $fileData = [
                    'original_name' => $audioFile->getClientOriginalName(),
                    'title' => $title,
                    'temp_path' => $tempPath,
                    'extension' => $audioFile->getClientOriginalExtension(),
                    'size' => $audioFile->getSize(),
                    'status' => 'pending',
                    'message' => 'Chờ xử lý...',
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $files[] = $fileData;

                // Dispatch job for this file
                \App\Jobs\ProcessAudioUploadJob::dispatch(
                    $batch->id,
                    $fileData,
                    $index,
                    $batch->settings
                )->delay(now()->addSeconds($index * 2)); // Stagger jobs by 2 seconds

            } catch (\Exception $e) {
                $files[] = [
                    'original_name' => $audioFile->getClientOriginalName(),
                    'title' => $audioFile->getClientOriginalName(),
                    'status' => 'failed',
                    'message' => 'Lỗi upload: ' . $e->getMessage(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        // Update batch with file info
        $batch->update([
            'files' => $files,
            'status' => 'processing'
        ]);

        return redirect()->route('admin.audio-library.batch-status', $batch->id)
                        ->with('success', "Đã bắt đầu upload {$batch->total_files} files. Bạn có thể theo dõi tiến trình tại đây.");
    }

    /**
     * Show batch upload status
     */
    public function batchStatus($batchId)
    {
        $batch = \App\Models\AudioUploadBatch::findOrFail($batchId);

        // Check if user owns this batch
        if ($batch->user_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền xem batch này.');
        }

        return view('admin.audio-library.batch-status', compact('batch'));
    }

    /**
     * Get batch status via API (for real-time updates)
     */
    public function getBatchStatus($batchId)
    {
        $batch = \App\Models\AudioUploadBatch::findOrFail($batchId);

        // Check if user owns this batch
        if ($batch->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'progress' => $batch->progress_percentage,
            'total_files' => $batch->total_files,
            'completed_files' => $batch->completed_files,
            'failed_files' => $batch->failed_files,
            'processing_files' => $batch->processing_files,
            'files' => $batch->files,
            'summary' => $batch->summary,
            'is_completed' => $batch->isCompleted(),
            'created_at' => $batch->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $batch->updated_at->format('d/m/Y H:i:s')
        ]);
    }

    /**
     * List user's upload batches
     */
    public function batchList()
    {
        $batches = \App\Models\AudioUploadBatch::where('user_id', auth()->id())
                                              ->orderBy('created_at', 'desc')
                                              ->paginate(20);

        return view('admin.audio-library.batch-list', compact('batches'));
    }

    /**
     * Display the specified audio file
     */
    public function show(AudioLibrary $audioLibrary)
    {
        $audioLibrary->load('uploader');

        // Load source if available
        if ($audioLibrary->source_type === 'story' && $audioLibrary->source_id) {
            $audioLibrary->load('sourceStory');
        } elseif ($audioLibrary->source_type === 'chapter' && $audioLibrary->source_id) {
            $audioLibrary->load('sourceChapter');
        }

        return view('admin.audio-library.show', compact('audioLibrary'));
    }

    /**
     * Show the form for editing the audio file
     */
    public function edit(AudioLibrary $audioLibrary)
    {
        $categories = AudioLibrary::getCategories();
        $sourceTypes = AudioLibrary::getSourceTypes();
        $voiceTypes = AudioLibrary::getVoiceTypes();
        $moodTypes = AudioLibrary::getMoodTypes();
        $languages = ['vi' => 'Tiếng Việt', 'en' => 'English'];

        return view('admin.audio-library.edit', compact(
            'audioLibrary', 'categories', 'sourceTypes', 'voiceTypes', 'moodTypes', 'languages'
        ));
    }

    /**
     * Update the specified audio file
     */
    public function update(Request $request, AudioLibrary $audioLibrary)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getCategories())),
            'source_type' => 'required|string|in:' . implode(',', array_keys(AudioLibrary::getSourceTypes())),
            'language' => 'required|string|in:vi,en',
            'voice_type' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getVoiceTypes())),
            'mood' => 'nullable|string|in:' . implode(',', array_keys(AudioLibrary::getMoodTypes())),
            'tags' => 'nullable|string',
            'is_public' => 'nullable|boolean'
        ]);

        // Process tags
        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $audioLibrary->update([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'source_type' => $request->source_type,
            'language' => $request->language,
            'voice_type' => $request->voice_type,
            'mood' => $request->mood,
            'tags' => $tags,
            'is_public' => $request->boolean('is_public')
        ]);

        return redirect()->route('admin.audio-library.index')
                        ->with('success', 'Audio file đã được cập nhật thành công!');
    }

    /**
     * Remove the specified audio file
     */
    public function destroy(AudioLibrary $audioLibrary)
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($audioLibrary->file_path)) {
            Storage::disk('public')->delete($audioLibrary->file_path);
        }

        $audioLibrary->delete();

        return redirect()->route('admin.audio-library.index')
                        ->with('success', 'Audio file đã được xóa thành công!');
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:update-category,toggle-public,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:audio_libraries,id',
            'category' => 'nullable|required_if:action,update-category|in:' . implode(',', array_keys(AudioLibrary::getCategories()))
        ]);

        $action = $request->action;
        $ids = $request->ids;
        $affectedCount = 0;

        try {
            switch ($action) {
                case 'update-category':
                    $affectedCount = AudioLibrary::whereIn('id', $ids)->update([
                        'category' => $request->category
                    ]);
                    $message = "Đã cập nhật danh mục cho {$affectedCount} audio files!";
                    break;

                case 'toggle-public':
                    // Toggle public status for each audio
                    $audioFiles = AudioLibrary::whereIn('id', $ids)->get();
                    foreach ($audioFiles as $audio) {
                        $audio->update(['is_public' => !$audio->is_public]);
                        $affectedCount++;
                    }
                    $message = "Đã đổi trạng thái công khai cho {$affectedCount} audio files!";
                    break;

                case 'delete':
                    // Delete files from storage first
                    $audioFiles = AudioLibrary::whereIn('id', $ids)->get();
                    foreach ($audioFiles as $audio) {
                        if (Storage::disk('public')->exists($audio->file_path)) {
                            Storage::disk('public')->delete($audio->file_path);
                        }
                    }

                    $affectedCount = AudioLibrary::whereIn('id', $ids)->delete();
                    $message = "Đã xóa {$affectedCount} audio files!";
                    break;
            }

            return redirect()->route('admin.audio-library.index')
                            ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('admin.audio-library.index')
                            ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export audio library to CSV
     */
    public function export(Request $request)
    {
        $query = $request->get('search');
        $category = $request->get('category');
        $sourceType = $request->get('source_type');
        $language = $request->get('language');
        $voiceType = $request->get('voice_type');

        $filters = compact('category', 'sourceType', 'language', 'voiceType');

        $audioFiles = AudioLibrary::search($query, $filters)
                                 ->with('uploader')
                                 ->get();

        $filename = 'audio_library_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($audioFiles) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Description', 'Category', 'Source Type', 'Language', 'Voice Type', 'Mood',
                'Duration (seconds)', 'File Size (bytes)', 'Format', 'Bitrate (kbps)', 'Sample Rate (Hz)',
                'Tags', 'Is Public', 'Usage Count', 'Uploaded By', 'Created At', 'Last Used At'
            ]);

            // CSV data
            foreach ($audioFiles as $audio) {
                fputcsv($file, [
                    $audio->id, $audio->title, $audio->description, $audio->category, $audio->source_type,
                    $audio->language, $audio->voice_type, $audio->mood, $audio->duration, $audio->file_size,
                    $audio->format, $audio->bitrate, $audio->sample_rate,
                    $audio->tags ? implode(', ', $audio->tags) : '', $audio->is_public ? 'Yes' : 'No',
                    $audio->usage_count, $audio->uploader->name ?? 'Unknown',
                    $audio->created_at->format('Y-m-d H:i:s'),
                    $audio->last_used_at ? $audio->last_used_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download audio file
     */
    public function download(AudioLibrary $audioLibrary)
    {
        $audioLibrary->incrementUsage();

        $filePath = Storage::disk('public')->path($audioLibrary->file_path);

        if (!File::exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $audioLibrary->file_name);
    }

    /**
     * Stream audio file for preview
     */
    public function stream(AudioLibrary $audioLibrary)
    {
        $filePath = Storage::disk('public')->path($audioLibrary->file_path);

        if (!File::exists($filePath)) {
            abort(404, 'File not found');
        }

        // Get file info
        $fileSize = filesize($filePath);
        $mimeType = $this->getAudioMimeType($audioLibrary->file_extension);

        // Handle range requests for audio streaming
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=3600',
        ];

        // Check if this is a range request
        $range = request()->header('Range');

        if ($range) {
            // Parse range header
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start = intval($matches[1]);
            $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

            // Validate range
            if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
                return response('', 416, ['Content-Range' => "bytes */{$fileSize}"]);
            }

            $length = $end - $start + 1;

            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
            $headers['Content-Length'] = $length;

            // Read partial content
            $file = fopen($filePath, 'rb');
            fseek($file, $start);
            $content = fread($file, $length);
            fclose($file);

            return response($content, 206, $headers);
        }

        // Return full file
        return response()->file($filePath, $headers);
    }

    /**
     * Get MIME type for audio file
     */
    private function getAudioMimeType($extension)
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
            'wma' => 'audio/x-ms-wma',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'audio/mpeg';
    }

    /**
     * Get audio for video generator (API endpoint)
     */
    public function getForVideoGenerator(Request $request)
    {
        $query = $request->get('search');
        $category = $request->get('category');
        $maxDuration = $request->get('max_duration');

        $filters = compact('category', 'maxDuration');

        $audioFiles = AudioLibrary::search($query, $filters)
                                 ->where('is_active', true)
                                 ->select('id', 'title', 'duration', 'file_path', 'category', 'voice_type')
                                 ->limit(50)
                                 ->get();

        return response()->json([
            'success' => true,
            'data' => $audioFiles->map(function($audio) {
                return [
                    'id' => $audio->id,
                    'title' => $audio->title,
                    'duration' => $audio->formatted_duration,
                    'url' => $audio->file_url,
                    'category' => $audio->category,
                    'voice_type' => $audio->voice_type
                ];
            })
        ]);
    }

    /**
     * Get random background music for templates
     */
    public function getRandomBackgroundMusic(Request $request)
    {
        $tag = $request->get('tag', 'music'); // Default tag

        // Define tag mappings for background music
        $tagMappings = [
            'music' => ['music', 'background', 'instrumental'],
            'relaxing' => ['relaxing', 'calm', 'peaceful', 'ambient'],
            'story' => ['story', 'narrative', 'audiobook', 'reading'],
            'upbeat' => ['upbeat', 'energetic', 'happy', 'positive'],
            'cinematic' => ['cinematic', 'epic', 'dramatic', 'orchestral'],
            'nature' => ['nature', 'rain', 'forest', 'ocean'],
        ];

        $searchTags = $tagMappings[$tag] ?? [$tag];

        // Build query for background music
        $query = AudioLibrary::where('is_public', true)
                            ->where('category', 'music') // Focus on music category
                            ->where(function($q) use ($searchTags) {
                                foreach ($searchTags as $searchTag) {
                                    $q->orWhere('tags', 'like', "%{$searchTag}%")
                                      ->orWhere('title', 'like', "%{$searchTag}%")
                                      ->orWhere('description', 'like', "%{$searchTag}%");
                                }
                            });

        // If no music found with specific tags, get any music
        $audio = $query->inRandomOrder()->first();

        if (!$audio) {
            // Fallback to any music category audio
            $audio = AudioLibrary::where('is_public', true)
                                ->where('category', 'music')
                                ->inRandomOrder()
                                ->first();
        }

        if (!$audio) {
            // Final fallback to any public audio
            $audio = AudioLibrary::where('is_public', true)
                                ->inRandomOrder()
                                ->first();
        }

        if ($audio) {
            return response()->json([
                'success' => true,
                'audio' => [
                    'id' => $audio->id,
                    'title' => $audio->title,
                    'description' => $audio->description,
                    'file_path' => $audio->file_path,
                    'duration' => $audio->duration,
                    'category' => $audio->category,
                    'tags' => $audio->tags,
                    'url' => asset('storage/' . $audio->file_path)
                ],
                'tag_used' => $tag,
                'search_tags' => $searchTags
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy audio phù hợp trong thư viện',
            'tag_used' => $tag,
            'search_tags' => $searchTags
        ]);
    }

    /**
     * Get server upload limits
     */
    public function getUploadLimits()
    {
        // Get raw PHP settings for debugging
        $rawSettings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads')
        ];

        $uploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));

        // Use 90% of post_max_size for safety (form overhead)
        $recommendedMaxTotal = $postMaxSize * 0.9; // 90% of post_max_size
        $recommendedMaxFiles = (int)ini_get('max_file_uploads');

        return response()->json([
            'debug' => [
                'raw_settings' => $rawSettings,
                'parsed_post_max_size' => $postMaxSize,
                'calculated_max_total' => $recommendedMaxTotal,
                'php_ini_path' => php_ini_loaded_file()
            ],
            'server_limits' => [
                'upload_max_filesize' => $uploadMaxFilesize,
                'upload_max_filesize_formatted' => $this->formatBytes($uploadMaxFilesize),
                'post_max_size' => $postMaxSize,
                'post_max_size_formatted' => $this->formatBytes($postMaxSize),
                'max_execution_time' => $maxExecutionTime,
                'memory_limit' => $memoryLimit,
                'memory_limit_formatted' => $this->formatBytes($memoryLimit)
            ],
            'recommended_limits' => [
                'max_total_size' => $recommendedMaxTotal,
                'max_total_size_formatted' => $this->formatBytes($recommendedMaxTotal),
                'max_files' => $recommendedMaxFiles,
                'max_file_size' => $uploadMaxFilesize,
                'max_file_size_formatted' => $this->formatBytes($uploadMaxFilesize)
            ]
        ]);
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Import story audios to library
     */
    public function importStoryAudios(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id'
        ]);

        $story = Story::findOrFail($request->story_id);
        $chapters = Chapter::where('story_id', $story->id)
                          ->whereNotNull('audio_file_path')
                          ->get();

        $imported = 0;
        foreach ($chapters as $chapter) {
            // Check if already imported
            $exists = AudioLibrary::where('source_type', 'chapter')
                                 ->where('source_id', $chapter->id)
                                 ->exists();

            if (!$exists && Storage::exists($chapter->audio_file_path)) {
                $audioPath = Storage::path($chapter->audio_file_path);
                $metadata = $this->getAudioMetadata($audioPath);

                AudioLibrary::create([
                    'title' => $story->title . ' - ' . $chapter->title,
                    'description' => "Audio từ chương {$chapter->chapter_number}: {$chapter->title}",
                    'file_path' => $chapter->audio_file_path,
                    'file_name' => basename($chapter->audio_file_path),
                    'file_extension' => pathinfo($chapter->audio_file_path, PATHINFO_EXTENSION),
                    'file_size' => Storage::size($chapter->audio_file_path),
                    'duration' => $metadata['duration'] ?? 0,
                    'format' => $metadata['format'] ?? 'MP3',
                    'bitrate' => $metadata['bitrate'] ?? null,
                    'sample_rate' => $metadata['sample_rate'] ?? null,
                    'category' => 'story',
                    'source_type' => 'chapter',
                    'source_id' => $chapter->id,
                    'language' => 'vi',
                    'voice_type' => 'female', // Default, can be updated
                    'tags' => [$story->title, 'truyện', 'chapter'],
                    'metadata' => array_merge($metadata, [
                        'story_id' => $story->id,
                        'chapter_number' => $chapter->chapter_number
                    ]),
                    'is_public' => false,
                    'uploaded_by' => auth()->id()
                ]);

                $imported++;
            }
        }

        return redirect()->route('admin.audio-library.index')
                        ->with('success', "Đã import {$imported} audio files từ truyện {$story->title}!");
    }

    /**
     * Generate clean title from filename
     */
    private function generateTitleFromFilename($filename)
    {
        // Remove file extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Replace common separators with spaces
        $name = str_replace(['_', '-', '.'], ' ', $name);

        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove common prefixes/suffixes
        $name = preg_replace('/^(audio|track|song|music|sound)\s*/i', '', $name);
        $name = preg_replace('/\s*(audio|track|song|music|sound)$/i', '', $name);

        // Remove numbers at the beginning if they look like track numbers
        $name = preg_replace('/^\d{1,3}[\s\-\.]*/', '', $name);

        // Capitalize each word
        $name = ucwords(strtolower(trim($name)));

        // If empty after cleaning, use original filename
        if (empty($name)) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = ucwords(str_replace(['_', '-'], ' ', $name));
        }

        return $name;
    }

    /**
     * Get audio metadata using ffprobe
     */
    private function getAudioMetadata($filePath)
    {
        try {
            // Get duration
            $durationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$filePath}\"";
            $duration = (float) trim(shell_exec($durationCmd));

            // Get format info
            $formatCmd = "ffprobe -v quiet -show_entries format=format_name -of csv=p=0 \"{$filePath}\"";
            $format = trim(shell_exec($formatCmd));

            // Get bitrate
            $bitrateCmd = "ffprobe -v quiet -show_entries format=bit_rate -of csv=p=0 \"{$filePath}\"";
            $bitrate = (int) trim(shell_exec($bitrateCmd));

            // Get sample rate
            $sampleRateCmd = "ffprobe -v quiet -show_entries stream=sample_rate -of csv=p=0 \"{$filePath}\"";
            $sampleRate = (int) trim(shell_exec($sampleRateCmd));

            return [
                'duration' => (int) $duration,
                'format' => strtoupper(explode(',', $format)[0] ?? ''),
                'bitrate' => $bitrate > 0 ? round($bitrate / 1000) : null, // Convert to kbps
                'sample_rate' => $sampleRate > 0 ? $sampleRate : null,
                'analyzed_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'duration' => 0,
                'format' => null,
                'bitrate' => null,
                'sample_rate' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
