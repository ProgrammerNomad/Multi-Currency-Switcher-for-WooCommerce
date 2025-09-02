<?php
/**
 * Mini-cart
 *
 * Override WooCommerce mini-cart template to ensure currency switching works properly
 */

if (!defined('ABSPATH')) {
    exit;
}

// Skip cart calculation during admin requests to prevent timeout
if (is_admin() && !wp_doing_ajax()) {
    wc_get_template('cart/mini-cart.php');
    return;
}

// Simplified approach - just ensure WooCommerce loads its template
// without any complex processing that might cause memory issues
if (function_exists('WC') && WC()->cart && !is_admin()) {
    // Only do minimal processing if cart is small
    $cart_item_count = WC()->cart->get_cart_contents_count();
    
    // For small carts, do a quick calculation
    if ($cart_item_count > 0 && $cart_item_count < 10) {
        try {
            // Very simple cache check
            $current_currency = WC()->session->get('chosen_currency', '');
            $cache_key = 'mcs_simple_' . $current_currency . '_' . $cart_item_count;
            
            if (get_transient($cache_key) === false) {
                WC()->cart->calculate_totals();
                set_transient($cache_key, true, 60); // Cache for 1 minute
            }
        } catch (Exception $e) {
            // Silently continue if calculation fails
        }
    }
}

// Let WooCommerce handle the rest via its template
wc_get_template('cart/mini-cart.php');