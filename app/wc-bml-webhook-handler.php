<?php

/**
 * BML Connect Webhook Handler
 * Handles real-time webhook notifications from BML Connect API v2.0
 */

class BML_Webhook_Handler
{
    /**
     * Process incoming webhook
     */
    public static function handle_webhook()
    {
        try {
            // Get raw POST data
            $payload = file_get_contents('php://input');
            $data = json_decode($payload, true);

            if (!$data) {
                throw new Exception('Invalid webhook payload');
            }

            // Log webhook for debugging
            self::log_webhook('Webhook received', $data);

            // Get payment gateway instance
            $gateway = self::get_gateway_instance();

            if (!$gateway) {
                throw new Exception('BML Gateway not found');
            }

            // Verify webhook signature if secret is configured
            if (!empty($gateway->webhook_secret)) {
                self::verify_webhook_signature($payload, $gateway->webhook_secret);
            }

            // Process webhook data
            self::process_webhook_data($data, $gateway);

            // Send success response
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
            exit;
        } catch (Exception $e) {
            self::log_webhook('Webhook error', ['error' => $e->getMessage()]);

            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Get gateway instance
     */
    private static function get_gateway_instance()
    {
        if (!function_exists('WC')) {
            return null;
        }

        $payment_gateways = WC()->payment_gateways->payment_gateways();
        return isset($payment_gateways['woocommerce_bml_mpos_integration'])
            ? $payment_gateways['woocommerce_bml_mpos_integration']
            : null;
    }

    /**
     * Verify webhook signature
     */
    private static function verify_webhook_signature($payload, $webhook_secret)
    {
        $headers = getallheaders();

        // Check different possible header names
        $signature = null;
        foreach (['X-BML-Signature', 'X-Signature', 'Signature'] as $header) {
            if (isset($headers[$header])) {
                $signature = $headers[$header];
                break;
            }
        }

        if (empty($signature)) {
            throw new Exception('Missing webhook signature header');
        }

        $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);

        if (!hash_equals($expected_signature, $signature)) {
            throw new Exception('Webhook signature verification failed');
        }
    }

    /**
     * Process webhook data
     */
    private static function process_webhook_data($data, $gateway)
    {
        // Validate required fields
        if (!isset($data['localId']) || !isset($data['state'])) {
            throw new Exception('Missing required webhook fields: localId or state');
        }

        $order_id = intval($data['localId']);
        $state = strtolower(sanitize_text_field($data['state']));
        $transaction_id = isset($data['transactionId']) ? sanitize_text_field($data['transactionId']) : '';

        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Order not found: ' . $order_id);
        }

        // Verify webhook data integrity
        if (!self::verify_webhook_data($data, $order, $gateway)) {
            throw new Exception('Webhook data verification failed');
        }

        // Process based on state
        switch ($state) {
            case 'qr_code_generated':
                self::handle_qr_generated($order, $data);
                break;

            case 'confirmed':
                self::handle_payment_confirmed($order, $transaction_id, $data);
                break;

            case 'cancelled':
                self::handle_payment_cancelled($order, $data);
                break;

            case 'refund_requested':
                self::handle_refund_requested($order, $data);
                break;

            case 'refunded':
                self::handle_payment_refunded($order, $data);
                break;

            default:
                self::log_webhook('Unknown webhook state', ['state' => $state, 'order_id' => $order_id]);
                break;
        }

        self::log_webhook('Webhook processed successfully', [
            'order_id' => $order_id,
            'state' => $state,
            'transaction_id' => $transaction_id
        ]);
    }

