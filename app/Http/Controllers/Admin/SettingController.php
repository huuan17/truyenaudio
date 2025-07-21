<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Display settings by group
     */
    public function index(Request $request)
    {
        $group = $request->get('group', 'general');
        $groups = Setting::getGroups();
        
        // Validate group
        if (!array_key_exists($group, $groups)) {
            $group = 'general';
        }

        $settings = Setting::where('group', $group)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('admin.settings.index', compact('settings', 'groups', 'group'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $group = $request->get('group', 'general');
        $settings = $request->get('settings', []);

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear cache
        Cache::forget('settings');

        return redirect()
            ->route('admin.settings.index', ['group' => $group])
            ->with('success', 'Cài đặt đã được cập nhật thành công!');
    }

    /**
     * Create new setting
     */
    public function create(Request $request)
    {
        $group = $request->get('group', 'general');
        $groups = Setting::getGroups();
        $types = Setting::getTypes();

        return view('admin.settings.create', compact('groups', 'types', 'group'));
    }

    /**
     * Store new setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:settings,key|max:255',
            'label' => 'required|string|max:255',
            'value' => 'nullable|string',
            'type' => 'required|string|in:text,textarea,boolean,json,url,email,code',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->has('is_active');

        Setting::create($validated);

        return redirect()
            ->route('admin.settings.index', ['group' => $validated['group']])
            ->with('success', 'Cài đặt mới đã được tạo thành công!');
    }

    /**
     * Edit setting
     */
    public function edit($id)
    {
        $setting = Setting::findOrFail($id);
        $groups = Setting::getGroups();
        $types = Setting::getTypes();

        return view('admin.settings.edit', compact('setting', 'groups', 'types'));
    }

    /**
     * Update single setting
     */
    public function updateSingle(Request $request, $id)
    {
        $setting = Setting::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,key,' . $setting->id,
            'label' => 'required|string|max:255',
            'value' => 'nullable|string',
            'type' => 'required|string|in:text,textarea,boolean,json,url,email,code',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->has('is_active');

        $setting->update($validated);

        return redirect()
            ->route('admin.settings.index', ['group' => $setting->group])
            ->with('success', 'Cài đặt đã được cập nhật thành công!');
    }

    /**
     * Delete setting
     */
    public function destroy($id)
    {
        try {
            $setting = Setting::findOrFail($id);
            $group = $setting->group;
            $settingName = $setting->label;

            $setting->delete();

            return redirect()
                ->route('admin.settings.index', ['group' => $group])
                ->with('success', "Cài đặt '{$settingName}' đã được xóa thành công!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Có lỗi xảy ra khi xóa cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Initialize default settings
     */
    public function initializeDefaults()
    {
        $defaultSettings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Audio Lara',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Tên website',
                'description' => 'Tên hiển thị của website',
                'sort_order' => 1,
            ],
            [
                'key' => 'site_description',
                'value' => 'Website nghe truyện audio online miễn phí',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Mô tả website',
                'description' => 'Mô tả ngắn về website',
                'sort_order' => 2,
            ],
            [
                'key' => 'site_keywords',
                'value' => 'truyện audio, sách nói, nghe truyện online',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Từ khóa website',
                'description' => 'Các từ khóa chính của website',
                'sort_order' => 3,
            ],

            // SEO Settings
            [
                'key' => 'seo_home_title',
                'value' => 'Audio Lara - Nghe truyện audio online miễn phí',
                'type' => 'text',
                'group' => 'seo',
                'label' => 'SEO Title trang chủ',
                'description' => 'Tiêu đề SEO cho trang chủ',
                'sort_order' => 1,
            ],
            [
                'key' => 'seo_home_description',
                'value' => 'Khám phá kho tàng truyện audio phong phú với hàng ngàn tác phẩm hay. Nghe truyện online miễn phí, chất lượng cao tại Audio Lara.',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'SEO Description trang chủ',
                'description' => 'Mô tả SEO cho trang chủ (150-160 ký tự)',
                'sort_order' => 2,
            ],
            [
                'key' => 'seo_home_keywords',
                'value' => 'truyện audio, sách nói, nghe truyện online, truyện hay, audio book',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'SEO Keywords trang chủ',
                'description' => 'Từ khóa SEO cho trang chủ',
                'sort_order' => 3,
            ],

            // Tracking Settings
            [
                'key' => 'google_analytics_id',
                'value' => '',
                'type' => 'text',
                'group' => 'tracking',
                'label' => 'Google Analytics ID',
                'description' => 'Mã Google Analytics (GA4): G-XXXXXXXXXX',
                'sort_order' => 1,
            ],
            [
                'key' => 'google_tag_manager_id',
                'value' => '',
                'type' => 'text',
                'group' => 'tracking',
                'label' => 'Google Tag Manager ID',
                'description' => 'Mã Google Tag Manager: GTM-XXXXXXX',
                'sort_order' => 2,
            ],
            [
                'key' => 'google_search_console_verification',
                'value' => '',
                'type' => 'text',
                'group' => 'tracking',
                'label' => 'Google Search Console Verification',
                'description' => 'Mã xác thực Google Search Console',
                'sort_order' => 3,
            ],
            [
                'key' => 'facebook_pixel_id',
                'value' => '',
                'type' => 'text',
                'group' => 'tracking',
                'label' => 'Facebook Pixel ID',
                'description' => 'Mã Facebook Pixel',
                'sort_order' => 4,
            ],
            [
                'key' => 'custom_head_code',
                'value' => '',
                'type' => 'code',
                'group' => 'tracking',
                'label' => 'Custom Head Code',
                'description' => 'Mã tùy chỉnh trong thẻ <head>',
                'sort_order' => 5,
            ],
            [
                'key' => 'custom_body_code',
                'value' => '',
                'type' => 'code',
                'group' => 'tracking',
                'label' => 'Custom Body Code',
                'description' => 'Mã tùy chỉnh trong thẻ <body> (remarketing, etc.)',
                'sort_order' => 6,
            ],

            // Social Settings
            [
                'key' => 'facebook_url',
                'value' => '',
                'type' => 'url',
                'group' => 'social',
                'label' => 'Facebook URL',
                'description' => 'Đường dẫn trang Facebook',
                'sort_order' => 1,
            ],
            [
                'key' => 'twitter_url',
                'value' => '',
                'type' => 'url',
                'group' => 'social',
                'label' => 'Twitter URL',
                'description' => 'Đường dẫn trang Twitter',
                'sort_order' => 2,
            ],
            [
                'key' => 'youtube_url',
                'value' => '',
                'type' => 'url',
                'group' => 'social',
                'label' => 'YouTube URL',
                'description' => 'Đường dẫn kênh YouTube',
                'sort_order' => 3,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Đã khởi tạo cài đặt mặc định thành công!');
    }
}
