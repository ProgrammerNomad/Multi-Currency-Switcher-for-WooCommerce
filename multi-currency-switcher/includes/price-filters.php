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
function multi_currency_switcher_apply_price_filters() {
    // Skip if WooCommerce isn't active or session not available
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    // Get the current and base currencies
    $current_currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $base_currency = get_woocommerce_currency();

    // Skip if using base currency
    if ($current_currency === $base_currency) {
        return;
    }

    // Product prices - regular products
    add_filter('woocommerce_product_get_price', 'multi_currency_switcher_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_get_regular_price', 'multi_currency_switcher_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_get_sale_price', 'multi_currency_switcher_convert_raw_price', 10, 2);
    
    // Product prices - variations
    add_filter('woocommerce_product_variation_get_price', 'multi_currency_switcher_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_variation_get_regular_price', 'multi_currency_switcher_convert_raw_price', 10, 2);
    add_filter('woocommerce_product_variation_get_sale_price', 'multi_currency_switcher_convert_raw_price', 10, 2);

    // Change currency symbol and formatting
    add_filter('woocommerce_currency', 'multi_currency_switcher_change_currency_code', 10);
    add_filter('woocommerce_currency_symbol', 'multi_currency_switcher_change_currency_symbol', 10, 2);
    add_filter('wc_price_args', 'multi_currency_switcher_price_format_args', 10);
    
    // Cart, checkout, and order prices
    add_filter('woocommerce_cart_product_subtotal', 'multi_currency_switcher_cart_product_subtotal', 10, 4);
    add_filter('woocommerce_cart_subtotal', 'multi_currency_switcher_cart_subtotal', 10, 3);
    add_filter('woocommerce_cart_total', 'multi_currency_switcher_price_html', 10);
    add_filter('woocommerce_calculated_total', 'multi_currency_switcher_calculated_total', 10, 2);
    
    // Mini cart
    add_filter('woocommerce_cart_item_price', 'multi_currency_switcher_cart_item_price', 10, 3);
    
    // Shipping and tax
    add_filter('woocommerce_package_rates', 'multi_currency_switcher_adjust_shipping_cost', 10, 2);
    
    // Coupons
    add_filter('woocommerce_coupon_get_amount', 'multi_currency_switcher_coupon_amount', 10, 2);
    
    // Product variations cache
    add_filter('woocommerce_get_variation_prices_hash', 'multi_currency_switcher_variation_prices_hash', 10, 3);

    // Mini cart handling
    add_filter('woocommerce_cart_contents_total', 'multi_currency_switcher_cart_contents_total', 10, 1);
    
    // This is critical for mini cart display
    add_action('woocommerce_before_mini_cart', 'multi_currency_switcher_before_mini_cart');
    add_action('woocommerce_after_mini_cart', 'multi_currency_switcher_after_mini_cart');
    
    // For updating mini cart totals
    add_filter('woocommerce_cart_item_subtotal', 'multi_currency_switcher_cart_item_subtotal', 10, 3);
}
add_action('init', 'multi_currency_switcher_apply_price_filters', 20);

/**
 * Convert raw product prices
 */
function multi_currency_switcher_convert_raw_price($price, $product) {
    if (empty($price) || !is_numeric($price)) {
        return $price;
    }
    
    // Get current currency
    $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $base_currency = get_woocommerce_currency();
    
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
    $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
    return floatval($price) * floatval($exchange_rate);
}

/**
 * Add the current currency to variation price hash to prevent caching issues
 */
function multi_currency_switcher_variation_prices_hash($hash, $product, $for_display) {
    $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $hash[] = 'currency_' . $currency;
    return $hash;
}

/**
 * Change the WooCommerce currency code
 */
function multi_currency_switcher_change_currency_code($currency) {
    if (function_exists('WC') && WC()->session) {
        $chosen_currency = WC()->session->get('chosen_currency', '');
        if (!empty($chosen_currency)) {
            return $chosen_currency;
        }
    }
    return $currency;
}

/**
 * Change the currency symbol
 */
function multi_currency_switcher_change_currency_symbol($symbol, $currency) {
    $all_currencies = get_all_available_currencies();
    
    if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
        return $all_currencies[$currency]['symbol'];
    }
    
    return $symbol;
}

/**
 * Apply formatting for the current currency (decimal places, separators, etc.)
 */
