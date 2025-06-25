<?php
// filepath: c:\xampp\htdocs\Multi-Currency-Switcher-for-WooCommerce\multi-currency-switcher\includes\admin\class-admin-settings.php
/**
 * Main Admin Settings Class
 * This class handles the admin menu registration and loads the appropriate settings page.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure we load the individual admin page classes
require_once plugin_dir_path(__FILE__) . 'class-general-settings.php';
require_once plugin_dir_path(__FILE__) . 'class-currencies-settings.php';
require_once plugin_dir_path(__FILE__) . 'class-style-settings.php';
require_once plugin_dir_path(__FILE__) . 'class-payment-settings.php';

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
        
        // Add meta boxes for product-specific currency pricing
        add_action('add_meta_boxes', array($this, 'add_product_currency_meta_boxes'));
        add_action('save_post_product', array($this, 'save_product_currency_prices'));
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

        // Add our admin styles
        wp_enqueue_style('multi-currency-admin-styles', plugins_url('../../assets/css/admin-styles.css', __FILE__));
        
        // Enqueue color picker script and style
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('multi-currency-admin-scripts', plugins_url('../../assets/js/admin-scripts.js', __FILE__), array('jquery', 'wp-color-picker'), false, true);
    }
    
    /**
     * Add meta boxes to product edit screen
     */
    public function add_product_currency_meta_boxes() {
        add_meta_box(
            'product_currency_prices',
            'Currency Prices',
            array($this, 'render_product_currency_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Render the product currency meta box
     */
    public function render_product_currency_meta_box($post) {
        // Nonce field for security
        wp_nonce_field('save_product_currency_prices', 'product_currency_nonce');
        
        // Get current product currency prices
        $currency_prices = get_post_meta($post->ID, '_currency_prices', true);
        $currency_prices = is_array($currency_prices) ? $currency_prices : array();
        
        // Get enabled currencies
        $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array(get_woocommerce_currency()));
        $all_currencies = get_all_available_currencies();
        $base_currency = get_woocommerce_currency();
        ?>
        <div class="currency-prices-meta-box">
            <h4>Set Product Prices by Currency</h4>
            <p>Enter the price for each currency. Leave blank to use the default currency price.</p>
            
            <table class="form-table">
                <tbody>
                    <?php foreach ($enabled_currencies as $code): 
                        $price = isset($currency_prices[$code]) ? $currency_prices[$code] : '';
                        $name = isset($all_currencies[$code]['name']) ? $all_currencies[$code]['name'] : $code;
                    ?>
                    <tr>
                        <th scope="row"><label for="price_<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)</label></th>
                        <td>
                            <input type="text" id="price_<?php echo esc_attr($code); ?>" name="currency_prices[<?php echo esc_attr($code); ?>]" value="<?php echo esc_attr($price); ?>" class="regular-text">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Save product currency prices
     */
    public function save_product_currency_prices($post_id) {
        // Check nonce
        if (!isset($_POST['product_currency_nonce']) || !wp_verify_nonce($_POST['product_currency_nonce'], 'save_product_currency_prices')) {
            return;
        }
        
        // Save the currency prices
        $currency_prices = isset($_POST['currency_prices']) ? array_map('sanitize_text_field', $_POST['currency_prices']) : array();
        update_post_meta($post_id, '_currency_prices', $currency_prices);
    }
}

// Initialize the main admin settings class
new Multi_Currency_Switcher_Admin_Settings();