<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display help center index
     */
    public function index()
    {
        $helpSections = $this->getHelpSections();
        return view('admin.help.index', compact('helpSections'));
    }

    /**
     * Display specific help section
     */
    public function show($section)
    {
        $helpSections = $this->getHelpSections();
        
        if (!isset($helpSections[$section])) {
            abort(404, 'Help section not found');
        }

        $currentSection = $helpSections[$section];
        $content = $this->getHelpContent($section);

        return view('admin.help.show', compact('helpSections', 'currentSection', 'content', 'section'));
    }

    /**
     * Get help sections configuration
     */
    private function getHelpSections()
    {
        return [
            'overview' => [
                'title' => 'Tổng quan hệ thống',
                'icon' => 'fas fa-home',
                'description' => 'Giới thiệu chung về các tính năng của hệ thống',
                'color' => 'primary'
            ],
            'stories' => [
                'title' => 'Quản lý Truyện',
                'icon' => 'fas fa-book',
                'description' => 'Hướng dẫn tạo, chỉnh sửa và quản lý truyện',
                'color' => 'success'
            ],
            'chapters' => [
                'title' => 'Quản lý Chương',
                'icon' => 'fas fa-file-alt',
                'description' => 'Thêm chương, bulk actions, TTS conversion',
                'color' => 'info'
            ],
            'audio' => [
                'title' => 'Audio & TTS',
                'icon' => 'fas fa-volume-up',
                'description' => 'Text-to-Speech, audio player, speed control',
                'color' => 'warning'
            ],
            'video' => [
                'title' => 'Video Generator',
                'icon' => 'fas fa-video',
                'description' => 'Universal Video Generator cho TikTok & YouTube',
                'color' => 'danger'
            ],
            'channels' => [
                'title' => 'Quản lý Kênh',
                'icon' => 'fas fa-broadcast-tower',
                'description' => 'Kết nối TikTok, YouTube, quản lý OAuth',
                'color' => 'secondary'
            ],
            'queue' => [
                'title' => 'Queue Management',
                'icon' => 'fas fa-tasks',
                'description' => 'Theo dõi và quản lý queue xử lý video',
                'color' => 'dark'
            ],
            'troubleshooting' => [
                'title' => 'Xử lý sự cố',
                'icon' => 'fas fa-tools',
                'description' => 'Hướng dẫn khắc phục các lỗi thường gặp',
                'color' => 'danger'
            ],
            'api' => [
                'title' => 'API Documentation',
                'icon' => 'fas fa-code',
                'description' => 'Hướng dẫn sử dụng API và integration',
                'color' => 'info'
            ],
            'tts-bulk' => [
                'title' => 'TTS Bulk Actions',
                'icon' => 'fas fa-microphone',
                'description' => 'Hướng dẫn TTS hàng loạt và quản lý queue',
                'color' => 'primary',
                'md_file' => 'QUEUE_BASED_BULK_TTS_GUIDE.md'
            ],
            'tiktok-setup' => [
                'title' => 'TikTok Setup',
                'icon' => 'fab fa-tiktok',
                'description' => 'Hướng dẫn cài đặt và sử dụng TikTok',
                'color' => 'info',
                'md_file' => 'TIKTOK_SETUP_GUIDE.md'
            ],
            'story-visibility' => [
                'title' => 'Story Visibility',
                'icon' => 'fas fa-eye',
                'description' => 'Hướng dẫn quản lý hiển thị truyện',
                'color' => 'success',
                'md_file' => 'STORY_VISIBILITY_GUIDE.md'
            ],
            'auto-next' => [
                'title' => 'Auto Next Chapter',
                'icon' => 'fas fa-forward',
                'description' => 'Hướng dẫn tính năng tự động chuyển chương',
                'color' => 'warning',
                'md_file' => 'AUTO_NEXT_CHAPTER_GUIDE.md'
            ],
            'breadcrumb-ui' => [
                'title' => 'Navigation & UI',
                'icon' => 'fas fa-sitemap',
                'description' => 'Hướng dẫn breadcrumb và giao diện',
                'color' => 'secondary',
                'md_file' => 'BREADCRUMB_AND_INDIVIDUAL_TTS_CANCEL_GUIDE.md'
            ],
            'deployment' => [
                'title' => 'Hosting Deployment',
                'icon' => 'fas fa-server',
                'description' => 'Hướng dẫn deploy lên hosting',
                'color' => 'danger',
                'md_file' => 'HOSTING_DEPLOYMENT.md'
            ],
            'universal-video' => [
                'title' => 'Universal Video Generator',
                'icon' => 'fas fa-video',
                'description' => 'Hướng dẫn Universal Video Generator',
                'color' => 'primary',
                'md_file' => 'UNIVERSAL_VIDEO_GENERATOR_GUIDE.md'
            ],
            'enhanced-audio' => [
                'title' => 'Enhanced Audio Player',
                'icon' => 'fas fa-music',
                'description' => 'Hướng dẫn audio player nâng cao',
                'color' => 'info',
                'md_file' => 'ENHANCED_AUDIO_PLAYER_GUIDE.md'
            ],
            'bulk-actions' => [
                'title' => 'Bulk Actions Guide',
                'icon' => 'fas fa-tasks',
                'description' => 'Hướng dẫn các thao tác hàng loạt',
                'color' => 'warning',
                'md_file' => 'BULK_ACTIONS_QUICK_GUIDE.md'
            ]
        ];
    }

    /**
     * Get help content for specific section
     */
    private function getHelpContent($section)
    {
        $sections = $this->getHelpSections();

        // Check if section has markdown file
        if (isset($sections[$section]['md_file'])) {
            return $this->getMarkdownContent($sections[$section]['md_file']);
        }

        // Fallback to hardcoded content
        switch ($section) {
            case 'overview':
                return $this->getOverviewContent();
            case 'stories':
                return $this->getStoriesContent();
            case 'chapters':
                return $this->getChaptersContent();
            case 'audio':
                return $this->getAudioContent();
            case 'video':
                return $this->getVideoContent();
            case 'channels':
                return $this->getChannelsContent();
            case 'queue':
                return $this->getQueueContent();
            case 'troubleshooting':
                return $this->getTroubleshootingContent();
            default:
                return [];
        }
    }

    /**
     * Read and parse markdown file content
     */
    private function getMarkdownContent($filename)
    {
        $filePath = base_path($filename);

        if (!file_exists($filePath)) {
            return [
                'title' => 'File không tìm thấy',
                'content' => "Không thể tìm thấy file hướng dẫn: {$filename}",
                'sections' => []
            ];
        }

        try {
            $content = file_get_contents($filePath);

            // Parse markdown content
            return $this->parseMarkdownContent($content, $filename);

        } catch (Exception $e) {
            return [
                'title' => 'Lỗi đọc file',
                'content' => "Không thể đọc file hướng dẫn: {$e->getMessage()}",
                'sections' => []
            ];
        }
    }

    /**
     * Parse markdown content into structured format
     */
    private function parseMarkdownContent($content, $filename)
    {
        $lines = explode("\n", $content);
        $title = '';
        $sections = [];
        $currentSection = null;
        $currentContent = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Extract main title (first # heading)
            if (empty($title) && preg_match('/^#\s+(.+)/', $line, $matches)) {
                $title = $matches[1];
                continue;
            }

            // Extract sections (## headings)
            if (preg_match('/^##\s+(.+)/', $line, $matches)) {
                // Save previous section
                if ($currentSection !== null) {
                    $sections[] = [
                        'title' => $currentSection,
                        'content' => $this->formatMarkdownContent(implode("\n", $currentContent))
                    ];
                }

                // Start new section
                $currentSection = $matches[1];
                $currentContent = [];
                continue;
            }

            // Add content to current section
            if ($currentSection !== null) {
                $currentContent[] = $line;
            }
        }

        // Save last section
        if ($currentSection !== null) {
            $sections[] = [
                'title' => $currentSection,
                'content' => $this->formatMarkdownContent(implode("\n", $currentContent))
            ];
        }

        // If no sections found, treat entire content as one section
        if (empty($sections)) {
            $sections[] = [
                'title' => $title ?: 'Hướng dẫn',
                'content' => $this->formatMarkdownContent($content)
            ];
        }

        return [
            'title' => $title ?: pathinfo($filename, PATHINFO_FILENAME),
            'content' => '', // Main content is in sections
            'sections' => $sections,
            'source_file' => $filename
        ];
    }

    /**
     * Format markdown content for HTML display
     */
    private function formatMarkdownContent($content)
    {
        // Basic markdown to HTML conversion
        $content = trim($content);

        // Convert headers
        $content = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $content);
        $content = preg_replace('/^#### (.+)$/m', '<h5>$1</h5>', $content);

        // Convert bold text
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);

        // Convert italic text
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);

        // Convert inline code
        $content = preg_replace('/`(.+?)`/', '<code>$1</code>', $content);

        // Convert code blocks
        $content = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code>$2</code></pre>', $content);

        // Convert links
        $content = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1</a>', $content);

        // Convert bullet points
        $content = preg_replace('/^[\-\*\+]\s+(.+)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);

        // Convert numbered lists
        $content = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $content);

        // Convert line breaks
        $content = preg_replace('/\n\n/', '</p><p>', $content);
        $content = '<p>' . $content . '</p>';

        // Clean up empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);

        return $content;
    }

    private function getOverviewContent()
    {
        return [
            'intro' => [
                'title' => 'Chào mừng đến với Audio Lara',
                'content' => 'Hệ thống quản lý truyện audio với đầy đủ tính năng từ crawl content, TTS conversion, đến video generation và social media publishing.'
            ],
            'features' => [
                'title' => 'Tính năng chính',
                'items' => [
                    '📚 Quản lý truyện và chapters với story visibility control',
                    '🎵 Text-to-Speech conversion với enhanced audio player',
                    '🎬 Universal Video Generator cho TikTok và YouTube',
                    '📱 Social media integration với OAuth authentication',
                    '⚡ Real-time queue management với bulk operations',
                    '🔄 Auto-next chapter functionality',
                    '📊 Comprehensive analytics và progress tracking'
                ]
            ],
            'navigation' => [
                'title' => 'Điều hướng nhanh',
                'items' => [
                    'Dashboard: Tổng quan hệ thống và thống kê',
                    'Truyện: Quản lý stories với visibility controls',
                    'Thể loại: Phân loại và tổ chức content',
                    'Quản lý Kênh: Kết nối social media platforms',
                    'Video Generator: Tạo video cho TikTok/YouTube',
                    'Trạng thái xử lý: Monitor queue và tasks'
                ]
            ]
        ];
    }

    private function getStoriesContent()
    {
        return [
            'create' => [
                'title' => 'Tạo truyện mới',
                'steps' => [
                    'Vào "Truyện" → "Thêm truyện mới"',
                    'Nhập tiêu đề (slug tự động tạo)',
                    'Chọn thể loại (có thể chọn nhiều)',
                    'Thêm tác giả và mô tả',
                    'Cấu hình visibility: Public/Private và Active/Inactive',
                    'Upload ảnh bìa (optional)',
                    'Lưu để tạo truyện'
                ]
            ],
            'visibility' => [
                'title' => 'Story Visibility Control',
                'content' => 'Hệ thống có 2 trường kiểm soát hiển thị:',
                'items' => [
                    '🌐 is_public: Truyện có hiển thị ở frontend không',
                    '⚡ is_active: Truyện có đang hoạt động không',
                    '✅ Visible: Chỉ stories vừa public vừa active mới hiển thị',
                    '🔍 Filter: Sử dụng filter buttons để lọc theo trạng thái'
                ]
            ],
            'management' => [
                'title' => 'Quản lý truyện',
                'items' => [
                    'Edit: Chỉnh sửa thông tin và visibility',
                    'Chapters: Xem và quản lý danh sách chương',
                    'Crawl: Thu thập chapters từ nguồn external',
                    'TTS: Chuyển đổi text thành audio hàng loạt',
                    'Scan: Quét và import chapters từ files',
                    'Video: Tạo video cho chapters'
                ]
            ]
        ];
    }

    private function getChaptersContent()
    {
        return [
            'bulk_actions' => [
                'title' => 'Bulk Actions - Thao tác hàng loạt',
                'content' => 'Chọn nhiều chapters để thực hiện các thao tác cùng lúc:',
                'steps' => [
                    '☑️ Select All: Click checkbox header để chọn tất cả',
                    '☑️ Individual: Click checkbox từng chapter',
                    '🎵 Bulk TTS: Chuyển đổi TTS cho nhiều chapters',
                    '🗑️ Bulk Delete: Xóa nhiều chapters (có double confirm)',
                    '❌ Clear: Bỏ chọn tất cả'
                ]
            ],
            'url_pattern' => [
                'title' => 'URL Pattern quan trọng',
                'content' => 'Hệ thống sử dụng slug-based routing:',
                'items' => [
                    '✅ Đúng: /admin/stories/{slug}/chapters',
                    '❌ Sai: /admin/stories/{id}/chapters',
                    'Ví dụ: /admin/stories/tien-nghich/chapters'
                ]
            ],
            'safety' => [
                'title' => 'An toàn khi sử dụng',
                'items' => [
                    '⚠️ TTS Bulk: An toàn, có thể retry nếu lỗi',
                    '🚨 Delete Bulk: NGUY HIỂM - xóa vĩnh viễn',
                    '🔒 Double Confirm: Xác nhận 2 lần cho delete',
                    '📊 Progress Tracking: Theo dõi kết quả chi tiết'
                ]
            ]
        ];
    }

    private function getAudioContent()
    {
        return [
            'enhanced_player' => [
                'title' => 'Enhanced Audio Player',
                'content' => 'Player chuyên nghiệp với nhiều tính năng:',
                'features' => [
                    '⚡ Speed Control: 0.5x → 2x (7 levels)',
                    '🔊 Volume Control: 0% → 100% với slider',
                    '⏯️ Play/Pause: Space bar hoặc click button',
                    '⏪⏩ Skip: 10 giây backward/forward',
                    '🔄 Auto-Next: Tự động chuyển chapter (optional)',
                    '💾 Settings Persistence: Lưu preferences'
                ]
            ],
            'auto_next' => [
                'title' => 'Auto-Next Chapter',
                'content' => 'Tính năng chuyển chapter tự động:',
                'steps' => [
                    '☑️ Enable: Check "Tự động next" trong player',
                    '⏰ Countdown: 5 giây đếm ngược khi audio kết thúc',
                    '❌ Cancel: Click "Hủy" để dừng auto-next',
                    '▶️ Manual: Click "Chuyển ngay" để skip countdown',
                    '💾 Remember: Hệ thống nhớ preference của user'
                ]
            ],
            'keyboard' => [
                'title' => 'Keyboard Shortcuts',
                'items' => [
                    'Space: Play/Pause audio',
                    '← →: Rewind/Forward 10 giây',
                    '↑ ↓: Volume up/down 10%',
                    'Smart Detection: Không conflict với form inputs'
                ]
            ]
        ];
    }

    private function getVideoContent()
    {
        return [
            'universal_generator' => [
                'title' => 'Universal Video Generator',
                'content' => 'Hệ thống tạo video thống nhất cho TikTok và YouTube:',
                'benefits' => [
                    '🎯 Single Interface: Một giao diện cho cả 2 platforms',
                    '📱 Platform Tabs: Chuyển đổi dễ dàng TikTok ↔ YouTube',
                    '⚙️ Shared Settings: Audio, subtitle, logo settings chung',
                    '🔄 Unified Processing: Cùng một engine xử lý',
                    '📊 64% Code Reduction: Giảm duplicate code'
                ]
            ],
            'tiktok_features' => [
                'title' => 'TikTok Video Features',
                'items' => [
                    '📝 Multiple Scripts: Nhập nhiều script cùng lúc',
                    '🎬 Product Videos: Upload video sản phẩm',
                    '🖼️ Product Images: Upload hình ảnh sản phẩm',
                    '🏷️ Logo Overlay: Thêm logo với position control',
                    '📱 Vertical Format: Tối ưu cho mobile viewing'
                ]
            ],
            'youtube_features' => [
                'title' => 'YouTube Video Features',
                'items' => [
                    '🎵 Audio Input: Text TTS hoặc upload MP3',
                    '🖼️ Multiple Images: Slideshow với transition effects',
                    '🎬 Background Video: Video nền với loop option',
                    '🔇 Audio Removal: Tự động remove audio từ video',
                    '📺 Horizontal Format: Tối ưu cho desktop viewing'
                ]
            ],
            'subtitle_system' => [
                'title' => 'Subtitle System',
                'content' => 'Hệ thống subtitle/text overlay:',
                'options' => [
                    '📍 Position: Top, Center, Bottom',
                    '📏 Size: Font size customizable',
                    '🎨 Color: Text và background color',
                    '⏱️ Duration: Thời gian hiển thị subtitle',
                    '🔤 Font: Font family selection'
                ]
            ]
        ];
    }

    private function getChannelsContent()
    {
        return [
            'setup' => [
                'title' => 'Thiết lập kênh',
                'steps' => [
                    'Vào "Quản lý Kênh" → "Thêm kênh mới"',
                    'Chọn platform (TikTok/YouTube)',
                    'Nhập thông tin kênh',
                    'Thực hiện OAuth authentication',
                    'Test connection để verify',
                    'Kích hoạt kênh'
                ]
            ],
            'oauth' => [
                'title' => 'OAuth Authentication',
                'content' => 'Quy trình xác thực an toàn:',
                'items' => [
                    '🔐 Secure Flow: OAuth 2.0 standard',
                    '🔄 Auto Refresh: Token tự động refresh',
                    '✅ Test Connection: Verify credentials',
                    '🔌 Disconnect: Revoke access an toàn',
                    '📊 Status Monitoring: Theo dõi connection status'
                ]
            ],
            'management' => [
                'title' => 'Quản lý kênh',
                'features' => [
                    'Toggle Status: Bật/tắt kênh nhanh chóng',
                    'Test Connection: Kiểm tra kết nối',
                    'Refresh Token: Làm mới authentication',
                    'View Analytics: Xem thống kê kênh',
                    'Scheduled Posts: Lên lịch đăng video'
                ]
            ]
        ];
    }

    private function getQueueContent()
    {
        return [
            'overview' => [
                'title' => 'Queue Management System',
                'content' => 'Hệ thống quản lý queue real-time với enhanced features:',
                'features' => [
                    '🔄 Auto-refresh: Cập nhật trạng thái tự động',
                    '⏸️ Smart Pausing: Tạm dừng khi user tương tác',
                    '🔔 Toast Notifications: Thông báo với sound',
                    '⌨️ Keyboard Shortcuts: Ctrl+R, F5, Escape',
                    '📱 Responsive: Tối ưu cho mobile'
                ]
            ],
            'operations' => [
                'title' => 'Thao tác với Queue',
                'items' => [
                    '▶️ Retry: Thử lại task thất bại',
                    '❌ Cancel: Hủy task đang chờ',
                    '🗑️ Delete: Xóa task khỏi queue',
                    '📊 View Details: Xem chi tiết task',
                    '🔄 Bulk Actions: Thao tác hàng loạt'
                ]
            ],
            'monitoring' => [
                'title' => 'Monitoring & Tracking',
                'content' => 'Theo dõi tiến trình xử lý:',
                'items' => [
                    '📈 Progress Bars: Hiển thị % hoàn thành',
                    '⏱️ Time Tracking: Thời gian xử lý',
                    '📊 Success Rate: Tỷ lệ thành công',
                    '🚨 Error Alerts: Cảnh báo lỗi real-time',
                    '📋 Detailed Logs: Log chi tiết cho debugging'
                ]
            ]
        ];
    }

    private function getTroubleshootingContent()
    {
        return [
            'common_issues' => [
                'title' => 'Các lỗi thường gặp',
                'items' => [
                    '🔧 Route [stories.store] not defined → Sử dụng admin.stories.store',
                    '🔗 URL không hoạt động → Check slug-based routing',
                    '☑️ Checkbox không select → Refresh page, check JavaScript',
                    '🎵 TTS thất bại → Check content và voice settings',
                    '🎬 Video generation lỗi → Check file permissions và storage'
                ]
            ],
            'debugging' => [
                'title' => 'Debug và khắc phục',
                'steps' => [
                    '🔍 Check Browser Console (F12) cho JavaScript errors',
                    '📋 Check Laravel Logs trong storage/logs/',
                    '🔄 Clear Cache: php artisan cache:clear',
                    '🗃️ Check Database connections',
                    '📁 Verify file permissions cho storage/',
                    '🌐 Test network connectivity'
                ]
            ],
            'performance' => [
                'title' => 'Tối ưu hiệu suất',
                'tips' => [
                    '📊 Bulk operations: Xử lý 10-20 items/lần',
                    '🔄 Queue processing: Monitor server resources',
                    '💾 Storage cleanup: Định kỳ dọn dẹp files cũ',
                    '📈 Database optimization: Index và query tuning',
                    '🚀 Caching: Sử dụng Redis cho performance'
                ]
            ],
            'support' => [
                'title' => 'Hỗ trợ kỹ thuật',
                'contact' => [
                    '📧 Email: admin@audiolara.com',
                    '💬 Chat: Support chat trong admin panel',
                    '📚 Documentation: Tài liệu chi tiết',
                    '🐛 Bug Report: GitHub issues',
                    '💡 Feature Request: Feedback form'
                ]
            ]
        ];
    }
}
