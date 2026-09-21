<?php
/**
 * Beauty In marketplace UI layer.
 *
 * Adds a premium marketplace shell on top of Astra/WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BEAUTYIN_MARKETPLACE_MAIN_EMAIL', 'moabeauty@kilindia.in' );
define( 'BEAUTYIN_MARKETPLACE_MAIN_PHONE', '+91 60053 61564' );

if ( ! function_exists( 'beautyin_marketplace_main_phone' ) ) {
	function beautyin_marketplace_main_phone() {
		return BEAUTYIN_MARKETPLACE_MAIN_PHONE;
	}
}

if ( ! function_exists( 'beautyin_marketplace_main_phone_href' ) ) {
	function beautyin_marketplace_main_phone_href() {
		return '+916005361564';
	}
}

add_action( 'wp_enqueue_scripts', 'beautyin_marketplace_assets', 30 );
function beautyin_marketplace_assets() {
	wp_enqueue_style(
		'beautyin-marketplace-ui',
		content_url( 'mu-plugins/beautyin-marketplace-ui.css' ),
		array(),
		'1.7.0'
	);

	wp_register_script( 'beautyin-marketplace-ui', false, array(), '1.0.0', true );
	wp_enqueue_script( 'beautyin-marketplace-ui' );
	wp_add_inline_script( 'beautyin-marketplace-ui', beautyin_marketplace_inline_script() );
}

add_action( 'init', 'beautyin_marketplace_ensure_core_pages', 20 );
add_filter( 'pre_option_woocommerce_enable_guest_checkout', 'beautyin_marketplace_disable_guest_checkout' );
add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', 'beautyin_marketplace_return_yes' );
add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', 'beautyin_marketplace_return_yes' );
add_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
add_filter( 'woocommerce_checkout_registration_required', '__return_true' );
add_filter( 'woocommerce_login_redirect', 'beautyin_marketplace_login_redirect', 10, 2 );
add_action( 'template_redirect', 'beautyin_marketplace_handle_wishlist_action', 8 );
add_action( 'template_redirect', 'beautyin_marketplace_purchase_gate' );
add_filter( 'cartflows_global_checkout_url', 'beautyin_marketplace_global_checkout_url', 20 );
add_action( 'woocommerce_after_add_to_cart_button', 'beautyin_marketplace_single_buy_now_button' );
add_action( 'woocommerce_after_add_to_cart_button', 'beautyin_marketplace_single_wishlist_button' );
add_action( 'woocommerce_single_product_summary', 'beautyin_marketplace_product_value_stack', 7 );
add_action( 'woocommerce_single_product_summary', 'beautyin_marketplace_product_marketplace_box', 35 );
add_action( 'woocommerce_after_single_product_summary', 'beautyin_marketplace_product_assurance_panel', 8 );
add_action( 'woocommerce_before_main_content', 'beautyin_marketplace_shop_hero', 4 );
add_action( 'astra_content_before', 'beautyin_marketplace_shop_hero_fallback', 7 );
add_action( 'woocommerce_before_shop_loop_item_title', 'beautyin_marketplace_loop_badges', 8 );
add_action( 'woocommerce_after_shop_loop_item', 'beautyin_marketplace_loop_wishlist_button', 8 );
add_action( 'woocommerce_after_shop_loop_item', 'beautyin_marketplace_loop_buy_now_button', 12 );
add_action( 'woocommerce_after_shop_loop_item', 'beautyin_marketplace_loop_action_row', 20 );
add_action( 'woocommerce_review_order_before_payment', 'beautyin_marketplace_checkout_international_terms' );
add_action( 'woocommerce_after_checkout_validation', 'beautyin_marketplace_validate_international_terms', 20, 2 );
add_filter( 'woocommerce_prices_include_tax', 'beautyin_marketplace_checkout_prices_include_tax', 20 );
add_filter( 'woocommerce_cart_display_prices_including_tax', 'beautyin_marketplace_checkout_display_prices_including_tax', 20 );
add_filter( 'woocommerce_cart_subtotal', 'beautyin_marketplace_checkout_subtotal_html', 20, 3 );
add_filter( 'woocommerce_cart_tax_totals', 'beautyin_marketplace_checkout_tax_totals', 20, 2 );
add_filter( 'woocommerce_cart_subtotal', 'beautyin_marketplace_cart_subtotal_html', 30, 3 );
add_filter( 'woocommerce_cart_tax_totals', 'beautyin_marketplace_cart_tax_totals', 30, 2 );
add_filter( 'woocommerce_default_gateway', 'beautyin_marketplace_default_payment_gateway', 20 );
add_filter( 'woocommerce_available_payment_gateways', 'beautyin_marketplace_prefer_razorpay_gateway', 20 );
add_filter( 'woocommerce_get_cart_url', 'beautyin_marketplace_cart_url', 20 );
add_filter( 'woocommerce_get_price_html', 'beautyin_marketplace_missing_price_html', 10, 2 );
add_filter( 'astra_get_option_single-product-enable-shipping', 'beautyin_marketplace_disable_single_product_shipping_badge', 999, 3 );
add_filter( 'astra_get_option_single-product-shipping-text', 'beautyin_marketplace_hide_single_product_shipping_badge_text', 999, 3 );
add_action( 'pre_get_posts', 'beautyin_marketplace_enforce_price_filter', 99 );
add_filter( 'woocommerce_price_filter_widget_min_amount', 'beautyin_marketplace_price_filter_min_amount' );
add_filter( 'woocommerce_price_filter_widget_max_amount', 'beautyin_marketplace_price_filter_max_amount' );
add_filter( 'woocommerce_product_categories_widget_args', 'beautyin_marketplace_product_category_widget_args' );
add_filter( 'woocommerce_product_categories_widget_dropdown_args', 'beautyin_marketplace_product_category_widget_args' );
add_filter( 'wp_mail_from', 'beautyin_marketplace_mail_from', 9999 );
add_filter( 'wp_mail_from_name', 'beautyin_marketplace_mail_from_name', 9999 );
add_filter( 'pre_option_admin_email', 'beautyin_marketplace_mail_from' );
add_filter( 'woocommerce_email_from_address', 'beautyin_marketplace_mail_from' );
add_filter( 'woocommerce_email_from_name', 'beautyin_marketplace_mail_from_name' );
add_filter( 'woocommerce_email_recipient_new_order', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_cancelled_order', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_failed_order', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_low_stock', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_no_stock', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_backorder', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'dokan_email_admin_mail', 'beautyin_marketplace_mail_from' );
add_filter( 'woocommerce_email_recipient_dokan_new_seller', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_dokan_new_product', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_dokan_new_product_pending', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_dokan_vendor_withdraw_request', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_dokan_refund_request', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_filter( 'woocommerce_email_recipient_dokan_updated_product', 'beautyin_marketplace_admin_mail_recipient', 9999 );
add_action( 'woocommerce_product_options_general_product_data', 'beautyin_marketplace_product_flags_fields' );
add_action( 'woocommerce_admin_process_product_object', 'beautyin_marketplace_save_product_flags' );
add_action( 'woocommerce_product_query', 'beautyin_marketplace_filter_product_query' );
add_filter( 'woocommerce_shortcode_products_query', 'beautyin_marketplace_filter_shortcode_product_query', 10, 2 );
remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
add_action( 'woocommerce_no_products_found', 'beautyin_marketplace_no_products_found', 10 );

function beautyin_marketplace_main_email() {
	return BEAUTYIN_MARKETPLACE_MAIN_EMAIL;
}

function beautyin_marketplace_mail_from() {
	return beautyin_marketplace_main_email();
}

function beautyin_marketplace_mail_from_name() {
	return 'MOA Beauty';
}

function beautyin_marketplace_admin_mail_recipient( $recipient ) {
	return beautyin_marketplace_main_email();
}

function beautyin_marketplace_disable_single_product_shipping_badge( $value ) {
	return false;
}

function beautyin_marketplace_hide_single_product_shipping_badge_text( $value ) {
	return '';
}

function beautyin_marketplace_current_user_is_vendor() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}

	$user_id = get_current_user_id();
	if ( function_exists( 'dokan_is_user_seller' ) && dokan_is_user_seller( $user_id ) ) {
		return true;
	}

	$user = wp_get_current_user();
	return $user && in_array( 'seller', (array) $user->roles, true );
}

function beautyin_marketplace_ensure_core_pages() {
	if ( get_option( 'beautyin_marketplace_core_pages_v5' ) ) {
		return;
	}

	$pages = array(
		'about-us'             => 'About Us',
		'cart'                 => 'Cart',
		'korean-beauty-india'  => 'Korean Beauty in India',
		'korean-beauty-delhi'  => 'Korean Beauty in Delhi',
		'korean-beauty-mumbai' => 'Korean Beauty in Mumbai',
		'korean-beauty-goa'    => 'Korean Beauty in Goa',
		'korean-beauty-punjab'  => 'Korean Beauty in Punjab',
		'customer-guide'       => 'Customer Guide',
		'domestic-vendor'      => 'Domestic Vendor',
		'international-vendor' => 'International Vendor',
		'seller-policy'        => 'Seller Policy',
		'vendor-payouts'       => 'Vendor Payouts',
		'gst-customs'          => 'GST, Tax and Customs',
		'shipping-policy'      => 'Shipping Policy',
		'returns-refunds'      => 'Returns and Refunds',
	);

	foreach ( $pages as $slug => $title ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $title,
			)
		);
	}

	update_option( 'beautyin_marketplace_core_pages_v5', 1 );
}

function beautyin_marketplace_disable_guest_checkout() {
	return 'no';
}

function beautyin_marketplace_cart_url( $url ) {
	$cart_page = beautyin_marketplace_page_url( 'cart', '/cart/' );
	return $cart_page ? $cart_page : $url;
}

function beautyin_marketplace_return_yes() {
	return 'yes';
}

function beautyin_marketplace_login_redirect( $redirect, $user ) {
	if ( ! empty( $_REQUEST['redirect'] ) ) {
		$requested = esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) );
		if ( $requested ) {
			return $requested;
		}
	}

	return $redirect;
}

function beautyin_marketplace_purchase_gate() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( isset( $_GET['bm_buy_now'] ) ) {
		beautyin_marketplace_handle_buy_now();
		return;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_user_logged_in() ) {
		beautyin_marketplace_redirect_to_login( beautyin_page_suite_checkout_url(), 'Please login or create an account before checkout.' );
	}
}

function beautyin_marketplace_global_checkout_url( $url ) {
	$checkout_step_id = beautyin_marketplace_get_cartflows_checkout_step_id();
	if ( $checkout_step_id ) {
		$permalink = get_permalink( $checkout_step_id );
		if ( $permalink ) {
			return $permalink;
		}
	}

	return $url;
}

function beautyin_marketplace_get_cartflows_checkout_step_id() {
	$flow_id = absint( get_option( '_cartflows_store_checkout' ) );
	if ( ! $flow_id ) {
		return 0;
	}

	$step_ids = get_posts(
		array(
			'post_type'        => 'cartflows_step',
			'post_status'      => 'publish',
			'fields'           => 'ids',
			'numberposts'      => 20,
			'orderby'          => 'menu_order',
			'order'            => 'ASC',
			'meta_query'       => array(
				array(
					'key'     => 'wcf-flow-id',
					'value'   => $flow_id,
					'compare' => '=',
				),
				array(
					'key'     => 'wcf-step-type',
					'value'   => 'checkout',
					'compare' => '=',
				),
			),
			'suppress_filters' => true,
		)
	);

	if ( empty( $step_ids ) ) {
		return 0;
	}

	return absint( $step_ids[0] );
}

add_action( 'template_redirect', 'beautyin_marketplace_redirect_old_cartflows_checkout', 5 );
function beautyin_marketplace_redirect_old_cartflows_checkout() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( false === strpos( $request_uri, '/step/checkout-2' ) ) {
		return;
	}

	$target = beautyin_marketplace_global_checkout_url( beautyin_page_suite_checkout_url() );
	if ( $target ) {
		wp_safe_redirect( $target, 301 );
		exit;
	}
}

add_action( 'template_redirect', 'beautyin_marketplace_redirect_old_cart', 6 );
function beautyin_marketplace_redirect_old_cart() {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_cart' ) ) {
		return;
	}

	if ( is_cart() && ! is_page( array( 'cart', 'cart-2' ) ) ) {
		wp_safe_redirect( beautyin_marketplace_page_url( 'cart', '/cart/' ), 301 );
		exit;
	}
}

function beautyin_marketplace_checkout_prices_include_tax( $include ) {
	if ( ! beautyin_marketplace_is_checkout_context() ) {
		return $include;
	}

	return true;
}

function beautyin_marketplace_checkout_display_prices_including_tax( $display ) {
	if ( ! beautyin_marketplace_is_checkout_context() ) {
		return $display;
	}

	return true;
}

function beautyin_marketplace_checkout_subtotal_html( $html, $compound = false, $cart = null ) {
	return $html;
}

function beautyin_marketplace_checkout_tax_totals( $tax_totals, $cart ) {
	if ( ! beautyin_marketplace_is_checkout_context() ) {
		return $tax_totals;
	}

	return array();
}

function beautyin_marketplace_cart_subtotal_html( $html, $compound = false, $cart = null ) {
	return $html;
}

function beautyin_marketplace_cart_tax_totals( $tax_totals, $cart ) {
	if ( ! is_cart() ) {
		return $tax_totals;
	}

	return array();
}

function beautyin_marketplace_default_payment_gateway( $default_gateway ) {
	if ( ! beautyin_marketplace_is_checkout_context() ) {
		return $default_gateway;
	}

	if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( isset( $gateways['razorpay'] ) ) {
			if ( WC()->session ) {
				WC()->session->set( 'chosen_payment_method', 'razorpay' );
			}
			return 'razorpay';
		}
	}

	return $default_gateway;
}

function beautyin_marketplace_prefer_razorpay_gateway( $gateways ) {
	if ( ! beautyin_marketplace_is_checkout_context() || ! is_array( $gateways ) ) {
		return $gateways;
	}

	if ( ! isset( $gateways['razorpay'] ) ) {
		return $gateways;
	}

	$chosen = '';
	if ( isset( $_POST['payment_method'] ) ) {
		$chosen = sanitize_text_field( wp_unslash( $_POST['payment_method'] ) );
	}
	if ( ! $chosen && function_exists( 'WC' ) && WC()->session ) {
		$chosen = (string) WC()->session->get( 'chosen_payment_method', '' );
	}

	if ( isset( $gateways['razorpay'] ) && 'razorpay' !== $chosen ) {
		$chosen = 'razorpay';
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'chosen_payment_method', 'razorpay' );
		}
	}

	if ( isset( $gateways['cod'] ) ) {
		unset( $gateways['cod'] );
	}

	foreach ( $gateways as $gateway_id => $gateway ) {
		if ( is_object( $gateway ) && property_exists( $gateway, 'chosen' ) ) {
			$gateway->chosen = ( $gateway_id === $chosen );
		}
	}

	if ( isset( $gateways['razorpay'] ) ) {
		$preferred = array( 'razorpay' => $gateways['razorpay'] );
		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( 'razorpay' !== $gateway_id ) {
				$preferred[ $gateway_id ] = $gateway;
			}
		}
		$gateways = $preferred;
	}

	return $gateways;
}

function beautyin_marketplace_collection_slugs() {
	return array( 'new-arrivals', 'best-sellers' );
}

function beautyin_marketplace_collection_labels() {
	return array(
		'new-arrivals' => 'New arrivals',
		'best-sellers'  => 'Best sellers',
	);
}

function beautyin_marketplace_collection_meta_key( $collection ) {
	$map = array(
		'new-arrivals' => '_beautyin_new_arrival',
		'best-sellers' => '_beautyin_best_seller',
	);

	return $map[ $collection ] ?? '';
}

function beautyin_marketplace_current_collection() {
	$collection = isset( $_GET['bm_collection'] ) ? sanitize_key( wp_unslash( $_GET['bm_collection'] ) ) : '';
	if ( in_array( $collection, beautyin_marketplace_collection_slugs(), true ) ) {
		return $collection;
	}

	return '';
}

function beautyin_marketplace_collection_query_args( $collection, $args = array() ) {
	$collection = in_array( $collection, beautyin_marketplace_collection_slugs(), true ) ? $collection : '';
	if ( ! $collection ) {
		return $args;
	}

	$meta_key = beautyin_marketplace_collection_meta_key( $collection );
	if ( ! $meta_key ) {
		return $args;
	}

	$meta_query   = isset( $args['meta_query'] ) ? (array) $args['meta_query'] : array();
	$meta_query[]  = array(
		'key'     => $meta_key,
		'value'   => 'yes',
		'compare' => '=',
	);
	$args['meta_query'] = $meta_query;

	if ( 'new-arrivals' === $collection ) {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	} elseif ( 'best-sellers' === $collection ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = '_total_sales';
		$args['order']    = 'DESC';
	}

	return $args;
}

function beautyin_marketplace_collection_product_count() {
	global $wp_query;

	if ( ! $wp_query || ! isset( $wp_query->found_posts ) ) {
		return 0;
	}

	return max( 0, (int) $wp_query->found_posts );
}

function beautyin_marketplace_collection_meta_badge( $collection ) {
	$count = beautyin_marketplace_collection_product_count();
	if ( $count <= 0 ) {
		return '';
	}

	$labels = beautyin_marketplace_collection_labels();
	$label  = isset( $labels[ $collection ] ) ? $labels[ $collection ] : 'Collection';
	$copy   = sprintf(
		/* translators: %s is the number of products. */
		_n( '%s product', '%s products', $count, 'woocommerce' ),
		number_format_i18n( $count )
	);

	return '<div class="bm-collection-meta"><span class="bm-collection-badge">' . esc_html( $label ) . '</span><span class="bm-collection-count">' . esc_html( $copy ) . '</span></div>';
}

