<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "Creating test setting for appearance group...\n";

$testSetting = Setting::create([
    'key' => 'test_appearance_setting_' . time(),
    'value' => 'This is a test appearance setting for deletion',
    'type' => 'text',
    'group' => 'appearance',
    'label' => 'Test Appearance Setting',
    'description' => 'This setting is created for testing delete functionality in appearance group',
    'sort_order' => 999,
    'is_active' => true,
]);

echo "Test appearance setting created with ID: {$testSetting->id}\n";
echo "Key: {$testSetting->key}\n";
echo "Label: {$testSetting->label}\n";
echo "Group: {$testSetting->group}\n";
echo "\nYou can now test deleting this setting from the admin panel.\n";
echo "URL: http://localhost:8000/admin/settings?group=appearance\n";

echo "\nDone!\n";
