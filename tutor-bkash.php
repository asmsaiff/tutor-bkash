<?php
    /**
     * Plugin Name:     Tutor bKash
     * Plugin URI:      https://github.com/asmsaiff/tutor-bkash
     * Description:     bKash payment gateway integration for Tutor LMS (Free & Pro). Accept online payments directly within your Tutor LMS-powered site using bKash Tokenized Checkout.
     * Version:         1.0.0
     * Author:          S. Saif
     * Author URI:      https://github.com/asmsaiff
     * License:         GPLv2 or later
     * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
     * Text Domain:     tutor-bkash
     * Domain Path:    /languages
     * Requires Plugins: tutor
     */

    defined('ABSPATH') || exit;

    // Activation and deactivation hooks
    function plugin_activation() {
        TutorBkash\RewriteRules::custom_rewrite_rule();
        flush_rewrite_rules();
    }
    register_activation_hook(__FILE__, 'plugin_activation');

    function plugin_deactivation() {
        flush_rewrite_rules();
    }
    register_deactivation_hook(__FILE__, 'plugin_deactivation');

    /**
     * Main Plugin Class
     *
     * Handles plugin initialization and core functionality.
     *
     * @since 1.0.0
     */
    final class Tutor_Bkash_Plugin {

        /**
         * Single instance of the plugin
         *
         * @since 1.0.0
         * @var Tutor_Bkash_Plugin|null
         */
        private static $instance = null;

        /**
         * Get singleton instance
         *
         * @since 1.0.0
         * @return Tutor_Bkash_Plugin
         */
        public static function get_instance(): self {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor - Initialize the plugin
         *
         * @since 1.0.0
         */
        private function __construct() {
            $this->init();
        }

        /**
         * Initialize the plugin
         *
         * @since 1.0.0
         */
        private function init(): void {
            $this->load_dependencies();
            $this->define_constants();
            $this->init_hooks();
        }

        /**
         * Load plugin dependencies
         *
         * @since 1.0.0
         */
        private function load_dependencies(): void {
            require_once __DIR__ . '/vendor/autoload.php';

            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
        }

        /**
         * Define plugin constants
         *
         * @since 1.0.0
         */
        private function define_constants(): void {
            define('TUTOR_BKASH_VERSION', '1.0.0');
            define('TUTOR_BKASH_URL', plugin_dir_url(__FILE__));
            define('TUTOR_BKASH_PATH', plugin_dir_path(__FILE__));
        }

        /**
         * Initialize WordPress hooks
         *
         * @since 1.0.0
         */
        private function init_hooks(): void {
            add_action('plugins_loaded', [$this, 'init_gateway'], 100);
            add_action('template_redirect', ['TutorBkash\\ExecutePayment', 'handle_payment_execution']);
            add_action('init', ['TutorBkash\\RewriteRules', 'custom_rewrite_rule']);
            add_filter('query_vars', ['TutorBkash\\RewriteRules', 'custom_query_vars']);
        }

        /**
         * Initialize the bKash payment gateway
         *
         * @since 1.0.0
         */
        public function init_gateway(): void {
            //works with the free version of Tutor LMS
            if (is_plugin_active('tutor/tutor.php')) {
                new TutorBkash\Init();
            }
        }
    }

    // Initialize the plugin
    Tutor_Bkash_Plugin::get_instance();
