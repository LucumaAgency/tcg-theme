<?php
/**
 * CPT: ygo_card — Catálogo central de cartas Yu-Gi-Oh!
 * Solo el admin importa/edita. Los vendedores no tocan esto.
 */

add_action( 'init', 'tcg_register_ygo_card_cpt' );

function tcg_register_ygo_card_cpt() {
	$labels = [
		'name'               => 'Cartas YGO',
		'singular_name'      => 'Carta YGO',
		'add_new'            => 'Añadir carta',
		'add_new_item'       => 'Añadir nueva carta',
		'edit_item'          => 'Editar carta',
		'new_item'           => 'Nueva carta',
		'view_item'          => 'Ver carta',
		'search_items'       => 'Buscar cartas',
		'not_found'          => 'No se encontraron cartas',
		'not_found_in_trash' => 'No hay cartas en la papelera',
		'all_items'          => 'Todas las cartas',
		'menu_name'          => 'Cartas YGO',
	];

	$args = [
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => [ 'slug' => 'carta', 'with_front' => false ],
		'menu_icon'    => 'dashicons-tickets-alt',
		'supports'     => [ 'title', 'editor', 'thumbnail' ],
		'show_in_rest' => true,
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	];

	register_post_type( 'ygo_card', $args );
}
