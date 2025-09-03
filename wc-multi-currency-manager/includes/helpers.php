<?php
/**
 * Helper functions for WC Multi Currency Manager.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function get_available_currencies() {
    $enabled_currency_codes = get_option('wc_multi_currency_manager_enabled_currencies', array(get_option('woocommerce_currency', 'USD')));
    $all_currencies = get_all_available_currencies();
    
    $result = array();
    foreach ($enabled_currency_codes as $code) {
        if (isset($all_currencies[$code])) {
            $result[$code] = $all_currencies[$code]['name'];
        }
    }
    
    return $result;
}

function get_user_country() {
    if (!class_exists('WC_Geolocation')) {
        return '';
    }
    
    try {
        $location = WC_Geolocation::geolocate_ip();
        return isset($location['country']) ? $location['country'] : '';
    } catch (Exception $e) {
        error_log('WC Multi Currency Manager: Geolocation error - ' . $e->getMessage());
        return '';
    }
}

function get_currency_by_country($country_code) {
    if (empty($country_code)) {
        return '';
    }
    
    // Get enabled currencies
    $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_woocommerce_currency()));
    
    // Get custom mappings first (higher priority)
    $custom_mappings = get_option('wc_multi_currency_manager_country_mappings', array());
    if (isset($custom_mappings[$country_code]) && in_array($custom_mappings[$country_code], $enabled_currencies)) {
        return $custom_mappings[$country_code];
    }
    
    // Get default mapping
    $country_currency_mapping = get_country_currency_mapping();
    if (isset($country_currency_mapping[$country_code])) {
        $default_currencies = $country_currency_mapping[$country_code];
        
        // Find first enabled currency from the list
        foreach ($default_currencies as $currency) {
            if (in_array($currency, $enabled_currencies)) {
                return $currency;
            }
        }
    }
    
    // Fallback to WooCommerce default currency
    return get_woocommerce_currency();
}

/**
 * Get country-currency mapping from data file and JSON
 */
function get_country_currency_mapping() {
    static $mapping = null;
    
    if ($mapping === null) {
        // First, load the manual mapping from countries-currencies.php
        $mapping_file = plugin_dir_path(__FILE__) . '../data/countries-currencies.php';
        if (file_exists($mapping_file)) {
            $mapping = include $mapping_file;
        } else {
            $mapping = array();
        }
        
        // Then, enhance it with data from currencies.json
        $json_file = plugin_dir_path(__FILE__) . '../data/currencies.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $currencies_data = json_decode($json_data, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Add mappings from JSON for countries not in manual mapping
                foreach ($currencies_data as $currency_code => $currency_info) {
                    if (isset($currency_info['country_code'])) {
                        $country_code = $currency_info['country_code'];
                        
                        // Only add if not already defined in manual mapping
                        if (!isset($mapping[$country_code])) {
                            $mapping[$country_code] = array($currency_code);
                        } else if (!in_array($currency_code, $mapping[$country_code])) {
                            // Add as secondary currency if not already listed
                            $mapping[$country_code][] = $currency_code;
                        }
                    }
                }
            }
        }
    }
    
    return $mapping;
}

/**
 * Check if auto-detection is enabled
 */
function is_auto_detect_enabled() {
    $general_settings = get_option('wc_multi_currency_manager_general_settings', array('auto_detect' => 'yes'));
    return isset($general_settings['auto_detect']) && $general_settings['auto_detect'] === 'yes';
}

function wc_multi_currency_manager_get_exchange_rate($from_currency_or_target, $to_currency = null) {
    // Support both old and new function signatures
    if ($to_currency === null) {
        // Old signature: wc_multi_currency_manager_get_exchange_rate($target_currency)
        $target_currency = $from_currency_or_target;
        $base_currency = get_option('woocommerce_currency', 'USD');
    } else {
        // New signature: wc_multi_currency_manager_get_exchange_rate($from_currency, $to_currency)
        $base_currency = $from_currency_or_target;
        $target_currency = $to_currency;
    }
    
    // Same currency, rate is 1
    if ($base_currency === $target_currency) {
        return 1;
    }
    
    // Get exchange rates saved from admin
    $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
    
    // Get the actual WooCommerce base currency
    $woo_base_currency = get_option('woocommerce_currency', 'USD');
    
    // The stored rates are all relative to WooCommerce base currency
    if ($base_currency === $woo_base_currency) {
        // Converting FROM WooCommerce base TO another currency
        if (isset($exchange_rates[$target_currency])) {
            $rate = floatval($exchange_rates[$target_currency]);
            return $rate;
        }
    } elseif ($target_currency === $woo_base_currency) {
        // Converting FROM another currency TO WooCommerce base
        if (isset($exchange_rates[$base_currency])) {
            $rate = 1.0 / floatval($exchange_rates[$base_currency]);
            return $rate;
        }
    } else {
        // Converting between two non-base currencies
        if (isset($exchange_rates[$base_currency]) && isset($exchange_rates[$target_currency])) {
            $base_to_woo_base = 1.0 / floatval($exchange_rates[$base_currency]);
            $woo_base_to_target = floatval($exchange_rates[$target_currency]);
            $rate = $base_to_woo_base * $woo_base_to_target;
            return $rate;
        }
    }
    
    // Fallback to 1 if currency not found
    return 1;
}

