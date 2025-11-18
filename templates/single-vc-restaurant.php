<?php
/**
 * Template single do CPT vc_restaurant.
 *
 * @package VemComer\Core
 */

get_header();
?>
<main class="vc-single">
	<div class="wrap vc-single__wrap">
		<?php
		while ( have_posts() ) :
			the_post();
			$cuisine_terms   = wp_get_post_terms( get_the_ID(), 'vc_cuisine', array( 'fields' => 'names' ) );
			$location_terms  = wp_get_post_terms( get_the_ID(), 'vc_location', array( 'fields' => 'names' ) );
			$cuisine_list    = ! is_wp_error( $cuisine_terms ) ? implode( ', ', $cuisine_terms ) : '';
			$location_list   = ! is_wp_error( $location_terms ) ? implode( ', ', $location_terms ) : '';
			$site_url        = get_post_meta( get_the_ID(), 'vc_restaurant_site', true );
			$delivery_raw    = get_post_meta( get_the_ID(), 'vc_restaurant_delivery', true );
			$delivery_status = '1' === $delivery_raw ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' );
			$address         = get_post_meta( get_the_ID(), 'vc_restaurant_address', true );
			$whatsapp        = get_post_meta( get_the_ID(), 'vc_restaurant_whatsapp', true );
			$hours           = get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true );
			$excerpt         = has_excerpt() ? get_the_excerpt() : '';
			$wa_digits       = preg_replace( '/\D+/', '', (string) $whatsapp );
			$title_letter    = get_the_title();
			$title_letter    = strtoupper( (string) ( function_exists( 'mb_substr' ) ? mb_substr( $title_letter, 0, 1, 'UTF-8' ) : substr( $title_letter, 0, 1 ) ) );
			?>
			<article class="vc-single__card">
				<section class="vc-single__hero">
					<div class="vc-single__media">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'vc-single__image' ) ); ?>
						<?php else : ?>
							<div class="vc-single__placeholder" aria-hidden="true">
								<span><?php echo esc_html( $title_letter ); ?></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="vc-single__summary">
						<?php if ( $cuisine_list ) : ?>
							<span class="vc-single__eyebrow"><?php echo esc_html( $cuisine_list ); ?></span>
						<?php endif; ?>
						<h1 class="vc-single__title"><?php the_title(); ?></h1>
						<?php if ( $excerpt ) : ?>
							<p class="vc-single__excerpt"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
						<ul class="vc-single__details">
							<?php if ( $address ) : ?>
								<li>
									<span><?php echo esc_html__( 'Endereço', 'vemcomer' ); ?></span>
									<strong><?php echo esc_html( $address ); ?></strong>
								</li>
							<?php endif; ?>
							<?php if ( $whatsapp ) : ?>
								<li>
									<span><?php echo esc_html__( 'WhatsApp', 'vemcomer' ); ?></span>
									<strong>
										<?php if ( $wa_digits ) : ?>
											<a href="<?php echo esc_url( 'https://wa.me/' . ltrim( $wa_digits, '0' ) ); ?>" target="_blank" rel="noopener">
												<?php echo esc_html( $whatsapp ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $whatsapp ); ?>
										<?php endif; ?>
									</strong>
								</li>
							<?php endif; ?>
							<?php if ( $hours ) : ?>
								<li>
									<span><?php echo esc_html__( 'Horário', 'vemcomer' ); ?></span>
									<strong><?php echo esc_html( $hours ); ?></strong>
								</li>
							<?php endif; ?>
							<li>
								<span><?php echo esc_html__( 'Delivery', 'vemcomer' ); ?></span>
								<strong class="vc-badge <?php echo '1' === $delivery_raw ? 'vc-badge--ok' : 'vc-badge--muted'; ?>">
									<?php echo esc_html( $delivery_status ); ?>
								</strong>
							</li>
							<?php if ( $location_list ) : ?>
								<li>
									<span><?php echo esc_html__( 'Bairro', 'vemcomer' ); ?></span>
									<strong><?php echo esc_html( $location_list ); ?></strong>
								</li>
							<?php endif; ?>
						</ul>
						<div class="vc-single__ctas">
							<a class="vc-btn" href="#vc-menu"><?php echo esc_html__( 'Ver cardápio completo', 'vemcomer' ); ?></a>
							<?php if ( ! empty( $site_url ) ) : ?>
								<a class="vc-btn vc-btn--ghost" href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html__( 'Visitar site', 'vemcomer' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</section>
				<?php if ( get_the_content() ) : ?>
					<section class="vc-single__content">
						<?php the_content(); ?>
					</section>
				<?php endif; ?>
				<div class="vc-menu-wrapper" id="vc-menu">
					<h2><?php echo esc_html__( 'Cardápio', 'vemcomer' ); ?></h2>
					<?php echo do_shortcode( '[vc_menu_items]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php get_footer(); ?>
