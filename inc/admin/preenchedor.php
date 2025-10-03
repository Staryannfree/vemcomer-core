<?php
/**
 * Submenu Admin – Preenchedor de dados (seed) para testes
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once dirname( __DIR__ ) . '/seed/Seeder.php';

add_action( 'admin_menu', static function() {
    global $admin_page_hooks;
    if ( ! isset( $admin_page_hooks['vemcomer-root'] ) ) {
        add_menu_page( 'VemComer', 'VemComer', 'edit_posts', 'vemcomer-root', '__return_null', 'dashicons-store', 25 );
    }
    add_submenu_page( 'vemcomer-root', 'Preenchedor', 'Preenchedor', 'edit_posts', 'vc-preenchedor', 'vc_render_preenchedor' );
});

add_action( 'admin_enqueue_scripts', static function( $hook ) {
    if ( 'vemcomer-root_page_vc-preenchedor' !== $hook ) {
        return;
    }

    if ( defined( 'VEMCOMER_CORE_DIR' ) && file_exists( VEMCOMER_CORE_DIR . 'assets/css/preenchedor.css' ) ) {
        wp_enqueue_style(
            'vc-preenchedor',
            defined( 'VEMCOMER_CORE_URL' ) ? VEMCOMER_CORE_URL . 'assets/css/preenchedor.css' : plugins_url( '../../assets/css/preenchedor.css', __FILE__ ),
            [],
            defined( 'VEMCOMER_CORE_VERSION' ) ? VEMCOMER_CORE_VERSION : '1.0.0'
        );
    }
});

function vc_render_preenchedor() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'Sem permissão.', 'vemcomer' ) );
    }

    $created_message = '';
    $notice_class    = 'notice-success';

    if ( isset( $_POST['vc_filler_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vc_filler_nonce'] ) ), 'vc_filler_action' ) ) {
        $count_raw = isset( $_POST['vc_qtd_restaurantes'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_qtd_restaurantes'] ) ) : '5';
        $qtd       = max( 1, (int) $count_raw );
        $with_items = isset( $_POST['vc_criar_itens'] );

        $seeder = new VC_Seeder();
        $ids    = $seeder->seed_restaurants( $qtd, ! empty( $_POST['vc_force'] ) );

        $total_items = 0;
        if ( $with_items ) {
            foreach ( $ids as $rid ) {
                $total_items += count( $seeder->seed_menu_items_for( $rid, 3, 6 ) );
            }
        }

        if ( empty( $ids ) ) {
            $created_message = __( 'Nenhum restaurante criado. Verifique se o CPT vc_restaurant está ativo.', 'vemcomer' );
            $notice_class    = 'notice-warning';
        } else {
            $created_message = sprintf(
                'Criados %d restaurantes%s.',
                count( $ids ),
                $with_items ? ' e ' . $total_items . ' itens' : ''
            );
        }
    }
    ?>
    <div class="wrap vc-preenchedor">
        <h1>Preenchedor de Dados</h1>
        <?php if ( $created_message ) : ?>
            <div class="notice <?php echo esc_attr( $notice_class ); ?>"><p><?php echo esc_html( $created_message ); ?></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'vc_filler_action', 'vc_filler_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="vc-qtd-restaurantes">Quantidade de restaurantes</label></th>
                    <td><input type="number" id="vc-qtd-restaurantes" name="vc_qtd_restaurantes" value="5" min="1" class="small-text" /></td>
                </tr>
                <tr>
                    <th>Itens de cardápio</th>
                    <td>
                        <label>
                            <input type="checkbox" name="vc_criar_itens" value="1" />
                            Criar itens (3–6 por restaurante) <em>(se o CPT <code>vc_menu_item</code> existir)</em>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Forçar recriação</th>
                    <td>
                        <label>
                            <input type="checkbox" name="vc_force" value="1" />
                            Apagar existentes antes de criar
                        </label>
                    </td>
                </tr>
            </table>
            <p><button class="button button-primary" type="submit">Executar</button></p>
        </form>
        <p class="description">Dica: você também pode usar via WP-CLI → <code>wp vemcomer seed-restaurants --count=5 --force</code></p>
    </div>
    <?php
}
