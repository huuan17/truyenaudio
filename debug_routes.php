<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "=== Debug Routes ===\n";

// Test setting
$setting = Setting::first();
if ($setting) {
    echo "Setting ID: {$setting->id}\n";
    echo "Setting Key: {$setting->key}\n";
    
    // Test route generation
    try {
        $destroyRoute = route('admin.settings.destroy', $setting->id);
        echo "Destroy route: {$destroyRoute}\n";
        
        $editRoute = route('admin.settings.edit', $setting->id);
        echo "Edit route: {$editRoute}\n";
        
    } catch (Exception $e) {
        echo "Route error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No settings found\n";
}

// List all settings routes
echo "\n=== All Settings Routes ===\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
foreach ($routes as $route) {
    if (str_contains($route->getName() ?? '', 'settings')) {
        echo sprintf("%-8s %-30s %s\n", 
            implode('|', $route->methods()), 
            $route->uri(), 
            $route->getName()
        );
    }
}

echo "\nDone!\n";
