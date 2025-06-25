jQuery(document).ready(function($) {
    // Handle currency checkbox toggling and visual updates
    $(document).on('change', 'input[type="checkbox"][name^="currencies"]', function() {
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
    
    // Prevent unchecking base currency
    $(document).on('click', '.base-currency input[type="checkbox"]', function(e) {
        if (!$(this).is(':checked')) {
            e.preventDefault();
            alert('Base currency cannot be disabled.');
            return false;
        }
    });
});