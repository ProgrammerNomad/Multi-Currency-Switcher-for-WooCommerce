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

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/currency-switcher.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

// Initialize the plugin
function multi_currency_switcher_init() {
    // Add hooks and filters here
}
add_action('plugins_loaded', 'multi_currency_switcher_init');

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
    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $custom_price = get_post_meta($product->get_id(), '_price_' . $currency, true);

    if ($custom_price) {
        return wc_price($custom_price, array('currency' => $currency));
    }

    return $price; // Fallback to default price
}
add_filter('woocommerce_get_price_html', 'multi_currency_switcher_override_product_price', 10, 2);

function multi_currency_switcher_adjust_shipping_cost($package_rates, $package) {
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

function multi_currency_switcher_adjust_coupon_amount($coupon_amount, $coupon) {
    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $custom_amount = get_post_meta($coupon->get_id(), '_coupon_amount_' . $currency, true);

    if ($custom_amount) {
        return $custom_amount;
    }

    return $coupon_amount; // Fallback to default amount
}
add_filter('woocommerce_coupon_get_discount_amount', 'multi_currency_switcher_adjust_coupon_amount', 10, 2);