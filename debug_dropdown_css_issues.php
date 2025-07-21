<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG DROPDOWN CSS ISSUES ===\n";

// Test 1: Check current CSS in layout
echo "1. 🎨 Current CSS Analysis:\n";
$layoutFile = resource_path('views/layouts/app.blade.php');
$layoutContent = file_get_contents($layoutFile);

// Check for z-index related CSS
if (strpos($layoutContent, 'z-index') !== false) {
    echo "  ✅ z-index CSS found in layout\n";
} else {
    echo "  ⚠️ No z-index CSS found in layout\n";
}

// Check for dropdown-menu CSS
if (strpos($layoutContent, 'dropdown-menu') !== false) {
    echo "  ✅ dropdown-menu CSS references found\n";
} else {
    echo "  ⚠️ No dropdown-menu CSS found\n";
}

// Check for position CSS
if (strpos($layoutContent, 'position') !== false) {
    echo "  ✅ Position CSS found\n";
} else {
    echo "  ⚠️ No position CSS found\n";
}

// Test 2: Extract existing custom CSS
echo "\n2. 📄 Existing Custom CSS:\n";
$customCssStart = strpos($layoutContent, '<style>');
$customCssEnd = strpos($layoutContent, '</style>');

if ($customCssStart !== false && $customCssEnd !== false) {
    $customCss = substr($layoutContent, $customCssStart + 7, $customCssEnd - $customCssStart - 7);
    echo "  ✅ Custom CSS found:\n";
    $cssLines = explode("\n", trim($customCss));
    foreach (array_slice($cssLines, 0, 10) as $line) {
        if (trim($line)) {
            echo "    " . trim($line) . "\n";
        }
    }
    if (count($cssLines) > 10) {
        echo "    ... (" . (count($cssLines) - 10) . " more lines)\n";
    }
} else {
    echo "  ⚠️ No custom CSS block found\n";
}

// Test 3: Generate CSS fixes
echo "\n3. 🔧 CSS Fixes for Dropdown:\n";
$dropdownFixCss = "
/* Dropdown Menu CSS Fixes */
.navbar-nav .dropdown-menu {
    position: absolute !important;
    z-index: 9999 !important;
    display: none;
    min-width: 200px;
    background-color: #fff !important;
    border: 1px solid rgba(0,0,0,.15) !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175) !important;
    margin-top: 0.125rem !important;
}

.navbar-nav .dropdown-menu.show {
    display: block !important;
}

.navbar-nav .dropdown-menu-right {
    right: 0 !important;
    left: auto !important;
}

.navbar-nav .dropdown-toggle::after {
    display: none !important; /* Hide default Bootstrap caret */
}

.navbar-nav .dropdown-item {
    display: block !important;
    width: 100% !important;
    padding: 0.375rem 1rem !important;
    clear: both !important;
    font-weight: 400 !important;
    color: #212529 !important;
    text-align: inherit !important;
    text-decoration: none !important;
    white-space: nowrap !important;
    background-color: transparent !important;
    border: 0 !important;
}

.navbar-nav .dropdown-item:hover,
.navbar-nav .dropdown-item:focus {
    color: #16181b !important;
    background-color: #f8f9fa !important;
}

.navbar-nav .dropdown-divider {
    height: 0 !important;
    margin: 0.5rem 0 !important;
    overflow: hidden !important;
    border-top: 1px solid #e9ecef !important;
}

.navbar-nav .dropdown-header {
    display: block !important;
    padding: 0.5rem 1rem !important;
    margin-bottom: 0 !important;
    font-size: 0.875rem !important;
    color: #6c757d !important;
    white-space: nowrap !important;
}

/* Force dropdown visibility for debugging */
.dropdown-debug .dropdown-menu {
    display: block !important;
    position: static !important;
    float: none !important;
    width: auto !important;
    margin-top: 0 !important;
    background-color: #fff !important;
    border: 1px solid #ccc !important;
    box-shadow: none !important;
}

/* AdminLTE specific fixes */
.main-header .navbar-nav .dropdown-menu {
    z-index: 10000 !important;
}

.main-header .navbar-nav .nav-item.dropdown {
    position: relative !important;
}
";

echo "  ✅ Generated CSS fixes for dropdown issues\n";

// Test 4: Create test HTML with CSS fixes
echo "\n4. 🧪 Create Test HTML with CSS Fixes:\n";
$testHtmlWithCss = '
<!DOCTYPE html>
<html>
<head>
    <title>Dropdown CSS Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
' . $dropdownFixCss . '
    
    /* Additional test styles */
    body { padding: 20px; }
    .test-navbar { 
        background: #343a40; 
        padding: 10px; 
        margin-bottom: 20px;
    }
    .debug-info {
        background: #f8f9fa;
        padding: 15px;
        margin: 10px 0;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }
    </style>
