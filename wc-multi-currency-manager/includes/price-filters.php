<?php
/**
 * Price conversion filters for WooCommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Convert prices throughout WooCommerce
 */
function wc_multi_currency_manager_apply_price_filters() {
    // Skip if WooCommerce isn't active
    if (!function_exists('WC') || !WC()) {
        return;
    }

    // Always apply currency formatting filters
    add_filter('woocommerce_currency', 'wc_multi_currency_manager_change_currency_code', 10);
    add_filter('woocommerce_currency_symbol', 'wc_multi_currency_manager_change_currency_symbol', 10, 2);
    add_filter('wc_price_args', 'wc_multi_currency_manager_price_format_args', 10);

    // Always apply price conversion filters - the individual functions will handle currency checking
    // Product prices - regular products
    add_filter('woocommerce_product_get_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_get_regular_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_get_sale_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    
    // Product prices - variations
    add_filter('woocommerce_product_variation_get_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_variation_get_regular_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_variation_get_sale_price', 'wc_multi_currency_manager_convert_raw_price', 10, 2);
    
    // Cart, checkout, and order prices
    add_filter('woocommerce_cart_product_subtotal', 'wc_multi_currency_manager_cart_product_subtotal', 10, 4);
    add_filter('woocommerce_cart_subtotal', 'wc_multi_currency_manager_cart_subtotal', 10, 3);
    add_filter('woocommerce_cart_total', 'wc_multi_currency_manager_price_html', 10);
    add_filter('woocommerce_calculated_total', 'wc_multi_currency_manager_calculated_total', 10, 2);
    
    // Mini cart
    add_filter('woocommerce_cart_item_price', 'wc_multi_currency_manager_cart_item_price', 10, 3);
    
    // Shipping and tax
    add_filter('woocommerce_package_rates', 'wc_multi_currency_manager_adjust_shipping_cost', 10, 2);
    
    // Coupons
    add_filter('woocommerce_coupon_get_amount', 'wc_multi_currency_manager_coupon_amount', 10, 2);
    
    // Product variations cache
    add_filter('woocommerce_get_variation_prices_hash', 'wc_multi_currency_manager_variation_prices_hash', 10, 3);

    // Mini cart handling
    add_filter('woocommerce_cart_contents_total', 'wc_multi_currency_manager_cart_contents_total', 10, 1);
    
    // This is critical for mini cart display
    add_action('woocommerce_before_mini_cart', 'wc_multi_currency_manager_before_mini_cart');
    add_action('woocommerce_after_mini_cart', 'wc_multi_currency_manager_after_mini_cart');
    
    // For updating mini cart totals
    add_filter('woocommerce_cart_item_subtotal', 'wc_multi_currency_manager_cart_item_subtotal', 10, 3);
}
// Hook to woocommerce_init instead of init, and run earlier
add_action('woocommerce_init', 'wc_multi_currency_manager_apply_price_filters', 5);

/**
 * Convert raw product prices
 */
function wc_multi_currency_manager_convert_raw_price($price, $product) {
    if (empty($price) || !is_numeric($price)) {
        return $price;
    }
    
    // Get current currency using improved detection
    $currency = wc_multi_currency_manager_get_current_currency();
    // Get the ACTUAL WooCommerce base currency, not the filtered one
    $base_currency = get_option('woocommerce_currency', 'USD');
    
    // Skip if using base currency
    if ($currency === $base_currency) {
        return $price;
    }
    
    // Try to get custom price for this product/currency
    $product_id = $product->get_id();
    $custom_price = get_post_meta($product_id, '_price_' . $currency, true);
    
    // If a custom price exists for this currency, use it
    if (!empty($custom_price) && is_numeric($custom_price)) {
        return $custom_price;
    }
    
    // Otherwise, convert the price using exchange rate
    // Get the ACTUAL WooCommerce base currency, not the filtered one
    $base_currency = get_option('woocommerce_currency', 'USD');
    $exchange_rate = wc_multi_currency_manager_get_exchange_rate($base_currency, $currency);
    $converted_price = floatval($price) * floatval($exchange_rate);
    
    return $converted_price;
}

/**
 * Add the current currency to variation price hash to prevent caching issues
 */
function wc_multi_currency_manager_variation_prices_hash($hash, $product, $for_display) {
    $currency = wc_multi_currency_manager_get_current_currency();
    $hash[] = 'currency_' . $currency;
    return $hash;
}

/**
 * Change the WooCommerce currency code
 */
function wc_multi_currency_manager_change_currency_code($currency) {
    $chosen_currency = wc_multi_currency_manager_get_current_currency();
    if (!empty($chosen_currency)) {
        return $chosen_currency;
    }
    return $currency;
}

/**
 * Change the currency symbol
 */
function wc_multi_currency_manager_change_currency_symbol($symbol, $currency) {
    $all_currencies = get_all_available_currencies();
    
    if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
        return $all_currencies[$currency]['symbol'];
    }
    
    return $symbol;
}

