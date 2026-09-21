<?php
/**
 * Beauty In mail delivery hardening.
 *
 * Keeps WordPress/API mail requests away from dead local proxy variables,
 * chooses a working API mailer, and logs delivery failures so signup emails do
 * not fail silently.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'beautyin_mail_delivery_clear_dead_proxy', 1 );
function beautyin_mail_delivery_clear_dead_proxy() {
	foreach ( array( 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'http_proxy', 'https_proxy', 'all_proxy' ) as $name ) {
		$value = getenv( $name );
		if ( false !== $value && false !== strpos( (string) $value, '127.0.0.1:9' ) ) {
			putenv( $name );
			unset( $_ENV[ $name ], $_SERVER[ $name ] );
		}
	}
}

add_action( 'init', 'beautyin_mail_delivery_configure_mailer', 5 );
function beautyin_mail_delivery_configure_mailer() {
	$options = get_option( 'wp_mail_smtp', array() );
	if ( ! is_array( $options ) ) {
		return;
	}

	$options = beautyin_mail_delivery_normalize_options( $options );
	update_option( 'wp_mail_smtp', $options );

	beautyin_mail_delivery_enable_customer_account_email();
}

add_filter( 'option_wp_mail_smtp', 'beautyin_mail_delivery_normalize_options' );
function beautyin_mail_delivery_normalize_options( $options ) {
	if ( ! is_array( $options ) ) {
		return $options;
	}

	if ( ! isset( $options['mail'] ) || ! is_array( $options['mail'] ) ) {
		$options['mail'] = array();
	}

	$mailersend = isset( $options['mailersend'] ) && is_array( $options['mailersend'] ) ? $options['mailersend'] : array();
	$sendinblue = isset( $options['sendinblue'] ) && is_array( $options['sendinblue'] ) ? $options['sendinblue'] : array();
	$smtp       = isset( $options['smtp'] ) && is_array( $options['smtp'] ) ? $options['smtp'] : array();

	/*
	 * Brevo/Sendinblue can reject sends when the current server IP is not
	 * allowlisted, and stale API keys can fail with "Unauthenticated". Prefer
	 * authenticated SMTP when configured, then fall back to API mailers.
	 */
	if ( ! empty( $smtp['host'] ) && ! empty( $smtp['user'] ) && ! empty( $smtp['pass'] ) ) {
		$options['mail']['mailer'] = 'smtp';
	} elseif ( ! empty( $mailersend['api_key'] ) ) {
		$options['mail']['mailer'] = 'mailersend';
	} elseif ( ! empty( $sendinblue['api_key'] ) ) {
		$options['mail']['mailer'] = 'sendinblue';
	}

	$options['mail']['from_email']       = 'moabeauty@kilindia.in';
	$options['mail']['from_name']        = 'Beautyin';
	$options['mail']['from_email_force'] = true;
	$options['mail']['from_name_force']  = true;

	return $options;
}

add_action( 'wp_mail_failed', 'beautyin_mail_delivery_log_failure' );
function beautyin_mail_delivery_log_failure( $error ) {
	if ( ! $error instanceof WP_Error ) {
		return;
	}

	update_option(
		'beautyin_last_mail_failure',
		array(
			'time'    => current_time( 'mysql' ),
			'message' => $error->get_error_message(),
		),
		false
	);

	error_log( '[BeautyIn mail] ' . $error->get_error_message() );
}

function beautyin_mail_delivery_enable_customer_account_email() {
	$settings = get_option( 'woocommerce_email_customer_new_account_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings['enabled'] = 'yes';
	update_option( 'woocommerce_email_customer_new_account_settings', $settings );
}
