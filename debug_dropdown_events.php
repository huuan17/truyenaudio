<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG DROPDOWN EVENTS ===\n";

// Test 1: Create enhanced debug script for admin page
echo "1. 🔧 Enhanced Debug Script for Admin Page:\n";
$adminDebugScript = "
// Enhanced Dropdown Debug for Admin Page
window.enhancedDropdownDebug = function() {
    console.log('=== ENHANCED DROPDOWN DEBUG ===');
    
    // Check jQuery and Bootstrap
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
    console.log('Bootstrap dropdown available:', typeof $.fn.dropdown !== 'undefined');
    
    // Find all dropdown elements
    var dropdownToggles = $('.dropdown-toggle');
    console.log('Dropdown toggles found:', dropdownToggles.length);
    
    dropdownToggles.each(function(i) {
        var \$toggle = $(this);
        var \$menu = \$toggle.next('.dropdown-menu');
        
        console.log('Dropdown ' + (i+1) + ':', {
            'Toggle element': \$toggle[0],
            'Menu element': \$menu[0],
            'Toggle classes': \$toggle.attr('class'),
            'Menu classes': \$menu.attr('class'),
            'Toggle data-toggle': \$toggle.attr('data-toggle'),
            'Toggle aria-expanded': \$toggle.attr('aria-expanded'),
            'Menu computed styles': {
                'display': \$menu.css('display'),
                'position': \$menu.css('position'),
                'z-index': \$menu.css('z-index'),
                'top': \$menu.css('top'),
                'right': \$menu.css('right'),
                'visibility': \$menu.css('visibility'),
                'opacity': \$menu.css('opacity')
            },
            'Event handlers': \$._data(\$toggle[0], 'events')
        });
    });
    
    // Test manual click
    console.log('Testing manual click...');
    if (dropdownToggles.length > 0) {
        var \$firstToggle = dropdownToggles.first();
        var \$firstMenu = \$firstToggle.next('.dropdown-menu');
        
        console.log('Before manual click:', {
            'Menu visible': \$firstMenu.is(':visible'),
            'Menu display': \$firstMenu.css('display'),
            'Has show class': \$firstMenu.hasClass('show')
        });
        
        // Force show
        \$firstMenu.addClass('show').show();
        \$firstToggle.attr('aria-expanded', 'true');
        
        console.log('After force show:', {
            'Menu visible': \$firstMenu.is(':visible'),
            'Menu display': \$firstMenu.css('display'),
            'Has show class': \$firstMenu.hasClass('show')
        });
    }
};

// Test click event binding
window.testDropdownClick = function() {
    console.log('=== TESTING CLICK EVENTS ===');
    
    $('.dropdown-toggle').off('click.test').on('click.test', function(e) {
        console.log('TEST CLICK EVENT FIRED');
        e.preventDefault();
        e.stopPropagation();
        
        var \$toggle = $(this);
        var \$menu = \$toggle.next('.dropdown-menu');
        
        console.log('Click event details:', {
            'Event type': e.type,
            'Target': e.target,
            'Current target': e.currentTarget,
            'Toggle element': \$toggle[0],
            'Menu element': \$menu[0]
        });
        
        // Force toggle
        if (\$menu.is(':visible')) {
            \$menu.removeClass('show').hide();
            \$toggle.attr('aria-expanded', 'false');
            console.log('Menu hidden');
        } else {
            \$menu.addClass('show').show();
            \$toggle.attr('aria-expanded', 'true');
            console.log('Menu shown');
        }
    });
    
    console.log('Test click event bound to', $('.dropdown-toggle').length, 'elements');
};

// Fix dropdown functionality
window.fixDropdown = function() {
    console.log('=== FIXING DROPDOWN ===');
    
    // Remove all existing event handlers
    $('.dropdown-toggle').off('click');
    
    // Add new working click handler
    $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('FIXED CLICK HANDLER');
        
        var \$toggle = $(this);
        var \$menu = \$toggle.next('.dropdown-menu');
        
        // Hide all other dropdowns
        $('.dropdown-menu').not(\$menu).removeClass('show').hide();
        $('.dropdown-toggle').not(\$toggle).attr('aria-expanded', 'false');
        
        // Toggle current dropdown
        if (\$menu.hasClass('show') || \$menu.is(':visible')) {
            \$menu.removeClass('show').hide();
            \$toggle.attr('aria-expanded', 'false');
            console.log('Dropdown hidden');
        } else {
            \$menu.addClass('show').show();
            \$toggle.attr('aria-expanded', 'true');
            console.log('Dropdown shown');
        }
    });
    
    // Add outside click handler
    $(document).off('click.dropdown').on('click.dropdown', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show').hide();
            $('.dropdown-toggle').attr('aria-expanded', 'false');
        }
    });
    
    console.log('Dropdown functionality fixed!');
};

// Auto-run functions
enhancedDropdownDebug();
testDropdownClick();

