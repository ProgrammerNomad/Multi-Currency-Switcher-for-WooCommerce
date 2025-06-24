<?php
// This file handles the admin settings for the currency switcher.
// It defines functions to create and manage the settings page in the WordPress admin area.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Multi_Currency_Switcher_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_menu_page(
            'Multi Currency Switcher',
            'Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array( $this, 'create_settings_page' ),
            'dashicons-money-alt',
            100
        );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1>Multi Currency Switcher Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'multi_currency_switcher_settings' );
                do_settings_sections( 'multi_currency_switcher' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'multi_currency_switcher_settings', 'default_currency' );
        register_setting( 'multi_currency_switcher_settings', 'enable_currency_switcher' );
    }
}

new Multi_Currency_Switcher_Admin_Settings();
?>