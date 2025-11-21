<?php
/**
 * Modifiers_Metabox — Metabox para gerenciar modificadores nos itens do cardápio
 * @package VemComerCore
 */

namespace VC\Admin;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_ProductModifier;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Modifiers_Metabox {
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'save_post_' . CPT_MenuItem::SLUG, [ $this, 'save_modifiers' ] );
	}

	public function register_metabox(): void {
		add_meta_box(
			'vc_menu_item_modifiers',
			__( 'Modificadores/Complementos', 'vemcomer' ),
			[ $this, 'render' ],
			CPT_MenuItem::SLUG,
			'normal',
			'default'
		);
	}

	public function enqueue_assets( string $hook ): void {
		global $post_type;

		if ( CPT_MenuItem::SLUG !== $post_type || ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		// jQuery UI Sortable já vem com WordPress
		wp_enqueue_script( 'jquery-ui-sortable' );

		// CSS customizado
		wp_add_inline_style( 'wp-admin', $this->get_inline_css() );

		// JavaScript customizado
		wp_add_inline_script( 'jquery-ui-sortable', $this->get_inline_js() );
	}

	public function render( $post ): void {
		wp_nonce_field( 'vc_menu_item_modifiers_nonce', 'vc_menu_item_modifiers_nonce_field' );

		// Buscar modificadores vinculados a este item
		$modifier_ids = get_post_meta( $post->ID, '_vc_menu_item_modifiers', true );
		$modifier_ids = is_array( $modifier_ids ) ? array_map( 'absint', $modifier_ids ) : [];

		// Buscar todos os modificadores disponíveis
		$all_modifiers = get_posts( [
			'post_type'      => CPT_ProductModifier::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		// Separar modificadores vinculados e não vinculados
		$linked_modifiers   = [];
		$unlinked_modifiers = [];

		foreach ( $all_modifiers as $modifier ) {
			$modifier_data = [
				'id'          => $modifier->ID,
				'title'       => $modifier->post_title,
				'type'        => get_post_meta( $modifier->ID, '_vc_modifier_type', true ) ?: 'optional',
				'price'       => (float) get_post_meta( $modifier->ID, '_vc_modifier_price', true ),
				'min'         => (int) get_post_meta( $modifier->ID, '_vc_modifier_min', true ),
				'max'         => (int) get_post_meta( $modifier->ID, '_vc_modifier_max', true ),
			];

			if ( in_array( $modifier->ID, $modifier_ids, true ) ) {
				$linked_modifiers[] = $modifier_data;
			} else {
				$unlinked_modifiers[] = $modifier_data;
			}
		}

		// Ordenar modificadores vinculados pela ordem salva
		$ordered_linked = [];
		foreach ( $modifier_ids as $id ) {
			foreach ( $linked_modifiers as $mod ) {
				if ( $mod['id'] === $id ) {
					$ordered_linked[] = $mod;
					break;
				}
			}
		}
		// Adicionar qualquer modificador vinculado que não esteja na ordem salva
		foreach ( $linked_modifiers as $mod ) {
			if ( ! in_array( $mod['id'], $modifier_ids, true ) ) {
				$ordered_linked[] = $mod;
			}
		}
		$linked_modifiers = $ordered_linked;

		?>
		<div class="vc-modifiers-metabox">
			<p class="description">
				<?php echo esc_html__( 'Arraste os modificadores para reordená-los. Os modificadores vinculados aparecerão no modal do produto no frontend.', 'vemcomer' ); ?>
			</p>

			<div class="vc-modifiers-section">
				<h4><?php echo esc_html__( 'Modificadores Vinculados', 'vemcomer' ); ?></h4>
				<ul id="vc-modifiers-linked" class="vc-modifiers-list">
					<?php foreach ( $linked_modifiers as $modifier ) : ?>
						<li class="vc-modifier-item" data-id="<?php echo esc_attr( (string) $modifier['id'] ); ?>">
							<span class="vc-modifier-handle dashicons dashicons-menu"></span>
							<span class="vc-modifier-title"><?php echo esc_html( $modifier['title'] ); ?></span>
							<span class="vc-modifier-badge vc-modifier-type-<?php echo esc_attr( $modifier['type'] ); ?>">
								<?php echo esc_html( 'required' === $modifier['type'] ? __( 'Obrigatório', 'vemcomer' ) : __( 'Opcional', 'vemcomer' ) ); ?>
							</span>
							<span class="vc-modifier-price">
								<?php
								if ( $modifier['price'] > 0 ) {
									echo esc_html( 'R$ ' . number_format( $modifier['price'], 2, ',', '.' ) );
								} else {
									echo esc_html__( 'Grátis', 'vemcomer' );
								}
								?>
							</span>
							<span class="vc-modifier-minmax">
								<?php
								$max_display = $modifier['max'] > 0 ? (string) $modifier['max'] : '∞';
								echo esc_html( $modifier['min'] . ' / ' . $max_display );
								?>
							</span>
							<button type="button" class="button-link vc-modifier-remove" data-id="<?php echo esc_attr( (string) $modifier['id'] ); ?>">
								<span class="dashicons dashicons-dismiss"></span>
							</button>
							<input type="hidden" name="vc_menu_item_modifiers[]" value="<?php echo esc_attr( (string) $modifier['id'] ); ?>" />
						</li>
					<?php endforeach; ?>
					<?php if ( empty( $linked_modifiers ) ) : ?>
						<li class="vc-modifier-empty">
							<?php echo esc_html__( 'Nenhum modificador vinculado. Use a lista abaixo para adicionar.', 'vemcomer' ); ?>
						</li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="vc-modifiers-section">
				<h4><?php echo esc_html__( 'Modificadores Disponíveis', 'vemcomer' ); ?></h4>
				<ul id="vc-modifiers-available" class="vc-modifiers-list">
					<?php foreach ( $unlinked_modifiers as $modifier ) : ?>
						<li class="vc-modifier-item" data-id="<?php echo esc_attr( (string) $modifier['id'] ); ?>">
							<span class="vc-modifier-title"><?php echo esc_html( $modifier['title'] ); ?></span>
							<span class="vc-modifier-badge vc-modifier-type-<?php echo esc_attr( $modifier['type'] ); ?>">
								<?php echo esc_html( 'required' === $modifier['type'] ? __( 'Obrigatório', 'vemcomer' ) : __( 'Opcional', 'vemcomer' ) ); ?>
							</span>
							<span class="vc-modifier-price">
								<?php
								if ( $modifier['price'] > 0 ) {
									echo esc_html( 'R$ ' . number_format( $modifier['price'], 2, ',', '.' ) );
								} else {
									echo esc_html__( 'Grátis', 'vemcomer' );
								}
								?>
							</span>
							<span class="vc-modifier-minmax">
								<?php
								$max_display = $modifier['max'] > 0 ? (string) $modifier['max'] : '∞';
								echo esc_html( $modifier['min'] . ' / ' . $max_display );
								?>
							</span>
							<button type="button" class="button vc-modifier-add" data-id="<?php echo esc_attr( (string) $modifier['id'] ); ?>">
								<?php echo esc_html__( 'Adicionar', 'vemcomer' ); ?>
							</button>
						</li>
					<?php endforeach; ?>
					<?php if ( empty( $unlinked_modifiers ) ) : ?>
						<li class="vc-modifier-empty">
							<?php echo esc_html__( 'Todos os modificadores já estão vinculados ou não há modificadores cadastrados.', 'vemcomer' ); ?>
							<br />
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . CPT_ProductModifier::SLUG ) ); ?>" target="_blank">
								<?php echo esc_html__( 'Criar novo modificador', 'vemcomer' ); ?>
							</a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	public function save_modifiers( int $post_id ): void {
		// Verificar nonce
		if ( ! isset( $_POST['vc_menu_item_modifiers_nonce_field'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['vc_menu_item_modifiers_nonce_field'], 'vc_menu_item_modifiers_nonce' ) ) {
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

		// Salvar modificadores vinculados
		$modifier_ids = isset( $_POST['vc_menu_item_modifiers'] ) && is_array( $_POST['vc_menu_item_modifiers'] )
			? array_map( 'absint', $_POST['vc_menu_item_modifiers'] )
			: [];
		update_post_meta( $post_id, '_vc_menu_item_modifiers', $modifier_ids );

		// Atualizar meta reversa nos modificadores
		// Primeiro, remover este item de todos os modificadores
		$all_modifiers = get_posts( [
			'post_type'      => CPT_ProductModifier::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		foreach ( $all_modifiers as $modifier_id ) {
			$menu_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
			$menu_items = is_array( $menu_items ) ? $menu_items : [];
			$key        = array_search( $post_id, $menu_items, true );
			if ( $key !== false ) {
				unset( $menu_items[ $key ] );
				$menu_items = array_values( $menu_items ); // Reindexar
				update_post_meta( $modifier_id, '_vc_modifier_menu_items', $menu_items );
			}
		}

		// Agora adicionar este item aos modificadores selecionados
		foreach ( $modifier_ids as $modifier_id ) {
			$menu_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
			$menu_items = is_array( $menu_items ) ? $menu_items : [];
			if ( ! in_array( $post_id, $menu_items, true ) ) {
				$menu_items[] = $post_id;
				update_post_meta( $modifier_id, '_vc_modifier_menu_items', $menu_items );
			}
		}
	}

	private function get_inline_css(): string {
		return '
		.vc-modifiers-metabox { margin: 10px 0; }
		.vc-modifiers-section { margin-bottom: 30px; }
		.vc-modifiers-section h4 { margin-bottom: 10px; font-size: 14px; }
		.vc-modifiers-list { list-style: none; margin: 0; padding: 0; border: 1px solid #ddd; background: #fff; min-height: 50px; }
		.vc-modifiers-list.ui-sortable { cursor: move; }
		.vc-modifier-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; background: #fff; }
		.vc-modifier-item:last-child { border-bottom: none; }
		.vc-modifier-item.ui-sortable-helper { box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
		.vc-modifier-handle { cursor: move; color: #666; margin-right: 10px; }
		.vc-modifier-title { flex: 1; font-weight: 500; }
		.vc-modifier-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin: 0 10px; }
		.vc-modifier-badge.vc-modifier-type-required { background: #dc3232; color: #fff; }
		.vc-modifier-badge.vc-modifier-type-optional { background: #46b450; color: #fff; }
		.vc-modifier-price { margin: 0 10px; color: #0073aa; font-weight: 500; }
		.vc-modifier-minmax { margin: 0 10px; color: #666; font-size: 12px; }
		.vc-modifier-remove, .vc-modifier-add { margin-left: auto; }
		.vc-modifier-empty { padding: 20px; text-align: center; color: #666; font-style: italic; }
		';
	}

	private function get_inline_js(): string {
		return "
		jQuery(document).ready(function($) {
			// Inicializar drag-and-drop
			$('#vc-modifiers-linked').sortable({
				handle: '.vc-modifier-handle',
				placeholder: 'vc-modifier-item ui-state-highlight',
				axis: 'y',
				update: function() {
					// Atualizar ordem dos hidden inputs
					var order = [];
					$('#vc-modifiers-linked .vc-modifier-item').each(function() {
						var id = $(this).data('id');
						if (id) order.push(id);
					});
					$('#vc-modifiers-linked input[type=\"hidden\"]').remove();
					order.forEach(function(id) {
						$('#vc-modifiers-linked .vc-modifier-item[data-id=\"' + id + '\"]').append('<input type=\"hidden\" name=\"vc_menu_item_modifiers[]\" value=\"' + id + '\" />');
					});
				}
			});

			// Adicionar modificador
			$(document).on('click', '.vc-modifier-add', function(e) {
				e.preventDefault();
				var item = $(this).closest('.vc-modifier-item');
				var id = item.data('id');
				var title = item.find('.vc-modifier-title').text();
				var type = item.find('.vc-modifier-badge').hasClass('vc-modifier-type-required') ? 'required' : 'optional';
				var typeLabel = type === 'required' ? 'Obrigatório' : 'Opcional';
				var price = item.find('.vc-modifier-price').text();
				var minmax = item.find('.vc-modifier-minmax').text();

				// Remover da lista de disponíveis
				item.fadeOut(300, function() {
					item.remove();
					if ($('#vc-modifiers-available .vc-modifier-item').length === 0) {
						$('#vc-modifiers-available').append('<li class=\"vc-modifier-empty\">Todos os modificadores já estão vinculados.</li>');
					}
				});

				// Adicionar à lista de vinculados
				var emptyMsg = $('#vc-modifiers-linked .vc-modifier-empty');
				if (emptyMsg.length) emptyMsg.remove();

				var newItem = $('<li class=\"vc-modifier-item\" data-id=\"' + id + '\">' +
					'<span class=\"vc-modifier-handle dashicons dashicons-menu\"></span>' +
					'<span class=\"vc-modifier-title\">' + title + '</span>' +
					'<span class=\"vc-modifier-badge vc-modifier-type-' + type + '\">' + typeLabel + '</span>' +
					'<span class=\"vc-modifier-price\">' + price + '</span>' +
					'<span class=\"vc-modifier-minmax\">' + minmax + '</span>' +
					'<button type=\"button\" class=\"button-link vc-modifier-remove\" data-id=\"' + id + '\">' +
					'<span class=\"dashicons dashicons-dismiss\"></span>' +
					'</button>' +
					'<input type=\"hidden\" name=\"vc_menu_item_modifiers[]\" value=\"' + id + '\" />' +
					'</li>');

				$('#vc-modifiers-linked').append(newItem);
				newItem.hide().fadeIn(300);
			});

			// Remover modificador
			$(document).on('click', '.vc-modifier-remove', function(e) {
				e.preventDefault();
				var item = $(this).closest('.vc-modifier-item');
				var id = item.data('id');
				var title = item.find('.vc-modifier-title').text();
				var type = item.find('.vc-modifier-badge').hasClass('vc-modifier-type-required') ? 'required' : 'optional';
				var typeLabel = type === 'required' ? 'Obrigatório' : 'Opcional';
				var price = item.find('.vc-modifier-price').text();
				var minmax = item.find('.vc-modifier-minmax').text();

				// Remover da lista de vinculados
				item.fadeOut(300, function() {
					item.remove();
					if ($('#vc-modifiers-linked .vc-modifier-item').length === 0) {
						$('#vc-modifiers-linked').append('<li class=\"vc-modifier-empty\">Nenhum modificador vinculado. Use a lista abaixo para adicionar.</li>');
					}
				});

				// Adicionar à lista de disponíveis
				var emptyMsg = $('#vc-modifiers-available .vc-modifier-empty');
				if (emptyMsg.length) emptyMsg.remove();

				var newItem = $('<li class=\"vc-modifier-item\" data-id=\"' + id + '\">' +
					'<span class=\"vc-modifier-title\">' + title + '</span>' +
					'<span class=\"vc-modifier-badge vc-modifier-type-' + type + '\">' + typeLabel + '</span>' +
					'<span class=\"vc-modifier-price\">' + price + '</span>' +
					'<span class=\"vc-modifier-minmax\">' + minmax + '</span>' +
					'<button type=\"button\" class=\"button vc-modifier-add\" data-id=\"' + id + '\">Adicionar</button>' +
					'</li>');

				$('#vc-modifiers-available').append(newItem);
				newItem.hide().fadeIn(300);
			});
		});
		";
	}
}

