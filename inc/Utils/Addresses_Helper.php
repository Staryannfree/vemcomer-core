<?php
/**
 * Addresses_Helper — Funções auxiliares para gerenciar endereços de entrega
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Addresses_Helper {
	/**
	 * Meta key para endereços de entrega
	 */
	public const META_ADDRESSES = 'vc_delivery_addresses';

	/**
	 * Obtém lista de endereços do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return array Array de endereços
	 */
	public static function get_addresses( int $user_id ): array {
		$addresses = get_user_meta( $user_id, self::META_ADDRESSES, true );
		if ( ! is_array( $addresses ) ) {
			return [];
		}
		return $addresses;
	}

	/**
	 * Obtém endereço principal do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return array|null Endereço principal ou null
	 */
	public static function get_primary_address( int $user_id ): ?array {
		$addresses = self::get_addresses( $user_id );
		foreach ( $addresses as $address ) {
			if ( isset( $address['primary'] ) && $address['primary'] ) {
				return $address;
			}
		}
		return ! empty( $addresses ) ? $addresses[0] : null;
	}

	/**
	 * Adiciona endereço ao usuário.
	 *
	 * @param int   $user_id ID do usuário
	 * @param array $address Dados do endereço
	 * @return int ID do endereço (índice no array)
	 */
	public static function add_address( int $user_id, array $address ): int {
		$addresses = self::get_addresses( $user_id );

		// Gerar ID único
		$address_id = time() . '_' . wp_generate_password( 6, false );

		// Se for marcado como principal, remover principal dos outros
		if ( isset( $address['primary'] ) && $address['primary'] ) {
			foreach ( $addresses as &$addr ) {
				$addr['primary'] = false;
			}
		} else {
			// Se não há endereços, este será o principal
			if ( empty( $addresses ) ) {
				$address['primary'] = true;
			}
		}

		$address['id'] = $address_id;
		$addresses[] = $address;

		update_user_meta( $user_id, self::META_ADDRESSES, $addresses );

		return $address_id;
	}

	/**
	 * Atualiza endereço do usuário.
	 *
	 * @param int   $user_id ID do usuário
	 * @param string $address_id ID do endereço
	 * @param array $address Dados atualizados
	 * @return bool True se atualizado, false se não encontrado
	 */
	public static function update_address( int $user_id, string $address_id, array $address ): bool {
		$addresses = self::get_addresses( $user_id );

		foreach ( $addresses as &$addr ) {
			if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
				// Se for marcado como principal, remover principal dos outros
				if ( isset( $address['primary'] ) && $address['primary'] ) {
					foreach ( $addresses as &$a ) {
						if ( $a['id'] !== $address_id ) {
							$a['primary'] = false;
						}
					}
				}

				$address['id'] = $address_id;
				$addr = array_merge( $addr, $address );
				update_user_meta( $user_id, self::META_ADDRESSES, $addresses );
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove endereço do usuário.
	 *
	 * @param int    $user_id ID do usuário
	 * @param string $address_id ID do endereço
	 * @return bool True se removido, false se não encontrado
	 */
	public static function remove_address( int $user_id, string $address_id ): bool {
		$addresses = self::get_addresses( $user_id );

		foreach ( $addresses as $key => $addr ) {
			if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
				unset( $addresses[ $key ] );
				$addresses = array_values( $addresses ); // Reindexar

				// Se era o principal e há outros, tornar o primeiro principal
				if ( isset( $addr['primary'] ) && $addr['primary'] && ! empty( $addresses ) ) {
					$addresses[0]['primary'] = true;
				}

				update_user_meta( $user_id, self::META_ADDRESSES, $addresses );
				return true;
			}
		}

		return false;
	}

	/**
	 * Define endereço como principal.
	 *
	 * @param int    $user_id ID do usuário
	 * @param string $address_id ID do endereço
	 * @return bool True se definido, false se não encontrado
	 */
	public static function set_primary_address( int $user_id, string $address_id ): bool {
		$addresses = self::get_addresses( $user_id );

		$found = false;
		foreach ( $addresses as &$addr ) {
			if ( isset( $addr['id'] ) && $addr['id'] === $address_id ) {
				$addr['primary'] = true;
				$found = true;
			} else {
				$addr['primary'] = false;
			}
		}

		if ( $found ) {
			update_user_meta( $user_id, self::META_ADDRESSES, $addresses );
			return true;
		}

		return false;
	}
}

