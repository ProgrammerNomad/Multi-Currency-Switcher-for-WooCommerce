jQuery(document).ready(function($) {
    console.log('Currency admin script loaded');
    
    // Ensure allCurrencies is properly defined
    if (typeof window.allCurrencies === 'undefined') {
        window.allCurrencies = {};
    }
    
    // Make table responsive
    function makeTableResponsive() {
        $('.wp-list-table').each(function() {
            if (!$(this).parent().hasClass('currency-table-responsive')) {
                $(this).wrap('<div class="currency-table-responsive"></div>');
            }
        });
    }
    
    // Initialize responsive table
    makeTableResponsive();
    
    // Add currency functionality
    $(document).on('click', '#add-currency-btn', function(e) {
        e.preventDefault();
        
        var selectedCurrency = $('#add-currency-select').val();
        if (!selectedCurrency) {
            alert('Please select a currency to add.');
            return;
        }
        
        // Check if currency already exists
        var exists = false;
        $('#currency-table-body tr').each(function() {
            var code = $(this).find('td:nth-child(2)').text().trim();
            if (code === selectedCurrency) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            alert('Currency ' + selectedCurrency + ' is already enabled.');
            return;
        }
        
        var currencyData = window.allCurrencies[selectedCurrency];
        if (!currencyData) {
            alert('Currency data not found for ' + selectedCurrency);
            return;
        }
        
        // Create new table row with responsive design
        var newRow = $(`
            <tr class="enabled-currency">
                <td>
                    <button type="button" class="button-link-delete remove-currency" data-currency="${selectedCurrency}" title="Remove ${selectedCurrency}">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
                <td><strong>${selectedCurrency}</strong></td>
                <td>${currencyData.name}</td>
                <td>
                    <input type="number" step="0.0001" min="0" 
                           name="currencies[${selectedCurrency}][exchange_rate]" 
                           value="1.00" 
                           class="small-text">
                </td>
                <td>
                    <select name="currencies[${selectedCurrency}][position]" class="regular-text">
                        <option value="left">Left ($99)</option>
                        <option value="right">Right (99$)</option>
                        <option value="left_space">Left with space ($ 99)</option>
                        <option value="right_space">Right with space (99 $)</option>
                    </select>
                </td>
                <td>
                    <input type="number" min="0" max="4" 
                           name="currencies[${selectedCurrency}][decimals]" 
                           value="2" 
                           class="small-text">
                </td>
                <td>
                    <input type="text" maxlength="5" 
                           name="currencies[${selectedCurrency}][thousand_sep]" 
                           value="," 
                           class="small-text">
                </td>
                <td>
                    <input type="text" maxlength="5" 
                           name="currencies[${selectedCurrency}][decimal_sep]" 
                           value="." 
                           class="small-text">
                </td>
            </tr>
        `);
        
        // Add row to table
        $('#currency-table-body').append(newRow);
        
        // Remove from dropdown
        $('#add-currency-select option[value="' + selectedCurrency + '"]').remove();
        
        // Reset dropdown
        $('#add-currency-select').val('');
        
        // Highlight the new row briefly
        newRow.css('background-color', '#ffffcc');
        setTimeout(function() {
            newRow.css('background-color', '');
        }, 2000);
        
        console.log('Added currency:', selectedCurrency);
    });
    
    // Remove currency functionality
    $(document).on('click', '.remove-currency', function(e) {
        e.preventDefault();
        
        var currency = $(this).data('currency');
        var row = $(this).closest('tr');
        
        if (confirm('Are you sure you want to remove ' + currency + ' from enabled currencies?')) {
            // Get currency data for re-adding to dropdown
            var currencyData = window.allCurrencies[currency];
            if (currencyData) {
                // Add back to dropdown in alphabetical order
                var dropdown = $('#add-currency-select');
                var inserted = false;
                
                dropdown.find('option').each(function() {
                    if ($(this).val() && $(this).val() > currency) {
                        $('<option value="' + currency + '">' + currency + ' - ' + currencyData.name + '</option>').insertBefore($(this));
                        inserted = true;
                        return false;
                    }
                });
                
                if (!inserted) {
                    dropdown.append('<option value="' + currency + '">' + currency + ' - ' + currencyData.name + '</option>');
                }
            }
            
            // Remove the row
            row.remove();
            
            console.log('Removed currency:', currency);
        }
    });
    
    // Handle responsive layout changes
    $(window).on('resize', function() {
        // Responsive adjustments can be added here if needed
    });
    
    console.log('Currency admin script initialized');
});
