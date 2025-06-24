<?php
/**
 * Currency Switcher functionality for WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class CurrencySwitcher
 */
class CurrencySwitcher {

    private $currencies;

    public function __construct() {
        $this->currencies = apply_filters( 'mcs_available_currencies', array(
            'USD' => 'United States Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
        ));

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'switch_currency' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'currency-switcher', plugins_url( '../assets/js/scripts.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_enqueue_style( 'currency-switcher-style', plugins_url( '../assets/css/styles.css', __FILE__ ) );
    }

    public function switch_currency() {
        if ( isset( $_POST['currency'] ) && array_key_exists( $_POST['currency'], $this->currencies ) ) {
            $selected_currency = sanitize_text_field( $_POST['currency'] );
            // Logic to switch currency and update prices accordingly.
            // This would typically involve updating session or transient data.
        }
    }

    public function display_currency_options() {
        $output = '<select id="currency-switcher">';
        foreach ( $this->currencies as $code => $name ) {
            $output .= sprintf( '<option value="%s">%s</option>', esc_attr( $code ), esc_html( $name ) );
        }
        $output .= '</select>';
        echo $output;
    }
}

new CurrencySwitcher();

function multi_currency_switcher_display() {
    // Logic to display currency switcher
    echo '<div class="currency-switcher">';
    echo '<select id="currency-selector">';
    echo '<option value="USD">USD</option>';
    echo '<option value="EUR">EUR</option>';
    echo '<option value="GBP">GBP</option>';
    echo '</select>';
    echo '</div>';
}
add_shortcode('multi_currency_switcher', 'multi_currency_switcher_display');
?>