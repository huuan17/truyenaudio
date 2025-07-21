<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG USER DROPDOWN DISPLAY ISSUE ===\n";

// Test 1: Check layout file structure
echo "1. 📄 Layout File Analysis:\n";
$layoutFile = resource_path('views/layouts/app.blade.php');
$layoutContent = file_get_contents($layoutFile);

// Check for dropdown structure
if (strpos($layoutContent, 'dropdown') !== false) {
    echo "  ✅ Dropdown classes found in layout\n";
} else {
    echo "  ❌ No dropdown classes found\n";
}

// Check for Bootstrap/AdminLTE
if (strpos($layoutContent, 'bootstrap') !== false) {
    echo "  ✅ Bootstrap references found\n";
} else {
    echo "  ⚠️ No Bootstrap references found\n";
}

if (strpos($layoutContent, 'adminlte') !== false) {
    echo "  ✅ AdminLTE references found\n";
} else {
    echo "  ⚠️ No AdminLTE references found\n";
}

// Check for jQuery
if (strpos($layoutContent, 'jquery') !== false) {
    echo "  ✅ jQuery references found\n";
} else {
    echo "  ⚠️ No jQuery references found\n";
}

// Test 2: Extract dropdown HTML structure
echo "\n2. 🔍 Dropdown HTML Structure:\n";
$lines = explode("\n", $layoutContent);
$inDropdown = false;
$dropdownLines = [];

foreach ($lines as $lineNum => $line) {
    if (strpos($line, 'dropdown') !== false && strpos($line, 'nav-item') !== false) {
        $inDropdown = true;
    }
    
    if ($inDropdown) {
        $dropdownLines[] = ($lineNum + 1) . ": " . trim($line);
        
        if (strpos($line, '</li>') !== false && strpos($line, 'nav-item') === false) {
            $inDropdown = false;
            break;
        }
    }
}

if (!empty($dropdownLines)) {
    echo "  ✅ Dropdown structure found:\n";
    foreach (array_slice($dropdownLines, 0, 10) as $line) {
        echo "    {$line}\n";
    }
    if (count($dropdownLines) > 10) {
        echo "    ... (" . (count($dropdownLines) - 10) . " more lines)\n";
    }
} else {
    echo "  ❌ No dropdown structure found\n";
}

// Test 3: Check CSS/JS assets
echo "\n3. 🎨 Assets Analysis:\n";

// Check for CSS includes
$cssMatches = [];
preg_match_all('/<link[^>]*href=["\']([^"\']*\.css[^"\']*)["\'][^>]*>/', $layoutContent, $cssMatches);
if (!empty($cssMatches[1])) {
    echo "  ✅ CSS files found:\n";
    foreach ($cssMatches[1] as $css) {
        echo "    - {$css}\n";
    }
} else {
    echo "  ❌ No CSS files found\n";
}

// Check for JS includes
$jsMatches = [];
preg_match_all('/<script[^>]*src=["\']([^"\']*\.js[^"\']*)["\'][^>]*>/', $layoutContent, $jsMatches);
if (!empty($jsMatches[1])) {
    echo "  ✅ JavaScript files found:\n";
    foreach ($jsMatches[1] as $js) {
        echo "    - {$js}\n";
    }
} else {
    echo "  ❌ No JavaScript files found\n";
}

// Test 4: Check for common dropdown issues
echo "\n4. 🐛 Common Issues Check:\n";

// Check for data-toggle
if (strpos($layoutContent, 'data-toggle="dropdown"') !== false) {
    echo "  ✅ data-toggle=\"dropdown\" found\n";
} else {
    echo "  ❌ data-toggle=\"dropdown\" missing\n";
}

// Check for dropdown-toggle class
if (strpos($layoutContent, 'dropdown-toggle') !== false) {
    echo "  ✅ dropdown-toggle class found\n";
} else {
    echo "  ❌ dropdown-toggle class missing\n";
}

// Check for dropdown-menu class
if (strpos($layoutContent, 'dropdown-menu') !== false) {
    echo "  ✅ dropdown-menu class found\n";
} else {
    echo "  ❌ dropdown-menu class missing\n";
}

