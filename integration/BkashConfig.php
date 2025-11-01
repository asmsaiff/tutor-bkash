<?php
/**
 * bKash Configuration class
 *
 * @author Developer
 * @since 1.0.0
 */

namespace TutorBkash;

use Tutor\Ecommerce\Settings;
use Ollyo\PaymentHub\Core\Payment\BaseConfig;
use Tutor\PaymentGateways\Configs\PaymentUrlsTrait;
use Ollyo\PaymentHub\Contracts\Payment\ConfigContract;

/**
 * BkashConfig class.
 *
 * This class is used to manage the configuration settings for the "bKash" gateway. It extends the `BaseConfig`
 * class and implements the `ConfigContract` interface.
 *
 * @since 1.0.0
 */
class BkashConfig extends BaseConfig implements ConfigContract {

	/**
	 * Configuration keys and their types for bKash gateway
	 *
	 * @since 1.0.0
	 */
	private const CONFIG_KEYS = [
		'environment' => 'select',
		'username' => 'text',
		'password' => 'secret_key',
		'app_key' => 'text',
		'app_secret' => 'secret_key',
	];

	/**
	 * This trait provides methods to retrieve the URLs used in the payment process for success, cancellation, and webhook
	 * notifications.
	 */
	use PaymentUrlsTrait;

	/**
	 * Stores the environment setting for the payment gateway, such as 'sandbox' or 'live'.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $environment;

	/**
	 * Stores the bKash Username.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Stores the bKash Password.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $password;

	/**
	 * Stores the bKash App Key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $app_key;

	/**
	 * Stores the bKash App Secret.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $app_secret;

	/**
	 * The name of the payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name = 'bkash';

	/**
	 * Constructor.
	 *
	 * Initializes the bKash configuration by loading gateway settings from Tutor's
	 * payment gateway settings and populating the corresponding properties.
	 * Excludes webhook_url as it's handled separately by the PaymentUrlsTrait.
	 * Handles new installations gracefully by using empty array if settings don't exist yet.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$settings = Settings::get_payment_gateway_settings('bkash');

		// Handle new installations where settings might be empty
		if (!is_array($settings)) {
			$settings = [];
		}

		$config_keys = array_keys(self::CONFIG_KEYS);

		foreach ($config_keys as $key) {
			if ('webhook_url' !== $key) {
				$this->$key = $this->get_field_value($settings, $key);
			}
		}
	}

	/**
	 * Retrieves the mode of the bKash payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The mode of the payment gateway ('sandbox' or 'live').
	 */
	public function getMode(): string {
		return $this->environment;
	}

	/**
	 * Retrieves the Username for the bKash payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured Username.
	 */
	public function getUsername(): string {
		return $this->username;
	}

	/**
	 * Retrieves the Password for the bKash payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured Password.
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * Retrieves the App Key for the bKash payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured App Key.
	 */
	public function getAppKey(): string {
		return $this->app_key;
	}

	/**
	 * Retrieves the App Secret for the bKash payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured App Secret.
	 */
	public function getAppSecret(): string {
		return $this->app_secret;
	}

	/**
	 * Get the bKash API domain based on the configured environment.
	 *
	 * @since 1.0.0
	 *
	 * @return string The appropriate API domain URL for sandbox or live environment.
	 */
	public function getApiDomain(): string {
		return $this->environment === 'sandbox'
			? 'https://tokenized.sandbox.bka.sh/v2'
			: 'https://tokenized.pay.bka.sh/v1.2.0-beta';
	}

	/**
	 * Checks if the bKash payment gateway is properly configured.
	 *
	 * Verifies that all credentials are configured
	 * and not empty, which are required for bKash API communication.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if all credentials are configured, false otherwise.
	 */
	public function is_configured(): bool {
		return !empty($this->username) && !empty($this->password) && !empty($this->app_key) && !empty($this->app_secret);
	}

	/**
	 * Creates and updates the bKash payment gateway configuration.
	 *
	 * This method extends the parent class configuration and adds bKash-specific
	 * settings including credentials and API domain for use by the payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function createConfig(): void {
		parent::createConfig();

		$config = [
			'mode' => $this->getMode(),
			'username' => $this->getUsername(),
			'password' => $this->getPassword(),
			'app_key' => $this->getAppKey(),
			'app_secret' => $this->getAppSecret(),
			'api_domain' => $this->getApiDomain(),
		];

		$this->updateConfig($config);
	}
}
