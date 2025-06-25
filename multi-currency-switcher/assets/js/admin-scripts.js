jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-picker').wpColorPicker();
    
    // Remove the currency checkbox handling from this file - it's now in admin-currencies.js
    // This prevents conflicts with the same functionality in different files
    
    // Additional visual cue on hover for all admin pages
    $('.currency-table-container tbody tr').hover(
        function() {
            $(this).css('background-color', '#f9f9f9');
        },
        function() {
            // Return to original color based on class
            if ($(this).hasClass('base-currency')) {
                $(this).css('background-color', '#f7fcff');
            } else if ($(this).hasClass('enabled-currency')) {
                $(this).css('background-color', '');
            } else {
                $(this).css('background-color', '');
            }
        }
    );
});