// Check for aria attributes
if (strpos($layoutContent, 'aria-haspopup') !== false) {
    echo "  ✅ aria-haspopup found\n";
} else {
    echo "  ⚠️ aria-haspopup missing (optional)\n";
}

// Test 5: Check authentication context
echo "\n5. 🔐 Authentication Context:\n";
if (strpos($layoutContent, '@auth') !== false || strpos($layoutContent, 'auth()->user()') !== false) {
    echo "  ✅ Authentication checks found\n";
} else {
    echo "  ❌ No authentication checks found\n";
}

if (strpos($layoutContent, 'auth()->user()->name') !== false) {
    echo "  ✅ User name display found\n";
} else {
    echo "  ⚠️ User name display not found\n";
}

// Test 6: Generate test HTML
echo "\n6. 🧪 Generate Test HTML:\n";
$testHtml = '
<!DOCTYPE html>
<html>
<head>
    <title>Dropdown Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user"></i> Test User
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="/logout" method="POST" style="display: inline;">
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </div>
    </nav>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            console.log("jQuery loaded:", typeof $ !== "undefined");
            console.log("Bootstrap loaded:", typeof $.fn.dropdown !== "undefined");
            
            // Test dropdown functionality
            $(".dropdown-toggle").on("click", function(e) {
                e.preventDefault();
                console.log("Dropdown clicked");
                $(this).next(".dropdown-menu").toggle();
            });
        });
    </script>
</body>
</html>';

$testFile = public_path('dropdown-test.html');
file_put_contents($testFile, $testHtml);
echo "  ✅ Test HTML created: http://localhost:8000/dropdown-test.html\n";

// Test 7: Recommendations
echo "\n7. 💡 Recommendations:\n";
echo "  A. Check browser console for JavaScript errors\n";
echo "  B. Verify Bootstrap/AdminLTE CSS and JS are loading\n";
echo "  C. Test dropdown with simple HTML (see test file above)\n";
echo "  D. Check if user is authenticated when viewing page\n";
echo "  E. Verify CSS z-index and positioning\n";

echo "\n8. 🔧 Quick Fixes to Try:\n";
echo "  1. Hard refresh browser (Ctrl+F5)\n";
echo "  2. Clear browser cache\n";
echo "  3. Check browser console for errors\n";
echo "  4. Test in incognito mode\n";
echo "  5. Verify user is logged in\n";

echo "\n9. 🌐 Testing URLs:\n";
echo "  Login: http://localhost:8000/login\n";
echo "  Admin: http://localhost:8000/admin/dashboard\n";
echo "  Test: http://localhost:8000/dropdown-test.html\n";

echo "\n📋 SUMMARY:\n";
echo "Layout file: " . (file_exists($layoutFile) ? "✅ Exists" : "❌ Missing") . "\n";
echo "Dropdown HTML: " . (!empty($dropdownLines) ? "✅ Found" : "❌ Missing") . "\n";
echo "CSS assets: " . (!empty($cssMatches[1]) ? "✅ Found" : "❌ Missing") . "\n";
echo "JS assets: " . (!empty($jsMatches[1]) ? "✅ Found" : "❌ Missing") . "\n";
echo "Bootstrap classes: " . (strpos($layoutContent, 'dropdown-toggle') !== false ? "✅ Found" : "❌ Missing") . "\n";

if (!empty($dropdownLines) && !empty($cssMatches[1]) && !empty($jsMatches[1])) {
    echo "\n✅ DIAGNOSIS: Layout structure looks correct\n";
    echo "💡 LIKELY CAUSES:\n";
    echo "  - JavaScript not loading properly\n";
    echo "  - CSS conflicts or missing styles\n";
    echo "  - User not authenticated\n";
    echo "  - Browser cache issues\n";
} else {
    echo "\n❌ DIAGNOSIS: Layout structure issues detected\n";
    echo "💡 FIXES NEEDED:\n";
    echo "  - Add missing dropdown HTML structure\n";
    echo "  - Include Bootstrap CSS/JS\n";
    echo "  - Add proper dropdown classes\n";
}

echo "\n✅ Dropdown display debugging completed!\n";

?>
