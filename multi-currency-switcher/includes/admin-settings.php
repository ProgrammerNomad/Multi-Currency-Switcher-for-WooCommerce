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
    }

    public function add_admin_menu() {
        add_menu_page(
            'Multi Currency Switcher',
            'Currency Switcher',
            'manage_options',
            'multi-currency-switcher',
            array( $this, 'create_settings_page' ),
            'dashicons-money-alt',
            100
        );

        add_submenu_page(
            'multi-currency-switcher',
            'Currencies',
            'Currencies',
            'manage_options',
            'multi-currency-switcher-currencies',
            array( $this, 'create_currencies_page' )
        );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1>Multi Currency Switcher Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'multi_currency_switcher_settings' );
                do_settings_sections( 'multi_currency_switcher' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function create_currencies_page() {
        if ( isset( $_POST['save_currencies'] ) ) {
            $this->save_currencies();
        }

        $all_currencies = $this->get_all_available_currencies();
        $enabled_currencies = get_option( 'multi_currency_switcher_enabled_currencies', array( 'USD' ) );
        $exchange_rates = get_option( 'multi_currency_switcher_exchange_rates', array() );
        $currency_settings = get_option( 'multi_currency_switcher_currency_settings', array() );

        ?>
        <div class="wrap">
            <h1>Manage Currencies</h1>
            <p>Select currencies to enable in your shop and set their exchange rates.</p>

            <form method="post" action="">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Enable</th>
                            <th>Currency</th>
                            <th>Symbol</th>
                            <th>Exchange Rate (1 USD =)</th>
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
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" <?php checked( $is_enabled, true ); ?>>
                            </td>
                            <td>
                                <?php echo esc_html( $currency['name'] ); ?> (<?php echo esc_html( $code ); ?>)
                            </td>
                            <td>
                                <?php echo esc_html( $currency['symbol'] ); ?>
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0.0001" name="currencies[<?php echo esc_attr( $code ); ?>][rate]" value="<?php echo esc_attr( $exchange_rate ); ?>" <?php if ( $code === 'USD' ) echo 'readonly'; ?>>
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
        $exchange_rates = array();
        $currency_settings = array();

        // Always include USD as the base currency
        $enabled_currencies[] = 'USD';
        $exchange_rates['USD'] = 1;

        foreach ( $_POST['currencies'] as $code => $data ) {
            if ( isset( $data['enabled'] ) ) {
                $enabled_currencies[] = $code;
            }

            $exchange_rates[ $code ] = floatval( $data['rate'] );

            $currency_settings[ $code ] = array(
                'position' => sanitize_text_field( $data['position'] ),
                'decimals' => intval( $data['decimals'] ),
                'thousand_sep' => sanitize_text_field( $data['thousand_sep'] ),
                'decimal_sep' => sanitize_text_field( $data['decimal_sep'] )
            );
        }

        update_option( 'multi_currency_switcher_enabled_currencies', array_unique( $enabled_currencies ) );
        update_option( 'multi_currency_switcher_exchange_rates', $exchange_rates );
        update_option( 'multi_currency_switcher_currency_settings', $currency_settings );

        add_settings_error(
            'multi_currency_switcher_messages',
            'currencies_updated',
            'Currencies have been updated successfully.',
            'updated'
        );
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

    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'multi-currency-switcher' ) !== false ) {
            wp_enqueue_style( 'multi-currency-switcher-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-styles.css', array(), '1.0.0' );
        }
    }

    private function get_all_available_currencies() {
        return array(
            'USD' => array( 'name' => 'US Dollar', 'symbol' => '$' ),
            'EUR' => array( 'name' => 'Euro', 'symbol' => '€' ),
            'GBP' => array( 'name' => 'British Pound', 'symbol' => '£' ),
            'JPY' => array( 'name' => 'Japanese Yen', 'symbol' => '¥' ),
            'AUD' => array( 'name' => 'Australian Dollar', 'symbol' => 'A$' ),
            'CAD' => array( 'name' => 'Canadian Dollar', 'symbol' => 'C$' ),
            'CHF' => array( 'name' => 'Swiss Franc', 'symbol' => 'CHF' ),
            'CNY' => array( 'name' => 'Chinese Yuan', 'symbol' => '¥' ),
            'SEK' => array( 'name' => 'Swedish Krona', 'symbol' => 'kr' ),
            'NZD' => array( 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$' ),
            'MXN' => array( 'name' => 'Mexican Peso', 'symbol' => '$' ),
            'SGD' => array( 'name' => 'Singapore Dollar', 'symbol' => 'S$' ),
            'HKD' => array( 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$' ),
            'NOK' => array( 'name' => 'Norwegian Krone', 'symbol' => 'kr' ),
            'KRW' => array( 'name' => 'South Korean Won', 'symbol' => '₩' ),
            'TRY' => array( 'name' => 'Turkish Lira', 'symbol' => '₺' ),
            'RUB' => array( 'name' => 'Russian Ruble', 'symbol' => '₽' ),
            'INR' => array( 'name' => 'Indian Rupee', 'symbol' => '₹' ),
            'BRL' => array( 'name' => 'Brazilian Real', 'symbol' => 'R$' ),
            'ZAR' => array( 'name' => 'South African Rand', 'symbol' => 'R' ),
            'AED' => array( 'name' => 'United Arab Emirates Dirham', 'symbol' => 'د.إ' ),
            'AFN' => array( 'name' => 'Afghan Afghani', 'symbol' => '؋' ),
            'ALL' => array( 'name' => 'Albanian Lek', 'symbol' => 'L' ),
            'AMD' => array( 'name' => 'Armenian Dram', 'symbol' => '֏' ),
            'ANG' => array( 'name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ' ),
            'AOA' => array( 'name' => 'Angolan Kwanza', 'symbol' => 'Kz' ),
            'ARS' => array( 'name' => 'Argentine Peso', 'symbol' => '$' ),
            'AWG' => array( 'name' => 'Aruban Florin', 'symbol' => 'ƒ' ),
            'AZN' => array( 'name' => 'Azerbaijani Manat', 'symbol' => '₼' ),
            'BAM' => array( 'name' => 'Bosnia-Herzegovina Convertible Mark', 'symbol' => 'KM' ),
            'BBD' => array( 'name' => 'Barbadian Dollar', 'symbol' => '$' ),
            'BDT' => array( 'name' => 'Bangladeshi Taka', 'symbol' => '৳' ),
            'BGN' => array( 'name' => 'Bulgarian Lev', 'symbol' => 'лв' ),
            'BHD' => array( 'name' => 'Bahraini Dinar', 'symbol' => '.د.ب' ),
            'BIF' => array( 'name' => 'Burundian Franc', 'symbol' => 'FBu' ),
            'BMD' => array( 'name' => 'Bermudan Dollar', 'symbol' => '$' ),
            'BND' => array( 'name' => 'Brunei Dollar', 'symbol' => '$' ),
            'BOB' => array( 'name' => 'Bolivian Boliviano', 'symbol' => 'Bs.' ),
            'BSD' => array( 'name' => 'Bahamian Dollar', 'symbol' => '$' ),
            'BTN' => array( 'name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.' ),
            'BWP' => array( 'name' => 'Botswanan Pula', 'symbol' => 'P' ),
            'BYN' => array( 'name' => 'Belarusian Ruble', 'symbol' => 'Br' ),
            'BZD' => array( 'name' => 'Belize Dollar', 'symbol' => 'BZ$' ),
            'CDF' => array( 'name' => 'Congolese Franc', 'symbol' => 'FC' ),
            'CLP' => array( 'name' => 'Chilean Peso', 'symbol' => '$' ),
            'COP' => array( 'name' => 'Colombian Peso', 'symbol' => '$' ),
            'CRC' => array( 'name' => 'Costa Rican Colón', 'symbol' => '₡' ),
            'CUC' => array( 'name' => 'Cuban Convertible Peso', 'symbol' => '$' ),
            'CUP' => array( 'name' => 'Cuban Peso', 'symbol' => '₱' ),
            'CVE' => array( 'name' => 'Cape Verdean Escudo', 'symbol' => '$' ),
            'CZK' => array( 'name' => 'Czech Republic Koruna', 'symbol' => 'Kč' ),
            'DJF' => array( 'name' => 'Djiboutian Franc', 'symbol' => 'Fdj' ),
            'DKK' => array( 'name' => 'Danish Krone', 'symbol' => 'kr' ),
            'DOP' => array( 'name' => 'Dominican Peso', 'symbol' => 'RD$' ),
            'DZD' => array( 'name' => 'Algerian Dinar', 'symbol' => 'دج' ),
            'EGP' => array( 'name' => 'Egyptian Pound', 'symbol' => 'E£' ),
            'ERN' => array( 'name' => 'Eritrean Nakfa', 'symbol' => 'Nfk' ),
            'ETB' => array( 'name' => 'Ethiopian Birr', 'symbol' => 'Br' ),
            'FJD' => array( 'name' => 'Fijian Dollar', 'symbol' => '$' ),
            'FKP' => array( 'name' => 'Falkland Islands Pound', 'symbol' => '£' ),
            'GEL' => array( 'name' => 'Georgian Lari', 'symbol' => '₾' ),
            'GGP' => array( 'name' => 'Guernsey Pound', 'symbol' => '£' ),
            'GHS' => array( 'name' => 'Ghanaian Cedi', 'symbol' => '₵' ),
            'GIP' => array( 'name' => 'Gibraltar Pound', 'symbol' => '£' ),
            'GMD' => array( 'name' => 'Gambian Dalasi', 'symbol' => 'D' ),
            'GNF' => array( 'name' => 'Guinean Franc', 'symbol' => 'FG' ),
            'GTQ' => array( 'name' => 'Guatemalan Quetzal', 'symbol' => 'Q' ),
            'GYD' => array( 'name' => 'Guyanaese Dollar', 'symbol' => '$' ),
            'HNL' => array( 'name' => 'Honduran Lempira', 'symbol' => 'L' ),
            'HRK' => array( 'name' => 'Croatian Kuna', 'symbol' => 'kn' ),
            'HTG' => array( 'name' => 'Haitian Gourde', 'symbol' => 'G' ),
            'HUF' => array( 'name' => 'Hungarian Forint', 'symbol' => 'Ft' ),
            'IDR' => array( 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp' ),
            'ILS' => array( 'name' => 'Israeli New Shekel', 'symbol' => '₪' ),
            'IMP' => array( 'name' => 'Manx pound', 'symbol' => '£' ),
            'IQD' => array( 'name' => 'Iraqi Dinar', 'symbol' => 'ع.د' ),
            'IRR' => array( 'name' => 'Iranian Rial', 'symbol' => '﷼' ),
            'ISK' => array( 'name' => 'Icelandic Króna', 'symbol' => 'kr' ),
            'JEP' => array( 'name' => 'Jersey Pound', 'symbol' => '£' ),
            'JMD' => array( 'name' => 'Jamaican Dollar', 'symbol' => 'J$' ),
            'JOD' => array( 'name' => 'Jordanian Dinar', 'symbol' => 'د.ا' ),
            'KES' => array( 'name' => 'Kenyan Shilling', 'symbol' => 'KSh' ),
            'KGS' => array( 'name' => 'Kyrgystani Som', 'symbol' => 'с' ),
            'KHR' => array( 'name' => 'Cambodian Riel', 'symbol' => '៛' ),
            'KMF' => array( 'name' => 'Comorian Franc', 'symbol' => 'CF' ),
            'KPW' => array( 'name' => 'North Korean Won', 'symbol' => '₩' ),
            'KWD' => array( 'name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك' ),
            'KYD' => array( 'name' => 'Cayman Islands Dollar', 'symbol' => '$' ),
            'KZT' => array( 'name' => 'Kazakhstani Tenge', 'symbol' => '₸' ),
            'LAK' => array( 'name' => 'Laotian Kip', 'symbol' => '₭' ),
            'LBP' => array( 'name' => 'Lebanese Pound', 'symbol' => 'ل.ل' ),
            'LKR' => array( 'name' => 'Sri Lankan Rupee', 'symbol' => '₨' ),
            'LRD' => array( 'name' => 'Liberian Dollar', 'symbol' => '$' ),
            'LSL' => array( 'name' => 'Lesotho Loti', 'symbol' => 'L' ),
            'LYD' => array( 'name' => 'Libyan Dinar', 'symbol' => 'ل.د' ),
            'MAD' => array( 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.' ),
            'MDL' => array( 'name' => 'Moldovan Leu', 'symbol' => 'L' ),
            'MGA' => array( 'name' => 'Malagasy Ariary', 'symbol' => 'Ar' ),
            'MKD' => array( 'name' => 'Macedonian Denar', 'symbol' => 'ден' ),
            'MMK' => array( 'name' => 'Myanma Kyat', 'symbol' => 'Ks' ),
            'MNT' => array( 'name' => 'Mongolian Tugrik', 'symbol' => '₮' ),
            'MOP' => array( 'name' => 'Macanese Pataca', 'symbol' => 'MOP$' ),
            'MRU' => array( 'name' => 'Mauritanian Ouguiya', 'symbol' => 'UM' ),
            'MUR' => array( 'name' => 'Mauritian Rupee', 'symbol' => '₨' ),
            'MVR' => array( 'name' => 'Maldivian Rufiyaa', 'symbol' => 'Rf' ),
            'MWK' => array( 'name' => 'Malawian Kwacha', 'symbol' => 'MK' ),
            'MYR' => array( 'name' => 'Malaysian Ringgit', 'symbol' => 'RM' ),
            'MZN' => array( 'name' => 'Mozambican Metical', 'symbol' => 'MT' ),
            'NAD' => array( 'name' => 'Namibian Dollar', 'symbol' => '$' ),
            'NGN' => array( 'name' => 'Nigerian Naira', 'symbol' => '₦' ),
            'NIO' => array( 'name' => 'Nicaraguan Córdoba', 'symbol' => 'C$' ),
            'NPR' => array( 'name' => 'Nepalese Rupee', 'symbol' => '₨' ),
            'OMR' => array( 'name' => 'Omani Rial', 'symbol' => 'ر.ع.' ),
            'PAB' => array( 'name' => 'Panamanian Balboa', 'symbol' => 'B/.' ),
            'PEN' => array( 'name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/' ),
            'PGK' => array( 'name' => 'Papua New Guinean Kina', 'symbol' => 'K' ),
            'PHP' => array( 'name' => 'Philippine Peso', 'symbol' => '₱' ),
            'PKR' => array( 'name' => 'Pakistani Rupee', 'symbol' => '₨' ),
            'PLN' => array( 'name' => 'Polish Zloty', 'symbol' => 'zł' ),
            'PYG' => array( 'name' => 'Paraguayan Guarani', 'symbol' => '₲' ),
            'QAR' => array( 'name' => 'Qatari Rial', 'symbol' => 'ر.ق' ),
            'RON' => array( 'name' => 'Romanian Leu', 'symbol' => 'lei' ),
            'RSD' => array( 'name' => 'Serbian Dinar', 'symbol' => 'дин.' ),
            'RWF' => array( 'name' => 'Rwandan Franc', 'symbol' => 'FRw' ),
            'SAR' => array( 'name' => 'Saudi Riyal', 'symbol' => 'ر.س' ),
            'SBD' => array( 'name' => 'Solomon Islands Dollar', 'symbol' => '$' ),
            'SCR' => array( 'name' => 'Seychellois Rupee', 'symbol' => '₨' ),
            'SDG' => array( 'name' => 'Sudanese Pound', 'symbol' => 'ج.س.' ),
            'SHP' => array( 'name' => 'Saint Helena Pound', 'symbol' => '£' ),
            'SLL' => array( 'name' => 'Sierra Leonean Leone', 'symbol' => 'Le' ),
            'SOS' => array( 'name' => 'Somali Shilling', 'symbol' => 'Sh' ),
            'SRD' => array( 'name' => 'Surinamese Dollar', 'symbol' => '$' ),
            'SSP' => array( 'name' => 'South Sudanese Pound', 'symbol' => '£' ),
            'STN' => array( 'name' => 'São Tomé and Príncipe Dobra', 'symbol' => 'Db' ),
            'SVC' => array( 'name' => 'Salvadoran Colón', 'symbol' => '₡' ),
            'SYP' => array( 'name' => 'Syrian Pound', 'symbol' => '£' ),
            'SZL' => array( 'name' => 'Swazi Lilangeni', 'symbol' => 'L' ),
            'THB' => array( 'name' => 'Thai Baht', 'symbol' => '฿' ),
            'TJS' => array( 'name' => 'Tajikistani Somoni', 'symbol' => 'ЅМ' ),
            'TMT' => array( 'name' => 'Turkmenistani Manat', 'symbol' => 'm' ),
            'TND' => array( 'name' => 'Tunisian Dinar', 'symbol' => 'د.ت' ),
            'TOP' => array( 'name' => 'Tongan Pa\'anga', 'symbol' => 'T$' ),
            'TTD' => array( 'name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$' ),
            'TWD' => array( 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$' ),
            'TZS' => array( 'name' => 'Tanzanian Shilling', 'symbol' => 'TSh' ),
            'UAH' => array( 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴' ),
            'UGX' => array( 'name' => 'Ugandan Shilling', 'symbol' => 'USh' ),
            'UYU' => array( 'name' => 'Uruguayan Peso', 'symbol' => '$U' ),
            'UZS' => array( 'name' => 'Uzbekistan Som', 'symbol' => 'лв' ),
            'VES' => array( 'name' => 'Venezuelan Bolívar Soberano', 'symbol' => 'Bs.' ),
            'VND' => array( 'name' => 'Vietnamese Dong', 'symbol' => '₫' ),
            'VUV' => array( 'name' => 'Vanuatu Vatu', 'symbol' => 'VT' ),
            'WST' => array( 'name' => 'Samoan Tala', 'symbol' => 'WS$' ),
            'XAF' => array( 'name' => 'CFA Franc BEAC', 'symbol' => 'FCFA' ),
            'XCD' => array( 'name' => 'East Caribbean Dollar', 'symbol' => '$' ),
            'XOF' => array( 'name' => 'CFA Franc BCEAO', 'symbol' => 'CFA' ),
            'XPF' => array( 'name' => 'CFP Franc', 'symbol' => '₣' ),
            'YER' => array( 'name' => 'Yemeni Rial', 'symbol' => '﷼' ),
            'ZMW' => array( 'name' => 'Zambian Kwacha', 'symbol' => 'ZK' ),
            'ZWL' => array( 'name' => 'Zimbabwean Dollar', 'symbol' => '$' ),
        );
    }
}

// Initialize the settings page
new Multi_Currency_Switcher_Admin_Settings();