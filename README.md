# 🌍 WC Multi Currency Manager

A professional WooCommerce plugin for multi-currency management, designed to maximize international sales by allowing customers to view and pay in their local currency.

## 📋 **Current Features (v1.0.0)**

### ✅ **Core Currency Management**
- **Multi-currency Support**: Enable multiple currencies in your WooCommerce store
- **Exchange Rate Management**: Manual exchange rate configuration with automatic updates
- **Currency Formatting**: Customize symbol position, decimal places, and separators per currency
- **Base Currency Integration**: Seamless integration with WooCommerce base currency
- **Enable/Disable Control**: Easy currency activation/deactivation

### ✅ **Professional Admin Interface**
- **General Settings**: Centralized configuration for all currency options
- **Currency Management**: Dedicated page for adding/editing currencies
- **Style Customization**: Color schemes and visual customization options
- **Payment Restrictions**: Control which payment methods work with specific currencies
- **Card-based Design**: Modern, WordPress-standard admin interface
- **Plugin Directory Integration**: Quick access settings link

### ✅ **Frontend Integration**
- **Currency Switcher**: Dropdown widget for currency selection
- **Product Page Integration**: Automatic currency conversion on product pages
- **Cart & Checkout**: Full cart and checkout currency support
- **Session Management**: Persistent currency selection across user sessions
- **Cookie-based Storage**: Remember user preferences

### ✅ **Cart & Mini-Cart Features**
- **Real-time Cart Updates**: Instant price conversion when currency changes
- **Mini-Cart Fragments**: Proper AJAX updates for mini-cart widget
- **Cart Page Integration**: Currency information display on cart page
- **Theme Compatibility**: Works with Storefront and most themes
- **Performance Optimized**: Efficient cart recalculation with caching

### ✅ **Checkout & Order Management**
- **Checkout Currency Lock**: Prevents currency switching during checkout process
- **Order Currency Tracking**: Saves currency and exchange rate with each order
- **Admin Order Display**: Shows currency information in order details
- **Order History**: Correct currency display in customer order history
- **Thank You Page**: Order summary with currency details
- **Email Integration**: Proper currency formatting in order emails

### ✅ **Enhanced User Experience**
- **Currency Information**: Clear display of exchange rates and currency details
- **Loading Indicators**: Visual feedback during currency switches
- **Error Handling**: Graceful fallbacks and error messages
- **Browser Compatibility**: Works with all modern browsers
- **Mobile Responsive**: Touch-friendly currency switchers

### ✅ **Widget & Display Options**
- **Sticky Widget**: Optional floating currency switcher
- **Position Control**: Left/right positioning for sticky widget
- **Multiple Styles**: Dropdown, buttons, and link display options
- **Flag Support**: Optional country flag display
- **Shortcode Support**: `[wc_multi_currency_manager]` with parameters

### ✅ **Payment Gateway Integration**
- **Gateway Filtering**: Show/hide payment methods per currency
- **Currency-Specific Settings**: Configure gateways for specific currencies
- **Checkout Validation**: Ensures payment method compatibility

### ✅ **Advanced Features**
- **HPOS Support**: Compatible with WooCommerce High-Performance Order Storage
- **Order Columns**: Additional currency columns in admin order lists
- **Coupon Conversion**: Automatic coupon amount conversion
- **Shipping Conversion**: Shipping cost calculation in selected currency
- **Tax Integration**: Proper tax calculation with currency conversion

### ✅ **Customization & Styling**
- **Color Customization**: Titles, text, active selection, background, borders
- **Custom CSS Support**: Advanced styling options
- **Responsive Design**: Mobile-friendly currency switchers
- **Theme Compatibility**: Works with most WordPress themes
- **Nice Select Support**: Compatible with custom select styling plugins

---

## 🚀 **Planned Features (Roadmap)**

### **Phase 1: Advanced Rate Management**
- 🔲 **Automatic Exchange Rates**: Integration with Fixer.io, CurrencyLayer, OpenExchangeRates
- 🔲 **Scheduled Updates**: Hourly/daily automatic rate updates
- 🔲 **Rate Providers**: Multiple providers with fallback options
- 🔲 **Update Logs**: Rate change history and error logging