/**
 * Apply formatting for the current currency (decimal places, separators, etc.)
 */
function wc_multi_currency_manager_price_format_args($args) {
    $currency = $args['currency'];
    $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());
    
    if (isset($currency_settings[$currency])) {
        $settings = $currency_settings[$currency];
        
        // Update formatting args
        $args['decimals'] = isset($settings['decimals']) ? (int)$settings['decimals'] : $args['decimals'];
        $args['decimal_separator'] = isset($settings['decimal_sep']) ? $settings['decimal_sep'] : $args['decimal_separator'];
        $args['thousand_separator'] = isset($settings['thousand_sep']) ? $settings['thousand_sep'] : $args['thousand_separator'];
        
        // Set price format based on symbol position
        if (isset($settings['position'])) {
            $symbol = $args['currency_symbol'];
            
            switch ($settings['position']) {
                case 'left':
                    $args['price_format'] = '%1$s%2$s';
                    break;
                case 'right':
                    $args['price_format'] = '%2$s%1$s';
                    break;
                case 'left_space':
                    $args['price_format'] = '%1$s&nbsp;%2$s';
                    break;
                case 'right_space':
                    $args['price_format'] = '%2$s&nbsp;%1$s';
                    break;
            }
        }
    }
    
    return $args;
}

/**
 * Handle cart product subtotal display
 */
function wc_multi_currency_manager_cart_product_subtotal($subtotal, $product, $quantity, $cart) {
    // The subtotal is already formatted, we just need to ensure it uses our currency
    return $subtotal;
}

/**
 * Handle price HTML (used for cart subtotal and total)
 */
function wc_multi_currency_manager_price_html($html) {
    // The HTML is already formatted with proper currency
    return $html;
}

/**
 * Handle cart item price
 */
function wc_multi_currency_manager_cart_item_price($price, $cart_item, $cart_item_key) {
    // The price is already formatted with proper currency
    return $price;
}

/**
 * Convert the calculated total
 */
function wc_multi_currency_manager_calculated_total($total, $cart) {
    if (empty($total)) {
        return $total;
    }
    
    // Currency is already handled by the other filters
    return $total;
}

/**
 * Adjust shipping cost based on currency
 */
function wc_multi_currency_manager_adjust_shipping_cost($package_rates, $package) {
    $currency = wc_multi_currency_manager_get_current_currency();
    // Get the ACTUAL WooCommerce base currency, not the filtered one
    $base_currency = get_option('woocommerce_currency', 'USD');
    
    // Skip if using base currency
    if ($currency === $base_currency) {
        return $package_rates;
    }
    
    $exchange_rate = wc_multi_currency_manager_get_exchange_rate($currency);
    
    foreach ($package_rates as $id => $rate) {
        // Convert cost
        if (is_numeric($rate->cost)) {
            $rate->cost = floatval($rate->cost) * floatval($exchange_rate);
        }
        
        // Convert taxes
        if (!empty($rate->taxes) && is_array($rate->taxes)) {
            foreach ($rate->taxes as $tax_id => $tax) {
                if (is_numeric($tax)) {
                    $rate->taxes[$tax_id] = floatval($tax) * floatval($exchange_rate);
                }
            }
        }
    }
    
    return $package_rates;
}

/**
 * Convert coupon amount
 */
function wc_multi_currency_manager_coupon_amount($amount, $coupon) {
    if (empty($amount)) {
        return $amount;
    }
    
    $currency = wc_multi_currency_manager_get_current_currency();
    // Get the ACTUAL WooCommerce base currency, not the filtered one
    $base_currency = get_option('woocommerce_currency', 'USD');
    
    // Skip if using base currency
    if ($currency === $base_currency) {
        return $amount;
    }
    
    // Check for currency-specific coupon amount
    $coupon_id = $coupon->get_id();
    $currency_amount = get_post_meta($coupon_id, '_coupon_amount_' . $currency, true);
    
    if (!empty($currency_amount) && is_numeric($currency_amount)) {
        return $currency_amount;
    }
    
    // Otherwise convert using exchange rate
    $exchange_rate = wc_multi_currency_manager_get_exchange_rate($currency);
    return floatval($amount) * floatval($exchange_rate);
}

