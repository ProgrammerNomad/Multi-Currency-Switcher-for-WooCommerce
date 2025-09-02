/**
 * Cart Currency Management JavaScript
 * Handles cart and mini-cart updates when currency changes
 */

(function($) {
    'use strict';

    var cartCurrency = {
        
        init: function() {
            this.bindEvents();
            this.monitorCurrencyChanges();
            this.updateCartDisplay();
        },
        
        bindEvents: function() {
            // Monitor cart updates
            $(document.body).on('updated_cart_totals', this.updateCartDisplay.bind(this));
            $(document.body).on('added_to_cart', this.updateMiniCart.bind(this));
            $(document.body).on('removed_from_cart', this.updateMiniCart.bind(this));
            
            // Monitor currency switcher changes
            $(document).on('currency_switched', this.handleCurrencySwitch.bind(this));
            
            // Handle cart quantity changes
            $('.cart').on('change', '.qty', this.handleQuantityChange.bind(this));
        },
        
        updateCartDisplay: function() {
            // Update currency symbols and formatting
            this.updateCurrencyDisplay();
            
            // Update mini cart if present
            this.updateMiniCart();
        },
        
        updateCurrencyDisplay: function() {
            var currentCurrency = this.getCurrentCurrency();
            
            if (currentCurrency) {
                // Update all currency symbols on the page
                $('.woocommerce-Price-currencySymbol').each(function() {
                    var $symbol = $(this);
                    // Currency symbol will be updated by PHP filters
                });
                
                // Update currency indicators
                $('[data-currency]').attr('data-currency', currentCurrency);
            }
        },
        
        updateMiniCart: function() {
            if (typeof wcMultiCurrencyCart === 'undefined') {
                return;
            }
            
            var data = {
                action: 'update_mini_cart_currency',
                security: wcMultiCurrencyCart.nonce
            };
            
            $.ajax({
                url: wcMultiCurrencyCart.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success && response.data.fragments) {
                        // Update fragments
                        $.each(response.data.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                        
                        // Trigger update event
                        $(document.body).trigger('updated_wc_div');
                    }
                },
                error: function() {
                    console.log('Mini cart update failed');
                }
            });
        },
        
        handleCurrencySwitch: function(event, currency) {
            // Show loading indicator
            this.showLoadingIndicator();
            
            // Update cart with new currency
            this.updateCartPrices(currency);
        },
        
        updateCartPrices: function(currency) {
            // Trigger cart update to recalculate prices
            $('body').trigger('update_checkout');
            
            // Update mini cart
            this.updateMiniCart();
            
            // Hide loading indicator after a delay
            setTimeout(function() {
                cartCurrency.hideLoadingIndicator();
            }, 1000);
        },
        
        handleQuantityChange: function(event) {
            var $input = $(event.target);
            var $form = $input.closest('form');
            
            // Small delay to allow WooCommerce to process the change
            setTimeout(function() {
                cartCurrency.updateMiniCart();
            }, 500);
        },
        
        monitorCurrencyChanges: function() {
            // Monitor for currency changes via MutationObserver
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'data-current-currency') {
                            cartCurrency.handleCurrencySwitch(null, mutation.target.getAttribute('data-current-currency'));
                        }
                    });
                });
                
                // Observe currency indicators
                var currencyElements = document.querySelectorAll('[data-current-currency]');
                currencyElements.forEach(function(element) {
                    observer.observe(element, {
                        attributes: true,
                        attributeFilter: ['data-current-currency']
                    });
                });
            }
        },
        
        showLoadingIndicator: function() {
            // Remove existing indicators
            $('.cart-currency-loading').remove();
            
            // Add loading indicator
            var $indicator = $('<div class="cart-currency-loading">Updating prices...</div>');
            $indicator.css({
                'position': 'fixed',
                'top': '50%',
                'left': '50%',
                'transform': 'translate(-50%, -50%)',
                'background': '#fff',
                'padding': '20px',
                'border': '1px solid #ddd',
                'border-radius': '4px',
                'box-shadow': '0 2px 10px rgba(0,0,0,0.1)',
                'z-index': '9999',
                'font-weight': 'bold'
            });
            
            $('body').append($indicator);
        },
        
        hideLoadingIndicator: function() {
            $('.cart-currency-loading').fadeOut(function() {
                $(this).remove();
            });
        },
        
        getCurrentCurrency: function() {
            // Try to get from data attribute
            var currency = $('[data-current-currency]').attr('data-current-currency');
            
            if (!currency && typeof wcMultiCurrencyCart !== 'undefined') {
                currency = wcMultiCurrencyCart.current_currency;
            }
            
            return currency;
        }
    };
    
    // Enhanced mini cart functionality
    var miniCartEnhancer = {
        
        init: function() {
            this.enhanceMiniCart();
            this.bindMiniCartEvents();
        },
        
        enhanceMiniCart: function() {
            // Add currency indicator to mini cart
            $('.widget_shopping_cart').each(function() {
                var $widget = $(this);
                
                if (!$widget.find('.mini-cart-currency').length) {
                    var currency = cartCurrency.getCurrentCurrency();
                    if (currency) {
                        var $indicator = $('<div class="mini-cart-currency">Currency: ' + currency + '</div>');
                        $indicator.css({
                            'font-size': '11px',
                            'color': '#666',
                            'text-align': 'center',
                            'padding': '5px',
                            'border-top': '1px solid #eee'
                        });
                        
                        $widget.find('.woocommerce-mini-cart').after($indicator);
                    }
                }
            });
        },
        
        bindMiniCartEvents: function() {
            // Monitor mini cart updates
            $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
                miniCartEnhancer.enhanceMiniCart();
            });
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('body').hasClass('woocommerce-cart') || $('.widget_shopping_cart').length) {
            cartCurrency.init();
            miniCartEnhancer.init();
        }
    });
    
    // Initialize on cart/checkout pages
    $(document.body).on('updated_cart_totals updated_checkout', function() {
        cartCurrency.updateCartDisplay();
    });

})(jQuery);

// Additional cart enhancements for currency display
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Enhanced cart total display
    function updateCartTotalDisplay() {
        var totalElements = document.querySelectorAll('.cart-subtotal .amount, .order-total .amount');
        
        totalElements.forEach(function(element) {
            // Add currency class for styling
            if (!element.classList.contains('currency-amount')) {
                element.classList.add('currency-amount');
            }
        });
    }
    
    // Monitor for dynamic content changes
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                updateCartTotalDisplay();
            }
        });
    });
    
    // Start observing cart containers
    var cartContainers = document.querySelectorAll('.woocommerce-cart, .cart_totals, .widget_shopping_cart');
    cartContainers.forEach(function(container) {
        observer.observe(container, {
            childList: true,
            subtree: true
        });
    });
    
    // Initial update
    updateCartTotalDisplay();
});
