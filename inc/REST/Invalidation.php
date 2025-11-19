<?php
/**
 * Invalidation — Invalidação simples do cache quando restaurantes/itens mudam
 * @package VemComerCore
 */

namespace VC\REST;

use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Invalidation {
    public function init(): void {
        // Sempre que salvar restaurante ou item do cardápio, limpa transients relacionados
        add_action( 'save_post_vc_restaurant', [ $this, 'flush_all' ] );
        add_action( 'save_post_vc_menu_item', [ $this, 'flush_all' ] );
        add_action( 'deleted_post', [ $this, 'flush_all' ] );
        add_action( 'trashed_post', [ $this, 'flush_all' ] );
    }

    public function flush_all(): void {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vc_rest_cache_%' OR option_name LIKE '_transient_timeout_vc_rest_cache_%'" );
        log_event( 'REST cache invalidated', [], 'info' );
    }
}
