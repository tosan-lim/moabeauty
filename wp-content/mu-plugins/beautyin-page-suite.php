<?php
/**
 * Beauty In page suite.
 *
 * Creates functional marketplace pages around WooCommerce account, checkout,
 * policy and content workflows without replacing WooCommerce core flows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'beautyin_marketplace_main_phone' ) ) {
	function beautyin_marketplace_main_phone() {
		return '+91 60053 61564';
	}
}

if ( ! function_exists( 'beautyin_marketplace_main_phone_href' ) ) {
	function beautyin_marketplace_main_phone_href() {
		return '+916005361564';
	}
}

add_filter( 'the_content', 'beautyin_page_suite_content', 45 );
add_action( 'template_redirect', 'beautyin_page_suite_redirects' );
add_action( 'template_redirect', 'beautyin_page_suite_cart_two_actions', 7 );
add_action( 'template_redirect', 'beautyin_page_suite_xml_sitemap' );
add_action( 'init', 'beautyin_page_suite_checkout_alias_rule' );
add_action( 'wp_head', 'beautyin_page_suite_404_meta', 1 );
add_action( 'wp_enqueue_scripts', 'beautyin_page_suite_styles', 45 );
add_filter( 'body_class', 'beautyin_page_suite_body_class' );

function beautyin_page_suite_content( $content ) {
	if ( is_admin() || is_front_page() || ! is_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$page = get_post();
	if ( ! $page ) {
		return $content;
	}

	$slug = $page->post_name;
	if ( 'cart-2' === $slug ) {
		$slug = 'cart';
	}

	if ( 'cart' === $slug ) {
		$map = beautyin_page_suite_map();
		if ( isset( $map[ $slug ] ) ) {
			$data = $map[ $slug ];
			$body  = is_callable( $data['body'] ) ? call_user_func( $data['body'] ) : $data['body'];
			return $body;
		}
	}

	$map  = beautyin_page_suite_map();
	if ( ! isset( $map[ $slug ] ) ) {
		return $content;
	}

	$data = $map[ $slug ];
	$body = is_callable( $data['body'] ) ? call_user_func( $data['body'] ) : $data['body'];

	return beautyin_page_suite_shell( $data['eyebrow'], $data['title'], $data['copy'], $body );
}

function beautyin_page_suite_redirects() {
	if ( is_admin() || ! is_page() ) {
		return;
	}

	$page = get_post();
	if ( ! $page ) {
		return;
	}

	if ( 'checkout' === $page->post_name ) {
		$request_path = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
		}
		if ( false !== strpos( $request_path, '/checkout-2' ) ) {
			wp_safe_redirect( beautyin_page_suite_checkout_url() );
			exit;
		}
	}

	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$routes  = array(
		'order-history'    => $account . 'orders/',
		'saved-addresses'  => $account . 'edit-address/',
		'profile-settings' => $account . 'edit-account/',
	);

	if ( isset( $routes[ $page->post_name ] ) && ! isset( $_GET['stay'] ) ) {
		wp_safe_redirect( $routes[ $page->post_name ] );
		exit;
	}
}

function beautyin_page_suite_checkout_url() {
	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	if ( function_exists( 'beautyin_marketplace_global_checkout_url' ) ) {
		$checkout_url = beautyin_marketplace_global_checkout_url( $checkout_url );
	}

	return $checkout_url;
}

function beautyin_page_suite_checkout_alias_rule() {
	add_rewrite_rule( '^checkout-2/?$', 'index.php?pagename=checkout', 'top' );
}

function beautyin_page_suite_xml_sitemap() {
	if ( ! is_page( 'sitemap' ) || empty( $_GET['xml'] ) ) {
		return;
	}

	$urls = beautyin_page_suite_sitemap_urls();
	header( 'Content-Type: application/xml; charset=' . get_bloginfo( 'charset' ), true );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ( $urls as $url ) {
		echo '<url><loc>' . esc_url( $url ) . '</loc></url>' . "\n";
	}
	echo '</urlset>';
	exit;
}

function beautyin_page_suite_404_meta() {
	if ( is_404() ) {
		echo "<meta name=\"robots\" content=\"noindex,follow\">\n";
	}
}

function beautyin_page_suite_body_class( $classes ) {
	if ( is_page( 'cart' ) || is_page( 'cart-2' ) ) {
		$classes[] = 'beautyin-cart-two';
	}

	if ( is_page( 'about-us' ) ) {
		$classes[] = 'beautyin-about-us';
	}

	if ( is_page( array( 'korean-beauty-india', 'korean-beauty-delhi', 'korean-beauty-mumbai', 'korean-beauty-goa', 'korean-beauty-punjab' ) ) ) {
		$classes[] = 'beautyin-city-page';
	}

	return $classes;
}

function beautyin_page_suite_map() {
	return array(
		'order-history' => array(
			'eyebrow' => 'Account',
			'title'   => 'Order History',
			'copy'    => 'View orders, invoices, payment status and delivery updates from your customer account.',
			'body'    => 'beautyin_page_suite_order_history',
		),
		'wishlist' => array(
			'eyebrow' => 'Saved products',
			'title'   => 'Wishlist',
			'copy'    => 'Save Korean skincare, makeup and personal care picks for your next purchase.',
			'body'    => 'beautyin_page_suite_wishlist',
		),
		'saved-addresses' => array(
			'eyebrow' => 'Account',
			'title'   => 'Saved Addresses',
			'copy'    => 'Manage billing and shipping addresses used during checkout.',
			'body'    => 'beautyin_page_suite_saved_addresses',
		),
		'profile-settings' => array(
			'eyebrow' => 'Account',
			'title'   => 'Profile Settings',
			'copy'    => 'Update your name, email, password and mobile-linked account details.',
			'body'    => 'beautyin_page_suite_profile_settings',
		),
		'privacy-policy' => array(
			'eyebrow' => 'Policy',
			'title'   => 'Privacy Policy',
			'copy'    => 'How customer, seller, visitor and order information is collected, used and protected.',
			'body'    => function () {
				return function_exists( 'beautyin_marketplace_policy_markup' ) ? beautyin_marketplace_policy_markup() : beautyin_page_suite_policy_copy( 'privacy' );
			},
		),
		'terms-and-conditions' => array(
			'eyebrow' => 'Terms',
			'title'   => 'Terms and Conditions',
			'copy'    => 'Marketplace rules for shopping, checkout, sellers, payments, delivery and support.',
			'body'    => function () {
				return function_exists( 'beautyin_marketplace_terms_markup' ) ? beautyin_marketplace_terms_markup() : beautyin_page_suite_policy_copy( 'terms' );
			},
		),
		'refund-return-policy' => array(
			'eyebrow' => 'Policy',
			'title'   => 'Refund and Return Policy',
			'copy'    => 'Return, refund and cancellation rules for skincare, cosmetics and personal care orders.',
			'body'    => 'beautyin_page_suite_refund_policy',
		),
		'shipping-policy' => array(
			'eyebrow' => 'Policy',
			'title'   => 'Shipping Policy',
			'copy'    => 'Delivery timelines, shipping fees, pincode serviceability and international product handling.',
			'body'    => 'beautyin_page_suite_shipping_policy',
		),
		'cookie-policy' => array(
			'eyebrow' => 'Policy',
			'title'   => 'Cookie Policy',
			'copy'    => 'How cookies support login, cart, checkout, analytics and marketplace security.',
			'body'    => 'beautyin_page_suite_cookie_policy',
		),
		'about-us' => array(
			'eyebrow' => 'About',
			'title'   => 'About MOA Beauty',
			'copy'    => 'MOA Beauty is the consumer brand powered by KIL INDIA TRADE PRIVATE LIMITED for Indian shoppers and verified sellers.',
			'body'    => 'beautyin_page_suite_about',
		),
		'korean-beauty-india' => array(
			'eyebrow' => 'SEO city hub',
			'title'   => 'Korean Beauty in India',
			'copy'    => 'A national landing page for Korean beauty shoppers searching in India.',
			'body'    => function () {
				return beautyin_page_suite_location_landing( 'india' );
			},
		),
		'korean-beauty-delhi' => array(
			'eyebrow' => 'SEO city hub',
			'title'   => 'Korean Beauty in Delhi',
			'copy'    => 'A Delhi landing page for Korean skincare, makeup and beauty delivery searches.',
			'body'    => function () {
				return beautyin_page_suite_location_landing( 'delhi' );
			},
		),
		'korean-beauty-mumbai' => array(
			'eyebrow' => 'SEO city hub',
			'title'   => 'Korean Beauty in Mumbai',
			'copy'    => 'A Mumbai landing page for Korean skincare, makeup and beauty delivery searches.',
			'body'    => function () {
				return beautyin_page_suite_location_landing( 'mumbai' );
			},
		),
		'korean-beauty-goa' => array(
			'eyebrow' => 'SEO city hub',
			'title'   => 'Korean Beauty in Goa',
			'copy'    => 'A Goa landing page for Korean skincare, makeup and beauty delivery searches.',
			'body'    => function () {
				return beautyin_page_suite_location_landing( 'goa' );
			},
		),
		'korean-beauty-punjab' => array(
			'eyebrow' => 'SEO city hub',
			'title'   => 'Korean Beauty in Punjab',
			'copy'    => 'A Punjab landing page for Korean skincare, makeup and beauty delivery searches.',
			'body'    => function () {
				return beautyin_page_suite_location_landing( 'punjab' );
			},
		),
		'contact-us' => array(
			'eyebrow' => 'Support',
			'title'   => 'Contact Us',
			'copy'    => 'Reach the MOA Beauty team powered by KIL INDIA TRADE PRIVATE LIMITED for product help, orders, vendors and bulk requirements.',
			'body'    => function () {
				return function_exists( 'beautyin_marketplace_contact_markup' ) ? beautyin_marketplace_contact_markup() : beautyin_page_suite_contact();
			},
		),
		'cart' => array(
			'eyebrow' => 'Checkout',
			'title'   => 'Cart',
			'copy'    => 'Review items, adjust quantity and continue to checkout with a clean mobile-safe layout.',
			'body'    => 'beautyin_page_suite_cart_two',
		),
		'faq' => array(
			'eyebrow' => 'Help',
			'title'   => 'Frequently Asked Questions',
			'copy'    => 'Answers for shopping, account, delivery, returns, vendors and international products.',
			'body'    => 'beautyin_page_suite_faq',
		),
		'track-order' => array(
			'eyebrow' => 'Order support',
			'title'   => 'Track Order',
			'copy'    => 'Track order status with your order ID, billing email or mobile number.',
			'body'    => 'beautyin_page_suite_track_order',
		),
		'seo-pages' => array(
			'eyebrow' => 'SEO',
			'title'   => 'Korean Beauty SEO Hub',
			'copy'    => 'Category and education pages for skincare shoppers searching Korean products in India.',
			'body'    => 'beautyin_page_suite_seo_pages',
		),
		'blog' => array(
			'eyebrow' => 'Journal',
			'title'   => 'Beauty In Blog',
			'copy'    => 'Skincare routines, ingredient explainers, product education and marketplace updates.',
			'body'    => 'beautyin_page_suite_blog',
		),
		'search-results' => array(
			'eyebrow' => 'Search',
			'title'   => 'Search Results',
			'copy'    => 'Find Korean skincare, makeup, masks, serums and marketplace help pages.',
			'body'    => 'beautyin_page_suite_search_page',
		),
		'404-error-page' => array(
			'eyebrow' => 'Not found',
			'title'   => '404 Error Page',
			'copy'    => 'A helpful fallback for visitors who land on a missing Beauty In page.',
			'body'    => 'beautyin_page_suite_404_page',
		),
		'sitemap' => array(
			'eyebrow' => 'Index',
			'title'   => 'Sitemap',
			'copy'    => 'Browse important shopping, account, policy and skincare education pages.',
			'body'    => 'beautyin_page_suite_sitemap',
		),
		'payment-success' => array(
			'eyebrow' => 'Payment',
			'title'   => 'Payment Successful',
			'copy'    => 'Your payment was completed. Check your order details and delivery status.',
			'body'    => 'beautyin_page_suite_payment_success',
		),
		'payment-failed' => array(
			'eyebrow' => 'Payment',
			'title'   => 'Payment Failed',
			'copy'    => 'Your payment could not be completed. You can retry checkout or contact support.',
			'body'    => 'beautyin_page_suite_payment_failed',
		),
		'thank-you' => array(
			'eyebrow' => 'Order',
			'title'   => 'Thank You',
			'copy'    => 'Thanks for shopping with MOA Beauty.',
			'body'    => 'beautyin_page_suite_thank_you',
		),
		'thank-you-2' => array(
			'eyebrow' => 'Order',
			'title'   => 'Thank You',
			'copy'    => 'Thanks for shopping with MOA Beauty.',
			'body'    => 'beautyin_page_suite_thank_you',
		),
		'korean-skincare-routine-guide' => array(
			'eyebrow' => 'Guide',
			'title'   => 'Korean Skincare Routine Guide',
			'copy'    => 'Build a simple morning and night routine using cleanser, toner, serum, cream and SPF.',
			'body'    => 'beautyin_page_suite_routine_guide',
		),
		'choose-products-for-your-skin-type' => array(
			'eyebrow' => 'Guide',
			'title'   => 'How to Choose Products for Your Skin Type',
			'copy'    => 'Choose Korean skincare by oily, dry, combination, sensitive or acne-prone skin needs.',
			'body'    => 'beautyin_page_suite_skin_type_guide',
		),
		'ingredients-guide' => array(
			'eyebrow' => 'Ingredients',
			'title'   => 'Ingredients Guide',
			'copy'    => 'Understand niacinamide, centella, hyaluronic acid, collagen, ceramides and more.',
			'body'    => 'beautyin_page_suite_ingredients_guide',
		),
		'reviews' => array(
			'eyebrow' => 'Community',
			'title'   => 'Reviews',
			'copy'    => 'Read product feedback and share your experience after purchase.',
			'body'    => 'beautyin_page_suite_reviews',
		),
		'before-after-results' => array(
			'eyebrow' => 'Results',
			'title'   => 'Before and After Results',
			'copy'    => 'Track skincare progress responsibly with routine notes and realistic expectations.',
			'body'    => 'beautyin_page_suite_before_after',
		),
		'authenticity-guarantee' => array(
			'eyebrow' => 'Trust',
			'title'   => 'Authenticity Guarantee',
			'copy'    => 'Marketplace commitment for original Korean products and verified seller review.',
			'body'    => 'beautyin_page_suite_authenticity',
		),
		'rewards-loyalty-program' => array(
			'eyebrow' => 'Rewards',
			'title'   => 'Rewards and Loyalty Program',
			'copy'    => 'A planned loyalty experience for repeat shoppers, reviews and referrals.',
			'body'    => 'beautyin_page_suite_rewards',
		),
		'referral-program' => array(
			'eyebrow' => 'Rewards',
			'title'   => 'Referral Program',
			'copy'    => 'Invite friends to discover Korean beauty and earn future marketplace benefits.',
			'body'    => 'beautyin_page_suite_referrals',
		),
	);

	return $map;
}

function beautyin_page_suite_shell( $eyebrow, $title, $copy, $body ) {
	return sprintf(
		'<section class="bm-page-hero"><span>%s</span><h1>%s</h1><p>%s</p></section><section class="bm-page-panel bm-suite-panel">%s</section>',
		esc_html( $eyebrow ),
		esc_html( $title ),
		esc_html( $copy ),
		wp_kses_post( $body )
	);
}

function beautyin_page_suite_cards( $items ) {
	$html = '<div class="bm-suite-grid">';
	foreach ( $items as $item ) {
		$url   = isset( $item[2] ) ? $item[2] : '';
		$inner = '<strong>' . esc_html( $item[0] ) . '</strong><small>' . esc_html( $item[1] ) . '</small>';
		$html .= $url ? '<a href="' . esc_url( $url ) . '">' . $inner . '</a>' : '<span>' . $inner . '</span>';
	}
	return $html . '</div>';
}

function beautyin_page_suite_policy_copy( $type ) {
	return '<div class="bm-policy-copy"><h2>' . esc_html( ucfirst( $type ) ) . '</h2><p>This policy explains marketplace rules for customers, sellers, orders, payments, delivery and support. Contact KIL India for help connected to a specific order or account.</p></div>';
}

function beautyin_page_suite_order_history() {
	$account = beautyin_page_suite_account_url();
	return beautyin_page_suite_cards( array(
		array( 'View my orders', 'Open your WooCommerce order history, invoices and current delivery status.', $account . 'orders/' ),
		array( 'Track order', 'Use order ID and account details for support and courier follow-up.', beautyin_page_suite_page_url( 'track-order' ) ),
		array( 'Need help?', 'Contact support with order ID, product name and payment reference.', beautyin_page_suite_page_url( 'contact-us' ) ),
	) );
}

function beautyin_page_suite_wishlist() {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$ids  = function_exists( 'beautyin_marketplace_get_wishlist_ids' ) ? beautyin_marketplace_get_wishlist_ids() : array();

	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		return '<div class="bm-empty-state"><h2>Your wishlist is empty</h2><p>Save products from product cards or product detail pages and they will appear here.</p><a class="bm-page-btn" href="' . esc_url( $shop ) . '">Continue shopping</a></div>';
	}

	$html = '<div class="bm-products bm-wishlist-grid">';
	foreach ( $ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== get_post_status( $product_id ) ) {
			continue;
		}
		$image = $product->get_image( 'woocommerce_thumbnail' );
		$html .= '<article class="bm-product"><a class="bm-product-img" href="' . esc_url( $product->get_permalink() ) . '">' . $image . '</a><div class="bm-product-body">';
		$html .= '<a class="bm-product-title" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
		$html .= '<div class="bm-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		$html .= '<div class="bm-product-actions">';
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$html .= '<a class="bm-add" href="' . esc_url( '?add-to-cart=' . $product_id ) . '">Add to cart</a>';
		}
		if ( function_exists( 'beautyin_marketplace_wishlist_button' ) ) {
			$html .= beautyin_marketplace_wishlist_button( $product_id, 'is-saved' );
		}
		$html .= '</div></div></article>';
	}
	$html .= '</div>';

	return $html ? $html : '<div class="bm-empty-state"><h2>Your wishlist is empty</h2><p>Saved products are no longer available.</p><a class="bm-page-btn" href="' . esc_url( $shop ) . '">Continue shopping</a></div>';
}

function beautyin_page_suite_saved_addresses() {
	$account = beautyin_page_suite_account_url();
	return beautyin_page_suite_cards( array(
		array( 'Billing address', 'Update invoice and billing details used at checkout.', $account . 'edit-address/billing/' ),
		array( 'Shipping address', 'Update delivery name, mobile number, address and pincode.', $account . 'edit-address/shipping/' ),
		array( 'Checkout', 'Confirm address while placing your next order.', beautyin_page_suite_checkout_url() ),
	) );
}

function beautyin_page_suite_profile_settings() {
	$account = beautyin_page_suite_account_url();
	return beautyin_page_suite_cards( array(
		array( 'Edit account', 'Update name, email and password.', $account . 'edit-account/' ),
		array( 'Saved addresses', 'Manage billing and shipping address details.', $account . 'edit-address/' ),
		array( 'Order history', 'Review past purchases and payment status.', $account . 'orders/' ),
	) );
}

function beautyin_page_suite_refund_policy() {
	return '<div class="bm-policy-copy"><h2>Return eligibility</h2><p>Returns are reviewed by product category, seller policy, delivery condition and hygiene rules. Opened, used, damaged-after-delivery or seal-broken beauty and personal care products may not be eligible for return.</p><h2>Refund process</h2><p>Approved refunds are processed to the original payment method or another method supported by the payment gateway. Timelines depend on bank, UPI, card or gateway processing.</p><h2>Damaged or wrong item</h2><p>Contact support quickly with order ID, product photos, packaging photos and delivery details. Claims may be rejected if evidence is missing or submitted late.</p><h2>Cancellations</h2><p>Orders can be cancelled before dispatch where seller and fulfilment status allows. Once shipped, return rules apply.</p></div>';
}

function beautyin_page_suite_shipping_policy() {
	return '<div class="bm-policy-copy"><h2>India delivery</h2><p>Delivery depends on seller dispatch time, pincode serviceability, courier availability, holidays and payment confirmation. Estimated dates are guidance, not a guaranteed delivery promise.</p><h2>Shipping charges</h2><p>Shipping fees, free-shipping eligibility, taxes and handling charges are shown during checkout where configured.</p><h2>International products</h2><p>Imported or international vendor products may include customs duty, import charges, clearance fees and longer timelines. These details should be reviewed before payment.</p><h2>Tracking</h2><p>Customers can track order status from the account order area after login.</p></div>';
}

function beautyin_page_suite_cookie_policy() {
	return '<div class="bm-policy-copy"><h2>Cookies we use</h2><p>Cookies support login sessions, cart contents, checkout, product search, security, analytics and preference storage.</p><h2>Third-party services</h2><p>Payment gateways, analytics, maps, security tools or marketing services may set cookies when enabled on the website.</p><h2>Managing cookies</h2><p>You can control cookies from your browser settings. Some marketplace features may not work correctly if required cookies are blocked.</p></div>';
}

function beautyin_page_suite_about() {
	$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$contact = beautyin_page_suite_page_url( 'contact-us' );

	$hero = '<section class="bm-about-hero"><div class="bm-about-hero-copy"><span>About MOA Beauty</span><h1>MOA Beauty</h1><p class="bm-about-hero-lead">Powered by KIL INDIA TRADE PRIVATE LIMITED</p><p>MOA Beauty is the retail brand on beautyin.in. It is built to bring Korean beauty, skincare and lifestyle products to Indian customers through a curated shopping experience with verified sellers, clear checkout and dependable support.</p></div><div class="bm-about-hero-side"><div><strong>Brand</strong><small>MOA Beauty on beautyin.in</small></div><div><strong>Company</strong><small>KIL INDIA TRADE PRIVATE LIMITED</small></div><div><strong>Focus</strong><small>Korean beauty for India</small></div><div><strong>Support</strong><small>Orders, vendors and bulk inquiries</small></div></div></section>';

	$intro = '<div class="bm-about-panels"><div class="bm-about-card"><h2>Our story</h2><p>The official KIL India website presents the company as a focused business presence with About, Service, Works and Contact pages. That structure tells us the business is meant to be easy to understand, easy to contact and easy to trust. MOA Beauty takes that base and turns it into a customer-facing shopping experience for India.</p></div><div class="bm-about-card"><h2>What customers should expect</h2><p>Customers should see a curated product selection, transparent product and seller information, India-friendly checkout, and support that helps with product questions, orders, vendors and bulk requirements. The goal is clarity first, so shopping feels simple instead of cluttered.</p></div></div>';

	$metrics = '<div class="bm-about-metrics"><div><strong>Beauty in India</strong><span>Retail brand built for Indian shoppers</span></div><div><strong>Verified sellers</strong><span>Marketplace approvals and review</span></div><div><strong>Clear checkout</strong><span>Cart and order flow</span></div><div><strong>Direct support</strong><span>Email and contact assistance</span></div></div>';

	$promise = '<div class="bm-about-card"><h2>Our promise</h2><p>We try to keep the brand language simple, the product story honest and the buying journey predictable. That means less confusion, fewer mixed labels and a cleaner path from product discovery to checkout and post-purchase support.</p><div class="bm-about-panels bm-about-panels--tight"><div class="bm-about-mini"><strong>Curated selection</strong><small>We prefer a focused catalogue over a noisy storefront.</small></div><div class="bm-about-mini"><strong>Trust before trend</strong><small>Seller verification and product clarity matter more than hype.</small></div></div></div>';

	$cards = beautyin_page_suite_cards( array(
		array( 'Original product focus', 'We keep the focus on original, verified and clearly listed Korean beauty products.', $shop ),
		array( 'India checkout', 'Cart, payment and order flows stay connected for Indian customers.', $shop ),
		array( 'Seller approval', 'Domestic and international sellers move through verification before going live.', $contact ),
		array( 'Support and contact', 'Customers can reach the team for product help, order support and vendor queries.', $contact ),
	) );

	$steps = beautyin_page_suite_steps( array(
		array( 'Discover', 'Customers browse curated Korean beauty and lifestyle products on MOA Beauty.' ),
		array( 'Verify', 'Seller details, product listings and approvals help keep the marketplace reliable.' ),
		array( 'Shop', 'Orders, cart and checkout stay connected to the marketplace backend.' ),
		array( 'Support', 'The team stays reachable for order help, vendor support and bulk queries.' ),
	) );

	$cta = '<div class="bm-about-cta"><div><strong>Want the full brand story?</strong><p>Visit the original KIL India website, explore MOA Beauty products, or contact the team for seller and partnership discussions.</p></div><div class="bm-about-cta-actions"><a class="bm-page-btn" href="' . esc_url( $shop ) . '">Shop MOA Beauty</a><a class="bm-page-btn bm-about-ghost" href="' . esc_url( $contact ) . '">Contact KIL India</a></div></div>';

	return '<div class="bm-about-page">' . $hero . $intro . $metrics . $promise . '<div class="bm-about-card bm-about-process"><h2>How the marketplace works</h2>' . $steps . '</div>' . $cards . $cta . '</div>';
}

function beautyin_page_suite_contact() {
	$phone_label = beautyin_marketplace_main_phone();
	$phone_href   = beautyin_marketplace_main_phone_href();

	return '<div class="bm-contact-grid"><div class="bm-contact-card"><h2>Talk to us</h2><p>For product help, order support, vendor queries and bulk buying, reach the KIL India team.</p><div class="bm-info-list"><a href="tel:' . esc_attr( $phone_href ) . '">' . esc_html( $phone_label ) . '</a><a href="tel:+919650638811">+91 96506 38811</a><a href="tel:+911149070215">+91 1149070215</a><a href="mailto:moabeauty@kilindia.in">moabeauty@kilindia.in</a></div></div></div>';
}

function beautyin_page_suite_cart_two_cart_url() {
	return function_exists( 'beautyin_marketplace_page_url' ) ? beautyin_marketplace_page_url( 'cart', '/cart/' ) : home_url( '/cart/' );
}

function beautyin_page_suite_cart_two_checkout_url() {
	return beautyin_page_suite_checkout_url();
}

function beautyin_page_suite_cart_two_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
}

function beautyin_page_suite_cart_two_remove_url( $cart_item_key ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'bm_remove_item' => rawurlencode( $cart_item_key ),
			),
			beautyin_page_suite_cart_two_cart_url()
		),
		'bm_cart_two_remove_' . $cart_item_key
	);
}

function beautyin_page_suite_cart_two_money( $amount ) {
	if ( function_exists( 'wc_price' ) ) {
		return wc_price( (float) $amount );
	}

	return esc_html( number_format_i18n( (float) $amount, 2 ) );
}

function beautyin_page_suite_cart_two_line_total( $product, $qty = 1 ) {
	if ( ! $product instanceof WC_Product ) {
		return 0.0;
	}

	$qty = max( 1, (int) $qty );
	return (float) $product->get_price() * $qty;
}

function beautyin_page_suite_cart_two_subtotal( $cart ) {
	if ( ! $cart instanceof WC_Cart ) {
		return 0.0;
	}

	$subtotal = 0.0;
	foreach ( $cart->get_cart() as $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product instanceof WC_Product || ! $product->exists() ) {
			continue;
		}

		$qty = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		$subtotal += beautyin_page_suite_cart_two_line_total( $product, $qty );
	}

	return round( $subtotal, 2 );
}

function beautyin_page_suite_cart_two_trash_icon() {
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3.75h6m-8 3h10m-8 0 .4 10.2a2 2 0 0 0 2 1.95h1.2a2 2 0 0 0 2-1.95L14 6.75M10 10v5.5M14 10v5.5"/><path d="M7.75 6.75 8.7 20.2a2.25 2.25 0 0 0 2.24 2.05h2.12a2.25 2.25 0 0 0 2.24-2.05l.95-13.45"/></svg>';
}

function beautyin_page_suite_cart_two_actions() {
	if ( is_admin() || wp_doing_ajax() || ! is_page( array( 'cart', 'cart-2' ) ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	if ( isset( $_GET['bm_remove_item'] ) ) {
		$cart_item_key = sanitize_text_field( wp_unslash( $_GET['bm_remove_item'] ) );
		$nonce         = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( $cart_item_key && wp_verify_nonce( $nonce, 'bm_cart_two_remove_' . $cart_item_key ) ) {
			WC()->cart->remove_cart_item( $cart_item_key );
			WC()->cart->calculate_totals();
			WC()->cart->set_session();
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( 'Item removed from cart.', 'success' );
			}
		}

		wp_safe_redirect( beautyin_page_suite_cart_two_cart_url() );
		exit;
	}

	if ( isset( $_POST['bm_cart2_update'] ) ) {
		$nonce = isset( $_POST['bm_cart2_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bm_cart2_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bm_cart2_update' ) ) {
			wp_safe_redirect( beautyin_page_suite_cart_two_cart_url() );
			exit;
		}

		$posted_qty = isset( $_POST['bm_qty'] ) ? (array) wp_unslash( $_POST['bm_qty'] ) : array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$qty = isset( $posted_qty[ $cart_item_key ] ) ? absint( $posted_qty[ $cart_item_key ] ) : (int) $cart_item['quantity'];
			WC()->cart->set_quantity( $cart_item_key, $qty, false );
		}

		WC()->cart->calculate_totals();
		WC()->cart->set_session();
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( 'Cart updated.', 'success' );
		}

		wp_safe_redirect( beautyin_page_suite_cart_two_cart_url() );
		exit;
	}
}

function beautyin_page_suite_cart_two() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '<div class="bm-empty-state"><h2>Cart unavailable</h2><p>The shopping cart is not ready yet. Please refresh the page or return to the shop.</p><a class="bm-page-btn" href="' . esc_url( beautyin_page_suite_cart_two_shop_url() ) . '">Go to shop</a></div>';
	}

	$cart = WC()->cart;
	if ( $cart->is_empty() ) {
		return '<section class="bm-cart2"><div class="bm-cart2-hero bm-cart2-hero-empty"><div class="bm-cart2-hero-copy"><span>Secure checkout</span><h1>Cart</h1></div></div><div class="bm-empty-state bm-cart2-empty"><h2>No items in cart</h2><p>Browse products and continue shopping when ready.</p><a class="bm-page-btn" href="' . esc_url( beautyin_page_suite_cart_two_shop_url() ) . '">Continue shopping</a></div></section>';
	}

	$items_count    = $cart->get_cart_contents_count();
	$subtotal       = beautyin_page_suite_cart_two_subtotal( $cart );
	$shipping_total = (float) $cart->get_shipping_total();
	$total          = max( 0, round( $subtotal + $shipping_total, 2 ) );
	$checkout_url   = beautyin_page_suite_cart_two_checkout_url();
	$shop_url       = beautyin_page_suite_cart_two_shop_url();
	$shipping_text  = $shipping_total > 0 ? 'Delivery fee: ' . beautyin_page_suite_cart_two_money( $shipping_total ) : '<span class="bm-cart2-muted">Calculated at checkout</span>';

	ob_start();
	?>
	<section class="bm-cart2" aria-label="Cart">
		<div class="bm-cart2-hero">
			<div class="bm-cart2-hero-copy">
				<span>Secure checkout</span>
				<h1>Cart</h1>
				<p>Review items, adjust quantity and continue to checkout.</p>
			</div>
			<div class="bm-cart2-hero-meta">
				<div><strong><?php echo esc_html( number_format_i18n( $items_count ) ); ?></strong><small>Items in cart</small></div>
				<div><strong><?php echo esc_html( wp_strip_all_tags( beautyin_page_suite_cart_two_money( $subtotal ) ) ); ?></strong><small>Subtotal</small></div>
				<div><strong><?php echo esc_html( wp_strip_all_tags( beautyin_page_suite_cart_two_money( $total ) ) ); ?></strong><small>Total</small></div>
			</div>
		</div>
		<div class="bm-cart2-grid">
			<form class="bm-cart2-form" method="post" action="<?php echo esc_url( beautyin_page_suite_cart_two_cart_url() ); ?>">
				<?php wp_nonce_field( 'bm_cart2_update', 'bm_cart2_nonce' ); ?>
				<div class="bm-cart2-list">
					<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
						if ( ! $product || ! is_a( $product, 'WC_Product' ) || ! $product->exists() || ! $cart_item['quantity'] ) {
							continue;
						}
						$product_id    = $product->get_id();
						$product_name  = $product->get_name();
						$product_link  = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
						$vendor_name   = function_exists( 'beautyin_marketplace_product_vendor_name' ) ? beautyin_marketplace_product_vendor_name( $product_id ) : '';
						$product_image  = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'bm-cart2-product-image' ) );
						$product_price  = (float) $product->get_price();
						$qty            = (int) $cart_item['quantity'];
						$line_total     = beautyin_page_suite_cart_two_line_total( $product, $qty );
						$sku            = $product->get_sku() ? $product->get_sku() : 'KIL-' . $product_id;
						?>
						<article class="bm-cart2-item">
							<a class="bm-cart2-remove" href="<?php echo esc_url( beautyin_page_suite_cart_two_remove_url( $cart_item_key ) ); ?>" aria-label="<?php echo esc_attr( 'Remove ' . $product_name ); ?>" title="Remove item">
								<?php echo beautyin_page_suite_cart_two_trash_icon(); ?>
							</a>
							<div class="bm-cart2-media">
								<?php echo $product_link ? '<a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $product_image ) . '</a>' : wp_kses_post( $product_image ); ?>
							</div>
							<div class="bm-cart2-body">
								<div class="bm-cart2-title-row">
									<div class="bm-cart2-title-stack">
										<h2><?php echo esc_html( $product_name ); ?></h2>
										<p class="bm-cart2-vendor"><?php echo $vendor_name ? esc_html( 'Vendor: ' . $vendor_name ) : esc_html( 'Verified marketplace listing' ); ?></p>
									</div>
									<div class="bm-cart2-price-tag"><?php echo wp_kses_post( beautyin_page_suite_cart_two_money( $product_price ) ); ?></div>
								</div>
								<div class="bm-cart2-meta">
									<span>SKU: <?php echo esc_html( $sku ); ?></span>
									<span><?php echo esc_html( $product->get_stock_status() === 'instock' ? 'In stock' : 'Out of stock' ); ?></span>
								</div>
								<div class="bm-cart2-footer">
									<div class="bm-cart2-qty" data-bm-cart-qty>
										<button type="button" class="bm-cart2-qty-btn" data-bm-step="-1" aria-label="Decrease quantity">-</button>
										<input type="number" min="1" step="1" name="bm_qty[<?php echo esc_attr( $cart_item_key ); ?>]" value="<?php echo esc_attr( $qty ); ?>" inputmode="numeric" aria-label="<?php echo esc_attr( $product_name . ' quantity' ); ?>">
										<button type="button" class="bm-cart2-qty-btn" data-bm-step="1" aria-label="Increase quantity">+</button>
									</div>
									<div class="bm-cart2-line-total">
										<span>Subtotal</span>
										<strong><?php echo wp_kses_post( beautyin_page_suite_cart_two_money( $line_total ) ); ?></strong>
									</div>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="bm-cart2-actions">
					<a class="bm-cart2-ghost" href="<?php echo esc_url( $shop_url ); ?>">Continue shopping</a>
					<button class="bm-cart2-update" type="submit" name="bm_cart2_update" value="1">Update cart</button>
				</div>
			</form>
			<aside class="bm-cart2-summary">
				<div class="bm-cart2-summary-head">
					<span>Order summary</span>
					<strong>Protected checkout</strong>
				</div>
				<div class="bm-cart2-summary-lines">
					<div class="bm-cart2-line"><span>Subtotal</span><strong><?php echo wp_kses_post( beautyin_page_suite_cart_two_money( $subtotal ) ); ?></strong></div>
					<div class="bm-cart2-line"><span>Shipping</span><strong><?php echo wp_kses_post( $shipping_text ); ?></strong></div>
					<div class="bm-cart2-line bm-cart2-line-total"><span>Total</span><strong><?php echo wp_kses_post( beautyin_page_suite_cart_two_money( $total ) ); ?></strong></div>
				</div>
				<a class="bm-cart2-checkout" href="<?php echo esc_url( $checkout_url ); ?>">Proceed to checkout</a>
			</aside>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function beautyin_page_suite_faq() {
	$items = array(
		array( 'Do I need an account to checkout?', 'Yes. Checkout requires login or registration so orders, addresses and invoices stay linked to your account.' ),
		array( 'Can I login with mobile number?', 'Yes. Customer login supports email or saved mobile number with password.' ),
		array( 'Are international products different?', 'Yes. International products can include customs duties, import charges and longer delivery timelines.' ),
		array( 'How do vendors get approved?', 'Vendors register, submit details and wait for admin verification before selling live.' ),
		array( 'Are opened cosmetics returnable?', 'Usually no, because hygiene and safety rules apply. Damaged or wrong items are reviewed separately.' ),
	);
	$html = '<div class="bm-faq-list">';
	foreach ( $items as $item ) {
		$html .= '<details><summary>' . esc_html( $item[0] ) . '</summary><p>' . esc_html( $item[1] ) . '</p></details>';
	}
	return $html . '</div>';
}

function beautyin_page_suite_track_order() {
	return beautyin_page_suite_track_order_form();
}

function beautyin_page_suite_track_order_form() {
	$request_order_id = isset( $_REQUEST['bm_track_order_id'] ) ? absint( wp_unslash( $_REQUEST['bm_track_order_id'] ) ) : 0;
	$request_contact  = isset( $_REQUEST['bm_track_contact'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bm_track_contact'] ) ) : '';
	$order_id = $request_order_id;
	$contact  = $request_contact;
	$result   = '';
	$order    = false;

	if ( $order_id && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		$allowed = false;

		if ( $order ) {
			if ( is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id() ) {
				$allowed = true;
			} elseif ( $contact ) {
				$email_match = 0 === strcasecmp( $contact, (string) $order->get_billing_email() );
				$phone_match = preg_replace( '/\D+/', '', $contact ) === preg_replace( '/\D+/', '', (string) $order->get_billing_phone() );
				$allowed     = $email_match || $phone_match;
			}
		}

		if ( $order && $allowed ) {
			$result = beautyin_page_suite_track_order_result( $order );
		} else {
			$result = '<div class="bm-track-result is-error"><h2>Order not found</h2><p>Check the order ID and use the billing email or mobile number from checkout. Logged-in customers can also track their own orders directly.</p></div>';
		}
	}

	$form = '<div class="bm-track-form bm-track-card"><div class="bm-track-card__header"><span>Order lookup</span><h2>Track your order</h2><p>Enter your order ID. Guests must also enter the billing email or mobile number used at checkout. Logged-in customers can track with order ID only.</p></div><form method="post"><input type="number" name="bm_track_order_id" placeholder="Order ID" value="' . esc_attr( $order_id ?: '' ) . '" required><input type="text" name="bm_track_contact" placeholder="Billing email or mobile number (optional if logged in)" value="' . esc_attr( $contact ) . '"><button type="submit">Track order</button></form><div class="bm-track-form__tip">Need help? Use the same phone or email you used at checkout for a match.</div></div>';
	$account = beautyin_page_suite_account_url();
	$latest_order = beautyin_page_suite_latest_customer_order();
	$quick_actions = array(
		array( 'My orders', 'Open your account order history, invoices and delivery updates.', $account . 'orders/' ),
		array( 'Account tracking', 'Use your account to review status without re-entering details.', $account . 'orders/' ),
		array( 'Need help?', 'Contact support if courier updates are delayed or details need a correction.', beautyin_page_suite_page_url( 'contact-us' ) ),
	);

	if ( $latest_order ) {
		$quick_actions[0] = array( 'Latest order', 'Jump straight to your newest order in one click.', $latest_order['url'] );
	}

	return $form . $result . beautyin_page_suite_cards( $quick_actions );
}

function beautyin_page_suite_track_order_result( $order ) {
	$status = $order->get_status();
	$steps  = beautyin_page_suite_track_order_steps( $status );
	$tracking = beautyin_page_suite_get_tracking_summary( $order );
	$tracking_number = $tracking['number'];
	$tracking_carrier = $tracking['carrier'];
	$tracking_url = $tracking['url'];
	$view_url    = $order->get_view_order_url();
	$payment_text = $order->is_paid() ? 'Paid' : 'Pending';
	$order_total  = $order->get_formatted_order_total();
	$order_date   = $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '';
	$billing_name = trim( $order->get_formatted_billing_full_name() );
	$shipping_to  = trim( $order->get_formatted_shipping_full_name() );
	$summary_note = $tracking_number ? 'Your parcel reference has been added by the team.' : ( in_array( $status, array( 'delivered', 'completed' ), true ) ? 'This order has been delivered.' : 'Tracking reference will appear once dispatch details are updated.' );

	$html  = '<div class="bm-track-result">';
	$html .= '<div class="bm-track-result__head"><div><span>Order #' . esc_html( $order->get_order_number() ) . '</span><strong>' . esc_html( wc_get_order_status_name( $status ) ) . '</strong></div><em>' . esc_html( $summary_note ) . '</em></div>';
	$html .= '<div class="bm-track-result__meta">';
	$html .= $order_date ? '<div class="bm-track-meta-chip"><small>Placed on</small><strong>' . esc_html( $order_date ) . '</strong></div>' : '';
	$html .= $billing_name ? '<div class="bm-track-meta-chip"><small>Customer</small><strong>' . esc_html( $billing_name ) . '</strong></div>' : '';
	$html .= $shipping_to ? '<div class="bm-track-meta-chip"><small>Ship to</small><strong>' . esc_html( $shipping_to ) . '</strong></div>' : '';
	$html .= '<div class="bm-track-meta-chip"><small>Payment</small><strong>' . esc_html( $payment_text ) . '</strong></div>';
	$html .= '</div>';
	$html .= '<div class="bm-track-grid">';
	$html .= '<div><strong>Total</strong><p>' . wp_kses_post( $order_total ) . '</p></div>';
	$html .= '<div><strong>Payment</strong><p>' . esc_html( $payment_text ) . '</p></div>';
	$html .= '<div><strong>Courier</strong><p>' . esc_html( $tracking_carrier ? $tracking_carrier : 'Not added yet' ) . '</p></div>';
	$html .= '<div><strong>Tracking link</strong><p>' . ( $tracking_url ? '<a class="bm-track-inline-link" href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener">Open carrier tracking</a>' : '<span class="bm-track-inline-muted">Not added yet</span>' ) . '</p></div>';
	$html .= '</div>';
	$html .= '<div class="bm-track-order-note">';
	$html .= '<strong>Tracking reference</strong><span>' . esc_html( $tracking_number ? $tracking_number : 'Will appear after dispatch is updated.' ) . '</span>';
	$html .= '</div>';
	$html .= '<div class="bm-track-meta bm-track-meta--timeline">';
	$html .= '<strong>Delivery progress</strong>';
	$html .= '<div class="bm-track-steps bm-track-steps--compact">';
	foreach ( $steps as $step ) {
		$html .= '<span class="' . esc_attr( $step['active'] ? 'is-done' : '' ) . '">' . esc_html( $step['label_short'] ) . '</span>';
	}
	$html .= '</div>';
	$html .= '</div>';
	if ( $tracking_url ) {
		$html .= '<p><a class="bm-page-btn" href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener">Open carrier tracking</a></p>';
	}
	$html .= '<p><a class="bm-page-btn bm-page-btn-light" href="' . esc_url( $view_url ) . '">View order details</a></p>';
	$html .= '</div>';

	return $html;
}

function beautyin_page_suite_get_tracking_summary( $order ) {
	$tracking = array(
		'carrier' => (string) $order->get_meta( '_beautyin_delivery_carrier', true ),
		'number'  => (string) $order->get_meta( '_beautyin_delivery_receipt_number', true ),
		'url'     => (string) $order->get_meta( '_beautyin_delivery_tracking_url', true ),
	);

	if ( ! $order instanceof WC_Order ) {
		return $tracking;
	}

	$tracking_settings = class_exists( 'VI_WOO_ORDERS_TRACKING_DATA' ) ? VI_WOO_ORDERS_TRACKING_DATA::get_instance() : null;
	foreach ( $order->get_items() as $item_id => $item ) {
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

			$tracking_number = beautyin_page_suite_array_value( $tracking_data, array( 'tracking_number' ), '' );
			if ( ! $tracking_number ) {
				continue;
			}

			$carrier_slug = beautyin_page_suite_array_value( $tracking_data, array( 'carrier_slug' ), '' );
			$carrier_name = beautyin_page_suite_array_value( $tracking_data, array( 'carrier_name', 'formatted_tracking_provider', 'tracking_provider', 'custom_tracking_provider' ), '' );
			$carrier_url  = beautyin_page_suite_array_value( $tracking_data, array( 'tracking_url', 'carrier_url', 'custom_tracking_link', 'ast_tracking_link' ), '' );
			if ( ! $carrier_url && $tracking_settings instanceof VI_WOO_ORDERS_TRACKING_DATA ) {
				$carrier_url = $tracking_settings->get_url_tracking( $carrier_url, $tracking_number, $carrier_slug, $order->get_shipping_postcode(), false, true, $order->get_id() );
			}

			$tracking['number']  = $tracking_number;
			$tracking['carrier']  = $carrier_name ? $carrier_name : $tracking['carrier'];
			$tracking['url']     = $carrier_url ? $carrier_url : $tracking['url'];
		}
	}

	return $tracking;
}

function beautyin_page_suite_array_value( $array, $keys, $fallback ) {
	foreach ( $keys as $key ) {
		if ( isset( $array[ $key ] ) && '' !== $array[ $key ] ) {
			return $array[ $key ];
		}
	}
	return $fallback;
}

function beautyin_page_suite_track_order_steps( $status ) {
	$order = array(
		array( 'label' => 'Placed', 'label_short' => 'Placed', 'active' => true ),
		array( 'label' => 'Accepted', 'label_short' => 'Accepted', 'active' => in_array( $status, array( 'processing', 'accepted', 'packed', 'out-for-delivery', 'completed', 'delivered' ), true ) ),
		array( 'label' => 'Packed', 'label_short' => 'Packed', 'active' => in_array( $status, array( 'packed', 'out-for-delivery', 'completed', 'delivered' ), true ) ),
		array( 'label' => 'Out for delivery', 'label_short' => 'Out', 'active' => in_array( $status, array( 'out-for-delivery', 'completed', 'delivered' ), true ) ),
		array( 'label' => 'Delivered', 'label_short' => 'Delivered', 'active' => in_array( $status, array( 'completed', 'delivered' ), true ) ),
	);

	return $order;
}

function beautyin_page_suite_latest_customer_order() {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_orders' ) ) {
		return false;
	}

	$orders = wc_get_orders( array(
		'customer_id' => get_current_user_id(),
		'limit'       => 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'return'      => 'objects',
	) );

	if ( empty( $orders ) || ! $orders[0] instanceof WC_Order ) {
		return false;
	}

	$order = $orders[0];
	return array(
		'url' => $order->get_view_order_url(),
	);
}

function beautyin_page_suite_seo_pages() {
	return beautyin_page_suite_cards( array(
		array( 'Korean beauty in India', 'National landing page for shoppers searching Korean products across India.', beautyin_page_suite_page_url( 'korean-beauty-india' ) ),
		array( 'Korean beauty in Delhi', 'Delhi NCR landing page for Korean skincare, makeup and beauty products.', beautyin_page_suite_page_url( 'korean-beauty-delhi' ) ),
		array( 'Korean beauty in Mumbai', 'Mumbai landing page for Korean skincare, makeup and beauty products.', beautyin_page_suite_page_url( 'korean-beauty-mumbai' ) ),
		array( 'Korean beauty in Goa', 'Goa landing page for Korean skincare, makeup and beauty products.', beautyin_page_suite_page_url( 'korean-beauty-goa' ) ),
		array( 'Korean beauty in Punjab', 'Punjab landing page for Korean skincare, makeup and beauty products.', beautyin_page_suite_page_url( 'korean-beauty-punjab' ) ),
		array( 'Korean skincare routine', 'Step-by-step routine guide for beginners.', beautyin_page_suite_page_url( 'korean-skincare-routine-guide' ) ),
		array( 'Skin type guide', 'Choose products for oily, dry, combination and sensitive skin.', beautyin_page_suite_page_url( 'choose-products-for-your-skin-type' ) ),
		array( 'Ingredients guide', 'Learn niacinamide, centella, hyaluronic acid and more.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
		array( 'Authenticity guarantee', 'Learn how the marketplace approaches original Korean products.', beautyin_page_suite_page_url( 'authenticity-guarantee' ) ),
	) );
}

function beautyin_page_suite_blog() {
	$posts = get_posts( array( 'numberposts' => 6, 'post_status' => 'publish' ) );
	if ( empty( $posts ) ) {
		return beautyin_page_suite_cards( array(
			array( 'Korean skincare routine guide', 'Start with cleanser, toner, serum, cream and sunscreen.', beautyin_page_suite_page_url( 'korean-skincare-routine-guide' ) ),
			array( 'Ingredients guide', 'Understand active ingredients before buying.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
		) );
	}
	$items = array();
	foreach ( $posts as $post ) {
		$items[] = array( get_the_title( $post ), get_the_excerpt( $post ) ? get_the_excerpt( $post ) : 'Read the Beauty In journal update.', get_permalink( $post ) );
	}
	return beautyin_page_suite_cards( $items );
}

function beautyin_page_suite_search_page() {
	return '<div class="bm-support-search"><h2>Search marketplace</h2><p>Search products, brands, ingredients or help topics.</p><form method="get" action="' . esc_url( home_url( '/' ) ) . '"><input type="search" name="s" placeholder="Search Korean skincare, niacinamide, sunscreen..." /><input type="hidden" name="post_type" value="product" /><button type="submit">Search</button></form></div>';
}

function beautyin_page_suite_404_page() {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	return '<div class="bm-empty-state"><h2>Page not found</h2><p>The page may have moved, the link may be old, or the product may no longer be available.</p><a class="bm-page-btn" href="' . esc_url( $shop ) . '">Continue shopping</a></div>' . beautyin_page_suite_cards( array(
		array( 'Search products', 'Find skincare, makeup, serums, masks and creams.', beautyin_page_suite_page_url( 'search-results' ) ),
		array( 'Contact support', 'Ask the KIL India team for help finding a product or order.', beautyin_page_suite_page_url( 'contact-us' ) ),
		array( 'Sitemap', 'Browse all important Beauty In pages.', beautyin_page_suite_page_url( 'sitemap' ) ),
	) );
}

function beautyin_page_suite_sitemap() {
	$items = array();
	foreach ( beautyin_page_suite_sitemap_urls() as $url ) {
		$label   = trim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$label   = $label ? ucwords( str_replace( '-', ' ', basename( $label ) ) ) : 'Home';
		$items[] = array( $label, $url, $url );
	}
	return '<p class="bm-page-center"><a class="bm-page-btn" href="' . esc_url( beautyin_page_suite_page_url( 'sitemap' ) . '?xml=1' ) . '">Open XML sitemap</a></p>' . beautyin_page_suite_cards( $items );
}

function beautyin_page_suite_payment_success() {
	$account = beautyin_page_suite_account_url();
	return beautyin_page_suite_cards( array(
		array( 'View order history', 'Check payment status, invoice and delivery updates.', $account . 'orders/' ),
		array( 'Continue shopping', 'Discover more Korean skincare and beauty products.', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ),
	) );
}

function beautyin_page_suite_payment_failed() {
	return beautyin_page_suite_cards( array(
		array( 'Retry checkout', 'Return to checkout and choose an available payment method.', beautyin_page_suite_checkout_url() ),
		array( 'Contact support', 'Share payment reference or screenshot if amount was debited.', beautyin_page_suite_page_url( 'contact-us' ) ),
	) );
}

function beautyin_page_suite_thank_you() {
	$account = beautyin_page_suite_account_url();
	return '<div class="bm-empty-state"><h2>Your order journey continues in your account</h2><p>WooCommerce order received pages are generated by checkout after payment. Use your account to view confirmed orders.</p><a class="bm-page-btn" href="' . esc_url( $account . 'orders/' ) . '">View my orders</a></div>';
}

function beautyin_page_suite_routine_guide() {
	return beautyin_page_suite_steps( array(
		array( 'Cleanser', 'Start with a gentle cleanser to remove sweat, oil, sunscreen and daily buildup.' ),
		array( 'Toner or essence', 'Hydrate and prepare skin before targeted serums.' ),
		array( 'Serum', 'Choose actives like niacinamide, centella or hyaluronic acid based on skin concern.' ),
		array( 'Cream', 'Seal hydration with a moisturizer suited to your skin type.' ),
		array( 'Sunscreen', 'Use SPF every morning, especially when using brightening or active ingredients.' ),
	) );
}

function beautyin_page_suite_skin_type_guide() {
	return beautyin_page_suite_cards( array(
		array( 'Oily skin', 'Look for lightweight gel creams, niacinamide and non-greasy sunscreen.' ),
		array( 'Dry skin', 'Choose hyaluronic acid, ceramides, barrier creams and nourishing masks.' ),
		array( 'Sensitive skin', 'Start with centella, fragrance-aware formulas and fewer actives.' ),
		array( 'Combination skin', 'Use light hydration with richer cream only on dry zones.' ),
		array( 'Acne-prone skin', 'Patch test products and avoid heavy layers that clog easily.' ),
	) );
}

function beautyin_page_suite_ingredients_guide() {
	return beautyin_page_suite_cards( array(
		array( 'Niacinamide', 'Supports tone, oil balance and skin barrier appearance.' ),
		array( 'Centella asiatica', 'Often used in calming products for sensitive-looking skin.' ),
		array( 'Hyaluronic acid', 'Helps skin feel hydrated and plump by attracting water.' ),
		array( 'Collagen', 'Common in moisturizing formulas for soft, smooth skin feel.' ),
		array( 'Ceramides', 'Support the skin barrier and comfort for dry skin routines.' ),
		array( 'AHA/BHA', 'Exfoliating acids should be introduced slowly and paired with sunscreen.' ),
	) );
}

function beautyin_page_suite_reviews() {
	return '<div class="bm-empty-state"><h2>Product reviews live on product pages</h2><p>Verified reviews and ratings are connected to each WooCommerce product. Open a product and use the Reviews tab to read or add feedback.</p><a class="bm-page-btn" href="' . esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ) . '">Shop products</a></div>';
}

function beautyin_page_suite_before_after() {
	return beautyin_page_suite_steps( array(
		array( 'Take baseline photos', 'Use the same lighting, angle and time of day for fair comparison.' ),
		array( 'Patch test first', 'Introduce new actives slowly and stop if irritation occurs.' ),
		array( 'Track routine', 'Record cleanser, serum, cream and sunscreen use for 4-8 weeks.' ),
		array( 'Stay realistic', 'Results vary by skin type, concern, consistency and product suitability.' ),
	) );
}

function beautyin_page_suite_authenticity() {
	return '<div class="bm-policy-copy"><h2>100% original product focus</h2><p>MOA Beauty, powered by KIL INDIA TRADE PRIVATE LIMITED, is built around verified marketplace sellers and authentic Korean product sourcing. Sellers must provide accurate product and business details.</p><h2>Seller review</h2><p>Domestic and international vendors can be reviewed before approval. International sellers may need additional document verification.</p><h2>Customer support</h2><p>If product authenticity, packaging or batch details look suspicious, contact support with order ID and product photos.</p></div>';
}

function beautyin_page_suite_location_landing( $location ) {
	$locations = array(
		'india' => array(
			'label'      => 'India',
			'lead'       => 'Shop Korean skincare, makeup and personal care across India with verified sellers, secure checkout and dependable support.',
			'headline'   => 'Korean Beauty for shoppers across India',
			'benefits'   => array(
				'Search intent' => 'Built for shoppers searching Korean beauty products in India, from product pages to support.',
				'Delivery reach' => 'Serviceability depends on pincode, but the store is designed for India-first fulfillment.',
				'Trust layer' => 'Verified sellers, order support, GST billing where applicable and secure payments.',
			),
			'cards'      => array(
				array( 'Shop by routine', 'Browse cleanser, toner, serum, cream and SPF guidance for Indian shoppers.', beautyin_page_suite_page_url( 'korean-skincare-routine-guide' ) ),
				array( 'Choose by skin type', 'Find oily, dry, combination, sensitive and acne-prone friendly picks.', beautyin_page_suite_page_url( 'choose-products-for-your-skin-type' ) ),
				array( 'Ingredients guide', 'Learn what niacinamide, centella and hyaluronic acid do before buying.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
				array( 'Authenticity guarantee', 'Understand how the marketplace reviews product sourcing and sellers.', beautyin_page_suite_page_url( 'authenticity-guarantee' ) ),
			),
		),
		'delhi' => array(
			'label'      => 'Delhi NCR',
			'lead'       => 'Discover Korean skincare and makeup in Delhi with a curated marketplace built for fast local shopping and India-wide delivery support.',
			'headline'   => 'Korean Beauty in Delhi NCR',
			'benefits'   => array(
				'Delhi-first intent' => 'Targets Korean beauty searches from Delhi, New Delhi and NCR buyers looking for trusted product options.',
				'Better fit' => 'Great for shoppers comparing original Korean skincare, daily makeup and beauty essentials in one place.',
				'Support' => 'Use account tracking, order support and clear delivery information when you buy online.',
			),
			'cards'      => array(
				array( 'For humid seasons', 'Lightweight toners, gel creams and breathable makeup tend to work well in city routines.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
				array( 'Daily skin routine', 'Morning and evening routines tailored for busy office and student schedules.', beautyin_page_suite_page_url( 'korean-skincare-routine-guide' ) ),
				array( 'Shop original products', 'Verified sellers and authenticity-focused marketplace structure help reduce confusion.', beautyin_page_suite_page_url( 'authenticity-guarantee' ) ),
				array( 'Track your order', 'Use order ID and account details to review courier progress after checkout.', beautyin_page_suite_page_url( 'track-order' ) ),
			),
		),
		'mumbai' => array(
			'label'      => 'Mumbai',
			'lead'       => 'Shop Korean beauty products in Mumbai with a clean, high-trust buying experience built for skincare fans, makeup lovers and fast online checkout.',
			'headline'   => 'Korean Beauty in Mumbai',
			'benefits'   => array(
				'Mumbai searches' => 'A strong landing page for buyers looking for Korean skincare in Mumbai and nearby locations.',
				'Urban routines' => 'Useful for buyers who want lightweight layers, glow formulas and easy daily-use products.',
				'Reliable flow' => 'Cart, checkout, payment and support are kept simple so shoppers can move quickly.',
			),
			'cards'      => array(
				array( 'Lightweight skincare', 'Choose products that feel comfortable in humid, high-activity city routines.', beautyin_page_suite_page_url( 'choose-products-for-your-skin-type' ) ),
				array( 'Makeup picks', 'Explore base, lip and daily wear makeup from the curated marketplace selection.', beautyin_page_suite_page_url( 'store' ) ),
				array( 'Trusted support', 'Order help, shipping questions and product queries stay easy to find.', beautyin_page_suite_page_url( 'contact-us' ) ),
				array( 'Delivery across India', 'Mumbai buyers can order with the same India-wide shipping support model.', beautyin_page_suite_page_url( 'delivery-across-india' ) ),
			),
		),
		'goa' => array(
			'label'      => 'Goa',
			'lead'       => 'Find Korean skincare and beauty products in Goa with a destination-friendly shopping page that still feels premium and India-ready.',
			'headline'   => 'Korean Beauty in Goa',
			'benefits'   => array(
				'Coastal routine fit' => 'Helpful for buyers who want lighter textures, hydration and glow-focused skincare.',
				'Tourist + local search' => 'Matches both local shoppers and destination buyers searching Korean beauty online in Goa.',
				'Easy checkout' => 'Simple checkout and support-driven service make the buying journey less confusing.',
			),
			'cards'      => array(
				array( 'Hydration first', 'Hyaluronic acid, barrier care and moisture support for warm-weather routines.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
				array( 'Skin type help', 'Find product choices that feel right for oily, dry and combination skin.', beautyin_page_suite_page_url( 'choose-products-for-your-skin-type' ) ),
				array( 'Order support', 'Use the account order area or contact support if delivery details need updates.', beautyin_page_suite_page_url( 'track-order' ) ),
				array( 'Buyer confidence', 'See authenticity guidance and product structure before you order.', beautyin_page_suite_page_url( 'authenticity-guarantee' ) ),
			),
		),
		'punjab' => array(
			'label'      => 'Punjab',
			'lead'       => 'Shop Korean skincare, makeup and beauty essentials in Punjab with a page designed for city and state-level search intent.',
			'headline'   => 'Korean Beauty in Punjab',
			'benefits'   => array(
				'Punjab search intent' => 'Helpful for shoppers looking for Korean beauty products in Punjab and major nearby cities.',
				'Seasonal routine' => 'Useful for richer hydration, barrier care and comfortable daily wear products.',
				'Support-led commerce' => 'Track orders, reach support and explore authentic product options without clutter.',
			),
			'cards'      => array(
				array( 'Dry-skin support', 'Look for creams, serums and repair-focused formulas that suit seasonal dryness.', beautyin_page_suite_page_url( 'ingredients-guide' ) ),
				array( 'Simple routine', 'A practical Korean skincare routine can be built around cleanser, serum, cream and SPF.', beautyin_page_suite_page_url( 'korean-skincare-routine-guide' ) ),
				array( 'Order tracking', 'Logged-in customers can track delivery progress from their account area.', beautyin_page_suite_page_url( 'track-order' ) ),
				array( 'Bulk and vendor help', 'Businesses and resellers can request quotes and vendor support when needed.', beautyin_page_suite_page_url( 'request-for-quote' ) ),
			),
		),
	);

	if ( ! isset( $locations[ $location ] ) ) {
		$location = 'india';
	}

	$data   = $locations[ $location ];
	$shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$track  = beautyin_page_suite_page_url( 'track-order' );
	$contact = beautyin_page_suite_page_url( 'contact-us' );
	$cards  = beautyin_page_suite_cards( $data['cards'] );
	$hero   = sprintf(
		'<div class="bm-about-hero"><div class="bm-about-hero-copy"><span>Location SEO</span><h1>%s</h1><p class="bm-about-hero-lead">%s</p><p>MOA Beauty is powered by KIL INDIA TRADE PRIVATE LIMITED and built to surface K-beauty and Korean beauty products for India-focused search queries. The page structure below keeps the intent clear for both shoppers and search engines.</p></div><div class="bm-about-hero-side"><div><strong>Primary location</strong><small>%s</small></div><div><strong>Buyer intent</strong><small>%s</small></div><div><strong>Quick actions</strong><small>Track orders, browse products and contact support from one place.</small></div></div></div>',
		esc_html( $data['headline'] ),
		esc_html( $data['lead'] ),
		esc_html( $data['label'] ),
		esc_html( 'Korean beauty in ' . $data['label'] )
	);

	$intro = sprintf(
		'<div class="bm-about-panels"><div class="bm-about-card"><h2>Why this page exists</h2><p>Searchers in %1$s should find a focused landing page for K-beauty, Korean skincare and beauty products instead of a generic category dump. This page helps match local search intent while keeping the shopping journey on brand.</p></div><div class="bm-about-card"><h2>What visitors can do next</h2><p>Browse the store, read ingredient guidance, use order tracking and contact support if you need help with delivery, product fit or vendor enquiries.</p></div></div>',
		esc_html( $data['label'] )
	);

	$metrics = sprintf(
		'<div class="bm-about-metrics"><div><strong>%s</strong><span>Focused keyword targeting</span></div><div><strong>%s</strong><span>Secure checkout + support</span></div><div><strong>%s</strong><span>Delivery support where serviceable</span></div><div><strong>%s</strong><span>Verified marketplace sourcing</span></div></div>',
		esc_html( $data['label'] ),
		esc_html( 'India' ),
		esc_html( 'Track order' ),
		esc_html( 'Original products' )
	);

	$process = beautyin_page_suite_steps( array(
		array( 'Discover', 'Find Korean skincare and beauty products matched to the location and search intent.' ),
		array( 'Compare', 'Read routine and ingredient guidance before buying to reduce confusion.' ),
		array( 'Checkout', 'Use the secure cart and payment flow with delivery support where applicable.' ),
		array( 'Track', 'Use your account and order page to follow delivery progress after purchase.' ),
	) );

	$cta = sprintf(
		'<div class="bm-about-cta"><div><strong>Continue shopping from %1$s</strong><p>Move from the city landing page to the store, support or order tracking area without losing the clean marketplace flow.</p></div><div class="bm-about-cta-actions"><a class="bm-page-btn" href="%2$s">Shop now</a><a class="bm-page-btn bm-about-ghost" href="%3$s">Track order</a><a class="bm-page-btn bm-about-ghost" href="%4$s">Contact support</a></div></div>',
		esc_html( $data['label'] ),
		esc_url( $shop ),
		esc_url( $track ),
		esc_url( $contact )
	);

	return '<div class="bm-about-page bm-location-page">' . $hero . $intro . $metrics . '<div class="bm-about-card bm-about-process"><h2>How shoppers use this page</h2>' . $process . '</div><div class="bm-about-panels bm-about-panels--tight"><div class="bm-about-card"><h2>What to expect</h2><p>Simple categories, location-aware copy and support links that help both discovery and conversion.</p></div><div class="bm-about-card"><h2>Support and trust</h2><p>Powered by KIL India and backed by the same support stack used across the marketplace.</p></div></div>' . $cards . $cta . '</div>';
}

function beautyin_page_suite_rewards() {
	return '<div class="bm-empty-state"><h2>Loyalty program foundation</h2><p>Rewards can be connected through a dedicated WooCommerce loyalty plugin when final rules are approved. Suggested rewards: repeat purchases, reviews, birthdays and referrals.</p><a class="bm-page-btn" href="' . esc_url( beautyin_page_suite_page_url( 'profile-settings' ) ) . '">Keep profile updated</a></div>';
}

function beautyin_page_suite_referrals() {
	return '<div class="bm-empty-state"><h2>Referral program foundation</h2><p>Referral tracking requires a referral plugin or coupon automation. This page is ready for public terms, referral links and campaign details once the rewards system is selected.</p><a class="bm-page-btn" href="' . esc_url( beautyin_page_suite_page_url( 'contact-us' ) ) . '">Contact support</a></div>';
}

function beautyin_page_suite_steps( $steps ) {
	$html = '<ol class="bm-suite-steps">';
	foreach ( $steps as $step ) {
		$html .= '<li><strong>' . esc_html( $step[0] ) . '</strong><span>' . esc_html( $step[1] ) . '</span></li>';
	}
	return $html . '</ol>';
}

function beautyin_page_suite_sitemap_urls() {
	$urls = array( home_url( '/' ), function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) );
	foreach ( array_keys( beautyin_page_suite_map() ) as $slug ) {
		$urls[] = beautyin_page_suite_page_url( $slug );
	}

	if ( function_exists( 'wc_get_products' ) ) {
		$product_ids = wc_get_products( array(
			'status' => 'publish',
			'limit'  => 500,
			'return' => 'ids',
		) );
		foreach ( $product_ids as $product_id ) {
			$urls[] = get_permalink( $product_id );
		}
	}

	$product_categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => 200,
	) );
	if ( ! is_wp_error( $product_categories ) ) {
		foreach ( $product_categories as $category ) {
			$link = get_term_link( $category );
			if ( ! is_wp_error( $link ) ) {
				$urls[] = $link;
			}
		}
	}

	return array_values( array_unique( array_filter( $urls ) ) );
}

function beautyin_page_suite_account_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
}

function beautyin_page_suite_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . trim( $slug, '/' ) . '/' );
}

function beautyin_page_suite_styles() {
	wp_register_style( 'beautyin-page-suite', false, array(), '1.0.0' );
	wp_enqueue_style( 'beautyin-page-suite' );
	wp_add_inline_style(
		'beautyin-page-suite',
		'
		.bm-suite-panel .bm-suite-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 14px;
		}
		.bm-suite-panel .bm-suite-grid > a,
		.bm-suite-panel .bm-suite-grid > span {
			display: block;
			min-height: 112px;
			padding: 18px;
			border: 1px solid #eadfe7;
			border-radius: 12px;
			background: #fff;
			color: #17151c;
			text-decoration: none;
		}
		.bm-suite-panel .bm-suite-grid strong,
		.bm-suite-panel .bm-suite-grid small {
			display: block;
		}
		.bm-suite-panel .bm-suite-grid strong {
			font-size: 17px;
			line-height: 1.2;
		}
		.bm-suite-panel .bm-suite-grid small {
			margin-top: 8px;
			color: #6d6573;
			line-height: 1.5;
		}
		.bm-suite-steps {
			display: grid;
			gap: 12px;
			margin: 0;
			padding: 0;
			list-style: none;
			counter-reset: bm-step;
		}
		.bm-suite-steps li {
			counter-increment: bm-step;
			display: grid;
			grid-template-columns: 48px minmax(0, 1fr);
			gap: 14px;
			padding: 16px;
			border: 1px solid #eadfe7;
			border-radius: 12px;
			background: #fff;
		}
		.bm-suite-steps li::before {
			content: counter(bm-step);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 40px;
			height: 40px;
			border-radius: 999px;
			background: #17151c;
			color: #fff;
			font-weight: 900;
		}
		.bm-suite-steps strong,
		.bm-suite-steps span {
			display: block;
			grid-column: 2;
		}
		.bm-suite-steps span {
			margin-top: -20px;
			color: #6d6573;
			line-height: 1.6;
		}
		.bm-faq-list {
			display: grid;
			gap: 10px;
		}
		.bm-faq-list details {
			padding: 16px 18px;
			border: 1px solid #eadfe7;
			border-radius: 12px;
			background: #fff;
		}
		.bm-faq-list summary {
			color: #17151c;
			font-weight: 900;
			cursor: pointer;
		}
		.bm-faq-list p {
			margin: 10px 0 0;
			color: #6d6573;
			line-height: 1.6;
		}
		.bm-track-form {
			display: grid;
			gap: 18px;
			padding: 28px;
			border-radius: 24px;
			background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
			border: 1px solid #efe0e7;
			box-shadow: 0 18px 44px rgba(28, 16, 24, .08);
		}
		.bm-track-card__header span {
			display: inline-flex;
			align-items: center;
			min-height: 22px;
			padding: 0 10px;
			border-radius: 999px;
			background: #17151c;
			color: #fff;
			font-size: 10px;
			font-weight: 800;
			letter-spacing: .12em;
			text-transform: uppercase;
		}
		.bm-track-form h2 {
			margin: 0;
			font-size: clamp(28px, 2.8vw, 38px);
			line-height: 1.08;
			letter-spacing: -.04em;
		}
		.bm-track-card__header p,
		.bm-track-form__tip {
			margin: 0;
			color: #6d6573;
			line-height: 1.6;
			max-width: 78ch;
		}
		.bm-track-form form {
			display: grid;
			grid-template-columns: minmax(0, 240px) minmax(0, 1fr) auto;
			gap: 12px;
			align-items: center;
			margin-top: 2px;
		}
		.bm-track-form input {
			width: 100%;
			min-height: 56px;
			padding: 14px 16px;
			border: 1px solid #eadfe7;
			border-radius: 16px;
			background: #fff;
			font-size: 15px;
			box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
		}
		.bm-track-form button {
			min-height: 56px;
			padding: 0 22px;
			border: 0;
			border-radius: 999px;
			background: linear-gradient(135deg, #17151c 0%, #6a2944 46%, #d12d6a 100%);
			color: #fff;
			font-weight: 800;
			cursor: pointer;
			box-shadow: 0 16px 30px rgba(28, 16, 24, .18);
		}
		.bm-track-result {
			margin-top: 18px;
			padding: 24px;
			border: 1px solid #eadfe7;
			border-radius: 26px;
			background: linear-gradient(180deg, #fff 0%, #fff7fb 100%);
			box-shadow: 0 18px 42px rgba(28, 16, 24, .08);
			display: grid;
			gap: 16px;
		}
		.bm-track-result.is-error {
			background: #fff;
		}
		.bm-track-result__head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 16px;
			flex-wrap: wrap;
		}
		.bm-track-result__head > div {
			display: grid;
			gap: 6px;
		}
		.bm-track-result__head span {
			color: #6d6573;
			font-size: 13px;
			font-weight: 700;
		}
		.bm-track-result__head strong {
			color: #17151c;
			font-size: 18px;
			line-height: 1.2;
		}
		.bm-track-result__head em {
			max-width: 280px;
			color: #7c7280;
			font-size: 13px;
			font-style: normal;
			line-height: 1.5;
		}
		.bm-track-result__meta {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 10px;
		}
		.bm-track-meta-chip {
			display: grid;
			gap: 4px;
			padding: 14px 16px;
			border-radius: 18px;
			border: 1px solid #efe2e9;
			background: #fff;
		}
		.bm-track-result__meta small {
			color: #8a8090;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: .08em;
		}
		.bm-track-result__meta strong {
			font-size: 14px;
			line-height: 1.35;
		}
		.bm-track-steps {
			display: grid;
			grid-template-columns: repeat(5, minmax(0, 1fr));
			gap: 10px;
		}
		.bm-track-steps span {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 44px;
			padding: 10px 12px;
			border-radius: 999px;
			border: 1px solid #eadfe7;
			background: #fff;
			color: #817989;
			font-size: 12px;
			font-weight: 800;
			text-align: center;
		}
		.bm-track-steps span.is-done {
			border-color: #17151c;
			background: #17151c;
			color: #fff;
		}
		.bm-track-steps--compact span {
			min-height: 34px;
			font-size: 11px;
		}
		.bm-track-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px;
		}
		.bm-track-grid > div,
		.bm-track-meta {
			padding: 16px 18px;
			border-radius: 18px;
			background: #fff;
			border: 1px solid #f0e6ec;
		}
		.bm-track-grid strong,
		.bm-track-meta strong {
			display: block;
			margin-bottom: 6px;
			font-size: 11px;
			color: #7a6c79;
			text-transform: uppercase;
			letter-spacing: .09em;
		}
		.bm-track-grid p {
			margin: 0;
			color: #17151c;
			font-weight: 700;
			font-size: 16px;
		}
		.bm-track-inline-link {
			color: #d12d6a;
			font-weight: 800;
			text-decoration: none;
		}
		.bm-track-inline-link:hover {
			text-decoration: underline;
		}
		.bm-track-inline-muted {
			color: #a399a7;
			font-weight: 600;
		}
		.bm-track-order-note {
			padding: 14px 16px;
			border-radius: 18px;
			background: #fff;
			border: 1px solid #f0e6ec;
		}
		.bm-track-order-note strong {
			display: block;
			margin-bottom: 5px;
			font-size: 11px;
			color: #7a6c79;
			text-transform: uppercase;
			letter-spacing: .09em;
		}
		.bm-track-order-note span {
			color: #17151c;
			font-size: 15px;
			font-weight: 700;
		}
		.bm-track-meta--timeline {
			padding: 16px 18px;
			border-radius: 18px;
			background: #fff;
			border: 1px solid #f0e6ec;
			display: grid;
			gap: 12px;
		}
		.bm-track-meta--timeline strong {
			margin-bottom: 0;
		}
		.bm-track-result .bm-page-btn,
		.bm-track-result .bm-page-btn-light {
			justify-self: start;
			min-width: 190px;
		}
		@media (max-width: 900px) {
			.bm-suite-panel .bm-suite-grid {
				grid-template-columns: 1fr;
			}
			.bm-track-form {
				padding: 20px;
			}
			.bm-track-form form,
			.bm-track-grid,
			.bm-track-steps {
				grid-template-columns: 1fr;
			}
			.bm-track-result__meta {
				grid-template-columns: 1fr 1fr;
			}
			.bm-track-meta--timeline,
			.bm-track-order-note {
				padding: 14px 16px;
			}
			.bm-track-result__head em {
				max-width: 100%;
			}
		}
		'
	);

	if ( is_page( 'about-us' ) ) {
		wp_add_inline_style(
			'beautyin-page-suite',
			'
			body.beautyin-about-us .entry-header,
			body.beautyin-about-us .page-header,
			body.beautyin-about-us .page-title,
			body.beautyin-about-us .entry-title,
			body.beautyin-city-page .entry-header,
			body.beautyin-city-page .page-header,
			body.beautyin-city-page .page-title,
			body.beautyin-city-page .entry-title {
				display: none !important;
			}
			body.beautyin-about-us .site-content,
			body.beautyin-about-us .content-area,
			body.beautyin-about-us .site-main,
			body.beautyin-about-us .entry-content,
			body.beautyin-city-page .site-content,
			body.beautyin-city-page .content-area,
			body.beautyin-city-page .site-main,
			body.beautyin-city-page .entry-content {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}
			body.beautyin-about-us .entry-content > *:first-child,
			body.beautyin-city-page .entry-content > *:first-child {
				margin-top: 0 !important;
			}
			.bm-about-page {
				width: min(100% - 32px, 1440px);
				margin: 0 auto;
				padding: 18px 0 12px;
				color: #17151c;
			}
			.bm-about-hero {
				display: grid;
				grid-template-columns: minmax(0, 1.7fr) minmax(280px, .9fr);
				gap: 18px;
				padding: 24px 24px 26px;
				border-radius: 24px;
				background: linear-gradient(135deg, #201721 0%, #452136 48%, #d12d6a 100%);
				color: #fff;
				box-shadow: 0 20px 60px rgba(28, 16, 24, .14);
			}
			.bm-about-hero-copy,
			.bm-about-hero-side {
				position: relative;
				z-index: 1;
			}
			.bm-about-hero-copy {
				max-width: 680px;
			}
			.bm-about-hero-copy span {
				display: inline-flex;
				align-items: center;
				min-height: 22px;
				padding: 0 10px;
				border-radius: 999px;
				background: rgba(255,255,255,.12);
				color: rgba(255,255,255,.9);
				font-size: 10px;
				font-weight: 800;
				text-transform: uppercase;
				letter-spacing: .1em;
			}
			.bm-about-hero-copy h1 {
				margin: 10px 0 6px;
				color: #fff;
				font-size: clamp(30px, 3.2vw, 42px);
				line-height: 1.02;
				font-weight: 900;
				letter-spacing: -.04em;
				text-shadow: 0 10px 28px rgba(0, 0, 0, .18);
				text-wrap: balance;
			}
			.bm-about-hero-lead {
				margin: 0 0 10px;
				color: rgba(255,255,255,.94);
				font-size: 15px;
				font-weight: 800;
				line-height: 1.4;
				letter-spacing: -.01em;
			}
			.bm-about-hero-copy p {
				margin: 0;
				max-width: 62ch;
				color: rgba(255,255,255,.88);
				font-size: 15px;
				line-height: 1.65;
			}
			.bm-about-hero-side {
				display: grid;
				gap: 10px;
				align-content: center;
			}
			.bm-about-hero-side > div {
				padding: 14px 16px;
				border: 1px solid rgba(255,255,255,.16);
				border-radius: 16px;
				background: rgba(255,255,255,.08);
				backdrop-filter: blur(8px);
				box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
			}
			.bm-about-hero-side strong,
			.bm-about-hero-side small {
				display: block;
			}
			.bm-about-hero-side strong {
				font-size: 14px;
				font-weight: 900;
				color: #fff;
			}
			.bm-about-hero-side small {
				margin-top: 4px;
				color: rgba(255,255,255,.82);
				font-size: 13px;
				line-height: 1.45;
			}
			.bm-about-panels,
			.bm-about-metrics {
				display: grid;
				gap: 12px;
			}
			.bm-about-panels {
				grid-template-columns: repeat(2, minmax(0, 1fr));
				margin-top: 12px;
			}
			.bm-about-panels--tight {
				grid-template-columns: repeat(2, minmax(0, 1fr));
				margin-top: 12px;
			}
			.bm-about-card {
				margin-top: 12px;
				padding: 20px 22px;
				border: 1px solid #eadfe7;
				border-radius: 20px;
				background: #fff;
				box-shadow: 0 18px 48px rgba(28, 16, 24, .06);
			}
			.bm-about-card h2 {
				margin: 0 0 6px;
				color: #17151c;
				font-size: 22px;
				line-height: 1.18;
				font-weight: 900;
				letter-spacing: -.025em;
			}
			.bm-about-card p {
				margin: 0;
				color: #6d6573;
				font-size: 15px;
				line-height: 1.68;
			}
			.bm-about-mini {
				padding: 16px 18px;
				border-radius: 16px;
				background: #fdfbfc;
				border: 1px solid #f0e6ed;
			}
			.bm-about-mini strong,
			.bm-about-mini small {
				display: block;
			}
			.bm-about-mini strong {
				color: #17151c;
				font-size: 15px;
				line-height: 1.25;
				font-weight: 900;
			}
			.bm-about-mini small {
				margin-top: 4px;
				color: #6d6573;
				font-size: 13px;
				line-height: 1.6;
			}
			.bm-about-metrics {
				grid-template-columns: repeat(4, minmax(0, 1fr));
				margin-top: 12px;
			}
			.bm-about-metrics > div {
				padding: 16px 18px;
				border: 1px solid #eadfe7;
				border-radius: 16px;
				background: linear-gradient(180deg, #fff 0%, #fcfafc 100%);
				box-shadow: 0 12px 34px rgba(28, 16, 24, .05);
			}
			.bm-about-metrics strong,
			.bm-about-metrics span {
				display: block;
			}
			.bm-about-metrics strong {
				color: #17151c;
				font-size: 15px;
				line-height: 1.2;
				font-weight: 900;
			}
			.bm-about-metrics span {
				margin-top: 4px;
				color: #6d6573;
				font-size: 13px;
				line-height: 1.5;
			}
			.bm-about-process {
				margin-top: 12px;
			}
			.bm-about-process h2 {
				margin-bottom: 12px;
			}
			.bm-about-cta {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 18px;
				margin-top: 12px;
				padding: 20px 22px;
				border: 1px solid #eadfe7;
				border-radius: 20px;
				background: linear-gradient(135deg, #fff 0%, #fff7fb 100%);
				box-shadow: 0 18px 48px rgba(28, 16, 24, .06);
			}
			.bm-about-cta strong {
				display: block;
				color: #17151c;
				font-size: 20px;
				line-height: 1.2;
				font-weight: 900;
				letter-spacing: -.025em;
			}
			.bm-about-cta p {
				margin: 6px 0 0;
				max-width: 62ch;
				color: #6d6573;
				font-size: 15px;
				line-height: 1.65;
			}
			.bm-about-cta-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				justify-content: flex-end;
				min-width: 260px;
			}
			.bm-about-ghost {
				background: #fff !important;
				color: #17151c !important;
				border: 1px solid #eadfe7;
				box-shadow: none;
			}
			.bm-about-ghost:hover {
				background: #f8f2f7 !important;
			}
			@media (max-width: 1100px) {
				.bm-about-hero,
				.bm-about-panels,
				.bm-about-panels--tight,
				.bm-about-metrics {
					grid-template-columns: 1fr;
				}
				.bm-about-hero-copy {
					max-width: none;
				}
				.bm-about-cta {
					flex-direction: column;
					align-items: flex-start;
				}
				.bm-about-cta-actions {
					justify-content: flex-start;
					min-width: 0;
				}
			}
			@media (max-width: 700px) {
				.bm-about-page {
					width: min(100% - 18px, 1440px);
					padding-top: 16px;
				}
				.bm-about-hero,
				.bm-about-card,
				.bm-about-cta {
					padding: 18px;
					border-radius: 18px;
				}
				.bm-about-hero-copy h1 {
					font-size: clamp(28px, 9vw, 36px);
				}
				.bm-about-card h2,
				.bm-about-cta strong {
					font-size: 20px;
				}
			}
			'
		);
	}

	if ( is_page( 'cart' ) || is_page( 'cart-2' ) ) {
		wp_add_inline_style(
			'beautyin-page-suite',
			'
			body.beautyin-cart-two .entry-header,
			body.beautyin-cart-two .page-header,
			body.beautyin-cart-two .page-title,
			body.beautyin-cart-two .entry-title {
				display: none !important;
			}
			body.beautyin-cart-two .site-content,
			body.beautyin-cart-two .content-area,
			body.beautyin-cart-two .site-main,
			body.beautyin-cart-two .entry-content {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}
			body.beautyin-cart-two .entry-content > *:first-child {
				margin-top: 0 !important;
			}
			.bm-cart2 {
				width: min(100% - 32px, 1440px);
				margin: 0 auto;
				padding: 24px 0 12px;
				color: #17151c;
			}
			.bm-cart2-hero {
				position: relative;
				overflow: hidden;
				display: grid;
				grid-template-columns: minmax(0, 1fr) auto;
				align-items: center;
				gap: 24px;
				padding: 20px 24px;
				border-radius: 24px;
				background: linear-gradient(135deg, #201721 0%, #452136 48%, #d12d6a 100%);
				color: #fff;
				box-shadow: 0 20px 60px rgba(28, 16, 24, .14);
			}
			.bm-cart2-hero::after {
				content: "";
				position: absolute;
				inset: auto -60px -100px auto;
				width: 260px;
				height: 260px;
				border-radius: 50%;
				background: radial-gradient(circle, rgba(255,255,255,.16) 0%, rgba(255,255,255,0) 68%);
				pointer-events: none;
			}
			.bm-cart2-hero-copy {
				max-width: 420px;
				min-width: 0;
			}
			.bm-cart2-hero-copy,
			.bm-cart2-hero-meta {
				position: relative;
				z-index: 1;
			}
			.bm-cart2-hero-empty {
				display: flex;
				align-items: center;
				justify-content: center;
				text-align: center;
			}
			.bm-cart2-hero-empty h1 {
				margin: 6px 0 6px;
				color: #fff;
				font-size: clamp(34px, 3.6vw, 48px);
				line-height: 1;
				font-weight: 900;
				letter-spacing: -.04em;
				text-align: center;
				text-shadow: 0 10px 28px rgba(0, 0, 0, .18);
			}
			.bm-cart2-hero-empty p {
				margin: 0;
				max-width: 36ch;
				margin-inline: auto;
				color: rgba(255,255,255,.9);
				font-size: 14px;
				line-height: 1.5;
				text-align: center;
			}
			.bm-cart2-hero-copy span {
				display: inline-flex;
				align-items: center;
				min-height: 24px;
				padding: 0 10px;
				border-radius: 999px;
				background: rgba(255,255,255,.12);
				font-size: 9px;
				font-weight: 800;
				letter-spacing: .08em;
				text-transform: uppercase;
			}
			.bm-cart2-hero-copy h1 {
				margin: 6px 0 4px;
				font-size: clamp(30px, 3.5vw, 48px);
				line-height: 1;
				font-weight: 900;
				letter-spacing: -.03em;
				color: #fff;
				text-shadow: 0 10px 30px rgba(0, 0, 0, .18);
			}
			.bm-cart2-hero-copy p {
				margin: 0;
				max-width: 38ch;
				color: rgba(255,255,255,.84);
				font-size: 14px;
				line-height: 1.5;
			}
			.bm-cart2-hero-meta {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: 12px;
				min-width: min(430px, 100%);
				justify-self: end;
			}
			.bm-cart2-hero-meta div {
				padding: 16px 18px;
				border: 1px solid rgba(255,255,255,.12);
				border-radius: 18px;
				background: rgba(255,255,255,.08);
				backdrop-filter: blur(8px);
			}
			.bm-cart2-hero-meta strong {
				display: block;
				font-size: 20px;
				line-height: 1.2;
				font-weight: 900;
			}
			.bm-cart2-hero-meta small {
				display: block;
				margin-top: 6px;
				color: rgba(255,255,255,.76);
				font-size: 12px;
				font-weight: 700;
			}
			.bm-cart2-grid {
				display: grid;
				grid-template-columns: minmax(0, 1fr) clamp(320px, 30vw, 400px);
				gap: 24px;
				align-items: start;
				margin-top: 20px;
			}
			.bm-cart2-form {
				display: grid;
				gap: 16px;
				min-width: 0;
			}
			.bm-cart2-list {
				display: grid;
				gap: 14px;
			}
			.bm-cart2-item {
				position: relative;
				display: grid;
				grid-template-columns: 42px 96px minmax(0, 1fr);
				gap: 16px;
				padding: 18px 18px 18px 16px;
				border: 1px solid #eadfe7;
				border-radius: 24px;
				background: #fff;
				box-shadow: 0 16px 42px rgba(28, 16, 24, .06);
			}
			.bm-cart2-remove {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 34px;
				height: 34px;
				margin-top: 4px;
				border: 1px solid #eadfe7;
				border-radius: 999px;
				background: #fff8fb;
				color: #cf2d67;
			}
			.bm-cart2-remove svg {
				display: block;
				width: 17px;
				height: 17px;
				stroke: currentColor;
				fill: none;
				stroke-width: 1.8;
				stroke-linecap: round;
				stroke-linejoin: round;
			}
			.bm-cart2-media a,
			.bm-cart2-media img {
				display: block;
				width: 96px;
				height: 96px;
				border-radius: 18px;
				object-fit: cover;
			}
			.bm-cart2-body {
				display: grid;
				gap: 10px;
				min-width: 0;
			}
			.bm-cart2-title-row {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				gap: 16px;
				min-width: 0;
			}
			.bm-cart2-title-stack { min-width: 0; }
			.bm-cart2-title-stack h2 {
				margin: 0;
				font-size: 18px;
				line-height: 1.35;
				font-weight: 900;
				overflow-wrap: anywhere;
			}
			.bm-cart2-vendor {
				margin: 6px 0 0;
				color: #6d6573;
				font-size: 13px;
				line-height: 1.45;
			}
			.bm-cart2-price-tag {
				flex: 0 0 auto;
				color: #17151c;
				font-size: 18px;
				font-weight: 900;
				white-space: nowrap;
			}
			.bm-cart2-meta {
				display: flex;
				flex-wrap: wrap;
				gap: 8px 14px;
				color: #7b7280;
				font-size: 12px;
				font-weight: 700;
			}
			.bm-cart2-footer {
				display: flex;
				align-items: flex-end;
				justify-content: space-between;
				gap: 16px;
				flex-wrap: wrap;
			}
			.bm-cart2-qty {
				display: inline-flex;
				align-items: center;
				border: 1px solid #e2d6de;
				border-radius: 999px;
				background: #fff;
				box-shadow: 0 10px 22px rgba(23, 21, 28, .06);
				overflow: hidden;
			}
			.bm-cart2-qty-btn {
				width: 40px;
				height: 40px;
				border: 0;
				background: #fbf7fa;
				color: #17151c;
				font-size: 20px;
				font-weight: 700;
				cursor: pointer;
			}
			.bm-cart2-qty input {
				width: 58px;
				height: 40px;
				border: 0;
				background: #fff;
				color: #17151c;
				font-size: 15px;
				font-weight: 800;
				text-align: center;
			}
			.bm-cart2-qty input::-webkit-outer-spin-button,
			.bm-cart2-qty input::-webkit-inner-spin-button {
				appearance: none;
				margin: 0;
			}
			.bm-cart2-line-total {
				display: grid;
				gap: 3px;
				justify-items: end;
				min-width: 124px;
				text-align: right;
			}
			.bm-cart2-line-total span {
				color: #7b7280;
				font-size: 12px;
				font-weight: 700;
			}
			.bm-cart2-line-total strong {
				color: #d1401a;
				font-size: 17px;
				font-weight: 900;
			}
			.bm-cart2-actions {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 14px;
				padding-top: 4px;
			}
			.bm-cart2-ghost,
			.bm-cart2-update,
			.bm-cart2-checkout {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-height: 52px;
				padding: 0 22px;
				border-radius: 999px;
				font-size: 15px;
				font-weight: 900;
				text-decoration: none;
			}
			.bm-cart2-ghost {
				border: 1px solid #eadfe7;
				background: #fff;
				color: #17151c;
			}
			.bm-cart2-update {
				border: 0;
				background: #17151c;
				color: #fff;
				box-shadow: 0 14px 30px rgba(23, 21, 28, .14);
				cursor: pointer;
			}
			.bm-cart2-summary {
				position: sticky;
				top: 18px;
				border: 1px solid #eadfe7;
				border-radius: 28px;
				background: #fff;
				box-shadow: 0 20px 56px rgba(28, 16, 24, .08);
				overflow: hidden;
			}
			.bm-cart2-summary-head {
				display: flex;
				align-items: flex-end;
				justify-content: space-between;
				gap: 10px;
				padding: 22px 24px;
				background: linear-gradient(135deg, #201721 0%, #4f2038 58%, #d12d6a 100%);
				color: #fff;
			}
			.bm-cart2-summary-head span {
				font-size: 22px;
				line-height: 1.1;
				font-weight: 900;
			}
			.bm-cart2-summary-head strong {
				font-size: 11px;
				font-weight: 800;
				letter-spacing: .08em;
				text-transform: uppercase;
				opacity: .86;
			}
			.bm-cart2-summary-lines {
				display: grid;
				padding: 20px 24px 10px;
			}
			.bm-cart2-line {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				gap: 16px;
				padding: 14px 0;
				border-bottom: 1px solid #eee1e9;
			}
			.bm-cart2-line:last-child {
				border-bottom: 0;
			}
			.bm-cart2-line span {
				color: #2c2430;
				font-size: 14px;
				font-weight: 700;
			}
			.bm-cart2-line strong {
				color: #17151c;
				font-size: 15px;
				font-weight: 900;
				text-align: right;
			}
			.bm-cart2-line-total span,
			.bm-cart2-line-total strong {
				font-size: 18px;
			}
			.bm-cart2-line-total strong {
				color: #d1401a;
			}
			.bm-cart2-note {
				margin: 0;
				padding: 0 24px 18px;
				color: #7b7280;
				font-size: 12px;
				line-height: 1.6;
			}
			.bm-cart2-checkout {
				margin: 0 24px 24px;
				background: linear-gradient(135deg, #ffd23f 0%, #ff8c1a 100%);
				color: #17151c;
				box-shadow: 0 18px 34px rgba(255, 179, 0, .25);
			}
			.bm-cart2-muted {
				color: #7b7280;
			}
			@media (max-width: 1100px) {
				.bm-cart2-grid {
					grid-template-columns: 1fr;
				}
				.bm-cart2-summary {
					position: static;
				}
			}
			@media (max-width: 767px) {
				.bm-cart2 {
					width: min(100% - 16px, 100%);
					padding-top: 16px;
				}
				.bm-cart2-hero {
					padding: 16px 18px;
					border-radius: 20px;
					grid-template-columns: 1fr;
					justify-items: start;
				}
				.bm-cart2-hero-meta {
					min-width: 0;
					grid-template-columns: repeat(3, minmax(0, 1fr));
					justify-self: stretch;
				}
				.bm-cart2-item {
					grid-template-columns: 30px 64px minmax(0, 1fr);
					gap: 12px;
					padding: 14px;
				}
				.bm-cart2-media a,
				.bm-cart2-media img {
					width: 64px;
					height: 64px;
					border-radius: 14px;
				}
				.bm-cart2-title-row {
					flex-direction: column;
					align-items: flex-start;
				}
				.bm-cart2-price-tag {
					font-size: 16px;
				}
				.bm-cart2-line-total {
					min-width: 0;
					justify-items: flex-start;
					text-align: left;
				}
				.bm-cart2-actions {
					flex-direction: column-reverse;
					align-items: stretch;
				}
				.bm-cart2-ghost,
				.bm-cart2-update,
				.bm-cart2-checkout {
					width: 100%;
				}
				.bm-cart2-summary-head {
					padding: 18px 18px 16px;
				}
				.bm-cart2-summary-head span {
					font-size: 18px;
				}
				.bm-cart2-summary-lines {
					padding: 16px 18px 10px;
				}
				.bm-cart2-note {
					padding: 0 18px 16px;
				}
				.bm-cart2-checkout {
					margin: 0 18px 18px;
				}
			}
			.bm-cart2-empty {
				padding: 22px 20px 26px;
			}
			.bm-cart2-empty h2 {
				margin: 0 0 8px;
				font-size: 26px;
				line-height: 1.15;
			}
			.bm-cart2-empty p {
				max-width: 44ch;
				margin: 0 auto 16px;
				font-size: 14px;
				line-height: 1.55;
			}
			@media (max-width: 420px) {
				.bm-cart2-hero-meta {
					grid-template-columns: 1fr;
				}
				.bm-cart2-qty input {
					width: 44px;
				}
			}
			'
		);

		wp_register_script( 'beautyin-page-suite-cart-two', false, array(), '1.0.0', true );
		wp_enqueue_script( 'beautyin-page-suite-cart-two' );
		wp_add_inline_script(
			'beautyin-page-suite-cart-two',
			'
			document.addEventListener("click", function (event) {
				var button = event.target.closest(".bm-cart2-qty-btn");
				if (!button) {
					return;
				}

				var wrap = button.closest("[data-bm-cart-qty]");
				var input = wrap ? wrap.querySelector(`input[type="number"]`) : null;
				if (!input) {
					return;
				}

				event.preventDefault();
				var step = parseInt(button.getAttribute("data-bm-step"), 10) || 0;
				var min = parseInt(input.getAttribute("min"), 10);
				var max = parseInt(input.getAttribute("max"), 10);
				var value = parseInt(input.value, 10);
				if (isNaN(value)) {
					value = isNaN(min) ? 0 : min;
				}
				if (isNaN(min)) {
					min = 0;
				}
				value = value + step;
				if (value < min) {
					value = min;
				}
				if (!isNaN(max) && value > max) {
					value = max;
				}
				input.value = String(value);
			});
			'
		);
	}
}
