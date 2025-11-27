<?php
/**
 * Auto_Cache_Clear — Limpa cache automaticamente para admins/desenvolvimento
 * @package VemComerCore
 */

namespace VC\Cache;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Auto_Cache_Clear {
	/**
	 * Inicializa o sistema de limpeza automática de cache
	 */
	public function init(): void {
		// Limpar cache do plugin a cada acesso para admins
		if ( current_user_can( 'manage_options' ) ) {
			// Limpar transients do VemComer
			add_action( 'init', [ $this, 'clear_vemcomer_cache' ], 1 );
			
			// Adicionar headers no-cache para admin
			add_action( 'init', [ $this, 'add_no_cache_headers' ], 1 );
			
			// Limpar cache do LiteSpeed se disponível
			add_action( 'init', [ $this, 'clear_litespeed_cache' ], 1 );
		}
		
		// Limpar cache em modo de desenvolvimento (se WP_DEBUG estiver ativo)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'init', [ $this, 'clear_vemcomer_cache' ], 1 );
			add_action( 'init', [ $this, 'add_no_cache_headers' ], 1 );
		}
		
		// Limpar cache REST API para admins
		add_filter( 'rest_request_before_callbacks', [ $this, 'maybe_clear_rest_cache' ], 1, 3 );
	}
	
	/**
	 * Limpa todos os transients do VemComer
	 */
	public function clear_vemcomer_cache(): void {
		global $wpdb;
		
		// Limpar transients do VemComer
		$wpdb->query( 
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_vc_%' 
			OR option_name LIKE '_transient_timeout_vc_%'"
		);
		
		// Limpar cache REST
		$wpdb->query( 
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_vc_rest_cache_%' 
			OR option_name LIKE '_transient_timeout_vc_rest_cache_%'"
		);
	}
	
	/**
	 * Adiciona headers no-cache para evitar cache do navegador
	 */
	public function add_no_cache_headers(): void {
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}
	}
	
	/**
	 * Limpa cache do LiteSpeed Cache se o plugin estiver ativo
	 */
	public function clear_litespeed_cache(): void {
		// Verificar se LiteSpeed Cache está ativo
		if ( class_exists( '\LiteSpeed\Core' ) ) {
			// Limpar cache do LiteSpeed
			do_action( 'litespeed_purge_all' );
		}
		
		// Alternativa: usar função direta se disponível
		if ( function_exists( 'litespeed_purge_all' ) ) {
			litespeed_purge_all();
		}
		
		// Limpar via opção do LiteSpeed
		if ( class_exists( '\LiteSpeed\Purge' ) ) {
			\LiteSpeed\Purge::purge_all();
		}
	}
	
	/**
	 * Limpa cache REST antes de processar requisições para admins
	 */
	public function maybe_clear_rest_cache( $response, array $handler, \WP_REST_Request $request ): mixed {
		// Apenas para usuários logados como admin
		if ( current_user_can( 'manage_options' ) ) {
			// Limpar cache REST apenas uma vez por requisição
			static $cleared = false;
			if ( ! $cleared ) {
				global $wpdb;
				$wpdb->query( 
					"DELETE FROM {$wpdb->options} 
					WHERE option_name LIKE '_transient_vc_rest_cache_%' 
					OR option_name LIKE '_transient_timeout_vc_rest_cache_%'"
				);
				$cleared = true;
			}
		}
		
		return $response;
	}
	
	/**
	 * Limpa cache completo (pode ser chamado manualmente)
	 */
	public static function clear_all(): void {
		$instance = new self();
		$instance->clear_vemcomer_cache();
		$instance->clear_litespeed_cache();
		
		// Limpar cache do WordPress
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		
		// Limpar cache do objeto
		wp_cache_flush_group( 'vemcomer' );
	}
}

