<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force-enable WooCommerce registration on My Account.
 */
add_filter('woocommerce_registration_enabled','__return_true');
add_filter('woocommerce_enable_myaccount_registration','__return_true');
