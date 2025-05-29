# Changelog

All notable changes to the BML Connect Payment Gateway for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-01-XX

### 🚀 Major Release - Complete Rewrite

This is a major release that completely modernizes the plugin with BML Connect API v2.0 support, real-time webhooks, enhanced security, and improved architecture.

### ✨ Added

#### Core Features

- **Real-time Webhooks**: Complete webhook system for instant payment notifications
- **Enhanced Security**: Advanced signature verification, nonce protection, and webhook HMAC validation
- **Modern API Integration**: Full compatibility with BML Connect API v2.0 specifications
- **HPOS Compatibility**: Support for WooCommerce High-Performance Order Storage
- **Comprehensive Logging**: Detailed logging system with configurable debug modes
- **Error Handling**: Robust error handling with user-friendly messages
- **Refund Support**: Framework for processing refunds (manual process via BML portal)

#### Admin Experience

- **Enhanced Settings Panel**: Improved admin interface with better organization
- **Webhook URL Display**: Automatic webhook URL generation and display in admin
- **Better Documentation**: Comprehensive inline help and documentation
- **Dependency Checking**: Automatic validation of required components
- **Settings Links**: Quick access to settings from plugins page

#### Security Improvements

- **CSRF Protection**: WordPress nonce verification for all callbacks
- **Signature Verification**: Enhanced transaction signature validation
- **Webhook Security**: Optional HMAC signature verification for webhooks
- **SSL Enforcement**: Proper SSL/TLS validation and warnings
- **Security Headers**: Enhanced security headers for webhook endpoints
- **Input Sanitization**: Comprehensive sanitization of all input data

#### Developer Experience

- **Modern PHP Standards**: PHP 7.4+ compatibility with modern coding practices
- **Composer Integration**: Proper dependency management
- **Code Documentation**: Comprehensive inline documentation
- **Modular Architecture**: Clean separation of concerns
- **Testing Framework**: Foundation for automated testing
- **Translation Ready**: Full internationalization support

### 🔧 Changed

#### API Integration

- **Updated API Calls**: Migrated from legacy API to BML Connect API v2.0
- **Improved Transaction Flow**: Streamlined transaction creation and management
- **Better Error Handling**: More informative error messages and recovery options
- **Enhanced Validation**: Stronger validation of transaction data

#### User Interface

- **Modern Settings Design**: Updated admin interface following WordPress standards
- **Better Payment Flow**: Improved customer payment experience
- **Clear Status Messages**: Enhanced order status communication
- **Responsive Design**: Better mobile compatibility

#### Code Architecture

- **Class Structure**: Complete rewrite with modern OOP principles
- **Separation of Concerns**: Dedicated classes for webhooks, transactions, and admin
- **Performance Optimization**: Reduced database queries and improved caching
- **Memory Management**: Better resource utilization

### 🔄 Improved

#### Payment Processing

- **Faster Payments**: Reduced latency in payment processing
- **Better Error Recovery**: Improved handling of failed transactions
- **Transaction Tracking**: Enhanced transaction history and metadata
- **Status Synchronization**: Real-time order status updates via webhooks

#### Security

- **Data Protection**: Enhanced protection of sensitive payment data
- **Audit Logging**: Comprehensive audit trail for all transactions
- **Access Control**: Better permission handling for admin functions
- **Vulnerability Mitigation**: Protection against common security threats

#### Performance

- **Optimized Database Queries**: Reduced database load
- **Caching Improvements**: Better caching of API responses
- **Resource Management**: Optimized memory and CPU usage
- **Loading Speed**: Faster plugin initialization

### 🐛 Fixed

#### Legacy Issues

- **Global Variable Conflicts**: Eliminated problematic global variable usage
- **Function Naming**: Resolved function naming conflicts
- **Memory Leaks**: Fixed memory management issues
- **Database Queries**: Optimized inefficient database operations

#### Security Vulnerabilities

- **SQL Injection**: Enhanced protection against SQL injection attacks
- **XSS Protection**: Better cross-site scripting prevention
- **CSRF Vulnerabilities**: Added comprehensive CSRF protection
- **Data Sanitization**: Improved input validation and sanitization

#### Compatibility Issues

