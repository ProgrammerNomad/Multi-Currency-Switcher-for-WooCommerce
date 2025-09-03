<?php
/**
 * Geolocation Helper Functions
 * Handles currency detection based on user location
 *
 * @package WC_Multi_Currency_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get user's country code based on IP address
 * Uses WooCommerce's built-in geolocation
 *
 * @return string Country code (2-letter ISO) or empty string if detection fails
 */
function wc_multi_currency_manager_get_user_country() {
    if (!class_exists('WC_Geolocation')) {
        return '';
    }

    $location = WC_Geolocation::geolocate_ip();
    return isset($location['country']) ? $location['country'] : '';
}

/**
 * Get currency code for a specific country
 * 
 * @param string $country_code 2-letter country code
 * @return string Currency code or empty string if not found
 */
function wc_multi_currency_manager_get_currency_by_country($country_code) {
    if (empty($country_code)) {
        return '';
    }

    // Get enabled currencies
    $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_option('woocommerce_currency', 'USD')));
    
    // Get custom mappings first (highest priority)
    $custom_mappings = get_option('wc_multi_currency_manager_country_mappings', array());
    if (isset($custom_mappings[$country_code]) && !empty($custom_mappings[$country_code])) {
        $custom_currency = $custom_mappings[$country_code];
        if (in_array($custom_currency, $enabled_currencies)) {
            return $custom_currency;
        }
    }

    // Get default country-currency mapping
    $default_mapping = wc_multi_currency_manager_get_default_country_mapping();
    if (isset($default_mapping[$country_code])) {
        $country_currencies = $default_mapping[$country_code];
        
        // Find first enabled currency from the list
        foreach ($country_currencies as $currency) {
            if (in_array($currency, $enabled_currencies)) {
                return $currency;
            }
        }
    }

    // No suitable currency found
    return '';
}

/**
 * Get the default country-currency mapping array
 *
 * @return array
 */
function wc_multi_currency_manager_get_default_country_mapping() {
    // Use the consolidated function instead of duplicate code
    return get_country_currency_mapping();
}

/**
 * Auto-detect and set currency based on user's location
 * Called when auto-detection is enabled
 *
 * @return string|false The detected currency code or false if detection failed
 */
function wc_multi_currency_manager_auto_detect_currency() {
    // Check if auto-detection is enabled
    $general_settings = get_option('wc_multi_currency_manager_general_settings', array());
    if (!isset($general_settings['auto_detect']) || $general_settings['auto_detect'] !== 'yes') {
        return false;
    }

    // Get user's country
    $country_code = wc_multi_currency_manager_get_user_country();
    if (empty($country_code)) {
        return false;
    }

    // Get currency for the country
    $currency_code = wc_multi_currency_manager_get_currency_by_country($country_code);
    if (empty($currency_code)) {
        return false;
    }

    return $currency_code;
}

/**
 * Initialize auto-detection when user visits the site
 * Only runs once per session unless currency is manually changed
 */
function wc_multi_currency_manager_init_auto_detection() {
    // Don't auto-detect in admin
    if (is_admin()) {
        return;
    }

    // Don't auto-detect if WooCommerce isn't available
    if (!class_exists('WooCommerce') || !WC()->session) {
        return;
    }

    // Don't auto-detect if currency is already set by user
    $current_currency = WC()->session->get('chosen_currency');
    $auto_detected = WC()->session->get('auto_detected_currency');
    
    // If currency was manually changed, don't auto-detect again
    if ($current_currency && $auto_detected && $current_currency !== $auto_detected) {
        return;
    }

    // Only auto-detect if no currency is set or we need to refresh detection
    if (!$current_currency) {
        $detected_currency = wc_multi_currency_manager_auto_detect_currency();
        
        if ($detected_currency) {
            // Set the detected currency
            WC()->session->set('chosen_currency', $detected_currency);
            WC()->session->set('auto_detected_currency', $detected_currency);
            
            // Set cookie for persistence
            if (!headers_sent()) {
                setcookie('chosen_currency', $detected_currency, time() + (86400 * 30), '/');
            }
            
            // Trigger recalculation
            WC()->session->set('currency_changed', true);
        }
    }
}

// Hook into WordPress to run auto-detection
add_action('wp_loaded', 'wc_multi_currency_manager_init_auto_detection', 20);

/**
 * Reset auto-detection flag when user manually changes currency
 * This allows auto-detection to work again if they clear their cookies
 */
function wc_multi_currency_manager_reset_auto_detection() {
    if (class_exists('WooCommerce') && WC()->session) {
        WC()->session->set('auto_detected_currency', null);
    }
}

/**
 * Get countries list for admin interface
 * 
 * @return array Array of country_code => country_name
 */
function wc_multi_currency_manager_get_countries_list() {
    if (class_exists('WooCommerce')) {
        return WC()->countries->get_countries();
    }
    
    // Fallback basic list if WooCommerce isn't available
    return array(
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'DE' => 'Germany',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'JP' => 'Japan',
        'CN' => 'China',
    );
}
