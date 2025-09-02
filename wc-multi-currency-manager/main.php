<?php
/**
 * Plugin Name: WC Multi Currency Manager
 * Description: A professional WooCommerce plugin for multi-currency management, designed to maximize international sales by allowing customers to view and pay in their local currency.
 * Version: 1.0.0
 * Author: ProgrammerNomad
 * Author URI: https://github.com/ProgrammerNomad/WC-Multi-Currency-Manager
 * Plugin URI: https://github.com/ProgrammerNomad/WC-Multi-Currency-Manager
 * License: MIT
 * Text Domain: wc-multi-currency-manager
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// Increase memory limit for the plugin
if (!defined('WP_MAX_MEMORY_LIMIT')) {
    define('WP_MAX_MEMORY_LIMIT', '256M');
}

// Try to increase PHP memory limit
@ini_set('memory_limit', '256M');

// Include files in the correct order
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php'; // Load helpers first
require_once plugin_dir_path(__FILE__) . 'includes/geolocation.php'; // Load geolocation functions
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-settings.php'; // <-- Use this, not admin-settings.php
require_once plugin_dir_path(__FILE__) . 'includes/price-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/currency-switcher.php';

// Initialize the plugin
function wc_multi_currency_manager_init() {
    // Add hooks and filters here
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_multi_currency_manager_woocommerce_notice');
        return;
    }
    
    // Run migration on init
    wc_multi_currency_manager_migrate_settings();
}
add_action('plugins_loaded', 'wc_multi_currency_manager_init');

/**
 * Migrate style settings to general settings
 */
function wc_multi_currency_manager_migrate_settings() {
    // Check if migration has already been done
    $migration_done = get_option('wc_multi_currency_manager_migration_done', false);
    
    if (!$migration_done) {
        // Get existing style settings
        $style_settings = get_option('wc_multi_currency_manager_style_settings', array());
        $general_settings = get_option('wc_multi_currency_manager_general_settings', array());
        
        // Migrate specific fields from style to general settings
        $fields_to_migrate = array('show_sticky_widget', 'sticky_position', 'limit_currencies', 'show_flags', 'widget_style');
        
        foreach ($fields_to_migrate as $field) {
            if (isset($style_settings[$field]) && !isset($general_settings[$field])) {
                $general_settings[$field] = $style_settings[$field];
                // Remove from style settings
                unset($style_settings[$field]);
            }
        }
        
        // Update both settings
        update_option('wc_multi_currency_manager_general_settings', $general_settings);
        update_option('wc_multi_currency_manager_style_settings', $style_settings);
        
        // Mark migration as complete
        update_option('wc_multi_currency_manager_migration_done', true);
    }
}

// Add plugin action links
function wc_multi_currency_manager_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-multi-currency-manager') . '">' . __('Settings', 'wc-multi-currency-manager') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_multi_currency_manager_add_action_links');

// Display notice if WooCommerce is not active
function wc_multi_currency_manager_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('WC Multi Currency Manager requires WooCommerce to be installed and active.', 'wc-multi-currency-manager'); ?></p>
    </div>
    <?php
}

