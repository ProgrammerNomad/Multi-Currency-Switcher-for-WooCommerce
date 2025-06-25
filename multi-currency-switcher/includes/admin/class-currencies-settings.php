<?php
// filepath: c:\xampp\htdocs\Multi-Currency-Switcher-for-WooCommerce\multi-currency-switcher\includes\admin\class-currencies-settings.php
/**
 * Currencies Settings Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Multi_Currency_Switcher_Currencies_Settings {
    
    /**
     * Render the currencies settings page
     */
    public function render_page() {
        // Handle manual update if requested
        if (isset($_POST['update_exchange_rates']) && check_admin_referer('update_exchange_rates', 'update_rates_nonce')) {
            $updated = multi_currency_switcher_update_all_exchange_rates();
            
            if ($updated) {
                add_settings_error(
                    'multi_currency_switcher_messages',
                    'rates_updated',
                    'Exchange rates have been updated successfully.',
                    'updated'
                );
            } else {
                add_settings_error(
                    'multi_currency_switcher_messages',
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

        $all_currencies = get_all_available_currencies();
        $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array(get_woocommerce_currency()));
        $exchange_rates = get_option('multi_currency_switcher_exchange_rates', array());
        $currency_settings = get_option('multi_currency_switcher_currency_settings', array());

        // Get WooCommerce base currency
        $base_currency = get_option('woocommerce_currency', 'USD');

        // Get last update time
        $last_updated = get_option('multi_currency_switcher_rates_last_updated', 0);
        $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';

        // Sort currencies: base currency first, then enabled, then the rest
        $sorted_currencies = array();
        
        // 1. Add base currency first (if it exists in all_currencies)
        if (isset($all_currencies[$base_currency])) {
            $sorted_currencies[$base_currency] = $all_currencies[$base_currency];
        }
        
        // 2. Add other enabled currencies (skipping base which is already added)
        foreach ($enabled_currencies as $code) {
            if ($code !== $base_currency && isset($all_currencies[$code])) {
                $sorted_currencies[$code] = $all_currencies[$code];
            }
        }
        
        // 3. Add remaining currencies that aren't enabled
        foreach ($all_currencies as $code => $currency) {
            if (!in_array($code, $enabled_currencies)) {
                $sorted_currencies[$code] = $currency;
            }
        }

        settings_errors('multi_currency_switcher_messages');
        ?>
        <div class="wrap">
            <h1>Manage Currencies</h1>
            
            <?php $this->display_admin_tabs('currencies'); ?>
            
            <p>Select currencies to enable in your shop and set their exchange rates.</p>

            <div class="notice notice-info">
                <p>
                    <strong>Base Currency:</strong> <?php echo esc_html($base_currency); ?> 
                    (set in <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=general')); ?>">WooCommerce Settings</a>)
                    <br>
                    <strong>Last Exchange Rate Update:</strong> <?php echo esc_html($last_updated_text); ?>
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('update_exchange_rates', 'update_rates_nonce'); ?>
                    <p>
                        <input type="submit" name="update_exchange_rates" class="button button-secondary" value="Update Exchange Rates Now">
                    </p>
                </form>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('save_currencies', 'currencies_nonce'); ?>
                <div class="currency-table-container" style="max-width: 100%; overflow-x: auto;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Enable</th>
                                <th style="width: 50px;">Currency<br>Code</th>
                                <th style="width: 180px;">Currency</th>
                                <th style="width: 40px;">Symbol</th>
                                <th style="width: 70px;">Exchange Rate<br>(1 <?php echo esc_html($base_currency); ?> =)</th>
                                <th style="width: 50px;">Position</th>
                                <th style="width: 40px;">Decimals</th>
                                <th style="width: 40px;">Thousand<br>Separator</th>
                                <th style="width: 40px;">Decimal<br>Separator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Use the sorted currencies array instead of all_currencies
                            foreach ($sorted_currencies as $code => $currency):
                                $is_enabled = in_array($code, $enabled_currencies);
                                $exchange_rate = isset($exchange_rates[$code]) ? $exchange_rates[$code] : 1;
                                $settings = isset($currency_settings[$code]) ? $currency_settings[$code] : array(
                                    'position' => 'left',
                                    'decimals' => 2,
                                    'thousand_sep' => ',',
                                    'decimal_sep' => '.'
                                );
                                $is_base = ($code === $base_currency);
                                
                                // Add visual separator after base currency and enabled currencies
                                $separator_class = '';
                                if ($is_base) {
                                    $separator_class = ' base-currency';
                                } elseif ($is_enabled && !$is_base) {
                                    $separator_class = ' enabled-currency';
                                } else {
                                    $separator_class = ' disabled-currency';
                                }
                            ?>
                            <tr class="<?php echo esc_attr($separator_class); ?>">
                                <td>
                                    <input type="checkbox" name="currencies[<?php echo esc_attr($code); ?>][enable]" value="1" 
                                           <?php checked($is_enabled); ?> 
                                           <?php if ($is_base) echo 'checked disabled'; ?>>
                                    <?php if ($is_base): ?>
                                    <input type="hidden" name="currencies[<?php echo esc_attr($code); ?>][enable]" value="1">
                                    <?php endif; ?>
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
                                           value="<?php echo esc_attr($exchange_rate); ?>" class="regular-text"
                                           <?php if ($is_base) echo 'readonly value="1"'; ?>>
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
                
                <p class="submit">
                    <input type="submit" name="save_currencies" class="button-primary" value="Save Currencies">
                </p>
            </form>
        </div>
        
        <style>
        /* Optional visual styling to separate the currency groups */
        .base-currency {
            background-color: #f7fcff !important;
            border-bottom: 2px solid #bfe7ff !important;
        }
        .enabled-currency:last-of-type {
            border-bottom: 1px solid #ccd0d4 !important;
        }
        </style>
        <?php
    }

    /**
     * Save the currencies
     */
    public function save_currencies() {
        // Verify nonce
        if ( ! check_admin_referer('save_currencies', 'currencies_nonce') ) {
            return;
        }

        $enabled_currencies = array();
        $exchange_rates = array();
        $currency_settings = array();

        foreach ($_POST['currencies'] as $code => $data) {
            if (isset($data['enable']) && $data['enable'] == 1) {
                $enabled_currencies[] = $code;
                
                $exchange_rates[$code] = isset($data['rate']) ? floatval($data['rate']) : 1;
                
                $currency_settings[$code] = array(
                    'position' => isset($data['position']) ? sanitize_text_field($data['position']) : 'left',
                    'decimals' => isset($data['decimals']) ? intval($data['decimals']) : 2,
                    'thousand_sep' => isset($data['thousand_sep']) ? sanitize_text_field($data['thousand_sep']) : ',',
                    'decimal_sep' => isset($data['decimal_sep']) ? sanitize_text_field($data['decimal_sep']) : '.',
                );
            }
        }

        update_option('multi_currency_switcher_enabled_currencies', $enabled_currencies);
        update_option('multi_currency_switcher_exchange_rates', $exchange_rates);
        update_option('multi_currency_switcher_currency_settings', $currency_settings);
        
        add_settings_error(
            'multi_currency_switcher_messages',
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
                'url' => 'admin.php?page=multi-currency-switcher',
                'label' => 'General Settings'
            ),
            'currencies' => array(
                'url' => 'admin.php?page=multi-currency-switcher-currencies',
                'label' => 'Currencies'
            ),
            'style' => array(
                'url' => 'admin.php?page=multi-currency-switcher-style',
                'label' => 'Style Settings'
            ),
            'payment' => array(
                'url' => 'admin.php?page=multi-currency-switcher-payment',
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