/**
 * Get current currency with improved detection (similar to YITH approach)
 */
function wc_multi_currency_manager_get_current_currency() {
    static $current_currency = null;
    static $is_detecting = false;
    
    // Prevent infinite loops by checking if we're already detecting
    if ($is_detecting) {
        return get_option('woocommerce_currency', 'USD');
    }
    
    // Return cached result if available
    if ($current_currency !== null) {
        return $current_currency;
    }
    
    // Set flag to indicate we're detecting currency
    $is_detecting = true;
    
    // First, try to get from WooCommerce session if available
    if (function_exists('WC') && WC() && WC()->session) {
        $session_currency = WC()->session->get('chosen_currency', '');
        if (!empty($session_currency)) {
            $current_currency = $session_currency;
            $is_detecting = false; // Reset flag
            return $current_currency;
        }
    }
    
    // Try to get from AJAX request
    if (defined('DOING_AJAX') && DOING_AJAX) {
        if (isset($_REQUEST['currency']) && !empty($_REQUEST['currency'])) {
            $current_currency = sanitize_text_field($_REQUEST['currency']);
            $is_detecting = false; // Reset flag
            return $current_currency;
        }
    }
    
    // Try to get from URL parameter (for non-AJAX currency switching)
    if (isset($_GET['currency']) && !empty($_GET['currency'])) {
        $currency = sanitize_text_field($_GET['currency']);
        // Simple currency validation - just check if it's 3 letters
        if (strlen($currency) === 3 && ctype_alpha($currency)) {
            // Set in session if available
            if (function_exists('WC') && WC() && WC()->session) {
                WC()->session->set('chosen_currency', $currency);
            }
            $current_currency = $currency;
            $is_detecting = false; // Reset flag
            return $current_currency;
        }
    }
    
    // Try to get from cookie
    if (isset($_COOKIE['chosen_currency']) && !empty($_COOKIE['chosen_currency'])) {
        $currency = sanitize_text_field($_COOKIE['chosen_currency']);
        // Simple currency validation - just check if it's 3 letters
        if (strlen($currency) === 3 && ctype_alpha($currency)) {
            // Set in session if available
            if (function_exists('WC') && WC() && WC()->session) {
                WC()->session->set('chosen_currency', $currency);
            }
            $current_currency = $currency;
            $is_detecting = false; // Reset flag
            return $current_currency;
        }
    }
    
    // Try user meta if user is logged in
    if (is_user_logged_in()) {
        $user_currency = get_user_meta(get_current_user_id(), 'chosen_currency', true);
        if (!empty($user_currency) && strlen($user_currency) === 3 && ctype_alpha($user_currency)) {
            // Set in session if available
            if (function_exists('WC') && WC() && WC()->session) {
                WC()->session->set('chosen_currency', $user_currency);
            }
            $current_currency = $user_currency;
            $is_detecting = false; // Reset flag
            return $current_currency;
        }
    }
    
    // Default to WooCommerce base currency
    $current_currency = get_option('woocommerce_currency', 'USD');
    $is_detecting = false; // Reset flag
    return $current_currency;
}

function format_price_in_currency($price, $currency = null) {
    if ($currency === null) {
        $currency = get_option('woocommerce_currency', 'USD');
    }
    $exchange_rate = wc_multi_currency_manager_get_exchange_rate($currency);
    $converted_price = $price * $exchange_rate;
    
    $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());
    $settings = isset($currency_settings[$currency]) ? $currency_settings[$currency] : array(
        'position' => 'left',
        'decimals' => 2,
        'thousand_sep' => ',',
        'decimal_sep' => '.'
    );
    
    $formatted_price = number_format(
        $converted_price, 
        $settings['decimals'], 
        $settings['decimal_sep'], 
        $settings['thousand_sep']
    );
    
    // Get currency data from JSON instead of hardcoding
    $all_currencies = get_all_available_currencies();
    $symbol = isset($all_currencies[$currency]['symbol']) ? $all_currencies[$currency]['symbol'] : $currency;
    
    switch ($settings['position']) {
        case 'left':
            return $symbol . $formatted_price;
        case 'right':
            return $formatted_price . $symbol;
        case 'left_space':
            return $symbol . ' ' . $formatted_price;
        case 'right_space':
            return $formatted_price . ' ' . $symbol;
        default:
            return $symbol . $formatted_price;
    }
}