</head>
<body>
    <div class="test-navbar">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user"></i> Test User <i class="fas fa-caret-down"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <div class="dropdown-header">
                            <strong>Test User</strong><br>
                            <small class="text-muted">test@example.com</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user mr-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </div>
                </li>
            </div>
        </nav>
    </div>
    
    <div class="debug-info">
        <h5>Debug Controls:</h5>
        <button class="btn btn-primary" onclick="toggleDropdown()">Toggle Dropdown</button>
        <button class="btn btn-secondary" onclick="showDropdownInfo()">Show Dropdown Info</button>
        <button class="btn btn-warning" onclick="addDebugClass()">Add Debug Class</button>
    </div>
    
    <div id="debug-output" class="debug-info" style="display: none;">
        <h6>Debug Output:</h6>
        <pre id="debug-text"></pre>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            console.log("=== DROPDOWN CSS DEBUG ===");
            console.log("jQuery loaded:", typeof $ !== "undefined");
            console.log("Bootstrap loaded:", typeof $.fn.dropdown !== "undefined");
            
            // Initialize dropdown
            $(".dropdown-toggle").dropdown();
            
            // Manual click handler as fallback
            $(".dropdown-toggle").on("click", function(e) {
                e.preventDefault();
                console.log("Dropdown clicked");
                var $menu = $(this).next(".dropdown-menu");
                $menu.toggle();
            });
        });
        
        function toggleDropdown() {
            $(".dropdown-menu").toggle();
        }
        
        function showDropdownInfo() {
            var info = {
                "Dropdown toggle found": $(".dropdown-toggle").length,
                "Dropdown menu found": $(".dropdown-menu").length,
                "Menu is visible": $(".dropdown-menu").is(":visible"),
                "Menu computed display": $(".dropdown-menu").css("display"),
                "Menu z-index": $(".dropdown-menu").css("z-index"),
                "Menu position": $(".dropdown-menu").css("position"),
                "Bootstrap dropdown available": typeof $.fn.dropdown !== "undefined"
            };
            
            $("#debug-text").text(JSON.stringify(info, null, 2));
            $("#debug-output").show();
        }
        
        function addDebugClass() {
            $(".nav-item.dropdown").addClass("dropdown-debug");
            console.log("Added debug class");
        }
    </script>
</body>
</html>';

$testCssFile = public_path('dropdown-css-test.html');
file_put_contents($testCssFile, $testHtmlWithCss);
echo "  ✅ CSS test file created: http://localhost:8000/dropdown-css-test.html\n";

// Test 5: Check AdminLTE CSS conflicts
echo "\n5. 🔍 AdminLTE CSS Conflicts Check:\n";
$adminlteCssFile = public_path('assets/css/adminlte.min.css');
if (file_exists($adminlteCssFile)) {
    $adminlteContent = file_get_contents($adminlteCssFile);
    
    // Check for dropdown-menu styles
    if (strpos($adminlteContent, 'dropdown-menu') !== false) {
        echo "  ⚠️ AdminLTE has dropdown-menu styles (potential conflicts)\n";
    } else {
        echo "  ✅ No dropdown-menu conflicts in AdminLTE\n";
    }
    
    // Check for z-index styles
    $zIndexCount = substr_count($adminlteContent, 'z-index');
    echo "  📊 AdminLTE z-index declarations: {$zIndexCount}\n";
    
    echo "  📁 AdminLTE CSS size: " . round(filesize($adminlteCssFile) / 1024, 1) . "KB\n";
} else {
    echo "  ❌ AdminLTE CSS file not found\n";
}

echo "\n6. 💡 Recommended CSS Fixes:\n";
echo "  A. Add high z-index to dropdown-menu (9999+)\n";
echo "  B. Force position: absolute !important\n";
echo "  C. Add box-shadow for visibility\n";
echo "  D. Override AdminLTE conflicting styles\n";
echo "  E. Add debugging classes for testing\n";

echo "\n7. 🔧 Implementation Steps:\n";
echo "  1. Add CSS fixes to layout custom styles\n";
echo "  2. Test with CSS debug page\n";
echo "  3. Check browser inspector for conflicts\n";
echo "  4. Verify z-index stacking order\n";
echo "  5. Test dropdown functionality\n";

echo "\n📋 SUMMARY:\n";
echo "Custom CSS block: " . ($customCssStart !== false ? "✅ Found" : "❌ Missing") . "\n";
echo "AdminLTE CSS: " . (file_exists($adminlteCssFile) ? "✅ Available" : "❌ Missing") . "\n";
echo "CSS fixes generated: ✅ Ready to apply\n";
echo "Test page created: ✅ Available for testing\n";

echo "\n🎯 NEXT STEPS:\n";
echo "1. Apply CSS fixes to layout\n";
echo "2. Test with: http://localhost:8000/dropdown-css-test.html\n";
echo "3. Check browser inspector for z-index conflicts\n";
echo "4. Test dropdown in admin interface\n";

echo "\n✅ CSS debugging analysis completed!\n";

?>
