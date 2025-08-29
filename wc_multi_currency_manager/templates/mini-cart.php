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

// Force cart calculation for correct currency display, but with memory optimization
if (function_exists('WC') && WC()->cart && !is_admin()) {
    // Increase memory limit temporarily if possible
    $current_limit = ini_get('memory_limit');
    if (function_exists('wp_raise_memory_limit')) {
        wp_raise_memory_limit('admin');
    } else {
        @ini_set('memory_limit', '256M');
    }
    
    // Skip recalculation if cart is too large to avoid memory issues
    $cart_item_count = WC()->cart->get_cart_contents_count();
    if ($cart_item_count < 20) { // Only recalculate for reasonably sized carts
        try {
            // Use transient to prevent multiple calculations in the same request
            $cache_key = 'mcs_cart_' . md5(serialize(WC()->cart->get_cart_for_session()) . WC()->session->get('chosen_currency', ''));
            $cached = get_transient($cache_key);
            
            if ($cached === false) {
                WC()->cart->calculate_totals();
                set_transient($cache_key, true, 30); // Cache for 30 seconds
            }
        } catch (Exception $e) {
            error_log('Error calculating cart totals: ' . $e->getMessage());
        }
    }
    
    // Restore original memory limit
    @ini_set('memory_limit', $current_limit);
}

// Let WooCommerce handle the rest via its template
wc_get_template('cart/mini-cart.php');