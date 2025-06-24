<?php
/**
 * Plugin Name: Multi Currency Switcher for WooCommerce
 * Description: A free WooCommerce plugin for multi-currency switching.
 * Version: 1.0.0
 * Author: ProgrammerNomad
 * Author URI: https://github.com/ProgrammerNomad
 * License: MIT
 */

defined('ABSPATH') || exit;

// Include files in the correct order
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php'; // Load helpers first
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/currency-switcher.php';

// Initialize the plugin
function multi_currency_switcher_init() {
    // Add hooks and filters here
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'multi_currency_switcher_woocommerce_notice');
        return;
    }
}
add_action('plugins_loaded', 'multi_currency_switcher_init');

// Display notice if WooCommerce is not active
function multi_currency_switcher_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('Multi Currency Switcher for WooCommerce requires WooCommerce to be installed and active.', 'multi-currency-switcher'); ?></p>
    </div>
    <?php
}

// AJAX handlers
function handle_geolocation_currency() {
    $country = get_user_country();
    $currency = get_currency_by_country($country);

    if ($currency) {
        wp_send_json_success(['currency' => $currency]);
    } else {
        wp_send_json_error(['message' => 'Unable to determine currency']);
    }
}
add_action('wp_ajax_get_geolocation_currency', 'handle_geolocation_currency');
add_action('wp_ajax_nopriv_get_geolocation_currency', 'handle_geolocation_currency');

function handle_get_exchange_rate() {
    $currency = sanitize_text_field($_GET['currency']);
    $rate = multi_currency_switcher_get_exchange_rate($currency);

    if ($rate) {
        wp_send_json_success(['rate' => $rate]);
    } else {
        wp_send_json_error(['message' => 'Unable to fetch exchange rate']);
    }
}
add_action('wp_ajax_get_exchange_rate', 'handle_get_exchange_rate');
add_action('wp_ajax_nopriv_get_exchange_rate', 'handle_get_exchange_rate');

// Add a data attribute to store the original price
add_filter('woocommerce_get_price_html', function($price, $product) {
    $original_price = $product->get_price();
    return sprintf('<span class="woocommerce-Price-amount" data-original-price="%s">%s</span>', esc_attr($original_price), $price);
}, 10, 2);

function multi_currency_switcher_override_product_price($price, $product) {
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return $price;
    }
    
    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $custom_price = get_post_meta($product->get_id(), '_price_' . $currency, true);

    if ($custom_price) {
        return wc_price($custom_price, array('currency' => $currency));
    }

    return $price; // Fallback to default price
}
add_filter('woocommerce_get_price_html', 'multi_currency_switcher_override_product_price', 10, 2);

// Adjust shipping costs based on currency
function multi_currency_switcher_adjust_shipping_cost($package_rates, $package) {
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return $package_rates;
    }
    
    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);

    if ($exchange_rate) {
        foreach ($package_rates as $rate_id => $rate) {
            $original_cost = $rate->cost;
            $rate->cost = $original_cost * $exchange_rate;
        }
    }

    return $package_rates;
}
add_filter('woocommerce_package_rates', 'multi_currency_switcher_adjust_shipping_cost', 10, 2);

// Adjust coupon amount based on currency
function multi_currency_switcher_adjust_coupon_amount($coupon_amount, $coupon) {
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return $coupon_amount;
    }
    
    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $custom_amount = get_post_meta($coupon->get_id(), '_coupon_amount_' . $currency, true);

    if ($custom_amount) {
        return $custom_amount;
    }

    return $coupon_amount; // Fallback to default amount
}
add_filter('woocommerce_coupon_get_discount_amount', 'multi_currency_switcher_adjust_coupon_amount', 10, 2);

// Schedule daily exchange rate updates
register_activation_hook(__FILE__, 'multi_currency_switcher_schedule_updates');
add_action('multi_currency_switcher_daily_update', 'multi_currency_switcher_update_all_exchange_rates');

/**
 * Schedule the daily exchange rate update
 */
function multi_currency_switcher_schedule_updates() {
    if (!wp_next_scheduled('multi_currency_switcher_daily_update')) {
        wp_schedule_event(time(), 'daily', 'multi_currency_switcher_daily_update');
    }
}

/**
 * Clean up scheduled events on plugin deactivation
 */
register_deactivation_hook(__FILE__, 'multi_currency_switcher_clear_scheduled_updates');

function multi_currency_switcher_clear_scheduled_updates() {
    wp_clear_scheduled_hook('multi_currency_switcher_daily_update');
}

/**
 * Override get_price and related functions to apply exchange rates
 */
function multi_currency_switcher_filter_displayed_price($price, $product) {
    if (!function_exists('WC') || !WC()->session) {
        return $price;
    }
    
    $currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
    $base_currency = get_woocommerce_currency();
    
    // Don't convert if we're already using the base currency
    if ($currency === $base_currency) {
        return $price;
    }
    
    // Get the raw price value for conversion (it might be HTML at this point)
    $raw_price = $product->get_price();
    
    if (!is_numeric($raw_price)) {
        return $price; // If not a number, return the original price
    }
    
    // Check for a custom price for this product in this currency
    $custom_price = get_post_meta($product->get_id(), '_price_' . $currency, true);
    
    if (!empty($custom_price) && is_numeric($custom_price)) {
        // Format the price with WooCommerce's wc_price function
        return wc_price($custom_price, array('currency' => $currency));
    } else {
        // No custom price, so convert using exchange rate
        $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
        $converted_price = floatval($raw_price) * floatval($exchange_rate);
        
        // Format the converted price
        return wc_price($converted_price, array('currency' => $currency));
    }
}
add_filter('woocommerce_get_price_html', 'multi_currency_switcher_filter_displayed_price', 10, 2);