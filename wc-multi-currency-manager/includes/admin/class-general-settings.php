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
            'show_sticky_widget' => 'yes',
            'sticky_position' => 'left',
            'limit_currencies' => 'no',
            'show_flags' => 'none',
            'widget_style' => 'dropdown',
        ));
        
        // Get exchange rate data
        $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $last_updated = get_option('wc_multi_currency_manager_rates_last_updated', 0);
        $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';
        
        // Process form submissions
        if (isset($_POST['save_general_settings']) && check_admin_referer('save_general_settings', 'general_settings_nonce')) {
            $updated_settings = array(
                'auto_detect' => isset($_POST['general_settings']['auto_detect']) ? 'yes' : 'no',
                'widget_position' => sanitize_text_field($_POST['general_settings']['widget_position']),
                'show_sticky_widget' => isset($_POST['general_settings']['show_sticky_widget']) ? 'yes' : 'no',
                'sticky_position' => sanitize_text_field($_POST['general_settings']['sticky_position']),
                'limit_currencies' => isset($_POST['general_settings']['limit_currencies']) ? 'yes' : 'no',
                'show_flags' => sanitize_text_field($_POST['general_settings']['show_flags']),
                'widget_style' => sanitize_text_field($_POST['general_settings']['widget_style']),
            );
            
            update_option('wc_multi_currency_manager_general_settings', $updated_settings);
            
            // Update the current settings with the saved values
            $general_settings = array_merge($general_settings, $updated_settings);
            
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
        $base_currency = get_option('woocommerce_currency');
        $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array($base_currency));
        $all_currencies = get_all_available_currencies();
        
        // Display the settings page
        settings_errors('wc_multi_currency_manager_messages');
        ?>
        <div class="wrap">
            <h1>WC Multi Currency Manager</h1>
            
            <?php $this->display_admin_tabs('general'); ?>
            
            <!-- Two-column layout container -->
            <div class="wc-multi-currency-admin-container">
                <!-- Left Column -->
                <div class="wc-multi-currency-column-left">                    
                    <div class="card">
                        <h2>Settings Configuration</h2>
                        <form method="post" action="">
                            <?php wp_nonce_field('save_general_settings', 'general_settings_nonce'); ?>
                            
                            <h3>General Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Auto-Detect Currency</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="general_settings[auto_detect]" value="yes" <?php checked('yes', isset($general_settings['auto_detect']) ? $general_settings['auto_detect'] : 'yes'); ?>>
                                            Automatically detect and set currency based on visitor's location
                                        </label>
                                        <p class="description">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-multi-currency-manager-currencies#auto-detect-settings')); ?>" class="button button-secondary button-small">
                                                Configure Country-Currency Mapping
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Currency Switcher Display</th>
                                    <td>
                                        <select name="general_settings[widget_position]">
                                            <option value="both" <?php selected('both', isset($general_settings['widget_position']) ? $general_settings['widget_position'] : 'both'); ?>>Show in both product pages and sticky widget</option>
                                            <option value="products_only" <?php selected('products_only', isset($general_settings['widget_position']) ? $general_settings['widget_position'] : 'both'); ?>>Show only on product pages</option>
                                            <option value="sticky_only" <?php selected('sticky_only', isset($general_settings['widget_position']) ? $general_settings['widget_position'] : 'both'); ?>>Show only sticky widget</option>
                                            <option value="none" <?php selected('none', isset($general_settings['widget_position']) ? $general_settings['widget_position'] : 'both'); ?>>Don't show automatically</option>
                                        </select>
                                        <p class="description">Control where the currency switcher appears on your site. Use the shortcode [wc_multi_currency_manager] to add it to specific locations.</p>
                                    </td>
                                </tr>
                            </table>

                            <h3>Widget Style Options</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Default Widget Style</th>
                                    <td>
                                        <select name="general_settings[widget_style]">
                                            <option value="dropdown" <?php selected('dropdown', isset($general_settings['widget_style']) ? $general_settings['widget_style'] : 'dropdown'); ?>>Dropdown</option>
                                            <option value="buttons" <?php selected('buttons', isset($general_settings['widget_style']) ? $general_settings['widget_style'] : 'dropdown'); ?>>Buttons</option>
                                            <option value="links" <?php selected('links', isset($general_settings['widget_style']) ? $general_settings['widget_style'] : 'dropdown'); ?>>Text Links</option>
                                        </select>
                                        <p class="description">Choose the default style for currency widgets (can be overridden in shortcodes)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Show Flags</th>
                                    <td>
                                        <select name="general_settings[show_flags]">
                                            <option value="none" <?php selected('none', isset($general_settings['show_flags']) ? $general_settings['show_flags'] : 'none'); ?>>No Flags</option>
                                            <option value="before" <?php selected('before', isset($general_settings['show_flags']) ? $general_settings['show_flags'] : 'none'); ?>>Before Currency Code</option>
                                            <option value="after" <?php selected('after', isset($general_settings['show_flags']) ? $general_settings['show_flags'] : 'none'); ?>>After Currency Code</option>
                                        </select>
                                        <p class="description">Show country flags in currency widgets</p>
                                    </td>
                                </tr>
                            </table>

                            <h3>Sticky Widget Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Show Sticky Currency Widget</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="general_settings[show_sticky_widget]" value="yes" 
                                                   <?php checked('yes', isset($general_settings['show_sticky_widget']) ? $general_settings['show_sticky_widget'] : 'yes'); ?>>
                                            Enable to show the sticky currency widget in your shop
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Sticky Currency Widget Position</th>
                                    <td>
                                        <select name="general_settings[sticky_position]">
                                            <option value="left" <?php selected('left', isset($general_settings['sticky_position']) ? $general_settings['sticky_position'] : 'left'); ?>>Left Side</option>
                                            <option value="right" <?php selected('right', isset($general_settings['sticky_position']) ? $general_settings['sticky_position'] : 'left'); ?>>Right Side</option>
                                            <option value="top" <?php selected('top', isset($general_settings['sticky_position']) ? $general_settings['sticky_position'] : 'left'); ?>>Top</option>
                                            <option value="bottom" <?php selected('bottom', isset($general_settings['sticky_position']) ? $general_settings['sticky_position'] : 'left'); ?>>Bottom</option>
                                        </select>
                                        <p class="description">Choose the position of the sticky currency widget in your shop</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Limit Currencies in Sticky Widget</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="general_settings[limit_currencies]" value="yes" 
                                                   <?php checked('yes', isset($general_settings['limit_currencies']) ? $general_settings['limit_currencies'] : 'no'); ?>>
                                            Choose to limit the number of currencies showing in the sticky widget
                                        </label>
                                    </td>
                                </tr>
                            </table>

                            <div class="wc-multi-currency-form-actions">
                                <p class="submit">
                                    <input type="submit" name="save_general_settings" class="button-primary" value="Save Settings">
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="wc-multi-currency-column-right">
                    <div class="card">
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
                            <table class="widefat striped">
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
                    
                    <div class="card">
                        <h2>Shortcodes</h2>
                        <p>Use these shortcodes to add currency switchers to your site:</p>
                        
                        <table class="widefat">
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
            </div>
        </div>
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
