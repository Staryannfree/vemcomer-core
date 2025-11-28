<?php
/**
 * Template single do CPT vc_restaurant.
 *
 * @package VemComer\Core
 */

get_header();
?>
<style>
.vc-single__banners {display:flex;gap:10px;flex-wrap:wrap;margin:12px 0;}
.vc-single__banners img {width:110px;height:44px;object-fit:cover;border-radius:10px;box-shadow:0 1px 6px rgba(0,0,0,0.08);}
.vc-single__metrics {display:flex;flex-wrap:wrap;gap:12px;font-weight:600;color:#4a5b50;margin:8px 0;align-items:center;}
.vc-single__metrics .vc-chip {background:#eaf8f1;border:1px solid #cdeee2;border-radius:12px;padding:4px 10px;font-size:0.95em;display:inline-flex;align-items:center;gap:4px;}
.vc-single__badges {display:flex;flex-wrap:wrap;gap:8px;margin:6px 0 10px 0;}
.vc-single__badges .vc-badge-pill {background:#158943;color:#fff;padding:4px 12px;border-radius:12px;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,0.08);}
.vc-single__badges .vc-badge-pill--accent {background:#45c676;}
.vc-single__tags {display:flex;flex-wrap:wrap;gap:6px;margin:4px 0 12px 0;}
.vc-single__tag {background:#eaf8f1;color:#158943;border:1px solid #d7f1e2;padding:3px 10px;border-radius:8px;font-weight:600;font-size:0.95em;}
.vc-single__menu-tools {display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;margin:20px 0 12px;}
.vc-single__menu-search {flex:2;min-width:220px;padding:10px 12px;border-radius:9px;border:1px solid #d7e6df;}
.vc-single__menu-filters {display:flex;gap:8px;flex-wrap:wrap;align-items:center;font-weight:600;color:#158943;}
.vc-single__reservation {display:flex;justify-content:space-between;align-items:center;gap:12px;background:#eaf8f1;border:1px solid #cdeee2;border-radius:12px;padding:14px;margin:10px 0 18px;}
.vc-single__reservation strong {color:#158943;}
.vc-single__info-panels {display:grid;gap:12px;margin:18px 0;}
.vc-single__panel {background:#fff;border:1px solid #e5ece7;border-radius:10px;padding:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.vc-single__panel h3 {margin:0 0 8px;font-size:1.05rem;color:#158943;}
.vc-faq {list-style:none;margin:0;padding:0;}
.vc-faq li {border-top:1px solid #eef2ef;padding:8px 0;}
.vc-bottom-nav {position:fixed;bottom:0;left:0;right:0;height:68px;background:#fff;border-top:1px solid #e5ece7;box-shadow:0 -2px 8px rgba(0,0,0,0.08);z-index:30;}
.vc-bottom-nav__inner {display:flex;height:100%;align-items:center;justify-content:space-around;}
.vc-bottom-nav a {text-decoration:none;color:#5c6b63;font-weight:600;font-size:12px;display:flex;flex-direction:column;align-items:center;gap:4px;}
#vc-reservation-modal {display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:16px;}
#vc-reservation-modal .vc-modal__card {background:#fff;border-radius:14px;max-width:420px;width:100%;padding:18px;position:relative;box-shadow:0 10px 30px rgba(0,0,0,0.2);}
#vc-reservation-modal .vc-modal__close {position:absolute;top:10px;right:12px;background:none;border:none;font-size:22px;color:#888;cursor:pointer;}
#vc-reservation-modal input,#vc-reservation-modal textarea,#vc-reservation-modal select {width:100%;padding:9px 11px;border:1px solid #d7e6df;border-radius:8px;margin-bottom:8px;}
</style>
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
                        $list_from_text  = static function ( string $raw ): array {
                                $parts = preg_split( '/\r\n|\r|\n/', $raw );
                                $parts = is_array( $parts ) ? $parts : [];

                                return array_values( array_filter( array_map( 'trim', $parts ) ) );
                        };

                        $rating_data   = function_exists( 'vc_restaurant_get_rating' ) ? vc_restaurant_get_rating( get_the_ID() ) : [ 'avg' => null, 'count' => 0 ];
                        $is_open       = function_exists( 'vc_restaurant_is_open' ) ? vc_restaurant_is_open( get_the_ID() ) : null;
                        $orders_count  = (int) get_post_meta( get_the_ID(), 'vc_restaurant_orders_count', true );
                        $delivery_eta  = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_eta', true );
                        $delivery_fee  = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_fee', true );
                        $delivery_type = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_type', true );
                        $free_shipping = get_post_meta( get_the_ID(), '_vc_delivery_free_above', true );

                        $banner_urls    = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_banners', true ) );
                        $highlight_tags = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_highlight_tags', true ) );
                        $menu_filters   = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_menu_filters', true ) );
                        $payment_methods = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_payment_methods', true ) );
                        $facilities      = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_facilities', true ) );
                        $observations    = get_post_meta( get_the_ID(), 'vc_restaurant_observations', true );

                        $faq_raw   = (string) get_post_meta( get_the_ID(), 'vc_restaurant_faq', true );
                        $faq_lines = $list_from_text( $faq_raw );
                        $faq_items = [];
                        foreach ( $faq_lines as $line ) {
                                $parts = array_map( 'trim', explode( '|', $line, 2 ) );
                                if ( ! empty( $parts[0] ) ) {
                                        $faq_items[] = [
                                                'question' => $parts[0],
                                                'answer'   => $parts[1] ?? '',
                                        ];
                                }
                        }

                        $reservation_enabled = '1' === get_post_meta( get_the_ID(), 'vc_restaurant_reservation_enabled', true );
                        $reservation_link    = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_link', true );
                        $reservation_phone   = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_phone', true );
                        $reservation_notes   = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_notes', true );
                        $instagram_handle    = get_post_meta( get_the_ID(), 'vc_restaurant_instagram', true );

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
                                        <div class="vc-single__chips">
                                                <?php if ( $location_list ) : ?>
                                                        <span class="vc-chip vc-chip--muted"><?php echo esc_html( $location_list ); ?></span>
                                                <?php endif; ?>
                                                <span class="vc-chip vc-chip--status <?php echo '1' === $delivery_raw ? 'is-on' : 'is-off'; ?>">
                                                        <?php echo '1' === $delivery_raw ? esc_html__( 'Delivery ativo', 'vemcomer' ) : esc_html__( 'Somente retirada', 'vemcomer' ); ?>
                                                </span>
                                        </div>

                                        <?php if ( $banner_urls ) : ?>
                                                <div class="vc-single__banners">
                                                        <?php foreach ( $banner_urls as $banner_url ) : ?>
                                                                <img src="<?php echo esc_url( $banner_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Banner de %s', 'vemcomer' ), get_the_title() ) ); ?>" loading="lazy" />
                                                        <?php endforeach; ?>
                                                </div>
                                        <?php endif; ?>

                                        <div class="vc-single__metrics">
                                                <?php if ( ! empty( $rating_data['avg'] ) ) : ?>
                                                        <span class="vc-chip">★ <?php echo esc_html( number_format_i18n( (float) $rating_data['avg'], 1 ) ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( ! empty( $rating_data['count'] ) ) : ?>
                                                        <span class="vc-chip"><?php echo esc_html( sprintf( _n( '%s avaliação', '%s avaliações', (int) $rating_data['count'], 'vemcomer' ), number_format_i18n( (int) $rating_data['count'] ) ) ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $orders_count > 0 ) : ?>
                                                        <span class="vc-chip"><?php echo esc_html( sprintf( __( '%s pedidos', 'vemcomer' ), number_format_i18n( $orders_count ) ) ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $delivery_eta ) : ?>
                                                        <span class="vc-chip"><?php echo esc_html( $delivery_eta ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $delivery_fee ) : ?>
                                                        <span class="vc-chip"><?php echo esc_html( $delivery_fee ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( null !== $is_open ) : ?>
                                                        <span class="vc-chip" aria-live="polite"><?php echo $is_open ? esc_html__( 'Aberto agora', 'vemcomer' ) : esc_html__( 'Fechado no momento', 'vemcomer' ); ?></span>
                                                <?php endif; ?>
                                        </div>

                                        <div class="vc-single__badges">
                                                <?php if ( 'own' === $delivery_type ) : ?>
                                                        <span class="vc-badge-pill"><?php echo esc_html__( 'Entrega própria', 'vemcomer' ); ?></span>
                                                <?php elseif ( 'marketplace' === $delivery_type ) : ?>
                                                        <span class="vc-badge-pill"><?php echo esc_html__( 'Parceiro de entrega', 'vemcomer' ); ?></span>
                                                <?php elseif ( 'pickup' === $delivery_type ) : ?>
                                                        <span class="vc-badge-pill"><?php echo esc_html__( 'Retirada no local', 'vemcomer' ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $free_shipping ) : ?>
                                                        <?php
                                                        $free_shipping_label = function_exists( 'wc_price' )
                                                                ? wc_price( (float) $free_shipping )
                                                                : sprintf( 'R$ %s', number_format_i18n( (float) $free_shipping, 2 ) );
                                                        ?>
                                                        <span class="vc-badge-pill vc-badge-pill--accent"><?php echo esc_html( sprintf( __( 'Frete grátis acima de %s', 'vemcomer' ), $free_shipping_label ) ); ?></span>
                                                <?php endif; ?>
                                        </div>

                                        <?php if ( $highlight_tags ) : ?>
                                                <div class="vc-single__tags">
                                                        <?php foreach ( $highlight_tags as $tag ) : ?>
                                                                <span class="vc-single__tag"><?php echo esc_html( $tag ); ?></span>
                                                        <?php endforeach; ?>
                                                </div>
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

                                <div class="vc-single__menu-tools">
                                        <input type="search" class="vc-single__menu-search" placeholder="<?php echo esc_attr__( 'Buscar prato ou bebida...', 'vemcomer' ); ?>" aria-label="<?php echo esc_attr__( 'Buscar no cardápio', 'vemcomer' ); ?>" />
                                        <?php if ( $menu_filters ) : ?>
                                                <div class="vc-single__menu-filters" aria-label="<?php echo esc_attr__( 'Filtros rápidos do cardápio', 'vemcomer' ); ?>">
                                                        <?php foreach ( $menu_filters as $filter_label ) : ?>
                                                                <label><input type="checkbox" /> <?php echo esc_html( $filter_label ); ?></label>
                                                        <?php endforeach; ?>
                                                </div>
                                        <?php endif; ?>
                                </div>

                                <?php if ( $reservation_enabled ) : ?>
                                        <div class="vc-single__reservation">
                                                <div>
                                                        <strong><?php echo esc_html__( 'Reservar mesa', 'vemcomer' ); ?></strong>
                                                        <p style="margin:4px 0 0; color:#4a5b50;"><?php echo $reservation_notes ? wp_kses_post( $reservation_notes ) : esc_html__( 'Reserve online para eventos, aniversários ou grupos.', 'vemcomer' ); ?></p>
                                                </div>
                                                <button type="button" class="vc-btn" id="vc-open-reservation" data-target="#vc-reservation-modal"><?php echo esc_html__( 'Reservar', 'vemcomer' ); ?></button>
                                        </div>
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
                                <?php if ( $payment_methods || $instagram_handle || $facilities || $observations || $faq_items ) : ?>
                                        <div class="vc-single__info-panels">
                                                <?php if ( $payment_methods || $instagram_handle || $facilities ) : ?>
                                                        <div class="vc-single__panel">
                                                                <h3><?php echo esc_html__( 'Informações', 'vemcomer' ); ?></h3>
                                                                <?php if ( $payment_methods ) : ?>
                                                                        <p><strong><?php echo esc_html__( 'Pagamentos:', 'vemcomer' ); ?></strong> <?php echo esc_html( implode( ', ', $payment_methods ) ); ?></p>
                                                                <?php endif; ?>
                                                                <?php if ( $instagram_handle ) : ?>
                                                                        <p><strong><?php echo esc_html__( 'Instagram:', 'vemcomer' ); ?></strong> <a class="vc-link" href="<?php echo esc_url( ( false === strpos( $instagram_handle, 'http' ) ? 'https://instagram.com/' . ltrim( $instagram_handle, '@' ) : $instagram_handle ) ); ?>" target="_blank" rel="noopener">@<?php echo esc_html( ltrim( str_replace( 'https://instagram.com/', '', $instagram_handle ), '@' ) ); ?></a></p>
                                                                <?php endif; ?>
                                                                <?php if ( $facilities ) : ?>
                                                                        <div class="vc-single__tags" style="margin-top:6px;">
                                                                                <?php foreach ( $facilities as $facility ) : ?>
                                                                                        <span class="vc-single__tag"><?php echo esc_html( $facility ); ?></span>
                                                                                <?php endforeach; ?>
                                                                        </div>
                                                                <?php endif; ?>
                                                        </div>
                                                <?php endif; ?>

                                                <?php if ( $observations ) : ?>
                                                        <div class="vc-single__panel">
                                                                <h3><?php echo esc_html__( 'Observações', 'vemcomer' ); ?></h3>
                                                                <div><?php echo wp_kses_post( wpautop( $observations ) ); ?></div>
                                                        </div>
                                                <?php endif; ?>

                                                <?php if ( $faq_items ) : ?>
                                                        <div class="vc-single__panel">
                                                                <h3><?php echo esc_html__( 'FAQ', 'vemcomer' ); ?></h3>
                                                                <ul class="vc-faq">
                                                                        <?php foreach ( $faq_items as $faq ) : ?>
                                                                                <li>
                                                                                        <strong><?php echo esc_html( $faq['question'] ); ?></strong><br />
                                                                                        <span><?php echo esc_html( $faq['answer'] ); ?></span>
                                                                                </li>
                                                                        <?php endforeach; ?>
                                                                </ul>
                                                        </div>
                                                <?php endif; ?>
                                        </div>
                                <?php endif; ?>
                                <?php echo do_shortcode( '[vc_reviews restaurant_id="' . get_the_ID() . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </article>
                        <?php if ( $reservation_enabled ) : ?>
                                <div id="vc-reservation-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Reservar mesa', 'vemcomer' ); ?>">
                                        <div class="vc-modal__card">
                                                <button class="vc-modal__close" type="button" aria-label="<?php echo esc_attr__( 'Fechar', 'vemcomer' ); ?>">×</button>
                                                <h3 style="margin-top:0; color:#158943;"><?php echo esc_html__( 'Reservar mesa', 'vemcomer' ); ?></h3>
                                                <form id="vc-reservation-form">
                                                        <input type="text" name="name" required placeholder="<?php echo esc_attr__( 'Nome completo', 'vemcomer' ); ?>" />
                                                        <input type="tel" name="phone" required placeholder="<?php echo esc_attr__( 'Celular', 'vemcomer' ); ?>" />
                                                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                                <input type="date" name="date" required style="flex:1; min-width:140px;" />
                                                                <input type="time" name="time" required style="flex:1; min-width:120px;" />
                                                        </div>
                                                        <select name="people" required>
                                                                <option value=""><?php echo esc_html__( 'Nº de pessoas', 'vemcomer' ); ?></option>
                                                                <option>1</option>
                                                                <option>2</option>
                                                                <option>3</option>
                                                                <option>4</option>
                                                                <option>5</option>
                                                                <option>6+</option>
                                                        </select>
                                                        <textarea name="note" rows="3" placeholder="<?php echo esc_attr__( 'Comentário especial (opcional)', 'vemcomer' ); ?>"></textarea>
                                                        <button type="submit" class="vc-btn" style="width:100%; margin-top:4px;">
                                                                <?php echo esc_html__( 'Confirmar reserva', 'vemcomer' ); ?>
                                                        </button>
                                                </form>
                                                <p id="vc-reservation-feedback" style="display:none; color:#158943; font-weight:600; text-align:center; margin-top:8px;">
                                                        <?php echo esc_html__( 'Reserva enviada! Você será redirecionado.', 'vemcomer' ); ?>
                                                </p>
                                        </div>
                                </div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                        const modal = document.getElementById('vc-reservation-modal');
                                        const trigger = document.getElementById('vc-open-reservation');
                                        const closeBtn = modal ? modal.querySelector('.vc-modal__close') : null;
                                        const form = document.getElementById('vc-reservation-form');
                                        const feedback = document.getElementById('vc-reservation-feedback');
                                        const reservationLink = <?php echo wp_json_encode( $reservation_link ); ?>;
                                        const reservationPhone = <?php echo wp_json_encode( preg_replace( '/\D+/', '', (string) $reservation_phone ) ); ?>;

                                        const closeModal = () => {
                                                if ( modal ) {
                                                        modal.style.display = 'none';
                                                }
                                        };

                                        if ( trigger && modal ) {
                                                trigger.addEventListener( 'click', () => {
                                                        modal.style.display = 'flex';
                                                        feedback.style.display = 'none';
                                                } );
                                        }

                                        if ( closeBtn ) {
                                                closeBtn.addEventListener( 'click', closeModal );
                                        }

                                        if ( form ) {
                                                form.addEventListener( 'submit', function( event ) {
                                                        event.preventDefault();
                                                        const formData = new FormData( form );
                                                        const name = formData.get( 'name' ) || '';
                                                        const phone = formData.get( 'phone' ) || '';
                                                        const date = formData.get( 'date' ) || '';
                                                        const time = formData.get( 'time' ) || '';
                                                        const people = formData.get( 'people' ) || '';
                                                        const note = formData.get( 'note' ) || '';

                                                        const message = [
                                                                'Reserva solicitada:',
                                                                `Nome: ${ name }`,
                                                                `Celular: ${ phone }`,
                                                                `Data: ${ date }`,
                                                                `Hora: ${ time }`,
                                                                `Pessoas: ${ people }`,
                                                                note ? `Observações: ${ note }` : ''
                                                        ].filter(Boolean).join('\n');

                                                        if ( reservationLink ) {
                                                                window.open( reservationLink, '_blank' );
                                                        } else if ( reservationPhone ) {
                                                                const waUrl = `https://wa.me/${ reservationPhone }?text=${ encodeURIComponent( message ) }`;
                                                                window.open( waUrl, '_blank' );
                                                        }

                                                        if ( feedback ) {
                                                                feedback.style.display = 'block';
                                                        }
                                                        setTimeout( () => {
                                                                closeModal();
                                                                form.reset();
                                                                if ( feedback ) {
                                                                        feedback.style.display = 'none';
                                                                }
                                                        }, 1800 );
                                                } );
                                        }
                                });
                                </script>
                        <?php endif; ?>
                        <?php
                endwhile;
                ?>
        </div>
</main>

<nav class="vc-bottom-nav" aria-label="<?php echo esc_attr__( 'Navegação principal', 'vemcomer' ); ?>">
        <div class="vc-bottom-nav__inner">
                <a href="<?php echo esc_url( home_url( '/?mode=app' ) ); ?>">
                        <span aria-hidden="true">🏠</span>
                        <span><?php echo esc_html__( 'Início', 'vemcomer' ); ?></span>
                </a>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'vc_restaurant' ) ); ?>">
                        <span aria-hidden="true">🔍</span>
                        <span><?php echo esc_html__( 'Buscar', 'vemcomer' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/categorias/' ) ); ?>">
                        <span aria-hidden="true">🗂️</span>
                        <span><?php echo esc_html__( 'Categorias', 'vemcomer' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/meus-pedidos/' ) ); ?>">
                        <span aria-hidden="true">🧾</span>
                        <span><?php echo esc_html__( 'Pedidos', 'vemcomer' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/minha-conta/' ) ); ?>">
                        <span aria-hidden="true">👤</span>
                        <span><?php echo esc_html__( 'Perfil', 'vemcomer' ); ?></span>
                </a>
        </div>
</nav>

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
