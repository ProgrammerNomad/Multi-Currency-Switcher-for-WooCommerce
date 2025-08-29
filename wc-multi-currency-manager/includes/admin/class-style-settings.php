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
            'show_sticky_widget' => 'yes',
            'sticky_position' => 'left',
            'limit_currencies' => 'no',
            'show_flags' => 'none',
            'widget_style' => 'dropdown',
            'custom_css' => '',
        ));
        
        // Display any settings errors/notices
        settings_errors('wc_multi_currency_manager_messages');
        
        ?>
        <div class="wrap">
            <h1>Style Settings</h1>
            
            <?php $this->display_admin_tabs('style'); ?>
            
            <p>Customize the appearance of currency widgets and shortcodes used in your shop.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_style_settings', 'style_settings_nonce'); ?>
                
                <div class="card" style="margin-top: 20px;">
                    <h2>Widget Colors</h2>
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
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h2>Sticky Widget Settings</h2>
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
                    </table>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h2>Widget Style Options</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Default Widget Style</th>
                            <td>
                                <select name="style_settings[widget_style]">
                                    <option value="dropdown" <?php selected('dropdown', $style_settings['widget_style']); ?>>Dropdown</option>
                                    <option value="buttons" <?php selected('buttons', $style_settings['widget_style']); ?>>Buttons</option>
                                    <option value="links" <?php selected('links', $style_settings['widget_style']); ?>>Text Links</option>
                                </select>
                                <p class="description">Choose the default style for currency widgets (can be overridden in shortcodes)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Show Flags</th>
                            <td>
                                <select name="style_settings[show_flags]">
                                    <option value="none" <?php selected('none', $style_settings['show_flags']); ?>>No Flags</option>
                                    <option value="before" <?php selected('before', $style_settings['show_flags']); ?>>Before Currency Code</option>
                                    <option value="after" <?php selected('after', $style_settings['show_flags']); ?>>After Currency Code</option>
                                </select>
                                <p class="description">Show country flags in currency widgets</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h2>Custom CSS</h2>
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
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_style_settings" class="button-primary" value="Save Style Settings">
                </p>
            </form>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Style Shortcode Examples</h2>
                <p>Use these shortcode examples to customize the appearance of your currency switchers:</p>
                
                <table class="widefat" style="max-width: 100%;">
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
            'show_sticky_widget' => isset($_POST['style_settings']['show_sticky_widget']) ? 'yes' : 'no',
            'sticky_position' => sanitize_text_field($_POST['style_settings']['sticky_position']),
            'limit_currencies' => isset($_POST['style_settings']['limit_currencies']) ? 'yes' : 'no',
            'show_flags' => sanitize_text_field($_POST['style_settings']['show_flags']),
            'widget_style' => sanitize_text_field($_POST['style_settings']['widget_style']),
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
