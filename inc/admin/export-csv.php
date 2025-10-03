<?php
/**
 * Exporta CSV da listagem de vc_restaurant respeitando filtros.
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

add_action(
        'admin_menu',
        function() {
                add_submenu_page(
                        'vemcomer-root',
                        __( 'Exportar CSV', 'vemcomer' ),
                        __( 'Exportar CSV', 'vemcomer' ),
                        'edit_posts',
                        'vc-export-csv',
                        'vc_export_csv_page'
                );
        }
);

/**
 * Renderiza a página de exportação.
 */
function vc_export_csv_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
                wp_die( esc_html__( 'Sem permissão.', 'vemcomer' ) );
        }

        if ( isset( $_GET['do'] ) && 'download' === $_GET['do'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Página interna sem alterações de dados.
                vc_export_csv_download();
                exit;
        }
        ?>
        <div class="wrap">
                <h1><?php esc_html_e( 'Exportar CSV – Restaurantes', 'vemcomer' ); ?></h1>
                <p><?php esc_html_e( 'O CSV respeita filtros de cozinha, bairro e delivery. Use o formulário abaixo e clique em "Baixar CSV".', 'vemcomer' ); ?></p>
                <form method="get">
                        <input type="hidden" name="page" value="vc-export-csv" />
                        <input type="hidden" name="do" value="download" />
                        <?php
                        $tax_cuisine = get_terms(
                                array(
                                        'taxonomy'   => 'vc_cuisine',
                                        'hide_empty' => false,
                                )
                        );
                        $tax_loc     = get_terms(
                                array(
                                        'taxonomy'   => 'vc_location',
                                        'hide_empty' => false,
                                )
                        );
                        ?>
                        <p>
                                <label>
                                        <?php esc_html_e( 'Cozinha', 'vemcomer' ); ?>
                                        <select name="cuisine">
                                                <option value=""><?php esc_html_e( 'Todas', 'vemcomer' ); ?></option>
                                                <?php foreach ( $tax_cuisine as $term ) : ?>
                                                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                </label>
                                <label style="margin-left:12px;">
                                        <?php esc_html_e( 'Bairro', 'vemcomer' ); ?>
                                        <select name="location">
                                                <option value=""><?php esc_html_e( 'Todos', 'vemcomer' ); ?></option>
                                                <?php foreach ( $tax_loc as $term ) : ?>
                                                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                </label>
                                <label style="margin-left:12px;">
                                        <?php esc_html_e( 'Delivery', 'vemcomer' ); ?>
                                        <select name="delivery">
                                                <option value=""><?php esc_html_e( 'Todos', 'vemcomer' ); ?></option>
                                                <option value="1"><?php esc_html_e( 'Com delivery', 'vemcomer' ); ?></option>
                                                <option value="0"><?php esc_html_e( 'Sem delivery', 'vemcomer' ); ?></option>
                                        </select>
                                </label>
                        </p>
                        <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Baixar CSV', 'vemcomer' ); ?></button></p>
                </form>
        </div>
        <?php
}

/**
 * Faz o download do CSV com filtros aplicados.
 */
function vc_export_csv_download() {
        $tax = array();

        if ( ! empty( $_GET['cuisine'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
                $tax[] = array(
                        'taxonomy' => 'vc_cuisine',
                        'field'    => 'slug',
                        'terms'    => sanitize_title( wp_unslash( $_GET['cuisine'] ) ),
                );
        }

        if ( ! empty( $_GET['location'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
                $tax[] = array(
                        'taxonomy' => 'vc_location',
                        'field'    => 'slug',
                        'terms'    => sanitize_title( wp_unslash( $_GET['location'] ) ),
                );
        }

        if ( count( $tax ) > 1 ) {
                $tax['relation'] = 'AND';
        }

        $meta = array();
        if ( isset( $_GET['delivery'] ) && '' !== $_GET['delivery'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de filtros.
                $meta[] = array(
                        'key'   => 'vc_restaurant_delivery',
                        'value' => '1' === $_GET['delivery'] ? '1' : '0',
                );
        }

        $query = new WP_Query(
                array(
                        'post_type'      => 'vc_restaurant',
                        'posts_per_page' => -1,
                        'tax_query'      => $tax ? $tax : '',
                        'meta_query'     => $meta ? $meta : '',
                        'fields'         => 'ids',
                )
        );

        $rows   = array();
        $rows[] = array( 'ID', 'Título', 'CNPJ', 'WhatsApp', 'Site', 'Horários', 'Delivery', 'Endereço', 'Cozinhas', 'Bairros' );

        foreach ( $query->posts as $pid ) {
                $rows[] = array(
                        $pid,
                        get_the_title( $pid ),
                        get_post_meta( $pid, 'vc_restaurant_cnpj', true ),
                        get_post_meta( $pid, 'vc_restaurant_whatsapp', true ),
                        get_post_meta( $pid, 'vc_restaurant_site', true ),
                        get_post_meta( $pid, 'vc_restaurant_open_hours', true ),
                        get_post_meta( $pid, 'vc_restaurant_delivery', true ) === '1' ? 'Sim' : 'Não',
                        get_post_meta( $pid, 'vc_restaurant_address', true ),
                        implode( ', ', wp_get_post_terms( $pid, 'vc_cuisine', array( 'fields' => 'names' ) ) ),
                        implode( ', ', wp_get_post_terms( $pid, 'vc_location', array( 'fields' => 'names' ) ) ),
                );
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=restaurants.csv' );

        $handle = fopen( 'php://output', 'w' );
        foreach ( $rows as $row ) {
                fputcsv( $handle, $row, ';' );
        }
        fclose( $handle );
}
