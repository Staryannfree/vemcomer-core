<?php
/**
 * CPT de Auditoria: vc_audit.
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

add_action(
        'init',
        function() {
                $labels = array(
                        'name'          => __( 'Auditoria', 'vemcomer' ),
                        'singular_name' => __( 'Evento', 'vemcomer' ),
                        'menu_name'     => __( 'Auditoria', 'vemcomer' ),
                );

                register_post_type(
                        'vc_audit',
                        array(
                                'labels'          => $labels,
                                'public'          => false,
                                'show_ui'         => true,
                                'show_in_menu'    => 'vemcomer-root',
                                'capability_type' => 'post',
                                'supports'        => array( 'title', 'editor' ),
                        )
                );
        }
);