function multi_currency_switcher_price_format_args($args) {
    // Fix for undefined array key "currency_symbol"
    if (!isset($args['currency_symbol'])) {
        $args['currency_symbol'] = '$'; // Default fallback symbol
    }
    
    $currency = $args['currency'];
    $currency_settings = get_option('multi_currency_switcher_currency_settings', array());
    
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
function multi_currency_switcher_cart_product_subtotal($subtotal, $product, $quantity, $cart) {
    // The subtotal is already formatted, we just need to ensure it uses our currency
    return $subtotal;
}

/**
 * Handle price HTML (used for cart subtotal and total)
 */
function multi_currency_switcher_price_html($html) {
    // The HTML is already formatted with proper currency
    return $html;
}

/**
 * Handle cart item price
 */
function multi_currency_switcher_cart_item_price($price, $cart_item, $cart_item_key) {
    // The price is already formatted with proper currency
    return $price;
}

/**
 * Convert the calculated total
 */
function multi_currency_switcher_calculated_total($total, $cart) {
    if (empty($total)) {
        return $total;
    }
    
    // Currency is already handled by the other filters
    return $total;
}

/**
 * Adjust shipping cost based on currency
 */
function multi_currency_switcher_adjust_shipping_cost($package_rates, $package) {
    $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $base_currency = get_woocommerce_currency();
    
    // Skip if using base currency
    if ($currency === $base_currency) {
        return $package_rates;
    }
    
    $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
    
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
function multi_currency_switcher_coupon_amount($amount, $coupon) {
    if (empty($amount)) {
        return $amount;
    }
    
    $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $base_currency = get_woocommerce_currency();
    
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
    $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
    return floatval($amount) * floatval($exchange_rate);
}

/**
 * Convert mini cart fragments to display correct currency
 */
function multi_currency_switcher_mini_cart_fragments($fragments) {
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
add_filter('woocommerce_add_to_cart_fragments', 'multi_currency_switcher_mini_cart_fragments', 999);

/**
 * Handle mini cart price display
 */
function multi_currency_switcher_mini_cart_price_filter() {
    // Filters for mini cart prices
    add_filter('woocommerce_cart_item_price', 'multi_currency_switcher_filter_item_price', 10, 3);
    add_filter('woocommerce_widget_cart_item_quantity', 'multi_currency_switcher_filter_widget_cart_item_quantity', 10, 3);
}
add_action('wp_loaded', 'multi_currency_switcher_mini_cart_price_filter');

/**
 * Filter mini cart item price
 */
function multi_currency_switcher_filter_item_price($price_html, $cart_item, $cart_item_key) {
    // Price is already converted by WooCommerce, we just need to ensure it's properly displayed
    return $price_html;
}

/**
 * Filter mini cart item quantity text with correct currency
 */
function multi_currency_switcher_filter_widget_cart_item_quantity($quantity_html, $cart_item, $cart_item_key) {
    // Quantity already includes price that has been converted, we just need to ensure it's properly displayed
    return $quantity_html;
}

/**
 * Function to run before mini cart is displayed
 */
function multi_currency_switcher_before_mini_cart() {
    // Prepare the mini cart for display with the correct currency
    WC()->cart->calculate_totals();
}

/**
 * Function to run after mini cart is displayed
 */
function multi_currency_switcher_after_mini_cart() {
    // Cleanup after mini cart display if needed
}

/**
 * Filter cart contents total
 */
function multi_currency_switcher_cart_contents_total($total) {
    // Total already converted, just return it
    return $total;
}

/**
 * Filter cart item subtotal
 */
function multi_currency_switcher_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
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
function multi_currency_switcher_cart_subtotal($cart_subtotal, $compound = false, $cart = null) {
    // The cart subtotal is already converted by WooCommerce core
    // We just need to make sure it's displayed correctly
    return $cart_subtotal;
}

// Add this function to ensure cart items are updated with the correct currency
function multi_currency_switcher_update_cart_items() {
    // Skip if not in a cart context or WooCommerce isn't fully loaded
    if (!function_exists('WC') || !WC()->cart || !WC()->session || !is_object(WC()->cart) || !method_exists(WC()->cart, 'get_cart')) {
        return;
    }
    
    try {
        $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
        $base_currency = get_woocommerce_currency();
        
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
                $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
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
        error_log('Error in multi_currency_switcher_update_cart_items: ' . $e->getMessage());
    }
}
remove_action('woocommerce_before_calculate_totals', 'multi_currency_switcher_update_cart_items', 20);
add_action('woocommerce_before_calculate_totals', 'multi_currency_switcher_update_cart_items', 20);
remove_action('woocommerce_before_mini_cart', 'multi_currency_switcher_update_cart_items', 10);
add_action('woocommerce_before_mini_cart', 'multi_currency_switcher_update_cart_items', 10);

/**
 * Ensure order totals display correctly
 */
function multi_currency_switcher_order_formatted_line_subtotal($formatted_subtotal, $item, $order) {
    // The order has already been processed with a specific currency
    // Just ensure proper formatting
    return $formatted_subtotal;
}
add_filter('woocommerce_order_formatted_line_subtotal', 'multi_currency_switcher_order_formatted_line_subtotal', 10, 3);

/**
 * Apply currency symbol to order pages
 */
function multi_currency_switcher_order_currency_symbol($currency_symbol, $currency) {
    // For orders, get symbol from our currency data
    $all_currencies = get_all_available_currencies();
    
    if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
        return $all_currencies[$currency]['symbol'];
    }
    
    return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'multi_currency_switcher_order_currency_symbol', 20, 2);

/**
 * Ensure currency format is applied on thank you page
 */
function multi_currency_switcher_thankyou_page_currency($args, $currency_pos) {
    // Make sure currency_symbol is set
    if (!isset($args['currency_symbol']) && isset($args['currency'])) {
        $currency = $args['currency'];
        $all_currencies = get_all_available_currencies();
        
        if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['symbol'])) {
            $args['currency_symbol'] = $all_currencies[$currency]['symbol'];
        } else {
            // Fallback to currency code if symbol not found
            $args['currency_symbol'] = $currency;
        }
    }
    
    return $args;
}
add_filter('wc_price_args', 'multi_currency_switcher_thankyou_page_currency', 20, 2);