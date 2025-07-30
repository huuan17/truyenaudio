<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Debug Template Duration Settings ===\n\n";

// Get template
$template = \App\Models\VideoTemplate::find(21);

if (!$template) {
    echo "Template not found!\n";
    exit;
}

echo "Template: {$template->name}\n";
echo "Template Settings:\n";
$settings = $template->settings;
echo json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";

// Check specific duration settings
echo "Duration Settings:\n";
echo "- duration_based_on: " . ($settings['duration_based_on'] ?? 'NOT SET') . "\n";
echo "- custom_duration: " . ($settings['custom_duration'] ?? 'NOT SET') . "\n";
echo "- image_duration: " . ($settings['image_duration'] ?? 'NOT SET') . "\n";
echo "- sync_with_audio: " . ($settings['sync_with_audio'] ?? 'NOT SET') . "\n\n";

// Simulate the merge process
$data = $settings; // Start with template settings

$defaults = [
    'tts_speed' => 1.0,
    'tts_volume' => 18,
    'audio_volume' => 18,
    'logo_opacity' => 1.0,
    'logo_margin' => 20,
    'duration_based_on' => 'images', // Default only if not set in template
    'custom_duration' => 30, // Default only if not set in template
    'video_title' => $template->generateVideoName(),
    'platform' => 'none',
    'audio_source' => 'none',
];

echo "Defaults:\n";
echo json_encode($defaults, JSON_PRETTY_PRINT) . "\n\n";

// Merge defaults first, then template settings (template settings take priority)
$mergedData = array_merge($defaults, $data);

echo "After merge (template should override defaults):\n";
echo "- duration_based_on: " . ($mergedData['duration_based_on'] ?? 'NOT SET') . "\n";
echo "- custom_duration: " . ($mergedData['custom_duration'] ?? 'NOT SET') . "\n";
echo "- image_duration: " . ($mergedData['image_duration'] ?? 'NOT SET') . "\n";
echo "- sync_with_audio: " . ($mergedData['sync_with_audio'] ?? 'NOT SET') . "\n\n";

// Test Request creation
$request = new \Illuminate\Http\Request();
$request->merge($mergedData);

echo "Request input values:\n";
echo "- duration_based_on: " . ($request->input('duration_based_on') ?? 'NOT SET') . "\n";
echo "- custom_duration: " . ($request->input('custom_duration') ?? 'NOT SET') . "\n";
echo "- image_duration: " . ($request->input('image_duration') ?? 'NOT SET') . "\n";
echo "- sync_with_audio: " . ($request->input('sync_with_audio') ?? 'NOT SET') . "\n\n";

echo "=== Test Complete ===\n";
?>
