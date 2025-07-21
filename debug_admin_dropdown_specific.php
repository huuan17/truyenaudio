<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG ADMIN DROPDOWN SPECIFIC ===\n";

// Test 1: Create admin-specific debug script
echo "1. 🔧 Admin-Specific Debug Script:\n";
$adminSpecificDebug = "
// ADMIN PAGE DROPDOWN FIX - Paste in admin page console
console.log('=== ADMIN DROPDOWN FIX ===');

// Step 1: Check current state
console.log('Step 1: Checking current state...');
var dropdownToggles = \$('.dropdown-toggle');
var dropdownMenus = \$('.dropdown-menu');

console.log('Dropdown toggles found:', dropdownToggles.length);
console.log('Dropdown menus found:', dropdownMenus.length);

if (dropdownToggles.length === 0) {
    console.error('❌ No dropdown toggles found!');
    console.log('Looking for alternative selectors...');
    
    // Try alternative selectors
    var userLinks = \$('a[data-toggle=\"dropdown\"]');
    var navLinks = \$('.nav-link[data-toggle=\"dropdown\"]');
    
    console.log('data-toggle dropdown links:', userLinks.length);
    console.log('nav-link dropdown links:', navLinks.length);
    
    if (userLinks.length > 0) {
        dropdownToggles = userLinks;
        console.log('✅ Using data-toggle selector');
    } else if (navLinks.length > 0) {
        dropdownToggles = navLinks;
        console.log('✅ Using nav-link selector');
    }
}

// Step 2: Check for conflicts
console.log('Step 2: Checking for conflicts...');
dropdownToggles.each(function(i) {
    var \$toggle = \$(this);
    var events = \$._data(\$toggle[0], 'events');
    console.log('Toggle ' + (i+1) + ' events:', events);
    
    if (events && events.click && events.click.length > 1) {
        console.warn('⚠️ Multiple click handlers detected on toggle ' + (i+1));
    }
});

// Step 3: Force fix dropdown
console.log('Step 3: Applying force fix...');

// Remove ALL event handlers
dropdownToggles.off();
\$('.dropdown-menu').off();
\$(document).off('click.dropdown');

// Add simple working handler
dropdownToggles.on('click.adminfix', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    console.log('🔽 Admin dropdown clicked!');
    
    var \$toggle = \$(this);
    var \$menu = \$toggle.next('.dropdown-menu');
    
    if (\$menu.length === 0) {
        // Try finding menu differently
        \$menu = \$toggle.siblings('.dropdown-menu');
        if (\$menu.length === 0) {
            \$menu = \$toggle.parent().find('.dropdown-menu');
        }
    }
    
    console.log('Menu found:', \$menu.length > 0);
    
    if (\$menu.length > 0) {
        // Hide all other dropdowns
        \$('.dropdown-menu').not(\$menu).removeClass('show').hide();
        
        // Toggle current dropdown
        if (\$menu.is(':visible')) {
            \$menu.removeClass('show').hide();
            \$toggle.attr('aria-expanded', 'false');
            console.log('✅ Dropdown hidden');
        } else {
            \$menu.addClass('show').show();
            \$toggle.attr('aria-expanded', 'true');
            console.log('✅ Dropdown shown');
        }
    } else {
        console.error('❌ Dropdown menu not found');
    }
});

// Add outside click handler
\$(document).on('click.adminfix', function(e) {
    if (!\$(e.target).closest('.dropdown').length) {
        \$('.dropdown-menu').removeClass('show').hide();
        dropdownToggles.attr('aria-expanded', 'false');
        console.log('🔽 Dropdown closed by outside click');
    }
});

console.log('✅ Admin dropdown fix applied!');
console.log('Try clicking the user dropdown now.');

// Step 4: Test function
window.testAdminDropdown = function() {
    console.log('Testing admin dropdown...');
    var \$menu = \$('.dropdown-menu').first();
    \$menu.toggle();
    console.log('Menu toggled. Visible:', \$menu.is(':visible'));
};

// Step 5: Force show function
window.forceShowAdminDropdown = function() {
    console.log('Force showing admin dropdown...');
    \$('.dropdown-menu').addClass('show').show();
    \$('.dropdown-toggle').attr('aria-expanded', 'true');
    console.log('Dropdown force shown');
};

console.log('Available functions:');
console.log('- testAdminDropdown() - Test toggle');
console.log('- forceShowAdminDropdown() - Force show');
";

$adminDebugFile = public_path('admin-dropdown-fix.js');
file_put_contents($adminDebugFile, $adminSpecificDebug);
echo "  ✅ Admin fix script: http://localhost:8000/admin-dropdown-fix.js\n";

// Test 2: Create admin page test
echo "\n2. 🧪 Admin Page Test Instructions:\n";
echo "  A. Login to Admin:\n";
echo "    1. Go to: http://localhost:8000/login\n";
echo "    2. Login: admin@example.com / password\n";
echo "    3. Should redirect to admin dashboard\n";
echo "  \n";
echo "  B. Apply Fix:\n";
echo "    1. Open browser console (F12)\n";
echo "    2. Copy/paste admin fix script\n";
echo "    3. Or load: http://localhost:8000/admin-dropdown-fix.js\n";
echo "    4. Look for success messages\n";
echo "  \n";
echo "  C. Test Dropdown:\n";
echo "    1. Look for user icon/name (top right)\n";
echo "    2. Click on user area\n";
echo "    3. Should see dropdown menu\n";
echo "    4. Test logout button\n";

// Test 3: Check layout differences
echo "\n3. 🔍 Layout vs Test Page Differences:\n";
$layoutFile = resource_path('views/layouts/app.blade.php');
$layoutContent = file_get_contents($layoutFile);

