<?php
// filepath: c:\xampp\htdocs\wc-multi-currency-manager-for-WooCommerce\wc-multi-currency-manager\includes\admin\class-general-settings.php
/**
 * General Settings Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class wc_multi_currency_manager_General_Settings {

    /**
     * Render the general settings page
     */
    public function render_page() {
        // Get current settings
        $general_settings = get_option('wc_multi_currency_manager_general_settings', array(
            'auto_detect' => 'yes',
            'widget_position' => 'both',
            'default_currency' => get_woocommerce_currency(),
        ));
        
        // Get exchange rate data
        $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $last_updated = get_option('wc_multi_currency_manager_rates_last_updated', 0);
        $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';
        
        // Process form submissions
        if (isset($_POST['save_general_settings']) && check_admin_referer('save_general_settings', 'general_settings_nonce')) {
            $general_settings = array(
                'auto_detect' => isset($_POST['general_settings']['auto_detect']) ? 'yes' : 'no',
                'widget_position' => sanitize_text_field($_POST['general_settings']['widget_position']),
                'default_currency' => sanitize_text_field($_POST['general_settings']['default_currency']),
            );
            
            update_option('wc_multi_currency_manager_general_settings', $general_settings);
            
            // Show success message
            add_settings_error(
                'wc_multi_currency_manager_messages',
                'settings_updated',
                'Settings saved successfully.',
                'updated'
            );
        }
        
        // Handle manual exchange rate update
        if (isset($_POST['update_exchange_rates']) && check_admin_referer('update_exchange_rates', 'update_rates_nonce')) {
            $updated = wc_multi_currency_manager_update_all_exchange_rates();
            
            if ($updated) {
                add_settings_error(
                    'wc_multi_currency_manager_messages',
                    'rates_updated',
                    'Exchange rates have been updated successfully.',
                    'updated'
                );
                
                // Refresh the exchange rates
                $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
                $last_updated = get_option('wc_multi_currency_manager_rates_last_updated', 0);
                $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';
            } else {
                add_settings_error(
                    'wc_multi_currency_manager_messages',
                    'rates_update_failed',
                    'Failed to update exchange rates. Please try again later.',
                    'error'
                );
            }
        }
        
        // Get enabled currencies
        $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_woocommerce_currency()));
        $all_currencies = get_all_available_currencies();
        $base_currency = get_woocommerce_currency();
        
        // Display the settings page
        settings_errors('wc_multi_currency_manager_messages');
        ?>
        <div class="wrap">
            <h1>WC Multi Currency Manager</h1>
            
            <?php $this->display_admin_tabs('general'); ?>
            
            <div class="card">
                <h2>Plugin Overview</h2>
                <p>WC Multi Currency Manager allows your customers to shop in their preferred currency. Key features include:</p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Support for multiple currencies with automatic exchange rate updates</li>
                    <li>Geolocation-based currency detection</li>
                    <li>Custom currency formatting options</li>
                    <li>Product-specific pricing for each currency</li>
                    <li>Currency switcher widgets and shortcodes</li>
                </ul>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Exchange Rate Information</h2>
                <p><strong>Base Currency:</strong> <?php echo esc_html($base_currency); ?> (set in WooCommerce settings)</p>
                <p><strong>Last Exchange Rate Update:</strong> <?php echo esc_html($last_updated_text); ?></p>
                <p>Exchange rates are automatically updated daily. You can also update them manually.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('update_exchange_rates', 'update_rates_nonce'); ?>
                    <p>
                        <input type="submit" name="update_exchange_rates" class="button button-secondary" value="Update Exchange Rates Now">
                    </p>
                </form>
                
                <?php if (!empty($exchange_rates)): ?>
                    <h3>Current Exchange Rates</h3>
                    <table class="widefat striped" style="max-width: 500px;">
                        <thead>
                            <tr>
                                <th>Currency</th>
                                <th>Rate (1 <?php echo esc_html($base_currency); ?> =)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exchange_rates as $code => $rate): ?>
                                <tr>
                                    <td><?php echo esc_html($code); ?></td>
                                    <td><?php echo esc_html($rate); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>General Settings</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('save_general_settings', 'general_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Auto-Detect Currency</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="general_settings[auto_detect]" value="yes" <?php checked('yes', $general_settings['auto_detect']); ?>>
                                    Automatically detect and set currency based on visitor's location
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Currency Switcher Display</th>
                            <td>
                                <select name="general_settings[widget_position]">
                                    <option value="both" <?php selected('both', $general_settings['widget_position']); ?>>Show in both product pages and sticky widget</option>
                                    <option value="products_only" <?php selected('products_only', $general_settings['widget_position']); ?>>Show only on product pages</option>
                                    <option value="sticky_only" <?php selected('sticky_only', $general_settings['widget_position']); ?>>Show only sticky widget</option>
                                    <option value="none" <?php selected('none', $general_settings['widget_position']); ?>>Don't show automatically</option>
                                </select>
                                <p class="description">Control where the currency switcher appears on your site. Use the shortcode [wc_multi_currency_manager] to add it to specific locations.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Currency</th>
                            <td>
                                <select name="general_settings[default_currency]">
                                    <?php foreach ($enabled_currencies as $code): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $general_settings['default_currency']); ?>>
                                            <?php echo esc_html($code); ?> - <?php echo isset($all_currencies[$code]['name']) ? esc_html($all_currencies[$code]['name']) : esc_html($code); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Default currency to use when auto-detection is disabled or fails</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="save_general_settings" class="button-primary" value="Save Settings">
                    </p>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Shortcodes</h2>
                <p>Use these shortcodes to add currency switchers to your site:</p>
                
                <table class="widefat" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th>Shortcode</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[wc_multi_currency_manager]</code></td>
                            <td>Basic currency switcher dropdown</td>
                        </tr>
                        <tr>
                            <td><code>[wc_multi_currency_manager style="buttons"]</code></td>
                            <td>Currency switcher with button style instead of dropdown</td>
                        </tr>
                        <tr>
                            <td><code>[wc_multi_currency_manager currencies="USD,EUR,GBP"]</code></td>
                            <td>Currency switcher with only specific currencies</td>
                        </tr>
                    </tbody>
                </table>
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
