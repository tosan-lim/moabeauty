<?php
/**
 * Customer registration and mobile login helpers for Beauty In.
 *
 * Keeps customer signup lightweight: name, mobile, email and password.
 * Address/order fields stay in checkout and account addresses.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'pre_option_woocommerce_registration_generate_username', 'beautyin_customer_generate_username' );
function beautyin_customer_generate_username( $value ) {
	if ( ! is_admin() && ! is_user_logged_in() ) {
		return 'yes';
	}

	return false;
}

add_action( 'woocommerce_register_form_start', 'beautyin_customer_register_name_fields' );
function beautyin_customer_register_name_fields() {
	if ( beautyin_customer_is_vendor_registration_request() ) {
		return;
	}

	$first_name = isset( $_POST['billing_first_name'] ) ? wc_clean( wp_unslash( $_POST['billing_first_name'] ) ) : '';
	$last_name  = isset( $_POST['billing_last_name'] ) ? wc_clean( wp_unslash( $_POST['billing_last_name'] ) ) : '';
	?>
	<p class="form-row form-row-first">
		<label for="reg_billing_first_name">First name&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" autocomplete="given-name" value="<?php echo esc_attr( $first_name ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="reg_billing_last_name">Last name&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" autocomplete="family-name" value="<?php echo esc_attr( $last_name ); ?>" />
	</p>
	<div class="clear"></div>
	<?php
}

add_action( 'woocommerce_register_form', 'beautyin_customer_register_mobile_field', 4 );
function beautyin_customer_register_mobile_field() {
	if ( beautyin_customer_is_vendor_registration_request() ) {
		return;
	}

	$mobile = isset( $_POST['billing_phone'] ) ? wc_clean( wp_unslash( $_POST['billing_phone'] ) ) : '';
	?>
	<p class="form-row form-row-wide">
		<label for="reg_billing_phone">Mobile number&nbsp;<span class="required">*</span></label>
		<input type="tel" class="input-text" name="billing_phone" id="reg_billing_phone" autocomplete="tel" inputmode="tel" placeholder="10 digit mobile number" value="<?php echo esc_attr( $mobile ); ?>" />
	</p>
	<?php
}

add_filter( 'woocommerce_registration_errors', 'beautyin_customer_validate_registration_fields', 10, 3 );
function beautyin_customer_validate_registration_fields( $errors, $username, $email ) {
	if ( beautyin_customer_is_vendor_registration_request() ) {
		return $errors;
	}

	$first_name = isset( $_POST['billing_first_name'] ) ? wc_clean( wp_unslash( $_POST['billing_first_name'] ) ) : '';
	$last_name  = isset( $_POST['billing_last_name'] ) ? wc_clean( wp_unslash( $_POST['billing_last_name'] ) ) : '';
	$phone      = isset( $_POST['billing_phone'] ) ? beautyin_customer_normalize_phone( wp_unslash( $_POST['billing_phone'] ) ) : '';

	if ( '' === $first_name ) {
		$errors->add( 'billing_first_name_error', __( 'Please enter your first name.', 'beautyin' ) );
	}

	if ( '' === $last_name ) {
		$errors->add( 'billing_last_name_error', __( 'Please enter your last name.', 'beautyin' ) );
	}

	if ( '' === $phone ) {
		$errors->add( 'billing_phone_error', __( 'Please enter your mobile number.', 'beautyin' ) );
	} elseif ( strlen( $phone ) < 10 || strlen( $phone ) > 13 ) {
		$errors->add( 'billing_phone_invalid_error', __( 'Please enter a valid mobile number.', 'beautyin' ) );
	} elseif ( beautyin_customer_find_user_by_phone( $phone ) ) {
		$errors->add( 'billing_phone_exists_error', __( 'An account already exists with this mobile number. Please login instead.', 'beautyin' ) );
	}

	return $errors;
}

add_action( 'woocommerce_created_customer', 'beautyin_customer_save_registration_fields' );
function beautyin_customer_save_registration_fields( $customer_id ) {
	$first_name = isset( $_POST['billing_first_name'] ) ? wc_clean( wp_unslash( $_POST['billing_first_name'] ) ) : '';
	$last_name  = isset( $_POST['billing_last_name'] ) ? wc_clean( wp_unslash( $_POST['billing_last_name'] ) ) : '';
	$phone_raw  = isset( $_POST['billing_phone'] ) ? wp_unslash( $_POST['billing_phone'] ) : '';
	$phone      = beautyin_customer_normalize_phone( $phone_raw );

	if ( $first_name ) {
		update_user_meta( $customer_id, 'first_name', $first_name );
		update_user_meta( $customer_id, 'billing_first_name', $first_name );
	}

	if ( $last_name ) {
		update_user_meta( $customer_id, 'last_name', $last_name );
		update_user_meta( $customer_id, 'billing_last_name', $last_name );
	}

	if ( $first_name || $last_name ) {
		wp_update_user(
			array(
				'ID'           => $customer_id,
				'display_name' => trim( $first_name . ' ' . $last_name ),
			)
		);
	}

	if ( $phone ) {
		update_user_meta( $customer_id, 'billing_phone', $phone );
		update_user_meta( $customer_id, 'beautyin_mobile_number', $phone );
	}
}

add_action( 'woocommerce_edit_account_form', 'beautyin_customer_account_tax_fields' );
function beautyin_customer_account_tax_fields() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();
	$phone   = get_user_meta( $user_id, 'billing_phone', true );
	if ( '' === $phone ) {
		$phone = get_user_meta( $user_id, 'beautyin_mobile_number', true );
	}
	$gst     = get_user_meta( $user_id, 'beautyin_customer_gst_number', true );
	$pan     = get_user_meta( $user_id, 'beautyin_customer_pan_number', true );
	?>
	<fieldset class="bm-customer-tax-fields">
		<legend>Contact, GST / PAN details</legend>
		<p class="description">Update your contact number once here. It will be reused at checkout for future orders, and GST/PAN details will be printed on the invoice when provided.</p>
		<p class="form-row form-row-wide">
			<label for="billing_phone">Mobile number <span class="optional">(optional)</span></label>
			<input type="tel" class="input-text" name="billing_phone" id="billing_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="10 digit mobile number" autocomplete="tel" inputmode="tel">
		</p>
		<p class="form-row form-row-wide">
			<label for="billing_gst_number">GST number <span class="optional">(optional)</span></label>
			<input type="text" class="input-text" name="billing_gst_number" id="billing_gst_number" value="<?php echo esc_attr( $gst ); ?>" placeholder="15-character GSTIN">
		</p>
		<p class="form-row form-row-wide">
			<label for="billing_pan_number">PAN number <span class="optional">(optional)</span></label>
			<input type="text" class="input-text" name="billing_pan_number" id="billing_pan_number" value="<?php echo esc_attr( $pan ); ?>" placeholder="10-character PAN">
		</p>
	</fieldset>
	<?php
}

add_filter( 'woocommerce_save_account_details_errors', 'beautyin_customer_validate_account_tax_fields', 20, 2 );
function beautyin_customer_validate_account_tax_fields( $errors, $user ) {
	$phone = isset( $_POST['billing_phone'] ) ? beautyin_customer_normalize_phone( wp_unslash( $_POST['billing_phone'] ) ) : '';
	$gst = isset( $_POST['billing_gst_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_gst_number'] ) ) : '';
	$pan = isset( $_POST['billing_pan_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_pan_number'] ) ) : '';
	$current_user_id = $user instanceof WP_User ? (int) $user->ID : get_current_user_id();
	$current_phone   = '';

	if ( $current_user_id ) {
		$current_phone = get_user_meta( $current_user_id, 'billing_phone', true );
		if ( '' === $current_phone ) {
			$current_phone = get_user_meta( $current_user_id, 'beautyin_mobile_number', true );
		}
		$current_phone = beautyin_customer_normalize_phone( $current_phone );
	}

	if ( '' !== $phone && ( strlen( $phone ) < 10 || strlen( $phone ) > 13 ) ) {
		$errors->add( 'billing_phone_invalid_error', __( 'Please enter a valid mobile number.', 'beautyin' ) );
	}

	if ( '' !== $phone && '' !== $current_phone && $phone === $current_phone ) {
		$phone = '';
	}

	if ( '' !== $phone ) {
		$existing_user = beautyin_customer_find_user_by_phone_excluding_user( $phone, $current_user_id );
		if ( $existing_user ) {
			$errors->add( 'billing_phone_exists_error', __( 'Another account already uses this mobile number.', 'beautyin' ) );
		}
	}

	if ( '' !== $gst && ! preg_match( '/^[0-9A-Z]{15}$/', $gst ) ) {
		$errors->add( 'billing_gst_number_error', __( 'Please enter a valid 15-character GST number.', 'beautyin' ) );
	}

	if ( '' !== $pan && ! preg_match( '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan ) ) {
		$errors->add( 'billing_pan_number_error', __( 'Please enter a valid PAN number.', 'beautyin' ) );
	}

	return $errors;
}

add_action( 'woocommerce_save_account_details', 'beautyin_customer_save_account_tax_fields', 20, 1 );
function beautyin_customer_save_account_tax_fields( $user_id ) {
	$phone_raw = isset( $_POST['billing_phone'] ) ? wp_unslash( $_POST['billing_phone'] ) : '';
	$phone     = beautyin_customer_normalize_phone( $phone_raw );
	if ( '' === $phone ) {
		$existing_phone = get_user_meta( $user_id, 'billing_phone', true );
		if ( '' === $existing_phone ) {
			$existing_phone = get_user_meta( $user_id, 'beautyin_mobile_number', true );
		}
		$existing_phone = beautyin_customer_normalize_phone( $existing_phone );
		if ( '' !== $existing_phone ) {
			$phone = $existing_phone;
		}
	}
	$gst = isset( $_POST['billing_gst_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_gst_number'] ) ) : '';
	$pan = isset( $_POST['billing_pan_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_pan_number'] ) ) : '';

	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'beautyin_mobile_number', $phone );
	update_user_meta( $user_id, 'beautyin_customer_gst_number', $gst );
	update_user_meta( $user_id, 'beautyin_customer_pan_number', $pan );
}

add_action( 'woocommerce_after_checkout_billing_form', 'beautyin_customer_checkout_tax_fields' );
function beautyin_customer_checkout_tax_fields() {
	if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
		return;
	}

	$state = beautyin_customer_checkout_tax_state();
	?>
	<div class="bm-customer-tax-fields bm-checkout-tax-fields bm-checkout-tax-compact">
		<h3>GST / PAN details</h3>
		<p class="description">Optional. Add GSTIN or PAN only if you need them on the invoice.</p>
		<p class="form-row form-row-wide">
			<label for="billing_gst_number">GST number <span class="optional">(optional)</span></label>
			<input type="text" class="input-text" name="billing_gst_number" id="billing_gst_number" value="<?php echo esc_attr( $state['gst'] ); ?>" placeholder="15-character GSTIN">
		</p>
		<p class="form-row form-row-wide">
			<label for="billing_pan_number">PAN number <span class="optional">(optional)</span></label>
			<input type="text" class="input-text" name="billing_pan_number" id="billing_pan_number" value="<?php echo esc_attr( $state['pan'] ); ?>" placeholder="10-character PAN">
		</p>
		<?php if ( $state['needs_confirmation'] ) : ?>
			<p class="form-row form-row-wide bm-tax-confirmation">
				<label>
					<input type="checkbox" name="beautyin_tax_confirmation" value="1" <?php checked( ! empty( $state['confirmation'] ) ); ?>>
					I do not need GST/PAN for this order.
				</label>
			</p>
		<?php else : ?>
			<p class="description">If added, they will print on the invoice.</p>
		<?php endif; ?>
	</div>
	<?php
}

add_filter( 'woocommerce_checkout_get_value', 'beautyin_customer_checkout_tax_prefill', 20, 2 );
function beautyin_customer_checkout_tax_prefill( $value, $input ) {
	if ( '' !== $value ) {
		return $value;
	}

	if ( ! is_user_logged_in() ) {
		return $value;
	}

	$user_id = get_current_user_id();
	if ( 'billing_gst_number' === $input ) {
		return get_user_meta( $user_id, 'beautyin_customer_gst_number', true );
	}
	if ( 'billing_pan_number' === $input ) {
		return get_user_meta( $user_id, 'beautyin_customer_pan_number', true );
	}
	if ( 'billing_phone' === $input ) {
		$phone = get_user_meta( $user_id, 'billing_phone', true );
		if ( '' === $phone ) {
			$phone = get_user_meta( $user_id, 'beautyin_mobile_number', true );
		}
		return $phone;
	}

	return $value;
}

add_filter( 'woocommerce_checkout_get_value', 'beautyin_customer_checkout_shipping_prefill', 19, 2 );
function beautyin_customer_checkout_shipping_prefill( $value, $input ) {
	if ( '' !== $value || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return $value;
	}

	if ( 0 !== strpos( (string) $input, 'shipping_' ) ) {
		return $value;
	}

	if ( isset( $_POST[ $input ] ) ) {
		return wc_clean( wp_unslash( $_POST[ $input ] ) );
	}

	return '';
}

add_filter( 'woocommerce_ship_to_different_address_checked', 'beautyin_customer_default_ship_to_billing', 20 );
function beautyin_customer_default_ship_to_billing( $checked ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $checked;
	}

	if ( isset( $_POST['ship_to_different_address'] ) ) {
		return wc_string_to_bool( wp_unslash( $_POST['ship_to_different_address'] ) ) ? 1 : 0;
	}

	return 0;
}

add_action( 'woocommerce_checkout_process', 'beautyin_customer_validate_checkout_tax_fields' );
function beautyin_customer_validate_checkout_tax_fields() {
	$state = beautyin_customer_checkout_tax_state();

	if ( '' === $state['gst'] && '' === $state['pan'] && empty( $state['confirmation'] ) ) {
		wc_add_notice( __( 'Please fill GST/PAN details or confirm that they are not required for this order.', 'beautyin' ), 'error' );
	}

	if ( '' !== $state['gst'] && ! preg_match( '/^[0-9A-Z]{15}$/', $state['gst'] ) ) {
		wc_add_notice( __( 'Please enter a valid 15-character GST number.', 'beautyin' ), 'error' );
	}

	if ( '' !== $state['pan'] && ! preg_match( '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $state['pan'] ) ) {
		wc_add_notice( __( 'Please enter a valid PAN number.', 'beautyin' ), 'error' );
	}
}

add_action( 'woocommerce_checkout_create_order', 'beautyin_customer_store_checkout_tax_fields', 20, 2 );
function beautyin_customer_store_checkout_tax_fields( $order, $data ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$state = beautyin_customer_checkout_tax_state();

	$order->update_meta_data( 'beautyin_customer_gst_number', $state['gst'] );
	$order->update_meta_data( 'beautyin_customer_pan_number', $state['pan'] );
	$order->update_meta_data( 'beautyin_customer_tax_confirmation', ! empty( $state['confirmation'] ) ? 'yes' : 'no' );
	$order->update_meta_data( 'beautyin_customer_tax_confirmation_text', ! empty( $state['confirmation'] ) ? 'Customer confirmed GST/PAN is not required' : '' );

	if ( $order->get_customer_id() ) {
		update_user_meta( $order->get_customer_id(), 'beautyin_customer_gst_number', $state['gst'] );
		update_user_meta( $order->get_customer_id(), 'beautyin_customer_pan_number', $state['pan'] );
	}
}

add_filter( 'authenticate', 'beautyin_customer_authenticate_by_mobile', 18, 3 );
function beautyin_customer_authenticate_by_mobile( $user, $username, $password ) {
	if ( $user instanceof WP_User || empty( $username ) || empty( $password ) ) {
		return $user;
	}

	$phone = beautyin_customer_normalize_phone( $username );
	if ( strlen( $phone ) < 10 ) {
		return $user;
	}

	$matched_user = beautyin_customer_find_user_by_phone( $phone );
	if ( ! $matched_user instanceof WP_User ) {
		return $user;
	}

	if ( wp_check_password( $password, $matched_user->user_pass, $matched_user->ID ) ) {
		return $matched_user;
	}

	return new WP_Error( 'incorrect_password', __( 'The password you entered is incorrect.', 'woocommerce' ) );
}

add_action( 'wp_footer', 'beautyin_customer_auth_inline_script', 45 );
function beautyin_customer_auth_inline_script() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var loginInput = document.querySelector('#customer_login #username');
		if (loginInput) {
			loginInput.setAttribute('placeholder', 'Email or mobile number');
			loginInput.setAttribute('autocomplete', 'username');
			var loginLabel = document.querySelector('#customer_login label[for="username"]');
			if (loginLabel) {
				loginLabel.innerHTML = 'Email / Mobile number <span class="required">*</span>';
			}
		}

		var emailInput = document.querySelector('#customer_login #reg_email');
		if (emailInput) {
			emailInput.setAttribute('placeholder', 'Email address');
		}
	});
	</script>
	<?php
}

function beautyin_customer_find_user_by_phone( $phone ) {
	$phone = beautyin_customer_normalize_phone( $phone );
	if ( '' === $phone ) {
		return false;
	}

	$phone_values = beautyin_customer_phone_lookup_values( $phone );

	$user_query = new WP_User_Query(
		array(
			'number'     => 1,
			'fields'     => 'all',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'billing_phone',
					'value'   => $phone_values,
					'compare' => 'IN',
				),
				array(
					'key'     => 'beautyin_mobile_number',
					'value'   => $phone_values,
					'compare' => 'IN',
				),
			),
		)
	);

	$users = $user_query->get_results();
	return ! empty( $users ) ? $users[0] : false;
}

function beautyin_customer_find_user_by_phone_excluding_user( $phone, $exclude_user_id = 0 ) {
	$matched_user = beautyin_customer_find_user_by_phone( $phone );
	if ( ! $matched_user instanceof WP_User ) {
		return false;
	}

	if ( $exclude_user_id && (int) $matched_user->ID === (int) $exclude_user_id ) {
		return false;
	}

	return $matched_user;
}

function beautyin_customer_normalize_phone( $phone ) {
	$phone = preg_replace( '/[^\d+]/', '', (string) $phone );
	if ( 0 === strpos( $phone, '+91' ) ) {
		$phone = substr( $phone, 3 );
	} elseif ( 0 === strpos( $phone, '91' ) && 12 === strlen( $phone ) ) {
		$phone = substr( $phone, 2 );
	}

	return $phone;
}

function beautyin_customer_phone_lookup_values( $phone ) {
	$phone  = beautyin_customer_normalize_phone( $phone );
	$values = array( $phone );

	if ( 10 === strlen( $phone ) ) {
		$values[] = '91' . $phone;
		$values[] = '+91' . $phone;
	}

	return array_values( array_unique( array_filter( $values ) ) );
}

function beautyin_customer_normalize_tax_id( $value ) {
	$value = strtoupper( trim( preg_replace( '/\s+/', '', (string) $value ) ) );
	return preg_replace( '/[^A-Z0-9]/', '', $value );
}

function beautyin_customer_checkout_tax_state() {
	$user_id = is_user_logged_in() ? get_current_user_id() : 0;

	$gst = isset( $_POST['billing_gst_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_gst_number'] ) ) : '';
	$pan = isset( $_POST['billing_pan_number'] ) ? beautyin_customer_normalize_tax_id( wp_unslash( $_POST['billing_pan_number'] ) ) : '';
	$confirmation = ! empty( $_POST['beautyin_tax_confirmation'] ) ? 'yes' : '';

	if ( '' === $gst && $user_id ) {
		$gst = beautyin_customer_normalize_tax_id( get_user_meta( $user_id, 'beautyin_customer_gst_number', true ) );
	}

	if ( '' === $pan && $user_id ) {
		$pan = beautyin_customer_normalize_tax_id( get_user_meta( $user_id, 'beautyin_customer_pan_number', true ) );
	}

	return array(
		'gst'               => $gst,
		'pan'               => $pan,
		'confirmation'      => $confirmation,
		'needs_confirmation' => '' === $gst && '' === $pan,
	);
}

function beautyin_customer_is_vendor_registration_request() {
	$post_role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';

	return in_array( $post_role, array( 'seller', 'vendor' ), true )
		|| is_page( 'vendor-registration' )
		|| is_page( 'vendor-login' );
}
