<?php
/**
 * TCG Theme — Functions
 */

// ─── CPT, Taxonomías y Meta Fields ───
require_once __DIR__ . '/includes/cpt-ygo-card.php';
require_once __DIR__ . '/includes/taxonomies.php';
require_once __DIR__ . '/includes/meta-ygo-card.php';

// ─── DEBUG: Cart Fragments Inspector (temporary) ───
add_action( 'wp_footer', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<script>
	(function() {
		// Intercept the WC fragments AJAX call
		var origOpen = XMLHttpRequest.prototype.open;
		var origSend = XMLHttpRequest.prototype.send;

		XMLHttpRequest.prototype.open = function(method, url) {
			this._tcgUrl = url;
			return origOpen.apply(this, arguments);
		};

		XMLHttpRequest.prototype.send = function() {
			var self = this;
			if (self._tcgUrl && self._tcgUrl.indexOf('get_refreshed_fragments') !== -1) {
				self.addEventListener('load', function() {
					try {
						var data = JSON.parse(self.responseText);
						var info = {};
						info['fragments_url'] = self._tcgUrl;
						info['response_status'] = self.status;
						info['has_fragments'] = data.fragments ? '✅ yes (' + Object.keys(data.fragments).length + ' keys)' : '❌ no';
						info['has_cart_hash'] = data.cart_hash ? '✅ ' + data.cart_hash : '❌ no';

						if (data.fragments) {
							var keys = Object.keys(data.fragments);
							for (var i = 0; i < keys.length; i++) {
								var val = String(data.fragments[keys[i]]);
								info['fragment_' + i + '_key'] = keys[i];
								info['fragment_' + i + '_len'] = val.length;
								if (keys[i].indexOf('widget_shopping_cart_content') !== -1) {
									info['*** MINI CART FRAGMENT ***'] = val.substring(0, 500);
								}
							}
						}

						// Check widget after fragments applied
						setTimeout(function() {
							var widget = document.querySelector('.widget_shopping_cart_content');
							info['widget_after_fragments_len'] = widget ? widget.innerHTML.length : 'not found';
							info['widget_after_fragments_preview'] = widget ? widget.innerHTML.substring(0, 200) : 'N/A';
							showPanel(info);
						}, 500);
					} catch(e) {
						showPanel({error: e.message, responsePreview: self.responseText.substring(0, 500)});
					}
				});
			}
			return origSend.apply(this, arguments);
		};

		// Also check on page load
		document.addEventListener('DOMContentLoaded', function() {
			var widget = document.querySelector('.widget_shopping_cart_content');
			var stored = sessionStorage.getItem('wc_fragments');
			var info = {
				'page_load_widget_len': widget ? widget.innerHTML.length : 'not found',
				'sessionStorage_fragments': stored ? '✅ found (len:' + stored.length + ')' : '❌ not found',
			};

			if (stored) {
				try {
					var frags = JSON.parse(stored);
					var keys = Object.keys(frags);
					info['stored_fragment_keys'] = keys.length;
					for (var i = 0; i < keys.length; i++) {
						if (keys[i].indexOf('widget_shopping_cart_content') !== -1) {
							info['*** STORED MINI CART ***'] = String(frags[keys[i]]).substring(0, 300);
						}
					}
				} catch(e) {}
			}

			// Try different storage keys WC might use
			for (var j = 0; j < sessionStorage.length; j++) {
				var skey = sessionStorage.key(j);
				if (skey.indexOf('wc') !== -1 || skey.indexOf('cart') !== -1) {
					info['sessionStorage_' + skey] = sessionStorage.getItem(skey).substring(0, 100);
				}
			}

			showPanel(info);
		});

		function showPanel(info) {
			var existing = document.getElementById('tcg-debug-fragments');
			if (existing) existing.remove();

			var panel = document.createElement('div');
			panel.id = 'tcg-debug-fragments';
			panel.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:#0f0;font-family:monospace;font-size:11px;padding:15px;z-index:999999;max-height:50vh;overflow-y:auto;border-top:2px solid #0f0;';
			var html = '<strong style="color:#ff0;font-size:14px;">🔍 TCG DEBUG — Cart Fragments</strong>';
			html += ' <button onclick="this.parentElement.remove()" style="float:right;background:#333;color:#fff;border:1px solid #666;padding:2px 8px;cursor:pointer;">X</button>';
			html += '<pre style="margin:8px 0 0;white-space:pre-wrap;">';
			for (var key in info) {
				var pad = key + '                                          ';
				html += pad.substring(0, 42) + ' → ' + String(info[key]).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '\n';
			}
			html += '</pre>';
			panel.innerHTML = html;
			document.body.appendChild(panel);
		}
	})();
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