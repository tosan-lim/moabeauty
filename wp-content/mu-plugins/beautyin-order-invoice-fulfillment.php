<?php
/**
 * Plugin Name: Beauty In Order Invoice & Fulfillment
 * Description: Adds Beauty In invoice, email, customer account, vendor dashboard and fulfillment workflow controls for WooCommerce/Dokan orders.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'beautyin_customer_normalize_tax_id' ) ) {
	function beautyin_customer_normalize_tax_id( $value ) {
		$value = strtoupper( trim( preg_replace( '/\s+/', '', (string) $value ) ) );
		return preg_replace( '/[^A-Z0-9]/', '', $value );
	}
}

final class BeautyIn_Order_Invoice_Fulfillment {
	const META_INVOICE_NUMBER = '_beautyin_invoice_number';
	const META_INVOICE_SENT   = '_beautyin_invoice_sent_at';
	const META_ROUND_OFF      = '_beautyin_round_off';
	const META_SHIPPING_GST   = '_beautyin_shipping_gst';
	const META_TRACKING_CARRIER = '_beautyin_delivery_carrier';
	const META_TRACKING_NUMBER  = '_beautyin_delivery_receipt_number';
	const META_TRACKING_URL     = '_beautyin_delivery_tracking_url';
	const SHIPPING_GST_RATE   = 0.18;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_order_statuses' ), 20 );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_order_statuses' ), 20 );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'add_report_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'add_paid_statuses' ) );

		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_order_actions' ), 20, 2 );
		add_action( 'woocommerce_order_action_beautyin_email_invoice', array( __CLASS__, 'handle_email_invoice_action' ) );
		add_action( 'woocommerce_order_action_beautyin_mark_accepted', array( __CLASS__, 'handle_mark_accepted_action' ) );
		add_action( 'woocommerce_order_action_beautyin_mark_packed', array( __CLASS__, 'handle_mark_packed_action' ) );
		add_action( 'woocommerce_order_action_beautyin_mark_out_for_delivery', array( __CLASS__, 'handle_mark_out_for_delivery_action' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_admin_metabox' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'render_admin_tracking_fields' ) );
		add_action( 'woocommerce_admin_process_shop_order_object', array( __CLASS__, 'save_admin_tracking_fields' ), 20 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_admin_tracking_fields_legacy' ), 20, 2 );
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'add_admin_list_action' ), 20, 2 );

		add_action( 'admin_post_beautyin_invoice', array( __CLASS__, 'serve_invoice' ) );
		add_action( 'admin_post_nopriv_beautyin_invoice', array( __CLASS__, 'serve_invoice' ) );

		add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'add_customer_order_action' ), 20, 2 );
		add_action( 'woocommerce_my_account_my_orders_column_woo-orders-tracking', array( __CLASS__, 'render_customer_orders_tracking_column' ), 20 );
		add_action( 'woocommerce_view_order', array( __CLASS__, 'render_customer_order_panel' ), 12 );

		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_admin_tracking_column' ), 25 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_admin_tracking_column' ), 25 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_admin_tracking_column_legacy' ), 25, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_admin_tracking_column_hpos' ), 25, 2 );

		add_action( 'dokan_order_detail_after_order_items', array( __CLASS__, 'render_dokan_order_panel' ), 12 );
		add_action( 'dokan_order_listing_row_before_action_field', array( __CLASS__, 'render_dokan_listing_invoice_cell' ), 12 );
		add_action( 'dokan_order_listing_header_before_action_column', array( __CLASS__, 'render_dokan_listing_invoice_header' ), 12 );

		add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'attach_invoice_to_emails' ), 20, 4 );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'add_invoice_link_to_email' ), 20, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'ensure_invoice_on_checkout' ), 30, 3 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'ensure_invoice_after_payment_complete' ), 20, 1 );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'mark_unpaid_order_payment_failed' ), 20, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'mark_unpaid_order_payment_cancelled' ), 20, 1 );
		add_filter( 'woocommerce_thankyou_order_received_title', array( __CLASS__, 'filter_thankyou_order_received_title' ), 9999, 2 );
		add_filter( 'woocommerce_thankyou_order_received_text', array( __CLASS__, 'filter_thankyou_order_received_text' ), 9999, 2 );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_checkout_adjustments' ), 20 );
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_package_rates' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'filter_cart_item_price' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'filter_cart_item_subtotal' ), 20, 3 );
		add_filter( 'woocommerce_calculated_total', array( __CLASS__, 'round_checkout_total' ), 20, 2 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front_styles' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ), 30 );
	}

	public static function register_order_statuses() {
		$statuses = array(
			'wc-accepted'         => array(
				'label' => 'Accepted',
				'count' => 'Accepted <span class="count">(%s)</span>',
			),
			'wc-packed'           => array(
				'label' => 'Packed',
				'count' => 'Packed <span class="count">(%s)</span>',
			),
			'wc-out-for-delivery' => array(
				'label' => 'Out for delivery',
				'count' => 'Out for delivery <span class="count">(%s)</span>',
			),
		);

		foreach ( $statuses as $status => $args ) {
			register_post_status(
				$status,
				array(
					'label'                     => $args['label'],
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( $args['count'], $args['count'] ),
				)
			);
		}
	}

	public static function add_order_statuses( $statuses ) {
		$insert = array(
			'wc-accepted'         => 'Accepted',
			'wc-packed'           => 'Packed',
			'wc-out-for-delivery' => 'Out for delivery',
		);

		$output = array();
		foreach ( $statuses as $key => $label ) {
			$output[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$output = array_merge( $output, $insert );
			}
		}

		return $output;
	}

	public static function add_report_statuses( $statuses ) {
		foreach ( array( 'accepted', 'packed', 'out-for-delivery', 'updated-tracking', 'partial-shipped', 'delivered' ) as $status ) {
			if ( ! in_array( $status, $statuses, true ) ) {
				$statuses[] = $status;
			}
		}
		return $statuses;
	}

	public static function add_paid_statuses( $statuses ) {
		foreach ( array( 'accepted', 'packed', 'out-for-delivery', 'updated-tracking', 'partial-shipped', 'delivered' ) as $status ) {
			if ( ! in_array( $status, $statuses, true ) ) {
				$statuses[] = $status;
			}
		}
		return $statuses;
	}

	public static function add_order_actions( $actions, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $actions;
		}

		$actions['beautyin_email_invoice']          = 'Email Beauty In invoice to customer, admin and vendor';
		$actions['beautyin_mark_accepted']         = 'Mark order as accepted';
		$actions['beautyin_mark_packed']           = 'Mark order as packed';
		$actions['beautyin_mark_out_for_delivery'] = 'Mark order as out for delivery';

		return $actions;
	}

	public static function add_checkout_adjustments( $cart ) {
		if ( ! $cart instanceof WC_Cart || $cart->is_empty() ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Shipping is folded into the displayed shipping amount to keep checkout concise.
		// The invoice splits the same amount into taxable base and GST for print.
	}

	public static function round_checkout_total( $total, $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $total;
		}

		if ( ! $cart instanceof WC_Cart || $cart->is_empty() ) {
			return $total;
		}

		// Keep cart and checkout on the same total path; only normalize precision here.
		return round( (float) $total, 2 );
	}

	public static function filter_package_rates( $rates, $package ) {
		$quote = self::calculate_speed_post_quote( $package );
		if ( ! $quote ) {
			return $rates;
		}

		$selected_rate_id = '';
		foreach ( $rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}

			if ( 'flat_rate' !== $rate->get_method_id() ) {
				continue;
			}

			if ( '' === $selected_rate_id ) {
				$selected_rate_id = $rate->get_id();
			} else {
				unset( $rates[ $rate->get_id() ] );
				continue;
			}

			$rate->set_label( 'Delivery fee' );
			$rate->set_cost( $quote['base_cost'] );
			$rate->set_tax_status( 'none' );
			$rate->set_taxes( array() );
		}

		if ( '' !== $selected_rate_id && isset( $rates[ $selected_rate_id ] ) ) {
			return array( $selected_rate_id => $rates[ $selected_rate_id ] );
		}

		return $rates;
	}

	public static function filter_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
		if ( is_cart() ) {
			return $price_html;
		}

		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product instanceof WC_Product ) {
			return $price_html;
		}

		$inclusive = wc_get_price_including_tax( $product, array( 'qty' => 1 ) );
		return wc_price( $inclusive, array( 'currency' => get_woocommerce_currency() ) );
	}

	public static function filter_cart_item_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
		if ( is_cart() ) {
			return $subtotal_html;
		}

		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product instanceof WC_Product ) {
			return $subtotal_html;
		}

		$qty       = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
		$inclusive  = wc_get_price_including_tax( $product, array( 'qty' => $qty ) );
		return wc_price( $inclusive, array( 'currency' => get_woocommerce_currency() ) );
	}

	private static function calculate_speed_post_quote( $package ) {
		if ( empty( $package['destination'] ) || ! is_array( $package['destination'] ) ) {
			return false;
		}

		$weight_grams = 0;
		if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
			foreach ( $package['contents'] as $item ) {
				if ( empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
					continue;
				}

				$product_weight = $item['data']->get_weight();
				$product_weight = '' === $product_weight ? 0 : wc_get_weight( (float) $product_weight, 'g' );
				if ( $product_weight <= 0 ) {
					$product_weight = 100;
				}
				$weight_grams += $product_weight * max( 1, (int) $item['quantity'] );
			}
		}

		if ( $weight_grams <= 0 ) {
			return false;
		}

		$band = self::get_speed_post_band( $package['destination'] );
		$table = self::speed_post_tariffs();
		$slab  = self::speed_post_weight_slab( $weight_grams );

		if ( empty( $table[ $slab ][ $band ] ) ) {
			return false;
		}

		$base_cost = (float) $table[ $slab ][ $band ];

		if ( '500+' === $slab ) {
			$extra_steps = (int) ceil( max( 0, $weight_grams - 500 ) / 250 );
			$increment   = isset( $table['increment'][ $band ] ) ? (float) $table['increment'][ $band ] : 15;
			$base_cost   = $base_cost + ( $extra_steps * $increment );
		}

		// Increase courier charge by 50% and round it as the final visible delivery fee.
		$base_cost = round( $base_cost * 1.5, 0 );

		return array(
			'band'      => $band,
			'slab'      => $slab,
			'weight_g'  => $weight_grams,
			'base_cost' => round( $base_cost, wc_get_price_decimals() ),
		);
	}

	private static function get_speed_post_band( $destination ) {
		$state  = strtoupper( isset( $destination['state'] ) ? (string) $destination['state'] : '' );
		$city   = strtolower( isset( $destination['city'] ) ? (string) $destination['city'] : '' );
		$postcode = isset( $destination['postcode'] ) ? preg_replace( '/\D+/', '', (string) $destination['postcode'] ) : '';

		if ( 'DL' === $state || strpos( $city, 'new delhi' ) !== false || strpos( $city, 'delhi' ) !== false || 0 === strpos( $postcode, '110' ) ) {
			return 'local';
		}

		if ( in_array( $state, array( 'HR', 'PB', 'UP', 'RJ', 'CH', 'HP', 'JK', 'UK' ), true ) ) {
			return 'near';
		}

		if ( in_array( $state, array( 'MH', 'GJ', 'KA', 'TN', 'WB', 'TG', 'AP', 'KL', 'OR' ), true ) ) {
			return 'metro';
		}

		return 'far';
	}

	private static function speed_post_tariffs() {
		return array(
			'50'   => array( 'local' => 19, 'near' => 47, 'metro' => 47, 'far' => 47 ),
			'250'  => array( 'local' => 24, 'near' => 59, 'metro' => 59, 'far' => 77 ),
			'500'  => array( 'local' => 28, 'near' => 70, 'metro' => 70, 'far' => 93 ),
			'500+' => array( 'local' => 28, 'near' => 70, 'metro' => 70, 'far' => 93 ),
			'increment' => array( 'local' => 12, 'near' => 15, 'metro' => 15, 'far' => 20 ),
		);
	}

	private static function speed_post_weight_slab( $weight_grams ) {
		if ( $weight_grams <= 50 ) {
			return '50';
		}
		if ( $weight_grams <= 250 ) {
			return '250';
		}
		if ( $weight_grams <= 500 ) {
			return '500';
		}
		return '500+';
	}

	private static function calculate_round_off( $amount ) {
		return round( $amount ) - (float) $amount;
	}

	private static function get_invoice_breakdown( $order ) {
		$product_subtotal = 0.0;
		$product_tax      = 0.0;
		$shipping_total   = 0.0;
		$shipping_gst     = 0.0;
		$shipping_net     = 0.0;
		$round_off        = 0.0;
		$fee_total        = 0.0;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$line_total = (float) $item->get_total();
			$line_tax   = (float) $item->get_total_tax();
			if ( abs( $line_tax ) < 0.0001 && $line_total > 0 ) {
				$line_tax = round( $line_total * self::SHIPPING_GST_RATE, 2 );
			}
			$product_subtotal += $line_total;
			$product_tax      += $line_tax;
		}

		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shipping_total += (float) $item->get_total();
			$shipping_gst   += (float) $item->get_total_tax();
		}

		foreach ( $order->get_items( 'fee' ) as $item ) {
			$label = strtolower( $item->get_name() );
			$amount = (float) $item->get_total();
			if ( false !== strpos( $label, 'round off' ) ) {
				$round_off += $amount;
				continue;
			}
			if ( false !== strpos( $label, 'shipping gst' ) ) {
				$shipping_gst += $amount;
				continue;
			}
			$fee_total += $amount;
		}

		$meta_round_off = (float) $order->get_meta( self::META_ROUND_OFF, true );
		$meta_shipping_gst = (float) $order->get_meta( self::META_SHIPPING_GST, true );
		if ( $shipping_total > 0 ) {
			$shipping_net = round( $shipping_total / ( 1 + self::SHIPPING_GST_RATE ), 2 );
			$shipping_gst = round( $shipping_total - $shipping_net, 2 );
		}
		if ( abs( $shipping_gst ) < 0.0001 && abs( $meta_shipping_gst ) > 0.0001 ) {
			$shipping_gst = $meta_shipping_gst;
		}

		$computed_round_off = round( (float) $order->get_total() - ( $product_subtotal + $product_tax + $shipping_total + $fee_total ), 2 );
		if ( abs( $round_off ) < 0.0001 ) {
			$round_off = $computed_round_off;
		}
		if ( abs( $round_off ) < 0.0001 && abs( $meta_round_off ) > 0.0001 ) {
			$round_off = $meta_round_off;
		}

		return array(
			'product_subtotal' => $product_subtotal,
			'product_tax'      => $product_tax,
			'product_total'    => $product_subtotal + $product_tax,
			'shipping_total'   => $shipping_total,
			'shipping_net'     => $shipping_net,
			'shipping_gst'     => $shipping_gst,
			'round_off'        => $round_off,
			'fee_total'        => $fee_total,
			'grand_total'      => round( $product_subtotal + $product_tax + $shipping_total + $round_off + $fee_total, 2 ),
		);
	}

	private static function build_invoice_rows( $order, $breakdown ) {
		$rows = array();
		$tax_rate = round( self::SHIPPING_GST_RATE * 100, 0 ) . '%';

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$qty = max( 1, (int) $item->get_quantity() );
			$net = (float) $item->get_total();
			$tax = (float) $item->get_total_tax();
			if ( abs( $tax ) < 0.0001 && $net > 0 ) {
				$tax = round( $net * self::SHIPPING_GST_RATE, 2 );
			}
			$product = $item->get_product();
			$rows[] = array(
				'type'           => 'product',
				'description'    => $item->get_name(),
				'sku'            => $product ? $product->get_sku() : '',
				'unit_price'     => round( $net / $qty, 2 ),
				'qty'            => $qty,
				'net_amount'     => round( $net, 2 ),
				'tax_rate_label' => $tax_rate,
				'tax_type'       => 'CGST + SGST',
				'tax_amount'     => round( $tax, 2 ),
				'total_amount'   => round( $net + $tax, 2 ),
			);
		}

		if ( $breakdown['shipping_total'] > 0 ) {
			$rows[] = array(
				'type'           => 'shipping',
				'description'    => 'Delivery fee' . "\n" . 'Courier / freight for the above item(s)',
				'sku'            => '',
				'unit_price'     => round( $breakdown['shipping_net'], 2 ),
				'qty'            => 1,
				'net_amount'     => round( $breakdown['shipping_net'], 2 ),
				'tax_rate_label' => $tax_rate,
				'tax_type'       => 'CGST + SGST',
				'tax_amount'     => round( $breakdown['shipping_gst'], 2 ),
				'total_amount'   => round( $breakdown['shipping_total'], 2 ),
			);
		}

		return $rows;
	}

	private static function amount_to_words( $amount ) {
		$amount = round( (float) $amount, 2 );
		$rupees = (int) floor( $amount );
		$paise = (int) round( ( $amount - $rupees ) * 100 );
		$words = self::number_to_words( $rupees );
		if ( 0 === $paise ) {
			return ucfirst( $words ) . ' rupees only';
		}
		return ucfirst( $words ) . ' rupees and ' . self::number_to_words( $paise ) . ' paise only';
	}

	private static function number_to_words( $number ) {
		$number = (int) $number;
		if ( 0 === $number ) {
			return 'zero';
		}

		$ones = array(
			0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
			10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
			18 => 'eighteen', 19 => 'nineteen',
		);
		$tens = array( 2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty', 6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety' );
		$parts = array();

		$units = array(
			'crore' => 10000000,
			'lakh'  => 100000,
			'thousand' => 1000,
			'hundred' => 100,
		);

		foreach ( $units as $label => $value ) {
			if ( $number >= $value ) {
				$count = (int) floor( $number / $value );
				$parts[] = self::number_to_words( $count ) . ' ' . $label;
				$number = $number % $value;
			}
		}

		if ( $number > 0 ) {
			if ( $number < 20 ) {
				$parts[] = $ones[ $number ];
			} else {
				$t = (int) floor( $number / 10 );
				$r = $number % 10;
				$parts[] = $tens[ $t ] . ( $r ? ' ' . $ones[ $r ] : '' );
			}
		}

		return trim( implode( ' ', $parts ) );
	}

	private static function invoice_qr_svg( $order ) {
		$ref = esc_attr( 'KIL,' . $order->get_order_number() );
		return sprintf(
			'<svg class="qr-svg" viewBox="0 0 88 88" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect width="88" height="88" fill="#fff"/><rect x="2" y="2" width="84" height="84" fill="none" stroke="#111" stroke-width="2"/><rect x="8" y="8" width="18" height="18" fill="#111"/><rect x="12" y="12" width="10" height="10" fill="#fff"/><rect x="62" y="8" width="18" height="18" fill="#111"/><rect x="66" y="12" width="10" height="10" fill="#fff"/><rect x="8" y="62" width="18" height="18" fill="#111"/><rect x="12" y="66" width="10" height="10" fill="#fff"/><rect x="32" y="8" width="6" height="6" fill="#111"/><rect x="40" y="8" width="6" height="6" fill="#111"/><rect x="48" y="8" width="6" height="6" fill="#111"/><rect x="32" y="16" width="6" height="6" fill="#111"/><rect x="48" y="16" width="6" height="6" fill="#111"/><rect x="32" y="24" width="22" height="6" fill="#111"/><rect x="32" y="34" width="6" height="6" fill="#111"/><rect x="42" y="34" width="6" height="6" fill="#111"/><rect x="52" y="34" width="6" height="6" fill="#111"/><rect x="32" y="44" width="6" height="6" fill="#111"/><rect x="42" y="44" width="18" height="6" fill="#111"/><rect x="32" y="54" width="6" height="6" fill="#111"/><rect x="44" y="54" width="6" height="6" fill="#111"/><rect x="54" y="54" width="6" height="6" fill="#111"/><rect x="32" y="64" width="22" height="6" fill="#111"/><text x="44" y="84" text-anchor="middle" font-size="6" fill="#111">%s</text></svg>',
			$ref
		);
	}

	public static function handle_email_invoice_action( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		self::send_invoice_email_bundle( $order );
		$order->add_order_note( 'Beauty In invoice emailed to customer, store admin and vendor.', false, true );
		$order->update_meta_data( self::META_INVOICE_SENT, current_time( 'mysql' ) );
		$order->save();
	}

	public static function handle_mark_accepted_action( $order ) {
		self::update_status_with_note( $order, 'accepted', 'Order accepted by Beauty In.' );
	}

	public static function handle_mark_packed_action( $order ) {
		self::update_status_with_note( $order, 'packed', 'Order packed and ready for dispatch.' );
	}

	public static function handle_mark_out_for_delivery_action( $order ) {
		self::update_status_with_note( $order, 'out-for-delivery', 'Order marked out for delivery.' );
	}

	private static function update_status_with_note( $order, $status, $note ) {
		if ( $order instanceof WC_Order ) {
			$order->update_status( $status, $note, true );
		}
	}

	public static function add_admin_metabox( $screen_id, $object ) {
		$order = self::get_order_from_object( $object );
		if ( ! $order ) {
			return;
		}

		add_meta_box(
			'beautyin-invoice-fulfillment',
			'Beauty In invoice & fulfillment',
			array( __CLASS__, 'render_admin_metabox' ),
			$screen_id,
			'side',
			'high'
		);
	}

	public static function render_admin_metabox( $object ) {
		$order = self::get_order_from_object( $object );
		if ( ! $order ) {
			return;
		}

		$invoice_url = self::invoice_url( $order, false );
		$download_url = self::invoice_url( $order, true );
		$sent_at = $order->get_meta( self::META_INVOICE_SENT, true );
		?>
		<div class="beautyin-order-tools">
			<p><strong>Invoice number:</strong><br><?php echo esc_html( self::ensure_invoice_number( $order ) ); ?></p>
			<p class="beautyin-order-tools__buttons">
				<a class="button button-primary" href="<?php echo esc_url( $invoice_url ); ?>" target="_blank" rel="noopener">Open invoice</a>
				<a class="button" href="<?php echo esc_url( $download_url ); ?>">Download</a>
			</p>
			<p><strong>Email status:</strong><br><?php echo $sent_at ? esc_html( $sent_at ) : 'Not sent from Beauty In action yet.'; ?></p>
			<?php self::render_tracking_summary( $order ); ?>
			<?php self::render_fulfillment_steps( $order ); ?>
			<p class="description">Use the Order actions dropdown to email the invoice or move the order through accepted, packed and out-for-delivery.</p>
		</div>
		<?php
	}

	public static function render_admin_tracking_fields( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$carrier = $order->get_meta( self::META_TRACKING_CARRIER, true );
		$number  = $order->get_meta( self::META_TRACKING_NUMBER, true );
		$url     = $order->get_meta( self::META_TRACKING_URL, true );
		?>
		<div class="beautyin-order-tracking-fields">
			<h4 style="margin:16px 0 8px;">Delivery tracking</h4>
			<p class="form-field form-field-wide">
				<label for="beautyin_delivery_carrier">Courier / carrier</label>
				<input type="text" class="short" name="beautyin_delivery_carrier" id="beautyin_delivery_carrier" value="<?php echo esc_attr( $carrier ); ?>" placeholder="e.g. Delhivery, Blue Dart, Shiprocket">
			</p>
			<p class="form-field form-field-wide">
				<label for="beautyin_delivery_receipt_number">Delivery receipt / tracking number</label>
				<input type="text" class="short" name="beautyin_delivery_receipt_number" id="beautyin_delivery_receipt_number" value="<?php echo esc_attr( $number ); ?>" placeholder="Enter tracking or receipt number">
			</p>
			<p class="form-field form-field-wide">
				<label for="beautyin_delivery_tracking_url">Tracking link</label>
				<input type="url" class="short" name="beautyin_delivery_tracking_url" id="beautyin_delivery_tracking_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://...">
			</p>
		</div>
		<?php
	}

	public static function save_admin_tracking_fields( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		self::save_tracking_posted_fields( $order );
	}

	public static function save_admin_tracking_fields_legacy( $order_id, $post ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		self::save_tracking_posted_fields( $order );
	}

	private static function save_tracking_posted_fields( $order ) {
		$carrier = isset( $_POST['beautyin_delivery_carrier'] ) ? sanitize_text_field( wp_unslash( $_POST['beautyin_delivery_carrier'] ) ) : '';
		$number  = isset( $_POST['beautyin_delivery_receipt_number'] ) ? sanitize_text_field( wp_unslash( $_POST['beautyin_delivery_receipt_number'] ) ) : '';
		$url     = isset( $_POST['beautyin_delivery_tracking_url'] ) ? esc_url_raw( wp_unslash( $_POST['beautyin_delivery_tracking_url'] ) ) : '';

		$order->update_meta_data( self::META_TRACKING_CARRIER, $carrier );
		$order->update_meta_data( self::META_TRACKING_NUMBER, $number );
		$order->update_meta_data( self::META_TRACKING_URL, $url );
		$order->save();
	}

	public static function add_admin_list_action( $actions, $order ) {
		if ( ! is_admin() || ! $order instanceof WC_Order || ! self::current_user_can_view_invoice( $order ) ) {
			return $actions;
		}

		$actions['beautyin_invoice'] = array(
			'url'    => self::invoice_url( $order, false ),
			'name'   => 'Invoice',
			'action' => 'beautyin-invoice',
			'icon'   => '<span class="dashicons dashicons-media-document"></span>',
		);

		return $actions;
	}

	public static function add_admin_tracking_column( $columns ) {
		$output = array();
		foreach ( $columns as $key => $label ) {
			$output[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$output['beautyin_tracking_state'] = 'Tracking';
			}
		}

		if ( ! isset( $output['beautyin_tracking_state'] ) ) {
			$output['beautyin_tracking_state'] = 'Tracking';
		}

		return $output;
	}

	public static function render_admin_tracking_column_legacy( $column, $post_id ) {
		if ( 'beautyin_tracking_state' !== $column ) {
			return;
		}

		$order = wc_get_order( $post_id );
		self::render_admin_tracking_badge( $order );
	}

	public static function render_admin_tracking_column_hpos( $column, $order ) {
		if ( 'beautyin_tracking_state' !== $column ) {
			return;
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		self::render_admin_tracking_badge( $order );
	}

	private static function render_admin_tracking_badge( $order ) {
		if ( ! $order instanceof WC_Order ) {
			echo '&ndash;';
			return;
		}

		$tracking = self::build_tracking_summary( $order );
		$has_saved = ! empty( $tracking['number'] ) || ! empty( $tracking['carrier'] ) || ! empty( $tracking['url'] );
		$badge_class = $has_saved ? 'beautyin-tracking-badge beautyin-tracking-badge--saved' : 'beautyin-tracking-badge beautyin-tracking-badge--missing';
		$label = $has_saved ? 'Tracking saved' : 'Not saved';

		echo '<div class="' . esc_attr( $badge_class ) . '">';
		echo '<span>' . esc_html( $label ) . '</span>';
		if ( $tracking['number'] ) {
			echo '<small>' . esc_html( $tracking['number'] ) . '</small>';
		}
		if ( $tracking['url'] ) {
			echo '<a href="' . esc_url( $tracking['url'] ) . '" target="_blank" rel="noopener">Open link</a>';
		}
		echo '</div>';
	}

	public static function ensure_invoice_on_checkout( $order_id, $posted_data, $order ) {
		if ( $order instanceof WC_Order ) {
			$payment_method = (string) $order->get_payment_method();
			$should_generate = $order->is_paid() || 'cod' === $payment_method;

			// Keep Razorpay / online orders unconfirmed until payment is actually completed.
			if ( ! $should_generate ) {
				return;
			}

			self::ensure_invoice_number( $order );
			self::sync_invoice_meta( $order );
			self::write_invoice_file( $order );
		}
	}

	public static function ensure_invoice_after_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			self::ensure_invoice_number( $order );
			self::sync_invoice_meta( $order );
			self::write_invoice_file( $order );
		}
	}

	public static function filter_thankyou_order_received_title( $title, $order ) {
		$order = self::resolve_thankyou_order( $order );
		if ( ! $order instanceof WC_Order ) {
			return $title;
		}

		if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			return 'Payment pending';
		}

		if ( $order->has_status( array( 'failed', 'cancelled' ) ) ) {
			return 'Payment not completed';
		}

		return $order->is_paid() ? 'Order confirmed' : $title;
	}

	public static function filter_thankyou_order_received_text( $text, $order ) {
		$order = self::resolve_thankyou_order( $order );
		if ( ! $order instanceof WC_Order ) {
			return 'Payment is pending. Tap Pay now to confirm your order, or cancel if you do not want to continue.';
		}

		if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			return 'Payment is pending. Tap Pay now to confirm your order, or cancel if you do not want to continue.';
		}

		if ( $order->has_status( array( 'failed', 'cancelled' ) ) ) {
			return 'Payment was not completed. Tap Pay now to retry or cancel if you want to stop here.';
		}

		if ( $order->is_paid() ) {
			return 'Thank you. Your order has been confirmed.';
		}

		return $text;
	}

	public static function mark_unpaid_order_payment_failed( $order_id ) {
		self::mark_unpaid_order_payment_status( $order_id, 'failed', 'Payment failed before confirmation. Customer can retry payment.' );
	}

	public static function mark_unpaid_order_payment_cancelled( $order_id ) {
		self::mark_unpaid_order_payment_status( $order_id, 'cancelled', 'Payment cancelled before confirmation. Order remains unconfirmed.' );
	}

	private static function mark_unpaid_order_payment_status( $order_id, $status, $note ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( $order->is_paid() ) {
			return;
		}

		$order->update_meta_data( '_beautyin_payment_confirmation_state', $status );
		$order->add_order_note( $note, false, true );
		$order->save();
	}

	private static function resolve_thankyou_order( $order ) {
		if ( $order instanceof WC_Order ) {
			return $order;
		}

		$order_id = 0;
		if ( function_exists( 'get_query_var' ) ) {
			$order_id = absint( get_query_var( 'order-received' ) );
		}

		if ( ! $order_id && isset( $GLOBALS['wp']->query_vars['order-received'] ) ) {
			$order_id = absint( $GLOBALS['wp']->query_vars['order-received'] );
		}

		if ( ! $order_id ) {
			return null;
		}

		$order_key = '';
		if ( isset( $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_key = wc_clean( wp_unslash( $_GET['key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$resolved = wc_get_order( $order_id );
		if ( ! $resolved instanceof WC_Order ) {
			return null;
		}

		if ( $order_key && $resolved->get_order_key() !== $order_key ) {
			return null;
		}

		return $resolved;
	}

	public static function add_customer_order_action( $actions, $order ) {
		if ( $order instanceof WC_Order && self::current_user_can_view_invoice( $order ) ) {
			$actions['beautyin_invoice'] = array(
				'url'  => self::invoice_url( $order, false ),
				'name' => 'Invoice',
			);

			if ( $order->needs_payment() ) {
				$actions['beautyin_pay_now'] = array(
					'url'  => $order->get_checkout_payment_url(),
					'name' => 'Pay now',
				);
			}
		}
		return $actions;
	}

	public static function render_customer_orders_tracking_column( $order ) {
		if ( ! $order instanceof WC_Order ) {
			echo '&ndash;';
			return;
		}

		$tracking = self::build_tracking_summary( $order );
		$track_page = function_exists( 'beautyin_page_suite_page_url' ) ? beautyin_page_suite_page_url( 'track-order' ) : home_url( '/track-order/' );
		$track_url  = add_query_arg(
			array( 'bm_track_order_id' => $order->get_id() ),
			$track_page
		);

		echo '<div class="beautyin-order-track-cell">';
		if ( $tracking['url'] ) {
			echo '<a class="button button-small" href="' . esc_url( $tracking['url'] ) . '" target="_blank" rel="noopener">Carrier link</a>';
		}
		echo '<a class="button button-small" href="' . esc_url( $track_url ) . '">Track order</a>';
		if ( $tracking['number'] ) {
			echo '<small>' . esc_html( $tracking['number'] ) . '</small>';
		} else {
			echo '<small>Tracking not added yet</small>';
		}
		echo '</div>';
	}

	public static function render_customer_order_panel( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! self::current_user_can_view_invoice( $order ) ) {
			return;
		}

		$tracking = self::build_tracking_summary( $order );

		?>
		<section class="beautyin-account-order-panel">
			<h2>Invoice & delivery</h2>
			<div class="beautyin-account-order-panel__grid">
				<div>
					<strong>Invoice</strong>
					<p><?php echo esc_html( self::ensure_invoice_number( $order ) ); ?></p>
					<a class="button" href="<?php echo esc_url( self::invoice_url( $order, false ) ); ?>" target="_blank" rel="noopener">View invoice</a>
					<a class="button" href="<?php echo esc_url( self::invoice_url( $order, true ) ); ?>">Download invoice</a>
				</div>
				<div>
					<strong>Order status</strong>
					<p><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></p>
					<?php if ( $tracking['number'] ) : ?>
						<p><strong>Tracking no:</strong> <?php echo esc_html( $tracking['number'] ); ?></p>
					<?php endif; ?>
					<?php if ( $tracking['carrier'] ) : ?>
						<p><strong>Courier:</strong> <?php echo esc_html( $tracking['carrier'] ); ?></p>
					<?php endif; ?>
					<?php if ( $tracking['url'] ) : ?>
						<p><a class="button" href="<?php echo esc_url( $tracking['url'] ); ?>" target="_blank" rel="noopener">Open tracking link</a></p>
					<?php endif; ?>
					<?php self::render_tracking_summary( $order ); ?>
				</div>
			</div>
		</section>
		<?php
	}

	public static function render_dokan_listing_invoice_header() {
		echo '<th class="beautyin-dokan-invoice-col">Invoice</th>';
	}

	public static function render_dokan_listing_invoice_cell( $order ) {
		if ( ! $order instanceof WC_Order || ! self::current_user_can_view_invoice( $order ) ) {
			echo '<td class="beautyin-dokan-invoice-col" data-title="Invoice">-</td>';
			return;
		}

		printf(
			'<td class="beautyin-dokan-invoice-col" data-title="Invoice"><a class="dokan-btn dokan-btn-default dokan-btn-sm" href="%s" target="_blank" rel="noopener">Invoice</a></td>',
			esc_url( self::invoice_url( $order, false ) )
		);
	}

	public static function render_dokan_order_panel( $order ) {
		if ( ! $order instanceof WC_Order || ! self::current_user_can_view_invoice( $order ) ) {
			return;
		}

		?>
		<div class="dokan-panel dokan-panel-default beautyin-dokan-order-panel">
			<div class="dokan-panel-heading"><strong>Beauty In invoice & fulfillment</strong></div>
			<div class="dokan-panel-body">
				<p><strong>Invoice:</strong> <?php echo esc_html( self::ensure_invoice_number( $order ) ); ?></p>
				<p>
					<a class="dokan-btn dokan-btn-theme dokan-btn-sm" href="<?php echo esc_url( self::invoice_url( $order, false ) ); ?>" target="_blank" rel="noopener">Open invoice</a>
					<a class="dokan-btn dokan-btn-default dokan-btn-sm" href="<?php echo esc_url( self::invoice_url( $order, true ) ); ?>">Download invoice</a>
				</p>
				<?php self::render_fulfillment_steps( $order ); ?>
				<?php self::render_tracking_summary( $order ); ?>
			</div>
		</div>
		<?php
	}

	public static function attach_invoice_to_emails( $attachments, $email_id, $object, $email = null ) {
		if ( ! $object instanceof WC_Order ) {
			return $attachments;
		}

		$allowed = array(
			'new_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_invoice',
			'customer_note',
			'dokan_vendor_new_order',
			'dokan_vendor_completed_order',
		);

		if ( ! in_array( $email_id, $allowed, true ) ) {
			return $attachments;
		}

		$file = self::write_invoice_file( $object );
		if ( $file && file_exists( $file ) ) {
			$attachments[] = $file;
		}

		return $attachments;
	}

	public static function add_invoice_link_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$url = self::invoice_url( $order, false, true );
		if ( $plain_text ) {
			echo "\nInvoice: " . esc_url_raw( $url ) . "\n";
			return;
		}

		?>
		<p style="margin:18px 0;">
			<a href="<?php echo esc_url( $url ); ?>" style="background:#6f3f8f;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:4px;display:inline-block;">View Beauty In invoice</a>
		</p>
		<?php
	}

	public static function send_invoice_email_bundle( $order ) {
		$recipients = array_filter(
			array_unique(
				array_merge(
					array( $order->get_billing_email(), get_option( 'admin_email' ) ),
					self::get_vendor_emails( $order )
				)
			)
		);

		if ( empty( $recipients ) ) {
			return false;
		}

		$invoice_file = self::write_invoice_file( $order );
		$subject = sprintf( 'Invoice for Beauty In order %s', $order->get_order_number() );
		$link = self::invoice_url( $order, false, true );
		$message = self::email_invoice_message( $order, $link );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$attachments = $invoice_file ? array( $invoice_file ) : array();

		return wp_mail( $recipients, $subject, $message, $headers, $attachments );
	}

	private static function email_invoice_message( $order, $link ) {
		ob_start();
		?>
		<p>Hello,</p>
		<p>The Beauty In invoice for order <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> is attached.</p>
		<p><a href="<?php echo esc_url( $link ); ?>">Open invoice online</a></p>
		<p>Order status: <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></p>
		<?php
		return ob_get_clean();
	}

	public static function serve_invoice() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_die( 'Invoice not found.', 404 );
		}

		$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		$nonce = isset( $_GET['_wpnonce'] ) ? wc_clean( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$valid_key = $key && hash_equals( $order->get_order_key(), $key );
		$valid_nonce = $nonce && wp_verify_nonce( $nonce, 'beautyin_invoice_' . $order->get_id() );

		if ( ! $valid_key && ( ! $valid_nonce || ! self::current_user_can_view_invoice( $order ) ) ) {
			wp_die( 'You are not allowed to view this invoice.', 403 );
		}

		$html = self::render_invoice_html( $order );
		$filename = sanitize_file_name( 'beautyin-invoice-' . $order->get_order_number() . '.html' );

		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		if ( ! empty( $_GET['download'] ) ) {
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function invoice_url( $order, $download = false, $with_key = false ) {
		$args = array(
			'action'   => 'beautyin_invoice',
			'order_id' => $order->get_id(),
		);

		if ( $download ) {
			$args['download'] = 1;
		}

		if ( $with_key ) {
			$args['key'] = $order->get_order_key();
			return add_query_arg( $args, admin_url( 'admin-post.php' ) );
		}

		$url = add_query_arg( $args, admin_url( 'admin-post.php' ) );
		return wp_nonce_url( $url, 'beautyin_invoice_' . $order->get_id() );
	}

	private static function write_invoice_file( $order ) {
		$upload = wp_upload_dir();
		if ( empty( $upload['basedir'] ) ) {
			return '';
		}

		$dir = trailingslashit( $upload['basedir'] ) . 'beautyin-invoices';
		if ( ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		$file = trailingslashit( $dir ) . sanitize_file_name( 'beautyin-invoice-' . $order->get_order_number() . '.html' );
		file_put_contents( $file, self::render_invoice_html( $order ) );

		return $file;
	}

	private static function render_invoice_html( $order ) {
		$invoice_number = self::ensure_invoice_number( $order );
		$seller = self::get_invoice_seller_details();
		$logo = self::get_logo_url();
		$breakdown = self::get_invoice_breakdown( $order );
		$order_created = $order->get_date_created();
		$order_paid = $order->get_date_paid();
		$order_date = $order_created ? date_i18n( 'd M Y', $order_created->getTimestamp() ) : date_i18n( 'd M Y', current_time( 'timestamp' ) );
		$invoice_date = $order_paid ? date_i18n( 'd M Y', $order_paid->getTimestamp() ) : date_i18n( 'd M Y', current_time( 'timestamp' ) );
		$shipping_method = $order->get_shipping_method() ? $order->get_shipping_method() : 'India Post Speed Post';
		$payment_method = $order->get_payment_method_title() ? $order->get_payment_method_title() : 'Credit Card/Debit Card/NetBanking';
		$order_total = (float) $breakdown['grand_total'];
		$invoice_ref = 'KIL-' . $order->get_order_number();
		$invoice_details = $order->get_transaction_id() ? $order->get_transaction_id() : 'pay_' . substr( sha1( (string) $order->get_id() ), 0, 12 );
		$amount_words = self::amount_to_words( $order_total );
		$rows = self::build_invoice_rows( $order, $breakdown );
		$invoice_qr_text = esc_url_raw( $order->get_view_order_url() );
		$customer_user = $order->get_customer_id() ? get_userdata( $order->get_customer_id() ) : false;
		$customer_email = $order->get_billing_email() ? $order->get_billing_email() : ( $customer_user ? $customer_user->user_email : '' );
		$customer_tax   = self::get_customer_tax_details( $order );

		ob_start();
		?>
<!doctype html>
<html>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<title><?php echo esc_html( $invoice_ref ); ?></title>
	<style>
		:root{--ink:#111827;--muted:#4b5563;--line:#adb5c3;--border:#8a93a3}
		*{box-sizing:border-box}
		body{margin:0;background:#fff;color:var(--ink);font:11px/1.22 Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}
		@page{size:A4 portrait;margin:5mm}
		.invoice{width:200mm;max-width:none;margin:0 auto;background:#fff;padding:11px 12px 10px}
		.topline{display:grid;grid-template-columns:108px 1fr 150px;gap:10px;align-items:start}
		.logo-wrap{height:74px;display:flex;align-items:flex-start;justify-content:flex-start}
		.logo-wrap img{max-width:100%;max-height:74px;object-fit:contain}
		.center-head{text-align:center;padding-top:0}
		.center-head .title{font-size:21px;line-height:.8;font-weight:900;letter-spacing:0;text-transform:uppercase}
		.center-head .subtitle{margin-top:1px;font-size:10px;line-height:1.0;font-weight:900;letter-spacing:0;text-transform:uppercase}
		.center-head .memo{margin-top:1px;font-size:8.4px;line-height:1.02;font-weight:700}
		.right-head{text-align:right}
		.order-box{display:inline-block;border:1px solid #7a7a7a;padding:5px 9px;min-width:140px;text-align:left}
		.order-box .label{font-size:9px;font-weight:700;letter-spacing:0;text-transform:uppercase}
		.order-box .value{font-size:15px;font-weight:800;margin-top:2px}
		.meta-strip{display:grid;grid-template-columns:repeat(5,1fr);margin-top:12px;border:1px solid var(--border)}
		.meta-cell{padding:6px 10px;min-height:36px;border-right:1px solid var(--border)}
		.meta-cell:last-child{border-right:none}
		.meta-cell .label{font-size:9.5px;font-weight:700;text-transform:uppercase;line-height:1.08}
		.meta-cell .value{margin-top:4px;font-size:10.8px;font-weight:700;word-break:break-word}
		.main-grid{display:grid;grid-template-columns:1.02fr 1fr 1fr;gap:8px;margin-top:7px;align-items:stretch}
		.seller-col{display:flex;flex-direction:column;gap:6px}
		.seller-block{padding:2px 0 0}
		.seller-block .label{font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:4px}
		.seller-name{font-size:10.8px;font-weight:700;margin-bottom:2px}
		.seller-line{font-size:9.2px;line-height:1.12}
		.payment-box,.addr-box{border:1px solid var(--border);padding:7px 9px;min-height:180px;height:100%}
		.payment-box{min-height:102px}
		.box-title{font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:5px}
		.box-subtitle{font-size:9.2px;line-height:1.1}
		.addr-box strong{display:block;font-size:10.8px;margin-bottom:3px}
		.addr-box .address{margin-bottom:6px}
		.address{font-size:9.2px;line-height:1.12;white-space:pre-line;margin:0 0 4px}
		.table-wrap{margin-top:7px}
		.items{width:100%;border-collapse:collapse;table-layout:fixed}
		.items th,.items td{border:1px solid var(--border);padding:4px 5px;vertical-align:top}
		.items th{font-size:8px;line-height:1.0;text-transform:uppercase;font-weight:800;text-align:center}
		.items .num{text-align:right;white-space:nowrap}
		.items .center{text-align:center}
		.items .desc{font-weight:700;line-height:1.2;white-space:pre-line}
		.items .sub{display:block;font-weight:400;font-size:8.8px;margin-top:2px}
		.total-row td{font-weight:700}
		.total-row td:first-child{text-align:right}
		.summary-row{display:grid;grid-template-columns:minmax(0,1.72fr) minmax(0,1fr);gap:10px;margin-top:8px;align-items:stretch}
		.words,.qr{border:1px solid var(--border);padding:7px 9px;min-height:102px;width:100%}
		.words,.qr{min-width:0}
		.words .label,.qr .label,.footer-block .label{font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:6px}
		.qr-inner{display:grid;grid-template-columns:86px 1fr;gap:8px;align-items:start}
		.qr-box{width:86px;height:86px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:#fff;overflow:hidden}
		.qr-box canvas,.qr-box table,.qr-box svg{width:84px!important;height:84px!important;display:block}
		.note{margin-top:7px;font-size:10px;line-height:1.26}
		.footer-meta{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:7px}
		.footer-block{border:1px solid var(--border);padding:6px 9px;min-height:48px}
		.gst-note{margin-top:6px;font-size:9.7px;line-height:1.26}
		.topline,.meta-strip,.main-grid,.table-wrap,.summary-row,.footer-meta{break-inside:avoid;page-break-inside:avoid}
		.actions{display:none}
		@media print{
			html,body{width:210mm;height:297mm}
			body{background:#fff;margin:0!important;padding:0!important;display:block;position:relative}
			.invoice{
				width:190mm;
				max-width:none;
				min-height:287mm;
				margin:0;
				padding:0;
				overflow:hidden;
				position:relative;
				left:50%;
				transform:translateX(-50%);
			}
			.topline{grid-template-columns:108px 1fr 150px;gap:10px}
			.meta-strip{display:grid;grid-template-columns:repeat(5,1fr);margin-top:10px}
			.main-grid{display:grid;grid-template-columns:1.02fr 1fr 1fr;gap:8px;margin-top:6px}
			.summary-row{display:grid;grid-template-columns:minmax(0,1.72fr) minmax(0,1fr);gap:10px;margin-top:7px;align-items:stretch}
			.footer-meta{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:6px}
			.meta-strip{margin-top:9px}
			.main-grid{margin-top:6px}
			.table-wrap{margin-top:7px}
			.summary-row{margin-top:7px}
			.footer-meta{margin-top:7px}
			.actions{display:none}
			*{-webkit-print-color-adjust:exact;print-color-adjust:exact}
		}
		@media screen and (max-width:900px){.topline,.meta-strip,.main-grid,.summary-row,.footer-meta{grid-template-columns:1fr;display:grid}.right-head{text-align:left}}
	</style>
</head>
<body>
	<div class="invoice">
		<header class="topline">
			<div class="logo-wrap"><?php if ( $logo ) : ?><img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $seller['display_name'] ); ?>"><?php endif; ?></div>
			<div class="center-head">
				<div class="title">TAX</div>
				<div class="title">INVOICE</div>
				<div class="subtitle">BILL OF SUPPLY / CASH</div>
				<div class="subtitle">MEMO</div>
				<div class="memo">Original for Recipient</div>
				<div class="memo"><?php echo esc_html( $seller['display_name'] ); ?></div>
			</div>
			<div class="right-head">
				<div class="order-box">
					<div class="label">Order Number</div>
					<div class="value">#<?php echo esc_html( $order->get_order_number() ); ?></div>
				</div>
			</div>
		</header>

		<div class="meta-strip">
			<div class="meta-cell">
				<div class="label">Order Number</div>
				<div class="value">#<?php echo esc_html( $order->get_order_number() ); ?></div>
			</div>
			<div class="meta-cell">
				<div class="label">Invoice Number</div>
				<div class="value"><?php echo esc_html( $invoice_ref ); ?></div>
			</div>
			<div class="meta-cell">
				<div class="label">Invoice Date</div>
				<div class="value"><?php echo esc_html( $invoice_date ); ?></div>
			</div>
			<div class="meta-cell">
				<div class="label">Order Date</div>
				<div class="value"><?php echo esc_html( $order_date ); ?></div>
			</div>
			<div class="meta-cell">
				<div class="label">Invoice Details</div>
				<div class="value"><?php echo esc_html( $invoice_details ); ?></div>
			</div>
		</div>

		<div class="main-grid">
			<div class="seller-col">
				<div class="seller-block">
					<div class="label">Seller</div>
					<div class="seller-name"><?php echo esc_html( $seller['display_name'] ); ?></div>
					<div class="seller-line"><?php echo wp_kses_post( self::format_invoice_address( $seller['address_lines'] ) ); ?></div>
					<div class="seller-line">Email: <?php echo esc_html( $seller['email'] ); ?></div>
					<div class="seller-line">Contact: <?php echo esc_html( $seller['contact'] ); ?></div>
					<div class="seller-line">PAN No: <?php echo esc_html( $seller['pan'] ); ?></div>
					<div class="seller-line">GST Registration No: <?php echo esc_html( $seller['gst'] ); ?></div>
				</div>
				<div class="payment-box">
					<div class="box-title">Shipment / Payment</div>
					<div class="box-subtitle">Method: <?php echo esc_html( $payment_method ); ?></div>
					<div class="box-subtitle">Txs ID: <?php echo esc_html( $invoice_details ); ?></div>
					<div class="box-subtitle">Supply: Delhi (State/UT Code: 07)</div>
					<div class="box-subtitle">Delivery: Delhi (State/UT Code: 07)</div>
					<div class="box-subtitle">Shipping: Same as billing</div>
				</div>
			</div>
			<div class="addr-box">
				<div class="box-title">Billing Address</div>
				<strong><?php echo esc_html( trim( $order->get_formatted_billing_full_name() ) ); ?></strong>
				<div class="address"><?php echo wp_kses_post( self::format_invoice_address( self::get_order_address_lines( $order, 'billing' ) ) ); ?></div>
				<div class="seller-line">Phone: <?php echo esc_html( $order->get_billing_phone() ? $order->get_billing_phone() : 'N/A' ); ?></div>
				<?php if ( $customer_tax['gst'] ) : ?>
					<div class="seller-line">Customer GST No: <?php echo esc_html( $customer_tax['gst'] ); ?></div>
				<?php endif; ?>
				<?php if ( $customer_tax['pan'] ) : ?>
					<div class="seller-line">Customer PAN No: <?php echo esc_html( $customer_tax['pan'] ); ?></div>
				<?php endif; ?>
				<?php if ( $customer_tax['confirmation'] && ! $customer_tax['gst'] && ! $customer_tax['pan'] ) : ?>
					<div class="seller-line">Customer tax declaration: GST/PAN not required for this order.</div>
				<?php endif; ?>
				<div class="seller-line">State/UT Code: 07</div>
			</div>
			<div class="addr-box">
				<div class="box-title">Shipping Address</div>
				<strong><?php echo esc_html( trim( $order->get_formatted_shipping_full_name() ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name() ) ); ?></strong>
				<div class="address"><?php echo wp_kses_post( self::format_invoice_address( self::get_order_address_lines( $order, 'shipping' ) ) ); ?></div>
				<div class="seller-line">State/UT Code: 07</div>
			</div>
		</div>

		<div class="table-wrap">
		<table class="items">
			<thead>
				<tr>
					<th style="width:5%">Sl. No.</th>
					<th style="width:42%">Description</th>
					<th style="width:10%">Unit Price</th>
					<th style="width:6%">Qty</th>
					<th style="width:10%">Net Amount</th>
					<th style="width:8%">Tax Rate</th>
					<th style="width:10%">Tax Type</th>
					<th style="width:9%">Tax Amount</th>
					<th style="width:10%">Total Amount</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $index => $row ) : ?>
					<tr>
						<td class="center"><?php echo esc_html( isset( $row['type'] ) && 'shipping' === $row['type'] ? '' : $index + 1 ); ?></td>
						<td>
							<div class="desc"><?php echo esc_html( $row['description'] ); ?></div>
							<?php if ( ! empty( $row['sku'] ) ) : ?><span class="sub">SKU: <?php echo esc_html( $row['sku'] ); ?></span><?php endif; ?>
						</td>
						<td class="num"><?php echo wp_kses_post( wc_price( $row['unit_price'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
						<td class="center"><?php echo esc_html( $row['qty'] ); ?></td>
						<td class="num"><?php echo wp_kses_post( wc_price( $row['net_amount'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
						<td class="center"><?php echo esc_html( $row['tax_rate_label'] ); ?></td>
						<td class="center"><?php echo esc_html( $row['tax_type'] ); ?></td>
						<td class="num"><?php echo wp_kses_post( wc_price( $row['tax_amount'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
						<td class="num"><?php echo wp_kses_post( wc_price( $row['total_amount'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr class="total-row">
					<td colspan="4" class="num">TOTAL</td>
					<td class="num"><?php echo wp_kses_post( wc_price( $breakdown['product_subtotal'] + $breakdown['shipping_net'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
					<td></td>
					<td></td>
					<td class="num"><?php echo wp_kses_post( wc_price( $breakdown['product_tax'] + $breakdown['shipping_gst'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
					<td class="num"><?php echo wp_kses_post( wc_price( $order_total, array( 'currency' => $order->get_currency() ) ) ); ?></td>
				</tr>
			</tbody>
		</table>
		</div>

		<div class="summary-row">
			<div class="words">
				<div class="label">Amount in Words</div>
				<div><?php echo esc_html( $amount_words ); ?></div>
				<div class="note">GST is shown separately for print on taxable product and delivery values. This is a system generated invoice from <?php echo esc_html( rtrim( $seller['display_name'], '.' ) ); ?>.</div>
			</div>
			<div class="qr">
				<div class="label">QR Reference</div>
				<div class="qr-inner">
					<div class="qr-box" id="invoice-qr" aria-label="Invoice QR code"></div>
					<div>
						<p>Scan the QR code below to open the invoice and order details.</p>
						<p><strong>INVOICE REF:</strong> <?php echo esc_html( $invoice_ref ); ?></p>
						<p><strong>Payment status:</strong> <?php echo esc_html( $order->is_paid() ? 'Paid' : 'Pending' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-meta">
			<div class="footer-block">
				<div class="label">Customer Email</div>
				<div><?php echo esc_html( $customer_email ); ?></div>
			</div>
			<div class="footer-block">
				<div class="label">Reverse Charge</div>
				<div>Whether tax is payable under reverse charge - No</div>
			</div>
			<div class="footer-block">
				<div class="label">Order Status</div>
				<div><?php echo esc_html( $order->get_status() ); ?></div>
			</div>
		</div>

		<div class="gst-note">This is a system generated invoice. For refunds, support and shipment queries, contact <?php echo esc_html( $seller['email'] ); ?> with your order number.</div>
	</div>
	<script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
	<script src="<?php echo esc_url( content_url( 'plugins/woocommerce/assets/js/jquery-qrcode/jquery.qrcode.js' ) ); ?>"></script>
	<script>
	(function($){
		$(function(){
			var $qr = $('#invoice-qr');
			if ($qr.length && $.fn.qrcode) {
				$qr.empty().qrcode({
					render: 'canvas',
					width: 84,
					height: 84,
					text: <?php echo wp_json_encode( $invoice_qr_text ); ?>,
					correctLevel: QRErrorCorrectLevel.H
				});
			}
		});
	})(jQuery);
	</script>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	private static function ensure_invoice_number( $order ) {
		$invoice_number = $order->get_meta( self::META_INVOICE_NUMBER, true );
		if ( $invoice_number ) {
			return $invoice_number;
		}

		$created = $order->get_date_created();
		$year = $created ? $created->date_i18n( 'Y' ) : date_i18n( 'Y' );
		$invoice_number = sprintf( 'BI-%s-%06d', $year, $order->get_id() );
		$order->update_meta_data( self::META_INVOICE_NUMBER, $invoice_number );
		$order->save();

		return $invoice_number;
	}

	private static function sync_invoice_meta( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$breakdown = self::get_invoice_breakdown( $order );
		$order->update_meta_data( self::META_ROUND_OFF, $breakdown['round_off'] );
		$order->update_meta_data( self::META_SHIPPING_GST, $breakdown['shipping_gst'] );
		$order->save();
	}

	private static function get_order_from_object( $object ) {
		if ( $object instanceof WC_Order ) {
			return $object;
		}

		if ( $object instanceof WP_Post ) {
			return wc_get_order( $object->ID );
		}

		return false;
	}

	private static function current_user_can_view_invoice( $order ) {
		if ( current_user_can( 'edit_shop_order', $order->get_id() ) || current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( $user_id && (int) $order->get_customer_id() === $user_id ) {
			return true;
		}

		return $user_id && self::user_is_order_vendor( $order, $user_id );
	}

	private static function user_is_order_vendor( $order, $user_id ) {
		if ( function_exists( 'dokan_get_seller_id_by_order' ) && (int) dokan_get_seller_id_by_order( $order->get_id() ) === (int) $user_id ) {
			return true;
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( $product && (int) get_post_field( 'post_author', $product->get_id() ) === (int) $user_id ) {
				return true;
			}
		}

		return false;
	}

	private static function get_vendor_emails( $order ) {
		$emails = array();
		foreach ( self::get_vendor_ids( $order ) as $vendor_id ) {
			$user = get_userdata( $vendor_id );
			if ( $user && $user->user_email ) {
				$emails[] = $user->user_email;
			}
		}
		return $emails;
	}

	private static function get_vendor_ids( $order ) {
		$ids = array();
		if ( function_exists( 'dokan_get_seller_id_by_order' ) ) {
			$seller_id = absint( dokan_get_seller_id_by_order( $order->get_id() ) );
			if ( $seller_id ) {
				$ids[] = $seller_id;
			}
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$author = absint( get_post_field( 'post_author', $product->get_id() ) );
				if ( $author ) {
					$ids[] = $author;
				}
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	private static function get_vendor_names( $order ) {
		$names = array();
		foreach ( self::get_vendor_ids( $order ) as $vendor_id ) {
			if ( function_exists( 'dokan_get_store_info' ) ) {
				$store = dokan_get_store_info( $vendor_id );
				if ( ! empty( $store['store_name'] ) ) {
					$names[] = $store['store_name'];
					continue;
				}
			}
			$user = get_userdata( $vendor_id );
			if ( $user ) {
				$names[] = $user->display_name;
			}
		}

		return $names ? implode( ', ', array_unique( $names ) ) : get_bloginfo( 'name' );
	}

	private static function get_item_vendor_name( $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			return get_bloginfo( 'name' );
		}

		$vendor_id = absint( get_post_field( 'post_author', $product->get_id() ) );
		if ( $vendor_id && function_exists( 'dokan_get_store_info' ) ) {
			$store = dokan_get_store_info( $vendor_id );
			if ( ! empty( $store['store_name'] ) ) {
				return $store['store_name'];
			}
		}

		$user = $vendor_id ? get_userdata( $vendor_id ) : false;
		return $user ? $user->display_name : get_bloginfo( 'name' );
	}

	private static function get_invoice_seller_details() {
		return array(
			'display_name' => 'KIL INDIA TRADE PRIVATE LIMITED',
				'address_lines' => array(
				'21/3 & 4, 2nd Floor, Yusuf Sarai Main Market',
				'New Delhi 110016, IN:DL',
			),
			'email'   => 'moabeauty@kilindia.in',
			'contact' => '012-44488464',
			'pan'     => 'AALCK8533A',
			'gst'     => '07AALCK8533A1ZL',
		);
	}

	private static function get_customer_tax_details( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return array(
				'gst'         => '',
				'pan'         => '',
				'confirmation' => false,
			);
		}

		$gst = beautyin_customer_normalize_tax_id( $order->get_meta( 'beautyin_customer_gst_number', true ) );
		$pan = beautyin_customer_normalize_tax_id( $order->get_meta( 'beautyin_customer_pan_number', true ) );
		$confirmation = 'yes' === $order->get_meta( 'beautyin_customer_tax_confirmation', true );

		if ( '' === $gst && '' === $pan && $order->get_customer_id() ) {
			$gst = beautyin_customer_normalize_tax_id( get_user_meta( $order->get_customer_id(), 'beautyin_customer_gst_number', true ) );
			$pan = beautyin_customer_normalize_tax_id( get_user_meta( $order->get_customer_id(), 'beautyin_customer_pan_number', true ) );
		}

		return array(
			'gst'         => $gst,
			'pan'         => $pan,
			'confirmation' => $confirmation,
		);
	}

	private static function format_invoice_address( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( "\n", array_filter( array_map( 'trim', $value ) ) );
		}

		$value = (string) $value;
		$value = preg_replace( '/<br\s*\/?>/i', "\n", $value );
		$value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$value = wp_strip_all_tags( $value, false );
		$value = preg_replace( "/\r\n|\r/", "\n", $value );
		$value = preg_replace( "/[ \t]+/", ' ', $value );
		$value = trim( $value );

		return nl2br( esc_html( $value ) );
	}

	private static function get_order_address_lines( $order, $type = 'billing' ) {
		if ( ! $order instanceof WC_Order ) {
			return 'N/A';
		}

		$getter = 'billing' === $type ? 'get_billing_' : 'get_shipping_';
		$lines = array();

		$company = trim( (string) $order->{ $getter . 'company' }() );
		$address_1 = trim( (string) $order->{ $getter . 'address_1' }() );
		$address_2 = trim( (string) $order->{ $getter . 'address_2' }() );
		$city = trim( (string) $order->{ $getter . 'city' }() );
		$state = trim( (string) $order->{ $getter . 'state' }() );
		$postcode = trim( (string) $order->{ $getter . 'postcode' }() );
		foreach ( array( $company, $address_1, $address_2 ) as $line ) {
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		$city_line = trim( implode( ' ', array_filter( array( $city, $postcode ) ) ) );
		if ( '' !== $city_line ) {
			$lines[] = $city_line;
		}

		if ( '' !== $state ) {
			$lines[] = $state;
		}

		return $lines ? $lines : 'N/A';
	}

	private static function get_logo_url() {
		$logo_id = get_theme_mod( 'custom_logo' );
		return $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	}

	private static function get_tracking_items( $order ) {
		$items = array();
		if ( function_exists( 'ast_get_tracking_items' ) ) {
			$ast_items = ast_get_tracking_items( $order->get_id() );
			if ( is_array( $ast_items ) && $ast_items ) {
				$items = array_merge( $items, $ast_items );
			}
		}

		$shipment_items = $order->get_meta( '_wc_shipment_tracking_items', true );
		if ( is_array( $shipment_items ) && $shipment_items ) {
			$items = array_merge( $items, $shipment_items );
		}

		if ( class_exists( 'VI_WOO_ORDERS_TRACKING_DATA' ) ) {
			$wot_items = self::get_woo_orders_tracking_items( $order );
			if ( $wot_items ) {
				$items = array_merge( $items, $wot_items );
			}
		}

		return $items;
	}

	private static function get_woo_orders_tracking_items( $order ) {
		$items = array();
		if ( ! $order instanceof WC_Order ) {
			return $items;
		}

		$tracking_settings = class_exists( 'VI_WOO_ORDERS_TRACKING_DATA' ) ? VI_WOO_ORDERS_TRACKING_DATA::get_instance() : null;
		foreach ( $order->get_items() as $item_id => $line_item ) {
			$raw_trackings = wc_get_order_item_meta( $item_id, '_vi_wot_order_item_tracking_data', true );
			if ( ! $raw_trackings ) {
				continue;
			}

			$decoded_trackings = function_exists( 'vi_wot_json_decode' ) ? vi_wot_json_decode( $raw_trackings ) : json_decode( $raw_trackings, true );
			if ( ! is_array( $decoded_trackings ) ) {
				continue;
			}

			foreach ( $decoded_trackings as $tracking_data ) {
				if ( ! is_array( $tracking_data ) ) {
					continue;
				}

				$tracking_number = self::array_value( $tracking_data, array( 'tracking_number' ), '' );
				$carrier_slug    = self::array_value( $tracking_data, array( 'carrier_slug' ), '' );
				$carrier_name    = self::array_value( $tracking_data, array( 'carrier_name', 'formatted_tracking_provider', 'tracking_provider', 'custom_tracking_provider' ), '' );
				$carrier_url     = self::array_value( $tracking_data, array( 'tracking_url', 'carrier_url', 'custom_tracking_link', 'ast_tracking_link' ), '' );

				if ( ! $carrier_url && $tracking_settings instanceof VI_WOO_ORDERS_TRACKING_DATA && $tracking_number ) {
					$carrier_url = $tracking_settings->get_url_tracking( $carrier_url, $tracking_number, $carrier_slug, $order->get_shipping_postcode(), false, true, $order->get_id() );
				}

				$items[] = array(
					'formatted_tracking_provider' => $carrier_name,
					'tracking_provider'           => $carrier_name,
					'custom_tracking_provider'    => $carrier_name,
					'tracking_number'             => $tracking_number,
					'tracking_link'               => $carrier_url,
					'custom_tracking_link'        => $carrier_url,
					'carrier_url'                 => $carrier_url,
					'date_shipped'                => self::array_value( $tracking_data, array( 'date_shipped', 'time' ), '' ),
				);
			}
		}

		return $items;
	}

	private static function build_tracking_summary( $order ) {
		$tracking_settings = class_exists( 'VI_WOO_ORDERS_TRACKING_DATA' ) ? VI_WOO_ORDERS_TRACKING_DATA::get_instance() : null;
		$tracking = array(
			'carrier' => (string) $order->get_meta( self::META_TRACKING_CARRIER, true ),
			'number'  => (string) $order->get_meta( self::META_TRACKING_NUMBER, true ),
			'url'     => (string) $order->get_meta( self::META_TRACKING_URL, true ),
			'status'  => wc_get_order_status_name( $order->get_status() ),
		);

		$tracking_items = self::get_tracking_items( $order );
		if ( ! empty( $tracking_items ) ) {
			$tracking_item = null;
			foreach ( $tracking_items as $item ) {
				if ( is_array( $item ) && ! empty( self::array_value( $item, array( 'tracking_number' ), '' ) ) ) {
					$tracking_item = $item;
				}
			}

			if ( is_array( $tracking_item ) ) {
				$tracking['number'] = self::array_value( $tracking_item, array( 'tracking_number' ), $tracking['number'] );
				$tracking['carrier'] = self::array_value( $tracking_item, array( 'formatted_tracking_provider', 'tracking_provider', 'custom_tracking_provider' ), $tracking['carrier'] );
				$tracking['url']     = self::array_value( $tracking_item, array( 'tracking_link', 'custom_tracking_link', 'carrier_url' ), $tracking['url'] );
			}
		}

		if ( empty( $tracking['url'] ) && $tracking_settings instanceof VI_WOO_ORDERS_TRACKING_DATA && $tracking['number'] ) {
			$tracking['url'] = $tracking_settings->get_url_tracking( '', $tracking['number'], '', $order->get_shipping_postcode(), false, true, $order->get_id() );
		}

		return $tracking;
	}

	private static function format_tracking_for_invoice( $tracking ) {
		if ( empty( $tracking ) ) {
			return 'Not added yet';
		}

		$lines = array();
		foreach ( $tracking as $item ) {
			$provider = self::array_value( $item, array( 'formatted_tracking_provider', 'tracking_provider', 'custom_tracking_provider' ), 'Courier' );
			$number = self::array_value( $item, array( 'tracking_number' ), '' );
			$link = self::array_value( $item, array( 'ast_tracking_link', 'tracking_link', 'custom_tracking_link' ), '' );
			$date = self::array_value( $item, array( 'date_shipped' ), '' );
			$date_text = is_numeric( $date ) ? date_i18n( get_option( 'date_format' ), (int) $date ) : $date;
			$text = esc_html( trim( $provider . ' ' . $number ) );
			if ( $date_text ) {
				$text .= ' (' . esc_html( $date_text ) . ')';
			}
			if ( $link ) {
				$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
			}
			$lines[] = $text;
		}

		return implode( '<br>', $lines );
	}

	private static function array_value( $array, $keys, $fallback ) {
		foreach ( $keys as $key ) {
			if ( isset( $array[ $key ] ) && '' !== $array[ $key ] ) {
				return $array[ $key ];
			}
		}
		return $fallback;
	}

	private static function fulfillment_steps() {
		return array(
			'processing'       => 'Paid / Processing',
			'accepted'         => 'Accepted',
			'packed'           => 'Packed',
			'out-for-delivery' => 'Out for delivery',
			'completed'        => 'Delivered',
		);
	}

	private static function is_step_active( $order, $step ) {
		$order_status = $order->get_status();
		$order_steps = array_keys( self::fulfillment_steps() );
		$current_index = array_search( $order_status, $order_steps, true );
		$step_index = array_search( $step, $order_steps, true );

		if ( false === $current_index ) {
			$current_index = in_array( $order_status, array( 'updated-tracking', 'partial-shipped', 'delivered' ), true ) ? 4 : 0;
		}

		return false !== $step_index && $step_index <= $current_index;
	}

	private static function render_fulfillment_steps( $order ) {
		echo '<div class="beautyin-steps">';
		foreach ( self::fulfillment_steps() as $status => $label ) {
			printf(
				'<span class="%s">%s</span>',
				esc_attr( self::is_step_active( $order, $status ) ? 'is-active' : '' ),
				esc_html( $label )
			);
		}
		echo '</div>';
	}

	private static function render_tracking_summary( $order ) {
		$tracking = self::build_tracking_summary( $order );
		$tracking_items = self::get_tracking_items( $order );
		echo '<div class="beautyin-tracking-summary"><strong>Tracking:</strong><br>';
		if ( $tracking['number'] || $tracking['carrier'] || $tracking['url'] ) {
			echo '<div>';
			if ( $tracking['carrier'] ) {
				echo '<div><strong>Courier:</strong> ' . esc_html( $tracking['carrier'] ) . '</div>';
			}
			if ( $tracking['number'] ) {
				echo '<div><strong>Receipt / tracking no:</strong> ' . esc_html( $tracking['number'] ) . '</div>';
			}
			if ( $tracking['url'] ) {
				echo '<div><a href="' . esc_url( $tracking['url'] ) . '" target="_blank" rel="noopener">Open tracking link</a></div>';
			}
			echo '</div>';
		}
		echo wp_kses_post( self::format_tracking_for_invoice( $tracking_items ) );
		echo '<div><strong>Order status:</strong> ' . esc_html( $tracking['status'] ) . '</div>';
		echo '</div>';
	}

	public static function enqueue_front_styles() {
		wp_register_style( 'beautyin-order-invoice-fulfillment', false, array(), '1.0.0' );
		wp_enqueue_style( 'beautyin-order-invoice-fulfillment' );
		wp_add_inline_style( 'beautyin-order-invoice-fulfillment', self::shared_css() );
	}

	public static function enqueue_admin_styles() {
		wp_register_style( 'beautyin-order-invoice-fulfillment-admin', false, array(), '1.0.0' );
		wp_enqueue_style( 'beautyin-order-invoice-fulfillment-admin' );
		wp_add_inline_style( 'beautyin-order-invoice-fulfillment-admin', self::shared_css() . '.wc-action-button-beautyin-invoice::after{content:"\f498";font-family:Dashicons}.beautyin-order-tools__buttons .button{margin:0 4px 6px 0}' );
	}

	private static function shared_css() {
		return '
			.beautyin-account-order-panel,.beautyin-dokan-order-panel{margin:22px 0}
			.beautyin-account-order-panel__grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
			.beautyin-steps{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:6px;margin:12px 0}
			.beautyin-steps span{border:1px solid #d8dee8;background:#fff;color:#5f6b7a;padding:7px 8px;font-size:12px;text-align:center}
			.beautyin-steps span.is-active{border-color:#6f3f8f;background:#f7f4f9;color:#6f3f8f;font-weight:700}
			.beautyin-tracking-summary{margin:10px 0;color:#374151;line-height:1.5}
			.beautyin-tracking-summary strong{font-size:13px}
			.beautyin-dokan-invoice-col{white-space:nowrap}
			.beautyin-order-track-cell{display:grid;gap:8px}
			.beautyin-order-track-cell small{display:block;color:#6d6573;line-height:1.4}
			.beautyin-tracking-badge{display:inline-flex;flex-direction:column;gap:4px;align-items:flex-start;padding:8px 10px;border-radius:12px;line-height:1.2;min-width:120px}
			.beautyin-tracking-badge span{font-size:12px;font-weight:700}
			.beautyin-tracking-badge small,.beautyin-tracking-badge a{font-size:11px;color:#5f6573;text-decoration:none}
			.beautyin-tracking-badge--saved{background:#eefaf1;border:1px solid #b9e3c1;color:#116b2f}
			.beautyin-tracking-badge--missing{background:#fff5f5;border:1px solid #f1c0c0;color:#9f1239}
			.beautyin-order-tracking-fields .form-field{margin:10px 0}
			.beautyin-order-tracking-fields input{width:100%}
			@media(max-width:720px){.beautyin-account-order-panel__grid,.beautyin-steps{grid-template-columns:1fr}}
		';
	}
}

BeautyIn_Order_Invoice_Fulfillment::init();
