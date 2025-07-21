
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
        var $toggle = $(this);
        var $menu = $toggle.next('.dropdown-menu');
        
        console.log('Dropdown ' + (i+1) + ':', {
            'Toggle element': $toggle[0],
            'Menu element': $menu[0],
            'Toggle classes': $toggle.attr('class'),
            'Menu classes': $menu.attr('class'),
            'Toggle data-toggle': $toggle.attr('data-toggle'),
            'Toggle aria-expanded': $toggle.attr('aria-expanded'),
            'Menu computed styles': {
                'display': $menu.css('display'),
                'position': $menu.css('position'),
                'z-index': $menu.css('z-index'),
                'top': $menu.css('top'),
                'right': $menu.css('right'),
                'visibility': $menu.css('visibility'),
                'opacity': $menu.css('opacity')
            },
            'Event handlers': $._data($toggle[0], 'events')
        });
    });
    
    // Test manual click
    console.log('Testing manual click...');
    if (dropdownToggles.length > 0) {
        var $firstToggle = dropdownToggles.first();
        var $firstMenu = $firstToggle.next('.dropdown-menu');
        
        console.log('Before manual click:', {
            'Menu visible': $firstMenu.is(':visible'),
            'Menu display': $firstMenu.css('display'),
            'Has show class': $firstMenu.hasClass('show')
        });
        
        // Force show
        $firstMenu.addClass('show').show();
        $firstToggle.attr('aria-expanded', 'true');
        
        console.log('After force show:', {
            'Menu visible': $firstMenu.is(':visible'),
            'Menu display': $firstMenu.css('display'),
            'Has show class': $firstMenu.hasClass('show')
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
        
        var $toggle = $(this);
        var $menu = $toggle.next('.dropdown-menu');
        
        console.log('Click event details:', {
            'Event type': e.type,
            'Target': e.target,
            'Current target': e.currentTarget,
            'Toggle element': $toggle[0],
            'Menu element': $menu[0]
        });
        
        // Force toggle
        if ($menu.is(':visible')) {
            $menu.removeClass('show').hide();
            $toggle.attr('aria-expanded', 'false');
            console.log('Menu hidden');
        } else {
            $menu.addClass('show').show();
            $toggle.attr('aria-expanded', 'true');
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
        
        var $toggle = $(this);
        var $menu = $toggle.next('.dropdown-menu');
        
        // Hide all other dropdowns
        $('.dropdown-menu').not($menu).removeClass('show').hide();
        $('.dropdown-toggle').not($toggle).attr('aria-expanded', 'false');
        
        // Toggle current dropdown
        if ($menu.hasClass('show') || $menu.is(':visible')) {
            $menu.removeClass('show').hide();
            $toggle.attr('aria-expanded', 'false');
            console.log('Dropdown hidden');
        } else {
            $menu.addClass('show').show();
            $toggle.attr('aria-expanded', 'true');
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
