<?php
/**
 * CPT_SubscriptionPlan — Custom Post Type "Subscription Plan" (Planos de Assinatura)
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_SubscriptionPlan {
	public const SLUG = 'vc_subscription_plan';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Planos de Assinatura', 'vemcomer' ),
			'singular_name'         => __( 'Plano de Assinatura', 'vemcomer' ),
			'menu_name'             => __( 'Planos', 'vemcomer' ),
			'name_admin_bar'        => __( 'Plano', 'vemcomer' ),
			'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar novo plano', 'vemcomer' ),
			'new_item'              => __( 'Novo plano', 'vemcomer' ),
			'edit_item'             => __( 'Editar plano', 'vemcomer' ),
			'view_item'             => __( 'Ver plano', 'vemcomer' ),
			'all_items'             => __( 'Todos os planos', 'vemcomer' ),
			'search_items'          => __( 'Buscar planos', 'vemcomer' ),
			'not_found'             => __( 'Nenhum plano encontrado.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhum plano na lixeira.', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => [ 'title', 'editor' ],
			'capability_type' => 'post',
		];
		register_post_type( self::SLUG, $args );
	}

	public function register_metaboxes(): void {
		add_meta_box(
			'vc_subscription_plan_meta',
			__( 'Dados do Plano', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_subscription_plan_meta_nonce', 'vc_subscription_plan_meta_nonce_field' );

		$monthly_price = (float) get_post_meta( $post->ID, '_vc_plan_monthly_price', true );
		$features_json = get_post_meta( $post->ID, '_vc_plan_features', true );
		$features = $features_json ? json_decode( $features_json, true ) : [];
		$active = (bool) get_post_meta( $post->ID, '_vc_plan_active', true );

		// Limites padrão
		$max_menu_items = (int) get_post_meta( $post->ID, '_vc_plan_max_menu_items', true );
		$max_modifiers_per_item = (int) get_post_meta( $post->ID, '_vc_plan_max_modifiers_per_item', true );
		$advanced_analytics = (bool) get_post_meta( $post->ID, '_vc_plan_advanced_analytics', true );
		$priority_support = (bool) get_post_meta( $post->ID, '_vc_plan_priority_support', true );

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_plan_monthly_price"><?php echo esc_html__( 'Preço Mensal (R$)', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_plan_monthly_price" name="vc_plan_monthly_price" class="small-text" step="0.01" min="0" value="<?php echo esc_attr( $monthly_price > 0 ? number_format( $monthly_price, 2, '.', '' ) : '' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label><?php echo esc_html__( 'Limites do Plano', 'vemcomer' ); ?></label></th>
				<td>
					<p>
						<label for="vc_plan_max_menu_items">
							<?php echo esc_html__( 'Máximo de itens no cardápio:', 'vemcomer' ); ?>
							<input type="number" id="vc_plan_max_menu_items" name="vc_plan_max_menu_items" class="small-text" min="0" value="<?php echo esc_attr( $max_menu_items > 0 ? (string) $max_menu_items : '' ); ?>" />
							<span class="description"><?php echo esc_html__( '(0 = ilimitado)', 'vemcomer' ); ?></span>
						</label>
					</p>
					<p>
						<label for="vc_plan_max_modifiers_per_item">
							<?php echo esc_html__( 'Máximo de modificadores por item:', 'vemcomer' ); ?>
							<input type="number" id="vc_plan_max_modifiers_per_item" name="vc_plan_max_modifiers_per_item" class="small-text" min="0" value="<?php echo esc_attr( $max_modifiers_per_item > 0 ? (string) $max_modifiers_per_item : '' ); ?>" />
							<span class="description"><?php echo esc_html__( '(0 = ilimitado)', 'vemcomer' ); ?></span>
						</label>
					</p>
				</td>
			</tr>
			<tr>
				<th><label><?php echo esc_html__( 'Recursos Especiais', 'vemcomer' ); ?></label></th>
				<td>
					<p>
						<label>
							<input type="checkbox" id="vc_plan_advanced_analytics" name="vc_plan_advanced_analytics" value="1" <?php checked( $advanced_analytics ); ?> />
							<?php echo esc_html__( 'Analytics avançado', 'vemcomer' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" id="vc_plan_priority_support" name="vc_plan_priority_support" value="1" <?php checked( $priority_support ); ?> />
							<?php echo esc_html__( 'Suporte prioritário', 'vemcomer' ); ?>
						</label>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_plan_features"><?php echo esc_html__( 'Recursos (JSON)', 'vemcomer' ); ?></label></th>
				<td>
					<textarea id="vc_plan_features" name="vc_plan_features" class="widefat" rows="5"><?php echo esc_textarea( $features_json ?: '[]' ); ?></textarea>
					<p class="description"><?php echo esc_html__( 'Array JSON com recursos adicionais do plano.', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_plan_active"><?php echo esc_html__( 'Ativo', 'vemcomer' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="vc_plan_active" name="vc_plan_active" value="1" <?php checked( $active ); ?> />
						<?php echo esc_html__( 'Plano está ativo e disponível', 'vemcomer' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['vc_subscription_plan_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_subscription_plan_meta_nonce_field'], 'vc_subscription_plan_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$monthly_price = isset( $_POST['vc_plan_monthly_price'] ) ? (float) $_POST['vc_plan_monthly_price'] : 0.0;
		update_post_meta( $post_id, '_vc_plan_monthly_price', $monthly_price );

		$max_menu_items = isset( $_POST['vc_plan_max_menu_items'] ) ? (int) $_POST['vc_plan_max_menu_items'] : 0;
		update_post_meta( $post_id, '_vc_plan_max_menu_items', $max_menu_items );

		$max_modifiers = isset( $_POST['vc_plan_max_modifiers_per_item'] ) ? (int) $_POST['vc_plan_max_modifiers_per_item'] : 0;
		update_post_meta( $post_id, '_vc_plan_max_modifiers_per_item', $max_modifiers );

		$advanced_analytics = isset( $_POST['vc_plan_advanced_analytics'] ) && '1' === $_POST['vc_plan_advanced_analytics'];
		update_post_meta( $post_id, '_vc_plan_advanced_analytics', $advanced_analytics ? '1' : '0' );

		$priority_support = isset( $_POST['vc_plan_priority_support'] ) && '1' === $_POST['vc_plan_priority_support'];
		update_post_meta( $post_id, '_vc_plan_priority_support', $priority_support ? '1' : '0' );

		$features = isset( $_POST['vc_plan_features'] ) ? wp_unslash( $_POST['vc_plan_features'] ) : '[]';
		$decoded = json_decode( $features, true );
		if ( is_array( $decoded ) ) {
			update_post_meta( $post_id, '_vc_plan_features', wp_json_encode( $decoded ) );
		} else {
			update_post_meta( $post_id, '_vc_plan_features', '[]' );
		}

		$active = isset( $_POST['vc_plan_active'] ) && '1' === $_POST['vc_plan_active'];
		update_post_meta( $post_id, '_vc_plan_active', $active ? '1' : '0' );
	}
}

