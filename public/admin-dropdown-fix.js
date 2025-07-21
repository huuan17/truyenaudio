
// ADMIN PAGE DROPDOWN FIX - Paste in admin page console
console.log('=== ADMIN DROPDOWN FIX ===');

// Step 1: Check current state
console.log('Step 1: Checking current state...');
var dropdownToggles = $('.dropdown-toggle');
var dropdownMenus = $('.dropdown-menu');

console.log('Dropdown toggles found:', dropdownToggles.length);
console.log('Dropdown menus found:', dropdownMenus.length);

if (dropdownToggles.length === 0) {
    console.error('❌ No dropdown toggles found!');
    console.log('Looking for alternative selectors...');
    
    // Try alternative selectors
    var userLinks = $('a[data-toggle="dropdown"]');
    var navLinks = $('.nav-link[data-toggle="dropdown"]');
    
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
    var $toggle = $(this);
    var events = $._data($toggle[0], 'events');
    console.log('Toggle ' + (i+1) + ' events:', events);
    
    if (events && events.click && events.click.length > 1) {
        console.warn('⚠️ Multiple click handlers detected on toggle ' + (i+1));
    }
});

// Step 3: Force fix dropdown
console.log('Step 3: Applying force fix...');

// Remove ALL event handlers
dropdownToggles.off();
$('.dropdown-menu').off();
$(document).off('click.dropdown');

// Add simple working handler
dropdownToggles.on('click.adminfix', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    console.log('🔽 Admin dropdown clicked!');
    
    var $toggle = $(this);
    var $menu = $toggle.next('.dropdown-menu');
    
    if ($menu.length === 0) {
        // Try finding menu differently
        $menu = $toggle.siblings('.dropdown-menu');
        if ($menu.length === 0) {
            $menu = $toggle.parent().find('.dropdown-menu');
        }
    }
    
    console.log('Menu found:', $menu.length > 0);
    
    if ($menu.length > 0) {
        // Hide all other dropdowns
        $('.dropdown-menu').not($menu).removeClass('show').hide();
        
        // Toggle current dropdown
        if ($menu.is(':visible')) {
            $menu.removeClass('show').hide();
            $toggle.attr('aria-expanded', 'false');
            console.log('✅ Dropdown hidden');
        } else {
            $menu.addClass('show').show();
            $toggle.attr('aria-expanded', 'true');
            console.log('✅ Dropdown shown');
        }
    } else {
        console.error('❌ Dropdown menu not found');
    }
});

// Add outside click handler
$(document).on('click.adminfix', function(e) {
    if (!$(e.target).closest('.dropdown').length) {
        $('.dropdown-menu').removeClass('show').hide();
        dropdownToggles.attr('aria-expanded', 'false');
        console.log('🔽 Dropdown closed by outside click');
    }
});

console.log('✅ Admin dropdown fix applied!');
console.log('Try clicking the user dropdown now.');

// Step 4: Test function
window.testAdminDropdown = function() {
    console.log('Testing admin dropdown...');
    var $menu = $('.dropdown-menu').first();
    $menu.toggle();
    console.log('Menu toggled. Visible:', $menu.is(':visible'));
};

// Step 5: Force show function
window.forceShowAdminDropdown = function() {
    console.log('Force showing admin dropdown...');
    $('.dropdown-menu').addClass('show').show();
    $('.dropdown-toggle').attr('aria-expanded', 'true');
    console.log('Dropdown force shown');
};

console.log('Available functions:');
console.log('- testAdminDropdown() - Test toggle');
console.log('- forceShowAdminDropdown() - Force show');
