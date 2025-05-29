<?php

/*
Plugin Name: BML Connect Payment Gateway for WooCommerce
Plugin URI: https://github.com/ahmed3salah/woocommerce-bml-mpos-integration
Description: Official BML Connect Payment Gateway integration for WooCommerce. Secure payments via Bank of Maldives Connect API v2.0 with real-time webhooks support.
Version: 2.0.0
Author: Ahmed Salah
Author URI: https://github.com/ahmed3salah
Author Email: ahmed3salah311@gmail.com
Requires at least: 5.0
Tested up to: 6.4
WC requires at least: 3.0
WC tested up to: 8.3
Requires PHP: 7.4
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

BML Connect Payment Gateway for WooCommerce
Copyright (C) 2024

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

Disclaimer: This plugin is developed independently and is not officially 
endorsed by Bank of Maldives. Use at your own risk.
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('BML_CONNECT_VERSION', '2.0.0');
define('BML_CONNECT_PLUGIN_FILE', __FILE__);
define('BML_CONNECT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BML_CONNECT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'bml_connect_woocommerce_missing_notice');
    return;
}

/**
 * Display notice when WooCommerce is not active
 */
function bml_connect_woocommerce_missing_notice()
{
    echo '<div class="error"><p>';
    echo __('BML Connect Payment Gateway requires WooCommerce to be installed and active.', 'woocommerce_bml_mpos_integration');
    echo '</p></div>';
}

// Load plugin files
require_once(BML_CONNECT_PLUGIN_PATH . 'vendor/autoload.php');
require_once(BML_CONNECT_PLUGIN_PATH . 'app/wc-bml-mpos-gateway-init.php');
require_once(BML_CONNECT_PLUGIN_PATH . 'app/wc-bml-mpos-gateway-menu-column.php');
require_once(BML_CONNECT_PLUGIN_PATH . 'app/wc-bml-webhook-handler.php');
require_once(BML_CONNECT_PLUGIN_PATH . 'app/functions.php');

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'bml_connect_activate');

function bml_connect_activate()
{
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('BML Connect Payment Gateway requires WordPress 5.0 or higher.', 'woocommerce_bml_mpos_integration'));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('BML Connect Payment Gateway requires PHP 7.4 or higher.', 'woocommerce_bml_mpos_integration'));
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'bml_connect_deactivate');

function bml_connect_deactivate()
{
    // Clear any scheduled events
    wp_clear_scheduled_hook('bml_connect_cleanup');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Load plugin textdomain for translations
 */
add_action('plugins_loaded', 'bml_connect_load_textdomain');

function bml_connect_load_textdomain()
{
    load_plugin_textdomain(
        'woocommerce_bml_mpos_integration',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}

/**
 * Add admin notices for configuration
 */
add_action('admin_notices', 'bml_connect_admin_notices');

function bml_connect_admin_notices()
{
    // Only show on WooCommerce settings pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
        return;
    }

    // Show webhook URL notice on payment settings
    if (
        isset($_GET['tab']) && $_GET['tab'] === 'checkout' &&
        isset($_GET['section']) && $_GET['section'] === 'woocommerce_bml_mpos_integration'
    ) {

        if (class_exists('BML_Webhook_Handler')) {
            BML_Webhook_Handler::display_webhook_url();
        }
    }
}

/**
 * Add settings link to plugins page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bml_connect_plugin_action_links');

function bml_connect_plugin_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=woocommerce_bml_mpos_integration') . '">' .
        __('Settings', 'woocommerce_bml_mpos_integration') . '</a>';

    $docs_link = '<a href="https://github.com/bankofmaldives/bml-connect" target="_blank">' .
        __('Documentation', 'woocommerce_bml_mpos_integration') . '</a>';

    array_unshift($links, $settings_link, $docs_link);

    return $links;
}

/**
 * Check for required dependencies
 */
add_action('admin_init', 'bml_connect_check_dependencies');

function bml_connect_check_dependencies()
{
    // Check if BML Connect PHP SDK is available
    if (!class_exists('BMLConnect\Client')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>';
            echo __('BML Connect Payment Gateway: Required BML Connect PHP SDK not found. Please run "composer install" in the plugin directory.', 'woocommerce_bml_mpos_integration');
            echo '</p></div>';
        });
    }
}

/**
 * Add HPOS compatibility declaration
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Register custom order status for pending BML payments
 */
add_action('init', 'bml_connect_register_order_status');

function bml_connect_register_order_status()
{
    register_post_status('wc-bml-pending', array(
        'label' => __('BML Payment Pending', 'woocommerce_bml_mpos_integration'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('BML Payment Pending <span class="count">(%s)</span>', 'BML Payment Pending <span class="count">(%s)</span>', 'woocommerce_bml_mpos_integration')
    ));
}

/**
 * Add custom order status to WooCommerce order statuses
 */
add_filter('wc_order_statuses', 'bml_connect_add_order_statuses');

function bml_connect_add_order_statuses($order_statuses)
{
    $new_order_statuses = array();

    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;

        if ('wc-pending' === $key) {
            $new_order_statuses['wc-bml-pending'] = __('BML Payment Pending', 'woocommerce_bml_mpos_integration');
        }
    }

    return $new_order_statuses;
}

/**
 * Security headers for webhook endpoints
 */
add_action('init', 'bml_connect_add_security_headers');

function bml_connect_add_security_headers()
{
    if (isset($_GET['wc-api']) && $_GET['wc-api'] === 'bml_webhook') {
        // Add security headers for webhook endpoint
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}
