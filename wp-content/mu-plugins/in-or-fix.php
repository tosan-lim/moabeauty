<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
Plugin Name: Woo: add IN:OR state if missing
*/
add_filter('woocommerce_states', function ($states) {
    if (isset($states['IN']) && !isset($states['IN']['OR'])) {
        $states['IN']['OR'] = 'Odisha';
    }
    return $states;
});
