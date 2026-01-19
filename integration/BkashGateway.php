<?php
/**
 * bKash Payment Gateway Integration
 *
 * Concrete implementation of the bKash payment gateway for Tutor LMS.
 * This class provides the necessary integration points for bKash payment processing
 * within the Tutor e-commerce ecosystem.
 *
 * @author S. Saif<https://github.com/asmsaiff>
 * @since 1.0.1
 */

namespace FinersPay;

use Payments\Bkash\Bkash;
use Tutor\PaymentGateways\GatewayBase;

/**
 * bKash Payment Gateway Class
 *
 * This class extends GatewayBase to provide bKash payment gateway functionality
 * for Tutor LMS. It defines the gateway's directory structure, payment class, and
 * configuration class for seamless integration with the Tutor payment system.
 *
 * @since 1.0.1
 */
class BkashGateway extends GatewayBase {

	/**
	 * Get the root directory name for the bKash payment gateway source files.
	 *
	 * This method returns the directory name where bKash payment gateway
	 * source files are located within the payments directory structure.
	 *
	 * @since 1.0.1
	 *
	 * @return string The directory name ('Bkash').
	 */
	public function get_root_dir_name(): string {
		return 'Bkash';
	}

	/**
	 * Get the payment class name for bKash integration.
	 *
	 * Returns the fully qualified class name of the bKash payment processor
	 * from the PaymentHub library, used for handling payment transactions.
	 *
	 * @since 1.0.1
	 *
	 * @return string The bKash payment class name.
	 */
	public function get_payment_class(): string {
		return Bkash::class;
	}

	/**
	 * Get the configuration class name for bKash gateway.
	 *
	 * Returns the fully qualified class name of the bKash configuration class
	 * that manages gateway settings, credentials, and environment configuration.
	 *
	 * @since 1.0.1
	 *
	 * @return string The bKash configuration class name.
	 */
	public function get_config_class(): string {
		return BkashConfig::class;
	}

	/**
	 * Get the autoload file path for the bKash payment gateway.
	 *
	 * Returns an empty string as bKash uses Composer autoloading
	 * and doesn't require a custom autoload file.
	 *
	 * @since 1.0.1
	 *
	 * @return string Empty string (Composer autoloading is used).
	 */
	public static function get_autoload_file(): string {
		return '';
	}
}
