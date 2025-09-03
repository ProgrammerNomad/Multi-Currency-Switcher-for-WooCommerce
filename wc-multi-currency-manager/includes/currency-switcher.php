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
        
        // Change this to wp action instead of wp_loaded to ensure cart is fully loaded
        add_action('wp', array($this, 'force_cart_recalculation'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'currency-switcher', plugins_url( '../assets/js/scripts.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_enqueue_style( 'currency-switcher-style', plugins_url( '../assets/css/styles.css', __FILE__ ) );
        
        // Localize script with AJAX URL
        wp_localize_script( 'currency-switcher', 'currencySwitcherAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'currency_switcher_nonce' )
        ));
    }

    public function switch_currency($cart_contents) {
        if (!function_exists('WC') || !WC()->session) {
            return $cart_contents;
        }

        // Get chosen currency (from cookie or geolocation)
        $chosen_currency = WC()->session->get('chosen_currency', '');
        
        // If no currency is set, try to get from geolocation
        if (empty($chosen_currency)) {
            $country = get_user_country();
            $currency = get_currency_by_country($country);
            
            if ($currency && array_key_exists($currency, $this->currencies)) {
                WC()->session->set('chosen_currency', $currency);
                $chosen_currency = $currency;
            } else {
                // Default to WooCommerce base currency
                $chosen_currency = get_woocommerce_currency();
                WC()->session->set('chosen_currency', $chosen_currency);
            }
        }

        // Set a cookie to remember the currency
        if (!isset($_COOKIE['chosen_currency']) || $_COOKIE['chosen_currency'] !== $chosen_currency) {
            setcookie('chosen_currency', $chosen_currency, time() + (86400 * 30), '/');
        }
        
        return $cart_contents;
    }

    public function display_currency_options() {
        $output = '<select id="currency-switcher">';
        foreach ( $this->currencies as $code => $name ) {
            $output .= sprintf( '<option value="%s">%s</option>', esc_attr( $code ), esc_html( $name ) );
        }
        $output .= '</select>';
        echo $output;
    }

    // Update the force_cart_recalculation method 
    public function force_cart_recalculation() {
        // Only run this on frontend, not during AJAX or admin
        if (!is_admin() && !defined('DOING_AJAX') && function_exists('WC') && WC()->cart) {
            WC()->cart->calculate_totals();
        }
    }
}

new CurrencySwitcher();

function wc_multi_currency_manager_display($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'style' => 'dropdown',
        'currencies' => '',
    ), $atts, 'wc_multi_currency_manager');
    
    $style = $atts['style'];
    $selected_currencies = !empty($atts['currencies']) ? explode(',', $atts['currencies']) : array();
    
    // Get all available currencies
    $currencies = get_available_currencies();
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', get_woocommerce_currency()) : get_woocommerce_currency();
    
    // Filter currencies if specified
    if (!empty($selected_currencies)) {
        $filtered_currencies = array();
        foreach ($selected_currencies as $code) {
            if (isset($currencies[$code])) {
                $filtered_currencies[$code] = $currencies[$code];
            }
        }
        $currencies = $filtered_currencies;
    }
    
    $output = '';
    
    // Generate the switcher based on style
    if ($style === 'buttons') {
        $output .= '<div class="currency-switcher currency-switcher-buttons">';
        foreach ($currencies as $code => $name) {
            $active_class = ($code === $current_currency) ? 'active' : '';
            $output .= sprintf(
                '<button type="button" class="currency-button %s" data-currency="%s">%s</button>',
                esc_attr($active_class),
                esc_attr($code),
                esc_html($code)
            );
        }
        $output .= '</div>';
        
        // Add JavaScript for button-based switching
        $output .= "
        <script type='text/javascript'>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.currency-switcher-buttons .currency-button');
            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const currency = this.getAttribute('data-currency');
                    document.cookie = 'chosen_currency=' + currency + '; path=/; max-age=2592000';
                    window.location.reload();
                });
            });
        });
        </script>
        ";
    } else {
        // Default dropdown style
        $output .= '<div class="currency-switcher">';
        $output .= '<select id="currency-selector">';
        
        foreach ($currencies as $code => $name) {
            $selected = ($code === $current_currency) ? 'selected' : '';
            $output .= sprintf('<option value="%s" %s>%s</option>', esc_attr($code), $selected, esc_html($name));
        }
        
        $output .= '</select>';
        $output .= '</div>';
    }
    
    return $output;
}
add_shortcode('wc_multi_currency_manager', 'wc_multi_currency_manager_display');

