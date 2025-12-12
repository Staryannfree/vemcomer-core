<?php
/**
 * Limits_Validator — Validador de limites de planos
 * @package VemComerCore
 */

namespace VC\Subscription;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_ProductModifier;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Limits_Validator {
	public function init(): void {
		// Hook ao criar/atualizar item do cardápio
		add_action( 'save_post_' . CPT_MenuItem::SLUG, [ $this, 'validate_menu_item_limits' ], 10, 3 );

		// Limpar cache de contagens ao salvar/atualizar itens do cardápio
		add_action( 'save_post_' . CPT_MenuItem::SLUG, [ $this, 'clear_menu_item_count_cache' ], 20, 3 );

		// Hook ao criar modificador
		add_action( 'save_post_' . CPT_ProductModifier::SLUG, [ $this, 'validate_modifier_limits' ], 10, 3 );

		// Limpar cache de contagens ao salvar/atualizar modificadores
		add_action( 'save_post_' . CPT_ProductModifier::SLUG, [ $this, 'clear_modifier_count_cache' ], 20, 3 );

		// Limpar caches de contagem em alterações de status/remoção
		add_action( 'trashed_post', [ $this, 'clear_cache_on_status_change' ], 10, 1 );
		add_action( 'untrashed_post', [ $this, 'clear_cache_on_status_change' ], 10, 1 );
		add_action( 'before_delete_post', [ $this, 'clear_cache_on_status_change' ], 10, 1 );
	}

	/**
	 * Valida limites ao salvar item do cardápio.
	 */
	public function validate_menu_item_limits( int $post_id, ?\WP_Post $post, bool $update ): void {
		// Bypass durante REST requests (onboarding via API)
		// Isso permite que produtos sejam criados durante o onboarding sem validação de limite
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		
		// Validar se o post existe
		if ( ! $post || ! $post instanceof \WP_Post ) {
			return;
		}
		
		// Apenas para novos itens (não atualizações)
		if ( $update ) {
			return;
		}

		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		if ( ! $restaurant_id ) {
			return;
		}

		$max_items = Plan_Manager::get_max_menu_items( $restaurant_id );
		if ( $max_items <= 0 ) {
			return; // Ilimitado
		}

		// Contar itens atuais do restaurante
                $current_count = self::count_menu_items( $restaurant_id );

		if ( $current_count >= $max_items ) {
			// Remover o post que acabou de ser criado
			wp_delete_post( $post_id, true );

			// Adicionar notice de erro
			add_action( 'admin_notices', function() use ( $max_items ) {
				?>
				<div class="notice notice-error">
					<p><?php echo esc_html( sprintf( __( 'Limite de itens no cardápio atingido! Seu plano permite no máximo %d itens.', 'vemcomer' ), $max_items ) ); ?></p>
				</div>
				<?php
			} );
		}
	}

	/**
	 * Valida limites ao criar modificador.
	 */
	public function validate_modifier_limits( int $post_id, \WP_Post $post, bool $update ): void {
		if ( $update ) {
			return;
		}

		// Obter menu items vinculados
		$menu_items = get_post_meta( $post_id, '_vc_modifier_menu_items', true );
		if ( ! is_array( $menu_items ) || empty( $menu_items ) ) {
			return;
		}

		// Verificar cada item vinculado
		foreach ( $menu_items as $menu_item_id ) {
			$menu_item_id = (int) $menu_item_id;
			if ( ! $menu_item_id ) {
				continue;
			}

			$restaurant_id = (int) get_post_meta( $menu_item_id, '_vc_restaurant_id', true );
			if ( ! $restaurant_id ) {
				continue;
			}

			$max_modifiers = Plan_Manager::get_max_modifiers_per_item( $restaurant_id );
			if ( $max_modifiers <= 0 ) {
				continue; // Ilimitado
			}

			// Contar modificadores atuais do item
                        $current_count = self::count_modifiers_for_item( $menu_item_id );

			if ( $current_count >= $max_modifiers ) {
				// Remover o post que acabou de ser criado
				wp_delete_post( $post_id, true );

				// Adicionar notice de erro
				add_action( 'admin_notices', function() use ( $max_modifiers ) {
					?>
					<div class="notice notice-error">
						<p><?php echo esc_html( sprintf( __( 'Limite de modificadores por item atingido! Seu plano permite no máximo %d modificadores por item.', 'vemcomer' ), $max_modifiers ) ); ?></p>
					</div>
					<?php
				} );

				return; // Parar após primeiro erro
			}
		}
	}