function beautyin_marketplace_filter_product_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_shop' ) ) {
		return;
	}

	if ( ! beautyin_marketplace_is_shop_context() ) {
		return;
	}

	$collection = beautyin_marketplace_current_collection();
	if ( ! $collection ) {
		return;
	}

	$args = beautyin_marketplace_collection_query_args(
		$collection,
		array(
			'meta_query' => (array) $query->get( 'meta_query' ),
		)
	);

	$query->set( 'meta_query', $args['meta_query'] ?? array() );
	if ( isset( $args['orderby'] ) ) {
		$query->set( 'orderby', $args['orderby'] );
	}
	if ( isset( $args['order'] ) ) {
		$query->set( 'order', $args['order'] );
	}
	if ( isset( $args['meta_key'] ) ) {
		$query->set( 'meta_key', $args['meta_key'] );
	}
}

function beautyin_marketplace_filter_shortcode_product_query( $args, $attributes ) {
	$collection = '';
	if ( ! empty( $attributes['bm_collection'] ) ) {
		$collection = sanitize_key( $attributes['bm_collection'] );
	}

	if ( ! $collection ) {
		$collection = beautyin_marketplace_current_collection();
	}

	if ( ! $collection ) {
		return $args;
	}

	return beautyin_marketplace_collection_query_args( $collection, $args );
}

function beautyin_marketplace_product_flags_fields() {
	echo '<div class="options_group">';

	woocommerce_wp_checkbox(
		array(
			'id'          => '_beautyin_new_arrival',
			'label'       => 'New arrival',
			'description' => 'Show this product in the New arrivals collection.',
		)
	);

	woocommerce_wp_checkbox(
		array(
			'id'          => '_beautyin_best_seller',
			'label'       => 'Best seller',
			'description' => 'Show this product in the Best sellers collection.',
		)
	);

	echo '</div>';
}

function beautyin_marketplace_save_product_flags( $product ) {
	$new_arrival = isset( $_POST['_beautyin_new_arrival'] ) ? 'yes' : 'no';
	$best_seller = isset( $_POST['_beautyin_best_seller'] ) ? 'yes' : 'no';

	$product->update_meta_data( '_beautyin_new_arrival', $new_arrival );
	$product->update_meta_data( '_beautyin_best_seller', $best_seller );
}

function beautyin_marketplace_no_products_found() {
	if ( ! beautyin_marketplace_is_shop_context() ) {
		wc_no_products_found();
		return;
	}

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$collection = beautyin_marketplace_current_collection();
	$labels = beautyin_marketplace_collection_labels();
	$title = $collection && isset( $labels[ $collection ] ) ? sprintf( 'No %s yet', strtolower( $labels[ $collection ] ) ) : 'No products found';
	$copy  = $collection
		? 'This collection is empty right now. Once a product is marked in wp-admin, it will appear here automatically.'
		: 'We could not find any matching products right now. Try another category or browse all products.';

	echo '<div class="bm-empty-state bm-empty-products">';
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<p>' . esc_html( $copy ) . '</p>';
	echo '<div class="bm-empty-actions">';
	echo '<a class="bm-page-btn" href="' . esc_url( $shop_url ) . '">Browse all products</a>';
	echo '<a class="bm-page-btn bm-page-btn-secondary" href="' . esc_url( add_query_arg( 'bm_collection', 'new-arrivals', $shop_url ) ) . '">New arrivals</a>';
	echo '<a class="bm-page-btn bm-page-btn-secondary" href="' . esc_url( add_query_arg( 'bm_collection', 'best-sellers', $shop_url ) ) . '">Best sellers</a>';
	echo '</div>';
	echo '</div>';
}

function beautyin_marketplace_handle_buy_now() {
	if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$product_id = absint( $_GET['bm_buy_now'] );
	$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! $product_id || ! wp_verify_nonce( $nonce, 'bm_buy_now_' . $product_id ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		wp_safe_redirect( $product ? $product->get_permalink() : wc_get_page_permalink( 'shop' ) );
		exit;
	}

	if ( ! is_user_logged_in() ) {
		beautyin_marketplace_redirect_to_login( beautyin_marketplace_buy_now_url( $product_id ), 'Login or register to buy this product.' );
	}

	if ( WC()->cart ) {
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product_id, 1 );
	}

	wp_safe_redirect( beautyin_page_suite_checkout_url() );
	exit;
}

function beautyin_marketplace_redirect_to_login( $redirect_url, $message = '' ) {
	if ( function_exists( 'wc_add_notice' ) && $message ) {
		wc_add_notice( $message, 'notice' );
	}

	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	wp_safe_redirect( add_query_arg( 'redirect', $redirect_url, $account_url ) );
	exit;
}

function beautyin_marketplace_buy_now_url( $product_id ) {
	return add_query_arg(
		array(
			'bm_buy_now' => absint( $product_id ),
			'_wpnonce'  => wp_create_nonce( 'bm_buy_now_' . absint( $product_id ) ),
		),
		home_url( '/' )
	);
}

function beautyin_marketplace_wishlist_url( $product_id ) {
	return add_query_arg(
		array(
			'bm_wishlist' => absint( $product_id ),
			'_wpnonce'   => wp_create_nonce( 'bm_wishlist_' . absint( $product_id ) ),
		),
		home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) )
	);
}

function beautyin_marketplace_get_wishlist_ids() {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), 'beautyin_wishlist_product_ids', true );
	} else {
		$ids = isset( $_COOKIE['beautyin_wishlist'] ) ? json_decode( wp_unslash( $_COOKIE['beautyin_wishlist'] ), true ) : array();
	}

	if ( ! is_array( $ids ) ) {
		return array();
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

function beautyin_marketplace_save_wishlist_ids( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'beautyin_wishlist_product_ids', $ids );
		return;
	}

	setcookie( 'beautyin_wishlist', wp_json_encode( $ids ), time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE['beautyin_wishlist'] = wp_json_encode( $ids );
}

function beautyin_marketplace_handle_wishlist_action() {
	if ( is_admin() || empty( $_GET['bm_wishlist'] ) ) {
		return;
	}

	$product_id = absint( $_GET['bm_wishlist'] );
	$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! $product_id || ! wp_verify_nonce( $nonce, 'bm_wishlist_' . $product_id ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}

	$ids = beautyin_marketplace_get_wishlist_ids();
	if ( in_array( $product_id, $ids, true ) ) {
		$ids = array_diff( $ids, array( $product_id ) );
		wc_add_notice( 'Removed from wishlist.', 'notice' );
	} else {
		$ids[] = $product_id;
		wc_add_notice( 'Saved to your wishlist.', 'success' );
	}

	beautyin_marketplace_save_wishlist_ids( $ids );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : wc_get_page_permalink( 'shop' ) );
	exit;
}

function beautyin_marketplace_wishlist_button( $product_id, $class = '' ) {
	$is_saved = in_array( absint( $product_id ), beautyin_marketplace_get_wishlist_ids(), true );
	$label    = $is_saved ? 'Saved' : 'Wishlist';
	return '<a class="bm-wishlist-button ' . esc_attr( $class ) . ( $is_saved ? ' is-saved' : '' ) . '" href="' . esc_url( beautyin_marketplace_wishlist_url( $product_id ) ) . '" aria-label="' . esc_attr( $label ) . ' product">' . esc_html( $label ) . '</a>';
}

function beautyin_marketplace_single_wishlist_button() {
	global $product;

	if ( ! $product ) {
		return;
	}

	echo beautyin_marketplace_wishlist_button( $product->get_id(), 'bm-single-wishlist' );
}

function beautyin_marketplace_product_is_international( $product_id ) {
	$author_id = (int) get_post_field( 'post_author', $product_id );
	return $author_id && 'international' === get_user_meta( $author_id, 'beautyin_vendor_type', true );
}

function beautyin_marketplace_cart_has_international_product() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = ! empty( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
		if ( $product_id && beautyin_marketplace_product_is_international( $product_id ) ) {
			return true;
		}
	}

	return false;
}

function beautyin_marketplace_loop_badges() {
	global $product;

	if ( ! $product ) {
		return;
	}

	if ( beautyin_marketplace_product_is_international( $product->get_id() ) ) {
		echo '<span class="bm-international-badge">International</span>';
	}
}

function beautyin_marketplace_loop_wishlist_button() {
	global $product;

	if ( ! $product ) {
		return;
	}

	echo beautyin_marketplace_wishlist_button( $product->get_id(), 'bm-loop-wishlist' );
}

function beautyin_marketplace_single_buy_now_button() {
	global $product;

	if ( ! $product || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	echo '<a class="button bm-single-buy-now" href="' . esc_url( beautyin_marketplace_buy_now_url( $product->get_id() ) ) . '">Buy now</a>';
}

function beautyin_marketplace_product_value_stack() {
	global $product;

	if ( ! $product ) {
		return;
	}

	$stock = $product->is_in_stock() ? 'In stock' : 'Currently unavailable';
	$is_international = beautyin_marketplace_product_is_international( $product->get_id() );
	echo '<div class="bm-product-value-stack" aria-label="Marketplace highlights">';
	echo '<span><strong>GST invoice</strong><small>Available after payment</small></span>';
	echo '<span><strong>' . esc_html( $is_international ? 'International product' : 'India delivery' ) . '</strong><small>' . esc_html( $is_international ? 'Customs and import terms apply' : 'Courier support by pincode' ) . '</small></span>';
	echo '<span><strong>' . esc_html( $stock ) . '</strong><small>Verified marketplace listing</small></span>';
	echo '</div>';
}

function beautyin_marketplace_product_marketplace_box() {
	global $product;

	if ( ! $product ) {
		return;
	}

	$sku = $product->get_sku() ? $product->get_sku() : 'KIL-' . $product->get_id();
	echo '<div class="bm-product-buy-box">';
	echo '<h3>Buy with KIL India confidence</h3>';
	echo '<ul>';
	echo '<li><strong>Pay securely</strong><span>Checkout supports configured online payment gateways.</span></li>';
	echo '<li><strong>Tax breakup</strong><span>GST, fees and shipping show during checkout before payment.</span></li>';
	if ( beautyin_marketplace_product_is_international( $product->get_id() ) ) {
		echo '<li><strong>Customs review</strong><span>Buyer must accept import duty, courier clearance and longer delivery terms at checkout.</span></li>';
	}
	echo '<li><strong>Order support</strong><span>Track orders, invoices and support from your account.</span></li>';
	echo '</ul>';
	echo '<div class="bm-product-meta-strip"><span>SKU: ' . esc_html( $sku ) . '</span><span>Sold through KIL marketplace</span></div>';
	echo '</div>';
}

function beautyin_marketplace_checkout_international_terms() {
	if ( ! beautyin_marketplace_cart_has_international_product() ) {
		return;
	}

	echo '<div class="bm-checkout-international-terms">';
	echo '<strong>International product terms</strong>';
	echo '<p>Your cart includes an international vendor product. Customs duty, import charges, clearance fees, document checks and longer delivery timelines may apply and can be payable before or during delivery.</p>';
	echo '<label><input type="checkbox" name="beautyin_accept_international_terms" value="1"> I understand and accept the customs, import-duty and international delivery terms for this order.</label>';
	echo '</div>';
}

function beautyin_marketplace_validate_international_terms( $data, $errors ) {
	if ( beautyin_marketplace_cart_has_international_product() && empty( $_POST['beautyin_accept_international_terms'] ) ) {
		$errors->add( 'beautyin_international_terms_required', 'Please accept international product customs and delivery terms before placing the order.' );
	}
}

function beautyin_marketplace_product_assurance_panel() {
	if ( ! is_product() ) {
		return;
	}

	echo '<section class="bm-product-assurance">';
	echo '<div><strong>Domestic sellers</strong><span>Products are listed through verified marketplace accounts and reviewed by the KIL India team.</span></div>';
	echo '<div><strong>International sellers</strong><span>Imported listings can include customs, duties and marketplace handling shown at checkout where applicable.</span></div>';
	echo '<div><strong>Invoice and order slip</strong><span>Customers and vendors can use order records after successful payment.</span></div>';
	echo '<div><strong>Need product help?</strong><span>Contact support before ordering for ingredients, stock or bulk requirements.</span></div>';
	echo '</section>';
}

function beautyin_marketplace_shop_hero() {
	if ( ! beautyin_marketplace_is_shop_context() ) {
		return;
	}

	global $beautyin_marketplace_shop_hero_done;
	if ( $beautyin_marketplace_shop_hero_done ) {
		return;
	}
	$beautyin_marketplace_shop_hero_done = true;

	if ( is_product_category() || is_product_tag() ) {
		$title = single_term_title( '', false );
		$copy  = term_description() ? wp_strip_all_tags( term_description() ) : 'Explore curated K-beauty products, seller picks and daily essentials.';
	} elseif ( is_search() ) {
		$title = 'Search results';
		$copy  = 'Browse matching products from the MOA Beauty marketplace.';
	} else {
		$title = 'Store';
		$copy  = 'Shop Korean skincare, cosmetics, personal care, face sheets, serums and curated imports.';
	}

	$after = '';
	$collection = beautyin_marketplace_current_collection();
	if ( $collection ) {
		$labels = beautyin_marketplace_collection_labels();
		if ( isset( $labels[ $collection ] ) ) {
			$title = $labels[ $collection ];
		}
		$copy  = 'Products are hand-selected from items tagged in wp-admin, so each collection stays clean and intentional.';
		$after = beautyin_marketplace_collection_meta_badge( $collection );
	}

	echo beautyin_marketplace_page_shell( 'Marketplace', $title, $copy, beautyin_marketplace_shop_quick_links(), $after );
}

function beautyin_marketplace_shop_hero_fallback() {
	if ( beautyin_marketplace_is_shop_context() ) {
		beautyin_marketplace_shop_hero();
	}
}

function beautyin_marketplace_is_shop_context() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	return is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() || ( is_search() && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) || ( $shop_id && is_page( $shop_id ) );
}

