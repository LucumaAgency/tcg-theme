<?php
/**
 * TCG Theme — Functions
 */

// ─── CPT, Taxonomías y Meta Fields ───
require_once __DIR__ . '/includes/cpt-ygo-card.php';
require_once __DIR__ . '/includes/taxonomies.php';
require_once __DIR__ . '/includes/meta-ygo-card.php';

// ─── DEBUG: Mini Cart Diagnostics (temporary) ───
add_action( 'wp_footer', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // Solo admins ven el debug.
	}

	$diagnostics = [];

	// 1. WooCommerce activo?
	$diagnostics['woocommerce_active'] = class_exists( 'WooCommerce' );

	// 2. WC() disponible?
	$diagnostics['wc_instance'] = function_exists( 'WC' ) && WC() !== null;

	// 3. Cart inicializado?
	$diagnostics['cart_initialized'] = isset( WC()->cart );

	// 4. Items en el carrito
	$diagnostics['cart_contents_count'] = isset( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 'N/A';
	$diagnostics['cart_items'] = isset( WC()->cart ) ? count( WC()->cart->get_cart() ) : 'N/A';

	// 5. Shortcode registrado?
	global $shortcode_tags;
	$diagnostics['shortcode_woocommerce_mini_cart'] = isset( $shortcode_tags['woocommerce_mini_cart'] );
	$diagnostics['shortcode_woocommerce_cart'] = isset( $shortcode_tags['woocommerce_cart'] );

	// 6. Cart fragments script registrado?
	$diagnostics['wc_cart_fragments_registered'] = wp_script_is( 'wc-cart-fragments', 'registered' );
	$diagnostics['wc_cart_fragments_enqueued'] = wp_script_is( 'wc-cart-fragments', 'enqueued' );

	// 7. WC AJAX endpoint
	$diagnostics['wc_ajax_url'] = class_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( 'get_refreshed_fragments' ) : 'N/A';

	// 8. Cart page configurada?
	$diagnostics['cart_page_id'] = get_option( 'woocommerce_cart_page_id' );
	$diagnostics['cart_page_exists'] = get_post( get_option( 'woocommerce_cart_page_id' ) ) ? true : false;

	// 9. WC session
	$diagnostics['wc_session_class'] = isset( WC()->session ) ? get_class( WC()->session ) : 'N/A';

	// 10. Render test del mini cart
	ob_start();
	if ( function_exists( 'woocommerce_mini_cart' ) ) {
		woocommerce_mini_cart();
	}
	$mini_cart_html = ob_get_clean();
	$diagnostics['mini_cart_html_length'] = strlen( $mini_cart_html );
	$diagnostics['mini_cart_html_preview'] = substr( strip_tags( $mini_cart_html ), 0, 200 );

	// 11. WC template path
	$diagnostics['wc_template_path'] = function_exists( 'wc_get_template_part' ) ? WC()->plugin_path() . '/templates/' : 'N/A';

	// 12. Template overrides
	$diagnostics['theme_has_wc_folder'] = is_dir( get_stylesheet_directory() . '/woocommerce' );
	$diagnostics['mini_cart_template_override'] = file_exists( get_stylesheet_directory() . '/woocommerce/cart/mini-cart.php' );

	echo '<!-- TCG DEBUG: Mini Cart Diagnostics -->';
	echo '<div id="tcg-debug-minicart" style="position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:#0f0;font-family:monospace;font-size:12px;padding:15px;z-index:999999;max-height:40vh;overflow-y:auto;border-top:2px solid #0f0;">';
	echo '<strong style="color:#ff0;font-size:14px;">🔍 TCG DEBUG — Mini Cart</strong>';
	echo ' <button onclick="this.parentElement.remove()" style="float:right;background:#333;color:#fff;border:1px solid #666;padding:2px 8px;cursor:pointer;">X</button>';
	echo '<pre style="margin:8px 0 0;white-space:pre-wrap;">';
	foreach ( $diagnostics as $key => $val ) {
		$display = is_bool( $val ) ? ( $val ? '✅ true' : '❌ false' ) : $val;
		echo esc_html( str_pad( $key, 40 ) . ' → ' . $display ) . "\n";
	}
	echo '</pre>';
	echo '</div>';
}, 9999 );

/**
 * Register/enqueue custom scripts and styles
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime( get_stylesheet_directory() . '/style.css' ) );

	// Force WooCommerce cart fragments on all pages (fixes empty mini cart on non-WC pages).
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
} );

/**
 * Register custom elements
 */