	/**
	 * Conta itens do cardápio de um restaurante.
	 */
	private static function count_menu_items( int $restaurant_id ): int {
		$cache_key = 'vc_menu_items_count_' . $restaurant_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$query = new \WP_Query( [
			'post_type'              => CPT_MenuItem::SLUG,
			'posts_per_page'         => 1,
			'post_status'            => [ 'publish', 'pending', 'draft' ],
			'fields'                 => 'ids',
			'meta_query'             => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );
		$count = (int) $query->found_posts;

		set_transient( $cache_key, $count, MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Conta modificadores vinculados a um item.
	 */
	private static function count_modifiers_for_item( int $menu_item_id ): int {
		$cache_key = 'vc_modifiers_count_' . $menu_item_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$query = new \WP_Query( [
			'post_type'              => CPT_ProductModifier::SLUG,
			'posts_per_page'         => 1,
			'post_status'            => [ 'publish', 'pending', 'draft' ],
			'fields'                 => 'ids',
			'meta_query'             => [
				[
					'key'     => '_vc_modifier_menu_items',
					'value'   => (string) $menu_item_id,
					'compare' => 'LIKE',
				],
			],
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );
		$count = (int) $query->found_posts;

		set_transient( $cache_key, $count, MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Limpa cache de contagem de itens do cardápio ao alterar o post.
	 */
	public function clear_menu_item_count_cache( int $post_id, ?\WP_Post $post = null, bool $update = false ): void {
		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );

		if ( $restaurant_id ) {
			self::delete_menu_item_count_cache( $restaurant_id );
		}
	}

	/**
	 * Limpa cache de contagem de modificadores ao alterar o post.
	 */
	public function clear_modifier_count_cache( int $post_id ): void {
		$menu_items = get_post_meta( $post_id, '_vc_modifier_menu_items', true );

		if ( ! is_array( $menu_items ) ) {
			return;
		}

		foreach ( $menu_items as $menu_item_id ) {
			$menu_item_id = (int) $menu_item_id;

			if ( $menu_item_id ) {
				self::delete_modifier_count_cache( $menu_item_id );
			}
		}
	}

	/**
	 * Limpa caches em mudanças de status ou exclusões.
	 */
	public function clear_cache_on_status_change( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		if ( CPT_MenuItem::SLUG === $post->post_type ) {
			$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );

			if ( $restaurant_id ) {
				self::delete_menu_item_count_cache( $restaurant_id );
			}
		}

		if ( CPT_ProductModifier::SLUG === $post->post_type ) {
			$menu_items = get_post_meta( $post_id, '_vc_modifier_menu_items', true );

			if ( ! is_array( $menu_items ) ) {
				return;
			}

			foreach ( $menu_items as $menu_item_id ) {
				$menu_item_id = (int) $menu_item_id;

				if ( $menu_item_id ) {
					self::delete_modifier_count_cache( $menu_item_id );
				}
			}
		}
	}

	/**
	 * Remove cache de contagem de itens do cardápio.
	 */
	private static function delete_menu_item_count_cache( int $restaurant_id ): void {
		delete_transient( 'vc_menu_items_count_' . $restaurant_id );
	}

	/**
	 * Remove cache de contagem de modificadores.
	 */
	private static function delete_modifier_count_cache( int $menu_item_id ): void {
		delete_transient( 'vc_modifiers_count_' . $menu_item_id );
	}

	/**
	 * Valida se pode criar item (para uso em REST API).
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return WP_Error|null Erro se não pode criar, null se pode
	 */
	public static function can_create_menu_item( int $restaurant_id ): ?WP_Error {
		$max_items = Plan_Manager::get_max_menu_items( $restaurant_id );
		if ( $max_items <= 0 ) {
			return null; // Ilimitado
		}

                $current_items = self::count_menu_items( $restaurant_id );

                if ( $current_items >= $max_items ) {
                        return new WP_Error(
                                'vc_limit_reached',
                                sprintf( __( 'Limite de itens no cardápio atingido! Seu plano permite no máximo %d itens.', 'vemcomer' ), $max_items ),
                                [ 'status' => 403, 'limit' => $max_items, 'current' => $current_items ]
                        );
                }

		return null;
	}

	/**
	 * Valida se pode criar modificador para um item (para uso em REST API).
	 *
	 * @param int $menu_item_id ID do item do cardápio
	 * @return WP_Error|null Erro se não pode criar, null se pode
	 */
	public static function can_create_modifier( int $menu_item_id ): ?WP_Error {
		$restaurant_id = (int) get_post_meta( $menu_item_id, '_vc_restaurant_id', true );
		if ( ! $restaurant_id ) {
			return new WP_Error( 'vc_invalid_item', __( 'Item do cardápio inválido.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		$max_modifiers = Plan_Manager::get_max_modifiers_per_item( $restaurant_id );
		if ( $max_modifiers <= 0 ) {
			return null; // Ilimitado
		}

                $current_modifiers = self::count_modifiers_for_item( $menu_item_id );

                if ( $current_modifiers >= $max_modifiers ) {
                        return new WP_Error(
                                'vc_limit_reached',
                                sprintf( __( 'Limite de modificadores por item atingido! Seu plano permite no máximo %d modificadores por item.', 'vemcomer' ), $max_modifiers ),
                                [ 'status' => 403, 'limit' => $max_modifiers, 'current' => $current_modifiers ]
                        );
                }

		return null;
	}
}

