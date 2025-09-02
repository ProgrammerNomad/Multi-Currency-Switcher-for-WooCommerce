<?php
/**
 * Orders Management Class
 * Handles currency in orders, order history, and checkout process
 *
 * @package WC_Multi_Currency_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Multi_Currency_Manager_Orders {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Order creation and management
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_order_currency'), 10, 2);
        add_action('woocommerce_new_order', array($this, 'save_order_currency_meta'), 10, 1);
        
        // Order display in admin
        add_filter('woocommerce_admin_order_data_after_order_details', array($this, 'display_order_currency_admin'), 10, 1);
        
        // Order history on frontend
        add_filter('woocommerce_my_account_my_orders_column_order-total', array($this, 'format_order_total_my_account'), 10, 1);
        
        // Order received page
        add_action('woocommerce_thankyou', array($this, 'set_order_currency_on_thankyou'), 1, 1);
        
        // Email formatting
        add_filter('woocommerce_email_order_details', array($this, 'set_currency_for_order_emails'), 1, 4);
        
        // Checkout process
        add_action('woocommerce_checkout_init', array($this, 'lock_currency_during_checkout'));
        add_action('wp_footer', array($this, 'checkout_currency_lock_script'));
        
        // Order columns in admin
        add_filter('manage_shop_order_posts_columns', array($this, 'add_currency_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_currency_column'), 10, 2);
        
        // HPOS support
        add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_currency_column'));
        add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'display_currency_column_hpos'), 10, 2);
    }
    
    /**
     * Save currency information when order is created
     */
    public function save_order_currency($order_id, $data = null) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Get current currency
        $currency = $this->get_current_currency();
        
        // Save currency meta
        $order->update_meta_data('_order_currency_used', $currency);
        $order->update_meta_data('_order_exchange_rate', wc_multi_currency_manager_get_exchange_rate($currency));
        $order->update_meta_data('_order_base_currency', get_woocommerce_currency());
        
        // Set the order currency
        $order->set_currency($currency);
        $order->save();
        
        // Log for debugging
        error_log("Order {$order_id} saved with currency: {$currency}");
    }
    
    /**
     * Save currency meta data for new orders
     */
    public function save_order_currency_meta($order_id) {
        $this->save_order_currency($order_id);
    }
    
    /**
     * Display currency information in admin order details
     */
    public function display_order_currency_admin($order) {
        $currency_used = $order->get_meta('_order_currency_used');
        $exchange_rate = $order->get_meta('_order_exchange_rate');
        $base_currency = $order->get_meta('_order_base_currency');
        
        if ($currency_used && $currency_used !== get_woocommerce_currency()) {
            ?>
            <div class="order-currency-info" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 4px solid #0073aa;">
                <h4><?php _e('Currency Information', 'wc-multi-currency-manager'); ?></h4>
                <p><strong><?php _e('Order Currency:', 'wc-multi-currency-manager'); ?></strong> <?php echo esc_html($currency_used); ?></p>
                <?php if ($exchange_rate): ?>
                    <p><strong><?php _e('Exchange Rate:', 'wc-multi-currency-manager'); ?></strong> <?php echo esc_html($exchange_rate); ?></p>
                <?php endif; ?>
                <?php if ($base_currency): ?>
                    <p><strong><?php _e('Base Currency:', 'wc-multi-currency-manager'); ?></strong> <?php echo esc_html($base_currency); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Format order total in My Account page with correct currency
     */
    public function format_order_total_my_account($order) {
        if (!is_a($order, 'WC_Order')) {
            return $order;
        }
        
        $order_currency = $order->get_currency();
        $current_currency = $this->get_current_currency();
        
        // Temporarily set the currency for proper formatting
        if ($order_currency && $order_currency !== $current_currency) {
            add_filter('woocommerce_currency', function() use ($order_currency) {
                return $order_currency;
            }, 999);
            
            $formatted_total = $order->get_formatted_order_total();
            
            // Remove the temporary filter
            remove_all_filters('woocommerce_currency', 999);
            
            return $formatted_total;
        }
        
        return $order->get_formatted_order_total();
    }
    
    /**
     * Set currency on order received page
     */
    public function set_order_currency_on_thankyou($order_id) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $order_currency = $order->get_currency();
        
        // Set the currency filter for this page
        add_filter('woocommerce_currency', function() use ($order_currency) {
            return $order_currency;
        }, 999);
        
        // Also set currency symbol
        add_filter('woocommerce_currency_symbol', function($symbol, $currency) use ($order_currency) {
            if ($currency === $order_currency) {
                $all_currencies = get_all_available_currencies();
                if (isset($all_currencies[$order_currency]['symbol'])) {
                    return $all_currencies[$order_currency]['symbol'];
                }
            }
            return $symbol;
        }, 999, 2);
    }
    
    /**
     * Set currency for order emails
     */
    public function set_currency_for_order_emails($order, $sent_to_admin, $plain_text, $email) {
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        
        $order_currency = $order->get_currency();
        
        // Set currency for email formatting
        add_filter('woocommerce_currency', function() use ($order_currency) {
            return $order_currency;
        }, 999);
    }
    
    /**
     * Lock currency during checkout process
     */
    public function lock_currency_during_checkout() {
        if (is_checkout() && !is_order_received_page()) {
            // Prevent currency switching during checkout
            remove_action('wp_ajax_wc_multi_currency_switch', 'wc_multi_currency_manager_handle_currency_switch');
            remove_action('wp_ajax_nopriv_wc_multi_currency_switch', 'wc_multi_currency_manager_handle_currency_switch');
        }
    }
    
    /**
     * Add JavaScript to prevent currency switching on checkout
     */
    public function checkout_currency_lock_script() {
        if (is_checkout() && !is_order_received_page()) {
            ?>
            <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Disable all currency switchers on checkout page
                const currencySelectors = document.querySelectorAll('#currency-selector, #sticky-currency-selector, #product-currency-selector, .currency-switcher select');
                currencySelectors.forEach(function(selector) {
                    if (selector) {
                        selector.disabled = true;
                        selector.style.opacity = '0.6';
                        selector.style.cursor = 'not-allowed';
                    }
                });
                
                // Add warning message
                const checkoutForm = document.querySelector('.woocommerce-checkout');
                if (checkoutForm && currencySelectors.length > 0) {
                    const warning = document.createElement('div');
                    warning.className = 'woocommerce-message woocommerce-message--info';
                    warning.innerHTML = '<?php _e("Currency is locked during checkout to ensure accurate pricing.", "wc-multi-currency-manager"); ?>';
                    warning.style.marginBottom = '20px';
                    checkoutForm.insertBefore(warning, checkoutForm.firstChild);
                }
            });
            </script>
            <style>
            .checkout .currency-switcher select:disabled,
            .checkout #currency-selector:disabled,
            .checkout #sticky-currency-selector:disabled {
                opacity: 0.6 !important;
                cursor: not-allowed !important;
            }
            </style>
            <?php
        }
    }
    
    /**
     * Add currency column to order list
     */
    public function add_currency_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_total') {
                $new_columns['order_currency'] = __('Currency', 'wc-multi-currency-manager');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display currency in order list column (Legacy)
     */
    public function display_currency_column($column, $post_id) {
        if ($column === 'order_currency') {
            $order = wc_get_order($post_id);
            if ($order) {
                $currency = $order->get_currency();
                $exchange_rate = $order->get_meta('_order_exchange_rate');
                
                echo esc_html($currency);
                if ($exchange_rate && $exchange_rate != 1) {
                    echo '<br><small>' . sprintf(__('Rate: %s', 'wc-multi-currency-manager'), esc_html($exchange_rate)) . '</small>';
                }
            }
        }
    }
    
    /**
     * Display currency in order list column (HPOS)
     */
    public function display_currency_column_hpos($column, $order) {
        if ($column === 'order_currency' && is_a($order, 'WC_Order')) {
            $currency = $order->get_currency();
            $exchange_rate = $order->get_meta('_order_exchange_rate');
            
            echo esc_html($currency);
            if ($exchange_rate && $exchange_rate != 1) {
                echo '<br><small>' . sprintf(__('Rate: %s', 'wc-multi-currency-manager'), esc_html($exchange_rate)) . '</small>';
            }
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
}

// Initialize the orders class
new WC_Multi_Currency_Manager_Orders();
