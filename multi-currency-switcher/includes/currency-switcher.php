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
        $this->currencies = get_available_currencies();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_calculate_totals', array($this, 'switch_currency'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'currency-switcher', plugins_url( '../assets/js/scripts.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_enqueue_style( 'currency-switcher-style', plugins_url( '../assets/css/styles.css', __FILE__ ) );
    }

    public function switch_currency() {
        $country = get_user_country();
        $currency = get_currency_by_country($country);

        if ($currency && array_key_exists($currency, $this->currencies)) {
            // Set the currency based on geolocation
            WC()->session->set('chosen_currency', $currency);
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
    $currencies = get_available_currencies();
    $current_currency = WC()->session->get('chosen_currency', 'USD');
    
    echo '<div class="currency-switcher">';
    echo '<select id="currency-selector">';
    
    foreach ($currencies as $code => $name) {
        $selected = ($code === $current_currency) ? 'selected' : '';
        echo sprintf('<option value="%s" %s>%s</option>', esc_attr($code), $selected, esc_html($name));
    }
    
    echo '</select>';
    echo '</div>';
}
add_shortcode('multi_currency_switcher', 'multi_currency_switcher_display');

function multi_currency_switcher_filter_payment_gateways($available_gateways) {
    if (!WC()->session) {
        return $available_gateways; // Return default gateways if session is not initialized
    }

    $currency = WC()->session->get('chosen_currency', 'USD'); // Default to USD
    $restrictions = get_option('multi_currency_switcher_payment_restrictions', []);

    if (isset($restrictions[$currency])) {
        foreach ($restrictions[$currency] as $gateway_id) {
            unset($available_gateways[$gateway_id]);
        }
    }

    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'multi_currency_switcher_filter_payment_gateways', 20); // Ensure it runs after WooCommerce initialization

function multi_currency_switcher_display_sticky_widget() {
    echo '<div class="sticky-currency-switcher">';
    echo '<label for="sticky-currency-selector">Currency:</label>';
    echo '<select id="sticky-currency-selector">';
    foreach (get_available_currencies() as $code => $name) {
        echo sprintf('<option value="%s">%s</option>', esc_attr($code), esc_html($name));
    }
    echo '</select>';
    echo '</div>';
}
add_action('wp_footer', 'multi_currency_switcher_display_sticky_widget');

function multi_currency_switcher_display_on_product_page() {
    echo '<div class="product-currency-switcher">';
    echo '<label for="product-currency-selector">Currency:</label>';
    echo '<select id="product-currency-selector">';
    foreach (get_available_currencies() as $code => $name) {
        echo sprintf('<option value="%s">%s</option>', esc_attr($code), esc_html($name));
    }
    echo '</select>';
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'multi_currency_switcher_display_on_product_page', 25);
?>