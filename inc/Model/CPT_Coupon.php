<?php
/**
 * CPT_Coupon — Custom Post Type "Coupon" (Cupons de Desconto)
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Coupon {
	public const SLUG = 'vc_coupon';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
	}

	public function register_cpt(): void {
		$labels = [
			'name'                  => __( 'Cupons', 'vemcomer' ),
			'singular_name'         => __( 'Cupom', 'vemcomer' ),
			'menu_name'             => __( 'Cupons', 'vemcomer' ),
			'name_admin_bar'        => __( 'Cupom', 'vemcomer' ),
			'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
			'add_new_item'          => __( 'Adicionar novo cupom', 'vemcomer' ),
			'new_item'              => __( 'Novo cupom', 'vemcomer' ),
			'edit_item'             => __( 'Editar cupom', 'vemcomer' ),
			'view_item'             => __( 'Ver cupom', 'vemcomer' ),
			'all_items'             => __( 'Todos os cupons', 'vemcomer' ),
			'search_items'          => __( 'Buscar cupons', 'vemcomer' ),
			'not_found'             => __( 'Nenhum cupom encontrado.', 'vemcomer' ),
			'not_found_in_trash'    => __( 'Nenhum cupom na lixeira.', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => [ 'title' ],
			'capability_type' => 'post',
		];
		register_post_type( self::SLUG, $args );
	}

	public function register_metaboxes(): void {
		add_meta_box(
			'vc_coupon_meta',
			__( 'Dados do Cupom', 'vemcomer' ),
			[ $this, 'metabox' ],
			self::SLUG,
			'normal',
			'high'
		);
	}

	public function metabox( $post ): void {
		wp_nonce_field( 'vc_coupon_meta_nonce', 'vc_coupon_meta_nonce_field' );

		$code = (string) get_post_meta( $post->ID, '_vc_coupon_code', true );
		$type = (string) get_post_meta( $post->ID, '_vc_coupon_type', true );
		$value = (float) get_post_meta( $post->ID, '_vc_coupon_value', true );
		$expires_at = (string) get_post_meta( $post->ID, '_vc_coupon_expires_at', true );
		$max_uses = (int) get_post_meta( $post->ID, '_vc_coupon_max_uses', true );
		$used_count = (int) get_post_meta( $post->ID, '_vc_coupon_used_count', true );
		$restaurant_id = (int) get_post_meta( $post->ID, '_vc_coupon_restaurant_id', true );

		?>
		<table class="form-table">
			<tr>
				<th><label for="vc_coupon_code"><?php echo esc_html__( 'Código', 'vemcomer' ); ?></label></th>
				<td>
					<input type="text" id="vc_coupon_code" name="vc_coupon_code" class="regular-text" value="<?php echo esc_attr( $code ); ?>" />
					<p class="description"><?php echo esc_html__( 'Código do cupom (ex: DESCONTO10).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_coupon_type"><?php echo esc_html__( 'Tipo', 'vemcomer' ); ?></label></th>
				<td>
					<select id="vc_coupon_type" name="vc_coupon_type" class="regular-text">
						<option value="percent" <?php selected( $type, 'percent' ); ?>><?php echo esc_html__( 'Percentual (%)', 'vemcomer' ); ?></option>
						<option value="money" <?php selected( $type, 'money' ); ?>><?php echo esc_html__( 'Valor Fixo (R$)', 'vemcomer' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="vc_coupon_value"><?php echo esc_html__( 'Valor', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_coupon_value" name="vc_coupon_value" class="small-text" step="0.01" min="0" value="<?php echo esc_attr( $value > 0 ? number_format( $value, 2, '.', '' ) : '' ); ?>" />
					<p class="description"><?php echo esc_html__( 'Valor do desconto (percentual ou valor fixo).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_coupon_expires_at"><?php echo esc_html__( 'Válido até', 'vemcomer' ); ?></label></th>
				<td>
					<input type="datetime-local" id="vc_coupon_expires_at" name="vc_coupon_expires_at" value="<?php echo esc_attr( $expires_at ? date( 'Y-m-d\TH:i', strtotime( $expires_at ) ) : '' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="vc_coupon_max_uses"><?php echo esc_html__( 'Uso máximo', 'vemcomer' ); ?></label></th>
				<td>
					<input type="number" id="vc_coupon_max_uses" name="vc_coupon_max_uses" class="small-text" min="0" value="<?php echo esc_attr( $max_uses > 0 ? (string) $max_uses : '' ); ?>" />
					<p class="description"><?php echo esc_html__( 'Número máximo de vezes que o cupom pode ser usado (0 = ilimitado).', 'vemcomer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php echo esc_html__( 'Usado', 'vemcomer' ); ?></label></th>
				<td>
					<p><?php echo esc_html( sprintf( __( '%d vez(es)', 'vemcomer' ), $used_count ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="vc_coupon_restaurant_id"><?php echo esc_html__( 'Restaurante (opcional)', 'vemcomer' ); ?></label></th>
				<td>
					<?php
					$restaurants = get_posts( [
						'post_type'      => 'vc_restaurant',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					] );
					?>
					<select id="vc_coupon_restaurant_id" name="vc_coupon_restaurant_id" class="regular-text">
						<option value=""><?php echo esc_html__( '— Todos os restaurantes —', 'vemcomer' ); ?></option>
						<?php foreach ( $restaurants as $restaurant ) : ?>
							<option value="<?php echo esc_attr( (string) $restaurant->ID ); ?>" <?php selected( $restaurant_id, $restaurant->ID ); ?>>
								<?php echo esc_html( $restaurant->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['vc_coupon_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_coupon_meta_nonce_field'], 'vc_coupon_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$code = isset( $_POST['vc_coupon_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['vc_coupon_code'] ) ) ) : '';
		update_post_meta( $post_id, '_vc_coupon_code', $code );

		$type = isset( $_POST['vc_coupon_type'] ) ? sanitize_key( $_POST['vc_coupon_type'] ) : 'percent';
		update_post_meta( $post_id, '_vc_coupon_type', $type );

		$value = isset( $_POST['vc_coupon_value'] ) ? (float) $_POST['vc_coupon_value'] : 0.0;
		update_post_meta( $post_id, '_vc_coupon_value', $value );

		$expires_at = isset( $_POST['vc_coupon_expires_at'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_coupon_expires_at'] ) ) : '';
		if ( $expires_at ) {
			$expires_at = date( 'Y-m-d H:i:s', strtotime( $expires_at ) );
		}
		update_post_meta( $post_id, '_vc_coupon_expires_at', $expires_at );

		$max_uses = isset( $_POST['vc_coupon_max_uses'] ) ? (int) $_POST['vc_coupon_max_uses'] : 0;
		update_post_meta( $post_id, '_vc_coupon_max_uses', $max_uses );

		$restaurant_id = isset( $_POST['vc_coupon_restaurant_id'] ) ? (int) $_POST['vc_coupon_restaurant_id'] : 0;
		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_coupon_restaurant_id', $restaurant_id );
		} else {
			delete_post_meta( $post_id, '_vc_coupon_restaurant_id' );
		}
	}
}

