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
        
        // Show a loading indicator
        showLoadingIndicator();
        
        // Set a timeout to reload the page after 2 seconds if the AJAX call hasn't completed
        const fallbackTimer = setTimeout(function() {
            console.log("AJAX request taking too long, falling back to page reload");
            reloadPage(currency);
        }, 2000);
        
        // Make AJAX call with timeout
        fetch('/wp-admin/admin-ajax.php?action=multi_currency_switch&currency=' + currency, {
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            },
            timeout: 5000
        })
        .then(response => {
            clearTimeout(fallbackTimer);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            clearTimeout(fallbackTimer);
            hideLoadingIndicator();
            
            if (data.success) {
                console.log('Currency successfully changed to: ' + currency);
                reloadPage(currency);
            } else {
                console.error('Error changing currency:', data.data ? data.data.message : 'Unknown error');
                reloadPage(currency);
            }
        })
        .catch(error => {
            clearTimeout(fallbackTimer);
            hideLoadingIndicator();
            console.error('Error during currency switch:', error);
            // If there's an error, just reload the page with the currency parameter
            reloadPage(currency);
        });
    }
    
    function reloadPage(currency) {
        const timestamp = new Date().getTime();
        const separator = window.location.href.indexOf('?') !== -1 ? '&' : '?';
        window.location.href = window.location.href.split('#')[0] + 
                            separator + 
                            'currency=' + currency + 
                            '&_=' + timestamp;
    }
    
    function showLoadingIndicator() {
        // Create and show a simple loading indicator
        const loader = document.createElement('div');
        loader.id = 'currency-switcher-loader';
        loader.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:9999;display:flex;justify-content:center;align-items:center;';
        loader.innerHTML = '<div style="padding:20px;background:#fff;border-radius:5px;box-shadow:0 0 10px rgba(0,0,0,0.2);">Updating currency...</div>';
        document.body.appendChild(loader);
    }
    
    function hideLoadingIndicator() {
        const loader = document.getElementById('currency-switcher-loader');
        if (loader) {
            loader.remove();
        }
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