<?php

namespace App\Services;

class AssetManager
{
    protected static $css = [];
    protected static $js = [];
    protected static $inlineCSS = [];
    protected static $inlineJS = [];

    /**
     * Core CSS files (loaded first)
     */
    protected static $coreCss = [
        'bootstrap' => '/assets/css/bootstrap.min.css',
        'fontawesome' => '/assets/css/fontawesome-6.4.0-all.min.css',
        'admin' => '/assets/css/admin.css',
    ];

    /**
     * Core JS files (loaded first)
     */
    protected static $coreJs = [
        'jquery' => '/assets/js/jquery.min.js',
        'bootstrap' => '/assets/js/bootstrap.bundle.min.js',
        'admin' => '/assets/js/admin.js',
    ];

    /**
     * Add CSS file
     */
    public static function addCSS($name, $path, $priority = 10)
    {
        self::$css[$name] = [
            'path' => $path,
            'priority' => $priority,
        ];
    }

    /**
     * Add JS file
     */
    public static function addJS($name, $path, $priority = 10)
    {
        self::$js[$name] = [
            'path' => $path,
            'priority' => $priority,
        ];
    }

    /**
     * Add inline CSS
     */
    public static function addInlineCSS($css, $priority = 10)
    {
        self::$inlineCSS[] = [
            'content' => $css,
            'priority' => $priority,
        ];
    }

    /**
     * Add inline JS
     */
    public static function addInlineJS($js, $priority = 10)
    {
        self::$inlineJS[] = [
            'content' => $js,
            'priority' => $priority,
        ];
    }

