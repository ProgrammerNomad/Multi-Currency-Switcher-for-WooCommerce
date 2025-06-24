<?php
/**
 * Mini-cart
 *
 * Override WooCommerce mini-cart template to ensure currency switching works properly
 */

if (!defined('ABSPATH')) {
    exit;
}

// Force cart calculation for correct currency display
if (function_exists('WC') && WC()->cart) {
    WC()->cart->calculate_totals();
}

// Let WooCommerce handle the rest via its template
wc_get_template('cart/mini-cart.php');