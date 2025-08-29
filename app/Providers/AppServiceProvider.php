<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;
use App\Helpers\AdminHelper;
use App\Services\AssetManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register video preview services
        $this->app->singleton(\App\Services\VideoPreviewService::class);
        $this->app->singleton(\App\Services\DemoMediaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureUrls();
        $this->registerBladeDirectives();
        $this->configurePagination();
    }

    /**
     * Configure URLs based on environment
     */
    private function configureUrls(): void
    {
        $env = config('app.env');
        $envConfig = config("url.environments.{$env}");

        if ($envConfig) {
            // Force HTTPS if configured for this environment
            if ($envConfig['force_https'] ?? false) {
                URL::forceScheme('https');
            }

            // Set root URL
            if ($envConfig['url']) {
                URL::forceRootUrl($envConfig['url']);
            }
        } else {
            // Fallback to APP_URL
            if (config('app.url')) {
                URL::forceRootUrl(config('app.url'));
            }

            // Force HTTPS in production as fallback
            if ($env === 'production') {
                URL::forceScheme('https');
            }
        }
    }

    /**
     * Register custom Blade directives
     */
    private function registerBladeDirectives(): void
    {
        // @appUrl directive for consistent URL generation
        Blade::directive('appUrl', function ($expression) {
            return "<?php echo config('app.url') . ($expression ? '/' . ltrim($expression, '/') : ''); ?>";
        });

        // @assetUrl directive for asset URLs
        Blade::directive('assetUrl', function ($expression) {
            return "<?php echo asset($expression); ?>";
        });

        // Toast directives
        Blade::directive('toastSuccess', function ($expression) {
            return "<?php session()->flash('success', $expression); ?>";
        });

        Blade::directive('toastError', function ($expression) {
            return "<?php session()->flash('error', $expression); ?>";
        });

        Blade::directive('toastWarning', function ($expression) {
            return "<?php session()->flash('warning', $expression); ?>";
        });

        Blade::directive('toastInfo', function ($expression) {
            return "<?php session()->flash('info', $expression); ?>";
        });

        // JavaScript toast directive
        Blade::directive('jsToast', function ($expression) {
            $parts = explode(',', str_replace(['(', ')'], '', $expression));
            $type = trim($parts[0], '\'"');
            $message = trim($parts[1] ?? '""', '\'"');
            $title = trim($parts[2] ?? '""', '\'"');

            return "
            <script>
                $(document).ready(function() {
                    showToast.{$type}('{$message}'" . ($title ? ", '{$title}'" : '') . ");
                });
            </script>";
        });

        // Admin helper directives
        Blade::directive('adminConfig', function ($expression) {
            return "<?php echo AdminHelper::config($expression); ?>";
        });

        Blade::directive('ttsVoices', function () {
            return "<?php echo json_encode(AdminHelper::getTTSVoices()); ?>";
        });

        Blade::directive('videoResolutions', function () {
            return "<?php echo json_encode(AdminHelper::getVideoResolutions()); ?>";
        });

        // Asset management directives
        Blade::directive('renderCSS', function () {
            return "<?php echo AssetManager::renderCSS(); ?>";
        });

        Blade::directive('renderJS', function () {
            return "<?php echo AssetManager::renderJS(); ?>";
        });

        Blade::directive('addCSS', function ($expression) {
            $parts = explode(',', str_replace(['(', ')'], '', $expression));
            $name = trim($parts[0], '\'"');
            $path = trim($parts[1], '\'"');
            $priority = isset($parts[2]) ? (int)trim($parts[2]) : 10;
            return "<?php AssetManager::addCSS('{$name}', '{$path}', {$priority}); ?>";
        });

        Blade::directive('addJS', function ($expression) {
            $parts = explode(',', str_replace(['(', ')'], '', $expression));
            $name = trim($parts[0], '\'"');
            $path = trim($parts[1], '\'"');
            $priority = isset($parts[2]) ? (int)trim($parts[2]) : 10;
            return "<?php AssetManager::addJS('{$name}', '{$path}', {$priority}); ?>";
        });
    }

    /**
     * Configure pagination views
     */
    private function configurePagination(): void
    {
        // Set default pagination view for AdminLTE
        Paginator::defaultView('vendor.pagination.adminlte');
        Paginator::defaultSimpleView('vendor.pagination.simple-adminlte');
    }
}
