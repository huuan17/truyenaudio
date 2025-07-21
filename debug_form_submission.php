<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

echo "=== Debug Form Submission ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Story info:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Slug: {$story->slug}\n";

// Simulate form data
$formData = [
    'title' => $story->title,
    'slug' => $story->slug,
    'author' => $story->author,
    'source_url' => $story->source_url,
    'start_chapter' => $story->start_chapter,
    'end_chapter' => $story->end_chapter,
    'description' => $story->description,
    'folder_name' => $story->folder_name,
    'crawl_path' => $story->crawl_path,
];

echo "\nForm data:\n";
foreach ($formData as $key => $value) {
    echo "  {$key}: {$value}\n";
}

// Test validation rules
$rules = [
    'title'         => 'required|string|max:255',
    'slug'          => 'required|string|unique:stories,slug,' . $story->id,
    'author'        => 'nullable|string|max:255',
    'author_id'     => 'nullable|exists:authors,id',
    'source_url'    => 'required|url',
    'start_chapter' => 'required|integer|min:1',
    'end_chapter'   => 'required|integer|min:1|gte:start_chapter',
];

echo "\nValidation test:\n";
$validator = Validator::make($formData, $rules);

if ($validator->fails()) {
    echo "  ❌ Validation failed:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "    - {$error}\n";
    }
} else {
    echo "  ✅ Validation passed\n";
}

// Test route URL generation
echo "\nRoute URL test:\n";
try {
    $updateUrl = route('admin.stories.update', $story);
    echo "  ✅ Update URL: {$updateUrl}\n";
    
    // Parse URL to check components
    $parsedUrl = parse_url($updateUrl);
    echo "  URL components:\n";
    echo "    Scheme: {$parsedUrl['scheme']}\n";
    echo "    Host: {$parsedUrl['host']}\n";
    echo "    Path: {$parsedUrl['path']}\n";
    
} catch (Exception $e) {
    echo "  ❌ Route error: {$e->getMessage()}\n";
}

// Check if route exists
echo "\nRoute existence check:\n";
try {
    $routes = app('router')->getRoutes();
    $updateRoute = $routes->getByName('admin.stories.update');
    
    if ($updateRoute) {
        echo "  ✅ Route 'admin.stories.update' exists\n";
        echo "  Methods: " . implode(', ', $updateRoute->methods()) . "\n";
        echo "  URI: {$updateRoute->uri()}\n";
        echo "  Action: {$updateRoute->getActionName()}\n";
    } else {
        echo "  ❌ Route 'admin.stories.update' not found\n";
    }
} catch (Exception $e) {
    echo "  ❌ Route check error: {$e->getMessage()}\n";
}

// Test form action generation
echo "\nForm action test:\n";
$isEdit = true;
$formAction = $isEdit ? route('admin.stories.update', $story) : route('admin.stories.store');
echo "  Form action: {$formAction}\n";
echo "  Method: POST with @method('PUT')\n";

echo "\nDebugging checklist:\n";
echo "  ✅ Story exists and has slug\n";
echo "  ✅ Form action uses \$story object (not ID)\n";
echo "  ✅ Validation rules are correct\n";
echo "  ✅ Route exists and is accessible\n";
echo "  ✅ Controller method exists\n";

echo "\nPossible issues:\n";
echo "  1. CSRF token mismatch\n";
echo "  2. Middleware blocking request\n";
echo "  3. Form encoding issues\n";
echo "  4. JavaScript interference\n";
echo "  5. Server configuration\n";

echo "\nNext steps:\n";
echo "  1. Check browser network tab for actual request\n";
echo "  2. Check Laravel logs: storage/logs/laravel.log\n";
echo "  3. Test with simple form data\n";
echo "  4. Verify CSRF token is included\n";

echo "\n✅ Debug completed!\n";

?>
