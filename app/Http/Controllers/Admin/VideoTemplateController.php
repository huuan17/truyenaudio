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
}
