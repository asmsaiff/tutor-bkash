<?php
    /**
     * RewriteRules class
     *
     * @author S. Saif <https://github.com/asmsaiff>
     * @since 1.0.1
     */

    namespace FinersPay;

    class RewriteRules {
        public static function finerspay_custom_rewrite_rule() {
            add_rewrite_rule('^execute-payment/?$', 'index.php?execute_payment=1', 'top');
        }

        public static function finerspay_custom_query_vars($vars) {
            $vars[] = 'execute_payment';
            return $vars;
        }
    }
