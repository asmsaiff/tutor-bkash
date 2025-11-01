<?php
/**
 * Init class
 *
 * @author Developer
 * @since 1.0.0
 */

namespace TutorBkash;

/**
 * Init class
 *
 * This class initializes the bKash Payment Gateway by registering hooks and filters for integrating with Tutor's payment
 * system. It adds the bKash method to Tutor's list of payment gateways.
 */
final class Init {
    /**
     * bKash gateway configuration array
     *
     * @since 1.0.0
     */
    private const BKASH_GATEWAY_CONFIG = [
        'bkash' => [
            'gateway_class' => BkashGateway::class,
            'config_class' => BkashConfig::class,
        ],
    ];

    /**
     * Constructor - Register hooks and filters
     *
     * Registers WordPress filters to integrate bKash payment gateway with Tutor LMS:
     * - tutor_gateways_with_class: Adds gateway class references for webhook processing
     * - tutor_payment_gateways_with_class: Adds gateway to checkout integration
     * - tutor_payment_gateways: Adds payment method settings to Tutor admin
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter('tutor_gateways_with_class', [self::class,'payment_gateways_with_ref'], 10, 2);
        add_filter('tutor_payment_gateways_with_class', [self::class,'add_payment_gateways']);
        add_filter('tutor_payment_gateways', [$this, 'add_tutor_bkash_payment_method'], 100);
    }

    /**
     * Add bKash gateway class references for webhook processing
     *
     * Used by the tutor_gateways_with_class filter to provide class references
     * for bKash gateway when processing webhook notifications.
     *
     * @since 1.0.0
     *
     * @param array  $value   Existing gateway class references array.
     * @param string $gateway Gateway identifier being requested.
     *
     * @return array Modified gateway class references array.
     */
    public static function payment_gateways_with_ref(array $value, string $gateway): array {
        if (isset(self::BKASH_GATEWAY_CONFIG[$gateway])) {
            $value[$gateway] = self::BKASH_GATEWAY_CONFIG[$gateway];
        }

        return $value;
    }

    /**
     * Add bKash payment gateway to checkout integration
     *
     * Used by the tutor_payment_gateways_with_class filter to register
     * bKash gateway classes for checkout processing.
     *
     * @since 1.0.0
     *
     * @param array $gateways Existing payment gateways array.
     *
     * @return array Modified payment gateways array with bKash added.
     */
    public static function add_payment_gateways(array $gateways): array {
        return $gateways + self::BKASH_GATEWAY_CONFIG;
    }

    /**
     * Add bKash payment method configuration to Tutor settings
     *
     * Defines the complete configuration structure for bKash payment method
     * including all required fields (environment, credentials, webhook URL)
     * and adds it to Tutor's payment methods list for admin configuration.
     *
     * @since 1.0.0
     *
     * @param array $methods Existing Tutor payment methods array.
     *
     * @return array Modified payment methods array with bKash configuration added.
     */
    public function add_tutor_bkash_payment_method(array $methods): array {
        $bkash_payment_method = [
            'name' => 'bkash',
            'label' => esc_html__('bKash', 'tutor-bkash'),
            'is_installed' => true,
            'is_active' => true,
            'icon' => TUTOR_BKASH_URL . 'assets/bkash-logo.png',
            'support_subscription' => true,
            'fields' => [
                    [
                        'name' => 'environment',
                        'type' => 'select',
                        'label' => esc_html__('Environment', 'tutor-bkash'),
                        'options' => [
                            'sandbox' => esc_html__('Sandbox', 'tutor-bkash'),
                            'live' => esc_html__('Live', 'tutor-bkash'),
                        ],
                        'value' => 'sandbox',
                    ],
                    [
                        'name' => 'username',
                        'type' => 'text',
                        'label' => esc_html__('Merchant Username', 'tutor-bkash'),
                        'value' => '',
                        'desc' => esc_html__('Your bKash merchant username', 'tutor-bkash'),
                    ],
                    [
                        'name' => 'password',
                        'type' => 'secret_key',
                        'label' => esc_html__('Merchant Password', 'tutor-bkash'),
                        'value' => '',
                        'desc' => esc_html__('Your bKash merchant password', 'tutor-bkash'),
                    ],
                    [
                        'name' => 'app_key',
                        'type' => 'text',
                        'label' => esc_html__('App Key', 'tutor-bkash'),
                        'value' => '',
                        'desc' => esc_html__('Your bKash App Key', 'tutor-bkash'),
                    ],
                    [
                        'name' => 'app_secret',
                        'type' => 'secret_key',
                        'label' => esc_html__('App Secret', 'tutor-bkash'),
                        'value' => '',
                        'desc' => esc_html__('Your bKash App Secret', 'tutor-bkash'),
                    ],
                    [
                        'name' => 'webhook_url',
                        'type' => 'webhook_url',
                        'label' => esc_html__('Webhook URL', 'tutor-bkash'),
                        'value' => '',
                        'desc' => esc_html__('Copy this URL and configure it in your bKash merchant panel', 'tutor-bkash'),
                    ],
                ],
        ];

        $methods[] = $bkash_payment_method;
        return $methods;
    }

}
