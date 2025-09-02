/**
 * Checkout Currency Management JavaScript
 * Handles currency validation and locking during checkout
 */

(function($) {
    'use strict';

    var checkoutCurrency = {
        
        init: function() {
            this.bindEvents();
            this.validateCurrency();
            this.lockCurrencySwitchers();
            this.monitorCheckoutUpdates();
        },
        
        bindEvents: function() {
            // Monitor for checkout form updates
            $(document.body).on('update_checkout', this.validateCurrency.bind(this));
            
            // Monitor for payment method changes
            $(document.body).on('payment_method_selected', this.validateCurrency.bind(this));
            
            // Prevent currency switcher interactions
            $('.currency-switcher select, #currency-selector, #sticky-currency-selector').on('click change', function(e) {
                e.preventDefault();
                e.stopPropagation();
                checkoutCurrency.showCurrencyLockedMessage();
                return false;
            });
        },
        
        validateCurrency: function() {
            if (typeof wcMultiCurrencyCheckout === 'undefined') {
                return;
            }
            
            var data = {
                action: 'validate_checkout_currency',
                security: wcMultiCurrencyCheckout.nonce,
                currency: wcMultiCurrencyCheckout.current_currency
            };
            
            $.ajax({
                url: wcMultiCurrencyCheckout.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (!response.success) {
                        checkoutCurrency.handleCurrencyError(response.data.message);
                    }
                },
                error: function() {
                    console.log('Currency validation request failed');
                }
            });
        },
        
        lockCurrencySwitchers: function() {
            // Disable all currency switchers
            $('.currency-switcher select, #currency-selector, #sticky-currency-selector, [id*="currency"]').each(function() {
                var $element = $(this);
                
                if ($element.is('select')) {
                    $element.prop('disabled', true);
                    $element.css({
                        'opacity': '0.6',
                        'cursor': 'not-allowed',
                        'pointer-events': 'none'
                    });
                }
            });
            
            // Hide sticky currency switcher
            $('.sticky-currency-switcher').css({
                'opacity': '0.6',
                'pointer-events': 'none'
            });
            
            // Add locked indicator
            this.addLockedIndicator();
        },
        
        addLockedIndicator: function() {
            $('.currency-switcher, #sticky-currency-selector').each(function() {
                var $switcher = $(this);
                
                if (!$switcher.find('.currency-locked-indicator').length) {
                    var $indicator = $('<span class="currency-locked-indicator">🔒</span>');
                    $indicator.css({
                        'margin-left': '5px',
                        'color': '#999',
                        'font-size': '12px'
                    });
                    
                    $switcher.append($indicator);
                }
            });
        },
        
        showCurrencyLockedMessage: function() {
            // Remove existing messages
            $('.currency-locked-message').remove();
            
            var message = $('<div class="woocommerce-message woocommerce-message--info currency-locked-message">' +
                '<strong>Currency Locked:</strong> Currency cannot be changed during checkout. ' +
                '<a href="javascript:history.back()">Go back to shopping</a> if you need to change currency.' +
                '</div>');
            
            message.css({
                'margin': '10px 0',
                'padding': '10px',
                'background': '#e7f3ff',
                'border-left': '4px solid #0073aa',
                'color': '#0073aa'
            });
            
            $('.woocommerce-checkout').prepend(message);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                message.fadeOut();
            }, 5000);
        },
        
        handleCurrencyError: function(message) {
            // Show error message
            $('.woocommerce-error, .woocommerce-message').remove();
            
            var errorMessage = $('<div class="woocommerce-error">' + message + '</div>');
            $('.woocommerce-checkout').prepend(errorMessage);
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.woocommerce-checkout').offset().top - 100
            }, 500);
            
            // Disable checkout button
            $('#place_order').prop('disabled', true).text('Please refresh the page');
        },
        
        monitorCheckoutUpdates: function() {
            var self = this;
            
            // Monitor for dynamic content changes
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Re-lock any new currency switchers that might have appeared
                        self.lockCurrencySwitchers();
                    }
                });
            });
            
            // Start observing
            var checkoutForm = document.querySelector('.woocommerce-checkout');
            if (checkoutForm) {
                observer.observe(checkoutForm, {
                    childList: true,
                    subtree: true
                });
            }
        }
    };
    
    // Initialize when checkout page loads
    $(document).ready(function() {
        if ($('body').hasClass('woocommerce-checkout')) {
            checkoutCurrency.init();
        }
    });
    
    // Also initialize on checkout form updates
    $(document.body).on('updated_checkout', function() {
        if ($('body').hasClass('woocommerce-checkout')) {
            checkoutCurrency.lockCurrencySwitchers();
        }
    });

})(jQuery);

// Additional checkout validation
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Prevent form submission if currency has changed
    var checkoutForm = document.querySelector('.woocommerce-checkout');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            var currencyField = document.querySelector('input[name="checkout_currency"]');
            
            if (currencyField && typeof wcMultiCurrencyCheckout !== 'undefined') {
                if (currencyField.value !== wcMultiCurrencyCheckout.current_currency) {
                    e.preventDefault();
                    alert(wcMultiCurrencyCheckout.messages.currency_changed);
                    return false;
                }
            }
        });
    }
    
    // Monitor for page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && typeof wcMultiCurrencyCheckout !== 'undefined') {
            // Page became visible again - validate currency
            setTimeout(function() {
                if (typeof checkoutCurrency !== 'undefined' && checkoutCurrency.validateCurrency) {
                    checkoutCurrency.validateCurrency();
                }
            }, 1000);
        }
    });
});
