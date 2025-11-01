<?php
    namespace TutorBkash;

    class RewriteRules {
        public static function custom_rewrite_rule() {
            add_rewrite_rule('^execute-payment/?$', 'index.php?execute_payment=1', 'top');
        }

        public static function custom_query_vars($vars) {
            $vars[] = 'execute_payment';
            return $vars;
        }
    }