function beautyin_marketplace_shop_quick_links() {
	$links = array(
		array( 'All products', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ),
		array( 'Cosmetic', beautyin_marketplace_category_url( 'cosmetic', '/product-category/cosmetic/' ) ),
		array( 'Skincare', beautyin_marketplace_category_url( 'skincare', '/product-category/skincare/' ) ),
		array( 'Cream', beautyin_marketplace_category_url( 'cream', '/product-category/cream/' ) ),
		array( 'Serum', beautyin_marketplace_category_url( 'serum', '/product-category/serum/' ) ),
		array( 'Face Sheet', beautyin_marketplace_category_url( 'face-sheet', '/product-category/face-sheet/' ) ),
	);

	$html = '<div class="bm-shop-links">';
	foreach ( $links as $link ) {
		$html .= '<a href="' . esc_url( $link[1] ) . '">' . esc_html( $link[0] ) . '</a>';
	}
	$html .= '</div>';

	return $html;
}

function beautyin_marketplace_loop_buy_now_button() {
	if ( beautyin_marketplace_is_shop_context() || is_product() ) {
		return;
	}

	global $product;

	if ( ! $product || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() || '' === $product->get_price() ) {
		return;
	}

	echo '<a class="button bm-loop-buy-now" href="' . esc_url( beautyin_marketplace_buy_now_url( $product->get_id() ) ) . '">Buy now</a>';
}

function beautyin_marketplace_loop_action_row() {
	if ( ! beautyin_marketplace_is_shop_context() && ! is_product() ) {
		return;
	}

	global $product;

	if ( ! $product ) {
		return;
	}

	echo '<div class="bm-loop-actions">';

	if ( $product->is_purchasable() && $product->is_in_stock() && '' !== $product->get_price() ) {
		woocommerce_template_loop_add_to_cart();

		if ( $product->is_type( 'simple' ) ) {
			echo '<a class="button bm-loop-buy-now" href="' . esc_url( beautyin_marketplace_buy_now_url( $product->get_id() ) ) . '">Buy now</a>';
		}
	} else {
		echo '<a class="button bm-view-product" href="' . esc_url( $product->get_permalink() ) . '">View details</a>';
	}

	echo '</div>';
}

function beautyin_marketplace_missing_price_html( $price_html, $product ) {
	if ( is_admin() || ! $product || '' !== $product->get_price() || ! empty( $price_html ) ) {
		return $price_html;
	}

	return '<span class="bm-price-missing">Price unavailable</span>';
}

function beautyin_marketplace_enforce_price_filter( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_shop' ) ) {
		return;
	}

	$min_price = isset( $_GET['min_price'] ) ? wc_format_decimal( wp_unslash( $_GET['min_price'] ) ) : '';
	$max_price = isset( $_GET['max_price'] ) ? wc_format_decimal( wp_unslash( $_GET['max_price'] ) ) : '';

	if ( '' === $min_price && '' === $max_price ) {
		return;
	}

	if ( ! beautyin_marketplace_is_shop_context() ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );

	if ( '' !== $min_price && '' !== $max_price ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => array( (float) $min_price, (float) $max_price ),
			'compare' => 'BETWEEN',
			'type'    => 'DECIMAL(10,2)',
		);
	} elseif ( '' !== $min_price ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => (float) $min_price,
			'compare' => '>=',
			'type'    => 'DECIMAL(10,2)',
		);
	} else {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => (float) $max_price,
			'compare' => '<=',
			'type'    => 'DECIMAL(10,2)',
		);
	}

	$query->set( 'meta_query', $meta_query );
}

function beautyin_marketplace_price_filter_min_amount() {
	return 0;
}

function beautyin_marketplace_price_filter_max_amount( $max_price ) {
	global $wpdb;

	static $site_max_price = null;
	if ( null === $site_max_price ) {
		$lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
		$site_max_price = (float) $wpdb->get_var( "SELECT MAX(max_price) FROM {$lookup_table} WHERE max_price > 0" );
	}

	return $site_max_price > 0 ? ceil( $site_max_price / 10 ) * 10 : $max_price;
}

function beautyin_marketplace_product_category_widget_args( $args ) {
	$excluded = isset( $args['exclude'] ) && '' !== $args['exclude'] ? array_map( 'absint', explode( ',', (string) $args['exclude'] ) ) : array();

	foreach ( array( 'uncategorized-test', 'uncategorized' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$excluded[] = (int) $term->term_id;
		}
	}

	$args['exclude'] = implode( ',', array_unique( array_filter( $excluded ) ) );
	return $args;
}

function beautyin_marketplace_inline_script() {
	$change_address_url = function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' )
		? wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) )
		: home_url( '/my-account/edit-address/' );

	return "
document.addEventListener('DOMContentLoaded', function () {
	var shell = document.querySelector('.bm-shell');
	var menuToggle = document.querySelector('.bm-menu-toggle');
	if (shell && menuToggle) {
		menuToggle.addEventListener('click', function () {
			var isOpen = shell.classList.toggle('bm-mobile-open');
			menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
		});

		document.addEventListener('click', function (event) {
			if (!shell.classList.contains('bm-mobile-open') || shell.contains(event.target)) {
				return;
			}
			shell.classList.remove('bm-mobile-open');
			menuToggle.setAttribute('aria-expanded', 'false');
			menuToggle.setAttribute('aria-label', 'Open menu');
		});
	}

	var searchForms = document.querySelectorAll('.bm-search');
	searchForms.forEach(function (form) {
		var picker = form.querySelector('[data-bm-search-category]');
		if (picker) {
			var toggle = picker.querySelector('.bm-search-category-toggle');
			var label = toggle ? toggle.querySelector('span') : null;
			var menu = picker.querySelector('.bm-search-category-menu');
			var field = form.querySelector('[name=\"product_cat\"]');
			var options = menu ? menu.querySelectorAll('[data-value]') : [];

			function closePicker() {
				picker.classList.remove('is-open');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'false');
				}
			}

			function openPicker() {
				picker.classList.add('is-open');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'true');
				}
			}

			if (toggle && menu) {
				toggle.addEventListener('click', function (event) {
					event.preventDefault();
					event.stopPropagation();
					if (picker.classList.contains('is-open')) {
						closePicker();
					} else {
						openPicker();
					}
				});

				options.forEach(function (option) {
					option.addEventListener('click', function () {
						if (field) {
							field.disabled = false;
							field.value = option.getAttribute('data-value') || '';
						}
						if (label) {
							label.textContent = option.textContent.trim();
						}
						options.forEach(function (item) {
							item.setAttribute('aria-selected', item === option ? 'true' : 'false');
						});
						closePicker();
					});
				});

				document.addEventListener('click', function (event) {
					if (!picker.contains(event.target)) {
						closePicker();
					}
				});

				picker.addEventListener('keydown', function (event) {
					if (event.key === 'Escape') {
						closePicker();
						toggle.focus();
					}
				});
			}
		}

		form.addEventListener('submit', function () {
			var category = form.querySelector('[name=\"product_cat\"]');
			if (category && !category.value) {
				category.disabled = true;
			}
		});
	});

	var footerSearchForms = document.querySelectorAll('.bm-footer-search');
	footerSearchForms.forEach(function (form) {
		form.addEventListener('submit', function (event) {
			var input = form.querySelector('[name=\"s\"]');
			if (input && input.value.trim()) {
				return;
			}

			event.preventDefault();
			window.location.href = form.getAttribute('data-shop-url') || form.action;
		});
	});

	function prepareStoreNotice(notice) {
		if (!notice || notice.classList.contains('bm-notice-ready') || notice.classList.contains('cart-empty')) {
			return;
		}

		var isError = notice.classList.contains('woocommerce-error');
		notice.classList.add('bm-notice-ready');
		notice.setAttribute('role', isError ? 'alert' : 'status');

		var close = notice.querySelector('.bm-notice-close');
		if (!close) {
			close = document.createElement('button');
			close.type = 'button';
			close.className = 'bm-notice-close';
			close.setAttribute('aria-label', 'Dismiss notification');
			close.textContent = '×';
			notice.appendChild(close);
		}

		function dismiss() {
			if (notice.classList.contains('bm-notice-dismissing')) {
				return;
			}
			notice.classList.add('bm-notice-dismissing');
			window.setTimeout(function () {
				if (notice && notice.parentNode) {
					notice.parentNode.removeChild(notice);
				}
			}, 320);
		}

		close.addEventListener('click', dismiss);
		window.setTimeout(dismiss, isError ? 9000 : 5200);
	}

	function prepareStoreNotices(root) {
		(root || document).querySelectorAll('.woocommerce-notices-wrapper > .woocommerce-message, .woocommerce-notices-wrapper > .woocommerce-info, .woocommerce-notices-wrapper > .woocommerce-error').forEach(prepareStoreNotice);
	}

	prepareStoreNotices(document);
	if ('MutationObserver' in window) {
		var noticeObserver = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType === 1) {
						prepareStoreNotices(node);
						if (node.matches && node.matches('.woocommerce-message, .woocommerce-info, .woocommerce-error')) {
							prepareStoreNotice(node);
						}
					}
				});
			});
		});
		noticeObserver.observe(document.body, { childList: true, subtree: true });
	}

	var quantityFields = document.querySelectorAll('.single-product form.cart .quantity, .woocommerce-cart table.shop_table .quantity');
	quantityFields.forEach(function (quantity) {
		var input = quantity.querySelector('input.qty');
		if (!input || quantity.classList.contains('bm-qty-ready')) {
			return;
		}

		quantity.classList.add('bm-qty-ready');
		var minus = document.createElement('button');
		var plus = document.createElement('button');
		minus.type = 'button';
		plus.type = 'button';
		minus.className = 'bm-qty-btn bm-qty-minus';
		plus.className = 'bm-qty-btn bm-qty-plus';
		minus.setAttribute('aria-label', 'Decrease quantity');
		plus.setAttribute('aria-label', 'Increase quantity');
		minus.textContent = '-';
		plus.textContent = '+';
		quantity.insertBefore(minus, input);
		quantity.appendChild(plus);

		function changeQuantity(direction) {
			var step = parseFloat(input.getAttribute('step')) || 1;
			var min = parseFloat(input.getAttribute('min')) || 1;
			var max = parseFloat(input.getAttribute('max'));
			var current = parseFloat(input.value) || min;
			var next = current + (direction * step);
			next = Math.max(min, next);
			if (!isNaN(max)) {
				next = Math.min(max, next);
			}
			input.value = String(next);
			input.dispatchEvent(new Event('change', { bubbles: true }));
			var cartForm = input.closest('form.woocommerce-cart-form');
			if (cartForm) {
				var updateButton = cartForm.querySelector('button[name=\"update_cart\"]');
				if (updateButton) {
					updateButton.disabled = false;
					updateButton.removeAttribute('disabled');
					updateButton.classList.add('bm-update-ready');
				}
			}
		}

		minus.addEventListener('click', function () {
			changeQuantity(-1);
		});
		plus.addEventListener('click', function () {
			changeQuantity(1);
		});
	});

	var cartForm = document.querySelector('.woocommerce-cart form.woocommerce-cart-form');
	if (cartForm) {
		var cartRemoving = false;
		var removeLinks = cartForm.querySelectorAll('a.remove');
		var changeAddressLink = document.querySelector('.woocommerce-cart .cart_totals .shipping-calculator-button');
		var updateButton = cartForm.querySelector('button[name=\"update_cart\"]');

		if (updateButton && !updateButton.closest('.bm-update-cart-wrap')) {
			var updateWrap = document.createElement('div');
			updateWrap.className = 'bm-update-cart-wrap';
			updateButton.parentNode.insertBefore(updateWrap, updateButton);
			updateWrap.appendChild(updateButton);
		}

		function setRowRemoving(link) {
			var row = link && link.closest('tr');
			if (row) {
				row.classList.add('bm-row-removing');
			}
			if (link) {
				link.classList.add('bm-remove-pending');
				link.setAttribute('aria-busy', 'true');
			}
		}

		removeLinks.forEach(function (link) {
			link.classList.add('bm-remove-ready');
			link.setAttribute('title', 'Remove item');
			link.setAttribute('aria-label', 'Remove item');
			link.addEventListener('click', function (event) {
				if (cartRemoving) {
					event.preventDefault();
					return;
				}

				event.preventDefault();
				cartRemoving = true;
				setRowRemoving(link);

				var requestUrl = link.getAttribute('href');
				if (!requestUrl) {
					window.location.reload();
					return;
				}

				fetch(requestUrl, {
					credentials: 'same-origin',
					cache: 'no-store'
				}).then(function () {
					window.location.reload();
				}).catch(function () {
					window.location.href = requestUrl;
				});
			});
		});

		if (changeAddressLink) {
			var customChangeAddress = document.createElement('a');
			customChangeAddress.className = 'bm-change-address-link';
			customChangeAddress.href = '" . esc_url( $change_address_url ) . "';
			customChangeAddress.setAttribute('aria-label', 'Change shipping address');
			customChangeAddress.textContent = 'Change address';
			changeAddressLink.replaceWith(customChangeAddress);
		}
	}

	var rails = document.querySelectorAll('.bm-rail');
	rails.forEach(function (rail) {
		var scroller = rail.querySelector('.bm-products');
		var buttons = rail.querySelectorAll('.bm-rail-nav');
		if (!scroller || !buttons.length) {
			return;
		}

		function updateButtons() {
			var maxScroll = scroller.scrollWidth - scroller.clientWidth;
			var canScroll = maxScroll > 4;
			rail.classList.toggle('bm-rail-scrollable', canScroll);
			buttons.forEach(function (button) {
				var isPrev = button.classList.contains('bm-rail-prev');
				button.disabled = !canScroll || (isPrev ? scroller.scrollLeft <= 2 : scroller.scrollLeft >= maxScroll - 2);
			});
		}

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				var direction = button.classList.contains('bm-rail-prev') ? -1 : 1;
				var amount = Math.max(scroller.clientWidth * 0.75, 260);
				scroller.scrollBy({ left: direction * amount, behavior: 'smooth' });
			});
		});

		scroller.addEventListener('scroll', updateButtons, { passive: true });
		window.addEventListener('resize', updateButtons);
		updateButtons();
	});

	var customerLogin = document.querySelector('.woocommerce-account:not(.logged-in) #customer_login');
	if (customerLogin) {
		var registerColumn = customerLogin.querySelector('.u-column2');
		var loginColumn = customerLogin.querySelector('.u-column1');
		var hasRegisterError = customerLogin.querySelector('.u-column2 .woocommerce-error, .u-column2 .woocommerce-message');
		if (registerColumn && loginColumn && !customerLogin.querySelector('.bm-customer-register-prompt')) {
			var backToLogin = document.createElement('button');
			backToLogin.type = 'button';
			backToLogin.className = 'bm-back-to-login';
			backToLogin.textContent = 'Back to login';
			var registerHeading = registerColumn.querySelector('h2');
			if (registerHeading) {
				registerHeading.insertAdjacentElement('afterend', backToLogin);
			} else {
				registerColumn.insertBefore(backToLogin, registerColumn.firstChild);
			}
			backToLogin.addEventListener('click', function () {
				customerLogin.classList.remove('bm-show-register');
				if (window.history && window.history.replaceState && window.location.hash === '#register') {
					window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
				}
				loginColumn.scrollIntoView({ behavior: 'smooth', block: 'start' });
				var loginField = loginColumn.querySelector('input:not([type=\"hidden\"]), select, textarea');
				if (loginField) {
					setTimeout(function () { loginField.focus(); }, 320);
				}
			});

			var prompt = document.createElement('div');
			prompt.className = 'bm-customer-register-prompt';
			prompt.innerHTML = '<strong>New to Beauty In?</strong><span>Create a customer account to checkout faster, track orders and save your details.</span><button type=\"button\">Create account</button>';
			loginColumn.insertAdjacentElement('afterend', prompt);
			prompt.querySelector('button').addEventListener('click', function () {
				customerLogin.classList.add('bm-show-register');
				registerColumn.scrollIntoView({ behavior: 'smooth', block: 'start' });
				var firstField = registerColumn.querySelector('input:not([type=\"hidden\"]), select, textarea');
				if (firstField) {
					setTimeout(function () { firstField.focus(); }, 320);
				}
			});
		}
		if (window.location.hash === '#register' || hasRegisterError) {
			customerLogin.classList.add('bm-show-register');
		}
	}

	var vendorAreas = document.querySelectorAll('.single-product .dokan-vendor-info-wrap, .single-product #tab-seller');
	vendorAreas.forEach(function (area) {
		var nameTargets = area.querySelectorAll('.dokan-vendor-name h5, .store-name .details, .seller-name .details a, img[alt]');
		nameTargets.forEach(function (target) {
			if (target.tagName === 'IMG') {
				target.alt = 'MOA Beauty';
				return;
			}
			if (target.textContent.trim()) {
				target.textContent = 'MOA Beauty';
			}
		});

		area.querySelectorAll('.dokan-vendor-rating .dashicons').forEach(function (star) {
			star.classList.remove('dashicons-star-empty', 'dashicons-star-half');
			star.classList.add('dashicons-star-filled');
			star.setAttribute('aria-hidden', 'true');
		});

		var noRatings = area.querySelectorAll('li.clearfix');
		noRatings.forEach(function (item) {
			if (item.textContent.indexOf('No ratings') !== -1) {
				item.textContent = '5.00 rating';
			}
		});
	});

	var lightboxTargets = document.querySelectorAll('.woocommerce-product-gallery img');
	if (lightboxTargets.length) {
		var lightbox = document.createElement('div');
		lightbox.className = 'bm-image-lightbox';
		lightbox.setAttribute('aria-hidden', 'true');
		lightbox.innerHTML = '<button type=\"button\" class=\"bm-lightbox-close\" aria-label=\"Close image\">&times;</button><img alt=\"\" />';
		document.body.appendChild(lightbox);

		var lightboxImage = lightbox.querySelector('img');
		var closeLightbox = function () {
			lightbox.classList.remove('bm-lightbox-open');
			lightbox.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('bm-lightbox-locked');
			lightboxImage.removeAttribute('src');
		};

		lightboxTargets.forEach(function (img) {
			img.classList.add('bm-clickable-image');
			img.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var src = img.getAttribute('data-large_image') || img.currentSrc || img.src;
				if (!src) {
					return;
				}
				lightboxImage.src = src;
				lightboxImage.alt = img.alt || 'Product image';
				lightbox.classList.add('bm-lightbox-open');
				lightbox.setAttribute('aria-hidden', 'false');
				document.body.classList.add('bm-lightbox-locked');
			});
		});

		lightbox.addEventListener('click', function (event) {
			if (event.target === lightbox || event.target.classList.contains('bm-lightbox-close')) {
				closeLightbox();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && lightbox.classList.contains('bm-lightbox-open')) {
				closeLightbox();
			}
		});
	}
});
";
}

