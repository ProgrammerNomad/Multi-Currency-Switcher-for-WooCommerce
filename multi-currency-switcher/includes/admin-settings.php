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
        register_setting('multi_currency_switcher_settings', 'multi_currency_switcher_payment_restrictions');

        add_settings_section(
            'multi_currency_switcher_payment_section',
            'Payment Restrictions',
            'multi_currency_switcher_payment_section_callback',
            'multi_currency_switcher'
        );

        add_settings_field(
            'multi_currency_switcher_payment_restrictions',
            'Restrict Payment Methods by Currency',
            'multi_currency_switcher_payment_restrictions_callback',
            'multi_currency_switcher',
            'multi_currency_switcher_payment_section'
        );
    }
}

new Multi_Currency_Switcher_Admin_Settings();

function multi_currency_switcher_payment_section_callback() {
    echo '<p>Map currencies to payment methods you want to disable.</p>';
}

function multi_currency_switcher_payment_restrictions_callback() {
    $restrictions = get_option('multi_currency_switcher_payment_restrictions', []);
    $currencies = ['USD', 'GBP', 'EUR', 'AUD']; // Example currencies
    $gateways = WC()->payment_gateways->get_available_payment_gateways();

    foreach ($currencies as $currency) {
        echo "<h4>$currency</h4>";
        foreach ($gateways as $gateway_id => $gateway) {
            $checked = isset($restrictions[$currency]) && in_array($gateway_id, $restrictions[$currency]) ? 'checked' : '';
            echo "<label><input type='checkbox' name='multi_currency_switcher_payment_restrictions[$currency][]' value='$gateway_id' $checked> {$gateway->title}</label><br>";
        }
    }
}

function multi_currency_switcher_add_product_currency_fields() {
    global $post;

    $currencies = get_available_currencies();
    foreach ($currencies as $currency_code => $currency_name) {
        $value = get_post_meta($post->ID, '_price_' . $currency_code, true);
        echo '<div class="options_group">';
        woocommerce_wp_text_input(array(
            'id' => '_price_' . $currency_code,
            'label' => sprintf(__('Price in %s (%s)', 'multi-currency-switcher'), $currency_name, $currency_code),
            'type' => 'text',
            'value' => $value,
            'description' => sprintf(__('Set the price for %s.', 'multi-currency-switcher'), $currency_name),
            'desc_tip' => true,
        ));
        echo '</div>';
    }
}
add_action('woocommerce_product_options_pricing', 'multi_currency_switcher_add_product_currency_fields');

function multi_currency_switcher_save_product_currency_fields($post_id) {
    $currencies = get_available_currencies();
    foreach ($currencies as $currency_code => $currency_name) {
        if (isset($_POST['_price_' . $currency_code])) {
            update_post_meta($post_id, '_price_' . $currency_code, sanitize_text_field($_POST['_price_' . $currency_code]));
        }
    }
}
add_action('woocommerce_process_product_meta', 'multi_currency_switcher_save_product_currency_fields');

function multi_currency_switcher_add_coupon_currency_fields($coupon_id) {
    $currencies = get_available_currencies();
    foreach ($currencies as $currency_code => $currency_name) {
        $value = get_post_meta($coupon_id, '_coupon_amount_' . $currency_code, true);
        echo '<div class="options_group">';
        woocommerce_wp_text_input(array(
            'id' => '_coupon_amount_' . $currency_code,
            'label' => sprintf(__('Coupon Amount in %s (%s)', 'multi-currency-switcher'), $currency_name, $currency_code),
            'type' => 'text',
            'value' => $value,
            'description' => sprintf(__('Set the coupon amount for %s.', 'multi-currency-switcher'), $currency_name),
            'desc_tip' => true,
        ));
        echo '</div>';
    }
}
add_action('woocommerce_coupon_options', 'multi_currency_switcher_add_coupon_currency_fields');

function multi_currency_switcher_save_coupon_currency_fields($coupon_id) {
    $currencies = get_available_currencies();
    foreach ($currencies as $currency_code => $currency_name) {
        if (isset($_POST['_coupon_amount_' . $currency_code])) {
            update_post_meta($coupon_id, '_coupon_amount_' . $currency_code, sanitize_text_field($_POST['_coupon_amount_' . $currency_code]));
        }
    }
}
add_action('woocommerce_coupon_options_save', 'multi_currency_switcher_save_coupon_currency_fields');
?>