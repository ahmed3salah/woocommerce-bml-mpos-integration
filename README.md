# BML Connect Payment Gateway for WooCommerce v2.0

A comprehensive WooCommerce payment gateway integration for Bank of Maldives Connect API v2.0, featuring real-time webhooks, enhanced security, and modern WordPress standards.

![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)

## 🚀 Features

### ✨ New in v2.0

- **Real-time Webhooks**: Instant payment notifications for better user experience
- **Enhanced Security**: Advanced signature verification and nonce protection
- **Modern API Integration**: Full BML Connect API v2.0 compatibility
- **Improved Error Handling**: Comprehensive logging and debugging tools
- **HPOS Compatibility**: Support for WooCommerce High-Performance Order Storage
- **Better Admin Experience**: Enhanced settings panel with webhook URL display

### 🔧 Core Features

- Secure payment processing via BML Connect API
- Multiple payment methods (BML ePOS, Alipay, Credit/Debit Cards)
- Sandbox and Production environment support
- Transaction history tracking
- Order status synchronization
- SSL/TLS security enforcement
- Multilingual support ready

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **WooCommerce**: 3.0 or higher
- **PHP**: 7.4 or higher
- **BML Connect Account**: Merchant account with API credentials
- **SSL Certificate**: Required for production use
- **Composer**: For dependency management

## 🛠 Installation

### Method 1: Manual Installation

1. **Download the Plugin**

   ```bash
   git clone https://github.com/your-repo/bml-woocommerce.git
   cd bml-woocommerce
   ```

2. **Install Dependencies**

   ```bash
   composer install --no-dev
   ```

3. **Upload to WordPress**

   - Upload the entire plugin folder to `/wp-content/plugins/`
   - Or create a ZIP file and upload via WordPress admin

4. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "BML Connect Payment Gateway" and click "Activate"

### Method 2: WordPress Admin Upload

1. Download the latest release ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

## ⚙️ Configuration

### 1. Basic Setup

1. Navigate to **WooCommerce → Settings → Payments**
2. Find **BML Connect Payment** and click **"Manage"**
3. Configure the following settings:

#### Required Settings

- **Enable Gateway**: Check to enable the payment method
- **API Key (Client Secret)**: Your BML Connect API key from merchant portal
- **App ID (Client ID)**: Your BML Connect application ID
- **Environment**: Choose Sandbox for testing, uncheck for Production

#### Optional Settings

- **Title**: Payment method name shown to customers
- **Description**: Payment method description
- **Payment Provider**: Select specific provider or let customers choose
- **Display Icon**: Show/hide BML logo at checkout
- **Debug Mode**: Enable for detailed logging

### 2. Webhook Configuration

Webhooks provide real-time payment notifications for instant order updates.

#### In WordPress Admin:

1. Go to payment gateway settings
2. Copy the displayed **Webhook URL** (appears at top of settings page)
3. Optionally set a **Webhook Secret** for additional security

#### In BML Merchant Portal:

1. Log into your BML merchant dashboard
2. Navigate to **Connect → Webhooks**
3. Add the webhook URL: `https://yoursite.com/?wc-api=bml_webhook`
4. Save the configuration

### 3. Security Configuration

#### SSL Certificate (Required for Production)

- Ensure your website has a valid SSL certificate
- WooCommerce will enforce SSL on checkout pages
- The plugin will show warnings if SSL is not properly configured

#### Webhook Security (Recommended)

- Set a webhook secret in the gateway settings
- This adds HMAC signature verification for webhook requests
- Helps prevent unauthorized webhook calls

## 🧪 Testing

### Sandbox Environment

1. **Enable Sandbox Mode**

   - Check "Enable Sandbox Mode" in gateway settings
   - Use sandbox API credentials from BML merchant portal

2. **Test Transactions**

   - Create test orders in your WooCommerce store
   - Complete payments using BML sandbox environment
   - Verify webhook notifications in WooCommerce logs

3. **Debugging**
   - Enable "Debug Mode" in gateway settings
   - Check logs at **WooCommerce → Status → Logs**
   - Look for logs with source "bml-connect" and "bml-webhook"

### Production Deployment

1. **Switch to Production**

   - Uncheck "Enable Sandbox Mode"
   - Update API credentials to production keys
   - Test with small amounts initially

2. **Monitor Operations**
   - Regularly check WooCommerce logs
   - Monitor order statuses for any issues
   - Verify webhook notifications are working

## 🔍 Troubleshooting

### Common Issues

#### Payment Not Completing