/**
 * Convert mini cart fragments to display correct currency
 */
function wc_multi_currency_manager_mini_cart_fragments($fragments) {
    // Only attempt to modify fragments if cart is loaded
    if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
        // Get current currency to add to fragment cache keys
        if (WC()->session) {
            $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
            
            // Force WooCommerce to regenerate all fragments when currency changes
            foreach ($fragments as $key => $fragment) {
                // Add currency to the fragment key to ensure proper caching
                $new_key = $key . '_' . $currency;
                $fragments[$new_key] = $fragment;
                
                // Keep the original key for compatibility
                $fragments[$key] = $fragment;
            }
            
            // If using Storefront theme, update the specific cart fragment
            if (isset($fragments['a.cart-contents'])) {
                ob_start();
                ?>
                <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e('View your shopping cart', 'storefront'); ?>">
                    <?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?>
                    <span class="count"><?php echo wp_kses_data(sprintf(_n('%d item', '%d items', WC()->cart->get_cart_contents_count(), 'storefront'), WC()->cart->get_cart_contents_count())); ?></span>
                </a>
                <?php
                $fragments['a.cart-contents'] = ob_get_clean();
            }
        }
    }
    
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'wc_multi_currency_manager_mini_cart_fragments', 999);

/**
 * Handle mini cart price display
 */
function wc_multi_currency_manager_mini_cart_price_filter() {
    // Filters for mini cart prices
    add_filter('woocommerce_cart_item_price', 'wc_multi_currency_manager_filter_item_price', 10, 3);
    add_filter('woocommerce_widget_cart_item_quantity', 'wc_multi_currency_manager_filter_widget_cart_item_quantity', 10, 3);
}
add_action('wp_loaded', 'wc_multi_currency_manager_mini_cart_price_filter');

/**
 * Filter mini cart item price
 */
function wc_multi_currency_manager_filter_item_price($price_html, $cart_item, $cart_item_key) {
    // Price is already converted by WooCommerce, we just need to ensure it's properly displayed
    return $price_html;
}

/**
 * Filter mini cart item quantity text with correct currency
 */
function wc_multi_currency_manager_filter_widget_cart_item_quantity($quantity_html, $cart_item, $cart_item_key) {
    // Quantity already includes price that has been converted, we just need to ensure it's properly displayed
    return $quantity_html;
}

/**
 * Function to run before mini cart is displayed
 */
function wc_multi_currency_manager_before_mini_cart() {
    // Prepare the mini cart for display with the correct currency
    WC()->cart->calculate_totals();
}

/**
 * Function to run after mini cart is displayed
 */
function wc_multi_currency_manager_after_mini_cart() {
    // Cleanup after mini cart display if needed
}

/**
 * Filter cart contents total
 */
function wc_multi_currency_manager_cart_contents_total($total) {
    // Total already converted, just return it
    return $total;
}

/**
 * Filter cart item subtotal
 */
function wc_multi_currency_manager_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
    // Subtotal already converted, just return it
    return $subtotal;
}

/**
 * Filter the cart subtotal display
 * 
 * @param string $cart_subtotal The cart subtotal HTML
 * @param bool $compound Whether the subtotal includes compound taxes
 * @param WC_Cart $cart The cart object
 * @return string The filtered cart subtotal
 */
function wc_multi_currency_manager_cart_subtotal($cart_subtotal, $compound = false, $cart = null) {
    // The cart subtotal is already converted by WooCommerce core
    // We just need to make sure it's displayed correctly
    return $cart_subtotal;
}