function beautyin_marketplace_logo_url() {
	$logo = WP_CONTENT_DIR . '/uploads/2026/02/e2e8a9033661f.png';
	if ( file_exists( $logo ) ) {
		return content_url( 'uploads/2026/02/e2e8a9033661f.png' );
	}

	return '';
}

function beautyin_marketplace_moa_logo_url() {
	$logo = WP_CONTENT_DIR . '/uploads/2026/01/logo-1.png';
	if ( file_exists( $logo ) ) {
		return content_url( 'uploads/2026/01/logo-1.png' );
	}

	return '';
}

function beautyin_marketplace_page_url( $slug, $fallback = '' ) {
	$page = get_page_by_path( $slug );
	if ( $page && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}

	return $fallback ? home_url( $fallback ) : home_url( '/' . trim( $slug, '/' ) . '/' );
}

function beautyin_marketplace_category_url( $slug, $fallback = '' ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return $fallback ? home_url( $fallback ) : home_url( '/shop/' );
}

function beautyin_marketplace_icon( $name ) {
	$icons = array(
		'user'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>',
		'heart'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"></path></svg>',
		'package'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7.5 4.3 9 5.2"></path><path d="m7.5 19.7 9-5.2"></path><path d="M3 7.5 12 2l9 5.5v9L12 22l-9-5.5z"></path><path d="M12 12 3.3 7.7"></path><path d="M12 12v10"></path><path d="m12 12 8.7-4.3"></path></svg>',
		'bag'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8h12l1 13H5z"></path><path d="M9 8a3 3 0 0 1 6 0"></path></svg>',
	);

	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

add_filter( 'body_class', 'beautyin_marketplace_body_class' );
function beautyin_marketplace_body_class( $classes ) {
	$classes[] = 'beautyin-marketplace';
	if ( is_front_page() ) {
		$classes[] = 'beautyin-marketplace-home';
	}
	if ( is_page( 'cart' ) || is_page( 'cart-2' ) ) {
		$classes[] = 'beautyin-cart-two';
	}
	if ( beautyin_marketplace_is_shop_context() ) {
		$classes[] = 'beautyin-marketplace-shop';
	}
	if ( beautyin_marketplace_is_static_page_context() ) {
		$classes[] = 'beautyin-static-page';
	}
	if ( beautyin_marketplace_is_checkout_context() ) {
		$classes[] = 'beautyin-checkout';
	}
	return $classes;
}

function beautyin_marketplace_is_checkout_context() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	$page = get_post();
	if ( ! $page ) {
		return false;
	}

	return 'checkout' === get_post_meta( $page->ID, 'wcf-step-type', true )
		|| false !== strpos( (string) $page->post_name, 'checkout' );
}

function beautyin_marketplace_is_static_page_context() {
	if ( ! is_page() || is_front_page() ) {
		return false;
	}

	$page = get_post();
	if ( ! $page ) {
		return false;
	}

	return in_array(
		$page->post_name,
		array(
			'contact-us',
			'support',
			'privacy-policy',
			'terms-and-conditions',
			'request-for-quote',
			'bulk-quote',
			'wishlist',
			'track-your-order',
			'delivery-across-india',
			'k-beauty-marketplace',
		),
		true
	);
}

