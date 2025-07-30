<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Template Form Submission ===\n\n";

// Create test image file
$testImagePath = storage_path('app/temp/test-template-image.jpg');
if (!file_exists(dirname($testImagePath))) {
    mkdir(dirname($testImagePath), 0755, true);
}

// Create a simple test image
$image = imagecreate(800, 600);
$bg = imagecolorallocate($image, 100, 150, 200);
$text_color = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg);
imagestring($image, 5, 300, 280, 'TEMPLATE TEST', $text_color);
imagejpeg($image, $testImagePath);
imagedestroy($image);

echo "Test image created: {$testImagePath}\n";

// Create request data
$requestData = [
    'template_id' => 21,
    'inputs' => [
        'titktok_1_anh' => 'test-image-input',
        'titktok_1_sub' => 'Test subtitle content'
    ],
    'background_audio_id' => 29
];

echo "Request data:\n";
echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

// Create uploaded file
$uploadedFile = new \Illuminate\Http\UploadedFile(
    $testImagePath,
    'test-template-image.jpg',
    'image/jpeg',
    null,
    true
);

// Create request
$request = \Illuminate\Http\Request::create(
    '/admin/video-generator/generate-from-template',
    'POST',
    $requestData
);

// Add file to request
$request->files->set('inputs', [
    'titktok_1_anh' => $uploadedFile
]);

echo "Calling generateFromTemplate method...\n";

try {
    $controller = new \App\Http\Controllers\Admin\VideoGeneratorController();
    $response = $controller->generateFromTemplate($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . substr($response->getContent(), 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Check logs at storage/logs/laravel.log ===\n";
?>
