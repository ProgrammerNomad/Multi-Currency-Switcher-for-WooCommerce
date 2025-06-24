document.addEventListener('DOMContentLoaded', function() {
    // Currency selectors
    const currencySelectors = [
        document.getElementById('currency-selector'),
        document.getElementById('sticky-currency-selector'),
        document.getElementById('product-currency-selector'),
        document.getElementById('currency-switcher')
    ];

    // Add event listeners to all currency selectors that exist
    currencySelectors.forEach(function(selector) {
        if (selector) {
            selector.addEventListener('change', function() {
                changeCurrency(this.value);
            });
        }
    });

    function changeCurrency(currency) {
        // Create or update the cookie (30 days expiry)
        document.cookie = "chosen_currency=" + currency + "; path=/; max-age=2592000";
        
        // Update mini cart via AJAX
        updateMiniCart(currency, function() {
            // Add a timestamp parameter to prevent caching issues
            const timestamp = new Date().getTime();
            const separator = window.location.href.indexOf('?') !== -1 ? '&' : '?';
            
            // Reload the page with currency parameter (but only if not in cart or checkout)
            if (!isCartOrCheckout()) {
                window.location.href = window.location.href.split('#')[0] + 
                                    separator + 
                                    'currency=' + currency + 
                                    '&_=' + timestamp;
            }
        });
    }
    
    function isCartOrCheckout() {
        return window.location.href.indexOf('/cart/') !== -1 || 
               window.location.href.indexOf('/checkout/') !== -1;
    }
    
    function updateMiniCart(currency, callback) {
        // Make AJAX call to update mini cart without page reload
        fetch('/wp-admin/admin-ajax.php?action=multi_currency_switch&currency=' + currency)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update fragments in the DOM
                    if (data.data && data.data.fragments) {
                        jQuery.each(data.data.fragments, function(key, value) {
                            jQuery(key).replaceWith(value);
                        });
                    }
                    
                    // Trigger WooCommerce fragment refresh event
                    jQuery(document.body).trigger('wc_fragments_refreshed');
                    
                    // Execute callback after successful update
                    if (typeof callback === 'function') {
                        callback();
                    }
                } else {
                    console.error('Error updating currency:', data.data ? data.data.message : 'Unknown error');
                    // Execute callback even on error to ensure page reload
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            })
            .catch(error => {
                console.error('Error updating mini cart:', error);
                // Execute callback even on error to ensure page reload
                if (typeof callback === 'function') {
                    callback();
                }
            });
    }

    // Don't try to access elements that might not exist
    const currencyDisplay = document.getElementById('current-currency');
    if (currencyDisplay) {
        // Fetch geolocation-based currency
        fetch('/wp-admin/admin-ajax.php?action=get_geolocation_currency')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currencyDisplay.textContent = data.currency;
                }
            })
            .catch(error => {
                console.error('Error during geolocation currency fetch:', error);
            });
    }
});