### ✅ **Phase 2: Geolocation & Smart Detection (COMPLETED)**
- ✅ **IP Geolocation**: Automatic currency detection based on visitor location using WooCommerce geolocation
- ✅ **Country Rules**: Specific currency rules per country with comprehensive mapping system
- ✅ **Geolocation Settings**: Advanced rule management interface with 240+ countries
- ✅ **Default Currency Mappings**: Pre-configured country-currency mappings with customization options
- ✅ **Custom Mapping Management**: Admin interface to override default mappings per country
- ✅ **Fallback Mechanisms**: Smart defaults when detection fails (enabled currencies → WooCommerce default)
- ✅ **Search Functionality**: Country search in mapping interface for easy management
- ✅ **Navigation Integration**: Seamless links between general settings and currency mapping

### **Phase 3: Enhanced Shortcodes & Widgets**
- 🔲 **Currency Converter**: Real-time conversion calculator widget
- 🔲 **Rate Display**: Show current exchange rates
- 🔲 **Alphabetic Lists**: Organized currency displays
- 🔲 **Shortcode Builder**: Visual shortcode creation tool
- 🔲 **WordPress Widgets**: Sidebar integration
- 🔲 **Gutenberg Blocks**: Block editor integration

### **Phase 4: Advanced E-commerce Features**
- 🔲 **Coupon Conversion**: Fixed-amount coupon currency conversion
- 🔲 **Shipping Costs**: Currency-specific shipping rates
- 🔲 **Free Shipping Thresholds**: Per-currency minimum order amounts
- 🔲 **Enhanced Product Pricing**: Advanced per-currency pricing options
- 🔲 **Tax Integration**: Currency-specific tax handling

### **Phase 5: Analytics & Reporting**
- 🔲 **Order Currency Tracking**: Detailed currency usage analytics
- 🔲 **Revenue Reporting**: Currency-based sales reports
- 🔲 **Rate History**: Historical exchange rate tracking
- 🔲 **Customer Insights**: Currency preference analytics

### **Phase 6: Integrations & Compatibility**
- 🔲 **Page Builder Support**: Elementor, Beaver Builder integration
- 🔲 **SEO Optimization**: Yoast, RankMath compatibility
- 🔲 **Caching Solutions**: WP Rocket, W3 Total Cache support
- 🔲 **Popular Themes**: Compatibility testing and optimization
- Consistent admin interface with intuitive navigation tabs
- Shortcodes for flexible currency switcher placement
- Responsive design that works on all devices

## Installation
1. Download the plugin
2. Upload it to your WordPress site via the Plugins menu
3. Activate the plugin
4. Configure settings in the "Currency Manager" menu in the WordPress admin dashboard

## Usage

### **Basic Configuration**
1. Navigate to Currency Manager > General Settings to configure default options
2. Go to Currency Manager > Currencies to enable currencies and set exchange rates
3. Use Currency Manager > Style Settings to customize the appearance

### **Currency Management**
- Add currencies using the dropdown menu on the Currencies tab
- Remove currencies you don't need with the remove button
- Set exchange rates manually for each currency
- Configure decimal places, thousand separators, and symbol positions

### **Payment Gateway Restrictions**
- Control which payment methods are available for each currency
- Disable specific gateways for certain currencies to avoid processing fees

### **Style Customization**
- Customize colors for currency switcher elements
- Configure widget position
- Set text colors, background colors, and border styles

### **Shortcode Usage**
- Use `[wc_multi_currency_manager]` to display the currency switcher anywhere on your site
- Additional parameters available for customization: `[wc_multi_currency_manager style="dropdown"]`

## Troubleshooting
- If you encounter memory issues with large currency lists, increase your PHP memory limit or reduce the number of enabled currencies
- For issues with currency display, clear your WooCommerce cache
- Check that your currency settings match your WooCommerce general settings

## Author
Created by [ProgrammerNomad](https://github.com/ProgrammerNomad).

## License
This plugin is licensed under the MIT License. See the LICENSE file for details.

## Support
For issues or feature requests, please visit the [GitHub repository](https://github.com/ProgrammerNomad).