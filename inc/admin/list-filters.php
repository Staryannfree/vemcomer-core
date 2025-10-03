<?php
/**
 * Filtros na listagem do CPT vc_restaurant (admin).
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Adiciona dropdowns acima da tabela.
add_action(
	'restrict_manage_posts',
	function( $post_type ) {
		if ( 'vc_restaurant' !== $post_type ) {
			return;
		}

		$selected_cuisine = isset( $_GET['vc_cuisine'] ) ? sanitize_title( wp_unslash( $_GET['vc_cuisine'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Todas as cozinhas', 'vemcomer' ),
				'taxonomy'        => 'vc_cuisine',
				'name'            => 'vc_cuisine',
				'orderby'         => 'name',
				'selected'        => $selected_cuisine,
				'hierarchical'    => false,
				'show_count'      => false,
				'hide_empty'      => false,
				'value_field'     => 'slug',
			)
		);

		$selected_location = isset( $_GET['vc_location'] ) ? sanitize_title( wp_unslash( $_GET['vc_location'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Todos os bairros', 'vemcomer' ),
				'taxonomy'        => 'vc_location',
				'name'            => 'vc_location',
				'orderby'         => 'name',
				'selected'        => $selected_location,
				'hierarchical'    => false,
				'show_count'      => false,
				'hide_empty'      => false,
				'value_field'     => 'slug',
			)
		);

		$delivery = isset( $_GET['vc_delivery'] ) ? (string) $_GET['vc_delivery'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
		echo '<select name="vc_delivery">';
		echo '<option value="">' . esc_html__( 'Delivery: todos', 'vemcomer' ) . '</option>';
		echo '<option value="1"' . selected( $delivery, '1', false ) . '>' . esc_html__( 'Com delivery', 'vemcomer' ) . '</option>';
		echo '<option value="0"' . selected( $delivery, '0', false ) . '>' . esc_html__( 'Sem delivery', 'vemcomer' ) . '</option>';
		echo '</select>';
	}
);

// Aplica filtros na query.
add_action(
	'pre_get_posts',
	function( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'vc_restaurant' !== $query->get( 'post_type' ) ) {
			return;
		}

		$tax_query = array();

		if ( ! empty( $_GET['vc_cuisine'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
			$tax_query[] = array(
				'taxonomy' => 'vc_cuisine',
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['vc_cuisine'] ) ),
			);
		}

		if ( ! empty( $_GET['vc_location'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
			$tax_query[] = array(
				'taxonomy' => 'vc_location',
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['vc_location'] ) ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}

		if ( isset( $_GET['vc_delivery'] ) && '' !== $_GET['vc_delivery'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => 'vc_restaurant_delivery',
						'value' => '1' === $_GET['vc_delivery'] ? '1' : '0',
					),
				)
			);
		}
	}
);
