<?php
/**
 * CPT_Banner — Custom Post Type "Banner" (Banners da Home)
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Banner {
	public const SLUG = 'vc_banner';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
		add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
		add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_action' ], 10, 2 );
		add_action( 'admin_action_vc_duplicate_banner', [ $this, 'duplicate_banner' ] );
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Banners', 'vemcomer' ),
			'singular_name'         => __( 'Banner', 'vemcomer' ),
			'menu_name'             => __( 'Banners', 'vemcomer' ),
			'name_admin_bar'        => __( 'Banner', 'vemcomer' ),
			'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar novo banner', 'vemcomer' ),
			'new_item'              => __( 'Novo banner', 'vemcomer' ),
			'edit_item'             => __( 'Editar banner', 'vemcomer' ),
			'view_item'             => __( 'Ver banner', 'vemcomer' ),
			'all_items'             => __( 'Todos os banners', 'vemcomer' ),
			'search_items'          => __( 'Buscar banners', 'vemcomer' ),
			'not_found'             => __( 'Nenhum banner encontrado.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhum banner na lixeira.', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => [ 'title', 'thumbnail' ],
			'capability_type' => 'post',
		];
		register_post_type( self::SLUG, $args );
	}

	public function register_metaboxes(): void {
		add_meta_box(
			'vc_banner_meta',
			__( 'Dados do Banner', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_banner_meta_nonce', 'vc_banner_meta_nonce_field' );

		$link            = (string) get_post_meta( $post->ID, '_vc_banner_link', true );
		$restaurant_id   = (int) get_post_meta( $post->ID, '_vc_banner_restaurant_id', true );
		$order           = (int) get_post_meta( $post->ID, '_vc_banner_order', true );
		$active          = (bool) get_post_meta( $post->ID, '_vc_banner_active', true );
		$size            = (string) get_post_meta( $post->ID, '_vc_banner_size', true );
		if ( empty( $size ) ) {
			$size = 'medium'; // Tamanho padrão
		}

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_banner_link"><?php echo esc_html__( 'Link', 'vemcomer' ); ?></label></th>
				<td>
					<input type="url" id="vc_banner_link" name="vc_banner_link" class="regular-text" value="<?php echo esc_attr( $link ); ?>" />
					<p class="description"><?php echo esc_html__( 'URL de destino ao clicar no banner.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_banner_restaurant_id"><?php echo esc_html__( 'Restaurante (opcional)', 'vemcomer' ); ?></label></th>
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
					<select id="vc_banner_restaurant_id" name="vc_banner_restaurant_id" class="regular-text">
						<option value=""><?php echo esc_html__( '— Nenhum —', 'vemcomer' ); ?></option>
						<?php foreach ( $restaurants as $restaurant ) : ?>
							<option value="<?php echo esc_attr( (string) $restaurant->ID ); ?>" <?php selected( $restaurant_id, $restaurant->ID ); ?>>
								<?php echo esc_html( $restaurant->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo esc_html__( 'Se selecionado, o banner será vinculado a este restaurante.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_banner_order"><?php echo esc_html__( 'Ordem', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_banner_order" name="vc_banner_order" class="small-text" value="<?php echo esc_attr( $order > 0 ? (string) $order : '0' ); ?>" min="0" />
					<p class="description"><?php echo esc_html__( 'Ordem de exibição (menor número aparece primeiro).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_banner_size"><?php echo esc_html__( 'Tamanho do Banner', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_banner_size" name="vc_banner_size" class="regular-text">
						<option value="small" <?php selected( $size, 'small' ); ?>><?php echo esc_html__( 'Pequeno (1/4 da largura)', 'vemcomer' ); ?></option>
						<option value="medium" <?php selected( $size, 'medium' ); ?>><?php echo esc_html__( 'Médio (1/2 da largura)', 'vemcomer' ); ?></option>
						<option value="large" <?php selected( $size, 'large' ); ?>><?php echo esc_html__( 'Grande (3/4 da largura)', 'vemcomer' ); ?></option>
						<option value="full" <?php selected( $size, 'full' ); ?>><?php echo esc_html__( 'Largura Total (100%)', 'vemcomer' ); ?></option>
					</select>
					<p class="description"><?php echo esc_html__( 'Escolha o tamanho de exibição do banner. Isso evita banners muito grandes.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_banner_active"><?php echo esc_html__( 'Ativo', 'vemcomer' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="vc_banner_active" name="vc_banner_active" value="1" <?php checked( $active ); ?> />
						<?php echo esc_html__( 'Banner está ativo e será exibido', 'vemcomer' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['vc_banner_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_banner_meta_nonce_field'], 'vc_banner_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$link = isset( $_POST['vc_banner_link'] ) ? esc_url_raw( wp_unslash( $_POST['vc_banner_link'] ) ) : '';
		update_post_meta( $post_id, '_vc_banner_link', $link );

		$restaurant_id = isset( $_POST['vc_banner_restaurant_id'] ) ? (int) $_POST['vc_banner_restaurant_id'] : 0;
		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_banner_restaurant_id', $restaurant_id );
		} else {
			delete_post_meta( $post_id, '_vc_banner_restaurant_id' );
		}

		$order = isset( $_POST['vc_banner_order'] ) ? (int) $_POST['vc_banner_order'] : 0;
		update_post_meta( $post_id, '_vc_banner_order', $order );

		$size = isset( $_POST['vc_banner_size'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_banner_size'] ) ) : 'medium';
		$allowed_sizes = [ 'small', 'medium', 'large', 'full' ];
		if ( ! in_array( $size, $allowed_sizes, true ) ) {
			$size = 'medium';
		}
		update_post_meta( $post_id, '_vc_banner_size', $size );

		$active = isset( $_POST['vc_banner_active'] ) && '1' === $_POST['vc_banner_active'];
		update_post_meta( $post_id, '_vc_banner_active', $active ? '1' : '0' );
	}

	public function admin_columns( array $columns ): array {
		$before = [
			'cb'    => $columns['cb'] ?? '',
			'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ),
		];
		$extra  = [
			'vc_image'      => __( 'Imagem', 'vemcomer' ),
			'vc_link'       => __( 'Link', 'vemcomer' ),
			'vc_restaurant' => __( 'Restaurante', 'vemcomer' ),
			'vc_size'       => __( 'Tamanho', 'vemcomer' ),
			'vc_order'      => __( 'Ordem', 'vemcomer' ),
			'vc_active'     => __( 'Ativo', 'vemcomer' ),
		];
		$rest   = $columns;
		unset( $rest['cb'], $rest['title'] );
		return array_merge( $before, $extra, $rest );
	}

	public function admin_column_values( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'vc_image':
				$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
				echo $thumbnail ? $thumbnail : '—';
				break;

			case 'vc_link':
				$link = (string) get_post_meta( $post_id, '_vc_banner_link', true );
				echo $link ? '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a>' : '—';
				break;

			case 'vc_restaurant':
				$restaurant_id = (int) get_post_meta( $post_id, '_vc_banner_restaurant_id', true );
				if ( $restaurant_id > 0 ) {
					$restaurant = get_post( $restaurant_id );
					echo esc_html( $restaurant ? $restaurant->post_title : '—' );
				} else {
					echo '—';
				}
				break;

			case 'vc_size':
				$size = (string) get_post_meta( $post_id, '_vc_banner_size', true );
				if ( empty( $size ) ) {
					$size = 'medium';
				}
				$size_labels = [
					'small'  => __( 'Pequeno', 'vemcomer' ),
					'medium' => __( 'Médio', 'vemcomer' ),
					'large'  => __( 'Grande', 'vemcomer' ),
					'full'   => __( 'Largura Total', 'vemcomer' ),
				];
				echo esc_html( $size_labels[ $size ] ?? __( 'Médio', 'vemcomer' ) );
				break;

			case 'vc_order':
				$order = (int) get_post_meta( $post_id, '_vc_banner_order', true );
				echo esc_html( (string) $order );
				break;

			case 'vc_active':
				$active = (bool) get_post_meta( $post_id, '_vc_banner_active', true );
				echo $active ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
				break;
		}
	}

	/**
	 * Adiciona ação "Duplicar" na lista de banners
	 */
	public function add_duplicate_action( array $actions, \WP_Post $post ): array {
		if ( self::SLUG !== $post->post_type ) {
			return $actions;
		}

		$duplicate_url = wp_nonce_url(
			admin_url( 'admin.php?action=vc_duplicate_banner&post=' . $post->ID ),
			'vc_duplicate_banner_' . $post->ID,
			'vc_duplicate_nonce'
		);

		$actions['duplicate'] = '<a href="' . esc_url( $duplicate_url ) . '" title="' . esc_attr__( 'Duplicar este banner', 'vemcomer' ) . '">' . esc_html__( 'Duplicar', 'vemcomer' ) . '</a>';

		return $actions;
	}

	/**
	 * Duplica um banner
	 */
	public function duplicate_banner(): void {
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['vc_duplicate_nonce'] ) ) {
			wp_die( esc_html__( 'Parâmetros inválidos.', 'vemcomer' ) );
		}

		$post_id = (int) $_GET['post'];
		$nonce = sanitize_text_field( wp_unslash( $_GET['vc_duplicate_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'vc_duplicate_banner_' . $post_id ) ) {
			wp_die( esc_html__( 'Verificação de segurança falhou.', 'vemcomer' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para duplicar banners.', 'vemcomer' ) );
		}

		$original_post = get_post( $post_id );
		if ( ! $original_post || self::SLUG !== $original_post->post_type ) {
			wp_die( esc_html__( 'Banner não encontrado.', 'vemcomer' ) );
		}

		// Criar novo post
		$new_post_data = [
			'post_title'   => $original_post->post_title . ' (Cópia)',
			'post_content' => $original_post->post_content,
			'post_status'  => 'draft', // Criar como rascunho para revisão
			'post_type'    => self::SLUG,
		];

		$new_post_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html__( 'Erro ao duplicar banner.', 'vemcomer' ) );
		}

		// Copiar thumbnail
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}

		// Copiar meta fields
		$meta_keys = [
			'_vc_banner_link',
			'_vc_banner_restaurant_id',
			'_vc_banner_order',
			'_vc_banner_size',
			'_vc_banner_active',
		];

		foreach ( $meta_keys as $meta_key ) {
			$meta_value = get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $meta_value ) {
				update_post_meta( $new_post_id, $meta_key, $meta_value );
			}
		}

		// Redirecionar para edição do novo banner
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}
}

