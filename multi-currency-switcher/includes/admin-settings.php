<?php
// This file handles the admin settings for the currency switcher.
// It defines functions to create and manage the settings page in the WordPress admin area.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Multi_Currency_Switcher_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_product_currency_meta_boxes' ) );
        add_action( 'save_post_product', array( $this, 'save_product_currency_prices' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Multi Currency Switcher',
            'Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array( $this, 'create_settings_page' ),
            'dashicons-money-alt',
            58 // Position after WooCommerce
        );

        add_submenu_page(
            'multi-currency-switcher',
            'General Settings',
            'General Settings',
            'manage_options',
            'multi-currency-switcher',
            array( $this, 'create_settings_page' )
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Currencies',
            'Currencies',
            'manage_options',
            'multi-currency-switcher-currencies',
            array( $this, 'create_currencies_page' )
        );
        
        add_submenu_page(
            'multi-currency-switcher',
            'Style Settings',
            'Style Settings',
            'manage_options',
            'multi-currency-switcher-style',
            array( $this, 'create_style_settings_page' )
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
        if ( isset( $_POST['update_exchange_rates'] ) ) {
            $updated = multi_currency_switcher_update_all_exchange_rates();
            if ( $updated ) {
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
        if ( isset( $_POST['save_currencies'] ) ) {
            $this->save_currencies();
        }

        $all_currencies = $this->get_all_available_currencies();
        $enabled_currencies = get_option( 'multi_currency_switcher_enabled_currencies', array( 'USD' ) );
        $exchange_rates = get_option( 'multi_currency_switcher_exchange_rates', array() );
        $currency_settings = get_option( 'multi_currency_switcher_currency_settings', array() );

        // Get WooCommerce base currency
        $base_currency = get_option( 'woocommerce_currency', 'USD' );

        // Get last update time
        $last_updated = get_option( 'multi_currency_switcher_rates_last_updated', 0 );
        $last_updated_text = $last_updated ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_updated ) : 'Never';

        settings_errors( 'multi_currency_switcher_messages' );
        ?>
        <div class="wrap">
            <h1>Manage Currencies</h1>
            <p>Select currencies to enable in your shop and set their exchange rates.</p>

            <div class="notice notice-info">
                <p>
                    <strong>Base Currency:</strong> <?php echo esc_html( $base_currency ); ?> (as set in WooCommerce settings)
                    <br>
                    <strong>Exchange Rates Last Updated:</strong> <?php echo esc_html( $last_updated_text ); ?>
                    <br>
                    Exchange rates are automatically updated daily. You can also update them manually using the button below.
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'update_exchange_rates', 'update_exchange_rates_nonce' ); ?>
                    <p>
                        <input type="submit" name="update_exchange_rates" class="button" value="Update Exchange Rates Now">
                    </p>
                </form>
            </div>

            <form method="post" action="">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Enable</th>
                            <th>Currency</th>
                            <th>Symbol</th>
                            <th>Exchange Rate (1 <?php echo esc_html( $base_currency ); ?> =)</th>
                            <th>Position</th>
                            <th>Decimals</th>
                            <th>Thousand Separator</th>
                            <th>Decimal Separator</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $all_currencies as $code => $currency ):
                            $is_enabled = in_array( $code, $enabled_currencies );
                            $exchange_rate = isset( $exchange_rates[ $code ] ) ? $exchange_rates[ $code ] : 1;
                            $settings = isset( $currency_settings[ $code ] ) ? $currency_settings[ $code ] : array(
                                'position' => 'left',
                                'decimals' => 2,
                                'thousand_sep' => ',',
                                'decimal_sep' => '.'
                            );
                            $is_base = ( $code === $base_currency );
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" <?php checked( $is_enabled, true ); ?> <?php if ( $is_base ) echo 'checked disabled'; ?>>
                                <?php if ( $is_base ): ?>
                                <input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" value="1">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html( $currency['name'] ); ?> (<?php echo esc_html( $code ); ?>)
                                <?php if ( $is_base ): ?> <strong>(Base Currency)</strong><?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html( $currency['symbol'] ); ?>
                            </td>
                            <td>
                                <input type="number" 
                                       step="any" 
                                       min="0.0001" 
                                       name="currencies[<?php echo esc_attr( $code ); ?>][rate]" 
                                       value="<?php echo esc_attr( $exchange_rate ); ?>" 
                                       <?php if ( $is_base ) echo 'readonly'; ?>>
                            </td>
                            <td>
                                <select name="currencies[<?php echo esc_attr( $code ); ?>][position]">
                                    <option value="left" <?php selected( $settings['position'], 'left' ); ?>><?php echo esc_html( $currency['symbol'] ); ?>99</option>
                                    <option value="right" <?php selected( $settings['position'], 'right' ); ?>>99<?php echo esc_html( $currency['symbol'] ); ?></option>
                                    <option value="left_space" <?php selected( $settings['position'], 'left_space' ); ?>><?php echo esc_html( $currency['symbol'] ); ?> 99</option>
                                    <option value="right_space" <?php selected( $settings['position'], 'right_space' ); ?>>99 <?php echo esc_html( $currency['symbol'] ); ?></option>
                                </select>
                            </td>
                            <td>
                                <input type="number" min="0" max="4" name="currencies[<?php echo esc_attr( $code ); ?>][decimals]" value="<?php echo esc_attr( $settings['decimals'] ); ?>">
                            </td>
                            <td>
                                <input type="text" size="1" name="currencies[<?php echo esc_attr( $code ); ?>][thousand_sep]" value="<?php echo esc_attr( $settings['thousand_sep'] ); ?>">
                            </td>
                            <td>
                                <input type="text" size="1" name="currencies[<?php echo esc_attr( $code ); ?>][decimal_sep]" value="<?php echo esc_attr( $settings['decimal_sep'] ); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="save_currencies" class="button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    private function save_currencies() {
        if ( ! isset( $_POST['currencies'] ) || ! is_array( $_POST['currencies'] ) ) {
            return;
        }

        $enabled_currencies = array();
        $exchange_rates = get_option( 'multi_currency_switcher_exchange_rates', array() );
        $currency_settings = array();

        // Get WooCommerce base currency
        $base_currency = get_option( 'woocommerce_currency', 'USD' );

        // Always include base currency
        $enabled_currencies[] = $base_currency;
        $exchange_rates[$base_currency] = 1;

        // Get previously enabled currencies to check for newly enabled ones
        $previous_currencies = get_option( 'multi_currency_switcher_enabled_currencies', array( $base_currency ) );

        foreach ( $_POST['currencies'] as $code => $data ) {
            if ( isset( $data['enabled'] ) ) {
                $enabled_currencies[] = $code;
                
                // Check if this is a newly enabled currency
                if ( !in_array( $code, $previous_currencies ) && $code !== $base_currency ) {
                    // This is a newly enabled currency, fetch its rate from API
                    $exchange_rates[$code] = $this->fetch_exchange_rate_for_currency( $code, $base_currency );
                } else {
                    // This is an existing currency, use the manual rate if provided
                    $exchange_rates[$code] = floatval( $data['rate'] );
                }
            }

            $currency_settings[$code] = array(
                'position' => sanitize_text_field( $data['position'] ),
                'decimals' => intval( $data['decimals'] ),
                'thousand_sep' => sanitize_text_field( $data['thousand_sep'] ),
                'decimal_sep' => sanitize_text_field( $data['decimal_sep'] )
            );
        }

        // Remove any currencies that were disabled
        foreach ( $previous_currencies as $code ) {
            if ( !in_array( $code, $enabled_currencies ) && isset( $exchange_rates[$code] ) ) {
                // Keep the exchange rate in case they re-enable it later
                // But you could also unset it if you prefer
                // unset( $exchange_rates[$code] );
            }
        }

        update_option( 'multi_currency_switcher_enabled_currencies', array_unique( $enabled_currencies ) );
        update_option( 'multi_currency_switcher_exchange_rates', $exchange_rates );
        update_option( 'multi_currency_switcher_currency_settings', $currency_settings );
        update_option( 'multi_currency_switcher_rates_last_updated', current_time( 'timestamp' ) );

        add_settings_error(
            'multi_currency_switcher_messages',
            'currencies_updated',
            'Currencies have been updated successfully.',
            'updated'
        );
    }

    /**
     * Fetch exchange rate for a newly added currency
     */
    private function fetch_exchange_rate_for_currency( $currency, $base_currency ) {
        // Use the API to fetch the exchange rate
        $api_url = "https://api.exchangerate-api.com/v4/latest/{$base_currency}";
        $response = wp_remote_get( $api_url );

        if ( !is_wp_error( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( isset( $data['rates'][$currency] ) ) {
                return $data['rates'][$currency];
            }
        }
        
        // Default to 1 if API fetch fails
        return 1;
    }

    public function register_settings() {
        register_setting( 'multi_currency_switcher_settings', 'multi_currency_switcher_payment_restrictions' );

        add_settings_section(
            'multi_currency_switcher_payment_section',
            'Payment Restrictions',
            array( $this, 'payment_section_callback' ),
            'multi_currency_switcher'
        );

        add_settings_field(
            'multi_currency_switcher_payment_restrictions',
            'Restrict Payment Methods by Currency',
            array( $this, 'payment_restrictions_callback' ),
            'multi_currency_switcher',
            'multi_currency_switcher_payment_section'
        );
    }

    public function payment_section_callback() {
        echo '<p>Map currencies to payment methods you want to disable.</p>';
    }

    public function payment_restrictions_callback() {
        $restrictions = get_option( 'multi_currency_switcher_payment_restrictions', array() );
        $currencies = get_option( 'multi_currency_switcher_enabled_currencies', array( 'USD' ) );

        // Only try to get payment gateways if WooCommerce is active and initialized
        if ( function_exists( 'WC' ) && WC()->payment_gateways ) {
            $gateways = WC()->payment_gateways->get_available_payment_gateways();

            if ( ! empty( $gateways ) ) {
                foreach ( $currencies as $currency ) {
                    echo "<h4>{$currency}</h4>";
                    foreach ( $gateways as $gateway_id => $gateway ) {
                        $checked = isset( $restrictions[ $currency ] ) && in_array( $gateway_id, $restrictions[ $currency ] ) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='multi_currency_switcher_payment_restrictions[{$currency}][]' value='{$gateway_id}' {$checked}> {$gateway->title}</label><br>";
                    }
                }
            } else {
                echo "<p>No payment gateways available. Please check your WooCommerce settings.</p>";
            }
        } else {
            echo "<p>WooCommerce is not active or not fully initialized. Please ensure WooCommerce is active and reload this page.</p>";
        }
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

    // Add this method to save style settings
    private function save_style_settings() {
        if (!isset($_POST['style_settings'])) {
            add_settings_error(
                'multi_currency_switcher_messages',
                'style_settings_error',
                'No settings data received. Please try again.',
                'error'
            );
            return;
        }
        
        // Sanitize inputs
        $style_settings = array(
            'title_color' => sanitize_hex_color($_POST['style_settings']['title_color']),
            'text_color' => sanitize_hex_color($_POST['style_settings']['text_color']),
            'active_color' => sanitize_hex_color($_POST['style_settings']['active_color']),
            'background_color' => sanitize_hex_color($_POST['style_settings']['background_color']),
            'border_color' => sanitize_hex_color($_POST['style_settings']['border_color']),
            'show_sticky_widget' => isset($_POST['style_settings']['show_sticky_widget']) ? 'yes' : 'no',
            'sticky_position' => sanitize_text_field($_POST['style_settings']['sticky_position']),
            'limit_currencies' => isset($_POST['style_settings']['limit_currencies']) ? 'yes' : 'no',
            'show_flags' => 'none', // Set to none for now since we're not implementing flags yet
        );
        
        // Check if colors are valid
        $valid_colors = true;
        foreach (['title_color', 'text_color', 'active_color', 'background_color', 'border_color'] as $color_field) {
            if (empty($style_settings[$color_field])) {
                $valid_colors = false;
            }
        }
        
        if (!$valid_colors) {
            add_settings_error(
                'multi_currency_switcher_messages',
                'style_settings_error',
                'Please enter valid color values in hexadecimal format (e.g., #333333).',
                'error'
            );
            return;
        }
        
        // Save the settings
        update_option('multi_currency_switcher_style_settings', $style_settings);
        
        // Add success message
        add_settings_error(
            'multi_currency_switcher_messages',
            'style_settings_updated',
            'Style settings have been updated successfully.',
            'updated'
        );
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'multi-currency-switcher' ) !== false ) {
            wp_enqueue_style( 'multi-currency-switcher-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-styles.css', array(), '1.0.0' );
            
            // For color picker
            if (strpos($hook, 'multi-currency-switcher-style') !== false) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_script('multi-currency-switcher-admin-js', 
                    plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-scripts.js', 
                    array('wp-color-picker'), 
                    '1.0.0', 
                    true
                );
            }
        }
    }

    private function get_all_available_currencies() {
        return get_all_available_currencies();
    }

    public function add_product_currency_meta_boxes() {
        add_meta_box(
            'multi-currency-prices',
            'Currency-Specific Prices',
            array($this, 'render_product_currency_prices'),
            'product',
            'normal',
            'high'
        );
    }

    public function render_product_currency_prices($post) {
        $product = wc_get_product($post->ID);
        $enabled_currencies = get_option('multi_currency_switcher_enabled_currencies', array('USD'));
        $base_currency = get_option('woocommerce_currency', 'USD');
        $all_currencies = $this->get_all_available_currencies();
        
        // Remove base currency from list
        $currencies = array_diff($enabled_currencies, array($base_currency));
        
        echo '<p>Set specific prices for each currency. Leave empty to use automatic conversion based on exchange rates.</p>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th>Currency</th>';
        echo '<th>Regular Price</th>';
        echo '<th>Sale Price</th>';
        echo '</tr>';
        
        // Base currency (read-only)
        echo '<tr>';
        echo '<td><strong>' . $base_currency . ' (' . $all_currencies[$base_currency]['name'] . ') - Base Currency</strong></td>';
        echo '<td>' . $product->get_regular_price() . '</td>';
        echo '<td>' . $product->get_sale_price() . '</td>';
        echo '</tr>';
        
        // Other currencies
        foreach ($currencies as $currency) {
            if (!isset($all_currencies[$currency])) {
                continue;
            }
            
            $regular_price = get_post_meta($post->ID, '_regular_price_' . $currency, true);
            $sale_price = get_post_meta($post->ID, '_sale_price_' . $currency, true);
            
            echo '<tr>';
            echo '<td><strong>' . $currency . ' (' . $all_currencies[$currency]['name'] . ')</strong></td>';
            echo '<td><input type="text" name="multi_currency_regular_price[' . $currency . ']" value="' . esc_attr($regular_price) . '" placeholder="Auto"></td>';
            echo '<td><input type="text" name="multi_currency_sale_price[' . $currency . ']" value="' . esc_attr($sale_price) . '" placeholder="Auto"></td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        wp_nonce_field('multi_currency_switcher_save_product_prices', 'multi_currency_switcher_product_nonce');
    }

    public function save_product_currency_prices($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['multi_currency_switcher_product_nonce']) || 
            !wp_verify_nonce($_POST['multi_currency_switcher_product_nonce'], 'multi_currency_switcher_save_product_prices')) {
            return;
        }
        
        // Check if not autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save regular prices
        if (isset($_POST['multi_currency_regular_price']) && is_array($_POST['multi_currency_regular_price'])) {
            foreach ($_POST['multi_currency_regular_price'] as $currency => $price) {
                if (!empty($price)) {
                    update_post_meta($post_id, '_regular_price_' . $currency, wc_format_decimal($price));
                    
                    // If no sale price, use regular price as the _price
                    $sale_price = isset($_POST['multi_currency_sale_price'][$currency]) ? 
                                  $_POST['multi_currency_sale_price'][$currency] : '';
                                  
                    if (empty($sale_price)) {
                        update_post_meta($post_id, '_price_' . $currency, wc_format_decimal($price));
                    }
                } else {
                    delete_post_meta($post_id, '_regular_price_' . $currency);
                    
                    // If no regular price and no sale price, remove the _price too
                    if (empty($_POST['multi_currency_sale_price'][$currency])) {
                        delete_post_meta($post_id, '_price_' . $currency);
                    }
                }
            }
        }
        
        // Save sale prices
        if (isset($_POST['multi_currency_sale_price']) && is_array($_POST['multi_currency_sale_price'])) {
            foreach ($_POST['multi_currency_sale_price'] as $currency => $price) {
                if (!empty($price)) {
                    update_post_meta($post_id, '_sale_price_' . $currency, wc_format_decimal($price));
                    update_post_meta($post_id, '_price_' . $currency, wc_format_decimal($price));
                } else {
                    delete_post_meta($post_id, '_sale_price_' . $currency);
                    
                    // If no sale price but there is a regular price, set _price to regular price
                    $regular_price = isset($_POST['multi_currency_regular_price'][$currency]) ? 
                                    $_POST['multi_currency_regular_price'][$currency] : '';
                                    
                    if (!empty($regular_price)) {
                        update_post_meta($post_id, '_price_' . $currency, wc_format_decimal($regular_price));
                    } else {
                        delete_post_meta($post_id, '_price_' . $currency);
                    }
                }
            }
        }
    }
}

// Initialize the settings page
new Multi_Currency_Switcher_Admin_Settings();