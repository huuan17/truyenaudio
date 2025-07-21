<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "=== Debug Form Action ===\n";

// Get a setting from appearance group
$setting = Setting::where('group', 'appearance')->first();
if (!$setting) {
    $setting = Setting::first();
}

if ($setting) {
    echo "Setting ID: {$setting->id}\n";
    echo "Setting Key: {$setting->key}\n";
    echo "Setting Group: {$setting->group}\n";
    
    // Test route generation
    try {
        $destroyRoute = route('admin.settings.destroy', $setting->id);
        echo "Destroy route: {$destroyRoute}\n";
        
        // Test with array parameter
        $destroyRouteArray = route('admin.settings.destroy', ['id' => $setting->id]);
        echo "Destroy route (array): {$destroyRouteArray}\n";
        
    } catch (Exception $e) {
        echo "Route error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No settings found\n";
}

// Test route with specific ID
echo "\n=== Test Route with ID 1 ===\n";
try {
    $testRoute = route('admin.settings.destroy', 1);
    echo "Test route: {$testRoute}\n";
} catch (Exception $e) {
    echo "Test route error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
