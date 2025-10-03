<?php
/**
 * UI avançada na listagem: colunas ordenáveis e ajustes visuais.
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

add_filter(
        'manage_edit-vc_restaurant_sortable_columns',
        function( $columns ) {
                $columns['title']            = 'title';
                $columns['vc_has_delivery'] = 'vc_has_delivery';
                return $columns;
        }
);

add_action(
        'pre_get_posts',
        function( WP_Query $query ) {
                if ( ! is_admin() || ! $query->is_main_query() ) {
                        return;
                }

                if ( 'vc_restaurant' !== $query->get( 'post_type' ) ) {
                        return;
                }

                $orderby = $query->get( 'orderby' );
                if ( 'vc_has_delivery' === $orderby ) {
                        $query->set( 'meta_key', 'vc_restaurant_delivery' );
                        $query->set( 'orderby', 'meta_value' );
                }
        }
);

add_action(
        'admin_head-edit.php',
        function() {
                $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
                if ( $screen && 'edit-vc_restaurant' === $screen->id ) {
                        echo '<style>.column-vc_has_delivery{width:90px}</style>';
                }
        }
);
