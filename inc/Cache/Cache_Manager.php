<?php
/**
 * Cache_Manager — Gerenciador de cache inteligente
 * @package VemComerCore
 */

namespace VC\Cache;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_Restaurant;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cache_Manager {
	/**
	 * Cache duration em segundos
	 */
	private const MENU_CACHE_DURATION = 3600; // 1 hora
	private const RESTAURANT_CACHE_DURATION = 1800; // 30 minutos

	public function init(): void {
		// Invalidar cache ao criar/editar/deletar item do cardápio
		add_action( 'save_post_' . CPT_MenuItem::SLUG, [ $this, 'invalidate_menu_cache' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'invalidate_menu_cache_on_delete' ] );

		// Invalidar cache ao atualizar restaurante
		add_action( 'save_post_' . CPT_Restaurant::SLUG, [ $this, 'invalidate_restaurant_cache' ], 10, 2 );
	}

	/**
	 * Obtém cardápio do restaurante (com cache).
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array|null Dados do cardápio ou null
	 */
	public static function get_menu( int $restaurant_id ): ?array {
		$cache_key = 'vc_menu_cache_' . $restaurant_id;
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Buscar itens do cardápio
		$items = get_posts( [
			'post_type'      => CPT_MenuItem::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
		] );

		$menu_data = [];
		foreach ( $items as $item ) {
			$menu_data[] = [
				'id'           => $item->ID,
				'title'        => get_the_title( $item ),
				'price'        => (string) get_post_meta( $item->ID, '_vc_price', true ),
				'prep_time'   => (int) get_post_meta( $item->ID, '_vc_prep_time', true ),
				'is_available' => (bool) get_post_meta( $item->ID, '_vc_is_available', true ),
			];
		}

		set_transient( $cache_key, $menu_data, self::MENU_CACHE_DURATION );

		return $menu_data;
	}

	/**
	 * Obtém dados do restaurante (com cache).
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array|null Dados do restaurante ou null
	 */
	public static function get_restaurant( int $restaurant_id ): ?array {
		$cache_key = 'vc_restaurant_cache_' . $restaurant_id;
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return null;
		}

		$restaurant_data = [
			'id'          => $restaurant->ID,
			'title'       => get_the_title( $restaurant ),
			'address'     => (string) get_post_meta( $restaurant_id, '_vc_address', true ),
			'phone'       => (string) get_post_meta( $restaurant_id, '_vc_phone', true ),
			'has_delivery' => (bool) get_post_meta( $restaurant_id, '_vc_has_delivery', true ),
		];

		set_transient( $cache_key, $restaurant_data, self::RESTAURANT_CACHE_DURATION );

		return $restaurant_data;
	}

	/**
	 * Invalida cache do cardápio ao salvar item.
	 */
	public function invalidate_menu_cache( int $post_id, \WP_Post $post ): void {
		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		if ( $restaurant_id > 0 ) {
			delete_transient( 'vc_menu_cache_' . $restaurant_id );
		}
	}

	/**
	 * Invalida cache do cardápio ao deletar item.
	 */
	public function invalidate_menu_cache_on_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || CPT_MenuItem::SLUG !== $post->post_type ) {
			return;
		}

		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		if ( $restaurant_id > 0 ) {
			delete_transient( 'vc_menu_cache_' . $restaurant_id );
		}
	}

	/**
	 * Invalida cache do restaurante ao salvar.
	 */
	public function invalidate_restaurant_cache( int $post_id, \WP_Post $post ): void {
		delete_transient( 'vc_restaurant_cache_' . $post_id );
	}

	/**
	 * Invalida todo o cache (admin).
	 */
	public static function invalidate_all(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vc_%' OR option_name LIKE '_transient_timeout_vc_%'" );
	}
}