function wc_multi_currency_manager_filter_payment_gateways($available_gateways) {
    if (!WC()->session) {
        return $available_gateways; // Return default gateways if session is not initialized
    }

    $currency = WC()->session->get('chosen_currency', get_option('woocommerce_currency', 'USD')); // Default to WooCommerce base currency
    $restrictions = get_option('wc_multi_currency_manager_payment_restrictions', []);

    if (isset($restrictions[$currency])) {
        foreach ($restrictions[$currency] as $gateway_id) {
            unset($available_gateways[$gateway_id]);
        }
    }

    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'wc_multi_currency_manager_filter_payment_gateways', 20);

function wc_multi_currency_manager_display_sticky_widget() {
    $style_settings = get_option('wc_multi_currency_manager_style_settings', array(
        'show_sticky_widget' => 'yes',
        'limit_currencies' => 'no',
        'show_flags' => 'none', // Default to no flags
    ));
    
    // Don't display if disabled
    if ($style_settings['show_sticky_widget'] !== 'yes') {
        return;
    }
    
    $currencies = get_available_currencies();
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', get_option('woocommerce_currency', 'USD')) : get_option('woocommerce_currency', 'USD');
    
    echo '<div class="sticky-currency-switcher">';
    echo '<label for="sticky-currency-selector">Currency:</label>';
    echo '<select id="sticky-currency-selector">';
    
    foreach ($currencies as $code => $name) {
        $selected = ($code === $current_currency) ? 'selected' : '';
        echo sprintf(
            '<option value="%s" %s>%s</option>', 
            esc_attr($code), 
            $selected, 
            esc_html($name)
        );
    }
    
    echo '</select>';
    echo '</div>';
}
add_action('wp_footer', 'wc_multi_currency_manager_display_sticky_widget');

function wc_multi_currency_manager_display_on_product_page() {
    // Only display if we're on a single product page
    if (!is_product()) {
        return;
    }
    
    $currencies = get_available_currencies();
    
    // Check if we have any currencies
    if (empty($currencies)) {
        return;
    }
    
    $current_currency = (function_exists('WC') && WC() && WC()->session) ? 
        WC()->session->get('chosen_currency', get_woocommerce_currency()) : get_woocommerce_currency();
    
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

// Register the product page currency switcher after WooCommerce is loaded
function wc_multi_currency_manager_register_product_hooks() {
    // Only add if WooCommerce is available
    if (class_exists('WooCommerce')) {
        add_action('woocommerce_single_product_summary', 'wc_multi_currency_manager_display_on_product_page', 25);
    }
}
add_action('wp_loaded', 'wc_multi_currency_manager_register_product_hooks');

function wc_multi_currency_manager_read_cookie() {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }
    
    $currency_changed = false;
    $available_currencies = get_available_currencies();
    $general_settings = get_option('wc_multi_currency_manager_general_settings', array(
        'auto_detect' => 'yes',
        'default_currency' => get_woocommerce_currency(),
    ));
    
    // First check for currency in the URL (for direct switching)
    if (isset($_GET['currency']) && !empty($_GET['currency'])) {
        $currency = sanitize_text_field($_GET['currency']);
        
        if (array_key_exists($currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $currency);
            setcookie('chosen_currency', $currency, time() + (86400 * 30), '/');
            $currency_changed = true;
        }
    }
    // Then check for existing cookie
    else if (isset($_COOKIE['chosen_currency'])) {
        $currency = sanitize_text_field($_COOKIE['chosen_currency']);
        
        if (array_key_exists($currency, $available_currencies)) {
            $current = WC()->session->get('chosen_currency', '');
            if ($current !== $currency) {
                WC()->session->set('chosen_currency', $currency);
                $currency_changed = true;
            }
        }
    }
    // If no currency set yet, try geolocating (only if auto-detect is enabled)
    else if (!WC()->session->get('chosen_currency') && is_auto_detect_enabled()) {
        $country = get_user_country();
        $currency = get_currency_by_country($country);
        
        if ($currency && array_key_exists($currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $currency);
            setcookie('chosen_currency', $currency, time() + (86400 * 30), '/');
            $currency_changed = true;
        } else {
            // Use default currency
            $default_currency = isset($general_settings['default_currency']) ? $general_settings['default_currency'] : get_woocommerce_currency();
            if (array_key_exists($default_currency, $available_currencies)) {
                WC()->session->set('chosen_currency', $default_currency);
                setcookie('chosen_currency', $default_currency, time() + (86400 * 30), '/');
                $currency_changed = true;
            }
        }
    }
    // If auto-detect is disabled and no currency is set, use the default
    else if (!WC()->session->get('chosen_currency') && (!isset($general_settings['auto_detect']) || $general_settings['auto_detect'] !== 'yes')) {
        $default_currency = isset($general_settings['default_currency']) ? $general_settings['default_currency'] : get_woocommerce_currency();
        if (array_key_exists($default_currency, $available_currencies)) {
            WC()->session->set('chosen_currency', $default_currency);
            setcookie('chosen_currency', $default_currency, time() + (86400 * 30), '/');
            $currency_changed = true;
        }
    }
    
    // Mark a flag in the session that we need to recalculate totals later
    // Rather than doing it now when cart isn't fully loaded
    if ($currency_changed) {
        WC()->session->set('currency_changed', true);
    }
}
// Change the hook from 'init' to 'wp_loaded' to ensure WooCommerce is ready
remove_action('init', 'wc_multi_currency_manager_read_cookie', 20);
add_action('wp_loaded', 'wc_multi_currency_manager_read_cookie', 20);

