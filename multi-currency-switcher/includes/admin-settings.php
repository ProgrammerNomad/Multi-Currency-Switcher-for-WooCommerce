<?php
// This file handles the admin settings for the currency switcher.
// It defines functions to create and manage the settings page in the WordPress admin area.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Multi_Currency_Switcher_Admin_Settings {

    public function __construct() {
        // Increase time limit for admin pages
        if (is_admin()) {
            $current_limit = ini_get('max_execution_time');
            if ($current_limit < 120) {
                @set_time_limit(120); // Increase to 120 seconds for admin pages
            }
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_product_currency_meta_boxes'));
        add_action('save_post_product', array($this, 'save_product_currency_prices'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Multi Currency Switcher',
            'Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array($this, 'create_settings_page'),
            'dashicons-money-alt',
            58 // Position after WooCommerce
        );

        add_submenu_page(
            'multi-currency-switcher',
            'General Settings',
            'General Settings',
            'manage_options',
            'multi-currency-switcher',
            array($this, 'create_settings_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Currencies',
            'Currencies',
            'manage_options',
            'multi-currency-switcher-currencies',
            array($this, 'create_currencies_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Style Settings',
            'Style Settings',
            'manage_options',
            'multi-currency-switcher-style',
            array($this, 'create_style_settings_page')
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Payment Restrictions',
            'Payment Restrictions',
            'manage_options',
            'multi-currency-switcher-payment',
            array($this, 'create_payment_settings_page')
        );
    }

    /**
     * Create the main settings page
     */
    public function create_settings_page() {
        // Get current settings
        $general_settings = get_option('multi_currency_switcher_general_settings', array(
            'auto_detect' => 'yes',
            'widget_position' => 'both',
            'default_currency' => get_woocommerce_currency(),
        ));
        
        // Get exchange rate data
        $exchange_rates = get_option('multi_currency_switcher_exchange_rates', array());
        $last_updated = get_option('multi_currency_switcher_rates_last_updated', 0);
        $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';
        
        // Process form submissions
        if (isset($_POST['save_general_settings']) && check_admin_referer('save_general_settings', 'general_settings_nonce')) {
            $general_settings = array(
                'auto_detect' => isset($_POST['general_settings']['auto_detect']) ? 'yes' : 'no',
                'widget_position' => sanitize_text_field($_POST['general_settings']['widget_position']),
                'default_currency' => sanitize_text_field($_POST['general_settings']['default_currency']),
            );
            
            update_option('multi_currency_switcher_general_settings', $general_settings);
            
            // Show success message
            add_settings_error(
                'multi_currency_switcher_messages',
                'settings_updated',
                'Settings saved successfully.',
                'updated'
            );
        }
        
        // Handle manual exchange rate update
        if (isset($_POST['update_exchange_rates']) && check_admin_referer('update_exchange_rates', 'update_rates_nonce')) {
            $updated = multi_currency_switcher_update_all_exchange_rates();
            
            if ($updated) {
                add_settings_error(
                    'multi_currency_switcher_messages',
                    'rates_updated',
                    'Exchange rates have been updated successfully.',
                    'updated'
                );
                
                // Refresh the exchange rates
                $exchange_rates = get_option('multi_currency_switcher_exchange_rates', array());
                $last_updated = get_option('multi_currency_switcher_rates_last_updated', 0);
                $last_updated_text = $last_updated ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated) : 'Never';
            } else {
                add_settings_error(
                    'multi_currency_switcher_messages',
                    'rates_update_failed',
                    'Failed to update exchange rates. Please try again later.',
                    'error'
                );
            }
        }
        
        // Get enabled currencies
        $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array(get_woocommerce_currency()));
        $all_currencies = get_all_available_currencies();
        $base_currency = get_woocommerce_currency();
        
        // Display the settings page
        settings_errors('multi_currency_switcher_messages');
        ?>
        <div class="wrap">
            <h1>Multi Currency Switcher</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=multi-currency-switcher" class="nav-tab nav-tab-active">General Settings</a>
                <a href="?page=multi-currency-switcher-currencies" class="nav-tab">Currencies</a>
                <a href="?page=multi-currency-switcher-style" class="nav-tab">Style Settings</a>
                <a href="?page=multi-currency-switcher-payment" class="nav-tab">Payment Restrictions</a>
            </h2>
            
            <div class="card">
                <h2>Plugin Overview</h2>
                <p>Multi Currency Switcher allows your customers to shop in their preferred currency. Key features include:</p>
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
                            <?php foreach ($enabled_currencies as $code): 
                                if ($code === $base_currency) continue;
                                $rate = isset($exchange_rates[$code]) ? $exchange_rates[$code] : 'N/A';
                                $name = isset($all_currencies[$code]['name']) ? $all_currencies[$code]['name'] : $code;
                            ?>
                                <tr>
                                    <td><?php echo esc_html($code); ?> - <?php echo esc_html($name); ?></td>
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
                            <th scope="row"><label for="auto_detect">Auto-detect Currency</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto_detect" name="general_settings[auto_detect]" value="yes" <?php checked($general_settings['auto_detect'], 'yes'); ?>>
                                    Automatically detect and set currency based on visitor's location
                                </label>
                                <p class="description">When enabled, the plugin will attempt to detect the visitor's country and set an appropriate currency.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="widget_position">Currency Switcher Display</label></th>
                            <td>
                                <select id="widget_position" name="general_settings[widget_position]">
                                    <option value="both" <?php selected($general_settings['widget_position'], 'both'); ?>>Show in both product pages and sticky widget</option>
                                    <option value="products_only" <?php selected($general_settings['widget_position'], 'products_only'); ?>>Show only on product pages</option>
                                    <option value="sticky_only" <?php selected($general_settings['widget_position'], 'sticky_only'); ?>>Show only as sticky widget</option>
                                    <option value="none" <?php selected($general_settings['widget_position'], 'none'); ?>>Don't show automatically (use shortcode only)</option>
                                </select>
                                <p class="description">Control where the currency switcher appears on your site. Use the shortcode [multi_currency_switcher] to add it to specific locations.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="default_currency">Default Currency</label></th>
                            <td>
                                <select id="default_currency" name="general_settings[default_currency]">
                                    <?php foreach ($enabled_currencies as $code): 
                                        $name = isset($all_currencies[$code]['name']) ? $all_currencies[$code]['name'] : $code;
                                    ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($general_settings['default_currency'], $code); ?>>
                                            <?php echo esc_html($code . ' - ' . $name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">The currency to use when auto-detection is disabled or fails. This should typically be your shop's base currency.</p>
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
                            <td><code>[multi_currency_switcher]</code></td>
                            <td>Basic currency dropdown selector</td>
                        </tr>
                        <tr>
                            <td><code>[multi_currency_switcher style="buttons"]</code></td>
                            <td>Currency selector with button-style interface</td>
                        </tr>
                        <tr>
                            <td><code>[multi_currency_switcher currencies="USD,EUR,GBP"]</code></td>
                            <td>Currency selector with only specified currencies</td>
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

    public function create_currencies_page() {
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

        settings_errors('multi_currency_switcher_messages');
        ?>
        <div class="wrap">
            <h1>Manage Currencies</h1>
            
            <!-- Updated navigation tabs to include Payment Restrictions -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=multi-currency-switcher" class="nav-tab">General Settings</a>
                <a href="?page=multi-currency-switcher-currencies" class="nav-tab nav-tab-active">Currencies</a>
                <a href="?page=multi-currency-switcher-style" class="nav-tab">Style Settings</a>
                <a href="?page=multi-currency-switcher-payment" class="nav-tab">Payment Restrictions</a>
            </h2>
            
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
                                <th style="width: 180px;">Currency</th>
                                <th style="width: 80px;">Symbol</th>
                                <th style="width: 150px;">Exchange Rate<br>(1 <?php echo esc_html($base_currency); ?> =)</th>
                                <th style="width: 100px;">Position</th>
                                <th style="width: 80px;">Decimals</th>
                                <th style="width: 80px;">Thousand<br>Separator</th>
                                <th style="width: 80px;">Decimal<br>Separator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_currencies as $code => $currency):
                                $is_enabled = in_array($code, $enabled_currencies);
                                $exchange_rate = isset($exchange_rates[$code]) ? $exchange_rates[$code] : 1;
                                $settings = isset($currency_settings[$code]) ? $currency_settings[$code] : array(
                                    'position' => 'left',
                                    'decimals' => 2,
                                    'thousand_sep' => ',',
                                    'decimal_sep' => '.'
                                );
                            ?>
                            <tr>
                                <td>
                                    <label>
                                        <input type="checkbox" name="currencies[<?php echo esc_attr($code); ?>][enable]" value="1" <?php checked($is_enabled); ?>>
                                        Enable
                                    </label>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($code); ?></strong>
                                    <br>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][name]" value="<?php echo esc_attr($currency['name']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][symbol]" value="<?php echo esc_attr($currency['symbol']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][rate]" value="<?php echo esc_attr($exchange_rate); ?>" class="regular-text">
                                </td>
                                <td>
                                    <select name="currencies[<?php echo esc_attr($code); ?>][position]">
                                        <option value="left" <?php selected($settings['position'], 'left'); ?>>Left</option>
                                        <option value="right" <?php selected($settings['position'], 'right'); ?>>Right</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="currencies[<?php echo esc_attr($code); ?>][decimals]" value="<?php echo esc_attr($settings['decimals']); ?>" class="small-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][thousand_sep]" value="<?php echo esc_attr($settings['thousand_sep']); ?>" class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="currencies[<?php echo esc_attr($code); ?>][decimal_sep]" value="<?php echo esc_attr($settings['decimal_sep']); ?>" class="regular-text">
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
        <?php
    }

    public function create_style_settings_page() {
        // Check if settings are being saved
        if (isset($_POST['save_style_settings']) && check_admin_referer('save_style_settings', 'style_settings_nonce')) {
            $this->save_style_settings();
        }

        // Get saved settings with defaults
        $style_settings = get_option('multi_currency_switcher_style_settings', array(
            'title_color' => '#333333',
            'text_color' => '#000000',
            'active_color' => '#04AE93',
            'background_color' => '#FFFFFF',
            'border_color' => '#B2B2B2',
            'show_sticky_widget' => 'yes',
            'sticky_position' => 'left',
            'limit_currencies' => 'no',
            'show_flags' => 'none',
        ));
        
        // Display any settings errors/notices
        settings_errors('multi_currency_switcher_messages');
        
        ?>
        <div class="wrap">
            <h1>Style Settings</h1>
            
            <!-- Updated navigation tabs to include Payment Restrictions -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=multi-currency-switcher" class="nav-tab">General Settings</a>
                <a href="?page=multi-currency-switcher-currencies" class="nav-tab">Currencies</a>
                <a href="?page=multi-currency-switcher-style" class="nav-tab nav-tab-active">Style Settings</a>
                <a href="?page=multi-currency-switcher-payment" class="nav-tab">Payment Restrictions</a>
            </h2>
            
            <p>Customize the appearance of currency widgets and shortcodes used in your shop.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_style_settings', 'style_settings_nonce'); ?>
                
                <div class="style-settings-container">
                    <div class="style-settings-section">
                        <h2>Colors</h2>
                        <p>Set the colors of all the currency widgets created in the shortcode tab.</p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Titles</th>
                                <td>
                                    <input type="text" class="color-picker" name="style_settings[title_color]" 
                                           value="<?php echo esc_attr($style_settings['title_color']); ?>">
                                    <p class="description">Color for widget titles</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Text</th>
                                <td>
                                    <input type="text" class="color-picker" name="style_settings[text_color]" 
                                           value="<?php echo esc_attr($style_settings['text_color']); ?>">
                                    <p class="description">Color for widget text</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Active Selection</th>
                                <td>
                                    <input type="text" class="color-picker" name="style_settings[active_color]" 
                                           value="<?php echo esc_attr($style_settings['active_color']); ?>">
                                    <p class="description">Color for active selection</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Background</th>
                                <td>
                                    <input type="text" class="color-picker" name="style_settings[background_color]" 
                                           value="<?php echo esc_attr($style_settings['background_color']); ?>">
                                    <p class="description">Background color for widgets</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Borders</th>
                                <td>
                                    <input type="text" class="color-picker" name="style_settings[border_color]" 
                                           value="<?php echo esc_attr($style_settings['border_color']); ?>">
                                    <p class="description">Border color for widgets</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="style-settings-section">
                        <h2>Sticky Widget</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Show Sticky Currency Widget</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="style_settings[show_sticky_widget]" value="yes" 
                                               <?php checked('yes', $style_settings['show_sticky_widget']); ?>>
                                        Enable to show the sticky currency widget in your shop
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Sticky Currency Widget Position</th>
                                <td>
                                    <select name="style_settings[sticky_position]">
                                        <option value="left" <?php selected('left', $style_settings['sticky_position']); ?>>Left Side</option>
                                        <option value="right" <?php selected('right', $style_settings['sticky_position']); ?>>Right Side</option>
                                        <option value="top" <?php selected('top', $style_settings['sticky_position']); ?>>Top</option>
                                        <option value="bottom" <?php selected('bottom', $style_settings['sticky_position']); ?>>Bottom</option>
                                    </select>
                                    <p class="description">Choose the position of the sticky currency widget in your shop</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Limit Currencies in Sticky Widget</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="style_settings[limit_currencies]" value="yes" 
                                               <?php checked('yes', $style_settings['limit_currencies']); ?>>
                                        Choose to limit the number of currencies showing in the sticky widget
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Show Flags in Widgets</th>
                                <td>
                                    <p class="description">Flag support will be added in a future update.</p>
                                    <input type="hidden" name="style_settings[show_flags]" value="none">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_style_settings" class="button-primary" value="Save Style Settings">
                </p>
            </form>
        </div>
        <?php
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
     * Save the general settings
     */
    public function save_general_settings() {
        // Verify nonce
        if ( ! check_admin_referer('save_general_settings', 'general_settings_nonce') ) {
            return;
        }
        
        $settings = array(
            'auto_detect' => isset($_POST['general_settings']['auto_detect']) ? 'yes' : 'no',
            'widget_position' => sanitize_text_field($_POST['general_settings']['widget_position']),
            'default_currency' => sanitize_text_field($_POST['general_settings']['default_currency']),
        );
        
        update_option('multi_currency_switcher_general_settings', $settings);
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
    }

    /**
     * Save the style settings
     */
    public function save_style_settings() {
        // Verify nonce
        if ( ! check_admin_referer('save_style_settings', 'style_settings_nonce') ) {
            return;
        }

        $settings = array(
            'title_color' => sanitize_hex_color($_POST['style_settings']['title_color']),
            'text_color' => sanitize_hex_color($_POST['style_settings']['text_color']),
            'active_color' => sanitize_hex_color($_POST['style_settings']['active_color']),
            'background_color' => sanitize_hex_color($_POST['style_settings']['background_color']),
            'border_color' => sanitize_hex_color($_POST['style_settings']['border_color']),
            'show_sticky_widget' => isset($_POST['style_settings']['show_sticky_widget']) ? 'yes' : 'no',
            'sticky_position' => sanitize_text_field($_POST['style_settings']['sticky_position']),
            'limit_currencies' => isset($_POST['style_settings']['limit_currencies']) ? 'yes' : 'no',
            'show_flags' => sanitize_text_field($_POST['style_settings']['show_flags']),
        );
        
        update_option('multi_currency_switcher_style_settings', $settings);
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
        wp_enqueue_style('multi-currency-admin-styles', plugins_url('../assets/css/admin-styles.css', __FILE__));
        
        // Enqueue color picker script and style
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('multi-currency-admin-scripts', plugins_url('../assets/js/admin-scripts.js', __FILE__), array('jquery', 'wp-color-picker'), false, true);
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
        if (!isset($_POST['product_currency_nonce']) || !check_admin_referer('save_product_currency_prices')) {
            return;
        }
        
        // Save the currency prices
        $currency_prices = isset($_POST['currency_prices']) ? array_map('sanitize_text_field', $_POST['currency_prices']) : array();
        update_post_meta($post_id, '_currency_prices', $currency_prices);
    }

    /**
     * Create the payment restrictions settings page
     */
    public function create_payment_settings_page() {
        // Process form submissions
        if (isset($_POST['save_payment_settings']) && check_admin_referer('save_payment_settings', 'payment_settings_nonce')) {
            $payment_restrictions = isset($_POST['multi_currency_switcher_payment_restrictions']) ? 
                              $_POST['multi_currency_switcher_payment_restrictions'] : array();
            
            update_option('multi_currency_switcher_payment_restrictions', $payment_restrictions);
            
            add_settings_error(
                'multi_currency_switcher_messages',
                'payment_settings_updated',
                'Payment restrictions have been updated successfully.',
                'updated'
            );
        }
        
        $restrictions = get_option('multi_currency_switcher_payment_restrictions', array());
        $currencies = get_option('multi_currency_switcher_enabled_currencies', array(get_woocommerce_currency()));
        
        settings_errors('multi_currency_switcher_messages');
        ?>
        <div class="wrap">
            <h1>Payment Method Restrictions</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=multi-currency-switcher" class="nav-tab">General Settings</a>
                <a href="?page=multi-currency-switcher-currencies" class="nav-tab">Currencies</a>
                <a href="?page=multi-currency-switcher-style" class="nav-tab">Style Settings</a>
                <a href="?page=multi-currency-switcher-payment" class="nav-tab nav-tab-active">Payment Restrictions</a>
            </h2>
            
            <p>Control which payment methods are available for each currency. Check a payment method to disable it for that currency.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_payment_settings', 'payment_settings_nonce'); ?>
                
                <?php
                // Only try to get payment gateways if WooCommerce is active and initialized
                if (function_exists('WC') && isset(WC()->payment_gateways) && WC()->payment_gateways) {
                    $gateways = WC()->payment_gateways->get_available_payment_gateways();
                    
                    if (!empty($gateways)) {
                        foreach ($currencies as $currency) {
                            echo "<div class='card' style='margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);'>";
                            echo "<h3>{$currency} Payment Methods</h3>";
                            echo "<p>Select payment methods to <strong>disable</strong> when {$currency} is the active currency:</p>";
                            
                            foreach ($gateways as $gateway_id => $gateway) {
                                $checked = isset($restrictions[$currency]) && in_array($gateway_id, $restrictions[$currency]) ? 'checked' : '';
                                echo "<label style='display: block; margin-bottom: 8px;'><input type='checkbox' name='multi_currency_switcher_payment_restrictions[{$currency}][]' value='{$gateway_id}' {$checked}> {$gateway->title}</label>";
                            }
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No payment gateways available. Please check your WooCommerce settings.</p>";
                    }
                } else {
                    echo "<p>WooCommerce is not active or not fully initialized. Please ensure WooCommerce is active and reload this page.</p>";
                }
                ?>
                
                <p class="submit" style="margin-top: 20px;">
                    <input type="submit" name="save_payment_settings" class="button-primary" value="Save Payment Restrictions">
                </p>
            </form>
        </div>
        <?php
    }
}

/**
 * Initialize the settings class
 */
new Multi_Currency_Switcher_Admin_Settings();