console.log('=== AVAILABLE FUNCTIONS ===');
console.log('enhancedDropdownDebug() - Detailed debug info');
console.log('testDropdownClick() - Test click events');
console.log('fixDropdown() - Fix dropdown functionality');
";

$debugFile = public_path('admin-dropdown-debug.js');
file_put_contents($debugFile, $adminDebugScript);
echo "  ✅ Enhanced debug script: http://localhost:8000/admin-dropdown-debug.js\n";

// Test 2: Create working dropdown test page
echo "\n2. 🧪 Working Dropdown Test Page:\n";
$workingTestHtml = '
<!DOCTYPE html>
<html>
<head>
    <title>Working Dropdown Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-section { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-test { 
            background: #343a40; 
            padding: 10px 20px; 
            border-radius: 8px;
        }
        
        /* Working dropdown CSS */
        .navbar-nav .dropdown-menu {
            position: absolute !important;
            z-index: 9999 !important;
            display: none !important;
            min-width: 200px !important;
            background-color: #fff !important;
            border: 1px solid rgba(0,0,0,.15) !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175) !important;
            margin-top: 0.125rem !important;
            top: 100% !important;
            left: auto !important;
        }
        
        .navbar-nav .dropdown-menu.show {
            display: block !important;
        }
        
        .navbar-nav .dropdown-menu-right {
            right: 0 !important;
            left: auto !important;
        }
        
        .navbar-nav .dropdown-item {
            display: block !important;
            width: 100% !important;
            padding: 0.375rem 1rem !important;
            color: #212529 !important;
            text-decoration: none !important;
            background-color: transparent !important;
            border: 0 !important;
            cursor: pointer !important;
        }
        
        .navbar-nav .dropdown-item:hover {
            color: #16181b !important;
            background-color: #f8f9fa !important;
        }
        
        .status { 
            padding: 5px 10px; 
            margin: 5px 0; 
            border-radius: 4px; 
            font-family: monospace; 
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="test-section">
        <h3>Working Dropdown Test</h3>
        <div class="navbar-test">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="navbar-nav ml-auto">
                    <li class="nav-item dropdown" id="working-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" 
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i> Working User <i class="fas fa-caret-down"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header">
                                <strong>Working User</strong><br>
                                <small class="text-muted">working@example.com</small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="alert(\'Profile clicked\')">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item text-danger" onclick="alert(\'Logout clicked\')">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </div>
                    </li>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="test-section">
        <h5>Test Controls</h5>
        <button class="btn btn-primary" onclick="testClick()">Test Click</button>
        <button class="btn btn-secondary" onclick="forceShow()">Force Show</button>
        <button class="btn btn-warning" onclick="forceHide()">Force Hide</button>
        <button class="btn btn-info" onclick="debugEvents()">Debug Events</button>
        <button class="btn btn-success" onclick="copyAdminCode()">Copy Admin Fix</button>
    </div>
    
    <div id="status-log" class="test-section">
        <h6>Status Log:</h6>
        <div id="log-content"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function log(message, type = "info") {
            var timestamp = new Date().toLocaleTimeString();
            var logEntry = `<div class="status ${type}">[${timestamp}] ${message}</div>`;
            $("#log-content").prepend(logEntry);
            console.log(`[${timestamp}] ${message}`);
        }
        
        $(document).ready(function() {
            log("Page loaded, initializing dropdown...", "info");
            
            // Remove any existing handlers
            $(".dropdown-toggle").off("click");
            
            // Add working click handler
            $(".dropdown-toggle").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                log("Dropdown clicked!", "success");
                
                var $toggle = $(this);
                var $menu = $toggle.next(".dropdown-menu");
                
                // Hide other dropdowns
                $(".dropdown-menu").not($menu).removeClass("show").hide();
                
                // Toggle current dropdown
                if ($menu.hasClass("show") || $menu.is(":visible")) {
                    $menu.removeClass("show").hide();
                    $toggle.attr("aria-expanded", "false");
                    log("Dropdown hidden", "info");
                } else {
                    $menu.addClass("show").show();
                    $toggle.attr("aria-expanded", "true");
                    log("Dropdown shown", "success");
                }
            });
            
            // Outside click to close
            $(document).on("click", function(e) {
                if (!$(e.target).closest(".dropdown").length) {
                    $(".dropdown-menu").removeClass("show").hide();
                    $(".dropdown-toggle").attr("aria-expanded", "false");
                    log("Dropdown closed by outside click", "info");
                }
            });
            
            log("Dropdown initialized successfully", "success");
        });
        
        function testClick() {
            log("Testing programmatic click...", "info");
            $(".dropdown-toggle").trigger("click");
        }
        
        function forceShow() {
            log("Force showing dropdown...", "info");
            $(".dropdown-menu").addClass("show").show();
            $(".dropdown-toggle").attr("aria-expanded", "true");
        }
        
        function forceHide() {
            log("Force hiding dropdown...", "info");
            $(".dropdown-menu").removeClass("show").hide();
            $(".dropdown-toggle").attr("aria-expanded", "false");
        }
        
        function debugEvents() {
            log("Debugging events...", "info");
            var $toggle = $(".dropdown-toggle");
            var events = $._data($toggle[0], "events");
            console.log("Events bound to dropdown toggle:", events);
            log("Check console for event details", "info");
        }
        
        function copyAdminCode() {
            var adminFixCode = `
// PASTE THIS IN ADMIN PAGE CONSOLE TO FIX DROPDOWN
$(document).ready(function() {
    console.log("Fixing admin dropdown...");
    
    // Remove existing handlers
    $(".dropdown-toggle").off("click");
    
    // Add working click handler
    $(".dropdown-toggle").on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log("Admin dropdown clicked!");
        
        var \\$toggle = $(this);
        var \\$menu = \\$toggle.next(".dropdown-menu");
        
        // Hide other dropdowns
        $(".dropdown-menu").not(\\$menu).removeClass("show").hide();
        
        // Toggle current dropdown
        if (\\$menu.hasClass("show") || \\$menu.is(":visible")) {
            \\$menu.removeClass("show").hide();
            \\$toggle.attr("aria-expanded", "false");
            console.log("Admin dropdown hidden");
        } else {
            \\$menu.addClass("show").show();
            \\$toggle.attr("aria-expanded", "true");
            console.log("Admin dropdown shown");
        }
    });
    
    // Outside click to close
    $(document).off("click.admindropdown").on("click.admindropdown", function(e) {
        if (!$(e.target).closest(".dropdown").length) {
            $(".dropdown-menu").removeClass("show").hide();
            $(".dropdown-toggle").attr("aria-expanded", "false");
        }
    });
    
    console.log("Admin dropdown fixed!");
});
            `;
            
            navigator.clipboard.writeText(adminFixCode).then(function() {
                log("Admin fix code copied to clipboard!", "success");
                alert("Admin fix code copied! Paste in admin page console.");
            });
        }
    </script>