// AJAX handlers
function handle_geolocation_currency() {
    $country = wc_multi_currency_manager_get_user_country();
    $currency = wc_multi_currency_manager_get_currency_by_country($country);

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
    $rate = wc_multi_currency_manager_get_exchange_rate($currency);

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
function wc_multi_currency_manager_refresh_fragments() {
    WC_AJAX::get_refreshed_fragments();
}
add_action('wp_ajax_wc_multi_currency_refresh_fragments', 'wc_multi_currency_manager_refresh_fragments');
add_action('wp_ajax_nopriv_wc_multi_currency_refresh_fragments', 'wc_multi_currency_manager_refresh_fragments');

// Add a data attribute to store the original price
add_filter('woocommerce_get_price_html', function($price, $product) {
    $original_price = $product->get_price();
    return sprintf('<span class="woocommerce-Price-amount" data-original-price="%s">%s</span>', esc_attr($original_price), $price);
}, 10, 2);

// Schedule daily exchange rate updates
register_activation_hook(__FILE__, 'wc_multi_currency_manager_schedule_updates');
add_action('wc_multi_currency_manager_daily_update', 'wc_multi_currency_manager_update_all_exchange_rates');

/**
 * Schedule the daily exchange rate update
 */
function wc_multi_currency_manager_schedule_updates() {
    if (!wp_next_scheduled('wc_multi_currency_manager_daily_update')) {
        wp_schedule_event(time(), 'daily', 'wc_multi_currency_manager_daily_update');
    }
}

/**
 * Clean up scheduled events on plugin deactivation
 */
register_deactivation_hook(__FILE__, 'wc_multi_currency_manager_clear_scheduled_updates');

function wc_multi_currency_manager_clear_scheduled_updates() {
    wp_clear_scheduled_hook('wc_multi_currency_manager_daily_update');
}

// Add this function to ensure the currency is properly set during AJAX add to cart:

function wc_multi_currency_manager_before_add_to_cart() {
    if (isset($_COOKIE['chosen_currency']) && function_exists('WC') && WC()->session) {
        $currency = sanitize_text_field($_COOKIE['chosen_currency']);
        $available_currencies = get_available_currencies();
        
        if (array_key_exists($currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $currency);
        }
    }
}
add_action('woocommerce_ajax_added_to_cart', 'wc_multi_currency_manager_before_add_to_cart', 1);

// Add this to your main.php file to load templates
function wc_multi_currency_manager_template_loader($template, $template_name, $template_path) {
    if ($template_name === 'cart/mini-cart.php') {
        // Check if our custom template exists
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/mini-cart.php';
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    return $template;
}
add_filter('wc_get_template', 'wc_multi_currency_manager_template_loader', 10, 3);

/**
 * AJAX handler for currency switching
 * This updates the cart and mini cart when currency is changed via JavaScript
 */
function wc_multi_currency_manager_handle_currency_switch() {
    // Enable error logging for debugging
    ini_set('display_errors', 0);
    error_log('Currency switch AJAX called for currency: ' . (isset($_GET['currency']) ? $_GET['currency'] : 'none'));
    
    try {
        // Security checks
        if (!isset($_GET['currency']) || empty($_GET['currency'])) {
            wp_send_json_error(['message' => 'Missing currency parameter']);
            return;
        }

        if (!function_exists('WC') || !WC()->session) {
            wp_send_json_error(['message' => 'WooCommerce session not initialized']);
            return;
        }
        
        $currency = sanitize_text_field($_GET['currency']);
        $available_currencies = get_available_currencies();
        
        if (!array_key_exists($currency, $available_currencies)) {
            wp_send_json_error(['message' => 'Invalid currency: ' . $currency]);
            return;
        }
        
        // Set the new currency in session
        $old_currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
        WC()->session->set('chosen_currency', $currency);
        
        // Set cookie
        setcookie('chosen_currency', $currency, time() + (86400 * 30), '/');
        
        // Clear WC fragments cache to force regeneration
        if (class_exists('WC_Cache_Helper')) {
            WC_Cache_Helper::get_transient_version('fragments', true);
        }
        
        // Return basic success response without trying to calculate cart totals
        // This prevents potential errors in the cart calculation
        wp_send_json_success([
            'message' => 'Currency changed successfully',
            'old_currency' => $old_currency,
            'new_currency' => $currency
        ]);
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('Currency switch error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred while switching currency',
            'error' => $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_wc_multi_currency_switch', 'wc_multi_currency_manager_handle_currency_switch');
add_action('wp_ajax_nopriv_wc_multi_currency_switch', 'wc_multi_currency_manager_handle_currency_switch');

// Add after the other functions
function wc_multi_currency_manager_widget_display_control() {
    // Get display settings from general settings now
    $general_settings = get_option('wc_multi_currency_manager_general_settings', array(
        'widget_position' => 'both',
        'show_sticky_widget' => 'yes',
    ));
    
    $position = isset($general_settings['widget_position']) ? $general_settings['widget_position'] : 'both';
    $show_sticky = isset($general_settings['show_sticky_widget']) ? $general_settings['show_sticky_widget'] : 'yes';
    
    // Remove the sticky widget if needed
    if ($position === 'products_only' || $position === 'none' || $show_sticky === 'no') {
        remove_action('wp_footer', 'wc_multi_currency_manager_display_sticky_widget');
    }
    
    // Remove the product page widget if needed - but only if it was actually added
    if ($position === 'sticky_only' || $position === 'none') {
        remove_action('woocommerce_single_product_summary', 'wc_multi_currency_manager_display_on_product_page', 25);
        remove_action('woocommerce_single_product_summary', 'wc_multi_currency_manager_test_product_page', 24);
    }
}
add_action('wp', 'wc_multi_currency_manager_widget_display_control', 20);