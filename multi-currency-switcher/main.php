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
require_once plugin_dir_path(__FILE__) . 'includes/price-filters.php'; // Add this line
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

/**
 * Force mini cart recalculation when currency changes
 */
function multi_currency_switcher_refresh_fragments() {
    WC_AJAX::get_refreshed_fragments();
}
add_action('wp_ajax_multi_currency_refresh_fragments', 'multi_currency_switcher_refresh_fragments');
add_action('wp_ajax_nopriv_multi_currency_refresh_fragments', 'multi_currency_switcher_refresh_fragments');

// Add a data attribute to store the original price
add_filter('woocommerce_get_price_html', function($price, $product) {
    $original_price = $product->get_price();
    return sprintf('<span class="woocommerce-Price-amount" data-original-price="%s">%s</span>', esc_attr($original_price), $price);
}, 10, 2);

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

// Add this function to ensure the currency is properly set during AJAX add to cart:

function multi_currency_switcher_before_add_to_cart() {
    if (isset($_COOKIE['chosen_currency']) && function_exists('WC') && WC()->session) {
        $currency = sanitize_text_field($_COOKIE['chosen_currency']);
        $available_currencies = get_available_currencies();
        
        if (array_key_exists($currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $currency);
        }
    }
}
add_action('woocommerce_ajax_added_to_cart', 'multi_currency_switcher_before_add_to_cart', 1);