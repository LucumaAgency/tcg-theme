<?php
/**
 * Meta fields y metabox para ygo_card
 * Campos: card_id, atk, def, level, rank, linkval, scale, frame_type, typeline, source_url, ref_prices
 */

add_action( 'add_meta_boxes', 'tcg_ygo_card_meta_boxes' );
add_action( 'save_post_ygo_card', 'tcg_save_ygo_card_meta', 10, 2 );
add_action( 'init', 'tcg_register_ygo_card_meta' );

/**
 * Registrar meta fields para REST API y queries
 */
function tcg_register_ygo_card_meta() {
	$int_fields = [
		'_ygo_card_id',
		'_ygo_atk',
		'_ygo_def',
		'_ygo_level',
		'_ygo_rank',
		'_ygo_linkval',
		'_ygo_scale',
	];

	foreach ( $int_fields as $field ) {
		register_post_meta( 'ygo_card', $field, [
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		] );
	}

	$string_fields = [
		'_ygo_frame_type',
		'_ygo_typeline',
		'_ygo_source_url',
	];

	foreach ( $string_fields as $field ) {
		register_post_meta( 'ygo_card', $field, [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );
	}

	// Precios de referencia (JSON string)
	register_post_meta( 'ygo_card', '_ygo_ref_prices', [
		'type'         => 'string',
		'single'       => true,
		'show_in_rest' => true,
		'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
	] );

	// Set fields (1 set per post).
	$set_fields = [
		'_ygo_set_code',
		'_ygo_set_rarity',
		'_ygo_set_rarity_code',
		'_ygo_set_price',
	];

	foreach ( $set_fields as $field ) {
		register_post_meta( 'ygo_card', $field, [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );
	}
}

/**
 * Agregar metabox en el editor de ygo_card
 */
function tcg_ygo_card_meta_boxes() {
	add_meta_box(
		'tcg_ygo_card_stats',
		'Card Stats',
		'tcg_render_ygo_card_meta_box',
		'ygo_card',
		'normal',
		'high'
	);

	add_meta_box(
		'tcg_ygo_card_ref_prices',
		'Precios de Referencia (externos)',
		'tcg_render_ygo_card_prices_box',
		'ygo_card',
		'side',
		'default'
	);
}

/**
 * Render del metabox principal de stats
 */
function tcg_render_ygo_card_meta_box( $post ) {
	wp_nonce_field( 'tcg_ygo_card_meta', 'tcg_ygo_card_nonce' );

	$fields = [
		'_ygo_card_id'    => [ 'label' => 'Card ID (YGOProDeck)', 'type' => 'number' ],
		'_ygo_frame_type' => [ 'label' => 'Frame Type',           'type' => 'select', 'options' => [
			''             => '— Seleccionar —',
			'normal'       => 'Normal',
			'effect'       => 'Effect',
			'ritual'       => 'Ritual',
			'fusion'       => 'Fusion',
			'synchro'      => 'Synchro',
			'xyz'          => 'XYZ',
			'link'         => 'Link',
			'pendulum'     => 'Pendulum',
			'spell'        => 'Spell',
			'trap'         => 'Trap',
			'token'        => 'Token',
			'skill'        => 'Skill',
		] ],
		'_ygo_typeline'   => [ 'label' => 'Typeline',  'type' => 'text', 'placeholder' => 'Machine / Effect' ],
		'_ygo_atk'        => [ 'label' => 'ATK',       'type' => 'number' ],
		'_ygo_def'        => [ 'label' => 'DEF',       'type' => 'number' ],
		'_ygo_level'      => [ 'label' => 'Level',     'type' => 'number' ],
		'_ygo_rank'       => [ 'label' => 'Rank',      'type' => 'number' ],
		'_ygo_linkval'    => [ 'label' => 'Link Value', 'type' => 'number' ],
		'_ygo_scale'      => [ 'label' => 'Pendulum Scale', 'type' => 'number' ],
		'_ygo_source_url' => [ 'label' => 'YGOProDeck URL', 'type' => 'url' ],
	];

	echo '<table class="form-table"><tbody>';

	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr>';
		echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
		echo '<td>';

		if ( $field['type'] === 'select' ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			foreach ( $field['options'] as $opt_val => $opt_label ) {
				$selected = selected( $value, $opt_val, false );
				echo '<option value="' . esc_attr( $opt_val ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
			}
			echo '</select>';
		} else {
			$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
			echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text">';
		}

		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';

	// Set info (1 set per post).
	$set_code   = get_post_meta( $post->ID, '_ygo_set_code', true );
	$set_rarity = get_post_meta( $post->ID, '_ygo_set_rarity', true );
	$set_price  = get_post_meta( $post->ID, '_ygo_set_price', true );

	echo '<h4 style="margin-top:20px;">Set Info</h4>';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th>Set Code</th><td><code>' . esc_html( $set_code ?: '—' ) . '</code></td></tr>';
	echo '<tr><th>Rarity</th><td>' . esc_html( $set_rarity ?: '—' ) . '</td></tr>';
	echo '<tr><th>Set Price</th><td>' . esc_html( $set_price ? '$' . $set_price : '—' ) . '</td></tr>';
	echo '</tbody></table>';
}

/**
 * Render del metabox de precios de referencia
 */
function tcg_render_ygo_card_prices_box( $post ) {
	$prices_json = get_post_meta( $post->ID, '_ygo_ref_prices', true );
	$prices = $prices_json ? json_decode( $prices_json, true ) : [];

	$price_fields = [
		'tcgplayer'  => 'TCGPlayer',
		'cardmarket' => 'Cardmarket',
		'ebay'       => 'eBay',
		'amazon'     => 'Amazon',
	];

	foreach ( $price_fields as $key => $label ) {
		$val = isset( $prices[ $key ] ) ? $prices[ $key ] : '';
		echo '<p>';
		echo '<label for="ygo_ref_price_' . $key . '"><strong>' . esc_html( $label ) . ':</strong></label><br>';
		echo '<input type="text" name="ygo_ref_price_' . esc_attr( $key ) . '" id="ygo_ref_price_' . $key . '" value="' . esc_attr( $val ) . '" class="widefat" readonly>';
		echo '</p>';
	}
	echo '<p class="description">Estos precios vienen de la API y son solo referencia.</p>';
}

/**
 * Guardar meta fields al salvar el post
 */
function tcg_save_ygo_card_meta( $post_id, $post ) {
	if ( ! isset( $_POST['tcg_ygo_card_nonce'] ) || ! wp_verify_nonce( $_POST['tcg_ygo_card_nonce'], 'tcg_ygo_card_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Campos enteros
	$int_fields = [ '_ygo_card_id', '_ygo_atk', '_ygo_def', '_ygo_level', '_ygo_rank', '_ygo_linkval', '_ygo_scale' ];
	foreach ( $int_fields as $field ) {
		if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
			update_post_meta( $post_id, $field, intval( $_POST[ $field ] ) );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	// Campos string
	$string_fields = [ '_ygo_frame_type', '_ygo_typeline', '_ygo_source_url' ];
	foreach ( $string_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
		}
	}

	// Precios de referencia
	$price_keys = [ 'tcgplayer', 'cardmarket', 'ebay', 'amazon' ];
	$prices = [];
	foreach ( $price_keys as $key ) {
		if ( isset( $_POST[ 'ygo_ref_price_' . $key ] ) ) {
			$prices[ $key ] = sanitize_text_field( $_POST[ 'ygo_ref_price_' . $key ] );
		}
	}
	update_post_meta( $post_id, '_ygo_ref_prices', wp_json_encode( $prices ) );
}
