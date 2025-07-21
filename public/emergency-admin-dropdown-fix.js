
// EMERGENCY ADMIN DROPDOWN FIX - Copy/paste in admin console
console.log('🚨 EMERGENCY ADMIN DROPDOWN FIX');

// Step 1: Nuclear option - remove everything
$('.dropdown-toggle').off();
$('a[data-toggle="dropdown"]').off();
$('.nav-link').off('click');
$(document).off('click.dropdown');
$(document).off('click.bs.dropdown');

// Step 2: Find dropdown elements
var $userDropdown = $('.dropdown-toggle').first();
if ($userDropdown.length === 0) {
    $userDropdown = $('a[data-toggle="dropdown"]').first();
}
if ($userDropdown.length === 0) {
    $userDropdown = $('.nav-link[data-toggle="dropdown"]').first();
}

console.log('Found user dropdown:', $userDropdown.length > 0);

if ($userDropdown.length > 0) {
    // Step 3: Add working click handler
    $userDropdown.on('click.emergency', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        console.log('🔽 EMERGENCY: User dropdown clicked!');
        
        var $menu = $(this).next('.dropdown-menu');
        if ($menu.length === 0) {
            $menu = $(this).siblings('.dropdown-menu');
        }
        if ($menu.length === 0) {
            $menu = $(this).parent().find('.dropdown-menu');
        }
        
        console.log('Menu found:', $menu.length);
        
        if ($menu.length > 0) {
            if ($menu.is(':visible')) {
                $menu.hide();
                console.log('✅ Menu hidden');
            } else {
                $menu.show();
                console.log('✅ Menu shown');
            }
        } else {
            console.error('❌ Menu not found');
            // Try force show any dropdown menu
            $('.dropdown-menu').first().toggle();
        }
    });
    
    // Step 4: Add outside click
    $(document).on('click.emergency', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').hide();
        }
    });
    
    console.log('✅ EMERGENCY FIX APPLIED!');
    console.log('Try clicking user dropdown now.');
    
    // Step 5: Test function
    window.testEmergencyDropdown = function() {
        console.log('Testing emergency dropdown...');
        $userDropdown.trigger('click');
    };
    
    console.log('Run testEmergencyDropdown() to test');
    
} else {
    console.error('❌ EMERGENCY: No dropdown elements found!');
    console.log('Available elements:');
    console.log('- .dropdown-toggle:', $('.dropdown-toggle').length);
    console.log('- [data-toggle=dropdown]:', $('[data-toggle="dropdown"]').length);
    console.log('- .nav-link:', $('.nav-link').length);
}
