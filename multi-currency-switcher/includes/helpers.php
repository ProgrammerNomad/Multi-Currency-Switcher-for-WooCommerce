<?php
/**
 * Helper functions for Multi Currency Switcher.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function get_available_currencies() {
    $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array('USD'));
    $all_currencies = array(
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'JPY' => 'Japanese Yen',
        'AUD' => 'Australian Dollar',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'CNY' => 'Chinese Yuan',
        'SEK' => 'Swedish Krona',
        'NZD' => 'New Zealand Dollar',
        // Add more currencies as needed
    );
    
    $result = array();
    foreach ($enabled_currencies as $code) {
        if (isset($all_currencies[$code])) {
            $result[$code] = $all_currencies[$code];
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
    
    $all_currencies = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'SEK' => 'kr',
        'NZD' => 'NZ$',
        // Add more currencies as needed
    );
    
    $symbol = isset($all_currencies[$currency]) ? $all_currencies[$currency] : $currency;
    
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