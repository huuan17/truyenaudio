<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "Creating test setting for deletion...\n";

$testSetting = Setting::create([
    'key' => 'test_delete_setting',
    'value' => 'This is a test setting for deletion',
    'type' => 'text',
    'group' => 'general',
    'label' => 'Test Delete Setting',
    'description' => 'This setting is created for testing delete functionality',
    'sort_order' => 999,
    'is_active' => true,
]);

echo "Test setting created with ID: {$testSetting->id}\n";
echo "Key: {$testSetting->key}\n";
echo "Label: {$testSetting->label}\n";
echo "\nYou can now test deleting this setting from the admin panel.\n";
echo "URL: http://localhost:8000/admin/settings?group=general\n";

echo "\nDone!\n";
