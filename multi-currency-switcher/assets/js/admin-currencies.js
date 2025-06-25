jQuery(document).ready(function($) {
    console.log('Currency admin script loaded');
    
    // Set row class on page load
    $('input[type="checkbox"][name^="currencies"]').each(function() {
        var $row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            $row.removeClass('disabled-currency').addClass('enabled-currency');
        } else {
            $row.removeClass('enabled-currency').addClass('disabled-currency');
        }
    });

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

    // Store initial state for each row
    $('tr[data-currency-code]').each(function() {
        var $row = $(this);
        var code = $row.data('currency-code');
        $row.data('initial', {
            enabled: $row.find('input[type="checkbox"][name^="currencies"]').is(':checked'),
            rate: $row.find('input[name^="currencies"][name$="[rate]"]').val(),
            position: $row.find('select[name^="currencies"][name$="[position]"]').val(),
            decimals: $row.find('input[name^="currencies"][name$="[decimals]"]').val(),
            thousand_sep: $row.find('input[name^="currencies"][name$="[thousand_sep]"]').val(),
            decimal_sep: $row.find('input[name^="currencies"][name$="[decimal_sep]"]').val()
        });
    });

    // Mark row as changed on any input change
    $('tr[data-currency-code] input, tr[data-currency-code] select').on('change input', function() {
        $(this).closest('tr').attr('data-changed', '1');
    });

    // On form submit, remove all unchanged rows except base currency
    $('form').on('submit', function(e) {
        $('tr[data-currency-code]').each(function() {
            var $row = $(this);
            var isBase = $row.hasClass('base-currency');
            var changed = $row.attr('data-changed') === '1';
            if (!isBase && !changed) {
                $row.remove();
            }
        });
    });
});