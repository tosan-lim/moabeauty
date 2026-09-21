<?php
/**
 * Beauty In security hardening.
 *
 * Lightweight protections that reduce common attack surface without changing
 * checkout, account, vendor, or order flows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'send_headers', 'beautyin_security_send_headers', 20 );
add_filter( 'login_errors', 'beautyin_security_generic_login_error' );
add_filter( 'rest_authentication_errors', 'beautyin_security_block_public_user_rest_routes' );
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Add a small set of safe browser headers.
 */
function beautyin_security_send_headers() {
	if ( headers_sent() ) {
		return;
	}

	// Keep framing local to this site only.
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), camera=(), microphone=()' );
}

/**
 * Avoid leaking whether a username or password was wrong.
 */
function beautyin_security_generic_login_error() {
	return 'Invalid login details.';
}

/**
 * Block unauthenticated access to public user REST endpoints to reduce
 * username / profile enumeration.
 *
 * @param mixed $result Current REST auth result.
 * @return mixed
 */
function beautyin_security_block_public_user_rest_routes( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( ! function_exists( 'rest_get_url_prefix' ) ) {
		return $result;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$rest_prefix = '/' . trim( rest_get_url_prefix(), '/' ) . '/';

	if ( false === strpos( $request_uri, $rest_prefix . 'wp/v2/users' ) ) {
		return $result;
	}

	if ( is_user_logged_in() ) {
		return $result;
	}

	return new WP_Error(
		'rest_forbidden',
		'Authentication required.',
		array( 'status' => 401 )
	);
}
