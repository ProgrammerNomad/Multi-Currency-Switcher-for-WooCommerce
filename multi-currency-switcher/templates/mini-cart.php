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

// Force cart calculation for correct currency display, but only when necessary
if (function_exists('WC') && WC()->cart && !is_admin()) {
    // Set a time limit to prevent execution time errors
    $previous_time_limit = ini_get('max_execution_time');
    set_time_limit(60); // Increase to 60 seconds
    
    try {
        WC()->cart->calculate_totals();
    } catch (Exception $e) {
        error_log('Error calculating cart totals: ' . $e->getMessage());
    }
    
    // Restore previous time limit
    set_time_limit($previous_time_limit);
}

// Let WooCommerce handle the rest via its template
wc_get_template('cart/mini-cart.php');