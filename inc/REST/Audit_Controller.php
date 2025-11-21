<?php
/**
 * Audit_Controller — REST endpoints para logs e auditoria
 * @package VemComerCore
 */

namespace VC\REST;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Audit_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista logs com filtros
		register_rest_route( 'vemcomer/v1', '/audit/logs', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_logs' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'type'      => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_key',
				],
				'user_id'   => [
					'required'          => false,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'start_date' => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'end_date'   => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'per_page'   => [
					'required'          => false,
					'default'           => 50,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'page'       => [
					'required'          => false,
					'default'           => 1,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// GET: Export logs para CSV
		register_rest_route( 'vemcomer/v1', '/audit/export', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'export_logs' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Ler arquivo de log se existir
		$uploads = wp_upload_dir();
		$log_file = trailingslashit( $uploads['basedir'] ) . 'vemcomer-debug.log';

		if ( ! file_exists( $log_file ) ) {
			return new WP_REST_Response( [ 'logs' => [], 'total' => 0 ], 200 );
		}

		$lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( false === $lines ) {
			return new WP_Error( 'vc_log_read_error', __( 'Erro ao ler arquivo de log.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		// Reverter ordem (mais recentes primeiro)
		$lines = array_reverse( $lines );

		// Filtrar
		$type = $request->get_param( 'type' );
		$user_id = $request->get_param( 'user_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date = $request->get_param( 'end_date' );

		$filtered = [];
		foreach ( $lines as $line ) {
			$parsed = $this->parse_log_line( $line );
			if ( ! $parsed ) {
				continue;
			}

			// Aplicar filtros
			if ( $type && $parsed['level'] !== $type ) {
				continue;
			}
			if ( $start_date && $parsed['timestamp'] < $start_date ) {
				continue;
			}
			if ( $end_date && $parsed['timestamp'] > $end_date ) {
				continue;
			}

			$filtered[] = $parsed;
		}

		// Paginação
		$per_page = (int) $request->get_param( 'per_page' );
		$page = (int) $request->get_param( 'page' );
		$total = count( $filtered );
		$offset = ( $page - 1 ) * $per_page;
		$logs = array_slice( $filtered, $offset, $per_page );

		return new WP_REST_Response( [
			'logs'      => $logs,
			'total'     => $total,
			'per_page'  => $per_page,
			'page'      => $page,
			'total_pages' => (int) ceil( $total / $per_page ),
		], 200 );
	}

	public function export_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$uploads = wp_upload_dir();
		$log_file = trailingslashit( $uploads['basedir'] ) . 'vemcomer-debug.log';

		if ( ! file_exists( $log_file ) ) {
			return new WP_Error( 'vc_log_not_found', __( 'Arquivo de log não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( false === $lines ) {
			return new WP_Error( 'vc_log_read_error', __( 'Erro ao ler arquivo de log.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		// Gerar CSV
		$csv = "Timestamp,Level,Message,Context\n";
		foreach ( $lines as $line ) {
			$parsed = $this->parse_log_line( $line );
			if ( ! $parsed ) {
				continue;
			}

			$csv .= sprintf(
				'"%s","%s","%s","%s"' . "\n",
				$parsed['timestamp'],
				$parsed['level'],
				str_replace( '"', '""', $parsed['message'] ),
				str_replace( '"', '""', $parsed['context'] ?? '' )
			);
		}

		// Retornar como download
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="vemcomer-audit-' . date( 'Y-m-d' ) . '.csv"' );
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Parse uma linha de log.
	 */
	private function parse_log_line( string $line ): ?array {
		// Formato: [timestamp] LEVEL message {context}
		if ( ! preg_match( '/^\[([^\]]+)\]\s+(\w+)\s+(.+?)(?:\s+(\{.*\}))?$/', $line, $matches ) ) {
			return null;
		}

		$timestamp = $matches[1] ?? '';
		$level = strtolower( $matches[2] ?? 'info' );
		$message = $matches[3] ?? '';
		$context_raw = $matches[4] ?? '{}';

		$context = json_decode( $context_raw, true );
		if ( ! is_array( $context ) ) {
			$context = [];
		}

		return [
			'timestamp' => $timestamp,
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		];
	}
}

