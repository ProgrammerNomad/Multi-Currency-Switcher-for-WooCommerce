# Multi Currency Switcher for WooCommerce

A WooCommerce plugin for multi-currency switching, designed to maximize international sales by allowing customers to view and pay in their local currency.

## Features
- Add and manage unlimited currencies with a clean, intuitive interface
- Automatic exchange rate updates via API (e.g., European Central Bank)
- Manual exchange rate input for full control
- Geolocation-based currency detection for automatic currency switching
- Customizable currency format (symbol position, separators, decimals)
- Payment gateway restrictions for specific currencies
- Sticky currency converter widget with customizable position
- Consistent admin interface with intuitive navigation tabs
- Shortcodes for flexible currency switcher placement
- Product-specific pricing in different currencies
- Memory-optimized cart calculations for better performance
- Responsive design that works on all devices

## Installation
1. Download the plugin
2. Upload it to your WordPress site via the Plugins menu
3. Activate the plugin
4. Configure settings in the "Currency Switcher" menu in the WordPress admin dashboard

## Usage

### **Basic Configuration**
1. Navigate to Currency Switcher > General Settings to configure default options
2. Go to Currency Switcher > Currencies to enable currencies and set exchange rates
3. Use Currency Switcher > Style Settings to customize the appearance

### **Currency Management**
- Enable/disable currencies from the Currencies tab
- Set exchange rates manually or update automatically
- Configure decimal places, thousand separators, and symbol positions

### **Geolocation**
- Enable automatic currency detection based on visitor location
- Set fallback currency for when geolocation fails

### **Payment Gateway Restrictions**
- Control which payment methods are available for each currency
- Disable specific gateways for certain currencies to avoid processing fees

### **Style Customization**
- Customize colors for currency switcher elements
- Configure sticky widget position (left, right, top, bottom)
- Set text colors, background colors, and border styles

### **Product-Specific Pricing**
- Set product prices manually for each currency in the product edit page
- Override automatic currency conversion for specific products

### **Shortcode Usage**
- Use `[multi_currency_switcher]` to display the currency switcher anywhere on your site
- Additional parameters available for customization: `[multi_currency_switcher style="buttons" title="Select Currency"]`

## Performance Considerations
- The plugin includes memory optimization for cart calculations
- Caching is implemented for exchange rates to improve performance
- Product prices are cached to minimize database queries

## Troubleshooting
- If you encounter memory issues, increase your PHP memory limit to at least 256MB
- For issues with currency display, clear your WooCommerce cache
- Check the debug log for detailed error messages if you encounter problems

## Author
Created by [ProgrammerNomad](https://github.com/ProgrammerNomad).

## License
This plugin is licensed under the MIT License. See the LICENSE file for details.

## Support
For issues or feature requests, please visit the [GitHub repository](https://github.com/ProgrammerNomad).