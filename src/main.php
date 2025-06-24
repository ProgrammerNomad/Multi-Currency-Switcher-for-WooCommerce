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