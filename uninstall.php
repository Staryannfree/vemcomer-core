<?php
/**
 * Limpeza opcional do CPT de auditoria ao desinstalar o plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
}

$posts = get_posts(
        array(
                'post_type'      => 'vc_audit',
                'posts_per_page' => -1,
                'fields'         => 'ids',
        )
);

foreach ( $posts as $pid ) {
        wp_delete_post( $pid, true );
}
