<?php
/**
 * Tracking_Middleware — Hooks automáticos para tracking de eventos
 * @package VemComerCore
 */

namespace VC\Analytics;

use VC\Model\CPT_Restaurant;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tracking_Middleware {
	public function init(): void {
		// Tracking de visualização de restaurante (front-end)
		add_action( 'template_redirect', [ $this, 'track_restaurant_view' ], 20 );

		// Tracking de visualização de cardápio (front-end)
		add_action( 'template_redirect', [ $this, 'track_menu_view' ], 20 );

		// Tracking via REST API (visualizações de restaurante)
		add_action( 'rest_api_init', [ $this, 'track_rest_api_views' ] );

		// JavaScript para tracking de cliques WhatsApp e add to cart
		add_action( 'wp_footer', [ $this, 'enqueue_tracking_script' ] );
	}

	/**
	 * Tracka visualização de restaurante no front-end.
	 */
	public function track_restaurant_view(): void {
		if ( ! is_singular( CPT_Restaurant::SLUG ) ) {
			return;
		}

		$restaurant_id = get_queried_object_id();
		if ( ! $restaurant_id ) {
			return;
		}

		$customer_id = is_user_logged_in() ? get_current_user_id() : 0;

		Event_Logger::log_view_restaurant( $restaurant_id, $customer_id );
	}

	/**
	 * Tracka visualização de cardápio (quando há parâmetro restaurant_id).
	 */
	public function track_menu_view(): void {
		// Verificar se estamos em uma página que exibe cardápio
		$restaurant_id = isset( $_GET['restaurant_id'] ) ? (int) $_GET['restaurant_id'] : 0;
		if ( $restaurant_id <= 0 ) {
			return;
		}

		// Verificar se o restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return;
		}

		$customer_id = is_user_logged_in() ? get_current_user_id() : 0;

		Event_Logger::log_view_menu( $restaurant_id, $customer_id );
	}

	/**
	 * Tracka visualizações via REST API.
	 */
	public function track_rest_api_views(): void {
		// Hook em get_restaurants para trackear visualizações na lista
		add_filter( 'rest_prepare_' . CPT_Restaurant::SLUG, function( $response, $post, $request ) {
			// Apenas trackear se for GET request e não for admin
			if ( 'GET' === $request->get_method() && ! current_user_can( 'manage_options' ) ) {
				$customer_id = is_user_logged_in() ? get_current_user_id() : 0;
				Event_Logger::log_view_restaurant( $post->ID, $customer_id );
			}
			return $response;
		}, 10, 3 );

		// Hook em get_menu_items para trackear visualizações de cardápio
		add_filter( 'rest_prepare_' . CPT_Restaurant::SLUG . '_menu_items', function( $response, $post, $request ) {
			if ( 'GET' === $request->get_method() && ! current_user_can( 'manage_options' ) ) {
				$restaurant_id = (int) $request->get_param( 'id' );
				if ( $restaurant_id > 0 ) {
					$customer_id = is_user_logged_in() ? get_current_user_id() : 0;
					Event_Logger::log_view_menu( $restaurant_id, $customer_id );
				}
			}
			return $response;
		}, 10, 3 );
	}

	/**
	 * Enfileira script JavaScript para tracking de cliques.
	 */
	public function enqueue_tracking_script(): void {
		// Apenas em páginas relevantes
		if ( ! is_singular( CPT_Restaurant::SLUG ) && ! isset( $_GET['restaurant_id'] ) ) {
			return;
		}

		$restaurant_id = is_singular( CPT_Restaurant::SLUG ) ? get_queried_object_id() : (int) ( $_GET['restaurant_id'] ?? 0 );
		if ( $restaurant_id <= 0 ) {
			return;
		}

		$customer_id = is_user_logged_in() ? get_current_user_id() : 0;
		?>
		<script type="text/javascript">
		(function() {
			'use strict';
			
			var restaurantId = <?php echo esc_js( $restaurant_id ); ?>;
			var customerId = <?php echo esc_js( $customer_id ); ?>;
			var apiUrl = '<?php echo esc_url( rest_url( 'vemcomer/v1/analytics/track' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

			// Função para enviar evento
			function trackEvent(eventType, metadata) {
				if (typeof navigator.sendBeacon !== 'undefined') {
					// Usar sendBeacon para não bloquear navegação
					var data = new FormData();
					data.append('event_type', eventType);
					data.append('restaurant_id', restaurantId);
					data.append('customer_id', customerId);
					if (metadata) {
						data.append('metadata', JSON.stringify(metadata));
					}
					data.append('_wpnonce', nonce);
					navigator.sendBeacon(apiUrl, data);
				} else {
					// Fallback para fetch
					fetch(apiUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
							'X-WP-Nonce': nonce
						},
						body: new URLSearchParams({
							event_type: eventType,
							restaurant_id: restaurantId,
							customer_id: customerId,
							metadata: metadata ? JSON.stringify(metadata) : ''
						})
					}).catch(function() {
						// Ignorar erros silenciosamente
					});
				}
			}

			// Trackar cliques no WhatsApp
			document.addEventListener('click', function(e) {
				var target = e.target.closest('a[href*="wa.me"], a[href*="whatsapp.com"], a[href*="api.whatsapp.com"]');
				if (target && restaurantId > 0) {
					trackEvent('click_whatsapp', {
						url: target.href
					});
				}
			});

			// Trackar adições ao carrinho (se houver botão com data-action="add-to-cart")
			document.addEventListener('click', function(e) {
				var target = e.target.closest('[data-action="add-to-cart"], .add-to-cart, [data-add-to-cart]');
				if (target && restaurantId > 0) {
					var menuItemId = target.getAttribute('data-menu-item-id') || target.closest('[data-menu-item-id]')?.getAttribute('data-menu-item-id') || 0;
					trackEvent('add_to_cart', {
						menu_item_id: parseInt(menuItemId) || 0
					});
				}
			});

			// Trackar início de checkout (se houver botão com data-action="checkout")
			document.addEventListener('click', function(e) {
				var target = e.target.closest('[data-action="checkout"], .checkout-button, [data-checkout]');
				if (target && restaurantId > 0) {
					trackEvent('checkout_start', {});
				}
			});
		})();
		</script>
		<?php
	}
}

