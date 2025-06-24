<?php
// filepath: c:\xampp\htdocs\Multi-Currency-Switcher-for-WooCommerce\src\includes\helpers.php

defined('ABSPATH') || exit;

function get_available_currencies() {
    return [
        'USD' => 'United States Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound Sterling',
        'JPY' => 'Japanese Yen',
        'AUD' => 'Australian Dollar',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'CNY' => 'Chinese Yuan',
        'SEK' => 'Swedish Krona',
        'NZD' => 'New Zealand Dollar',
    ];
}

function format_currency($amount, $currency) {
    $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($amount, $currency);
}

function get_currency_symbol($currency) {
    $symbols = [
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
    ];
    return $symbols[$currency] ?? '';
}

function multi_currency_switcher_get_exchange_rate($currency) {
    // Example API integration for exchange rates
    $api_url = "https://api.exchangerate-api.com/v4/latest/USD";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return $data['rates'][$currency] ?? false;
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