// Add a new function to handle cart recalculation after WooCommerce is fully loaded
function wc_multi_currency_manager_maybe_recalculate_cart() {
    if (!function_exists('WC') || !WC()->session || !WC()->cart) {
        return;
    }
    
    // Check if currency was changed
    if (WC()->session->get('currency_changed')) {
        // Clear WC fragments cache to force regeneration
        WC_Cache_Helper::get_transient_version('fragments', true);
        
        // Force cart recalculation
        WC()->cart->calculate_totals();
        
        // Reset the flag
        WC()->session->set('currency_changed', false);
    }
}
add_action('woocommerce_cart_loaded_from_session', 'wc_multi_currency_manager_maybe_recalculate_cart', 99);

function wc_multi_currency_manager_add_dynamic_styles() {
    $style_settings = get_option('wc_multi_currency_manager_style_settings', array(
        'title_color' => '#333333',
        'text_color' => '#000000',
        'active_color' => '#04AE93',
        'background_color' => '#FFFFFF',
        'border_color' => '#B2B2B2',
        'show_sticky_widget' => 'yes',
        'sticky_position' => 'left',
        'limit_currencies' => 'no',
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
            " . get_sticky_position_css(isset($style_settings['sticky_position']) ? $style_settings['sticky_position'] : 'left') . "
            background-color: {$style_settings['background_color']};
            border: 1px solid {$style_settings['border_color']};
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 9999;
        }
    </style>
    ";
    
    echo $css;
}
add_action('wp_head', 'wc_multi_currency_manager_add_dynamic_styles');

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

// Add this function to ensure AJAX requests use the correct currency:
function wc_multi_currency_manager_set_ajax_currency() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        if (isset($_COOKIE['chosen_currency'])) {
            $currency = sanitize_text_field($_COOKIE['chosen_currency']);
            $available_currencies = get_available_currencies();
            
            if (array_key_exists($currency, $available_currencies) && function_exists('WC') && WC()->session) {
                WC()->session->set('chosen_currency', $currency);
            }
        }
    }
}
add_action('woocommerce_init', 'wc_multi_currency_manager_set_ajax_currency', 5);

// Add this function to ensure the Storefront theme mini cart is updated
function wc_multi_currency_manager_storefront_compatibility() {
    // Check if Storefront theme is active
    if (function_exists('storefront_is_woocommerce_activated') && function_exists('WC') && WC()->cart) {
        // Add a filter to ensure the cart widget is properly displayed
        add_filter('storefront_cart_link_fragment', function($fragments) {
            ob_start();
            storefront_cart_link();
            $fragments['a.cart-contents'] = ob_get_clean();
            return $fragments;
        });
    }
}
add_action('wp_loaded', 'wc_multi_currency_manager_storefront_compatibility');
