<?php
/**
 * Template de arquivo do CPT vc_restaurant.
 *
 * @package VemComer\Core
 */

get_header();
?>
<main class="vc-archive">
		<div class="wrap">
		<?php
		$current_cuisine_param  = filter_input( INPUT_GET, 'cuisine', FILTER_SANITIZE_SPECIAL_CHARS );
		$current_location_param = filter_input( INPUT_GET, 'location', FILTER_SANITIZE_SPECIAL_CHARS );
		$delivery_param         = filter_input( INPUT_GET, 'delivery', FILTER_SANITIZE_NUMBER_INT );
		$search_param           = filter_input( INPUT_GET, 's', FILTER_SANITIZE_SPECIAL_CHARS );

		$current_cuisine  = $current_cuisine_param ? sanitize_title( $current_cuisine_param ) : '';
		$current_location = $current_location_param ? sanitize_title( $current_location_param ) : '';
		$has_delivery     = null !== $delivery_param ? (bool) (int) $delivery_param : false;
		$search_value     = $search_param ? sanitize_text_field( $search_param ) : '';
		?>
	<h1><?php echo esc_html__( 'Restaurantes', 'vemcomer' ); ?></h1>

		<form method="get" class="vc-filters" style="margin:1rem 0;">
				<input type="text" name="s" value="<?php echo esc_attr( $search_value ); ?>" placeholder="<?php esc_attr_e( 'Buscar...', 'vemcomer' ); ?>" />
				<select name="cuisine">
						<option value=""><?php echo esc_html__( 'Tipo de cozinha', 'vemcomer' ); ?></option>
				<?php
				$cuisine_terms = get_terms(
					array(
						'taxonomy'   => 'vc_cuisine',
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $cuisine_terms ) ) :
					foreach ( $cuisine_terms as $t ) :
						?>
				<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $current_cuisine, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
						<?php
					endforeach;
		endif;
				?>
		</select>
				<select name="location">
						<option value=""><?php echo esc_html__( 'Bairro', 'vemcomer' ); ?></option>
		<?php
		$location_terms = get_terms(
			array(
				'taxonomy'   => 'vc_location',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $location_terms ) ) :
			foreach ( $location_terms as $t ) :
				?>
				<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $current_location, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
				<?php
			endforeach;
		endif;
		?>
		</select>
				<label style="margin-left:.5rem;">
						<input type="checkbox" name="delivery" value="1" <?php checked( $has_delivery ); ?> /> <?php echo esc_html__( 'Delivery', 'vemcomer' ); ?>
				</label>
				<button type="submit"><?php echo esc_html__( 'Filtrar', 'vemcomer' ); ?></button>
		</form>

		<?php
		// Aplica filtros via WP_Query.
		$tax_query = array();
		if ( $current_cuisine ) {
			$tax_query[] = array(
				'taxonomy' => 'vc_cuisine',
				'field'    => 'slug',
				'terms'    => $current_cuisine,
			);
		}
		if ( $current_location ) {
			$tax_query[] = array(
				'taxonomy' => 'vc_location',
				'field'    => 'slug',
				'terms'    => $current_location,
			);
		}
		if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
		}

		$meta_query = array();
		if ( $has_delivery ) {
			$meta_query[] = array(
				'key'   => 'vc_restaurant_delivery',
				'value' => '1',
			);
		}

				$tax_query_args  = ! empty( $tax_query ) ? $tax_query : '';
				$meta_query_args = ! empty( $meta_query ) ? $meta_query : '';

				$q = new WP_Query(
					array(
						'post_type'  => 'vc_restaurant',
						's'          => $search_value,
						'tax_query'  => $tax_query_args,
						'meta_query' => $meta_query_args,
						'paged'      => max( 1, absint( get_query_var( 'paged' ) ) ),
					)
				);
				?>

		<?php if ( $q->have_posts() ) : ?>
				<ul class="vc-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
				<?php
				while ( $q->have_posts() ) :
					$q->the_post();
					?>
						<li class="vc-card" style="border:1px solid #eee;padding:12px;border-radius:12px;">
								<a href="<?php the_permalink(); ?>" style="text-decoration:none;">
								<?php
								if ( has_post_thumbnail() ) {
										the_post_thumbnail( 'medium' );
								}
								?>
										<h3><?php the_title(); ?></h3>
										<p><?php echo esc_html( get_post_meta( get_the_ID(), 'vc_restaurant_address', true ) ); ?></p>
										<p><?php echo esc_html( get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true ) ); ?></p>
								</a>
						</li>
						<?php endwhile; ?>
				</ul>

				<div class="vc-pagination">
						<?php echo wp_kses_post( paginate_links( array( 'total' => $q->max_num_pages ) ) ); ?>
				</div>
		<?php else : ?>
				<p><?php esc_html_e( 'Nenhum restaurante encontrado.', 'vemcomer' ); ?></p>
		<?php endif; ?>
	<?php wp_reset_postdata(); ?>
	</div>
</main>
<?php get_footer(); ?>
