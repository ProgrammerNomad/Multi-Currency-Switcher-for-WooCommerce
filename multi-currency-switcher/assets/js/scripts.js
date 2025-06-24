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
        
        // If this is a mini cart update only, refresh the fragments
        if (isMinicartPage()) {
            refreshMinicart(currency);
        } else {
            // Add a timestamp parameter to prevent caching issues
            const timestamp = new Date().getTime();
            const separator = window.location.href.indexOf('?') !== -1 ? '&' : '?';
            
            // Reload the page with currency parameter
            window.location.href = window.location.href.split('#')[0] + 
                                separator + 
                                'currency=' + currency + 
                                '&_=' + timestamp;
        }
    }
    
    // Check if we're on a mini cart page
    function isMinicartPage() {
        return window.location.href.indexOf('wc-ajax=get_refreshed_fragments') !== -1;
    }
    
    // Refresh mini cart without page reload
    function refreshMinicart(currency) {
        // Make AJAX call to refresh mini cart fragments
        fetch('/wp-admin/admin-ajax.php?action=multi_currency_refresh_fragments&currency=' + currency)
            .then(response => response.json())
            .then(data => {
                // Update fragments in the DOM
                if (data.fragments) {
                    jQuery.each(data.fragments, function(key, value) {
                        jQuery(key).replaceWith(value);
                    });
                }
                // Trigger mini cart update event
                document.body.dispatchEvent(new CustomEvent('wc_fragments_refreshed', {
                    detail: {
                        fragments: data.fragments
                    }
                }));
            })
            .catch(error => {
                console.error('Error refreshing mini cart:', error);
                // Fall back to page reload if AJAX fails
                window.location.reload();
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