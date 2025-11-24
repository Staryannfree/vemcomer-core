<?php
/**
 * CPT_ProductModifier — Custom Post Type "Product Modifier" (Complementos/Modificadores)
 * + Capabilities customizadas e concessão por role (grant_caps).
 * + Relacionamento Many-to-Many com vc_menu_item via meta fields.
 *
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_ProductModifier {
	public const SLUG = 'vc_product_modifier';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
		add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
		add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
		add_action( 'init', [ $this, 'grant_caps' ], 5 );
		// Validação de permissão de plano
		add_filter( 'wp_insert_post_data', [ $this, 'check_permission_on_save' ], 10, 2 );
	}

	/**
	 * Verifica se o plano permite criar modificadores.
	 */
	public function check_permission_on_save( $data, $postarr ) {
		// Apenas para este CPT
		if ( $data['post_type'] !== self::SLUG ) {
			return $data;
		}

		// Se for administrador, libera tudo
		if ( current_user_can( 'manage_options' ) ) {
			return $data;
		}

		// Descobrir o restaurante (user_id do autor)
		$author_id = (int) $data['post_author'];
		
		// Busca restaurante(s) deste autor
		$restaurants = get_posts([
			'post_type' => 'vc_restaurant',
			'author'    => $author_id,
			'posts_per_page' => 1,
			'fields' => 'ids'
		]);

		if ( empty( $restaurants ) ) {
			return $data;
		}

		$restaurant_id = $restaurants[0];
		
		// Verificar se o plano permite modificadores
		if ( ! \VC\Subscription\Plan_Manager::can_use_modifiers( $restaurant_id ) ) {
			// Se não permitir, força Rascunho (não deixa publicar)
			if ( $data['post_status'] === 'publish' ) {
				$data['post_status'] = 'draft';
			}
		}

		return $data;
	}

	private function capabilities(): array {
		return [
			'edit_post'              => 'edit_vc_product_modifier',
			'read_post'              => 'read_vc_product_modifier',
			'delete_post'            => 'delete_vc_product_modifier',
			'edit_posts'             => 'edit_vc_product_modifiers',
			'edit_others_posts'      => 'edit_others_vc_product_modifiers',
			'publish_posts'          => 'publish_vc_product_modifiers',
			'read_private_posts'     => 'read_private_vc_product_modifiers',
			'delete_posts'           => 'delete_vc_product_modifiers',
			'delete_private_posts'   => 'delete_private_vc_product_modifiers',
			'delete_published_posts' => 'delete_published_vc_product_modifiers',
			'delete_others_posts'    => 'delete_others_vc_product_modifiers',
			'edit_private_posts'     => 'edit_private_vc_product_modifiers',
			'edit_published_posts'   => 'edit_published_vc_product_modifiers',
			'create_posts'           => 'create_vc_product_modifiers',
		];
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Complementos/Modificadores', 'vemcomer' ),
			'singular_name'         => __( 'Complemento/Modificador', 'vemcomer' ),
			'menu_name'             => __( 'Modificadores', 'vemcomer' ),
			'name_admin_bar'        => __( 'Modificador', 'vemcomer' ),
			'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar novo modificador', 'vemcomer' ),
			'new_item'              => __( 'Novo modificador', 'vemcomer' ),
			'edit_item'             => __( 'Editar modificador', 'vemcomer' ),
			'view_item'             => __( 'Ver modificador', 'vemcomer' ),
			'all_items'             => __( 'Todos os modificadores', 'vemcomer' ),
			'search_items'          => __( 'Buscar modificadores', 'vemcomer' ),
			'not_found'             => __( 'Nenhum modificador encontrado.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhum modificador na lixeira.', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false, // Não é público, apenas admin
			'show_ui'         => true,
			'show_in_menu'    => false, // Será adicionado ao menu do VemComer
			'show_in_rest'    => true,
			'supports'        => [ 'title', 'editor' ],
			'capability_type' => [ 'vc_product_modifier', 'vc_product_modifiers' ],
			'map_meta_cap'    => true,
			'capabilities'    => $this->capabilities(),
		];
		register_post_type( self::SLUG, $args );
	}

	public function register_metaboxes(): void {
		add_meta_box(
			'vc_product_modifier_meta',
			__( 'Dados do Modificador', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_product_modifier_meta_nonce', 'vc_product_modifier_meta_nonce_field' );

		$type      = get_post_meta( $post->ID, '_vc_modifier_type', true );
		$price     = get_post_meta( $post->ID, '_vc_modifier_price', true );
		$min       = get_post_meta( $post->ID, '_vc_modifier_min', true );
		$max       = get_post_meta( $post->ID, '_vc_modifier_max', true );
		$menu_item_ids = get_post_meta( $post->ID, '_vc_modifier_menu_items', true );
		$menu_item_ids = is_array( $menu_item_ids ) ? $menu_item_ids : [];

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_modifier_type"><?php echo esc_html__( 'Tipo', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_modifier_type" name="vc_modifier_type" class="regular-text">
						<option value="optional" <?php selected( $type, 'optional' ); ?>><?php echo esc_html__( 'Opcional', 'vemcomer' ); ?></option>
						<option value="required" <?php selected( $type, 'required' ); ?>><?php echo esc_html__( 'Obrigatório', 'vemcomer' ); ?></option>
					</select>
					<p class="description"><?php echo esc_html__( 'Se o modificador é obrigatório ou opcional para o cliente selecionar.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_modifier_price"><?php echo esc_html__( 'Preço (R$)', 'vemcomer' ); ?></label></th>
				<td>
					<input type="text" id="vc_modifier_price" name="vc_modifier_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" placeholder="0.00" />
					<p class="description"><?php echo esc_html__( 'Preço adicional do modificador. Deixe vazio para gratuito.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_modifier_min"><?php echo esc_html__( 'Mínimo de seleção', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_modifier_min" name="vc_modifier_min" value="<?php echo esc_attr( $min ); ?>" class="small-text" min="0" />
					<p class="description"><?php echo esc_html__( 'Número mínimo de opções que o cliente deve selecionar.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_modifier_max"><?php echo esc_html__( 'Máximo de seleção', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_modifier_max" name="vc_modifier_max" value="<?php echo esc_attr( $max ); ?>" class="small-text" min="0" />
					<p class="description"><?php echo esc_html__( 'Número máximo de opções que o cliente pode selecionar. Deixe vazio para ilimitado.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php echo esc_html__( 'Itens do Cardápio', 'vemcomer' ); ?></label></th>
				<td>
					<?php
					$menu_items = get_posts( [
						'post_type'      => 'vc_menu_item',
						'posts_per_page' => -1,
						'post_status'    => 'any',
						'orderby'        => 'title',
						'order'          => 'ASC',
					] );

					if ( ! empty( $menu_items ) ) {
						foreach ( $menu_items as $item ) {
							$checked = in_array( (string) $item->ID, $menu_item_ids, true ) ? 'checked' : '';
							?>
							<label style="display: block; margin-bottom: 5px;">
								<input type="checkbox" name="vc_modifier_menu_items[]" value="<?php echo esc_attr( (string) $item->ID ); ?>" <?php echo esc_attr( $checked ); ?> />
								<?php echo esc_html( $item->post_title ); ?>
							</label>
							<?php
						}
					} else {
						echo '<p>' . esc_html__( 'Nenhum item do cardápio encontrado.', 'vemcomer' ) . '</p>';
					}
					?>
					<p class="description"><?php echo esc_html__( 'Selecione os itens do cardápio que podem usar este modificador.', 'vemcomer' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta( int $post_id ): void {
		// Verificar nonce
		if ( ! isset( $_POST['vc_product_modifier_meta_nonce_field'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['vc_product_modifier_meta_nonce_field'], 'vc_product_modifier_meta_nonce' ) ) {
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

		// Salvar tipo
		$type = isset( $_POST['vc_modifier_type'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_modifier_type'] ) ) : 'optional';
		if ( in_array( $type, [ 'required', 'optional' ], true ) ) {
			update_post_meta( $post_id, '_vc_modifier_type', $type );
		}

		// Salvar preço
		$price = isset( $_POST['vc_modifier_price'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_modifier_price'] ) ) : '';
		if ( $price !== '' ) {
			$price_float = (float) str_replace( ',', '.', $price );
			update_post_meta( $post_id, '_vc_modifier_price', $price_float >= 0 ? (string) $price_float : '0' );
		} else {
			update_post_meta( $post_id, '_vc_modifier_price', '0' );
		}

		// Salvar mínimo
		$min = isset( $_POST['vc_modifier_min'] ) ? (int) $_POST['vc_modifier_min'] : 0;
		update_post_meta( $post_id, '_vc_modifier_min', max( 0, $min ) );

		// Salvar máximo
		$max = isset( $_POST['vc_modifier_max'] ) ? (int) $_POST['vc_modifier_max'] : 0;
		$max = $max > 0 ? $max : 0; // 0 = ilimitado
		update_post_meta( $post_id, '_vc_modifier_max', $max );

		// Validar: mínimo <= máximo (se máximo > 0)
		$min_saved = (int) get_post_meta( $post_id, '_vc_modifier_min', true );
		$max_saved = (int) get_post_meta( $post_id, '_vc_modifier_max', true );
		if ( $max_saved > 0 && $min_saved > $max_saved ) {
			// Ajustar mínimo para não exceder máximo
			update_post_meta( $post_id, '_vc_modifier_min', $max_saved );
		}

		// Salvar relacionamento Many-to-Many com itens do cardápio
		$menu_item_ids = isset( $_POST['vc_modifier_menu_items'] ) && is_array( $_POST['vc_modifier_menu_items'] )
			? array_map( 'intval', $_POST['vc_modifier_menu_items'] )
			: [];
		update_post_meta( $post_id, '_vc_modifier_menu_items', $menu_item_ids );

		// Atualizar meta reversa nos itens do cardápio (para facilitar queries)
		// Primeiro, remover este modificador de todos os itens
		$all_menu_items = get_posts( [
			'post_type'      => 'vc_menu_item',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		foreach ( $all_menu_items as $item_id ) {
			$modifiers = get_post_meta( $item_id, '_vc_menu_item_modifiers', true );
			$modifiers = is_array( $modifiers ) ? $modifiers : [];
			$key       = array_search( $post_id, $modifiers, true );
			if ( $key !== false ) {
				unset( $modifiers[ $key ] );
				$modifiers = array_values( $modifiers ); // Reindexar
				update_post_meta( $item_id, '_vc_menu_item_modifiers', $modifiers );
			}
		}

		// Agora adicionar este modificador aos itens selecionados
		foreach ( $menu_item_ids as $item_id ) {
			$modifiers = get_post_meta( $item_id, '_vc_menu_item_modifiers', true );
			$modifiers = is_array( $modifiers ) ? $modifiers : [];
			if ( ! in_array( $post_id, $modifiers, true ) ) {
				$modifiers[] = $post_id;
				update_post_meta( $item_id, '_vc_menu_item_modifiers', $modifiers );
			}
		}
	}

	public function admin_columns( array $columns ): array {
		$before = [
			'cb'    => $columns['cb'] ?? '',
			'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ),
		];
		$extra  = [
			'vc_type'      => __( 'Tipo', 'vemcomer' ),
			'vc_price'     => __( 'Preço', 'vemcomer' ),
			'vc_min_max'   => __( 'Min/Max', 'vemcomer' ),
			'vc_menu_items' => __( 'Itens', 'vemcomer' ),
		];
		$rest   = $columns;
		unset( $rest['cb'], $rest['title'] );
		return array_merge( $before, $extra, $rest );
	}

	public function admin_column_values( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'vc_type':
				$type = get_post_meta( $post_id, '_vc_modifier_type', true );
				echo esc_html( 'required' === $type ? __( 'Obrigatório', 'vemcomer' ) : __( 'Opcional', 'vemcomer' ) );
				break;

			case 'vc_price':
				$price = get_post_meta( $post_id, '_vc_modifier_price', true );
				echo esc_html( $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : __( 'Grátis', 'vemcomer' ) );
				break;

			case 'vc_min_max':
				$min = (int) get_post_meta( $post_id, '_vc_modifier_min', true );
				$max = (int) get_post_meta( $post_id, '_vc_modifier_max', true );
				$max_display = $max > 0 ? (string) $max : __( '∞', 'vemcomer' );
				echo esc_html( $min . ' / ' . $max_display );
				break;

			case 'vc_menu_items':
				$menu_item_ids = get_post_meta( $post_id, '_vc_modifier_menu_items', true );
				$menu_item_ids = is_array( $menu_item_ids ) ? $menu_item_ids : [];
				$count         = count( $menu_item_ids );
				echo esc_html( $count > 0 ? (string) $count : '—' );
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
			'edit_vc_product_modifier',
			'edit_vc_product_modifiers',
			'publish_vc_product_modifiers',
			'delete_vc_product_modifier',
			'delete_vc_product_modifiers',
			'edit_published_vc_product_modifiers',
			'delete_published_vc_product_modifiers',
			'create_vc_product_modifiers',
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
			'edit_vc_product_modifier',
			'edit_vc_product_modifiers',
			'create_vc_product_modifiers',
		];
		if ( $contrib ) {
			foreach ( $contrib_caps as $c ) {
				if ( ! $contrib->has_cap( $c ) ) {
					$contrib->add_cap( $c );
				}
			}
		}
	}
}

