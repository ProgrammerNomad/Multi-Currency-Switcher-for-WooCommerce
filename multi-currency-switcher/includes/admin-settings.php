<?php
// This file handles the admin settings for the currency switcher.
// It defines functions to create and manage the settings page in the WordPress admin area.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Make sure we load the individual admin page classes
require_once plugin_dir_path(__FILE__) . 'admin/class-general-settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-currencies-settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-style-settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-payment-settings.php';

class Multi_Currency_Switcher_Admin_Settings {

    /**
     * Instance of each admin page class
     */
    private $general_settings;
    private $currencies_settings;
    private $style_settings;
    private $payment_settings;

    public function __construct() {
        // Increase time limit for admin pages
        if (is_admin()) {
            $current_limit = ini_get('max_execution_time');
            if ($current_limit < 120) {
                @set_time_limit(120); // Increase to 120 seconds for admin pages
            }
        }
        
        // Initialize the admin page classes
        $this->general_settings = new Multi_Currency_Switcher_General_Settings();
        $this->currencies_settings = new Multi_Currency_Switcher_Currencies_Settings();
        $this->style_settings = new Multi_Currency_Switcher_Style_Settings();
        $this->payment_settings = new Multi_Currency_Switcher_Payment_Settings();
        
        // Add admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            'Multi Currency Switcher',
            'Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array($this->general_settings, 'render_page'),
            'dashicons-money-alt',
            58 // Position after WooCommerce
        );

        add_submenu_page(
            'multi-currency-switcher',
            'General Settings',
            'General Settings',
            'manage_options',
            'multi-currency-switcher',
            array($this->general_settings, 'render_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Currencies',
            'Currencies',
            'manage_options',
            'multi-currency-switcher-currencies',
            array($this->currencies_settings, 'render_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Style Settings',
            'Style Settings',
            'manage_options',
            'multi-currency-switcher-style',
            array($this->style_settings, 'render_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Payment Restrictions',
            'Payment Restrictions',
            'manage_options',
            'multi-currency-switcher-payment',
            array($this->payment_settings, 'render_page')
        );
    }

    /**
     * Register plugin settings with WordPress
     */
    public function register_settings() {
        // Register general settings
        register_setting(
            'multi_currency_switcher_general_settings',
            'multi_currency_switcher_general_settings'
        );
        
        // Register currency settings
        register_setting(
            'multi_currency_switcher_enabled_currencies',
            'multi_currency_switcher_enabled_currencies'
        );
        
        register_setting(
            'multi_currency_switcher_exchange_rates',
            'multi_currency_switcher_exchange_rates'
        );
        
        register_setting(
            'multi_currency_switcher_currency_settings',
            'multi_currency_switcher_currency_settings'
        );
        
        // Register style settings
        register_setting(
            'multi_currency_switcher_style_settings',
            'multi_currency_switcher_style_settings'
        );
        
        // Register payment restrictions
        register_setting(
            'multi_currency_switcher_payment_restrictions',
            'multi_currency_switcher_payment_restrictions'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on our plugin's admin pages
        if (strpos($hook, 'multi-currency-switcher') === false) {
            return;
        }

        // Add our admin styles - FIXED PATH
        wp_enqueue_style('multi-currency-admin-styles', plugins_url('../assets/css/admin-styles.css', __FILE__));
        
        // Enqueue color picker script and style
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('multi-currency-admin-scripts', plugins_url('../assets/js/admin-scripts.js', __FILE__), array('jquery', 'wp-color-picker'), false, true);
    }
}

// Initialize the main admin settings class
new Multi_Currency_Switcher_Admin_Settings();