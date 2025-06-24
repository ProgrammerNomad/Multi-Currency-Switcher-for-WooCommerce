<?php
/**
 * Helper functions for Multi Currency Switcher.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function get_available_currencies() {
    $enabled_currency_codes = get_option('multi_currency_switcher_enabled_currencies', array('USD'));
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
    if (class_exists('WC_Geolocation')) {
        $geolocation = new WC_Geolocation();
        $user_ip = $geolocation->get_ip_address();
        $location = $geolocation->geolocate_ip($user_ip);
        return $location['country'] ?? null;
    }
    return null;
}

function get_currency_by_country($country) {
    $country_currency_map = [
        'US' => 'USD',
        'GB' => 'GBP',
        'EU' => 'EUR',
        'JP' => 'JPY',
        'AU' => 'AUD',
        'CA' => 'CAD',
        'CN' => 'CNY',
        'SE' => 'SEK',
        'NZ' => 'NZD',
    ];
    return $country_currency_map[$country] ?? 'USD'; // Default to USD if no match
}

function multi_currency_switcher_get_exchange_rate($currency) {
    $exchange_rates = get_option('multi_currency_switcher_exchange_rates', array());
    
    if (isset($exchange_rates[$currency])) {
        return $exchange_rates[$currency];
    }
    
    // If no stored rate, try to fetch from API
    $api_url = "https://api.exchangerate-api.com/v4/latest/USD";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return 1; // Default to 1:1 if API call fails
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['rates'][$currency])) {
        // Store the rate for future use
        $exchange_rates[$currency] = $data['rates'][$currency];
        update_option('multi_currency_switcher_exchange_rates', $exchange_rates);
        return $data['rates'][$currency];
    }
    
    return 1; // Default to 1:1 if currency not found
}

function format_price_in_currency($price, $currency = 'USD') {
    $exchange_rate = multi_currency_switcher_get_exchange_rate($currency);
    $converted_price = $price * $exchange_rate;
    
    $currency_settings = get_option('multi_currency_switcher_currency_settings', array());
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
 * Update all exchange rates automatically from an API
 */
function multi_currency_switcher_update_all_exchange_rates() {
    // Get WooCommerce base currency
    $base_currency = get_option('woocommerce_currency', 'USD');
    
    // Get all enabled currencies
    $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array($base_currency));
    
    // Initialize exchange rates array
    $exchange_rates = array();
    
    // Set base currency exchange rate to 1
    $exchange_rates[$base_currency] = 1;
    
    // Fetch rates from API
    $api_url = "https://api.exchangerate-api.com/v4/latest/{$base_currency}";
    $response = wp_remote_get($api_url);
    
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['rates']) && is_array($data['rates'])) {
            foreach ($enabled_currencies as $currency) {
                if ($currency === $base_currency) {
                    continue; // Skip base currency
                }
                
                if (isset($data['rates'][$currency])) {
                    $exchange_rates[$currency] = $data['rates'][$currency];
                }
            }
            
            // Save the updated exchange rates
            update_option('multi_currency_switcher_exchange_rates', $exchange_rates);
            
            // Save the last update time
            update_option('multi_currency_switcher_rates_last_updated', current_time('timestamp'));
            
            return true;
        }
    }
    
    return false;
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
        $log_message = '[Multi Currency Switcher] ' . $message;
        
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
function multi_currency_switcher_with_increased_memory($callback, $context = 'admin') {
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
        error_log('Multi Currency Switcher memory error: ' . $e->getMessage());
    } finally {
        // Restore original memory limit
        @ini_set('memory_limit', $current_limit);
    }
    
    return $result;
}