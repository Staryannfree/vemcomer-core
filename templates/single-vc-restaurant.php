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
			// Usar horários estruturados se disponível
			$schedule = [];
			$hours = '';
			if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
				$schedule = \VC\Utils\Schedule_Helper::get_schedule( get_the_ID() );
				if ( empty( $schedule ) ) {
					// Fallback para campo texto legado
					$hours = get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true );
				} else {
					// Formatar horários estruturados para exibição
					$day_names_pt = [
						'monday'    => __( 'Segunda-feira', 'vemcomer' ),
						'tuesday'   => __( 'Terça-feira', 'vemcomer' ),
						'wednesday' => __( 'Quarta-feira', 'vemcomer' ),
						'thursday'  => __( 'Quinta-feira', 'vemcomer' ),
						'friday'    => __( 'Sexta-feira', 'vemcomer' ),
						'saturday'  => __( 'Sábado', 'vemcomer' ),
						'sunday'    => __( 'Domingo', 'vemcomer' ),
					];
					$hours_parts = [];
					foreach ( $schedule as $day => $day_data ) {
						if ( ! empty( $day_data['enabled'] ) && ! empty( $day_data['periods'] ) ) {
							$periods_str = [];
							foreach ( $day_data['periods'] as $period ) {
								$open = $period['open'] ?? '';
								$close = $period['close'] ?? '';
								if ( $open && $close ) {
									$periods_str[] = $open . ' às ' . $close;
								}
							}
							if ( ! empty( $periods_str ) ) {
								$day_label = $day_names_pt[ $day ] ?? ucfirst( $day );
								$hours_parts[] = $day_label . ': ' . implode( ', ', $periods_str );
							}
						}
					}
					if ( ! empty( $hours_parts ) ) {
						$hours = implode( "\n", $hours_parts );
					}
				}
			} else {
				$hours = get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true );
			}
			$lat             = (float) get_post_meta( get_the_ID(), 'vc_restaurant_lat', true );
			$lng             = (float) get_post_meta( get_the_ID(), 'vc_restaurant_lng', true );
			$excerpt         = has_excerpt() ? get_the_excerpt() : '';
			$wa_digits       = preg_replace( '/\\D+/', '', (string) $whatsapp );
			$title_letter    = get_the_title();
			$title_letter    = strtoupper( (string) ( function_exists( 'mb_substr' ) ? mb_substr( $title_letter, 0, 1, 'UTF-8' ) : substr( $title_letter, 0, 1 ) ) );
			$has_coordinates = $lat && $lng;

			if ( $has_coordinates ) {
				wp_enqueue_style( 'leaflet' );
				wp_enqueue_script( 'leaflet' );
				wp_enqueue_script( 'vemcomer-restaurant-map' );
				wp_localize_script(
					'vemcomer-restaurant-map',
					'VC_RESTAURANT_MAP',
					[
						'lat'   => $lat,
						'lng'   => $lng,
						'title' => get_the_title(),
						'tiles' => function_exists( 'vc_tiles_url' ) ? vc_tiles_url() : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
					]
				);
			}
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
					</div>
					<div class="vc-single__summary">
						<?php if ( $cuisine_list ) : ?>
							<span class="vc-single__eyebrow"><?php echo esc_html( $cuisine_list ); ?></span>
						<?php endif; ?>
						<h1 class="vc-single__title"><?php the_title(); ?></h1>
						<?php if ( $excerpt ) : ?>
							<p class="vc-single__excerpt"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
						<div class="vc-single__chips">
							<?php if ( $location_list ) : ?>
								<span class="vc-chip vc-chip--muted"><?php echo esc_html( $location_list ); ?></span>
							<?php endif; ?>
							<span class="vc-chip vc-chip--status <?php echo '1' === $delivery_raw ? 'is-on' : 'is-off'; ?>">
								<?php echo '1' === $delivery_raw ? esc_html__( 'Delivery ativo', 'vemcomer' ) : esc_html__( 'Somente retirada', 'vemcomer' ); ?>
							</span>
						</div>
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
											<a class="vc-link" href="<?php echo esc_url( 'https://wa.me/' . ltrim( $wa_digits, '0' ) ); ?>" target="_blank" rel="noopener">
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
									<strong style="white-space: pre-line;"><?php echo esc_html( $hours ); ?></strong>
								</li>
							<?php endif; ?>
							<li>
								<span><?php echo esc_html__( 'Delivery', 'vemcomer' ); ?></span>
								<strong class="vc-badge <?php echo '1' === $delivery_raw ? 'vc-badge--ok' : 'vc-badge--muted'; ?>">
									<?php echo esc_html( $delivery_status ); ?>
								</strong>
							</li>
							<?php if ( $site_url ) : ?>
								<li>
									<span><?php echo esc_html__( 'Site oficial', 'vemcomer' ); ?></span>
									<strong>
										<a class="vc-link" href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener">
											<?php echo esc_html( wp_parse_url( $site_url, PHP_URL_HOST ) ?: $site_url ); ?>
										</a>
									</strong>
								</li>
							<?php endif; ?>
						</ul>
						<div class="vc-single__ctas">
							<a class="vc-btn" href="#vc-menu"><?php echo esc_html__( 'Ver cardápio completo', 'vemcomer' ); ?></a>
							<?php if ( $site_url ) : ?>
								<a class="vc-btn vc-btn--ghost" href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html__( 'Visitar site', 'vemcomer' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $wa_digits ) : ?>
								<a class="vc-btn vc-btn--outline" href="<?php echo esc_url( 'https://wa.me/' . ltrim( $wa_digits, '0' ) ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html__( 'Falar no WhatsApp', 'vemcomer' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</section>
				<?php if ( $has_coordinates ) :
					$coords            = $lat . ',' . $lng;
					$google_directions = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $coords );
					$osm_directions    = 'https://www.openstreetmap.org/directions?to=' . rawurlencode( $coords );
					?>
					<section class="vc-single__map">
						<div class="vc-single__map-header">
							<p class="vc-single__eyebrow"><?php echo esc_html__( 'Localização', 'vemcomer' ); ?></p>
							<h2><?php echo esc_html__( 'Onde estamos', 'vemcomer' ); ?></h2>
						</div>
						<div id="vc-restaurant-map" class="vc-map" aria-label="<?php echo esc_attr__( 'Mapa do restaurante', 'vemcomer' ); ?>"></div>
						<div class="vc-map__actions">
							<a class="vc-btn vc-btn--ghost" href="<?php echo esc_url( $google_directions ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html__( 'Ver rota no Google Maps', 'vemcomer' ); ?>
							</a>
							<a class="vc-btn vc-btn--outline" href="<?php echo esc_url( $osm_directions ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html__( 'Ver rota no OpenStreetMap', 'vemcomer' ); ?>
							</a>
						</div>
					</section>
				<?php endif; ?>
				<?php if ( get_the_content() ) : ?>
					<section class="vc-single__content">
						<?php the_content(); ?>
					</section>
				<?php endif; ?>
				<div class="vc-menu-wrapper" id="vc-menu">
					<div class="vc-menu-wrapper__header">
						<div>
							<p class="vc-menu-wrapper__eyebrow"><?php echo esc_html__( 'Cardápio oficial', 'vemcomer' ); ?></p>
							<h2><?php echo esc_html__( 'Peça agora mesmo', 'vemcomer' ); ?></h2>
						</div>
						<a class="vc-btn" href="#vc-menu"><?php echo esc_html__( 'Atualizar lista', 'vemcomer' ); ?></a>
					</div>
					<?php echo do_shortcode( '[vc_menu_items]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php echo do_shortcode( '[vc_reviews restaurant_id="' . get_the_ID() . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
// Modal de Informações do Restaurante (para modo standalone)
if ( function_exists( 'vc_is_standalone_mode' ) && vc_is_standalone_mode() ) :
	$restaurant_id = get_the_ID();
	$restaurant_title = get_the_title( $restaurant_id );
	$restaurant_address = get_post_meta( $restaurant_id, '_vc_address', true );
	$restaurant_phone = get_post_meta( $restaurant_id, '_vc_phone', true );
	$restaurant_whatsapp = get_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', true );
	$schedule = [];
	if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
		$schedule = \VC\Utils\Schedule_Helper::get_schedule( $restaurant_id );
	}
	$hours = get_post_meta( $restaurant_id, 'vc_restaurant_open_hours', true );
?>
<div id="vc-restaurant-info-modal" class="vc-info-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
	<div class="vc-info-modal-content" style="background: white; border-radius: 16px; max-width: 400px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative;">
		<button type="button" onclick="document.getElementById('vc-restaurant-info-modal').style.display='none';" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
		<h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; padding-right: 30px;"><?php echo esc_html( $restaurant_title ); ?></h2>
		
		<?php if ( $restaurant_address ) : ?>
		<div style="margin-bottom: 16px;">
			<strong style="display: block; margin-bottom: 4px; color: #333;"><?php esc_html_e( 'Endereço', 'vemcomer' ); ?></strong>
			<p style="color: #666; margin: 0;"><?php echo esc_html( $restaurant_address ); ?></p>
		</div>
		<?php endif; ?>
		
		<?php if ( $restaurant_phone ) : ?>
		<div style="margin-bottom: 16px;">
			<strong style="display: block; margin-bottom: 4px; color: #333;"><?php esc_html_e( 'Telefone', 'vemcomer' ); ?></strong>
			<p style="color: #666; margin: 0;">
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $restaurant_phone ) ); ?>" style="color: #158943; text-decoration: none;">
					<?php echo esc_html( $restaurant_phone ); ?>
				</a>
			</p>
		</div>
		<?php endif; ?>
		
		<?php if ( $restaurant_whatsapp ) : ?>
		<div style="margin-bottom: 16px;">
			<strong style="display: block; margin-bottom: 4px; color: #333;"><?php esc_html_e( 'WhatsApp', 'vemcomer' ); ?></strong>
			<p style="color: #666; margin: 0;">
				<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $restaurant_whatsapp ) ); ?>" target="_blank" style="color: #158943; text-decoration: none;">
					<?php echo esc_html( $restaurant_whatsapp ); ?>
				</a>
			</p>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $schedule ) || $hours ) : ?>
		<div style="margin-bottom: 16px;">
			<strong style="display: block; margin-bottom: 4px; color: #333;"><?php esc_html_e( 'Horário de Funcionamento', 'vemcomer' ); ?></strong>
			<?php if ( ! empty( $schedule ) ) : ?>
				<?php
				$day_names_pt = [
					'monday'    => __( 'Segunda', 'vemcomer' ),
					'tuesday'   => __( 'Terça', 'vemcomer' ),
					'wednesday' => __( 'Quarta', 'vemcomer' ),
					'thursday'  => __( 'Quinta', 'vemcomer' ),
					'friday'    => __( 'Sexta', 'vemcomer' ),
					'saturday'  => __( 'Sábado', 'vemcomer' ),
					'sunday'    => __( 'Domingo', 'vemcomer' ),
				];
				foreach ( $schedule as $day => $day_data ) :
					if ( ! empty( $day_data['enabled'] ) && ! empty( $day_data['periods'] ) ) :
						$periods_str = [];
						foreach ( $day_data['periods'] as $period ) {
							$open = $period['open'] ?? '';
							$close = $period['close'] ?? '';
							if ( $open && $close ) {
								$periods_str[] = $open . ' - ' . $close;
							}
						}
						if ( ! empty( $periods_str ) ) :
				?>
				<p style="color: #666; margin: 4px 0;">
					<strong><?php echo esc_html( $day_names_pt[ $day ] ?? ucfirst( $day ) ); ?>:</strong> 
					<?php echo esc_html( implode( ', ', $periods_str ) ); ?>
				</p>
				<?php
						endif;
					endif;
				endforeach;
				?>
			<?php else : ?>
				<p style="color: #666; margin: 0; white-space: pre-line;"><?php echo esc_html( $hours ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
