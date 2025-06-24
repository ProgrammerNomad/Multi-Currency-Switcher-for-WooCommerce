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
        
        // Add a timestamp parameter to prevent caching issues
        const timestamp = new Date().getTime();
        const separator = window.location.href.indexOf('?') !== -1 ? '&' : '?';
        
        // Reload the page with currency parameter
        window.location.href = window.location.href.split('#')[0] + 
                              separator + 
                              'currency=' + currency + 
                              '&_=' + timestamp;
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
    
    // Remove the code that creates a sticky switcher manually
    // It will be created by the PHP code instead
});