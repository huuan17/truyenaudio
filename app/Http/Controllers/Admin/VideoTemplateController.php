<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $category = $request->get('category');
        $search = $request->get('search');

        $query = VideoTemplate::with('creator')
                              ->where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('usage_count', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(12);

        $categories = VideoTemplate::getCategories();
        $popularTemplates = VideoTemplate::getPopular(5);

        return view('admin.video-templates.index', compact(
            'templates',
            'categories',
            'popularTemplates',
            'category',
            'search'
        ));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        $categories = VideoTemplate::getCategories();
        $inputTypes = VideoTemplate::getInputTypes();

        // Load available channels for default channel selection
        $channels = \App\Models\Channel::where('is_active', true)
                                      ->orderBy('platform')
                                      ->orderBy('name')
                                      ->get();

        // Load audio library for background music selection
        $audioLibrary = \App\Models\AudioLibrary::where('is_public', true)
                                               ->orderBy('category')
                                               ->orderBy('title')
                                               ->get();

        return view('admin.video-templates.create', compact('categories', 'inputTypes', 'channels', 'audioLibrary'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        \Log::info('=== VideoTemplate store method START ===');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Request URL: ' . $request->url());
        \Log::info('User authenticated: ' . (auth()->check() ? 'YES' : 'NO'));

        try {
            \Log::info('VideoTemplate store method called', [
                'request_data' => $request->all(),
                'has_files' => $request->hasFile('thumbnail') || $request->hasFile('background_music_file'),
                'settings_raw' => $request->input('settings'),
                'settings_type' => gettype($request->input('settings')),
                'settings_length' => strlen($request->input('settings') ?? ''),
                'settings_preview' => substr($request->input('settings') ?? '', 0, 200)
            ]);

            try {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string|max:1000',
                    'category' => 'required|string|in:' . implode(',', array_keys(VideoTemplate::getCategories())),
                    'settings' => 'required|string', // JSON string from form
                    'required_inputs' => 'required|array',
                    'optional_inputs' => 'nullable|array',
                    'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                    'is_public' => 'nullable|boolean',
                    'default_channel_id' => 'nullable|exists:channels,id',
                    'background_music_type' => 'nullable|in:none,upload,library,random',
                    'background_music_file' => 'nullable|file|mimes:mp3,wav,aac,ogg|max:51200', // 50MB
                    'background_music_library_id' => 'nullable|integer',
                    'background_music_random_tag' => 'nullable|string',
                    'background_music_volume' => 'nullable|integer|min:0|max:100'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('VideoTemplate validation failed', [
                    'errors' => $e->errors(),
                    'request_data' => $request->all()
                ]);
                throw $e;
            }

            \Log::info('VideoTemplate validation passed');

            $data = $request->only([
                'name', 'description', 'category', 'settings',
                'required_inputs', 'optional_inputs', 'is_public', 'default_channel_id',
                'background_music_type', 'background_music_library_id',
                'background_music_random_tag', 'background_music_volume'
            ]);

            // Parse JSON settings
            try {
                $data['settings'] = json_decode($request->settings, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format in settings');
                }
            } catch (\Exception $e) {
                \Log::error('JSON parsing error', ['error' => $e->getMessage(), 'settings' => $request->settings]);
                return back()->withErrors(['settings' => 'Cài đặt JSON không hợp lệ: ' . $e->getMessage()])
                            ->withInput();
            }

            // Process options for select inputs
            $data = $this->processInputOptions($data);

            $data['created_by'] = auth()->id();
            $data['is_public'] = $request->boolean('is_public');

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('templates/thumbnails', 'public');
            }

            // Handle background music file upload
            if ($request->hasFile('background_music_file') && $data['background_music_type'] === 'upload') {
                $data['background_music_file'] = $request->file('background_music_file')->store('templates/background-music', 'public');
            }

            // Set default background music volume
            if (!isset($data['background_music_volume'])) {
                $data['background_music_volume'] = 30;
            }

            \Log::info('Creating VideoTemplate with data', ['data' => $data]);

            $template = VideoTemplate::create($data);

            \Log::info('VideoTemplate created successfully', ['template_id' => $template->id]);

            return redirect()->route('admin.video-templates.index')
                            ->with('success', 'Template đã được tạo thành công!');

        } catch (\Exception $e) {
            \Log::error('VideoTemplate creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Có lỗi xảy ra khi tạo template: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified template
     */
    public function show(VideoTemplate $videoTemplate)
    {
        $videoTemplate->load('creator');

        return view('admin.video-templates.show', compact('videoTemplate'));
    }

    /**
     * Show the form for editing the template
     */
    public function edit(VideoTemplate $videoTemplate)
    {
        // Check if user can edit this template
        if ($videoTemplate->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền chỉnh sửa template này.');
        }

        $categories = VideoTemplate::getCategories();
        $inputTypes = VideoTemplate::getInputTypes();

        return view('admin.video-templates.edit', compact('videoTemplate', 'categories', 'inputTypes'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, VideoTemplate $videoTemplate)
    {
        // Check if user can edit this template
        if ($videoTemplate->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền chỉnh sửa template này.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:' . implode(',', array_keys(VideoTemplate::getCategories())),
            'settings' => 'required|string',
            'required_inputs' => 'required|array',
            'optional_inputs' => 'nullable|array',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_public' => 'nullable|boolean'
        ]);

        // Validate and parse JSON settings
        try {
            $settings = json_decode($request->settings, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['settings' => 'Cài đặt JSON không hợp lệ.'])->withInput();
        }

        $data = $request->only([
            'name', 'description', 'category',
            'required_inputs', 'optional_inputs', 'is_public'
        ]);

        // Add parsed settings
        $data['settings'] = $settings;

        // Process options for select inputs
        $data = $this->processInputOptions($data);

        $data['is_public'] = $request->boolean('is_public');

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($videoTemplate->thumbnail) {
                Storage::disk('public')->delete($videoTemplate->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('templates/thumbnails', 'public');
        }

        $videoTemplate->update($data);

        return redirect()->route('admin.video-templates.index')
                        ->with('success', 'Template đã được cập nhật thành công!');
    }

    /**
     * Remove the specified template
     */
    public function destroy(VideoTemplate $videoTemplate)
    {
        // Check if user can delete this template
        if ($videoTemplate->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền xóa template này.');
        }

        // Delete thumbnail if exists
        if ($videoTemplate->thumbnail) {
            Storage::disk('public')->delete($videoTemplate->thumbnail);
        }

        $videoTemplate->delete();

        return redirect()->route('admin.video-templates.index')
                        ->with('success', 'Template đã được xóa thành công!');
    }

    /**
     * Save layout configuration from drag & drop editor
     */
    public function saveLayout(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:video_templates,id',
            'layout_config' => 'required|array'
        ]);

        try {
            $template = VideoTemplate::findOrFail($request->template_id);

            // Check if user can edit this template
            if ($template->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chỉnh sửa template này.'
                ], 403);
            }

            // Get current settings
            $settings = $template->settings;

            // Add layout config to settings
            $settings['layout_config'] = $request->layout_config;
            $settings['layout_updated_at'] = now()->toISOString();

            // Update template
            $template->update(['settings' => $settings]);

            return response()->json([
                'success' => true,
                'message' => 'Layout đã được lưu thành công!',
                'layout_config' => $request->layout_config
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving layout config: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Use template to generate video
     */
    public function use(VideoTemplate $videoTemplate)
    {
        try {
            \Log::info('=== VideoTemplate USE method START ===', [
                'template_id' => $videoTemplate->id,
                'template_name' => $videoTemplate->name,
                'user_id' => auth()->id()
            ]);

            $videoTemplate->incrementUsage();

            // Load audio library for background music selection
            $audioLibrary = \App\Models\AudioLibrary::where('is_public', true)
                                                   ->orderBy('category')
                                                   ->orderBy('title')
                                                   ->get();

            \Log::info('Audio library loaded', ['count' => $audioLibrary->count()]);

            // Load available channels for upload
            $channels = \App\Models\Channel::where('is_active', true)
                                          ->orderBy('platform')
                                          ->orderBy('name')
                                          ->get();

            \Log::info('Channels loaded', ['count' => $channels->count()]);

            \Log::info('Template settings', [
                'settings' => $videoTemplate->settings,
                'required_inputs' => $videoTemplate->required_inputs,
                'optional_inputs' => $videoTemplate->optional_inputs
            ]);

            return view('admin.video-templates.use', compact('videoTemplate', 'audioLibrary', 'channels'));

        } catch (\Exception $e) {
            \Log::error('VideoTemplate use method failed', [
                'template_id' => $videoTemplate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.video-templates.index')
                           ->with('error', 'Không thể sử dụng template: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate template
     */
    public function duplicate(VideoTemplate $videoTemplate)
    {
        $newTemplate = $videoTemplate->replicate();
        $newTemplate->name = $videoTemplate->name . ' (Copy)';
        $newTemplate->created_by = auth()->id();
        $newTemplate->usage_count = 0;
        $newTemplate->last_used_at = null;
        $newTemplate->is_public = false;
        $newTemplate->save();

        return redirect()->route('admin.video-templates.edit', $newTemplate)
                        ->with('success', 'Template đã được sao chép thành công!');
    }

    /**
     * Process input options for select inputs
     */
    private function processInputOptions($data)
    {
        // Process required inputs
        if (isset($data['required_inputs'])) {
            foreach ($data['required_inputs'] as &$input) {
                if ($input['type'] === 'select' && isset($input['options'])) {
                    // Parse JSON options
                    if (is_string($input['options'])) {
                        $input['options'] = json_decode($input['options'], true) ?: [];
                    }
                }
            }
        }

        // Process optional inputs
        if (isset($data['optional_inputs'])) {
            foreach ($data['optional_inputs'] as &$input) {
                if ($input['type'] === 'select' && isset($input['options'])) {
                    // Parse JSON options
                    if (is_string($input['options'])) {
                        $input['options'] = json_decode($input['options'], true) ?: [];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Generate preview video for template
     */
    public function generatePreview(Request $request)
    {
        try {
            $request->validate([
                'template_id' => 'required|exists:video_templates,id',
                'settings' => 'required|string',
                'preview_text' => 'nullable|string|max:1000',
                'preview_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120' // 5MB
            ]);

            $template = VideoTemplate::findOrFail($request->template_id);
            $settings = json_decode($request->settings, true);

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cài đặt template không hợp lệ'
                ]);
            }

            // Generate unique preview ID
            $previewId = 'preview_' . uniqid();
            $tempDir = storage_path('app/temp/preview/' . $previewId);

            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Handle preview image using DemoMediaService
            $demoMediaService = app(\App\Services\DemoMediaService::class);
            $previewImagePath = null;

            if ($request->hasFile('preview_image')) {
                $previewImagePath = $request->file('preview_image')->store('temp/preview_uploads', 'local');
                $previewImagePath = storage_path('app/' . $previewImagePath);
            } else {
                // Use demo image with template settings
                $previewImagePath = $demoMediaService->getDemoImage($settings);
            }

            // Prepare preview components with template settings
            $components = [
                'images' => [$previewImagePath],
                'audio' => null, // Could add demo audio later
                'subtitle' => [
                    'text' => $request->preview_text ?: 'Đây là text demo để xem subtitle tiếng Việt có dấu hoạt động như thế nào trong video.',
                    'size' => $settings['subtitle_size'] ?? 24,
                    'color' => $settings['subtitle_color'] ?? '#FFFFFF',
                    'position' => $settings['subtitle_position'] ?? 'bottom',
                    'font' => $settings['subtitle_font'] ?? 'Arial'
                ],
                'template_settings' => $settings
            ];

            $isAutoUpdate = $request->boolean('auto_update', false);

            \Log::info('Preview generation started', [
                'preview_id' => $previewId,
                'components' => $components,
                'auto_update' => $isAutoUpdate,
                'template_settings_applied' => [
                    'resolution' => $settings['resolution'] ?? '1920x1080',
                    'subtitle_size' => $settings['subtitle_size'] ?? 24,
                    'subtitle_color' => $settings['subtitle_color'] ?? '#ffffff',
                    'subtitle_position' => $settings['subtitle_position'] ?? 'bottom',
                    'logo_enabled' => $settings['enable_logo'] ?? false,
                    'logo_position' => $settings['logo_position'] ?? 'bottom-right'
                ]
            ]);

            // Generate preview video using existing preview service
            $previewService = app(\App\Services\VideoPreviewService::class);
            $result = $previewService->generatePreview($components, $previewId);

            if ($result['success']) {
                // Move preview to public accessible location
                $publicPreviewPath = 'videos/previews/' . $previewId . '.mp4';
                $publicFullPath = storage_path('app/public/' . $publicPreviewPath);

                // Ensure directory exists
                $publicDir = dirname($publicFullPath);
                if (!file_exists($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }

                // Copy preview to public location
                if (file_exists($result['video_path'])) {
                    copy($result['video_path'], $publicFullPath);

                    return response()->json([
                        'success' => true,
                        'preview_url' => asset('storage/' . $publicPreviewPath),
                        'preview_id' => $previewId,
                        'message' => 'Preview đã được tạo thành công'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo file preview'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Có lỗi xảy ra khi tạo preview'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Preview generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }


}
