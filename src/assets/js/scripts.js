document.addEventListener('DOMContentLoaded', function() {
    const currencySwitcher = document.getElementById('currency-switcher');
    const currencyDisplay = document.getElementById('current-currency');
    
    currencySwitcher.addEventListener('change', function() {
        const selectedCurrency = this.value;
        updateCurrency(selectedCurrency);
    });

    function updateCurrency(currency) {
        // Make an AJAX request to update the currency on the server
        fetch('/wp-admin/admin-ajax.php?action=switch_currency&currency=' + currency)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currencyDisplay.textContent = data.new_currency;
                    // Optionally, refresh the page or update prices dynamically
                    location.reload();
                } else {
                    console.error('Currency switch failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Error during currency switch:', error);
            });
    }

    const currencySelector = document.getElementById('currency-selector');
    currencySelector.addEventListener('change', function () {
        alert('Currency switched to: ' + this.value);
    });
});