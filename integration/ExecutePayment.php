<?php
/**
 * Execute Payment Handler
 *
 * Handles payment execution after bKash redirects back to the site.
 * Processes payment execution with bKash API and notifies Tutor LMS webhook.
 *
 * @author S. Saif <https://github.com/asmsaiff>
 * @package FinersPay
 * @since 1.0.0
 */

namespace FinersPay;

class ExecutePayment {
    /**
     * Handle payment execution after bKash redirect.
     *
     * Processes the payment execution request from bKash, validates the transaction,
     * and notifies Tutor LMS webhook to update the order status.
     *
     * @since 1.0.0
     */
    public static function finerspay_handle_payment_execution() {
        global $wp_query;

        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }

        // Check if this is the execute_payment route
        if ( ! isset( $wp_query->query_vars['execute_payment'] ) ) {
            return;
        }

        // Sanitize and retrieve GET parameters
        // Note: Nonce verification is not applicable here as this handles external payment gateway redirects from bKash.
        // The data comes from bKash payment gateway callback URL, not from WordPress forms.
        $payment_id = '';
        if ( isset( $_SESSION['bkash_payment_info']['paymentID'] ) && ! empty( $_SESSION['bkash_payment_info']['paymentID'] ) ) {
            $payment_id = sanitize_text_field( $_SESSION['bkash_payment_info']['paymentID'] );
        }
        // Fallback to GET param if session not available
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        if ( empty( $payment_id ) && isset( $_GET['paymentID'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
            $payment_id = sanitize_text_field( wp_unslash( $_GET['paymentID'] ) );
        }

        // Sanitize all GET parameters from external payment gateway callback
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $token       = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $x_app_key   = isset( $_GET['xak'] ) ? sanitize_text_field( wp_unslash( $_GET['xak'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $success_url = isset( $_GET['success_url'] ) ? esc_url_raw( wp_unslash( $_GET['success_url'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $cancel_url  = isset( $_GET['cancel_url'] ) ? esc_url_raw( wp_unslash( $_GET['cancel_url'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $webhook_url = isset( $_GET['webhook_url'] ) ? esc_url_raw( wp_unslash( $_GET['webhook_url'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $order_id    = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External payment gateway callback, nonce not applicable.
        $api_domain  = isset( $_GET['api_domain'] ) ? esc_url_raw( wp_unslash( $_GET['api_domain'] ) ) : '';

        // Validate required parameters
        if ( empty( $payment_id ) || empty( $api_domain ) || empty( $token ) ) {
            if ( ! empty( $cancel_url ) ) {
                wp_safe_redirect( $cancel_url );
                exit;
            }
            return;
        }

        // Request URL for bKash API
        $url = trailingslashit( $api_domain ) . 'tokenized/checkout/execute';

        // Prepare request body
        $request_body = array(
            'paymentID' => $payment_id,
        );

        // Prepare headers
        $headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => $token,
            'X-APP-Key'     => $x_app_key,
        );

        // Prepare request arguments
        $args = array(
            'method'      => 'POST',
            'timeout'     => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => $headers,
            'body'        => wp_json_encode( $request_body ),
            'data_format' => 'body',
            'sslverify'   => true,
        );

        // Make the request to bKash API
        $response = wp_remote_post( $url, $args );

        // Check for errors
        if ( is_wp_error( $response ) ) {
            if ( ! empty( $cancel_url ) ) {
                wp_safe_redirect( $cancel_url );
                exit;
            }
            return;
        }

        // Get and decode response body
        $response_body = wp_remote_retrieve_body( $response );
        $decoded       = json_decode( $response_body, true );

        // Check if payment was successful
        if ( is_array( $decoded ) && isset( $decoded['statusCode'] ) && $decoded['statusCode'] === '0000' ) {
            // Notify Tutor LMS webhook to update order status
            if ( ! empty( $webhook_url ) && ! empty( $order_id ) ) {
                // Do NOT modify webhook URL - Tutor LMS expects the gateway identifier in the path
                // The webhook URL from PaymentUrlsTrait is already in correct format:
                // /wp-json/tutor/v1/ecommerce-webhook/bkash

                // Extract transaction status from bKash execution response
                $transaction_status = isset( $decoded['transactionStatus'] ) ? sanitize_text_field( $decoded['transactionStatus'] ) : '';
                $amount            = isset( $decoded['amount'] ) ? sanitize_text_field( $decoded['amount'] ) : '';

                // Prepare payload matching what Tutor LMS webhook handler expects
                // Include all necessary data from bKash execution response
                $payload = array(
                    'paymentID'         => $payment_id,
                    'order_id'          => strval( $order_id ), // Ensure order_id is string for compatibility
                    'transactionStatus' => $transaction_status,
                    'statusCode'        => $decoded['statusCode'],
                    'amount'            => $amount,
                );

                // Add any additional fields from bKash response that might be needed
                if ( isset( $decoded['trxID'] ) ) {
                    $payload['trxID'] = sanitize_text_field( $decoded['trxID'] );
                }

                // Send webhook notification to Tutor LMS
                wp_remote_post(
                    $webhook_url,
                    array(
                        'method'  => 'POST',
                        'timeout' => 30,
                        'headers' => array(
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ),
                        'body'    => $payload,
                    )
                );
            }

            // Redirect to success URL
            if ( ! empty( $success_url ) ) {
                $redirect_url = add_query_arg(
                    array(
                        'status'    => 'success',
                        'gateway'   => 'bkash',
                        'paymentID' => urlencode( $payment_id ),
                        'order_id'  => $order_id,
                    ),
                    $success_url
                );
                wp_safe_redirect( $redirect_url );
                exit;
            }
        } else {
            // Payment failed - redirect to cancel URL
            if ( ! empty( $cancel_url ) ) {
                wp_safe_redirect( $cancel_url );
                exit;
            }
        }
    }
}
