<?php
/**
 * Taxonomías custom para ygo_card y product (WooCommerce)
 */

add_action( 'init', 'tcg_register_taxonomies' );

function tcg_register_taxonomies() {

	// ─── Taxonomías de ygo_card ───

	// Card Type: Effect Monster, Spell Card, Trap Card, Synchro Monster, etc.
	register_taxonomy( 'ygo_card_type', 'ygo_card', [
		'labels'       => tcg_tax_labels( 'Card Type', 'Card Types' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'card-type' ],
		'show_in_rest' => true,
	] );

	// Attribute: LIGHT, DARK, FIRE, WATER, EARTH, WIND, DIVINE
	register_taxonomy( 'ygo_attribute', 'ygo_card', [
		'labels'       => tcg_tax_labels( 'Atributo', 'Atributos' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'atributo' ],
		'show_in_rest' => true,
	] );

	// Race/Monster Type: Machine, Warrior, Spellcaster, Dragon, etc.
	register_taxonomy( 'ygo_race', 'ygo_card', [
		'labels'       => tcg_tax_labels( 'Monster Type', 'Monster Types' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'monster-type' ],
		'show_in_rest' => true,
	] );

	// Archetype: Cyber Dragon, Blue-Eyes, Dark Magician, Branded, etc.
	register_taxonomy( 'ygo_archetype', 'ygo_card', [
		'labels'       => tcg_tax_labels( 'Arquetipo', 'Arquetipos' ),
		'hierarchical' => false,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'arquetipo' ],
		'show_in_rest' => true,
	] );

	// ─── Taxonomías compartidas (ygo_card + ygo_listing) ───

	// Set: Duel Power, Legend of Blue Eyes, Structure Deck Cyber Strike, etc.
	register_taxonomy( 'ygo_set', [ 'ygo_card', 'product' ], [
		'labels'       => tcg_tax_labels( 'Set', 'Sets' ),
		'hierarchical' => false,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'set' ],
		'show_in_rest' => true,
	] );

	// ─── Taxonomías de listing (product WooCommerce) ───

	// Rarity: Common, Rare, Super Rare, Ultra Rare, Secret Rare, etc.
	register_taxonomy( 'ygo_rarity', 'product', [
		'labels'       => tcg_tax_labels( 'Rareza', 'Rarezas' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'rareza' ],
		'show_in_rest' => true,
	] );

	// Condition: Near Mint, Lightly Played, Moderately Played, Heavily Played, Damaged
	register_taxonomy( 'ygo_condition', 'product', [
		'labels'       => tcg_tax_labels( 'Condición', 'Condiciones' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'condicion' ],
		'show_in_rest' => true,
	] );

	// Printing: 1st Edition, Unlimited, Limited
	register_taxonomy( 'ygo_printing', 'product', [
		'labels'       => tcg_tax_labels( 'Printing', 'Printings' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'printing' ],
		'show_in_rest' => true,
	] );

	// Language: English, Spanish, Japanese, Portuguese, etc.
	register_taxonomy( 'ygo_language', 'product', [
		'labels'       => tcg_tax_labels( 'Idioma', 'Idiomas' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'idioma' ],
		'show_in_rest' => true,
	] );
}

/**
 * Helper para generar labels de taxonomías sin repetir código
 */
function tcg_tax_labels( $singular, $plural ) {
	return [
		'name'              => $plural,
		'singular_name'     => $singular,
		'search_items'      => "Buscar {$plural}",
		'all_items'         => "Todos los {$plural}",
		'parent_item'       => "{$singular} padre",
		'parent_item_colon' => "{$singular} padre:",
		'edit_item'         => "Editar {$singular}",
		'update_item'       => "Actualizar {$singular}",
		'add_new_item'      => "Añadir {$singular}",
		'new_item_name'     => "Nuevo {$singular}",
		'menu_name'         => $plural,
	];
}
