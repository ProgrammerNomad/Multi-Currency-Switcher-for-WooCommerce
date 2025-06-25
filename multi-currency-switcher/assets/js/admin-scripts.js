jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-picker').wpColorPicker();
    
    // Handle currency checkbox toggling and visual updates
    $('input[type="checkbox"][name^="currencies"]').on('change', function() {
        var $row = $(this).closest('tr');
        var isChecked = $(this).is(':checked');
        
        // Update row class
        if (isChecked) {
            $row.removeClass('disabled-currency').addClass('enabled-currency');
            
            // Move the row below base currency but above disabled currencies
            var $baseCurrency = $('.base-currency');
            var $disabledCurrencies = $('.disabled-currency').first();
            
            if ($disabledCurrencies.length) {
                $row.insertBefore($disabledCurrencies);
            } else {
                // If no disabled currencies, add to the end of the table
                $row.appendTo($row.parent());
            }
        } else {
            $row.removeClass('enabled-currency').addClass('disabled-currency');
            
            // Move to the disabled section (end of the table)
            $row.appendTo($row.parent());
        }
    });
    
    // Additional visual cue on hover
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
    
    // Prevent unchecking base currency
    $('.base-currency input[type="checkbox"]').on('click', function(e) {
        if (!$(this).is(':checked')) {
            e.preventDefault();
            alert('Base currency cannot be disabled.');
            return false;
        }
    });
});