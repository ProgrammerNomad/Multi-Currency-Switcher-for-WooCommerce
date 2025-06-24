<?php
/**
 * Currency Switcher functionality for WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available
if ( ! function_exists( 'get_available_currencies' ) ) {
    // If for some reason helpers.php wasn't loaded first, load it now
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers.php';
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
        if (!function_exists('WC') || !WC() || !WC()->session) {
            return;
        }

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
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', 'USD') : 'USD';
    
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
    $style_settings = get_option('multi_currency_switcher_style_settings', array(
        'show_sticky_widget' => 'yes',
        'limit_currencies' => 'no',
        'show_flags' => 'left',
    ));
    
    // Don't display if disabled
    if ($style_settings['show_sticky_widget'] !== 'yes') {
        return;
    }
    
    $currencies = get_available_currencies();
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', 'USD') : 'USD';
    
    // Add flag class based on settings
    $flag_class = '';
    if ($style_settings['show_flags'] !== 'none') {
        $flag_class = 'flag-' . $style_settings['show_flags'];
    }
    
    echo '<div class="sticky-currency-switcher ' . esc_attr($flag_class) . '">';
    echo '<label for="sticky-currency-selector">Currency:</label>';
    echo '<select id="sticky-currency-selector">';
    
    foreach ($currencies as $code => $name) {
        $selected = ($code === $current_currency) ? 'selected' : '';
        $flag_html = '';
        
        // Add flag if enabled
        if ($style_settings['show_flags'] !== 'none') {
            $country_code = strtolower(get_country_code_for_currency($code));
            $flag_html = '<span class="currency-flag" style="background-image: url(' . 
                         plugins_url('/assets/flags/' . $country_code . '.png', dirname(__FILE__)) . 
                         ');"></span>';
        }
        
        // Flag position based on settings
        if ($style_settings['show_flags'] === 'left') {
            echo sprintf(
                '<option value="%s" %s>%s %s</option>', 
                esc_attr($code), 
                $selected, 
                $flag_html, 
                esc_html($name)
            );
        } else if ($style_settings['show_flags'] === 'right') {
            echo sprintf(
                '<option value="%s" %s>%s %s</option>', 
                esc_attr($code), 
                $selected, 
                esc_html($name), 
                $flag_html
            );
        } else {
            echo sprintf(
                '<option value="%s" %s>%s</option>', 
                esc_attr($code), 
                $selected, 
                esc_html($name)
            );
        }
    }
    
    echo '</select>';
    echo '</div>';
}
add_action('wp_footer', 'multi_currency_switcher_display_sticky_widget');

function multi_currency_switcher_display_on_product_page() {
    $currencies = get_available_currencies();
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', 'USD') : 'USD';
    
    echo '<div class="product-currency-switcher">';
    echo '<label for="product-currency-selector">Currency:</label>';
    echo '<select id="product-currency-selector">';
    
    foreach ($currencies as $code => $name) {
        $selected = ($code === $current_currency) ? 'selected' : '';
        echo sprintf('<option value="%s" %s>%s</option>', esc_attr($code), $selected, esc_html($name));
    }
    
    echo '</select>';
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'multi_currency_switcher_display_on_product_page', 25);

function multi_currency_switcher_read_cookie() {
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }
    
    // Check if the currency cookie exists
    if (isset($_COOKIE['chosen_currency'])) {
        $currency = sanitize_text_field($_COOKIE['chosen_currency']);
        $available_currencies = get_available_currencies();
        
        // Ensure the currency is valid
        if (array_key_exists($currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $currency);
        }
    }
}
add_action('init', 'multi_currency_switcher_read_cookie', 20);

function multi_currency_switcher_add_dynamic_styles() {
    $style_settings = get_option('multi_currency_switcher_style_settings', array(
        'title_color' => '#333333',
        'text_color' => '#000000',
        'active_color' => '#04AE93',
        'background_color' => '#FFFFFF',
        'border_color' => '#B2B2B2',
        'show_sticky_widget' => 'yes',
        'sticky_position' => 'left',
        'limit_currencies' => 'no',
        'show_flags' => 'left',
    ));
    
    $css = "
    <style type='text/css'>
        .currency-switcher label,
        .product-currency-switcher label,
        .sticky-currency-switcher label {
            color: {$style_settings['title_color']};
        }
        
        .currency-switcher select,
        .product-currency-switcher select,
        .sticky-currency-switcher select {
            color: {$style_settings['text_color']};
            background-color: {$style_settings['background_color']};
            border: 1px solid {$style_settings['border_color']};
        }
        
        .currency-switcher select option:checked,
        .product-currency-switcher select option:checked,
        .sticky-currency-switcher select option:checked {
            background-color: {$style_settings['active_color']};
            color: white;
        }
        
        .sticky-currency-switcher {
            " . get_sticky_position_css($style_settings['sticky_position']) . "
            background-color: {$style_settings['background_color']};
            border: 1px solid {$style_settings['border_color']};
        }
    </style>
    ";
    
    echo $css;
}
add_action('wp_head', 'multi_currency_switcher_add_dynamic_styles');

// Helper function to get sticky position CSS
function get_sticky_position_css($position) {
    switch ($position) {
        case 'left':
            return 'left: 20px; top: 50%; transform: translateY(-50%);';
        case 'right':
            return 'right: 20px; top: 50%; transform: translateY(-50%);';
        case 'top':
            return 'top: 20px; left: 50%; transform: translateX(-50%);';
        case 'bottom':
            return 'bottom: 20px; left: 50%; transform: translateX(-50%);';
        default:
            return 'left: 20px; top: 50%; transform: translateY(-50%);';
    }
}

// Helper function to get country code from currency
function get_country_code_for_currency($currency) {
    $all_currencies = get_all_available_currencies();
    if (isset($all_currencies[$currency]) && isset($all_currencies[$currency]['country_code'])) {
        return $all_currencies[$currency]['country_code'];
    }
    return 'us'; // Default to US flag
}
?>