- **WordPress Compatibility**: Fixed issues with newer WordPress versions
- **WooCommerce Compatibility**: Resolved conflicts with WooCommerce updates
- **PHP Compatibility**: Fixed deprecated PHP function usage
- **Plugin Conflicts**: Resolved conflicts with other popular plugins

### 🗑️ Removed

#### Deprecated Features

- **Legacy Security Options**: Removed outdated security level configurations
- **Old API Methods**: Eliminated deprecated API integration methods
- **Unused Functions**: Cleaned up unused helper functions
- **Obsolete Configuration**: Removed unnecessary configuration options

#### Code Cleanup

- **Dead Code**: Removed unused code paths and variables
- **Commented Code**: Cleaned up commented-out legacy code
- **Debug Code**: Removed hardcoded debug statements
- **Temporary Files**: Eliminated temporary development files

### 📦 Dependencies

#### Updated

- **BML Connect PHP SDK**: Updated to latest v2.1.0
- **Composer Dependencies**: Updated all composer packages
- **WordPress Requirements**: Minimum WordPress 5.0
- **PHP Requirements**: Minimum PHP 7.4

#### Added

- **Webhook Handler**: New dedicated webhook processing class
- **Logger Integration**: Enhanced logging with WooCommerce logger
- **Security Libraries**: Additional security validation libraries

### 🔧 Technical Changes

#### Database

- **Schema Updates**: Improved order metadata structure
- **Index Optimization**: Added database indexes for better performance
- **Data Migration**: Automatic migration from v1.x data format

#### API Changes

- **Endpoint Updates**: Updated to use latest BML Connect endpoints
- **Response Handling**: Improved API response processing
- **Error Mapping**: Better error code mapping and handling

#### Configuration

- **Setting Migration**: Automatic migration of legacy settings
- **New Options**: Added new configuration options for webhooks and security
- **Validation**: Enhanced setting validation and error checking

### 🚨 Breaking Changes

#### Configuration

- **Setting Names**: Some setting field names have changed (automatic migration included)
- **API Credentials**: App ID field replaces API Login field
- **Environment Settings**: Updated environment configuration format

#### API Integration

- **SDK Requirements**: Now requires BML Connect PHP SDK v2.0+
- **Minimum PHP**: PHP 7.4+ now required
- **WordPress Version**: WordPress 5.0+ now required

### 🔄 Migration Guide

#### From v1.x to v2.0

1. **Backup**: Always backup your site before upgrading
2. **Update Credentials**: Verify API credentials in new format
3. **Configure Webhooks**: Set up webhook URL in BML merchant portal
4. **Test Environment**: Test thoroughly in sandbox before production
5. **Monitor Logs**: Check logs after upgrade for any issues

#### Settings Migration

- Plugin automatically migrates most settings
- API Login → App ID (manual verification recommended)
- Security Level → Removed (modern security used automatically)
- New webhook settings need manual configuration

### 📋 Upgrade Notes

#### Required Actions

- [ ] Update API credentials if needed
- [ ] Configure webhook URL in BML merchant portal
- [ ] Test payment flow in sandbox environment
- [ ] Update any custom integrations
- [ ] Review and update security settings

#### Recommended Actions

- [ ] Enable debug mode for initial monitoring
- [ ] Set up webhook secret for enhanced security
- [ ] Review payment method settings
- [ ] Update customer communication templates
- [ ] Monitor logs for first few days

---

## [1.0.0] - Previous Release

### Initial Release

- Basic BML mPOS integration
- Simple transaction processing
- Basic admin configuration
- Legacy API integration

### Known Issues (Fixed in v2.0)

- Security vulnerabilities in signature verification
- No real-time webhook support
- Limited error handling
- Performance issues with large order volumes
- Compatibility issues with newer WordPress/WooCommerce versions

---

## Versioning Strategy

- **Major versions** (X.0.0): Breaking changes, major new features
- **Minor versions** (X.Y.0): New features, non-breaking changes
- **Patch versions** (X.Y.Z): Bug fixes, security updates

## Support

For technical support and questions about this changelog:

- Create an issue on GitHub
- Check the documentation for migration guides
- Contact support for assistance with major version upgrades
