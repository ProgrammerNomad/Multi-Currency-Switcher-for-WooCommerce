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
        add_options_page(
            'Multi Currency Switcher Settings',
            'Multi Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array( $this, 'create_settings_page' )
        );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1>Multi Currency Switcher Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'multi_currency_switcher_group' );
                do_settings_sections( 'multi_currency_switcher_group' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Default Currency</th>
                        <td>
                            <select name="default_currency">
                                <option value="USD" <?php selected( get_option('default_currency'), 'USD' ); ?>>USD</option>
                                <option value="EUR" <?php selected( get_option('default_currency'), 'EUR' ); ?>>EUR</option>
                                <option value="GBP" <?php selected( get_option('default_currency'), 'GBP' ); ?>>GBP</option>
                                <!-- Add more currencies as needed -->
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Enable Currency Switcher</th>
                        <td>
                            <input type="checkbox" name="enable_currency_switcher" value="1" <?php checked( get_option('enable_currency_switcher'), 1 ); ?> />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'multi_currency_switcher_group', 'default_currency' );
        register_setting( 'multi_currency_switcher_group', 'enable_currency_switcher' );
    }
}

new Multi_Currency_Switcher_Admin_Settings();
?>