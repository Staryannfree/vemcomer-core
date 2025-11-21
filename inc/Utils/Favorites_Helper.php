<?php
/**
 * Favorites_Helper — Funções auxiliares para gerenciar favoritos de usuários
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Favorites_Helper {
	/**
	 * Meta key para restaurantes favoritos
	 */
	public const META_RESTAURANTS = 'vc_favorite_restaurants';

	/**
	 * Meta key para itens do cardápio favoritos
	 */
	public const META_MENU_ITEMS = 'vc_favorite_menu_items';

	/**
	 * Obtém lista de restaurantes favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return int[] Array de IDs de restaurantes
	 */
	public static function get_favorite_restaurants( int $user_id ): array {
		$favorites = get_user_meta( $user_id, self::META_RESTAURANTS, true );
		if ( ! is_array( $favorites ) ) {
			return [];
		}
		// Garantir que são todos inteiros
		return array_map( 'absint', array_filter( $favorites, 'is_numeric' ) );
	}

	/**
	 * Obtém lista de itens do cardápio favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return int[] Array de IDs de itens do cardápio
	 */
	public static function get_favorite_menu_items( int $user_id ): array {
		$favorites = get_user_meta( $user_id, self::META_MENU_ITEMS, true );
		if ( ! is_array( $favorites ) ) {
			return [];
		}
		// Garantir que são todos inteiros
		return array_map( 'absint', array_filter( $favorites, 'is_numeric' ) );
	}

	/**
	 * Verifica se um restaurante está nos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $restaurant_id ID do restaurante
	 * @return bool True se está nos favoritos
	 */
	public static function is_restaurant_favorite( int $user_id, int $restaurant_id ): bool {
		$favorites = self::get_favorite_restaurants( $user_id );
		return in_array( $restaurant_id, $favorites, true );
	}

	/**
	 * Verifica se um item do cardápio está nos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $menu_item_id ID do item do cardápio
	 * @return bool True se está nos favoritos
	 */
	public static function is_menu_item_favorite( int $user_id, int $menu_item_id ): bool {
		$favorites = self::get_favorite_menu_items( $user_id );
		return in_array( $menu_item_id, $favorites, true );
	}

	/**
	 * Adiciona um restaurante aos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $restaurant_id ID do restaurante
	 * @return bool True se foi adicionado, false se já estava nos favoritos
	 */
	public static function add_favorite_restaurant( int $user_id, int $restaurant_id ): bool {
		$favorites = self::get_favorite_restaurants( $user_id );
		if ( in_array( $restaurant_id, $favorites, true ) ) {
			return false; // Já está nos favoritos
		}
		$favorites[] = $restaurant_id;
		update_user_meta( $user_id, self::META_RESTAURANTS, $favorites );
		return true;
	}

	/**
	 * Adiciona um item do cardápio aos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $menu_item_id ID do item do cardápio
	 * @return bool True se foi adicionado, false se já estava nos favoritos
	 */
	public static function add_favorite_menu_item( int $user_id, int $menu_item_id ): bool {
		$favorites = self::get_favorite_menu_items( $user_id );
		if ( in_array( $menu_item_id, $favorites, true ) ) {
			return false; // Já está nos favoritos
		}
		$favorites[] = $menu_item_id;
		update_user_meta( $user_id, self::META_MENU_ITEMS, $favorites );
		return true;
	}

	/**
	 * Remove um restaurante dos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $restaurant_id ID do restaurante
	 * @return bool True se foi removido, false se não estava nos favoritos
	 */
	public static function remove_favorite_restaurant( int $user_id, int $restaurant_id ): bool {
		$favorites = self::get_favorite_restaurants( $user_id );
		$key       = array_search( $restaurant_id, $favorites, true );
		if ( false === $key ) {
			return false; // Não estava nos favoritos
		}
		unset( $favorites[ $key ] );
		$favorites = array_values( $favorites ); // Reindexar array
		update_user_meta( $user_id, self::META_RESTAURANTS, $favorites );
		return true;
	}

	/**
	 * Remove um item do cardápio dos favoritos do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @param int $menu_item_id ID do item do cardápio
	 * @return bool True se foi removido, false se não estava nos favoritos
	 */
	public static function remove_favorite_menu_item( int $user_id, int $menu_item_id ): bool {
		$favorites = self::get_favorite_menu_items( $user_id );
		$key       = array_search( $menu_item_id, $favorites, true );
		if ( false === $key ) {
			return false; // Não estava nos favoritos
		}
		unset( $favorites[ $key ] );
		$favorites = array_values( $favorites ); // Reindexar array
		update_user_meta( $user_id, self::META_MENU_ITEMS, $favorites );
		return true;
	}

	/**
	 * Alterna status de favorito de um restaurante (adiciona se não está, remove se está).
	 *
	 * @param int $user_id ID do usuário
	 * @param int $restaurant_id ID do restaurante
	 * @return bool True se foi adicionado, false se foi removido
	 */
	public static function toggle_favorite_restaurant( int $user_id, int $restaurant_id ): bool {
		if ( self::is_restaurant_favorite( $user_id, $restaurant_id ) ) {
			self::remove_favorite_restaurant( $user_id, $restaurant_id );
			return false; // Foi removido
		} else {
			self::add_favorite_restaurant( $user_id, $restaurant_id );
			return true; // Foi adicionado
		}
	}

	/**
	 * Alterna status de favorito de um item do cardápio (adiciona se não está, remove se está).
	 *
	 * @param int $user_id ID do usuário
	 * @param int $menu_item_id ID do item do cardápio
	 * @return bool True se foi adicionado, false se foi removido
	 */
	public static function toggle_favorite_menu_item( int $user_id, int $menu_item_id ): bool {
		if ( self::is_menu_item_favorite( $user_id, $menu_item_id ) ) {
			self::remove_favorite_menu_item( $user_id, $menu_item_id );
			return false; // Foi removido
		} else {
			self::add_favorite_menu_item( $user_id, $menu_item_id );
			return true; // Foi adicionado
		}
	}

	/**
	 * Limpa todos os favoritos de restaurantes do usuário.
	 *
	 * @param int $user_id ID do usuário
	 */
	public static function clear_favorite_restaurants( int $user_id ): void {
		delete_user_meta( $user_id, self::META_RESTAURANTS );
	}

	/**
	 * Limpa todos os favoritos de itens do cardápio do usuário.
	 *
	 * @param int $user_id ID do usuário
	 */
	public static function clear_favorite_menu_items( int $user_id ): void {
		delete_user_meta( $user_id, self::META_MENU_ITEMS );
	}
}

