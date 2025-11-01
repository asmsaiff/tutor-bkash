<?php
/**
 * bKash Payment Gateway Implementation
 *
 * Concrete implementation of the bKash Tokenized payment gateway for Tutor LMS.
 * This class handles the complete payment flow including payment creation,
 * transaction validation, and webhook processing for bKash integration.
 *
 * Features:
 * - Secure payment processing with bKash Tokenized API
 * - Transaction validation and authentication
 * - IPN (Instant Payment Notification) handling
 * - Support for sandbox and live environments
 * - Comprehensive error handling and logging
 *
 * @author S. Saif<https://github.com/asmsaiff>
 * @since 1.0.0
 */

namespace Payments\Bkash;

use Throwable;
use ErrorException;
use Ollyo\PaymentHub\Core\Support\Arr;
use Ollyo\PaymentHub\Core\Support\System;
use GuzzleHttp\Exception\RequestException;
use Ollyo\PaymentHub\Core\Payment\BasePayment;

/**
 * bKash Payment Gateway Class
 *
 * This class extends BasePayment to provide bKash Tokenized payment gateway functionality.
 * It implements the complete payment lifecycle from initiation to completion,
 * including validation and webhook processing.
 *
 * @since 1.0.0
 */
class Bkash extends BasePayment {
	/**
	 * bKash API endpoints and configuration constants
	 */
	private const API_TOKEN_ENDPOINT = '/tokenized/checkout/token/grant';
	private const API_PAYMENT_CREATE_ENDPOINT = '/tokenized/checkout/create';
	private const API_PAYMENT_EXECUTE_ENDPOINT = '/tokenized/checkout/execute';
	private const API_PAYMENT_QUERY_ENDPOINT = '/tokenized/checkout/payment/status';

	private const DEFAULT_CURRENCY = 'BDT';
	private const TRANSACTION_PREFIX = 'TUTOR-';
	private const INTENT = 'sale';

	/**
	 * Payment status mapping constants
	 */
	private const STATUS_MAP = [
		'completed' => 'paid',
		'success' => 'paid',
		'succeeded' => 'paid',
		'failed' => 'failed',
		'cancelled' => 'cancelled',
		'pending' => 'pending',
	];

	/**
	 * Stores the bKash API client configuration
	 *
	 * @var array
	 */
	protected $client;

	/**
	 * Stores the access token
	 *
	 * @var string
	 */
	private $accessToken = '';

	/**
	 * Checks if all required configuration keys are present and not empty.
	 *
	 * Validates that the essential bKash configuration parameters
	 * (username, password, app_key, app_secret, mode) are properly configured before
	 * allowing payment processing.
	 *
	 * @return bool Returns true if all required configuration keys are present and not empty, otherwise false.
	 */
	public function check(): bool {
		$configKeys = Arr::make(['username', 'password', 'app_key', 'app_secret', 'mode']);

		$isConfigOk = $configKeys->every(function ($key) {
			return $this->config->has($key) && !empty($this->config->get($key));
		});

		return $isConfigOk;
	}

	/**
	 * Initializes the necessary configurations for the bKash payment gateway.
	 *
	 * Sets up the client configuration array with credentials and API domain
	 * required for bKash API communication. This method must be called before
	 * any payment processing operations.
	 *
	 * @throws Throwable If configuration retrieval fails or invalid data is provided.
	 */
	public function setup(): void {
		try {
			$this->client = [
				'username' => $this->config->get('username'),
				'password' => $this->config->get('password'),
				'app_key' => $this->config->get('app_key'),
				'app_secret' => $this->config->get('app_secret'),
				'api_domain' => $this->config->get('api_domain'),
			];
		} catch (Throwable $error) {
			throw $error;
		}
	}

	/**
	 * Sets the payment data according to bKash requirements.
	 *
	 * Processes and structures the payment data from Tutor LMS into the format
	 * expected by the bKash API. This includes generating transaction IDs,
	 * formatting amounts, and organizing customer and product information.
	 *
	 * @param  object $data The payment data object from Tutor LMS.
	 * @throws Throwable If the parent `setData` method throws an error or data processing fails.
	 */
	public function setData($data): void {
		try {
			// Structure the payment data according to bKash requirements
			$structuredData = $this->prepareData($data);
			parent::setData($structuredData);
		} catch (Throwable $error) {
			throw $error;
		}
	}

