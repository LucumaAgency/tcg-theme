<?php
/**
 * TCG Theme — Functions
 */

// ─── CPT, Taxonomías y Meta Fields ───
require_once __DIR__ . '/includes/cpt-ygo-card.php';
require_once __DIR__ . '/includes/taxonomies.php';
require_once __DIR__ . '/includes/meta-ygo-card.php';

// ─── FIX: Prevent 404 on paginated shop archive (products are hidden, Bricks queries ygo_card) ───
add_action( 'wp', function() {
	global $wp_query;

	// Shop archive paged returns 404 because all products are catalog_visibility:hidden (0 results).
	// But Bricks query loop fetches ygo_card independently, so the page is valid.
	if ( $wp_query->is_404
		&& $wp_query->get( 'post_type' ) === 'product'
		&& $wp_query->get( 'paged' ) > 1
	) {
		$wp_query->is_404    = false;
		$wp_query->is_archive = true;
		status_header( 200 );
	}
} );

// ─── DEBUG: Pagination diagnostics (temporary) ───
add_action( 'wp_footer', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wp_query, $wp;

	$info = [];
	$info['current_url']        = home_url( $wp->request );
	$info['is_page']            = is_page() ? 'true' : 'false';
	$info['is_singular']        = is_singular() ? 'true' : 'false';
	$info['is_archive']         = is_archive() ? 'true' : 'false';
	$info['is_home']            = is_home() ? 'true' : 'false';
	$info['is_front_page']      = is_front_page() ? 'true' : 'false';
	$info['is_404']             = is_404() ? '❌ YES 404' : '✅ not 404';
	$info['is_paged']           = is_paged() ? 'true' : 'false';
	$info['query_var_paged']    = get_query_var( 'paged', 0 );
	$info['query_var_page']     = get_query_var( 'page', 0 );
	$info['queried_object_id']  = get_queried_object_id();
	$info['queried_object_type'] = get_queried_object() ? get_class( get_queried_object() ) : 'null';
	$info['post_type']          = $wp_query->get( 'post_type' ) ?: 'default';
	$info['posts_per_page_wp']  = get_option( 'posts_per_page' );
	$info['query_posts_per_page'] = $wp_query->get( 'posts_per_page' );
	$info['found_posts']        = $wp_query->found_posts;
	$info['max_num_pages']      = $wp_query->max_num_pages;
	$info['post_count']         = $wp_query->post_count;

	// WooCommerce shop page
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		$info['wc_shop_page_id']    = $shop_id;
		$info['wc_is_shop']         = function_exists( 'is_shop' ) && is_shop() ? 'true' : 'false';
		$info['current_page_id']    = get_the_ID();
		$info['is_wc_shop_page']    = ( (int) get_the_ID() === (int) $shop_id ) ? 'true' : 'false';
	}

	// Rewrite rules check
	$info['rewrite_request']    = $wp->request;
	$info['rewrite_matched_rule'] = $wp->matched_rule ?: 'none';
	$info['rewrite_matched_query'] = $wp->matched_query ?: 'none';

	// Permalink structure
	$info['permalink_structure'] = get_option( 'permalink_structure' );

	echo '<div id="tcg-debug-pagination" style="position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:#0f0;font-family:monospace;font-size:11px;padding:15px;z-index:999999;max-height:50vh;overflow-y:auto;border-top:2px solid #0f0;">';
	echo '<strong style="color:#ff0;font-size:14px;">🔍 TCG DEBUG — Pagination</strong>';
	echo ' <button onclick="this.parentElement.remove()" style="float:right;background:#333;color:#fff;border:1px solid #666;padding:2px 8px;cursor:pointer;">X</button>';
	echo '<pre style="margin:8px 0 0;white-space:pre-wrap;">';
	foreach ( $info as $key => $val ) {
		$pad = str_pad( $key, 30 );
		echo esc_html( $pad . ' → ' . $val ) . "\n";
	}
	echo '</pre>';
	echo '</div>';
} );

// ─── FIX: Inject mini cart HTML into WooCommerce cart fragments ───
// 1. AJAX fragments: update mini cart when items are added/removed.
add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {
	ob_start();
	woocommerce_mini_cart();
	$mini_cart_html = ob_get_clean();

	$fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart_html . '</div>';

	return $fragments;
} );

// 2. SSR: pre-fill mini cart on page load so it's not empty on first render.
add_action( 'wp_footer', function() {
	if ( ! class_exists( 'WooCommerce' ) || ! isset( WC()->cart ) ) {
		return;
	}
	ob_start();
	woocommerce_mini_cart();
	$mini_cart_html = ob_get_clean();

	if ( empty( $mini_cart_html ) ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var widget = document.querySelector('.widget_shopping_cart_content');
		if (widget && widget.innerHTML.trim().length === 0) {
			widget.innerHTML = <?php echo wp_json_encode( $mini_cart_html ); ?>;
		}
	});
	</script>
	<?php
}, 50 );

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