add_action( 'wp_body_open', 'beautyin_marketplace_header', 5 );
add_action( 'astra_header_before', 'beautyin_marketplace_header', 5 );
function beautyin_marketplace_header() {
	if ( is_admin() ) {
		return;
	}

	global $beautyin_marketplace_header_done;
	if ( $beautyin_marketplace_header_done ) {
		return;
	}
	$beautyin_marketplace_header_done = true;

	$cart_count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$cart_total = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_total() : '';
	$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	$account    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$wishlist   = beautyin_marketplace_page_url( 'wishlist', '/wishlist/' );
	$cats       = beautyin_marketplace_categories( 9 );
	$moa_logo   = beautyin_marketplace_moa_logo_url();
	$selected_product_cat = isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : '';
	$selected_cat_label   = 'All';
	foreach ( $cats as $cat ) {
		if ( $selected_product_cat && $selected_product_cat === $cat->slug ) {
			$selected_cat_label = $cat->name;
			break;
		}
	}
	$dashboard_url = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url() : home_url( '/dashboard/' );
	$sell_with_us_url = beautyin_marketplace_current_user_is_vendor() ? $dashboard_url : beautyin_marketplace_page_url( 'vendor-registration', '/vendor-registration/' );
	$current_user = wp_get_current_user();
	$account_label = 'Login / Sign in';
	$account_kicker = 'Account';
	if ( is_user_logged_in() && $current_user && $current_user->exists() ) {
		$display_name  = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
		$first_name    = trim( (string) $current_user->first_name );
		$account_label = 'Hello, ' . ( $first_name ? $first_name : $display_name );
		$account_kicker = 'Your account';
	}
	?>
	<header class="bm-shell" role="banner">
		<div class="bm-header-inner">
			<div class="bm-topbar">
				<a class="bm-delivery" href="<?php echo esc_url( beautyin_marketplace_page_url( 'delivery-across-india', '/delivery-across-india/' ) ); ?>">
					<span>Delivering across India</span>
					<strong>K-beauty marketplace</strong>
				</a>
				<nav class="bm-toplinks" aria-label="Quick links">
					<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'track-order', '/track-order/' ) ); ?>">Track order</a>
					<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'bulk-quote', '/bulk-quote/' ) ); ?>">Bulk quote</a>
					<a href="<?php echo esc_url( $sell_with_us_url ); ?>">Sell with us</a>
					<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'about-us', '/about-us/' ) ); ?>">About us</a>
					<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ); ?>">Contact us</a>
				</nav>
			</div>
			<div class="bm-mainbar">
				<a class="bm-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<?php if ( $moa_logo ) : ?>
						<img class="bm-logo bm-logo-moa" src="<?php echo esc_url( $moa_logo ); ?>" alt="MOA Beauty" />
					<?php elseif ( has_custom_logo() ) : ?>
						<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'bm-logo' ) ); ?>
					<?php else : ?>
						<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<?php endif; ?>
				</a>
				<button class="bm-menu-toggle" type="button" aria-label="Open menu" aria-controls="bm-mobile-menu" aria-expanded="false">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<a class="bm-location" href="<?php echo esc_url( beautyin_marketplace_page_url( 'delivery-across-india', '/delivery-across-india/' ) ); ?>">
					<span>Deliver to</span>
					<strong>India</strong>
				</a>
				<form class="bm-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<select name="product_cat" aria-label="Product category">
						<option value="" <?php selected( $selected_product_cat, '' ); ?>>All</option>
						<?php foreach ( $cats as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $selected_product_cat, $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="search" name="s" placeholder="Search Korean skincare, makeup, serums, masks..." value="<?php echo esc_attr( get_search_query() ); ?>" />
					<input type="hidden" name="post_type" value="product" />
					<button type="submit" aria-label="Search">Search</button>
				</form>
				<div class="bm-actions">
					<div class="bm-account-menu">
						<a class="bm-action-card" href="<?php echo esc_url( $account ); ?>" aria-haspopup="true">
							<?php echo beautyin_marketplace_icon( 'user' ); ?>
							<span><em><?php echo esc_html( $account_kicker ); ?></em><strong><?php echo esc_html( $account_label ); ?></strong></span>
						</a>
						<?php if ( is_user_logged_in() ) : ?>
							<div class="bm-account-dropdown" role="menu">
								<a href="<?php echo esc_url( $account ); ?>" role="menuitem">My account</a>
								<a href="<?php echo esc_url( $account . 'orders/' ); ?>" role="menuitem">Orders</a>
								<a class="bm-account-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" role="menuitem">Logout</a>
							</div>
						<?php endif; ?>
					</div>
					<a class="bm-action-card" href="<?php echo esc_url( $wishlist ); ?>">
						<?php echo beautyin_marketplace_icon( 'heart' ); ?>
						<span><em>Save</em><strong>Wishlist</strong></span>
					</a>
					<a class="bm-action-card" href="<?php echo esc_url( $account . 'orders/' ); ?>">
						<?php echo beautyin_marketplace_icon( 'package' ); ?>
						<span><em>Track</em><strong>Orders</strong></span>
					</a>
					<a class="bm-action-card bm-cart" href="<?php echo esc_url( $cart_url ); ?>">
						<?php echo beautyin_marketplace_icon( 'bag' ); ?>
						<span class="bm-cart-count"><?php echo esc_html( $cart_count ); ?></span>
						<span><em>Basket</em><strong>Cart</strong></span>
					</a>
				</div>
			</div>
			<nav class="bm-catbar" id="bm-mobile-menu" aria-label="Product categories">
				<a class="bm-cat-all" href="<?php echo esc_url( $shop_url ); ?>">Explore all</a>
				<a class="bm-cat-feature" href="<?php echo esc_url( add_query_arg( 'bm_collection', 'new-arrivals', wc_get_page_permalink( 'shop' ) ) ); ?>">New arrivals</a>
				<a class="bm-cat-feature" href="<?php echo esc_url( add_query_arg( 'bm_collection', 'best-sellers', wc_get_page_permalink( 'shop' ) ) ); ?>">Best sellers</a>
				<?php foreach ( beautyin_marketplace_nav_categories() as $item ) : ?>
					<?php if ( ! empty( $item['children'] ) ) : ?>
						<div class="bm-nav-item bm-has-menu">
							<a href="<?php echo esc_url( get_term_link( $item['term'] ) ); ?>">
								<?php echo esc_html( beautyin_marketplace_nav_label( $item['term']->name, $item['term']->slug ) ); ?>
								<span class="bm-nav-arrow" aria-hidden="true"></span>
							</a>
							<div class="bm-submenu">
								<a class="bm-submenu-title" href="<?php echo esc_url( get_term_link( $item['term'] ) ); ?>">All <?php echo esc_html( beautyin_marketplace_nav_label( $item['term']->name, $item['term']->slug ) ); ?></a>
								<?php foreach ( $item['children'] as $child ) : ?>
									<a href="<?php echo esc_url( get_term_link( $child ) ); ?>"><?php echo esc_html( $child->name ); ?> <span><?php echo esc_html( $child->count ); ?></span></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php else : ?>
						<a href="<?php echo esc_url( get_term_link( $item['term'] ) ); ?>"><?php echo esc_html( beautyin_marketplace_nav_label( $item['term']->name, $item['term']->slug ) ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
		</div>
	</header>
	<?php
}

add_action( 'astra_footer_before', 'beautyin_marketplace_footer', 5 );
add_action( 'wp_footer', 'beautyin_marketplace_footer', 5 );
function beautyin_marketplace_footer() {
	if ( is_admin() ) {
		return;
	}

	global $beautyin_marketplace_footer_done;
	if ( $beautyin_marketplace_footer_done ) {
		return;
	}
	$beautyin_marketplace_footer_done = true;

	$account  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$dashboard_url = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url() : home_url( '/dashboard/' );
	$moa_logo = beautyin_marketplace_moa_logo_url();
	$phone_label = beautyin_marketplace_main_phone();
	$phone_href   = beautyin_marketplace_main_phone_href();
	?>
	<footer class="bm-footer" role="contentinfo">
		<div class="bm-footer-promises">
			<span><strong>Fast India Delivery</strong><small>Beauty essentials at your doorstep</small></span>
			<span><strong>Secure Payments</strong><small>Cards, UPI and trusted gateways</small></span>
			<span><strong>Seller Marketplace</strong><small>Verified brands and vendors</small></span>
			<span><strong>Order Support</strong><small>Track orders from your account</small></span>
		</div>
		<div class="bm-footer-newsletter">
			<div>
				<span>MOA Beauty Club</span>
				<strong>Get new launches, seller offers and skincare edits.</strong>
			</div>
			<form class="bm-footer-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-shop-url="<?php echo esc_url( $shop_url ); ?>">
				<input type="search" name="s" placeholder="Search products or start shopping" aria-label="Search products or start shopping" />
				<input type="hidden" name="post_type" value="product" />
				<button type="submit">Explore</button>
			</form>
		</div>
		<div class="bm-footer-grid">
			<section class="bm-footer-brand">
				<?php if ( $moa_logo ) : ?>
					<img class="bm-footer-moa" src="<?php echo esc_url( $moa_logo ); ?>" alt="MOA Beauty" />
				<?php elseif ( has_custom_logo() ) : ?>
					<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'bm-footer-moa' ) ); ?>
				<?php endif; ?>
				<p>MOA Beauty brings Korean beauty, skincare and lifestyle products into one curated marketplace for Indian customers.</p>
				<div class="bm-trust-row">
					<span>Secure checkout</span>
					<span>Seller marketplace</span>
					<span>India delivery</span>
				</div>
				<a class="bm-footer-brand-cta" href="<?php echo esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ); ?>">Talk to support</a>
			</section>
			<section class="bm-footer-contact">
				<h3>Contact</h3>
				<div class="bm-footer-callout">
					<span>Primary number</span>
					<strong>Fast support for orders, vendors and bulk quotes</strong>
				</div>
				<a href="tel:<?php echo esc_attr( $phone_href ); ?>"><?php echo esc_html( $phone_label ); ?></a>
				<a href="mailto:<?php echo esc_attr( beautyin_marketplace_main_email() ); ?>"><?php echo esc_html( beautyin_marketplace_main_email() ); ?></a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ); ?>">Contact page</a>
				<small style="display:block;margin-top:10px;opacity:.8;">Mon-Sat, 10:00 AM - 6:00 PM IST</small>
			</section>
			<section class="bm-footer-shop">
				<h3>Shop</h3>
				<a href="<?php echo esc_url( $shop_url ); ?>">All products</a>
				<a href="<?php echo esc_url( beautyin_marketplace_category_url( 'cosmetic', '/product-category/cosmetic/' ) ); ?>">Cosmetic</a>
				<a href="<?php echo esc_url( beautyin_marketplace_category_url( 'cream', '/product-category/cream/' ) ); ?>">Cream</a>
				<a href="<?php echo esc_url( beautyin_marketplace_category_url( 'serum', '/product-category/serum/' ) ); ?>">Serum</a>
				<a href="<?php echo esc_url( beautyin_marketplace_category_url( 'face-sheet', '/product-category/face-sheet/' ) ); ?>">Face Sheet</a>
				<a href="<?php echo esc_url( beautyin_marketplace_category_url( 'personal-care', '/product-category/personal-care/' ) ); ?>">Personal Care</a>
			</section>
			<section class="bm-footer-customer">
				<h3>Customer Care</h3>
				<a href="<?php echo esc_url( $account ); ?>">My account</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'track-order', '/track-order/' ) ); ?>">Track order</a>
				<a href="<?php echo esc_url( $cart_url ); ?>">Cart</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ); ?>">Contact us</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'about-us', '/about-us/' ) ); ?>">About us</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'privacy-policy', '/privacy-policy/' ) ); ?>">Privacy policy</a>
			</section>
			<section class="bm-footer-sell">
				<h3>Sell With MOA Beauty</h3>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'vendor-registration', '/vendor-registration/' ) ); ?>">Vendor registration</a>
				<a href="<?php echo esc_url( $account ); ?>">Vendor login</a>
				<a href="<?php echo esc_url( $dashboard_url ); ?>">Vendor dashboard</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'request-for-quote', '/request-for-quote/' ) ); ?>">Request for quote</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'terms-and-conditions', '/terms-and-conditions/' ) ); ?>">Terms and conditions</a>
			</section>
		</div>
		<div class="bm-footer-bottom">
			<span>Copyright &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> MOA Beauty.</span>
			<div>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'about-us', '/about-us/' ) ); ?>">About us</a>
				<a href="<?php echo esc_url( $shop_url ); ?>">Shop</a>
				<a href="<?php echo esc_url( beautyin_marketplace_page_url( 'support', '/support/' ) ); ?>">Support</a>
			</div>
		</div>
	</footer>
	<?php
}

add_action( 'wp_footer', 'beautyin_marketplace_checkout_header_fallback', 4 );
function beautyin_marketplace_checkout_header_fallback() {
	if ( is_admin() || ! beautyin_marketplace_is_checkout_context() ) {
		return;
	}

	beautyin_marketplace_header();
}

add_action( 'wp_footer', 'beautyin_marketplace_cartflows_shell_relocator', 6 );
function beautyin_marketplace_cartflows_shell_relocator() {
	if ( ! beautyin_marketplace_is_checkout_context() ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		document.body.classList.add('beautyin-marketplace', 'beautyin-checkout');

		var hidden = document.querySelector('.wcf-hide');
		var header = document.querySelector('.bm-shell');
		if (!header && hidden) {
			header = hidden.querySelector('.bm-shell');
		}
		if (header) {
			header.hidden = false;
			header.removeAttribute('aria-hidden');
			header.style.display = '';
			document.body.insertBefore(header, document.body.firstChild);
		}

		var footer = document.querySelector('.bm-footer');
		if (!footer && hidden) {
			footer = hidden.querySelector('.bm-footer');
		}
		if (footer) {
			footer.hidden = false;
			footer.removeAttribute('aria-hidden');
			footer.style.display = '';
			document.body.appendChild(footer);
		}
	});
	</script>
	<?php
}

add_filter( 'the_content', 'beautyin_marketplace_front_page', 5 );
function beautyin_marketplace_front_page( $content ) {
	if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( ! function_exists( 'wc_get_products' ) ) {
		return $content;
	}

	ob_start();
	beautyin_marketplace_home_markup();
	return ob_get_clean();
}

add_filter( 'the_content', 'beautyin_marketplace_static_page_content', 30 );
function beautyin_marketplace_static_page_content( $content ) {
	if ( is_admin() || is_front_page() || ! is_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$page = get_post();
	if ( ! $page ) {
		return $content;
	}

	$slug = $page->post_name;
	$templates = array(
		'about-us'              => array(
			'eyebrow' => 'About us',
			'title'   => 'MOA Beauty and KIL India',
			'copy'    => 'A curated Korean beauty marketplace built for Indian customers, sellers and everyday self-care shoppers.',
			'body'    => beautyin_marketplace_about_markup(),
		),
		'contact-us'            => array(
			'eyebrow' => 'Support',
			'title'   => 'Contact KIL India',
			'copy'    => 'Reach our team for orders, vendor questions, marketplace support and beauty product assistance.',
			'body'    => beautyin_marketplace_contact_markup(),
		),
		'support'               => array(
			'eyebrow' => 'Help center',
			'title'   => 'How can we help?',
			'copy'    => 'Find quick support for orders, delivery, checkout, returns, seller queries and bulk buying.',
			'body'    => beautyin_marketplace_support_markup(),
		),
		'privacy-policy'        => array(
			'eyebrow' => 'Policy',
			'title'   => 'Privacy Policy',
			'copy'    => 'Clear information on how customer, seller and order details are handled across this marketplace.',
			'body'    => beautyin_marketplace_policy_markup(),
		),
		'terms-and-conditions'  => array(
			'eyebrow' => 'Terms',
			'title'   => 'Terms and Conditions',
			'copy'    => 'Marketplace terms for shopping, seller participation, payment, delivery and support.',
			'body'    => beautyin_marketplace_terms_markup(),
		),
		'request-for-quote'     => array(
			'eyebrow' => 'Bulk buying',
			'title'   => 'Request for Quote',
			'copy'    => 'Share your product list and quantities for wholesale, salon, reseller or marketplace supply.',
			'body'    => beautyin_marketplace_quote_markup( 'Request a Quote' ),
		),
		'bulk-quote'            => array(
			'eyebrow' => 'Wholesale',
			'title'   => 'Bulk Quote',
			'copy'    => 'Get pricing support for larger orders and recurring beauty product requirements.',
			'body'    => beautyin_marketplace_quote_markup( 'Send Bulk Requirement' ),
		),
		'wishlist'              => array(
			'eyebrow' => 'Saved products',
			'title'   => 'Wishlist',
			'copy'    => 'Keep your favorite skincare, makeup and personal care picks ready for your next order.',
			'body'    => beautyin_marketplace_wishlist_markup(),
		),
		'track-your-order'      => array(
			'eyebrow' => 'Order support',
			'title'   => 'Track Your Order',
			'copy'    => 'Login to your account to view live order status, invoices and delivery updates.',
			'body'    => beautyin_marketplace_track_order_markup(),
		),
		'delivery-across-india' => array(
			'eyebrow' => 'Delivery',
			'title'   => 'Delivery Across India',
			'copy'    => 'Beauty essentials shipped across India through available seller and courier networks.',
			'body'    => beautyin_marketplace_delivery_markup(),
		),
		'k-beauty-marketplace'  => array(
			'eyebrow' => 'Marketplace',
			'title'   => 'K-beauty Marketplace',
			'copy'    => 'A curated destination for Korean skincare, makeup, face sheets, serums, creams and imports.',
			'body'    => beautyin_marketplace_kbeauty_markup(),
		),
	);

	if ( ! isset( $templates[ $slug ] ) ) {
		return $content;
	}

	$data = $templates[ $slug ];
	return beautyin_marketplace_page_shell( $data['eyebrow'], $data['title'], $data['copy'], $data['body'] );
}

function beautyin_marketplace_page_shell( $eyebrow, $title, $copy, $body, $after = '' ) {
	return sprintf(
		'<section class="bm-page-hero"><span>%s</span><h1>%s</h1><p>%s</p></section><section class="bm-page-panel">%s%s</section>',
		esc_html( $eyebrow ),
		esc_html( $title ),
		esc_html( $copy ),
		wp_kses_post( $body ),
		$after ? wp_kses_post( $after ) : ''
	);
}

function beautyin_marketplace_contact_markup() {
	$phone_label = beautyin_marketplace_main_phone();
	$phone_href  = beautyin_marketplace_main_phone_href();

	return '<div class="bm-contact-grid"><div class="bm-contact-card"><h2>Talk to us</h2><p>For product help, order support, vendor queries and bulk buying, reach the KIL India team.</p><div class="bm-info-list"><a href="tel:' . esc_attr( $phone_href ) . '">' . esc_html( $phone_label ) . '</a><a href="tel:+919650638811">+91 96506 38811</a><a href="tel:+911149070215">+91 1149070215</a><a href="mailto:moabeauty@kilindia.in">moabeauty@kilindia.in</a></div><a class="bm-page-btn" href="mailto:moabeauty@kilindia.in">Email support</a></div><div class="bm-location-card"><span>Support desk</span><h2>KIL India / Beauty In</h2><p>Use these channels for order updates, payment queries, vendor approval, bulk quotes and product assistance.</p><div class="bm-contact-tiles"><a href="' . esc_url( beautyin_marketplace_page_url( 'track-order', '/track-order/' ) ) . '"><strong>Track order</strong><small>Check order status with order ID.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'vendor-registration', '/vendor-registration/' ) ) . '"><strong>Vendor support</strong><small>Domestic and international seller help.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'request-for-quote', '/request-for-quote/' ) ) . '"><strong>Bulk quote</strong><small>Wholesale and recurring requirements.</small></a><a href="https://www.google.com/maps/search/?api=1&query=KIL%20India%20Yusuf%20Sarai%20New%20Delhi" target="_blank" rel="noopener"><strong>Open map</strong><small>Yusuf Sarai, New Delhi.</small></a></div><div class="bm-support-hours"><strong>Support hours</strong><span>Monday to Saturday, 10:00 AM - 6:00 PM IST</span></div></div></div>';
}

function beautyin_marketplace_support_markup() {
	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$cart    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	return '<div class="bm-support-search"><h2>Support center</h2><p>Choose a topic below or search the marketplace to continue shopping.</p><form method="get" action="' . esc_url( home_url( '/' ) ) . '"><input type="search" name="s" placeholder="Search products, skincare, masks, serums..." /><input type="hidden" name="post_type" value="product" /><button type="submit">Search</button></form></div><div class="bm-support-grid"><a href="' . esc_url( $account . 'orders/' ) . '"><strong>Track an order</strong><small>View live order status, invoices and purchase history.</small></a><a href="' . esc_url( $cart ) . '"><strong>Cart and checkout</strong><small>Review cart items and complete checkout after login.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'delivery-across-india', '/delivery-across-india/' ) ) . '"><strong>Delivery across India</strong><small>Learn about shipping support and serviceable locations.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'request-for-quote', '/request-for-quote/' ) ) . '"><strong>Bulk quote</strong><small>Request pricing for wholesale, salons and reseller orders.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'vendor-registration', '/vendor-registration/' ) ) . '"><strong>Seller support</strong><small>Register as a vendor or manage your seller account.</small></a><a href="' . esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ) . '"><strong>Contact us</strong><small>Talk to the KIL India team for direct assistance.</small></a></div><div class="bm-support-strip"><span><strong>Need urgent help?</strong><small>Call ' . esc_html( beautyin_marketplace_main_phone() ) . ' or +91 96506 38811 or email moabeauty@kilindia.in</small></span><a class="bm-page-btn" href="' . esc_url( $shop ) . '">Continue shopping</a></div>';
}

