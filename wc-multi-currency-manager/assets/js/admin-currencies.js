jQuery(document).ready(function($) {
    console.log('Currency admin script loaded');
    
    // Get currency data from WordPress localized script
    window.allCurrencies = {};
    if (typeof currencyManagerData !== 'undefined' && currencyManagerData.allCurrencies) {
        window.allCurrencies = currencyManagerData.allCurrencies;
        console.log('Currency data loaded:', window.allCurrencies);
    } else {
        console.warn('Currency data not found, using fallback');
        // Fallback currency data
        window.allCurrencies = {
            'USD': { name: 'US Dollar', symbol: '$' },
            'EUR': { name: 'Euro', symbol: '€' },
            'GBP': { name: 'British Pound', symbol: '£' },
            'JPY': { name: 'Japanese Yen', symbol: '¥' },
            'AUD': { name: 'Australian Dollar', symbol: 'A$' },
            'CAD': { name: 'Canadian Dollar', symbol: 'C$' },
            'CHF': { name: 'Swiss Franc', symbol: 'CHF' },
            'CNY': { name: 'Chinese Yuan', symbol: '¥' },
            'SEK': { name: 'Swedish Krona', symbol: 'kr' },
            'NZD': { name: 'New Zealand Dollar', symbol: 'NZ$' }
        };
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
        if (!code) {
            alert('Please select a currency to add.');
            return;
        }
        
        // Check if currency already exists in the table
        var exists = false;
        $('#enabled-currencies tr[data-currency-code]').each(function() {
            if ($(this).data('currency-code') === code) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            alert('Currency ' + code + ' is already enabled.');
            return;
        }
        
        console.log('Adding currency:', code);
        
        // Get currency data with fallback
        var currencyData;
        if (window.allCurrencies && window.allCurrencies[code]) {
            currencyData = window.allCurrencies[code];
        } else {
            // Fallback currency data
            currencyData = {
                name: code,
                symbol: code
            };
            console.log('Using fallback data for currency:', code);
        }
        
        // Create new row
        var newRow = $('<tr class="enabled-currency" data-currency-code="' + code + '" data-changed="1"></tr>');
        
        // Add cells
        newRow.append('<td><button type="button" class="button remove-currency" title="Remove Currency">&times;</button>' +
            '<input type="hidden" name="currencies[' + code + '][enable]" value="1"></td>');
        newRow.append('<td><strong>' + code + '</strong></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][name]" value="' + currencyData.name + '" class="regular-text"></td>');
        newRow.append('<td><input type="text" name="currencies[' + code + '][symbol]" value="' + currencyData.symbol + '" class="regular-text"></td>');
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
        
        // Add visual feedback
        newRow.css('background-color', '#d4edda');
        setTimeout(function() {
            newRow.css('background-color', '');
        }, 2000);
        
        // Remove from dropdown
        $('#add-currency-select option[value="' + code + '"]').remove();
        $('#add-currency-select').val('');
        
        console.log('Successfully added currency to table:', code);
    });
    
    // Remove currency - FIX HERE - use window.allCurrencies instead of allCurrencies
    $(document).on('click', '.remove-currency', function() {
        var row = $(this).closest('tr');
        var code = row.data('currency-code');
        
        console.log('Removing currency:', code);
        
        // Check if this currency is already in the dropdown (to prevent duplicates)
        if ($('#add-currency-select option[value="' + code + '"]').length === 0) {
            var optionText;
            // Try to get currency data from the localized data
            if (window.allCurrencies && window.allCurrencies[code]) {
                optionText = code + ' - ' + window.allCurrencies[code].name;
            } else {
                // Fallback: try to get from the removed row or use code only
                var currencyName = row.find('input[name^="currencies['+code+'][name]"]').val() || code;
                optionText = code + ' - ' + currencyName;
                console.log('Using fallback currency name for:', code, '->', currencyName);
            }
            
            $('#add-currency-select').append($('<option></option>').attr('value', code).text(optionText));
            console.log('Added currency to dropdown:', code, '->', optionText);
        }
        
        // Create a hidden field to track removed currencies
        $('form').append('<input type="hidden" name="removed_currencies[]" value="' + code + '">');
        
        // Remove row
        row.remove();
    });

    // Country search functionality - improved version
    $('#country-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var totalRows = 0;
        var visibleRows = 0;
        
        $('#country-mapping-tbody tr.country-row').each(function() {
            var $row = $(this);
            totalRows++;
            
            // Get search text from multiple sources
            var countryName = $row.find('td:first strong').text().toLowerCase();
            var countryCode = $row.find('td:nth-child(2) code').text().toLowerCase();
            var searchText = countryName + ' ' + countryCode;
            
            var isVisible = searchTerm === '' || searchText.includes(searchTerm);
            
            if (isVisible) {
                $row.show();
                visibleRows++;
                
                // Add highlight if there's a search term
                if (searchTerm.length > 0) {
                    $row.addClass('highlight');
                } else {
                    $row.removeClass('highlight');
                }
            } else {
                $row.hide().removeClass('highlight');
            }
        });
        
        // Optional: Show search result count
        console.log('Country search: ' + visibleRows + ' of ' + totalRows + ' countries shown');
    });

    // Auto-detect checkbox sync
    $('#enable-auto-detect').on('change', function() {
        var isEnabled = $(this).is(':checked');
        $('input[name="auto_detect_settings[enabled]"]').prop('checked', isEnabled);
    });

    // Sync the auto-detect setting with general settings
    $('input[name="auto_detect_settings[enabled]"]').on('change', function() {
        var isEnabled = $(this).is(':checked');
        $('#enable-auto-detect').prop('checked', isEnabled);
    });
});
