<?php
    /**
     * RewriteRules class
     *
     * @author S. Saif <https://github.com/asmsaiff>
     * @since 1.0.0
     */

    namespace TutorBkash;

    class RewriteRules {
        public static function tutor_bkash_custom_rewrite_rule() {
            add_rewrite_rule('^execute-payment/?$', 'index.php?execute_payment=1', 'top');
        }

        public static function tutor_bkash_custom_query_vars($vars) {
            $vars[] = 'execute_payment';
            return $vars;
        }
    }
