<?php
// filepath: c:\xampp\htdocs\wc-multi-currency-manager-for-WooCommerce\wc-multi-currency-manager\includes\admin\class-currencies-settings.php
/**
 * Currencies Settings Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class wc_multi_currency_manager_Currencies_Settings {
    
    /**
     * Render the currencies settings page
     */
    public function render_page() {
        // Handle manual update if requested
        if (isset($_POST['update_exchange_rates']) && check_admin_referer('update_exchange_rates', 'update_rates_nonce')) {
            $updated = wc_multi_currency_manager_update_all_exchange_rates();
            
            if ($updated) {
                add_settings_error(
                    'wc_multi_currency_manager_messages',
                    'rates_updated',
                    'Exchange rates have been updated successfully.',
                    'updated'
                );
            } else {
                add_settings_error(
                    'wc_multi_currency_manager_messages',
                    'rates_update_failed',
                    'Failed to update exchange rates. Please try again later.',
                    'error'
                );
            }
        }

        // Handle saving currencies if submitted
        if (isset($_POST['save_currencies']) && check_admin_referer('save_currencies', 'currencies_nonce')) {
            $this->save_currencies();
        }

        // Get all available currencies (for the dropdown)
        $all_currencies = get_all_available_currencies();
        
        // Get currently enabled currencies (for the table)
        $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_woocommerce_currency()));
        $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());

        // Get WooCommerce base currency
        $base_currency = get_option('woocommerce_currency', 'USD');

        // Get last update time
        $last_updated = get_option('wc_multi_currency_manager_rates_last_updated', 0);
        $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';

        settings_errors('wc_multi_currency_manager_messages');
        ?>
        <div class="wrap">
            <h1>Manage Currencies</h1>
            
            <?php $this->display_admin_tabs('currencies'); ?>
            
            <!-- Currencies page custom wrapper -->
            <div class="wc-currencies-admin-container">
                <p>Select currencies to enable in your shop and set their exchange rates.</p>

            <div class="card">
                <h2>Exchange Rate Information</h2>
                <div class="wc-currency-info-grid">
                    <div class="wc-currency-info-item">
                        <p><strong>Base Currency:</strong> <?php echo esc_html($base_currency); ?></p>
                        <p><em>(set in <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=general')); ?>">WooCommerce Settings</a>)</em></p>
                    </div>
                    <div class="wc-currency-info-item">
                        <p><strong>Last Exchange Rate Update:</strong> <?php echo esc_html($last_updated_text); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field('update_exchange_rates', 'update_rates_nonce'); ?>
                            <input type="submit" name="update_exchange_rates" class="button button-secondary" value="Update Exchange Rates Now">
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Enabled Currencies</h2>
                <p>Manage your store's currencies, exchange rates, and formatting options. The base currency is automatically included and cannot be disabled.</p>
                <form method="post" action="" id="currencies-form">
                    <?php wp_nonce_field('save_currencies', 'currencies_nonce'); ?>
                <div class="currency-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Currency<br>Code</th>
                                <th>Currency</th>
                                <th>Symbol</th>
                                <th>Exchange Rate<br>(1 <?php echo esc_html($base_currency); ?> =)</th>
                                <th>Position</th>
                                <th>Decimals</th>
                                <th>Thousand<br>Separator</th>
                                <th>Decimal<br>Separator</th>
                            </tr>
                        </thead>
                        <tbody id="enabled-currencies">
                            <?php
                            // Always show base currency first
                            if (isset($all_currencies[$base_currency])):
                                $currency = $all_currencies[$base_currency];
                                $exchange_rate = isset($exchange_rates[$base_currency]) ? $exchange_rates[$base_currency] : 1;
                                $settings = isset($currency_settings[$base_currency]) ? $currency_settings[$base_currency] : array(
                                    'position' => 'left',
                                    'decimals' => 2,
                                    'thousand_sep' => ',',
                                    'decimal_sep' => '.'
                                );
                            ?>
                            <tr class="base-currency" data-currency-code="<?php echo esc_attr($base_currency); ?>">
                                <td>
                                    <span class="dashicons dashicons-star-filled" title="Base Currency"></span>
                                    <input type="hidden" name="currencies[<?php echo esc_attr($base_currency); ?>][enable]" value="1">
                                </td>
                                <td><strong><?php echo esc_html($base_currency); ?></strong></td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($base_currency); ?>][name]" 
                                           value="<?php echo esc_attr($currency['name']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($base_currency); ?>][symbol]" 
                                           value="<?php echo esc_attr($currency['symbol']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($base_currency); ?>][rate]" 
                                           value="1" class="regular-text" readonly>
                                </td>
                                <td>
                                    <select name="currencies[<?php echo esc_attr($base_currency); ?>][position]">
                                        <option value="left" <?php selected($settings['position'], 'left'); ?>>Left</option>
                                        <option value="right" <?php selected($settings['position'], 'right'); ?>>Right</option>
                                        <option value="left_space" <?php selected($settings['position'], 'left_space'); ?>>Left with space</option>
                                        <option value="right_space" <?php selected($settings['position'], 'right_space'); ?>>Right with space</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="currencies[<?php echo esc_attr($base_currency); ?>][decimals]" 
                                           value="<?php echo esc_attr($settings['decimals']); ?>" class="small-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($base_currency); ?>][thousand_sep]" 
                                           value="<?php echo esc_attr($settings['thousand_sep']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($base_currency); ?>][decimal_sep]" 
                                           value="<?php echo esc_attr($settings['decimal_sep']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php
                            // Show other enabled currencies (except base currency)
                            foreach ($enabled_currencies as $code):
                                if ($code === $base_currency) continue; // Skip base currency (already shown)
                                if (!isset($all_currencies[$code])) continue; // Skip if currency doesn't exist
                                
                                $currency = $all_currencies[$code];
                                $exchange_rate = isset($exchange_rates[$code]) ? $exchange_rates[$code] : 1;
                                $settings = isset($currency_settings[$code]) ? $currency_settings[$code] : array(
                                    'position' => 'left',
                                    'decimals' => 2,
                                    'thousand_sep' => ',',
                                    'decimal_sep' => '.'
                                );
                            ?>
                            <tr class="enabled-currency" data-currency-code="<?php echo esc_attr($code); ?>">
                                <td>
                                    <button type="button" class="button remove-currency" title="Remove Currency">&times;</button>
                                    <input type="hidden" name="currencies[<?php echo esc_attr($code); ?>][enable]" value="1">
                                </td>
                                <td><strong><?php echo esc_html($code); ?></strong></td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][name]" 
                                           value="<?php echo esc_attr($currency['name']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][symbol]" 
                                           value="<?php echo esc_attr($currency['symbol']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][rate]" 
                                           value="<?php echo esc_attr($exchange_rate); ?>" class="regular-text">
                                </td>
                                <td>
                                    <select name="currencies[<?php echo esc_attr($code); ?>][position]">
                                        <option value="left" <?php selected($settings['position'], 'left'); ?>>Left</option>
                                        <option value="right" <?php selected($settings['position'], 'right'); ?>>Right</option>
                                        <option value="left_space" <?php selected($settings['position'], 'left_space'); ?>>Left with space</option>
                                        <option value="right_space" <?php selected($settings['position'], 'right_space'); ?>>Right with space</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="currencies[<?php echo esc_attr($code); ?>][decimals]" 
                                           value="<?php echo esc_attr($settings['decimals']); ?>" class="small-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][thousand_sep]" 
                                           value="<?php echo esc_attr($settings['thousand_sep']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][decimal_sep]" 
                                           value="<?php echo esc_attr($settings['decimal_sep']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="wc-currencies-controls">
                    <div class="wc-add-currency-section">
                        <select id="add-currency-select">
                            <option value="">-- Select currency to add --</option>
                            <?php 
                            foreach ($all_currencies as $code => $currency):
                                // Skip currencies that are already enabled
                                if (in_array($code, $enabled_currencies)) continue;
                            ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code . ' - ' . $currency['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="add-currency-btn" class="button">Add Currency</button>
                    </div>
                    
                    <div class="wc-form-actions">
                        <p class="submit">
                            <input type="submit" name="save_currencies" class="button-primary" value="Save Currencies">
                        </p>
                    </div>
                </div>
                </form>
            </div>
            </div> <!-- Close wc-currencies-admin-container -->
        </div>
        <?php
    }

    /**
     * Save the currencies
     */
    public function save_currencies() {
        if (!check_admin_referer('save_currencies', 'currencies_nonce')) {
            return;
        }

        $base_currency = get_option('woocommerce_currency', 'USD');
        
        // Get existing data
        $existing_enabled = get_option('wc_multi_currency_manager_enabled_currencies', array($base_currency));
        $existing_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $existing_settings = get_option('wc_multi_currency_manager_currency_settings', array());
        
        // Start with base currency always enabled
        $enabled_currencies = array($base_currency);
        $exchange_rates = array($base_currency => 1);
        $currency_settings = array();
        
        // Process currencies in the form
        if (isset($_POST['currencies']) && is_array($_POST['currencies'])) {
            foreach ($_POST['currencies'] as $code => $data) {
                // Skip completely missing data
                if (empty($data)) continue;
                
                // Save settings for all currencies in the form
                $currency_settings[$code] = array(
                    'position' => isset($data['position']) ? sanitize_text_field($data['position']) : 'left',
                    'decimals' => isset($data['decimals']) ? intval($data['decimals']) : 2,
                    'thousand_sep' => isset($data['thousand_sep']) ? sanitize_text_field($data['thousand_sep']) : ',',
                    'decimal_sep' => isset($data['decimal_sep']) ? sanitize_text_field($data['decimal_sep']) : '.',
                );
                
                // Save exchange rate for all currencies in the form
                $exchange_rates[$code] = isset($data['rate']) ? floatval($data['rate']) : 1;
                
                // Add to enabled currencies if explicitly enabled or is base currency
                if ($code === $base_currency || (isset($data['enable']) && $data['enable'] == 1)) {
                    if (!in_array($code, $enabled_currencies)) {
                        $enabled_currencies[] = $code;
                    }
                }
            }
        }
        
        // Make sure base currency is always enabled
        if (!in_array($base_currency, $enabled_currencies)) {
            $enabled_currencies[] = $base_currency;
        }
        
        // Base currency rate is always 1
        $exchange_rates[$base_currency] = 1;
        
        // Remove duplicates
        $enabled_currencies = array_unique($enabled_currencies);
        
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
