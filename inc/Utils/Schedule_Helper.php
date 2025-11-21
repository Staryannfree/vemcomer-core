<?php
/**
 * Schedule_Helper — Funções auxiliares para validação de horários de restaurantes
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Schedule_Helper {
	/**
	 * Verifica se um restaurante está aberto no momento especificado.
	 *
	 * @param int      $restaurant_id ID do restaurante
	 * @param int|null $timestamp     Timestamp Unix (null = agora)
	 * @return bool True se está aberto, false caso contrário
	 */
	public static function is_open( int $restaurant_id, ?int $timestamp = null ): bool {
		if ( $timestamp === null ) {
			$timestamp = time();
		}

		// Verificar se restaurante existe e está publicado
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type || 'publish' !== $restaurant->post_status ) {
			return false;
		}

		// Verificar se é feriado
		if ( self::is_holiday( $restaurant_id, $timestamp ) ) {
			return false;
		}

		// Obter horários estruturados
		$schedule_json = get_post_meta( $restaurant_id, '_vc_restaurant_schedule', true );
		if ( ! $schedule_json ) {
			// Fallback: usar campo legado _vc_is_open se não houver schedule
			$is_open_legacy = (bool) get_post_meta( $restaurant_id, '_vc_is_open', true );
			return $is_open_legacy;
		}

		$schedule = json_decode( $schedule_json, true );
		if ( ! is_array( $schedule ) ) {
			return false;
		}

		// Obter dia da semana (0 = domingo, 1 = segunda, ..., 6 = sábado)
		$day_of_week = (int) date( 'w', $timestamp );
		$day_names   = [
			0 => 'sunday',
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
		];
		$day_key = $day_names[ $day_of_week ];

		// Verificar se o dia está habilitado
		if ( ! isset( $schedule[ $day_key ] ) || ! isset( $schedule[ $day_key ]['enabled'] ) || ! $schedule[ $day_key ]['enabled'] ) {
			return false;
		}

		// Obter períodos do dia
		$periods = $schedule[ $day_key ]['periods'] ?? [];
		if ( empty( $periods ) ) {
			return false;
		}

		// Obter horário atual no timezone do WordPress
		$current_time = self::get_current_time( $timestamp );

		// Verificar se está em algum período
		foreach ( $periods as $period ) {
			$open_time  = isset( $period['open'] ) ? $period['open'] : '09:00';
			$close_time = isset( $period['close'] ) ? $period['close'] : '22:00';

			if ( self::is_time_between( $current_time, $open_time, $close_time ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Verifica se uma data é feriado para o restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @param int $timestamp     Timestamp Unix
	 * @return bool True se é feriado
	 */
	public static function is_holiday( int $restaurant_id, int $timestamp ): bool {
		$holidays_json = get_post_meta( $restaurant_id, '_vc_restaurant_holidays', true );
		if ( ! $holidays_json ) {
			return false;
		}

		$holidays = json_decode( $holidays_json, true );
		if ( ! is_array( $holidays ) ) {
			return false;
		}

		$date = date( 'Y-m-d', $timestamp );

		// Verificar se a data está na lista de feriados
		return in_array( $date, $holidays, true );
	}

	/**
	 * Obtém o horário atual formatado (HH:MM) no timezone do WordPress.
	 *
	 * @param int $timestamp Timestamp Unix
	 * @return string Horário no formato HH:MM
	 */
	private static function get_current_time( int $timestamp ): string {
		$timezone = wp_timezone();
		$datetime = new \DateTime( '@' . $timestamp );
		$datetime->setTimezone( $timezone );
		return $datetime->format( 'H:i' );
	}

	/**
	 * Verifica se um horário está entre dois outros horários.
	 *
	 * @param string $time  Horário atual (HH:MM)
	 * @param string $start Horário de início (HH:MM)
	 * @param string $end   Horário de fim (HH:MM)
	 * @return bool True se está entre os horários
	 */
	private static function is_time_between( string $time, string $start, string $end ): bool {
		$time_seconds  = self::time_to_seconds( $time );
		$start_seconds = self::time_to_seconds( $start );
		$end_seconds   = self::time_to_seconds( $end );

		// Se o horário de fim é menor que o de início, significa que passa da meia-noite
		if ( $end_seconds < $start_seconds ) {
			// Período que cruza a meia-noite (ex: 22:00 - 02:00)
			return $time_seconds >= $start_seconds || $time_seconds <= $end_seconds;
		}

		// Período normal (ex: 09:00 - 22:00)
		return $time_seconds >= $start_seconds && $time_seconds <= $end_seconds;
	}

	/**
	 * Converte horário HH:MM para segundos desde meia-noite.
	 *
	 * @param string $time Horário no formato HH:MM
	 * @return int Segundos desde meia-noite
	 */
	private static function time_to_seconds( string $time ): int {
		$parts = explode( ':', $time );
		if ( count( $parts ) !== 2 ) {
			return 0;
		}
		$hours   = (int) $parts[0];
		$minutes = (int) $parts[1];
		return ( $hours * 3600 ) + ( $minutes * 60 );
	}

	/**
	 * Obtém os horários estruturados de um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array Horários estruturados ou array vazio
	 */
	public static function get_schedule( int $restaurant_id ): array {
		$schedule_json = get_post_meta( $restaurant_id, '_vc_restaurant_schedule', true );
		if ( ! $schedule_json ) {
			return [];
		}

		$schedule = json_decode( $schedule_json, true );
		return is_array( $schedule ) ? $schedule : [];
	}

	/**
	 * Obtém o próximo horário de abertura de um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @param int $from_timestamp Timestamp de referência (null = agora)
	 * @return array|null Array com 'day', 'time' e 'timestamp' ou null se não houver próximo horário
	 */
	public static function get_next_open_time( int $restaurant_id, ?int $from_timestamp = null ): ?array {
		if ( $from_timestamp === null ) {
			$from_timestamp = time();
		}

		$schedule = self::get_schedule( $restaurant_id );
		if ( empty( $schedule ) ) {
			return null;
		}

		$day_names = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];
		$current_day = (int) date( 'w', $from_timestamp );
		$current_time = self::get_current_time( $from_timestamp );

		// Verificar nos próximos 7 dias
		for ( $days_ahead = 0; $days_ahead < 7; $days_ahead++ ) {
			$check_day_index = ( $current_day + $days_ahead ) % 7;
			$day_key         = $day_names[ $check_day_index ];
			$check_timestamp = $from_timestamp + ( $days_ahead * DAY_IN_SECONDS );

			if ( ! isset( $schedule[ $day_key ] ) || ! $schedule[ $day_key ]['enabled'] ) {
				continue;
			}

			$periods = $schedule[ $day_key ]['periods'] ?? [];
			foreach ( $periods as $period ) {
				$open_time = $period['open'] ?? '09:00';

				// Se for hoje, verificar se já passou do horário de abertura
				if ( $days_ahead === 0 ) {
					if ( self::time_to_seconds( $current_time ) < self::time_to_seconds( $open_time ) ) {
						// Ainda não abriu hoje
						$open_datetime = new \DateTime( date( 'Y-m-d', $from_timestamp ) . ' ' . $open_time, wp_timezone() );
						return [
							'day'       => $day_key,
							'time'      => $open_time,
							'timestamp' => $open_datetime->getTimestamp(),
						];
					}
				} else {
					// Próximo dia
					$open_datetime = new \DateTime( date( 'Y-m-d', $check_timestamp ) . ' ' . $open_time, wp_timezone() );
					return [
						'day'       => $day_key,
						'time'      => $open_time,
						'timestamp' => $open_datetime->getTimestamp(),
					];
				}
			}
		}

		return null;
	}
}

/**
 * Função helper global para verificar se restaurante está aberto.
 *
 * @param int      $restaurant_id ID do restaurante
 * @param int|null $timestamp     Timestamp Unix (null = agora)
 * @return bool
 */
if ( ! function_exists( 'vc_restaurant_is_open' ) ) {
	function vc_restaurant_is_open( int $restaurant_id, ?int $timestamp = null ): bool {
		return \VC\Utils\Schedule_Helper::is_open( $restaurant_id, $timestamp );
	}
}

