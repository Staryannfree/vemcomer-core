<?php
/**
 * Image_Optimizer — Otimizador de imagens e geração de thumbnails
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Image_Optimizer {
	/**
	 * Tamanhos customizados para imagens do VemComer
	 */
	private const IMAGE_SIZES = [
		'vc_thumbnail' => [ 150, 150, true ],
		'vc_medium'     => [ 300, 300, true ],
		'vc_large'      => [ 800, 800, true ],
	];

	public function init(): void {
		// Registrar tamanhos customizados
		add_action( 'init', [ $this, 'register_image_sizes' ] );

		// Hook ao fazer upload de imagem
		add_action( 'add_attachment', [ $this, 'process_image' ] );
		add_action( 'edit_attachment', [ $this, 'process_image' ] );
	}

	/**
	 * Registra tamanhos customizados de imagem.
	 */
	public function register_image_sizes(): void {
		foreach ( self::IMAGE_SIZES as $name => $size ) {
			add_image_size( $name, $size[0], $size[1], $size[2] );
		}
	}

	/**
	 * Processa imagem ao fazer upload.
	 */
	public function process_image( int $attachment_id ): void {
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type || strpos( $mime_type, 'image/' ) !== 0 ) {
			return; // Não é uma imagem
		}

		// Gerar tamanhos
		$metadata = wp_generate_attachment_metadata( $attachment_id, get_attached_file( $attachment_id ) );
		if ( ! is_wp_error( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		// Salvar URLs dos tamanhos em meta
		$sizes = [];
		foreach ( array_keys( self::IMAGE_SIZES ) as $size_name ) {
			$image_url = wp_get_attachment_image_url( $attachment_id, $size_name );
			if ( $image_url ) {
				$sizes[ $size_name ] = $image_url;
			}
		}

		// Adicionar tamanhos padrão do WordPress também
		$sizes['thumbnail'] = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		$sizes['medium']    = wp_get_attachment_image_url( $attachment_id, 'medium' );
		$sizes['large']     = wp_get_attachment_image_url( $attachment_id, 'large' );
		$sizes['full']      = wp_get_attachment_image_url( $attachment_id, 'full' );

		update_post_meta( $attachment_id, '_vc_image_sizes', $sizes );
	}

	/**
	 * Obtém URLs de diferentes tamanhos de uma imagem.
	 *
	 * @param int $attachment_id ID do attachment
	 * @return array Array com URLs por tamanho
	 */
	public static function get_image_sizes( int $attachment_id ): array {
		$sizes = get_post_meta( $attachment_id, '_vc_image_sizes', true );
		if ( is_array( $sizes ) && ! empty( $sizes ) ) {
			return $sizes;
		}

		// Fallback: gerar URLs na hora
		$sizes = [];
		foreach ( array_keys( self::IMAGE_SIZES ) as $size_name ) {
			$image_url = wp_get_attachment_image_url( $attachment_id, $size_name );
			if ( $image_url ) {
				$sizes[ $size_name ] = $image_url;
			}
		}

		$sizes['thumbnail'] = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		$sizes['medium']    = wp_get_attachment_image_url( $attachment_id, 'medium' );
		$sizes['large']     = wp_get_attachment_image_url( $attachment_id, 'large' );
		$sizes['full']      = wp_get_attachment_image_url( $attachment_id, 'full' );

		return $sizes;
	}
}

