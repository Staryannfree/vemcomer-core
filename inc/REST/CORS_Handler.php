<?php
/**
 * CORS_Handler — Gerenciador de CORS headers para REST API
 * Permite requisições cross-origin do frontend PWA
 * 
 * @package VemComerCore
 */

namespace VC\REST;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CORS_Handler {
	/**
	 * Inicializa o handler de CORS
	 */
	public function init(): void {
		// Remove o handler padrão do WordPress para ter controle total
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		
		// Adiciona nosso handler de CORS com prioridade alta
		add_filter( 'rest_pre_serve_request', [ $this, 'handle_preflight' ], 0, 4 );
		add_filter( 'rest_pre_serve_request', [ $this, 'add_cors_headers' ], 10, 4 );
		
		// Também adiciona hook no init para garantir que funcione mesmo fora do contexto REST
		add_action( 'init', [ $this, 'handle_options_preflight' ], 1 );
	}
	
	/**
	 * Manipula requisições OPTIONS (preflight) no hook init
	 * Garante que funcione mesmo antes do WordPress processar a requisição REST
	 */
	public function handle_options_preflight(): void {
		// Apenas processa requisições OPTIONS para endpoints REST
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'OPTIONS' ) {
			return;
		}
		
		// Verifica se é uma requisição para a API REST
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $request_uri, '/wp-json/vemcomer/v1/' ) === false ) {
			return;
		}
		
		$origin = $this->get_request_origin();
		if ( ! $origin ) {
			return;
		}
		
		$allowed_origins = $this->get_allowed_origins();
		
		// Verifica se a origem é permitida
		if ( ! in_array( $origin, $allowed_origins, true ) && ! in_array( '*', $allowed_origins, true ) ) {
			return;
		}
		
		// Define a origem permitida (ou * se configurado)
		$allowed_origin = in_array( '*', $allowed_origins, true ) ? '*' : $origin;
		
		// Headers para preflight
		header( 'Access-Control-Allow-Origin: ' . $allowed_origin );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Max-Age: 86400' );
		header( 'Content-Length: 0' );
		header( 'Content-Type: text/plain' );
		
		// Retorna 200 OK para preflight
		status_header( 200 );
		exit;
	}

	/**
	 * Adiciona headers CORS nas respostas REST
	 * 
	 * @param bool $served Se a requisição já foi servida
	 * @param WP_REST_Response $result Resultado da requisição
	 * @param WP_REST_Request $request Objeto da requisição
	 * @param WP_REST_Server $server Objeto do servidor REST
	 * @return bool
	 */
	public function add_cors_headers( $served, $result, $request, $server ) {
		// Não processa se já foi servido ou se for OPTIONS (preflight é tratado separadamente)
		if ( $served || $request->get_method() === 'OPTIONS' ) {
			return $served;
		}

		$origin = $this->get_request_origin();
		$allowed_origins = $this->get_allowed_origins();
		
		// Se não há origem (requisição do mesmo domínio), não precisa de CORS
		if ( ! $origin ) {
			return $served;
		}
		
		// Verifica se a origem é permitida
		if ( ! in_array( $origin, $allowed_origins, true ) && ! in_array( '*', $allowed_origins, true ) ) {
			return $served;
		}

		// Define a origem permitida (ou * se configurado)
		$allowed_origin = in_array( '*', $allowed_origins, true ) ? '*' : $origin;

		// Adiciona headers CORS
		header( 'Access-Control-Allow-Origin: ' . $allowed_origin );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Max-Age: 86400' ); // 24 horas

		return $served;
	}

	/**
	 * Manipula requisições OPTIONS (preflight)
	 * 
	 * @param bool $served Se a requisição já foi servida
	 * @param WP_REST_Response $result Resultado da requisição
	 * @param WP_REST_Request $request Objeto da requisição
	 * @param WP_REST_Server $server Objeto do servidor REST
	 * @return bool
	 */
	public function handle_preflight( $served, $result, $request, $server ) {
		if ( $request->get_method() !== 'OPTIONS' ) {
			return $served;
		}

		$origin = $this->get_request_origin();
		if ( ! $origin ) {
			return $served;
		}

		$allowed_origins = $this->get_allowed_origins();
		
		// Verifica se a origem é permitida
		if ( ! in_array( $origin, $allowed_origins, true ) && ! in_array( '*', $allowed_origins, true ) ) {
			return $served;
		}

		// Define a origem permitida (ou * se configurado)
		$allowed_origin = in_array( '*', $allowed_origins, true ) ? '*' : $origin;

		// Headers para preflight
		header( 'Access-Control-Allow-Origin: ' . $allowed_origin );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Max-Age: 86400' );
		header( 'Content-Length: 0' );
		header( 'Content-Type: text/plain' );

		// Retorna 200 OK para preflight
		status_header( 200 );
		exit;
	}

	/**
	 * Obtém a origem da requisição
	 * 
	 * @return string|null
	 */
	private function get_request_origin(): ?string {
		// Usa a função do WordPress que é mais segura
		$origin = get_http_origin();
		
		if ( ! $origin ) {
			// Fallback para $_SERVER se get_http_origin não retornar nada
			if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
				$origin = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
			}
		}
		
		return $origin ?: null;
	}

	/**
	 * Obtém as origens permitidas via filtro
	 * 
	 * @return array
	 */
	private function get_allowed_origins(): array {
		$default_origins = [
			'http://localhost:3000',
			'http://localhost:5173',
			'http://localhost:8080',
			'http://127.0.0.1:3000',
			'http://127.0.0.1:5173',
			'http://pedevem-local.local', // Ambiente local Local by Flywheel
			'https://hungry-hub-core.lovable.app', // Frontend Lovable (produção)
			'https://47191717-b1f5-4559-bdab-f069bc62cec6.lovableproject.com', // Frontend Lovable (desenvolvimento)
			'https://id-preview--47191717-b1f5-4559-bdab-f069bc62cec6.lovable.app', // Frontend Lovable (preview)
			'https://periodic-symbol.localsite.io', // Live Link do Local by Flywheel
		];

		/**
		 * Filtro para configurar origens permitidas no CORS
		 * 
		 * @param array $origins Lista de origens permitidas
		 * @return array
		 */
		$origins = apply_filters( 'vemcomer_rest_allowed_origins', $default_origins );

		// Garante que é um array
		if ( ! is_array( $origins ) ) {
			return $default_origins;
		}

		return $origins;
	}
}