// Check for AdminLTE specific classes
$adminlteChecks = [
    'main-header' => 'AdminLTE header class',
    'navbar-nav' => 'Bootstrap navbar',
    'nav-item dropdown' => 'Dropdown structure',
    'dropdown-toggle' => 'Bootstrap dropdown class',
    'data-toggle="dropdown"' => 'Bootstrap data attribute'
];

foreach ($adminlteChecks as $check => $description) {
    if (strpos($layoutContent, $check) !== false) {
        echo "  ✅ {$description}: Found\n";
    } else {
        echo "  ❌ {$description}: Missing\n";
    }
}

// Test 4: Check for script conflicts
echo "\n4. 🔧 Potential Script Conflicts:\n";
$scriptChecks = [
    'adminlte.min.js' => 'AdminLTE JavaScript',
    'bootstrap' => 'Bootstrap JavaScript',
    'jquery' => 'jQuery library',
    'dropdown' => 'Dropdown initialization'
];

foreach ($scriptChecks as $check => $description) {
    if (strpos($layoutContent, $check) !== false) {
        echo "  ✅ {$description}: Found\n";
    } else {
        echo "  ⚠️ {$description}: Not found\n";
    }
}

// Test 5: Create simple admin dropdown test
echo "\n5. 🎯 Simple Admin Test:\n";
$simpleAdminTest = '
<!DOCTYPE html>
<html>
<head>
    <title>Simple Admin Dropdown Test</title>
    <link href="/assets/css/adminlte.min.css" rel="stylesheet">
    <link href="/assets/css/fontawesome-6.4.0-all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .main-header { background: #343a40; padding: 10px 20px; }
        
        /* Force dropdown visibility */
        .navbar-nav .dropdown-menu {
            position: absolute !important;
            z-index: 99999 !important;
            display: none !important;
            background: white !important;
            border: 1px solid #ccc !important;
            min-width: 200px !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
        
        .navbar-nav .dropdown-menu.show {
            display: block !important;
        }
        
        .test-info {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="test-info">
        <h4>Simple Admin Dropdown Test</h4>
        <p>This mimics the admin layout structure</p>
    </div>
    
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user"></i>
                    <span class="ml-1">Admin User</span>
                    <i class="fas fa-caret-down ml-1"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="dropdown-header">
                        <strong>Admin User</strong><br>
                        <small class="text-muted">admin@example.com</small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item text-danger" onclick="alert(\'Logout clicked\')">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </li>
        </ul>
    </nav>
    
    <div class="test-info">
        <h5>Test Controls</h5>
        <button class="btn btn-primary" onclick="testClick()">Test Click</button>
        <button class="btn btn-success" onclick="forceShow()">Force Show</button>
        <button class="btn btn-warning" onclick="applyFix()">Apply Fix</button>
        <div id="log" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>
    </div>
    
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/bootstrap-4.6.2.bundle.min.js"></script>
    <script src="/assets/js/adminlte.min.js"></script>
    
    <script>
        function log(msg) {
            document.getElementById("log").innerHTML += "<div>" + new Date().toLocaleTimeString() + ": " + msg + "</div>";
            console.log(msg);
        }
        
        $(document).ready(function() {
            log("Page loaded with AdminLTE assets");
            
            // Check what is loaded
            log("jQuery: " + (typeof $ !== "undefined" ? "✅" : "❌"));
            log("Bootstrap dropdown: " + (typeof $.fn.dropdown !== "undefined" ? "✅" : "❌"));
            log("AdminLTE: " + (typeof AdminLTE !== "undefined" ? "✅" : "❌"));
        });
        
        function testClick() {
            log("Testing dropdown click...");
            $(".dropdown-toggle").trigger("click");
        }
        
        function forceShow() {
            log("Force showing dropdown...");
            $(".dropdown-menu").addClass("show").show();
        }
        
        function applyFix() {
            log("Applying dropdown fix...");
            
            $(".dropdown-toggle").off("click").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                log("Dropdown clicked!");
                var $menu = $(this).next(".dropdown-menu");
                $menu.toggle();
                log("Menu toggled. Visible: " + $menu.is(":visible"));
            });
            
            log("Fix applied!");
        }
    </script>
</body>
</html>';

$simpleAdminFile = public_path('simple-admin-dropdown-test.html');
file_put_contents($simpleAdminFile, $simpleAdminTest);
echo "  ✅ Simple admin test: http://localhost:8000/simple-admin-dropdown-test.html\n";

echo "\n6. 🔧 Recommended Fix Steps:\n";
echo "  Step 1: Test simple admin page\n";
echo "    - Verify AdminLTE assets load correctly\n";
echo "    - Check if dropdown works in isolated environment\n";
echo "  \n";
echo "  Step 2: Apply admin fix script\n";
echo "    - Load admin page and open console\n";
echo "    - Paste admin fix script\n";
echo "    - Test dropdown functionality\n";
echo "  \n";
echo "  Step 3: Update layout if needed\n";
echo "    - Add admin-specific event handling\n";
echo "    - Override AdminLTE conflicts\n";
echo "    - Test in production environment\n";

echo "\n📋 SUMMARY:\n";
echo "Working test page: ✅ Dropdown works\n";
echo "Admin page: ❌ Still has conflicts\n";
echo "Admin fix script: ✅ Created\n";
echo "Simple admin test: ✅ Available\n";
echo "Debug tools: ✅ Console functions ready\n";

echo "\n🎯 NEXT STEPS:\n";
echo "1. Test simple admin page: http://localhost:8000/simple-admin-dropdown-test.html\n";
echo "2. Apply fix to real admin page via console\n";
echo "3. Update layout with working solution\n";

echo "\n✅ Admin dropdown debugging completed!\n";

?>
