<?php
// filepath: c:\xampp\htdocs\wc-multi-currency-manager-for-WooCommerce\wc-multi-currency-manager\includes\admin\class-payment-settings.php
/**
 * Payment Settings Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class wc_multi_currency_manager_Payment_Settings {
    
    /**
     * Render the payment settings page
     */
    public function render_page() {
        // Process form submissions
        if (isset($_POST['save_payment_settings']) && check_admin_referer('save_payment_settings', 'payment_settings_nonce')) {
            $this->save_payment_settings();
        }
        
        // Get current settings
        $restrictions = get_option('wc_multi_currency_manager_payment_restrictions', array());
        $currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_woocommerce_currency()));
        
        // Display any settings errors/notices
        settings_errors('wc_multi_currency_manager_messages');
        
        ?>
        <div class="wrap">
            <h1>Payment Method Restrictions</h1>
            
            <?php $this->display_admin_tabs('payment'); ?>
            
            <p>Control which payment methods are available for each currency. Check a payment method to disable it for that currency.</p>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Why Restrict Payment Methods?</h2>
                <p>Some payment gateways may charge higher fees for processing certain currencies, or may not support all currencies. By restricting payment methods for specific currencies, you can:</p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Avoid higher processing fees for certain currency/payment method combinations</li>
                    <li>Prevent checkout errors when a payment gateway doesn't support a currency</li>
                    <li>Direct customers to preferred payment methods for each currency</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_payment_settings', 'payment_settings_nonce'); ?>
                
                <?php
                // Check if WooCommerce class exists
                if (!class_exists('WooCommerce')) {
                    echo '<div class="notice notice-error"><p>WooCommerce is not active. Please activate WooCommerce plugin.</p></div>';
                } else {
                    // Make sure payment gateways are loaded
                    $gateways = array();
                    
                    // Load payment gateways in a way that works even if WC isn't fully initialized
                    if (function_exists('WC')) {
                        // Try direct initialization if needed
                        if (!isset(WC()->payment_gateways) || !WC()->payment_gateways) {
                            // If WC() exists but payment gateways aren't loaded, try to initialize them
                            if (!did_action('woocommerce_payment_gateways_loaded')) {
                                // Load WC payment gateways
                                require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-payment-gateways.php');
                                $wc_payment_gateways = new WC_Payment_Gateways();
                                $gateways = $wc_payment_gateways->payment_gateways();
                            }
                        } else {
                            // Use existing WC payment gateways
                            $gateways = WC()->payment_gateways->payment_gateways();
                        }
                    }
                    
                    if (!empty($gateways)) {
                        foreach ($currencies as $currency) {
                            echo "<div class='card' style='margin-top: 20px;'>";
                            echo "<h3>{$currency} Payment Methods</h3>";
                            echo "<p>Select payment methods to <strong>disable</strong> when {$currency} is the active currency:</p>";
                            
                            echo "<table class='widefat' style='max-width: 600px;'>";
                            echo "<thead><tr><th style='width: 30px;'>Disable</th><th>Payment Method</th><th>Description</th></tr></thead>";
                            echo "<tbody>";
                            
                            foreach ($gateways as $gateway_id => $gateway) {
                                $checked = isset($restrictions[$currency]) && in_array($gateway_id, $restrictions[$currency]) ? 'checked' : '';
                                echo "<tr>";
                                echo "<td><input type='checkbox' name='payment_restrictions[{$currency}][]' value='{$gateway_id}' {$checked}></td>";
                                echo "<td><strong>{$gateway->title}</strong></td>";
                                echo "<td>" . wp_kses_post($gateway->description) . "</td>";
                                echo "</tr>";
                            }
                            
                            echo "</tbody></table>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='notice notice-warning'><p>No payment gateways found. This could be because WooCommerce is still initializing or no payment gateways are enabled. Please check your WooCommerce payment settings.</p></div>";
                    }
                }
                ?>
                
                <p class="submit" style="margin-top: 20px;">
                    <input type="submit" name="save_payment_settings" class="button-primary" value="Save Payment Restrictions">
                </p>
            </form>
            
            <div class="card" style="margin-top: 20px;">
                <h2>How It Works</h2>
                <p>When a customer selects a currency on your site:</p>
                <ol style="list-style-type: decimal; margin-left: 20px;">
                    <li>The plugin checks the payment restrictions for that currency</li>
                    <li>Any disabled payment methods are hidden from the checkout page</li>
                    <li>The customer can only choose from the allowed payment methods</li>
                </ol>
                <p><strong>Note:</strong> If you've disabled all payment methods for a currency, the customer will not be able to complete checkout. Make sure to leave at least one payment method available for each currency.</p>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        </style>
        <?php
    }

    /**
     * Save the payment settings
     */
    public function save_payment_settings() {
        // Verify nonce
        if ( ! check_admin_referer('save_payment_settings', 'payment_settings_nonce') ) {
            return;
        }

        // Get the payment restrictions from the form
        $payment_restrictions = isset($_POST['payment_restrictions']) ? $_POST['payment_restrictions'] : array();
        
        // Sanitize the values
        $sanitized_restrictions = array();
        
        foreach ($payment_restrictions as $currency => $gateways) {
            $currency = sanitize_text_field($currency);
            $sanitized_restrictions[$currency] = array_map('sanitize_text_field', $gateways);
        }
        
        // Save the settings
        update_option('wc_multi_currency_manager_payment_restrictions', $sanitized_restrictions);
        
        add_settings_error(
            'wc_multi_currency_manager_messages',
            'payment_settings_updated',
            'Payment restrictions have been updated successfully.',
            'updated'
        );
    }

    /**
     * Save the currencies
     */
    public function save_currencies() {
        // Verify nonce
        if (!check_admin_referer('save_currencies', 'currencies_nonce')) {
            return;
        }

        // Get existing data to preserve settings for currencies being disabled
        $existing_exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $existing_currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());
        $base_currency = get_option('woocommerce_currency', 'USD');
        
        // Initialize arrays
        $enabled_currencies = array($base_currency); // Always include base currency
        $exchange_rates = array($base_currency => 1); // Base currency rate is always 1
        $currency_settings = array();
        
        // Process each currency from the form
        if (isset($_POST['currencies']) && is_array($_POST['currencies'])) {
            foreach ($_POST['currencies'] as $code => $data) {
                // Always include currency settings in case we need them later
                $currency_settings[$code] = array(
                    'position' => isset($data['position']) ? sanitize_text_field($data['position']) : 'left',
                    'decimals' => isset($data['decimals']) ? intval($data['decimals']) : 2,
                    'thousand_sep' => isset($data['thousand_sep']) ? sanitize_text_field($data['thousand_sep']) : ',',
                    'decimal_sep' => isset($data['decimal_sep']) ? sanitize_text_field($data['decimal_sep']) : '.',
                );
                
                // Store exchange rate regardless of enabled status (to preserve values)
                $exchange_rates[$code] = isset($data['rate']) ? floatval($data['rate']) : 1;
                
                // Only add to enabled currencies if checkbox is checked
                if (isset($data['enable']) && $data['enable'] == 1) {
                    // Don't add base currency twice
                    if ($code !== $base_currency) {
                        $enabled_currencies[] = $code;
                    }
                }
            }
        }
        
        // Update options
        update_option('wc_multi_currency_manager_enabled_currencies', $enabled_currencies);
        update_option('wc_multi_currency_manager_exchange_rates', $exchange_rates);
        update_option('wc_multi_currency_manager_currency_settings', $currency_settings);
        
        add_settings_error(
            'wc_multi_currency_manager_messages',
            'currencies_updated',
            'Currencies have been updated successfully.',
            'updated'
        );
    }

    /**
     * Display admin navigation tabs with the current tab highlighted
     * 
     * @param string $current_tab The slug of the current tab
     */
    public function display_admin_tabs($current_tab) {
        $tabs = array(
            'general' => array(
                'url' => 'admin.php?page=wc-multi-currency-manager',
                'label' => 'General Settings'
            ),
            'currencies' => array(
                'url' => 'admin.php?page=wc-multi-currency-manager-currencies',
                'label' => 'Currencies'
            ),
            'style' => array(
                'url' => 'admin.php?page=wc-multi-currency-manager-style',
                'label' => 'Style Settings'
            ),
            'payment' => array(
                'url' => 'admin.php?page=wc-multi-currency-manager-payment',
                'label' => 'Payment Restrictions'
            )
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        
        foreach ($tabs as $tab_id => $tab) {
            $active_class = ($current_tab === $tab_id) ? 'nav-tab-active' : '';
            printf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url(admin_url($tab['url'])),
                esc_attr($active_class),
                esc_html($tab['label'])
            );
        }
        
        echo '</h2>';
    }
}