function beautyin_marketplace_quote_markup( $button ) {
	return '<div class="bm-split-cards"><div><h2>What to send</h2><ul><li>Product names or category list</li><li>Required quantity</li><li>Delivery city and timeline</li><li>Business or seller details, if applicable</li></ul></div><div><h2>Get pricing</h2><p>Our team will review availability and respond with the best quote.</p><a class="bm-page-btn" href="mailto:moabeauty@kilindia.in">' . esc_html( $button ) . '</a></div></div>';
}

function beautyin_marketplace_about_markup() {
	return '<div class="bm-policy-copy"><h2>Our story</h2><p>MOA Beauty and KIL India were created to give Indian shoppers a more organized, trustworthy way to discover Korean beauty. Instead of a cluttered catalog, we want a marketplace that feels curated, easier to browse and more helpful at every step, from product discovery to checkout and delivery support.</p><p>Our focus is simple: bring together authentic-feeling marketplace workflows, verified seller support and a shopping experience that makes premium beauty products feel accessible for everyday customers, salons and growing beauty businesses.</p><h2>What MOA Beauty stands for</h2><p>We want the brand to feel premium, but still practical. That means clear product listings, simpler category browsing, readable order summaries, strong support touchpoints and seller tools that help vendors grow without making the customer experience messy.</p><div class="bm-feature-list"><span><strong>Curated selection</strong><small>K-beauty, skincare, makeup, creams, serums, masks and everyday essentials.</small></span><span><strong>Secure shopping</strong><small>Login, cart, checkout and payment flows designed for clarity and trust.</small></span><span><strong>Seller ecosystem</strong><small>Vendor registration, approval, dashboard access and bulk quote support.</small></span></div><h2>For customers</h2><p>Shoppers can explore the marketplace by category, find product ranges that match daily routines, compare items faster and move through checkout with less confusion. We also keep support visible so questions about delivery, payments, addresses or orders are easier to handle.</p><p>We know beauty purchases are personal. That is why the site is designed to reduce friction and help customers feel confident before they place an order.</p><h2>For sellers</h2><p>For vendors, MOA Beauty is built to support visibility and growth. Verified sellers can register, manage products and access marketplace workflows that are meant to be straightforward rather than complicated. The aim is to help quality sellers reach the right customers while keeping standards consistent.</p><p>We are especially focused on helping domestic and international sellers participate with clear approval, documentation and support processes.</p><div class="bm-split-cards"><div><h2>Why people shop with us</h2><p>Curated assortment, responsive support, clear order information and a marketplace look that feels modern and premium.</p></div><div><h2>What we keep improving</h2><p>Better category discovery, smoother mobile shopping, simpler account journeys and more polished customer and seller touchpoints.</p></div></div><p class="bm-page-center"><a class="bm-page-btn" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Explore the marketplace</a></p></div>';
}

function beautyin_marketplace_wishlist_markup() {
	if ( function_exists( 'beautyin_page_suite_wishlist' ) ) {
		return beautyin_page_suite_wishlist();
	}

	return '<div class="bm-empty-state"><h2>Your wishlist is empty</h2><p>Save products from product cards or product detail pages and they will appear here.</p><a class="bm-page-btn" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Continue shopping</a></div>';
}

function beautyin_marketplace_track_order_markup() {
	if ( function_exists( 'beautyin_page_suite_track_order_form' ) ) {
		return beautyin_page_suite_track_order_form();
	}

	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	return '<div class="bm-split-cards"><div><h2>Live order area</h2><p>Order tracking is connected to your WooCommerce account. Login to see order history, current status and invoices.</p><a class="bm-page-btn" href="' . esc_url( $account . 'orders/' ) . '">View my orders</a></div><div><h2>Need help?</h2><p>Keep your order ID ready and contact support for delivery or payment questions.</p><a class="bm-page-btn bm-page-btn-light" href="' . esc_url( beautyin_marketplace_page_url( 'contact-us', '/contact-us/' ) ) . '">Contact support</a></div></div>';
}

function beautyin_marketplace_delivery_markup() {
	return '<div class="bm-feature-list"><span><strong>Pan India reach</strong><small>Delivery support across available serviceable pincodes.</small></span><span><strong>Order tracking</strong><small>Track live status from your account dashboard.</small></span><span><strong>Seller dispatch</strong><small>Verified vendors prepare and dispatch marketplace orders.</small></span></div>';
}

function beautyin_marketplace_kbeauty_markup() {
	return '<div class="bm-feature-list"><span><strong>Skincare</strong><small>Serums, creams, toners, masks and daily routines.</small></span><span><strong>Makeup</strong><small>Tints, palettes and curated Korean beauty picks.</small></span><span><strong>Imports</strong><small>Selected K-beauty products for Indian customers.</small></span></div><p class="bm-page-center"><a class="bm-page-btn" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Shop marketplace</a></p>';
}

function beautyin_marketplace_policy_markup() {
	return '<div class="bm-policy-copy"><h2>Privacy overview</h2><p>KIL India / Beauty In respects customer, vendor and visitor privacy. This policy explains how we collect, use, store and protect information when you browse the marketplace, create an account, place an order, register as a seller, request support or contact our team.</p><h2>Information we collect</h2><p>We may collect name, phone number, email address, billing address, shipping address, account login details, order history, product enquiries, vendor registration details, payment confirmation status, device information and messages shared with our support team.</p><h2>How we use information</h2><p>Customer and seller details are used for account access, order processing, delivery communication, payment confirmation, invoices, support, fraud prevention, marketplace operations, vendor verification, legal compliance and service improvement.</p><h2>Accounts and login</h2><p>When customers or sellers create an account, the information submitted is used to manage profile access, order records, wishlist activity, seller dashboards, checkout history and support communication. Users are responsible for keeping login credentials confidential.</p><h2>Orders, payments and invoices</h2><p>Order details are processed through WooCommerce and connected services configured on this website. Payment information is handled by the selected payment gateway. We do not intentionally publish private payment details, card information or customer billing information publicly.</p><h2>Shipping and delivery data</h2><p>Shipping name, phone number, address, pincode and order details may be shared with sellers, fulfilment teams or courier partners only when needed to pack, dispatch, track or resolve delivery issues for an order.</p><h2>Vendor and seller information</h2><p>Vendor registration details, business information, product listings, payout-related details and communication records may be used for seller verification, marketplace approval, compliance checks, catalogue management, order fulfilment and vendor support.</p><h2>Cookies and analytics</h2><p>The website may use cookies or similar technologies to support login sessions, cart activity, checkout flow, security, performance, analytics and marketing measurement. Users can control browser cookie settings, but some marketplace features may not work correctly if cookies are disabled.</p><h2>Sharing of information</h2><p>We may share necessary information with payment gateways, shipping providers, verified vendors, technical service providers, support tools, tax or legal authorities and website security services when required for marketplace operations, order fulfilment, compliance or protection against misuse.</p><h2>Data security</h2><p>We use reasonable administrative, technical and operational safeguards to protect marketplace data. No online system is completely risk free, so customers and sellers should use strong passwords, avoid sharing OTPs or login details and contact support if suspicious activity is noticed.</p><h2>Data retention</h2><p>We keep account, order, invoice, support and vendor records for as long as needed for marketplace operations, legal obligations, tax records, dispute resolution, fraud prevention and service improvement. Some records may remain in backups or logs for a limited period.</p><h2>User choices and requests</h2><p>Customers and sellers may contact us to request help with account details, order information, communication preferences or privacy-related questions. Certain records connected to completed transactions, legal obligations or fraud prevention may need to be retained.</p><h2>Children and sensitive information</h2><p>This marketplace is intended for users who can lawfully shop or participate as sellers. Users should not submit unnecessary sensitive personal information through forms, reviews, support messages or product enquiries.</p><h2>Policy updates</h2><p>This Privacy Policy may be updated when marketplace features, payment methods, vendor workflows, legal requirements or support processes change. Continued use of the website after updates means the revised policy is accepted.</p><h2>Contact for privacy support</h2><p>For privacy, account, vendor or order information support, contact the KIL India / Beauty In team through the contact details available on this website. Please include your order ID or account email when the request relates to a specific purchase.</p></div>';
}

function beautyin_marketplace_terms_markup() {
	return '<div class="bm-policy-copy"><h2>Marketplace use</h2><p>By accessing or placing an order on KIL India / Beauty In, customers, vendors and visitors agree to use this marketplace responsibly and follow the terms shown on this website. These terms apply to product browsing, account use, order placement, payments, delivery, support requests and seller participation.</p><h2>Accounts and customer responsibility</h2><p>Customers are responsible for keeping account details, login information, delivery address and contact information accurate. Orders, invoices, shipment updates and support communication may be linked to the customer account used during checkout.</p><h2>Products, pricing and availability</h2><p>Product images, descriptions, ingredients, stock status, prices and offers are provided for shopping guidance. Availability, pricing, discounts and delivery timelines may change without prior notice because products can be listed by different sellers or updated due to stock movement.</p><h2>Orders and checkout</h2><p>Checkout may require login or registration. An order is confirmed only after successful submission and applicable payment confirmation. KIL India / Beauty In may review, hold or cancel orders if details are incomplete, payment fails, stock is unavailable, pricing errors occur or verification is required.</p><h2>Payments and invoices</h2><p>Payments are processed through the payment methods and gateways enabled on the website. Customers should review cart value, taxes, shipping charges and order details before completing checkout. GST invoices or order records may be provided where applicable and based on available seller and payment information.</p><h2>Shipping and delivery</h2><p>Delivery support depends on serviceable pincodes, seller dispatch timelines, courier availability and product type. Estimated delivery timelines are indicative and may change due to courier delays, address issues, holidays, customs checks for imported products or events outside marketplace control.</p><h2>Returns, refunds and cancellations</h2><p>Return, refund and cancellation eligibility depends on product category, seller policy, condition of the item, order status and applicable marketplace rules. Beauty, skincare, cosmetic and personal care products may have limited return eligibility for hygiene and safety reasons once opened, used or damaged after delivery.</p><h2>Vendor participation</h2><p>Sellers and vendors must provide accurate business details, product information, stock status, pricing, tax details and fulfilment support. Vendor participation is subject to registration, verification, marketplace approval and ongoing compliance with KIL India / Beauty In policies.</p><h2>Prohibited activity</h2><p>Users must not misuse the website, upload false information, attempt fraudulent transactions, copy marketplace content without permission, interfere with site security, abuse support channels or list restricted, unsafe, counterfeit or misleading products.</p><h2>Limitation of responsibility</h2><p>KIL India / Beauty In works to keep marketplace information accurate and shopping workflows reliable, but product performance, individual skin suitability, courier delays, seller fulfilment issues and third-party payment services may vary. Customers should read product details carefully and seek professional advice where needed.</p><h2>Support and communication</h2><p>For order, payment, delivery, vendor or product support, customers and sellers can contact the KIL India / Beauty In team through the contact details provided on this website. Please keep order ID, account email and product details ready for faster assistance.</p><h2>Policy updates</h2><p>These terms may be updated from time to time to reflect marketplace operations, legal requirements, payment methods, vendor rules or customer support processes. Continued use of the website after updates means the revised terms are accepted.</p></div>';
}

add_action( 'astra_content_before', 'beautyin_marketplace_front_page_hook', 5 );
function beautyin_marketplace_front_page_hook() {
	if ( is_admin() || ! is_front_page() || ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	beautyin_marketplace_home_markup();
}

function beautyin_marketplace_home_markup() {
	$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$hero_img  = content_url( 'uploads/2026/02/Untitled-1-1.jpg' );
	$hero_file = WP_CONTENT_DIR . '/uploads/2026/02/Untitled-1-1.jpg';
	?>
	<main class="bm-home">
		<section class="bm-hero" <?php echo file_exists( $hero_file ) ? 'style="background-image:url(' . esc_url( $hero_img ) . ')"' : ''; ?>>
			<div class="bm-hero-copy">
				<p>KIL India Select</p>
				<h1>Korean beauty, curated like a premium fashion store.</h1>
				<span class="bm-hero-sub">Discover skincare, cosmetics and daily essentials from trusted sellers, with a storefront built for fast shopping.</span>
				<div class="bm-hero-actions">
					<a class="bm-btn bm-btn-primary" href="<?php echo esc_url( $shop_url ); ?>">Shop new drops</a>
					<a class="bm-btn bm-btn-light" href="<?php echo esc_url( home_url( '/product-category/skincare/' ) ); ?>">Explore skincare</a>
				</div>
			</div>
			<div class="bm-hero-panel">
				<strong>Today's edit</strong>
				<span>Glow tint, masks, toner, ampoules</span>
				<a href="<?php echo esc_url( home_url( '/product-category/cosmetic/' ) ); ?>">View collection</a>
			</div>
		</section>

		<section class="bm-category-grid" aria-label="Featured departments">
			<?php foreach ( beautyin_marketplace_categories( 8 ) as $cat ) : ?>
				<a class="bm-dept-card" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
					<?php echo beautyin_marketplace_category_image( $cat ); ?>
					<span><?php echo esc_html( beautyin_marketplace_nav_label( $cat->name, $cat->slug ) ); ?></span>
					<small><?php echo esc_html( $cat->count ); ?> items</small>
				</a>
			<?php endforeach; ?>
		</section>

		<?php
		beautyin_marketplace_product_rail( 'Fresh Beauty Drops', array( 'orderby' => 'date', 'order' => 'DESC', 'limit' => 10 ) );
		beautyin_marketplace_product_rail( 'Most Wanted Right Now', array( 'orderby' => 'popularity', 'order' => 'DESC', 'limit' => 10 ) );
		?>

		<section class="bm-promo-grid">
			<a href="<?php echo esc_url( home_url( '/product-category/cosmetic/' ) ); ?>">
				<strong>Makeup Studio</strong>
				<span>Lip tints, palettes and daily glam picks</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/product-category/skincare/' ) ); ?>">
				<strong>Routine Builder</strong>
				<span>Serums, toners, creams and soothing masks</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/vendor-registration/' ) ); ?>">
				<strong>Marketplace for sellers</strong>
				<span>Launch your K-beauty storefront with KIL</span>
			</a>
		</section>

		<?php beautyin_marketplace_product_rail( 'Picked For You', array( 'orderby' => 'rand', 'limit' => 12 ) ); ?>
	</main>
	<?php
}

function beautyin_marketplace_categories( $limit = 8 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => $limit,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'exclude'    => beautyin_marketplace_excluded_category_ids(),
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

function beautyin_marketplace_excluded_category_ids() {
	$ids = array();
	foreach ( array( 'uncategorized', 'uncategorized-test' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[] = (int) $term->term_id;
		}
	}

	return $ids;
}

function beautyin_marketplace_nav_categories() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$children = array();
	foreach ( $terms as $term ) {
		$children[ (int) $term->parent ][] = $term;
	}

	$items = array();
	foreach ( $children[0] ?? array() as $parent ) {
		if ( in_array( $parent->slug, array( 'uncategorized', 'uncategorized-test' ), true ) ) {
			continue;
		}

		$visible_children = array_values(
			array_filter(
				$children[ (int) $parent->term_id ] ?? array(),
				function ( $child ) use ( $children ) {
					return (int) $child->count > 0 || ! empty( $children[ (int) $child->term_id ] );
				}
			)
		);

		if ( (int) $parent->count <= 0 && empty( $visible_children ) ) {
			continue;
		}

		$items[] = array(
			'term'     => $parent,
			'children' => $visible_children,
		);
	}

	return $items;
}

function beautyin_marketplace_nav_label( $name, $slug ) {
	if ( 'shipped-from-korea' === $slug ) {
		return 'Korean Imports';
	}

	return $name;
}

function beautyin_marketplace_category_image( $cat ) {
	$curated = beautyin_marketplace_category_image_overrides();
	if ( isset( $curated[ $cat->slug ] ) ) {
		return sprintf(
			'<img class="bm-dept-photo" src="%s" alt="%s" loading="lazy" />',
			esc_url( $curated[ $cat->slug ] ),
			esc_attr( beautyin_marketplace_nav_label( $cat->name, $cat->slug ) )
		);
	}

	$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
	if ( $thumbnail_id ) {
		return wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail' );
	}

	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 1,
				'category' => array( $cat->slug ),
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);

		if ( ! empty( $products ) && $products[0]->get_image_id() ) {
			return wp_get_attachment_image( $products[0]->get_image_id(), 'woocommerce_thumbnail' );
		}
	}

	return '<span class="bm-dept-fallback">' . esc_html( mb_substr( $cat->name, 0, 1 ) ) . '</span>';
}

