<?php
/**
 * TCG Theme — Functions
 */

// ─── CPT, Taxonomías y Meta Fields ───
require_once __DIR__ . '/includes/cpt-ygo-card.php';
require_once __DIR__ . '/includes/taxonomies.php';
require_once __DIR__ . '/includes/meta-ygo-card.php';

// ─── DEBUG: Mini Cart DOM Inspector (temporary) ───
add_action( 'wp_footer', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var info = {};

		// 1. Find Bricks mini cart element
		var miniCartEl = document.querySelector('.brxe-woocommerce-mini-cart');
		info['bricks_mini_cart_element'] = miniCartEl ? '✅ found' : '❌ not found';

		if (miniCartEl) {
			info['mini_cart_classes'] = miniCartEl.className;
			info['mini_cart_innerHTML_length'] = miniCartEl.innerHTML.length;

			// 2. Find the cart detail / dropdown
			var cartDetail = miniCartEl.querySelector('.cart-detail');
			info['cart_detail_found'] = cartDetail ? '✅ found' : '❌ not found';
			if (cartDetail) {
				info['cart_detail_classes'] = cartDetail.className;
				info['cart_detail_display'] = window.getComputedStyle(cartDetail).display;
				info['cart_detail_visibility'] = window.getComputedStyle(cartDetail).visibility;
				info['cart_detail_opacity'] = window.getComputedStyle(cartDetail).opacity;
				info['cart_detail_innerHTML_length'] = cartDetail.innerHTML.length;
				info['cart_detail_children'] = cartDetail.children.length;
				info['cart_detail_innerHTML_preview'] = cartDetail.innerHTML.substring(0, 300);
			}

			// 3. Find WC widget inside
			var wcWidget = miniCartEl.querySelector('.widget_shopping_cart_content');
			info['wc_widget_found'] = wcWidget ? '✅ found' : '❌ not found';
			if (wcWidget) {
				info['wc_widget_innerHTML_length'] = wcWidget.innerHTML.length;
				info['wc_widget_innerHTML_preview'] = wcWidget.innerHTML.substring(0, 300);
			}

			// 4. Find cart list
			var cartList = miniCartEl.querySelector('.woocommerce-mini-cart');
			info['wc_mini_cart_list'] = cartList ? '✅ found (' + cartList.children.length + ' items)' : '❌ not found';

			// 5. Cart count badge
			var countBadge = miniCartEl.querySelector('.cart-count, .count, .cart-contents-count');
			info['count_badge'] = countBadge ? '✅ "' + countBadge.textContent.trim() + '"' : '❌ not found';
		}

		// 6. Other possible mini cart elements
		var allMiniCarts = document.querySelectorAll('[class*="mini-cart"], [class*="minicart"], [class*="cart-detail"]');
		info['all_mini_cart_elements'] = allMiniCarts.length + ' found';
		for (var i = 0; i < Math.min(allMiniCarts.length, 5); i++) {
			info['  element_' + i] = allMiniCarts[i].tagName + '.' + allMiniCarts[i].className.split(' ').slice(0,3).join('.');
		}

		// 7. WC fragments data
		info['wc_cart_fragments_params'] = typeof wc_cart_fragments_params !== 'undefined' ? '✅ loaded' : '❌ missing';
		info['wc_add_to_cart_params'] = typeof wc_add_to_cart_params !== 'undefined' ? '✅ loaded' : '❌ missing';

		// 8. Check for fragment targets in DOM
		var fragmentTarget = document.querySelector('div.widget_shopping_cart_content');
		info['fragment_target_div'] = fragmentTarget ? '✅ found (len:' + fragmentTarget.innerHTML.length + ')' : '❌ not found';

		// Build debug panel
		var panel = document.createElement('div');
		panel.id = 'tcg-debug-minicart';
		panel.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:#0f0;font-family:monospace;font-size:12px;padding:15px;z-index:999999;max-height:50vh;overflow-y:auto;border-top:2px solid #0f0;';
		var html = '<strong style="color:#ff0;font-size:14px;">🔍 TCG DEBUG — Mini Cart DOM</strong>';
		html += ' <button onclick="this.parentElement.remove()" style="float:right;background:#333;color:#fff;border:1px solid #666;padding:2px 8px;cursor:pointer;">X</button>';
		html += '<pre style="margin:8px 0 0;white-space:pre-wrap;">';
		for (var key in info) {
			var pad = key + '                                        ';
			html += pad.substring(0, 40) + ' → ' + String(info[key]).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '\n';
		}
		html += '</pre>';
		panel.innerHTML = html;
		document.body.appendChild(panel);
	});
	</script>
	<?php
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