
// Dropdown Debug Script - Paste in browser console
console.log('=== DROPDOWN DEBUG ===');
console.log('jQuery loaded:', typeof $ !== 'undefined');
console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
console.log('Bootstrap dropdown available:', typeof $.fn.dropdown !== 'undefined');

// Check dropdown elements
var dropdownToggles = $('.dropdown-toggle');
console.log('Dropdown toggles found:', dropdownToggles.length);

dropdownToggles.each(function(i, el) {
    console.log('Dropdown ' + (i+1) + ':', {
        'has dropdown-toggle class': $(el).hasClass('dropdown-toggle'),
        'has data-toggle': $(el).attr('data-toggle'),
        'has role': $(el).attr('role'),
        'has aria-haspopup': $(el).attr('aria-haspopup'),
        'has aria-expanded': $(el).attr('aria-expanded'),
        'next element is dropdown-menu': $(el).next().hasClass('dropdown-menu')
    });
});

// Test manual click
console.log('Testing manual dropdown click...');
$('.dropdown-toggle').first().trigger('click');
