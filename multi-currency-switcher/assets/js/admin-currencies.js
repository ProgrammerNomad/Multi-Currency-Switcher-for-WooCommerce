jQuery(document).ready(function($) {
    console.log('Currency admin script loaded');
    
    // Track changes to checkboxes
    $(document).on('change', 'input[type="checkbox"][name^="currencies"]', function() {
        var $row = $(this).closest('tr');
        var isChecked = $(this).is(':checked');
        
        // Update row class based on checkbox state
        if (isChecked) {
            $row.removeClass('disabled-currency').addClass('enabled-currency');
        } else {
            $row.removeClass('enabled-currency').addClass('disabled-currency');
        }
    });
    
    // Prevent base currency from being unchecked
    $('.base-currency input[type="checkbox"]').on('click', function(e) {
        if ($(this).is(':checked')) {
            // Don't allow unchecking
            e.preventDefault();
            return false;
        }
    });
});