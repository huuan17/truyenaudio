<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get template
$template = \App\Models\VideoTemplate::find(21);

if ($template) {
    echo "Template: " . $template->name . "\n";
    echo "Settings:\n";
    echo json_encode($template->settings, JSON_PRETTY_PRINT) . "\n";
    
    // Check specific settings
    $settings = $template->settings;
    echo "\nKey Duration Settings:\n";
    echo "- duration_based_on: " . ($settings['duration_based_on'] ?? 'not set') . "\n";
    echo "- custom_duration: " . ($settings['custom_duration'] ?? 'not set') . "\n";
    echo "- image_duration: " . ($settings['image_duration'] ?? 'not set') . "\n";
    echo "- sync_with_audio: " . ($settings['sync_with_audio'] ?? 'not set') . "\n";
} else {
    echo "Template not found\n";
}
?>