	/**
	 * Prepares the payment data according to bKash API requirements.
	 *
	 * @param object $data Payment data from Tutor
	 * @return array Formatted data for bKash
	 */
	private function prepareData(object $data): array {
		// Validate required data
		if (!isset($data->order_id) || empty($data->order_id)) {
			throw new \InvalidArgumentException(esc_html__('Order ID is required for payment processing', 'tutor-bkash'));
		}

		if (!isset($data->currency) || !isset($data->currency->code)) {
			throw new \InvalidArgumentException(esc_html__('Currency information is required for payment processing', 'tutor-bkash'));
		}

		if (!isset($data->customer) || !isset($data->customer->email)) {
			throw new \InvalidArgumentException(esc_html__('Customer email is required for payment processing', 'tutor-bkash'));
		}

		// Generate unique transaction ID
		$tran_id = self::TRANSACTION_PREFIX . $data->order_id . '-' . time();

		// Get total price - Tutor uses 'total_price' property
		$total_price = isset($data->total_price) && !empty($data->total_price) ? (float) $data->total_price : 0;

		// Validate amount
		if ($total_price <= 0) {
			throw new \InvalidArgumentException(esc_html__('Payment amount must be greater than zero', 'tutor-bkash'));
		}

		// Format amounts for bKash
		$total_amount = number_format($total_price, 2, '.', '');

		// Prepare bKash required fields
		$bkashData = [
			// Required transaction information
			'amount' => $total_amount,
			'currency' => $data->currency->code,
			'invoiceID' => $tran_id,
			'intent' => self::INTENT,

			// URLs
			'success_url' => $this->config->get('success_url'),
			'fail_url' => $this->config->get('cancel_url'),

			// Customer information
			'customer' => [
				'name' => $data->customer->name ?? esc_html__('Customer', 'tutor-bkash'),
				'email' => $data->customer->email,
				'phone' => $data->customer->phone_number ?? '',
			],

			// Additional information
			'order_id' => $data->order_id,
		];

		return $bkashData;
	}

	/**
	 * Creates the payment process by sending data to bKash gateway.
	 *
	 * @throws ErrorException
	 */
	public function createPayment(): void {
		try {
			$paymentData = $this->getData();

			// First, get grant token
			$this->accessToken = $this->getGrantToken();

			// if (!$this->accessToken) {
			// 	throw new ErrorException(esc_html__('Failed to obtain bKash access token', 'tutor-bkash'));
			// }

			// Make API call to bKash to create payment
			$apiUrl = $this->client['api_domain'] . self::API_PAYMENT_CREATE_ENDPOINT;

			// Build request payload matching bKash expected keys
			$base_url = get_home_url();
			$_bkash_app_key = $this->client['app_key'] ?? $this->config->get('app_key');
			// Determine payerReference: site title in live, sandbox wallet otherwise
			$payerReference = ($this->config->get('mode') === 'live') ? get_bloginfo('name') : ($this->config->get('sandbox_wallet') ?? 'sandbox');
			$merchantAssociationInfo = $this->config->get('merchant_association_info') ?? 'MI05MID54RF09123456One';

			$request_data = [
				'mode' => $this->config->get('mode') === 'live' ? '0011' : '0010',
				'payerReference' => $payerReference,
				// Use a query parameter based callback so we don't need a custom rewrite rule.
				// bKash will append its own params (status, paymentID) to this URL.
				'callbackURL' => $base_url
                    . '/execute-payment?token=' . $this->accessToken
                    . '&xak=' . $_bkash_app_key
                    . '&base_url=' . $base_url
                    . '&api_domain=' . urlencode($this->client['api_domain'])
                    . '&success_url=' . urlencode($this->config->get('success_url'))
                    . '&cancel_url=' . urlencode($this->config->get('cancel_url'))
                    . '&webhook_url=' . urlencode($this->config->get('webhook_url'))
                    . '&order_id=' . urlencode(strval($paymentData['order_id'] ?? $paymentData['invoiceID'] ?? '')),
				'merchantAssociationInfo' => $merchantAssociationInfo,
				'amount' => strval($paymentData['amount']),
				'currency' => $paymentData['currency'] ?? self::DEFAULT_CURRENCY,
				'intent' => $paymentData['intent'] ?? self::INTENT,
				'merchantInvoiceNumber' => strval($paymentData['order_id'] ?? $paymentData['invoiceID'] ?? ''),
			];

			$response = $this->callBkashApi($apiUrl, $request_data);

            session_start();
            $_SESSION["bkash_payment_info"] = $response;

			if ($response && isset($response['statusCode']) && $response['statusCode'] === '0000') {
				if (isset($response['paymentID']) && !empty($response['bkashURL'])) {
					// Redirect to bKash payment page
					header("Location: " . $response['bkashURL']);
					exit;
				} else {
					throw new ErrorException(esc_html__('Payment URL not found in response', 'tutor-bkash'));
				}
			} else {
				$errorMessage = $response['statusMessage'] ?? esc_html__('Unknown error occurred', 'tutor-bkash');
				throw new ErrorException(esc_html__('bKash Payment Failed: ', 'tutor-bkash') . $errorMessage);
			}

		} catch (RequestException $error) {
			throw new ErrorException(esc_html($error->getMessage()));
		}
	}

