<?php
/**
 * Enhanced Cart Management
 * Handles cart and mini-cart currency conversion with improved performance
 *
 * @package WC_Multi_Currency_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Multi_Currency_Manager_Cart {
    
    private $is_processing = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Cart price updates
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_prices'), 99);
        
        // Mini cart fragments
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'enhanced_cart_fragments'), 999);
        
        // Cart page enhancements
        add_action('woocommerce_before_cart', array($this, 'display_cart_currency_info'));
        add_filter('woocommerce_cart_totals_coupon_html', array($this, 'convert_coupon_display'), 10, 3);
        
        // AJAX cart updates
        add_action('wp_ajax_update_mini_cart_currency', array($this, 'ajax_update_mini_cart'));
        add_action('wp_ajax_nopriv_update_mini_cart_currency', array($this, 'ajax_update_mini_cart'));
        
        // Add to cart handling
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'add_currency_to_fragments'), 1000);
        
        // Cart widget updates
        add_action('wp_enqueue_scripts', array($this, 'enqueue_cart_scripts'));
    }
    
    /**
     * Update cart item prices with current currency
     */
    public function update_cart_prices($cart) {
        if (admin_url() || $this->is_processing) {
            return;
        }
        
        $this->is_processing = true;
        
        try {
            $current_currency = $this->get_current_currency();
            $base_currency = get_woocommerce_currency();
            
            if ($current_currency === $base_currency) {
                $this->is_processing = false;
                return;
            }
            
            $exchange_rate = wc_multi_currency_manager_get_exchange_rate($current_currency);
            
            if (!$exchange_rate || $exchange_rate <= 0) {
                $this->is_processing = false;
                return;
            }
            
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                
                if (!$product) {
                    continue;
                }
                
                // Get original prices
                $original_price = $this->get_original_product_price($product);
                $original_regular_price = $this->get_original_regular_price($product);
                $original_sale_price = $this->get_original_sale_price($product);
                
                if ($original_price) {
                    $converted_price = floatval($original_price) * floatval($exchange_rate);
                    $product->set_price($converted_price);
                }
                
                if ($original_regular_price) {
                    $converted_regular = floatval($original_regular_price) * floatval($exchange_rate);
                    $product->set_regular_price($converted_regular);
                }
                
                if ($original_sale_price) {
                    $converted_sale = floatval($original_sale_price) * floatval($exchange_rate);
                    $product->set_sale_price($converted_sale);
                }
            }
            
        } catch (Exception $e) {
            error_log('Cart price update error: ' . $e->getMessage());
        } finally {
            $this->is_processing = false;
        }
    }
    
    /**
     * Enhanced cart fragments for better mini-cart updates
     */
    public function enhanced_cart_fragments($fragments) {
        if (!function_exists('WC') || !WC()->cart) {
            return $fragments;
        }
        
        $current_currency = $this->get_current_currency();
        
        // Add currency-specific keys to force proper cache invalidation
        $currency_fragments = array();
        foreach ($fragments as $key => $fragment) {
            $currency_key = $key . '_currency_' . $current_currency;
            $currency_fragments[$currency_key] = $fragment;
        }
        
        // Merge with original fragments
        $fragments = array_merge($fragments, $currency_fragments);
        
        // Add specific cart count and subtotal fragments with currency
        ob_start();
        ?>
        <span class="cart-contents-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        <?php
        $fragments['span.cart-contents-count'] = ob_get_clean();
        
        // Add cart subtotal fragment
        ob_start();
        ?>
        <span class="cart-subtotal"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
        <?php
        $fragments['span.cart-subtotal'] = ob_get_clean();
        
        // Add currency indicator
        $fragments['[data-currency]'] = '<span data-currency="' . esc_attr($current_currency) . '"></span>';
        
        return $fragments;
    }
    
    /**
     * Display currency information on cart page
     */
    public function display_cart_currency_info() {
        $current_currency = $this->get_current_currency();
        $base_currency = get_woocommerce_currency();
        
        if ($current_currency !== $base_currency) {
            $exchange_rate = wc_multi_currency_manager_get_exchange_rate($current_currency);
            $all_currencies = get_all_available_currencies();
            $currency_name = isset($all_currencies[$current_currency]['name']) ? $all_currencies[$current_currency]['name'] : $current_currency;
            
            ?>
            <div class="cart-currency-info woocommerce-message woocommerce-message--info">
                <h4><?php _e('Shopping Cart Currency', 'wc-multi-currency-manager'); ?></h4>
                <p>
                    <strong><?php _e('You are shopping in:', 'wc-multi-currency-manager'); ?></strong> 
                    <?php echo esc_html($currency_name . ' (' . $current_currency . ')'); ?>
                </p>
                <?php if ($exchange_rate && $exchange_rate != 1): ?>
                <p>
                    <strong><?php _e('Exchange Rate:', 'wc-multi-currency-manager'); ?></strong> 
                    1 <?php echo esc_html($base_currency); ?> = <?php echo esc_html($exchange_rate); ?> <?php echo esc_html($current_currency); ?>
                </p>
                <?php endif; ?>
                <p class="cart-currency-switch">
                    <a href="<?php echo esc_url(add_query_arg('switch_currency', 'show')); ?>" class="button button-secondary">
                        <?php _e('Change Currency', 'wc-multi-currency-manager'); ?>
                    </a>
                </p>
            </div>
            
            <style>
            .cart-currency-info {
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #0073aa;
            }
            .cart-currency-info h4 {
                margin-top: 0;
                color: #0073aa;
            }
            .cart-currency-switch {
                margin-bottom: 0;
            }
            </style>
            <?php
        }
    }
    
    /**
     * Convert coupon amounts for display
     */
    public function convert_coupon_display($coupon_html, $coupon, $discount_amount_html) {
        $current_currency = $this->get_current_currency();
        $base_currency = get_woocommerce_currency();
        
        if ($current_currency !== $base_currency) {
            // The discount amount is already converted by WooCommerce filters
            // We just ensure proper display
            return $coupon_html;
        }
        
        return $coupon_html;
    }
    
    /**
     * AJAX handler for mini cart updates
     */
    public function ajax_update_mini_cart() {
        check_ajax_referer('update_mini_cart_nonce', 'security');
        
        try {
            // Increase memory limit temporarily if possible
            if (function_exists('wp_raise_memory_limit')) {
                wp_raise_memory_limit('admin');
            }
            
            // Force cart calculation
            if (function_exists('WC') && WC()->cart) {
                WC()->cart->calculate_totals();
                
                // Get refreshed fragments with error handling
                $fragments = array();
                
                // Try to get fragments, but catch any memory errors
                if (class_exists('WC_AJAX')) {
                    $fragments = WC_AJAX::get_refreshed_fragments();
                } else {
                    // Fallback: just return basic cart info
                    $fragments = array(
                        '.cart-contents' => WC()->cart->get_cart_contents_count(),
                        '.amount' => WC()->cart->get_cart_total()
                    );
                }
                
                wp_send_json_success(array(
                    'fragments' => $fragments,
                    'cart_hash' => WC()->cart->get_cart_hash()
                ));
            }
            
            wp_send_json_error('Cart not available');
            
        } catch (Exception $e) {
            error_log('Mini cart AJAX error: ' . $e->getMessage());
            wp_send_json_error('Memory or processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Add currency information to cart fragments
     */
    public function add_currency_to_fragments($fragments) {
        $current_currency = $this->get_current_currency();
        
        // Add current currency as a fragment for JavaScript access
        $fragments['[data-current-currency]'] = '<span data-current-currency="' . esc_attr($current_currency) . '" style="display:none;"></span>';
        
        return $fragments;
    }
    
    /**
     * Enqueue cart-specific scripts
     */
    public function enqueue_cart_scripts() {
        if (is_cart() || is_checkout()) {
            wp_enqueue_script('wc-multi-currency-cart', 
                plugins_url('../assets/js/cart.js', __FILE__), 
                array('jquery', 'wc-cart'), 
                '1.0.1', 
                true
            );
            
            wp_localize_script('wc-multi-currency-cart', 'wcMultiCurrencyCart', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('update_mini_cart_nonce'),
                'current_currency' => $this->get_current_currency(),
                'messages' => array(
                    'updating_cart' => __('Updating cart...', 'wc-multi-currency-manager'),
                    'update_failed' => __('Failed to update cart. Please refresh the page.', 'wc-multi-currency-manager')
                )
            ));
        }
    }
    
    /**
     * Get original product price before conversion
     */
    private function get_original_product_price($product) {
        // Try to get the original price from meta or property
        $original_price = $product->get_meta('_original_price');
        if (!$original_price) {
            // Fall back to current price (might already be converted)
            $original_price = $product->get_price();
        }
        return $original_price;
    }
    
    /**
     * Get original regular price
     */
    private function get_original_regular_price($product) {
        $original_price = $product->get_meta('_original_regular_price');
        if (!$original_price) {
            $original_price = $product->get_regular_price();
        }
        return $original_price;
    }
    
    /**
     * Get original sale price
     */
    private function get_original_sale_price($product) {
        $original_price = $product->get_meta('_original_sale_price');
        if (!$original_price) {
            $original_price = $product->get_sale_price();
        }
        return $original_price;
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

// Initialize the cart class
new WC_Multi_Currency_Manager_Cart();