// Add this function to ensure cart items are updated with the correct currency
function wc_multi_currency_manager_update_cart_items() {
    // Skip if not in a cart context or WooCommerce isn't fully loaded
    if (!function_exists('WC') || !WC()->cart || !WC()->session || !is_object(WC()->cart) || !method_exists(WC()->cart, 'get_cart')) {
        return;
    }
    
    try {
        $currency = WC()->session->get('chosen_currency', get_option('woocommerce_currency', 'USD'));
        // Get the ACTUAL WooCommerce base currency, not the filtered one
        $base_currency = get_option('woocommerce_currency', 'USD');
        
        // Skip if using base currency
        if ($currency === $base_currency) {
            return;
        }
        
        // Get the cart - with extra checks to prevent errors
        $cart = WC()->cart->get_cart();
        
        if (empty($cart) || !is_array($cart)) {
            return;
        }
        
        // Flag to track if we need to recalculate
        $needs_recalculation = false;
        
        foreach ($cart as $cart_item_key => $cart_item) {
            // Skip if the cart item is invalid
            if (!isset($cart_item['data']) || !is_object($cart_item['data']) || !method_exists($cart_item['data'], 'get_price')) {
                continue;
            }
            
            $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
            $variation_id = isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : 0;
            
            // Skip if product ID is invalid
            if (empty($product_id)) {
                continue;
            }
            
            // Get the correct product ID for price lookup
            $price_product_id = $variation_id ? $variation_id : $product_id;
            
            // Check if there's a custom price for this product in the current currency
            $custom_price = get_post_meta($price_product_id, '_price_' . $currency, true);
            
            if (!empty($custom_price) && is_numeric($custom_price)) {
                // Use custom price if set
                if ($cart_item['data']->get_price() != $custom_price) {
                    $cart_item['data']->set_price($custom_price);
                    $needs_recalculation = true;
                }
            } else {
                // Otherwise use exchange rate conversion
                $exchange_rate = wc_multi_currency_manager_get_exchange_rate($currency);
                $base_price = get_post_meta($price_product_id, '_price', true);
                
                if (!empty($base_price) && is_numeric($base_price)) {
                    $converted_price = floatval($base_price) * floatval($exchange_rate);
                    
                    if ($cart_item['data']->get_price() != $converted_price) {
                        $cart_item['data']->set_price($converted_price);
                        $needs_recalculation = true;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Log any errors but don't let them break the site
        error_log('Error in wc_multi_currency_manager_update_cart_items: ' . $e->getMessage());
    }
}
remove_action('woocommerce_before_calculate_totals', 'wc_multi_currency_manager_update_cart_items', 20);
add_action('woocommerce_before_calculate_totals', 'wc_multi_currency_manager_update_cart_items', 20);
remove_action('woocommerce_before_mini_cart', 'wc_multi_currency_manager_update_cart_items', 10);
add_action('woocommerce_before_mini_cart', 'wc_multi_currency_manager_update_cart_items', 10);

/**
 * Ensure the correct currency is displayed on order pages
 */
function wc_multi_currency_manager_order_currency($currency) {
    global $wp;
    
    // Check if we're on an order-received page
    if (is_checkout() && !empty($wp->query_vars['order-received'])) {
        $order_id = absint($wp->query_vars['order-received']);
        $order = wc_get_order($order_id);
        
        if ($order && is_a($order, 'WC_Order')) {
            // Return the currency used for this specific order
            return $order->get_currency();
        }
    }
    
    // Otherwise return the current currency
    if (function_exists('WC') && WC()->session) {
        $chosen_currency = WC()->session->get('chosen_currency', '');
        if (!empty($chosen_currency)) {
            return $chosen_currency;
        }
    }
    
    // Always return a value, which was missing before
    return $currency;
}
// Add this filter to ensure the function is used
add_filter('woocommerce_currency', 'wc_multi_currency_manager_order_currency', 999);

/**
 * Save the current currency with new orders
 */
function wc_multi_currency_manager_update_order_currency($order_id) {
    if (function_exists('WC') && WC()->session) {
        $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
        
        if (!empty($currency)) {
            // Get the order
            $order = wc_get_order($order_id);
            if ($order) {
                // Set the order currency
                $order->set_currency($currency);
                $order->save();
            }
        }
    }
}
add_action('woocommerce_checkout_update_order_meta', 'wc_multi_currency_manager_update_order_currency', 10, 1);

/**
 * Ensure order pages use the correct currency symbol
 */
function wc_multi_currency_manager_order_page_currency_symbol($symbol, $currency) {
    global $wp;
    
    // Check if we're on an order-received page
    if (is_checkout() && !empty($wp->query_vars['order-received'])) {
        $order_id = absint($wp->query_vars['order-received']);
        $order = wc_get_order($order_id);
        
        if ($order && is_a($order, 'WC_Order')) {
            // Get the currency for this order
            $order_currency = $order->get_currency();
            
            // If the requested currency matches the order currency, get its symbol
            if ($currency === $order_currency) {
                $all_currencies = get_all_available_currencies();
                if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
                    return $all_currencies[$currency]['symbol'];
                }
            }
        }
    }
    
    // For other pages, use the standard currency symbol logic
    $all_currencies = get_all_available_currencies();
    if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
        return $all_currencies[$currency]['symbol'];
    }
    
    return $symbol;
}

// Replace the existing currency symbol filter with this more specific one
remove_filter('woocommerce_currency_symbol', 'wc_multi_currency_manager_change_currency_symbol', 10);
add_filter('woocommerce_currency_symbol', 'wc_multi_currency_manager_order_page_currency_symbol', 10, 2);

/**
 * Apply correct formatting for order pages
 */
function wc_multi_currency_manager_order_price_format($args) {
    global $wp;
    
    // Check if we're on an order-received page
    if (is_checkout() && !empty($wp->query_vars['order-received'])) {
        $order_id = absint($wp->query_vars['order-received']);
        $order = wc_get_order($order_id);
        
        if ($order && is_a($order, 'WC_Order')) {
            // Get the currency for this order
            $currency = $order->get_currency();
            $args['currency'] = $currency;
            
            // Get formatting settings for this currency
            $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());
            
            if (isset($currency_settings[$currency])) {
                $settings = $currency_settings[$currency];
                
                // Apply the currency-specific formatting
                $args['decimals'] = isset($settings['decimals']) ? (int)$settings['decimals'] : $args['decimals'];
                $args['decimal_separator'] = isset($settings['decimal_sep']) ? $settings['decimal_sep'] : $args['decimal_separator'];
                $args['thousand_separator'] = isset($settings['thousand_sep']) ? $settings['thousand_sep'] : $args['thousand_separator'];
                
                // Set price format based on symbol position
                if (isset($settings['position']) && isset($args['currency_symbol'])) {
                    switch ($settings['position']) {
                        case 'left':
                            $args['price_format'] = '%1$s%2$s';
                            break;
                        case 'right':
                            $args['price_format'] = '%2$s%1$s';
                            break;
                        case 'left_space':
                            $args['price_format'] = '%1$s&nbsp;%2$s';
                            break;
                        case 'right_space':
                            $args['price_format'] = '%2$s&nbsp;%1$s';
                            break;
                    }
                }
            }
        }
    }
    
    return $args;
}
add_filter('wc_price_args', 'wc_multi_currency_manager_order_price_format', 999);

/**
 * Ensure price formatting is correct on thank you page
 */
function wc_multi_currency_manager_thankyou_page_formatting($formatted_price, $price, $args, $unformatted_price) {
    global $wp;
    
    // Check if we're on an order-received page
    if (is_checkout() && !empty($wp->query_vars['order-received'])) {
        $order_id = absint($wp->query_vars['order-received']);
        $order = wc_get_order($order_id);
        
        if ($order && is_a($order, 'WC_Order')) {
            $currency = $order->get_currency();
            $all_currencies = get_all_available_currencies();
            
            // Ensure currency symbol is correct
            if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
                $symbol = $all_currencies[$currency]['symbol'];
                
                // Get formatting settings
                $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());
                
                if (isset($currency_settings[$currency])) {
                    $settings = $currency_settings[$currency];
                    
                    // Format the price manually
                    $decimals = isset($settings['decimals']) ? (int)$settings['decimals'] : 2;
                    $decimal_sep = isset($settings['decimal_sep']) ? $settings['decimal_sep'] : '.';
                    $thousand_sep = isset($settings['thousand_sep']) ? $settings['thousand_sep'] : ',';
                    
                    $formatted_number = number_format($price, $decimals, $decimal_sep, $thousand_sep);
                    
                    // Apply the position
                    $position = isset($settings['position']) ? $settings['position'] : 'left';
                    
                    switch ($position) {
                        case 'left':
                            return $symbol . $formatted_number;
                        case 'right':
                            return $formatted_number . $symbol;
                        case 'left_space':
                            return $symbol . ' ' . $formatted_number;
                        case 'right_space':
                            return $formatted_number . ' ' . $symbol;
                        default:
                            return $symbol . $formatted_number;
                    }
                }
            }
        }
    }
    
    return $formatted_price;
}
add_filter('wc_price', 'wc_multi_currency_manager_thankyou_page_formatting', 999, 4);

/**
 * Add script to prevent currency switching on order received page
 */
function wc_multi_currency_manager_order_received_scripts() {
    global $wp;
    
    // Only add on order received page
    if (is_checkout() && !empty($wp->query_vars['order-received'])) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Disable all currency switchers on order received page
            const currencySelectors = document.querySelectorAll('#currency-selector, #sticky-currency-selector, #product-currency-selector, #currency-switcher');
            currencySelectors.forEach(function(selector) {
                if (selector) {
                    selector.disabled = true;
                }
            });
            
            // Hide sticky currency switcher if present
            const stickySwitcher = document.querySelector('.sticky-currency-switcher');
            if (stickySwitcher) {
                stickySwitcher.style.display = 'none';
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'wc_multi_currency_manager_order_received_scripts');