function beautyin_marketplace_category_image_overrides() {
	return array(
		'cosmetic'           => 'https://images.pexels.com/photos/31552021/pexels-photo-31552021.jpeg?auto=compress&cs=tinysrgb&w=900',
		'cream'              => 'https://images.pexels.com/photos/5582621/pexels-photo-5582621.jpeg?auto=compress&cs=tinysrgb&w=900',
		'serum'              => 'https://images.pexels.com/photos/34939722/pexels-photo-34939722.jpeg?auto=compress&cs=tinysrgb&w=900',
		'face-sheet'         => 'https://unsplash.com/photos/gPwoRKbx5UU/download?force=true&w=900',
		'personal-care'      => 'https://images.pexels.com/photos/30988734/pexels-photo-30988734.jpeg?auto=compress&cs=tinysrgb&w=900',
		'shipped-from-korea' => 'https://images.pexels.com/photos/31552021/pexels-photo-31552021.jpeg?auto=compress&cs=tinysrgb&w=900',
		'skincare'           => 'https://images.pexels.com/photos/28927902/pexels-photo-28927902.jpeg?auto=compress&cs=tinysrgb&w=900',
		'toner'              => 'https://images.pexels.com/photos/15072928/pexels-photo-15072928.jpeg?auto=compress&cs=tinysrgb&w=900',
		'cleaner'            => 'https://images.pexels.com/photos/5582621/pexels-photo-5582621.jpeg?auto=compress&cs=tinysrgb&w=900',
	);
}

function beautyin_marketplace_product_rail( $title, $args ) {
	$defaults = array(
		'status' => 'publish',
		'limit'  => 10,
	);
	$products = wc_get_products( wp_parse_args( $args, $defaults ) );

	if ( empty( $products ) ) {
		return;
	}
	?>
	<section class="bm-rail">
		<div class="bm-section-head">
			<h2><?php echo esc_html( $title ); ?></h2>
			<div class="bm-section-actions">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">See all</a>
				<button class="bm-rail-nav bm-rail-prev" type="button" aria-label="<?php echo esc_attr( 'Scroll ' . $title . ' left' ); ?>"></button>
				<button class="bm-rail-nav bm-rail-next" type="button" aria-label="<?php echo esc_attr( 'Scroll ' . $title . ' right' ); ?>"></button>
			</div>
		</div>
		<div class="bm-products">
			<?php foreach ( $products as $product ) : ?>
				<?php $price_html = $product->get_price_html(); ?>
				<article class="bm-product <?php echo empty( $price_html ) ? 'bm-product-no-price' : ''; ?>">
					<a class="bm-product-img" href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
					</a>
					<div class="bm-product-body">
						<a class="bm-product-title" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
						<div class="bm-rating"><?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ) ); ?></div>
						<?php if ( ! empty( $price_html ) ) : ?>
							<div class="bm-price"><?php echo wp_kses_post( $price_html ); ?></div>
						<?php else : ?>
							<div class="bm-price bm-price-missing">Price unavailable</div>
						<?php endif; ?>
						<?php if ( ! empty( $price_html ) && $product->is_purchasable() ) : ?>
							<div class="bm-product-actions">
								<a class="bm-add" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">Add to cart</a>
								<?php if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) : ?>
									<a class="bm-buy-now" href="<?php echo esc_url( beautyin_marketplace_buy_now_url( $product->get_id() ) ); ?>">Buy now</a>
								<?php endif; ?>
							</div>
						<?php else : ?>
							<a class="bm-add bm-view" href="<?php echo esc_url( $product->get_permalink() ); ?>">View details</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

add_filter( 'wpseo_title', 'beautyin_marketplace_seo_title' );
add_filter( 'wpseo_metadesc', 'beautyin_marketplace_seo_description' );
add_filter( 'wpseo_opengraph_title', 'beautyin_marketplace_seo_title' );
add_filter( 'wpseo_opengraph_desc', 'beautyin_marketplace_seo_description' );
add_filter( 'wpseo_twitter_title', 'beautyin_marketplace_seo_title' );
add_filter( 'wpseo_twitter_description', 'beautyin_marketplace_seo_description' );
add_filter( 'wpseo_robots', 'beautyin_marketplace_seo_robots' );
add_filter( 'wpseo_opengraph_image', 'beautyin_marketplace_seo_image' );
add_filter( 'pre_get_document_title', 'beautyin_marketplace_document_title' );
add_action( 'wp_head', 'beautyin_marketplace_force_site_icon', 0 );
add_action( 'admin_head', 'beautyin_marketplace_force_site_icon', 0 );
add_action( 'login_head', 'beautyin_marketplace_force_site_icon', 0 );
add_action( 'wp_head', 'beautyin_marketplace_head_seo_meta', 1 );
add_action( 'wp_head', 'beautyin_marketplace_json_ld_schema', 30 );
add_filter( 'wp_get_attachment_image_attributes', 'beautyin_marketplace_product_image_alt', 20, 3 );

function beautyin_marketplace_seo_title( $title ) {
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product ) {
			return beautyin_marketplace_product_seo_title( $product );
		}
	}

	$collection = beautyin_marketplace_current_collection();
	if ( $collection ) {
		$labels = beautyin_marketplace_collection_labels();
		return isset( $labels[ $collection ] ) ? $labels[ $collection ] . ' | MOA Beauty' : $title;
	}

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			return beautyin_marketplace_trim_meta( sprintf( 'Buy %s Korean Beauty Products Online in India | MOA Beauty', $term->name ), 62 );
		}
	}

	if ( is_shop() ) {
		return 'Korean Beauty, Skincare and Makeup Online in India | MOA Beauty';
	}

	if ( is_page() ) {
		return beautyin_marketplace_page_seo_title( get_post() );
	}

	return $title;
}

function beautyin_marketplace_seo_description( $description ) {
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product ) {
			return beautyin_marketplace_product_seo_description( $product );
		}
	}

	$collection = beautyin_marketplace_current_collection();
	if ( $collection ) {
		$descriptions = array(
			'new-arrivals' => 'Browse the latest Korean beauty arrivals selected for the MOA Beauty store.',
			'best-sellers'  => 'Browse the best selling Korean beauty products selected for the MOA Beauty store.',
		);

		return isset( $descriptions[ $collection ] ) ? $descriptions[ $collection ] : $description;
	}

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			return beautyin_marketplace_trim_meta(
				sprintf(
					'Shop %s Korean beauty products online in India at MOA Beauty. Authentic skincare, makeup and personal care with secure payments, delivery support and GST billing.',
					$term->name
				),
				155
			);
		}
	}

	if ( is_shop() ) {
		return 'Buy authentic Korean skincare, makeup, serums, creams, masks and personal care online in India at MOA Beauty with secure checkout and order support.';
	}

	if ( is_page() ) {
		return beautyin_marketplace_page_seo_description( get_post() );
	}

	return $description;
}

function beautyin_marketplace_product_seo_title( $product ) {
	$category = beautyin_marketplace_primary_product_category( $product->get_id() );
	$label    = $category ? $category->name : 'Korean Beauty';

	return beautyin_marketplace_trim_meta( sprintf( '%s - %s Online in India | MOA Beauty', $product->get_name(), $label ), 62 );
}

function beautyin_marketplace_product_seo_description( $product ) {
	$category = beautyin_marketplace_primary_product_category( $product->get_id() );
	$label    = $category ? strtolower( $category->name ) : 'Korean beauty product';
	$price    = $product->get_price() ? ' at ' . wp_strip_all_tags( wc_price( $product->get_price() ) ) : '';
	$vendor   = beautyin_marketplace_product_vendor_name( $product->get_id() );
	$vendor   = $vendor ? ' from ' . $vendor : '';

	$description = sprintf(
		'Buy %s%s online in India%s. Authentic Korean %s with secure checkout, GST invoice, delivery support and order tracking.',
		$product->get_name(),
		$price,
		$vendor,
		$label
	);

	if ( strlen( $description ) > 155 ) {
		$description = sprintf(
			'Buy %s online in India. Authentic Korean %s with secure checkout, GST invoice and delivery support.',
			$product->get_name(),
			$label
		);
	}

	return beautyin_marketplace_trim_meta( $description, 155 );
}

function beautyin_marketplace_page_seo_title( $page ) {
	if ( ! $page ) {
		return get_bloginfo( 'name' );
	}

	$map = array(
		'home'                  => 'MOA Beauty - Korean Beauty and Skincare Online in India',
		'store'                 => 'Shop Korean Beauty Products Online in India | MOA Beauty',
		'my-account'            => 'My Account | MOA Beauty India',
		'cart'                  => 'Cart | MOA Beauty India',
		'support'               => 'Support Center | MOA Beauty India',
		'order-history'         => 'Order History | MOA Beauty India',
		'wishlist'              => 'Wishlist | Saved Korean Beauty Products | MOA Beauty',
		'saved-addresses'       => 'Saved Addresses | MOA Beauty Account',
		'profile-settings'      => 'Profile Settings | MOA Beauty Account',
		'about-us'              => 'About Us | MOA Beauty India',
		'about'                 => 'About MOA Beauty and KIL India | Korean Beauty Marketplace',
		'contact-us'            => 'Contact MOA Beauty India | Korean Beauty Support',
		'faq'                   => 'Korean Beauty Shopping FAQ | MOA Beauty India',
		'track-order'           => 'Track Order | MOA Beauty India',
		'vendor-registration'   => 'Sell Korean Beauty Products in India | Vendor Registration',
		'vendor-dashboard'      => 'Vendor Dashboard | MOA Beauty India',
		'request-for-quote'     => 'Request Bulk Korean Beauty Quote | MOA Beauty India',
		'bulk-quote'            => 'Bulk Korean Beauty Products Quote | MOA Beauty India',
		'delivery-across-india' => 'Korean Beauty Delivery Across India | MOA Beauty',
		'k-beauty-marketplace'  => 'K-Beauty Marketplace in India | MOA Beauty',
		'korean-beauty-india'   => 'K-Beauty in India | Korean Beauty Products | MOA Beauty',
		'korean-beauty-delhi'   => 'K-Beauty in Delhi | Korean Beauty Products | MOA Beauty',
		'korean-beauty-mumbai'  => 'K-Beauty in Mumbai | Korean Beauty Products | MOA Beauty',
		'korean-beauty-goa'     => 'K-Beauty in Goa | Korean Beauty Products | MOA Beauty',
		'korean-beauty-punjab'  => 'K-Beauty in Punjab | Korean Beauty Products | MOA Beauty',
		'shipping-policy'       => 'Shipping Policy | MOA Beauty India',
		'refund-return-policy'  => 'Refund and Return Policy | MOA Beauty India',
		'returns-refunds'       => 'Returns and Refunds Policy | MOA Beauty India',
		'privacy-policy'        => 'Privacy Policy | MOA Beauty India',
		'terms-and-conditions'  => 'Terms and Conditions | MOA Beauty India',
		'cookie-policy'         => 'Cookie Policy | MOA Beauty India',
		'korean-skincare-routine-guide' => 'Korean Skincare Routine Guide for India | MOA Beauty',
		'choose-products-for-your-skin-type' => 'Choose Skincare for Your Skin Type | MOA Beauty',
		'ingredients-guide'     => 'Korean Skincare Ingredients Guide | MOA Beauty',
		'authenticity-guarantee' => 'Authenticity Guarantee | Original Korean Products | MOA Beauty',
		'reviews'               => 'Customer Reviews | MOA Beauty Korean Products',
		'before-after-results'  => 'Before and After Results | MOA Beauty',
		'payment-success'       => 'Payment Successful | MOA Beauty',
		'payment-failed'        => 'Payment Failed | MOA Beauty',
	);

	return isset( $map[ $page->post_name ] ) ? $map[ $page->post_name ] : beautyin_marketplace_trim_meta( $page->post_title . ' | MOA Beauty', 62 );
}