/**
 * CENTRALIZED API FUNCTION - Use this everywhere instead of duplicating code
 * Fetches exchange rates from API using the actual WooCommerce base currency
 */
function wc_multi_currency_manager_fetch_exchange_rates_from_api($base_currency = null) {
    if ($base_currency === null) {
        $base_currency = get_option('woocommerce_currency', 'USD');
    }
    
    // Use DYNAMIC API URL based on actual base currency
    $api_url = "https://api.exchangerate-api.com/v4/latest/{$base_currency}";
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'WC Multi Currency Manager'
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('WC Multi Currency Manager: API Error - ' . $response->get_error_message());
        return false;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['rates'])) {
        error_log('WC Multi Currency Manager: Invalid API response');
        return false;
    }
    
    return $data;
}

/**
 * Update all exchange rates automatically from API - SIMPLIFIED using centralized function
 */
function wc_multi_currency_manager_update_all_exchange_rates() {
    // Get WooCommerce base currency (always use option, never filtered)
    $base_currency = get_option('woocommerce_currency', 'USD');
    
    // Get all enabled currencies
    $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array($base_currency));
    
    // Use centralized API function
    $api_data = wc_multi_currency_manager_fetch_exchange_rates_from_api($base_currency);
    
    if (!$api_data) {
        return false;
    }
    
    // Initialize exchange rates array
    $exchange_rates = array();
    
    // Set base currency exchange rate to 1
    $exchange_rates[$base_currency] = 1;
    
    // Process rates from API (now they're already relative to our base currency!)
    foreach ($enabled_currencies as $currency) {
        if ($currency === $base_currency) {
            continue; // Skip base currency
        }
        
        if (isset($api_data['rates'][$currency])) {
            $exchange_rates[$currency] = floatval($api_data['rates'][$currency]);
        }
    }
    
    // Save the updated exchange rates
    update_option('wc_multi_currency_manager_exchange_rates', $exchange_rates);
    
    // Save the last update time
    update_option('wc_multi_currency_manager_rates_last_updated', current_time('timestamp'));
    
    return true;
}

/**
 * Fetch exchange rate for a single currency - SIMPLIFIED using centralized function
 */
function wc_multi_currency_manager_fetch_single_currency_rate($target_currency, $base_currency = null) {
    if ($base_currency === null) {
        $base_currency = get_option('woocommerce_currency', 'USD');
    }
    
    // Same currency
    if ($target_currency === $base_currency) {
        return 1;
    }
    
    // Use centralized API function
    $api_data = wc_multi_currency_manager_fetch_exchange_rates_from_api($base_currency);
    
    if (!$api_data) {
        return 1; // Fallback
    }
    
    // Return rate directly (already relative to our base currency)
    return isset($api_data['rates'][$target_currency]) ? floatval($api_data['rates'][$target_currency]) : 1;
}

/**
 * Get all available currencies from the JSON file
 */
function get_all_available_currencies() {
    $json_file = plugin_dir_path(dirname(__FILE__)) . 'data/currencies.json';
    
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $currencies = json_decode($json_data, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $currencies;
        }
    }
    
    // Fallback to a minimal set of currencies if JSON file not found or invalid
    return array(
        'USD' => array('name' => 'US Dollar', 'symbol' => '$'),
        'EUR' => array('name' => 'Euro', 'symbol' => '€'),
        'GBP' => array('name' => 'British Pound', 'symbol' => '£')
    );
}

/**
 * Log errors for debugging
 */
function mcs_log_error($message, $data = []) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[WC Multi Currency Manager] ' . $message;
        
        if (!empty($data)) {
            $log_message .= ' | Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
}

/**
 * Helper function to safely manage memory during intensive operations
 *
 * @param callable $callback Function to run with higher memory
 * @param string $context Context for the memory limit (default, admin, etc)
 * @return mixed The result of the callback
 */
function wc_multi_currency_manager_with_increased_memory($callback, $context = 'admin') {
    // Save current memory limit
    $current_limit = ini_get('memory_limit');
    $result = null;
    
    try {
        // Increase memory limit if possible
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit($context);
        } else {
            @ini_set('memory_limit', '256M');
        }
        
        // Execute the callback
        $result = call_user_func($callback);
    } catch (Exception $e) {
        error_log('WC Multi Currency Manager memory error: ' . $e->getMessage());
    } finally {
        // Restore original memory limit
        @ini_set('memory_limit', $current_limit);
    }
    
    return $result;
}