    /**
     * Verify webhook data integrity
     */
    private static function verify_webhook_data($data, $order, $gateway)
    {
        // Verify signature if present
        if (isset($data['signature'])) {
            $amount = isset($data['amount']) ? intval($data['amount']) : intval(round($order->get_total() * 100));
            $currency = isset($data['currency']) ? $data['currency'] : $order->get_currency();

            $expected_signature = sha1("amount={$amount}&currency={$currency}&apiKey={$gateway->api_key}");

            if (!hash_equals($expected_signature, $data['signature'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handle QR code generated
     */
    private static function handle_qr_generated($order, $data)
    {
        $order->add_order_note(__('QR code generated for payment.', 'woocommerce_bml_mpos_integration'));

        // Save QR code URL if provided
        if (isset($data['qrCode']['url'])) {
            $order->update_meta_data('_bml_qr_code_url', sanitize_url($data['qrCode']['url']));
            $order->save();
        }
    }

    /**
     * Handle payment confirmed
     */
    private static function handle_payment_confirmed($order, $transaction_id, $data)
    {
        // Don't process if already completed
        if (in_array($order->get_status(), ['processing', 'completed'])) {
            return;
        }

        // Complete payment
        $order->payment_complete($transaction_id);
        $order->add_order_note(__('Payment confirmed via BML Connect webhook.', 'woocommerce_bml_mpos_integration'));

        // Update transaction metadata
        $order->update_meta_data('_bml_webhook_confirmed', current_time('mysql'));
        if (isset($data['provider'])) {
            $order->update_meta_data('_bml_payment_provider', sanitize_text_field($data['provider']));
        }
        $order->save();

        // Clear cart if customer is logged in and this is their order
        if (is_user_logged_in() && $order->get_user_id() == get_current_user_id()) {
            WC()->cart->empty_cart();
        }
    }

    /**
     * Handle payment cancelled
     */
    private static function handle_payment_cancelled($order, $data)
    {
        if ($order->get_status() === 'pending') {
            $order->update_status('cancelled', __('Payment cancelled via BML Connect webhook.', 'woocommerce_bml_mpos_integration'));
            $order->update_meta_data('_bml_webhook_cancelled', current_time('mysql'));
            $order->save();
        }
    }

    /**
     * Handle refund requested
     */
    private static function handle_refund_requested($order, $data)
    {
        $amount = isset($data['amount']) ? ($data['amount'] / 100) : $order->get_total();

        $order->add_order_note(sprintf(
            __('Refund requested via BML Connect: %s %s', 'woocommerce_bml_mpos_integration'),
            wc_price($amount),
            $order->get_currency()
        ));

        $order->update_meta_data('_bml_refund_requested', current_time('mysql'));
        $order->save();
    }

    /**
     * Handle payment refunded
     */
    private static function handle_payment_refunded($order, $data)
    {
        if (!in_array($order->get_status(), ['processing', 'completed'])) {
            return;
        }

        $amount = isset($data['amount']) ? ($data['amount'] / 100) : $order->get_total();

        // Update order status
        $order->update_status('refunded', sprintf(
            __('Payment refunded via BML Connect webhook: %s %s', 'woocommerce_bml_mpos_integration'),
            wc_price($amount),
            $order->get_currency()
        ));

        $order->update_meta_data('_bml_webhook_refunded', current_time('mysql'));
        $order->save();
    }

    /**
     * Log webhook events
     */
    private static function log_webhook($message, $data = [])
    {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $context = array('source' => 'bml-webhook');

            if (!empty($data)) {
                $message .= ' | Data: ' . wp_json_encode($data);
            }

            $logger->info($message, $context);
        } else {
            $log_message = "BML Webhook: {$message}";
            if (!empty($data)) {
                $log_message .= ' | ' . print_r($data, true);
            }
            error_log($log_message);
        }
    }

    /**
     * Get webhook URL for configuration
     */
    public static function get_webhook_url()
    {
        return add_query_arg('wc-api', 'bml_webhook', home_url('/'));
    }

    /**
     * Display webhook URL in admin
     */
    public static function display_webhook_url()
    {
        $webhook_url = self::get_webhook_url();

        echo '<div class="notice notice-info">';
        echo '<p><strong>' . __('BML Connect Webhook URL:', 'woocommerce_bml_mpos_integration') . '</strong></p>';
        echo '<p><code>' . esc_url($webhook_url) . '</code></p>';
        echo '<p>' . __('Configure this URL in your BML merchant portal to receive real-time payment notifications.', 'woocommerce_bml_mpos_integration') . '</p>';
        echo '</div>';
    }
}

// Register webhook handler
add_action('woocommerce_api_bml_webhook', array('BML_Webhook_Handler', 'handle_webhook'));
