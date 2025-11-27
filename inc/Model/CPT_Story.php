<?php
/**
 * CPT_Story — Custom Post Type "Story" (Stories estilo Instagram)
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Story {
	public const SLUG = 'vc_story';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
		add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
		add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Stories', 'vemcomer' ),
			'singular_name'         => __( 'Story', 'vemcomer' ),
			'menu_name'             => __( 'Stories', 'vemcomer' ),
			'name_admin_bar'        => __( 'Story', 'vemcomer' ),
			'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar novo story', 'vemcomer' ),
			'new_item'              => __( 'Novo story', 'vemcomer' ),
			'edit_item'             => __( 'Editar story', 'vemcomer' ),
			'view_item'             => __( 'Ver story', 'vemcomer' ),
			'all_items'             => __( 'Todos os stories', 'vemcomer' ),
			'search_items'          => __( 'Buscar stories', 'vemcomer' ),
			'not_found'             => __( 'Nenhum story encontrado.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhum story na lixeira.', 'vemcomer' ),
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
			'vc_story_meta',
			__( 'Dados do Story', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_story_meta_nonce', 'vc_story_meta_nonce_field' );

		$restaurant_id = (int) get_post_meta( $post->ID, '_vc_story_restaurant_id', true );
		$type          = (string) get_post_meta( $post->ID, '_vc_story_type', true );
		if ( empty( $type ) ) {
			$type = 'image';
		}
		$duration = (int) get_post_meta( $post->ID, '_vc_story_duration', true );
		if ( $duration <= 0 ) {
			$duration = 5000; // Padrão: 5 segundos
		}
		$order   = (int) get_post_meta( $post->ID, '_vc_story_order', true );
		$active  = (bool) get_post_meta( $post->ID, '_vc_story_active', true );
		$link_type = (string) get_post_meta( $post->ID, '_vc_story_link_type', true );
		if ( empty( $link_type ) ) {
			$link_type = 'none'; // Padrão: sem link
		}

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_story_restaurant_id"><?php echo esc_html__( 'Restaurante', 'vemcomer' ); ?> <span style="color: red;">*</span></label></th>
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
					<select id="vc_story_restaurant_id" name="vc_story_restaurant_id" class="regular-text" required>
						<option value=""><?php echo esc_html__( '— Selecione —', 'vemcomer' ); ?></option>
						<?php foreach ( $restaurants as $restaurant ) : ?>
							<option value="<?php echo esc_attr( (string) $restaurant->ID ); ?>" <?php selected( $restaurant_id, $restaurant->ID ); ?>>
								<?php echo esc_html( $restaurant->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo esc_html__( 'Restaurante dono deste story.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_story_type"><?php echo esc_html__( 'Tipo', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_story_type" name="vc_story_type" class="regular-text">
						<option value="image" <?php selected( $type, 'image' ); ?>><?php echo esc_html__( 'Imagem', 'vemcomer' ); ?></option>
						<option value="video" <?php selected( $type, 'video' ); ?>><?php echo esc_html__( 'Vídeo', 'vemcomer' ); ?></option>
					</select>
					<p class="description"><?php echo esc_html__( 'Tipo de mídia do story. Use a imagem destacada para upload.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_story_duration"><?php echo esc_html__( 'Duração (ms)', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_story_duration" name="vc_story_duration" class="small-text" value="<?php echo esc_attr( (string) $duration ); ?>" min="1000" max="30000" step="500" />
					<p class="description"><?php echo esc_html__( 'Duração em milissegundos (1000 = 1 segundo). Padrão: 5000ms (5 segundos).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_story_order"><?php echo esc_html__( 'Ordem', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_story_order" name="vc_story_order" class="small-text" value="<?php echo esc_attr( $order > 0 ? (string) $order : '0' ); ?>" min="0" />
					<p class="description"><?php echo esc_html__( 'Ordem de exibição dentro do grupo do restaurante (menor número aparece primeiro).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_story_link_type"><?php echo esc_html__( 'Ação do Botão CTA', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_story_link_type" name="vc_story_link_type" class="regular-text">
						<option value="none" <?php selected( $link_type, 'none' ); ?>><?php echo esc_html__( '— Sem botão —', 'vemcomer' ); ?></option>
						<option value="profile" <?php selected( $link_type, 'profile' ); ?>><?php echo esc_html__( 'Perfil do Restaurante', 'vemcomer' ); ?></option>
						<option value="menu" <?php selected( $link_type, 'menu' ); ?>><?php echo esc_html__( 'Cardápio', 'vemcomer' ); ?></option>
					</select>
					<p class="description"><?php echo esc_html__( 'Ação ao clicar no botão CTA do story. "Cardápio" mostrará uma lista do cardápio do restaurante para o usuário escolher um item.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php echo esc_html__( 'Formato da Imagem', 'vemcomer' ); ?></label></th>
				<td>
					<p class="description" style="color: #d63638; font-weight: 600;">
						⚠️ <?php echo esc_html__( 'IMPORTANTE: A imagem deve ser retangular (formato vertical/telefone), proporção aproximada 9:16.', 'vemcomer' ); ?>
					</p>
					<p class="description">
						<?php echo esc_html__( 'Recomendado: 1080x1920px ou proporção similar. Imagens horizontais serão cortadas automaticamente.', 'vemcomer' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_story_active"><?php echo esc_html__( 'Ativo', 'vemcomer' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="vc_story_active" name="vc_story_active" value="1" <?php checked( $active ); ?> />
						<?php echo esc_html__( 'Story está ativo e será exibido', 'vemcomer' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['vc_story_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_story_meta_nonce_field'], 'vc_story_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$restaurant_id = isset( $_POST['vc_story_restaurant_id'] ) ? (int) $_POST['vc_story_restaurant_id'] : 0;
		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_story_restaurant_id', $restaurant_id );
		} else {
			delete_post_meta( $post_id, '_vc_story_restaurant_id' );
		}

		$type = isset( $_POST['vc_story_type'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_story_type'] ) ) : 'image';
		$allowed_types = [ 'image', 'video' ];
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'image';
		}
		update_post_meta( $post_id, '_vc_story_type', $type );

		$duration = isset( $_POST['vc_story_duration'] ) ? (int) $_POST['vc_story_duration'] : 5000;
		if ( $duration < 1000 ) {
			$duration = 1000;
		}
		if ( $duration > 30000 ) {
			$duration = 30000;
		}
		update_post_meta( $post_id, '_vc_story_duration', $duration );

		$order = isset( $_POST['vc_story_order'] ) ? (int) $_POST['vc_story_order'] : 0;
		update_post_meta( $post_id, '_vc_story_order', $order );

		$link = isset( $_POST['vc_story_link'] ) ? esc_url_raw( wp_unslash( $_POST['vc_story_link'] ) ) : '';
		update_post_meta( $post_id, '_vc_story_link', $link );

		$link_text = isset( $_POST['vc_story_link_text'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_story_link_text'] ) ) : __( 'Ver Cardápio', 'vemcomer' );
		update_post_meta( $post_id, '_vc_story_link_text', $link_text );

		$active = isset( $_POST['vc_story_active'] ) && '1' === $_POST['vc_story_active'];
		update_post_meta( $post_id, '_vc_story_active', $active ? '1' : '0' );
	}

	public function admin_columns( array $columns ): array {
		$before = [
			'cb'    => $columns['cb'] ?? '',
			'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ),
		];
		$extra  = [
			'vc_image'      => __( 'Mídia', 'vemcomer' ),
			'vc_restaurant' => __( 'Restaurante', 'vemcomer' ),
			'vc_type'       => __( 'Tipo', 'vemcomer' ),
			'vc_duration'   => __( 'Duração', 'vemcomer' ),
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

			case 'vc_restaurant':
				$restaurant_id = (int) get_post_meta( $post_id, '_vc_story_restaurant_id', true );
				if ( $restaurant_id > 0 ) {
					$restaurant = get_post( $restaurant_id );
					if ( $restaurant ) {
						echo '<a href="' . esc_url( get_edit_post_link( $restaurant_id ) ) . '">' . esc_html( $restaurant->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '<span style="color: red;">—</span>';
				}
				break;

			case 'vc_type':
				$type = (string) get_post_meta( $post_id, '_vc_story_type', true );
				if ( empty( $type ) ) {
					$type = 'image';
				}
				$type_labels = [
					'image' => __( 'Imagem', 'vemcomer' ),
					'video' => __( 'Vídeo', 'vemcomer' ),
				];
				echo esc_html( $type_labels[ $type ] ?? __( 'Imagem', 'vemcomer' ) );
				break;

			case 'vc_duration':
				$duration = (int) get_post_meta( $post_id, '_vc_story_duration', true );
				if ( $duration <= 0 ) {
					$duration = 5000;
				}
				echo esc_html( (string) ( $duration / 1000 ) . 's' );
				break;

			case 'vc_order':
				$order = (int) get_post_meta( $post_id, '_vc_story_order', true );
				echo esc_html( (string) $order );
				break;

			case 'vc_active':
				$active = (bool) get_post_meta( $post_id, '_vc_story_active', true );
				echo $active ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
				break;
		}
	}

	/**
	 * Valida e ajusta imagem do story para formato retangular (vertical)
	 */
	private function validate_story_image( int $attachment_id ): void {
		$image_path = get_attached_file( $attachment_id );
		if ( ! $image_path || ! file_exists( $image_path ) ) {
			return;
		}

		$image_meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! $image_meta || ! isset( $image_meta['width'] ) || ! isset( $image_meta['height'] ) ) {
			return;
		}

		$width = $image_meta['width'];
		$height = $image_meta['height'];

		// Calcular proporção
		$ratio = $width / $height;

		// Proporção ideal para stories: ~0.5625 (9:16) ou mais vertical
		// Se a imagem for muito horizontal (ratio > 0.7), avisar
		if ( $ratio > 0.7 ) {
			// Adicionar notice para o admin
			add_action( 'admin_notices', function() use ( $ratio ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p>
						<strong><?php echo esc_html__( 'Aviso Story:', 'vemcomer' ); ?></strong>
						<?php 
						printf(
							esc_html__( 'A imagem do story não está no formato ideal (retangular vertical). Proporção atual: %.2f. Recomendado: 0.56 (9:16) ou mais vertical.', 'vemcomer' ),
							$ratio
						);
						?>
					</p>
				</div>
				<?php
			} );
		}
	}
}

