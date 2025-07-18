<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
