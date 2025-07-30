<?php

echo "=== Testing Workaround Logic ===\n\n";

// Simulate template form parameters (what we expect from template)
$templateParams = [
    'slide_duration' => 30,
    'duration_based_on' => null,  // Missing from template merge
    'custom_duration' => null,    // Missing from template merge
    'image_duration' => null      // Missing from template merge
];

echo "Template parameters (simulating missing duration settings):\n";
foreach ($templateParams as $key => $value) {
    echo "- {$key}: " . ($value ?? 'NULL') . "\n";
}
echo "\n";

// Apply workaround logic
$slideDuration = $templateParams['slide_duration'];
$durationBasedOn = $templateParams['duration_based_on'];
$customDuration = $templateParams['custom_duration'];
$imageDuration = $templateParams['image_duration'];

echo "Before workaround:\n";
echo "- slide_duration: " . ($slideDuration ?? 'NULL') . "\n";
echo "- duration_based_on: " . ($durationBasedOn ?? 'NULL') . "\n";
echo "- custom_duration: " . ($customDuration ?? 'NULL') . "\n";
echo "- image_duration: " . ($imageDuration ?? 'NULL') . "\n\n";

// ENHANCED WORKAROUND: Force custom duration for template-based generation
if ($slideDuration == 30 && !$durationBasedOn && !$customDuration) {
    echo "✅ WORKAROUND 1 TRIGGERED: Detected template with slide_duration=30\n";
    $durationBasedOn = 'custom';
    $customDuration = 30;
    $imageDuration = 30;
}

// ADDITIONAL FIX: If still no duration settings but slide_duration exists, use it
if (!$durationBasedOn && $slideDuration) {
    echo "✅ WORKAROUND 2 TRIGGERED: Using slide_duration as custom_duration\n";
    $durationBasedOn = 'custom';
    $customDuration = $slideDuration;
    $imageDuration = $slideDuration;
}

echo "\nAfter workaround:\n";
echo "- slide_duration: " . ($slideDuration ?? 'NULL') . "\n";
echo "- duration_based_on: " . ($durationBasedOn ?? 'NULL') . "\n";
echo "- custom_duration: " . ($customDuration ?? 'NULL') . "\n";
echo "- image_duration: " . ($imageDuration ?? 'NULL') . "\n\n";

// Check if parameters would be added to command
echo "Command parameters that would be added:\n";
if ($durationBasedOn) {
    echo "✅ --duration-based-on=" . $durationBasedOn . "\n";
} else {
    echo "❌ --duration-based-on MISSING\n";
}

if ($customDuration) {
    echo "✅ --custom-duration=" . $customDuration . "\n";
} else {
    echo "❌ --custom-duration MISSING\n";
}

if ($imageDuration) {
    echo "✅ --image-duration=" . $imageDuration . "\n";
} else {
    echo "❌ --image-duration MISSING\n";
}

if ($slideDuration) {
    echo "✅ --slide-duration=" . $slideDuration . "\n";
} else {
    echo "❌ --slide-duration MISSING\n";
}

echo "\n=== Expected Result: 30-second video ===\n";
?>
