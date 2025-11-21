<?php
/**
 * Rating_Helper — Funções auxiliares para cálculo de ratings agregados
 * @package VemComerCore
 */

namespace VC\Utils;

use VC\Model\CPT_Review;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Rating_Helper {
	/**
	 * Cache duration em segundos (1 hora)
	 */
	private const CACHE_DURATION = 3600;

	/**
	 * Obtém o rating agregado de um restaurante (média e total de avaliações).
	 * Usa cache com transient para melhor performance.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array{avg: float, count: int, formatted: string} Array com média, total e formato formatado
	 */
	public static function get_rating( int $restaurant_id ): array {
		// Verificar cache primeiro
		$cache_key = 'vc_restaurant_rating_' . $restaurant_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Buscar todas as avaliações aprovadas do restaurante
		$reviews = get_posts( [
			'post_type'      => CPT_Review::SLUG,
			'posts_per_page' => -1,
			'post_status'    => CPT_Review::STATUS_APPROVED,
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
		] );

		$ratings = [];
		foreach ( $reviews as $review ) {
			$rating = (int) get_post_meta( $review->ID, '_vc_rating', true );
			if ( $rating >= 1 && $rating <= 5 ) {
				$ratings[] = $rating;
			}
		}

		$count = count( $ratings );
		$avg   = $count > 0 ? round( array_sum( $ratings ) / $count, 2 ) : 0.0;

		// Formatar rating (ex: "4.5 (12 avaliações)")
		$formatted = $count > 0
			? sprintf( '%.1f (%d %s)', $avg, $count, _n( 'avaliação', 'avaliações', $count, 'vemcomer' ) )
			: __( 'Sem avaliações', 'vemcomer' );

		$result = [
			'avg'       => $avg,
			'count'     => $count,
			'formatted' => $formatted,
		];

		// Salvar no cache
		set_transient( $cache_key, $result, self::CACHE_DURATION );

		// Atualizar meta fields do restaurante (para compatibilidade)
		update_post_meta( $restaurant_id, '_vc_restaurant_rating_avg', $avg );
		update_post_meta( $restaurant_id, '_vc_restaurant_rating_count', $count );

		return $result;
	}

	/**
	 * Invalida o cache de rating de um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 */
	public static function invalidate_cache( int $restaurant_id ): void {
		$cache_key = 'vc_restaurant_rating_' . $restaurant_id;
		delete_transient( $cache_key );
	}

	/**
	 * Recalcula e atualiza o rating agregado de um restaurante.
	 * Útil quando uma avaliação é aprovada/rejeitada.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array{avg: float, count: int, formatted: string} Array com média, total e formato formatado
	 */
	public static function recalculate( int $restaurant_id ): array {
		// Invalidar cache primeiro
		self::invalidate_cache( $restaurant_id );

		// Recalcular (isso vai atualizar o cache e as meta fields)
		return self::get_rating( $restaurant_id );
	}

	/**
	 * Obtém apenas a média do rating (float).
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return float Média do rating (0.0 a 5.0)
	 */
	public static function get_average( int $restaurant_id ): float {
		$rating = self::get_rating( $restaurant_id );
		return $rating['avg'];
	}

	/**
	 * Obtém apenas o total de avaliações (int).
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return int Total de avaliações aprovadas
	 */
	public static function get_count( int $restaurant_id ): int {
		$rating = self::get_rating( $restaurant_id );
		return $rating['count'];
	}
}

/**
 * Função helper global para obter rating de restaurante.
 *
 * @param int $restaurant_id ID do restaurante
 * @return array{avg: float, count: int, formatted: string} Array com média, total e formato formatado
 */
if ( ! function_exists( 'vc_restaurant_get_rating' ) ) {
	function vc_restaurant_get_rating( int $restaurant_id ): array {
		return \VC\Utils\Rating_Helper::get_rating( $restaurant_id );
	}
}

