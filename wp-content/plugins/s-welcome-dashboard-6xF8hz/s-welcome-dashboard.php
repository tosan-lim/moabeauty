<?php
/*
Plugin Name: S Welcome Dashboard
Plugin URI: https://wordpress.org
Description: A custom welcome dashboard for website setup.
Version: 1.0
Author: Website Devs
Author URI: https://wordpress.org
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// delete_option('s_dashboard_stored_data');

$sdata = get_option('s_dashboard_stored_data', []);
if( $sdata === false ) { $sdata = []; }


// Register the welcome page
add_action('admin_menu', 's_welcome_dashboard_menu', 1);

function s_welcome_dashboard_menu() {
    // Add a top-level menu page
    add_menu_page(
        'Welcome',
        'Getting Started',
        'manage_options',
        's-welcome-dashboard',
        's_welcome_dashboard_display',
        'dashicons-sos',
        2 //position
    );
}

// Display the content of the welcome dashboard
function s_welcome_dashboard_display() {
	global $sdata;
	
	$wooCommerceActivated = class_exists('WooCommerce');
    ?>
    <div class="wrap s-wrap">
		<div class="s-card mt-0">
			<div class="row active">
					<div class="col-12">
						<h1 class="s-title"><?php esc_html_e('Welcome to your new WordPress site', 's-welcome-dashboard'); ?></h1>
					</div>
				<div class="col-12">
					<p>We’re excited to help you get started! Follow these steps to set up and customize your website easily.</p>
					<br>
					<br>
				</div>
				<div class="col-md-8">
					<div class="s-progress-wrap mb-2">
						<div class="s-progress <?php if( array_key_exists( 'progress_complete_template', $sdata ) ) { ?>complete<?php } ?>">
							<div class="s-icon">
								<span class="dashicons dashicons-yes"></span>
							</div>
							<div class="s-bar">
							</div>
						</div>
						<div class="s-progress-content" style="padding-top:0.1rem;">
							<h2 class="s-title">Step 1: Choose Your Template</h2>
							<p>Select from a variety of professionally designed templates to give your website the perfect look and feel. Whether you’re creating a blog, business site, or online store, we’ve got a template for you.</p>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/Uu7fM5byrBg" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Choose-template.webp', __FILE__)); ?>" alt="Setting Up WP, Starter Templates & Astra | | WordPress & Spectra Tutorial"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/Uu7fM5byrBg" target="_blank" class="s-yt-link">Setting Up WP, Starter Templates & Astra</a></h3>
									<p>Watch this tutorial to learn how to set up WordPress, install starter templates, and use the theme to get your site up and running quickly.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<a href="<?=admin_url('themes.php?page=starter-templates&ci=1' . ($wooCommerceActivated ? '&s=Online+Shop' : ''));?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_template', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Choose template</a>
							<?php if( array_key_exists( 'progress_complete_template', $sdata ) ) { ?>
								<div class="complete small-icon d-inline-block mt-1">
									<div class="s-icon">
										<span class="dashicons dashicons-yes"></span>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="col-md-4 mb-3">
					<img src="<?php echo esc_url(plugins_url('assets/img/templates-showcase.webp', __FILE__)); ?>" alt="Welcome" class="s-img-welcome">
				</div>
				<div class="col-md-8">
					<div class="s-progress-wrap mb-2">
						<div class="s-progress <?php if( array_key_exists( 'progress_complete_customize', $sdata ) ) { ?>complete<?php } ?>">
							<div class="s-icon">
								<span class="dashicons dashicons-yes"></span>
							</div>
							<div class="s-bar">
							</div>
						</div>
						<div class="s-progress-content" style="padding-top:0.1rem; padding-bottom: 30px;">
							<h2 class="s-title">Step 2: Customize Your Website</h2>
							<p>Make your website truly your own by customizing the header, footer, and other key elements. No coding required!</p>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/uTgaDTAprI0" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Header-Footer.webp', __FILE__)); ?>" alt="Customize Header & Footer | Build a Website With WordPress Spectra"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/uTgaDTAprI0" target="_blank" class="s-yt-link">Customize Header & Footer</a></h3>
									<p>This video will guide you through adjusting the header, footer, and other important parts of your site using Spectra’s tools.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/Uu7fM5byrBg?t=170" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Customize-template.webp', __FILE__)); ?>" alt="Working with the Astra Theme Editor"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/Uu7fM5byrBg?t=170" target="_blank" class="s-yt-link">Working with the Astra Theme Editor</a></h3>
									<p>Learn how to easily make edits to your site’s design using the Astra Theme Editor. This will allow you to tweak colors, fonts, and layouts to fit your brand.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<a href="<?=admin_url('customize.php');?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_customize', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Customize your website</a>
							<?php if( array_key_exists( 'progress_complete_customize', $sdata ) ) { ?>
								<div class="complete small-icon d-inline-block mt-1">
									<div class="s-icon">
										<span class="dashicons dashicons-yes"></span>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="col-md-4 mb-3">
					<img src="<?php echo esc_url(plugins_url('assets/img/Customize.webp', __FILE__)); ?>" alt="Welcome" class="s-img-welcome">
				</div>
				<div class="col-md-8">
					<div class="s-progress-wrap mb-3">
						<div class="s-progress <?php if( array_key_exists( 'progress_complete_posts', $sdata ) || array_key_exists( 'progress_complete_pages', $sdata ) ) { ?>complete<?php } ?>">
							<div class="s-icon">
								<span class="dashicons dashicons-yes"></span>
							</div>
							<?php if( $wooCommerceActivated ) { ?>
								<div class="s-bar">
								</div>
							<?php } ?>
						</div>
						<div class="s-progress-content" style="padding-top:0.1rem; padding-bottom: 30px;">
							<h2 class="s-title">Pages & posts</h2>
							<p><strong>Organize your website content by creating and managing pages and blog posts. Follow the steps below to learn how to add, edit, and modify posts and pages.</strong></p>
							<br>
							<strong>Posts</strong>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/-al_VN2UC8U" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Post.webp', __FILE__)); ?>" alt="3 WAYS To Add A BLOG To Your WordPress Website | Spectra Course"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/-al_VN2UC8U" target="_blank" class="s-yt-link">3 Ways to Add a Blog to Your WordPress Website</a></h3>
									<p>Learn three different methods to add a blog to your site, and discover how to effectively write, organize, and edit posts.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<a href="<?=admin_url('edit.php');?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_posts', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Modify Posts</a>
							<?php if( array_key_exists( 'progress_complete_posts', $sdata ) ) { ?>
								<div class="complete small-icon d-inline-block mt-1">
									<div class="s-icon">
										<span class="dashicons dashicons-yes"></span>
									</div>
								</div>
							<?php } ?>
							<br>
							<br>
							<strong>Pages</strong>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/ywLPlWqhM0Y" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Page-New-Page.webp', __FILE__)); ?>" alt="Building A New Page From SCRATCH | WordPress & Spectra Tutorial"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/ywLPlWqhM0Y" target="_blank" class="s-yt-link">Building a New Page From Scratch</a></h3>
									<p>Watch this tutorial to learn how to create a new page from scratch using WordPress and Spectra's powerful design tools.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/3TYf_v-T7n8" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Page-Home-Page.webp', __FILE__)); ?>" alt="Building The Homepage | WordPress & Spectra Tutorial"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/3TYf_v-T7n8" target="_blank" class="s-yt-link">Building the Homepage</a></h3>
									<p>Your homepage is the first impression visitors get. Learn how to craft a professional, visually appealing homepage.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/m2uVrKWPnbA" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Page-Service-Page.webp', __FILE__)); ?>" alt="Building The Services Page | WordPress & Spectra Tutorial"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/m2uVrKWPnbA" target="_blank" class="s-yt-link">Building the Services Page</a></h3>
									<p>Follow this guide to set up your services page and highlight the value you offer to your customers.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<div class="s-yt-video-with-desc">
								<div class="s-youtube-thumb">
									<a href="https://youtu.be/0PKYbB2XbFE" target="_blank">
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
										<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Page-Contact-Page.webp', __FILE__)); ?>" alt="Building The Contact Page | WordPress & Spectra Tutorial"/>
									</a>
								</div>
								<div class="s-iframe-player-info">
									<h3><a href="https://youtu.be/0PKYbB2XbFE" target="_blank" class="s-yt-link">Building The Contact Page</a></h3>
									<p>Your website’s contact page is essential for users to reach out to you. Learn how to create and customize a professional contact page for your website.</p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<a href="<?=admin_url('edit.php?post_type=page');?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_pages', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Modify Pages</a>
							<?php if( array_key_exists( 'progress_complete_pages', $sdata ) ) { ?>
								<div class="complete small-icon d-inline-block mt-1">
									<div class="s-icon">
										<span class="dashicons dashicons-yes"></span>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="col-md-4 mb-3">
					<img src="<?php echo esc_url(plugins_url('assets/img/PostandPages.webp', __FILE__)); ?>" alt="Welcome" class="s-img-welcome">
				</div>
				<?php if( $wooCommerceActivated ) { ?>
					<div class="col-md-8">
						<div class="s-progress-wrap mb-3">
							<div class="s-progress <?php if( array_key_exists( 'progress_complete_woocommerce', $sdata ) && array_key_exists( 'progress_complete_products', $sdata ) ) { ?>complete<?php } ?>">
								<div class="s-icon">
									<span class="dashicons dashicons-yes"></span>
								</div>
							</div>
							<div class="s-progress-content" style="padding-top:0.1rem; padding-bottom: 30px;">
								<h2 class="s-title">Shop and Product Settings</h2>
								<p><strong>Set up your online store, manage products, and get started with selling effortlessly. This section will guide you through setting up your WooCommerce store and products.</strong></p>
								<br>
								<strong>Set Up Your Store</strong>
								<div class="s-yt-video-with-desc">
									<div class="s-youtube-thumb">
										<a href="https://youtu.be/UL1k8jG_f28?t=257" target="_blank">
											<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
											<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Set-up-Store.webp', __FILE__)); ?>" alt="How To Use WooCommerce | WordPress eCommerce Tutorial for Beginners"/>
										</a>
									</div>
									<div class="s-iframe-player-info">
										<h3><a href="https://youtu.be/UL1k8jG_f28?t=257" target="_blank" class="s-yt-link">How To Use WooCommerce</a></h3>
										<p>Learn how to install and configure WooCommerce, the leading eCommerce platform for WordPress, and get your online shop ready to launch.</p>
									</div>
									<div style="clear:both;"></div>
								</div>
								<a href="<?=admin_url('admin.php?page=wc-admin&path=%2Fsetup-wizard');?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_woocommerce', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Set Up Store</a>
								<?php if( array_key_exists( 'progress_complete_woocommerce', $sdata ) ) { ?>
									<div class="complete small-icon d-inline-block mt-1">
										<div class="s-icon">
											<span class="dashicons dashicons-yes"></span>
										</div>
									</div>
								<?php } ?>
								<br>
								<br>
								<strong>Products</strong>
								<div class="s-yt-video-with-desc">
									<div class="s-youtube-thumb">
										<a href="https://youtu.be/UL1k8jG_f28?t=1127" target="_blank">
											<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/SP-WP-Plugin-YT-icon.webp', __FILE__)); ?>" class="s-yt-play-icon"/>
											<img src="<?php echo esc_url(plugins_url('assets/img/yt-thumbs/VTMB-Set up-Products.webp', __FILE__)); ?>" alt="How To Use WooCommerce | WordPress eCommerce Tutorial for Beginners"/>
										</a>
									</div>
									<div class="s-iframe-player-info">
										<h3><a href="https://youtu.be/UL1k8jG_f28?t=1127" target="_blank" class="s-yt-link">How To Use WooCommerce</a></h3>
										<p>This tutorial shows you how to add new products, set up categories, and manage product attributes within WooCommerce for a smooth shopping experience.</p>
									</div>
									<div style="clear:both;"></div>
								</div>
								<a href="<?=admin_url('edit.php?post_type=product');?>" class="button button-primary s-button s-button-primary <?php if( array_key_exists( 'progress_complete_products', $sdata ) ) { ?>s-button-inactive<?php } ?>" target="_blank">Modify Products</a>
								<?php if( array_key_exists( 'progress_complete_products', $sdata ) ) { ?>
									<div class="complete small-icon d-inline-block mt-1">
										<div class="s-icon">
											<span class="dashicons dashicons-yes"></span>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<div class="col-md-4 mb-3">
						<img src="<?php echo esc_url(plugins_url('assets/img/shopandproducts.webp', __FILE__)); ?>" alt="Welcome" class="s-img-welcome">
					</div>
				<?php
				} ?>
			</div>
		</div>
	</div>
    <?php
}

// Redirect to plugin page after activation
register_activation_hook(__FILE__, 's_welcome_dashboard_activate');

function s_welcome_dashboard_activate() {
	delete_option('s_dashboard_stored_data');
    add_option('s_welcome_dashboard_redirect', true);
}

add_action('admin_init', 's_welcome_dashboard_redirect_after_activation', 2);

function s_welcome_dashboard_redirect_after_activation() {
	remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
    if (get_option('s_welcome_dashboard_redirect')) {
        delete_option('s_welcome_dashboard_redirect');
        if ( ! is_multisite() ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page' => 's-welcome-dashboard',
					),
					admin_url( 'admin.php' )
				)
			);
			exit();
		}
    }
}

// Ensure this page appears first (higher priority)
add_action('admin_init', 's_override_woocommerce_welcome', 1);
add_action('admin_enqueue_scripts', 's_welcome_dashboard_enqueue_styles');
add_action('admin_enqueue_scripts', 's_dashboard_enqueue_styles');

function s_override_woocommerce_welcome() {
	remove_action('admin_init', array('UAGB_Admin', 'activation_redirect')); //remove Spectra redirect
    remove_action('admin_notices', 'woocommerce_admin_notices'); // Remove WooCommerce notice
    remove_action('admin_notices', 'astra_sites_admin_notice'); // Remove Astra Sites notice
    remove_action('admin_notices', 'uag_welcome_notice'); // Remove Ultimate Addons for Gutenberg notice
	update_option( '__uagb_do_redirect', false );
	delete_option( 'st_start_onboarding' );
}

function s_welcome_dashboard_enqueue_styles() {
    // Зареждаме Google Fonts: Open Sans
    wp_enqueue_style('s-dashboard-open-sans', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', false);
}

function s_dashboard_enqueue_styles() {
    // Use wp_enqueue_style() to add your CSS file
    wp_enqueue_style(
        's-dashboard-style', // Handle name for your CSS file
        plugin_dir_url(__FILE__) . 'assets/css/style.css', // Path to your CSS file
        [], // Dependencies, leave as empty array if no dependencies
        '1.0.0', // Version of your CSS file
        'all' // Media type (default is 'all')
    );
}


function s_dashboard_wpbody_prepend() {
	global $sdata;
	if( array_key_exists( 'dashboard_notice_dont_show', $sdata ) ) {
		return;
	}
	$doNotShowHere = [ 
						'toplevel_page_s-welcome-dashboard',
						'appearance_page_starter-templates',
						'woocommerce_page_wc-admin',
						'edit-product',
					];
	$currentScreen = get_current_screen()->id;
	if( in_array( $currentScreen, $doNotShowHere) ) {
		return;
	}
	$wooCommerceActivated = class_exists('WooCommerce');
	$currentStep = 0;
	$totalSteps = $wooCommerceActivated ? 4 : 3;
	$completedSteps = [];
	if( array_key_exists( 'progress_complete_template', $sdata ) ) {
		$currentStep = 1;
		$completedSteps[1] = true;
	}
	if( array_key_exists( 'progress_complete_customize', $sdata ) ) {
		$currentStep = 2;
		$completedSteps[2] = true;
	}
	if( array_key_exists( 'progress_complete_posts', $sdata ) || array_key_exists( 'progress_complete_pages', $sdata ) ) {
		$currentStep = 3;
		$completedSteps[3] = true;
	}
	if( array_key_exists( 'progress_complete_woocommerce', $sdata ) && array_key_exists( 'progress_complete_products', $sdata ) ) {
		$currentStep = 4;
		$completedSteps[4] = true;
	}
	if( count($completedSteps) == $totalSteps ) {
		return;
	}
    ?>
    <script type="text/javascript">
	console.log('Current screen: <?=$currentScreen;?>');
	var currentStep = <?=$currentStep;?>;
	var totalSteps = <?=$totalSteps;?>;
	var completedSteps = <?=json_encode($completedSteps);?>;
    jQuery(document).ready(function($) {
        var customContent = '<div class="wrap s-wrap s-dashboard-notice">'+
								'<a href="#" id="s-dont-show-notice"><span class="dashicons dashicons-no-alt"></span>Don\'t show again</a>'+
								'<div class="s-card">'+
									'<div class="row">'+
										'<div class="col-md-6">'+
											'<h3 class="d-inline-block m-0">Finish setting up your site</h3>'+
											'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>'+
											'<a href="<?=admin_url('admin.php?page=s-welcome-dashboard');?>">Learn more</a>'+
											'<br><br>'+
										'</div>'+
										'<div class="col-md-6">'+
											'<div class="step" step="1">'+
												'<div class="<?php if( array_key_exists( 'progress_complete_template', $sdata ) ) { ?>complete<?php } ?> small-icon d-inline-block mt-1">'+
													'<div class="s-icon">'+
														'<span class="dashicons dashicons-yes"></span>'+
													'</div>'+
												'</div>'+
												'<h3 class="summary-title d-inline-block m-0">Choose Your Template</h3>'+
											'</div>'+
											'<div class="step" step="2">'+
												'<div class="<?php if( array_key_exists( 'progress_complete_customize', $sdata ) ) { ?>complete<?php } ?> small-icon d-inline-block mt-1">'+
													'<div class="s-icon">'+
														'<span class="dashicons dashicons-yes"></span>'+
													'</div>'+
												'</div>'+
												'<h3 class="summary-title d-inline-block m-0">Customize Your Website</h3>'+
											'</div>'+
											'<div class="step" step="3">'+
												'<div class="<?php if( array_key_exists( 'progress_complete_posts', $sdata ) || array_key_exists( 'progress_complete_pages', $sdata ) ) { ?>complete<?php } ?> small-icon d-inline-block mt-1">'+
													'<div class="s-icon">'+
														'<span class="dashicons dashicons-yes"></span>'+
													'</div>'+
												'</div>'+
												'<h3 class="summary-title d-inline-block m-0">Pages & posts</h3>'+
											'</div>'+
											<?php if( $wooCommerceActivated ) { ?>
											'<div class="step" step="4">'+
												'<div class="<?php if( array_key_exists( 'progress_complete_woocommerce', $sdata ) || array_key_exists( 'progress_complete_products', $sdata ) ) { ?>complete<?php } ?> small-icon d-inline-block mt-1">'+
													'<div class="s-icon">'+
														'<span class="dashicons dashicons-yes"></span>'+
													'</div>'+
												'</div>'+
												'<h3 class="summary-title d-inline-block m-0">WooCommerce Setup & Products</h3>'+
											'</div>'+
											<?php } ?>
										'</div>'+
									'</div>'+
								'</div>'+
							'</div>';
		
        $('#wpbody').prepend(customContent);
		
		$(document).on('click', '#s-dont-show-notice', function(e) {
			e.preventDefault();
			var data = {
				'action': 'hide_dashboard_notice',
				'nonce': '<?php echo wp_create_nonce("hide_dashboard_notice_nonce"); ?>'
			};
			$(".s-dashboard-notice").remove();
			$.post(ajaxurl, data, function(response) {});
		});
    });
    </script>
    <?php
}

add_action('admin_head', 's_dashboard_wpbody_prepend');

// Track Spectra Template Selection
add_action('astra_sites_import_complete', 'handle_astra_sites_import_complete');
function handle_astra_sites_import_complete() {
    $sdata = get_option('s_dashboard_stored_data', []);
    $sdata['progress_complete_template'] = true;
    update_option('s_dashboard_stored_data', $sdata);
}


// Track Customizer Changes
add_action('customize_save_after', 'track_customizer_changes');
function track_customizer_changes() {
    if (current_user_can('edit_theme_options')) {
        $sdata = get_option('s_dashboard_stored_data', []);
        $sdata['progress_complete_customize'] = true;
        update_option('s_dashboard_stored_data', $sdata);
    }
}

// Track Post Changes (Posts)
add_action('save_post_post', 'track_post_changes');
function track_post_changes($post_id) {
    if (!wp_is_post_revision($post_id) && current_user_can('administrator')) {
        $sdata = get_option('s_dashboard_stored_data', []);
        $sdata['progress_complete_posts'] = true;
        update_option('s_dashboard_stored_data', $sdata);
    }
}

// Track Page Changes (Pages)
add_action('save_post_page', 'track_page_changes');
function track_page_changes($post_id) {
    if (!wp_is_post_revision($post_id) && current_user_can('administrator')) {
        $sdata = get_option('s_dashboard_stored_data', []);
        $sdata['progress_complete_pages'] = true;
        update_option('s_dashboard_stored_data', $sdata);
    }
}

// Track Product Changes (WooCommerce Products)
add_action('save_post_product', 'track_product_changes');
function track_product_changes($post_id) {
    if (!wp_is_post_revision($post_id) && current_user_can('administrator')) {
        $sdata = get_option('s_dashboard_stored_data', []);
        $sdata['progress_complete_products'] = true;
        update_option('s_dashboard_stored_data', $sdata);
    }
}

add_action('wp_ajax_hide_dashboard_notice', 'hide_dashboard_notice_callback');

function hide_dashboard_notice_callback() {
    // Verify the nonce
    check_ajax_referer('hide_dashboard_notice_nonce', 'nonce');

    // Update the option in the database
	$sdata = get_option('s_dashboard_stored_data', []);
	$sdata['dashboard_notice_dont_show'] = true;
	update_option('s_dashboard_stored_data', $sdata);

    // Return a successful response
    wp_send_json_success();
}
add_action('admin_footer', 's_dashboard_refresh_on_refocus');
function s_dashboard_refresh_on_refocus() {
	if( get_current_screen()->id !== 'toplevel_page_s-welcome-dashboard' ) {
		return;
	}
    ?>
    <script type="text/javascript">
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // location.reload();
            }
        });
    </script>
    <?php
}

add_action('admin_footer', 'woocommerce_setup_complete_tracker');
function woocommerce_setup_complete_tracker() {
    ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                if (window.location.href.indexOf('wc-admin&path=%2Flaunch-your-store') > -1) {
                    // Send an AJAX request to track progress completion
                    $.post(ajaxurl, {
                        'action': 'track_woocommerce_setup_completion',
                        'nonce': '<?php echo wp_create_nonce("woocommerce_setup_complete_nonce"); ?>'
                    }, function(response) {
                        if(response.success) {
                            console.log('WooCommerce setup completion tracked successfully.');
                        }
                    });
                }
            });
        })(jQuery);
    </script>
    <?php
}

add_action('wp_ajax_track_woocommerce_setup_completion', 'track_woocommerce_setup_completion');
function track_woocommerce_setup_completion() {
	// Verify the nonce
    check_ajax_referer('woocommerce_setup_complete_nonce', 'nonce');
	
    $sdata = get_option('s_dashboard_stored_data', []);
    $sdata['progress_complete_woocommerce'] = true;
    update_option('s_dashboard_stored_data', $sdata);

    wp_send_json_success('WooCommerce setup completion tracked.');
    wp_die();
}