	/**
	 * Gets the grant token from bKash API
	 *
	 * @return string|null Access token or null on failure
	 */
	private function getGrantToken(): ?string {
		$apiUrl = $this->client['api_domain'] . self::API_TOKEN_ENDPOINT;

		// Base64 encode credentials
		$credentials = base64_encode($this->client['username'] . ':' . $this->client['password']);
		$basicAuth = base64_encode($this->client['app_key'] . ':' . $this->client['app_secret']);

        $response = $this->callBkashApi($apiUrl, [
            'app_key' => $this->client['app_key'],
            'app_secret' => $this->client['app_secret'],
        ], [
            // Per bKash docs: Authorization must be Basic base64(app_key:app_secret)
            'Authorization' => 'Basic ' . $basicAuth,
            'username' => $this->client['username'],
            'password' => $this->client['password'],
            'Content-Type' => 'application/json',
        ]);

		if ($response && isset($response['id_token'])) {
			return $response['id_token'];
		}

		return null;
	}

	/**
	 * Makes a request to bKash API using WordPress HTTP API
	 *
	 * @param string $url API endpoint
	 * @param array $data Post data
	 * @param array $headers Additional headers
	 * @return array Response data
	 */
	private function callBkashApi(string $url, array $data, array $headers = []): array {
		// Set SSL verification based on environment
		$isLocalhost = $this->config->get('mode') === 'sandbox';
		$ssl_verify = true; // Always verify SSL

		// Prepare arguments for wp_remote_post
		$defaultHeaders = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			// Send X-APP-Key as a proper header key (value is app_key)
			'X-APP-Key' => $this->client['app_key'] ?? $this->config->get('app_key'),
		];

		if (!empty($this->accessToken)) {
			// accessToken should be sent as a Bearer token
			$defaultHeaders['Authorization'] = 'Bearer ' . $this->accessToken;
		}

		$headers = array_merge($defaultHeaders, $headers);


		$args = [
			'method' => 'POST',
			'timeout' => 60,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => wp_json_encode($data),
			'sslverify' => $ssl_verify,
		];

		// Make the request
		$response = wp_remote_post($url, $args);

