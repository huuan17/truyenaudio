<?php

// Simple debug script to test template merge logic
echo "=== Debug Template Merge Logic ===\n\n";

// Simulate template settings
$templateSettings = [
    "platform" => "tiktok",
    "media_type" => "mixed",
    "duration_based_on" => "custom",
    "custom_duration" => 30,
    "image_duration" => 30,
    "slide_duration" => 30,
    "audio_source" => "none"
];

echo "Template settings:\n";
echo json_encode($templateSettings, JSON_PRETTY_PRINT) . "\n\n";

// Simulate user inputs
$userInputs = [
    'template_id' => 21,
    'inputs' => [
        'titktok_1_sub' => 'Test content'
    ],
    'background_audio_id' => 22
];

echo "User inputs:\n";
echo json_encode($userInputs, JSON_PRETTY_PRINT) . "\n\n";

// Simulate merge process (like in VideoGeneratorController)
$data = $templateSettings; // Start with template settings

$defaults = [
    'tts_speed' => 1.0,
    'tts_volume' => 18,
    'audio_volume' => 18,
    'logo_opacity' => 1.0,
    'logo_margin' => 20,
    'duration_based_on' => 'images', // Default only if not set in template
    'custom_duration' => 30, // Default only if not set in template
    'video_title' => 'Generated Video',
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
echo "- slide_duration: " . ($mergedData['slide_duration'] ?? 'NOT SET') . "\n";
echo "- platform: " . ($mergedData['platform'] ?? 'NOT SET') . "\n\n";

// Simulate request input() calls
echo "Simulated request->input() calls:\n";
echo "- input('duration_based_on'): " . ($mergedData['duration_based_on'] ?? 'NULL') . "\n";
echo "- input('custom_duration'): " . ($mergedData['custom_duration'] ?? 'NULL') . "\n";
echo "- input('image_duration'): " . ($mergedData['image_duration'] ?? 'NULL') . "\n";
echo "- input('slide_duration'): " . ($mergedData['slide_duration'] ?? 'NULL') . "\n\n";

// Check if values would be added to command parameters
echo "Command parameters that would be added:\n";
if ($mergedData['duration_based_on'] ?? false) {
    echo "✅ --duration-based-on=" . $mergedData['duration_based_on'] . "\n";
} else {
    echo "❌ --duration-based-on MISSING\n";
}

if ($mergedData['custom_duration'] ?? false) {
    echo "✅ --custom-duration=" . $mergedData['custom_duration'] . "\n";
} else {
    echo "❌ --custom-duration MISSING\n";
}

if ($mergedData['image_duration'] ?? false) {
    echo "✅ --image-duration=" . $mergedData['image_duration'] . "\n";
} else {
    echo "❌ --image-duration MISSING\n";
}

if ($mergedData['slide_duration'] ?? false) {
    echo "✅ --slide-duration=" . $mergedData['slide_duration'] . "\n";
} else {
    echo "❌ --slide-duration MISSING\n";
}

echo "\n=== Debug Complete ===\n";
?>