add_action( 'init', function() {
  $element_files = [
    __DIR__ . '/elements/title.php',
  ];

  foreach ( $element_files as $file ) {
    \Bricks\Elements::register_element( $file );
  }
}, 11 );

/**
 * Filter which elements to show in the builder
 * 
 * Simple outcomment (prefix: //) the elements you don't want to use in Bricks
 */
function bricks_filter_builder_elements( $elements ) {
	$elements = [
		// Basic
		// 'container', // since 1.2
		// 'heading',
		'text',
		'button',
		'icon',
		'image',
		'video',

		// General
		'divider',
		'icon-box',
		'list',
		'accordion',
		'tabs',
		'form',
		'map',
		'alert',
		'animated-typing',
		'countdown',
		'counter',
		'pricing-tables',
		'progress-bar',
		'pie-chart',
		'team-members',
		'testimonials',
		'html',
		'code',
		'logo',

		// Media
		'image-gallery',
		'audio',
		'carousel',
		'slider',
		'svg',

		// Social
		'social-icons',
		'facebook-page',
		'instagram-feed',

		// WordPress
		'wordpress',
		'posts',
		'nav-menu',
		'sidebar',
		'search',
		'shortcode',

		// Single
		'post-title',
		'post-excerpt',
		'post-meta',
		'post-content',
		'post-sharing',
		'post-related-posts',
		'post-author',
		'post-comments',
		'post-taxonomy',
		'post-navigation',

		// Hidden in builder panel
		'section',
		'row',
		'column',
	];

	return $elements;
}
// add_filter( 'bricks/builder/elements', 'bricks_filter_builder_elements' );

/**
 * Add text strings to builder
 */
add_filter( 'bricks/builder/i18n', function( $i18n ) {
  // For element category 'custom'
  $i18n['custom'] = esc_html__( 'Custom', 'bricks' );

  return $i18n;
} );

/**
 * Custom save messages
 */
add_filter( 'bricks/builder/save_messages', function( $messages ) {
	// First option: Add individual save message
	$messages[] = 'Yasss';

	// Second option: Replace all save messages
	$messages = [
		'Done',
		'Cool',
		'High five!',
	];

  return $messages;
} );

/**
 * Customize standard fonts
 */
// add_filter( 'bricks/builder/standard_fonts', function( $standard_fonts ) {
// 	// First option: Add individual standard font
// 	$standard_fonts[] = 'Verdana';

// 	// Second option: Replace all standard fonts
// 	$standard_fonts = [
// 		'Georgia',
// 		'Times New Roman',
// 		'Verdana',
// 	];

//   return $standard_fonts;
// } );

/** 
 * Add custom map style
 */
// add_filter( 'bricks/builder/map_styles', function( $map_styles ) {
//   // Shades of grey (https://snazzymaps.com/style/38/shades-of-grey)
//   $map_styles['shadesOfGrey'] = [
//     'label' => esc_html__( 'Shades of grey', 'bricks' ),
//     'style' => '[ { "featureType": "all", "elementType": "labels.text.fill", "stylers": [ { "saturation": 36 }, { "color": "#000000" }, { "lightness": 40 } ] }, { "featureType": "all", "elementType": "labels.text.stroke", "stylers": [ { "visibility": "on" }, { "color": "#000000" }, { "lightness": 16 } ] }, { "featureType": "all", "elementType": "labels.icon", "stylers": [ { "visibility": "off" } ] }, { "featureType": "administrative", "elementType": "geometry.fill", "stylers": [ { "color": "#000000" }, { "lightness": 20 } ] }, { "featureType": "administrative", "elementType": "geometry.stroke", "stylers": [ { "color": "#000000" }, { "lightness": 17 }, { "weight": 1.2 } ] }, { "featureType": "landscape", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 20 } ] }, { "featureType": "poi", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 21 } ] }, { "featureType": "road.highway", "elementType": "geometry.fill", "stylers": [ { "color": "#000000" }, { "lightness": 17 } ] }, { "featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [ { "color": "#000000" }, { "lightness": 29 }, { "weight": 0.2 } ] }, { "featureType": "road.arterial", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 18 } ] }, { "featureType": "road.local", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 16 } ] }, { "featureType": "transit", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 19 } ] }, { "featureType": "water", "elementType": "geometry", "stylers": [ { "color": "#000000" }, { "lightness": 17 } ] } ]'
//   ];

//   return $map_styles;
// } );