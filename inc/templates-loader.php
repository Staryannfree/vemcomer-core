<?php
/**
 * Loader de templates para vc_restaurant.
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
        'template_include',
        function ( $template ) {
                if ( is_post_type_archive( 'vc_restaurant' ) ) {
                                $tpl = plugin_dir_path( __FILE__ ) . '../templates/archive-vc-restaurant.php';
                        if ( file_exists( $tpl ) ) {
                                        return $tpl;
                        }
                }
                if ( is_singular( 'vc_restaurant' ) ) {
                                $tpl = plugin_dir_path( __FILE__ ) . '../templates/single-vc-restaurant.php';
                        if ( file_exists( $tpl ) ) {
                                        return $tpl;
                        }
                }
                return $template;
        }
);

add_action(
        'wp_enqueue_scripts',
        function () {
                if ( ! is_singular( 'vc_restaurant' ) && ! is_post_type_archive( 'vc_restaurant' ) ) {
                        return;
                }

                wp_enqueue_style( 'vemcomer-front' );
                wp_enqueue_script( 'vemcomer-front' );
        },
        20
);
