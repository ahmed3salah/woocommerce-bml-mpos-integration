<?php

/**
 * Woocommerce Payment Gateway Object for BML Connect API v2.0
 * Updated to support latest BML Connect API features and security standards
 */

use BMLConnect\Client;

class WOOCOMMERCE_BML_MPOS_INTEGRATION extends WC_Payment_Gateway
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        // Gateway configuration
        $this->id = "woocommerce_bml_mpos_integration";
        $this->method_title = __("BML Connect Payment", 'woocommerce_bml_mpos_integration');
        $this->method_description = __("BML Connect Payment Gateway for WooCommerce - Secure payments via Bank of Maldives Connect API v2.0", 'woocommerce_bml_mpos_integration');
        $this->title = __("BML Payment", 'woocommerce_bml_mpos_integration');
        $this->has_fields = false;

        // Supported features
        $this->supports = array(
            'products',
            'refunds'
        );

        // Initialize form fields and settings
        $this->init_form_fields();
        $this->init_settings();

        // Load settings into object properties
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // API response handler
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_api_callback'));

        // Webhook handler for real-time notifications
        add_action('woocommerce_api_bml_webhook', array($this, 'handle_webhook'));

        // Admin settings save
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        // Display icon configuration
        $this->setup_icon();
    }

    /**
     * Setup gateway icon
     */
    private function setup_icon()
    {
        if ($this->icon_at_checkout === 'yes') {
            $this->icon = apply_filters(
                'woocommerce_gateway_icon',
                plugin_dir_url(__FILE__) . 'icons/Payment Gateway logos_1.png'
            );
        }
    }

    /**
     * Initialize form fields for admin settings
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce_bml_mpos_integration'),
                'label' => __('Enable BML Connect Payment Gateway', 'woocommerce_bml_mpos_integration'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce_bml_mpos_integration'),
                'type' => 'text',
                'desc_tip' => __('Payment method title shown to customers during checkout.', 'woocommerce_bml_mpos_integration'),
                'default' => __('BML Payment', 'woocommerce_bml_mpos_integration'),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce_bml_mpos_integration'),
                'type' => 'textarea',
                'desc_tip' => __('Payment method description shown to customers.', 'woocommerce_bml_mpos_integration'),
                'default' => __('Pay securely using BML Connect payment gateway.', 'woocommerce_bml_mpos_integration'),
                'css' => 'max-width:450px;'
            ),
            'icon_at_checkout' => array(
                'title' => __('Display Icon', 'woocommerce_bml_mpos_integration'),
                'label' => __('Show BML logo at checkout', 'woocommerce_bml_mpos_integration'),
                'type' => 'checkbox',
                'default' => 'yes',
            ),
            'api_key' => array(
                'title' => __('API Key (Client Secret)', 'woocommerce_bml_mpos_integration'),
                'type' => 'password',
                'desc_tip' => __('Your BML Connect API Key (Client Secret) from the merchant portal.', 'woocommerce_bml_mpos_integration'),
            ),
            'app_id' => array(
                'title' => __('App ID (Client ID)', 'woocommerce_bml_mpos_integration'),
                'type' => 'text',
                'desc_tip' => __('Your BML Connect App ID (Client ID) from the merchant portal.', 'woocommerce_bml_mpos_integration'),
            ),
            'provider' => array(
                'title' => __('Payment Provider', 'woocommerce_bml_mpos_integration'),
                'type' => 'select',
                'options' => array(
                    '' => __('Let customer choose', 'woocommerce_bml_mpos_integration'),
                    'bml_epos' => __('BML ePOS', 'woocommerce_bml_mpos_integration'),
                    'alipay' => __('Alipay', 'woocommerce_bml_mpos_integration'),
                    'card' => __('Credit/Debit Card', 'woocommerce_bml_mpos_integration'),
                ),
                'desc_tip' => __('Choose a specific payment method or let customer select.', 'woocommerce_bml_mpos_integration'),
                'default' => '',
            ),
            'environment' => array(
                'title' => __('Environment', 'woocommerce_bml_mpos_integration'),
                'label' => __('Enable Sandbox Mode', 'woocommerce_bml_mpos_integration'),
                'type' => 'checkbox',
                'description' => __('Use sandbox environment for testing.', 'woocommerce_bml_mpos_integration'),
                'default' => 'yes',
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'woocommerce_bml_mpos_integration'),
                'type' => 'text',
                'desc_tip' => __('Optional webhook secret for additional security. Leave empty if not using webhooks.', 'woocommerce_bml_mpos_integration'),
            ),
            'debug_mode' => array(
                'title' => __('Debug Mode', 'woocommerce_bml_mpos_integration'),
                'label' => __('Enable debug logging', 'woocommerce_bml_mpos_integration'),
                'type' => 'checkbox',
                'description' => __('Log all payment interactions for debugging.', 'woocommerce_bml_mpos_integration'),
                'default' => 'no',
            ),
        );
    }

    /**
     * Process payment when customer clicks Pay button
     */
    public function process_payment($order_id)
    {
        try {
            $customer_order = wc_get_order($order_id);

            if (!$customer_order) {
                throw new Exception('Order not found');
            }

            // Validate order amount
            $amount = intval(round($customer_order->get_total() * 100));
            if ($amount < 100) {
                throw new Exception('Minimum payment amount is 1.00 MVR');
            }

            // Check for existing valid transaction
            $existing_transaction = $this->get_existing_transaction($customer_order, $amount);
            if ($existing_transaction) {
                return array(
                    'result' => 'success',
                    'redirect' => $existing_transaction->url,
                );
            }

            // Create new transaction
            $transaction = $this->create_new_transaction($customer_order, $amount);

            // Save transaction details
            $this->save_transaction_data($customer_order, $transaction);

            return array(
                'result' => 'success',
                'redirect' => $transaction->url,
            );
        } catch (Exception $e) {
            $this->log_error('Payment processing failed: ' . $e->getMessage());
            wc_add_notice(__('Payment failed. Please try again.', 'woocommerce_bml_mpos_integration'), 'error');
            return array('result' => 'failure');
        }
    }

    /**
     * Check for existing valid transaction
     */
    private function get_existing_transaction($order, $amount)
    {
        $transaction_id = $order->get_transaction_id();

        if (empty($transaction_id)) {
            return null;
        }

        try {
            $client = $this->get_api_client();
            $transaction = $client->transactions->get($transaction_id);

            // Verify transaction is valid and reusable
            if (
                $transaction->amount == $amount &&
                $transaction->currency == $order->get_currency() &&
                in_array($transaction->state, ['INITIATED', 'QR_CODE_GENERATED'])
            ) {
                return $transaction;
            }
        } catch (Exception $e) {
            $this->log_error('Failed to fetch existing transaction: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create new BML Connect transaction
     */
    private function create_new_transaction($order, $amount)
    {
        $client = $this->get_api_client();

        // Prepare transaction data according to API v2.0
        $transaction_data = array(
            'amount' => $amount,
            'currency' => $order->get_currency(),
            'localId' => strval($order->get_id()),
            'customerReference' => sprintf('Order #%s', $order->get_order_number()),
            'redirectUrl' => $this->get_callback_url($order->get_id()),
            'signature' => $this->generate_signature($amount, $order->get_currency()),
            'deviceId' => $this->app_id,
            'appVersion' => 'WooCommerce-BML-v2.0',
            'apiVersion' => '2.0',
            'signMethod' => 'sha1'
        );

        // Add provider if specified
        if (!empty($this->provider)) {
            $transaction_data['provider'] = $this->provider;
        }

        return $client->transactions->create($transaction_data);
    }

    /**
     * Save transaction data to order
     */
    private function save_transaction_data($order, $transaction)
    {
        $order->set_transaction_id($transaction->id);

        // Save transaction history
        $existing_transactions = $order->get_meta('_bml_transaction_ids', true);
        $transaction_list = $existing_transactions ? $existing_transactions . ',' . $transaction->id : $transaction->id;
        $order->update_meta_data('_bml_transaction_ids', $transaction_list);

        // Save transaction details for reference
        $order->update_meta_data('_bml_transaction_data', array(
            'id' => $transaction->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'state' => $transaction->state,
            'created' => current_time('mysql')
        ));

        $order->save();

        $this->log_info("Transaction created: {$transaction->id} for order: {$order->get_id()}");
    }

    /**
     * Get API client instance
     */
    private function get_api_client()
    {
        $environment = ($this->environment === 'yes') ? 'sandbox' : 'production';
        return new Client($this->api_key, $this->app_id, $environment);
    }

    /**
     * Generate callback URL for order
     */
    private function get_callback_url($order_id)
    {
        return add_query_arg(array(
            'wc-api' => strtolower(get_class($this)),
            'order_id' => $order_id,
            'security' => wp_create_nonce('bml_callback_' . $order_id)
        ), home_url('/'));
    }

    /**
     * Generate transaction signature
     */
    private function generate_signature($amount, $currency)
    {
        $data = "amount={$amount}&currency={$currency}&apiKey={$this->api_key}";
        return sha1($data);
    }

    /**
     * Handle API callback from BML Connect
     */
    public function handle_api_callback()
    {
        try {
            $this->log_info('API callback received: ' . print_r($_GET, true));

            // Validate callback parameters
            if (!isset($_GET['order_id'], $_GET['security'])) {
                throw new Exception('Missing required callback parameters');
            }

            $order_id = intval($_GET['order_id']);
            $security = sanitize_text_field($_GET['security']);

            // Verify nonce
            if (!wp_verify_nonce($security, 'bml_callback_' . $order_id)) {
                throw new Exception('Security verification failed');
            }

            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            // Process payment result
            $this->process_payment_callback($order, $_GET);
        } catch (Exception $e) {
            $this->log_error('Callback processing failed: ' . $e->getMessage());
            wp_die('Callback processing failed', 'Payment Error', array('response' => 400));
        }
    }

    /**
     * Process payment callback result
     */
    private function process_payment_callback($order, $callback_data)
    {
        // Validate signature if present
        if (isset($callback_data['signature'])) {
            if (!$this->verify_callback_signature($order, $callback_data)) {
                throw new Exception('Signature verification failed');
            }
        }

        $state = isset($callback_data['state']) ? strtolower(sanitize_text_field($callback_data['state'])) : '';
        $transaction_id = isset($callback_data['transactionId']) ? sanitize_text_field($callback_data['transactionId']) : '';

        switch ($state) {
            case 'confirmed':
                $this->handle_payment_confirmed($order, $transaction_id);
                break;

            case 'cancelled':
                $this->handle_payment_cancelled($order);
                break;

            default:
                $this->handle_payment_failed($order, $state);
                break;
        }
    }

    /**
     * Handle confirmed payment
     */
    private function handle_payment_confirmed($order, $transaction_id)
    {
        if ($order->get_status() === 'processing' || $order->get_status() === 'completed') {
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        }

        $order->payment_complete($transaction_id);
        $order->add_order_note(__('Payment confirmed via BML Connect.', 'woocommerce_bml_mpos_integration'));

        WC()->cart->empty_cart();

        $this->log_info("Payment confirmed for order: {$order->get_id()}, transaction: {$transaction_id}");

        wp_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * Handle cancelled payment
     */
    private function handle_payment_cancelled($order)
    {
        $order->add_order_note(__('Payment cancelled by customer.', 'woocommerce_bml_mpos_integration'));
        wp_redirect($order->get_cancel_order_url());
        exit;
    }

    /**
     * Handle failed payment
     */
    private function handle_payment_failed($order, $state)
    {
        $order->add_order_note(sprintf(__('Payment failed with state: %s', 'woocommerce_bml_mpos_integration'), $state));

        if (!in_array($order->get_status(), ['processing', 'completed'])) {
            wp_redirect($order->get_cancel_order_url());
        } else {
            wp_redirect(home_url('/'));
        }
        exit;
    }

    /**
     * Verify callback signature
     */
    private function verify_callback_signature($order, $callback_data)
    {
        if (!isset($callback_data['signature'])) {
            return false;
        }

        $amount = intval(round($order->get_total() * 100));
        $expected_signature = $this->generate_signature($amount, $order->get_currency());

        return hash_equals($expected_signature, $callback_data['signature']);
    }

    /**
     * Handle webhook notifications (real-time payment updates)
     */
    public function handle_webhook()
    {
        try {
            $payload = file_get_contents('php://input');
            $data = json_decode($payload, true);

            if (!$data) {
                throw new Exception('Invalid webhook payload');
            }

            $this->log_info('Webhook received: ' . $payload);

            // Verify webhook signature if secret is configured
            if (!empty($this->webhook_secret)) {
                $this->verify_webhook_signature($payload);
            }

            // Process webhook data
            $this->process_webhook_data($data);

            http_response_code(200);
            echo 'OK';
            exit;
        } catch (Exception $e) {
            $this->log_error('Webhook processing failed: ' . $e->getMessage());
            http_response_code(400);
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Process webhook notification data
     */
    private function process_webhook_data($data)
    {
        if (!isset($data['localId']) || !isset($data['state'])) {
            throw new Exception('Missing required webhook data');
        }

        $order_id = intval($data['localId']);
        $order = wc_get_order($order_id);

        if (!$order) {
            throw new Exception('Order not found: ' . $order_id);
        }

        $state = strtolower($data['state']);
        $transaction_id = isset($data['transactionId']) ? $data['transactionId'] : '';

        // Update order based on webhook state
        switch ($state) {
            case 'confirmed':
                if (!in_array($order->get_status(), ['processing', 'completed'])) {
                    $order->payment_complete($transaction_id);
                    $order->add_order_note(__('Payment confirmed via webhook.', 'woocommerce_bml_mpos_integration'));
                }
                break;

            case 'cancelled':
                if ($order->get_status() === 'pending') {
                    $order->update_status('cancelled', __('Payment cancelled via webhook.', 'woocommerce_bml_mpos_integration'));
                }
                break;

            case 'refunded':
                if (in_array($order->get_status(), ['processing', 'completed'])) {
                    $order->update_status('refunded', __('Payment refunded via webhook.', 'woocommerce_bml_mpos_integration'));
                }
                break;
        }

        $this->log_info("Webhook processed for order: {$order_id}, state: {$state}");
    }

    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload)
    {
        $headers = getallheaders();
        $signature = isset($headers['X-BML-Signature']) ? $headers['X-BML-Signature'] : '';

        if (empty($signature)) {
            throw new Exception('Missing webhook signature');
        }

        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);

        if (!hash_equals($expected_signature, $signature)) {
            throw new Exception('Webhook signature verification failed');
        }
    }

    /**
     * Process refund (if supported by BML Connect API)
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('error', 'Order not found');
        }

        $transaction_id = $order->get_transaction_id();

        if (empty($transaction_id)) {
            return new WP_Error('error', 'No transaction ID found for this order');
        }

        try {
            // Note: Implement refund logic based on BML Connect API capabilities
            // Currently, BML Connect API documentation doesn't show direct refund endpoint
            // This would need to be handled manually through BML merchant portal

            $order->add_order_note(sprintf(
                __('Refund requested: %s %s. Reason: %s. Please process manually through BML merchant portal.', 'woocommerce_bml_mpos_integration'),
                $amount ? wc_price($amount) : wc_price($order->get_total()),
                $order->get_currency(),
                $reason
            ));

            return new WP_Error('error', 'Please process refund manually through BML merchant portal');
        } catch (Exception $e) {
            $this->log_error('Refund failed: ' . $e->getMessage());
            return new WP_Error('error', 'Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Log informational message
     */
    private function log_info($message)
    {
        if ($this->debug_mode === 'yes') {
            $this->log($message, 'info');
        }
    }

    /**
     * Log error message
     */
    private function log_error($message)
    {
        $this->log($message, 'error');
    }

    /**
     * Log message to WooCommerce logs
     */
    private function log($message, $level = 'info')
    {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->log($level, $message, array('source' => 'bml-connect'));
        } else {
            error_log("BML Connect [{$level}]: {$message}");
        }
    }

    /**
     * SSL check for admin notices
     */
    public function do_ssl_check()
    {
        if ($this->enabled === 'yes' && get_option('woocommerce_force_ssl_checkout') === 'no') {
            echo '<div class="error"><p>' . sprintf(
                __('<strong>%s</strong> is enabled but SSL is not enforced. Please ensure you have a valid SSL certificate and <a href="%s">force SSL on checkout pages</a>.', 'woocommerce_bml_mpos_integration'),
                $this->method_title,
                admin_url('admin.php?page=wc-settings&tab=checkout')
            ) . '</p></div>';
        }
    }
}
