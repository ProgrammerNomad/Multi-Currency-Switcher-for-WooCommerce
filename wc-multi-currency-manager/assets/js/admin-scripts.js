jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-picker').wpColorPicker({
        // Live preview updates for style settings
        change: function(event, ui) {
            updateStylePreview();
        },
        clear: function() {
            setTimeout(updateStylePreview, 50);
        }
    });
    
    // Update style preview function
    function updateStylePreview() {
        if ($('#style-preview').length) {
            var root = document.documentElement;
            root.style.setProperty('--title-color', $('#title_color').val() || '#333333');
            root.style.setProperty('--text-color', $('#text_color').val() || '#000000');
            root.style.setProperty('--active-color', $('#active_color').val() || '#04AE93');
            root.style.setProperty('--background-color', $('#background_color').val() || '#FFFFFF');
            root.style.setProperty('--border-color', $('#border_color').val() || '#B2B2B2');
        }
    }
    
    // Make tables responsive on mobile
    function makeTablesResponsive() {
        $('.wp-list-table').each(function() {
            if (!$(this).parent().hasClass('currency-table-responsive')) {
                $(this).wrap('<div class="currency-table-responsive"></div>');
            }
        });
    }
    
    // Initialize responsive tables
    makeTablesResponsive();
    
    // Handle window resize for responsive adjustments
    $(window).on('resize', function() {
        // Additional responsive handling can go here
    });
    
    // Postbox handling for metabox-holder
    if (typeof postboxes !== 'undefined') {
        postboxes.add_postbox_toggles(pagenow);
    }
});