function beautyin_marketplace_page_seo_description( $page ) {
	if ( ! $page ) {
		return get_bloginfo( 'description' );
	}

	$map = array(
		'home'                  => 'MOA Beauty brings authentic Korean skincare, makeup, serums, creams and beauty essentials to India with secure checkout and trusted delivery support.',
		'store'                 => 'Shop authentic Korean beauty products online in India, including skincare, makeup, serums, creams, masks and personal care from MOA Beauty.',
		'my-account'            => 'Login or manage your MOA Beauty account, saved addresses, orders and wishlist from one secure dashboard.',
		'cart'                  => 'Review cart items, product pricing, shipping and checkout before completing your MOA Beauty order.',
		'support'               => 'Find quick help for orders, checkout, delivery, returns, vendor support and bulk quote requests at MOA Beauty India.',
		'order-history'         => 'View MOA Beauty order history, payment status, invoices and delivery updates from your customer account.',
		'wishlist'              => 'Save Korean skincare, makeup, serums, creams and masks to your MOA Beauty wishlist for faster shopping later.',
		'saved-addresses'       => 'Manage saved billing and shipping addresses for faster MOA Beauty checkout and delivery across India.',
		'profile-settings'      => 'Update your MOA Beauty customer profile, password, addresses and account details.',
		'about-us'              => 'Learn about MOA Beauty and KIL India, a curated Korean beauty marketplace for Indian customers, vendors and skincare lovers.',
		'about'                 => 'Learn about MOA Beauty and KIL India, a curated Korean beauty marketplace for Indian customers, vendors and skincare lovers.',
		'contact-us'            => 'Contact MOA Beauty India for product support, order help, vendor enquiries, bulk quotes and Korean beauty marketplace assistance.',
		'faq'                   => 'Find answers about MOA Beauty orders, Korean skincare, delivery, returns, vendor registration and international products.',
		'track-order'           => 'Track your MOA Beauty order status using order ID with billing email or mobile number.',
		'vendor-registration'   => 'Register as a vendor and sell Korean beauty, skincare, makeup and personal care products to Indian customers through MOA Beauty.',
		'vendor-dashboard'      => 'Manage vendor products, orders, payouts and store settings from the MOA Beauty seller dashboard.',
		'request-for-quote'     => 'Request a quote for Korean beauty products, bulk skincare orders, vendor sourcing and marketplace buying support from MOA Beauty India.',
		'bulk-quote'            => 'Get bulk pricing for Korean skincare, makeup and beauty essentials in India from MOA Beauty and KIL India.',
		'delivery-across-india' => 'MOA Beauty supports Korean beauty product delivery across India with secure payments, order tracking and customer care.',
		'k-beauty-marketplace'  => 'Discover a curated K-beauty marketplace in India for Korean skincare, makeup, serums, creams, masks and beauty essentials.',
		'korean-beauty-india'   => 'Shop K-beauty and authentic Korean skincare, makeup and beauty products across India with secure checkout, delivery support and GST billing where applicable.',
		'korean-beauty-delhi'   => 'Shop K-beauty and authentic Korean skincare, makeup and beauty products in Delhi with secure checkout, delivery support and customer care.',
		'korean-beauty-mumbai'  => 'Shop K-beauty and authentic Korean skincare, makeup and beauty products in Mumbai with secure checkout, delivery support and customer care.',
		'korean-beauty-goa'     => 'Shop K-beauty and authentic Korean skincare, makeup and beauty products in Goa with secure checkout, delivery support and customer care.',
		'korean-beauty-punjab'  => 'Shop K-beauty and authentic Korean skincare, makeup and beauty products in Punjab with secure checkout, delivery support and customer care.',
		'shipping-policy'       => 'Read MOA Beauty shipping policy for delivery timelines, shipping charges, pincode serviceability and international products.',
		'refund-return-policy'  => 'Read MOA Beauty refund and return policy for damaged items, cancellations, skincare hygiene rules and refund timelines.',
		'privacy-policy'        => 'Learn how MOA Beauty handles customer, seller, order, payment, delivery and support information.',
		'terms-and-conditions'  => 'Read MOA Beauty marketplace terms for shopping, checkout, sellers, payments, delivery and support.',
		'cookie-policy'         => 'Learn how MOA Beauty uses cookies for login, cart, checkout, analytics, security and marketplace performance.',
		'korean-skincare-routine-guide' => 'Follow a Korean skincare routine for cleanser, toner, serum, cream and sunscreen with product guidance for Indian shoppers.',
		'choose-products-for-your-skin-type' => 'Choose Korean skincare products for oily, dry, combination or sensitive skin with MOA Beauty guidance.',
		'ingredients-guide'     => 'Understand niacinamide, centella, hyaluronic acid, collagen and other Korean skincare ingredients before buying.',
		'authenticity-guarantee' => 'MOA Beauty focuses on original Korean beauty products through verified sellers, marketplace review and customer support.',
		'reviews'               => 'Read customer review guidance and Korean beauty product feedback support from MOA Beauty.',
		'before-after-results'  => 'Explore before and after result guidance for Korean skincare routines and product expectations.',
	);

	if ( isset( $map[ $page->post_name ] ) ) {
		return $map[ $page->post_name ];
	}

	return beautyin_marketplace_trim_meta( wp_strip_all_tags( $page->post_excerpt ? $page->post_excerpt : $page->post_title . ' at MOA Beauty India.' ), 155 );
}

function beautyin_marketplace_primary_product_category( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( 'uncategorized' !== $term->slug && 'uncategorized-test' !== $term->slug ) {
			return $term;
		}
	}

	return $terms[0];
}

function beautyin_marketplace_trim_meta( $text, $limit ) {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( html_entity_decode( (string) $text ) ) ) );
	if ( strlen( $text ) <= $limit ) {
		return $text;
	}

	$trimmed = rtrim( substr( $text, 0, $limit ), " \t\n\r\0\x0B.,;:-" );
	$space   = strrpos( $trimmed, ' ' );
	if ( false !== $space && $space > (int) ( $limit * 0.72 ) ) {
		$trimmed = substr( $trimmed, 0, $space );
	}

	return rtrim( $trimmed, " \t\n\r\0\x0B.,;:-" );
}

function beautyin_marketplace_seo_robots( $robots ) {
	if ( is_cart() || is_checkout() || is_account_page() || is_page( array( 'cart', 'cart-2', 'wishlist', 'track-order', 'track-your-order', 'orders-tracking', 'payment-success', 'payment-failed', 'thank-you', 'thank-you-2' ) ) ) {
		return 'noindex,follow';
	}

	if ( is_search() || ! empty( $_GET['min_price'] ) || ! empty( $_GET['max_price'] ) || ! empty( $_GET['orderby'] ) || ! empty( $_GET['bm_collection'] ) ) {
		return 'noindex,follow';
	}

	return $robots;
}

function beautyin_marketplace_seo_image( $image ) {
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product && $product->get_image_id() ) {
			$product_image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
			if ( $product_image ) {
				return $product_image;
			}
		}
	}

	return $image ? $image : beautyin_marketplace_moa_logo_url();
}

function beautyin_marketplace_document_title( $title ) {
	if ( is_admin() ) {
		return $title;
	}

	$seo_title = beautyin_marketplace_seo_title( $title );
	return $seo_title ? $seo_title : $title;
}

function beautyin_marketplace_force_site_icon() {
	$site_icon_id = (int) get_option( 'site_icon' );
	$icon_url     = $site_icon_id ? wp_get_attachment_image_url( $site_icon_id, 'woocommerce_gallery_thumbnail' ) : '';
	$apple_url    = $site_icon_id ? wp_get_attachment_image_url( $site_icon_id, 'thumbnail' ) : '';

	if ( ! $icon_url ) {
		$icon_url = beautyin_marketplace_moa_logo_url();
	}
	if ( ! $apple_url ) {
		$apple_url = $icon_url;
	}

	if ( ! $icon_url ) {
		return;
	}

	echo '<link rel="icon" href="' . esc_url( $icon_url ) . '" sizes="100x100" type="image/png">' . "\n";
	echo '<link rel="shortcut icon" href="' . esc_url( $icon_url ) . '" type="image/png">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $apple_url ) . '">' . "\n";
}

function beautyin_marketplace_head_seo_meta() {
	if ( is_admin() || defined( 'WPSEO_VERSION' ) ) {
		return;
	}

	$title       = beautyin_marketplace_seo_title( wp_get_document_title() );
	$description = beautyin_marketplace_seo_description( get_bloginfo( 'description' ) );
	$canonical   = beautyin_marketplace_current_canonical_url();
	$image       = beautyin_marketplace_seo_image( '' );
	$robots      = beautyin_marketplace_seo_robots( 'index,follow,max-image-preview:large' );

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
	if ( $robots && false !== strpos( $robots, 'noindex' ) ) {
		echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
	}
	if ( $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
	}
	if ( $title ) {
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	}
	if ( $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	}
	if ( $canonical ) {
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta property="og:type" content="' . ( is_singular( 'product' ) ? 'product' : 'website' ) . '">' . "\n";
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product && $product->get_price() ) {
			echo '<meta property="product:price:amount" content="' . esc_attr( $product->get_price() ) . '">' . "\n";
			echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}

function beautyin_marketplace_current_canonical_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_product_category() || is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			return is_wp_error( $link ) ? home_url( '/' ) : $link;
		}
	}
	if ( is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}

	return home_url( add_query_arg( null, null ) );
}

function beautyin_marketplace_json_ld_schema() {
	if ( is_admin() ) {
		return;
	}

	$schema = array();

	$schema[] = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'@id'      => home_url( '/#organization' ),
		'name'     => 'MOA Beauty',
		'alternateName' => 'MOA Beauty',
		'url'      => home_url( '/' ),
		'logo'     => beautyin_marketplace_moa_logo_url(),
		'email'    => beautyin_marketplace_main_email(),
		'telephone' => beautyin_marketplace_main_phone(),
		'contactPoint' => array(
			array(
				'@type'       => 'ContactPoint',
				'contactType' => 'customer support',
				'telephone'   => beautyin_marketplace_main_phone(),
				'email'       => beautyin_marketplace_main_email(),
				'areaServed'  => 'IN',
				'availableLanguage' => array( 'en', 'hi' ),
			),
		),
		'areaServed' => array( 'IN' ),
		'sameAs'   => array(),
	);

	$schema[] = array(
		'@context' => 'https://schema.org',
		'@type'    => 'WebSite',
		'@id'      => home_url( '/#website' ),
		'name'     => 'MOA Beauty',
		'url'      => home_url( '/' ),
		'publisher' => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => home_url( '/?s={search_term_string}&post_type=product' ),
			'query-input' => 'required name=search_term_string',
		),
	);

	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product ) {
			$schema[] = beautyin_marketplace_product_schema( $product );
			$schema[] = beautyin_marketplace_breadcrumb_schema();
		}
	} elseif ( is_product_category() || is_shop() ) {
		$schema[] = beautyin_marketplace_item_list_schema();
		$schema[] = beautyin_marketplace_breadcrumb_schema();
	} elseif ( is_page( 'faq' ) ) {
		$schema[] = beautyin_marketplace_faq_schema();
	} elseif ( is_singular() ) {
		$schema[] = beautyin_marketplace_webpage_schema();
		$schema[] = beautyin_marketplace_breadcrumb_schema();
	}

	foreach ( array_filter( $schema ) as $item ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}

function beautyin_marketplace_product_schema( $product ) {
	$image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : beautyin_marketplace_moa_logo_url();
	$availability = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
	$category = beautyin_marketplace_primary_product_category( $product->get_id() );
	$brand = beautyin_marketplace_product_vendor_name( $product->get_id() );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'@id'         => get_permalink( $product->get_id() ) . '#product',
		'name'        => $product->get_name(),
		'description' => beautyin_marketplace_product_seo_description( $product ),
		'image'       => $image ? array( $image ) : array(),
		'sku'         => $product->get_sku() ? $product->get_sku() : 'KIL-' . $product->get_id(),
		'category'    => $category ? $category->name : 'Korean Beauty',
		'brand'       => array(
			'@type' => 'Brand',
			'name'  => $brand ? $brand : 'MOA Beauty',
		),
		'offers'      => array(
			'@type'         => 'Offer',
			'url'           => get_permalink( $product->get_id() ),
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price() ? wc_format_decimal( $product->get_price(), 2 ) : '0.00',
			'availability'  => $availability,
			'itemCondition' => 'https://schema.org/NewCondition',
			'seller'        => array( '@id' => home_url( '/#organization' ) ),
		),
	);

	$rating = $product->get_average_rating();
	$count  = $product->get_rating_count();
	if ( $rating && $count ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $rating,
			'reviewCount' => $count,
		);
	}

	return $schema;
}

function beautyin_marketplace_item_list_schema() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$ids = wc_get_products( array(
		'status' => 'publish',
		'limit'  => 12,
		'return' => 'ids',
	) );

	$items = array();
	$position = 1;
	foreach ( $ids as $id ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'url'      => get_permalink( $id ),
			'name'     => get_the_title( $id ),
		);
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => is_product_category() ? single_term_title( '', false ) : 'Korean Beauty Products',
		'itemListElement' => $items,
	);
}

function beautyin_marketplace_breadcrumb_schema() {
	$items = array(
		array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
	);

	if ( is_singular( 'product' ) ) {
		$category = beautyin_marketplace_primary_product_category( get_the_ID() );
		if ( $category ) {
			$link = get_term_link( $category );
			if ( ! is_wp_error( $link ) ) {
				$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => $category->name, 'item' => $link );
			}
		}
		$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title(), 'item' => get_permalink() );
	} elseif ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$items[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => $term->name, 'item' => get_term_link( $term ) );
		}
	} elseif ( is_singular() ) {
		$items[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(), 'item' => get_permalink() );
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

function beautyin_marketplace_webpage_schema() {
	return array(
		'@context'    => 'https://schema.org',
		'@type'       => 'WebPage',
		'@id'         => beautyin_marketplace_current_canonical_url() . '#webpage',
		'url'         => beautyin_marketplace_current_canonical_url(),
		'name'        => beautyin_marketplace_seo_title( wp_get_document_title() ),
		'description' => beautyin_marketplace_seo_description( get_bloginfo( 'description' ) ),
		'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
	);
}

function beautyin_marketplace_faq_schema() {
	$questions = array(
		array( 'Do I need an account to checkout?', 'Yes. Checkout requires login or registration so orders, addresses and invoices stay linked to your account.' ),
		array( 'Can I login with mobile number?', 'Yes. Customer login supports email or saved mobile number with password.' ),
		array( 'Are international products different?', 'Yes. International products can include customs duties, import charges and longer delivery timelines.' ),
		array( 'How do vendors get approved?', 'Vendors register, submit details and wait for admin verification before selling live.' ),
	);
	$main = array();
	foreach ( $questions as $question ) {
		$main[] = array(
			'@type' => 'Question',
			'name'  => $question[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $question[1],
			),
		);
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $main,
	);
}

function beautyin_marketplace_product_vendor_name( $product_id ) {
	$author_id = (int) get_post_field( 'post_author', $product_id );
	if ( ! $author_id ) {
		return '';
	}

	if ( function_exists( 'dokan_get_store_info' ) ) {
		$store = dokan_get_store_info( $author_id );
		if ( ! empty( $store['store_name'] ) ) {
			return sanitize_text_field( $store['store_name'] );
		}
	}

	$user = get_userdata( $author_id );
	return $user ? $user->display_name : '';
}

function beautyin_marketplace_product_image_alt( $attr, $attachment, $size ) {
	if ( ! empty( $attr['alt'] ) || ! is_singular( 'product' ) && ! beautyin_marketplace_is_shop_context() ) {
		return $attr;
	}

	$product_id = get_the_ID();
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( $product ) {
		$category = beautyin_marketplace_primary_product_category( $product_id );
		$attr['alt'] = beautyin_marketplace_trim_meta(
			sprintf( '%s %s Korean beauty product at MOA Beauty India', $product->get_name(), $category ? $category->name : '' ),
			120
		);
	}

	return $attr;
}