- **Check webhook configuration**: Ensure webhook URL is correctly set in BML portal
- **Verify API credentials**: Confirm sandbox/production keys match environment
- **Check logs**: Look for errors in WooCommerce logs
- **SSL issues**: Ensure SSL certificate is valid and enforced

#### Webhook Not Working

- **URL accessibility**: Ensure webhook URL is publicly accessible
- **Signature verification**: Check if webhook secret matches between plugin and BML portal
- **Server logs**: Check server error logs for any blocking issues

#### Order Status Issues

- **Webhook delays**: BML may have delays in sending webhooks
- **Double processing**: Plugin prevents duplicate webhook processing
- **Manual verification**: Orders can be manually updated if needed

### Debug Information

#### Enable Detailed Logging

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Check Log Files

- **WooCommerce Logs**: Admin → WooCommerce → Status → Logs
- **PHP Error Logs**: Server error logs
- **Webhook Logs**: Look for "bml-webhook" source in WooCommerce logs

### Support Resources

- **BML Connect Documentation**: [Official API Docs](https://github.com/bankofmaldives/bml-connect)
- **WooCommerce Documentation**: [Payment Gateway Development](https://woocommerce.com/document/payment-gateway-api/)
- **WordPress Debugging**: [WordPress Debug Guide](https://wordpress.org/support/article/debugging-in-wordpress/)

## 🔒 Security Features

### Payment Security

- **PCI DSS Compliance**: No card data stored on your server
- **Signature Verification**: All transactions verified with BML signatures
- **Nonce Protection**: WordPress nonces prevent CSRF attacks
- **SSL Enforcement**: HTTPS required for all payment transactions

### Webhook Security

- **HMAC Verification**: Optional webhook signature verification
- **Payload Validation**: All webhook data validated before processing
- **Rate Limiting**: Built-in protection against webhook flooding
- **Security Headers**: Enhanced headers for webhook endpoints

## 🏗 Architecture

### File Structure

```
bml-woocommerce/
├── app/
│   ├── wc-bml-mpos-gateway-class.php      # Main gateway class
│   ├── wc-bml-mpos-gateway-init.php       # Gateway initialization
│   ├── wc-bml-webhook-handler.php         # Webhook processing
│   ├── wc-bml-mpos-gateway-menu-column.php # Admin columns
│   ├── functions.php                      # Utility functions
│   └── icons/                            # Payment method icons
├── vendor/                               # Composer dependencies
├── assets/                              # Plugin assets
├── wc-bml-mpos-gateway.php             # Main plugin file
├── composer.json                       # Dependencies
└── README.md                          # Documentation
```

### Integration Flow

1. **Order Creation**: Customer initiates payment at checkout
2. **Transaction Creation**: Plugin creates transaction via BML Connect API
3. **Payment Processing**: Customer redirected to BML payment interface
4. **Webhook Notification**: BML sends real-time status updates
5. **Order Completion**: Plugin processes webhook and updates order status

## 📊 Monitoring and Analytics

### Transaction Tracking

- All transaction IDs stored in order metadata
- Transaction history maintained for audit purposes
- Payment provider information recorded
- Webhook processing timestamps logged

### Performance Monitoring

- Debug mode provides detailed operation logs
- Error tracking with context information
- Webhook response time monitoring
- API call success/failure rates

## 🤝 Contributing

We welcome contributions to improve this plugin. Please follow these guidelines:

1. **Fork the Repository**
2. **Create Feature Branch**: `git checkout -b feature/new-feature`
3. **Commit Changes**: `git commit -am 'Add new feature'`
4. **Push to Branch**: `git push origin feature/new-feature`
5. **Submit Pull Request**

### Development Setup

```bash
# Clone repository
git clone https://github.com/your-repo/bml-woocommerce.git

# Install development dependencies
composer install

# Run tests (if available)
composer test
```

## 📄 License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

## ⚠️ Disclaimer

This plugin is developed independently and is not officially endorsed by Bank of Maldives. While we strive for reliability and security, please use at your own risk and thoroughly test in a staging environment before production deployment.

## 🆘 Support

### Community Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/your-repo/bml-woocommerce/issues)
- **WordPress Support**: [Plugin support forum](https://wordpress.org/support/plugin/bml-connect-woocommerce/)

### Commercial Support

For priority support, custom modifications, or consulting services, please contact the plugin maintainers.

### BML Connect API Support

For issues related to the BML Connect API itself, please contact Bank of Maldives directly:

- **Email**: ahmed3salah311@gmail.com
- **Merchant Portal**: [https://dashboard.merchants.bankofmaldives.com.mv](https://dashboard.merchants.bankofmaldives.com.mv)

---

**Made with ❤️ for the Maldivian e-commerce community**
