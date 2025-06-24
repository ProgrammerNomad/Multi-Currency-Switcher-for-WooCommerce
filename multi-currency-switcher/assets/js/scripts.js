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
        // Create or update the cookie
        document.cookie = "chosen_currency=" + currency + "; path=/; max-age=86400";
        
        // Reload the page to reflect the currency change
        window.location.reload();
    }

    const currencyDisplay = document.getElementById('current-currency');

    // Fetch geolocation-based currency
    fetch('/wp-admin/admin-ajax.php?action=get_geolocation_currency')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currencyDisplay.textContent = data.currency;
            } else {
                console.error('Failed to fetch geolocation currency:', data.message);
            }
        })
        .catch(error => {
            console.error('Error during geolocation currency fetch:', error);
        });
    
    const stickySwitcher = document.createElement('div');
    stickySwitcher.className = 'sticky-currency-switcher';
    stickySwitcher.innerHTML = `
        <label for="sticky-currency-selector">Currency:</label>
        <select id="sticky-currency-selector">
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="GBP">GBP</option>
            <option value="JPY">JPY</option>
        </select>
    `;
    document.body.appendChild(stickySwitcher);

    const stickyCurrencySelector = document.getElementById('sticky-currency-selector');
    stickyCurrencySelector.addEventListener('change', function() {
        const selectedCurrency = this.value;
        fetch('/wp-admin/admin-ajax.php?action=switch_currency&currency=' + selectedCurrency)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh the page to apply the new currency
                } else {
                    console.error('Currency switch failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Error during currency switch:', error);
            });
    });

    const productCurrencySelector = document.getElementById('product-currency-selector');
    const productPriceElement = document.querySelector('.woocommerce-Price-amount');

    if (productCurrencySelector && productPriceElement) {
        productCurrencySelector.addEventListener('change', function() {
            const selectedCurrency = this.value;
            const originalPrice = parseFloat(productPriceElement.dataset.originalPrice);

            fetch('/wp-admin/admin-ajax.php?action=get_exchange_rate&currency=' + selectedCurrency)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const exchangeRate = data.rate;
                        const newPrice = (originalPrice * exchangeRate).toFixed(2);
                        productPriceElement.textContent = `${newPrice} ${selectedCurrency}`;
                    } else {
                        console.error('Failed to fetch exchange rate:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error during exchange rate fetch:', error);
                });
        });
    }
});