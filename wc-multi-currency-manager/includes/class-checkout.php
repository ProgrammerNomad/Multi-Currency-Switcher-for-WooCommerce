<?php
/**
 * Checkout Currency Management
 * Enhanced checkout functionality for multi-currency
 *
 * @package WC_Multi_Currency_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Multi_Currency_Manager_Checkout {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Checkout page enhancements
        add_action('woocommerce_before_checkout_form', array($this, 'display_checkout_currency_info'));
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_currency'));
        
        // Payment gateway filtering
        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_payment_gateways_by_currency'));
        
        // Checkout field modifications
        add_filter('woocommerce_checkout_fields', array($this, 'add_currency_checkout_field'));
        
        // Ajax checkout validation
        add_action('wp_ajax_validate_checkout_currency', array($this, 'ajax_validate_checkout_currency'));
        add_action('wp_ajax_nopriv_validate_checkout_currency', array($this, 'ajax_validate_checkout_currency'));
        
        // Thank you page
        add_action('woocommerce_thankyou', array($this, 'display_order_currency_summary'), 5);
        
        // Checkout scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_scripts'));
    }
    
    /**
     * Display currency information on checkout page
     */
    public function display_checkout_currency_info() {
        $current_currency = $this->get_current_currency();
        $base_currency = get_woocommerce_currency();
        
        if ($current_currency !== $base_currency) {
            $exchange_rate = wc_multi_currency_manager_get_exchange_rate($current_currency);
            $all_currencies = get_all_available_currencies();
            $currency_name = isset($all_currencies[$current_currency]['name']) ? $all_currencies[$current_currency]['name'] : $current_currency;
            
            ?>
            <div class="checkout-currency-info woocommerce-info" style="margin-bottom: 20px;">
                <h3><?php _e('Checkout Currency Information', 'wc-multi-currency-manager'); ?></h3>
                <p>
                    <strong><?php _e('You are checking out in:', 'wc-multi-currency-manager'); ?></strong> 
                    <?php echo esc_html($currency_name . ' (' . $current_currency . ')'); ?>
                </p>
                <?php if ($exchange_rate && $exchange_rate != 1): ?>
                <p>
                    <strong><?php _e('Exchange Rate:', 'wc-multi-currency-manager'); ?></strong> 
                    1 <?php echo esc_html($base_currency); ?> = <?php echo esc_html($exchange_rate); ?> <?php echo esc_html($current_currency); ?>
                </p>
                <?php endif; ?>
                <p class="checkout-currency-notice">
                    <em><?php _e('Currency cannot be changed during checkout. Please go back to continue shopping if you need to change currency.', 'wc-multi-currency-manager'); ?></em>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Validate currency consistency during checkout
     */
    public function validate_checkout_currency() {
        $session_currency = WC()->session->get('chosen_currency', get_woocommerce_currency());
        $cart_currency = $this->get_cart_currency();
        
        if ($session_currency !== $cart_currency) {
            wc_add_notice(__('Currency mismatch detected. Please refresh the page and try again.', 'wc-multi-currency-manager'), 'error');
        }
    }
    
    /**
     * Filter payment gateways based on currency
     */
    public function filter_payment_gateways_by_currency($gateways) {
        $current_currency = $this->get_current_currency();
        
        // Get currency-specific gateway settings
        $currency_gateways = get_option('wc_multi_currency_manager_payment_gateways', array());
        
        if (isset($currency_gateways[$current_currency])) {
            $allowed_gateways = $currency_gateways[$current_currency];
            
            foreach ($gateways as $gateway_id => $gateway) {
                if (!in_array($gateway_id, $allowed_gateways)) {
                    unset($gateways[$gateway_id]);
                }
            }
        }
        
        return $gateways;
    }
    
    /**
     * Add hidden currency field to checkout
     */
    public function add_currency_checkout_field($fields) {
        $fields['billing']['checkout_currency'] = array(
            'type' => 'hidden',
            'default' => $this->get_current_currency(),
            'class' => array('checkout-currency-field')
        );
        
        return $fields;
    }
    
    /**
     * AJAX validate checkout currency
     */
    public function ajax_validate_checkout_currency() {
        check_ajax_referer('checkout_currency_nonce', 'security');
        
        $posted_currency = sanitize_text_field($_POST['currency']);
        $session_currency = $this->get_current_currency();
        
        if ($posted_currency !== $session_currency) {
            wp_send_json_error(array(
                'message' => __('Currency has changed. Please refresh the page.', 'wc-multi-currency-manager')
            ));
        }
        
        wp_send_json_success();
    }
    
    /**
     * Display order currency summary on thank you page
     */
    public function display_order_currency_summary($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $order_currency = $order->get_currency();
        $base_currency = get_woocommerce_currency();
        
        if ($order_currency !== $base_currency) {
            $exchange_rate = $order->get_meta('_order_exchange_rate');
            $all_currencies = get_all_available_currencies();
            $currency_name = isset($all_currencies[$order_currency]['name']) ? $all_currencies[$order_currency]['name'] : $order_currency;
            
            ?>
            <div class="order-currency-summary woocommerce-message woocommerce-message--info" style="margin: 20px 0;">
                <h3><?php _e('Order Currency Information', 'wc-multi-currency-manager'); ?></h3>
                <p>
                    <strong><?php _e('This order was placed in:', 'wc-multi-currency-manager'); ?></strong> 
                    <?php echo esc_html($currency_name . ' (' . $order_currency . ')'); ?>
                </p>
                <?php if ($exchange_rate && $exchange_rate != 1): ?>
                <p>
                    <strong><?php _e('Exchange Rate Used:', 'wc-multi-currency-manager'); ?></strong> 
                    1 <?php echo esc_html($base_currency); ?> = <?php echo esc_html($exchange_rate); ?> <?php echo esc_html($order_currency); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Enqueue checkout-specific scripts
     */
    public function enqueue_checkout_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('wc-multi-currency-checkout', 
                plugins_url('../assets/js/checkout.js', __FILE__), 
                array('jquery', 'wc-checkout'), 
                '1.0.1', 
                true
            );
            
            wp_localize_script('wc-multi-currency-checkout', 'wcMultiCurrencyCheckout', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('checkout_currency_nonce'),
                'current_currency' => $this->get_current_currency(),
                'messages' => array(
                    'currency_changed' => __('Currency has changed during checkout. Please refresh the page.', 'wc-multi-currency-manager'),
                    'validation_error' => __('Currency validation failed. Please try again.', 'wc-multi-currency-manager')
                )
            ));
        }
    }
    
    /**
     * Get current currency
     */
    private function get_current_currency() {
        if (function_exists('WC') && WC()->session) {
            return WC()->session->get('chosen_currency', get_woocommerce_currency());
        }
        return get_woocommerce_currency();
    }
    
    /**
     * Get cart currency (for validation)
     */
    private function get_cart_currency() {
        // This should match the currency used for cart calculations
        return $this->get_current_currency();
    }
}

// Initialize the checkout class
new WC_Multi_Currency_Manager_Checkout();
