<?php
/**
 * Addresses_Controller — REST endpoints para endereços de entrega
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Utils\Addresses_Helper;
use VC\Utils\Geocoding_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Addresses_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista endereços do usuário
		register_rest_route( 'vemcomer/v1', '/addresses', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_addresses' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
		] );

		// POST: Criar endereço
		register_rest_route( 'vemcomer/v1', '/addresses', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_address' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
		] );

		// PATCH: Atualizar endereço
		register_rest_route( 'vemcomer/v1', '/addresses/(?P<id>[a-zA-Z0-9_]+)', [
			'methods'             => 'PATCH',
			'callback'            => [ $this, 'update_address' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// DELETE: Deletar endereço
		register_rest_route( 'vemcomer/v1', '/addresses/(?P<id>[a-zA-Z0-9_]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_address' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// POST: Definir endereço como principal
		register_rest_route( 'vemcomer/v1', '/addresses/(?P<id>[a-zA-Z0-9_]+)/set-primary', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'set_primary_address' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET /wp-json/vemcomer/v1/addresses
	 * Lista endereços do usuário autenticado
	 */
	public function get_addresses( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$addresses = Addresses_Helper::get_addresses( $user_id );

		return new WP_REST_Response( [
			'addresses' => $addresses,
			'total'     => count( $addresses ),
		], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/addresses
	 * Cria novo endereço
	 */
	public function create_address( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();

		$name        = sanitize_text_field( $request->get_param( 'name' ) );
		$street      = sanitize_text_field( $request->get_param( 'street' ) );
		$number      = sanitize_text_field( $request->get_param( 'number' ) );
		$complement  = sanitize_text_field( $request->get_param( 'complement' ) );
		$neighborhood = sanitize_text_field( $request->get_param( 'neighborhood' ) );
		$city        = sanitize_text_field( $request->get_param( 'city' ) );
		$zipcode     = sanitize_text_field( $request->get_param( 'zipcode' ) );
		$primary     = (bool) $request->get_param( 'primary' );

		if ( empty( $street ) || empty( $number ) || empty( $city ) ) {
			return new WP_Error( 'vc_invalid_address', __( 'Campos obrigatórios: rua, número e cidade.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Montar endereço completo para geocodificação
		$full_address = sprintf( '%s, %s, %s, %s', $street, $number, $neighborhood, $city );
		if ( $zipcode ) {
			$full_address .= ', ' . $zipcode;
		}

		// Geocodificar (se helper disponível)
		$lat = null;
		$lng = null;
		if ( class_exists( '\\VC\\Utils\\Geocoding_Helper' ) ) {
			$coords = Geocoding_Helper::geocode( $full_address );
			if ( $coords ) {
				$lat = $coords['lat'];
				$lng = $coords['lng'];
			}
		}

		$address = [
			'name'         => $name,
			'street'       => $street,
			'number'       => $number,
			'complement'   => $complement,
			'neighborhood' => $neighborhood,
			'city'         => $city,
			'zipcode'      => $zipcode,
			'lat'          => $lat,
			'lng'          => $lng,
			'primary'      => $primary,
		];

		$address_id = Addresses_Helper::add_address( $user_id, $address );

		return new WP_REST_Response( [
			'id'      => $address_id,
			'address' => Addresses_Helper::get_addresses( $user_id )[ count( Addresses_Helper::get_addresses( $user_id ) ) - 1 ],
		], 201 );
	}

	/**
	 * PATCH /wp-json/vemcomer/v1/addresses/{id}
	 * Atualiza endereço
	 */
	public function update_address( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		$address_id = $request->get_param( 'id' );

		$addresses = Addresses_Helper::get_addresses( $user_id );
		$found = false;
		foreach ( $addresses as $addr ) {
			if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new WP_Error( 'vc_address_not_found', __( 'Endereço não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$updates = [];
		if ( $request->has_param( 'name' ) ) {
			$updates['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->has_param( 'street' ) ) {
			$updates['street'] = sanitize_text_field( $request->get_param( 'street' ) );
		}
		if ( $request->has_param( 'number' ) ) {
			$updates['number'] = sanitize_text_field( $request->get_param( 'number' ) );
		}
		if ( $request->has_param( 'complement' ) ) {
			$updates['complement'] = sanitize_text_field( $request->get_param( 'complement' ) );
		}
		if ( $request->has_param( 'neighborhood' ) ) {
			$updates['neighborhood'] = sanitize_text_field( $request->get_param( 'neighborhood' ) );
		}
		if ( $request->has_param( 'city' ) ) {
			$updates['city'] = sanitize_text_field( $request->get_param( 'city' ) );
		}
		if ( $request->has_param( 'zipcode' ) ) {
			$updates['zipcode'] = sanitize_text_field( $request->get_param( 'zipcode' ) );
		}
		if ( $request->has_param( 'primary' ) ) {
			$updates['primary'] = (bool) $request->get_param( 'primary' );
		}

		if ( empty( $updates ) ) {
			return new WP_Error( 'vc_no_updates', __( 'Nenhum campo para atualizar.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Se endereço mudou, re-geocodificar
		if ( isset( $updates['street'] ) || isset( $updates['number'] ) || isset( $updates['neighborhood'] ) || isset( $updates['city'] ) ) {
			$current = Addresses_Helper::get_addresses( $user_id );
			foreach ( $current as $addr ) {
				if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
					$street = $updates['street'] ?? $addr['street'];
					$number = $updates['number'] ?? $addr['number'];
					$neighborhood = $updates['neighborhood'] ?? $addr['neighborhood'];
					$city = $updates['city'] ?? $addr['city'];
					$zipcode = $updates['zipcode'] ?? $addr['zipcode'];

					$full_address = sprintf( '%s, %s, %s, %s', $street, $number, $neighborhood, $city );
					if ( $zipcode ) {
						$full_address .= ', ' . $zipcode;
					}

					if ( class_exists( '\\VC\\Utils\\Geocoding_Helper' ) ) {
						$coords = Geocoding_Helper::geocode( $full_address );
						if ( $coords ) {
							$updates['lat'] = $coords['lat'];
							$updates['lng'] = $coords['lng'];
						}
					}
					break;
				}
			}
		}

		$success = Addresses_Helper::update_address( $user_id, $address_id, $updates );

		if ( ! $success ) {
			return new WP_Error( 'vc_update_failed', __( 'Erro ao atualizar endereço.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		// Retornar endereço atualizado
		$addresses = Addresses_Helper::get_addresses( $user_id );
		foreach ( $addresses as $addr ) {
			if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
				return new WP_REST_Response( [ 'address' => $addr ], 200 );
			}
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * DELETE /wp-json/vemcomer/v1/addresses/{id}
	 * Remove endereço
	 */
	public function delete_address( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		$address_id = $request->get_param( 'id' );

		$success = Addresses_Helper::remove_address( $user_id, $address_id );

		if ( ! $success ) {
			return new WP_Error( 'vc_address_not_found', __( 'Endereço não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/addresses/{id}/set-primary
	 * Define endereço como principal
	 */
	public function set_primary_address( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		$address_id = $request->get_param( 'id' );

		$success = Addresses_Helper::set_primary_address( $user_id, $address_id );

		if ( ! $success ) {
			return new WP_Error( 'vc_address_not_found', __( 'Endereço não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}
}