</body>
</html>';

$workingTestFile = public_path('working-dropdown-test.html');
file_put_contents($workingTestFile, $workingTestHtml);
echo "  ✅ Working test page: http://localhost:8000/working-dropdown-test.html\n";

echo "\n3. 🔧 Issue Analysis:\n";
echo "  Based on your description:\n";
echo "    ❌ Normal click doesn't work\n";
echo "    ✅ Force Visible works\n";
echo "    💡 This means CSS is correct but JavaScript events are broken\n";
echo "  \n";
echo "  Possible causes:\n";
echo "    - Event handlers not properly bound\n";
echo "    - Event conflicts with other scripts\n";
echo "    - Bootstrap dropdown initialization issues\n";
echo "    - Event propagation problems\n";

echo "\n4. 🧪 Testing Steps:\n";
echo "  A. Test Working Page:\n";
echo "    1. Open: http://localhost:8000/working-dropdown-test.html\n";
echo "    2. Click dropdown - should work immediately\n";
echo "    3. Check status log for events\n";
echo "    4. Copy admin fix code\n";
echo "  \n";
echo "  B. Fix Admin Page:\n";
echo "    1. Login to admin: http://localhost:8000/admin/dashboard\n";
echo "    2. Open browser console (F12)\n";
echo "    3. Paste admin fix code from working test page\n";
echo "    4. Test dropdown functionality\n";
echo "  \n";
echo "  C. Debug Admin Page:\n";
echo "    1. Load debug script: http://localhost:8000/admin-dropdown-debug.js\n";
echo "    2. Run: enhancedDropdownDebug()\n";
echo "    3. Run: fixDropdown()\n";
echo "    4. Test dropdown\n";

echo "\n5. 💡 Quick Fix for Admin:\n";
echo "  If dropdown still doesn't work, try this in console:\n";
echo "  \n";
echo "  \$('.dropdown-toggle').off('click').on('click', function(e) {\n";
echo "    e.preventDefault();\n";
echo "    var \$menu = \$(this).next('.dropdown-menu');\n";
echo "    \$menu.toggle();\n";
echo "  });\n";

echo "\n📋 SUMMARY:\n";
echo "Debug script created: ✅ Enhanced debugging tools\n";
echo "Working test page: ✅ Functional dropdown example\n";
echo "Admin fix code: ✅ Copy-paste solution available\n";
echo "Issue identified: ✅ JavaScript event handling problem\n";

echo "\n🎯 NEXT STEPS:\n";
echo "1. Test working page to confirm fix works\n";
echo "2. Apply fix to admin page via console\n";
echo "3. Update layout file with working code\n";
echo "4. Test dropdown functionality\n";

echo "\n✅ Dropdown event debugging completed!\n";
echo "Test working page: http://localhost:8000/working-dropdown-test.html\n";

?>
