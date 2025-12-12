<?php
/**
 * CPT_Review — Custom Post Type "Review" (Avaliações)
 * + Capabilities customizadas e concessão por role (grant_caps).
 * + Status customizados: pending, approved, rejected
 *
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Review {
	public const SLUG = 'vc_review';

	public const STATUS_PENDING  = 'vc-review-pending';
	public const STATUS_APPROVED = 'vc-review-approved';
	public const STATUS_REJECTED = 'vc-review-rejected';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'init', [ $this, 'register_statuses' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
		add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
		add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
		add_action( 'init', [ $this, 'grant_caps' ], 5 );
		
		// Invalidar cache de rating ao criar/atualizar/deletar avaliação
		add_action( 'wp_insert_post', [ $this, 'invalidate_rating_cache_on_save' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'invalidate_rating_cache_on_delete' ] );
	}

	private function capabilities(): array {
		return [
			'edit_post'              => 'edit_vc_review',
			'read_post'              => 'read_vc_review',
			'delete_post'            => 'delete_vc_review',
			'edit_posts'             => 'edit_vc_reviews',
			'edit_others_posts'      => 'edit_others_vc_reviews',
			'publish_posts'          => 'publish_vc_reviews',
			'read_private_posts'     => 'read_private_vc_reviews',
			'delete_posts'           => 'delete_vc_reviews',
			'delete_private_posts'   => 'delete_private_vc_reviews',
			'delete_published_posts' => 'delete_published_vc_reviews',
			'delete_others_posts'    => 'delete_others_vc_reviews',
			'edit_private_posts'     => 'edit_private_vc_reviews',
			'edit_published_posts'   => 'edit_published_vc_reviews',
			'create_posts'           => 'create_vc_reviews',
		];
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Avaliações', 'vemcomer' ),
			'singular_name'         => __( 'Avaliação', 'vemcomer' ),
			'menu_name'             => __( 'Avaliações', 'vemcomer' ),
			'name_admin_bar'        => __( 'Avaliação', 'vemcomer' ),
			'add_new'               => __( 'Adicionar nova', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar nova avaliação', 'vemcomer' ),
			'new_item'              => __( 'Nova avaliação', 'vemcomer' ),
			'edit_item'             => __( 'Editar avaliação', 'vemcomer' ),
			'view_item'             => __( 'Ver avaliação', 'vemcomer' ),
			'all_items'             => __( 'Todas as avaliações', 'vemcomer' ),
			'search_items'          => __( 'Buscar avaliações', 'vemcomer' ),
			'not_found'             => __( 'Nenhuma avaliação encontrada.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhuma avaliação na lixeira.', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false, // Não é público, apenas admin
			'show_ui'         => true,
			'show_in_menu'    => false, // Será adicionado ao menu do VemComer
			'show_in_rest'    => true,
			'supports'        => [ 'title', 'editor' ],
			'capability_type' => [ 'vc_review', 'vc_reviews' ],
			'map_meta_cap'    => true,
			'capabilities'    => $this->capabilities(),
		];
		register_post_type( self::SLUG, $args );
	}

	public function register_statuses(): void {
		$statuses = [
			self::STATUS_PENDING  => __( 'Pendente', 'vemcomer' ),
			self::STATUS_APPROVED => __( 'Aprovada', 'vemcomer' ),
			self::STATUS_REJECTED => __( 'Rejeitada', 'vemcomer' ),
		];

		foreach ( $statuses as $key => $label ) {
			register_post_status( $key, [
				'label'                     => $label,
				'public'                    => false,
				'internal'                  => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( "$label <span class=\"count\">(%s)</span>", "$label <span class=\"count\">(%s)</span>", 'vemcomer' ),
			] );
		}
	}

	public function register_metaboxes(): void {
		add_meta_box(
			'vc_review_meta',
			__( 'Dados da Avaliação', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);

		add_meta_box(
			'vc_review_status',
			__( 'Status da Avaliação', 'vemcomer' ),
			[ $this, 'status_metabox' ],
			self::SLUG,
			'side',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_review_meta_nonce', 'vc_review_meta_nonce_field' );

		$restaurant_id = (int) get_post_meta( $post->ID, '_vc_restaurant_id', true );
		$customer_id   = (int) get_post_meta( $post->ID, '_vc_customer_id', true );
		$rating        = (int) get_post_meta( $post->ID, '_vc_rating', true );
		$order_id      = (int) get_post_meta( $post->ID, '_vc_order_id', true );

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_restaurant_id"><?php echo esc_html__( 'Restaurante', 'vemcomer' ); ?></label></th>
				<td>
					<?php
					$restaurants = get_posts( [
						'post_type'      => 'vc_restaurant',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'orderby'        => 'title',
						'order'          => 'ASC',
					] );
					?>
					<select id="vc_restaurant_id" name="vc_restaurant_id" class="regular-text">
						<option value=""><?php echo esc_html__( '— Selecione —', 'vemcomer' ); ?></option>
						<?php foreach ( $restaurants as $restaurant ) : ?>
							<option value="<?php echo esc_attr( (string) $restaurant->ID ); ?>" <?php selected( $restaurant_id, $restaurant->ID ); ?>>
								<?php echo esc_html( $restaurant->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="vc_customer_id"><?php echo esc_html__( 'Cliente', 'vemcomer' ); ?></label></th>
				<td>
					<?php
					$users = get_users( [
						'number' => 100,
						'orderby' => 'display_name',
					] );
					?>
					<select id="vc_customer_id" name="vc_customer_id" class="regular-text">
						<option value=""><?php echo esc_html__( '— Selecione —', 'vemcomer' ); ?></option>
						<?php foreach ( $users as $user ) : ?>
							<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $customer_id, $user->ID ); ?>>
								<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo esc_html__( 'Cliente que fez a avaliação.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_rating"><?php echo esc_html__( 'Avaliação (1-5)', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_rating" name="vc_rating" class="small-text">
						<option value=""><?php echo esc_html__( '— Selecione —', 'vemcomer' ); ?></option>
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $rating, $i ); ?>>
								<?php echo esc_html( (string) $i . ' ' . str_repeat( '★', $i ) ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<p class="description"><?php echo esc_html__( 'Avaliação de 1 a 5 estrelas.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_order_id"><?php echo esc_html__( 'Pedido (opcional)', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_order_id" name="vc_order_id" value="<?php echo esc_attr( $order_id > 0 ? (string) $order_id : '' ); ?>" class="small-text" />
					<p class="description"><?php echo esc_html__( 'ID do pedido relacionado, se houver.', 'vemcomer' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function status_metabox( $post ): void {
		$current_status = get_post_status( $post );
		wp_nonce_field( 'vc_review_status_nonce', 'vc_review_status_nonce_field' );

		$statuses = [
			self::STATUS_PENDING  => __( 'Pendente', 'vemcomer' ),
			self::STATUS_APPROVED => __( 'Aprovada', 'vemcomer' ),
			self::STATUS_REJECTED => __( 'Rejeitada', 'vemcomer' ),
		];

		?>
		<p>
			<label for="vc_review_status_sel"><?php echo esc_html__( 'Status', 'vemcomer' ); ?></label>
		</p>
		<select id="vc_review_status_sel" name="vc_review_status_sel" class="widefat">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_status, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php echo esc_html__( 'Apenas avaliações aprovadas aparecem publicamente.', 'vemcomer' ); ?>
		</p>
		<?php
	}

	public function save_meta( int $post_id ): void {
		// Verificar nonce
		if ( ! isset( $_POST['vc_review_meta_nonce_field'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['vc_review_meta_nonce_field'], 'vc_review_meta_nonce' ) ) {
			return;
		}

		// Verificar autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verificar permissões
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Salvar restaurante
		$restaurant_id = isset( $_POST['vc_restaurant_id'] ) ? (int) $_POST['vc_restaurant_id'] : 0;
		$old_restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		
		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_restaurant_id', $restaurant_id );
			// Se mudou de restaurante, recalcular ambos
			if ( $old_restaurant_id > 0 && $old_restaurant_id !== $restaurant_id ) {
				$this->update_restaurant_rating( $old_restaurant_id );
			}
		} else {
			delete_post_meta( $post_id, '_vc_restaurant_id' );
			// Se tinha restaurante antes, recalcular
			if ( $old_restaurant_id > 0 ) {
				$this->update_restaurant_rating( $old_restaurant_id );
			}
		}

		// Salvar cliente
		$customer_id = isset( $_POST['vc_customer_id'] ) ? (int) $_POST['vc_customer_id'] : 0;
		if ( $customer_id > 0 ) {
			update_post_meta( $post_id, '_vc_customer_id', $customer_id );
		} else {
			delete_post_meta( $post_id, '_vc_customer_id' );
		}

		// Salvar rating
		$rating = isset( $_POST['vc_rating'] ) ? (int) $_POST['vc_rating'] : 0;
		$old_rating = (int) get_post_meta( $post_id, '_vc_rating', true );
		
		if ( $rating >= 1 && $rating <= 5 ) {
			update_post_meta( $post_id, '_vc_rating', $rating );
			// Se mudou o rating e a avaliação está aprovada, recalcular
			if ( $old_rating !== $rating && $restaurant_id > 0 ) {
				$current_status = get_post_status( $post_id );
				if ( self::STATUS_APPROVED === $current_status ) {
					$this->update_restaurant_rating( $restaurant_id );
				}
			}
		} else {
			delete_post_meta( $post_id, '_vc_rating' );
		}

		// Salvar pedido (opcional)
		$order_id = isset( $_POST['vc_order_id'] ) ? (int) $_POST['vc_order_id'] : 0;
		if ( $order_id > 0 ) {
			update_post_meta( $post_id, '_vc_order_id', $order_id );
		} else {
			delete_post_meta( $post_id, '_vc_order_id' );
		}

		// Salvar status
		if ( isset( $_POST['vc_review_status_nonce_field'] ) && wp_verify_nonce( $_POST['vc_review_status_nonce_field'], 'vc_review_status_nonce' ) ) {
			$new_status = isset( $_POST['vc_review_status_sel'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_review_status_sel'] ) ) : self::STATUS_PENDING;
			$valid_statuses = [ self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED ];
			if ( in_array( $new_status, $valid_statuses, true ) ) {
				$old_status = get_post_status( $post_id );
				if ( $new_status !== $old_status ) {
					global $wpdb;
					$wpdb->update( $wpdb->posts, [ 'post_status' => $new_status ], [ 'ID' => $post_id ] );
					clean_post_cache( $post_id );

					// Se foi aprovada, atualizar rating agregado do restaurante
					if ( self::STATUS_APPROVED === $new_status && $restaurant_id > 0 ) {
						$this->update_restaurant_rating( $restaurant_id );
					}

					// Se foi rejeitada ou mudou de aprovada, recalcular rating
					if ( self::STATUS_REJECTED === $new_status || ( self::STATUS_APPROVED === $old_status && $new_status !== self::STATUS_APPROVED ) ) {
						if ( $restaurant_id > 0 ) {
							$this->update_restaurant_rating( $restaurant_id );
						}
					}
				}
			}
		}
	}

	/**
	 * Atualiza o rating agregado do restaurante.
	 * Usa Rating_Helper para recalcular com cache.
	 */
	private function update_restaurant_rating( int $restaurant_id ): void {
		if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
			\VC\Utils\Rating_Helper::recalculate( $restaurant_id );
		} else {
			// Fallback se Rating_Helper não estiver disponível
			$reviews = get_posts( [
				'post_type'      => self::SLUG,
				'posts_per_page' => -1,
				'post_status'    => self::STATUS_APPROVED,
				'meta_query'     => [
					[
						'key'   => '_vc_restaurant_id',
						'value' => (string) $restaurant_id,
					],
				],
			] );

			$ratings = [];
			foreach ( $reviews as $review ) {
				$rating = (int) get_post_meta( $review->ID, '_vc_rating', true );
				if ( $rating >= 1 && $rating <= 5 ) {
					$ratings[] = $rating;
				}
			}

			$count = count( $ratings );
			$avg   = $count > 0 ? round( array_sum( $ratings ) / $count, 2 ) : 0.0;

			update_post_meta( $restaurant_id, '_vc_restaurant_rating_avg', $avg );
			update_post_meta( $restaurant_id, '_vc_restaurant_rating_count', $count );
			delete_transient( 'vc_restaurant_rating_' . $restaurant_id );
		}
	}

	public function admin_columns( array $columns ): array {
		$before = [
			'cb'    => $columns['cb'] ?? '',
			'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ),
		];
		$extra  = [
			'vc_restaurant' => __( 'Restaurante', 'vemcomer' ),
			'vc_customer'   => __( 'Cliente', 'vemcomer' ),
			'vc_rating'     => __( 'Avaliação', 'vemcomer' ),
			'vc_order'      => __( 'Pedido', 'vemcomer' ),
		];
		$rest   = $columns;
		unset( $rest['cb'], $rest['title'] );
		return array_merge( $before, $extra, $rest );
	}

	public function admin_column_values( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'vc_restaurant':
				$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
				if ( $restaurant_id > 0 ) {
					$restaurant = get_post( $restaurant_id );
					echo esc_html( $restaurant ? $restaurant->post_title : '—' );
				} else {
					echo '—';
				}
				break;

			case 'vc_customer':
				$customer_id = (int) get_post_meta( $post_id, '_vc_customer_id', true );
				if ( $customer_id > 0 ) {
					$customer = get_userdata( $customer_id );
					echo esc_html( $customer ? $customer->display_name : '—' );
				} else {
					echo '—';
				}
				break;

			case 'vc_rating':
				$rating = (int) get_post_meta( $post_id, '_vc_rating', true );
				if ( $rating >= 1 && $rating <= 5 ) {
					echo esc_html( str_repeat( '★', $rating ) . ' (' . $rating . '/5)' );
				} else {
					echo '—';
				}
				break;

			case 'vc_order':
				$order_id = (int) get_post_meta( $post_id, '_vc_order_id', true );
				echo esc_html( $order_id > 0 ? '#' . $order_id : '—' );
				break;
		}
	}

	public function grant_caps(): void {
		if ( ! function_exists( 'get_role' ) ) {
			return;
		}
		$all = array_values( $this->capabilities() );

		$admins = get_role( 'administrator' );
		$editor = get_role( 'editor' );
		$author = get_role( 'author' );
		$contrib = get_role( 'contributor' );

		foreach ( $all as $cap ) {
			if ( $admins && ! $admins->has_cap( $cap ) ) {
				$admins->add_cap( $cap );
			}
			if ( $editor && ! $editor->has_cap( $cap ) ) {
				$editor->add_cap( $cap );
			}
		}

		// Autores: sem "others" e sem deletar de outros
		$author_caps = [
			'edit_vc_review',
			'edit_vc_reviews',
			'publish_vc_reviews',
			'delete_vc_review',
			'delete_vc_reviews',
			'edit_published_vc_reviews',
			'delete_published_vc_reviews',
			'create_vc_reviews',
		];
		if ( $author ) {
			foreach ( $author_caps as $c ) {
				if ( ! $author->has_cap( $c ) ) {
					$author->add_cap( $c );
				}
			}
		}

		// Contribuidores: criar/editar não-publicado (sem publicar)
		$contrib_caps = [
			'edit_vc_review',
			'edit_vc_reviews',
			'create_vc_reviews',
		];
		if ( $contrib ) {
			foreach ( $contrib_caps as $c ) {
				if ( ! $contrib->has_cap( $c ) ) {
					$contrib->add_cap( $c );
				}
			}
		}
	}

	/**
	 * Invalida cache de rating ao salvar avaliação.
	 * 
	 * @param int      $post_id ID do post.
	 * @param \WP_Post|null $post   Objeto do post (pode ser null ao criar novo post).
	 * @param bool     $update  Se é atualização ou criação.
	 */
	public function invalidate_rating_cache_on_save( int $post_id, $post, bool $update ): void {
		// O WordPress pode passar null quando um post está sendo criado pela primeira vez
		if ( ! $post || ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		if ( self::SLUG !== $post->post_type ) {
			return;
		}

		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		if ( $restaurant_id > 0 && class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
			\VC\Utils\Rating_Helper::invalidate_cache( $restaurant_id );
		}
	}

	/**
	 * Invalida cache de rating ao deletar avaliação.
	 */
	public function invalidate_rating_cache_on_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || self::SLUG !== $post->post_type ) {
			return;
		}

		$restaurant_id = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
		if ( $restaurant_id > 0 && class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
			// Recalcular (não apenas invalidar) para atualizar meta fields
			\VC\Utils\Rating_Helper::recalculate( $restaurant_id );
		}
	}
}

