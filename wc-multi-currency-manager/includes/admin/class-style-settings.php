<?php
// filepath: c:\xampp\htdocs\wc-multi-currency-manager-for-WooCommerce\wc-multi-currency-manager\includes\admin\class-style-settings.php
/**
 * Style Settings Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class wc_multi_currency_manager_Style_Settings {
    
    /**
     * Render the style settings page
     */
    public function render_page() {
        // Check if settings are being saved
        if (isset($_POST['save_style_settings']) && check_admin_referer('save_style_settings', 'style_settings_nonce')) {
            $this->save_style_settings();
        }

        // Get saved settings with defaults
        $style_settings = get_option('wc_multi_currency_manager_style_settings', array(
            'title_color' => '#333333',
            'text_color' => '#000000',
            'active_color' => '#04AE93',
            'background_color' => '#FFFFFF',
            'border_color' => '#B2B2B2',
            'custom_css' => '',
        ));
        
        // Display any settings errors/notices
        settings_errors('wc_multi_currency_manager_messages');
        
        ?>
        <div class="wrap">
            <h1>Style Settings</h1>
            
            <?php $this->display_admin_tabs('style'); ?>
            
            <p>Customize the appearance of currency widgets and shortcodes used in your shop.</p>
            
            <div class="wc-style-admin-container">
                <!-- Left Column: Combined Settings Form -->
                <div class="wc-style-column-left">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_style_settings', 'style_settings_nonce'); ?>
                        
                        <div class="card">
                            <h2>Style Settings</h2>
                            
                            <h3>Widget Colors</h3>
                            <p>Set the colors of all the currency widgets and switchers in your shop.</p>
                            
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

                            <h3>Custom CSS</h3>
                            <p>Add custom CSS to further style the currency switcher widgets.</p>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Custom CSS</th>
                                    <td>
                                        <textarea name="style_settings[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($style_settings['custom_css']); ?></textarea>
                                        <p class="description">Add custom CSS rules to override the default styles</p>
                                    </td>
                                </tr>
                            </table>

                            <h3>Style Preview</h3>
                            <p>Preview how your currency switcher will look with the selected colors:</p>
                            <div class="wc-style-preview" id="style-preview">
                                <div class="wc-currency-switcher-preview">
                                    <label class="preview-title">Select Currency:</label>
                                    <select class="preview-dropdown">
                                        <option>USD - US Dollar</option>
                                        <option selected>EUR - Euro</option>
                                        <option>GBP - British Pound</option>
                                    </select>
                                </div>
                            </div>

                            <div class="wc-style-form-actions">
                                <p class="submit">
                                    <input type="submit" name="save_style_settings" class="button-primary" value="Save Style Settings">
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Right Column: Style Shortcode Examples -->
                <div class="wc-style-column-right">
                    <div class="card">
                        <h2>Style Shortcode Examples</h2>
                        <p>Use these shortcode examples to customize the appearance of your currency switchers:</p>
                        
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
                                    <td>Default currency switcher using the style settings configured on this page</td>
                                </tr>
                                <tr>
                                    <td><code>[wc_multi_currency_manager style="buttons"]</code></td>
                                    <td>Currency switcher with button style</td>
                                </tr>
                                <tr>
                                    <td><code>[wc_multi_currency_manager style="links"]</code></td>
                                    <td>Currency switcher with text links style</td>
                                </tr>
                                <tr>
                                    <td><code>[wc_multi_currency_manager title="Select Your Currency"]</code></td>
                                    <td>Currency switcher with custom title</td>
                                </tr>
                                <tr>
                                    <td><code>[wc_multi_currency_manager currencies="USD,EUR,GBP"]</code></td>
                                    <td>Currency switcher limited to specific currencies</td>
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
     * Save the style settings
     */
    public function save_style_settings() {
        // Verify nonce
        if ( ! check_admin_referer('save_style_settings', 'style_settings_nonce') ) {
            return;
        }

        // Sanitize and save the style settings
        $style_settings = array(
            'title_color' => sanitize_hex_color($_POST['style_settings']['title_color']),
            'text_color' => sanitize_hex_color($_POST['style_settings']['text_color']),
            'active_color' => sanitize_hex_color($_POST['style_settings']['active_color']),
            'background_color' => sanitize_hex_color($_POST['style_settings']['background_color']),
            'border_color' => sanitize_hex_color($_POST['style_settings']['border_color']),
            'custom_css' => sanitize_textarea_field($_POST['style_settings']['custom_css']),
        );

        update_option('wc_multi_currency_manager_style_settings', $style_settings);
        
        add_settings_error(
            'wc_multi_currency_manager_messages',
            'style_settings_updated',
            'Style settings have been updated successfully.',
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
