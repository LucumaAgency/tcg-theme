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
		$wp_query->is_404     = false;
		$wp_query->is_archive = true;
		$wp_query->is_post_type_archive = true;
		status_header( 200 );

		// Restore WooCommerce shop query vars so is_shop() returns true.
		$wp_query->set( 'wc_query', 'product_query' );
		$wp_query->queried_object = get_post_type_object( 'product' );
		$wp_query->queried_object_id = 0;
	}
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

	// Live search: preload cards for instant client-side search.
	if ( ! is_admin() ) {
		wp_enqueue_style(
			'tcg-live-search',
			get_stylesheet_directory_uri() . '/assets/css/live-search.css',
			[],
			filemtime( get_stylesheet_directory() . '/assets/css/live-search.css' )
		);

		wp_enqueue_script(
			'tcg-live-search',
			get_stylesheet_directory_uri() . '/assets/js/live-search.js',
			[],
			filemtime( get_stylesheet_directory() . '/assets/js/live-search.js' ),
			true
		);

		wp_localize_script( 'tcg-live-search', 'tcgLiveSearch', [
			'cards'       => tcg_get_cards_for_live_search(),
			'cardBaseUrl' => home_url( '/carta/' ),
		] );
	}
} );

/**
 * Get all ygo_card posts for client-side live search.
 * Reuses the tcg_dokan_cards_js transient if available, otherwise builds its own.
 */
function tcg_get_cards_for_live_search() {
	$cache_key = 'tcg_theme_live_search';
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT p.ID, p.post_title, p.post_name,
			MAX(CASE WHEN pm.meta_key = '_ygo_set_code' THEN pm.meta_value END) AS set_code,
			MAX(CASE WHEN pm.meta_key = '_ygo_set_rarity' THEN pm.meta_value END) AS set_rarity
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_ygo_set_code', '_ygo_set_rarity')
		WHERE p.post_type = 'ygo_card' AND p.post_status = 'publish'
		GROUP BY p.ID
		ORDER BY p.post_title ASC",
		ARRAY_A
	);

	$cards = [];
	foreach ( $rows as $row ) {
		$set_code = $row['set_code'] ?: '';
		$thumb    = get_the_post_thumbnail_url( (int) $row['ID'], 'thumbnail' );

		$cards[] = [
			'id'         => (int) $row['ID'],
			'label'      => $row['post_title'] . ( $set_code ? " [{$set_code}]" : '' ),
			'slug'       => $row['post_name'],
			'set_rarity' => $row['set_rarity'] ?: '',
			'thumb'      => $thumb ?: '',
		];
	}

	set_transient( $cache_key, $cards, HOUR_IN_SECONDS );

	return $cards;
}

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