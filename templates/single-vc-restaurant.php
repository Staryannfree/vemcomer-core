<?php
/**
 * Template single do CPT vc_restaurant.
 *
 * @package VemComer\Core
 */

get_header();
?>
<main class="vc-single">
	<div class="wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article>
		<h1><?php the_title(); ?></h1>
				<?php
				if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'large' );
				}
				?>
		<div class="vc-meta" style="margin:12px 0;">
			<?php
						$cuisine_terms   = wp_get_post_terms( get_the_ID(), 'vc_cuisine', array( 'fields' => 'names' ) );
						$location_terms  = wp_get_post_terms( get_the_ID(), 'vc_location', array( 'fields' => 'names' ) );
						$cuisine_list    = ! is_wp_error( $cuisine_terms ) ? implode( ', ', $cuisine_terms ) : '';
						$location_list   = ! is_wp_error( $location_terms ) ? implode( ', ', $location_terms ) : '';
						$site_url        = get_post_meta( get_the_ID(), 'vc_restaurant_site', true );
						$delivery_raw    = get_post_meta( get_the_ID(), 'vc_restaurant_delivery', true );
						$delivery_status = '1' === $delivery_raw ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' );
			?>
						<p><strong><?php echo esc_html__( 'Endereço:', 'vemcomer' ); ?></strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'vc_restaurant_address', true ) ); ?></p>
						<p><strong><?php echo esc_html__( 'WhatsApp:', 'vemcomer' ); ?></strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'vc_restaurant_whatsapp', true ) ); ?></p>
						<?php if ( ! empty( $site_url ) ) : ?>
								<p><strong><?php echo esc_html__( 'Site:', 'vemcomer' ); ?></strong> <a href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Abrir', 'vemcomer' ); ?></a></p>
						<?php endif; ?>
						<p><strong><?php echo esc_html__( 'Horários:', 'vemcomer' ); ?></strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true ) ); ?></p>
						<p><strong><?php echo esc_html__( 'Delivery:', 'vemcomer' ); ?></strong> <?php echo esc_html( $delivery_status ); ?></p>
						<p><strong><?php echo esc_html__( 'Cozinha:', 'vemcomer' ); ?></strong> <?php echo esc_html( $cuisine_list ); ?></p>
						<p><strong><?php echo esc_html__( 'Bairro:', 'vemcomer' ); ?></strong> <?php echo esc_html( $location_list ); ?></p>
		</div>
		<div class="vc-content">
			<?php the_content(); ?>
		</div>
		</article>
	<?php endwhile; ?>
	</div>
</main>
<?php get_footer(); ?>
