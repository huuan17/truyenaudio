<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "Adding test tracking codes...\n";

// Test tracking codes
$testSettings = [
    'google_analytics_id' => [
        'value' => 'G-XXXXXXXXXX',
        'label' => 'Google Analytics ID (Test)'
    ],
    'google_tag_manager_id' => [
        'value' => 'GTM-XXXXXXX',
        'label' => 'Google Tag Manager ID (Test)'
    ],
    'google_search_console_verification' => [
        'value' => 'abcdef123456789',
        'label' => 'Google Search Console Verification (Test)'
    ],
    'facebook_pixel_id' => [
        'value' => '123456789012345',
        'label' => 'Facebook Pixel ID (Test)'
    ],
    'custom_head_code' => [
        'value' => '<!-- Custom Head Code Test -->
<meta name="test-meta" content="test-value">',
        'label' => 'Custom Head Code (Test)'
    ],
    'custom_body_code' => [
        'value' => '<!-- Custom Body Code Test -->
<script>
console.log("Custom body code loaded");
</script>',
        'label' => 'Custom Body Code (Test)'
    ],
];

foreach ($testSettings as $key => $data) {
    Setting::updateOrCreate(
        ['key' => $key],
        [
            'value' => $data['value'],
            'label' => $data['label'],
            'type' => 'code',
            'group' => 'tracking'
        ]
    );
    echo "Updated {$key}: {$data['value']}\n";
}

echo "\nTest tracking codes added successfully!\n";

// Test helper functions
echo "\n=== Testing Helper Functions ===\n";

use App\Helpers\SettingHelper;

echo "Head tracking codes:\n";
echo SettingHelper::getHeadTrackingCodes();

echo "\n\nBody tracking codes:\n";
echo SettingHelper::getBodyTrackingCodes();

echo "\n\nMeta verification tags:\n";
echo SettingHelper::getMetaVerificationTags();

echo "\n\nSEO tags:\n";
$seoTags = SettingHelper::getHomeSeoTags();
print_r($seoTags);

echo "\nDone!\n";
