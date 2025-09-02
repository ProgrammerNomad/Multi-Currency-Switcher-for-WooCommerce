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

        // Handle saving auto-detect settings if submitted
        if (isset($_POST['save_auto_detect_settings']) && check_admin_referer('save_auto_detect_settings', 'auto_detect_nonce')) {
            $this->save_auto_detect_settings();
        }

        // Get all available currencies (for the dropdown)
        $all_currencies = get_all_available_currencies();
        
        // Get currently enabled currencies (for the table)
        $enabled_currencies = get_option('wc_multi_currency_manager_enabled_currencies', array(get_woocommerce_currency()));
        $exchange_rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $currency_settings = get_option('wc_multi_currency_manager_currency_settings', array());

        // Get auto-detect settings
        $general_settings = get_option('wc_multi_currency_manager_general_settings', array('auto_detect' => 'yes'));
        $auto_detect_enabled = isset($general_settings['auto_detect']) ? $general_settings['auto_detect'] : 'yes';

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

            <!-- Auto-Detect Currency Settings Section -->
            <div class="card" id="auto-detect-settings">
                <h2>Auto-Detect Currency Settings</h2>
                <p>Configure currency detection based on visitor's location. Countries can use multiple currencies - the first enabled currency from the list will be selected.</p>
                
                <form method="post" action="" id="auto-detect-form">
                    <?php wp_nonce_field('save_auto_detect_settings', 'auto_detect_nonce'); ?>
                    
                    <div class="auto-detect-main-setting">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Auto-Detection</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_detect_settings[enabled]" value="yes" 
                                               <?php checked('yes', $auto_detect_enabled); ?>>
                                        Automatically detect and set currency based on visitor's location
                                    </label>
                                    <p class="description">
                                        This setting is also available in 
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-multi-currency-manager')); ?>">General Settings</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="country-currency-mapping">
                        <h3>Country-Currency Mapping</h3>
                        <p>Customize which currency should be used for each country. Only enabled currencies will be selected.</p>
                        
                        <div class="mapping-search">
                            <input type="text" id="country-search" placeholder="Search countries..." class="regular-text">
                        </div>
                        
                        <div class="mapping-table-container">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col" class="manage-column">Country</th>
                                        <th scope="col" class="manage-column">Country Code</th>
                                        <th scope="col" class="manage-column">Default Currency</th>
                                        <th scope="col" class="manage-column">Custom Currency</th>
                                        <th scope="col" class="manage-column">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="country-mapping-tbody">
                                    <?php 
                                    $countries = WC()->countries->get_countries();
                                    $country_currency_mapping = $this->get_country_currency_mapping();
                                    $custom_mappings = get_option('wc_multi_currency_manager_country_mappings', array());
                                    
                                    foreach ($countries as $country_code => $country_name):
                                        $default_currencies = isset($country_currency_mapping[$country_code]) ? $country_currency_mapping[$country_code] : array();
                                        $custom_currency = isset($custom_mappings[$country_code]) ? $custom_mappings[$country_code] : '';
                                        $has_enabled_currency = false;
                                        
                                        // Check if any default or custom currency is enabled
                                        $all_currencies_for_country = array_merge($default_currencies, array($custom_currency));
                                        foreach ($all_currencies_for_country as $currency) {
                                            if ($currency && in_array($currency, $enabled_currencies)) {
                                                $has_enabled_currency = true;
                                                break;
                                            }
                                        }
                                    ?>
                                    <tr class="country-row" data-country="<?php echo esc_attr(strtolower($country_name)); ?>" data-code="<?php echo esc_attr($country_code); ?>">
                                        <td><strong><?php echo esc_html($country_name); ?></strong></td>
                                        <td><code><?php echo esc_html($country_code); ?></code></td>
                                        <td>
                                            <?php if (!empty($default_currencies)): ?>
                                                <span class="default-currencies">
                                                    <?php foreach ($default_currencies as $index => $currency): ?>
                                                        <span class="currency-code <?php echo in_array($currency, $enabled_currencies) ? 'enabled' : 'disabled'; ?>">
                                                            <?php echo esc_html($currency); ?>
                                                        </span><?php echo ($index < count($default_currencies) - 1) ? ', ' : ''; ?>
                                                    <?php endforeach; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="no-default">No default</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select name="country_mappings[<?php echo esc_attr($country_code); ?>]" class="custom-currency-select">
                                                <option value="">-- Use Default --</option>
                                                <?php foreach ($enabled_currencies as $currency_code): ?>
                                                    <option value="<?php echo esc_attr($currency_code); ?>" <?php selected($currency_code, $custom_currency); ?>>
                                                        <?php echo esc_html($currency_code); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <?php if ($has_enabled_currency): ?>
                                                <span class="status-active">✓ Active</span>
                                            <?php else: ?>
                                                <span class="status-inactive">⚠ No enabled currency</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="auto-detect-form-actions">
                        <p class="submit">
                            <input type="submit" name="save_auto_detect_settings" class="button-primary" value="Save Auto-Detect Settings">
                            <button type="button" id="reset-mappings" class="button button-secondary">Reset to Defaults</button>
                        </p>
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

    /**
     * Get the default country-currency mapping
     */
    private function get_country_currency_mapping() {
        return include plugin_dir_path(__FILE__) . '../../data/country-currency-mapping.php';
    }

    /**
     * Save auto-detect settings
     */
    private function save_auto_detect_settings() {
        // Update the general settings auto-detect option
        $general_settings = get_option('wc_multi_currency_manager_general_settings', array());
        $general_settings['auto_detect'] = isset($_POST['auto_detect_settings']['enabled']) ? 'yes' : 'no';
        update_option('wc_multi_currency_manager_general_settings', $general_settings);

        // Save custom country mappings
        $country_mappings = array();
        if (isset($_POST['country_mappings']) && is_array($_POST['country_mappings'])) {
            foreach ($_POST['country_mappings'] as $country_code => $currency_code) {
                $country_code = sanitize_text_field($country_code);
                $currency_code = sanitize_text_field($currency_code);
                
                // Only save non-empty custom mappings
                if (!empty($currency_code)) {
                    $country_mappings[$country_code] = $currency_code;
                }
            }
        }
        update_option('wc_multi_currency_manager_country_mappings', $country_mappings);

        add_settings_error(
            'wc_multi_currency_manager_messages',
            'auto_detect_updated',
            'Auto-detect settings have been saved successfully.',
            'updated'
        );
    }
}