		// Get response code and body
		$http_code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return $decoded;
	}

	/**
	 * Verifies and processes the order data received from bKash.
	 *
	 * @param  object $payload Webhook payload
	 * @return object Order data
	 * @throws Throwable
	 */
	public function verifyAndCreateOrderData(object $payload): object {
		$returnData = System::defaultOrderData();

		try {
			// Get POST data from bKash callback
			$post_data = $payload->post;

			// Validate that we have POST data
			if (empty($post_data) || !is_array($post_data)) {
				$returnData->payment_status = 'failed';
				$returnData->payment_error_reason = esc_html__('No transaction data received. IPN endpoint should only receive POST requests from bKash.', 'tutor-bkash');
				return $returnData;
			}

			if (empty($post_data['paymentID'])) {
				$returnData->payment_status = 'failed';
				$returnData->payment_error_reason = esc_html__('Invalid transaction data: Missing payment ID.', 'tutor-bkash');
				return $returnData;
			}

			// Sanitize payment ID
			$paymentID = sanitize_text_field($post_data['paymentID']);

			// Sanitize order ID if present
			$order_id = isset($post_data['order_id']) ? absint($post_data['order_id']) : 0;

			// Query payment status from bKash to verify transaction
			$paymentStatus = $this->queryPayment($paymentID);

			if ($paymentStatus && isset($paymentStatus['statusCode']) && $paymentStatus['statusCode'] === '0000') {
				// Use transactionStatus from query response (most reliable)
				$transaction = isset($paymentStatus['transactionStatus']) ? sanitize_text_field($paymentStatus['transactionStatus']) : '';

				// If transactionStatus is in payload and query response doesn't have it, use payload value
				if (empty($transaction) && isset($post_data['transactionStatus'])) {
					$transaction = sanitize_text_field($post_data['transactionStatus']);
				}

				// Map bKash status to Tutor status
				$payment_status = $this->mapPaymentStatus($transaction);

				$returnData->id = $order_id;
				$returnData->payment_status = $payment_status;
				$returnData->transaction_id = isset($paymentStatus['paymentID']) ? sanitize_text_field($paymentStatus['paymentID']) : $paymentID;
				$returnData->payment_payload = wp_json_encode($paymentStatus);
				$returnData->payment_error_reason = $payment_status === 'paid' ? '' : esc_html__('Payment failed', 'tutor-bkash');

				// Calculate fees and earnings - use amount from query response or payload
				$amount = 0.0;
				if (isset($paymentStatus['amount'])) {
					$amount = floatval($paymentStatus['amount']);
				} elseif (isset($post_data['amount'])) {
					$amount = floatval($post_data['amount']);
				}
				$returnData->fees = 0; // bKash fees would be handled separately
				$returnData->earnings = number_format($amount, 2, '.', '');
				$returnData->tax_amount = 0;

			} else {
				// Query failed - check if we have statusCode in payload as fallback
				if (isset($post_data['statusCode']) && $post_data['statusCode'] === '0000' && isset($post_data['transactionStatus'])) {
					// Use payload data as fallback if query fails but payload indicates success
					$transaction = sanitize_text_field($post_data['transactionStatus']);
					$payment_status = $this->mapPaymentStatus($transaction);

					$returnData->id = $order_id;
					$returnData->payment_status = $payment_status;
					$returnData->transaction_id = $paymentID;
					$returnData->payment_payload = wp_json_encode($post_data);
					$returnData->payment_error_reason = $payment_status === 'paid' ? '' : esc_html__('Payment verification pending', 'tutor-bkash');

					$amount = isset($post_data['amount']) ? floatval($post_data['amount']) : 0.0;
					$returnData->fees = 0;
					$returnData->earnings = number_format($amount, 2, '.', '');
					$returnData->tax_amount = 0;
				} else {
					// Query failed and no valid payload fallback
					$returnData->payment_status = 'failed';
					$returnData->payment_error_reason = esc_html__('Transaction validation with bKash API failed.', 'tutor-bkash');
				}
			}

			return $returnData;

		} catch (Throwable $error) {
			// Return failed status instead of throwing
			$returnData->payment_status = 'failed';
			$error_message = $error->getMessage();
			$returnData->payment_error_reason = esc_html__('Error processing payment: ', 'tutor-bkash') . esc_html($error_message);
			return $returnData;
		}
	}

    /**
     * Execute a created payment (called after bKash redirects back with paymentID).
     *
     * @param string $paymentID
     * @return array
     */
    public function executePayment(string $paymentID): array {
        // Ensure we have a grant token
        $this->accessToken = $this->getGrantToken();

        if (empty($this->accessToken)) {
            return ['statusCode' => '9999', 'statusMessage' => 'Failed to obtain access token'];
        }

        $apiUrl = $this->client['api_domain'] . self::API_PAYMENT_EXECUTE_ENDPOINT;

        $payload = [
            'paymentID' => $paymentID,
        ];

        $response = $this->callBkashApi($apiUrl, $payload);

        return is_array($response) ? $response : ['statusCode' => '9999', 'statusMessage' => 'Invalid response'];
    }

	/**
	 * Queries payment status from bKash API
	 *
	 * @param string $paymentID Payment ID
	 * @return array|null Payment status or null on failure
	 */
	private function queryPayment(string $paymentID): ?array {
		// Get grant token
		$this->accessToken = $this->getGrantToken();

		if (empty($this->accessToken)) {
			return null;
		}

		$apiUrl = $this->client['api_domain'] . self::API_PAYMENT_QUERY_ENDPOINT;

		$response = $this->callBkashApi($apiUrl, [
			'paymentID' => $paymentID,
		]);

		return $response;
	}

	/**
	 * Maps bKash payment status to Tutor payment status
	 *
	 * @param string $bkashStatus
	 * @return string
	 */
	private function mapPaymentStatus(string $bkashStatus): string {
		return self::STATUS_MAP[strtolower($bkashStatus)] ?? 'failed';
	}
}
