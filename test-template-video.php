<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request for template video generation
$request = Request::create('/admin/video-generator/generate-from-template', 'POST', [
    'template_id' => 21, // titktok_1_anh template
    'inputs' => [
        'titktok_1_sub' => 'Test content for video generation',
        'titktok_1_anh' => [], // Empty image input
        'titktok_1_video' => [] // Empty video input
    ],
    'background_audio_id' => 22
]);

// Simulate file upload for testing
$request->files->set('inputs', [
    'titktok_1_anh' => new \Illuminate\Http\UploadedFile(
        __DIR__ . '/public/test-image.jpg', // You need to create this test image
        'test-image.jpg',
        'image/jpeg',
        null,
        true
    )
]);

try {
    echo "Testing template video generation...\n";
    
    // Get the controller
    $controller = new \App\Http\Controllers\Admin\VideoGeneratorController();
    
    // Call the method
    $response = $controller->generateFromTemplate($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nCheck logs at storage/logs/laravel.log for detailed information.\n";