    /**
     * Get all CSS files in correct order
     */
    public static function getCSS()
    {
        // Merge core CSS with additional CSS
        $allCss = [];
        
        // Add core CSS first (priority 0)
        foreach (self::$coreCss as $name => $path) {
            $allCss[$name] = [
                'path' => $path,
                'priority' => 0,
            ];
        }
        
        // Add additional CSS
        $allCss = array_merge($allCss, self::$css);
        
        // Sort by priority
        uasort($allCss, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $allCss;
    }

    /**
     * Get all JS files in correct order
     */
    public static function getJS()
    {
        // Merge core JS with additional JS
        $allJs = [];
        
        // Add core JS first (priority 0)
        foreach (self::$coreJs as $name => $path) {
            $allJs[$name] = [
                'path' => $path,
                'priority' => 0,
            ];
        }
        
        // Add additional JS
        $allJs = array_merge($allJs, self::$js);
        
        // Sort by priority
        uasort($allJs, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $allJs;
    }

    /**
     * Get inline CSS sorted by priority
     */
    public static function getInlineCSS()
    {
        usort(self::$inlineCSS, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return self::$inlineCSS;
    }

    /**
     * Get inline JS sorted by priority
     */
    public static function getInlineJS()
    {
        usort(self::$inlineJS, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return self::$inlineJS;
    }

    /**
     * Render CSS links
     */
    public static function renderCSS()
    {
        $output = '';
        
        foreach (self::getCSS() as $name => $asset) {
            $url = self::getAssetUrl($asset['path']);
            $output .= "<link rel=\"stylesheet\" href=\"{$url}\" data-asset=\"{$name}\">\n";
        }
        
        // Add inline CSS
        $inlineCSS = self::getInlineCSS();
        if (!empty($inlineCSS)) {
            $output .= "<style>\n";
            foreach ($inlineCSS as $css) {
                $output .= $css['content'] . "\n";
            }
            $output .= "</style>\n";
        }
        
        return $output;
    }

    /**
     * Render JS scripts
     */
    public static function renderJS()
    {
        $output = '';
        
        foreach (self::getJS() as $name => $asset) {
            $url = self::getAssetUrl($asset['path']);
            $output .= "<script src=\"{$url}\" data-asset=\"{$name}\"></script>\n";
        }
        
        // Add inline JS
        $inlineJS = self::getInlineJS();
        if (!empty($inlineJS)) {
            $output .= "<script>\n";
            foreach ($inlineJS as $js) {
                $output .= $js['content'] . "\n";
            }
            $output .= "</script>\n";
        }
        
        return $output;
    }

    /**
     * Get asset URL with versioning
     */
    protected static function getAssetUrl($path)
    {
        $url = asset($path);
        
        // Add version parameter for cache busting
        $filePath = public_path($path);
        if (file_exists($filePath)) {
            $timestamp = filemtime($filePath);
            $url .= '?v=' . $timestamp;
        }
        
        return $url;
    }

    /**
     * Add TinyMCE assets
     */
    public static function addTinyMCE()
    {
        self::addJS('tinymce', '/assets/js/tinymce.min.js', 5);
        
        self::addInlineJS('
            // TinyMCE Configuration
            window.tinyMCEConfig = {
                selector: ".tinymce-editor",
                height: 400,
                menubar: false,
                plugins: [
                    "advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table paste code help wordcount"
                ],
                toolbar: "undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help",
                content_style: "body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }"
            };
        ', 6);
    }

    /**
     * Add Chart.js assets
     */
    public static function addChartJS()
    {
        self::addJS('chartjs', '/assets/js/chart.min.js', 5);
    }

    /**
     * Add DataTables assets
     */
    public static function addDataTables()
    {
        self::addCSS('datatables', '/assets/css/datatables.min.css', 5);
        self::addJS('datatables', '/assets/js/datatables.min.js', 5);
    }

    /**
     * Add Select2 assets
     */
    public static function addSelect2()
    {
        self::addCSS('select2', '/assets/css/select2.min.css', 5);
        self::addJS('select2', '/assets/js/select2.min.js', 5);
    }

    /**
     * Add page-specific assets based on route
     */
    public static function addPageAssets()
    {
        $route = request()->route()->getName();
        
        switch (true) {
            case str_contains($route, 'video-generator'):
            case str_contains($route, 'video-templates'):
                self::addVideoGeneratorAssets();
                break;
                
            case str_contains($route, 'tts-monitor'):
                self::addTTSMonitorAssets();
                break;
                
            case str_contains($route, 'stories'):
            case str_contains($route, 'chapters'):
                self::addTinyMCE();
                break;
                
            case str_contains($route, 'dashboard'):
                self::addChartJS();
                break;
        }
    }

    /**
     * Add video generator specific assets
     */
    protected static function addVideoGeneratorAssets()
    {
        // Add video generator CSS
        self::addCSS('video-generator', '/assets/css/video-generator.css', 5);

        self::addInlineJS('
            // Video Generator specific JavaScript
            window.VideoGenerator = {
                init: function() {
                    this.initFormValidation();
                    this.initPreviewHandlers();
                    this.initPlatformTabs();
                },
                initFormValidation: function() {
                    // Form validation logic
                    const forms = document.querySelectorAll(".platform-form");
                    forms.forEach(form => {
                        form.addEventListener("submit", function(e) {
                            if (!form.checkValidity()) {
                                e.preventDefault();
                                e.stopPropagation();
                            }
                            form.classList.add("was-validated");
                        });
                    });
                },
                initPreviewHandlers: function() {
                    // Image preview handlers
                    const imageInputs = document.querySelectorAll("input[type=file][accept*=image]");
                    imageInputs.forEach(input => {
                        input.addEventListener("change", function() {
                            VideoGenerator.previewImages(this);
                        });
                    });
                },
                initPlatformTabs: function() {
                    // Platform tab switching
                    const tabs = document.querySelectorAll(".nav-tabs .nav-link");
                    tabs.forEach(tab => {
                        tab.addEventListener("click", function() {
                            VideoGenerator.switchPlatform(this.getAttribute("data-platform"));
                        });
                    });
                },
                previewImages: function(input) {
                    // Image preview logic
                    if (input.files && input.files.length > 0) {
                        const previewContainer = document.getElementById(input.id + "_preview");
                        if (previewContainer) {
                            previewContainer.innerHTML = "";
                            Array.from(input.files).forEach((file, index) => {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const img = document.createElement("img");
                                    img.src = e.target.result;
                                    img.className = "img-thumbnail me-2 mb-2";
                                    img.style.maxWidth = "100px";
                                    img.style.maxHeight = "100px";
                                    previewContainer.appendChild(img);
                                };
                                reader.readAsDataURL(file);
                            });
                        }
                    }
                },
                switchPlatform: function(platform) {
                    // Platform switching logic
                    const sections = document.querySelectorAll(".platform-section");
                    sections.forEach(section => {
                        section.style.display = section.id === platform + "-section" ? "block" : "none";
                    });
                }
            };

            $(document).ready(function() {
                VideoGenerator.init();
            });
        ', 15);
    }

    /**
     * Add TTS monitor specific assets
     */
    protected static function addTTSMonitorAssets()
    {
        self::addInlineCSS('
            .tts-progress { margin-bottom: 1rem; }
            .tts-status { font-weight: bold; }
            .queue-item { padding: 0.5rem; border-bottom: 1px solid #eee; }
        ');
        
        self::addInlineJS('
            // TTS Monitor auto-refresh
            setInterval(function() {
                if (window.location.pathname.includes("tts-monitor")) {
                    location.reload();
                }
            }, 30000); // Refresh every 30 seconds
        ', 15);
    }

    /**
     * Reset all assets (useful for testing)
     */
    public static function reset()
    {
        self::$css = [];
        self::$js = [];
        self::$inlineCSS = [];
        self::$inlineJS = [];
    }
}
