<?php
/**
 * CPT_AnalyticsEvent — Custom Post Type para eventos de analytics
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_AnalyticsEvent {
	public const SLUG = 'vc_analytics_event';

	// Tipos de eventos
	public const EVENT_VIEW_RESTAURANT = 'view_restaurant';
	public const EVENT_VIEW_MENU       = 'view_menu';
	public const EVENT_CLICK_WHATSAPP  = 'click_whatsapp';
	public const EVENT_ADD_TO_CART     = 'add_to_cart';
	public const EVENT_CHECKOUT_START  = 'checkout_start';

	public function init(): void {
		add_action( 'init', [ $this, 'register_cpt' ] );
	}

	public function register_cpt(): void {
		$labels = [
			'name'          => __( 'Eventos de Analytics', 'vemcomer' ),
			'singular_name' => __( 'Evento', 'vemcomer' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false, // Não é público
			'show_ui'         => false, // Não mostrar no admin (será usado apenas programaticamente)
			'show_in_menu'    => false,
			'show_in_rest'    => false,
			'supports'        => [], // Sem suporte a campos padrão
			'capability_type' => 'post',
			'capabilities'    => [
				'create_posts' => 'do_not_allow', // Apenas código pode criar
			],
			'map_meta_cap'    => true,
		];
		register_post_type( self::SLUG, $args );
	}
}

