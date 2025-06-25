jQuery(document).ready(function($) {
    console.log('Currency admin script loaded');
    
    // Make sure allCurrencies is properly defined
    if (typeof window.allCurrencies === 'undefined') {
        window.allCurrencies = {};
    }
    
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
    $('#currencies-form').on('submit', function(e) {
        // Don't remove any rows, let all currencies be submitted
        return true;
    });
    
    // Add new currency row
    $('#add-currency-btn').on('click', function() {
        var code = $('#add-currency-select').val();
        if (!code || !window.allCurrencies || !window.allCurrencies[code]) {
            console.log('Unable to add currency:', code);
            return;
        }
        
        console.log('Adding currency:', code);
        var currencyData = window.allCurrencies[code];
        
        // Create new row
        var newRow = $('<tr class="enabled-currency" data-currency-code="' + code + '" data-changed="1"></tr>');
        
        // Add cells
        newRow.append('<td><button type="button" class="button remove-currency" title="Remove Currency">&times;</button>' +
            '<input type="hidden" name="currencies[' + code + '][enable]" value="1"></td>');
        newRow.append('<td><strong>' + code + '</strong></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][name]" value="' + currencyData.name + '" class="regular-text" readonly></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][symbol]" value="' + currencyData.symbol + '" class="regular-text" readonly></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][rate]" value="1" class="regular-text"></td>');
        
        // Position dropdown
        var positionSelect = '<td><select name="currencies[' + code + '][position]">' +
            '<option value="left">Left</option>' +
            '<option value="right">Right</option>' +
            '<option value="left_space">Left with space</option>' +
            '<option value="right_space">Right with space</option>' +
            '</select></td>';
        newRow.append(positionSelect);
        
        // Remaining fields
        newRow.append('<td><input type="number" name="currencies[' + code + '][decimals]" value="2" class="small-text"></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][thousand_sep]" value="," class="regular-text"></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][decimal_sep]" value="." class="regular-text"></td>');
        
        // Append to table
        $('#enabled-currencies').append(newRow);
        
        // Remove from dropdown
        $('#add-currency-select option[value="' + code + '"]').remove();
        $('#add-currency-select').val('');
    });
    
    // Remove currency - FIX HERE - use window.allCurrencies instead of allCurrencies
    $(document).on('click', '.remove-currency', function() {
        var row = $(this).closest('tr');
        var code = row.data('currency-code');
        
        console.log('Removing currency:', code);
        console.log('Available currencies:', window.allCurrencies);
        
        // Only proceed if we have the currency data
        if (typeof window.allCurrencies !== 'undefined' && window.allCurrencies && window.allCurrencies[code]) {
            var optionText = code + ' - ' + window.allCurrencies[code].name;
            $('#add-currency-select').append($('<option></option>').attr('value', code).text(optionText));
        } else {
            console.log('Currency data not found for:', code);
            // Add a fallback for when currency data isn't available
            var currencyName = row.find('input[name^="currencies['+code+'][name]"]').val() || code;
            $('#add-currency-select').append($('<option></option>').attr('value', code).text(code + ' - ' + currencyName));
        }
        
        // Create a hidden field to track removed currencies
        $('form').append('<input type="hidden" name="removed_currencies[]" value="' + code + '">');
        
        // Remove row
        row.remove();
    });
});