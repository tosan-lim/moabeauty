<?php
/**
 * Beauty In domestic/international vendor workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'dokan_new_seller_enable_selling_status', '__return_false', 999 );
add_filter( 'dokan_get_new_post_status', 'beautyin_vendor_force_product_review', 999 );
add_filter( 'dokan_get_default_product_status', 'beautyin_vendor_force_product_review', 999 );
add_action( 'init', 'beautyin_force_my_account_customer_registration', 1 );
add_filter( 'option_dokan_appearance', 'beautyin_hide_vendor_role_on_my_account' );
add_filter( 'the_content', 'beautyin_vendor_registration_login_first_layout', 12 );

function beautyin_vendor_force_product_review( $status ) {
	if ( is_admin() && current_user_can( 'manage_woocommerce' ) ) {
		return $status;
	}

	return 'pending';
}

function beautyin_hide_vendor_role_on_my_account( $options ) {
	if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || is_page( 'vendor-registration' ) ) {
		return $options;
	}

	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options['show_register_as_vendor'] = 'off';
	return $options;
}

function beautyin_force_my_account_customer_registration() {
	if ( empty( $_POST['register'] ) || empty( $_POST['role'] ) || 'seller' !== sanitize_key( wp_unslash( $_POST['role'] ) ) ) {
		return;
	}

	if ( ! empty( $_POST['beautyin_vendor_type'] ) ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( false !== strpos( $request_uri, '/my-account' ) ) {
		$_POST['role'] = 'customer';
	}
}

function beautyin_vendor_registration_login_first_layout( $content ) {
	if ( is_admin() || ! is_page( 'vendor-registration' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$dashboard_url = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url() : home_url( '/dashboard/' );
	$register_url  = add_query_arg( 'vendor_signup', '1', get_permalink() ) . '#bm-vendor-register-panel';
	$show_register = ! empty( $_GET['vendor_signup'] ) || ! empty( $_POST['register'] ) || false !== strpos( $content, 'woocommerce-error' );

	if ( is_user_logged_in() ) {
		return '<section class="bm-vendor-access bm-vendor-access-logged-in"><div class="bm-vendor-login-card"><span class="bm-vendor-kicker">Vendor access</span><h2>Vendor dashboard</h2><p>You are already signed in. Continue to your seller dashboard to manage products, orders, payouts and store settings.</p><div class="bm-vendor-logged-in"><a href="' . esc_url( $dashboard_url ) . '">Open vendor dashboard</a></div></div></section>';
	}

	ob_start();
	?>
	<section class="bm-vendor-access">
		<div class="bm-vendor-login-card">
			<span class="bm-vendor-kicker">Vendor access</span>
			<h2>Vendor login</h2>
			<p>Already registered or approved? Login first to manage products, orders, payouts and your seller dashboard.</p>
			<?php
			wp_login_form(
				array(
					'echo'           => true,
					'redirect'       => $dashboard_url,
					'form_id'        => 'bm-vendor-login-form',
					'label_username' => 'Username or email address',
					'label_password' => 'Password',
					'label_remember' => 'Remember me',
					'label_log_in'   => 'Login',
					'remember'       => true,
				)
			);
			?>
			<a class="bm-vendor-lost" href="<?php echo esc_url( wp_lostpassword_url( $account_url ) ); ?>">Lost your password?</a>
		</div>
		<div class="bm-vendor-signup-card">
			<span class="bm-vendor-kicker">New seller?</span>
			<h2>Do not have a vendor account?</h2>
			<p>Sign up below as a domestic or international vendor. International vendors require document verification and admin approval before selling.</p>
			<a class="bm-vendor-register-toggle" href="<?php echo esc_url( $register_url ); ?>">Register as vendor</a>
		</div>
	</section>
	<?php

	$access_markup = ob_get_clean();

	if ( ! $show_register ) {
		return $access_markup;
	}

	return $access_markup . '<div id="bm-vendor-register-panel" class="bm-vendor-register-panel is-open">' . $content . '</div>';
}

add_action( 'dokan_seller_registration_after_shopurl_field', 'beautyin_vendor_registration_type_fields' );
add_action( 'dokan_after_seller_migration_fields', 'beautyin_vendor_registration_type_fields' );
add_action( 'dokan_vendor_reg_form_start', 'beautyin_vendor_registration_intro' );
function beautyin_vendor_registration_intro() {
	?>
	<div class="bm-vendor-register-intro">
		<strong>New vendor signup</strong>
		<span>Fill this form only if you do not already have a vendor account. Choose Domestic vendor if you ship from India, or International vendor if products ship from outside India and require customs/import review.</span>
	</div>
	<?php
}

function beautyin_vendor_registration_type_fields() {
	$vendor_type = isset( $_POST['beautyin_vendor_type'] ) ? sanitize_key( wp_unslash( $_POST['beautyin_vendor_type'] ) ) : '';
	?>
	<div class="bm-vendor-type-box">
		<h3>Seller type and verification</h3>
		<p>Choose how your store will sell on KIL India. International sellers require document review and admin approval before products can go live.</p>

		<div class="bm-vendor-type-options">
			<label>
				<input type="radio" name="beautyin_vendor_type" value="domestic" <?php checked( $vendor_type, 'domestic' ); ?> required>
				<span><strong>Domestic vendor</strong><small>India-based seller shipping within India.</small></span>
			</label>
			<label>
				<input type="radio" name="beautyin_vendor_type" value="international" <?php checked( $vendor_type, 'international' ); ?> required>
				<span><strong>International vendor</strong><small>Seller outside India. Customs, import duties and extra buyer terms apply.</small></span>
			</label>
		</div>

		<div class="bm-domestic-vendor-fields">
			<p class="form-row form-group form-row-wide">
				<label for="beautyin_domestic_tax_id">GST / PAN / business ID <span class="optional">(recommended)</span></label>
				<input type="text" class="input-text form-control" name="beautyin_domestic_tax_id" id="beautyin_domestic_tax_id" value="<?php echo isset( $_POST['beautyin_domestic_tax_id'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['beautyin_domestic_tax_id'] ) ) ) : ''; ?>" placeholder="GSTIN, PAN or business registration ID">
			</p>
		</div>

		<div class="bm-international-vendor-fields">
			<p class="form-row form-group form-row-wide">
				<label for="beautyin_intl_business_registration">Business registration / tax ID <span class="required">*</span></label>
				<input type="text" class="input-text form-control" name="beautyin_intl_business_registration" id="beautyin_intl_business_registration" value="<?php echo isset( $_POST['beautyin_intl_business_registration'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['beautyin_intl_business_registration'] ) ) ) : ''; ?>" placeholder="Company registration, tax ID or equivalent">
			</p>
			<p class="form-row form-group form-row-wide">
				<label for="beautyin_intl_import_export_code">Import/export or customs ID <span class="required">*</span></label>
				<input type="text" class="input-text form-control" name="beautyin_intl_import_export_code" id="beautyin_intl_import_export_code" value="<?php echo isset( $_POST['beautyin_intl_import_export_code'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['beautyin_intl_import_export_code'] ) ) ) : ''; ?>" placeholder="Import/export code, customs ID or exporter reference">
			</p>
			<p class="form-row form-group form-row-wide">
				<label for="beautyin_intl_document_links">Verification document links <span class="optional">(recommended)</span></label>
				<textarea class="input-text form-control" name="beautyin_intl_document_links" id="beautyin_intl_document_links" rows="3" placeholder="Paste Google Drive, Dropbox or document URLs for company registration, tax certificate, export license, etc."><?php echo isset( $_POST['beautyin_intl_document_links'] ) ? esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['beautyin_intl_document_links'] ) ) ) : ''; ?></textarea>
			</p>
			<p class="form-row form-group form-row-wide bm-vendor-checkbox">
				<label>
					<input type="checkbox" name="beautyin_intl_customs_terms" value="1" <?php checked( isset( $_POST['beautyin_intl_customs_terms'] ) ); ?>>
					I understand international products may include customs duty, import charges, courier clearance fees and longer delivery timelines. KIL India will review my documents before seller approval.
				</label>
			</p>
		</div>
	</div>
	<?php
}

add_filter( 'woocommerce_registration_errors', 'beautyin_vendor_registration_validate_type', 20, 3 );
function beautyin_vendor_registration_validate_type( $errors, $username, $email ) {
	$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
	if ( 'seller' !== $role ) {
		return $errors;
	}

	$vendor_type = isset( $_POST['beautyin_vendor_type'] ) ? sanitize_key( wp_unslash( $_POST['beautyin_vendor_type'] ) ) : '';
	if ( ! in_array( $vendor_type, array( 'domestic', 'international' ), true ) ) {
		$errors->add( 'beautyin_vendor_type_error', 'Please select Domestic vendor or International vendor.' );
		return $errors;
	}

	if ( 'international' === $vendor_type ) {
		$business_registration = isset( $_POST['beautyin_intl_business_registration'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['beautyin_intl_business_registration'] ) ) ) : '';
		$customs_id            = isset( $_POST['beautyin_intl_import_export_code'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['beautyin_intl_import_export_code'] ) ) ) : '';

		if ( '' === $business_registration ) {
			$errors->add( 'beautyin_intl_business_registration_error', 'Please enter international business registration or tax ID.' );
		}
		if ( '' === $customs_id ) {
			$errors->add( 'beautyin_intl_import_export_code_error', 'Please enter import/export or customs ID.' );
		}
		if ( empty( $_POST['beautyin_intl_customs_terms'] ) ) {
			$errors->add( 'beautyin_intl_customs_terms_error', 'Please accept the international vendor customs and verification terms.' );
		}
	}

	return $errors;
}

add_action( 'dokan_new_seller_created', 'beautyin_vendor_registration_save_type', 20, 2 );
function beautyin_vendor_registration_save_type( $user_id, $dokan_settings ) {
	$vendor_type = isset( $_POST['beautyin_vendor_type'] ) ? sanitize_key( wp_unslash( $_POST['beautyin_vendor_type'] ) ) : '';
	if ( ! in_array( $vendor_type, array( 'domestic', 'international' ), true ) ) {
		$vendor_type = 'domestic';
	}

	update_user_meta( $user_id, 'beautyin_vendor_type', $vendor_type );
	update_user_meta( $user_id, 'beautyin_vendor_verification_status', 'pending_review' );
	update_user_meta( $user_id, 'beautyin_domestic_tax_id', isset( $_POST['beautyin_domestic_tax_id'] ) ? sanitize_text_field( wp_unslash( $_POST['beautyin_domestic_tax_id'] ) ) : '' );
	update_user_meta( $user_id, 'beautyin_intl_business_registration', isset( $_POST['beautyin_intl_business_registration'] ) ? sanitize_text_field( wp_unslash( $_POST['beautyin_intl_business_registration'] ) ) : '' );
	update_user_meta( $user_id, 'beautyin_intl_import_export_code', isset( $_POST['beautyin_intl_import_export_code'] ) ? sanitize_text_field( wp_unslash( $_POST['beautyin_intl_import_export_code'] ) ) : '' );
	update_user_meta( $user_id, 'beautyin_intl_document_links', isset( $_POST['beautyin_intl_document_links'] ) ? sanitize_textarea_field( wp_unslash( $_POST['beautyin_intl_document_links'] ) ) : '' );
	update_user_meta( $user_id, 'beautyin_intl_customs_terms_accepted', ! empty( $_POST['beautyin_intl_customs_terms'] ) ? 'yes' : 'no' );
}

add_action( 'show_user_profile', 'beautyin_vendor_admin_profile_fields' );
add_action( 'edit_user_profile', 'beautyin_vendor_admin_profile_fields' );
function beautyin_vendor_admin_profile_fields( $user ) {
	if ( empty( $user->roles ) || ! in_array( 'seller', (array) $user->roles, true ) ) {
		return;
	}

	$vendor_type = get_user_meta( $user->ID, 'beautyin_vendor_type', true );
	$status      = get_user_meta( $user->ID, 'beautyin_vendor_verification_status', true );
	?>
	<h2>KIL vendor verification</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="beautyin_vendor_type">Vendor type</label></th>
			<td>
				<select name="beautyin_vendor_type" id="beautyin_vendor_type">
					<option value="domestic" <?php selected( $vendor_type, 'domestic' ); ?>>Domestic vendor</option>
					<option value="international" <?php selected( $vendor_type, 'international' ); ?>>International vendor</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="beautyin_vendor_verification_status">Verification status</label></th>
			<td>
				<select name="beautyin_vendor_verification_status" id="beautyin_vendor_verification_status">
					<option value="pending_review" <?php selected( $status, 'pending_review' ); ?>>Pending review</option>
					<option value="documents_verified" <?php selected( $status, 'documents_verified' ); ?>>Documents verified</option>
					<option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
					<option value="rejected" <?php selected( $status, 'rejected' ); ?>>Rejected</option>
				</select>
				<p class="description">Use Approved only after documents are checked and seller selling permission is enabled in Dokan.</p>
			</td>
		</tr>
		<tr>
			<th>Domestic GST/PAN/business ID</th>
			<td><input type="text" class="regular-text" name="beautyin_domestic_tax_id" value="<?php echo esc_attr( get_user_meta( $user->ID, 'beautyin_domestic_tax_id', true ) ); ?>"></td>
		</tr>
		<tr>
			<th>International business registration</th>
			<td><input type="text" class="regular-text" name="beautyin_intl_business_registration" value="<?php echo esc_attr( get_user_meta( $user->ID, 'beautyin_intl_business_registration', true ) ); ?>"></td>
		</tr>
		<tr>
			<th>International customs/import ID</th>
			<td><input type="text" class="regular-text" name="beautyin_intl_import_export_code" value="<?php echo esc_attr( get_user_meta( $user->ID, 'beautyin_intl_import_export_code', true ) ); ?>"></td>
		</tr>
		<tr>
			<th>Verification document links</th>
			<td><textarea class="large-text" rows="4" name="beautyin_intl_document_links"><?php echo esc_textarea( get_user_meta( $user->ID, 'beautyin_intl_document_links', true ) ); ?></textarea></td>
		</tr>
	</table>
	<?php
}

add_action( 'personal_options_update', 'beautyin_vendor_admin_profile_save_fields' );
add_action( 'edit_user_profile_update', 'beautyin_vendor_admin_profile_save_fields' );
function beautyin_vendor_admin_profile_save_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	foreach ( array( 'beautyin_vendor_type', 'beautyin_vendor_verification_status', 'beautyin_domestic_tax_id', 'beautyin_intl_business_registration', 'beautyin_intl_import_export_code' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	if ( isset( $_POST['beautyin_intl_document_links'] ) ) {
		update_user_meta( $user_id, 'beautyin_intl_document_links', sanitize_textarea_field( wp_unslash( $_POST['beautyin_intl_document_links'] ) ) );
	}
}

add_action( 'wp_enqueue_scripts', 'beautyin_vendor_workflow_styles', 40 );
function beautyin_vendor_workflow_styles() {
	wp_register_style( 'beautyin-vendor-workflow', false, array(), '1.0.0' );
	wp_enqueue_style( 'beautyin-vendor-workflow' );
	wp_add_inline_style(
		'beautyin-vendor-workflow',
		'
		.bm-vendor-access {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0;
			margin: 0 0 28px;
		}
		.bm-vendor-access-logged-in {
			max-width: 760px;
			margin-right: auto;
			margin-left: auto;
		}
		.bm-vendor-access-logged-in .bm-vendor-login-card {
			border-radius: 18px;
		}
		.bm-vendor-login-card,
		.bm-vendor-signup-card {
			padding: 24px;
			border: 1px solid #eadfe7;
			border-radius: 16px;
			background: #fff;
			box-shadow: 0 16px 42px rgba(64, 24, 50, 0.08);
		}
		.bm-vendor-login-card {
			background: linear-gradient(135deg, #fff, #fff8fb);
		}
		.bm-vendor-login-card {
			max-width: 680px;
			margin-inline: auto;
			width: 100%;
			border-radius: 18px 18px 0 0;
		}
		.bm-vendor-kicker {
			display: block;
			margin-bottom: 8px;
			color: #cf2d67;
			font-size: 12px;
			font-weight: 900;
			text-transform: uppercase;
		}
		.bm-vendor-login-card h2,
		.bm-vendor-signup-card h2 {
			margin: 0 0 8px;
			font-size: 32px;
			line-height: 1.08;
		}
		.bm-vendor-login-card p,
		.bm-vendor-signup-card p {
			margin: 0 0 18px;
			color: #6d6573;
			font-size: 15px;
			line-height: 1.6;
		}
		#bm-vendor-login-form {
			display: grid;
			gap: 14px;
			margin: 0;
		}
		#bm-vendor-login-form p {
			margin: 0;
		}
		#bm-vendor-login-form label {
			display: block;
			margin-bottom: 7px;
			color: #17151c;
			font-weight: 800;
		}
		#bm-vendor-login-form input[type="text"],
		#bm-vendor-login-form input[type="password"] {
			width: 100%;
			min-height: 48px;
			border: 1px solid #eadfe7;
			border-radius: 10px;
			background: #fff;
		}
		#bm-vendor-login-form .login-remember label {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-weight: 700;
		}
		#bm-vendor-login-form .button,
		.bm-vendor-signup-card a,
		.bm-vendor-logged-in a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: max-content;
			min-height: 44px;
			padding: 0 20px;
			border: 0;
			border-radius: 999px;
			background: #17151c;
			color: #fff;
			font-weight: 900;
			text-decoration: none;
			cursor: pointer;
		}
		.bm-vendor-signup-card {
			display: grid;
			grid-template-columns: minmax(0, 1fr) auto;
			align-items: center;
			gap: 16px;
			max-width: 680px;
			width: 100%;
			margin-inline: auto;
			border-top: 0;
			border-radius: 0 0 18px 18px;
			background: #fff8fb;
			color: #17151c;
		}
		.bm-vendor-signup-card .bm-vendor-kicker {
			color: #cf2d67;
		}
		.bm-vendor-signup-card h2 {
			margin-bottom: 6px;
			font-size: 24px;
			color: #17151c;
		}
		.bm-vendor-signup-card p {
			margin-bottom: 0;
			color: #6d6573;
		}
		.bm-vendor-signup-card a {
			background: #17151c;
			color: #fff;
			white-space: nowrap;
		}
		.bm-vendor-register-panel {
			display: none;
			max-width: 860px;
			margin: 0 auto;
		}
		.bm-vendor-register-panel.is-open {
			display: block;
		}
		.bm-vendor-register-panel[hidden] {
			display: none !important;
		}
		.bm-vendor-lost {
			display: inline-block;
			margin-top: 14px;
			color: #cf2d67;
			font-weight: 800;
			text-decoration: none;
		}
		.bm-vendor-logged-in {
			display: grid;
			gap: 12px;
		}
		.bm-vendor-register-intro {
			display: grid;
			gap: 8px;
			margin: 0 0 22px;
			padding: 18px;
			border: 1px solid #eadfe7;
			border-radius: 14px;
			background: linear-gradient(135deg, #fff8fb, #fff);
		}
		.bm-vendor-register-intro strong {
			font-size: 22px;
			line-height: 1.2;
		}
		.bm-vendor-register-intro span {
			color: #6d6573;
		}
		.woocommerce-account:not(.logged-in) .vendor-customer-registration,
		.woocommerce-account:not(.logged-in) .show_if_seller {
			display: none !important;
		}
		.bm-vendor-type-box {
			margin: 20px 0;
			padding: 18px;
			border: 1px solid #eadfe7;
			border-radius: 14px;
			background: #fff8fb;
		}
		.bm-vendor-type-box h3 {
			margin: 0 0 6px;
			font-size: 20px;
			line-height: 1.2;
		}
		.bm-vendor-type-box > p {
			margin: 0 0 14px;
			color: #6d6573;
		}
		.bm-vendor-type-options {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px;
			margin-bottom: 16px;
		}
		.bm-vendor-type-options label,
		.bm-vendor-checkbox label {
			display: flex;
			gap: 10px;
			align-items: flex-start;
			margin: 0;
			padding: 14px;
			border: 1px solid #eadfe7;
			border-radius: 12px;
			background: #fff;
		}
		.bm-vendor-type-options strong,
		.bm-vendor-type-options small {
			display: block;
		}
		.bm-vendor-type-options small {
			margin-top: 3px;
			color: #6d6573;
			line-height: 1.35;
		}
		.bm-international-vendor-fields {
			padding-top: 4px;
		}
		@media (max-width: 720px) {
			.bm-vendor-access {
				grid-template-columns: 1fr;
			}
			.bm-vendor-signup-card {
				grid-template-columns: 1fr;
			}
			.bm-vendor-type-options {
				grid-template-columns: 1fr;
			}
		}
		'
	);
}

add_action( 'wp_footer', 'beautyin_vendor_workflow_script', 30 );
function beautyin_vendor_workflow_script() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var vendorRegisterPanel = document.getElementById('bm-vendor-register-panel');
		if (vendorRegisterPanel) {
			vendorRegisterPanel.classList.add('is-open');
			if (window.location.hash === '#bm-vendor-register-panel') {
				vendorRegisterPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}

		document.querySelectorAll('.bm-vendor-type-box').forEach(function (box) {
			var intlFields = box.querySelector('.bm-international-vendor-fields');
			var domesticFields = box.querySelector('.bm-domestic-vendor-fields');
			var radios = box.querySelectorAll('input[name="beautyin_vendor_type"]');

			function sync() {
				var selected = box.querySelector('input[name="beautyin_vendor_type"]:checked');
				var isInternational = selected && selected.value === 'international';
				if (intlFields) {
					intlFields.hidden = !isInternational;
				}
				if (domesticFields) {
					domesticFields.hidden = isInternational;
				}
			}

			radios.forEach(function (radio) {
				radio.addEventListener('change', sync);
			});
			sync();
		});
	});
	</script>
	<?php
}
